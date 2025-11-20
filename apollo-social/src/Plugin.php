<?php
namespace Apollo;

use Apollo\Modules\Core\CoreServiceProvider;
use Apollo\Infrastructure\Http\Routes;
use Apollo\Core\RoleManager;
use Apollo\Core\PWADetector;

/**
 * Main Plugin class
 *
 * Handles plugin lifecycle and service providers registration.
 */
class Plugin
{
    private $providers = [];
    private $initialized = false;

    /**
     * Constructor - MANDATORY for all Apollo plugins
     * Automatically initializes the plugin
     * Builds all CPT hooks, preparations, and Canvas pages
     */
    public function __construct()
    {
        // Prevent double initialization
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        
        // Register Role Manager (rename WordPress roles)
        $role_manager = new \Apollo\Core\RoleManager();
        $role_manager->register();
        
        // Bootstrap plugin automatically
        $this->bootstrap();
        
        // Initialize Canvas pages
        $this->initializeCanvasPages();
    }

    /**
     * Initialize Canvas pages on plugin activation/construction
     * Creates all Apollo pages independently from theme
     */
    private function initializeCanvasPages()
    {
        // Hook into init to ensure WordPress is ready
        add_action('init', [$this, 'createCanvasPages'], 20);
    }

    /**
     * Create Canvas pages
     */
    public function createCanvasPages()
    {
        // Check if pages already exist
        $pages_created = get_option('apollo_social_canvas_pages_created', false);
        
        if ($pages_created) {
            return; // Already created
        }

        // Pages to create
        $pages = [
            'feed' => [
                'title' => 'Feed Social',
                'slug' => 'feed',
                'template' => 'feed/feed.php',
            ],
            'chat' => [
                'title' => 'Chat',
                'slug' => 'chat',
                'template' => 'chat/chat-list.php',
            ],
            'painel' => [
                'title' => 'Painel',
                'slug' => 'painel',
                'template' => 'users/dashboard.php',
            ],
            'cena' => [
                'title' => 'Cena::rio',
                'slug' => 'cena',
                'template' => 'cena/cena.php',
            ],
            'cena-rio' => [
                'title' => 'Cena::rio',
                'slug' => 'cena-rio',
                'template' => 'cena/cena.php',
            ],
        ];

        foreach ($pages as $key => $page_data) {
            // Check if page exists
            $existing = get_page_by_path($page_data['slug']);
            
            if (!$existing) {
                $page_id = wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_content' => '<!-- Apollo Canvas Page -->',
                ]);

                if ($page_id && !is_wp_error($page_id)) {
                    // Mark as Apollo Canvas page
                    update_post_meta($page_id, '_apollo_canvas_page', true);
                    update_post_meta($page_id, '_apollo_canvas_template', $page_data['template']);
                }
            }
        }

        // Mark as created
        update_option('apollo_social_canvas_pages_created', true);
    }

    /**
     * Bootstrap the plugin (register providers, hooks)
     */
    public function bootstrap()
    {
        // Register service providers
        $this->registerProviders();
        
        // Initialize core functionality
        $this->initializeCore();
    }

    /**
     * Register all service providers
     */
    private function registerProviders()
    {
        // Load helper functions
        if (!function_exists('config')) {
            require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/helpers.php';
        }
        
        $this->providers = [
            new CoreServiceProvider(),
            new \Apollo\Modules\Auth\AuthServiceProvider(),
            new \Apollo\Modules\Registration\RegistrationServiceProvider(),
            new \Apollo\Modules\Builder\BuilderServiceProvider(),
            new \Apollo\Modules\Shortcodes\ShortcodeServiceProvider(),
            new \Apollo\Modules\Pwa\PwaServiceProvider(),
            new \Apollo\Infrastructure\Providers\AnalyticsServiceProvider(),
        ];

        // Register Widgets API endpoints
        $widgets_endpoints = new \Apollo\API\Endpoints\WidgetsEndpoints();
        $widgets_endpoints->register();

        // Register User Page Auto-Create hook
        $user_page_auto_create = new \Apollo\Hooks\UserPageAutoCreate();
        $user_page_auto_create->register();

        foreach ($this->providers as $provider) {
            $provider->register();
        }

        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Initialize core functionality
     */
    private function initializeCore()
    {
        // Register routes
        add_action('init', [$this, 'registerRoutes']);
        
        // Handle plugin requests
        add_action('template_redirect', [$this, 'handlePluginRequests']);
    }

    /**
     * Register plugin routes
     */
    public function registerRoutes()
    {
        $routes = new Routes();
        $routes->register();
    }

    /**
     * Handle plugin requests and Canvas Mode
     */
    public function handlePluginRequests()
    {
        $routes = new Routes();
        $routes->handleRequest();
    }
}