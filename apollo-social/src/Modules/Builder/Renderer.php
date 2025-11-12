<?php

namespace Apollo\Modules\Builder;

use SiteOrigin_Panels_Renderer;

/**
 * Responsible for rendering SiteOrigin layouts stored in user meta.
 */
class Renderer
{
    private LayoutRepository $repository;

    public function __construct(LayoutRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Render layout for a specific user.
     */
    public function renderForUser(int $userId, array $args = []): string
    {
        if (!class_exists(SiteOrigin_Panels_Renderer::class)) {
            return $this->dependencyMissingNotice();
        }

        $layout = $this->repository->getLayout($userId);

        if (empty($layout['widgets'])) {
            return $this->emptyState();
        }

        if (empty($layout['grids']) || empty($layout['grid_cells'])) {
            return $this->renderAbsoluteLayout($layout);
        }

        /**
         * The renderer expects the same structure as `panels_data`.
         * We fake a "post id" cache key using the user id so caching still works.
         */
        $cacheKey = sprintf('apollo-user-%d', $userId);
        $renderer = SiteOrigin_Panels_Renderer::single();

        return $renderer->render($cacheKey, $layout, $args);
    }

    public function emptyState(): string
    {
        ob_start();
        ?>
        <div class="apollo-builder-empty">
            <div class="apollo-card">
                <h3 class="apollo-card__title">Nenhum bloco configurado</h3>
                <p class="apollo-card__description">
                    Comece adicionando um widget no construtor visual Habbo e arraste-o para a cena.
                </p>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function dependencyMissingNotice(): string
    {
        return '<div class="apollo-alert apollo-alert--warning">' .
            esc_html__('SiteOrigin Page Builder não está ativo. Ative o plugin para renderizar perfis.', 'apollo-social') .
            '</div>';
    }

    private function renderAbsoluteLayout(array $layout): string
    {
        $output = '<div class="apollo-profile-canvas" style="position:relative;min-height:420px;">';

        foreach ($layout['widgets'] as $widget) {
            if (empty($widget['id_base'])) {
                continue;
            }

            $position = $widget['position'] ?? [];
            $style = sprintf(
                'left:%spx;top:%spx;width:%spx;height:%spx;position:absolute;z-index:%s;',
                isset($position['x']) ? (float) $position['x'] : 0,
                isset($position['y']) ? (float) $position['y'] : 0,
                isset($position['width']) ? (float) $position['width'] : 260,
                isset($position['height']) ? (float) $position['height'] : 180,
                isset($position['z']) ? (int) $position['z'] : 10
            );

            $output .= '<div class="apollo-widget-instance" style="' . esc_attr($style) . '">';
            $output .= $this->renderWidgetManually($widget['id_base'], $widget['settings'] ?? []);
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    private function renderWidgetManually(string $idBase, array $settings): string
    {
        switch ($idBase) {
            case 'apollo_sticky_note':
                $title = $settings['title'] ?? __('Nota', 'apollo-social');
                $content = $settings['content'] ?? '';
                $color = $settings['color'] ?? '#fef3c7';

                return sprintf(
                    '<div class="apollo-sticky-note" style="background:%s;"><header class="apollo-sticky-note__header"><span class="apollo-sticky-note__pin"></span><h3 class="apollo-sticky-note__title">%s</h3></header><div class="apollo-sticky-note__content">%s</div></div>',
                    esc_attr($color),
                    esc_html($title),
                    wpautop(wp_kses_post($content))
                );
            default:
                ob_start();
                the_widget($idBase, $settings, ['before_widget' => '', 'after_widget' => '']);
                return (string) ob_get_clean();
        }
    }
}

