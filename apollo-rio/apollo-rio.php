<?php
// phpcs:ignoreFile
declare(strict_types=1);
/**
 * Plugin Name: Apollo::Rio - PWA Page Builders
 * Plugin URI: https://apollo.rio.br/plugins/apollo-rio
 * Description: Three PWA-aware page builders with strict device detection
 * Version: 1.0.0
 * Author: Apollo Rio Team
 * Author URI: https://apollo.rio.br
 * License: GPL v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: apollo-rio
 * Domain Path: /languages
 * Requires at least: 6.4
 * Tested up to: 6.7
 * Requires PHP: 8.1
 * Requires Plugins: apollo-core
 *
 * @package Apollo_Rio
 */

if (! defined('ABSPATH')) {
    exit;
}

// Define constants - using plugin-specific prefixes to avoid collisions
if (! defined('APOLLO_RIO_VERSION')) {
    define('APOLLO_RIO_VERSION', '1.0.0');
}
if (! defined('APOLLO_RIO_PATH')) {
    define('APOLLO_RIO_PATH', plugin_dir_path(__FILE__));
}
if (! defined('APOLLO_RIO_URL')) {
    define('APOLLO_RIO_URL', plugin_dir_url(__FILE__));
}

// Legacy aliases for backward compatibility (deprecated - use APOLLO_RIO_* instead)
if (! defined('APOLLO_PATH')) {
    define('APOLLO_PATH', APOLLO_RIO_PATH);
}
if (! defined('APOLLO_URL')) {
    define('APOLLO_URL', APOLLO_RIO_URL);
}

// Load main class
$main_class_file = APOLLO_RIO_PATH . 'includes/class-pwa-page-builders.php';
if (file_exists($main_class_file)) {
    require_once $main_class_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing file: ' . esc_html($main_class_file));
}

// Load helper functions
$template_functions_file = APOLLO_RIO_PATH . 'includes/template-functions.php';
if (file_exists($template_functions_file)) {
    require_once $template_functions_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing file: ' . esc_html($template_functions_file));
}

// Load admin settings (always - contains helper functions like apollo_is_seo_enabled() used on frontend)
$admin_settings_file = APOLLO_RIO_PATH . 'includes/admin-settings.php';
if (file_exists($admin_settings_file)) {
    require_once $admin_settings_file;
    if (is_admin()) {
        add_action('admin_notices', 'apollo_rio_admin_notices');
    }
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing file: ' . esc_html($admin_settings_file));
}

// Load SEO handler (outputs meta tags for Apollo content types)
$seo_handler_file = APOLLO_RIO_PATH . 'includes/class-apollo-seo-handler.php';
if (file_exists($seo_handler_file)) {
    require_once $seo_handler_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing file: ' . esc_html($seo_handler_file));
}

// Load Core Integration (Phase 2 Architecture - hooks into Apollo Core for PWA optimization)
$core_integration_file = APOLLO_RIO_PATH . 'includes/class-apollo-rio-core-integration.php';
if (file_exists($core_integration_file)) {
    require_once $core_integration_file;
}

/**
 * Display admin notices
 */
if ( ! function_exists( 'apollo_rio_admin_notices' ) ) {
    function apollo_rio_admin_notices()
    {
        // Check Apollo Core dependency
        if (! defined('APOLLO_CORE_BOOTSTRAPPED') && ! class_exists('Apollo_Core')) {
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
        $permalink_checked   = get_transient($permalink_check_key);

        if (! $permalink_checked && get_option('permalink_structure') === '') {
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
}

// Activation hook
register_activation_hook(__FILE__, 'apollo_rio_activate');
if ( ! function_exists( 'apollo_rio_activate' ) ) {
    function apollo_rio_activate()
    {
        // Set default options
        add_option('apollo_android_app_url', esc_url_raw('https://play.google.com/store/apps/details?id=br.rio.apollo'));

        // Set default SEO options (all enabled by default)
        $seo_content_types = [ 'user', 'comunidade', 'nucleo', 'dj', 'local', 'event', 'classifieds' ];
        foreach ($seo_content_types as $type) {
            add_option('apollo_seo_' . $type, true);
        }

        flush_rewrite_rules();

        // Set transient to show permalink notice after activation
        set_transient('apollo_rio_flush_rewrite_rules', true, 30);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'apollo_rio_deactivate');
if ( ! function_exists( 'apollo_rio_deactivate' ) ) {
    function apollo_rio_deactivate()
    {
        flush_rewrite_rules();
    }
}

// --- Apollo SchemaOrchestrator Integration ---
add_action('apollo_register_schema_modules', function($orchestrator) {
    // Autoload RioSchemaModule
    $schema_file = APOLLO_RIO_PATH . 'src/Schema/RioSchemaModule.php';
    if (file_exists($schema_file)) {
        require_once $schema_file;
    }

    if (class_exists('Apollo_Rio\Schema\RioSchemaModule')) {
        $orchestrator->registerModule(new Apollo_Rio\Schema\RioSchemaModule());
    }
}, 40); // Priority 40 = after core(10), social(20), events(30)
