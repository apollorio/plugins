<?php
// phpcs:ignoreFile
/**
 * ============================================
 * TEMPLATE: pagx_site.php
 * PAGE BUILDER: Site::rio (BLANK CANVAS)
 * ============================================
 *
 * ULTRA PRO BLANK CANVAS TEMPLATE
 *
 * Features:
 * - Full HTML document (no theme interference)
 * - All Apollo CSS/JS auto-loaded
 * - Full header/footer with Apollo navigation
 * - Works on all devices (no PWA restriction)
 * - Theme CSS/JS filtered out
 * - Ready for shortcodes, blocks, and custom content
 *
 * Assets Loaded:
 * - Apollo CDN (uni.css, icons, dark mode)
 * - Apollo Rio PWA templates CSS
 * - PWA detection script
 * - Critical inline CSS for instant paint
 *
 * @package Apollo_Rio
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// ========================================
// FORCE LOAD ALL APOLLO ASSETS
// ========================================
if (function_exists('apollo_ensure_base_assets')) {
    apollo_ensure_base_assets();
}

// Get full header (outputs DOCTYPE, <head>, wp_head(), etc.)
apollo_get_header_for_template('pagx_site');
?>

<main id="apollo-main" class="apollo-content-wrapper pagx-site" role="main">
	<div class="apollo-container">
		<?php
        while (have_posts()) :
            the_post();
            ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('apollo-article apollo-canvas'); ?>>

				<!-- BLANK CANVAS: Pure content, no title interference -->
				<div class="apollo-entry-content apollo-prose">
					<?php
                    // Apply content filters including shortcodes
                    the_content();
                    ?>
				</div>

				<?php
                // Pagination for multi-page posts
                wp_link_pages([
                    'before'      => '<nav class="apollo-page-links"><span class="apollo-page-links-title">' . __('Páginas:', 'apollo-rio') . '</span>',
                    'after'       => '</nav>',
                    'link_before' => '<span class="apollo-page-link">',
                    'link_after'  => '</span>',
                ]);

                // Comments section if enabled
                if (comments_open() || get_comments_number()) {
                    echo '<div class="apollo-comments-wrapper">';
                    comments_template();
                    echo '</div>';
                }
            ?>

			</article>
			<?php
        endwhile;
        ?>
	</div>
</main>

<?php
// Get full footer (outputs footer content, wp_footer(), </body></html>)
apollo_get_footer_for_template('pagx_site');
?>
