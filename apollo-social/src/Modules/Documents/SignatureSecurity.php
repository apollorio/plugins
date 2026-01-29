<?php
/**
 * Signature Security Service
 *
 * FASE 10: Segurança e auditoria de assinatura.
 *
 * Funcionalidades:
 * - CPF obrigatório (ou hash) para assinatura válida
 * - Regra "passaporte não assina" aplicada no backend
 * - Auditoria: método, timestamp, user_id, doc_hash, versão, IP/UA
 * - Verificação: hash do PDF final + cadeia de dados
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Infrastructure\ApolloLogger;
use WP_Error;

/**
 * Signature Security Service
 */
class SignatureSecurity {

	// =========================================================================
	// Constants
	// =========================================================================

	/** @var array Valid signature methods */
	public const VALID_METHODS = array(
		'electronic', // Simple electronic (click-to-sign)
		'digital',    // With certificate
		'pki',        // ICP-Brasil certificate
		'biometric',  // With biometric data
	);

	/** @var array Required fields for valid signature */
	private const REQUIRED_FIELDS = array(
		'signer_name',
	);

	/** @var array ID types that CAN sign (CPF required) */
	private const SIGNABLE_ID_TYPES = array(
		'cpf',
		'cnpj',
	);

	/** @var array ID types that CANNOT sign */
	private const NON_SIGNABLE_ID_TYPES = array(
		'passport',     // Passaporte
		'rne',          // Registro Nacional de Estrangeiro
		'rg',           // RG without CPF
		'cnh_expired',  // CNH vencida
	);

	// =========================================================================
	// Validation
	// =========================================================================

	/**
	 * Validate signature request
	 *
	 * @param array $signature Signature data.
	 * @param array $document  Document data.
	 * @return true|WP_Error True if valid.
	 */
	public static function validateSignature( array $signature, array $document = array() ) {
		$errors = array();

		// 1. Check required fields
		foreach ( self::REQUIRED_FIELDS as $field ) {
			if ( empty( $signature[ $field ] ) ) {
				$errors[] = sprintf(
					/* translators: %s: field name */
					__( 'Campo obrigatório: %s', 'apollo-social' ),
					$field
				);
			}
		}

		// 2. Require CPF or user_id (FASE 10 - CPF obrigatório)
		if ( empty( $signature['signer_cpf_hash'] ) && empty( $signature['signer_user_id'] ) ) {
			$errors[] = __( 'CPF ou identificação do usuário é obrigatório para assinatura.', 'apollo-social' );
		}

		// 3. Validate CPF format if provided
		if ( ! empty( $signature['signer_cpf'] ) ) {
			if ( ! self::isValidCpf( $signature['signer_cpf'] ) ) {
				$errors[] = __( 'CPF inválido.', 'apollo-social' );
			}
		}

		// 4. Check ID type restriction (passaporte não assina)
		if ( ! empty( $signature['id_type'] ) ) {
			if ( in_array( $signature['id_type'], self::NON_SIGNABLE_ID_TYPES, true ) ) {
				$errors[] = sprintf(
					/* translators: %s: ID type */
					__( 'Tipo de documento "%s" não é aceito para assinatura. Use CPF.', 'apollo-social' ),
					$signature['id_type']
				);
			}
		}

		// 5. Validate signature method
		$method = $signature['method'] ?? 'electronic';
		if ( ! in_array( $method, self::VALID_METHODS, true ) ) {
			$errors[] = __( 'Método de assinatura inválido.', 'apollo-social' );
		}

		// 6. Check document is in signable state
		if ( ! empty( $document ) ) {
			$doc_state = $document['state'] ?? 'draft';
			if ( ! DocumentStatus::isSignable( $doc_state ) ) {
				$errors[] = sprintf(
					/* translators: %s: current state */
					__( 'Documento em estado "%s" não pode ser assinado.', 'apollo-social' ),
					DocumentStatus::getLabel( $doc_state )
				);
			}
		}

		// Return errors or true
		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'signature_validation_failed',
				implode( ' ', $errors ),
				array( 'errors' => $errors )
			);
		}

		return true;
	}

	/**
	 * Check if CPF is valid (format and checksum)
	 *
	 * @param string $cpf CPF (with or without formatting).
	 * @return bool True if valid.
	 */
	public static function isValidCpf( string $cpf ): bool {
		// Remove non-digits
		$cpf = preg_replace( '/\D/', '', $cpf );

		// Must be 11 digits
		if ( strlen( $cpf ) !== 11 ) {
			return false;
		}

		// Check for known invalid sequences
		if ( preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
			return false;
		}

		// Validate check digits
		for ( $t = 9; $t < 11; $t++ ) {
			$d = 0;
			for ( $c = 0; $c < $t; $c++ ) {
				$d += $cpf[ $c ] * ( ( $t + 1 ) - $c );
			}
			$d = ( ( 10 * $d ) % 11 ) % 10;
			if ( $cpf[ $c ] != $d ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Hash CPF for storage (LGPD compliance)
	 *
	 * @param string $cpf CPF.
	 * @return string SHA-256 hash.
	 */
	public static function hashCpf( string $cpf ): string {
		$cpf = preg_replace( '/\D/', '', $cpf );
		return hash( 'sha256', $cpf . wp_salt( 'auth' ) );
	}

	// =========================================================================
	// Audit Trail
	// =========================================================================

	/**
	 * Create audit record for signature
	 *
	 * @param int   $post_id   Document post ID.
	 * @param array $signature Signature data.
	 * @return array Audit record.
	 */
	public static function createAuditRecord( int $post_id, array $signature ): array {
		$document = DocumentsRepository::getDocument( $post_id );

		$audit = array(
			'id'                => wp_generate_uuid4(),
			'timestamp'         => current_time( 'mysql', true ),
			'post_id'           => $post_id,
			'file_id'           => is_array( $document ) ? $document['file_id'] : null,

			// Signer info (anonymized)
			'signer_user_id'    => $signature['signer_user_id'] ?? null,
			'signer_cpf_hash'   => $signature['signer_cpf_hash'] ?? null,

			// Document state at signing
			'doc_hash'          => is_array( $document ) ? $document['doc_hash'] : null,
			'pdf_hash'          => is_array( $document ) ? $document['pdf_hash'] : null,
			'doc_version'       => is_array( $document ) ? $document['version'] : null,

			// Signature details
			'method'            => $signature['method'] ?? 'electronic',

			// Request context
			'ip_address'        => self::getClientIp(),
			'user_agent'        => self::getUserAgent(),
			'referer'           => sanitize_url( $_SERVER['HTTP_REFERER'] ?? '' ),

			// Geolocation (if available from IP)
			'geo_country'       => null, // Can be filled by external service

			// Verification data
			'verification_hash' => null, // Will be set after PDF generation
		);

		// Generate verification hash
		$audit['verification_hash'] = self::generateVerificationHash( $audit );

		return $audit;
	}

	/**
	 * Store audit record
	 *
	 * @param int   $post_id Document post ID.
	 * @param array $audit   Audit record.
	 * @return bool Success.
	 */
	public static function storeAuditRecord( int $post_id, array $audit ): bool {
		// Store in post meta
		$audits   = get_post_meta( $post_id, '_apollo_doc_audit_trail', true ) ?: array();
		$audits[] = $audit;
		update_post_meta( $post_id, '_apollo_doc_audit_trail', $audits );

		// Log to Apollo Logger
		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::logSignature(
				'signature_audit',
				$post_id,
				array(
					'audit_id'     => $audit['id'],
					'signer'       => $audit['signer_user_id'] ?? 'anonymous',
					'method'       => $audit['method'],
					'verification' => substr( $audit['verification_hash'], 0, 16 ) . '...',
				)
			);
		}

		return true;
	}

	/**
	 * Get audit trail for document
	 *
	 * @param int $post_id Document post ID.
	 * @return array Audit records.
	 */
	public static function getAuditTrail( int $post_id ): array {
		return get_post_meta( $post_id, '_apollo_doc_audit_trail', true ) ?: array();
	}

	// =========================================================================
	// Verification
	// =========================================================================

	/**
	 * Generate verification hash for audit record
	 *
	 * @param array $audit Audit data.
	 * @return string SHA-256 hash.
	 */
	public static function generateVerificationHash( array $audit ): string {
		$data = implode(
			'|',
			array(
				$audit['post_id'],
				$audit['timestamp'],
				$audit['signer_cpf_hash'] ?? $audit['signer_user_id'] ?? '',
				$audit['doc_hash'] ?? '',
				$audit['pdf_hash'] ?? '',
				$audit['method'],
				wp_salt( 'auth' ),
			)
		);

		return hash( 'sha256', $data );
	}

	/**
	 * Verify signature authenticity
	 *
	 * @param int    $post_id           Document post ID.
	 * @param string $signature_id      Signature ID.
	 * @param string $verification_hash Hash to verify.
	 * @return array Verification result.
	 */
	public static function verifySignature( int $post_id, string $signature_id, string $verification_hash = '' ): array {
		$result = array(
			'valid'   => false,
			'errors'  => array(),
			'details' => array(),
		);

		// Get document
		$document = DocumentsRepository::getDocument( $post_id );
		if ( is_wp_error( $document ) ) {
			$result['errors'][] = __( 'Documento não encontrado.', 'apollo-social' );
			return $result;
		}

		// Find signature
		$signatures = $document['signatures'] ?? array();
		$signature  = null;

		foreach ( $signatures as $sig ) {
			if ( ( $sig['id'] ?? '' ) === $signature_id ) {
				$signature = $sig;
				break;
			}
		}

		if ( ! $signature ) {
			$result['errors'][] = __( 'Assinatura não encontrada.', 'apollo-social' );
			return $result;
		}

		// Get audit record
		$audit_trail = self::getAuditTrail( $post_id );
		$audit       = null;

		foreach ( $audit_trail as $record ) {
			if ( ( $record['id'] ?? '' ) === $signature_id ) {
				$audit = $record;
				break;
			}
		}

		// Verify hash consistency
		if ( $verification_hash ) {
			if ( $audit && $audit['verification_hash'] !== $verification_hash ) {
				$result['errors'][] = __( 'Hash de verificação não corresponde.', 'apollo-social' );
				return $result;
			}
		}

		// Build verification details
		$result['valid']   = true;
		$result['details'] = array(
			'document_id'    => $post_id,
			'document_title' => $document['title'],
			'signature_date' => $signature['signed_at'] ?? null,
			'signer_name'    => $signature['signer_name'] ?? null,
			'method'         => $signature['method'] ?? 'electronic',
			'doc_hash_match' => true,
			'integrity'      => 'verified',
		);

		// Verify document hasn't changed since signing
		if ( $audit && $audit['doc_hash'] !== $document['doc_hash'] ) {
			$result['details']['doc_hash_match'] = false;
			$result['details']['integrity']      = 'document_modified';
			$result['valid']                     = false;
			$result['errors'][]                  = __( 'Documento foi modificado após assinatura.', 'apollo-social' );
		}

		return $result;
	}

	/**
	 * Generate verification URL for signature
	 *
	 * @param int    $post_id      Document post ID.
	 * @param string $signature_id Signature ID.
	 * @return string Verification URL.
	 */
	public static function getVerificationUrl( int $post_id, string $signature_id ): string {
		$audit_trail = self::getAuditTrail( $post_id );

		foreach ( $audit_trail as $audit ) {
			if ( ( $audit['id'] ?? '' ) === $signature_id ) {
				return add_query_arg(
					array(
						'apollo_action' => 'verify_signature',
						'doc'           => $post_id,
						'sig'           => $signature_id,
						'hash'          => substr( $audit['verification_hash'], 0, 16 ),
					),
					home_url( '/apollo/signature/' )
				);
			}
		}

		return '';
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Get client IP
	 *
	 * @return string IP address.
	 */
	private static function getClientIp(): string {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Get user agent (sanitized)
	 *
	 * @return string User agent.
	 */
	private static function getUserAgent(): string {
		$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
		return sanitize_text_field( substr( $ua, 0, 255 ) );
	}

	/**
	 * Check if document has all required signatures
	 *
	 * @param int $post_id         Document post ID.
	 * @param int $required_count  Required signature count.
	 * @return bool True if complete.
	 */
	public static function hasRequiredSignatures( int $post_id, int $required_count = 1 ): bool {
		$signatures = DocumentsRepository::getSignatures( $post_id );
		return count( $signatures ) >= $required_count;
	}

	/**
	 * Check if user has already signed document
	 *
	 * @param int $post_id Document post ID.
	 * @param int $user_id User ID.
	 * @return bool True if already signed.
	 */
	public static function hasUserSigned( int $post_id, int $user_id ): bool {
		$signatures = DocumentsRepository::getSignatures( $post_id );

		foreach ( $signatures as $sig ) {
			if ( ( $sig['signer_user_id'] ?? 0 ) === $user_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if CPF has already signed document
	 *
	 * @param int    $post_id  Document post ID.
	 * @param string $cpf_hash Hashed CPF.
	 * @return bool True if already signed.
	 */
	public static function hasCpfSigned( int $post_id, string $cpf_hash ): bool {
		$signatures = DocumentsRepository::getSignatures( $post_id );

		foreach ( $signatures as $sig ) {
			if ( ( $sig['signer_cpf_hash'] ?? '' ) === $cpf_hash ) {
				return true;
			}
		}

		return false;
	}
}
