<?php

/**
 * Apollo Documents - PKI Integration Hooks
 *
 * Provides hooks and integration points for PKI/certificate-based signatures.
 * This file does NOT implement cryptography - it provides clean integration points.
 *
 * @package Apollo\Modules\Documents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Class DocumentsPkiIntegration
 *
 * Handles PKI integration hooks and configuration.
 */
class DocumentsPkiIntegration {

	/**
	 * Initialize PKI hooks
	 */
	public static function init(): void {
		// Hook into signature process
		add_action( 'apollo_doc_signed', array( __CLASS__, 'handle_pki_signature' ), 10, 2 );
	}

	/**
	 * Handle PKI signature after basic signature is recorded
	 *
	 * This hook is called after a signature is recorded in the database.
	 * If PKI is enabled and available, it will attempt to sign the PDF cryptographically.
	 *
	 * @param int   $doc_id Document post ID.
	 * @param array $signature_entry Signature entry that was just created.
	 */
	public static function handle_pki_signature( int $doc_id, array $signature_entry ): void {
		// Check if PKI is enabled
		$pki_enabled = get_option( 'apollo_docs_pki_enabled', false );
		if ( ! $pki_enabled ) {
			return;
		}

		// Check if IcpBrasilSigner is available
		if ( ! class_exists( 'Apollo\\Modules\\Signatures\\IcpBrasilSigner' ) ) {
			return;
		}

		// TODO: Implement PKI signing flow
		// This should:
		// 1. Check if signer has provided certificate data
		// 2. Call IcpBrasilSigner to sign PDF
		// 3. Store PKI signature reference in signature_entry['pki_signature_id']
		// 4. Update signature meta with PKI data

		// For now, this is a placeholder that can be extended when PKI UI is ready
		do_action( 'apollo_doc_pki_signature_attempt', $doc_id, $signature_entry );
	}

	/**
	 * Check if PKI is available
	 *
	 * @return bool True if PKI integration is available.
	 */
	public static function is_pki_available(): bool {
		$pki_enabled = get_option( 'apollo_docs_pki_enabled', false );
		$has_signer  = class_exists( 'Apollo\\Modules\\Signatures\\IcpBrasilSigner' );

		return $pki_enabled && $has_signer;
	}
}

// Initialize if class exists
if ( class_exists( 'Apollo\\Modules\\Documents\\DocumentsPkiIntegration' ) ) {
	DocumentsPkiIntegration::init();
}
