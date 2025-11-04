<?php
/**
 * Apollo PWA Page Builders - Main Class
 * 
 * Template registration inspired by Blank Slate plugin pattern:
 * - Global API for template registration (apollo_rio_add_template)
 * - Robust template loading with theme override support
 * - WordPress version compatibility (accepts only 2 args in filters)
 */

if (!defined('ABSPATH')) exit;

/**
 * Global template registry (Blank Slate pattern)
 * Stores templates in a filterable array
 */
$GLOBALS['apollo_rio_templates'] = [];

/**
 * Register a PWA page template
 * 
 * Inspired by Blank Slate plugin pattern for robust template registration.
 * Use this during plugins_loaded to register templates.
 * 
 * @param string $file Template filename (e.g., 'pagx_site.php')
 * @param string $label Display label (e.g., 'Site::rio')
 */
function apollo_rio_add_template($file, $label) {
    if (!isset($GLOBALS['apollo_rio_templates'])) {
        $GLOBALS['apollo_rio_templates'] = [];
    }
    $GLOBALS['apollo_rio_templates'][$file] = apply_filters('apollo_rio_template_label', $label, $file);
}

/**
 * Get all registered PWA templates
 * 
 * @return array Array of registered templates [filename => label]
 */
function apollo_rio_get_templates() {
    return apply_filters('apollo_rio_templates', isset($GLOBALS['apollo_rio_templates']) ? $GLOBALS['apollo_rio_templates'] : []);
}

class Apollo_PWA_Page_Builders {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // WordPress compatibility: accepted_args = 2 (minimum always passed)
        // Blank Slate pattern: accept only 2 args to avoid errors in older WP versions
        add_filter('theme_page_templates', [$this, 'register_templates'], 10, 2);
        add_filter('template_include', [$this, 'load_template'], 999);
        add_filter('body_class', [$this, 'add_body_classes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_pwa_assets']);
    }
    
    /**
     * Register PWA page templates in WordPress template selector
     * 
     * Blank Slate pattern: Accept only 2 args for maximum compatibility.
     * WordPress may pass 2, 3, or 4 args depending on context/version.
     * 
     * @param array $post_templates Existing templates array
     * @param WP_Theme $wp_theme Current theme object (may be null in some contexts)
     * @return array Merged templates array
     */
    public function register_templates($post_templates, $wp_theme = null) {
        // Merge Apollo templates with existing templates (Blank Slate pattern)
        $apollo_templates = apollo_rio_get_templates();
        return array_merge($post_templates, $apollo_templates);
    }
    
    /**
     * Load the correct template file when a PWA template is selected
     * 
     * Blank Slate pattern with robust fallbacks:
     * 1. Check if assigned template is absolute path (theme override)
     * 2. Try locate_template() to allow theme to override plugin template
     * 3. Fallback to plugin templates directory
     * 
     * @param string $template Current template path
     * @return string Template path to use
     */
    public function load_template($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }
        
        // Get assigned template from post meta (Blank Slate pattern)
        $assigned_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if (empty($assigned_template)) {
            return $template;
        }
        
        // Get registered Apollo templates
        $apollo_templates = apollo_rio_get_templates();
        
        // Check if assigned template is one of our registered templates
        if (!array_key_exists($assigned_template, $apollo_templates)) {
            return $template;
        }
        
        // Step 1: Check if $assigned_template is an absolute path (theme override)
        if (file_exists($assigned_template) && is_file($assigned_template)) {
            return $assigned_template;
        }
        
        // Step 2: Try locate_template() to allow theme to override plugin template
        // This allows themes to place templates in /apollo-rio/ folder
        $theme_template = locate_template('apollo-rio/' . $assigned_template);
        if ($theme_template) {
            return $theme_template;
        }
        
        // Step 3: Fallback to plugin templates directory (Blank Slate pattern)
        $plugin_template = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $assigned_template;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        // If none found, return original template
        return $template;
    }
    
    public function add_body_classes($classes) {
        global $post;
        
        if (!$post) {
            return $classes;
        }
        
        $template = get_post_meta($post->ID, '_wp_page_template', true);
        $apollo_templates = apollo_rio_get_templates();
        
        if (array_key_exists($template, $apollo_templates)) {
            $classes[] = 'apollo-pwa-template';
            $classes[] = str_replace('.php', '', $template);
            
            if (wp_is_mobile()) {
                $classes[] = 'is-mobile';
            } else {
                $classes[] = 'is-desktop';
            }
        }
        
        return $classes;
    }
    
    public function enqueue_pwa_assets() {
        global $post;
        
        if (!$post) {
            return;
        }
        
        $template = get_post_meta($post->ID, '_wp_page_template', true);
        $apollo_templates = apollo_rio_get_templates();
        
        if (array_key_exists($template, $apollo_templates)) {
            // Global Apollo CSS - required for all PWA templates
            wp_enqueue_style(
                'apollo-uni-css',
                'https://assets.apollo.rio.br/uni.css',
                [],
                null // No version for external CDN
            );
            
            wp_enqueue_script(
                'apollo-pwa-detect',
                plugins_url('assets/js/pwa-detect.js', dirname(__FILE__)),
                [],
                '1.0.0',
                true
            );
            
            wp_enqueue_style(
                'apollo-pwa-templates',
                plugins_url('assets/css/pwa-templates.css', dirname(__FILE__)),
                ['apollo-uni-css'], // Depend on global CSS
                '1.0.0'
            );
            
            wp_localize_script('apollo-pwa-detect', 'apolloPWA', [
                'template' => $template,
                'isMobile' => wp_is_mobile(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apollo_pwa_nonce'),
                'androidAppUrl' => get_option('apollo_android_app_url', '#'),
            ]);
        }
    }
}

/**
 * Register Apollo PWA templates during plugin bootstrap
 * 
 * Blank Slate pattern: Register templates early so they appear in template selector.
 * This runs during plugins_loaded to ensure templates are available before admin loads.
 */
function apollo_rio_register_templates() {
    apollo_rio_add_template('pagx_site.php', __('Site::rio', 'apollo-rio'));
    apollo_rio_add_template('pagx_app.php', __('App::rio', 'apollo-rio'));
    apollo_rio_add_template('pagx_appclean.php', __('App::rio clean', 'apollo-rio'));
}
add_action('plugins_loaded', 'apollo_rio_register_templates', 5); // Priority 5: early registration

/**
 * Initialize Apollo PWA Page Builders
 */
function apollo_pwa_page_builders() {
    return Apollo_PWA_Page_Builders::get_instance();
}
add_action('plugins_loaded', 'apollo_pwa_page_builders', 10); // Priority 10: after template registration