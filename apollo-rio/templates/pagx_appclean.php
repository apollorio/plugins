<?php
/**
 * ============================================
 * FILE: templates/pagx_appclean.php
 * PAGE BUILDER 3: App::rio clean
 * Minimal header/footer (NO NAV BAR)
 * Desktop: Show content
 * Mobile + PWA: Show content
 * Mobile + Browser: Show PWA install page
 * ============================================
 */

if (!defined('ABSPATH')) exit;

$should_show_content = apollo_should_show_content('pagx_appclean');

// Get minimal header (no nav)
apollo_get_header_for_template('pagx_appclean');
?>

<div id="apollo-main" class="apollo-content-wrapper pagx-appclean">
    <div class="apollo-container">
        
        <?php if ($should_show_content) : ?>
            <!-- REGULAR CONTENT -->
            <?php
            while (have_posts()) : the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('apollo-article apollo-article-clean'); ?>>
                    
                    <!-- ✅ CANVAS MODE: Title removed - only content -->
                    <div class="apollo-entry-content">
                        <?php the_content(); ?>
                    </div>
                    
                </article>
                <?php
            endwhile;
            ?>
            
        <?php else : ?>
            <!-- PWA INSTALL PAGE -->
            <?php apollo_render_pwa_install_page(); ?>
            
        <?php endif; ?>
        
    </div>
</div>

<?php
// Get minimal footer (no widgets)
apollo_get_footer_for_template('pagx_appclean');
?>