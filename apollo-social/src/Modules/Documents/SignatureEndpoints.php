<?php
/**
 * Apollo Signature REST Endpoints.
 *
 * Endpoints REST para todas as funcionalidades de assinatura:
 * - Upload e assinatura com certificado ICP-Brasil.
 * - Assinatura eletrônica (canvas).
 * - Verificação de documentos.
 * - Geração de protocolos.
 * - Auditoria.
 *
 * @package Apollo\Modules\Documents
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
 * phpcs:disable Squiz.Commenting.FunctionComment.ParamCommentFullStop
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput
 * phpcs:disable WordPressVIPMinimum.Variables
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Modules\Signatures\IcpBrasilSigner;
use Apollo\Modules\Signatures\AuditLog;

/**
 * Signature Endpoints
 */
class SignatureEndpoints {

	/** @var string Namespace */
	private const NAMESPACE = 'apollo-docs/v1';

	/** @var DocumentLibraries */
	private DocumentLibraries $libraries;

	/** @var IcpBrasilSigner */
	private IcpBrasilSigner $signer;

	/** @var AuditLog */
	private AuditLog $audit;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->libraries = new DocumentLibraries();
		$this->signer    = new IcpBrasilSigner();
		$this->audit     = new AuditLog();
	}

	/**
	 * Register REST routes
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
	}

	/**
	 * Register routes
	 */
	public function registerRoutes(): void {
		// TEMP: Xdebug breakpoint para depuração Apollo.
		if ( function_exists( 'xdebug_break' ) ) {
			xdebug_break();
		}

		// ===== DOCUMENT LIBRARY ENDPOINTS =====

		// Get documents by library
		register_rest_route(
			self::NAMESPACE,
			'/library/(?P<library>[a-z]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getLibraryDocuments' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'library'  => [
						'required'          => true,
						'validate_callback' => fn( $v ) => in_array( $v, [ 'apollo', 'cenario', 'private' ], true ),
					],
					'status'   => [ 'type' => 'string' ],
					'type'     => [ 'type' => 'string' ],
					'search'   => [ 'type' => 'string' ],
					'page'     => [
						'type'    => 'integer',
						'default' => 1,
					],
					'per_page' => [
						'type'    => 'integer',
						'default' => 20,
					],
				],
			]
		);

		// Get library stats
		register_rest_route(
			self::NAMESPACE,
			'/library/(?P<library>[a-z]+)/stats',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getLibraryStats' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
			]
		);

		// Create document
		register_rest_route(
			self::NAMESPACE,
			'/document',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'createDocument' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'library' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'title'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'type'    => [
						'type'              => 'string',
						'default'           => 'document',
						'sanitize_callback' => 'sanitize_key',
					],
					'content' => [
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					],
				],
			]
		);

		// Get document
		register_rest_route(
			self::NAMESPACE,
			'/document/(?P<file_id>[a-zA-Z0-9]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getDocument' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
			]
		);

		// Update document
		register_rest_route(
			self::NAMESPACE,
			'/document/(?P<file_id>[a-zA-Z0-9]+)',
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'updateDocument' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'title'        => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'content'      => [
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					],
					'html_content' => [
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					],
				],
			]
		);

		// Finalize document
		register_rest_route(
			self::NAMESPACE,
			'/document/(?P<file_id>[a-zA-Z0-9]+)/finalize',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'finalizeDocument' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
			]
		);

		// Move document
		register_rest_route(
			self::NAMESPACE,
			'/document/(?P<file_id>[a-zA-Z0-9]+)/move',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'moveDocument' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'target_library' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);

		// ===== SIGNATURE ENDPOINTS =====

		// Sign with certificate (ICP-Brasil)
		register_rest_route(
			self::NAMESPACE,
			'/sign/certificate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'signWithCertificate' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'document_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'certificate' => [
						'required' => true,
						'type'     => 'string',
					],
					'password'    => [
						'required' => true,
						'type'     => 'string',
					],
					'name'        => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'cpf'         => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// Sign with canvas (electronic)
		register_rest_route(
			self::NAMESPACE,
			'/sign/canvas',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'signWithCanvas' ],
				'permission_callback' => '__return_true',
				// Public for external signers - token validated in callback.
				'args'                => [
					'token'     => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'signature' => [
						'required' => true,
						'type'     => 'string',
					],
					'name'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'cpf'       => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'email'     => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
					],
				],
			]
		);

		// Request signature from another person
		register_rest_route(
			self::NAMESPACE,
			'/sign/request',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'requestSignature' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'document_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'party'       => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'email'       => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
					],
					'name'        => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// ===== VERIFICATION ENDPOINTS =====

		// Verify by protocol (public)
		register_rest_route(
			self::NAMESPACE,
			'/verify/protocol/(?P<code>[A-Z0-9-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'verifyByProtocol' ],
				'permission_callback' => '__return_true',
			]
		);

		// Verify by hash (public)
		register_rest_route(
			self::NAMESPACE,
			'/verify/hash',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'verifyByHash' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'hash' => [
						'required' => true,
						'type'     => 'string',
					],
				],
			]
		);

		// Verify PDF file (public)
		register_rest_route(
			self::NAMESPACE,
			'/verify/file',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'verifyFile' ],
				'permission_callback' => '__return_true',
			]
		);

		// ===== AUDIT ENDPOINTS =====

		// Get document audit log
		register_rest_route(
			self::NAMESPACE,
			'/audit/(?P<file_id>[a-zA-Z0-9]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getAuditLog' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
			]
		);

		// Generate verification report
		register_rest_route(
			self::NAMESPACE,
			'/audit/(?P<file_id>[a-zA-Z0-9]+)/report',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getVerificationReport' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
			]
		);

		// Generate protocol
		register_rest_route(
			self::NAMESPACE,
			'/protocol/generate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'generateProtocol' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'document_id' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			]
		);

		// ===== TEMPLATE ENDPOINTS =====

		// Get templates
		register_rest_route(
			self::NAMESPACE,
			'/templates',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getTemplates' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'category' => [ 'type' => 'string' ],
				],
			]
		);

		// Create from template
		register_rest_route(
			self::NAMESPACE,
			'/templates/(?P<file_id>[a-zA-Z0-9]+)/use',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'createFromTemplate' ],
				'permission_callback' => [ $this, 'checkAuthenticated' ],
				'args'                => [
					'target_library' => [
						'type'    => 'string',
						'default' => 'private',
					],
				],
			]
		);
	}

	// ===== DOCUMENT LIBRARY HANDLERS =====

	/**
	 * Get library documents
	 */
	public function getLibraryDocuments( \WP_REST_Request $request ): \WP_REST_Response {
		// TEMP: Xdebug breakpoint para depuração Apollo.
		if ( function_exists( 'xdebug_break' ) ) {
			xdebug_break();
		}

		$library = $request->get_param( 'library' );
		$args    = [
			'status'   => $request->get_param( 'status' ),
			'type'     => $request->get_param( 'type' ),
			'search'   => $request->get_param( 'search' ),
			'page'     => $request->get_param( 'page' ),
			'per_page' => $request->get_param( 'per_page' ),
		];

		$result = $this->libraries->getDocumentsByLibrary( $library, null, array_filter( $args ) );

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Get library stats
	 */
	public function getLibraryStats( \WP_REST_Request $request ): \WP_REST_Response {
		$library = $request->get_param( 'library' );
		$stats   = $this->libraries->getLibraryStats( $library );

		return new \WP_REST_Response(
			[
				'library'      => $library,
				'library_info' => DocumentLibraries::LIBRARY_TYPES[ $library ] ?? null,
				'stats'        => $stats,
			],
			200
		);
	}

	/**
	 * Create document
	 */
	public function createDocument( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->libraries->createDocument(
			$request->get_param( 'library' ),
			[
				'type'    => $request->get_param( 'type' ),
				'title'   => $request->get_param( 'title' ),
				'content' => $request->get_param( 'content' ),
			]
		);

		$status = $result['success'] ? 201 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Get document
	 */
	public function getDocument( \WP_REST_Request $request ): \WP_REST_Response {
		$document = $this->libraries->getDocument( $request->get_param( 'file_id' ) );

		if ( ! $document ) {
			return new \WP_REST_Response( [ 'error' => 'Documento não encontrado' ], 404 );
		}

		return new \WP_REST_Response( $document, 200 );
	}

	/**
	 * Update document
	 */
	public function updateDocument( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->libraries->updateDocument(
			$request->get_param( 'file_id' ),
			[
				'title'        => $request->get_param( 'title' ),
				'content'      => $request->get_param( 'content' ),
				'html_content' => $request->get_param( 'html_content' ),
			]
		);

		$status = $result['success'] ? 200 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Finalize document
	 */
	public function finalizeDocument( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->libraries->finalizeDocument( $request->get_param( 'file_id' ) );

		if ( $result['success'] ) {
			// Generate protocol automatically
			$document = $this->libraries->getDocument( $request->get_param( 'file_id' ) );
			if ( $document && $document['pdf_hash'] ) {
				$protocol           = $this->audit->generateProtocol( (int) $document['id'], $document['pdf_hash'] );
				$result['protocol'] = $protocol;
			}
		}

		$status = $result['success'] ? 200 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Move document
	 */
	public function moveDocument( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->libraries->moveDocument(
			$request->get_param( 'file_id' ),
			$request->get_param( 'target_library' )
		);

		$status = $result['success'] ? 200 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	// ===== SIGNATURE HANDLERS =====

	/**
	 * Sign with certificate
	 */
	public function signWithCertificate( \WP_REST_Request $request ): \WP_REST_Response {
		// TEMP: Xdebug breakpoint para depuração Apollo.
		if ( function_exists( 'xdebug_break' ) ) {
			xdebug_break();
		}

		global $wpdb;

		$document_id = $request->get_param( 'document_id' );

		// Get document
		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$documents_table} WHERE id = %d", $document_id ),
			ARRAY_A
		);

		if ( ! $document ) {
			return new \WP_REST_Response( [ 'error' => 'Documento não encontrado' ], 404 );
		}

		if ( ! $document['pdf_path'] ) {
			return new \WP_REST_Response( [ 'error' => 'Documento precisa ser finalizado antes de assinar' ], 400 );
		}

		// Get full PDF path
		$pdf_path = ABSPATH . ltrim( $document['pdf_path'], '/' );

		// Sign
		$result = $this->signer->signWithCertificate(
			$pdf_path,
			$request->get_param( 'certificate' ),
			$request->get_param( 'password' ),
			[
				'name' => $request->get_param( 'name' ),
				'cpf'  => $request->get_param( 'cpf' ),
			]
		);

		if ( $result['success'] ) {
			// Log signature
			$this->audit->logSignature(
				$document_id,
				$result['signer'],
				$result['hash'],
				$document['pdf_hash'] ?? ''
			);

			// Update document status
			$wpdb->update(
				$documents_table,
				[
					'status'   => 'signing',
					'pdf_path' => str_replace( ABSPATH, '/', $result['signed_pdf_path'] ),
				],
				[ 'id' => $document_id ]
			);
		}

		$status = $result['success'] ? 200 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Sign with canvas
	 */
	public function signWithCanvas( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$token = $request->get_param( 'token' );

		// Get signature request
		$signatures_table  = $wpdb->prefix . 'apollo_document_signatures';
		$signature_request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$signatures_table} WHERE verification_token = %s AND status = 'pending'",
				$token
			),
			ARRAY_A
		);

		if ( ! $signature_request ) {
			return new \WP_REST_Response( [ 'error' => 'Token inválido ou já utilizado' ], 400 );
		}

		// Get document
		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$documents_table} WHERE id = %d", $signature_request['document_id'] ),
			ARRAY_A
		);

		if ( ! $document || ! $document['pdf_path'] ) {
			return new \WP_REST_Response( [ 'error' => 'Documento não encontrado' ], 404 );
		}

		// Validate CPF
		$cpf = preg_replace( '/[^0-9]/', '', $request->get_param( 'cpf' ) );
		if ( ! $this->validateCpf( $cpf ) ) {
			return new \WP_REST_Response( [ 'error' => 'CPF inválido' ], 400 );
		}

		// Get full PDF path
		$pdf_path = ABSPATH . ltrim( $document['pdf_path'], '/' );

		// Sign
		$result = $this->signer->signWithCanvas(
			$pdf_path,
			$request->get_param( 'signature' ),
			[
				'name'  => $request->get_param( 'name' ),
				'cpf'   => $cpf,
				'email' => $request->get_param( 'email' ),
			]
		);

		if ( $result['success'] ) {
			// Update signature request
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
				: '';
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
				: '';

			$wpdb->update(
				$signatures_table,
				[
					'signer_name'    => sanitize_text_field( $request->get_param( 'name' ) ),
					'signer_cpf'     => $cpf,
					'signer_email'   => sanitize_email( $request->get_param( 'email' ) ),
					'signature_data' => $request->get_param( 'signature' ),
					'status'         => 'signed',
					'signed_at'      => current_time( 'mysql' ),
					'ip_address'     => $ip_address,
					'user_agent'     => $user_agent,
				],
				[ 'id' => $signature_request['id'] ]
			);

			// Log signature
			$this->audit->logSignature(
				(int) $document['id'],
				[
					'name'  => sanitize_text_field( $request->get_param( 'name' ) ),
					'cpf'   => $cpf,
					'email' => sanitize_email( $request->get_param( 'email' ) ),
					'type'  => 'electronic',
				],
				$result['hash'],
				$document['pdf_hash'] ?? ''
			);

			// Update document
			$wpdb->update(
				$documents_table,
				[ 'pdf_path' => str_replace( ABSPATH, '/', $result['signed_pdf_path'] ) ],
				[ 'id' => $document['id'] ]
			);

			// Check if all signatures complete
			$pending = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$signatures_table} WHERE document_id = %d AND status = 'pending'",
					$document['id']
				)
			);

			if ( 0 === (int) $pending ) {
				$wpdb->update( $documents_table, [ 'status' => 'completed' ], [ 'id' => $document['id'] ] );
			}
		}//end if

		$status = $result['success'] ? 200 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Request signature
	 */
	public function requestSignature( \WP_REST_Request $request ): \WP_REST_Response {
		$manager = new DocumentsManager();

		$result = $manager->createSignatureRequest(
			$request->get_param( 'document_id' ),
			$request->get_param( 'party' ),
			$request->get_param( 'email' ),
			$request->get_param( 'name' ) ?? ''
		);

		$status = $result['success'] ? 201 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	// ===== VERIFICATION HANDLERS =====

	/**
	 * Verify by protocol
	 */
	public function verifyByProtocol( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->audit->verifyByProtocol( $request->get_param( 'code' ) );

		$status = $result['valid'] ? 200 : 404;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Verify by hash
	 */
	public function verifyByHash( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->audit->verifyByHash( $request->get_param( 'hash' ) );

		$status = $result['valid'] ? 200 : 404;
		return new \WP_REST_Response( $result, $status );
	}

	/**
	 * Verify uploaded file
	 */
	public function verifyFile( \WP_REST_Request $request ): \WP_REST_Response {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			return new \WP_REST_Response( [ 'error' => 'Nenhum arquivo enviado' ], 400 );
		}

		$file = $files['file'];

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new \WP_REST_Response( [ 'error' => 'Erro no upload' ], 400 );
		}

		// Calculate hash
		$hash = hash_file( 'sha256', $file['tmp_name'] );

		// Verify by hash
		$result = $this->audit->verifyByHash( $hash );

		// Also try to verify signatures in the PDF
		$signature_verification = $this->signer->verifySignature( $file['tmp_name'] );

		$result['signature_verification'] = $signature_verification;
		$result['file_hash']              = $hash;

		return new \WP_REST_Response( $result, 200 );
	}

	// ===== AUDIT HANDLERS =====

	/**
	 * Get audit log
	 */
	public function getAuditLog( \WP_REST_Request $request ): \WP_REST_Response {
		$document = $this->libraries->getDocument( $request->get_param( 'file_id' ) );

		if ( ! $document ) {
			return new \WP_REST_Response( [ 'error' => 'Documento não encontrado' ], 404 );
		}

		$logs = $this->audit->getDocumentLogs( (int) $document['id'] );

		return new \WP_REST_Response(
			[
				'document' => [
					'file_id' => $document['file_id'],
					'title'   => $document['title'],
				],
				'logs'     => $logs,
			],
			200
		);
	}

	/**
	 * Get verification report
	 */
	public function getVerificationReport( \WP_REST_Request $request ): \WP_REST_Response {
		$document = $this->libraries->getDocument( $request->get_param( 'file_id' ) );

		if ( ! $document ) {
			return new \WP_REST_Response( [ 'error' => 'Documento não encontrado' ], 404 );
		}

		$report = $this->audit->generateVerificationReport( (int) $document['id'] );

		return new \WP_REST_Response( $report, 200 );
	}

	/**
	 * Generate protocol
	 */
	public function generateProtocol( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$document_id = $request->get_param( 'document_id' );

		// Get document hash
		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare( "SELECT pdf_hash FROM {$documents_table} WHERE id = %d", $document_id ),
			ARRAY_A
		);

		if ( ! $document || ! $document['pdf_hash'] ) {
			return new \WP_REST_Response( [ 'error' => 'Documento precisa ser finalizado primeiro' ], 400 );
		}

		$result = $this->audit->generateProtocol( $document_id, $document['pdf_hash'] );

		$status = $result['success'] ? 200 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	// ===== TEMPLATE HANDLERS =====

	/**
	 * Get templates
	 */
	public function getTemplates( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->libraries->getTemplates( $request->get_param( 'category' ) );

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Create from template
	 */
	public function createFromTemplate( \WP_REST_Request $request ): \WP_REST_Response {
		$result = $this->libraries->createFromTemplate(
			$request->get_param( 'file_id' ),
			$request->get_param( 'target_library' )
		);

		$status = $result['success'] ? 201 : 400;
		return new \WP_REST_Response( $result, $status );
	}

	// ===== PERMISSION CALLBACKS =====

	/**
	 * Check if user is authenticated
	 */
	public function checkAuthenticated(): bool {
		return is_user_logged_in();
	}

	// ===== HELPERS =====

	/**
	 * Validate CPF
	 */
	private function validateCpf( string $cpf ): bool {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( strlen( $cpf ) !== 11 || preg_match( '/(\d)\1{10}/', $cpf ) ) {
			return false;
		}

		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$sum += (int) $cpf[ $i ] * ( 10 - $i );
		}
		$digit1 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );

		if ( (int) $cpf[9] !== $digit1 ) {
			return false;
		}

		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$sum += (int) $cpf[ $i ] * ( 11 - $i );
		}
		$digit2 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );

		return (int) $cpf[10] === $digit2;
	}
}
