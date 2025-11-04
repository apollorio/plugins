<?php
/**
 * Plugin Name: Apollo::Rio - PWA Page Builders
 * Description: Three PWA-aware page builders with strict device detection
 * Version: 1.0.0
 * Author: Apollo Rio Team
 * License: GPL v3
 * Text Domain: apollo-rio
 */

if (!defined('ABSPATH')) exit;

// Define constants
define('APOLLO_VERSION', '1.0.0');
define('APOLLO_PATH', plugin_dir_path(__FILE__));
define('APOLLO_URL', plugin_dir_url(__FILE__));

// Load main class
require_once APOLLO_PATH . 'includes/class-pwa-page-builders.php';

// Load helper functions
require_once APOLLO_PATH . 'includes/template-functions.php';

// Load admin settings
if (is_admin()) {
    require_once APOLLO_PATH . 'includes/admin-settings.php';
}

// Activation hook
register_activation_hook(__FILE__, 'apollo_activate');
function apollo_activate() {
    // Set default options
    add_option('apollo_android_app_url', 'https://play.google.com/store/apps/details?id=br.rio.apollo');
    add_option('apollo_pwa_install_page_id', '');
    
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'apollo_deactivate');
function apollo_deactivate() {
    flush_rewrite_rules();
}