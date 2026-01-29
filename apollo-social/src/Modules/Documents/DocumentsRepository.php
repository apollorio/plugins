<?php
/**
 * Documents Repository
 *
 * FASE 4: Ponto único de escrita/leitura para documentos.
 *
 * Fonte de verdade = CPT apollo_document
 * wp_apollo_documents = índice/cache operacional (opcional)
 *
 * Contrato:
 * - Todas as operações passam por este repository
 * - CPT é SEMPRE atualizado
 * - Tabela índice é atualizada opcionalmente (para queries rápidas)
 * - Nunca ler/escrever diretamente em outros lugares
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Infrastructure\ApolloLogger;
use WP_Error;
use WP_Post;
use WP_Query;

/**
 * Documents Repository - Single Point of Truth
 */
class DocumentsRepository {

	// =========================================================================
	// Constants
	// =========================================================================

	/** @var string CPT name - FONTE DE VERDADE */
	public const POST_TYPE = 'apollo_document';

	/** @var string Table name - ÍNDICE OPERACIONAL */
	public const TABLE_NAME = 'apollo_documents';

	/** @var string Meta prefix */
	public const META_PREFIX = '_apollo_doc_';

	/** @var string Canonical signature meta key (FASE 6) */
	public const SIGNATURES_META_KEY = '_apollo_doc_signatures';

	/** @var string Legacy signature meta key (read-only for migration) */
	public const SIGNATURES_META_KEY_LEGACY = '_apollo_document_signatures';

	/** @var bool Whether to sync to index table */
	private const SYNC_TO_INDEX = true;

	// =========================================================================
	// Document CRUD
	// =========================================================================

	/**
	 * Create a new document
	 *
	 * @param array $data Document data.
	 * @return array|WP_Error Document array or error.
	 */
	public static function createDocument( array $data ) {
		// Validate required fields
		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Título é obrigatório.', 'apollo-social' ) );
		}

		$title        = sanitize_text_field( $data['title'] );
		$content      = $data['content'] ?? '';
		$doc_type     = $data['type'] ?? 'documento';
		$apollo_state = $data['state'] ?? 'draft';
		$author_id    = $data['author_id'] ?? get_current_user_id();
		$metadata     = $data['metadata'] ?? array();

		// Generate unique identifiers
		$file_id  = wp_generate_uuid4();
		$doc_hash = self::generateDocHash( $title, $content, $author_id );

		// Map Apollo state to WP post_status (FASE 5)
		$post_status = DocumentStatus::mapToPostStatus( $apollo_state );

		// 1. CREATE CPT (Fonte de Verdade)
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => $post_status,
				'post_author'  => $author_id,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			ApolloLogger::error(
				'document_create_failed',
				array(
					'error' => $post_id->get_error_message(),
					'data'  => $data,
				),
				ApolloLogger::CAT_DOCUMENT
			);
			return $post_id;
		}

		// 2. STORE META on CPT
		$meta_values = array(
			'file_id'    => $file_id,
			'doc_hash'   => $doc_hash,
			'type'       => $doc_type,
			'state'      => $apollo_state,
			'version'    => 1,
			'created_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		);

		foreach ( $meta_values as $key => $value ) {
			update_post_meta( $post_id, self::META_PREFIX . $key, $value );
		}

		// Store additional metadata
		if ( ! empty( $metadata ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'metadata', $metadata );
		}

		// 3. SYNC TO INDEX TABLE (optional)
		if ( self::SYNC_TO_INDEX ) {
			self::syncToIndex( $post_id );
		}

		// 4. LOG
		ApolloLogger::logDocument(
			'document_created',
			$post_id,
			array(
				'file_id'     => $file_id,
				'type'        => $doc_type,
				'state'       => $apollo_state,
				'post_status' => $post_status,
			)
		);

		return self::getDocument( $post_id );
	}

	/**
	 * Update an existing document
	 *
	 * @param int   $post_id Document post ID.
	 * @param array $data    Update data.
	 * @return array|WP_Error Updated document or error.
	 */
	public static function updateDocument( int $post_id, array $data ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		$update_post = array( 'ID' => $post_id );

		// Update title
		if ( isset( $data['title'] ) ) {
			$update_post['post_title'] = sanitize_text_field( $data['title'] );
		}

		// Update content
		if ( isset( $data['content'] ) ) {
			$update_post['post_content'] = $data['content'];

			// Recalculate hash if content changed
			$doc_hash = self::generateDocHash(
				$update_post['post_title'] ?? $post->post_title,
				$data['content'],
				$post->post_author
			);
			update_post_meta( $post_id, self::META_PREFIX . 'doc_hash', $doc_hash );
		}

		// Update WordPress post
		if ( count( $update_post ) > 1 ) {
			$result = wp_update_post( $update_post, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Update metadata
		if ( isset( $data['type'] ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'type', sanitize_key( $data['type'] ) );
		}

		if ( isset( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
			$existing = get_post_meta( $post_id, self::META_PREFIX . 'metadata', true ) ?: array();
			$merged   = array_merge( $existing, $data['metadata'] );
			update_post_meta( $post_id, self::META_PREFIX . 'metadata', $merged );
		}

		// Increment version
		$version = (int) get_post_meta( $post_id, self::META_PREFIX . 'version', true );
		update_post_meta( $post_id, self::META_PREFIX . 'version', $version + 1 );
		update_post_meta( $post_id, self::META_PREFIX . 'updated_at', current_time( 'mysql', true ) );

		// Sync to index
		if ( self::SYNC_TO_INDEX ) {
			self::syncToIndex( $post_id );
		}

		ApolloLogger::logDocument(
			'document_updated',
			$post_id,
			array(
				'version' => $version + 1,
				'changes' => array_keys( $data ),
			)
		);

		return self::getDocument( $post_id );
	}

	/**
	 * Transition document status
	 *
	 * @param int    $post_id   Document post ID.
	 * @param string $new_state New Apollo state.
	 * @param array  $context   Transition context (who, why).
	 * @return array|WP_Error Updated document or error.
	 */
	public static function transitionStatus( int $post_id, string $new_state, array $context = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		$old_state = get_post_meta( $post_id, self::META_PREFIX . 'state', true ) ?: 'draft';

		// Validate transition (FASE 9 - workflow)
		if ( ! DocumentStatus::isValidTransition( $old_state, $new_state ) ) {
			ApolloLogger::warning(
				'invalid_status_transition',
				array(
					'post_id'   => $post_id,
					'old_state' => $old_state,
					'new_state' => $new_state,
				),
				ApolloLogger::CAT_DOCUMENT
			);

			return new WP_Error(
				'invalid_transition',
				sprintf(
					/* translators: %1$s: old state, %2$s: new state */
					__( 'Transição inválida de "%1$s" para "%2$s".', 'apollo-social' ),
					$old_state,
					$new_state
				)
			);
		}

		// Update Apollo state
		update_post_meta( $post_id, self::META_PREFIX . 'state', $new_state );
		update_post_meta( $post_id, self::META_PREFIX . 'state_changed_at', current_time( 'mysql', true ) );

		// Map to WP post_status
		$new_post_status = DocumentStatus::mapToPostStatus( $new_state );

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => $new_post_status,
			)
		);

		// Log transition
		$transition_log   = get_post_meta( $post_id, self::META_PREFIX . 'transitions', true ) ?: array();
		$transition_log[] = array(
			'from'      => $old_state,
			'to'        => $new_state,
			'timestamp' => current_time( 'mysql', true ),
			'user_id'   => $context['user_id'] ?? get_current_user_id(),
			'reason'    => $context['reason'] ?? '',
		);
		update_post_meta( $post_id, self::META_PREFIX . 'transitions', $transition_log );

		// Sync to index
		if ( self::SYNC_TO_INDEX ) {
			self::syncToIndex( $post_id );
		}

		ApolloLogger::logDocument(
			'document_status_changed',
			$post_id,
			array(
				'old_state'       => $old_state,
				'new_state'       => $new_state,
				'new_post_status' => $new_post_status,
			)
		);

		return self::getDocument( $post_id );
	}

	/**
	 * Attach PDF to document
	 *
	 * @param int    $post_id      Document post ID.
	 * @param int    $attachment_id PDF attachment ID.
	 * @param string $pdf_hash     SHA-256 hash of PDF content.
	 * @return array|WP_Error Updated document or error.
	 */
	public static function attachPdf( int $post_id, int $attachment_id, string $pdf_hash = '' ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		// Validate attachment
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return new WP_Error( 'invalid_attachment', __( 'Anexo inválido.', 'apollo-social' ) );
		}

		// Calculate hash if not provided
		if ( empty( $pdf_hash ) ) {
			$file_path = get_attached_file( $attachment_id );
			if ( $file_path && file_exists( $file_path ) ) {
				$pdf_hash = hash_file( 'sha256', $file_path );
			}
		}

		// Store PDF reference
		update_post_meta( $post_id, self::META_PREFIX . 'pdf_attachment_id', $attachment_id );
		update_post_meta( $post_id, self::META_PREFIX . 'pdf_hash', $pdf_hash );
		update_post_meta( $post_id, self::META_PREFIX . 'pdf_attached_at', current_time( 'mysql', true ) );

		// Increment version
		$version = (int) get_post_meta( $post_id, self::META_PREFIX . 'version', true );
		update_post_meta( $post_id, self::META_PREFIX . 'version', $version + 1 );

		// Sync to index
		if ( self::SYNC_TO_INDEX ) {
			self::syncToIndex( $post_id );
		}

		ApolloLogger::logDocument(
			'pdf_attached',
			$post_id,
			array(
				'attachment_id' => $attachment_id,
				'pdf_hash'      => \substr( $pdf_hash, 0, 16 ) . '...',
			)
		);

		return self::getDocument( $post_id );
	}

	/**
	 * Store signature on document (FASE 6 - unified metakey)
	 *
	 * @param int   $post_id   Document post ID.
	 * @param array $signature Signature data.
	 * @return array|WP_Error Result or error.
	 */
	public static function storeSignature( int $post_id, array $signature ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		// Validate signature data (FASE 10)
		$validation = self::validateSignatureData( $signature );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Get existing signatures (FASE 6 - read from both, migrate)
		$signatures = self::getSignatures( $post_id );

		// Check for duplicate signer
		$signer_id = $signature['signer_user_id'] ?? $signature['signer_cpf_hash'] ?? null;
		foreach ( $signatures as $existing ) {
			$existing_id = $existing['signer_user_id'] ?? $existing['signer_cpf_hash'] ?? null;
			if ( $signer_id && $existing_id === $signer_id ) {
				return new WP_Error( 'already_signed', __( 'Este documento já foi assinado por você.', 'apollo-social' ) );
			}
		}

		// Prepare signature record
		$signature_record = array(
			'id'              => wp_generate_uuid4(),
			'signer_user_id'  => $signature['signer_user_id'] ?? null,
			'signer_name'     => sanitize_text_field( $signature['signer_name'] ?? '' ),
			'signer_email'    => sanitize_email( $signature['signer_email'] ?? '' ),
			'signer_cpf_hash' => $signature['signer_cpf_hash'] ?? null,
			'method'          => $signature['method'] ?? 'electronic', // electronic, digital, pki
			'doc_hash'        => get_post_meta( $post_id, self::META_PREFIX . 'doc_hash', true ),
			'pdf_hash'        => get_post_meta( $post_id, self::META_PREFIX . 'pdf_hash', true ),
			'signed_at'       => current_time( 'mysql', true ),
			'ip_address'      => self::getClientIp(),
			'user_agent'      => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
			'version'         => (int) get_post_meta( $post_id, self::META_PREFIX . 'version', true ),
		);

		$signatures[] = $signature_record;

		// WRITE TO CANONICAL METAKEY ONLY (FASE 6)
		update_post_meta( $post_id, self::SIGNATURES_META_KEY, $signatures );

		// Sync signature to table (FASE 7 - uses post_id)
		self::syncSignatureToTable( $post_id, $signature_record );

		// Auto-transition to 'signed' if in 'signing' state
		$current_state = get_post_meta( $post_id, self::META_PREFIX . 'state', true );
		if ( in_array( $current_state, array( 'ready', 'signing' ), true ) ) {
			self::transitionStatus(
				$post_id,
				'signed',
				array(
					'reason' => 'Signature added',
				)
			);
		}

		// Sync to index
		if ( self::SYNC_TO_INDEX ) {
			self::syncToIndex( $post_id );
		}

		ApolloLogger::logSignature(
			'signature_stored',
			$post_id,
			array(
				'signature_id'   => $signature_record['id'],
				'signer_user_id' => $signature_record['signer_user_id'],
				'method'         => $signature_record['method'],
				'total'          => count( $signatures ),
			)
		);

		return array(
			'success'          => true,
			'signature'        => $signature_record,
			'total_signatures' => count( $signatures ),
		);
	}

	/**
	 * Get document by post_id or file_id
	 *
	 * @param int|string $identifier Post ID or file_id.
	 * @return array|WP_Error Document array or error.
	 */
	public static function getDocument( $identifier ) {
		$post = null;

		if ( is_numeric( $identifier ) ) {
			$post = get_post( (int) $identifier );
		} else {
			// Lookup by file_id
			$query = new WP_Query(
				array(
					'post_type'      => self::POST_TYPE,
					'meta_key'       => self::META_PREFIX . 'file_id',
					'meta_value'     => sanitize_text_field( $identifier ),
					'posts_per_page' => 1,
					'post_status'    => 'any',
				)
			);

			if ( $query->have_posts() ) {
				$post = $query->posts[0];
			}
		}

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		return self::formatDocument( $post );
	}

	/**
	 * Get signatures for document (FASE 6 - read canonical, fallback legacy)
	 *
	 * @param int $post_id Document post ID.
	 * @return array Signatures array.
	 */
	public static function getSignatures( int $post_id ): array {
		// Try canonical key first
		$signatures = get_post_meta( $post_id, self::SIGNATURES_META_KEY, true );

		if ( ! empty( $signatures ) && is_array( $signatures ) ) {
			return $signatures;
		}

		// Fallback to legacy key (FASE 6 - migration on read)
		$legacy = get_post_meta( $post_id, self::SIGNATURES_META_KEY_LEGACY, true );

		if ( ! empty( $legacy ) && is_array( $legacy ) ) {
			// Migrate to canonical key
			update_post_meta( $post_id, self::SIGNATURES_META_KEY, $legacy );

			ApolloLogger::info(
				'signature_metakey_migrated',
				array(
					'post_id' => $post_id,
					'count'   => count( $legacy ),
				),
				ApolloLogger::CAT_SYNC
			);

			return $legacy;
		}

		return array();
	}

	/**
	 * Query documents
	 *
	 * @param array $args Query arguments.
	 * @return array Documents array.
	 */
	public static function queryDocuments( array $args = array() ): array {
		$defaults = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => 20,
			'post_status'    => 'any',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Filter by Apollo state
		if ( ! empty( $args['state'] ) ) {
			$args['meta_query'][] = array(
				'key'   => self::META_PREFIX . 'state',
				'value' => $args['state'],
			);
			unset( $args['state'] );
		}

		// Filter by type
		if ( ! empty( $args['type'] ) ) {
			$args['meta_query'][] = array(
				'key'   => self::META_PREFIX . 'type',
				'value' => $args['type'],
			);
			unset( $args['type'] );
		}

		$query_args = array_merge( $defaults, $args );
		$query      = new WP_Query( $query_args );

		$documents = array();
		foreach ( $query->posts as $post ) {
			$documents[] = self::formatDocument( $post );
		}

		return $documents;
	}

	// =========================================================================
	// Index Synchronization (FASE 8)
	// =========================================================================

	/**
	 * Sync document to index table
	 *
	 * @param int $post_id Post ID.
	 * @return bool Success.
	 */
	public static function syncToIndex( int $post_id ): bool {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return false;
		}

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Check if table exists
		// SECURITY FIX: Use prepared statement for table existence check.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return false;
		}

		$file_id    = get_post_meta( $post_id, self::META_PREFIX . 'file_id', true );
		$state      = get_post_meta( $post_id, self::META_PREFIX . 'state', true ) ?: 'draft';
		$doc_type   = get_post_meta( $post_id, self::META_PREFIX . 'type', true ) ?: 'documento';
		$signatures = self::getSignatures( $post_id );

		$data = array(
			'post_id'         => $post_id,
			'file_id'         => $file_id,
			'title'           => $post->post_title,
			'type'            => $doc_type,
			'status'          => $state,
			'created_by'      => $post->post_author,
			'signature_count' => count( $signatures ),
			'updated_at'      => current_time( 'mysql' ),
		);

		// Check if row exists
		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table_name} WHERE post_id = %d", $post_id )
		);

		if ( $existing ) {
			$result = $wpdb->update(
				$table_name,
				$data,
				array( 'post_id' => $post_id )
			);
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$result             = $wpdb->insert( $table_name, $data );
		}

		return $result !== false;
	}

	/**
	 * Sync signature to table (FASE 7 - uses post_id)
	 *
	 * @param int   $post_id   Post ID.
	 * @param array $signature Signature data.
	 * @return bool Success.
	 */
	private static function syncSignatureToTable( int $post_id, array $signature ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_document_signatures';

		// Check if table exists
		// SECURITY FIX: Use prepared statement for table existence check.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return false;
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'post_id'         => $post_id, // FASE 7: using post_id directly
				'signature_id'    => $signature['id'],
				'signer_user_id'  => $signature['signer_user_id'],
				'signer_name'     => $signature['signer_name'],
				'signer_email'    => $signature['signer_email'],
				'signer_cpf_hash' => $signature['signer_cpf_hash'],
				'method'          => $signature['method'],
				'doc_hash'        => $signature['doc_hash'],
				'pdf_hash'        => $signature['pdf_hash'],
				'signed_at'       => $signature['signed_at'],
				'ip_address'      => $signature['ip_address'],
				'created_at'      => current_time( 'mysql' ),
			)
		);

		return (bool) $result;
	}

	// =========================================================================
	// Validation (FASE 10)
	// =========================================================================

	/**
	 * Validate signature data
	 *
	 * @param array $signature Signature data.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private static function validateSignatureData( array $signature ) {
		// Require CPF or user_id
		if ( empty( $signature['signer_user_id'] ) && empty( $signature['signer_cpf_hash'] ) ) {
			return new WP_Error(
				'missing_signer_id',
				__( 'CPF ou ID do usuário é obrigatório para assinatura.', 'apollo-social' )
			);
		}

		// Require name
		if ( empty( $signature['signer_name'] ) ) {
			return new WP_Error(
				'missing_signer_name',
				__( 'Nome do assinante é obrigatório.', 'apollo-social' )
			);
		}

		// Check method
		$valid_methods = array( 'electronic', 'digital', 'pki', 'biometric' );
		if ( ! empty( $signature['method'] ) && ! in_array( $signature['method'], $valid_methods, true ) ) {
			return new WP_Error(
				'invalid_signature_method',
				__( 'Método de assinatura inválido.', 'apollo-social' )
			);
		}

		return true;
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Format document for output
	 *
	 * @param WP_Post $post Post object.
	 * @return array Document array.
	 */
	private static function formatDocument( WP_Post $post ): array {
		$signatures = self::getSignatures( $post->ID );

		return array(
			'id'              => $post->ID,
			'file_id'         => get_post_meta( $post->ID, self::META_PREFIX . 'file_id', true ),
			'title'           => $post->post_title,
			'content'         => $post->post_content,
			'type'            => get_post_meta( $post->ID, self::META_PREFIX . 'type', true ) ?: 'documento',
			'state'           => get_post_meta( $post->ID, self::META_PREFIX . 'state', true ) ?: 'draft',
			'post_status'     => $post->post_status,
			'version'         => (int) get_post_meta( $post->ID, self::META_PREFIX . 'version', true ) ?: 1,
			'doc_hash'        => get_post_meta( $post->ID, self::META_PREFIX . 'doc_hash', true ),
			'pdf_attachment'  => get_post_meta( $post->ID, self::META_PREFIX . 'pdf_attachment_id', true ),
			'pdf_hash'        => get_post_meta( $post->ID, self::META_PREFIX . 'pdf_hash', true ),
			'author_id'       => (int) $post->post_author,
			'signature_count' => count( $signatures ),
			'signatures'      => $signatures,
			'created_at'      => $post->post_date_gmt,
			'updated_at'      => $post->post_modified_gmt,
			'metadata'        => get_post_meta( $post->ID, self::META_PREFIX . 'metadata', true ) ?: array(),
		);
	}

	/**
	 * Generate document hash
	 *
	 * @param string $title     Title.
	 * @param string $content   Content.
	 * @param int    $author_id Author ID.
	 * @return string SHA-256 hash.
	 */
	private static function generateDocHash( string $title, string $content, int $author_id ): string {
		$data = \implode( '|', array( $title, $content, $author_id, time() ) );
		return hash( 'sha256', $data );
	}

	/**
	 * Get client IP
	 *
	 * @return string IP address.
	 */
	private static function getClientIp(): string {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( \strpos( $ip, ',' ) !== false ) {
					$ip = \trim( \explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '';
	}
}
