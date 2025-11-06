<?php
namespace Apollo;

use Apollo\Modules\Core\CoreServiceProvider;
use Apollo\Infrastructure\Http\Routes;

/**
 * Main Plugin class
 *
 * Handles plugin lifecycle and service providers registration.
 */
class Plugin
{
    private $providers = [];

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
            new \Apollo\Infrastructure\Providers\AnalyticsServiceProvider(),
        ];

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
        
        // Initialize DJ Contacts Table
        $this->initializeDJContactsTable();
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

    /**
     * Initialize DJ Contacts Table component
     */
    private function initializeDJContactsTable()
    {
        if (class_exists('\Apollo\Admin\DJContactsTable')) {
            new \Apollo\Admin\DJContactsTable();
        }
    }
}