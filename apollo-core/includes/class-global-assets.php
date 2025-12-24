<?php

/**
 * Apollo Global Assets Manager
 *
 * ============================================================================
 * DEPRECATED - USE Apollo_Assets INSTEAD
 * ============================================================================
 *
 * This class is maintained for backwards compatibility only.
 * All new code should use Apollo_Assets which loads from local bundle.
 *
 * Old CDN URLs (deprecated):
 *   https://assets.apollo.rio.br/uni.css
 *   https://assets.apollo.rio.br/base.js
 *   https://assets.apollo.rio.br/event-page.js
 *   https://assets.apollo.rio.br/animate.css
 *
 * New Local Paths:
 *   apollo-core/assets/core/uni.css
 *   apollo-core/assets/core/base.js
 *   apollo-core/assets/core/event-page.js
 *   apollo-core/assets/core/animate.css
 *
 * ============================================================================
 *
 * Usage (DEPRECATED):
 *   - Apollo_Global_Assets::enqueue_all()     // Use Apollo_Assets::enqueue_frontend().
 *   - Apollo_Global_Assets::enqueue_css()     // Use wp_enqueue_style('apollo-core-uni').
 *   - Apollo_Global_Assets::enqueue_js()      // Use wp_enqueue_script('apollo-core-base').
 *
 * New Usage:
 *   - Apollo_Assets::enqueue_frontend()       // Enqueue all Apollo frontend assets.
 *   - Apollo_Assets::enqueue_admin()          // Enqueue all Apollo admin assets.
 *
 * @package Apollo_Core
 * @since 1.0.0
 * @deprecated 2.0.0 Use Apollo_Assets instead
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Global_Assets
 *
 * @deprecated 2.0.0 Use Apollo_Assets instead for local-first asset loading.
 */
class Global_Assets {

	/**
	 * Local asset base URL (replaces CDN)
	 */
	public const LOCAL_BASE_URL = APOLLO_CORE_PLUGIN_URL . 'assets/core/';

	/**
	 * CDN base URL - deprecated, kept for reference only
	 *
	 * @deprecated 2.0.0
	 */
	public const CDN_BASE_URL = 'https://assets.apollo.rio.br/';

	/**
	 * Local fallback path relative to apollo-core plugin
	 */
	public const LOCAL_BASE_PATH = 'assets/core/';

	/**
	 * Whether to use CDN (always false now - local only)
	 *
	 * @deprecated 2.0.0 Always false
	 */
	private static $use_cdn = false;

	/**
	 * Asset versions for cache busting
	 * Updated: 2025-12-01 (UNI.CSS Refactor)
	 */
	private static $asset_versions = array(
		'uni.css'                 => '5.2.0',
		// UNI.CSS v5.2 - Classifieds/Marketplace (advert cards, currency widget)
					'base.js'     => '4.2.0',
		// base.js v4.2 - Classifieds utilities, currency converter
					'animate.css' => '1.0.0',
		'event-page.js'           => '1.0.0',
	);

	/**
	 * Flag to track if assets have been enqueued
	 */
	private static $css_enqueued = false;
	private static $js_enqueued  = false;

	/**
	 * Initialize the global assets system
	 *
	 * @return void
	 */
	public static function init(): void {
		// Allow filtering CDN usage.
		self::$use_cdn = (bool) apply_filters( 'apollo_use_cdn_assets', self::$use_cdn );

		// Register assets on init.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );

		// Auto-enqueue on Apollo pages.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_auto_enqueue' ), 10 );

		// Enqueue base.js on ALL pages (global behaviors)
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_global_js' ), 1 );
	}

	/**
	 * Register all global assets (but don't enqueue yet)
	 * Now uses local assets instead of CDN
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		$local_base = APOLLO_CORE_PLUGIN_URL . 'assets/';
		$local_dir  = APOLLO_CORE_PLUGIN_DIR . 'assets/';

		// Helper for filemtime version.
		$get_ver = function ( $file ) use ( $local_dir ) {
			$path = $local_dir . $file;
			return file_exists( $path ) ? filemtime( $path ) : APOLLO_CORE_VERSION;
		};

		// Register uni.css (local)
		wp_register_style(
			'apollo-uni-css',
			$local_base . 'core/uni.css',
			array(),
			$get_ver( 'core/uni.css' )
		);

		// Register RemixIcon (local).
		wp_register_style(
			'apollo-remixicon',
			$local_base . 'vendor/remixicon/remixicon.css',
			array(),
			$get_ver( 'vendor/remixicon/remixicon.css' )
		);

		// Register animate.css (local)
		wp_register_style(
			'apollo-animate-css',
			$local_base . 'core/animate.css',
			array(),
			$get_ver( 'core/animate.css' )
		);

		// Register base.js (local)
		wp_register_script(
			'apollo-base-js',
			$local_base . 'core/base.js',
			array(),
			$get_ver( 'core/base.js' ),
			true
		);

		// Register event-page.js (local)
		wp_register_script(
			'apollo-event-page-js',
			$local_base . 'core/event-page.js',
			array(),
			$get_ver( 'core/event-page.js' ),
			true
		);

		// Register Motion library (local).
		wp_register_script(
			'apollo-motion',
			$local_base . 'vendor/motion/motion.min.js',
			array(),
			$get_ver( 'vendor/motion/motion.min.js' ),
			true
		);
	}

	/**
	 * Enqueue event page specific assets
	 *
	 * @return void
	 */
	public static function enqueue_event_page_assets(): void {
		self::enqueue_css();
		wp_enqueue_script( 'apollo-event-page-js' );
	}

	/**
	 * Auto-enqueue assets on Apollo pages
	 *
	 * @return void
	 */
	public static function maybe_auto_enqueue(): void {
		if ( self::is_apollo_page() ) {
			self::enqueue_all();
		}
	}

	/**
	 * Check if current page is an Apollo page
	 *
	 * @return bool
	 */
	public static function is_apollo_page(): bool {
		// Check for canvas mode.
		if ( function_exists( 'apollo_is_canvas_mode' ) && apollo_is_canvas_mode() ) {
			return true;
		}

		// Check URL patterns.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$apollo_routes = array(
			'/a/',
			'/comunidade/',
			'/nucleo/',
			'/season/',
			'/membership',
			'/uniao/',
			'/anuncio/',
			'/feed/',
			'/chat/',
			'/painel/',
			'/cena/',
			'/cena-rio/',
			'/eco/',
			'/ecoa/',
			'/id/',
			'/clubber/',
			'/doc/',
			'/pla/',
			'/sign/',
			'/documentos/',
			'/enviar/',
			'/eventos/',
			'/evento/',
			'/dj/',
		);

		foreach ( $apollo_routes as $route ) {
			if ( strpos( $request_uri, $route ) !== false ) {
				return true;
			}
		}

		// Check for Apollo CPTs.
		$apollo_post_types = array( 'event_listing', 'event_local', 'event_dj', 'apollo_group', 'user_page' );
		if ( is_singular( $apollo_post_types ) || is_post_type_archive( $apollo_post_types ) ) {
			return true;
		}

		// Allow plugins to indicate Apollo page.
		return (bool) apply_filters( 'apollo_is_apollo_page', false );
	}

	/**
	 * Enqueue all global assets (CSS + JS)
	 *
	 * @return void
	 */
	public static function enqueue_all(): void {
		self::enqueue_css();
		self::enqueue_global_js();
	}

	/**
	 * Enqueue global CSS assets
	 *
	 * @param bool $force Force enqueueing even if already done
	 * @return void
	 */
	public static function enqueue_css( bool $force = false ): void {
		if ( self::$css_enqueued && ! $force ) {
			return;
		}

		// Enqueue RemixIcon first.
		wp_enqueue_style( 'apollo-remixicon' );

		// Enqueue uni.css
		wp_enqueue_style( 'apollo-uni-css' );

		// Add inline CSS for theme reset on Apollo pages.
		$inline_css = '
            /* Apollo Global Reset - Applied to Apollo pages */
            .apollo-canvas-mode body,
            body.apollo-page {
                font-family: "Urbanist", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
        ';
		wp_add_inline_style( 'apollo-uni-css', $inline_css );

		self::$css_enqueued = true;

		/**
		 * Action: apollo_global_css_enqueued
		 * Fired after global CSS assets are enqueued
		 */
		do_action( 'apollo_global_css_enqueued' );
	}

	/**
	 * Enqueue base.js on ALL pages (global behaviors like dark mode, menus, tooltips)
	 *
	 * @return void
	 */
	public static function enqueue_global_js(): void {
		// Only enqueue if not already enqueued.
		if ( wp_script_is( 'apollo-base-js', 'enqueued' ) ) {
			return;
		}

		// Register first if not registered.
		if ( ! wp_script_is( 'apollo-base-js', 'registered' ) ) {
			wp_register_script(
				'apollo-base-js',
				self::get_asset_url( 'base.js' ),
				array(),
				self::get_asset_version( 'base.js' ),
				true
			);
		}

		// Enqueue base.js globally
		wp_enqueue_script( 'apollo-base-js' );

		// Localize script with Apollo data.
		wp_localize_script(
			'apollo-base-js',
			'apolloGlobal',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'restUrl'    => rest_url( 'apollo/v1/' ),
				'nonce'      => wp_create_nonce( 'apollo_global' ),
				'isDarkMode' => self::get_user_dark_mode_preference(),
				'locale'     => get_locale(),
			)
		);
	}

	/**
	 * Get the full URL for an asset
	 *
	 * @param string $asset Asset filename (e.g., 'uni.css')
	 * @return string Full URL to asset
	 */
	public static function get_asset_url( string $asset ): string {
		if ( self::$use_cdn ) {
			return self::CDN_BASE_URL . ltrim( $asset, '/' );
		}

		// Local fallback.
		if ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ) {
			return APOLLO_CORE_PLUGIN_URL . self::LOCAL_BASE_PATH . ltrim( $asset, '/' );
		}

		// Ultimate fallback to CDN.
		return self::CDN_BASE_URL . ltrim( $asset, '/' );
	}

	/**
	 * Get asset version for cache busting
	 *
	 * @param string $asset Asset filename
	 * @return string Version string
	 */
	public static function get_asset_version( string $asset ): string {
		// In development, use file modification time.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! self::$use_cdn ) {
			$local_path = self::get_local_path( $asset );
			if ( $local_path && file_exists( $local_path ) ) {
				return (string) filemtime( $local_path );
			}
		}

		return self::$asset_versions[ $asset ] ?? '1.0.0';
	}

	/**
	 * Get local file path for an asset
	 *
	 * @param string $asset Asset filename
	 * @return string|false Local path or false if not found
	 */
	public static function get_local_path( string $asset ) {
		if ( defined( 'APOLLO_CORE_PLUGIN_DIR' ) ) {
			$path = APOLLO_CORE_PLUGIN_DIR . self::LOCAL_BASE_PATH . ltrim( $asset, '/' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Set whether to use CDN or local assets
	 *
	 * @param bool $use_cdn True for CDN, false for local
	 * @return void
	 */
	public static function set_use_cdn( bool $use_cdn ): void {
		self::$use_cdn = $use_cdn;
	}

	/**
	 * Check if using CDN
	 *
	 * @return bool
	 */
	public static function is_using_cdn(): bool {
		return self::$use_cdn;
	}

	/**
	 * Get user's dark mode preference
	 *
	 * @return bool
	 */
	private static function get_user_dark_mode_preference(): bool {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			return (bool) get_user_meta( $user_id, 'apollo_dark_mode', true );
		}

		return false;
	}

	/**
	 * Dequeue all theme styles (for Canvas mode)
	 *
	 * @return void
	 */
	public static function dequeue_theme_styles(): void {
		global $wp_styles;

		if ( ! is_object( $wp_styles ) || ! isset( $wp_styles->queue ) ) {
			return;
		}

		$allowed_patterns = array(
			'apollo-',
			'assets.apollo.rio.br',
			'remixicon',
			'cdn.jsdelivr.net/npm/motion',
		);

		foreach ( $wp_styles->queue as $handle ) {
			$keep = false;

			// Check handle prefix.
			if ( strpos( $handle, 'apollo-' ) === 0 ) {
				$keep = true;
			}

			// Check src patterns.
			if ( isset( $wp_styles->registered[ $handle ] ) && is_object( $wp_styles->registered[ $handle ] ) ) {
				$src = $wp_styles->registered[ $handle ]->src ?? '';
				foreach ( $allowed_patterns as $pattern ) {
					if ( strpos( $src, $pattern ) !== false ) {
						$keep = true;

						break;
					}
				}
			}

			if ( ! $keep ) {
				wp_dequeue_style( $handle );
			}
		}//end foreach
	}

	/**
	 * Get list of available UNI.CSS component classes
	 *
	 * @return array
	 */
	public static function get_component_classes(): array {
		return array(
			'cards'     => array(
				'.aprioEXP-card-shell' => 'Standard Apollo card with glass effect',
				'.card-glass'          => 'Glassmorphism card effect',
				'.community-card'      => 'Community/group card with gradient blur',
				'.user-card'           => 'User profile card',
				'.event-card'          => 'Event listing card',
				'.ticket-card'         => 'Ticket/CTA card',
				'.calendar-card'       => 'Calendar widget card',
			),
			'buttons'   => array(
				'.bottom-btn'         => 'Standard action button',
				'.bottom-btn.primary' => 'Primary action button',
				'.btn-player-main'    => 'Media player button',
				'.route-button'       => 'Navigation/route button',
			),
			'badges'    => array(
				'.apollo-badge'               => 'Base badge class',
				'.apollo-badge-role-dj'       => 'DJ role badge (blue)',
				'.apollo-badge-role-producer' => 'Producer role badge (blue)',
				'.apollo-badge-role-promoter' => 'Promoter role badge (blue)',
				'.apollo-badge-unverified'    => 'Unverified user badge',
				'.apollo-badge-mod'           => 'Moderator badge',
				'.apollo-badge-reported'      => 'Reported content badge',
				'.status-badge'               => 'Status indicator badge',
				'.status-badge-expected'      => 'Expected status (orange)',
				'.status-badge-confirmed'     => 'Confirmed status (green)',
				'.status-badge-published'     => 'Published status (blue)',
			),
			'avatars'   => array(
				'.avatar'             => 'Base avatar class',
				'.avatar-small'       => '24px avatar',
				'.avatar-medium'      => '32px avatar',
				'.avatar-large'       => '40px avatar',
				'.avatar-xlarge'      => '56px avatar',
				'.avatar-with-status' => 'Avatar with online indicator',
			),
			'forms'     => array(
				'.box-search'  => 'Search input container',
				'.route-input' => 'Text input with icon',
				'.menutag'     => 'Filter/category tag button',
			),
			'layout'    => array(
				'.main-container' => 'Main page container',
				'.hero-section'   => 'Hero/header section',
				'.event_listings' => 'Event grid container',
				'.event_listing'  => 'Single event card',
				'.card-grid'      => 'Bento/masonry grid',
			),
			'utilities' => array(
				'.glass'           => 'Glassmorphism effect',
				'.no-scrollbar'    => 'Hide scrollbar',
				'.pb-safe'         => 'Safe area padding (iOS)',
				'.visually-hidden' => 'Screen reader only',
				'.disabled'        => 'Disabled state',
			),
			'colors'    => array(
				'.bg-orange-{0-1000}'     => 'Orange background scale',
				'.text-orange-{0-1000}'   => 'Orange text scale',
				'.border-orange-{0-1000}' => 'Orange border scale',
				'.bg-grama'               => 'Green gradient background',
				'.online'                 => 'Online status green',
			),
		);
	}
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', array( 'Apollo_Core\Global_Assets', 'init' ), 5 );
