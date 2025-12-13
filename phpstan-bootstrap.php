<?php
/**
 * PHPStan Bootstrap File
 * Define WordPress constants and helper functions for static analysis
 */

// WordPress Core Constants
define('ABSPATH', __DIR__ . '/');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
define('WPINC', 'wp-includes');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// Apollo Plugin Constants
define('APOLLO_CORE_VERSION', '1.0.0');
define('APOLLO_CORE_PATH', WP_PLUGIN_DIR . '/apollo-core/');
define('APOLLO_CORE_URL', 'http://localhost/wp-content/plugins/apollo-core/');

// BuddyBoss Constants (if needed)
if (!defined('BP_PLATFORM_VERSION')) {
    define('BP_PLATFORM_VERSION', '2.0.0');
}

// Suppress errors during static analysis
error_reporting(0);

// Mock WordPress global functions that might not be available
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

// Mock global $wpdb
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new class {
        public $prefix = 'wp_';
        public $posts = 'wp_posts';
        public $postmeta = 'wp_postmeta';
        public $users = 'wp_users';
        public $usermeta = 'wp_usermeta';
        
        public function prepare($query, ...$args) {
            return vsprintf(str_replace('%s', "'%s'", $query), $args);
        }
        
        public function get_results($query) {
            return [];
        }
        
        public function get_var($query) {
            return null;
        }
        
        public function get_row($query) {
            return null;
        }
        
        public function insert($table, $data) {
            return 1;
        }
        
        public function update($table, $data, $where) {
            return 1;
        }
        
        public function delete($table, $where) {
            return 1;
        }
    };
}