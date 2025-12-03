<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Users Directory Renderer
 * Renders directory of all registered users
 */
class UsersDirectoryRenderer {

	public function render( $template_data ) {
		// Get all registered users
		$users = $this->getAllUsers();

		return array(
			'title'       => 'Diretório de Usuários',
			'content'     => '',
			'breadcrumbs' => array( 'Apollo Social', 'Usuários' ),
			'data'        => array(
				'users' => $users,
				'total' => count( $users ),
			),
		);
	}

	private function getAllUsers() {
		$args = array(
			'orderby' => 'registered',
			'order'   => 'DESC',
			'number'  => -1, 
		// Get all users
		);

		$users_query = new \WP_User_Query( $args );
		$users       = array();

		foreach ( $users_query->get_results() as $user ) {
			$users[] = array(
				'id'          => $user->ID,
				'login'       => $user->user_login,
				'name'        => $user->display_name,
				'email'       => $user->user_email,
				'avatar'      => get_avatar_url( $user->ID ),
				'registered'  => $user->user_registered,
				'roles'       => $user->roles,
				'bio'         => get_user_meta( $user->ID, 'description', true ),
				'profile_url' => '/id/' . $user->ID,
			);
		}

		return $users;
	}
}
