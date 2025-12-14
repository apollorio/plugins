<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * Chat Single Renderer
 * Renders chat page with specific user
 */
class ChatSingleRenderer
{
    public function render($template_data)
    {
        $current_user = wp_get_current_user();
        $user_id      = isset($template_data['user_id']) ? absint($template_data['user_id']) : 0;

        if (! $user_id) {
            return [
                'title'       => 'Chat - Usuário não encontrado',
                'content'     => '<p>Usuário não encontrado.</p>',
                'breadcrumbs' => [ 'Apollo Social', 'Chat' ],
                'data'        => [],
            ];
        }

        $target_user = get_user_by('ID', $user_id);

        if (! $target_user) {
            return [
                'title'       => 'Chat - Usuário não encontrado',
                'content'     => '<p>Usuário não encontrado.</p>',
                'breadcrumbs' => [ 'Apollo Social', 'Chat' ],
                'data'        => [],
            ];
        }

        // Get messages between users
        $messages = $this->getMessages();

        return [
            'title'       => 'Chat com ' . $target_user->display_name,
            'content'     => '',
            'breadcrumbs' => [ 'Apollo Social', 'Chat', $target_user->display_name ],
            'data'        => [
                'messages'     => $messages,
                'current_user' => [
                    'id'     => $current_user->ID,
                    'name'   => $current_user->display_name,
                    'avatar' => get_avatar_url($current_user->ID),
                ],
                'target_user' => [
                    'id'     => $target_user->ID,
                    'name'   => $target_user->display_name,
                    'avatar' => get_avatar_url($target_user->ID),
                ],
            ],
        ];
    }

    private function getMessages()
    {
        // TODO: Implement message retrieval logic
        // This is a placeholder - implement based on your chat system
        return [];
    }
}
