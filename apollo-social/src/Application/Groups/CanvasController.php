<?php

namespace Apollo\Application\Groups;

use Apollo\Application\Groups\Moderation;

/**
 * Canvas UI Controller for Groups
 * Handles group display with workflow status integration
 */
class CanvasController
{
    private $moderation;
    
    public function __construct()
    {
        $this->moderation = new Moderation();
    }
    
    /**
     * Render group card with status badge
     */
    public function renderGroupCard(array $group): string
    {
        $status = $group['status'] ?? 'draft';
        $rejection_notice = null;
        
        // Get rejection notice if group is rejected
        if ($status === 'rejected') {
            $rejection_notice = $this->moderation->getRejectionNotice($group['id']);
        }
        
        // Set template variables
        $template_vars = [
            'group' => $group,
            'status' => $status,
            'group_id' => $group['id'],
            'rejection_notice' => $rejection_notice
        ];
        
        return $this->renderTemplate('group-card', $template_vars);
    }
    
    /**
     * Render group status badge only
     */
    public function renderStatusBadge(array $group): string
    {
        $status = $group['status'] ?? 'draft';
        $rejection_notice = null;
        
        // Get rejection notice if group is rejected
        if ($status === 'rejected') {
            $rejection_notice = $this->moderation->getRejectionNotice($group['id']);
        }
        
        // Set template variables for status badge
        $template_vars = [
            'status' => $status,
            'group_id' => $group['id'],
            'rejection_notice' => $rejection_notice
        ];
        
        return $this->renderTemplate('group-status-badge', $template_vars);
    }
    
    /**
     * Render moderation action bar for editors/admins
     */
    public function renderModerationActions(array $group, \WP_User $user): string
    {
        $status = $group['status'] ?? 'draft';
        
        // Only show moderation actions to editors/admins
        if (!user_can($user, 'edit_posts')) {
            return '';
        }
        
        // Only show for pending items
        if (!in_array($status, ['pending', 'pending_review'])) {
            return '';
        }
        
        $template_vars = [
            'group' => $group,
            'status' => $status,
            'group_id' => $group['id'],
            'user' => $user
        ];
        
        return $this->renderTemplate('moderation-actions', $template_vars);
    }
    
    /**
     * Get group dashboard with workflow status
     */
    public function getUserGroupsDashboard(int $user_id): array
    {
        global $wpdb;
        
        $groups_table = $wpdb->prefix . 'apollo_groups';
        
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$groups_table} 
             WHERE creator_id = %d 
             ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);
        
        $dashboard_groups = [];
        
        foreach ($groups as $group) {
            $group_data = $group;
            $status = $group['status'] ?? 'draft';
            
            // Add rejection notice if rejected
            if ($status === 'rejected') {
                $group_data['rejection_notice'] = $this->moderation->getRejectionNotice($group['id']);
            }
            
            // Add workflow metadata
            $group_data['workflow_meta'] = $this->getWorkflowMetadata($group['id']);
            
            $dashboard_groups[] = $group_data;
        }
        
        return $dashboard_groups;
    }
    
    /**
     * Get workflow metadata for group
     */
    private function getWorkflowMetadata(int $group_id): array
    {
        global $wpdb;
        
        $moderation_table = $wpdb->prefix . 'apollo_moderation_queue';
        
        $workflow = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$moderation_table} 
             WHERE entity_id = %d AND entity_type = 'group' 
             ORDER BY submitted_at DESC LIMIT 1",
            $group_id
        ), ARRAY_A);
        
        if (!$workflow) {
            return [];
        }
        
        return [
            'submitted_at' => $workflow['submitted_at'],
            'reviewed_at' => $workflow['reviewed_at'],
            'moderator_id' => $workflow['moderator_id'],
            'status' => $workflow['status']
        ];
    }
    
    /**
     * Render template with variables
     */
    private function renderTemplate(string $template_name, array $vars = []): string
    {
        // Extract variables for template
        extract($vars);
        
        $template_path = plugin_dir_path(__FILE__) . "../../templates/{$template_name}.php";
        
        if (!file_exists($template_path)) {
            return "<!-- Template {$template_name} not found -->";
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for resubmission
     */
    public function handleResubmission(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apollo_resubmit_group')) {
            wp_die('Security check failed');
        }
        
        $group_id = intval($_POST['group_id']);
        $user_id = get_current_user_id();
        
        // Verify ownership
        global $wpdb;
        $groups_table = $wpdb->prefix . 'apollo_groups';
        
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$groups_table} WHERE id = %d AND creator_id = %d",
            $group_id,
            $user_id
        ));
        
        if (!$group) {
            wp_send_json_error('Grupo não encontrado ou sem permissão');
            return;
        }
        
        // Reset to draft status for editing
        $wpdb->update(
            $groups_table,
            [
                'status' => 'draft',
                'updated_at' => current_time('mysql')
            ],
            ['id' => $group_id]
        );
        
        wp_send_json_success([
            'message' => 'Grupo movido para rascunho. Você pode editá-lo e reenviar.',
            'redirect' => "/grupo/editar/{$group_id}/"
        ]);
    }
}