<?php
namespace Apollo\Infrastructure\Rendering;

use Apollo\Infrastructure\Dashboard\DashboardBuilder;

/**
 * User Dashboard Renderer
 * Renders customizable user dashboard page
 */
class UserDashboardRenderer
{
    public function render($template_data)
    {
        // Check if this is /painel/ (own dashboard) or /id/{userID} (public profile)
        $is_painel = (get_query_var('apollo_route') === 'user_dashboard' && !isset($template_data['user_id']));
        
        if ($is_painel) {
            // Own dashboard - show tabs and full functionality
            return $this->renderOwnDashboard($template_data);
        } else {
            // Public profile - show customizable profile page
            return $this->renderPublicProfile($template_data);
        }
    }

    /**
     * Render own dashboard (/painel/)
     */
    private function renderOwnDashboard($template_data)
    {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die('Você precisa estar logado para acessar esta página.', 'Acesso Negado', ['response' => 403]);
        }

        $user = get_user_by('ID', $user_id);
        
        // Get dashboard tabs data
        $tabs_data = $this->getDashboardTabsData($user_id);
        
        // Use specific template for /painel/
        $template_path = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/users/dashboard-painel.php';
        
        return [
            'title' => 'Painel - ' . $user->display_name,
            'content' => '', // Rendered by template
            'breadcrumbs' => ['Apollo Social', 'Painel'],
            'template' => 'users/dashboard-painel.php', // Specific template
            'data' => [
                'user' => [
                    'id' => $user->ID,
                    'login' => $user->user_login,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'avatar' => get_avatar_url($user->ID, ['size' => 200]),
                    'registered' => $user->user_registered,
                    'bio' => get_user_meta($user->ID, 'description', true),
                ],
                'tabs' => $tabs_data,
                'is_own_dashboard' => true,
            ],
        ];
    }

    /**
     * Render public profile (/id/{userID} or /clubber/{userID})
     */
    private function renderPublicProfile($template_data)
    {
        $user_id = isset($template_data['user_id']) ? absint($template_data['user_id']) : get_current_user_id();
        $current_user_id = get_current_user_id();
        $is_own_profile = ($user_id === $current_user_id);
        
        if (!$user_id) {
            return [
                'title' => 'Perfil - Usuário não encontrado',
                'content' => '<p>Usuário não encontrado.</p>',
                'breadcrumbs' => ['Apollo Social', 'Perfil'],
                'data' => [],
            ];
        }

        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return [
                'title' => 'Perfil - Usuário não encontrado',
                'content' => '<p>Usuário não encontrado.</p>',
                'breadcrumbs' => ['Apollo Social', 'Perfil'],
                'data' => [],
            ];
        }

        // Get user page
        $user_page = apollo_get_or_create_user_page($user_id);
        
        // Get widgets layout
        $widgets = get_post_meta($user_page->ID, '_apollo_widgets', true);
        if (!is_array($widgets)) {
            $widgets = [];
        }

        // Get depoimentos (comments)
        $depoimentos = $this->getDepoimentos($user_id);
        
        return [
            'title' => 'Perfil de ' . $user->display_name,
            'content' => '', // Rendered by template
            'breadcrumbs' => ['Apollo Social', 'Perfil', $user->display_name],
            'data' => [
                'user' => [
                    'id' => $user->ID,
                    'login' => $user->user_login,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'avatar' => get_avatar_url($user->ID, ['size' => 200]),
                    'registered' => $user->user_registered,
                    'bio' => get_user_meta($user->ID, 'description', true),
                ],
                'widgets' => $widgets,
                'depoimentos' => $depoimentos,
                'can_edit' => $is_own_profile || current_user_can('edit_post', $user_page->ID),
                'page_id' => $user_page->ID,
                'is_own_profile' => $is_own_profile,
            ],
        ];
    }

    /**
     * Get dashboard tabs data
     */
    private function getDashboardTabsData($user_id)
    {
        return [
            'events' => [
                'title' => 'Eventos favoritos',
                'icon' => 'ri-heart-3-line',
                'data' => $this->getFavoriteEvents($user_id),
            ],
            'metrics' => [
                'title' => 'Meus números',
                'icon' => 'ri-bar-chart-2-line',
                'data' => $this->getUserMetrics($user_id),
            ],
            'nucleo' => [
                'title' => 'Núcleo (privado)',
                'icon' => 'ri-lock-2-line',
                'data' => $this->getNucleos($user_id),
            ],
            'communities' => [
                'title' => 'Comunidades',
                'icon' => 'ri-community-line',
                'data' => $this->getCommunities($user_id),
            ],
            'docs' => [
                'title' => 'Documentos',
                'icon' => 'ri-file-text-line',
                'data' => $this->getDocuments($user_id),
            ],
        ];
    }

    /**
     * Get favorite events
     */
    private function getFavoriteEvents($user_id)
    {
        $user_id = absint($user_id);
        
        // Get events from apollo-events-manager if available
        if (function_exists('apollo_get_user_favorite_events')) {
            return apollo_get_user_favorite_events($user_id);
        }
        
        // Fallback: get from user meta
        $event_ids = get_user_meta($user_id, 'apollo_favorite_events', true);
        
        if (!is_array($event_ids) || empty($event_ids)) {
            return [];
        }
        
        $events = [];
        foreach ($event_ids as $event_id) {
            $event_id = absint($event_id);
            if ($event_id) {
                $post = get_post($event_id);
                if ($post && $post->post_status === 'publish') {
                    $events[] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'permalink' => get_permalink($post->ID),
                        'date' => get_post_meta($post->ID, '_event_start_date', true),
                    ];
                }
            }
        }
        
        return $events;
    }

    /**
     * Get user metrics
     */
    private function getUserMetrics($user_id)
    {
        $user_id = absint($user_id);
        
        $user_page = apollo_get_user_page($user_id);
        $page_id = $user_page ? $user_page->ID : 0;
        
        return [
            'posts' => count_user_posts($user_id),
            'comments' => $page_id ? get_comments_number($page_id) : 0,
            'favorites' => count($this->getFavoriteEvents($user_id)),
            'communities' => count($this->getCommunities($user_id)),
            'nucleos' => count($this->getNucleos($user_id)),
        ];
    }

    /**
     * Get nucleos
     */
    private function getNucleos($user_id)
    {
        $user_id = absint($user_id);
        
        // Get from groups system if available
        if (function_exists('apollo_get_user_nucleos')) {
            return apollo_get_user_nucleos($user_id);
        }
        
        // Fallback: get from user meta
        $nucleo_ids = get_user_meta($user_id, 'apollo_nucleos', true);
        
        if (!is_array($nucleo_ids) || empty($nucleo_ids)) {
            return [];
        }
        
        $nucleos = [];
        foreach ($nucleo_ids as $nucleo_id) {
            $nucleo_id = absint($nucleo_id);
            if ($nucleo_id) {
                $post = get_post($nucleo_id);
                if ($post && $post->post_status === 'publish') {
                    $nucleos[] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'slug' => $post->post_name,
                        'description' => get_post_meta($post->ID, '_apollo_group_description', true),
                    ];
                }
            }
        }
        
        return $nucleos;
    }

    /**
     * Get communities
     */
    private function getCommunities($user_id)
    {
        $user_id = absint($user_id);
        
        // Get from groups system if available
        if (function_exists('apollo_get_user_communities')) {
            return apollo_get_user_communities($user_id);
        }
        
        // Fallback: get from user meta
        $community_ids = get_user_meta($user_id, 'apollo_communities', true);
        
        if (!is_array($community_ids) || empty($community_ids)) {
            return [];
        }
        
        $communities = [];
        foreach ($community_ids as $community_id) {
            $community_id = absint($community_id);
            if ($community_id) {
                $post = get_post($community_id);
                if ($post && $post->post_status === 'publish') {
                    $communities[] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'slug' => $post->post_name,
                        'description' => get_post_meta($post->ID, '_apollo_group_description', true),
                    ];
                }
            }
        }
        
        return $communities;
    }

    /**
     * Get documents
     */
    private function getDocuments($user_id)
    {
        $user_id = absint($user_id);
        
        // Get from documents system if available
        if (function_exists('apollo_get_user_documents')) {
            return apollo_get_user_documents($user_id);
        }
        
        // Fallback: get from user meta
        $doc_ids = get_user_meta($user_id, 'apollo_documents', true);
        
        if (!is_array($doc_ids) || empty($doc_ids)) {
            return [];
        }
        
        $documents = [];
        foreach ($doc_ids as $doc_id) {
            $doc_id = absint($doc_id);
            if ($doc_id) {
                $post = get_post($doc_id);
                if ($post && $post->post_status === 'publish') {
                    $documents[] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'status' => get_post_meta($post->ID, '_apollo_doc_status', true) ?: 'draft',
                        'updated' => $post->post_modified,
                    ];
                }
            }
        }
        
        return $documents;
    }

    /**
     * Get depoimentos (comments) for user
     */
    private function getDepoimentos($user_id)
    {
        $user_page = apollo_get_user_page($user_id);
        
        if (!$user_page) {
            return [];
        }

        $args = [
            'post_id' => $user_page->ID,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'DESC',
            'number' => 50,
        ];

        $comments = get_comments($args);
        $depoimentos = [];

        foreach ($comments as $comment) {
            $depoimentos[] = [
                'id' => $comment->comment_ID,
                'author' => [
                    'id' => $comment->user_id,
                    'name' => $comment->comment_author,
                    'avatar' => get_avatar_url($comment->user_id ? $comment->user_id : $comment->comment_author_email),
                ],
                'content' => $comment->comment_content,
                'date' => $comment->comment_date,
                'date_formatted' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' atrás',
            ];
        }

        return $depoimentos;
    }
}

