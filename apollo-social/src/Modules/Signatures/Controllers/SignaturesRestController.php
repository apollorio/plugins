<?php
/**
 * Signatures REST Controller
 *
 * REST API endpoints for document signing operations.
 *
 * @package Apollo\Modules\Signatures\Controllers
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

declare( strict_types=1 );

namespace Apollo\Modules\Signatures\Controllers;

use Apollo\Modules\Signatures\Services\DocumentSignatureService;
use Apollo\Modules\Signatures\AuditLog;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class SignaturesRestController
 *
 * Handles REST API requests for signatures.
 */
class SignaturesRestController extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'apollo-social/v1';

	/**
	 * Resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'documents';

	/**
	 * Signature service instance.
	 *
	 * @var DocumentSignatureService
	 */
	private DocumentSignatureService $signature_service;

	/**
	 * Audit log instance.
	 *
	 * @var AuditLog
	 */
	private AuditLog $audit;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->signature_service = new DocumentSignatureService();
		$this->audit             = new AuditLog();
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// POST /documents/{id}/sign - Sign a document.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/sign',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'sign_document' ),
					'permission_callback' => array( $this, 'sign_permission_check' ),
					'args'                => $this->get_sign_args(),
				),
			)
		);

		// GET /documents/{id}/signatures - Get document signatures.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/signatures',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_signatures' ),
					'permission_callback' => array( $this, 'read_permission_check' ),
				),
			)
		);

		// POST /documents/{id}/verify - Verify document signature.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/verify',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'verify_document' ),
					// Public verification.
					'permission_callback' => '__return_true',
				),
			)
		);

		// GET /signatures/backends - Get available backends.
		register_rest_route(
			$this->namespace,
			'/signatures/backends',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_backends' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
			)
		);

		// POST /signatures/backends/set - Set active backend.
		register_rest_route(
			$this->namespace,
			'/signatures/backends/set',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_backend' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
					'args'                => array(
						'backend' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Sign a document.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function sign_document( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$document_id = (int) $request->get_param( 'id' );
		$user_id     = get_current_user_id();

		$cert_type = $request->get_param( 'certificate_type' );
		$cert_path = $request->get_param( 'certificate_path' );
		$cert_pass = $request->get_param( 'certificate_pass' );
		$reason    = $request->get_param( 'reason' );
		$location  = $request->get_param( 'location' );

		$options = array(
			'certificate_type' => $cert_type ? $cert_type : 'A1',
			'certificate_path' => $cert_path ? $cert_path : '',
			'certificate_pass' => $cert_pass ? $cert_pass : '',
			'reason'           => $reason ? $reason : '',
			'location'         => $location ? $location : get_bloginfo( 'name' ),
			'visible'          => (bool) $request->get_param( 'visible' ),
			'page'             => (int) $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1,
			'position'         => $request->get_param( 'position' ) ? $request->get_param( 'position' ) : array(),
		);

		$result = $this->signature_service->sign_document( $document_id, $user_id, $options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get last signature for response.
		$signatures     = $this->signature_service->get_signatures( $document_id );
		$last_signature = end( $signatures );

		$last_sig_data = null;
		if ( $last_signature ) {
			$last_sig_data = array(
				'user_name'        => $last_signature['user_name'] ?? '',
				'timestamp'        => $last_signature['timestamp'] ?? '',
				'certificate_type' => $last_signature['certificate_type'] ?? '',
			);
		}

		return new WP_REST_Response(
			array(
				'success'        => true,
				'message'        => __( 'Documento assinado com sucesso.', 'apollo-social' ),
				'signature_id'   => $result['signature_id'] ?? '',
				'timestamp'      => $result['timestamp'] ?? '',
				'backend'        => $result['backend'] ?? '',
				'is_stub'        => $result['is_stub'] ?? false,
				'last_signature' => $last_sig_data,
			),
			200
		);
	}

	/**
	 * Get document signatures.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_signatures( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$document_id = (int) $request->get_param( 'id' );

		$signatures = $this->signature_service->get_signatures( $document_id );

		// Mask sensitive data.
		$safe_signatures = array_map(
			function ( $sig ) {
				// Remove certificate password if present.
				unset( $sig['certificate_pass'] );

				// Mask CPF.
				if ( ! empty( $sig['cpf'] ) ) {
					$cpf        = preg_replace( '/[^0-9]/', '', $sig['cpf'] );
					$sig['cpf'] = substr( $cpf, 0, 3 ) . '.***.***-' . substr( $cpf, -2 );
				}

				return $sig;
			},
			$signatures
		);

		return new WP_REST_Response(
			array(
				'document_id' => $document_id,
				'count'       => count( $safe_signatures ),
				'signatures'  => $safe_signatures,
			),
			200
		);
	}

	/**
	 * Verify document signature.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function verify_document( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$document_id = (int) $request->get_param( 'id' );

		// Get document.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query required.
		$document = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_documents WHERE id = %d",
				$document_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			return new WP_Error(
				'apollo_document_not_found',
				__( 'Documento não encontrado.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		$pdf_path = $document['pdf_path'] ?? '';

		if ( empty( $pdf_path ) || ! file_exists( $pdf_path ) ) {
			return new WP_Error(
				'apollo_pdf_not_found',
				__( 'PDF do documento não encontrado.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		// Verify using backend.
		$result = $this->signature_service->verify_document( $pdf_path );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get verification report from audit.
		$report = $this->audit->generateVerificationReport( $document_id );

		return new WP_REST_Response(
			array(
				'valid'            => $result['valid'] ?? false,
				'document'         => array(
					'id'      => $document['id'],
					'file_id' => $document['file_id'],
					'title'   => $document['title'],
					'status'  => $document['status'],
				),
				'verification'     => $result,
				'protocol'         => $report['protocol'] ?? null,
				'signatures_count' => $report['signatures_summary']['signed'] ?? 0,
				'verified_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			),
			200
		);
	}

	/**
	 * Get available backends.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response.
	 */
	public function get_backends( WP_REST_Request $request ): WP_REST_Response {
		$backends = $this->signature_service->get_backends_info();
		$active   = $this->signature_service->get_active_backend();

		return new WP_REST_Response(
			array(
				'backends' => $backends,
				'active'   => $active ? $active->get_identifier() : null,
			),
			200
		);
	}

	/**
	 * Set active backend.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function set_backend( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$backend_id = $request->get_param( 'backend' );

		$success = $this->signature_service->set_active_backend( $backend_id );

		if ( ! $success ) {
			return new WP_Error(
				'apollo_backend_not_available',
				__( 'Backend não disponível ou não encontrado.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		update_option( 'apollo_signature_backend', $backend_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'active'  => $backend_id,
				'message' => sprintf(
					/* translators: %s: backend identifier */
					__( 'Backend "%s" ativado com sucesso.', 'apollo-social' ),
					$backend_id
				),
			),
			200
		);
	}

	/**
	 * Get sign endpoint arguments.
	 *
	 * @return array Endpoint arguments.
	 */
	private function get_sign_args(): array {
		return array(
			'certificate_type' => array(
				'type'              => 'string',
				'enum'              => array( 'A1', 'A3' ),
				'default'           => 'A1',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'certificate_path' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'certificate_pass' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'reason'           => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'location'         => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'visible'          => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'page'             => array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'position'         => array(
				'type'    => 'object',
				'default' => array(),
			),
		);
	}

	/**
	 * Check sign permission.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function sign_permission_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'apollo_rest_not_logged_in',
				__( 'Você precisa estar logado para assinar documentos.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		$document_id = (int) $request->get_param( 'id' );

		if ( ! $this->signature_service->user_can_sign( $document_id, get_current_user_id() ) ) {
			return new WP_Error(
				'apollo_rest_forbidden',
				__( 'Você não tem permissão para assinar este documento.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check read permission.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function read_permission_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'apollo_rest_not_logged_in',
				__( 'Você precisa estar logado para ver assinaturas.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Check admin permission.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function admin_permission_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'apollo_rest_forbidden',
				__( 'Acesso restrito a administradores.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
