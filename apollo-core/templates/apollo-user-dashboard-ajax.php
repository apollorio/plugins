<?php
/**
 * Apollo User Dashboard AJAX Handlers
 * File: inc/apollo-user-dashboard-ajax.php
 */

// AJAX: Update User Settings
add_action( 'wp_ajax_apollo_update_user_settings', 'apollo_ajax_update_user_settings' );

function apollo_ajax_update_user_settings() {
	check_ajax_referer( 'apollo_user_settings', 'settings_nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => 'Usuário não autenticado' ) );
	}

	$user    = get_userdata( $user_id );
	$updated = false;
	$reload  = false;

	// Update display name
	if ( isset( $_POST['display_name'] ) && $_POST['display_name'] !== $user->display_name ) {
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => sanitize_text_field( $_POST['display_name'] ),
			)
		);
		$updated = true;
		$reload  = true;
	}

	// Update email
	if ( isset( $_POST['user_email'] ) && is_email( $_POST['user_email'] ) && $_POST['user_email'] !== $user->user_email ) {
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => sanitize_email( $_POST['user_email'] ),
			)
		);
		$updated = true;
	}

	// Update bio
	if ( isset( $_POST['user_bio'] ) ) {
		update_user_meta( $user_id, 'description', sanitize_textarea_field( $_POST['user_bio'] ) );
		$updated = true;
	}

	// Update location
	if ( isset( $_POST['user_location'] ) ) {
		update_user_meta( $user_id, 'user_location', sanitize_text_field( $_POST['user_location'] ) );
		$updated = true;
	}

	// Update privacy settings
	if ( isset( $_POST['privacy_profile'] ) ) {
		update_user_meta( $user_id, 'privacy_profile', sanitize_text_field( $_POST['privacy_profile'] ) );
		$updated = true;
	}

	// Update notification preferences
	update_user_meta( $user_id, 'notify_events', isset( $_POST['notify_events'] ) ? 'yes' : 'no' );
	update_user_meta( $user_id, 'notify_messages', isset( $_POST['notify_messages'] ) ? 'yes' : 'no' );
	update_user_meta( $user_id, 'notify_docs', isset( $_POST['notify_docs'] ) ? 'yes' : 'no' );
	$updated = true;

	// Handle avatar upload
	if ( isset( $_FILES['user_avatar'] ) && $_FILES['user_avatar']['size'] > 0 ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'user_avatar', 0 );

		if ( ! is_wp_error( $attachment_id ) ) {
			update_user_meta( $user_id, 'apollo_avatar', $attachment_id );
			$updated = true;
		}
	}

	// Handle password change
	if ( ! empty( $_POST['current_password'] ) && ! empty( $_POST['new_password'] ) ) {
		if ( ! wp_check_password( $_POST['current_password'], $user->user_pass, $user_id ) ) {
			wp_send_json_error( array( 'message' => 'Senha atual incorreta' ) );
		}

		if ( $_POST['new_password'] !== $_POST['confirm_password'] ) {
			wp_send_json_error( array( 'message' => 'As senhas não coincidem' ) );
		}

		wp_set_password( $_POST['new_password'], $user_id );
		$updated = true;
	}

	if ( $updated ) {
		wp_send_json_success(
			array(
				'message' => 'Configurações atualizadas com sucesso!',
				'reload'  => $reload,
			)
		);
	} else {
		wp_send_json_error( array( 'message' => 'Nenhuma alteração foi feita' ) );
	}
}

// AJAX: Delete User Account
add_action( 'wp_ajax_apollo_delete_account', 'apollo_ajax_delete_account' );

function apollo_ajax_delete_account() {
	check_ajax_referer( 'delete_account' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_die( 'Usuário não autenticado' );
	}

	// Delete user and all associated content
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $user_id );

	wp_logout();
	wp_redirect( home_url() );
	exit;
}

// Enqueue dashboard scripts
add_action( 'wp_enqueue_scripts', 'apollo_enqueue_dashboard_scripts' );

function apollo_enqueue_dashboard_scripts() {
	if ( is_page_template( 'page-user-dashboard.php' ) ) {
		wp_enqueue_style(
			'apollo-dashboard',
			get_template_directory_uri() . '/assets/css/apollo-dashboard.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'apollo-dashboard',
			get_template_directory_uri() . '/assets/js/apollo-dashboard.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}
}
