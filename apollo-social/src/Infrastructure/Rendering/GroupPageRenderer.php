<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Group Page Renderer
 */
class GroupPageRenderer
{
    public function render($template_data)
    {
        $type = $template_data['type'];
        $slug = $template_data['param'];
        
        $group_data = $this->getGroupData($type, $slug);
        
        return [
            'title' => $group_data['name'],
            'content' => $this->renderGroupPage($group_data),
            'breadcrumbs' => ['Apollo Social', ucfirst($type) . 's', $group_data['name']],
            'group' => $group_data,
        ];
    }

    private function getGroupData($type, $slug)
    {
        return [
            'id' => 1,
            'name' => ucfirst($type) . ' ' . ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'type' => $type,
            'description' => 'Descrição detalhada do ' . $type . ' ' . $slug,
            'members_count' => rand(10, 100),
            'created' => '2025-01-01',
        ];
    }

    private function renderGroupPage($group_data)
    {
        ob_start();
        echo '<div class="apollo-group-single">';
        echo '<h1>' . esc_html($group_data['name']) . '</h1>';
        echo '<p>' . esc_html($group_data['description']) . '</p>';
        echo '<p>Membros: ' . intval($group_data['members_count']) . '</p>';
        echo '<p>Criado em: ' . esc_html($group_data['created']) . '</p>';
        echo '<!-- TODO: Add group content, members list, posts -->';
        echo '</div>';
        return ob_get_clean();
    }
}