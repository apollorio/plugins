<?php
/**
 * Apollo Document Functions
 * File: inc/apollo-document-functions.php
 */

/**
 * Get document data with signers and status
 */
function apollo_get_document_data( $document_id ) {
	return array(
		'id'          => $document_id,
		'title'       => get_the_title( $document_id ),
		'content'     => get_post_field( 'post_content', $document_id ),
		'category'    => get_post_meta( $document_id, 'document_category', true ),
		'subcategory' => get_post_meta( $document_id, 'document_subcategory', true ),
		'code'        => get_post_meta( $document_id, 'document_code', true ),
		'status'      => get_post_meta( $document_id, 'document_status', true ) ?: 'pending',
		'pages'       => get_post_meta( $document_id, 'document_pages', true ) ?: 1,
		'signers'     => get_post_meta( $document_id, 'document_signers', true ) ?: array(),
		'created_at'  => get_the_date( 'Y-m-d H:i:s', $document_id ),
	);
}

/**
 * Check if user has signed document
 */
function apollo_check_user_signed( $document_id, $user_id ) {
	global $wpdb;

	$signature = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_signatures 
        WHERE document_id = %d AND user_id = %d",
			$document_id,
			$user_id
		)
	);

	return ! empty( $signature );
}

/**
 * Count signed signatures for document
 */
function apollo_count_signed( $document_id ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_signatures 
        WHERE document_id = %d",
			$document_id
		)
	);
}

/**
 * Get user signature details
 */
function apollo_get_user_signature( $document_id, $user_id ) {
	global $wpdb;

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_signatures 
        WHERE document_id = %d AND user_id = %d",
			$document_id,
			$user_id
		),
		ARRAY_A
	);
}

/**
 * Record signature
 */
function apollo_record_signature( $document_id, $user_id, $provider, $metadata = array() ) {
	global $wpdb;

	$hash = hash( 'sha256', $document_id . $user_id . time() . wp_generate_password( 20, false ) );
	$code = strtoupper( $provider ) . '-' . str_pad( rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );

	$result = $wpdb->insert(
		$wpdb->prefix . 'apollo_signatures',
		array(
			'document_id' => $document_id,
			'user_id'     => $user_id,
			'provider'    => $provider,
			'code'        => $code,
			'hash'        => $hash,
			'metadata'    => json_encode( $metadata ),
			'signed_at'   => current_time( 'mysql' ),
			'ip_address'  => $_SERVER['REMOTE_ADDR'],
		),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( $result ) {
		// Update signer status in document meta
		$signers = get_post_meta( $document_id, 'document_signers', true ) ?: array();
		foreach ( $signers as &$signer ) {
			if ( $signer['user_id'] == $user_id ) {
				$signer['signed_at']    = current_time( 'mysql' );
				$signer['signature_id'] = $wpdb->insert_id;
				break;
			}
		}
		update_post_meta( $document_id, 'document_signers', $signers );

		// Check if all signed
		$all_signed = apollo_check_all_signed( $document_id );
		if ( $all_signed ) {
			update_post_meta( $document_id, 'document_status', 'signed' );
		}

		return array(
			'success'   => true,
			'code'      => $code,
			'hash'      => $hash,
			'signed_at' => current_time( 'mysql' ),
		);
	}

	return array( 'success' => false );
}

/**
 * Check if all signers have signed
 */
function apollo_check_all_signed( $document_id ) {
	$signers = get_post_meta( $document_id, 'document_signers', true ) ?: array();
	foreach ( $signers as $signer ) {
		if ( empty( $signer['signed_at'] ) ) {
			return false;
		}
	}
	return ! empty( $signers );
}

/**
 * Mask CPF
 */
function apollo_mask_cpf( $cpf ) {
	if ( empty( $cpf ) ) {
		return '***.***.***-**';
	}
	return preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.***.***-$4', $cpf );
}

/**
 * Get document signature URL
 */
function apollo_get_signature_url( $document_id ) {
	return home_url( '/document/' . $document_id . '/sign' );
}
