<?php

/**
 * Registro e renderização dos widgets base
 */
class Apollo_User_Page_Widgets
{
    public static function get_widgets()
    {
        $widgets = [
            'about' => [
                'title'       => 'Sobre',
                'icon'        => 'user',
                'propsSchema' => [
                    'bio'    => 'string',
                    'avatar' => 'string',
                ],
                'render' => function ($props, $ctx) {
                    return '<div class="card bg-white rounded-lg p-3 mb-2">'
                        . '<img src="' . esc_url($props['avatar']) . '" class="w-16 h-16 rounded-full mb-2">'
                        . '<div class="font-bold">' . esc_html($ctx['display_name']) . '</div>'
                        . '<div class="text-sm text-gray-600">' . esc_html($props['bio']) . '</div>'
                        . '</div>';
                },
            ],
            'depoimentos' => [
                'title'       => 'Depoimentos',
                'icon'        => 'comments',
                'propsSchema' => [],
                'render'      => function ($props, $ctx) {
                    $post_id  = $ctx['post_id'] ?? 0;
                    $comments = get_comments(
                        [
                            'post_id' => $post_id,
                            'status'  => 'approve',
                            'number'  => 10,
                            'orderby' => 'comment_date',
                            'order'   => 'DESC',
                        ]
                    );

                    $comments_count = get_comments_number($post_id);

                    // ShadCN Card component structure
                    $html = '<div class="apollo-depoimentos-widget" data-motion-widget="depoimentos">';
                    $html .= '<div class="shadcn-card rounded-lg border bg-card text-card-foreground shadow-sm">';
                    $html .= '<div class="shadcn-card-header flex flex-row items-center justify-between space-y-0 pb-2">';
                    $html .= '<h3 class="shadcn-card-title text-lg font-semibold leading-none tracking-tight flex items-center gap-2">';
                    $html .= '<i class="ri-chat-3-line"></i>';
                    $html .= '<span>Depoimentos</span>';
                    $html .= '</h3>';
                    $html .= '<span class="text-sm text-muted-foreground">' . esc_html($comments_count) . '</span>';
                    $html .= '</div>';

                    if ($comments_count > 0) {
                        $html .= '<div class="shadcn-card-content">';
                        $html .= '<div class="apollo-depoimentos-list space-y-4" data-motion-list="true">';

                        foreach ($comments as $index => $comment) {
                            $avatar = get_avatar_url($comment->user_id ?: $comment->comment_author_email, [ 'size' => 48 ]);
                            $author = $comment->comment_author ?: 'Anônimo';
                            $date   = human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' atrás';

                            // ShadCN Card for each depoimento with Motion.dev data attributes
                            $html .= '<div class="apollo-depoimento-item shadcn-card rounded-lg border bg-card p-4" ';
                            $html .= 'data-motion-item="true" ';
                            $html .= 'data-motion-delay="' . ($index * 50) . '" ';
                            $html .= 'style="opacity: 0; transform: translateY(10px);">';

                            $html .= '<div class="flex items-start gap-3">';
                            $html .= '<div class="shadcn-avatar">';
                            $html .= '<div class="relative h-10 w-10 overflow-hidden rounded-full">';
                            $html .= '<img src="' . esc_url($avatar) . '" alt="' . esc_attr($author) . '" class="h-full w-full object-cover">';
                            $html .= '</div>';
                            $html .= '</div>';

                            $html .= '<div class="flex-1 space-y-1">';
                            $html .= '<div class="flex items-center justify-between">';
                            $html .= '<p class="text-sm font-medium leading-none">' . esc_html($author) . '</p>';
                            $html .= '<p class="text-xs text-muted-foreground">' . esc_html($date) . '</p>';
                            $html .= '</div>';
                            $html .= '<p class="text-sm text-muted-foreground">' . wp_kses_post($comment->comment_content) . '</p>';
                            $html .= '</div>';

                            $html .= '</div>';
                            $html .= '</div>';
                        }//end foreach

                        $html .= '</div>';
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="shadcn-card-content">';
                        $html .= '<div class="flex flex-col items-center justify-center py-8 text-center">';
                        $html .= '<i class="ri-chat-3-line text-4xl text-muted-foreground mb-2"></i>';
                        $html .= '<p class="text-sm text-muted-foreground">Nenhum depoimento ainda.</p>';
                        $html .= '<p class="text-xs text-muted-foreground mt-1">Seja o primeiro a deixar um depoimento!</p>';
                        $html .= '</div>';
                        $html .= '</div>';
                    }//end if

                    $html .= '</div>';
                    $html .= '</div>';

                    // Motion.dev initialization script
                    $html .= '<script>';
                    $html .= '(function() {';
                    $html .= 'if (typeof window.motion !== "undefined") {';
                    $html .= 'const items = document.querySelectorAll(\'[data-motion-item="true"]\');';
                    $html .= 'items.forEach(function(item, index) {';
                    $html .= 'const delay = parseInt(item.dataset.motionDelay || 0);';
                    $html .= 'setTimeout(function() {';
                    $html .= 'window.motion.animate(item, {';
                    $html .= 'opacity: [0, 1],';
                    $html .= 'y: [10, 0]';
                    $html .= '}, {';
                    $html .= 'duration: 0.4,';
                    $html .= 'easing: "ease-out"';
                    $html .= '}).then(function() {';
                    $html .= 'item.style.opacity = "1";';
                    $html .= 'item.style.transform = "translateY(0)";';
                    $html .= '});';
                    $html .= '}, delay);';
                    $html .= '});';
                    $html .= '}';
                    $html .= '})();';
                    $html .= '</script>';

                    return $html;
                },
            ],
            'image' => [
                'title'       => 'Imagem',
                'icon'        => 'image',
                'propsSchema' => [
                    'src' => 'string',
                    'alt' => 'string',
                ],
                'render' => function ($props, $ctx) {
                    return '<img src="' . esc_url($props['src']) . '" alt="' . esc_attr($props['alt']) . '" class="rounded-lg mb-2">';
                },
            ],
        ];

        return apply_filters('apollo_userpage_widgets', $widgets);
    }
}
