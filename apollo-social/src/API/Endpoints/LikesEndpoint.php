<?php
/**
 * FASE 2: Likes Endpoint
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

if (!defined('ABSPATH')) exit;

class LikesEndpoint
{
    /**
     * Register REST routes
     */
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register REST API routes
     */
    public function registerRoutes(): void
    {
        register_rest_route('apollo/v1', '/like', [
            'methods' => 'POST',
            'callback' => [$this, 'toggleLike'],
            'permission_callback' => [$this, 'checkPermission'],
        ]);

        register_rest_route('apollo/v1', '/like/(?P<content_type>[a-zA-Z0-9_-]+)/(?P<content_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getLikeStatus'],
            'permission_callback' => '__return_true', // Público, mas retorna false se não logado
        ]);
    }

    /**
     * Check if user can like
     */
    public function checkPermission(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Toggle like (adicionar ou remover)
     */
    public function toggleLike(\WP_REST_Request $request)
    {
        $content_type = sanitize_text_field($request->get_param('content_type'));
        $content_id = absint($request->get_param('content_id'));
        $user_id = get_current_user_id();

        if (!$content_type || !$content_id || !$user_id) {
            return new \WP_Error(
                'invalid_params',
                __('Parâmetros inválidos.', 'apollo-social'),
                ['status' => 400]
            );
        }

        // Validar content_type
        $allowed_types = ['apollo_social_post', 'event_listing', 'post', 'apollo_ad'];
        if (!in_array($content_type, $allowed_types)) {
            return new \WP_Error(
                'invalid_content_type',
                __('Tipo de conteúdo inválido.', 'apollo-social'),
                ['status' => 400]
            );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'apollo_likes';

        // Verificar se já curtiu
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE content_type = %s AND content_id = %d AND user_id = %d",
            $content_type,
            $content_id,
            $user_id
        ));

        if ($existing) {
            // Remover like
            $deleted = $wpdb->delete(
                $table_name,
                [
                    'content_type' => $content_type,
                    'content_id' => $content_id,
                    'user_id' => $user_id,
                ],
                ['%s', '%d', '%d']
            );

            if ($deleted) {
                // Atualizar meta cache
                $this->updateLikeCountMeta($content_type, $content_id);
                
                return new \WP_REST_Response([
                    'success' => true,
                    'liked' => false,
                    'like_count' => $this->getLikeCount($content_type, $content_id),
                ], 200);
            }
        } else {
            // Adicionar like
            $inserted = $wpdb->insert(
                $table_name,
                [
                    'content_type' => $content_type,
                    'content_id' => $content_id,
                    'user_id' => $user_id,
                    'liked_at' => current_time('mysql'),
                ],
                ['%s', '%d', '%d', '%s']
            );

            if ($inserted) {
                // Atualizar meta cache
                $this->updateLikeCountMeta($content_type, $content_id);
                
                return new \WP_REST_Response([
                    'success' => true,
                    'liked' => true,
                    'like_count' => $this->getLikeCount($content_type, $content_id),
                ], 200);
            }
        }

        return new \WP_Error(
            'database_error',
            __('Erro ao processar like.', 'apollo-social'),
            ['status' => 500]
        );
    }

    /**
     * Obter status de like
     */
    public function getLikeStatus(\WP_REST_Request $request)
    {
        $content_type = sanitize_text_field($request->get_param('content_type'));
        $content_id = absint($request->get_param('content_id'));
        $user_id = get_current_user_id();

        $like_count = $this->getLikeCount($content_type, $content_id);
        $user_liked = $user_id ? $this->userLiked($content_type, $content_id, $user_id) : false;

        return new \WP_REST_Response([
            'like_count' => $like_count,
            'user_liked' => $user_liked,
        ], 200);
    }

    /**
     * Obter contagem de likes
     */
    private function getLikeCount($content_type, $content_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'apollo_likes';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE content_type = %s AND content_id = %d",
            $content_type,
            $content_id
        ));
    }

    /**
     * Verificar se usuário curtiu
     */
    private function userLiked($content_type, $content_id, $user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'apollo_likes';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE content_type = %s AND content_id = %d AND user_id = %d",
            $content_type,
            $content_id,
            $user_id
        ));

        return (int) $count > 0;
    }

    /**
     * Atualizar meta cache de contagem
     */
    private function updateLikeCountMeta($content_type, $content_id)
    {
        $like_count = $this->getLikeCount($content_type, $content_id);
        
        if ($content_type === 'apollo_social_post' || $content_type === 'event_listing' || $content_type === 'post') {
            update_post_meta($content_id, '_apollo_like_count', $like_count);
        }
    }
}

