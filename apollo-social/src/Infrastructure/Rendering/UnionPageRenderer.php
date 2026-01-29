<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * Union Page Renderer
 */
class UnionPageRenderer {

	public function render( $template_data ) {
		$slug       = $template_data['param'];
		$union_data = $this->getUnionData( $slug );

		return array(
			'title'       => $union_data['name'],
			'content'     => $this->renderUnionPage( $union_data ),
			'breadcrumbs' => array( 'Apollo Social', 'União', $union_data['name'] ),
			'union'       => $union_data,
		);
	}

	private function getUnionData( $slug ) {
		return array(
			'id'            => 1,
			'name'          => 'União ' . ucfirst( str_replace( '-', ' ', $slug ) ),
			'slug'          => $slug,
			'description'   => 'Descrição detalhada da união ' . $slug,
			'members_count' => wp_rand( 50, 200 ),
			'created'       => '2025-01-01',
			'leader'        => 'Líder Principal',
		);
	}

	private function renderUnionPage( $union_data ) {
		ob_start();
		echo '<div class="apollo-union-single">';
		echo '<h1>' . esc_html( $union_data['name'] ) . '</h1>';
		echo '<p>' . esc_html( $union_data['description'] ) . '</p>';
		echo '<p>Membros: ' . intval( $union_data['members_count'] ) . '</p>';
		echo '<p>Líder: ' . esc_html( $union_data['leader'] ) . '</p>';
		echo '<p>Criada em: ' . esc_html( $union_data['created'] ) . '</p>';
		echo '<!-- TODO: Add union content, members, events -->';
		echo '</div>';

		return ob_get_clean();
	}
}
