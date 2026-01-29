<?php

declare(strict_types=1);
/**
 * Apollo Map Provider - Single Source of Truth for Leaflet Tilesets
 *
 * Provides centralized tileset configuration for all Apollo plugins.
 * Prevents hardcoded tile URLs across the codebase.
 *
 * @package Apollo_Core
 * @since 3.2.0
 */

namespace Apollo\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Map_Provider
 *
 * Singleton provider for Leaflet tileset configuration.
 * All Apollo plugins should use this instead of hardcoded tile URLs.
 *
 * Usage PHP:
 *   $config = Apollo_Map_Provider::get_instance()->get_tileset( [ 'context' => 'event-modal' ] );
 *
 * Usage JS:
 *   L.tileLayer( window.apolloMapTileset.url, window.apolloMapTileset.options ).addTo( map );
 */
class Apollo_Map_Provider {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Map_Provider|null
	 */
	private static $instance = null;

	/**
	 * Tileset configurations.
	 *
	 * @var array
	 */
	private $tilesets = [];

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Map_Provider
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize tileset configurations.
	 */
	private function __construct() {
		$this->init_tilesets();
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tileset_config' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_tileset_config' ], 5 );
	}

	/**
	 * Initialize available tileset configurations.
	 *
	 * STRICT MODE: All tile URLs MUST be defined here only.
	 * No hardcoded URLs allowed in other files.
	 */
	private function init_tilesets() {
		$this->tilesets = [
			'default' => [
				'id'          => 'osm-standard',
				'name'        => 'OSM Standard',
				'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
				'subdomains'  => 'abc',
				'maxZoom'     => 19,
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			],
			'light'   => [
				'id'          => 'carto-positron',
				'name'        => 'Carto Positron (Light)',
				'url'         => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
				'subdomains'  => 'abcd',
				'maxZoom'     => 22,
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			],
			'dark'    => [
				'id'          => 'carto-dark',
				'name'        => 'Carto Dark',
				'url'         => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
				'subdomains'  => 'abcd',
				'maxZoom'     => 22,
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			],
		];
	}

	/**
	 * Get tileset configuration.
	 *
	 * @param array $context Optional context for filtering (e.g., ['page' => 'mapa', 'plugin' => 'apollo-social']).
	 * @return array Tileset configuration with url, subdomains, maxZoom, attribution.
	 */
	public function get_tileset( array $context = [] ) {
		// Read the existing WP option from apollo-events-manager.
		$style = get_option( 'event_manager_osm_tile_style', 'default' );

		// Validate style.
		if ( ! isset( $this->tilesets[ $style ] ) ) {
			$style = 'default';
		}

		$config = $this->tilesets[ $style ];

		/**
		 * Filter the map tileset configuration.
		 *
		 * Allows themes/plugins to override tileset per context.
		 *
		 * @since 3.2.0
		 *
		 * @param array $config  Tileset configuration (id, name, url, subdomains, maxZoom, attribution).
		 * @param array $context Context array with keys like 'page', 'plugin', 'route'.
		 */
		return apply_filters( 'apollo_map_tileset', $config, $context );
	}

	/**
	 * Get all available tilesets.
	 *
	 * @return array All tileset configurations.
	 */
	public function get_all_tilesets() {
		return $this->tilesets;
	}

	/**
	 * Get current style ID.
	 *
	 * @return string Current style (default|light|dark).
	 */
	public function get_current_style() {
		$style = get_option( 'event_manager_osm_tile_style', 'default' );
		return isset( $this->tilesets[ $style ] ) ? $style : 'default';
	}

	/**
	 * Enqueue tileset configuration as JavaScript global.
	 *
	 * Only enqueues on pages that need maps.
	 */
	public function enqueue_tileset_config() {
		// Check if we're on a page that needs maps.
		if ( ! $this->should_load_map_assets() ) {
			return;
		}

		// Register the tileset JS helper.
		wp_register_script(
			'apollo-map-tileset',
			APOLLO_CORE_PLUGIN_URL . 'assets/js/apollo-map-tileset.js',
			[], // No dependencies - loads before Leaflet usage.
			defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : '3.2.0',
			false // Load in head so it's available before map init.
		);

		// Get tileset config and localize.
		$config = $this->get_tileset( [ 'source' => 'enqueue' ] );

		wp_localize_script(
			'apollo-map-tileset',
			'apolloMapTileset',
			[
				'id'          => $config['id'],
				'url'         => $config['url'],
				'options'     => [
					'subdomains'  => $config['subdomains'],
					'maxZoom'     => $config['maxZoom'],
					'attribution' => $config['attribution'],
				],
				'attribution' => $config['attribution'], // Also at top level for easy access.
			]
		);

		wp_enqueue_script( 'apollo-map-tileset' );
	}

	/**
	 * Determine if current page needs map assets.
	 *
	 * @return bool True if map assets should be loaded.
	 */
	private function should_load_map_assets() {
		// Admin pages with maps.
		if ( is_admin() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && in_array( $screen->id, [ 'apollo-hub', 'event', 'toplevel_page_apollo-hub' ], true ) ) {
				return true;
			}
		}

		// Frontend: Check for map-related pages/templates.
		global $wp;
		$current_url = isset( $wp->request ) ? $wp->request : '';

		// Known map routes.
		$map_routes = [
			'mapa',
			'events',
			'evento',
			'cena',
			'cena-rio', // Legacy route.
		];

		foreach ( $map_routes as $route ) {
			if ( false !== strpos( $current_url, $route ) ) {
				return true;
			}
		}

		// Check for shortcodes that use maps.
		global $post;
		if ( $post && is_a( $post, 'WP_Post' ) ) {
			$map_shortcodes = [ 'cena_rio', 'apollo_map', 'event_map' ];
			foreach ( $map_shortcodes as $shortcode ) {
				if ( has_shortcode( $post->post_content, $shortcode ) ) {
					return true;
				}
			}
		}

		// Allow other plugins to force-load map assets.
		return apply_filters( 'apollo_should_load_map_assets', false );
	}

	/**
	 * Debug helper - Assert no hardcoded tiles in a file.
	 * Use in development to catch regressions.
	 *
	 * @param string $file_path Path to file to check.
	 * @return bool True if clean, false if hardcoded URLs found.
	 */
	public static function assert_no_hardcoded_tiles( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return true;
		}

		$content = file_get_contents( $file_path );

		// Patterns that should NOT appear outside this provider.
		$forbidden_patterns = [
			'tile.openstreetmap.org',
			'basemaps.cartocdn.com',
			'tiles.mapbox.com',
		];

		foreach ( $forbidden_patterns as $pattern ) {
			if ( false !== strpos( $content, $pattern ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					trigger_error(
						sprintf(
							'[Apollo Map Provider] Hardcoded tile URL found in %s: %s',
							$file_path,
							$pattern
						),
						E_USER_WARNING
					);
				}
				return false;
			}
		}

		return true;
	}
}

// Initialize singleton on plugins_loaded.
add_action( 'plugins_loaded', function() {
	Apollo_Map_Provider::get_instance();
}, 5 );
