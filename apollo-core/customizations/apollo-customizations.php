<?php
/**
 * Apollo Customizations
 *
 * ============================================================================
 * APOLLO-SPECIFIC THEME CUSTOMIZATIONS
 * ============================================================================
 *
 * This file acts as a "functions.php" equivalent for Apollo pages. Any
 * customizations that should apply ONLY to Apollo pages should be placed here.
 *
 * Unlike theme functions.php, this file:
 * - Is loaded only when is_apollo_page() returns true
 * - Is bundled with Apollo and survives theme switches
 * - Focuses on Apollo-specific behavior, not global site behavior
 *
 * GUIDELINES:
 * - Keep code organized by feature/section
 * - Use descriptive function names prefixed with apollo_custom_
 * - Document what each customization does
 * - Test on both Apollo and non-Apollo pages to confirm scoping
 *
 * NOTE: For CSS/JS customizations, prefer using the Apollo Snippets Manager
 * (Apollo > Snippets in admin). This file is for PHP logic only.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * =============================================================================
 * INITIALIZATION
 * =============================================================================
 */

/**
 * Initialize Apollo customizations
 *
 * @return void
 */
function apollo_customizations_init(): void {
	// Only load customizations on Apollo pages (frontend).
	if ( is_admin() ) {
		return;
	}

	// Setup hooks.
	apollo_custom_setup_body_classes();
	apollo_custom_setup_head_meta();
	apollo_custom_setup_footer();
}
add_action( 'wp', 'apollo_customizations_init' );

/**
 * =============================================================================
 * BODY CLASSES
 * =============================================================================
 */

/**
 * Setup body class hooks
 *
 * @return void
 */
function apollo_custom_setup_body_classes(): void {
	add_filter( 'body_class', 'apollo_custom_body_classes' );
}

/**
 * Add Apollo-specific body classes
 *
 * @param array $classes Existing body classes.
 * @return array Modified body classes
 */
function apollo_custom_body_classes( array $classes ): array {
	// Check if we're on an Apollo page.
	if ( function_exists( 'apollo_is_apollo_page' ) && apollo_is_apollo_page() ) {
		$classes[] = 'apollo-page';
		$classes[] = 'apollo-loaded';

		// Add dark mode class if enabled.
		if ( isset( $_COOKIE['apollo_dark_mode'] ) && '1' === $_COOKIE['apollo_dark_mode'] ) {
			$classes[] = 'apollo-dark';
		}

		// Add canvas mode class if active.
		$canvas_mode = get_query_var( 'canvas', '' );
		if ( '1' === $canvas_mode || 'true' === $canvas_mode ) {
			$classes[] = 'apollo-canvas-mode';
		}

		// Add route-specific classes.
		$route = get_query_var( 'apollo_route', '' );
		if ( $route ) {
			$classes[] = 'apollo-route-' . sanitize_html_class( $route );
		}

		// Add post type specific classes.
		if ( is_singular() ) {
			$post_type    = get_post_type();
			$apollo_types = array( 'event_listing', 'local', 'dj', 'post' );
			if ( in_array( $post_type, $apollo_types, true ) ) {
				$classes[] = 'apollo-single-' . sanitize_html_class( $post_type );
			}
		}
	}

	return $classes;
}

/**
 * =============================================================================
 * HEAD META TAGS
 * =============================================================================
 */

/**
 * Setup head meta hooks
 *
 * @return void
 */
function apollo_custom_setup_head_meta(): void {
	add_action( 'wp_head', 'apollo_custom_head_meta', 5 );
}

/**
 * Output Apollo-specific head meta tags
 *
 * @return void
 */
function apollo_custom_head_meta(): void {
	if ( ! function_exists( 'apollo_is_apollo_page' ) || ! apollo_is_apollo_page() ) {
		return;
	}

	// Theme color for mobile browsers.
	echo '<meta name="theme-color" content="#0d0d0d" media="(prefers-color-scheme: dark)">' . "\n";
	echo '<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">' . "\n";

	// Apollo app identifier.
	echo '<meta name="apollo-app" content="true">' . "\n";

	// Viewport settings for Apollo pages.
	// echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
}

/**
 * =============================================================================
 * FOOTER CUSTOMIZATIONS
 * =============================================================================
 */

/**
 * Setup footer hooks
 *
 * @return void
 */
function apollo_custom_setup_footer(): void {
	add_action( 'wp_footer', 'apollo_custom_footer_scripts', 99 );
}

/**
 * Output Apollo-specific footer scripts
 *
 * @return void
 */
function apollo_custom_footer_scripts(): void {
	if ( ! function_exists( 'apollo_is_apollo_page' ) || ! apollo_is_apollo_page() ) {
		return;
	}

	// Add page load animation complete class.
	?>
	<script>
	(function() {
		// Mark page as loaded after DOM ready.
		document.addEventListener('DOMContentLoaded', function() {
			document.body.classList.add('apollo-dom-ready');
		});

		// Mark page as fully loaded after all resources.
		window.addEventListener('load', function() {
			document.body.classList.add('apollo-fully-loaded');
		});
	})();
	</script>
	<?php
}

/**
 * =============================================================================
 * UTILITY FUNCTIONS
 * =============================================================================
 */

/**
 * Check if current request is for an Apollo single page
 *
 * @return bool
 */
function apollo_is_single_page(): bool {
	if ( ! is_singular() ) {
		return false;
	}

	$apollo_types = array( 'event_listing', 'local', 'dj' );
	return in_array( get_post_type(), $apollo_types, true );
}

/**
 * Check if dark mode is enabled
 *
 * @return bool
 */
function apollo_is_dark_mode(): bool {
	return isset( $_COOKIE['apollo_dark_mode'] ) && '1' === $_COOKIE['apollo_dark_mode'];
}

/**
 * Get current Apollo route name
 *
 * @return string Route name or empty string
 */
function apollo_get_current_route(): string {
	return sanitize_text_field( get_query_var( 'apollo_route', '' ) );
}

/**
 * Check if canvas mode is active
 *
 * @return bool
 */
if ( ! function_exists( 'apollo_is_canvas_mode' ) ) {
	function apollo_is_canvas_mode(): bool {
		$canvas = get_query_var( 'canvas', '' );
		return '1' === $canvas || 'true' === $canvas;
	}
}

/**
 * =============================================================================
 * TEMPLATE HELPERS
 * =============================================================================
 */

/**
 * Get placeholder image URL by type
 *
 * @param string $type Placeholder type: event, venue, dj, user, default.
 * @return string Image URL
 */
function apollo_get_placeholder_image( string $type = 'default' ): string {
	$base_url = APOLLO_CORE_PLUGIN_URL . 'assets/img/';

	$placeholders = array(
		'event'   => 'placeholder-event.webp',
		'venue'   => 'placeholder-venue.webp',
		'dj'      => 'placeholder-dj.webp',
		'user'    => 'placeholder-dj.webp',
		'default' => 'default-event.jpg',
	);

	$file = $placeholders[ $type ] ?? $placeholders['default'];

	return esc_url( $base_url . $file );
}

/**
 * Get Apollo logo URL
 *
 * @param string $variant Logo variant: default, light, dark.
 * @return string Logo URL
 */
function apollo_get_logo_url( string $variant = 'default' ): string {
	// For now, single logo. Extend with variants as needed.
	$logos = array(
		'default' => 'apollo-logo.webp',
		'light'   => 'apollo-logo-light.webp',
		'dark'    => 'apollo-logo-dark.webp',
	);

	$file = $logos[ $variant ] ?? $logos['default'];
	return esc_url( APOLLO_CORE_PLUGIN_URL . 'assets/img/' . $file );
}

/**
 * =============================================================================
 * HOOKS FOR EXTENSIONS
 * =============================================================================
 */

/**
 * Allow other plugins to add customizations
 *
 * @since 2.0.0
 */
do_action( 'apollo_customizations_loaded' );
