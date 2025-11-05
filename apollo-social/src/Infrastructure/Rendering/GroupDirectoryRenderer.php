<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Group Directory Renderer
 *
 * Renders group directory pages (/comunidade/, /nucleo/, /season/)
 */
class GroupDirectoryRenderer
{
    /**
     * Render group directory page
     */
    public function render($template_data)
    {
        $type = $template_data['type']; // comunidade, nucleo, season
        
        // Get group type configuration
        $group_config = $this->getGroupConfig($type);
        $groups = $this->getGroupsData($type);
        
        return [
            'title' => $group_config['label_plural'],
            'content' => $this->renderGroupDirectory($groups, $group_config),
            'breadcrumbs' => ['Apollo Social', $group_config['label_plural']],
            'groups' => $groups,
            'group_config' => $group_config,
        ];
    }

    /**
     * Get group type configuration
     */
    private function getGroupConfig($type)
    {
        $config_file = APOLLO_SOCIAL_PLUGIN_DIR . 'config/groups.php';
        if (file_exists($config_file)) {
            $config = require $config_file;
            if (isset($config['types'][$type])) {
                return $config['types'][$type];
            }
        }
        
        // Fallback
        return [
            'label' => ucfirst($type),
            'label_plural' => ucfirst($type) . 's',
            'description' => 'Grupos do tipo ' . $type,
        ];
    }

    /**
     * Get groups data (placeholder)
     */
    private function getGroupsData($type)
    {
        // Mock data for now
        $groups = [];
        
        for ($i = 1; $i <= 6; $i++) {
            $groups[] = [
                'id' => $i,
                'name' => ucfirst($type) . ' ' . $i,
                'slug' => $type . '-' . $i,
                'description' => 'Descrição do ' . $type . ' número ' . $i,
                'members_count' => rand(5, 50),
                'type' => $type,
                'url' => '/' . $type . '/' . $type . '-' . $i,
            ];
        }
        
        return $groups;
    }

    /**
     * Render group directory content
     */
    private function renderGroupDirectory($groups, $group_config)
    {
        ob_start();
        ?>
        <div class="apollo-group-directory">
            <div class="directory-header">
                <h1><?= esc_html($group_config['label_plural']) ?></h1>
                <p class="directory-description"><?= esc_html($group_config['description']) ?></p>
            </div>
            
            <div class="groups-grid">
                <?php foreach ($groups as $group): ?>
                    <div class="group-card">
                        <h3><a href="<?= esc_attr($group['url']) ?>"><?= esc_html($group['name']) ?></a></h3>
                        <p class="group-description"><?= esc_html($group['description']) ?></p>
                        <p class="group-meta">
                            <span class="members-count"><?= intval($group['members_count']) ?> membros</span>
                        </p>
                        <div class="group-actions">
                            <a href="<?= esc_attr($group['url']) ?>" class="btn btn-primary">Ver Grupo</a>
                            <!-- TODO: Add join/leave buttons based on user permissions -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- TODO: Add pagination, search, filters -->
        </div>
        <?php
        return ob_get_clean();
    }
}