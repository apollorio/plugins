<?php

/**
 * Widget Canvas Plano - Reuso do editor Fabric.js
 * Abre modal do editor, gera imagem e insere no layout
 */
class Apollo_User_Page_Widget_Canvas
{
    public static function register()
    {
        add_filter('apollo_userpage_widgets', [ __CLASS__, 'add_widget' ]);
    }

    public static function add_widget($widgets)
    {
        $widgets['canvas_plano'] = [
            'title'       => 'Canvas Plano',
            'icon'        => 'image-edit',
            'propsSchema' => [
                'image_id'  => 'integer',
                'image_url' => 'string',
            ],
            'render' => function ($props, $ctx) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
                unset( $ctx ); // Mark as intentionally unused.
                $image_url = $props['image_url'] ?? '';
                if (! $image_url && ! empty($props['image_id'])) {
                    $image_url = wp_get_attachment_image_url($props['image_id'], 'large');
                }

                $is_editing = isset($_GET['action']) && $_GET['action'] === 'edit'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $edit_url = home_url('/studio/');

                if (! $image_url) {
                    $output = '<div class="card p-4 bg-card border rounded-lg text-center text-muted-foreground">';
                    $output .= '<p>' . esc_html__('Imagem do Canvas não configurada', 'apollo-social') . '</p>';
                    if ($is_editing) {
                        $output .= '<a href="' . esc_url($edit_url) . '" class="btn btn-primary mt-2 inline-flex items-center gap-2">';
                        $output .= '<i class="ri-edit-line"></i> ';
                        $output .= esc_html__('Criar Canvas', 'apollo-social');
                        $output .= '</a>';
                    }
                    $output .= '</div>';
                    return $output;
                }

                $output = '<div class="card bg-card border rounded-lg overflow-hidden relative">';
                $output .= '<img src="' . esc_url($image_url) . '" alt="Canvas Plano" class="w-full h-auto">';
                if ($is_editing) {
                    $output .= '<div class="absolute top-2 right-2">';
                    $output .= '<a href="' . esc_url($edit_url) . '" class="btn btn-sm btn-primary inline-flex items-center gap-1" title="' . esc_attr__('Editar Canvas', 'apollo-social') . '">';
                    $output .= '<i class="ri-edit-line"></i>';
                    $output .= '</a>';
                    $output .= '</div>';
                }
                $output .= '</div>';
                return $output;
            },
        ];

        return $widgets;
    }
}
Apollo_User_Page_Widget_Canvas::register();
