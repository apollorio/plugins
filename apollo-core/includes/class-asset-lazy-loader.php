<?php
/**
 * Apollo Asset Lazy Loader
 * PHASE 7: Performance Optimization - Lazy Loading
 * Implements intelligent lazy loading for CSS and JavaScript assets
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asset_Lazy_Loader {

	private const CRITICAL_CSS_BREAKPOINT = 1440; // Above this = desktop.
	private const LAZY_LOAD_THRESHOLD     = 100; // KB threshold for lazy loading.

	/**
	 * Initialize lazy loading
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_lazy_assets' ), 100 );
		add_action( 'wp_head', array( self::class, 'add_lazy_load_scripts' ), 1 );
		add_filter( 'script_loader_tag', array( self::class, 'modify_script_tags' ), 10, 3 );
		add_filter( 'style_loader_tag', array( self::class, 'modify_style_tags' ), 10, 4 );
	}

	/**
	 * Enqueue assets with lazy loading metadata
	 */
	public static function enqueue_lazy_assets(): void {
		global $wp_scripts, $wp_styles;

		// Mark non-critical scripts for lazy loading.
		self::mark_lazy_scripts( $wp_scripts );

		// Mark non-critical styles for lazy loading.
		self::mark_lazy_styles( $wp_styles );
	}

	/**
	 * Mark scripts for lazy loading
	 */
	private static function mark_lazy_scripts( $wp_scripts ): void {
		$critical_scripts = array(
			'jquery',
			'jquery-core',
			'jquery-migrate',
			'wp-polyfill',
			'regenerator-runtime',
		);

		foreach ( $wp_scripts->queue as $handle ) {
			if ( ! in_array( $handle, $critical_scripts, true ) ) {
				$script = $wp_scripts->registered[ $handle ] ?? null;
				if ( $script && self::should_lazy_load_script( $script ) ) {
					$script->extra['lazy'] = true;
				}
			}
		}
	}

	/**
	 * Mark styles for lazy loading
	 */
	private static function mark_lazy_styles( $wp_styles ): void {
		$critical_styles = array(
			'admin-bar',
			'dashicons',
			'wp-block-library',
		);

		foreach ( $wp_styles->queue as $handle ) {
			if ( ! in_array( $handle, $critical_styles, true ) ) {
				$style = $wp_styles->registered[ $handle ] ?? null;
				if ( $style && self::should_lazy_load_style( $style ) ) {
					$style->extra['lazy'] = true;
				}
			}
		}
	}

	/**
	 * Determine if script should be lazy loaded
	 */
	private static function should_lazy_load_script( $script ): bool {
		// Don't lazy load if already marked as critical.
		if ( isset( $script->extra['critical'] ) && $script->extra['critical'] ) {
			return false;
		}

		// Check file size if available.
		if ( isset( $script->src ) && $script->src ) {
			$file_size = self::get_remote_file_size( $script->src );
			if ( $file_size > self::LAZY_LOAD_THRESHOLD * 1024 ) { // Convert KB to bytes.
				return true;
			}
		}

		// Lazy load scripts that are not in the critical rendering path.
		$lazy_handles = array(
			'comment-reply',
			'wp-embed',
			'jquery-ui-',
			'thickbox',
			'media-upload',
		);

		foreach ( $lazy_handles as $lazy_handle ) {
			if ( strpos( $script->handle, $lazy_handle ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if style should be lazy loaded
	 */
	private static function should_lazy_load_style( $style ): bool {
		// Don't lazy load if already marked as critical.
		if ( isset( $style->extra['critical'] ) && $style->extra['critical'] ) {
			return false;
		}

		// Check file size if available.
		if ( isset( $style->src ) && $style->src ) {
			$file_size = self::get_remote_file_size( $style->src );
			if ( $file_size > self::LAZY_LOAD_THRESHOLD * 1024 ) { // Convert KB to bytes.
				return true;
			}
		}

		// Lazy load styles that are not critical for initial render.
		$lazy_handles = array(
			'wp-mediaelement',
			'mediaelement',
			'thickbox',
			'dashicons',
			'admin-bar',
		);

		foreach ( $lazy_handles as $lazy_handle ) {
			if ( strpos( $style->handle, $lazy_handle ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get remote file size
	 */
	private static function get_remote_file_size( string $url ): int {
		static $cache = array();

		if ( isset( $cache[ $url ] ) ) {
			return $cache[ $url ];
		}

		// Only check local files or CDN files for performance.
		if ( strpos( $url, home_url() ) === 0 || strpos( $url, 'assets.apollo.rio.br' ) !== false ) {
			// PHP 8.0+: get_headers() second argument must be bool, not int.
			$headers = @get_headers( $url, true );
			if ( is_array( $headers ) && isset( $headers['Content-Length'] ) ) {
				$size          = (int) $headers['Content-Length'];
				$cache[ $url ] = $size;
				return $size;
			}
		}

		$cache[ $url ] = 0;
		return 0;
	}

	/**
	 * Add lazy loading scripts to head
	 */
	public static function add_lazy_load_scripts(): void {
		?>
		<script>
		// Apollo Lazy Loading System.
		window.apolloLazyLoad = {
			loaded: new Set(),
			observer: null,

			init: function() {
				// Intersection Observer for lazy loading.
				if ('IntersectionObserver' in window) {
					this.observer = new IntersectionObserver((entries) => {
						entries.forEach(entry => {
							if (entry.isIntersecting) {
								this.loadLazyAsset(entry.target);
							}
						});
					}, {
						rootMargin: '50px'
					});
				}

				// Load critical assets immediately.
				this.loadCriticalAssets();

				// Setup lazy loading after critical content loads.
				document.addEventListener('DOMContentLoaded', () => {
					this.setupLazyLoading();
				});
			},

			loadCriticalAssets: function() {
				// Load critical CSS immediately.
				const criticalStyles = document.querySelectorAll('link[data-critical="true"]');
				criticalStyles.forEach(link => {
					link.rel = 'stylesheet';
				});

				// Load critical JS immediately.
				const criticalScripts = document.querySelectorAll('script[data-critical="true"]');
				criticalScripts.forEach(script => {
					this.loadScript(script.dataset.src || script.src);
				});
			},

			setupLazyLoading: function() {
				// Setup lazy styles.
				const lazyStyles = document.querySelectorAll('link[data-lazy="true"]');
				lazyStyles.forEach(link => {
					if (this.observer) {
						this.observer.observe(link);
					} else {
						// Fallback for browsers without IntersectionObserver.
						this.loadStyle(link);
					}
				});

				// Setup lazy scripts.
				const lazyScripts = document.querySelectorAll('script[data-lazy="true"]');
				lazyScripts.forEach(script => {
					if (this.observer) {
						this.observer.observe(script);
					} else {
						// Fallback.
						this.loadScript(script.dataset.src || script.src);
					}
				});
			},

			loadLazyAsset: function(element) {
				if (element.tagName === 'LINK') {
					this.loadStyle(element);
				} else if (element.tagName === 'SCRIPT') {
					this.loadScript(element.dataset.src || element.src);
				}

				if (this.observer) {
					this.observer.unobserve(element);
				}
			},

			loadStyle: function(link) {
				if (this.loaded.has(link.href)) return;

				link.rel = 'stylesheet';
				this.loaded.add(link.href);

				// Trigger style recalculation.
				link.addEventListener('load', () => {
					// Force browser to recalculate styles.
					document.body.style.display = 'none';
					document.body.offsetHeight; // Trigger reflow.
					document.body.style.display = '';
				});
			},

			loadScript: function(src) {
				if (this.loaded.has(src)) return;

				const script = document.createElement('script');
				script.src = src;
				script.async = true;
				document.head.appendChild(script);
				this.loaded.add(src);
			}
		};

		// Initialize lazy loading.
		apolloLazyLoad.init();
		</script>
		<?php
	}

	/**
	 * Modify script tags for lazy loading
	 */
	public static function modify_script_tags( $tag, $handle, $src ): string {
		global $wp_scripts;

		$script = $wp_scripts->registered[ $handle ] ?? null;
		if ( $script && isset( $script->extra['lazy'] ) && $script->extra['lazy'] ) {
			// Convert to lazy script.
			$tag = str_replace(
				'<script ',
				'<script data-lazy="true" data-src="' . esc_url( $src ) . '" ',
				$tag
			);
			$tag = str_replace( ' src=', ' data-src=', $tag );
		} elseif ( $script && isset( $script->extra['critical'] ) && $script->extra['critical'] ) {
			// Mark as critical.
			$tag = str_replace(
				'<script ',
				'<script data-critical="true" ',
				$tag
			);
		}

		return $tag;
	}

	/**
	 * Modify style tags for lazy loading
	 */
	public static function modify_style_tags( $tag, $handle, $href, $media ): string {
		global $wp_styles;

		$style = $wp_styles->registered[ $handle ] ?? null;
		if ( $style && isset( $style->extra['lazy'] ) && $style->extra['lazy'] ) {
			// Convert to lazy style.
			$tag = str_replace(
				'<link ',
				'<link data-lazy="true" data-href="' . esc_url( $href ) . '" ',
				$tag
			);
			$tag = str_replace( ' href=', ' data-href=', $tag );
			$tag = str_replace( ' rel=', ' data-rel=', $tag );
		} elseif ( $style && isset( $style->extra['critical'] ) && $style->extra['critical'] ) {
			// Mark as critical.
			$tag = str_replace(
				'<link ',
				'<link data-critical="true" ',
				$tag
			);
		}

		return $tag;
	}

	/**
	 * Mark specific assets as critical
	 */
	public static function mark_critical( string $handle, string $type = 'script' ): void {
		if ( $type === 'script' ) {
			add_filter(
				'script_loader_tag',
				function ( $tag, $script_handle, $src ) use ( $handle ) {
					if ( $script_handle === $handle ) {
						return str_replace( '<script ', '<script data-critical="true" ', $tag );
					}
					return $tag;
				},
				10,
				3
			);
		} elseif ( $type === 'style' ) {
			add_filter(
				'style_loader_tag',
				function ( $tag, $style_handle, $href, $media ) use ( $handle ) {
					if ( $style_handle === $handle ) {
						return str_replace( '<link ', '<link data-critical="true" ', $tag );
					}
					return $tag;
				},
				10,
				4
			);
		}
	}

	/**
	 * Get lazy loading statistics
	 */
	public static function get_lazy_load_stats(): array {
		global $wp_scripts, $wp_styles;

		$stats = array(
			'scripts_lazy_loaded' => 0,
			'scripts_critical'    => 0,
			'styles_lazy_loaded'  => 0,
			'styles_critical'     => 0,
			'total_scripts'       => count( $wp_scripts->queue ),
			'total_styles'        => count( $wp_styles->queue ),
		);

		foreach ( $wp_scripts->queue as $handle ) {
			$script = $wp_scripts->registered[ $handle ] ?? null;
			if ( $script ) {
				if ( isset( $script->extra['lazy'] ) && $script->extra['lazy'] ) {
					++$stats['scripts_lazy_loaded'];
				} elseif ( isset( $script->extra['critical'] ) && $script->extra['critical'] ) {
					++$stats['scripts_critical'];
				}
			}
		}

		foreach ( $wp_styles->queue as $handle ) {
			$style = $wp_styles->registered[ $handle ] ?? null;
			if ( $style ) {
				if ( isset( $style->extra['lazy'] ) && $style->extra['lazy'] ) {
					++$stats['styles_lazy_loaded'];
				} elseif ( isset( $style->extra['critical'] ) && $style->extra['critical'] ) {
					++$stats['styles_critical'];
				}
			}
		}

		return $stats;
	}
}

// Mark UNI.CSS and base.js as critical
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( class_exists( '\\Apollo_Core\\Asset_Lazy_Loader' ) ) {
			\Apollo_Core\Asset_Lazy_Loader::mark_critical( 'apollo-uni-css', 'style' );
			\Apollo_Core\Asset_Lazy_Loader::mark_critical( 'apollo-base-js', 'script' );
		}
	},
	99
);

// Initialize lazy loading.
if ( class_exists( '\\Apollo_Core\\Asset_Lazy_Loader' ) ) {
	\Apollo_Core\Asset_Lazy_Loader::init();
}
