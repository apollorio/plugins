<?php
/**
 * ============================================
 * FILE: templates/partials/footer-minimal.php
 * MINIMAL FOOTER without widgets
 * Used by: pagx_appclean
 * ============================================
 */

if (!defined('ABSPATH')) exit;
?>
    </main><!-- #apollo-content -->
    
    <!-- Minimal Footer -->
    <footer id="apollo-footer" class="apollo-footer apollo-footer-minimal">
        <div class="apollo-footer-container">
            
            <div class="apollo-footer-info-minimal">
                <p class="apollo-copyright-minimal">
                    &copy; <?php echo date('Y'); ?> Apollo::Rio
                </p>
            </div>
            
        </div>
    </footer>
    
</div><!-- #apollo-wrapper -->

<?php wp_footer(); ?>

</body>
</html>