<?php
/**
 * Layout Repository for Apollo Builder.
 *
 * Persists user page layouts including widgets, backgrounds, and stickers.
 *
 * @package Apollo\Modules\Builder
 * @since   1.0.0
 */

namespace Apollo\Modules\Builder;

use Apollo\Modules\Builder\Assets\BackgroundRegistry;
use Apollo\Modules\Builder\Assets\StickerRegistry;

/**
 * Persist layout data in user meta.
 */
class LayoutRepository {

	/**
	 * User meta key for layout storage.
	 *
	 * @var string
	 */
	public const META_KEY = 'apollo_builder_layout';

	/**
	 * Current layout schema version.
	 *
	 * @var int
	 */
	public const SCHEMA_VERSION = 2;

	/**
	 * Default background ID for new layouts.
	 *
	 * @var string
	 */
	public const DEFAULT_BACKGROUND = 'solid-white';

	/**
	 * Retrieve layout for a given user.
	 *
	 * Ensures backward compatibility by normalizing old layouts.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array Normalized layout data.
	 */
	public function getLayout( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return $this->emptyLayout();
		}

		$stored = get_user_meta( $user_id, self::META_KEY, true );

		if ( empty( $stored ) || ! is_array( $stored ) ) {
			return $this->emptyLayout();
		}

		$layout = wp_unslash( $stored );

		// Normalize for backward compatibility.
		return $this->normalizeLayout( $layout );
	}

	/**
	 * Persist layout for user.
	 *
	 * @param int   $user_id WordPress user ID.
	 * @param array $layout  Layout data to save.
	 * @return bool True on success, false on failure.
	 */
	public function saveLayout( int $user_id, array $layout ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		// Sanitize before saving.
		$layout = $this->sanitizeLayout( $layout );

		$layout['__last_updated'] = current_time( 'mysql' );
		$layout['__version']      = self::SCHEMA_VERSION;

		return (bool) update_user_meta( $user_id, self::META_KEY, wp_slash( $layout ) );
	}

	/**
	 * Remove stored layout.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	public function deleteLayout( int $user_id ): void {
		if ( $user_id > 0 ) {
			delete_user_meta( $user_id, self::META_KEY );
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Background Methods
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Get the active background for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array|null Background data or null if not found.
	 */
	public function getBackground( int $user_id ): ?array {
		$layout        = $this->getLayout( $user_id );
		$background_id = $layout['background']['id'] ?? self::DEFAULT_BACKGROUND;

		return BackgroundRegistry::get_by_id( $background_id );
	}

	/**
	 * Set the active background for a user.
	 *
	 * @param int    $user_id       WordPress user ID.
	 * @param string $background_id Background identifier.
	 * @return bool True on success, false on failure.
	 */
	public function setBackground( int $user_id, string $background_id ): bool {
		$layout = $this->getLayout( $user_id );

		// Validate background exists.
		$background = BackgroundRegistry::get_by_id( $background_id );
		if ( null === $background ) {
			return false;
		}

		$layout['background'] = array(
			'id'         => sanitize_key( $background_id ),
			'updated_at' => current_time( 'mysql' ),
		);

		return $this->saveLayout( $user_id, $layout );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Sticker Methods
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Get all stickers for a user's layout.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array Array of sticker placements.
	 */
	public function getStickers( int $user_id ): array {
		$layout = $this->getLayout( $user_id );

		return $layout['stickers'] ?? array();
	}

	/**
	 * Save stickers array for a user.
	 *
	 * @param int   $user_id  WordPress user ID.
	 * @param array $stickers Array of sticker placements.
	 * @return bool True on success, false on failure.
	 */
	public function saveStickers( int $user_id, array $stickers ): bool {
		$layout             = $this->getLayout( $user_id );
		$layout['stickers'] = $this->sanitizeStickers( $stickers );

		return $this->saveLayout( $user_id, $layout );
	}

	/**
	 * Add a single sticker to the layout.
	 *
	 * @param int   $user_id    WordPress user ID.
	 * @param array $sticker    Sticker placement data.
	 * @return string|false Unique sticker instance ID or false on failure.
	 */
	public function addSticker( int $user_id, array $sticker ): string {
		$layout = $this->getLayout( $user_id );

		// Validate sticker asset exists.
		$asset_id = $sticker['asset'] ?? '';
		$asset    = StickerRegistry::get_by_id( $asset_id );
		if ( null === $asset ) {
			return '';
		}

		// Generate unique instance ID.
		$instance_id = 'stk_' . wp_generate_uuid4();

		$layout['stickers'][] = array(
			'id'       => $instance_id,
			'asset'    => sanitize_key( $asset_id ),
			'x'        => isset( $sticker['x'] ) ? (int) $sticker['x'] : 0,
			'y'        => isset( $sticker['y'] ) ? (int) $sticker['y'] : 0,
			'scale'    => isset( $sticker['scale'] ) ? $this->sanitizeScale( $sticker['scale'] ) : 1.0,
			'rotation' => isset( $sticker['rotation'] ) ? $this->sanitizeRotation( $sticker['rotation'] ) : 0,
			'z_index'  => isset( $sticker['z_index'] ) ? absint( $sticker['z_index'] ) : 50,
		);

		$this->saveLayout( $user_id, $layout );

		return $instance_id;
	}

	/**
	 * Update a sticker's position/properties.
	 *
	 * @param int    $user_id     WordPress user ID.
	 * @param string $instance_id Sticker instance ID.
	 * @param array  $updates     Properties to update.
	 * @return bool True on success, false on failure.
	 */
	public function updateSticker( int $user_id, string $instance_id, array $updates ): bool {
		$layout = $this->getLayout( $user_id );

		$found = false;
		foreach ( $layout['stickers'] as &$sticker ) {
			if ( ( $sticker['id'] ?? '' ) === $instance_id ) {
				if ( isset( $updates['x'] ) ) {
					$sticker['x'] = (int) $updates['x'];
				}
				if ( isset( $updates['y'] ) ) {
					$sticker['y'] = (int) $updates['y'];
				}
				if ( isset( $updates['scale'] ) ) {
					$sticker['scale'] = $this->sanitizeScale( $updates['scale'] );
				}
				if ( isset( $updates['rotation'] ) ) {
					$sticker['rotation'] = $this->sanitizeRotation( $updates['rotation'] );
				}
				if ( isset( $updates['z_index'] ) ) {
					$sticker['z_index'] = absint( $updates['z_index'] );
				}
				$found = true;
				break;
			}
		}//end foreach
		unset( $sticker );

		if ( ! $found ) {
			return false;
		}

		return $this->saveLayout( $user_id, $layout );
	}

	/**
	 * Remove a sticker from the layout.
	 *
	 * @param int    $user_id     WordPress user ID.
	 * @param string $instance_id Sticker instance ID.
	 * @return bool True on success, false on failure.
	 */
	public function removeSticker( int $user_id, string $instance_id ): bool {
		$layout = $this->getLayout( $user_id );

		$original_count     = count( $layout['stickers'] );
		$layout['stickers'] = array_values(
			array_filter(
				$layout['stickers'],
				function ( array $sticker ) use ( $instance_id ): bool {
					return ( $sticker['id'] ?? '' ) !== $instance_id;
				}
			)
		);

		if ( count( $layout['stickers'] ) === $original_count ) {
			return false; 
			// Sticker not found.
		}

		return $this->saveLayout( $user_id, $layout );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Layout Helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Provide a sane empty layout scaffold.
	 *
	 * @return array Empty layout structure.
	 */
	public function emptyLayout(): array {
		return array(
			'background' => array(
				'id' => self::DEFAULT_BACKGROUND,
			),
			'widgets'    => array(),
			'stickers'   => array(),
			'grids'      => array(),
			'grid_cells' => array(),
			'apollo'     => array(
				'absolute'  => true,
				'positions' => array(),
			),
			'__version'  => self::SCHEMA_VERSION,
		);
	}

	/**
	 * Normalize a layout for backward compatibility.
	 *
	 * Adds missing keys from new schema version.
	 *
	 * @param array $layout Raw layout from database.
	 * @return array Normalized layout.
	 */
	private function normalizeLayout( array $layout ): array {
		$empty = $this->emptyLayout();

		// Ensure background exists.
		if ( ! isset( $layout['background'] ) || ! is_array( $layout['background'] ) ) {
			$layout['background'] = $empty['background'];
		}

		// Ensure stickers array exists.
		if ( ! isset( $layout['stickers'] ) || ! is_array( $layout['stickers'] ) ) {
			$layout['stickers'] = array();
		}

		// Ensure other required keys.
		$layout['widgets']    = $layout['widgets'] ?? array();
		$layout['grids']      = $layout['grids'] ?? array();
		$layout['grid_cells'] = $layout['grid_cells'] ?? array();
		$layout['apollo']     = $layout['apollo'] ?? $empty['apollo'];

		return $layout;
	}

	/**
	 * Sanitize entire layout before saving.
	 *
	 * @param array $layout Raw layout data.
	 * @return array Sanitized layout.
	 */
	private function sanitizeLayout( array $layout ): array {
		// Sanitize background.
		if ( isset( $layout['background'] ) && is_array( $layout['background'] ) ) {
			$layout['background']['id'] = sanitize_key( $layout['background']['id'] ?? self::DEFAULT_BACKGROUND );
		}

		// Sanitize stickers.
		if ( isset( $layout['stickers'] ) && is_array( $layout['stickers'] ) ) {
			$layout['stickers'] = $this->sanitizeStickers( $layout['stickers'] );
		}

		return $layout;
	}

	/**
	 * Sanitize stickers array.
	 *
	 * @param array $stickers Raw stickers data.
	 * @return array Sanitized stickers.
	 */
	private function sanitizeStickers( array $stickers ): array {
		$sanitized = array();

		foreach ( $stickers as $sticker ) {
			if ( ! is_array( $sticker ) ) {
				continue;
			}

			$sanitized[] = array(
				'id'       => sanitize_key( $sticker['id'] ?? '' ),
				'asset'    => sanitize_key( $sticker['asset'] ?? '' ),
				'x'        => (int) ( $sticker['x'] ?? 0 ),
				'y'        => (int) ( $sticker['y'] ?? 0 ),
				'scale'    => $this->sanitizeScale( $sticker['scale'] ?? 1.0 ),
				'rotation' => $this->sanitizeRotation( $sticker['rotation'] ?? 0 ),
				'z_index'  => absint( $sticker['z_index'] ?? 50 ),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize scale value (0.25 - 3.0).
	 *
	 * @param mixed $scale Raw scale value.
	 * @return float Sanitized scale.
	 */
	private function sanitizeScale( $scale ): float {
		$scale = (float) $scale;
		return max( 0.25, min( 3.0, $scale ) );
	}

	/**
	 * Sanitize rotation value (0 - 360).
	 *
	 * @param mixed $rotation Raw rotation value.
	 * @return int Sanitized rotation in degrees.
	 */
	private function sanitizeRotation( $rotation ): int {
		$rotation = (int) $rotation;
		return ( ( $rotation % 360 ) + 360 ) % 360; 
		// Normalize to 0-359.
	}
}
