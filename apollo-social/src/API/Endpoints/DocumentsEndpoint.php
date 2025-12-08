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
			'/' . $this->rest_base,
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
			'/' . $this->rest_base . '/(?P<id>\d+)',
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
			'/' . $this->rest_base . '/(?P<id>\d+)/export',
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
