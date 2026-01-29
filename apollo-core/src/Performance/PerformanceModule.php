<?php
/**
 * Apollo Performance Module
 * Ultra-Pro WordPress Structure: Performance Pillar
 *
 * Enforces strict code minification (PHP/JS) and caching strategies.
 * Ensures scripts are non-blocking and execute via single-load paths.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performance Optimization Module
 */
final class PerformanceModule {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Minified assets cache.
	 *
	 * @var array
	 */
	private array $minified_cache = array();

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize performance optimizations.
	 *
	 * @return void
	 */
	public function init(): void {
		// Minify and cache assets
		add_action( 'wp_enqueue_scripts', array( $this, 'minifyAndCacheAssets' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'minifyAndCacheAssets' ), 999 );

		// Non-blocking script loading
		add_filter( 'script_loader_tag', array( $this, 'addAsyncDeferAttributes' ), 10, 3 );

		// Aggressive caching headers
		add_action( 'send_headers', array( $this, 'setAggressiveCaching' ) );

		// Remove query strings from static resources
		add_filter( 'script_loader_src', array( $this, 'removeQueryStrings' ), 999 );
		add_filter( 'style_loader_src', array( $this, 'removeQueryStrings' ), 999 );

		// Optimize database queries
		add_action( 'wp_loaded', array( $this, 'optimizeQueries' ) );

		// Lazy load images
		add_filter( 'the_content', array( $this, 'addLazyLoading' ) );
		add_filter( 'post_thumbnail_html', array( $this, 'addLazyLoading' ) );
	}

	/**
	 * Minify and cache JavaScript/CSS assets.
	 *
	 * @return void
	 */
	public function minifyAndCacheAssets(): void {
		global $wp_scripts, $wp_styles;

		// Minify scripts
		if ( $wp_scripts ) {
			foreach ( $wp_scripts->registered as $handle => $script ) {
				if ( $this->shouldMinify( $script->src ) ) {
					$minified = $this->getMinifiedContent( $script->src, 'js' );
					if ( $minified ) {
						wp_add_inline_script( $handle, $minified );
						wp_dequeue_script( $handle );
					}
				}
			}
		}

		// Minify styles
		if ( $wp_styles ) {
			foreach ( $wp_styles->registered as $handle => $style ) {
				if ( $this->shouldMinify( $style->src ) ) {
					$minified = $this->getMinifiedContent( $style->src, 'css' );
					if ( $minified ) {
						wp_add_inline_style( $handle, $minified );
						wp_dequeue_style( $handle );
					}
				}
			}
		}
	}

	/**
	 * Check if asset should be minified.
	 *
	 * @param string $src Asset source URL.
	 * @return bool
	 */
	private function shouldMinify( string $src ): bool {
		if ( ! $src || \strpos( $src, '.min.' ) !== false ) {
			return false; // Already minified
		}

		// Only minify Apollo assets
		return \strpos( $src, 'apollo' ) !== false;
	}

	/**
	 * Get minified content from cache or generate it.
	 *
	 * @param string $src  Asset source URL.
	 * @param string $type Asset type (js|css).
	 * @return string|null
	 */
	private function getMinifiedContent( string $src, string $type ): ?string {
		$cache_key = \md5( $src . $type );

		if ( isset( $this->minified_cache[ $cache_key ] ) ) {
			return $this->minified_cache[ $cache_key ];
		}

		$content = $this->fetchAssetContent( $src );
		if ( ! $content ) {
			return null;
		}

		$minified = $type === 'js' ? $this->minifyJs( $content ) : $this->minifyCss( $content );

		// Cache for this request
		$this->minified_cache[ $cache_key ] = $minified;

		return $minified;
	}

	/**
	 * Fetch asset content from URL.
	 *
	 * @param string $src Asset source URL.
	 * @return string|null
	 */
	private function fetchAssetContent( string $src ): ?string {
		// Convert relative URLs to absolute
		if ( \strpos( $src, 'http' ) !== 0 ) {
			$src = site_url( $src );
		}

		$response = wp_remote_get( $src );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Minify JavaScript content.
	 *
	 * @param string $content JavaScript content.
	 * @return string
	 */
	private function minifyJs( string $content ): string {
		// Remove comments
		$content = \preg_replace( '/\/\*.*?\*\//s', '', $content );
		$content = \preg_replace( '/\/\/.*$/m', '', $content );

		// Remove extra whitespace
		$content = \preg_replace( '/\s+/', ' ', $content );
		$content = \preg_replace( '/\s*([{}();,])\s*/', '$1', $content );

		return \trim( $content );
	}

	/**
	 * Minify CSS content.
	 *
	 * @param string $content CSS content.
	 * @return string
	 */
	private function minifyCss( string $content ): string {
		// Remove comments
		$content = \preg_replace( '/\/\*.*?\*\//s', '', $content );

		// Remove extra whitespace
		$content = \preg_replace( '/\s+/', ' ', $content );
		$content = \preg_replace( '/\s*([{}:;,])\s*/', '$1', $content );

		return \trim( $content );
	}

	/**
	 * Add async/defer attributes to scripts for non-blocking loading.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string
	 */
	public function addAsyncDeferAttributes( string $tag, string $handle, string $src ): string {
		// Only apply to Apollo scripts and non-critical WordPress scripts
		if ( \strpos( $handle, 'apollo' ) === false && ! \in_array( $handle, array( 'jquery', 'jquery-core' ) ) ) {
			return $tag;
		}

		// Add defer for non-jQuery scripts
		if ( $handle !== 'jquery' && $handle !== 'jquery-core' ) {
			$tag = \str_replace( '<script ', '<script defer ', $tag );
		}

		return $tag;
	}

	/**
	 * Set aggressive caching headers for static assets.
	 *
	 * @return void
	 */
	public function setAggressiveCaching(): void {
		if ( is_admin() ) {
			return;
		}

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';

		// Cache static assets for 1 year
		if ( \preg_match( '/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $request_uri ) ) {
			header( 'Cache-Control: public, max-age=31536000' );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 31536000 ) . ' GMT' );
		}
	}

	/**
	 * Remove query strings from static resources.
	 *
	 * @param string $src Resource source URL.
	 * @return string
	 */
	public function removeQueryStrings( string $src ): string {
		if ( \strpos( $src, '?' ) !== false ) {
			$src = \explode( '?', $src )[0];
		}
		return $src;
	}

	/**
	 * Optimize database queries.
	 *
	 * @return void
	 */
	public function optimizeQueries(): void {
		// Disable unnecessary queries
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'parent_post_rel_link' );
		remove_action( 'wp_head', 'start_post_rel_link' );

		// Optimize comment queries
		add_filter(
			'comments_clauses',
			function ( $clauses ) {
				$clauses['orderby'] = 'comment_date_gmt DESC';
				return $clauses;
			}
		);
	}

	/**
	 * Add lazy loading to images.
	 *
	 * @param string $content Content with images.
	 * @return string
	 */
	public function addLazyLoading( string $content ): string {
		return \preg_replace_callback(
			'/<img([^>]+)>/',
			function ( $matches ) {
				$img_tag = $matches[0];

				// Skip if already has loading attribute
				if ( \strpos( $img_tag, 'loading=' ) !== false ) {
					return $img_tag;
				}

				// Add lazy loading
				return \str_replace( '<img', '<img loading="lazy"', $img_tag );
			},
			$content
		);
	}
}
