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
$main_class_file = APOLLO_PATH . 'includes/class-pwa-page-builders.php';
if (file_exists($main_class_file)) {
    require_once $main_class_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing file: ' . esc_html($main_class_file));
}

// Load helper functions
$template_functions_file = APOLLO_PATH . 'includes/template-functions.php';
if (file_exists($template_functions_file)) {
    require_once $template_functions_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing file: ' . esc_html($template_functions_file));
}

// Load admin settings
if (is_admin()) {
    $admin_settings_file = APOLLO_PATH . 'includes/admin-settings.php';
    if (file_exists($admin_settings_file)) {
        require_once $admin_settings_file;
        add_action('admin_notices', 'apollo_rio_admin_notices');
    } elseif (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Apollo::Rio: Missing file: ' . esc_html($admin_settings_file));
    }
}

/**
 * Display admin notices
 */
function apollo_rio_admin_notices() {
    // Check Apollo Core dependency
    if (!defined('APOLLO_CORE_BOOTSTRAPPED') && !class_exists('Apollo_Core')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('Apollo::Rio', 'apollo-rio'); ?></strong>: 
                <?php esc_html_e('O plugin "Apollo Core" não está ativo. Algumas funcionalidades podem não funcionar corretamente.', 'apollo-rio'); ?>
            </p>
        </div>
        <?php
    }
    
    // Check if rewrite rules need flushing
    if (get_transient('apollo_rio_flush_rewrite_rules')) {
        delete_transient('apollo_rio_flush_rewrite_rules');
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php esc_html_e('Apollo::Rio', 'apollo-rio'); ?></strong>: 
                <?php esc_html_e('Se você encontrar erros 404 em páginas com templates PWA, vá em Configurações → Links Permanentes e clique em "Salvar alterações" para atualizar as regras de reescrita.', 'apollo-rio'); ?>
            </p>
        </div>
        <?php
    }
    
    // Check for permalink structure issues (only show once per day)
    $permalink_check_key = 'apollo_rio_permalink_check_' . date('Y-m-d');
    $permalink_checked = get_transient($permalink_check_key);
    
    if (!$permalink_checked && get_option('permalink_structure') === '') {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('Apollo::Rio', 'apollo-rio'); ?></strong>: 
                <?php esc_html_e('Permalinks estão configurados como "Simples". Para que os templates PWA funcionem corretamente, recomenda-se usar uma estrutura de permalinks personalizada. Vá em Configurações → Links Permanentes.', 'apollo-rio'); ?>
            </p>
        </div>
        <?php
        set_transient($permalink_check_key, true, DAY_IN_SECONDS);
    }
}

// Activation hook
register_activation_hook(__FILE__, 'apollo_activate');
function apollo_activate() {
    // Set default options
    add_option('apollo_android_app_url', esc_url_raw('https://play.google.com/store/apps/details?id=br.rio.apollo'));
    
    flush_rewrite_rules();
    
    // Set transient to show permalink notice after activation
    set_transient('apollo_rio_flush_rewrite_rules', true, 30);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'apollo_deactivate');
function apollo_deactivate() {
    flush_rewrite_rules();
}