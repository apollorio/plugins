<?php

/**
 * Apollo Global Assets Manager
 *
 * ============================================================================
 * APOLLO VISUAL ID CORE
 * ============================================================================
 *
 * This class manages the OFFICIAL Apollo Visual Identity assets.
 * These 4 files form the foundation of ALL Apollo UI:
 *
 *   - uni.css       = Design system (layout, typography, components, utilities)
 *   - base.js       = Global behaviors (dark mode, menus, tooltips, typewriter)
 *   - event-page.js = Event page interactions (sliders, maps, favorites)
 *   - animate.css   = Unified animation library (transitions, effects)
 *
 * Source Files (Development):
 *   apollo-core/templates/design-library/global assets-apollo-rio-br/
 *
 * CDN URLs (Production):
 *   https://assets.apollo.rio.br/uni.css
 *   https://assets.apollo.rio.br/base.js
 *   https://assets.apollo.rio.br/event-page.js
 *   https://assets.apollo.rio.br/animate.css
 *
 * ============================================================================
 *
 * Usage:
 *   - Apollo_Global_Assets::enqueue_all()     // Enqueue all global assets
 *   - Apollo_Global_Assets::enqueue_css()     // Enqueue only CSS
 *   - Apollo_Global_Assets::enqueue_js()      // Enqueue only JS
 *   - Apollo_Global_Assets::get_asset_url()   // Get asset URL (CDN or local)
 *
 * Toggle CDN/Local:
 *   - apollo_set_use_cdn(false)               // Use local files (dev)
 *   - apollo_set_use_cdn(true)                // Use CDN (prod, default)
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Global_Assets
 *
 * Manages global Apollo design system assets (UNI.CSS).
 */
class Apollo_Global_Assets {

	/**
	 * CDN base URL for production assets
	 */
	public const CDN_BASE_URL = 'https://assets.apollo.rio.br/';

	/**
	 * Local fallback path relative to apollo-core plugin
	 */
	public const LOCAL_BASE_PATH = 'templates/design-library/global assets-apollo-rio-br/';

	/**
	 * Whether to use CDN (true) or local assets (false)
	 * Set to false for local development
	 */
	private static $use_cdn = true;

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
		// Allow filtering CDN usage
		self::$use_cdn = (bool) apply_filters( 'apollo_use_cdn_assets', self::$use_cdn );

		// Register assets on init
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );

		// Auto-enqueue on Apollo pages
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_auto_enqueue' ), 10 );
	}

	/**
	 * Register all global assets (but don't enqueue yet)
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		// Register uni.css
		wp_register_style(
			'apollo-uni-css',
			self::get_asset_url( 'uni.css' ),
			array(),
			self::get_asset_version( 'uni.css' )
		);

		// Register RemixIcon (dependency for uni.css icons)
		wp_register_style(
			'apollo-remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Register animate.css
		wp_register_style(
			'apollo-animate-css',
			self::get_asset_url( 'animate.css' ),
			array(),
			self::get_asset_version( 'animate.css' )
		);

		// Register base.js (global behaviors)
		wp_register_script(
			'apollo-base-js',
			self::get_asset_url( 'base.js' ),
			array(),
			self::get_asset_version( 'base.js' ),
			true
		);

		// Register event-page.js (event page interactions)
		wp_register_script(
			'apollo-event-page-js',
			self::get_asset_url( 'event-page.js' ),
			array(),
			self::get_asset_version( 'event-page.js' ),
			true
		);

		// Register Motion library
		wp_register_script(
			'apollo-motion',
			'https://cdn.jsdelivr.net/npm/motion@latest/dist/motion.umd.js',
			array(),
			null,
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
		// Check for canvas mode
		if ( function_exists( 'apollo_is_canvas_mode' ) && apollo_is_canvas_mode() ) {
			return true;
		}

		// Check URL patterns
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

		// Check for Apollo CPTs
		$apollo_post_types = array( 'event_listing', 'event_local', 'event_dj', 'apollo_group', 'user_page' );
		if ( is_singular( $apollo_post_types ) || is_post_type_archive( $apollo_post_types ) ) {
			return true;
		}

		// Allow plugins to indicate Apollo page
		return (bool) apply_filters( 'apollo_is_apollo_page', false );
	}

	/**
	 * Enqueue all global assets (CSS + JS)
	 *
	 * @return void
	 */
	public static function enqueue_all(): void {
		self::enqueue_css();
		self::enqueue_js();
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

		// Enqueue RemixIcon first
		wp_enqueue_style( 'apollo-remixicon' );

		// Enqueue uni.css
		wp_enqueue_style( 'apollo-uni-css' );

		// Add inline CSS for theme reset on Apollo pages
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
	 * Enqueue global JS assets
	 *
	 * @param bool $force Force enqueueing even if already done
	 * @return void
	 */
	public static function enqueue_js( bool $force = false ): void {
		if ( self::$js_enqueued && ! $force ) {
			return;
		}

		// Enqueue Motion library
		wp_enqueue_script( 'apollo-motion' );

		// Enqueue base.js
		wp_enqueue_script( 'apollo-base-js' );

		// Localize script with Apollo data
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

		self::$js_enqueued = true;

		/**
		 * Action: apollo_global_js_enqueued
		 * Fired after global JS assets are enqueued
		 */
		do_action( 'apollo_global_js_enqueued' );
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

		// Local fallback
		if ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ) {
			return APOLLO_CORE_PLUGIN_URL . self::LOCAL_BASE_PATH . ltrim( $asset, '/' );
		}

		// Ultimate fallback to CDN
		return self::CDN_BASE_URL . ltrim( $asset, '/' );
	}

	/**
	 * Get asset version for cache busting
	 *
	 * @param string $asset Asset filename
	 * @return string Version string
	 */
	public static function get_asset_version( string $asset ): string {
		// In development, use file modification time
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

			// Check handle prefix
			if ( strpos( $handle, 'apollo-' ) === 0 ) {
				$keep = true;
			}

			// Check src patterns
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

// Initialize on plugins_loaded
add_action( 'plugins_loaded', array( 'Apollo_Global_Assets', 'init' ), 5 );
