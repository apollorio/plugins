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
    $plugin = new \Apollo\Plugin();
    $plugin->bootstrap();
});

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function() {
    // Register routes first
    $routes = new \Apollo\Infrastructure\Http\Routes();
    $routes->register();
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Clean up on deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});