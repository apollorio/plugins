<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * User Page Renderer
 *
 * Renders user profile pages (/a/{id|login})
 */
class UserPageRenderer {

	/**
	 * Render user profile page
	 */
	public function render( $template_data ) {
		$param = $template_data['param'];
		// user ID or login

		// TODO: Implement real user lookup
		$user_data = $this->getUserData( $param );

		return array(
			'title'       => 'Perfil: ' . $user_data['name'],
			'content'     => $this->renderUserProfile( $user_data ),
			'breadcrumbs' => array( 'Apollo Social', 'Usuários', $user_data['name'] ),
			'user'        => $user_data,
		);
	}

	/**
	 * Get user data (placeholder)
	 */
	private function getUserData( $param ) {
		// Mock data for now
		return array(
			'id'     => is_numeric( $param ) ? $param : 123,
			'login'  => is_numeric( $param ) ? 'user' . $param : $param,
			'name'   => is_numeric( $param ) ? 'Usuário ' . $param : ucfirst( $param ),
			'email'  => is_numeric( $param ) ? 'user' . $param . '@example.com' : $param . '@example.com',
			'joined' => '2025-01-15',
			'groups' => array( 'Comunidade Geral', 'Season 2025' ),
			'badges' => array( 'Novo Membro', 'Participativo' ),
		);
	}

	/**
	 * Render user profile content
	 */
	private function renderUserProfile( $user_data ) {
		ob_start();
		?>
		<div class="apollo-user-profile">
			<div class="user-header">
				<div class="user-avatar">
					<img src="https://via.placeholder.com/150x150?text=<?php echo esc_attr( substr( $user_data['name'], 0, 1 ) ); ?>" 
						alt="Avatar de <?php echo esc_attr( $user_data['name'] ); ?>">
				</div>
				<div class="user-info">
					<h1><?php echo esc_html( $user_data['name'] ); ?></h1>
					<p class="user-login">@<?php echo esc_html( $user_data['login'] ); ?></p>
					<p class="user-joined">Membro desde <?php echo esc_html( $user_data['joined'] ); ?></p>
				</div>
			</div>
			
			<div class="user-content">
				<div class="user-groups">
					<h3>Grupos</h3>
					<ul>
						<?php foreach ( $user_data['groups'] as $group ) : ?>
							<li><?php echo esc_html( $group ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				
				<div class="user-badges">
					<h3>Badges</h3>
					<ul>
						<?php foreach ( $user_data['badges'] as $badge ) : ?>
							<li><?php echo esc_html( $badge ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			
			<!-- TODO: Add more user profile sections -->
		</div>
		<?php
		return ob_get_clean();
	}
}
