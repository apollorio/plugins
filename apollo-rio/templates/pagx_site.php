<?php
/**
 * ============================================
 * FILE: templates/pagx_site.php
 * PAGE BUILDER 1: Site::rio
 * Full header/footer, all devices, no PWA check
 * ============================================
 */

if (!defined('ABSPATH')) exit;

// Get full header
apollo_get_header_for_template('pagx_site');
?>

<div id="apollo-main" class="apollo-content-wrapper pagx-site">
    <div class="apollo-container">
        <?php
        while (have_posts()) : the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('apollo-article'); ?>>
                
                <?php if (get_the_title()) : ?>
                    <header class="apollo-entry-header">
                        <h1 class="apollo-entry-title"><?php the_title(); ?></h1>
                    </header>
                <?php endif; ?>
                
                <div class="apollo-entry-content">
                    <?php the_content(); ?>
                </div>
                
                <?php
                // Comments if enabled
                if (comments_open() || get_comments_number()) {
                    comments_template();
                }
                ?>
                
            </article>
            <?php
        endwhile;
        ?>
    </div>
</div>

<?php
// Get full footer
apollo_get_footer_for_template('pagx_site');
?>