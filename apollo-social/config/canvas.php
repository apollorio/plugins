<?php
/**
 * Canvas configuration
 *
 * Settings for Canvas Mode rendering.
 */

return [
    'force_canvas_on_plugin_routes' => true,
    'allow_theme_assets' => false,
    'allow_admin_bar' => true,
    
    // Canvas layout settings
    'canvas_class' => 'apollo-canvas',
    'main_class' => 'apollo-main',
    
    // Asset loading
    'enqueue_canvas_css' => true,
    'enqueue_canvas_js' => true,
    'block_theme_css' => true,
    'block_theme_js' => true,
    
    // Security settings
    'security' => [
        'verify_nonce' => true,
        'sanitize_output' => true,
        'rate_limit' => false
    ]
];