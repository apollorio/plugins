<?php
namespace Apollo\Infrastructure\Http;

use Apollo\Infrastructure\Rendering\CanvasRenderer;

/**
 * Routes manager
 *
 * Registers URL patterns and handles requests for plugin pages.
 */
class Routes
{
    private $routes = [];
    private $current_route = null;

    public function __construct()
    {
        $this->loadRoutes();
    }

    /**
     * Load routes configuration
     */
    private function loadRoutes()
    {
        $config_file = APOLLO_SOCIAL_PLUGIN_DIR . 'config/routes.php';
        if (file_exists($config_file)) {
            $this->routes = require $config_file;
        }
    }

    /**
     * Register all routes from config
     */
    public function register()
    {
        // Add query vars
        add_filter('query_vars', [$this, 'addQueryVars']);

        // Register rewrite rules
        foreach ($this->routes as $route_config) {
            if (isset($route_config['pattern'])) {
                $query_string = $this->buildQueryString($route_config['query_vars']);
                add_rewrite_rule($route_config['pattern'], $query_string, 'top');
            }
        }
    }

    /**
     * Add custom query vars
     */
    public function addQueryVars($vars)
    {
        $vars[] = 'apollo_route';
        $vars[] = 'apollo_type';
        $vars[] = 'apollo_param';
        return $vars;
    }

    /**
     * Build query string from query vars
     */
    private function buildQueryString($query_vars)
    {
        $query_parts = [];
        foreach ($query_vars as $key => $value) {
            $query_parts[] = $key . '=' . $value;
        }
        return 'index.php?' . implode('&', $query_parts);
    }

    /**
     * Handle current request
     */
    public function handleRequest()
    {
        $apollo_route = get_query_var('apollo_route');
        
        if (!$apollo_route) {
            return; // Not our route
        }

        // Find matching route configuration
        $route_config = $this->findRouteConfig($apollo_route);
        if (!$route_config) {
            return;
        }

        // Set current route
        $this->current_route = $route_config;

        // Initialize Canvas Mode
        $canvas = new CanvasRenderer();
        $canvas->render($route_config);
        
        exit; // Prevent WordPress from continuing normal template loading
    }

    /**
     * Find route configuration by route name
     */
    private function findRouteConfig($route_name)
    {
        foreach ($this->routes as $route_config) {
            if (isset($route_config['query_vars']['apollo_route']) && 
                $route_config['query_vars']['apollo_route'] === $route_name) {
                return $route_config;
            }
        }
        return null;
    }

    /**
     * Get current route data
     */
    public function getCurrentRoute()
    {
        return $this->current_route;
    }

    /**
     * Check if current request is a plugin route
     */
    public function isPluginRoute()
    {
        return !empty(get_query_var('apollo_route'));
    }
}