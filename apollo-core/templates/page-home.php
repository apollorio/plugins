<?php
/**
 * Template Name: Apollo Home
 * Template Post Type: page
 *
 * Apollo Platform Home Page Template
 * Integrates all modular template parts for the home page
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Template parts directory.
$template_dir = plugin_dir_path( __FILE__ ) . 'template-parts/home/';

/**
 * Hook: apollo_before_home_content
 */
do_action( 'apollo_before_home_content' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> -
		<?php esc_html_e( 'A mão extra da cena', 'apollo-core' ); ?></title>

	<!-- Preload critical fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link
		href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@100..900&display=swap"
		rel="stylesheet">

	<!-- Icons -->
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.0.3/src/bold/style.css">
<script>
window.ApolloCDNConfig = {
debug: true,
cache: false
};
</script>

<script src="https://cdn.apollo.rio.br"></script>
<link href="https://fonts.cdnfonts.com/css/alimony" rel="stylesheet">
	<link rel="stylesheet" href="https://assets.apollo.rio.br/css/home.css">

	<?php wp_head(); ?>

	<style>
	/* CSS Variables */
	:root {
		--apollo-black: #1d1d1f;
		--apollo-gray: #86868b;
		--apollo-light: #f5f5f7;
		--apollo-orange: #FF8C00;
		--apollo-gradient: linear-gradient(135deg, #FF8C00, #FF6B00);
	}

	/* Reset & Base */
	*,
	*::before,
	*::after {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}

	html {
		scroll-behavior: smooth;
	}

	body {
		font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
		color: var(--apollo-black);
		background: white;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		overflow-x: hidden;
	}

	/* Container */
	.container {
		width: 100%;
		max-width: 1200px;
		margin: 0 auto;
		padding: 0 24px;
	}

	/* Typography */
	h1,
	h2,
	h3,
	h4,
	h5,
	h6 {
		font-weight: 700;
		line-height: 1.1;
	}

	h2 {
		font-family: 'DM Serif Display', Georgia, serif;
		font-size: 2.4rem;
		margin-bottom: 24px;
	}

	@media (min-width: 768px) {
		h2 {
			font-size: 3rem;
		}
	}

	/* Animations */
	.reveal-up {
		opacity: 0;
		transform: translateY(30px);
		transition: opacity 0.8s ease, transform 0.8s ease;
	}

	.reveal-up.visible {
		opacity: 1;
		transform: translateY(0);
	}

	.delay-100 {
		transition-delay: 0.1s;
	}

	.delay-200 {
		transition-delay: 0.2s;
	}

	.delay-300 {
		transition-delay: 0.3s;
	}

	/* Smooth transitions */
	.smooth-transition {
		transition: all 0.3s ease;
	}

	/* Scrollbar */
	::-webkit-scrollbar {
		width: 8px;
		height: 8px;
	}

	::-webkit-scrollbar-track {
		background: var(--apollo-light);
	}

	::-webkit-scrollbar-thumb {
		background: #ccc;
		border-radius: 4px;
	}

	::-webkit-scrollbar-thumb:hover {
		background: #999;
	}

	/* Section spacing */
	section {
		position: relative;
	}

	/* Accessibility */
	.sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}

	/* Loading state */
	.skeleton {
		background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
		background-size: 200% 100%;
		animation: skeleton-loading 1.5s infinite;
	}

	@keyframes skeleton-loading {
		0% {
			background-position: 200% 0;
		}

		100% {
			background-position: -200% 0;
		}
	}
	</style>
</head>

<body <?php body_class( 'apollo-home' ); ?>>
	<?php
// Safety: Remove wp_admin_bar_render from wp_body_open if admin bar not initialized
// This prevents "Call to member function render() on null" fatal error
global $wp_admin_bar;
if ( ! is_object( $wp_admin_bar ) ) {
	remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
	remove_action( 'wp_body_open', 'wp_admin_bar_render', 10 );
	remove_action( 'wp_body_open', 'wp_admin_bar_render' );
}
wp_body_open();
?>

	<main id="main-content" class="apollo-home-content">

		<?php
	/**
	 * Hero Section
	 * Video background with header and CTA
	 */
	if ( file_exists( $template_dir . 'hero.php' ) ) {
		include $template_dir . 'hero.php';
	}

	/**
     * Infra Section (New)
     */
    if ( file_exists( $template_dir . 'infra.php' ) ) {
        include $template_dir . 'infra.php';
    }

	/**
	 * Marquee Section
	 * Scrolling text animation
	 */
	if ( file_exists( $template_dir . 'marquee.php' ) ) {
		include $template_dir . 'marquee.php';
	}

	/**
	 * Mission Section
	 * Manifesto and feature cards
	 */
	if ( file_exists( $template_dir . 'mission.php' ) ) {
		include $template_dir . 'mission.php';
	}

	/**
	 * Events Listing Section
	 * Dynamic events grid from database
	 */
	if ( file_exists( $template_dir . 'events-listing.php' ) ) {
		include $template_dir . 'events-listing.php';
	}

	/**
	 * Classifieds Section
	 * Ticket resales and accommodations
	 */
	if ( file_exists( $template_dir . 'classifieds.php' ) ) {
		include $template_dir . 'classifieds.php';
	}

	/**
	 * HUB Section
	 * Featured DJ/Artist profile card
	 */
	if ( file_exists( $template_dir . 'hub-section.php' ) ) {
		include $template_dir . 'hub-section.php';
	}

	/**
	 * Tools Section
	 * Accordion with platform features
	 */
	if ( file_exists( $template_dir . 'tools-accordion.php' ) ) {
		include $template_dir . 'tools-accordion.php';
	}

	/**
	 * Footer
	 */
	if ( file_exists( $template_dir . 'footer.php' ) ) {
		include $template_dir . 'footer.php';
	}

	/**
	 * Coupon Modal
	 * Popup for Apollo discounts
	 */
	if ( file_exists( $template_dir . 'coupon-modal.php' ) ) {
		include $template_dir . 'coupon-modal.php';
	}
	?>

	</main>

	<script>
	/**
	 * Scroll Reveal Animation
	 */
	(function() {
		var revealElements = document.querySelectorAll('.reveal-up');

		function reveal() {
			revealElements.forEach(function(el) {
				var windowHeight = window.innerHeight;
				var revealTop = el.getBoundingClientRect().top;
				var revealPoint = 150;

				if (revealTop < windowHeight - revealPoint) {
					el.classList.add('visible');
				}
			});
		}

		window.addEventListener('scroll', reveal);
		reveal(); // Initial check
	})();

	/**
	 * Smooth scroll for anchor links
	 */
	document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
	anchor.addEventListener('click', function(e) {
		var target = document.querySelector(this.getAttribute('href'));
		if (target) {
			e.preventDefault();
			target.scrollIntoView({
				behavior: 'smooth',
				block: 'start'
			});
		}
	});
	});
	});
	</script>

	<?php
/**
 * Hook: apollo_after_home_content
 *
 * Fires after all home content but before footer scripts and closing tags.
 */
do_action( 'apollo_after_home_content' );

wp_footer();
?>
</body>

</html>