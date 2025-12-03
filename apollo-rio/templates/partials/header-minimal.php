<?php
// phpcs:ignoreFile
/**
 * ============================================
 * FILE: templates/partials/header-minimal.php
 * MINIMAL HEADER without navigation
 * Used by: pagx_appclean
 * ============================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="apollo-html apollo-minimal">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="theme-color" content="#FFFFF">
	
	<!-- PWA Manifest - served from apollo-social plugin -->
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'apollo-body apollo-body-minimal' ); ?>>
<?php wp_body_open(); ?>

<div id="apollo-wrapper" class="apollo-site-wrapper apollo-wrapper-minimal">
	
	<!-- Minimal Header (Logo only, no nav) -->
	<header id="apollo-header" class="apollo-header apollo-header-minimal">
		<div class="apollo-header-container">
			
			<!-- Logo Only -->
			<div class="apollo-branding-minimal">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="apollo-site-title-minimal">
						Apollo::Rio
					</a>
				<?php endif; ?>
			</div>
			
			<!-- Optional: Back button for app context -->
			<?php if ( is_user_logged_in() ) : ?>
				<button onclick="window.history.back()" class="apollo-back-btn" aria-label="Back">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 12H5M12 19l-7-7 7-7"/>
					</svg>
				</button>
			<?php endif; ?>
			
		</div>
	</header>
	
	<!-- Main Content Start -->
	<main id="apollo-content" class="apollo-main-content apollo-main-minimal">