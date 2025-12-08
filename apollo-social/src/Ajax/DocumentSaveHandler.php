<?php
/**
 * Apollo Social – AJAX Document Save Handler
 *
 * This file provides a secure AJAX endpoint for saving Quill editor content
 * using the Delta format. It handles both new document creation and updates
 * to existing documents.
 *
 * Delta Format Storage:
 *   Quill's Delta format is stored as JSON in the database. We store both:
 *   - Delta JSON: The structured content for editing (in post meta)
 *   - HTML: Rendered version for display (in post_content)
 *
 *   This dual storage approach provides:
 *   - Fast rendering for display (no Delta-to-HTML conversion needed)
 *   - Accurate editing (Delta preserves exact formatting)
 *   - Fallback compatibility (HTML works even without Quill)
 *
 * Security measures implemented:
 *   1. Nonce verification (CSRF protection)
 *   2. User capability check (edit_posts, edit_post)
 *   3. Delta JSON structure validation
 *   4. Content sanitization before storage
 *   5. Ownership verification for existing documents
 *
 * @package    ApolloSocial
 * @subpackage Ajax
 * @since      1.1.0
 * @author     Apollo Team
 */

namespace Apollo\Ajax;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DocumentSaveHandler
 *
 * Handles AJAX document save requests from the Quill editor.
 * Supports both creating new documents and updating existing ones.
 *
 * Usage:
 *   The JavaScript client sends a POST request to admin-ajax.php with:
 *     - action: 'apollo_save_document'
 *     - nonce: Security nonce from apolloQuillConfig
 *     - document_id: ID of existing document (empty for new)
 *     - title: Document title
 *     - delta: Delta JSON string
 *     - html: Rendered HTML content
 *
 * Response (JSON):
 *   Success: {
 *     success: true,
 *     data: {
 *       documentId: 123,
 *       editUrl: '/doc/123',
 *       message: 'Documento salvo com sucesso.'
 *     }
 *   }
 *   Failure: {
 *     success: false,
 *     data: { message: 'Error description' }
 *   }
 *
 * @since 1.1.0
 */
class DocumentSaveHandler {

	/**
	 * AJAX action name.
	 * Must match the 'saveAction' in apolloQuillConfig.
	 *
	 * @var string
	 */
	const ACTION = 'apollo_save_document';

	/**
	 * Nonce action name for verification.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'apollo_editor_image_upload';
	// Same nonce as editor

	/**
	 * Custom post type for documents.
	 *
	 * @var string
	 */
	const POST_TYPE = 'apollo_document';

	/**
	 * Meta key for storing Delta JSON.
	 *
	 * @var string
	 */
	const DELTA_META_KEY = '_apollo_document_delta';

	/**
	 * Meta key for storing document type (documento/planilha).
	 *
	 * @var string
	 */
	const TYPE_META_KEY = '_apollo_document_type';

	/**
	 * Meta key for tracking last autosave timestamp.
	 *
	 * @var string
	 */
	const AUTOSAVE_META_KEY = '_apollo_last_autosave';

	/**
	 * Constructor.
	 * Registers AJAX hooks for document saving.
	 */
	public function __construct() {
		// Register AJAX handler for logged-in users only
		// Documents should not be editable by non-authenticated users
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'handle_save' ] );

		// Ensure custom post type is registered
		add_action( 'init', [ $this, 'register_post_type' ], 5 );
	}

	/**
	 * Register the apollo_document custom post type.
	 *
	 * This CPT stores documents created with the Quill editor.
	 * It's registered with minimal UI since editing happens via our custom editor.
	 */
	public function register_post_type() {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			[
				'labels'          => [
					'name'          => __( 'Documentos', 'apollo-social' ),
					'singular_name' => __( 'Documento', 'apollo-social' ),
				],
				'public'          => false,
				'show_ui'         => false,
				'show_in_menu'    => false,
				'capability_type' => 'post',
				'hierarchical'    => false,
				'supports'        => [ 'title', 'editor', 'author', 'revisions' ],
				'has_archive'     => false,
				'rewrite'         => false,
				'query_var'       => false,
			]
		);
	}

	/**
	 * Handle the AJAX save request.
	 *
	 * This is the main entry point for document saves from the Quill editor.
	 * It validates the request, sanitizes content, and saves to the database.
	 *
	 * @return void Outputs JSON response and exits.
	 */
	public function handle_save() {
		// Step 1: Verify nonce for CSRF protection
		// The nonce is shared with the image upload handler for simplicity
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], self::NONCE_ACTION ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Sessão expirada. Recarregue a página e tente novamente.', 'apollo-social' ),
					'code'    => 'invalid_nonce',
				],
				403
			);
			return;
		}

		// Step 2: Check user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				[
					'message' => __( 'Você precisa estar logado para salvar documentos.', 'apollo-social' ),
					'code'    => 'not_logged_in',
				],
				403
			);
			return;
		}

		// Step 3: Get and validate input data
		$document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
		$delta_json  = isset( $_POST['delta'] ) ? wp_unslash( $_POST['delta'] ) : '';
		$html        = isset( $_POST['html'] ) ? wp_kses_post( wp_unslash( $_POST['html'] ) ) : '';

		// Step 4: Validate Delta JSON structure
		$delta_validation = $this->validate_delta( $delta_json );
		if ( is_wp_error( $delta_validation ) ) {
			wp_send_json_error(
				[
					'message' => $delta_validation->get_error_message(),
					'code'    => $delta_validation->get_error_code(),
				],
				400
			);
			return;
		}

		// Step 5: Handle existing document update vs new document creation
		if ( $document_id > 0 ) {
			$result = $this->update_document( $document_id, $title, $delta_json, $html );
		} else {
			$result = $this->create_document( $title, $delta_json, $html );
		}

		// Step 6: Return result
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				],
				400
			);
			return;
		}

		wp_send_json_success( $result );
	}

	/**
	 * Validate Delta JSON structure.
	 *
	 * Ensures the Delta is valid JSON and has the expected structure.
	 * This prevents storing malformed data that could break the editor.
	 *
	 * Expected structure:
	 * {
	 *   "ops": [
	 *     { "insert": "text" },
	 *     { "insert": { "image": "url" } },
	 *     ...
	 *   ]
	 * }
	 *
	 * @param string $delta_json The Delta JSON string to validate.
	 * @return true|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_delta( $delta_json ) {
		// Check for empty content (not an error, just empty document)
		if ( empty( $delta_json ) ) {
			return true;
		}

		// Attempt to decode JSON
		$delta = json_decode( $delta_json, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'invalid_json',
				__( 'O formato do documento é inválido (JSON malformado).', 'apollo-social' )
			);
		}

		// Validate basic structure: must have 'ops' array
		if ( ! is_array( $delta ) ) {
			return new \WP_Error(
				'invalid_structure',
				__( 'O formato do documento é inválido (estrutura inesperada).', 'apollo-social' )
			);
		}

		// 'ops' is optional for empty documents, but if present must be array
		if ( isset( $delta['ops'] ) && ! is_array( $delta['ops'] ) ) {
			return new \WP_Error(
				'invalid_ops',
				__( 'O formato do documento é inválido (operações inválidas).', 'apollo-social' )
			);
		}

		// Validate each operation (basic sanity check)
		if ( isset( $delta['ops'] ) ) {
			foreach ( $delta['ops'] as $index => $op ) {
				if ( ! is_array( $op ) ) {
					return new \WP_Error(
						'invalid_operation',
						sprintf(
							/* translators: %d: operation index */
							__( 'Operação %d inválida no documento.', 'apollo-social' ),
							$index
						)
					);
				}

				// Each op should have at least one key: insert, delete, or retain
				$valid_keys    = [ 'insert', 'delete', 'retain', 'attributes' ];
				$has_valid_key = false;
				foreach ( array_keys( $op ) as $key ) {
					if ( in_array( $key, $valid_keys, true ) ) {
						$has_valid_key = true;
						break;
					}
				}

				if ( ! $has_valid_key ) {
					return new \WP_Error(
						'invalid_operation',
						__( 'O documento contém operações não reconhecidas.', 'apollo-social' )
					);
				}
			}//end foreach
		}//end if

		return true;
	}

	/**
	 * Create a new document.
	 *
	 * Inserts a new document post and stores the Delta in post meta.
	 *
	 * @param string $title     Document title.
	 * @param string $delta_json Delta JSON content.
	 * @param string $html      Rendered HTML content.
	 * @return array|WP_Error Success data or error.
	 */
	private function create_document( $title, $delta_json, $html ) {
		// Check user capability to create posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'permission_denied',
				__( 'Você não tem permissão para criar documentos.', 'apollo-social' )
			);
		}

		// Generate title if empty
		if ( empty( $title ) ) {
			$title = sprintf(
				/* translators: %s: date */
				__( 'Documento sem título - %s', 'apollo-social' ),
				wp_date( 'd/m/Y H:i' )
			);
		}

		// Create the post
		$post_data = [
			'post_type'    => self::POST_TYPE,
			'post_title'   => $title,
			'post_content' => $html,
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id(),
		];

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return new \WP_Error(
				'insert_failed',
				__( 'Erro ao criar o documento. Tente novamente.', 'apollo-social' )
			);
		}

		// Store Delta in post meta
		update_post_meta( $post_id, self::DELTA_META_KEY, $delta_json );
		update_post_meta( $post_id, self::TYPE_META_KEY, 'documento' );
		update_post_meta( $post_id, self::AUTOSAVE_META_KEY, current_time( 'mysql' ) );

		// Return success with new document info
		return [
			'documentId' => $post_id,
			'editUrl'    => home_url( '/doc/' . $post_id ),
			'message'    => __( 'Documento criado com sucesso.', 'apollo-social' ),
			'created'    => true,
		];
	}

	/**
	 * Update an existing document.
	 *
	 * Updates the document post and Delta in post meta.
	 * Includes ownership and capability verification.
	 *
	 * @param int    $document_id Document post ID.
	 * @param string $title       Document title.
	 * @param string $delta_json  Delta JSON content.
	 * @param string $html        Rendered HTML content.
	 * @return array|WP_Error Success data or error.
	 */
	private function update_document( $document_id, $title, $delta_json, $html ) {
		// Get the existing post
		$post = get_post( $document_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_Error(
				'not_found',
				__( 'Documento não encontrado.', 'apollo-social' )
			);
		}

		// Check user capability to edit this specific post
		if ( ! current_user_can( 'edit_post', $document_id ) ) {
			return new \WP_Error(
				'permission_denied',
				__( 'Você não tem permissão para editar este documento.', 'apollo-social' )
			);
		}

		// Prepare update data
		$post_data = [
			'ID' => $document_id,
		];

		// Only update title if provided
		if ( ! empty( $title ) ) {
			$post_data['post_title'] = $title;
		}

		// Update content
		$post_data['post_content'] = $html;

		// Update the post
		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error(
				'update_failed',
				__( 'Erro ao atualizar o documento. Tente novamente.', 'apollo-social' )
			);
		}

		// Update Delta in post meta
		update_post_meta( $document_id, self::DELTA_META_KEY, $delta_json );
		update_post_meta( $document_id, self::AUTOSAVE_META_KEY, current_time( 'mysql' ) );

		// Return success
		return [
			'documentId' => $document_id,
			'editUrl'    => home_url( '/doc/' . $document_id ),
			'message'    => __( 'Documento salvo.', 'apollo-social' ),
			'updated'    => true,
		];
	}

	/**
	 * Get a document's Delta content by ID.
	 *
	 * Static method for use in templates and other parts of the plugin.
	 *
	 * @param int $document_id Document post ID.
	 * @return string|null Delta JSON or null if not found.
	 */
	public static function get_document_delta( $document_id ) {
		if ( ! $document_id ) {
			return null;
		}

		$post = get_post( $document_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return null;
		}

		return get_post_meta( $document_id, self::DELTA_META_KEY, true );
	}

	/**
	 * Get a document's rendered HTML content by ID.
	 *
	 * @param int $document_id Document post ID.
	 * @return string|null HTML content or null if not found.
	 */
	public static function get_document_html( $document_id ) {
		if ( ! $document_id ) {
			return null;
		}

		$post = get_post( $document_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return null;
		}

		return $post->post_content;
	}
}

// Initialize the handler
new DocumentSaveHandler();
