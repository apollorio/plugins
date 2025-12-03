<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Union Directory Renderer
 */
class UnionDirectoryRenderer {

	public function render( $template_data ) {
		$unions = $this->getUnionsData();

		return array(
			'title'       => 'União - Apollo Social',
			'content'     => $this->renderUnionDirectory( $unions ),
			'breadcrumbs' => array( 'Apollo Social', 'União' ),
			'unions'      => $unions,
		);
	}

	private function getUnionsData() {
		return array(
			array(
				'id'            => 1,
				'name'          => 'União dos Desenvolvedores',
				'slug'          => 'desenvolvedores',
				'members_count' => 150,
				'description'   => 'União para profissionais de desenvolvimento',
			),
			array(
				'id'            => 2,
				'name'          => 'União dos Designers',
				'slug'          => 'designers',
				'members_count' => 89,
				'description'   => 'União para profissionais de design',
			),
		);
	}

	private function renderUnionDirectory( $unions ) {
		ob_start();
		echo '<div class="apollo-union-directory">';
		echo '<h1>União</h1>';
		echo '<div class="union-grid">';

		foreach ( $unions as $union ) {
			echo '<div class="union-card">';
			echo '<h3><a href="/uniao/' . esc_attr( $union['slug'] ) . '/">' . esc_html( $union['name'] ) . '</a></h3>';
			echo '<p>' . esc_html( $union['description'] ) . '</p>';
			echo '<p>Membros: ' . intval( $union['members_count'] ) . '</p>';
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
		return ob_get_clean();
	}
}
