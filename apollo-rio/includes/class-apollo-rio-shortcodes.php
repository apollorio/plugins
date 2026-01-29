<?php
/**
 * Apollo RIO - Performance Shortcodes
 *
 * Shortcodes for optimized content loading, lazy load, and PWA features.
 * Provides performance-focused content wrappers and optimization utilities.
 *
 * @package Apollo_RIO
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_RIO_Shortcodes
 *
 * Performance-optimized shortcodes for RIO (Runtime Image Optimization).
 */
class Apollo_RIO_Shortcodes {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_RIO_Shortcodes|null
	 */
	private static ?Apollo_RIO_Shortcodes $instance = null;

	/**
	 * Performance metrics storage
	 *
	 * @var array
	 */
	private array $metrics = array();

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_RIO_Shortcodes
	 */
	public static function get_instance(): Apollo_RIO_Shortcodes {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->register_shortcodes();
	}

	/**
	 * Register all shortcodes
	 *
	 * @return void
	 */
	private function register_shortcodes(): void {
		// Optimized content wrapper.
		add_shortcode( 'apollo_rio_optimized', array( $this, 'render_optimized_content' ) );

		// Lazy load wrapper.
		add_shortcode( 'apollo_rio_lazy', array( $this, 'render_lazy_load' ) );

		// Skeleton loader.
		add_shortcode( 'apollo_rio_skeleton', array( $this, 'render_skeleton' ) );

		// Progressive image.
		add_shortcode( 'apollo_rio_image', array( $this, 'render_progressive_image' ) );

		// Deferred content (loads after page ready).
		add_shortcode( 'apollo_rio_defer', array( $this, 'render_deferred_content' ) );

		// Prefetch links.
		add_shortcode( 'apollo_rio_prefetch', array( $this, 'render_prefetch' ) );

		// Intersection observer container.
		add_shortcode( 'apollo_rio_viewport', array( $this, 'render_viewport_trigger' ) );

		// Performance debug info (dev only).
		add_shortcode( 'apollo_rio_debug', array( $this, 'render_debug_info' ) );
	}

	// =========================================================================
	// [apollo_rio_optimized] - Optimized Content Wrapper
	// =========================================================================

	/**
	 * Render optimized content shortcode
	 *
	 * Wraps content with optimized loading strategies including:
	 * - Critical CSS inlining
	 * - Deferred JS loading
	 * - Image optimization
	 * - Resource hints
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Enclosed content.
	 * @return string
	 */
	public function render_optimized_content( $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'priority'    => 'normal',
				'cache'       => 'true',
				'lazy_images' => 'true',
				'defer_js'    => 'true',
			),
			$atts,
			'apollo_rio_optimized'
		);

		if ( empty( $content ) ) {
			return '';
		}

		$processed_content = $content;

		// Apply lazy loading to images.
		if ( 'true' === $atts['lazy_images'] ) {
			$processed_content = $this->apply_lazy_loading( $processed_content );
		}

		// Apply script deferring.
		if ( 'true' === $atts['defer_js'] ) {
			$processed_content = $this->defer_scripts( $processed_content );
		}

		// Add optimization markers.
		$priority_class = 'rio-priority--' . sanitize_key( $atts['priority'] );

		$wrapper = sprintf(
			'<div class="apollo-rio-optimized %s" data-rio-optimized="true" data-cache="%s">%s</div>',
			esc_attr( $priority_class ),
			esc_attr( $atts['cache'] ),
			do_shortcode( $processed_content )
		);

		return $wrapper;
	}

	/**
	 * Apply lazy loading to images in content
	 *
	 * @param string $content Content with images.
	 * @return string
	 */
	private function apply_lazy_loading( string $content ): string {
		// Add loading="lazy" to images without it.
		$content = preg_replace(
			'/<img(?![^>]*loading=)([^>]*)(\/?)>/i',
			'<img loading="lazy"$1$2>',
			$content
		);

		// Add decoding="async" for better performance.
		$content = preg_replace(
			'/<img(?![^>]*decoding=)([^>]*)(\/?)>/i',
			'<img decoding="async"$1$2>',
			$content
		);

		return $content;
	}

	/**
	 * Defer inline scripts in content
	 *
	 * @param string $content Content with scripts.
	 * @return string
	 */
	private function defer_scripts( string $content ): string {
		// Add defer to inline scripts without it.
		$content = preg_replace(
			'/<script(?![^>]*defer)([^>]*)>/i',
			'<script defer$1>',
			$content
		);

		return $content;
	}

	// =========================================================================
	// [apollo_rio_lazy] - Lazy Load Wrapper
	// =========================================================================

	/**
	 * Render lazy load shortcode
	 *
	 * Content is loaded only when visible in viewport.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Enclosed content.
	 * @return string
	 */
	public function render_lazy_load( $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'threshold'   => '100px',
				'placeholder' => 'skeleton',
				'animation'   => 'fade',
				'height'      => 'auto',
			),
			$atts,
			'apollo_rio_lazy'
		);

		if ( empty( $content ) ) {
			return '';
		}

		$unique_id       = 'rio-lazy-' . wp_unique_id();
		$encoded_content = base64_encode( $content );

		$placeholder_html = $this->get_placeholder_html( $atts['placeholder'], $atts['height'] );

		ob_start();
		?>
		<div
			class="apollo-rio-lazy"
			id="<?php echo esc_attr( $unique_id ); ?>"
			data-rio-lazy="true"
			data-threshold="<?php echo esc_attr( $atts['threshold'] ); ?>"
			data-animation="<?php echo esc_attr( $atts['animation'] ); ?>"
			style="min-height: <?php echo esc_attr( $atts['height'] ); ?>;"
		>
			<div class="apollo-rio-lazy__placeholder">
				<?php echo $placeholder_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<template class="apollo-rio-lazy__content">
				<?php echo $encoded_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</template>
		</div>
		<?php

		$this->enqueue_lazy_script();

		return ob_get_clean();
	}

	/**
	 * Get placeholder HTML based on type
	 *
	 * @param string $type   Placeholder type.
	 * @param string $height Height value.
	 * @return string
	 */
	private function get_placeholder_html( string $type, string $height ): string {
		switch ( $type ) {
			case 'skeleton':
				return $this->render_skeleton(
					array(
						'type'   => 'content',
						'height' => $height,
					)
				);

			case 'spinner':
				return '<div class="apollo-rio-spinner"><i class="ri-loader-4-line ri-spin"></i></div>';

			case 'pulse':
				return '<div class="apollo-rio-pulse"></div>';

			default:
				return '<div class="apollo-rio-placeholder"></div>';
		}
	}

	// =========================================================================
	// [apollo_rio_skeleton] - Skeleton Loader
	// =========================================================================

	/**
	 * Render skeleton loader shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_skeleton( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'type'    => 'text',
				'lines'   => 3,
				'height'  => '1rem',
				'width'   => '100%',
				'rounded' => 'false',
				'avatar'  => 'false',
			),
			$atts,
			'apollo_rio_skeleton'
		);

		$this->enqueue_assets();

		ob_start();
		?>
		<div class="apollo-skeleton apollo-skeleton--<?php echo esc_attr( $atts['type'] ); ?>" aria-hidden="true">
			<?php if ( 'card' === $atts['type'] ) : ?>
				<div class="apollo-skeleton__image" style="height: 200px;"></div>
				<div class="apollo-skeleton__body">
					<?php if ( 'true' === $atts['avatar'] ) : ?>
						<div class="apollo-skeleton__avatar"></div>
					<?php endif; ?>
					<div class="apollo-skeleton__text apollo-skeleton__text--title"></div>
					<div class="apollo-skeleton__text"></div>
					<div class="apollo-skeleton__text apollo-skeleton__text--short"></div>
				</div>
			<?php elseif ( 'content' === $atts['type'] ) : ?>
				<?php for ( $i = 0; $i < absint( $atts['lines'] ); $i++ ) : ?>
					<div class="apollo-skeleton__text" style="width: <?php echo $i === absint( $atts['lines'] ) - 1 ? '60%' : '100%'; ?>;"></div>
				<?php endfor; ?>
			<?php elseif ( 'avatar' === $atts['type'] ) : ?>
				<div class="apollo-skeleton__avatar <?php echo 'true' === $atts['rounded'] ? 'apollo-skeleton__avatar--rounded' : ''; ?>"></div>
			<?php elseif ( 'image' === $atts['type'] ) : ?>
				<div class="apollo-skeleton__image" style="height: <?php echo esc_attr( $atts['height'] ); ?>; width: <?php echo esc_attr( $atts['width'] ); ?>;"></div>
			<?php else : ?>
				<?php for ( $i = 0; $i < absint( $atts['lines'] ); $i++ ) : ?>
					<div class="apollo-skeleton__text" style="height: <?php echo esc_attr( $atts['height'] ); ?>;"></div>
				<?php endfor; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_rio_image] - Progressive Image Loading
	// =========================================================================

	/**
	 * Render progressive image shortcode
	 *
	 * Uses LQIP (Low Quality Image Placeholder) technique.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_progressive_image( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'src'    => '',
				'alt'    => '',
				'width'  => '',
				'height' => '',
				'sizes'  => '',
				'class'  => '',
				'lqip'   => '',
				'webp'   => 'true',
			),
			$atts,
			'apollo_rio_image'
		);

		if ( empty( $atts['src'] ) ) {
			return '';
		}

		$this->enqueue_assets();
		$this->enqueue_progressive_image_script();

		// Generate LQIP if not provided.
		$lqip = $atts['lqip'];
		if ( empty( $lqip ) ) {
			$lqip = $this->generate_lqip_url( $atts['src'] );
		}

		// Generate srcset for responsive images.
		$srcset = $this->generate_srcset( $atts['src'] );

		$unique_id = 'rio-img-' . wp_unique_id();

		ob_start();
		?>
		<div class="apollo-rio-image <?php echo esc_attr( $atts['class'] ); ?>" id="<?php echo esc_attr( $unique_id ); ?>">
			<img
				src="<?php echo esc_url( $lqip ); ?>"
				data-src="<?php echo esc_url( $atts['src'] ); ?>"
				<?php if ( $srcset ) : ?>
					data-srcset="<?php echo esc_attr( $srcset ); ?>"
				<?php endif; ?>
				<?php if ( $atts['sizes'] ) : ?>
					sizes="<?php echo esc_attr( $atts['sizes'] ); ?>"
				<?php endif; ?>
				alt="<?php echo esc_attr( $atts['alt'] ); ?>"
				<?php if ( $atts['width'] ) : ?>
					width="<?php echo esc_attr( $atts['width'] ); ?>"
				<?php endif; ?>
				<?php if ( $atts['height'] ) : ?>
					height="<?php echo esc_attr( $atts['height'] ); ?>"
				<?php endif; ?>
				class="apollo-rio-image__img apollo-rio-image__img--lqip"
				loading="lazy"
				decoding="async"
			>
			<noscript>
				<img
					src="<?php echo esc_url( $atts['src'] ); ?>"
					alt="<?php echo esc_attr( $atts['alt'] ); ?>"
					<?php if ( $atts['width'] ) : ?>
						width="<?php echo esc_attr( $atts['width'] ); ?>"
					<?php endif; ?>
					<?php if ( $atts['height'] ) : ?>
						height="<?php echo esc_attr( $atts['height'] ); ?>"
					<?php endif; ?>
				>
			</noscript>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate LQIP URL for image
	 *
	 * @param string $src Original image URL.
	 * @return string
	 */
	private function generate_lqip_url( string $src ): string {
		// Use a placeholder service or generate a blurred version.
		// For now, return a simple placeholder.
		$attachment_id = attachment_url_to_postid( $src );

		if ( $attachment_id ) {
			$thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
			if ( $thumbnail ) {
				return $thumbnail[0];
			}
		}

		// Return a SVG placeholder.
		return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3Crect fill='%23f0f0f0' width='1' height='1'/%3E%3C/svg%3E";
	}

	/**
	 * Generate srcset for responsive images
	 *
	 * @param string $src Original image URL.
	 * @return string
	 */
	private function generate_srcset( string $src ): string {
		$attachment_id = attachment_url_to_postid( $src );

		if ( ! $attachment_id ) {
			return '';
		}

		return wp_get_attachment_image_srcset( $attachment_id, 'full' ) ?: '';
	}

	// =========================================================================
	// [apollo_rio_defer] - Deferred Content Loading
	// =========================================================================

	/**
	 * Render deferred content shortcode
	 *
	 * Content loads after initial page render.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Enclosed content.
	 * @return string
	 */
	public function render_deferred_content( $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'delay'       => '0',
				'event'       => 'load',
				'placeholder' => 'skeleton',
			),
			$atts,
			'apollo_rio_defer'
		);

		if ( empty( $content ) ) {
			return '';
		}

		$unique_id       = 'rio-defer-' . wp_unique_id();
		$encoded_content = base64_encode( $content );

		$placeholder_html = $this->get_placeholder_html( $atts['placeholder'], 'auto' );

		ob_start();
		?>
		<div
			class="apollo-rio-defer"
			id="<?php echo esc_attr( $unique_id ); ?>"
			data-rio-defer="true"
			data-delay="<?php echo esc_attr( $atts['delay'] ); ?>"
			data-event="<?php echo esc_attr( $atts['event'] ); ?>"
		>
			<div class="apollo-rio-defer__placeholder">
				<?php echo $placeholder_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<template class="apollo-rio-defer__content">
				<?php echo $encoded_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</template>
		</div>
		<?php

		$this->enqueue_defer_script();

		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_rio_prefetch] - Resource Prefetching
	// =========================================================================

	/**
	 * Render prefetch shortcode
	 *
	 * Adds prefetch hints for specified resources.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_prefetch( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'urls' => '',
				'type' => 'prefetch',
				'as'   => '',
			),
			$atts,
			'apollo_rio_prefetch'
		);

		if ( empty( $atts['urls'] ) ) {
			return '';
		}

		$urls = array_map( 'trim', explode( ',', $atts['urls'] ) );
		$type = in_array( $atts['type'], array( 'prefetch', 'preload', 'preconnect', 'dns-prefetch' ), true )
			? $atts['type']
			: 'prefetch';

		foreach ( $urls as $url ) {
			$this->add_resource_hint( $url, $type, $atts['as'] );
		}

		return '<!-- Apollo RIO: ' . count( $urls ) . ' resources ' . $type . 'ed -->';
	}

	/**
	 * Add resource hint to head
	 *
	 * @param string $url  Resource URL.
	 * @param string $type Hint type.
	 * @param string $as   Resource type.
	 * @return void
	 */
	private function add_resource_hint( string $url, string $type, string $as = '' ): void {
		add_action(
			'wp_head',
			function () use ( $url, $type, $as ) {
				$attrs = sprintf( 'rel="%s" href="%s"', esc_attr( $type ), esc_url( $url ) );
				if ( 'preload' === $type && $as ) {
					$attrs .= sprintf( ' as="%s"', esc_attr( $as ) );
				}
				echo '<link ' . $attrs . ">\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			},
			2
		);
	}

	// =========================================================================
	// [apollo_rio_viewport] - Viewport Trigger
	// =========================================================================

	/**
	 * Render viewport trigger shortcode
	 *
	 * Triggers events/callbacks when element enters viewport.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Enclosed content.
	 * @return string
	 */
	public function render_viewport_trigger( $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'callback'  => '',
				'threshold' => '0',
				'once'      => 'true',
				'class'     => '',
			),
			$atts,
			'apollo_rio_viewport'
		);

		$unique_id = 'rio-viewport-' . wp_unique_id();

		ob_start();
		?>
		<div
			class="apollo-rio-viewport <?php echo esc_attr( $atts['class'] ); ?>"
			id="<?php echo esc_attr( $unique_id ); ?>"
			data-rio-viewport="true"
			data-callback="<?php echo esc_attr( $atts['callback'] ); ?>"
			data-threshold="<?php echo esc_attr( $atts['threshold'] ); ?>"
			data-once="<?php echo esc_attr( $atts['once'] ); ?>"
		>
			<?php echo do_shortcode( $content ); ?>
		</div>
		<?php

		$this->enqueue_viewport_script();

		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_rio_debug] - Performance Debug Info
	// =========================================================================

	/**
	 * Render debug info shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_debug_info( $atts = array() ): string {
		// Only show for admins in development.
		if ( ! current_user_can( 'manage_options' ) || ! WP_DEBUG ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'show' => 'all',
			),
			$atts,
			'apollo_rio_debug'
		);

		global $wp_scripts, $wp_styles;

		$debug_data = array(
			'php_version'    => PHP_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'memory_usage'   => size_format( memory_get_usage( true ) ),
			'memory_peak'    => size_format( memory_get_peak_usage( true ) ),
			'queries'        => get_num_queries(),
			'load_time'      => timer_stop( 0, 3 ) . 's',
			'scripts_loaded' => count( $wp_scripts->done ),
			'styles_loaded'  => count( $wp_styles->done ),
			'cache_status'   => wp_using_ext_object_cache() ? 'External' : 'Built-in',
		);

		ob_start();
		?>
		<div class="apollo-rio-debug apollo-card">
			<div class="apollo-card__header">
				<h4><i class="ri-bug-line"></i> Apollo RIO Debug</h4>
			</div>
			<div class="apollo-card__body">
				<table class="apollo-table apollo-table--compact">
					<tbody>
						<?php foreach ( $debug_data as $key => $value ) : ?>
							<tr>
								<th><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></th>
								<td><?php echo esc_html( $value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// SCRIPT ENQUEUEING
	// =========================================================================

	/**
	 * Enqueue base assets
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		// Enqueue global Apollo assets.
		if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
			apollo_enqueue_global_assets();
		}

		// Apollo CDN Loader - handles all CSS/JS from CDN automatically
		// CDN URL: https://assets.apollo.rio.br/index.min.js
		// Auto-loads: styles/index.css, icon.js, reveal effects, dark mode, etc.
		if ( ! wp_script_is( 'apollo-cdn-loader', 'registered' ) ) {
			wp_register_script(
				'apollo-cdn-loader',
				'https://assets.apollo.rio.br/index.min.js',
				array(),
				'3.1.0',
				false // Load in head for priority
			);
		}
		wp_enqueue_script( 'apollo-cdn-loader' );

		// Enqueue Remix Icon (as fallback - CDN also loads icons).
		wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );

		// Add skeleton CSS inline.
		$this->add_inline_skeleton_css();
	}

	/**
	 * Add inline skeleton CSS
	 *
	 * @return void
	 */
	private function add_inline_skeleton_css(): void {
		static $added = false;

		if ( $added ) {
			return;
		}

		$css = '
			.apollo-skeleton { --skeleton-bg: #e0e0e0; --skeleton-shine: #f0f0f0; }
			.apollo-skeleton__text,
			.apollo-skeleton__image,
			.apollo-skeleton__avatar {
				background: linear-gradient(90deg, var(--skeleton-bg) 25%, var(--skeleton-shine) 50%, var(--skeleton-bg) 75%);
				background-size: 200% 100%;
				animation: skeleton-shimmer 1.5s infinite;
				border-radius: 4px;
			}
			.apollo-skeleton__text { height: 1rem; margin-bottom: 0.5rem; }
			.apollo-skeleton__text--title { height: 1.5rem; width: 60%; }
			.apollo-skeleton__text--short { width: 40%; }
			.apollo-skeleton__avatar { width: 48px; height: 48px; border-radius: 50%; }
			.apollo-skeleton__avatar--rounded { border-radius: 8px; }
			.apollo-skeleton__image { height: 200px; border-radius: 8px; }
			.apollo-rio-spinner { display: flex; align-items: center; justify-content: center; padding: 2rem; }
			.apollo-rio-pulse { height: 100%; min-height: 100px; background: var(--skeleton-bg); animation: pulse 2s infinite; border-radius: 8px; }
			.apollo-rio-image__img { transition: filter 0.3s, opacity 0.3s; }
			.apollo-rio-image__img--lqip { filter: blur(10px); }
			.apollo-rio-image__img--loaded { filter: blur(0); }
			@keyframes skeleton-shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
			@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
			.ri-spin { animation: ri-spin 1s linear infinite; }
			@keyframes ri-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
		';

		wp_add_inline_style( 'wp-block-library', $css );
		$added = true;
	}

	/**
	 * Enqueue lazy load script
	 *
	 * @return void
	 */
	private function enqueue_lazy_script(): void {
		static $added = false;

		if ( $added ) {
			return;
		}

		$script = "
			document.addEventListener('DOMContentLoaded', function() {
				const lazyContainers = document.querySelectorAll('[data-rio-lazy]');
				const observer = new IntersectionObserver((entries) => {
					entries.forEach(entry => {
						if (entry.isIntersecting) {
							const container = entry.target;
							const template = container.querySelector('template');
							if (template) {
								const content = atob(template.innerHTML.trim());
								container.innerHTML = content;
								container.classList.add('apollo-rio-lazy--loaded');
							}
							observer.unobserve(container);
						}
					});
				}, { rootMargin: '100px' });
				lazyContainers.forEach(container => observer.observe(container));
			});
		";

		wp_add_inline_script( 'wp-hooks', $script );
		$added = true;
	}

	/**
	 * Enqueue defer script
	 *
	 * @return void
	 */
	private function enqueue_defer_script(): void {
		static $added = false;

		if ( $added ) {
			return;
		}

		$script = "
			window.addEventListener('load', function() {
				document.querySelectorAll('[data-rio-defer]').forEach(function(container) {
					const delay = parseInt(container.dataset.delay) || 0;
					setTimeout(function() {
						const template = container.querySelector('template');
						if (template) {
							const content = atob(template.innerHTML.trim());
							container.innerHTML = content;
							container.classList.add('apollo-rio-defer--loaded');
						}
					}, delay);
				});
			});
		";

		wp_add_inline_script( 'wp-hooks', $script );
		$added = true;
	}

	/**
	 * Enqueue progressive image script
	 *
	 * @return void
	 */
	private function enqueue_progressive_image_script(): void {
		static $added = false;

		if ( $added ) {
			return;
		}

		$script = "
			document.addEventListener('DOMContentLoaded', function() {
				document.querySelectorAll('.apollo-rio-image__img[data-src]').forEach(function(img) {
					const fullSrc = img.dataset.src;
					const fullSrcset = img.dataset.srcset;
					const newImg = new Image();
					newImg.onload = function() {
						img.src = fullSrc;
						if (fullSrcset) img.srcset = fullSrcset;
						img.classList.remove('apollo-rio-image__img--lqip');
						img.classList.add('apollo-rio-image__img--loaded');
					};
					newImg.src = fullSrc;
				});
			});
		";

		wp_add_inline_script( 'wp-hooks', $script );
		$added = true;
	}

	/**
	 * Enqueue viewport observer script
	 *
	 * @return void
	 */
	private function enqueue_viewport_script(): void {
		static $added = false;

		if ( $added ) {
			return;
		}

		$script = "
			document.addEventListener('DOMContentLoaded', function() {
				document.querySelectorAll('[data-rio-viewport]').forEach(function(container) {
					const callback = container.dataset.callback;
					const threshold = parseFloat(container.dataset.threshold) || 0;
					const once = container.dataset.once !== 'false';

					const observer = new IntersectionObserver((entries) => {
						entries.forEach(entry => {
							if (entry.isIntersecting) {
								container.classList.add('apollo-rio-viewport--visible');
								container.dispatchEvent(new CustomEvent('rio:viewport', { detail: { visible: true }}));
								if (callback && typeof window[callback] === 'function') {
									window[callback](container);
								}
								if (once) observer.unobserve(container);
							}
						});
					}, { threshold: threshold });

					observer.observe(container);
				});
			});
		";

		wp_add_inline_script( 'wp-hooks', $script );
		$added = true;
	}
}

// Initialize on init.
add_action(
	'init',
	function () {
		Apollo_RIO_Shortcodes::get_instance();
	},
	20
);
