<?php
/**
 * Apollo Base Assets Manager
 *
 * Enqueues base.js from Apollo CDN (https://assets.apollo.rio.br/base.js)
 * with local fallback on all pages.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Base_Assets
 */
class Apollo_Base_Assets {

	/**
	 * Initialize asset enqueuing
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_base_js' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_base_js' ), 5 );
	}

	/**
	 * Enqueue base.js from Apollo CDN with local fallback
	 */
	public static function enqueue_base_js() {
		$cdn_url = 'https://assets.apollo.rio.br/base.js';
		$local_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/base.js';
		$local_url = APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/base.js';

		// Try CDN first (main source - work in progress).
		$script_url = $cdn_url;
		$version = time(); // Cache busting for WIP.

		// Fallback to local if CDN fails or for development.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// In debug mode, prefer local if exists.
			if ( file_exists( $local_path ) ) {
				$script_url = $local_url;
				$version = filemtime( $local_path ) ?: APOLLO_SOCIAL_VERSION;
			}
		} else {
			// Production: use CDN, but have local as backup.
			// WordPress will automatically fallback if CDN fails.
		}

		wp_enqueue_script(
			'apollo-base-js',
			$script_url,
			array(), // No dependencies - base.js is standalone.
			$version,
			true
		);

		// Register local as fallback (WordPress doesn't auto-fallback, so we handle it).
		if ( file_exists( $local_path ) ) {
			wp_register_script(
				'apollo-base-js-local',
				$local_url,
				array(),
				filemtime( $local_path ) ?: APOLLO_SOCIAL_VERSION,
				true
			);

			// Add inline script to fallback if CDN fails.
			$fallback_script = "
			(function() {
				var script = document.querySelector('script[src*=\"assets.apollo.rio.br/base.js\"]');
				if (script && !window.apolloBaseLoaded) {
					script.onerror = function() {
						var fallback = document.createElement('script');
						fallback.src = '" . esc_js( $local_url ) . "';
						fallback.onload = function() { window.apolloBaseLoaded = true; };
						document.head.appendChild(fallback);
					};
					script.onload = function() { window.apolloBaseLoaded = true; };
				}
			})();
			";
			wp_add_inline_script( 'apollo-base-js', $fallback_script, 'after' );
		}
	}
}

// Initialize - loads on ALL pages.
Apollo_Base_Assets::init();

