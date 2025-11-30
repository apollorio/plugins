<?php
/**
 * Apollo Core Integration Bridge
 *
 * Provides centralized hooks, utilities, and integration points for all Apollo plugins.
 * This file ensures Core acts as the "brain and heart" of the Apollo ecosystem.
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// SHARED UTILITY FUNCTIONS
// ============================================================================

if (!function_exists('apollo_log_missing_file')) {
    /**
     * Log missing file errors in debug mode
     *
     * @param string $path File path that was not found
     * @return void
     */
    function apollo_log_missing_file(string $path): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Apollo: Missing file: ' . $path);
        }
    }
}

if (!function_exists('apollo_is_debug_mode')) {
    /**
     * Check if Apollo debug mode is enabled
     *
     * @return bool
     */
    function apollo_is_debug_mode(): bool {
        return defined('APOLLO_DEBUG') && APOLLO_DEBUG;
    }
}

if (!function_exists('apollo_get_asset_url')) {
    /**
     * Get Apollo asset URL (unified assets source)
     *
     * @param string $asset Asset filename
     * @return string Full URL to asset
     */
    function apollo_get_asset_url(string $asset): string {
        return 'https://assets.apollo.rio.br/' . ltrim($asset, '/');
    }
}

if (!function_exists('apollo_get_plugin_data')) {
    /**
     * Get data about an Apollo plugin
     *
     * @param string $plugin Plugin identifier (core, social, events, rio)
     * @return array Plugin data or empty array
     */
    function apollo_get_plugin_data(string $plugin): array {
        $plugins = [
            'core' => [
                'slug' => 'apollo-core',
                'file' => 'apollo-core/apollo-core.php',
                'version_constant' => 'APOLLO_CORE_VERSION',
            ],
            'social' => [
                'slug' => 'apollo-social',
                'file' => 'apollo-social/apollo-social.php',
                'version_constant' => 'APOLLO_SOCIAL_VERSION',
            ],
            'events' => [
                'slug' => 'apollo-events-manager',
                'file' => 'apollo-events-manager/apollo-events-manager.php',
                'version_constant' => 'APOLLO_WPEM_VERSION',
            ],
            'rio' => [
                'slug' => 'apollo-rio',
                'file' => 'apollo-rio/apollo-rio.php',
                'version_constant' => 'APOLLO_RIO_VERSION',
            ],
        ];
        
        return $plugins[$plugin] ?? [];
    }
}

if (!function_exists('apollo_is_plugin_active')) {
    /**
     * Check if a specific Apollo plugin is active
     *
     * @param string $plugin Plugin identifier (core, social, events, rio)
     * @return bool
     */
    function apollo_is_plugin_active(string $plugin): bool {
        $data = apollo_get_plugin_data($plugin);
        
        if (empty($data)) {
            return false;
        }
        
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        return is_plugin_active($data['file']);
    }
}

// ============================================================================
// INTEGRATION HOOKS - Fire when Core loads for other plugins to hook into
// ============================================================================

/**
 * Fire Core ready action
 * Other plugins can hook into 'apollo_core_ready' to register their extensions
 */
add_action('plugins_loaded', function() {
    /**
     * Action: apollo_core_ready
     * Fired when Apollo Core is fully loaded and ready
     * Use this hook to register integrations with Core
     */
    do_action('apollo_core_ready');
}, 15);

/**
 * Fire REST API ready action
 */
add_action('rest_api_init', function() {
    /**
     * Action: apollo_rest_api_ready
     * Fired when REST API is ready for Apollo routes
     */
    do_action('apollo_rest_api_ready');
}, 5);

// ============================================================================
// CPT REGISTRATION HOOKS
// ============================================================================

if (!function_exists('apollo_register_cpt')) {
    /**
     * Register a CPT through Core (for consistency)
     *
     * @param string $post_type Post type slug
     * @param array $args Post type arguments
     * @return WP_Post_Type|WP_Error
     */
    function apollo_register_cpt(string $post_type, array $args) {
        /**
         * Filter: apollo_cpt_args_{$post_type}
         * Allows modifying CPT arguments before registration
         */
        $args = apply_filters("apollo_cpt_args_{$post_type}", $args);
        
        $result = register_post_type($post_type, $args);
        
        /**
         * Action: apollo_cpt_registered
         * Fired after a CPT is registered through Core
         */
        do_action('apollo_cpt_registered', $post_type, $args);
        
        return $result;
    }
}

// ============================================================================
// TEMPLATE HOOKS
// ============================================================================

if (!function_exists('apollo_get_template')) {
    /**
     * Get template from Apollo plugins (respects hierarchy)
     *
     * @param string $template_name Template filename
     * @param string $plugin Plugin to look in first (core, social, events, rio)
     * @return string|false Template path or false if not found
     */
    function apollo_get_template(string $template_name, string $plugin = 'core') {
        $paths = [];
        
        // Theme can override templates
        $paths[] = get_stylesheet_directory() . '/apollo/' . $template_name;
        $paths[] = get_template_directory() . '/apollo/' . $template_name;
        
        // Plugin-specific templates
        $plugin_dirs = [
            'core' => defined('APOLLO_CORE_PLUGIN_DIR') ? APOLLO_CORE_PLUGIN_DIR : '',
            'social' => defined('APOLLO_SOCIAL_PLUGIN_DIR') ? APOLLO_SOCIAL_PLUGIN_DIR : '',
            'events' => defined('APOLLO_WPEM_PATH') ? APOLLO_WPEM_PATH : '',
            'rio' => defined('APOLLO_RIO_PATH') ? APOLLO_RIO_PATH : '',
        ];
        
        // Prioritize specified plugin
        if (!empty($plugin_dirs[$plugin])) {
            $paths[] = $plugin_dirs[$plugin] . 'templates/' . $template_name;
        }
        
        // Then check all other plugins
        foreach ($plugin_dirs as $key => $dir) {
            if ($key !== $plugin && !empty($dir)) {
                $paths[] = $dir . 'templates/' . $template_name;
            }
        }
        
        /**
         * Filter: apollo_template_paths
         * Allows adding custom template paths
         */
        $paths = apply_filters('apollo_template_paths', $paths, $template_name, $plugin);
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return false;
    }
}

// ============================================================================
// CANVAS MODE INTEGRATION
// ============================================================================

if (!function_exists('apollo_is_canvas_mode')) {
    /**
     * Check if current page is in Canvas mode
     *
     * @return bool
     */
    function apollo_is_canvas_mode(): bool {
        return (bool) apply_filters('apollo_is_canvas_mode', false);
    }
}

if (!function_exists('apollo_enqueue_canvas_assets')) {
    /**
     * Enqueue Canvas mode assets (uni.css, etc)
     *
     * @return void
     */
    function apollo_enqueue_canvas_assets(): void {
        wp_enqueue_style(
            'apollo-uni-css',
            apollo_get_asset_url('uni.css'),
            [],
            defined('APOLLO_CORE_VERSION') ? APOLLO_CORE_VERSION : '1.0.0'
        );
        
        /**
         * Action: apollo_canvas_assets_enqueued
         * Fired after Canvas assets are enqueued
         */
        do_action('apollo_canvas_assets_enqueued');
    }
}

// ============================================================================
// USER/MEMBERSHIP INTEGRATION
// ============================================================================

if (!function_exists('apollo_get_user_membership_type')) {
    /**
     * Get user's membership type
     *
     * @param int|null $user_id User ID (default: current user)
     * @return string Membership type or empty string
     */
    function apollo_get_user_membership_type(?int $user_id = null): string {
        $user_id = $user_id ?? get_current_user_id();
        
        if (!$user_id) {
            return '';
        }
        
        $type = get_user_meta($user_id, 'apollo_membership_type', true);
        
        /**
         * Filter: apollo_user_membership_type
         * Allows modifying the membership type
         */
        return (string) apply_filters('apollo_user_membership_type', $type, $user_id);
    }
}

if (!function_exists('apollo_user_can_sign_documents')) {
    /**
     * Check if user can sign documents (has CPF)
     *
     * @param int|null $user_id User ID (default: current user)
     * @return bool
     */
    function apollo_user_can_sign_documents(?int $user_id = null): bool {
        $user_id = $user_id ?? get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        $can_sign = get_user_meta($user_id, 'apollo_can_sign_documents', true);
        
        /**
         * Filter: apollo_user_can_sign_documents
         * Allows modifying the document signing permission
         */
        return (bool) apply_filters('apollo_user_can_sign_documents', $can_sign, $user_id);
    }
}

// ============================================================================
// NOTIFICATION SYSTEM
// ============================================================================

if (!function_exists('apollo_add_notification')) {
    /**
     * Add a notification to the Apollo notification system
     *
     * @param int $user_id Target user ID
     * @param string $type Notification type
     * @param string $message Notification message
     * @param array $data Additional data
     * @return int|false Notification ID or false on failure
     */
    function apollo_add_notification(int $user_id, string $type, string $message, array $data = []) {
        /**
         * Filter: apollo_notification_before_create
         * Allows modifying notification before creation
         */
        $notification = apply_filters('apollo_notification_before_create', [
            'user_id' => $user_id,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'created_at' => current_time('mysql'),
            'read' => false,
        ]);
        
        // Store notification (implementation depends on storage method)
        $notifications = get_user_meta($user_id, 'apollo_notifications', true);
        if (!is_array($notifications)) {
            $notifications = [];
        }
        
        $notification['id'] = count($notifications) + 1;
        $notifications[] = $notification;
        
        update_user_meta($user_id, 'apollo_notifications', $notifications);
        
        /**
         * Action: apollo_notification_created
         * Fired after a notification is created
         */
        do_action('apollo_notification_created', $notification, $user_id);
        
        return $notification['id'];
    }
}

// ============================================================================
// ECOSYSTEM STATUS
// ============================================================================

if (!function_exists('apollo_get_ecosystem_status')) {
    /**
     * Get the status of all Apollo plugins
     *
     * @return array Status of each plugin
     */
    function apollo_get_ecosystem_status(): array {
        return [
            'core' => [
                'active' => true, // Core is running if this code executes
                'version' => defined('APOLLO_CORE_VERSION') ? APOLLO_CORE_VERSION : 'unknown',
            ],
            'social' => [
                'active' => apollo_is_plugin_active('social'),
                'version' => defined('APOLLO_SOCIAL_VERSION') ? APOLLO_SOCIAL_VERSION : 'unknown',
            ],
            'events' => [
                'active' => apollo_is_plugin_active('events'),
                'version' => defined('APOLLO_WPEM_VERSION') ? APOLLO_WPEM_VERSION : 'unknown',
            ],
            'rio' => [
                'active' => apollo_is_plugin_active('rio'),
                'version' => defined('APOLLO_RIO_VERSION') ? APOLLO_RIO_VERSION : 'unknown',
            ],
        ];
    }
}

// Log that integration bridge is loaded
if (apollo_is_debug_mode()) {
    error_log('Apollo Core Integration Bridge loaded');
}

