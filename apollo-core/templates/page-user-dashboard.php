<?php
/**
 * Template Name: User Dashboard
 * Description: Private user dashboard with stats, events, settings
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/login' ) );
	exit;
}

$current_user = wp_get_current_user();
$user_stats   = apollo_get_user_dashboard_stats( $current_user->ID );

get_header( 'dashboard' );
?>

<div class="apollo-shell">
	
	<?php get_template_part( 'template-parts/user/header' ); ?>
	
	<?php get_template_part( 'template-parts/user/hero' ); ?>
	
	<main class="main-grid">
		
		<section>
			<?php get_template_part( 'template-parts/user/tabs-nav' ); ?>
			
			<div id="tab-interests" class="content-section active">
				<?php get_template_part( 'template-parts/user/tab-interests' ); ?>
			</div>
			
			<div id="tab-nucleos" class="content-section">
				<?php get_template_part( 'template-parts/user/tab-nucleos' ); ?>
			</div>
			
			<div id="tab-communities" class="content-section">
				<?php get_template_part( 'template-parts/user/tab-communities' ); ?>
			</div>
			
			<div id="tab-docs" class="content-section">
				<?php get_template_part( 'template-parts/user/tab-docs' ); ?>
			</div>
			
			<div id="tab-settings" class="content-section">
				<?php get_template_part( 'template-parts/user/tab-settings' ); ?>
			</div>
		</section>
		
		<aside>
			<?php get_template_part( 'template-parts/user/sidebar-summary' ); ?>
			<?php get_template_part( 'template-parts/user/sidebar-status' ); ?>
		</aside>
		
	</main>
	
	<footer class="apollo-footer">
		apollo::rio dashboard · visual style v2.0
	</footer>
	
</div>

<?php get_footer( 'dashboard' ); ?>
