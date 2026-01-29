<?php
/**
 * Apollo Document AJAX Handlers
 * File: inc/apollo-document-ajax.php
 */

// AJAX: Sign Document
add_action( 'wp_ajax_apollo_sign_document', 'apollo_ajax_sign_document' );

function apollo_ajax_sign_document() {
	check_ajax_referer( 'apollo_doc_nonce', 'nonce' );

	$document_id = intval( $_POST['document_id'] );
	$provider    = sanitize_text_field( $_POST['provider'] );
	$user_id     = get_current_user_id();

	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => 'Usuário não autenticado' ) );
	}

	// Check if already signed
	if ( apollo_check_user_signed( $document_id, $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Documento já assinado' ) );
	}

	// Validate provider
	$allowed_providers = array( 'govbr', 'icp' );
	if ( ! in_array( $provider, $allowed_providers ) ) {
		wp_send_json_error( array( 'message' => 'Provedor inválido' ) );
	}

	// Record signature
	$result = apollo_record_signature(
		$document_id,
		$user_id,
		$provider,
		array(
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'timestamp'  => time(),
		)
	);

	if ( $result['success'] ) {
		wp_send_json_success(
			array(
				'code'      => $result['code'],
				'hash'      => $result['hash'],
				'signed_at' => date( 'd/m/Y H:i', strtotime( $result['signed_at'] ) ),
			)
		);
	} else {
		wp_send_json_error( array( 'message' => 'Erro ao registrar assinatura' ) );
	}
}

// Enqueue scripts
add_action( 'wp_enqueue_scripts', 'apollo_enqueue_document_scripts' );

function apollo_enqueue_document_scripts() {
	if ( is_singular( 'document' ) ) {
		wp_enqueue_script(
			'apollo-doc-sign',
			get_template_directory_uri() . '/assets/js/apollo-document-sign.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'apollo-doc-sign',
			'apolloDocData',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'apollo_doc_nonce' ),
				'documentId' => get_the_ID(),
			)
		);
	}
}

// Create signatures table on activation
function apollo_create_signatures_table() {
	global $wpdb;
	$table           = $wpdb->prefix . 'apollo_signatures';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        document_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        provider varchar(50) NOT NULL,
        code varchar(100) NOT NULL,
        hash varchar(255) NOT NULL,
        metadata longtext,
        signed_at datetime NOT NULL,
        ip_address varchar(50),
        PRIMARY KEY (id),
        KEY document_id (document_id),
        KEY user_id (user_id),
        KEY signed_at (signed_at)
    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'apollo_create_signatures_table' );
