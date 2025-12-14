<?php

namespace Apollo\Infrastructure\Http;

use Apollo\Infrastructure\Rendering\CanvasBuilder;

/**
 * Routes manager
 *
 * Registers URL patterns and handles requests for plugin pages.
 */
class Routes
{
    private $routes        = [];
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
        add_filter('query_vars', [ $this, 'addQueryVars' ]);

        // Register rewrite rules
        foreach ($this->routes as $route_config) {
            if (isset($route_config['pattern'])) {
                $query_string = $this->buildQueryString($route_config['query_vars']);
                // Use priority from config or default to 'top'
                // WordPress accepts 'top' or 'bottom' only
                $priority = isset($route_config['priority']) && in_array($route_config['priority'], [ 'top', 'bottom' ])
                    ? $route_config['priority']
                    : 'top';
                add_rewrite_rule($route_config['pattern'], $query_string, $priority);
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
        $vars[] = 'user_id';

        return $vars;
    }

    /**
     * Build query string from query vars
     */
    private function buildQueryString($query_vars)
    {
        $query_parts = [];
        foreach ($query_vars as $key => $value) {
            $key           = sanitize_key($key);
            $value         = urlencode(sanitize_text_field($value));
            $query_parts[] = $key . '=' . $value;
        }

        return 'index.php?' . implode('&', $query_parts);
    }

    /**
     * P0-4: Handle current request with strong Canvas Mode enforcement
     */
    public function handleRequest()
    {
        // Don't interfere with WordPress core functionality
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        // Don't interfere with feeds RSS (WordPress default feeds)
        // Only intercept /feed/ if it's explicitly an Apollo route
        if (is_feed() && ! get_query_var('apollo_route')) {
            return;
            // WordPress RSS feed, not Apollo route
        }

        // Don't interfere with REST API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        // Don't interfere with sitemaps
        if (function_exists('wp_is_sitemap') && wp_is_sitemap()) {
            return;
        }

        // Only process if we have an Apollo route query var
        $apollo_route = get_query_var('apollo_route');

        if (empty($apollo_route) || ! is_string($apollo_route)) {
            return;
            // Not our route
        }

        // Find matching route configuration
        $route_config = $this->findRouteConfig($apollo_route);
        if (! $route_config) {
            return;
        }

        // P0-4: If this is a Canvas route, enforce Canvas Mode BEFORE building
        if (! empty($route_config['canvas'])) {
            // Force template_redirect to prevent theme template loading
            add_filter(
                'template_include',
                function ($template) {
                    // Return empty string to completely prevent theme template
                    return '';
                },
                999
            );

            // Remove all theme actions from wp_head and wp_footer
            add_action(
                'template_redirect',
                function () {
                    // This runs early enough to remove theme hooks
                    global $wp_filter;
                    $theme_slug = get_stylesheet();

                    // Remove theme hooks from wp_head
                    if (isset($wp_filter['wp_head'])) {
                        foreach ($wp_filter['wp_head']->callbacks as $priority => $hooks) {
                            foreach ($hooks as $hook_id => $hook) {
                                $function = $hook['function'] ?? null;
                                if ($function && is_array($function) && isset($function[0])) {
                                    $class_name = get_class($function[0]);
                                    if (strpos($class_name, $theme_slug) !== false) {
                                        remove_action('wp_head', $function, $priority);
                                    }
                                }
                            }
                        }
                    }

                    // Remove theme hooks from wp_footer
                    if (isset($wp_filter['wp_footer'])) {
                        foreach ($wp_filter['wp_footer']->callbacks as $priority => $hooks) {
                            foreach ($hooks as $hook_id => $hook) {
                                $function = $hook['function'] ?? null;
                                if ($function && is_array($function) && isset($function[0])) {
                                    $class_name = get_class($function[0]);
                                    if (strpos($class_name, $theme_slug) !== false) {
                                        remove_action('wp_footer', $function, $priority);
                                    }
                                }
                            }
                        }
                    }
                },
                1
            );
        }//end if

        // Set current route (before building so getCurrentRoute() works)
        $this->current_route = $route_config;

        // P0-4: Use strong Canvas Builder for robust Canvas Mode
        // This will render complete HTML and exit, preventing theme template loading
        $builder = new CanvasBuilder();
        $builder->build($route_config);

        // If builder didn't exit, ensure we stop WordPress template loading
        wp_die('', '', [ 'response' => 200 ]);
    }

    /**
     * Find route configuration by route name
     */
    private function findRouteConfig($route_name)
    {
        foreach ($this->routes as $route_config) {
            if (isset($route_config['query_vars']['apollo_route']) && $route_config['query_vars']['apollo_route'] === $route_name) {
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
        $route = get_query_var('apollo_route');

        return ! empty($route) && is_string($route);
    }
}
