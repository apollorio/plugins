<?php
/**
 * Apollo Core
 *
 * @package Apollo_Core
 * @license GPL-2.0-or-later
 *
 * Copyright (c) 2026 Apollo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 */
/**
 * Apollo Asset Optimizer
 *
 * PHASE 8: Optimization - Conditional Asset Loading
 *
 * Optimizes asset loading by:
 * - Conditional loading (only load when needed)
 * - Defer non-critical scripts
 * - Preload critical assets
 * - Combine related stylesheets
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset Optimizer Class
 *
 * Handles optimization of asset loading for better performance.
 *
 * @since 2.0.0
 */
class Asset_Optimizer {

	/**
	 * Critical CSS handles that should be preloaded.
	 *
	 * @var string[]
	 */
	private static array $critical_styles = array(
		'apollo-core-uni',
		'apollo-core-base',
		'apollo-vendor-remixicon',
		'apollo-auth-styles',
	);

	/**
	 * Non-critical scripts that should be deferred.
	 *
	 * @var string[]
	 */
	private static array $defer_scripts = array(
		'apollo-vendor-leaflet',
		'apollo-vendor-sortable',
		'apollo-vendor-motion',
		'apollo-core-analytics',
		'apollo-social-feed',
		'apollo-auth-js',
	);

	/**
	 * Scripts that should be loaded asynchronously.
	 *
	 * @var string[]
	 */
	private static array $async_scripts = array(
		'apollo-core-lazy-loader',
		'apollo-vendor-phosphor',
	);

	/**
	 * Page-specific asset mapping.
	 *
	 * @var array<string, array{styles: string[], scripts: string[]}>
	 */
	private static array $page_assets = array(
		'events'      => array(
			'styles'  => array( 'apollo-events-calendar', 'apollo-events-listing' ),
			'scripts' => array( 'apollo-events-manager', 'apollo-vendor-leaflet' ),
		),
		'classifieds' => array(
			'styles'  => array( 'apollo-classifieds' ),
			'scripts' => array( 'apollo-classifieds-manager' ),
		),
		'social'      => array(
			'styles'  => array( 'apollo-social-feed', 'apollo-social-profile' ),
			'scripts' => array( 'apollo-social-feed', 'apollo-vendor-sortable' ),
		),
		'chat'        => array(
			'styles'  => array( 'apollo-chat' ),
			'scripts' => array( 'apollo-chat-client' ),
		),
		'map'         => array(
			'styles'  => array( 'apollo-vendor-leaflet', 'apollo-map' ),
			'scripts' => array( 'apollo-vendor-leaflet', 'apollo-map-client' ),
		),
		'auth'        => array(
			'styles'  => array( 'apollo-auth-styles' ),
			'scripts' => array( 'apollo-auth-js' ),
		),
	);

	/**
	 * Initialize asset optimization.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init(): void {
		// Add preload links for critical assets.
		add_action( 'wp_head', array( self::class, 'preload_critical_assets' ), 1 );

		// Add defer/async attributes to scripts.
		add_filter( 'script_loader_tag', array( self::class, 'add_script_attributes' ), 10, 2 );

		// Add preload attribute to critical styles.
		add_filter( 'style_loader_tag', array( self::class, 'optimize_style_loading' ), 10, 4 );

		// Conditional asset loading.
		add_action( 'wp_enqueue_scripts', array( self::class, 'conditional_dequeue' ), 999 );

		// Add resource hints.
		add_filter( 'wp_resource_hints', array( self::class, 'add_resource_hints' ), 10, 2 );

		// Remove unused scripts on non-Apollo pages.
		add_action( 'wp_print_scripts', array( self::class, 'cleanup_unused_scripts' ), 100 );
	}

	/**
	 * Preload critical assets in document head.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function preload_critical_assets(): void {
		if ( ! self::is_apollo_page() ) {
			return;
		}

		$base_url = self::get_assets_url();

		// Preload critical CSS.
		foreach ( self::$critical_styles as $handle ) {
			$file = self::get_asset_file_for_handle( $handle );
			if ( $file ) {
				printf(
					'<link rel="preload" href="%s" as="style" crossorigin>%s',
					esc_url( $base_url . $file ),
					"\n"
				);
			}
		}

		// Preload critical fonts (RemixIcon).
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>%s',
			esc_url( $base_url . 'vendor/remixicon/remixicon.woff2' ),
			"\n"
		);
	}

	/**
	 * Add defer/async attributes to scripts.
	 *
	 * @since 2.0.0
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @return string Modified script tag.
	 */
	public static function add_script_attributes( string $tag, string $handle ): string {
		// Skip if already has defer/async.
		if ( str_contains( $tag, 'defer' ) || str_contains( $tag, 'async' ) ) {
			return $tag;
		}

		// Add defer to non-critical scripts.
		if ( \in_array( $handle, self::$defer_scripts, true ) ) {
			$tag = \str_replace( ' src=', ' defer src=', $tag );
		}

		// Add async to async scripts.
		if ( \in_array( $handle, self::$async_scripts, true ) ) {
			$tag = \str_replace( ' src=', ' async src=', $tag );
		}

		return $tag;
	}

	/**
	 * Optimize style loading with preload.
	 *
	 * @since 2.0.0
	 *
	 * @param string $tag    Style tag HTML.
	 * @param string $handle Style handle.
	 * @param string $href   Stylesheet URL.
	 * @param string $media  Media attribute.
	 * @return string Modified style tag.
	 */
	public static function optimize_style_loading( string $tag, string $handle, string $href, string $media ): string {
		// Add fetchpriority to critical styles.
		if ( \in_array( $handle, self::$critical_styles, true ) ) {
			$tag = \str_replace( "rel='stylesheet'", "rel='stylesheet' fetchpriority='high'", $tag );
		}

		return $tag;
	}

	/**
	 * Conditionally dequeue unused assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function conditional_dequeue(): void {
		// Only process on Apollo pages.
		if ( ! self::is_apollo_page() ) {
			return;
		}

		$current_context = self::get_current_page_context();

		// Get assets needed for current page.
		$needed_assets = self::$page_assets[ $current_context ] ?? array(
			'styles'  => array(),
			'scripts' => array(),
		);

		// Always include core assets.
		$core_styles  = array( 'apollo-core-uni', 'apollo-core-base', 'apollo-vendor-remixicon' );
		$core_scripts = array( 'apollo-core-base', 'apollo-api' );

		$needed_assets['styles']  = array_merge( $core_styles, $needed_assets['styles'] );
		$needed_assets['scripts'] = array_merge( $core_scripts, $needed_assets['scripts'] );

		// Dequeue page-specific assets not needed.
		foreach ( self::$page_assets as $context => $assets ) {
			if ( $context === $current_context ) {
				continue;
			}

			foreach ( $assets['styles'] as $style ) {
				if ( ! in_array( $style, $needed_assets['styles'], true ) ) {
					wp_dequeue_style( $style );
				}
			}

			foreach ( $assets['scripts'] as $script ) {
				if ( ! in_array( $script, $needed_assets['scripts'], true ) ) {
					wp_dequeue_script( $script );
				}
			}
		}
	}

	/**
	 * Add resource hints for performance.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $hints Resource hint URLs.
	 * @param string   $type  Hint type (dns-prefetch, preconnect, etc.).
	 * @return string[] Modified hints.
	 */
	public static function add_resource_hints( array $hints, string $type ): array {
		if ( ! self::is_apollo_page() ) {
			return $hints;
		}

		if ( 'preconnect' === $type ) {
			// Preconnect to API endpoints.
			$hints[] = array(
				'href'        => rest_url(),
				'crossorigin' => 'anonymous',
			);
		}

		if ( 'dns-prefetch' === $type ) {
			// DNS prefetch for external resources if any.
			$hints[] = '//fonts.googleapis.com';
		}

		return $hints;
	}

	/**
	 * Cleanup unused scripts on non-Apollo pages.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function cleanup_unused_scripts(): void {
		if ( self::is_apollo_page() ) {
			return;
		}

		// List of Apollo scripts to remove on non-Apollo pages.
		$apollo_scripts = array(
			'apollo-core-base',
			'apollo-api',
			'apollo-events-manager',
			'apollo-social-feed',
			'apollo-chat-client',
			'apollo-vendor-leaflet',
			'apollo-vendor-sortable',
			'apollo-vendor-motion',
			'apollo-auth-js',
		);

		foreach ( $apollo_scripts as $handle ) {
			wp_dequeue_script( $handle );
		}
	}

	/**
	 * Get current page context for asset loading.
	 *
	 * @since 2.0.0
	 * @return string Page context (events, classifieds, social, etc.).
	 */
	private static function get_current_page_context(): string {
		global $post;

		// Check post type.
		if ( $post ) {
			$post_type = get_post_type( $post );

			if ( str_contains( $post_type, 'event' ) ) {
				return 'events';
			}

			if ( 'advert' === $post_type ) {
				return 'classifieds';
			}
		}

		// Check current URL path.
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		if ( str_contains( $request_uri, '/events' ) || str_contains( $request_uri, '/calendario' ) ) {
			return 'events';
		}

		if ( str_contains( $request_uri, '/classifieds' ) || str_contains( $request_uri, '/anuncios' ) ) {
			return 'classifieds';
		}

		if ( str_contains( $request_uri, '/social' ) || str_contains( $request_uri, '/feed' ) ) {
			return 'social';
		}

		if ( str_contains( $request_uri, '/chat' ) || str_contains( $request_uri, '/mensagens' ) ) {
			return 'chat';
		}

		if ( str_contains( $request_uri, '/mapa' ) || str_contains( $request_uri, '/map' ) ) {
			return 'map';
		}

		if ( str_contains( $request_uri, '/entre' ) || str_contains( $request_uri, '/registre' ) || get_query_var( 'apollo_auth' ) ) {
			return 'auth';
		}

		return 'general';
	}

	/**
	 * Check if current page is an Apollo page.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private static function is_apollo_page(): bool {
		// Use Apollo's page detection if available.
		if ( \function_exists( 'apollo_is_apollo_page' ) ) {
			return \apollo_is_apollo_page();
		}

		if ( get_query_var( 'apollo_auth' ) ) {
			return true;
		}

		// Fallback detection.
		global $post;

		if ( $post ) {
			$apollo_post_types = array(
				'event_listing',
				'event_dj',
				'event_local',
				'advert',
				'apollo_activity',
			);

			if ( \in_array( get_post_type( $post ), $apollo_post_types, true ) ) {
				return true;
			}

			// Check for Apollo page template.
			$template = get_page_template_slug( $post );
			if ( $template && str_contains( $template, 'apollo' ) ) {
				return true;
			}
		}

		// Check body classes.
		if ( function_exists( 'get_body_class' ) ) {
			$classes = get_body_class();
			foreach ( $classes as $class ) {
				if ( str_starts_with( $class, 'apollo-' ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get assets URL.
	 *
	 * @since 2.0.0
	 * @return string Assets URL.
	 */
	private static function get_assets_url(): string {
		if ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ) {
			return APOLLO_CORE_PLUGIN_URL . 'assets/';
		}

		return plugin_dir_url( __DIR__ ) . 'assets/';
	}

	/**
	 * Get asset file path for a handle.
	 *
	 * @since 2.0.0
	 *
	 * @param string $handle Asset handle.
	 * @return string|null File path or null.
	 */
	private static function get_asset_file_for_handle( string $handle ): ?string {
		$map = array(
			'apollo-core-uni'         => 'core/uni.css',
			'apollo-core-base'        => 'core/base.css',
			'apollo-vendor-remixicon' => 'vendor/remixicon/remixicon.css',
			'apollo-vendor-leaflet'   => 'vendor/leaflet/leaflet.css',
			'apollo-auth-styles'      => 'css/auth-styles.css',
		);

		return $map[ $handle ] ?? null;
	}

	/**
	 * Generate inline critical CSS.
	 *
	 * @since 2.0.0
	 * @return string Critical CSS content.
	 */
	public static function get_critical_css(): string {
		$critical_css = '';

		// Get above-the-fold critical styles.
		$critical_path = self::get_assets_path() . 'core/critical.css';

		if ( file_exists( $critical_path ) ) {
			$critical_css = file_get_contents( $critical_path );
		}

		/**
		 * Filter the critical CSS content.
		 *
		 * @since 2.0.0
		 *
		 * @param string $critical_css Critical CSS content.
		 */
		return apply_filters( 'apollo_critical_css', $critical_css );
	}

	/**
	 * Inline critical CSS in head.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function inline_critical_css(): void {
		$css = self::get_critical_css();

		if ( ! empty( $css ) ) {
			echo '<style id="apollo-critical-css">' . wp_strip_all_tags( $css ) . '</style>' . "\n";
		}
	}

	/**
	 * Get assets path.
	 *
	 * @since 2.0.0
	 * @return string Assets directory path.
	 */
	private static function get_assets_path(): string {
		if ( defined( 'APOLLO_CORE_PLUGIN_DIR' ) ) {
			return APOLLO_CORE_PLUGIN_DIR . 'assets/';
		}

		return plugin_dir_path( __DIR__ ) . 'assets/';
	}

	/**
	 * Get optimization statistics.
	 *
	 * @since 2.0.0
	 * @return array{deferred_scripts: int, async_scripts: int, critical_styles: int}
	 */
	public static function get_stats(): array {
		return array(
			'deferred_scripts' => count( self::$defer_scripts ),
			'async_scripts'    => count( self::$async_scripts ),
			'critical_styles'  => count( self::$critical_styles ),
			'page_contexts'    => array_keys( self::$page_assets ),
		);
	}
}
