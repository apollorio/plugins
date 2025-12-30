<?php
/**
 * Documents Schema Migration
 *
 * Creates and manages Documents module database tables.
 * Source of truth is CPT apollo_document; custom table is an index/cache.
 *
 * Tables:
 * - wp_apollo_documents (index/cache)
 * - wp_apollo_document_signatures
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Contracts\SchemaModuleInterface;
use Apollo\Infrastructure\ApolloLogger;
use WP_Error;

/**
 * Documents Schema - Database migrations for Documents module.
 */
class DocumentsSchema implements SchemaModuleInterface {

	/** @var string Migration version option key */
	private const VERSION_OPTION = 'apollo_documents_schema_version';

	/** @var string Current schema version */
	private const CURRENT_VERSION = '2.1.0';

	/**
	 * Run all pending migrations
	 *
	 * @return array Migration results.
	 */
	public static function migrate(): array {
		$current = get_option( self::VERSION_OPTION, '0.0.0' );
		$results = array();

		// Migration: Add post_id to signatures table
		if ( version_compare( $current, '2.1.0', '<' ) ) {
			$results['2.1.0'] = self::migrate_2_1_0();
		}

		// Update version
		update_option( self::VERSION_OPTION, self::CURRENT_VERSION );

		return $results;
	}

	/**
	 * Migration 2.1.0: Add post_id column and indices
	 *
	 * @return array Results.
	 */
	private static function migrate_2_1_0(): array {
		global $wpdb;

		$results = array(
			'success'        => true,
			'columns_added'  => array(),
			'indices_added'  => array(),
			'backfill_count' => 0,
			'errors'         => array(),
		);

		$signatures_table = $wpdb->prefix . 'apollo_document_signatures';
		$documents_table  = $wpdb->prefix . 'apollo_documents';

		// Check if tables exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$signatures_table}'" ) !== $signatures_table ) {
			$results['errors'][] = 'Signatures table does not exist';
			$results['success']  = false;
			return $results;
		}

		// 1. Add post_id column if not exists
		$columns = $wpdb->get_col( "DESCRIBE {$signatures_table}", 0 );

		if ( ! in_array( 'post_id', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN post_id BIGINT(20) UNSIGNED NULL AFTER document_id" );
			$results['columns_added'][] = 'post_id';
		}

		// 2. Add signature_id column if not exists
		if ( ! in_array( 'signature_id', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN signature_id VARCHAR(36) NULL AFTER id" );
			$results['columns_added'][] = 'signature_id';
		}

		// 3. Add signer_cpf_hash column if not exists
		if ( ! in_array( 'signer_cpf_hash', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN signer_cpf_hash VARCHAR(64) NULL AFTER signer_email" );
			$results['columns_added'][] = 'signer_cpf_hash';
		}

		// 4. Add method column if not exists
		if ( ! in_array( 'method', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN method VARCHAR(32) DEFAULT 'electronic' AFTER signer_cpf_hash" );
			$results['columns_added'][] = 'method';
		}

		// 5. Add doc_hash column if not exists
		if ( ! in_array( 'doc_hash', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN doc_hash VARCHAR(64) NULL AFTER method" );
			$results['columns_added'][] = 'doc_hash';
		}

		// 6. Add pdf_hash column if not exists
		if ( ! in_array( 'pdf_hash', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN pdf_hash VARCHAR(64) NULL AFTER doc_hash" );
			$results['columns_added'][] = 'pdf_hash';
		}

		// 7. Add ip_address column if not exists
		if ( ! in_array( 'ip_address', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN ip_address VARCHAR(45) NULL AFTER signed_at" );
			$results['columns_added'][] = 'ip_address';
		}

		// 8. Add signer_user_id column if not exists
		if ( ! in_array( 'signer_user_id', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$signatures_table} ADD COLUMN signer_user_id BIGINT(20) UNSIGNED NULL AFTER signature_id" );
			$results['columns_added'][] = 'signer_user_id';
		}

		// 9. Create indices
		$indices = $wpdb->get_results( "SHOW INDEX FROM {$signatures_table}", ARRAY_A );
		$index_names = array_column( $indices, 'Key_name' );

		if ( ! in_array( 'idx_post_id', $index_names, true ) ) {
			$wpdb->query( "CREATE INDEX idx_post_id ON {$signatures_table} (post_id)" );
			$results['indices_added'][] = 'idx_post_id';
		}

		if ( ! in_array( 'idx_signer_user', $index_names, true ) ) {
			$wpdb->query( "CREATE INDEX idx_signer_user ON {$signatures_table} (signer_user_id)" );
			$results['indices_added'][] = 'idx_signer_user';
		}

		// 10. Backfill post_id
		$results['backfill_count'] = self::backfillPostIds();

		// Log migration
		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::info( 'schema_migrated', array(
				'version' => '2.1.0',
				'results' => $results,
			), ApolloLogger::CAT_SYNC );
		}

		return $results;
	}

	/**
	 * Backfill post_id in signatures table
	 *
	 * Maps document_id (from wp_apollo_documents.id) to post_id (apollo_document CPT)
	 *
	 * @return int Number of rows updated.
	 */
	public static function backfillPostIds(): int {
		global $wpdb;

		$signatures_table = $wpdb->prefix . 'apollo_document_signatures';
		$documents_table  = $wpdb->prefix . 'apollo_documents';

		// Get signatures without post_id
		$orphans = $wpdb->get_results(
			"SELECT s.id, s.document_id, d.file_id
			 FROM {$signatures_table} s
			 LEFT JOIN {$documents_table} d ON s.document_id = d.id
			 WHERE s.post_id IS NULL",
			ARRAY_A
		);

		if ( empty( $orphans ) ) {
			return 0;
		}

		$updated = 0;

		foreach ( $orphans as $row ) {
			$file_id = $row['file_id'];

			if ( empty( $file_id ) ) {
				continue;
			}

			// Find post by file_id meta
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta}
					 WHERE meta_key = '_apollo_doc_file_id' AND meta_value = %s
					 LIMIT 1",
					$file_id
				)
			);

			if ( $post_id ) {
				$wpdb->update(
					$signatures_table,
					array( 'post_id' => (int) $post_id ),
					array( 'id' => $row['id'] )
				);
				++$updated;
			}
		}

		return $updated;
	}

	/**
	 * Create documents index table (for SYNC_TO_INDEX)
	 *
	 * @return bool Success.
	 */
	public static function createDocumentsTable(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_documents';
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			file_id VARCHAR(36) NOT NULL,
			title VARCHAR(255) NOT NULL,
			type VARCHAR(50) DEFAULT 'documento',
			status VARCHAR(50) DEFAULT 'draft',
			created_by BIGINT(20) UNSIGNED,
			signature_count INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_post_id (post_id),
			UNIQUE KEY unique_file_id (file_id),
			KEY idx_status (status),
			KEY idx_type (type),
			KEY idx_created_by (created_by)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Create signatures table with post_id support
	 *
	 * @return bool Success.
	 */
	public static function createSignaturesTable(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_document_signatures';
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			signature_id VARCHAR(36),
			document_id BIGINT(20) UNSIGNED,
			post_id BIGINT(20) UNSIGNED,
			signer_user_id BIGINT(20) UNSIGNED,
			signer_name VARCHAR(255),
			signer_email VARCHAR(255),
			signer_cpf_hash VARCHAR(64),
			method VARCHAR(32) DEFAULT 'electronic',
			doc_hash VARCHAR(64),
			pdf_hash VARCHAR(64),
			signed_at DATETIME,
			ip_address VARCHAR(45),
			status VARCHAR(50) DEFAULT 'signed',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_document_id (document_id),
			KEY idx_post_id (post_id),
			KEY idx_signer_user (signer_user_id),
			KEY idx_signed_at (signed_at),
			UNIQUE KEY unique_signature (signature_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Get current schema version
	 *
	 * @return string Version.
	 */
	public static function getVersion(): string {
		return get_option( self::VERSION_OPTION, '0.0.0' );
	}

	/**
	 * Check if migration is needed
	 *
	 * @return bool True if migration needed.
	 */
	public static function needsMigration(): bool {
		$current = get_option( self::VERSION_OPTION, '0.0.0' );
		return version_compare( $current, self::CURRENT_VERSION, '<' );
	}

	// =========================================================================
	// SchemaModuleInterface Implementation
	// =========================================================================

	/**
	 * Install module schema (idempotent via dbDelta).
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function install() {
		try {
			$docs   = self::createDocumentsTable();
			$sigs   = self::createSignaturesTable();

			if ( ! $docs || ! $sigs ) {
				return new WP_Error(
					'apollo_documents_schema_failed',
					'Failed to create documents tables',
					array( 'documents' => $docs, 'signatures' => $sigs )
				);
			}

			update_option( self::VERSION_OPTION, self::CURRENT_VERSION );

			return true;

		} catch ( \Throwable $e ) {
			return new WP_Error(
				'apollo_documents_schema_exception',
				$e->getMessage(),
				array( 'exception' => get_class( $e ) )
			);
		}
	}

	/**
	 * Upgrade module schema from one version to another.
	 *
	 * @param string $fromVersion Current stored version.
	 * @param string $toVersion   Target version.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function upgrade( string $fromVersion, string $toVersion ) {
		// Run migrations (idempotent).
		$results = self::migrate();

		if ( ! empty( $results ) ) {
			foreach ( $results as $version => $result ) {
				if ( isset( $result['success'] ) && false === $result['success'] ) {
					return new WP_Error(
						'apollo_documents_upgrade_failed',
						"Migration {$version} failed",
						$result
					);
				}
			}
		}

		return true;
	}

	/**
	 * Get module table status.
	 *
	 * @return array<string, bool> Table existence map.
	 */
	public function getStatus(): array {
		global $wpdb;

		$docs_table = $wpdb->prefix . 'apollo_documents';
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';

		return array(
			'apollo_documents'           => $wpdb->get_var( "SHOW TABLES LIKE '{$docs_table}'" ) === $docs_table,
			'apollo_document_signatures' => $wpdb->get_var( "SHOW TABLES LIKE '{$sigs_table}'" ) === $sigs_table,
		);
	}

	/**
	 * Uninstall module schema (drop tables).
	 *
	 * @return void
	 */
	public function uninstall(): void {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apollo_document_signatures" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apollo_documents" );

		delete_option( self::VERSION_OPTION );
	}
}
