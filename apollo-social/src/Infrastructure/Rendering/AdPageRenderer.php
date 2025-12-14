<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * Ad Page Renderer
 */
class AdPageRenderer
{
    public function render($template_data)
    {
        $slug    = $template_data['param'];
        $ad_data = $this->getAdData($slug);

        return [
            'title'       => $ad_data['title'],
            'content'     => $this->renderAdPage($ad_data),
            'breadcrumbs' => [ 'Apollo Social', 'Anúncios', $ad_data['title'] ],
            'ad'          => $ad_data,
        ];
    }

    private function getAdData($slug)
    {
        return [
            'id'          => 1,
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'description' => 'Descrição detalhada do anúncio ' . $slug,
            'price'       => 'R$ ' . number_format(wp_rand(1000, 100000), 0, ',', '.'),
            'category'    => 'Categoria Principal',
            'contact'     => 'contato@exemplo.com',
            'date'        => '2025-01-15',
        ];
    }

    private function renderAdPage($ad_data)
    {
        ob_start();
        echo '<div class="apollo-ad-single">';
        echo '<h1>' . esc_html($ad_data['title']) . '</h1>';
        echo '<p class="price">' . esc_html($ad_data['price']) . '</p>';
        echo '<p>' . esc_html($ad_data['description']) . '</p>';
        echo '<p>Categoria: ' . esc_html($ad_data['category']) . '</p>';
        echo '<p>Contato: ' . esc_html($ad_data['contact']) . '</p>';
        echo '<p>Publicado em: ' . esc_html($ad_data['date']) . '</p>';
        echo '<!-- TODO: Add image gallery, contact form -->';
        echo '</div>';

        return ob_get_clean();
    }
}
