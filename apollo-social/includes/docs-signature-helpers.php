<?php
/**
 * Apollo Documents Signature - Helper Functions
 *
 * Wrapper functions for signature operations.
 * Provides compatibility with aprio_* naming convention.
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get PDF hash for a document
 *
 * @param int $doc_id Document post ID.
 * @return string|WP_Error Hash hex string or WP_Error.
 */
function aprio_docs_get_pdf_hash( int $doc_id ) {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsSignatureService' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsSignatureService.php';
	}

	return \Apollo\Modules\Documents\DocumentsSignatureService::get_pdf_hash( $doc_id );
}

/**
 * Sign a document
 *
 * @param int    $doc_id Document post ID.
 * @param array  $signer_data Signer information.
 * @param string $signature_method Signature method.
 * @return array|WP_Error Signature result or WP_Error.
 */
function aprio_docs_sign_document( int $doc_id, array $signer_data, string $signature_method = 'e-sign-basic' ) {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsSignatureService' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsSignatureService.php';
	}

	return \Apollo\Modules\Documents\DocumentsSignatureService::sign_document( $doc_id, $signer_data, $signature_method );
}

/**
 * Get signatures for a document
 *
 * @param int $doc_id Document post ID.
 * @return array Array of signatures.
 */
function aprio_docs_get_signatures( int $doc_id ): array {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsSignatureService' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsSignatureService.php';
	}

	return \Apollo\Modules\Documents\DocumentsSignatureService::get_signatures( $doc_id );
}

/**
 * Verify document integrity
 *
 * @param int $doc_id Document post ID.
 * @return array Verification result.
 */
function aprio_docs_verify_document( int $doc_id ): array {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsSignatureService' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsSignatureService.php';
	}

	return \Apollo\Modules\Documents\DocumentsSignatureService::verify_document( $doc_id );
}

