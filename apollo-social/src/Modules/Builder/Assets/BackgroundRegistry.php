<?php
/**
 * Background Registry for Apollo Builder.
 *
 * Provides a catalog of available backgrounds for the Habbo-style page builder.
 * Backgrounds can be gradients, patterns, images, or solid colors.
 *
 * @package Apollo\Modules\Builder\Assets
 * @since   1.0.0
 */

namespace Apollo\Modules\Builder\Assets;

/**
 * Registry of available page backgrounds.
 */
class BackgroundRegistry {

	/**
	 * Get all registered backgrounds.
	 *
	 * @return array<string, array{
	 *     id: string,
	 *     label: string,
	 *     preview_url: string,
	 *     css_value: string,
	 *     category: string,
	 *     is_limited: bool,
	 *     required_capability: string
	 * }>
	 */
	public static function get_all(): array {
		$backgrounds = self::get_core_backgrounds();

		/**
		 * Filter the available backgrounds in Apollo Builder.
		 *
		 * @since 1.0.0
		 *
		 * @param array $backgrounds Array of background definitions.
		 */
		return apply_filters( 'apollo_builder_backgrounds', $backgrounds );
	}

	/**
	 * Get a single background by ID.
	 *
	 * @param string $id Background identifier.
	 * @return array|null Background data or null if not found.
	 */
	public static function get_by_id( string $id ): ?array {
		$backgrounds = self::get_all();

		return $backgrounds[ $id ] ?? null;
	}

	/**
	 * Get backgrounds available for a specific user.
	 *
	 * Filters out limited/premium backgrounds the user doesn't have access to.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array Filtered array of backgrounds.
	 */
	public static function get_available_for_user( int $user_id ): array {
		$all_backgrounds = self::get_all();

		if ( $user_id <= 0 ) {
			// Anonymous users only get free backgrounds.
			return array_filter(
				$all_backgrounds,
				function ( array $bg ): bool {
					return empty( $bg['is_limited'] ) && empty( $bg['required_capability'] );
				}
			);
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return [];
		}

		return array_filter(
			$all_backgrounds,
			function ( array $bg ) use ( $user ): bool {
				// Free backgrounds are always available.
				if ( empty( $bg['is_limited'] ) && empty( $bg['required_capability'] ) ) {
					return true;
				}

				// Check capability requirement.
				if ( ! empty( $bg['required_capability'] ) ) {
					return $user->has_cap( $bg['required_capability'] );
				}

				// Limited items require the premium capability.
				if ( ! empty( $bg['is_limited'] ) ) {
					return $user->has_cap( 'apollo_premium_assets' );
				}

				return true;
			}
		);
	}

	/**
	 * Get categories for grouping backgrounds in the UI.
	 *
	 * @return array<string, string> Category slug => label pairs.
	 */
	public static function get_categories(): array {
		return [
			'solid'    => __( 'Cores Sólidas', 'apollo-social' ),
			'gradient' => __( 'Gradientes', 'apollo-social' ),
			'pattern'  => __( 'Padrões', 'apollo-social' ),
			'image'    => __( 'Imagens', 'apollo-social' ),
			'premium'  => __( 'Premium', 'apollo-social' ),
		];
	}

	/**
	 * Core background definitions.
	 *
	 * @return array<string, array>
	 */
	private static function get_core_backgrounds(): array {
		$cdn_base = 'https://assets.apollo.rio.br/builder/backgrounds';

		return [
			// ─────────────────────────────────────────────────────────────
			// Solid Colors
			// ─────────────────────────────────────────────────────────────
			'solid-white'       => [
				'id'                  => 'solid-white',
				'label'               => __( 'Branco', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/solid-white.png",
				'css_value'           => '#ffffff',
				'category'            => 'solid',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'solid-slate-50'    => [
				'id'                  => 'solid-slate-50',
				'label'               => __( 'Cinza Claro', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/solid-slate-50.png",
				'css_value'           => '#f8fafc',
				'category'            => 'solid',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'solid-slate-900'   => [
				'id'                  => 'solid-slate-900',
				'label'               => __( 'Escuro', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/solid-slate-900.png",
				'css_value'           => '#0f172a',
				'category'            => 'solid',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'solid-orange-500'  => [
				'id'                  => 'solid-orange-500',
				'label'               => __( 'Apollo Orange', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/solid-orange-500.png",
				'css_value'           => '#f97316',
				'category'            => 'solid',
				'is_limited'          => false,
				'required_capability' => '',
			],

			// ─────────────────────────────────────────────────────────────
			// Gradients
			// ─────────────────────────────────────────────────────────────
			'gradient-sunset'   => [
				'id'                  => 'gradient-sunset',
				'label'               => __( 'Pôr do Sol', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/gradient-sunset.png",
				'css_value'           => 'linear-gradient(135deg, #f97316, #ec4899, #8b5cf6)',
				'category'            => 'gradient',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'gradient-ocean'    => [
				'id'                  => 'gradient-ocean',
				'label'               => __( 'Oceano', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/gradient-ocean.png",
				'css_value'           => 'linear-gradient(135deg, #0ea5e9, #06b6d4, #14b8a6)',
				'category'            => 'gradient',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'gradient-aurora'   => [
				'id'                  => 'gradient-aurora',
				'label'               => __( 'Aurora Boreal', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/gradient-aurora.png",
				'css_value'           => 'linear-gradient(135deg, #10b981, #06b6d4, #8b5cf6)',
				'category'            => 'gradient',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'gradient-midnight' => [
				'id'                  => 'gradient-midnight',
				'label'               => __( 'Meia-Noite', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/gradient-midnight.png",
				'css_value'           => 'linear-gradient(135deg, #1e1b4b, #312e81, #4338ca)',
				'category'            => 'gradient',
				'is_limited'          => false,
				'required_capability' => '',
			],

			// ─────────────────────────────────────────────────────────────
			// Patterns (CSS patterns using repeating gradients)
			// ─────────────────────────────────────────────────────────────
			'pattern-dots'      => [
				'id'                  => 'pattern-dots',
				'label'               => __( 'Pontos', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/pattern-dots.png",
				'css_value'           => 'radial-gradient(circle, #cbd5e1 1px, transparent 1px), #f1f5f9',
				'css_size'            => '20px 20px',
				'category'            => 'pattern',
				'is_limited'          => false,
				'required_capability' => '',
			],
			'pattern-grid'      => [
				'id'                  => 'pattern-grid',
				'label'               => __( 'Grade', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/pattern-grid.png",
				'css_value'           => 'linear-gradient(#e2e8f0 1px, transparent 1px), linear-gradient(90deg, #e2e8f0 1px, transparent 1px), #ffffff',
				'css_size'            => '20px 20px',
				'category'            => 'pattern',
				'is_limited'          => false,
				'required_capability' => '',
			],

			// ─────────────────────────────────────────────────────────────
			// Premium / Limited Edition
			// ─────────────────────────────────────────────────────────────
			'premium-neon'      => [
				'id'                  => 'premium-neon',
				'label'               => __( 'Neon Nights', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/premium-neon.png",
				'css_value'           => 'linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%)',
				'category'            => 'premium',
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
			],
			'premium-hologram'  => [
				'id'                  => 'premium-hologram',
				'label'               => __( 'Holograma', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/premium-hologram.png",
				'css_value'           => 'linear-gradient(135deg, #ff006650, #00ff8850, #0066ff50), linear-gradient(45deg, #1a1a2e, #16213e)',
				'category'            => 'premium',
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
			],
			'premium-gold'      => [
				'id'                  => 'premium-gold',
				'label'               => __( 'Ouro', 'apollo-social' ),
				'preview_url'         => "{$cdn_base}/previews/premium-gold.png",
				'css_value'           => 'linear-gradient(135deg, #fbbf24, #f59e0b, #d97706)',
				'category'            => 'premium',
				'is_limited'          => true,
				'required_capability' => 'apollo_premium_assets',
			],
		];
	}
}
