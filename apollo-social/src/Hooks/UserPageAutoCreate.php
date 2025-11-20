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
     * Create user page with default widgets
     */
    public function createUserPage($user_id)
    {
        $user_id = absint($user_id);
        
        if (!$user_id) {
            return;
        }

        // Check if page already exists
        $existing_page = apollo_get_user_page($user_id);
        if ($existing_page) {
            return; // Already exists
        }

        // Create user page
        $user_page = apollo_get_or_create_user_page($user_id);
        
        if (!$user_page) {
            error_log('Apollo: Failed to create user page for user ID: ' . $user_id);
            return;
        }

        // Initialize default widgets
        $this->initializeDefaultWidgets($user_id, $user_page->ID);

        // Set page template
        update_post_meta($user_page->ID, '_wp_page_template', 'apollo-user-dashboard');
        
        // Enable comments for depoimentos
        wp_update_post([
            'ID' => $user_page->ID,
            'comment_status' => 'open',
        ]);

        error_log('Apollo: User page created for user ID: ' . $user_id . ' (Page ID: ' . $user_page->ID . ')');
    }

    /**
     * Initialize default widgets for new user page
     */
    private function initializeDefaultWidgets($user_id, $page_id)
    {
        $user = get_user_by('ID', $user_id);
        
        $default_widgets = [
            [
                'id' => 'widget-profile-header',
                'type' => 'profile-header',
                'position' => ['x' => 0, 'y' => 0],
                'size' => ['w' => 12, 'h' => 3],
                'config' => []
            ],
            [
                'id' => 'widget-depoimentos',
                'type' => 'depoimentos',
                'position' => ['x' => 0, 'y' => 3],
                'size' => ['w' => 12, 'h' => 6],
                'config' => [
                    'title' => 'Depoimentos',
                    'allow_comments' => true,
                    'max_comments' => 50
                ]
            ],
            [
                'id' => 'widget-bio',
                'type' => 'bio',
                'position' => ['x' => 0, 'y' => 9],
                'size' => ['w' => 6, 'h' => 4],
                'config' => []
            ],
            [
                'id' => 'widget-stats',
                'type' => 'stats',
                'position' => ['x' => 6, 'y' => 9],
                'size' => ['w' => 6, 'h' => 4],
                'config' => []
            ]
        ];

        // Save widgets
        update_post_meta($page_id, '_apollo_widgets', $default_widgets);
        update_post_meta($page_id, '_apollo_layout_version', '1.0');
        update_post_meta($page_id, '_apollo_auto_created', current_time('mysql'));
    }
}

