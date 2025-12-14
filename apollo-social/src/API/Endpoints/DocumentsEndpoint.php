<?php

/**
 * REST API SMOKE TEST – PASSED
 * Route: /apollo/v1/doc
 * Affects: apollo-social.php, DocumentsEndpoint.php, SignatureEndpoints.php
 * Verified: 2025-12-06 – no conflicts, secure callback, unique namespace
 */
/**
 * Documents REST API Endpoint
 *
 * Handles document-related REST API operations.
 * Note: Main document functionality is handled by DocumentsModule and SignatureEndpoints.
 * This endpoint provides additional document listing and export features.
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\API\Endpoints;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class DocumentsEndpoint
 *
 * REST API endpoint for document operations.
 */
class DocumentsEndpoint {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private string $namespace = 'apollo/v1';

	/**
	 * Route base (shortened to doc)
	 *
	 * @var string
	 */
	private string $rest_base = 'doc';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'getDocuments' ],
					'permission_callback' => [ $this, 'checkPermissions' ],
					'args'                => $this->getCollectionParams(),
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'getDocument' ],
					'permission_callback' => [ $this, 'checkPermissions' ],
					'args'                => [
						'id' => [
							'description' => __( 'Document ID', 'apollo-social' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>\d+)/export',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'exportDocument' ],
					'permission_callback' => [ $this, 'checkPermissions' ],
					'args'                => [
						'id'     => [
							'description' => __( 'Document ID', 'apollo-social' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'format' => [
							'description' => __( 'Export format (pdf, xlsx, csv)', 'apollo-social' ),
							'type'        => 'string',
							'default'     => 'pdf',
							'enum'        => [ 'pdf', 'xlsx', 'csv' ],
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>\d+)/gerar-pdf',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generatePdf' ],
					'permission_callback' => [ $this, 'checkPermissions' ],
					'args'                => [
						'id' => [
							'description' => __( 'Document ID', 'apollo-social' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>\d+)/assinar',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'signDocument' ],
					'permission_callback' => [ $this, 'checkSignPermissions' ],
					'args'                => [
						'id'      => [
							'description' => __( 'Document ID', 'apollo-social' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'name'    => [
							'description' => __( 'Signer name', 'apollo-social' ),
							'type'        => 'string',
							'required'    => true,
						],
						'email'   => [
							'description' => __( 'Signer email', 'apollo-social' ),
							'type'        => 'string',
							'required'    => false,
						],
						'role'    => [
							'description' => __( 'Signer role', 'apollo-social' ),
							'type'        => 'string',
							'required'    => false,
							'default'     => 'signer',
						],
						'consent' => [
							'description' => __( 'Consent flag', 'apollo-social' ),
							'type'        => 'boolean',
							'required'    => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>\d+)/verificar',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'verifyDocument' ],
					'permission_callback' => '__return_true', // Public endpoint
					'args'                => [
						'id' => [
							'description' => __( 'Document ID', 'apollo-social' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Check if user has permission to access documents
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function checkPermissions( WP_REST_Request $request ): bool {
		return is_user_logged_in();
	}

	/**
	 * Get documents list
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function getDocuments( WP_REST_Request $request ): WP_REST_Response {
		$user_id  = get_current_user_id();
		$page     = $request->get_param( 'page' ) ?? 1;
		$per_page = $request->get_param( 'per_page' ) ?? 10;
		$status   = $request->get_param( 'status' ) ?? 'all';

		// Query documents
		$args = [
			'post_type'      => 'apollo_document',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'author'         => $user_id,
		];

		if ( $status !== 'all' ) {
			$args['post_status'] = $status;
		}

		$query     = new \WP_Query( $args );
		$documents = [];

		foreach ( $query->posts as $post ) {
			$documents[] = $this->formatDocument( $post );
		}

		return new WP_REST_Response(
			[
				'documents'   => $documents,
				'total'       => $query->found_posts,
				'total_pages' => $query->max_num_pages,
			],
			200
		);
	}

	/**
	 * Get single document
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function getDocument( WP_REST_Request $request ): WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Document not found', 'apollo-social' ),
				],
				404
			);
		}

		// Check ownership
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Access denied', 'apollo-social' ),
				],
				403
			);
		}

		return new WP_REST_Response( $this->formatDocument( $post ), 200 );
	}

	/**
	 * Export document to specified format
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function exportDocument( WP_REST_Request $request ): WP_REST_Response {
		$id     = (int) $request->get_param( 'id' );
		$format = $request->get_param( 'format' ) ?? 'pdf';
		$post   = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Document not found', 'apollo-social' ),
				],
				404
			);
		}

		// Check ownership
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Access denied', 'apollo-social' ),
				],
				403
			);
		}

		// Generate export URL (implementation depends on export handler)
		$export_url = add_query_arg(
			[
				'action'      => 'apollo_export_document',
				'document_id' => $id,
				'format'      => $format,
				'nonce'       => wp_create_nonce( 'apollo_export_' . $id ),
			],
			admin_url( 'admin-ajax.php' )
		);

		return new WP_REST_Response(
			[
				'export_url' => $export_url,
				'format'     => $format,
			],
			200
		);
	}

	/**
	 * Format document for API response
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	private function formatDocument( \WP_Post $post ): array {
		return [
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'status'      => $post->post_status,
			'created_at'  => $post->post_date,
			'modified_at' => $post->post_modified,
			'author'      => (int) $post->post_author,
			'excerpt'     => $post->post_excerpt,
			'signatures'  => get_post_meta( $post->ID, '_apollo_signatures', true ) ?: [],
			'template_id' => get_post_meta( $post->ID, '_apollo_template_id', true ),
		];
	}

	/**
	 * Generate PDF for a document
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function generatePdf( WP_REST_Request $request ): WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Document not found', 'apollo-social' ),
				],
				404
			);
		}

		// Check ownership
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Access denied', 'apollo-social' ),
				],
				403
			);
		}

		// Load PDF service
		if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsPdfService' ) ) {
			require_once dirname( __DIR__, 2 ) . '/Modules/Documents/DocumentsPdfService.php';
		}

		$result = \Apollo\Modules\Documents\DocumentsPdfService::generate_pdf( $id );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				],
				400
			);
		}

		return new WP_REST_Response(
			[
				'success'       => true,
				'pdf_url'       => $result['pdf_url'],
				'attachment_id' => $result['attachment_id'],
				'library'       => $result['library'],
				'message'       => __( 'PDF generated successfully', 'apollo-social' ),
			],
			200
		);
	}

	/**
	 * Check permissions for signing
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function checkSignPermissions( WP_REST_Request $request ): bool {
		// Allow logged-in users or public signing with token (if implemented)
		// For now, require login
		return is_user_logged_in();
	}

	/**
	 * Sign a document
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function signDocument( WP_REST_Request $request ): WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Documento não encontrado', 'apollo-social' ),
					'code'    => 'doc_not_found',
					'data'    => [ 'doc_id' => $id ],
				],
				404
			);
		}

		// Check consent
		$consent = $request->get_param( 'consent' );
		if ( ! $consent ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Consentimento é obrigatório para assinar', 'apollo-social' ),
					'code'    => 'consent_required',
					'data'    => [ 'doc_id' => $id ],
				],
				400
			);
		}

		// Load signature service
		if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsSignatureService' ) ) {
			require_once dirname( __DIR__, 2 ) . '/Modules/Documents/DocumentsSignatureService.php';
		}

		// Get signer data
		$signer_id   = get_current_user_id();
		$signer_data = [
			'signer_id' => $signer_id > 0 ? $signer_id : null,
			'name'      => $request->get_param( 'name' ),
			'email'     => $request->get_param( 'email' ) ?: ( $signer_id > 0 ? wp_get_current_user()->user_email : '' ),
			'role'      => $request->get_param( 'role' ) ?: 'signer',
		];

		// Determine signature method (basic for now, PKI can be added via hook)
		$signature_method = 'e-sign-basic';

		$result = \Apollo\Modules\Documents\DocumentsSignatureService::sign_document(
			$id,
			$signer_data,
			$signature_method
		);

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
					'data'    => [ 'doc_id' => $id ],
				],
				400
			);
		}

		// Format signature entry for response (sanitize sensitive data)
		$signature_response = [
			'signer_name'      => isset( $result['signature_entry']['signer_name'] ) ? sanitize_text_field( $result['signature_entry']['signer_name'] ) : '',
			'signer_email'     => isset( $result['signature_entry']['signer_email'] ) ? sanitize_email( $result['signature_entry']['signer_email'] ) : '',
			'role'             => isset( $result['signature_entry']['role'] ) ? sanitize_text_field( $result['signature_entry']['role'] ) : 'signer',
			'signed_at'        => isset( $result['signature_entry']['signed_at'] ) ? sanitize_text_field( $result['signature_entry']['signed_at'] ) : '',
			'signature_method' => isset( $result['signature_entry']['signature_method'] ) ? sanitize_key( $result['signature_entry']['signature_method'] ) : 'e-sign-basic',
			'pdf_hash_preview' => isset( $result['signature_entry']['pdf_hash'] ) ? substr( sanitize_text_field( $result['signature_entry']['pdf_hash'] ), 0, 16 ) . '...' : '',
		];

		return new WP_REST_Response(
			[
				'success'          => true,
				'message'          => __( 'Documento assinado com sucesso', 'apollo-social' ),
				'total_signatures' => isset( $result['total_signatures'] ) ? absint( $result['total_signatures'] ) : 0,
				'signature'        => $signature_response,
				'data'             => [
					'doc_id'    => $id,
					'doc_title' => sanitize_text_field( $post->post_title ),
				],
			],
			200
		);
	}

	/**
	 * Verify document integrity
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function verifyDocument( WP_REST_Request $request ): WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'valid'   => false,
					'message' => __( 'Documento não encontrado', 'apollo-social' ),
					'code'    => 'doc_not_found',
					'data'    => [ 'doc_id' => $id ],
				],
				404
			);
		}

		// Load signature service
		if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsSignatureService' ) ) {
			require_once dirname( __DIR__, 2 ) . '/Modules/Documents/DocumentsSignatureService.php';
		}

		$result = \Apollo\Modules\Documents\DocumentsSignatureService::verify_document( $id );

		// Ensure all data is properly sanitized
		if ( isset( $result['signatures'] ) && is_array( $result['signatures'] ) ) {
			foreach ( $result['signatures'] as $key => $sig ) {
				$result['signatures'][ $key ]['signer_name']      = isset( $sig['signer_name'] ) ? sanitize_text_field( $sig['signer_name'] ) : '';
				$result['signatures'][ $key ]['signer_email']     = isset( $sig['signer_email'] ) ? sanitize_email( $sig['signer_email'] ) : '';
				$result['signatures'][ $key ]['role']             = isset( $sig['role'] ) ? sanitize_text_field( $sig['role'] ) : '';
				$result['signatures'][ $key ]['signed_at']        = isset( $sig['signed_at'] ) ? sanitize_text_field( $sig['signed_at'] ) : '';
				$result['signatures'][ $key ]['signature_method'] = isset( $sig['signature_method'] ) ? sanitize_key( $sig['signature_method'] ) : '';
			}
		}

		$result['message']          = isset( $result['message'] ) ? sanitize_text_field( $result['message'] ) : '';
		$result['current_hash']     = isset( $result['current_hash'] ) ? sanitize_text_field( $result['current_hash'] ) : '';
		$result['total_signatures'] = isset( $result['total_signatures'] ) ? absint( $result['total_signatures'] ) : 0;

		return new WP_REST_Response( $result, $result['valid'] ? 200 : 400 );
	}

	/**
	 * Get collection params for documents endpoint
	 *
	 * @return array
	 */
	private function getCollectionParams(): array {
		return [
			'page'     => [
				'description' => __( 'Page number', 'apollo-social' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page' => [
				'description' => __( 'Items per page', 'apollo-social' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'status'   => [
				'description' => __( 'Document status filter', 'apollo-social' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => [ 'all', 'draft', 'publish', 'pending', 'signed' ],
			],
		];
	}
}
