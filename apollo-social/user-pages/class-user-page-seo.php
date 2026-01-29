<?php

/**
 * SEO e metas para páginas de usuário
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */
class Apollo_User_Page_SEO {

	/**
	 * Add SEO meta tags to user pages
	 *
	 * @return void
	 */
	public static function add_meta() {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$user_id = get_query_var( 'apollo_user_id' );
		if ( $user_id ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			if ( $action === 'edit' ) {
				echo '<meta name="robots" content="noindex, nofollow">';
				echo '<link rel="canonical" href="' . esc_url( home_url( '/id/' . $user_id ) ) . '">';
			} else {
				$user = get_userdata( $user_id );
				if ( $user ) {
					$display_name = esc_html( $user->display_name );
					echo '<meta property="og:title" content="Perfil de ' . esc_attr( $display_name ) . '">';
					echo '<meta property="og:url" content="' . esc_url( home_url( '/id/' . $user_id ) ) . '">';
				}
			}
		}
	}
}
add_action( 'wp_head', array( 'Apollo_User_Page_SEO', 'add_meta' ) );
