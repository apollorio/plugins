<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * User Profile Renderer
 * Renders user profile page by ID
 */
class UserProfileRenderer {

	public function render( $template_data ) {
		$user_id = isset( $template_data['user_id'] ) ? absint( $template_data['user_id'] ) : 0;

		if ( ! $user_id ) {
			return array(
				'title'       => 'Perfil - Usuário não encontrado',
				'content'     => '<p>Usuário não encontrado.</p>',
				'breadcrumbs' => array( 'Apollo Social', 'Perfil' ),
				'data'        => array(),
			);
		}

		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return array(
				'title'       => 'Perfil - Usuário não encontrado',
				'content'     => '<p>Usuário não encontrado.</p>',
				'breadcrumbs' => array( 'Apollo Social', 'Perfil' ),
				'data'        => array(),
			);
		}

		// Get user data
		$user_data = $this->getUserData( $user );

		return array(
			'title'       => 'Perfil de ' . $user->display_name,
			'content'     => '',
			'breadcrumbs' => array( 'Apollo Social', 'Perfil', $user->display_name ),
			'data'        => array(
				'user' => $user_data,
			),
		);
	}

	private function getUserData( $user ) {
		return array(
			'id'         => $user->ID,
			'login'      => $user->user_login,
			'name'       => $user->display_name,
			'email'      => $user->user_email,
			'avatar'     => get_avatar_url( $user->ID ),
			'registered' => $user->user_registered,
			'roles'      => $user->roles,
			'bio'        => get_user_meta( $user->ID, 'description', true ),
		);
	}
}
