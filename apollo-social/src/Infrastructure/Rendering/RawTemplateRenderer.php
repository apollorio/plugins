<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * Raw Template Renderer
 * 
 * Renders a template file directly, returning the raw content.
 * Used for pages that have their own full HTML structure (<html>...</html>).
 */
class RawTemplateRenderer
{
    public function render($template_data)
    {
        $route_config = $template_data['route_config'] ?? [];
        $template = $route_config['template'] ?? '';
        
        if (empty($template)) {
            return [
                'content' => 'Template not defined',
                'raw' => true
            ];
        }
        
        $template_path = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/' . $template;
        
        if (!file_exists($template_path)) {
            return [
                'content' => 'Template not found: ' . esc_html($template),
                'raw' => true
            ];
        }
        
        // Make data available to template
        $view = $template_data;
        
        ob_start();
        include $template_path;
        $content = ob_get_clean();
        
        return [
            'content' => $content,
            'raw' => true
        ];
    }
}

