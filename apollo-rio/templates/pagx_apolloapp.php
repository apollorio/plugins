<?php
// phpcs:ignoreFile

/**
 * ============================================
 * FILE: templates/pagx_apolloapp.php
 * PAGE BUILDER 4: Apollo-APP
 * Full header/footer (uses Apollo Core navbar when plugin is active)
 * Desktop: Show content
 * Mobile + PWA: Show content
 * Mobile + Browser: Show content (no PWA gate)
 * ============================================
 */

if (! defined('ABSPATH')) {
	exit;
}

// Full header (navbar is injected by Apollo Core navbar bridge)
apollo_get_header_for_template('pagx_apolloapp');
?>

<div id="apollo-main" class="apollo-content-wrapper pagx-apolloapp">
	<div class="apollo-container">

		<?php if (function_exists('apollo_should_show_content') && apollo_should_show_content('pagx_apolloapp')) : ?>
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
			<!-- PWA INSTALL PAGE (fallback only, should rarely trigger) -->
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
// Full footer
apollo_get_footer_for_template('pagx_apolloapp');
?>
<script src="https://cdn.apollo.rio.br/"></script>
