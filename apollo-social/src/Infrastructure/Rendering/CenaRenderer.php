<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Cena::rio Renderer
 * Renders the Cena::rio page based on CodePen design
 */
class CenaRenderer {

	public function render( $template_data ) {
		// Get current user
		$current_user = wp_get_current_user();

		// Get cena data (events, communities, etc.)
		$cena_data = $this->getCenaData();

		return array(
			'title'                       => 'Cena::rio',
			'content'                     => '', 
			// Rendered by template
							'breadcrumbs' => array( 'Apollo Social', 'Cena::rio' ),
			'data'                        => array(
				'user' => array(
					'id'     => $current_user->ID,
					'name'   => $current_user->display_name,
					'avatar' => get_avatar_url( $current_user->ID, array( 'size' => 200 ) ),
				),
				'cena' => $cena_data,
			),
		);
	}

	/**
	 * Get Cena data
	 */
	private function getCenaData() {
		// Get events, communities, etc.
		// This will be populated with actual data

		return array(
			'events'      => array(),
			'communities' => array(),
			'nucleos'     => array(),
		);
	}
}
