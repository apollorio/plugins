<?php
/**
 * Apollo PWA Page Builders - Main Class
 */

if (!defined('ABSPATH')) exit;

class Apollo_PWA_Page_Builders {
    
    private static $instance = null;
    private $templates = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Define the 3 page builders
        $this->templates = [
            'pagx_site.php' => __('Site::rio', 'apollo-rio'),
            'pagx_app.php' => __('App::rio', 'apollo-rio'),
            'pagx_appclean.php' => __('App::rio clean', 'apollo-rio'),
        ];
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_filter('theme_page_templates', [$this, 'register_templates'], 10, 4);
        add_filter('template_include', [$this, 'load_template'], 999);
        add_filter('body_class', [$this, 'add_body_classes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_pwa_assets']);
    }
    
    public function register_templates($post_templates, $wp_theme, $post, $post_type) {
        if ('page' === $post_type) {
            $post_templates = array_merge($post_templates, $this->templates);
        }
        return $post_templates;
    }
    
    public function load_template($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }
        
        $page_template = get_page_template_slug($post->ID);
        
        if (array_key_exists($page_template, $this->templates)) {
            $plugin_template = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $page_template;
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    public function add_body_classes($classes) {
        global $post;
        
        if (!$post) {
            return $classes;
        }
        
        $template = get_page_template_slug($post->ID);
        
        if (array_key_exists($template, $this->templates)) {
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
        
        $template = get_page_template_slug($post->ID);
        
        if (array_key_exists($template, $this->templates)) {
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
                [],
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

// Initialize
function apollo_pwa_page_builders() {
    return Apollo_PWA_Page_Builders::get_instance();
}
add_action('plugins_loaded', 'apollo_pwa_page_builders');