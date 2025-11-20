<?php
/**
 * Plugin Name: Apollo Social Core
 * Plugin URI:  https://example.org/plugins/apollo-social-core
 * Description: Esqueleto do plugin Apollo Social Core. Contém rotas, Canvas Mode, providers e stubs para integrações.
 * Version:     0.0.1
 * Author:      Apollo
 * Text Domain: apollo-social
 * Domain Path: /languages
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('APOLLO_SOCIAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APOLLO_SOCIAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APOLLO_SOCIAL_VERSION', '0.0.1');

// Autoload classes (PSR-4)
spl_autoload_register(function ($class) {
    $prefix = 'Apollo\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    // Constructor automatically calls bootstrap()
    $plugin = new \Apollo\Plugin();
    
    // Load ShadCN/Tailwind loader (centralizado para todos os plugins Apollo)
    $shadcn_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/apollo-shadcn-loader.php';
    if (file_exists($shadcn_loader)) {
        require_once $shadcn_loader;
    }
    
    // Load user-pages module
    $user_pages_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/user-pages-loader.php';
    if (file_exists($user_pages_loader)) {
        require_once $user_pages_loader;
    }
    
    // Load Help Menu Admin
    if (is_admin()) {
        $help_menu = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Admin/HelpMenuAdmin.php';
        if (file_exists($help_menu)) {
            require_once $help_menu;
        }
    }
}, 5);

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function() {
    // ✅ Verificar se rewrite rules já foram flushadas recentemente (últimos 5 minutos)
    $last_flush = get_transient('apollo_social_rewrite_rules_last_flush');
    if ($last_flush && (time() - $last_flush) < 300) {
        // Já foi flushado recentemente, pular
        error_log('✅ Apollo Social: Rewrite rules já foram flushadas recentemente, pulando...');
        return;
    }
    
    // Create database tables
    $schema = new \Apollo\Infrastructure\Database\Schema();
    $schema->install();
    $schema->updateGroupsTable();
    
    // Create default groups (COMUNIDADES and PROJECT TEAM)
    $default_groups = new \Apollo\Domain\Groups\DefaultGroups();
    $default_groups->createDefaults();
    
    // Register routes first
    $routes = new \Apollo\Infrastructure\Http\Routes();
    $routes->register();
    
    // Load user-pages CPT and rewrite
    $user_pages_cpt = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/class-user-page-cpt.php';
    if (file_exists($user_pages_cpt)) {
        require_once $user_pages_cpt;
        Apollo_User_Page_CPT::register();
    }
    
    $user_pages_rewrite = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/class-user-page-rewrite.php';
    if (file_exists($user_pages_rewrite)) {
        require_once $user_pages_rewrite;
        Apollo_User_Page_Rewrite::add_rewrite();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Marcar timestamp do flush
    set_transient('apollo_social_rewrite_rules_last_flush', time(), 600); // 10 minutos
    error_log('✅ Apollo Social: Rewrite rules flushadas com sucesso');
});

// Clean up on deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});