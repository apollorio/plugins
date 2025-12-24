<?php

/**
 * Document Signature Service
 *
 * Main service for document signing operations.
 * Manages backends, logs signatures, and updates document status.
 *
 * @package Apollo\Modules\Signatures\Services
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

namespace Apollo\Modules\Signatures\Services;

use Apollo\Modules\Signatures\Contracts\SignatureBackendInterface;
use Apollo\Modules\Signatures\Backends\LocalStubBackend;
use Apollo\Modules\Signatures\Backends\DemoiselleBackend;
use Apollo\Modules\Signatures\AuditLog;
use Apollo\Modules\Documents\DocumentsManager;
use WP_Error;

/**
 * Class DocumentSignatureService
 *
 * Orchestrates document signing with pluggable backends.
 */
class DocumentSignatureService {

	/**
	 * Registered backends.
	 *
	 * @var SignatureBackendInterface[]
	 */
	private array $backends = array();

	/**
	 * Active backend instance.
	 *
	 * @var SignatureBackendInterface|null
	 */
	private ?SignatureBackendInterface $active_backend = null;

	/**
	 * Audit logger instance.
	 *
	 * @var AuditLog
	 */
	private AuditLog $audit;

	/**
	 * Documents manager instance.
	 *
	 * @var DocumentsManager
	 */
	private DocumentsManager $documents;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->audit     = new AuditLog();
		$this->documents = new DocumentsManager();

		// Register default backends.
		$this->register_default_backends();
	}

	/**
	 * Register default signature backends.
	 *
	 * @return void
	 */
	private function register_default_backends(): void {
		// Local stub (always available, for development).
		$this->register_backend( new LocalStubBackend() );

		// Demoiselle ICP-Brasil.
		$this->register_backend( new DemoiselleBackend() );

		// Allow plugins to add more backends.
		do_action( 'apollo_register_signature_backends', $this );

		// Set active backend from option or first available.
		$preferred = get_option( 'apollo_signature_backend', 'local_stub' );
		$this->set_active_backend( $preferred );
	}

	/**
	 * Register a signature backend.
	 *
	 * @param SignatureBackendInterface $backend Backend instance.
	 *
	 * @return void
	 */
	public function register_backend( SignatureBackendInterface $backend ): void {
		$this->backends[ $backend->get_identifier() ] = $backend;
	}

	/**
	 * Set active backend.
	 *
	 * @param string $identifier Backend identifier.
	 *
	 * @return bool True if backend was set.
	 */
	public function set_active_backend( string $identifier ): bool {
		if ( isset( $this->backends[ $identifier ] ) ) {
			$backend = $this->backends[ $identifier ];

			if ( $backend->is_available() ) {
				$this->active_backend = $backend;

				return true;
			}
		}

		// Fallback to first available.
		foreach ( $this->backends as $backend ) {
			if ( $backend->is_available() ) {
				$this->active_backend = $backend;

				return true;
			}
		}

		return false;
	}

	/**
	 * Get active backend.
	 *
	 * @return SignatureBackendInterface|null Active backend or null.
	 */
	public function get_active_backend(): ?SignatureBackendInterface {
		return $this->active_backend;
	}

	/**
	 * Get all registered backends.
	 *
	 * @return SignatureBackendInterface[] All backends.
	 */
	public function get_backends(): array {
		return $this->backends;
	}

	/**
	 * Get available backends.
	 *
	 * @return SignatureBackendInterface[] Available backends.
	 */
	public function get_available_backends(): array {
		return array_filter(
			$this->backends,
			fn ( $backend ) => $backend->is_available()
		);
	}

	/**
	 * Sign a document.
	 *
	 * Main entry point for document signing.
	 *
	 * @param int   $document_id Document ID.
	 * @param int   $user_id     User performing signature.
	 * @param array $options     Signature options.
	 *
	 * @return array|WP_Error Result array or error.
	 */
	public function sign_document( int $document_id, int $user_id, array $options = array() ): array|WP_Error {
		// Check backend.
		if ( ! $this->active_backend ) {
			return new WP_Error(
				'apollo_sign_no_backend',
				__( 'Nenhum backend de assinatura disponível.', 'apollo-social' ),
				array( 'status' => 503 )
			);
		}

		// Validate user.
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'apollo_sign_invalid_user',
				__( 'Usuário inválido.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Check permission.
		if ( ! $this->user_can_sign( $document_id, $user_id ) ) {
			return new WP_Error(
				'apollo_sign_permission_denied',
				__( 'Você não tem permissão para assinar este documento.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		// Get document.
		$document = $this->documents->getDocumentById( $document_id );
		if ( ! $document ) {
			return new WP_Error(
				'apollo_sign_document_not_found',
				__( 'Documento não encontrado.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		// Check document status.
		if ( 'signed' === ( $document['status'] ?? '' ) ) {
			return new WP_Error(
				'apollo_sign_already_signed',
				__( 'Este documento já foi assinado.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Log signature attempt (don't log password).
		$safe_options = array_diff_key( $options, array( 'certificate_pass' => '' ) );
		$this->audit->log(
			$document_id,
			'signature_requested',
			array(
				'actor_id'    => $user_id,
				'actor_name'  => $user->display_name,
				'actor_email' => $user->user_email,
				'details'     => array(
					'backend' => $this->active_backend->get_identifier(),
					'options' => $safe_options,
				),
			)
		);

		// Perform signature.
		$result = $this->active_backend->sign( $document_id, $user_id, $options );

		if ( is_wp_error( $result ) ) {
			// Log failure.
			$this->audit->log(
				$document_id,
				'rejected',
				array(
					'actor_id'   => $user_id,
					'actor_name' => $user->display_name,
					'details'    => array(
						'error_code'    => $result->get_error_code(),
						'error_message' => $result->get_error_message(),
					),
				)
			);

			return $result;
		}

		// Success: update document status and log signature.
		$this->process_signature_success( $document_id, $user_id, $result );

		return $result;
	}

	/**
	 * Process successful signature.
	 *
	 * @param int   $document_id Document ID.
	 * @param int   $user_id     User ID.
	 * @param array $result      Signature result.
	 *
	 * @return void
	 */
	private function process_signature_success( int $document_id, int $user_id, array $result ): void {
		$user = get_userdata( $user_id );

		// Add signature to log.
		$this->add_signature_log(
			$document_id,
			array(
				'user_id'          => $user_id,
				'user_name'        => $user->display_name,
				'user_email'       => $user->user_email,
				'signature_id'     => $result['signature_id'] ?? '',
				'backend'          => $result['backend'] ?? $this->active_backend->get_identifier(),
				'certificate_type' => $result['certificate']['type'] ?? 'unknown',
				'certificate_cn'   => $result['certificate']['cn'] ?? '',
				'timestamp'        => $result['timestamp'] ?? gmdate( 'Y-m-d\TH:i:s\Z' ),
				'hash'             => $result['hash'] ?? '',
				'status'           => 'success',
				'is_stub'          => $result['is_stub'] ?? false,
			)
		);

		// Update document status.
		$this->documents->updateDocument(
			$document_id,
			array( 'status' => 'signed' )
		);

		// Update signed PDF path if available.
		if ( ! empty( $result['signed_pdf_path'] ) ) {
			global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update required.
			$wpdb->update(
				$wpdb->prefix . 'apollo_documents',
				array( 'pdf_path' => $result['signed_pdf_path'] ),
				array( 'id' => $document_id )
			);
		}

		// Log success in audit.
		$this->audit->logSignature(
			$document_id,
			array(
				'name'               => $user->display_name,
				'cpf'                => $result['certificate']['cpf'] ?? '',
				'email'              => $user->user_email,
				'type'               => ( $result['is_stub'] ?? false ) ? 'electronic_stub' : 'qualified',
				'certificate_serial' => $result['certificate']['serial'] ?? '',
			),
			$result['signature_id'] ?? '',
			$result['hash'] ?? ''
		);

		// Generate protocol if not exists.
		if ( ! empty( $result['hash'] ) ) {
			$this->audit->generateProtocol( $document_id, $result['hash'] );
		}

		// Fire action for integrations.
		do_action( 'apollo_document_signed', $document_id, $user_id, $result );
	}

	/**
	 * Add signature to document log.
	 *
	 * @param int   $document_id Document ID.
	 * @param array $signature   Signature data.
	 *
	 * @return bool Success.
	 */
	public function add_signature_log( int $document_id, array $signature ): bool {
		$signatures = $this->get_signatures( $document_id );

		$signature['logged_at'] = gmdate( 'Y-m-d\TH:i:s\Z' );
		$signatures[]           = $signature;

		return update_post_meta( $document_id, '_apollo_document_signatures', $signatures );
	}

	/**
	 * Get document signatures.
	 *
	 * @param int $document_id Document ID.
	 *
	 * @return array Signatures array.
	 */
	public function get_signatures( int $document_id ): array {
		$signatures = get_post_meta( $document_id, '_apollo_document_signatures', true );

		return is_array( $signatures ) ? $signatures : array();
	}

	/**
	 * Check if user can sign document.
	 *
	 * @param int $document_id Document ID.
	 * @param int $user_id     User ID.
	 *
	 * @return bool True if user can sign.
	 */
	public function user_can_sign( int $document_id, int $user_id ): bool {
		// Allow filter for custom permission logic.
		$can_sign = apply_filters(
			'apollo_user_can_sign_document',
			current_user_can( 'edit_post', $document_id ),
			$document_id,
			$user_id
		);

		return (bool) $can_sign;
	}

	/**
	 * Verify a signed document.
	 *
	 * @param string $pdf_path Path to PDF.
	 * @param array  $options  Verification options.
	 *
	 * @return array|WP_Error Verification result.
	 */
	public function verify_document( string $pdf_path, array $options = array() ): array|WP_Error {
		if ( ! $this->active_backend ) {
			return new WP_Error(
				'apollo_verify_no_backend',
				__( 'Nenhum backend de verificação disponível.', 'apollo-social' ),
				array( 'status' => 503 )
			);
		}

		return $this->active_backend->verify( $pdf_path, $options );
	}

	/**
	 * Get backends info for admin UI.
	 *
	 * @return array Backends information.
	 */
	public function get_backends_info(): array {
		$info = array();

		foreach ( $this->backends as $id => $backend ) {
			$info[ $id ] = array(
				'identifier'   => $backend->get_identifier(),
				'name'         => $backend->get_name(),
				'available'    => $backend->is_available(),
				'capabilities' => $backend->get_capabilities(),
				'active'       => $this->active_backend === $backend,
			);
		}

		return $info;
	}
}
