<?php
// phpcs:ignoreFile
/**
 * ============================================
 * FILE: templates/pagx_app.php
 * PAGE BUILDER 2: App::rio
 * Full header/footer
 * Desktop: Show content
 * Mobile + PWA: Show content
 * Mobile + Browser: Show PWA install page
 * ============================================
 */

if (! defined('ABSPATH')) {
    exit;
}

// Get full header
apollo_get_header_for_template('pagx_app');
?>

<div id="apollo-main" class="apollo-content-wrapper pagx-app">
	<div class="apollo-container">

		<?php if (function_exists('apollo_should_show_content') && apollo_should_show_content('pagx_app')) : ?>
			<!-- REGULAR CONTENT -->
			<?php
            while (have_posts()) :
                the_post();
                ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class('apollo-article'); ?>>

					<!-- ✅ CANVAS MODE: Title removed - only content -->
					<div class="apollo-entry-content">
						<?php the_content(); ?>
					</div>

					<?php
                    if (comments_open() || get_comments_number()) {
                        comments_template();
                    }
                ?>

				</article>
				<?php
            endwhile;
		    ?>

		<?php else : ?>
			<!-- PWA INSTALL PAGE -->
			<?php
		    if (function_exists('apollo_render_pwa_install_page')) {
		        apollo_render_pwa_install_page();
		    } else {
		        echo '<p>' . esc_html__('Instale o app para acessar este conteúdo.', 'apollo-rio') . '</p>';
		    }
		    ?>

		<?php endif; ?>

	</div>
</div>

<?php
// Get full footer
apollo_get_footer_for_template('pagx_app');
?>
<script src="https://cdn.apollo.rio.br/"></script>
