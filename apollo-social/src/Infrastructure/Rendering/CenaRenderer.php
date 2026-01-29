<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * Cena::rio Renderer - RAW OUTPUT MODE
 *
 * Renders the CENA calendar page with its own complete HTML structure.
 * Uses raw: true to bypass Canvas layout.php wrapper.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */
class CenaRenderer {

	/**
	 * Render the CENA page
	 *
	 * Returns raw HTML content that bypasses Canvas layout wrapper.
	 * The template outputs its own complete HTML structure.
	 *
	 * @param array $template_data Data from router.
	 * @return array Render output with raw flag.
	 */
	public function render( $template_data = array() ) {
		// Security: Only logged-in users can access CENA.
		if ( ! is_user_logged_in() ) {
			wp_die(
				esc_html__( 'Acesso negado. Faça login para continuar.', 'apollo-social' ),
				esc_html__( 'Não autorizado', 'apollo-social' ),
				array( 'response' => 401 )
			);
		}

		// Disable admin bar to prevent render conflicts.
		// Use filter method instead of remove_action to avoid "render() on null" fatal error.
		add_filter( 'show_admin_bar', '__return_false' );

		// Get current user data.
		$current_user = wp_get_current_user();

		// Prepare view data for template.
		$view_data = array(
			'user'       => array(
				'id'       => $current_user->ID,
				'name'     => $current_user->display_name,
				'username' => $current_user->user_login,
				'avatar'   => get_avatar_url( $current_user->ID, array( 'size' => 200 ) ),
			),
			'rest_url'   => rest_url( 'apollo/v1/cena/' ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'today'      => wp_date( 'Y-m-d' ),
		);

		// Capture template output.
		ob_start();
		$this->renderTemplate( $view_data );
		$content = ob_get_clean();

		// Return with raw flag to bypass Canvas layout wrapper.
		return array(
			'raw'     => true,
			'content' => $content,
		);
	}

	/**
	 * Render the template with view data
	 *
	 * @param array $view Data available to template.
	 */
	private function renderTemplate( $view ) {
		$template_dir = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/cena/';
		$template     = $template_dir . 'cena.php';

		if ( file_exists( $template ) ) {
			// Make $view available to template.
			include $template;
		} else {
			// Fallback error.
			echo '<div style="padding:40px;text-align:center;">';
			echo '<h1>CENA Template Not Found</h1>';
			echo '<p>Template file missing: ' . esc_html( $template ) . '</p>';
			echo '</div>';
		}
	}
}
