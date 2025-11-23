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

// P0-1: Improved activation hook with idempotency checks
register_activation_hook(__FILE__, function() {
    // Check if already activated recently (prevent double runs)
    $activation_key = 'apollo_social_activation_' . APOLLO_SOCIAL_VERSION;
    $last_activation = get_option($activation_key, false);
    
    // If activated in last 5 minutes, skip (might be double-click or refresh)
    if ($last_activation && (time() - $last_activation) < 300) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Apollo Social: Activation skipped (already activated recently)');
        }
        return;
    }
    
    // Mark activation start
    update_option($activation_key, time());
    
    try {
        // Create database tables (idempotent - uses dbDelta)
        $schema = new \Apollo\Infrastructure\Database\Schema();
        $schema->install();
        $schema->updateGroupsTable();
        
        // Create default groups (idempotent - checks existence)
        $default_groups = new \Apollo\Domain\Groups\DefaultGroups();
        $default_groups->createDefaults();
        
        // Register routes
        $routes = new \Apollo\Infrastructure\Http\Routes();
        $routes->register();
        
        // Load user-pages CPT and rewrite
        $user_pages_cpt = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/class-user-page-cpt.php';
        if (file_exists($user_pages_cpt)) {
            require_once $user_pages_cpt;
            if (class_exists('Apollo_User_Page_CPT')) {
                Apollo_User_Page_CPT::register();
            }
        }
        
        $user_pages_rewrite = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/class-user-page-rewrite.php';
        if (file_exists($user_pages_rewrite)) {
            require_once $user_pages_rewrite;
            if (class_exists('Apollo_User_Page_Rewrite')) {
                Apollo_User_Page_Rewrite::add_rewrite();
            }
        }
        
        // Flush rewrite rules (only once per version)
        flush_rewrite_rules(false); // false = soft flush (faster)
        
        // Mark activation complete
        update_option('apollo_social_activated_version', APOLLO_SOCIAL_VERSION);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Apollo Social: Activation completed successfully (v' . APOLLO_SOCIAL_VERSION . ')');
        }
    } catch (\Exception $e) {
        // Log error but don't break activation
        error_log('❌ Apollo Social: Activation error - ' . $e->getMessage());
        // Still mark as activated to prevent retry loops
        update_option($activation_key, time());
    }
});

// Clean up on deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});