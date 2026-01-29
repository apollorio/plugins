<?php
/**
 * Apollo Rio - Core Integration
 *
 * Hooks into Apollo Core's template system and integration bridge.
 * Provides PWA/performance optimization via filter hooks.
 *
 * @package Apollo_Rio
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Rio\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Rio_Core_Integration
 *
 * Integrates Apollo Rio with Apollo Core's template system.
 * Provides PWA and performance optimizations.
 */
class Apollo_Rio_Core_Integration {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Rio_Core_Integration|null
	 */
	private static ?Apollo_Rio_Core_Integration $instance = null;

	/**
	 * Integration bridge instance
	 *
	 * @var object|null
	 */
	private ?object $bridge = null;

	/**
	 * Optimization settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = array(
		'lazy_load_images' => true,
		'defer_scripts'    => true,
		'preconnect'       => true,
		'service_worker'   => true,
		'prefetch_links'   => true,
		'minify_html'      => false,
		'async_css'        => false,
	);

	/**
	 * Preconnect origins
	 *
	 * @var array<string>
	 */
	private array $preconnect_origins = array(
		'https://assets.apollo.rio.br',
		'https://cdn.jsdelivr.net',
		'https://unpkg.com',
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Rio_Core_Integration
	 */
	public static function get_instance(): Apollo_Rio_Core_Integration {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->load_settings();
		$this->init_hooks();
	}

	/**
	 * Load settings from options
	 *
	 * @return void
	 */
	private function load_settings(): void {
		$saved_settings = get_option( 'apollo_rio_settings', array() );
		$this->settings = wp_parse_args( $saved_settings, $this->settings );
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Register with Core Integration Bridge when ready.
		add_action( 'apollo_integration_bridge_ready', array( $this, 'register_with_bridge' ) );

		// Provide optimization via filter.
		add_filter( 'apollo_core_rio_optimize', array( $this, 'optimize_content' ), 10, 3 );

		// Add preconnect headers.
		add_action( 'wp_head', array( $this, 'add_preconnect_hints' ), 1 );

		// Add service worker registration.
		add_action( 'wp_footer', array( $this, 'register_service_worker' ), 100 );

		// Filter script/style loading.
		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );

		// Add lazy loading to images.
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_lazy_loading' ), 10, 2 );

		// PWA manifest.
		add_action( 'wp_head', array( $this, 'add_manifest_link' ), 2 );

		// Theme color meta.
		add_action( 'wp_head', array( $this, 'add_theme_color' ), 3 );
	}

	/**
	 * Register with Apollo Core Integration Bridge
	 *
	 * @param object $bridge Integration Bridge instance.
	 * @return void
	 */
	public function register_with_bridge( object $bridge ): void {
		$this->bridge = $bridge;

		if ( method_exists( $bridge, 'register_plugin' ) ) {
			$bridge->register_plugin(
				'rio',
				array(
					'version'      => defined( 'APOLLO_RIO_VERSION' ) ? APOLLO_RIO_VERSION : '1.0.0',
					'file'         => 'apollo-rio/apollo-rio.php',
					'path'         => defined( 'APOLLO_RIO_PATH' ) ? APOLLO_RIO_PATH : '',
					'url'          => defined( 'APOLLO_RIO_URL' ) ? APOLLO_RIO_URL : '',
					'capabilities' => array(),
					'supports'     => array(
						'pwa',
						'service_worker',
						'lazy_loading',
						'defer_scripts',
						'preconnect',
						'offline_mode',
					),
					'hooks'        => array(
						'apollo_core_rio_optimize',
					),
				)
			);
		}
	}

	/**
	 * Optimize content
	 *
	 * @param string $content HTML content.
	 * @param string $context Context (page, event, template).
	 * @param array  $args    Optimization arguments.
	 * @return string
	 */
	public function optimize_content( string $content, string $context, array $args ): string {
		$args = wp_parse_args( $args, $this->settings );

		// Lazy load images.
		if ( $args['lazy_load_images'] ) {
			$content = $this->add_lazy_loading_to_content( $content );
		}

		// Add prefetch for internal links.
		if ( $args['prefetch_links'] ) {
			$content = $this->add_prefetch_hints( $content );
		}

		// Minify HTML if enabled.
		if ( $args['minify_html'] ) {
			$content = $this->minify_html( $content );
		}

		/**
		 * Filter: apollo_rio_optimized_content
		 *
		 * Allows further optimization of content.
		 *
		 * @param string $content Optimized content.
		 * @param string $context Context.
		 * @param array  $args    Arguments.
		 */
		return apply_filters( 'apollo_rio_optimized_content', $content, $context, $args );
	}

	/**
	 * Add lazy loading to content images
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	private function add_lazy_loading_to_content( string $content ): string {
		// Add loading="lazy" to img tags without it.
		$content = preg_replace_callback(
			'/<img\s+([^>]*)>/i',
			function ( $matches ) {
				$attributes = $matches[1];

				// Skip if already has loading attribute.
				if ( preg_match( '/\bloading\s*=/i', $attributes ) ) {
					return $matches[0];
				}

				// Skip if it's a small icon or logo (likely above the fold).
				if ( preg_match( '/\b(logo|icon|avatar)\b/i', $attributes ) ) {
					return $matches[0];
				}

				return '<img loading="lazy" ' . $attributes . '>';
			},
			$content
		);

		// Add loading="lazy" to iframes.
		$content = preg_replace_callback(
			'/<iframe\s+([^>]*)>/i',
			function ( $matches ) {
				$attributes = $matches[1];

				// Skip if already has loading attribute.
				if ( preg_match( '/\bloading\s*=/i', $attributes ) ) {
					return $matches[0];
				}

				return '<iframe loading="lazy" ' . $attributes . '>';
			},
			$content
		);

		return $content;
	}

	/**
	 * Add prefetch hints to internal links
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	private function add_prefetch_hints( string $content ): string {
		$site_url = home_url();

		// Find internal links and add data-prefetch attribute.
		$content = preg_replace_callback(
			'/<a\s+([^>]*href=["\'](' . preg_quote( $site_url, '/' ) . '[^"\']*)["\'][^>]*)>/i',
			function ( $matches ) {
				$tag = $matches[0];

				// Skip if already has prefetch.
				if ( str_contains( $tag, 'data-prefetch' ) ) {
					return $tag;
				}

				// Add data-prefetch attribute.
				return str_replace( '<a ', '<a data-prefetch="true" ', $tag );
			},
			$content
		);

		return $content;
	}

	/**
	 * Minify HTML
	 *
	 * @param string $html HTML content.
	 * @return string
	 */
	private function minify_html( string $html ): string {
		// Remove HTML comments (except IE conditionals).
		$html = preg_replace( '/<!--(?!\[if).*?-->/s', '', $html );

		// Remove whitespace between tags.
		$html = preg_replace( '/>\s+</', '><', $html );

		// Reduce multiple spaces to single space.
		$html = preg_replace( '/\s+/', ' ', $html );

		return trim( $html );
	}

	/**
	 * Add preconnect hints to head
	 *
	 * @return void
	 */
	public function add_preconnect_hints(): void {
		if ( ! $this->settings['preconnect'] ) {
			return;
		}

		foreach ( $this->preconnect_origins as $origin ) {
			printf(
				'<link rel="preconnect" href="%s" crossorigin>' . "\n",
				esc_url( $origin )
			);
		}

		// DNS prefetch as fallback.
		foreach ( $this->preconnect_origins as $origin ) {
			$host = wp_parse_url( $origin, PHP_URL_HOST );
			if ( $host ) {
				printf(
					'<link rel="dns-prefetch" href="//%s">' . "\n",
					esc_attr( $host )
				);
			}
		}
	}

	/**
	 * Register service worker
	 *
	 * @return void
	 */
	public function register_service_worker(): void {
		if ( ! $this->settings['service_worker'] ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		$sw_url = defined( 'APOLLO_RIO_URL' ) ? APOLLO_RIO_URL . 'sw.js' : '';

		if ( empty( $sw_url ) ) {
			return;
		}
		?>
		<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function() {
				navigator.serviceWorker.register('<?php echo esc_js( $sw_url ); ?>')
					.then(function(registration) {
						console.log('Apollo SW registered:', registration.scope);

						// Track PWA installation in Apollo Analytics
						if (window.ApolloTrack && typeof window.ApolloTrack.event === 'function') {
							window.ApolloTrack.event('pwa_install', {
								service_worker_scope: registration.scope,
								install_timestamp: Date.now()
							});
						}
					})
					.catch(function(error) {
						console.log('Apollo SW registration failed:', error);
					});
			});
		}
		</script>
		<?php
	}

	/**
	 * Defer scripts
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string
	 */
	public function defer_scripts( string $tag, string $handle, string $src ): string {
		if ( ! $this->settings['defer_scripts'] ) {
			return $tag;
		}

		// Skip admin scripts.
		if ( is_admin() ) {
			return $tag;
		}

		// Skip already deferred/async scripts.
		if ( str_contains( $tag, 'defer' ) || str_contains( $tag, 'async' ) ) {
			return $tag;
		}

		// Skip critical scripts.
		$skip_handles = array(
			'jquery-core',
			'jquery-migrate',
			'wp-polyfill',
			'apollo-critical',
		);

		if ( in_array( $handle, $skip_handles, true ) ) {
			return $tag;
		}

		// Add defer attribute.
		return str_replace( ' src=', ' defer src=', $tag );
	}

	/**
	 * Add lazy loading to attachment images
	 *
	 * @param array   $attr       Image attributes.
	 * @param WP_Post $attachment Attachment post.
	 * @return array
	 */
	public function add_lazy_loading( array $attr, $attachment ): array {
		if ( ! $this->settings['lazy_load_images'] ) {
			return $attr;
		}

		// Add loading lazy if not present.
		if ( ! isset( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}

		// Add decoding async for better performance.
		if ( ! isset( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}

		return $attr;
	}

	/**
	 * Add manifest link
	 *
	 * @return void
	 */
	public function add_manifest_link(): void {
		$manifest_url = defined( 'APOLLO_RIO_URL' ) ? APOLLO_RIO_URL . 'manifest.json' : '';

		if ( empty( $manifest_url ) ) {
			return;
		}

		printf(
			'<link rel="manifest" href="%s">' . "\n",
			esc_url( $manifest_url )
		);
	}

	/**
	 * Add theme color meta
	 *
	 * @return void
	 */
	public function add_theme_color(): void {
		$theme_color = apply_filters( 'apollo_rio_theme_color', '#6366f1' );

		printf(
			'<meta name="theme-color" content="%s">' . "\n",
			esc_attr( $theme_color )
		);
	}

	/**
	 * Get optimization stats
	 *
	 * @return array<string, mixed>
	 */
	public function get_stats(): array {
		return array(
			'settings'           => $this->settings,
			'preconnect_origins' => $this->preconnect_origins,
			'service_worker'     => $this->settings['service_worker'],
		);
	}

	/**
	 * Update settings
	 *
	 * @param array $settings New settings.
	 * @return bool
	 */
	public function update_settings( array $settings ): bool {
		$this->settings = wp_parse_args( $settings, $this->settings );
		return update_option( 'apollo_rio_settings', $this->settings );
	}
}

// =========================================================================
// HELPER FUNCTIONS (Global namespace)
// =========================================================================

if ( ! function_exists( 'apollo_rio_integration' ) ) {
	/**
	 * Get Rio integration instance
	 *
	 * @return \Apollo_Rio\Integration\Apollo_Rio_Core_Integration
	 */
	function apollo_rio_integration(): \Apollo_Rio\Integration\Apollo_Rio_Core_Integration {
		return \Apollo_Rio\Integration\Apollo_Rio_Core_Integration::get_instance();
	}
}

if ( ! function_exists( 'apollo_rio_optimize' ) ) {
	/**
	 * Optimize content with Rio (wrapper)
	 *
	 * @param string $content HTML content.
	 * @param string $context Context.
	 * @param array  $args    Arguments.
	 * @return string
	 */
	function apollo_rio_optimize( string $content, string $context = 'page', array $args = array() ): string {
		return apollo_rio_integration()->optimize_content( $content, $context, $args );
	}
}

if ( ! function_exists( 'apollo_rio_is_pwa_enabled' ) ) {
	/**
	 * Check if PWA features are enabled
	 *
	 * @return bool
	 */
	function apollo_rio_is_pwa_enabled(): bool {
		$stats = apollo_rio_integration()->get_stats();
		return ! empty( $stats['service_worker'] );
	}

}

// Initialize integration.
add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'APOLLO_CORE_BOOTSTRAPPED' ) || class_exists( 'Apollo_Core' ) ) {
			\Apollo_Rio\Integration\Apollo_Rio_Core_Integration::get_instance();
		}
	},
	15
);
