<?php
/**
 * ============================================
 * FILE: templates/partials/footer.php
 * FULL FOOTER with widgets
 * Used by: pagx_site, pagx_app
 * ============================================
 */

if (!defined('ABSPATH')) exit;
?>
    </main><!-- #apollo-content -->
    
    <!-- Full Footer -->
    <footer id="apollo-footer" class="apollo-footer apollo-footer-full">
        <div class="apollo-footer-container">
            
            <?php if (is_active_sidebar('apollo_footer_1') || is_active_sidebar('apollo_footer_2') || is_active_sidebar('apollo_footer_3')) : ?>
                <div class="apollo-footer-widgets">
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
            
            <div class="apollo-footer-info">
                <p class="apollo-copyright">
                    &copy; <?php echo date('Y'); ?> 
                    <a href="<?php echo esc_url(home_url('/')); ?>">Apollo::Rio</a>
                    - <?php _e('All rights reserved', 'apollo-rio'); ?>
                </p>
                
                <?php
                if (has_nav_menu('apollo_footer')) {
                    wp_nav_menu([
                        'theme_location' => 'apollo_footer',
                        'menu_id' => 'apollo-footer-menu',
                        'menu_class' => 'apollo-footer-menu',
                        'container' => false,
                        'depth' => 1,
                    ]);
                }
                ?>
            </div>
            
        </div>
    </footer>
    
</div><!-- #apollo-wrapper -->

<?php wp_footer(); ?>

</body>
</html>
