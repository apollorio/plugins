<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * Ad Directory Renderer
 */
class AdDirectoryRenderer {

	public function render() {
		$ads = $this->getAdsData();

		return array(
			'title'       => 'Anúncios - Apollo Social',
			'content'     => $this->renderAdDirectory( $ads ),
			'breadcrumbs' => array( 'Apollo Social', 'Anúncios' ),
			'ads'         => $ads,
		);
	}

	private function getAdsData() {
		return array(
			array(
				'id'       => 1,
				'title'    => 'Casa para Venda',
				'slug'     => 'casa-venda-centro',
				'price'    => 'R$ 350.000',
				'category' => 'Imóveis',
				'date'     => '2025-01-15',
			),
			array(
				'id'       => 2,
				'title'    => 'Carro Usado',
				'slug'     => 'carro-usado-2020',
				'price'    => 'R$ 45.000',
				'category' => 'Veículos',
				'date'     => '2025-01-14',
			),
		);
	}

	private function renderAdDirectory( $ads ) {
		ob_start();
		echo '<div class="apollo-ad-directory">';
		echo '<h1>Anúncios</h1>';
		echo '<div class="ad-grid">';

		foreach ( $ads as $ad ) {
			echo '<div class="ad-card">';
			echo '<h3><a href="/anuncio/' . esc_attr( $ad['slug'] ) . '/">' . esc_html( $ad['title'] ) . '</a></h3>';
			echo '<p class="price">' . esc_html( $ad['price'] ) . '</p>';
			echo '<p class="category">' . esc_html( $ad['category'] ) . '</p>';
			echo '<p class="date">' . esc_html( $ad['date'] ) . '</p>';
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';

		return ob_get_clean();
	}
}
