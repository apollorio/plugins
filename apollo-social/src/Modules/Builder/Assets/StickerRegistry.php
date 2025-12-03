<?php
/**
 * Sticker Registry for Apollo Builder.
 *
 * Provides a catalog of available stickers for the Habbo-style page builder.
 * Stickers are decorative elements users can place on their profile canvas.
 *
 * @package Apollo\Modules\Builder\Assets
 * @since   1.0.0
 */

namespace Apollo\Modules\Builder\Assets;

/**
 * Registry of available page stickers.
 */
class StickerRegistry {

	/**
	 * Get all registered stickers.
	 *
	 * @return array<string, array{
	 *     id: string,
	 *     label: string,
	 *     preview_url: string,
	 *     image_url: string,
	 *     category: string,
	 *     width: int,
	 *     height: int,
	 *     is_limited: bool,
	 *     required_capability: string,
	 *     z_index_hint: int
	 * }>
	 */
	public static function get_all(): array {
		$stickers = self::get_core_stickers();

		/**
		 * Filter the available stickers in Apollo Builder.
		 *
		 * @since 1.0.0
		 *
		 * @param array $stickers Array of sticker definitions.
		 */
		return apply_filters( 'apollo_builder_stickers', $stickers );
	}

	/**
	 * Get a single sticker by ID.
	 *
	 * @param string $id Sticker identifier.
	 * @return array|null Sticker data or null if not found.
	 */
	public static function get_by_id( string $id ): ?array {
		$stickers = self::get_all();

		return $stickers[ $id ] ?? null;
	}

	/**
	 * Get stickers available for a specific user.
	 *
	 * Filters out limited/premium stickers the user doesn't have access to.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array Filtered array of stickers.
	 */
	public static function get_available_for_user( int $user_id ): array {
		$all_stickers = self::get_all();

		if ( $user_id <= 0 ) {
			// Anonymous users only get free stickers.
			return array_filter(
				$all_stickers,
				function ( array $sticker ): bool {
					return empty( $sticker['is_limited'] ) && empty( $sticker['required_capability'] );
				}
			);
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}

		return array_filter(
			$all_stickers,
			function ( array $sticker ) use ( $user ): bool {
				// Free stickers are always available.
				if ( empty( $sticker['is_limited'] ) && empty( $sticker['required_capability'] ) ) {
					return true;
				}

				// Check capability requirement.
				if ( ! empty( $sticker['required_capability'] ) ) {
					return $user->has_cap( $sticker['required_capability'] );
				}

				// Limited items require the premium capability.
				if ( ! empty( $sticker['is_limited'] ) ) {
					return $user->has_cap( 'apollo_premium_assets' );
				}

				return true;
			}
		);
	}

	/**
	 * Get stickers filtered by category.
	 *
	 * @param string $category Category slug.
	 * @param int    $user_id  Optional user ID for filtering availability.
	 * @return array Filtered stickers.
	 */
	public static function get_by_category( string $category, int $user_id = 0 ): array {
		$stickers = $user_id > 0
			? self::get_available_for_user( $user_id )
			: self::get_all();

		return array_filter(
			$stickers,
			function ( array $sticker ) use ( $category ): bool {
				return ( $sticker['category'] ?? '' ) === $category;
			}
		);
	}

	/**
	 * Get categories for grouping stickers in the UI.
	 *
	 * @return array<string, string> Category slug => label pairs.
	 */
	public static function get_categories(): array {
		return array(
			'emoji'      => __( 'Emojis', 'apollo-social' ),
			'badge'      => __( 'Badges', 'apollo-social' ),
			'decoration' => __( 'Decorações', 'apollo-social' ),
			'social'     => __( 'Redes Sociais', 'apollo-social' ),
			'music'      => __( 'Música', 'apollo-social' ),
			'premium'    => __( 'Premium', 'apollo-social' ),
		);
	}

	/**
	 * Core sticker definitions.
	 *
	 * @return array<string, array>
	 */
	private static function get_core_stickers(): array {
		$cdn_base = 'https://assets.apollo.rio.br/builder/stickers';

		return array(
			// ─────────────────────────────────────────────────────────────
			// Emojis
			// ─────────────────────────────────────────────────────────────
			'emoji-fire'        => array(
				'id'                  => 'emoji-fire',
				'label'               => __( 'Fogo', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/emoji/fire-preview.png",
				'image_url'           => "{$cdn_base}/emoji/fire.png",
				'category'            => 'emoji',
				'width'               => 64,
				'height'              => 64,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 50,
			),
			'emoji-heart'       => array(
				'id'                  => 'emoji-heart',
				'label'               => __( 'Coração', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/emoji/heart-preview.png",
				'image_url'           => "{$cdn_base}/emoji/heart.png",
				'category'            => 'emoji',
				'width'               => 64,
				'height'              => 64,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 50,
			),
			'emoji-star'        => array(
				'id'                  => 'emoji-star',
				'label'               => __( 'Estrela', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/emoji/star-preview.png",
				'image_url'           => "{$cdn_base}/emoji/star.png",
				'category'            => 'emoji',
				'width'               => 64,
				'height'              => 64,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 50,
			),
			'emoji-sparkles'    => array(
				'id'                  => 'emoji-sparkles',
				'label'               => __( 'Brilhos', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/emoji/sparkles-preview.png",
				'image_url'           => "{$cdn_base}/emoji/sparkles.png",
				'category'            => 'emoji',
				'width'               => 64,
				'height'              => 64,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 50,
			),
			'emoji-music'       => array(
				'id'                  => 'emoji-music',
				'label'               => __( 'Nota Musical', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/emoji/music-preview.png",
				'image_url'           => "{$cdn_base}/emoji/music.png",
				'category'            => 'emoji',
				'width'               => 64,
				'height'              => 64,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 50,
			),

			// ─────────────────────────────────────────────────────────────
			// Badges
			// ─────────────────────────────────────────────────────────────
			'badge-verified'    => array(
				'id'                  => 'badge-verified',
				'label'               => __( 'Verificado', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/badges/verified-preview.png",
				'image_url'           => "{$cdn_base}/badges/verified.png",
				'category'            => 'badge',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 60,
			),
			'badge-dj'          => array(
				'id'                  => 'badge-dj',
				'label'               => __( 'DJ', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/badges/dj-preview.png",
				'image_url'           => "{$cdn_base}/badges/dj.png",
				'category'            => 'badge',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 60,
			),
			'badge-founder'     => array(
				'id'                  => 'badge-founder',
				'label'               => __( 'Fundador', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/badges/founder-preview.png",
				'image_url'           => "{$cdn_base}/badges/founder.png",
				'category'            => 'badge',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => true,
				'required_capability' => 'apollo_founder_badge',
				'z_index_hint'        => 60,
			),
			'badge-premium'     => array(
				'id'                  => 'badge-premium',
				'label'               => __( 'Premium', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/badges/premium-preview.png",
				'image_url'           => "{$cdn_base}/badges/premium.png",
				'category'            => 'badge',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
				'z_index_hint'        => 60,
			),

			// ─────────────────────────────────────────────────────────────
			// Decorations
			// ─────────────────────────────────────────────────────────────
			'decor-ribbon'      => array(
				'id'                  => 'decor-ribbon',
				'label'               => __( 'Fita', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/decorations/ribbon-preview.png",
				'image_url'           => "{$cdn_base}/decorations/ribbon.png",
				'category'            => 'decoration',
				'width'               => 120,
				'height'              => 40,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 30,
			),
			'decor-frame'       => array(
				'id'                  => 'decor-frame',
				'label'               => __( 'Moldura', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/decorations/frame-preview.png",
				'image_url'           => "{$cdn_base}/decorations/frame.png",
				'category'            => 'decoration',
				'width'               => 200,
				'height'              => 200,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 20,
			),
			'decor-divider'     => array(
				'id'                  => 'decor-divider',
				'label'               => __( 'Divisor', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/decorations/divider-preview.png",
				'image_url'           => "{$cdn_base}/decorations/divider.png",
				'category'            => 'decoration',
				'width'               => 300,
				'height'              => 20,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 25,
			),

			// ─────────────────────────────────────────────────────────────
			// Social Icons
			// ─────────────────────────────────────────────────────────────
			'social-instagram'  => array(
				'id'                  => 'social-instagram',
				'label'               => __( 'Instagram', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/social/instagram-preview.png",
				'image_url'           => "{$cdn_base}/social/instagram.png",
				'category'            => 'social',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 55,
			),
			'social-spotify'    => array(
				'id'                  => 'social-spotify',
				'label'               => __( 'Spotify', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/social/spotify-preview.png",
				'image_url'           => "{$cdn_base}/social/spotify.png",
				'category'            => 'social',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 55,
			),
			'social-soundcloud' => array(
				'id'                  => 'social-soundcloud',
				'label'               => __( 'SoundCloud', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/social/soundcloud-preview.png",
				'image_url'           => "{$cdn_base}/social/soundcloud.png",
				'category'            => 'social',
				'width'               => 48,
				'height'              => 48,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 55,
			),

			// ─────────────────────────────────────────────────────────────
			// Music
			// ─────────────────────────────────────────────────────────────
			'music-headphones'  => array(
				'id'                  => 'music-headphones',
				'label'               => __( 'Fones', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/music/headphones-preview.png",
				'image_url'           => "{$cdn_base}/music/headphones.png",
				'category'            => 'music',
				'width'               => 80,
				'height'              => 80,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 45,
			),
			'music-vinyl'       => array(
				'id'                  => 'music-vinyl',
				'label'               => __( 'Vinil', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/music/vinyl-preview.png",
				'image_url'           => "{$cdn_base}/music/vinyl.png",
				'category'            => 'music',
				'width'               => 100,
				'height'              => 100,
				'is_limited'          => false,
				'required_capability' => '',
				'z_index_hint'        => 40,
			),

			// ─────────────────────────────────────────────────────────────
			// Premium Stickers
			// ─────────────────────────────────────────────────────────────
			'premium-crown'     => array(
				'id'                  => 'premium-crown',
				'label'               => __( 'Coroa', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/premium/crown-preview.png",
				'image_url'           => "{$cdn_base}/premium/crown.png",
				'category'            => 'premium',
				'width'               => 80,
				'height'              => 60,
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
				'z_index_hint'        => 70,
			),
			'premium-wings'     => array(
				'id'                  => 'premium-wings',
				'label'               => __( 'Asas', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/premium/wings-preview.png",
				'image_url'           => "{$cdn_base}/premium/wings.png",
				'category'            => 'premium',
				'width'               => 200,
				'height'              => 120,
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
				'z_index_hint'        => 15,
			),
			'premium-aura'      => array(
				'id'                  => 'premium-aura',
				'label'               => __( 'Aura Brilhante', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/premium/aura-preview.png",
				'image_url'           => "{$cdn_base}/premium/aura.png",
				'category'            => 'premium',
				'width'               => 150,
				'height'              => 150,
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
				'z_index_hint'        => 10,
			),
		);
	}
}
