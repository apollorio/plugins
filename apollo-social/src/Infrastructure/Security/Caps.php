<?php

namespace Apollo\Infrastructure\Security;

/**
 * Apollo Capabilities Manager
 *
 * Defines and manages WordPress capabilities for Apollo Social features.
 */
class Caps
{
    /**
     * Initialize capabilities
     */
    public function init(): void
    {
        \add_action('init', [ $this, 'registerCapabilities' ]);
        \add_action('admin_init', [ $this, 'assignCapabilitiesToRoles' ]);
    }

    /**
     * Register Apollo capabilities
     */
    public function registerCapabilities(): void
    {
        // Groups capabilities
        $this->registerGroupCapabilities();

        // Events capabilities
        $this->registerEventCapabilities();

        // Ads/Classifieds capabilities
        $this->registerAdCapabilities();

        // Moderation capabilities
        $this->registerModerationCapabilities();

        // Analytics capabilities
        $this->registerAnalyticsCapabilities();
    }

    /**
     * Register group capabilities
     */
    private function registerGroupCapabilities(): void
    {
        $capabilities = [
            // Read capabilities
            'read_apollo_group',
            'read_private_apollo_groups',

            // Create/Edit capabilities
            'create_apollo_groups',
            'edit_apollo_groups',
            'edit_others_apollo_groups',
            'edit_private_apollo_groups',

            // Publish capabilities
            'publish_apollo_groups',

            // Delete capabilities
            'delete_apollo_groups',
            'delete_others_apollo_groups',
            'delete_private_apollo_groups',

            // Management capabilities
            'manage_apollo_group_members',
            'moderate_apollo_group_content',
        ];

        foreach ($capabilities as $cap) {
            $this->addCapabilityIfNotExists($cap);
        }
    }

    /**
     * Register event capabilities
     */
    private function registerEventCapabilities(): void
    {
        $capabilities = [
            // Read capabilities
            'read_eva_event',
            'read_private_eva_events',

            // Create/Edit capabilities
            'create_eva_events',
            'edit_eva_events',
            'edit_others_eva_events',
            'edit_private_eva_events',

            // Publish capabilities
            'publish_eva_events',

            // Delete capabilities
            'delete_eva_events',
            'delete_others_eva_events',
            'delete_private_eva_events',

            // Management capabilities
            'manage_eva_event_categories',
            'manage_eva_event_locations',
        ];

        foreach ($capabilities as $cap) {
            $this->addCapabilityIfNotExists($cap);
        }
    }

    /**
     * Register ads/classifieds capabilities
     */
    private function registerAdCapabilities(): void
    {
        $capabilities = [
            // Read capabilities
            'read_apollo_ad',
            'read_private_apollo_ads',

            // Create/Edit capabilities
            'create_apollo_ads',
            'edit_apollo_ads',
            'edit_others_apollo_ads',
            'edit_private_apollo_ads',

            // Publish capabilities
            'publish_apollo_ads',

            // Delete capabilities
            'delete_apollo_ads',
            'delete_others_apollo_ads',
            'delete_private_apollo_ads',

            // Management capabilities
            'moderate_apollo_ads',
        ];

        foreach ($capabilities as $cap) {
            $this->addCapabilityIfNotExists($cap);
        }
    }

    /**
     * Register mod capabilities
     */
    private function registerModerationCapabilities(): void
    {
        $capabilities = [
            // General mod
            'apollo_moderate',

            // Specific mod
            'apollo_moderate_groups',
            'apollo_moderate_events',
            'apollo_moderate_ads',
            'apollo_moderate_users',

            // Advanced mod
            'apollo_moderate_all',
            'apollo_view_mod_queue',
            'apollo_manage_moderators',
        ];

        foreach ($capabilities as $cap) {
            $this->addCapabilityIfNotExists($cap);
        }
    }

    /**
     * Register analytics capabilities
     */
    private function registerAnalyticsCapabilities(): void
    {
        $capabilities = [
            'apollo_view_analytics',
            'apollo_manage_analytics',
            'apollo_export_analytics',
        ];

        foreach ($capabilities as $cap) {
            $this->addCapabilityIfNotExists($cap);
        }
    }

    /**
     * Assign capabilities to WordPress roles
     */
    public function assignCapabilitiesToRoles(): void
    {
        $this->assignAdministratorCapabilities();
        $this->assignEditorCapabilities();
        $this->assignAuthorCapabilities();
        $this->assignContributorCapabilities();
        $this->assignSubscriberCapabilities();
    }

    /**
     * Administrator capabilities (all permissions)
     */
    private function assignAdministratorCapabilities(): void
    {
        $admin = \get_role('administrator');
        if (! $admin) {
            return;
        }

        $capabilities = [
            // Groups - Full access
            'read_apollo_group',
            'read_private_apollo_groups',
            'create_apollo_groups',
            'edit_apollo_groups',
            'edit_others_apollo_groups',
            'edit_private_apollo_groups',
            'publish_apollo_groups',
            'delete_apollo_groups',
            'delete_others_apollo_groups',
            'delete_private_apollo_groups',
            'manage_apollo_group_members',
            'moderate_apollo_group_content',

            // Events - Full access
            'read_eva_event',
            'read_private_eva_events',
            'create_eva_events',
            'edit_eva_events',
            'edit_others_eva_events',
            'edit_private_eva_events',
            'publish_eva_events',
            'delete_eva_events',
            'delete_others_eva_events',
            'delete_private_eva_events',
            'manage_eva_event_categories',
            'manage_eva_event_locations',

            // Ads - Full access
            'read_apollo_ad',
            'read_private_apollo_ads',
            'create_apollo_ads',
            'edit_apollo_ads',
            'edit_others_apollo_ads',
            'edit_private_apollo_ads',
            'publish_apollo_ads',
            'delete_apollo_ads',
            'delete_others_apollo_ads',
            'delete_private_apollo_ads',
            'moderate_apollo_ads',

            // Moderation - Full access
            'apollo_moderate',
            'apollo_moderate_groups',
            'apollo_moderate_events',
            'apollo_moderate_ads',
            'apollo_moderate_users',
            'apollo_moderate_all',
            'apollo_view_mod_queue',
            'apollo_manage_moderators',

            // Analytics - Full access
            'apollo_view_analytics',
            'apollo_manage_analytics',
            'apollo_export_analytics',
        ];

        foreach ($capabilities as $cap) {
            $admin->add_cap($cap);
        }
    }

    /**
     * Editor capabilities
     */
    private function assignEditorCapabilities(): void
    {
        $editor = \get_role('editor');
        if (! $editor) {
            return;
        }

        $capabilities = [
            // Groups - Edit all, publish directly
            'read_apollo_group',
            'read_private_apollo_groups',
            'create_apollo_groups',
            'edit_apollo_groups',
            'edit_others_apollo_groups',
            'edit_private_apollo_groups',
            'publish_apollo_groups',
            'delete_apollo_groups',
            'delete_others_apollo_groups',
            'delete_private_apollo_groups',
            'manage_apollo_group_members',
            'moderate_apollo_group_content',

            // Events - Edit all, publish directly
            'read_eva_event',
            'read_private_eva_events',
            'create_eva_events',
            'edit_eva_events',
            'edit_others_eva_events',
            'edit_private_eva_events',
            'publish_eva_events',
            'delete_eva_events',
            'delete_others_eva_events',
            'delete_private_eva_events',
            'manage_eva_event_categories',
            'manage_eva_event_locations',

            // Ads - Edit all, publish directly
            'read_apollo_ad',
            'read_private_apollo_ads',
            'create_apollo_ads',
            'edit_apollo_ads',
            'edit_others_apollo_ads',
            'edit_private_apollo_ads',
            'publish_apollo_ads',
            'delete_apollo_ads',
            'delete_others_apollo_ads',
            'delete_private_apollo_ads',
            'moderate_apollo_ads',

            // Moderation - Can moderate content
            'apollo_moderate',
            'apollo_moderate_groups',
            'apollo_moderate_events',
            'apollo_moderate_ads',
            'apollo_view_mod_queue',

            // Analytics - View and manage
            'apollo_view_analytics',
            'apollo_manage_analytics',
        ];

        foreach ($capabilities as $cap) {
            $editor->add_cap($cap);
        }
    }

    /**
     * Author capabilities
     */
    private function assignAuthorCapabilities(): void
    {
        $author = \get_role('author');
        if (! $author) {
            return;
        }

        $capabilities = [
            // Groups - Create own, edit own, requires approval for non-user posts
            'read_apollo_group',
            'create_apollo_groups',
            'edit_apollo_groups',
            'delete_apollo_groups',

            // Events - Can publish directly!
            'read_eva_event',
            'create_eva_events',
            'edit_eva_events',
            'delete_eva_events',
            'publish_eva_events',

            // Ads - Create own, edit own, requires approval
            'read_apollo_ad',
            'create_apollo_ads',
            'edit_apollo_ads',
            'delete_apollo_ads',

            // Analytics - View only
            'apollo_view_analytics',
        ];

        foreach ($capabilities as $cap) {
            $author->add_cap($cap);
        }
    }

    /**
     * Contributor capabilities
     */
    private function assignContributorCapabilities(): void
    {
        $contributor = \get_role('contributor');
        if (! $contributor) {
            return;
        }

        $capabilities = [
            // Groups - Create, edit own drafts, NO publish, NO delete published
            'read_apollo_group',
            'create_apollo_groups',
            'edit_apollo_groups',

            // Events - Create, edit own drafts, NO publish, NO delete published
            'read_eva_event',
            'create_eva_events',
            'edit_eva_events',

            // Ads - Create, edit own drafts, NO publish, NO delete published
            'read_apollo_ad',
            'create_apollo_ads',
            'edit_apollo_ads',

            // Analytics - View basic stats only
            'apollo_view_analytics',
        ];

        foreach ($capabilities as $cap) {
            $contributor->add_cap($cap);
        }
    }

    /**
     * Subscriber capabilities (FINAL MATRIX)
     */
    private function assignSubscriberCapabilities(): void
    {
        $subscriber = \get_role('subscriber');
        if (! $subscriber) {
            return;
        }

        $capabilities = [
            // Groups - Can create but NO publish (drafts only, except community/nucleo)
            'read_apollo_group',
            'create_apollo_groups',

            // Events - Can create but NO publish (pending only)
            'read_eva_event',
            'create_eva_events',

            // Ads - Can create AND publish (published directly)
            'read_apollo_ad',
            'create_apollo_ads',
            'publish_apollo_ads',
        ];

        foreach ($capabilities as $cap) {
            $subscriber->add_cap($cap);
        }
    }

    /**
     * Check if user can publish content directly (without approval)
     */
    public function canPublishDirectly(string $content_type): bool
    {
        $user = \wp_get_current_user();

        // Administrators and editors can always publish directly
        if (in_array('administrator', $user->roles) || in_array('editor', $user->roles)) {
            return true;
        }

        // Check specific publish capability
        $publish_cap = "publish_apollo_{$content_type}s";
        if ($content_type === 'event') {
            $publish_cap = 'publish_eva_events';
        }

        return $user->has_cap($publish_cap);
    }

    /**
     * Get approval workflow status for content creation
     */
    public function getApprovalWorkflow(string $content_type, array $data = []): array
    {
        $user = \wp_get_current_user();

        if (! $user->exists()) {
            return [
                'requires_approval' => true,
                'initial_status'    => 'draft',
                'message'           => 'Usuário não autenticado',
            ];
        }

        // Administrator and Editor - always publish directly
        if (in_array('administrator', $user->roles) || in_array('editor', $user->roles)) {
            return [
                'requires_approval' => false,
                'initial_status'    => 'published',
                'message'           => 'Conteúdo publicado diretamente',
            ];
        }

        // Author rules
        if (in_array('author', $user->roles)) {
            // Authors can publish events directly
            if ($content_type === 'event') {
                return [
                    'requires_approval' => false,
                    'initial_status'    => 'published',
                    'message'           => 'Eventos de autores são publicados diretamente',
                ];
            }

            // Other content requires approval
            return [
                'requires_approval' => true,
                'initial_status'    => 'pending_review',
                'message'           => 'Conteúdo de autor será revisado antes da publicação',
            ];
        }

        // Subscriber rules
        if (in_array('subscriber', $user->roles)) {
            if ($content_type === 'group') {
                $group_type = $data['type'] ?? '';

                // User posts can be published directly
                if (in_array($group_type, [ 'post', 'discussion', 'question' ])) {
                    return [
                        'requires_approval' => false,
                        'initial_status'    => 'published',
                        'message'           => 'Posts de usuários são publicados diretamente',
                    ];
                }

                // Community/Núcleo groups need approval
                if (in_array($group_type, [ 'comunidade', 'nucleo' ])) {
                    return [
                        'requires_approval' => true,
                        'initial_status'    => 'pending_review',
                        'message'           => 'Grupos do tipo Comunidade e Núcleo requerem aprovação',
                    ];
                }

                // Default for other group types - published
                return [
                    'requires_approval' => false,
                    'initial_status'    => 'published',
                    'message'           => 'Grupo publicado diretamente',
                ];
            }//end if

            // Classifieds (ads) - contract & published directly
            if ($content_type === 'ad') {
                return [
                    'requires_approval' => false,
                    'initial_status'    => 'published',
                    'message'           => 'Classificados são publicados diretamente (contrato & publicação)',
                ];
            }

            // Events from subscribers need approval
            if ($content_type === 'event') {
                return [
                    'requires_approval' => true,
                    'initial_status'    => 'pending_review',
                    'message'           => 'Eventos de usuários requerem aprovação',
                ];
            }
        }//end if

        // Contributor - only creates drafts
        if (in_array('contributor', $user->roles)) {
            return [
                'requires_approval' => true,
                'initial_status'    => 'draft',
                'message'           => 'Colaboradores podem apenas criar rascunhos',
            ];
        }

        // Default fallback
        return [
            'requires_approval' => true,
            'initial_status'    => 'pending_review',
            'message'           => 'Seu conteúdo será revisado antes da publicação',
        ];
    }

    /**
     * Add capability if it doesn't exist
     */
    private function addCapabilityIfNotExists(string $capability): void
    {
        // WordPress automatically handles capability registration
        // Just ensure we track them for role assignment
    }

    /**
     * Legacy method for backward compatibility
     */
    public function register()
    {
        $this->registerCapabilities();
    }

    /**
     * Legacy method for backward compatibility
     */
    public function userCan($capability)
    {
        return \current_user_can($capability);
    }

    /**
     * Remove all Apollo capabilities (for uninstall)
     */
    public function removeAllCapabilities(): void
    {
        $roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ];

        $all_caps = [
            // Groups
            'read_apollo_group',
            'read_private_apollo_groups',
            'create_apollo_groups',
            'edit_apollo_groups',
            'edit_others_apollo_groups',
            'edit_private_apollo_groups',
            'publish_apollo_groups',
            'delete_apollo_groups',
            'delete_others_apollo_groups',
            'delete_private_apollo_groups',
            'manage_apollo_group_members',
            'moderate_apollo_group_content',

            // Events
            'read_eva_event',
            'read_private_eva_events',
            'create_eva_events',
            'edit_eva_events',
            'edit_others_eva_events',
            'edit_private_eva_events',
            'publish_eva_events',
            'delete_eva_events',
            'delete_others_eva_events',
            'delete_private_eva_events',
            'manage_eva_event_categories',
            'manage_eva_event_locations',

            // Ads
            'read_apollo_ad',
            'read_private_apollo_ads',
            'create_apollo_ads',
            'edit_apollo_ads',
            'edit_others_apollo_ads',
            'edit_private_apollo_ads',
            'publish_apollo_ads',
            'delete_apollo_ads',
            'delete_others_apollo_ads',
            'delete_private_apollo_ads',
            'moderate_apollo_ads',

            // Moderation
            'apollo_moderate',
            'apollo_moderate_groups',
            'apollo_moderate_events',
            'apollo_moderate_ads',
            'apollo_moderate_users',
            'apollo_moderate_all',
            'apollo_view_mod_queue',
            'apollo_manage_moderators',

            // Analytics
            'apollo_view_analytics',
            'apollo_manage_analytics',
            'apollo_export_analytics',
        ];

        foreach ($roles as $role_name) {
            $role = \get_role($role_name);
            if ($role) {
                foreach ($all_caps as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}
