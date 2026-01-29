<?php
// phpcs:ignoreFile
/**
 * ============================================
 * PARTIAL: templates/partials/footer.php
 * APOLLO RIO FOOTER - BLANK CANVAS
 * ============================================
 *
 * Full HTML document footer for Apollo Rio templates.
 * Used by: pagx_site, pagx_app, pagx_apolloapp
 *
 * Features:
 * - Widget areas for customization
 * - Footer navigation menu
 * - Copyright notice
 * - wp_footer() for proper script loading
 * - Dark mode support
 *
 * @package Apollo_Rio
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
	</div><!-- #apollo-content -->

	<!-- Apollo Footer -->
	<footer id="apollo-footer" class="apollo-footer mt-auto border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
		<div class="apollo-footer-container max-w-7xl mx-auto px-4 py-8 md:py-12">

			<?php if (is_active_sidebar('apollo_footer_1') || is_active_sidebar('apollo_footer_2') || is_active_sidebar('apollo_footer_3')) : ?>
				<div class="apollo-footer-widgets grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
					<div class="apollo-footer-column">
						<?php dynamic_sidebar('apollo_footer_1'); ?>
					</div>
					<div class="apollo-footer-column">
						<?php dynamic_sidebar('apollo_footer_2'); ?>
					</div>
					<div class="apollo-footer-column">
						<?php dynamic_sidebar('apollo_footer_3'); ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="apollo-footer-info flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-slate-100 dark:border-slate-800">
				<p class="apollo-copyright text-sm text-slate-500 dark:text-slate-400">
					&copy; <?php echo esc_html(date('Y')); ?>
					<a href="<?php echo esc_url(home_url('/')); ?>" class="text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">Apollo::Rio</a>
					— <?php esc_html_e('Todos os direitos reservados', 'apollo-rio'); ?>
				</p>

				<?php
                if (has_nav_menu('apollo_footer')) {
                    wp_nav_menu([
                        'theme_location' => 'apollo_footer',
                        'menu_id'        => 'apollo-footer-menu',
                        'menu_class'     => 'flex flex-wrap gap-4 text-sm text-slate-500 dark:text-slate-400',
                        'container'      => 'nav',
                        'container_class' => 'apollo-footer-nav',
                        'depth'          => 1,
                    ]);
                }
                ?>
			</div>

		</div>
	</footer>

</div><!-- #apollo-wrapper -->

<?php
// WordPress footer - loads all enqueued scripts
wp_footer();
?>

</body>
</html>
