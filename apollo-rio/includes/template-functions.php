<?php
/**
 * Apollo Template Helper Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if request is from PWA
 */
function apollo_is_pwa() {
    $is_pwa = false;
    
    if (isset($_COOKIE['apollo_display_mode']) && $_COOKIE['apollo_display_mode'] === 'standalone') {
        $is_pwa = true;
    }
    
    if (isset($_SERVER['HTTP_X_APOLLO_PWA']) && $_SERVER['HTTP_X_APOLLO_PWA'] === 'true') {
        $is_pwa = true;
    }
    
    return apply_filters('apollo_is_pwa', $is_pwa);
}

/**
 * Check if mobile device
 */
function apollo_is_mobile() {
    return wp_is_mobile();
}

/**
 * Determine if page should show content or PWA redirect
 */
function apollo_should_show_content($template_type) {
    $is_mobile = apollo_is_mobile();
    $is_pwa = apollo_is_pwa();
    
    // Site::rio - Always show content
    if ($template_type === 'pagx_site') {
        return true;
    }
    
    // App::rio and App::rio clean
    if (in_array($template_type, ['pagx_app', 'pagx_appclean'])) {
        if (!$is_mobile) {
            return true; // Desktop always shows
        }
        
        if ($is_mobile && $is_pwa) {
            return true; // Mobile PWA shows
        }
        
        return false; // Mobile browser shows install page
    }
    
    return true;
}

/**
 * Get header for template type
 */
function apollo_get_header_for_template($template_type) {
    $header_file = 'header.php';
    
    if ($template_type === 'pagx_appclean') {
        $header_file = 'header-minimal.php';
    }
    
    apollo_get_header($header_file);
}

/**
 * Get footer for template type
 */
function apollo_get_footer_for_template($template_type) {
    $footer_file = 'footer.php';
    
    if ($template_type === 'pagx_appclean') {
        $footer_file = 'footer-minimal.php';
    }
    
    apollo_get_footer($footer_file);
}

/**
 * Load custom header
 */
function apollo_get_header($name = null) {
    do_action('apollo_before_header', $name);
    
    $template = 'partials/header';
    if ($name) {
        $name = str_replace('.php', '', $name);
        $template = 'partials/' . $name;
    }
    $template .= '.php';
    
    $file = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template;
    
    if (file_exists($file)) {
        load_template($file, false);
    }
    
    do_action('apollo_after_header', $name);
}

/**
 * Load custom footer
 */
function apollo_get_footer($name = null) {
    do_action('apollo_before_footer', $name);
    
    $template = 'partials/footer';
    if ($name) {
        $name = str_replace('.php', '', $name);
        $template = 'partials/' . $name;
    }
    $template .= '.php';
    
    $file = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template;
    
    if (file_exists($file)) {
        load_template($file, false);
    }
    
    do_action('apollo_after_footer', $name);
}

/**
 * Render PWA install page
 */
function apollo_render_pwa_install_page() {
    // (O HTML completo da página de instalação PWA que está no documento original)
    // Copie a função apollo_render_pwa_install_page() completa do documento que você colou
}