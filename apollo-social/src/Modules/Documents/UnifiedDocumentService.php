<?php
/**
 * Apollo Unified Document Service
 *
 * Serviço único para operações de documentos.
 * Evita divergências entre CPT (wp_posts) e tabela customizada (wp_apollo_documents).
 *
 * FASE 0: Este serviço é um wrapper provisório que:
 * 1. Centraliza todas as escritas de documentos
 * 2. Detecta e loga divergências entre CPT e tabela
 * 3. Prepara a migração futura para fonte de verdade única
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Infrastructure\ApolloLogger;
use WP_Error;
use WP_Post;

/**
 * Unified Document Service
 *
 * IMPORTANTE: Este é o ÚNICO ponto de entrada para criar/atualizar documentos.
 * NÃO use DocumentsManager ou LocalWordPressDmsAdapter diretamente.
 */
class UnifiedDocumentService {

	/** @var string CPT name */
	public const POST_TYPE = 'apollo_document';

	/** @var string Table name */
	private const TABLE_NAME = 'apollo_documents';

	/** @var string Meta key prefix */
	public const META_PREFIX = '_apollo_doc_';

	/** @var string Unified signature meta key */
	public const SIGNATURES_META_KEY = '_apollo_document_signatures';

	/** @var array Valid document statuses */
	public const VALID_STATUSES = array(
		'draft',
		'pending',
		'ready',
		'signing',
		'signed',
		'completed',
		'archived',
	);

	/**
	 * Create a new document
	 *
	 * @param array $data Document data.
	 * @return array|WP_Error Document data or error.
	 */
	public static function create( array $data ) {
		// Validate required fields
		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Título é obrigatório.', 'apollo-social' ) );
		}

		$title     = sanitize_text_field( $data['title'] );
		$content   = $data['content'] ?? '';
		$type      = $data['type'] ?? 'documento';
		$status    = $data['status'] ?? 'draft';
		$author_id = $data['author_id'] ?? get_current_user_id();

		// Validate status
		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			$status = 'draft';
		}

		// Generate unique file ID
		$file_id = self::generateFileId();

		// Map to WordPress post_status
		$post_status = self::mapToPostStatus( $status );

		// 1. Create CPT (fonte de verdade)
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
			ApolloLogger::error( 'document_create_failed', array(
				'error'   => $post_id->get_error_message(),
				'data'    => $data,
			), ApolloLogger::CAT_DOCUMENT );
			return $post_id;
		}

		// 2. Store meta on CPT
		update_post_meta( $post_id, self::META_PREFIX . 'file_id', $file_id );
		update_post_meta( $post_id, self::META_PREFIX . 'type', $type );
		update_post_meta( $post_id, self::META_PREFIX . 'status', $status );
		update_post_meta( $post_id, self::META_PREFIX . 'version', 1 );
		update_post_meta( $post_id, self::META_PREFIX . 'created_at', current_time( 'mysql' ) );

		// 3. Sync to table (for backwards compatibility)
		$table_result = self::syncToTable( $post_id, $file_id, $title, $type, $status, $content, $author_id );

		if ( ! $table_result ) {
			ApolloLogger::warning( 'document_table_sync_failed', array(
				'post_id' => $post_id,
				'file_id' => $file_id,
			), ApolloLogger::CAT_SYNC );
		}

		// Log success
		ApolloLogger::logDocument( 'document_created', $post_id, array(
			'file_id' => $file_id,
			'type'    => $type,
			'status'  => $status,
			'title'   => $title,
		) );

		return array(
			'id'         => $post_id,
			'file_id'    => $file_id,
			'title'      => $title,
			'type'       => $type,
			'status'     => $status,
			'version'    => 1,
			'author_id'  => $author_id,
			'created_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Update a document
	 *
	 * @param int   $document_id Post ID or file_id lookup.
	 * @param array $data        Update data.
	 * @return array|WP_Error Updated document or error.
	 */
	public static function update( int $document_id, array $data ) {
		$post = get_post( $document_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		$update_data = array( 'ID' => $document_id );
		$meta_updates = array();

		// Update content
		if ( isset( $data['content'] ) ) {
			$update_data['post_content'] = $data['content'];
		}

		// Update title
		if ( isset( $data['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		// Update status
		if ( isset( $data['status'] ) && in_array( $data['status'], self::VALID_STATUSES, true ) ) {
			$old_status = get_post_meta( $document_id, self::META_PREFIX . 'status', true );
			$new_status = $data['status'];

			$update_data['post_status']       = self::mapToPostStatus( $new_status );
			$meta_updates['status']           = $new_status;

			// Log status change
			ApolloLogger::logDocument( 'document_status_changed', $document_id, array(
				'old_status' => $old_status,
				'new_status' => $new_status,
			) );
		}

		// Update WordPress post
		$result = wp_update_post( $update_data, true );

		if ( is_wp_error( $result ) ) {
			ApolloLogger::error( 'document_update_failed', array(
				'document_id' => $document_id,
				'error'       => $result->get_error_message(),
			), ApolloLogger::CAT_DOCUMENT );
			return $result;
		}

		// Update meta
		foreach ( $meta_updates as $key => $value ) {
			update_post_meta( $document_id, self::META_PREFIX . $key, $value );
		}

		// Increment version
		$current_version = (int) get_post_meta( $document_id, self::META_PREFIX . 'version', true );
		update_post_meta( $document_id, self::META_PREFIX . 'version', $current_version + 1 );
		update_post_meta( $document_id, self::META_PREFIX . 'updated_at', current_time( 'mysql' ) );

		// Sync to table
		$file_id = get_post_meta( $document_id, self::META_PREFIX . 'file_id', true );
		self::syncTableUpdate( $document_id, $file_id, $data );

		ApolloLogger::logDocument( 'document_updated', $document_id, array(
			'file_id' => $file_id,
			'version' => $current_version + 1,
		) );

		return self::get( $document_id );
	}

	/**
	 * Get a document by ID or file_id
	 *
	 * @param int|string $identifier Post ID or file_id.
	 * @return array|WP_Error Document data or error.
	 */
	public static function get( $identifier ) {
		$post = null;

		if ( is_numeric( $identifier ) ) {
			$post = get_post( (int) $identifier );
		} else {
			// Search by file_id
			$posts = get_posts( array(
				'post_type'   => self::POST_TYPE,
				'meta_key'    => self::META_PREFIX . 'file_id',
				'meta_value'  => sanitize_text_field( $identifier ),
				'numberposts' => 1,
				'post_status' => 'any',
			) );
			$post = $posts[0] ?? null;
		}

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		// Check for divergence
		self::checkDivergence( $post->ID );

		return self::formatDocument( $post );
	}

	/**
	 * Add signature to document
	 *
	 * @param int   $document_id Document ID.
	 * @param array $signature   Signature data.
	 * @return array|WP_Error Result or error.
	 */
	public static function addSignature( int $document_id, array $signature ) {
		$post = get_post( $document_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new WP_Error( 'document_not_found', __( 'Documento não encontrado.', 'apollo-social' ) );
		}

		// Get existing signatures using UNIFIED meta key
		$signatures = get_post_meta( $document_id, self::SIGNATURES_META_KEY, true );
		$signatures = is_array( $signatures ) ? $signatures : array();

		// Add timestamp
		$signature['signed_at'] = current_time( 'mysql', true );
		$signature['ip_address'] = self::getClientIp();

		// Check for duplicate
		foreach ( $signatures as $existing ) {
			$signer_id = $signature['signer_id'] ?? null;
			if ( $signer_id && isset( $existing['signer_id'] ) && $existing['signer_id'] === $signer_id ) {
				return new WP_Error( 'already_signed', __( 'Você já assinou este documento.', 'apollo-social' ) );
			}
		}

		$signatures[] = $signature;

		// Save using UNIFIED meta key
		update_post_meta( $document_id, self::SIGNATURES_META_KEY, $signatures );

		// Update document status
		$current_status = get_post_meta( $document_id, self::META_PREFIX . 'status', true );
		if ( in_array( $current_status, array( 'draft', 'ready', 'signing' ), true ) ) {
			update_post_meta( $document_id, self::META_PREFIX . 'status', 'signed' );
		}

		// Log signature
		ApolloLogger::logSignature( 'document_signed', $document_id, array(
			'signer_id'   => $signature['signer_id'] ?? null,
			'signer_name' => $signature['signer_name'] ?? '',
			'total_signatures' => count( $signatures ),
		) );

		// Sync signature to table (if exists)
		self::syncSignatureToTable( $document_id, $signature );

		return array(
			'success'          => true,
			'signature'        => $signature,
			'total_signatures' => count( $signatures ),
		);
	}

	/**
	 * Get signatures for document
	 *
	 * @param int $document_id Document ID.
	 * @return array Signatures array.
	 */
	public static function getSignatures( int $document_id ): array {
		$signatures = get_post_meta( $document_id, self::SIGNATURES_META_KEY, true );
		return is_array( $signatures ) ? $signatures : array();
	}

	/**
	 * Check for divergence between CPT and table
	 *
	 * @param int $post_id Post ID.
	 * @return array Divergences found.
	 */
	public static function checkDivergence( int $post_id ): array {
		global $wpdb;

		$divergences = array();
		$file_id = get_post_meta( $post_id, self::META_PREFIX . 'file_id', true );

		if ( ! $file_id ) {
			$divergences[] = 'missing_file_id';
			ApolloLogger::logSyncDivergence( 'missing_file_id', array(
				'post_id' => $post_id,
			) );
			return $divergences;
		}

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$table_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE file_id = %s",
				$file_id
			),
			ARRAY_A
		);

		// Check if table row exists
		if ( ! $table_row ) {
			$divergences[] = 'post_exists_table_missing';
			ApolloLogger::logSyncDivergence( 'post_exists_table_missing', array(
				'post_id' => $post_id,
				'file_id' => $file_id,
			) );
			return $divergences;
		}

		// Check status divergence
		$post_status = get_post_meta( $post_id, self::META_PREFIX . 'status', true );
		$table_status = $table_row['status'] ?? '';

		if ( $post_status !== $table_status ) {
			$divergences[] = 'status_mismatch';
			ApolloLogger::logSyncDivergence( 'status_mismatch', array(
				'post_id'      => $post_id,
				'file_id'      => $file_id,
				'post_status'  => $post_status,
				'table_status' => $table_status,
			) );
		}

		// Check title divergence
		$post = get_post( $post_id );
		if ( $post && $post->post_title !== $table_row['title'] ) {
			$divergences[] = 'title_mismatch';
			ApolloLogger::logSyncDivergence( 'title_mismatch', array(
				'post_id'     => $post_id,
				'file_id'     => $file_id,
				'post_title'  => $post->post_title,
				'table_title' => $table_row['title'],
			) );
		}

		return $divergences;
	}

	/**
	 * Run full divergence audit
	 *
	 * @return array Audit results.
	 */
	public static function runDivergenceAudit(): array {
		global $wpdb;

		$results = array(
			'posts_checked'           => 0,
			'table_rows_checked'      => 0,
			'posts_without_table'     => array(),
			'table_without_posts'     => array(),
			'status_mismatches'       => array(),
			'signature_key_conflicts' => array(),
		);

		// Get all apollo_document posts
		$posts = get_posts( array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'any',
			'numberposts' => -1,
		) );

		$results['posts_checked'] = count( $posts );

		foreach ( $posts as $post ) {
			$file_id = get_post_meta( $post->ID, self::META_PREFIX . 'file_id', true );

			if ( ! $file_id ) {
				continue;
			}

			$divergences = self::checkDivergence( $post->ID );

			if ( in_array( 'post_exists_table_missing', $divergences, true ) ) {
				$results['posts_without_table'][] = $post->ID;
			}

			if ( in_array( 'status_mismatch', $divergences, true ) ) {
				$results['status_mismatches'][] = $post->ID;
			}

			// Check for signature key conflicts
			$old_signatures = get_post_meta( $post->ID, '_apollo_doc_signatures', true );
			$new_signatures = get_post_meta( $post->ID, '_apollo_document_signatures', true );

			if ( ! empty( $old_signatures ) && ! empty( $new_signatures ) ) {
				$results['signature_key_conflicts'][] = $post->ID;
			}
		}

		// Check for orphaned table rows
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$table_rows = $wpdb->get_results(
			"SELECT file_id FROM {$table_name}",
			ARRAY_A
		);

		$results['table_rows_checked'] = count( $table_rows );

		foreach ( $table_rows as $row ) {
			$post_exists = get_posts( array(
				'post_type'   => self::POST_TYPE,
				'meta_key'    => self::META_PREFIX . 'file_id',
				'meta_value'  => $row['file_id'],
				'numberposts' => 1,
				'post_status' => 'any',
			) );

			if ( empty( $post_exists ) ) {
				$results['table_without_posts'][] = $row['file_id'];
			}
		}

		// Log audit results
		ApolloLogger::info( 'divergence_audit_completed', $results, ApolloLogger::CAT_SYNC );

		return $results;
	}

	// =========================================================================
	// Private Helper Methods
	// =========================================================================

	/**
	 * Generate unique file ID
	 */
	private static function generateFileId(): string {
		return wp_generate_uuid4();
	}

	/**
	 * Map Apollo status to WordPress post_status
	 */
	private static function mapToPostStatus( string $status ): string {
		$map = array(
			'draft'     => 'draft',
			'pending'   => 'pending',
			'ready'     => 'publish',
			'signing'   => 'publish',
			'signed'    => 'publish',
			'completed' => 'publish',
			'archived'  => 'private',
		);

		return $map[ $status ] ?? 'draft';
	}

	/**
	 * Format document for response
	 */
	private static function formatDocument( WP_Post $post ): array {
		return array(
			'id'         => $post->ID,
			'file_id'    => get_post_meta( $post->ID, self::META_PREFIX . 'file_id', true ),
			'title'      => $post->post_title,
			'content'    => $post->post_content,
			'type'       => get_post_meta( $post->ID, self::META_PREFIX . 'type', true ) ?: 'documento',
			'status'     => get_post_meta( $post->ID, self::META_PREFIX . 'status', true ) ?: 'draft',
			'version'    => (int) get_post_meta( $post->ID, self::META_PREFIX . 'version', true ) ?: 1,
			'author_id'  => (int) $post->post_author,
			'created_at' => $post->post_date,
			'updated_at' => $post->post_modified,
		);
	}

	/**
	 * Sync document to table (backwards compatibility)
	 */
	private static function syncToTable( int $post_id, string $file_id, string $title, string $type, string $status, string $content, int $author_id ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return false;
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'file_id'    => $file_id,
				'type'       => $type,
				'title'      => $title,
				'content'    => $content,
				'status'     => $status,
				'created_by' => $author_id,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		return (bool) $result;
	}

	/**
	 * Sync update to table
	 */
	private static function syncTableUpdate( int $post_id, string $file_id, array $data ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return false;
		}

		$update = array();
		$format = array();

		if ( isset( $data['title'] ) ) {
			$update['title'] = sanitize_text_field( $data['title'] );
			$format[]        = '%s';
		}

		if ( isset( $data['content'] ) ) {
			$update['content'] = $data['content'];
			$format[]          = '%s';
		}

		if ( isset( $data['status'] ) ) {
			$update['status'] = $data['status'];
			$format[]         = '%s';
		}

		if ( empty( $update ) ) {
			return true;
		}

		$result = $wpdb->update(
			$table_name,
			$update,
			array( 'file_id' => $file_id ),
			$format,
			array( '%s' )
		);

		return $result !== false;
	}

	/**
	 * Sync signature to table
	 */
	private static function syncSignatureToTable( int $document_id, array $signature ): bool {
		global $wpdb;

		$file_id = get_post_meta( $document_id, self::META_PREFIX . 'file_id', true );

		if ( ! $file_id ) {
			return false;
		}

		$docs_table = $wpdb->prefix . self::TABLE_NAME;
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';

		// Check if tables exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$sigs_table}'" ) !== $sigs_table ) {
			return false;
		}

		// Get document ID from table
		$doc_row = $wpdb->get_row(
			$wpdb->prepare( "SELECT id FROM {$docs_table} WHERE file_id = %s", $file_id ),
			ARRAY_A
		);

		if ( ! $doc_row ) {
			return false;
		}

		$result = $wpdb->insert(
			$sigs_table,
			array(
				'document_id'  => $doc_row['id'],
				'signer_name'  => $signature['signer_name'] ?? '',
				'signer_email' => $signature['signer_email'] ?? '',
				'signed_at'    => $signature['signed_at'] ?? current_time( 'mysql' ),
				'ip_address'   => $signature['ip_address'] ?? '',
				'status'       => 'signed',
			)
		);

		return (bool) $result;
	}

	/**
	 * Get client IP
	 */
	private static function getClientIp(): string {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

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
}
