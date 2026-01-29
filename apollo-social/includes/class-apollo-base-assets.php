<?php
/**
 * Apollo Base Assets Manager - SINGLE SOURCE OF TRUTH
 *
 * STRICT MODE: Only loads CDN loader (index.min.js) from Apollo Assets CDN.
 * The CDN loader automatically handles: styles/index.css, icons, dark-mode, scroll, etc.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Base_Assets
 *
 * Central asset loader for all Apollo plugins.
 * DO NOT enqueue uni.css or normalize.css separately - CDN loader handles everything.
 */
class Apollo_Base_Assets {

	/**
	 * CDN base URL
	 */
	const CDN_URL = 'https://assets.apollo.rio.br/';

	/**
	 * CDN Loader version
	 */
	const CDN_VERSION = '4.3.0';

	/**
	 * Flag to track if assets are already loaded
	 */
	private static $loaded = false;

	/**
	 * Initialize asset enqueuing
	 */
	public static function init() {
		// Load on frontend early (priority 1) to be first
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_base_assets' ), 1 );

		// Load on admin if needed
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ), 1 );

		// Remove duplicate asset loading from other sources
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_duplicate_assets' ), 999 );

		// Add preconnect hints early
		add_action( 'wp_head', array( __CLASS__, 'add_preconnect_hints' ), 1 );
	}

	/**
	 * Add preconnect hints for CDN performance
	 */
	public static function add_preconnect_hints() {
		if ( self::$loaded ) {
			return;
		}
		echo '<link rel="preconnect" href="https://cdn.apollo.rio.br" crossorigin>' . "\n";
		echo '<link rel="dns-prefetch" href="https://cdn.apollo.rio.br">' . "\n";
	}

	/**
	 * Enqueue base assets for frontend
	 *
	 * ULTRA PRO: Loads on ALL Apollo templates, pages, and CPTs.
	 * Detection order:
	 *   1. Apollo Rio templates (pagx_site, pagx_app, etc.)
	 *   2. Apollo Core templates (page-home.php, etc.)
	 *   3. Apollo CPTs (event_listing, user_page, apollo_classified, etc.)
	 *   4. Specific Apollo pages (mapa, home, etc.)
	 *   5. Force-loaded via filter or direct call
	 */
	public static function enqueue_base_assets() {
		if ( self::$loaded ) {
			return;
		}

		// Always load if explicitly requested (direct call to apollo_ensure_base_assets)
		if ( did_action( 'apollo_force_base_assets' ) ) {
			self::enqueue_base_js();
			self::$loaded = true;
			return;
		}

		// Check if we should load assets
		if ( self::should_load_assets() ) {
			self::enqueue_base_js();
			self::$loaded = true;
		}
	}

	/**
	 * Determine if Apollo base assets should be loaded on current page
	 *
	 * @return bool True if assets should load
	 */
	public static function should_load_assets() {
		global $post;

		// Admin always handled separately
		if ( is_admin() ) {
			return false;
		}

		// Filter to force enable/disable
		$force = apply_filters( 'apollo_force_base_assets', null );
		if ( $force !== null ) {
			return (bool) $force;
		}

		// 1. Check Apollo Rio templates
		if ( $post && self::is_apollo_rio_template( $post->ID ) ) {
			return true;
		}

		// 2. Check Apollo Core templates
		if ( $post && self::is_apollo_core_template( $post->ID ) ) {
			return true;
		}

		// 3. Check Apollo CPTs
		if ( self::is_apollo_cpt() ) {
			return true;
		}

		// 4. Check specific Apollo pages
		$apollo_pages = array( 'mapa', 'home', 'eventos', 'classificados', 'comunas', 'nucleos', 'documentos', 'agenda' );
		if ( is_page( $apollo_pages ) ) {
			return true;
		}

		// 5. Check Apollo taxonomy archives
		if ( self::is_apollo_taxonomy() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current post uses Apollo Rio template
	 *
	 * @param int $post_id Post ID
	 * @return bool
	 */
	private static function is_apollo_rio_template( $post_id ) {
		$template = get_post_meta( $post_id, '_wp_page_template', true );
		if ( empty( $template ) ) {
			return false;
		}

		$apollo_rio_templates = array(
			'pagx_site.php',
			'pagx_app.php',
			'pagx_apolloapp.php',
			'pagx_appclean.php',
			'pwa-redirector.php',
		);

		return in_array( $template, $apollo_rio_templates, true );
	}

	/**
	 * Check if current post uses Apollo Core template
	 *
	 * @param int $post_id Post ID
	 * @return bool
	 */
	private static function is_apollo_core_template( $post_id ) {
		$template = get_post_meta( $post_id, '_wp_page_template', true );
		if ( empty( $template ) ) {
			return false;
		}

		$apollo_core_templates = array(
			'page-home.php',
			'page-apollo.php',
		);

		return in_array( $template, $apollo_core_templates, true );
	}

	/**
	 * Check if viewing Apollo CPT
	 *
	 * @return bool
	 */
	private static function is_apollo_cpt() {
		$apollo_cpts = array(
			'event_listing',
			'event_dj',
			'event_local',
			'apollo_classified',
			'apollo_social_post',
			'user_page',
			'apollo_supplier',
			'apollo_document',
			'cena_document',
			'cena_event_plan',
		);

		if ( is_singular( $apollo_cpts ) || is_post_type_archive( $apollo_cpts ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if viewing Apollo taxonomy archive
	 *
	 * @return bool
	 */
	private static function is_apollo_taxonomy() {
		$apollo_taxonomies = array(
			'event_listing_category',
			'event_listing_type',
			'event_listing_tag',
			'event_sounds',
			'event_season',
			'classified_domain',
			'classified_intent',
			'apollo_supplier_category',
		);

		foreach ( $apollo_taxonomies as $tax ) {
			if ( is_tax( $tax ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue base assets for admin (selective)
	 */
	public static function enqueue_admin_assets() {
		// Only load on Apollo-related admin pages
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$apollo_screens = array(
			'apollo',
			'evento',
			'dj',
			'local',
			'classified',
			'documento',
		);

		$load_assets = false;
		foreach ( $apollo_screens as $slug ) {
			if ( strpos( $screen->id, $slug ) !== false ) {
				$load_assets = true;
				break;
			}
		}

		if ( $load_assets ) {
			self::enqueue_base_js();
		}
	}

	/**
	 * Enqueue base.js from CDN - THE ONLY SCRIPT NEEDED
	 * CDN loader (index.min.js) auto-loads: styles/index.css, icons, dark-mode, scroll, etc.
	 */
	private static function enqueue_base_js() {
		// Apollo CDN Loader - handles all design system assets
		wp_enqueue_script(
			'apollo-cdn-loader',
			self::CDN_URL . 'index.min.js',
			array(),
			self::CDN_VERSION,
			true
		);

		// Register empty style for inline styles attachment
		wp_register_style( 'apollo-inline', false );
		wp_enqueue_style( 'apollo-inline' );

		// Add initialization hook
		$init_script = "
		window.apolloBaseLoaded = true;
		window.apolloReady = window.apolloReady || function(fn) {
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', fn);
			} else {
				fn();
			}
		};
		";
		wp_add_inline_script( 'apollo-cdn-loader', $init_script, 'after' );
	}

	/**
	 * Remove duplicate assets that other plugins might have enqueued
	 */
	public static function remove_duplicate_assets() {
		// List of handles to dequeue (duplicates of what CDN loader provides)
		$duplicate_handles = array(
			'apollo-uni-css',
			'apollo-uni',
			'apollo-core-uni',
			'apollo-normalize',
			'apollo-base-js',
			'apollo-base-js-local',
			'apollo-core-base',
			'apollo-remixicon',
			'remixicon',
			'apollo-base',
		);

		foreach ( $duplicate_handles as $handle ) {
			if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
			if ( wp_script_is( $handle, 'enqueued' ) || wp_script_is( $handle, 'registered' ) ) {
				// Don't dequeue apollo-cdn-loader if it's our own
				if ( $handle !== 'apollo-cdn-loader' ) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
				}
			}
		}
	}

	/**
	 * Check if base assets are loaded
	 */
	public static function is_loaded() {
		return self::$loaded;
	}

	/**
	 * Force load base assets (bypasses all checks)
	 * Used by templates that need assets regardless of detection logic
	 */
	public static function force_load() {
		if ( self::$loaded ) {
			return;
		}
		do_action( 'apollo_force_base_assets' );
		self::enqueue_base_js();
		self::$loaded = true;
	}
}

// Initialize - loads base.js on ALL Apollo pages
Apollo_Base_Assets::init();

/**
 * Global helper function for other plugins to check/trigger asset loading
 *
 * ULTRA PRO: When called directly (not via hook), forces asset loading.
 * This ensures templates like pagx_site.php always get their assets.
 */
if ( ! function_exists( 'apollo_ensure_base_assets' ) ) {
	function apollo_ensure_base_assets() {
		// Force load when explicitly called (template-driven)
		Apollo_Base_Assets::force_load();
	}
}