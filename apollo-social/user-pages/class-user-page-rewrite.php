<?php
/**
 * Regras de rewrite para user pages:
 * - /id/{userID} - Public user page
 * - /meu-perfil/ - Private profile dashboard (logged-in user)
 */
class Apollo_User_Page_Rewrite {
	public static function add_rewrite() {
		// Public user page: /id/{user_id}/
		add_rewrite_rule( '^id/(\d+)/(?:$|\?.*)', 'index.php?apollo_user_id=$matches[1]', 'top' );
		add_rewrite_tag( '%apollo_user_id%', '([0-9]+)' );

		// Private profile dashboard: /meu-perfil/
		add_rewrite_rule( '^meu-perfil/?$', 'index.php?apollo_private_profile=1', 'top' );
		add_rewrite_tag( '%apollo_private_profile%', '([0-1]+)' );
	}
}
add_action( 'init', [ 'Apollo_User_Page_Rewrite', 'add_rewrite' ] );
