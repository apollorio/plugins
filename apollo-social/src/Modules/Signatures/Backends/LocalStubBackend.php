<?php

/**
 * Local Stub Signature Backend
 *
 * Stub implementation for development/testing.
 * Simulates ICP-Brasil signing without actual cryptographic operations.
 *
 * @package Apollo\Modules\Signatures\Backends
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 */

declare(strict_types=1);

namespace Apollo\Modules\Signatures\Backends;

use Apollo\Modules\Signatures\Contracts\SignatureBackendInterface;
use Apollo\Modules\Documents\DocumentsManager;
use WP_Error;

/**
 * Class LocalStubBackend
 *
 * Stub backend that simulates signature operations.
 * Use for development/testing only.
 */
class LocalStubBackend implements SignatureBackendInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_identifier(): string {
		return 'local_stub';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return __( 'Local Stub (Desenvolvimento)', 'apollo-social' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		// Stub is always available.
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_capabilities(): array {
		return array(
			// Simulated PAdES support.
			'pades'          => true,
			'cades'          => false,
			'xades'          => false,
			// Not real ICP-Brasil.
			'icp_brasil'     => false,
			// Local timestamp only.
			'timestamp'      => true,
			'batch_sign'     => false,
			// Simulated A1 certificate.
			'certificate_a1' => true,
			// Cannot simulate A3.
			'certificate_a3' => false,
		);
	}

	/**
	 * Sign a document.
	 *
	 * {@inheritDoc}
	 *
	 * @param int   $document_id The document ID.
	 * @param int   $user_id     The user ID performing the signature.
	 * @param array $options     Additional signing options.
	 */
	public function sign( int $document_id, int $user_id, array $options = array() ): array|WP_Error {
		// Validate document exists.
		$manager  = new DocumentsManager();
		$document = $manager->getDocumentById( $document_id );

		if ( ! $document ) {
			return new WP_Error(
				'apollo_sign_document_not_found',
				__( 'Documento não encontrado.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		// Check if document has PDF.
		$pdf_path = $document['pdf_path'] ?? '';
		if ( empty( $pdf_path ) || ! file_exists( $pdf_path ) ) {
			return new WP_Error(
				'apollo_sign_pdf_not_found',
				__( 'PDF do documento não encontrado. Gere o PDF primeiro.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Get user info.
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'apollo_sign_user_not_found',
				__( 'Usuário não encontrado.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		// Generate stub signature ID.
		$signature_id = 'STUB-' . strtoupper( wp_generate_password( 12, false ) );

		// Generate stub certificate info.
		$certificate = array(
			'type'       => $options['certificate_type'] ?? 'A1',
			'cn'         => $user->display_name,
			'cpf'        => get_user_meta( $user_id, 'cpf', true ) ? get_user_meta( $user_id, 'cpf', true ) : '000.000.000-00',
			'email'      => $user->user_email,
			'issuer'     => 'Apollo Stub CA (DEV)',
			'serial'     => strtoupper( wp_generate_password( 16, false ) ),
			'valid_from' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'valid_to'   => gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+1 year' ) ),
		);

		// Calculate document hash.
		$document_hash = hash_file( 'sha256', $pdf_path );

		// Generate signature timestamp.
		$timestamp = gmdate( 'Y-m-d\TH:i:s\Z' );

		/*
		 * NOTE: ICP-Brasil Integration Point
		 *
		 * This is where actual signing happens with real backends.
		 * See DemoiselleBackend for ICP-Brasil implementation.
		 */

		// For stub, we just copy the PDF (no actual signing).
		$uploads_dir     = wp_upload_dir();
		$signed_dir      = $uploads_dir['basedir'] . '/apollo-documents/signed';
		$signed_pdf_path = $signed_dir . '/' . basename( $pdf_path, '.pdf' ) . '_signed.pdf';

		if ( ! file_exists( $signed_dir ) ) {
			wp_mkdir_p( $signed_dir );
		}

		// Copy PDF (in real implementation, this would be the signed PDF).
		copy( $pdf_path, $signed_pdf_path );

		// Flag indicating this is a stub signature.
		$reason   = isset( $options['reason'] ) ? $options['reason'] : __( 'Assinatura Digital', 'apollo-social' );
		$location = isset( $options['location'] ) ? $options['location'] : get_bloginfo( 'name' );

		// Return success with signature details.
		return array(
			'success'         => true,
			'signature_id'    => $signature_id,
			'signed_pdf_path' => $signed_pdf_path,
			'certificate'     => $certificate,
			'timestamp'       => $timestamp,
			'hash'            => $document_hash,
			'backend'         => $this->get_identifier(),
			'is_stub'         => true,
			'metadata'        => array(
				'reason'     => $reason,
				'location'   => $location,
				'ip_address' => $this->get_client_ip(),
				'user_agent' => $this->get_user_agent(),
			),
		);
	}

	/**
	 * Verify a signed PDF.
	 *
	 * {@inheritDoc}
	 *
	 * @param string $pdf_path Path to the PDF file.
	 * @param array  $options  Additional verification options.
	 */
	public function verify( string $pdf_path, array $options = array() ): array|WP_Error {
		if ( ! file_exists( $pdf_path ) ) {
			return new WP_Error(
				'apollo_verify_file_not_found',
				__( 'Arquivo PDF não encontrado.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		// Stub: check if file has "_signed" in name.
		$is_signed = strpos( basename( $pdf_path ), '_signed' ) !== false;

		if ( ! $is_signed ) {
			return array(
				'valid'           => false,
				'signatures'      => array(),
				'certificate'     => null,
				'chain_valid'     => false,
				'timestamp_valid' => false,
				'revoked'         => false,
				'message'         => __( 'Documento não possui assinatura digital.', 'apollo-social' ),
			);
		}

		// Return stub verification result.
		return array(
			'valid'           => true,
			'signatures'      => array(
				array(
					'signer'    => 'Stub Signer (DEV)',
					'timestamp' => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'valid'     => true,
				),
			),
			'certificate'     => array(
				'issuer'     => 'Apollo Stub CA (DEV)',
				'subject'    => 'Stub Signer',
				'valid_from' => gmdate( 'Y-m-d' ),
				'valid_to'   => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			),
			'chain_valid'     => true,
			'timestamp_valid' => true,
			'revoked'         => false,
			'is_stub'         => true,
			'message'         => __( 'Assinatura stub válida (apenas para desenvolvimento).', 'apollo-social' ),
		);
	}

	/**
	 * Get certificate information.
	 *
	 * {@inheritDoc}
	 *
	 * @param string $certificate_path Path to the certificate file.
	 * @param string $password         Certificate password.
	 */
	public function get_certificate_info( string $certificate_path, string $password = '' ): array|WP_Error {
		// Stub: return fake certificate info.
		return array(
			'name'       => 'Stub User (DEV)',
			'cpf'        => '000.000.000-00',
			'email'      => 'stub@example.com',
			'issuer'     => 'Apollo Stub CA (DEV)',
			'valid_from' => gmdate( 'Y-m-d' ),
			'valid_to'   => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'serial'     => 'STUB-' . strtoupper( wp_generate_password( 8, false ) ),
			'type'       => 'A1',
			'is_stub'    => true,
		);
	}

	/**
	 * Get client IP address.
	 *
	 * @return string The client IP address.
	 */
	private function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Known server keys.
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get user agent string from request.
	 *
	 * @return string The user agent or empty string.
	 */
	private function get_user_agent(): string {
        // phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

        // phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		return '';
	}
}
