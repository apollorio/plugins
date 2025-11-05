<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Canvas renderer
 *
 * Handles Canvas Mode rendering without theme headers/footers.
 */
class CanvasRenderer
{
    private $config;
    private $assets_manager;
    private $output_guards;

    public function __construct()
    {
        $this->loadConfig();
        $this->assets_manager = new AssetsManager();
        $this->output_guards = new OutputGuards();
    }

    /**
     * Load Canvas configuration
     */
    private function loadConfig()
    {
        $config_file = APOLLO_SOCIAL_PLUGIN_DIR . 'config/canvas.php';
        if (file_exists($config_file)) {
            $this->config = require $config_file;
        } else {
            $this->config = [];
        }
    }

    /**
     * Check if current request should use Canvas Mode
     */
    public function shouldUseCanvas()
    {
        return !empty($this->config['force_canvas_on_plugin_routes']);
    }

    /**
     * Render page in Canvas Mode
     */
    public function render($route_config)
    {
        // Install output guards to prevent theme interference
        $this->output_guards->install();

        // Prepare template data
        $template_data = $this->prepareTemplateData($route_config);

        // Load and render the handler
        $handler_output = $this->renderHandler($route_config, $template_data);

        // Enqueue Canvas assets
        $this->assets_manager->enqueueCanvas();

        // Render the complete Canvas layout
        $this->renderCanvasLayout($route_config, $handler_output, $template_data);
    }

    /**
     * Prepare template data from route and query vars
     */
    private function prepareTemplateData($route_config)
    {
        return [
            'route' => get_query_var('apollo_route'),
            'type' => get_query_var('apollo_type'),
            'param' => get_query_var('apollo_param'),
            'route_config' => $route_config,
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
        return [
            'title' => 'Apollo Social - ' . ucfirst($template_data['route']),
            'content' => '<p>Handler em desenvolvimento para: ' . $template_data['route'] . '</p>',
            'breadcrumbs' => ['Apollo Social', ucfirst($template_data['route'])],
        ];
    }

    /**
     * Render the complete Canvas layout
     */
    private function renderCanvasLayout($route_config, $handler_output, $template_data)
    {
        // Start output buffering
        ob_start();

        // Load Canvas layout template
        $layout_file = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/_canvas/layout.php';
        
        if (file_exists($layout_file)) {
            // Make data available to template
            $canvas_data = array_merge($template_data, $handler_output);
            $view = $canvas_data; // For template compatibility
            
            include $layout_file;
        } else {
            // Fallback minimal HTML
            $this->renderFallbackLayout($handler_output);
        }

        // Output and clean buffer
        echo ob_get_clean();
    }

    /**
     * Render fallback layout if template is missing
     */
    private function renderFallbackLayout($handler_output)
    {
        $title = isset($handler_output['title']) ? $handler_output['title'] : 'Apollo Social';
        $content = isset($handler_output['content']) ? $handler_output['content'] : '';
        
        echo '<!DOCTYPE html>';
        echo '<html ' . get_language_attributes() . '>';
        echo '<head>';
        echo '<meta charset="' . get_bloginfo('charset') . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($title) . '</title>';
        wp_head();
        echo '</head>';
        echo '<body class="apollo-canvas">';
        echo '<div id="apollo-canvas-wrapper">';
        echo '<main id="apollo-main">';
        echo '<h1>' . esc_html($title) . '</h1>';
        echo $content;
        echo '</main>';
        echo '</div>';
        wp_footer();
        echo '</body>';
        echo '</html>';
    }
}