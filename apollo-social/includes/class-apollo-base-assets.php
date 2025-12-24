<?php
/**
 * Apollo Base Assets Manager - SINGLE SOURCE OF TRUTH
 *
 * STRICT MODE: Only loads base.js from Apollo CDN.
 * base.js automatically handles: uni.css, normalize.css, icons, dark-mode, etc.
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
 * DO NOT enqueue uni.css or normalize.css separately - base.js handles everything.
 */
class Apollo_Base_Assets {

	/**
	 * CDN base URL
	 */
	const CDN_URL = 'https://assets.apollo.rio.br/';

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
		echo '<link rel="preconnect" href="https://assets.apollo.rio.br" crossorigin>' . "\n";
		echo '<link rel="dns-prefetch" href="https://assets.apollo.rio.br">' . "\n";
	}

	/**
	 * Enqueue base assets for frontend
	 */
	public static function enqueue_base_assets() {
		if ( self::$loaded ) {
			return;
		}

		self::enqueue_base_js();
		self::$loaded = true;
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
	 * base.js auto-loads: uni.css, normalize.css, icons.js, dark-mode.js, etc.
	 */
	private static function enqueue_base_js() {
		wp_enqueue_script(
			'apollo-base',
			self::CDN_URL . 'base.js',
			array(),
			null, // Let CDN handle versioning
			false // Load in head, not footer (base.js needs to run early)
		);

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
		wp_add_inline_script( 'apollo-base', $init_script, 'after' );
	}

	/**
	 * Remove duplicate assets that other plugins might have enqueued
	 */
	public static function remove_duplicate_assets() {
		// List of handles to dequeue (duplicates of what base.js loads)
		$duplicate_handles = array(
			'apollo-uni-css',
			'apollo-uni',
			'apollo-core-uni',
			'apollo-normalize',
			'apollo-base-js',
			'apollo-base-js-local',
			'apollo-core-base',
			'apollo-remixicon',
		);

		foreach ( $duplicate_handles as $handle ) {
			if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
			if ( wp_script_is( $handle, 'enqueued' ) || wp_script_is( $handle, 'registered' ) ) {
				// Don't dequeue apollo-base if it's our own
				if ( $handle !== 'apollo-base' ) {
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
}

// Initialize - loads base.js on ALL pages
Apollo_Base_Assets::init();

/**
 * Global helper function for other plugins to check/trigger asset loading
 */
if ( ! function_exists( 'apollo_ensure_base_assets' ) ) {
	function apollo_ensure_base_assets() {
		if ( ! Apollo_Base_Assets::is_loaded() ) {
			Apollo_Base_Assets::enqueue_base_assets();
		}
	}
}
