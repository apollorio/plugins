<?php
/**
 * Assets Loader - Apollo Design System
 *
 * Ensures all templates load uni.css and base.js from CDN
 * and provides consistent asset loading across all plugins.
 *
 * @package ApolloCore\Assets
 */

class Apollo_Assets_Loader {
	/**
	 * CDN base URL
	 */
	const CDN_BASE = 'https://assets.apollo.rio.br/';

	/**
	 * Core assets that must be loaded on all pages
	 */
	const CORE_ASSETS = array(
		'css' => array(
			'uni.css' => 'uni-css',
		),
		'js'  => array(
			'base.js' => 'uni-js',
		),
	);

	/**
	 * Additional assets for specific contexts
	 */
	const CONTEXT_ASSETS = array(
		'events' => array(
			'css' => array(),
			'js'  => array(),
		),
		'social' => array(
			'css' => array(),
			'js'  => array(),
		),
		'admin'  => array(
			'css' => array(),
			'js'  => array(),
		),
	);

	/**
	 * Initialize the assets loader
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_core_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue core assets for frontend
	 *
	 * @return void
	 */
	public static function enqueue_core_assets() {
		// Load core CSS.
		foreach ( self::CORE_ASSETS['css'] as $file => $handle ) {
			wp_enqueue_style(
				$handle,
				self::CDN_BASE . $file,
				array(),
				self::get_asset_version( $file ),
				'all'
			);
		}

		// Load core JS.
		foreach ( self::CORE_ASSETS['js'] as $file => $handle ) {
			wp_enqueue_script(
				$handle,
				self::CDN_BASE . $file,
				array(),
				self::get_asset_version( $file ),
				true
			);
		}

		// Load RemixIcon via icon.js (SVG-based icon loader)
		wp_enqueue_script(
			'apollo-icon-loader',
			self::CDN_BASE . 'icon.js',
			array(),
			'1.0.0',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		// Load core assets in admin if needed.
		if ( self::should_load_in_admin( $hook ) ) {
			self::enqueue_core_assets();
		}

		// Load admin-specific assets.
		foreach ( self::CONTEXT_ASSETS['admin']['css'] as $file => $handle ) {
			wp_enqueue_style(
				$handle,
				self::CDN_BASE . $file,
				array(),
				self::get_asset_version( $file ),
				'all'
			);
		}

		foreach ( self::CONTEXT_ASSETS['admin']['js'] as $file => $handle ) {
			wp_enqueue_script(
				$handle,
				self::CDN_BASE . $file,
				array(),
				self::get_asset_version( $file ),
				true
			);
		}
	}

	/**
	 * Enqueue context-specific assets
	 *
	 * @param string $context Context (events, social, etc.)
	 * @return void
	 */
	public static function enqueue_context_assets( $context ) {
		if ( ! isset( self::CONTEXT_ASSETS[ $context ] ) ) {
			return;
		}

		$assets = self::CONTEXT_ASSETS[ $context ];

		// Load context CSS.
		foreach ( $assets['css'] as $file => $handle ) {
			wp_enqueue_style(
				$handle,
				self::CDN_BASE . $file,
				array_keys( self::CORE_ASSETS['css'] ),
				self::get_asset_version( $file ),
				'all'
			);
		}

		// Load context JS.
		foreach ( $assets['js'] as $file => $handle ) {
			wp_enqueue_script(
				$handle,
				self::CDN_BASE . $file,
				array_keys( self::CORE_ASSETS['js'] ),
				self::get_asset_version( $file ),
				true
			);
		}
	}

	/**
	 * Get asset version for cache busting
	 *
	 * @param string $filename Asset filename
	 * @return string Version string
	 */
	private static function get_asset_version( $filename ) {
		// In production, you might want to use file modification time or git hash.
		// For now, using a simple version.
		$versions = array(
			'uni.css' => '1.0.0',
			'base.js' => '1.0.0',
		);

		return $versions[ $filename ] ?? '1.0.0';
	}

	/**
	 * Check if core assets should be loaded in admin
	 *
	 * @param string $hook Current admin page hook
	 * @return bool
	 */
	private static function should_load_in_admin( $hook ) {
		// Load on Apollo-specific admin pages.
		$apollo_pages = array(
			'toplevel_page_apollo-events',
			'events_page_apollo-events-settings',
			'toplevel_page_apollo-social',
			'social_page_apollo-social-settings',
		);

		return in_array( $hook, $apollo_pages );
	}

	/**
	 * Force load assets for specific templates
	 *
	 * Call this in template files that need to ensure assets are loaded
	 *
	 * @param string $context Optional context
	 * @return void
	 */
	public static function ensure_assets_loaded( $context = '' ) {
		if ( ! wp_style_is( 'uni-css', 'enqueued' ) ) {
			self::enqueue_core_assets();
		}

		if ( $context && isset( self::CONTEXT_ASSETS[ $context ] ) ) {
			self::enqueue_context_assets( $context );
		}
	}

	/**
	 * Get CDN URL for an asset
	 *
	 * @param string $asset Asset path relative to CDN
	 * @return string Full CDN URL
	 */
	public static function get_cdn_url( $asset ) {
		return self::CDN_BASE . ltrim( $asset, '/' );
	}

	/**
	 * Verify CDN assets are accessible
	 *
	 * @return array Status of each core asset
	 */
	public static function verify_cdn_assets() {
		$results = array();

		foreach ( self::CORE_ASSETS['css'] as $file => $handle ) {
			$url              = self::get_cdn_url( $file );
			$response         = wp_remote_head( $url );
			$results[ $file ] = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		}

		foreach ( self::CORE_ASSETS['js'] as $file => $handle ) {
			$url              = self::get_cdn_url( $file );
			$response         = wp_remote_head( $url );
			$results[ $file ] = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		}

		return $results;
	}
}
