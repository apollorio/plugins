<?php

declare(strict_types=1);
/**
 * Assets Loader - Apollo Design System
 * CDN-first with local fallback strategy
 * @package ApolloCore\Assets
 */

class Apollo_Assets_Loader {
	const CDN_BASE = 'https://assets.apollo.rio.br/';
	const CDN_ALT = 'https://cdn.apollo.rio.br/';
	const CORE_ASSETS = array(
		'css' => array( 'uni.css' => 'uni-css' ),
		'js'  => array( 'base.js' => 'uni-js' ),
	);
	const CONTEXT_ASSETS = array(
		'events' => array( 'css' => array(), 'js' => array() ),
		'social' => array( 'css' => array(), 'js' => array() ),
		'admin'  => array( 'css' => array(), 'js' => array() ),
	);

	private static $local_base = null;

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_core_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'wp_head', array( __CLASS__, 'inject_fallback_handler' ), 1 );
	}

	public static function get_local_base() {
		if ( null === self::$local_base ) {
			self::$local_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
				? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
				: plugin_dir_url( dirname( __DIR__ ) ) . 'assets/core/';
		}
		return self::$local_base;
	}

	public static function inject_fallback_handler() {
		$local = esc_url( self::get_local_base() );
		echo <<<HTML
<script>window.ApolloFallback={cdn:"{$local}",load:function(t,u,f){var e=document.createElement(t);e.onerror=function(){var l=document.createElement(t);l[t==='link'?'href':'src']=f;if(t==='link'){l.rel='stylesheet';}document.head.appendChild(l);};e[t==='link'?'href':'src']=u;if(t==='link'){e.rel='stylesheet';}document.head.appendChild(e);}};</script>
HTML;
	}

	public static function enqueue_core_assets() {
		foreach ( self::CORE_ASSETS['css'] as $file => $handle ) {
			wp_enqueue_style( $handle, self::CDN_BASE . $file, array(), self::get_asset_version( $file ), 'all' );
			add_filter( 'style_loader_tag', function( $tag, $h ) use ( $handle, $file ) {
				if ( $h === $handle ) {
					$local = esc_url( self::get_local_base() . $file );
					return str_replace( '/>', ' onerror="this.onerror=null;this.href=\'' . $local . '\'" />', $tag );
				}
				return $tag;
			}, 10, 2 );
		}
		foreach ( self::CORE_ASSETS['js'] as $file => $handle ) {
			wp_enqueue_script( $handle, self::CDN_BASE . $file, array(), self::get_asset_version( $file ), true );
			add_filter( 'script_loader_tag', function( $tag, $h ) use ( $handle, $file ) {
				if ( $h === $handle ) {
					$local = esc_url( self::get_local_base() . $file );
					return str_replace( '></script>', ' onerror="this.onerror=null;this.src=\'' . $local . '\'"></script>', $tag );
				}
				return $tag;
			}, 10, 2 );
		}
		wp_enqueue_script( 'apollo-icon-loader', self::CDN_BASE . 'icon.js', array(), '1.0.0', array( 'strategy' => 'defer', 'in_footer' => true ) );
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( self::should_load_in_admin( $hook ) ) {
			self::enqueue_core_assets();
		}
		foreach ( self::CONTEXT_ASSETS['admin']['css'] as $file => $handle ) {
			wp_enqueue_style( $handle, self::CDN_BASE . $file, array(), self::get_asset_version( $file ), 'all' );
		}
		foreach ( self::CONTEXT_ASSETS['admin']['js'] as $file => $handle ) {
			wp_enqueue_script( $handle, self::CDN_BASE . $file, array(), self::get_asset_version( $file ), true );
		}
	}

	public static function enqueue_context_assets( $context ) {
		if ( ! isset( self::CONTEXT_ASSETS[ $context ] ) ) {
			return;
		}
		$assets = self::CONTEXT_ASSETS[ $context ];
		foreach ( $assets['css'] as $file => $handle ) {
			wp_enqueue_style( $handle, self::CDN_BASE . $file, array_keys( self::CORE_ASSETS['css'] ), self::get_asset_version( $file ), 'all' );
		}
		foreach ( $assets['js'] as $file => $handle ) {
			wp_enqueue_script( $handle, self::CDN_BASE . $file, array_keys( self::CORE_ASSETS['js'] ), self::get_asset_version( $file ), true );
		}
	}

	private static function get_asset_version( $filename ) {
		$versions = array( 'uni.css' => '1.0.0', 'base.js' => '1.0.0', 'icon.js' => '1.0.0' );
		return $versions[ $filename ] ?? '1.0.0';
	}

	private static function should_load_in_admin( $hook ) {
		$apollo_pages = array(
			'toplevel_page_apollo-events',
			'events_page_apollo-events-settings',
			'toplevel_page_apollo-social',
			'social_page_apollo-social-settings',
		);

		return in_array( $hook, $apollo_pages );
	}

	public static function ensure_assets_loaded( $context = '' ) {
		if ( ! wp_style_is( 'uni-css', 'enqueued' ) ) {
			self::enqueue_core_assets();
		}
		if ( $context && isset( self::CONTEXT_ASSETS[ $context ] ) ) {
			self::enqueue_context_assets( $context );
		}
	}

	public static function get_cdn_url( $asset ) {
		return self::CDN_BASE . ltrim( $asset, '/' );
	}

	public static function get_cdn_with_fallback( $asset ) {
		return array(
			'cdn'   => self::CDN_BASE . ltrim( $asset, '/' ),
			'local' => self::get_local_base() . ltrim( $asset, '/' ),
		);
	}

	public static function render_css_tag( $file, $id = '' ) {
		$cdn   = esc_url( self::CDN_BASE . $file );
		$local = esc_url( self::get_local_base() . $file );
		$id    = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		return '<link rel="stylesheet"' . $id . ' href="' . $cdn . '" onerror="this.onerror=null;this.href=\'' . $local . '\'">';
	}

	public static function render_js_tag( $file, $id = '', $defer = false ) {
		$cdn   = esc_url( self::CDN_BASE . $file );
		$local = esc_url( self::get_local_base() . $file );
		$id    = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		$d     = $defer ? ' defer' : '';
		return '<script' . $id . $d . ' src="' . $cdn . '" onerror="this.onerror=null;this.src=\'' . $local . '\'"></script>';
	}

	public static function verify_cdn_assets() {
		$results = array();
		foreach ( array_merge( self::CORE_ASSETS['css'], self::CORE_ASSETS['js'] ) as $file => $handle ) {
			$url              = self::get_cdn_url( $file );
			$response         = wp_remote_head( $url, array( 'timeout' => 3 ) );
			$results[ $file ] = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		}
		return $results;
	}
}
