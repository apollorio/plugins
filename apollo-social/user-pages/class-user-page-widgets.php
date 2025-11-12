<?php
/**
 * Registro e renderização dos widgets base
 */
class Apollo_User_Page_Widgets {
    public static function get_widgets() {
        $widgets = [
            'about' => [
                'title' => 'Sobre',
                'icon' => 'user',
                'propsSchema' => [ 'bio' => 'string', 'avatar' => 'string' ],
                'render' => function($props, $ctx) {
                    return '<div class="card bg-white rounded-lg p-3 mb-2">'
                        . '<img src="' . esc_url($props['avatar']) . '" class="w-16 h-16 rounded-full mb-2">'
                        . '<div class="font-bold">' . esc_html($ctx['display_name']) . '</div>'
                        . '<div class="text-sm text-gray-600">' . esc_html($props['bio']) . '</div>'
                        . '</div>';
                }
            ],
            'depoimentos' => [
                'title' => 'Depoimentos',
                'icon' => 'comments',
                'propsSchema' => [],
                'render' => function($props, $ctx) {
                    return '<section class="mt-4">'
                        . '<h2 class="text-lg font-bold mb-2">Depoimentos</h2>'
                        . get_comments_number($ctx['post_id']) . ' depoimentos.'
                        . '</section>';
                }
            ],
            'image' => [
                'title' => 'Imagem',
                'icon' => 'image',
                'propsSchema' => [ 'src' => 'string', 'alt' => 'string' ],
                'render' => function($props, $ctx) {
                    return '<img src="' . esc_url($props['src']) . '" alt="' . esc_attr($props['alt']) . '" class="rounded-lg mb-2">';
                }
            ]
        ];
        return apply_filters('apollo_userpage_widgets', $widgets);
    }
}
