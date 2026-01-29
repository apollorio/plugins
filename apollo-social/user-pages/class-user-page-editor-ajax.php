<?php

/**
 * Endpoints AJAX para salvar/carregar layout, upload de mídia, depoimentos
 */
class Apollo_User_Page_Editor_AJAX {

	/**
	 * Save user page layout via AJAX
	 *
	 * @return void
	 */
	public static function save_layout() {
		check_ajax_referer( 'apollo_userpage_save', 'nonce' );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( 'User ID inválido' );
		}
		$post_id = get_user_meta( $user_id, 'apollo_user_page_id', true );
		if ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Acesso negado' );
		}
		$layout_raw = isset( $_POST['layout'] ) ? wp_unslash( $_POST['layout'] ) : '';
		if ( strlen( $layout_raw ) > 300000 ) {
			wp_send_json_error( 'Layout muito grande (máximo 300KB)' );
		}
		$layout = json_decode( $layout_raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $layout ) ) {
			wp_send_json_error( 'Layout JSON inválido: ' . json_last_error_msg() );
		}
		update_post_meta( $post_id, 'apollo_userpage_layout_v1', $layout );
		wp_send_json_success( array( 'ok' => true ) );
	}
	/**
	 * Load user page layout via AJAX
	 *
	 * @return void
	 */
	public static function load_layout() {
		check_ajax_referer( 'apollo_userpage_load', 'nonce' );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( 'User ID inválido' );
		}
		$post_id = get_user_meta( $user_id, 'apollo_user_page_id', true );
		if ( ! $post_id ) {
			wp_send_json_error( 'Página não encontrada' );
		}
		$layout = get_post_meta( $post_id, 'apollo_userpage_layout_v1', true );
		wp_send_json_success( array( 'layout' => $layout ) );
	}
}
add_action( 'wp_ajax_apollo_userpage_save', array( 'Apollo_User_Page_Editor_AJAX', 'save_layout' ) );
add_action( 'wp_ajax_apollo_userpage_load', array( 'Apollo_User_Page_Editor_AJAX', 'load_layout' ) );
