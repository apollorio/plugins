<?php
namespace Apollo\Hooks;

use Apollo\Infrastructure\Dashboard\WidgetRegistry;

/**
 * Auto-create user page on registration
 * Creates customizable dashboard page with default widgets
 */
class UserPageAutoCreate
{
    private $widget_registry;

    public function __construct()
    {
        $this->widget_registry = new WidgetRegistry();
    }

    public function register()
    {
        // Hook into user registration
        add_action('user_register', [$this, 'createUserPage'], 10, 1);
        
        // Also hook into registration completion
        add_action('apollo_user_registered', [$this, 'createUserPage'], 10, 1);
    }

    /**
     * P0-8: Create user page with default widgets
     * Called on user_register hook
     */
    public function createUserPage($user_id)
    {
        $user_id = absint($user_id);
        
        if (!$user_id) {
            return;
        }

        // P0-8: Check if page already exists (idempotent)
        $existing_page = apollo_get_user_page($user_id);
        if ($existing_page) {
            // Page exists, but ensure it has widgets if missing
            $widgets = get_post_meta($existing_page->ID, '_apollo_widgets', true);
            if (empty($widgets)) {
                $this->initializeDefaultWidgets($user_id, $existing_page->ID);
            }
            return;
        }

        // P0-8: Create user page using repository
        if (!function_exists('apollo_get_or_create_user_page')) {
            // Fallback: create directly if helper not available
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return;
            }

            $page_id = wp_insert_post([
                'post_type' => 'user_page',
                'post_title' => $user->display_name . ' - Perfil',
                'post_status' => 'publish',
                'post_author' => $user_id,
                'comment_status' => 'open', // P0-8: Enable comments for depoimentos
            ]);

            if (is_wp_error($page_id) || !$page_id) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Apollo: Failed to create user page for user ID: ' . $user_id);
                }
                return;
            }

            // Link user to page
            update_user_meta($user_id, 'apollo_user_page_id', $page_id);
            update_post_meta($page_id, '_apollo_user_id', $user_id);
        } else {
            $user_page = apollo_get_or_create_user_page($user_id);
            if (!$user_page) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Apollo: Failed to create user page for user ID: ' . $user_id);
                }
                return;
            }
            $page_id = $user_page->ID;
        }

        // P0-8: Initialize default widgets
        $this->initializeDefaultWidgets($user_id, $page_id);

        // P0-8: Set page template and Canvas mode
        update_post_meta($page_id, '_wp_page_template', 'apollo-user-dashboard');
        update_post_meta($page_id, '_apollo_canvas_page', true);
        
        // P0-8: Enable comments for depoimentos (moderation required)
        wp_update_post([
            'ID' => $page_id,
            'comment_status' => 'open',
        ]);

        // P0-8: Set comment moderation
        update_post_meta($page_id, '_apollo_comment_moderation', true);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Apollo: User page created for user ID: ' . $user_id . ' (Page ID: ' . $page_id . ')');
        }
    }

    /**
     * P0-8: Initialize default widgets for new user page
     * Includes profile header, depoimentos, bio, stats, and playlist widget
     */
    private function initializeDefaultWidgets($user_id, $page_id)
    {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return;
        }

        $default_widgets = [
            [
                'id' => 'widget-profile-header',
                'type' => 'profile-header',
                'position' => ['x' => 0, 'y' => 0],
                'size' => ['w' => 12, 'h' => 3],
                'config' => [
                    'display_name' => $user->display_name,
                    'avatar' => get_avatar_url($user_id),
                ]
            ],
            [
                'id' => 'widget-bio',
                'type' => 'bio',
                'position' => ['x' => 0, 'y' => 3],
                'size' => ['w' => 8, 'h' => 4],
                'config' => [
                    'bio' => get_user_meta($user_id, 'description', true) ?: '',
                ]
            ],
            [
                'id' => 'widget-stats',
                'type' => 'stats',
                'position' => ['x' => 8, 'y' => 3],
                'size' => ['w' => 4, 'h' => 4],
                'config' => []
            ],
            [
                'id' => 'widget-playlist',
                'type' => 'playlist',
                'position' => ['x' => 0, 'y' => 7],
                'size' => ['w' => 6, 'h' => 6],
                'config' => [
                    'title' => 'Playlist',
                    'spotify_url' => '',
                    'soundcloud_url' => '',
                ]
            ],
            [
                'id' => 'widget-depoimentos',
                'type' => 'depoimentos',
                'position' => ['x' => 6, 'y' => 7],
                'size' => ['w' => 6, 'h' => 6],
                'config' => [
                    'title' => 'Depoimentos',
                    'allow_comments' => true,
                    'max_comments' => 50,
                    'moderation' => true, // P0-8: Require moderation
                ]
            ],
        ];

        // P0-8: Save widgets with version tracking
        update_post_meta($page_id, '_apollo_widgets', $default_widgets);
        update_post_meta($page_id, '_apollo_layout_version', '2.0');
        update_post_meta($page_id, '_apollo_auto_created', current_time('mysql'));
        update_post_meta($page_id, '_apollo_user_id', $user_id);
        
        // P0-8: Set page visibility based on user verification
        $is_verified = get_user_meta($user_id, 'apollo_verified', true);
        update_post_meta($page_id, '_apollo_page_visibility', $is_verified ? 'public' : 'members_only');
    }
}

