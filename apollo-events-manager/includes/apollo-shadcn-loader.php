<?php
/**
 * Apollo ShadCN Components Global Loader
 * 
 * This file ensures shadcn-style components are loaded across all Apollo plugins
 * Call apollo_load_shadcn_components() from any plugin to load the CSS
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Load Apollo ShadCN Components CSS globally
 * 
 * This function can be called from any Apollo plugin to ensure
 * shadcn-style components are available
 * 
 * @param string $plugin_url Optional plugin URL if called from another plugin
 * @param string $plugin_version Optional plugin version
 */
function apollo_load_shadcn_components($plugin_url = null, $plugin_version = null) {
    // Default to apollo-events-manager if not specified
    if (!$plugin_url) {
        $plugin_url = defined('APOLLO_WPEM_URL') ? APOLLO_WPEM_URL : plugin_dir_url(__FILE__) . '../';
    }
    
    if (!$plugin_version) {
        $plugin_version = defined('APOLLO_WPEM_VERSION') ? APOLLO_WPEM_VERSION : '2.0.0';
    }
    
    // Only enqueue if not already enqueued
    if (!wp_style_is('apollo-shadcn-components', 'enqueued')) {
        wp_enqueue_style(
            'apollo-shadcn-components',
            $plugin_url . 'assets/css/apollo-shadcn-components.css',
            array('apollo-uni-css', 'remixicon'), // Depends on uni.css and RemixIcon
            $plugin_version,
            'all'
        );
    }
}

/**
 * Hook to load shadcn components on frontend
 */
add_action('wp_enqueue_scripts', function() {
    // Ensure uni.css and RemixIcon are loaded first
    if (!wp_style_is('apollo-uni-css', 'enqueued')) {
        wp_enqueue_style(
            'apollo-uni-css',
            'https://assets.apollo.rio.br/uni.css',
            array(),
            '2.0.0',
            'all'
        );
    }
    
    if (!wp_style_is('remixicon', 'enqueued')) {
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
            array('apollo-uni-css'),
            '4.7.0',
            'all'
        );
    }
    
    // Load shadcn components
    apollo_load_shadcn_components();
}, 1); // Priority 1 to load early

/**
 * Hook to load shadcn components in admin
 */
add_action('admin_enqueue_scripts', function() {
    // Ensure uni.css and RemixIcon are loaded first
    if (!wp_style_is('apollo-uni-css', 'enqueued')) {
        wp_enqueue_style(
            'apollo-uni-css',
            'https://assets.apollo.rio.br/uni.css',
            array(),
            '2.0.0',
            'all'
        );
    }
    
    if (!wp_style_is('remixicon', 'enqueued')) {
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
            array('apollo-uni-css'),
            '4.7.0',
            'all'
        );
    }
    
    // Load shadcn components
    apollo_load_shadcn_components();
}, 1); // Priority 1 to load early

