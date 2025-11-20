<?php
namespace Apollo\Infrastructure\Rendering;

use Apollo\Core\PWADetector;

/**
 * Canvas Builder - Strong Constructor for Apollo Canvas Pages
 * 
 * Builds Canvas Mode pages with ONLY Apollo plugins assets (JS, CSS, Data)
 * Ensures complete isolation from theme interference
 */
class CanvasBuilder
{
    private $assets_manager;
    private $output_guards;
    private $route_config;
    private $template_data;
    private $handler_output;

    public function __construct()
    {
        $this->assets_manager = new AssetsManager();
        $this->output_guards = new OutputGuards();
    }

    /**
     * Build complete Canvas page
     * 
     * @param array $route_config Route configuration
     * @return void
     * @throws \Exception If route config is invalid
     */
    public function build($route_config)
    {
        // Validate route config
        if (!is_array($route_config) || empty($route_config)) {
            throw new \InvalidArgumentException('Route config must be a non-empty array');
        }

        $this->route_config = $route_config;

        try {
            // Step 1: Install output guards FIRST (before any WordPress output)
            $this->output_guards->install();

            // Step 2: Prepare template data
            $this->template_data = $this->prepareTemplateData($route_config);

            // Step 3: Render handler to get page content
            $this->handler_output = $this->renderHandler($route_config, $this->template_data);

            // Validate handler output
            if (!is_array($this->handler_output)) {
                $this->handler_output = $this->renderDefaultHandler($this->template_data);
            }
            
            // Add route template path to view data
            if (isset($route_config['template'])) {
                $template_path = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/' . $route_config['template'];
                if (file_exists($template_path)) {
                    $this->handler_output['route_template'] = $template_path;
                }
            }

            // Step 4: Enqueue ONLY Apollo assets
            $this->enqueueApolloAssets();

            // Step 5: Render complete Canvas layout
            $this->renderCanvasLayout();
        } catch (\Exception $e) {
            // Log error and render error fallback
            error_log('Apollo CanvasBuilder Error: ' . $e->getMessage());
            $this->renderErrorFallback($e);
        }
    }

    /**
     * Prepare template data from route and query vars
     */
    private function prepareTemplateData($route_config)
    {
        $route = get_query_var('apollo_route');
        $type = get_query_var('apollo_type');
        $param = get_query_var('apollo_param');
        $user_id = get_query_var('user_id');

        return [
            'route' => is_string($route) ? sanitize_text_field($route) : '',
            'type' => is_string($type) ? sanitize_text_field($type) : '',
            'param' => is_string($param) ? sanitize_text_field($param) : '',
            'user_id' => is_numeric($user_id) ? absint($user_id) : 0,
            'route_config' => $route_config,
            'current_user' => wp_get_current_user(),
        ];
    }

    /**
     * Render the page handler
     */
    private function renderHandler($route_config, $template_data)
    {
        if (!isset($route_config['handler'])) {
            return $this->renderDefaultHandler($template_data);
        }

        $handler_class = $route_config['handler'];
        
        // Security: Validate that handler class is in Apollo namespace
        if (strpos($handler_class, 'Apollo\\') !== 0) {
            error_log('Apollo Security: Attempted to instantiate non-Apollo class: ' . esc_html($handler_class));
            return $this->renderDefaultHandler($template_data);
        }
        
        if (!class_exists($handler_class)) {
            return $this->renderDefaultHandler($template_data);
        }

        $handler = new $handler_class();
        
        if (method_exists($handler, 'render')) {
            return $handler->render($template_data);
        }

        return $this->renderDefaultHandler($template_data);
    }

    /**
     * Render default handler output
     */
    private function renderDefaultHandler($template_data)
    {
        $route = isset($template_data['route']) ? sanitize_text_field($template_data['route']) : '';
        $route_title = ucfirst($route);
        return [
            'title' => 'Apollo Social - ' . $route_title,
            'content' => '<p>Handler em desenvolvimento para: ' . esc_html($route) . '</p>',
            'breadcrumbs' => ['Apollo Social', $route_title],
            'data' => [],
        ];
    }

    /**
     * Enqueue ONLY Apollo assets (strong filtering)
     */
    private function enqueueApolloAssets()
    {
        // Enqueue Canvas base assets
        $this->assets_manager->enqueueCanvas();

        // Enqueue route-specific assets if defined
        if (isset($this->route_config['assets'])) {
            $this->enqueueRouteAssets($this->route_config['assets']);
        }

        // Add route-specific data to JavaScript
        $this->localizeRouteData();
    }

    /**
     * Enqueue route-specific assets
     */
    private function enqueueRouteAssets($assets_config)
    {
        if (!is_array($assets_config)) {
            return;
        }

        // CSS
        if (isset($assets_config['css']) && is_array($assets_config['css'])) {
            foreach ($assets_config['css'] as $handle => $config) {
                if (!is_array($config) || !isset($config['src'])) {
                    continue;
                }

                // Validate handle
                $handle = sanitize_key($handle);
                if (empty($handle)) {
                    continue;
                }

                // Validate and sanitize src
                $src = esc_url_raw($config['src']);
                if (empty($src)) {
                    continue;
                }

                // Validate dependencies
                $deps = isset($config['deps']) && is_array($config['deps']) 
                    ? array_map('sanitize_key', $config['deps']) 
                    : [];

                // Validate version
                $version = isset($config['version']) ? sanitize_text_field($config['version']) : APOLLO_SOCIAL_VERSION;

                wp_enqueue_style($handle, $src, $deps, $version);
            }
        }

        // JS
        if (isset($assets_config['js']) && is_array($assets_config['js'])) {
            foreach ($assets_config['js'] as $handle => $config) {
                if (!is_array($config) || !isset($config['src'])) {
                    continue;
                }

                // Validate handle
                $handle = sanitize_key($handle);
                if (empty($handle)) {
                    continue;
                }

                // Validate and sanitize src
                $src = esc_url_raw($config['src']);
                if (empty($src)) {
                    continue;
                }

                // Validate dependencies
                $deps = isset($config['deps']) && is_array($config['deps']) 
                    ? array_map('sanitize_key', $config['deps']) 
                    : [];

                // Validate version
                $version = isset($config['version']) ? sanitize_text_field($config['version']) : APOLLO_SOCIAL_VERSION;

                // Validate in_footer
                $in_footer = isset($config['in_footer']) ? (bool) $config['in_footer'] : true;

                wp_enqueue_script($handle, $src, $deps, $version, $in_footer);
            }
        }
    }

    /**
     * Localize route-specific data to JavaScript
     */
    private function localizeRouteData()
    {
        $js_data = [
            'route' => isset($this->template_data['route']) ? sanitize_text_field($this->template_data['route']) : '',
            'type' => isset($this->template_data['type']) ? sanitize_text_field($this->template_data['type']) : '',
            'param' => isset($this->template_data['param']) ? sanitize_text_field($this->template_data['param']) : '',
            'user_id' => isset($this->template_data['user_id']) ? absint($this->template_data['user_id']) : 0,
            'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('apollo_canvas_' . (isset($this->template_data['route']) ? sanitize_key($this->template_data['route']) : 'default')),
            'pluginUrl' => esc_url(APOLLO_SOCIAL_PLUGIN_URL),
        ];

        // Merge handler output data if available (sanitize all values)
        if (isset($this->handler_output['data']) && is_array($this->handler_output['data'])) {
            $handler_data = $this->handler_output['data'];
            // Sanitize array values recursively
            $handler_data = $this->sanitizeArray($handler_data);
            $js_data = array_merge($js_data, $handler_data);
        }

        // Localize to main canvas script (only if script is enqueued)
        if (wp_script_is('apollo-canvas', 'enqueued') || wp_script_is('apollo-canvas', 'registered')) {
            wp_localize_script('apollo-canvas', 'apolloCanvasData', $js_data);
        }

        // Also localize to dashboard script if enqueued
        if (wp_script_is('apollo-dashboard', 'enqueued') || wp_script_is('apollo-dashboard', 'registered')) {
            wp_localize_script('apollo-dashboard', 'apolloCanvasData', $js_data);
        }
    }

    /**
     * Sanitize array recursively
     */
    private function sanitizeArray($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $array[$key] = sanitize_text_field($value);
            } elseif (is_int($value)) {
                $array[$key] = absint($value);
            } elseif (is_bool($value)) {
                $array[$key] = (bool) $value;
            }
        }
        return $array;
    }

    /**
     * Render the complete Canvas layout
     */
    private function renderCanvasLayout()
    {
        // Start output buffering
        ob_start();

        // Load Canvas layout template
        $layout_file = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/canvas/layout.php';
        
        if (file_exists($layout_file)) {
            // Get PWA detector
            $pwa_detector = new \Apollo\Core\PWADetector();

            // Make data available to template
            $canvas_data = array_merge($this->template_data, $this->handler_output);
            $view = $canvas_data; // For template compatibility
            
            // Add PWA data
            $view['pwa'] = [
                'is_pwa' => $pwa_detector->isPWAMode(),
                'is_apollo_rio_active' => $pwa_detector->isApolloRioActive(),
                'show_header' => $pwa_detector->shouldShowApolloHeader(),
                'is_clean_mode' => $pwa_detector->isCleanMode(),
                'instructions' => $pwa_detector->getPWAInstructions(),
            ];
            
            include $layout_file;
        } else {
            // Fallback minimal HTML
            $this->renderFallbackLayout();
        }

        // Output and clean buffer
        echo ob_get_clean();
    }

    /**
     * Render fallback layout if template is missing
     */
    private function renderFallbackLayout()
    {
        $title = isset($this->handler_output['title']) ? sanitize_text_field($this->handler_output['title']) : 'Apollo Social';
        $content = isset($this->handler_output['content']) ? $this->handler_output['content'] : '';
        
        echo '<!DOCTYPE html>';
        echo '<html ' . get_language_attributes() . '>';
        echo '<head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($title) . '</title>';
        wp_head();
        echo '</head>';
        echo '<body class="apollo-canvas">';
        echo '<div id="apollo-canvas-wrapper">';
        echo '<main id="apollo-main">';
        echo '<h1>' . esc_html($title) . '</h1>';
        echo wp_kses_post($content);
        echo '</main>';
        echo '</div>';
        wp_footer();
        echo '</body>';
        echo '</html>';
    }

    /**
     * Render error fallback layout
     */
    private function renderErrorFallback(\Exception $e)
    {
        $title = 'Erro ao Carregar Página';
        $content = '<p>Ocorreu um erro ao carregar esta página. Por favor, tente novamente.</p>';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $content .= '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;">';
            $content .= esc_html($e->getMessage());
            $content .= '</pre>';
        }
        
        echo '<!DOCTYPE html>';
        echo '<html ' . get_language_attributes() . '>';
        echo '<head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($title) . '</title>';
        wp_head();
        echo '</head>';
        echo '<body class="apollo-canvas apollo-error">';
        echo '<div id="apollo-canvas-wrapper">';
        echo '<main id="apollo-main">';
        echo '<h1>' . esc_html($title) . '</h1>';
        echo wp_kses_post($content);
        echo '</main>';
        echo '</div>';
        wp_footer();
        echo '</body>';
        echo '</html>';
    }
}

