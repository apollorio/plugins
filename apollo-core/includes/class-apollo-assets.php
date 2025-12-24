<?php
/**
 * Apollo Unified Asset Manager
 *
 * ============================================================================
 * UNIFIED ASSET ENQUEUER FOR APOLLO ECOSYSTEM
 * ============================================================================
 *
 * This class centralizes ALL asset registration and enqueueing for Apollo pages.
 * It serves LOCAL assets from plugin directories with NO CDN dependencies.
 *
 * Key Features:
 * - All assets served from apollo-core/assets/
 * - Unique prefixed handles (apollo-core-*, apollo-vendor-*)
 * - filemtime() versioning for cache busting
 * - Scoped loading via apollo_is_apollo_page()
 *
 * Asset Structure:
 *   apollo-core/assets/
 *     ├── core/           → Apollo Design System (uni.css, base.js, etc.)
 *     ├── css/            → Plugin-specific CSS
 *     ├── js/             → Plugin-specific JS
 *     ├── img/            → Images and icons
 *     └── vendor/         → Third-party libraries (Leaflet, RemixIcon, etc.)
 *
 * Usage:
 *   Apollo_Assets::init();                    // Called by apollo-core bootstrap.
 *   Apollo_Assets::enqueue_frontend();        // Auto-called on Apollo pages.
 *   Apollo_Assets::get_asset_url('core/uni.css');  // Get asset URL.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	/**
	 * Base path for assets (relative to plugin)
	 */
	public const ASSETS_DIR = 'assets/';

	/**
	 * Registered asset handles
	 */
	private static array $registered_handles = array();

	/**
	 * Whether frontend assets have been enqueued
	 */
	private static bool $frontend_enqueued = false;

	/**
	 * Whether admin assets have been enqueued
	 */
	private static bool $admin_enqueued = false;

	/**
	 * Cached apollo page check result
	 */
	private static ?bool $is_apollo_page_cached = null;

	/**
	 * Initialize the asset manager
	 *
	 * @return void
	 */
	public static function init(): void {
		// Register assets early (priority 5).
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_all' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_all' ), 5 );

		// Enqueue on Apollo pages (priority 10).
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_frontend' ), 10 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_admin' ), 10 );

		// Add snippet injection hooks (priority 100, after all assets).
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'inject_snippets' ), 100 );
	}

	/**
	 * Register all assets (but don't enqueue yet)
	 *
	 * @return void
	 */
	public static function register_all(): void {
		$base_url = self::get_assets_url();

		// =========================================================================.
		// VENDOR ASSETS (Third-party libraries).
		// =========================================================================.

		// RemixIcon.
		wp_register_style(
			'apollo-vendor-remixicon',
			$base_url . 'vendor/remixicon/remixicon.css',
			array(),
			self::get_version( 'vendor/remixicon/remixicon.css' )
		);
		self::$registered_handles['css'][] = 'apollo-vendor-remixicon';

		// Leaflet.
		wp_register_style(
			'apollo-vendor-leaflet',
			$base_url . 'vendor/leaflet/leaflet.css',
			array(),
			self::get_version( 'vendor/leaflet/leaflet.css' )
		);
		self::$registered_handles['css'][] = 'apollo-vendor-leaflet';

		wp_register_script(
			'apollo-vendor-leaflet',
			$base_url . 'vendor/leaflet/leaflet.js',
			array(),
			self::get_version( 'vendor/leaflet/leaflet.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-vendor-leaflet';

		// SortableJS.
		wp_register_script(
			'apollo-vendor-sortable',
			$base_url . 'vendor/sortablejs/Sortable.min.js',
			array(),
			self::get_version( 'vendor/sortablejs/Sortable.min.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-vendor-sortable';

		// Motion.
		wp_register_script(
			'apollo-vendor-motion',
			$base_url . 'vendor/motion/motion.min.js',
			array(),
			self::get_version( 'vendor/motion/motion.min.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-vendor-motion';

		// Phosphor Icons.
		wp_register_script(
			'apollo-vendor-phosphor',
			$base_url . 'vendor/phosphor-icons/phosphor-icons.js',
			array(),
			self::get_version( 'vendor/phosphor-icons/phosphor-icons.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-vendor-phosphor';

		// Chart.js
		wp_register_script(
			'apollo-vendor-chartjs',
			$base_url . 'vendor/chartjs/chart.umd.min.js',
			array(),
			self::get_version( 'vendor/chartjs/chart.umd.min.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-vendor-chartjs';

		// DataTables.
		wp_register_script(
			'apollo-vendor-datatables',
			$base_url . 'vendor/datatables/jquery.dataTables.min.js',
			array( 'jquery' ),
			self::get_version( 'vendor/datatables/jquery.dataTables.min.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-vendor-datatables';

		wp_register_style(
			'apollo-vendor-datatables',
			$base_url . 'vendor/datatables/jquery.dataTables.min.css',
			array(),
			self::get_version( 'vendor/datatables/jquery.dataTables.min.css' )
		);
		self::$registered_handles['css'][] = 'apollo-vendor-datatables';

		// =========================================================================.
		// CORE ASSETS (Apollo Design System).
		// =========================================================================.

		// UNI.CSS - Main design system
		wp_register_style(
			'apollo-core-uni',
			$base_url . 'core/uni.css',
			array( 'apollo-vendor-remixicon' ),
			self::get_version( 'core/uni.css' )
		);
		self::$registered_handles['css'][] = 'apollo-core-uni';

		// Animate.css - Animation library
		wp_register_style(
			'apollo-core-animate',
			$base_url . 'core/animate.css',
			array(),
			self::get_version( 'core/animate.css' )
		);
		self::$registered_handles['css'][] = 'apollo-core-animate';

		// Base.js - Global behaviors
		wp_register_script(
			'apollo-core-base',
			$base_url . 'core/base.js',
			array(),
			self::get_version( 'core/base.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-core-base';

		// Dark mode toggle.
		wp_register_script(
			'apollo-core-darkmode',
			$base_url . 'core/dark-mode.js',
			array( 'apollo-core-base' ),
			self::get_version( 'core/dark-mode.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-core-darkmode';

		// Clock widget.
		wp_register_script(
			'apollo-core-clock',
			$base_url . 'core/clock.js',
			array(),
			self::get_version( 'core/clock.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-core-clock';

		// Event page interactions.
		wp_register_script(
			'apollo-core-event-page',
			$base_url . 'core/event-page.js',
			array( 'apollo-core-base' ),
			self::get_version( 'core/event-page.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-core-event-page';

		// =========================================================================.
		// PLUGIN ASSETS (Apollo-specific).
		// =========================================================================.

		// Cookie consent.
		wp_register_style(
			'apollo-core-cookie-consent',
			$base_url . 'css/cookie-consent.css',
			array(),
			self::get_version( 'css/cookie-consent.css' )
		);
		self::$registered_handles['css'][] = 'apollo-core-cookie-consent';

		wp_register_script(
			'apollo-core-cookie-consent',
			$base_url . 'js/cookie-consent.js',
			array(),
			self::get_version( 'js/cookie-consent.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-core-cookie-consent';

		// CENA-RIO calendar.
		wp_register_script(
			'apollo-core-cena-calendar',
			$base_url . 'js/cena-rio-calendar.js',
			array(),
			self::get_version( 'js/cena-rio-calendar.js' ),
			true
		);
		self::$registered_handles['js'][] = 'apollo-core-cena-calendar';

		// =========================================================================.
		// LEGACY HANDLES (Backwards compatibility).
		// =========================================================================.

		// Map old handles to new ones.
		wp_register_style( 'apollo-uni-css', $base_url . 'core/uni.css', array( 'apollo-vendor-remixicon' ), self::get_version( 'core/uni.css' ) );
		wp_register_style( 'remixicon', $base_url . 'vendor/remixicon/remixicon.css', array(), self::get_version( 'vendor/remixicon/remixicon.css' ) );
		wp_register_style( 'apollo-remixicon', $base_url . 'vendor/remixicon/remixicon.css', array(), self::get_version( 'vendor/remixicon/remixicon.css' ) );
		wp_register_script( 'apollo-base-js', $base_url . 'core/base.js', array(), self::get_version( 'core/base.js' ), true );
		wp_register_script( 'apollo-motion', $base_url . 'vendor/motion/motion.min.js', array(), self::get_version( 'vendor/motion/motion.min.js' ), true );
		wp_register_script( 'framer-motion', $base_url . 'vendor/motion/motion.min.js', array(), self::get_version( 'vendor/motion/motion.min.js' ), true );
		wp_register_script( 'apollo-chartjs', $base_url . 'vendor/chartjs/chart.umd.min.js', array(), self::get_version( 'vendor/chartjs/chart.umd.min.js' ), true );
		wp_register_script( 'chartjs', $base_url . 'vendor/chartjs/chart.umd.min.js', array(), self::get_version( 'vendor/chartjs/chart.umd.min.js' ), true );
		wp_register_script( 'chart-js', $base_url . 'vendor/chartjs/chart.umd.min.js', array(), self::get_version( 'vendor/chartjs/chart.umd.min.js' ), true );
		wp_register_style( 'leaflet', $base_url . 'vendor/leaflet/leaflet.css', array(), self::get_version( 'vendor/leaflet/leaflet.css' ) );
		wp_register_script( 'leaflet', $base_url . 'vendor/leaflet/leaflet.js', array(), self::get_version( 'vendor/leaflet/leaflet.js' ), true );
		wp_register_script( 'datatables-js', $base_url . 'vendor/datatables/jquery.dataTables.min.js', array( 'jquery' ), self::get_version( 'vendor/datatables/jquery.dataTables.min.js' ), true );
		wp_register_style( 'datatables-css', $base_url . 'vendor/datatables/jquery.dataTables.min.css', array(), self::get_version( 'vendor/datatables/jquery.dataTables.min.css' ) );
	}

	/**
	 * Conditionally enqueue frontend assets
	 *
	 * @return void
	 */
	public static function maybe_enqueue_frontend(): void {
		if ( self::is_apollo_page() ) {
			self::enqueue_frontend();
		}
	}

	/**
	 * Enqueue frontend assets for Apollo pages
	 *
	 * @param bool $force Force enqueueing even if already done
	 * @return void
	 */
	public static function enqueue_frontend( bool $force = false ): void {
		if ( self::$frontend_enqueued && ! $force ) {
			return;
		}

		// Core CSS (includes RemixIcon as dependency).
		wp_enqueue_style( 'apollo-core-uni' );

		// Core JS.
		wp_enqueue_script( 'apollo-core-base' );
		wp_enqueue_script( 'apollo-core-darkmode' );

		// Add inline CSS for Apollo page body class.
		wp_add_inline_style( 'apollo-core-uni', self::get_inline_reset_css() );

		// Pass PHP data to JavaScript.
		wp_localize_script(
			'apollo-core-base',
			'apolloAssets',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'apollo_assets_nonce' ),
				'baseUrl'  => self::get_assets_url(),
				'imgUrl'   => self::get_assets_url() . 'img/',
				'isApollo' => true,
			)
		);

		self::$frontend_enqueued = true;

		/**
		 * Action fired after Apollo frontend assets are enqueued
		 *
		 * @since 2.0.0
		 */
		do_action( 'apollo_assets_enqueued' );
	}

	/**
	 * Conditionally enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public static function maybe_enqueue_admin( string $hook = '' ): void {
		// Only on Apollo admin pages.
		if ( self::is_apollo_admin_page( $hook ) ) {
			self::enqueue_admin();
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @return void
	 */
	public static function enqueue_admin(): void {
		if ( self::$admin_enqueued ) {
			return;
		}

		wp_enqueue_style( 'apollo-core-uni' );
		wp_enqueue_script( 'apollo-core-base' );

		self::$admin_enqueued = true;
	}

	/**
	 * Enqueue Leaflet for maps
	 *
	 * @return void
	 */
	public static function enqueue_leaflet(): void {
		wp_enqueue_style( 'apollo-vendor-leaflet' );
		wp_enqueue_script( 'apollo-vendor-leaflet' );
	}

	/**
	 * Enqueue event page assets
	 *
	 * @return void
	 */
	public static function enqueue_event_page(): void {
		self::enqueue_frontend();
		self::enqueue_leaflet();
		wp_enqueue_script( 'apollo-core-event-page' );
	}

	/**
	 * Inject CSS/JS snippets from Apollo Snippets Manager
	 *
	 * @return void
	 */
	public static function inject_snippets(): void {
		if ( ! self::is_apollo_page() ) {
			return;
		}

		// Get enabled snippets.
		$snippets = self::get_enabled_snippets();

		if ( empty( $snippets ) ) {
			return;
		}

		$css_output = '';
		$js_output  = '';

		foreach ( $snippets as $snippet ) {
			if ( 'css' === $snippet['type'] ) {
				$css_output .= "\n/* Snippet: " . esc_html( $snippet['title'] ) . " */\n";
				$css_output .= $snippet['code'] . "\n";
			} elseif ( 'js' === $snippet['type'] ) {
				$js_output .= "\n// Snippet: " . esc_js( $snippet['title'] ) . "\n";
				$js_output .= $snippet['code'] . "\n";
			}
		}

		if ( ! empty( $css_output ) ) {
			wp_add_inline_style( 'apollo-core-uni', $css_output );
		}

		if ( ! empty( $js_output ) ) {
			wp_add_inline_script( 'apollo-core-base', $js_output );
		}
	}

	/**
	 * Get enabled snippets from the database
	 *
	 * @return array
	 */
	private static function get_enabled_snippets(): array {
		if ( ! class_exists( 'Apollo_Core\Snippets_Manager' ) ) {
			return array();
		}

		return Snippets_Manager::get_enabled_snippets();
	}

	/**
	 * Get inline CSS for Apollo page reset
	 *
	 * @return string
	 */
	private static function get_inline_reset_css(): string {
		return '
/* Apollo Page Reset */
.apollo-canvas-mode body,
body.apollo-page {
	font-family: "Urbanist", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}
';
	}

	/**
	 * Check if current page is an Apollo page
	 *
	 * @return bool
	 */
	public static function is_apollo_page(): bool {
		// Return cached result if available.
		if ( null !== self::$is_apollo_page_cached ) {
			return self::$is_apollo_page_cached;
		}

		// Check for canvas mode.
		if ( function_exists( 'apollo_is_canvas_mode' ) && apollo_is_canvas_mode() ) {
			self::$is_apollo_page_cached = true;
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
			'/local/',
		);

		foreach ( $apollo_routes as $route ) {
			if ( false !== strpos( $request_uri, $route ) ) {
				self::$is_apollo_page_cached = true;
				return true;
			}
		}

		// Check for Apollo CPTs.
		$apollo_post_types = array( 'event_listing', 'event_local', 'event_dj', 'apollo_group', 'user_page', 'apollo_snippet' );
		if ( is_singular( $apollo_post_types ) || is_post_type_archive( $apollo_post_types ) ) {
			self::$is_apollo_page_cached = true;
			return true;
		}

		// Check stored Apollo page IDs.
		$apollo_page_ids = get_option( 'apollo_page_ids', array() );
		if ( ! empty( $apollo_page_ids ) && is_page( $apollo_page_ids ) ) {
			self::$is_apollo_page_cached = true;
			return true;
		}

		// Allow plugins to indicate Apollo page.
		$filtered = (bool) apply_filters( 'apollo_is_apollo_page', false );

		self::$is_apollo_page_cached = $filtered;
		return $filtered;
	}

	/**
	 * Check if current admin page is an Apollo admin page
	 *
	 * @param string $hook Admin page hook
	 * @return bool
	 */
	public static function is_apollo_admin_page( string $hook = '' ): bool {
		$apollo_admin_pages = array(
			'toplevel_page_apollo',
			'apollo_page_apollo-snippets',
			'apollo_page_apollo-settings',
			'apollo_page_apollo-cabin',
			'edit.php?post_type=event_listing',
			'edit.php?post_type=event_local',
			'edit.php?post_type=event_dj',
		);

		// Check hook.
		if ( in_array( $hook, $apollo_admin_pages, true ) ) {
			return true;
		}

		// Check screen.
		$screen = get_current_screen();
		if ( $screen ) {
			if ( in_array( $screen->post_type, array( 'event_listing', 'event_local', 'event_dj', 'apollo_snippet' ), true ) ) {
				return true;
			}
			if ( false !== strpos( $screen->id, 'apollo' ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'apollo_is_apollo_admin_page', false, $hook );
	}

	/**
	 * Get assets base URL
	 *
	 * @return string
	 */
	public static function get_assets_url(): string {
		return APOLLO_CORE_PLUGIN_URL . self::ASSETS_DIR;
	}

	/**
	 * Get assets base path
	 *
	 * @return string
	 */
	public static function get_assets_path(): string {
		return APOLLO_CORE_PLUGIN_DIR . self::ASSETS_DIR;
	}

	/**
	 * Get full URL for a specific asset
	 *
	 * @param string $relative_path Path relative to assets/
	 * @return string
	 */
	public static function get_asset_url( string $relative_path ): string {
		return self::get_assets_url() . ltrim( $relative_path, '/' );
	}

	/**
	 * Get full path for a specific asset
	 *
	 * @param string $relative_path Path relative to assets/
	 * @return string
	 */
	public static function get_asset_path( string $relative_path ): string {
		return self::get_assets_path() . ltrim( $relative_path, '/' );
	}

	/**
	 * Get image URL
	 *
	 * @param string $filename Image filename
	 * @return string
	 */
	public static function get_img_url( string $filename ): string {
		return self::get_asset_url( 'img/' . $filename );
	}

	/**
	 * Get asset version using filemtime
	 *
	 * @param string $relative_path Path relative to assets/
	 * @return string
	 */
	public static function get_version( string $relative_path ): string {
		$file_path = self::get_asset_path( $relative_path );

		if ( file_exists( $file_path ) ) {
			return (string) filemtime( $file_path );
		}

		// Fallback to plugin version.
		return defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : '1.0.0';
	}

	/**
	 * Get all registered handles
	 *
	 * @return array
	 */
	public static function get_registered_handles(): array {
		return self::$registered_handles;
	}

	/**
	 * Reset cached apollo page check (for testing)
	 *
	 * @return void
	 */
	public static function reset_cache(): void {
		self::$is_apollo_page_cached = null;
		self::$frontend_enqueued     = false;
		self::$admin_enqueued        = false;
	}
}
