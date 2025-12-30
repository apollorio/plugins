<?php
/**
 * Documents Migration & Rollout
 *
 * FASE 11: Rollout seguro e limpeza de legado.
 *
 * Etapas:
 * 1. Leitura compatível (ler ambas metakeys)
 * 2. Novo mapeamento de status
 * 3. Migrações WP-CLI (metakeys + post_id)
 * 4. Escrita somente no caminho novo
 * 5. Remoção de paths antigos
 * 6. Cleanup final
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Infrastructure\ApolloLogger;

/**
 * Documents Migration Handler
 */
class DocumentsMigration {

	/** @var string Migration status option */
	private const STATUS_OPTION = 'apollo_documents_migration_status';

	/** @var string Current migration phase */
	private const CURRENT_PHASE = 'phase_4'; // Update as phases complete

	/**
	 * Migration phases
	 */
	public const PHASES = array(
		'phase_1' => 'Leitura compatível ativa',
		'phase_2' => 'Novo mapeamento de status ativo',
		'phase_3' => 'Migrações executadas',
		'phase_4' => 'Escrita no caminho novo',
		'phase_5' => 'Legado desativado',
		'phase_6' => 'Cleanup concluído',
	);

	/**
	 * Get current migration status
	 *
	 * @return array Migration status.
	 */
	public static function getStatus(): array {
		$default = array(
			'current_phase'       => 'phase_1',
			'phase_1_completed'   => false,
			'phase_2_completed'   => false,
			'phase_3_completed'   => false,
			'phase_4_completed'   => false,
			'phase_5_completed'   => false,
			'phase_6_completed'   => false,
			'legacy_reads'        => 0,
			'new_writes'          => 0,
			'legacy_metakey_count' => 0,
			'last_check'          => null,
		);

		return get_option( self::STATUS_OPTION, $default );
	}

	/**
	 * Update migration status
	 *
	 * @param array $updates Updates to apply.
	 * @return bool Success.
	 */
	public static function updateStatus( array $updates ): bool {
		$status = self::getStatus();
		$status = array_merge( $status, $updates );
		$status['last_check'] = current_time( 'mysql' );
		return update_option( self::STATUS_OPTION, $status );
	}

	/**
	 * Advance to next phase
	 *
	 * @param string $phase Phase to mark complete.
	 * @return bool Success.
	 */
	public static function completePhase( string $phase ): bool {
		$phases = array_keys( self::PHASES );
		$index  = array_search( $phase, $phases, true );

		if ( $index === false ) {
			return false;
		}

		$updates = array(
			$phase . '_completed' => true,
		);

		// Advance to next phase if available
		if ( isset( $phases[ $index + 1 ] ) ) {
			$updates['current_phase'] = $phases[ $index + 1 ];
		}

		self::updateStatus( $updates );

		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::info( 'migration_phase_completed', array(
				'phase' => $phase,
			), ApolloLogger::CAT_SYNC );
		}

		return true;
	}

	/**
	 * Check if migration is complete
	 *
	 * @return bool True if all phases complete.
	 */
	public static function isComplete(): bool {
		$status = self::getStatus();
		return $status['phase_6_completed'] ?? false;
	}

	/**
	 * Check if legacy compatibility is still needed
	 *
	 * @return bool True if legacy compat active.
	 */
	public static function needsLegacyCompat(): bool {
		$status = self::getStatus();
		return ! ( $status['phase_5_completed'] ?? false );
	}

	/**
	 * Run pre-flight checks before migration
	 *
	 * @return array Check results.
	 */
	public static function preflightChecks(): array {
		global $wpdb;

		$results = array(
			'passed'  => true,
			'checks'  => array(),
			'warnings' => array(),
			'blockers' => array(),
		);

		// 1. Check CPT exists
		$post_count = wp_count_posts( 'apollo_document' );
		$total      = array_sum( (array) $post_count );

		$results['checks']['cpt_exists'] = array(
			'name'    => 'CPT apollo_document',
			'status'  => $total > 0 ? 'ok' : 'warning',
			'message' => sprintf( '%d documents found', $total ),
		);

		// 2. Check tables exist
		$docs_table = $wpdb->prefix . 'apollo_documents';
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';

		$docs_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$docs_table}'" ) === $docs_table;
		$sigs_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$sigs_table}'" ) === $sigs_table;

		$results['checks']['documents_table'] = array(
			'name'    => 'Documents index table',
			'status'  => $docs_exists ? 'ok' : 'warning',
			'message' => $docs_exists ? 'Table exists' : 'Table missing (will be created)',
		);

		$results['checks']['signatures_table'] = array(
			'name'    => 'Signatures table',
			'status'  => $sigs_exists ? 'ok' : 'warning',
			'message' => $sigs_exists ? 'Table exists' : 'Table missing (will be created)',
		);

		// 3. Check for legacy metakey
		$legacy_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_document_signatures'"
		);

		if ( $legacy_count > 0 ) {
			$results['warnings'][] = sprintf(
				'Found %d posts with legacy signature metakey. Run migration.',
				$legacy_count
			);
		}

		$results['checks']['legacy_metakey'] = array(
			'name'    => 'Legacy metakey check',
			'status'  => $legacy_count == 0 ? 'ok' : 'warning',
			'message' => sprintf( '%d legacy entries', $legacy_count ),
		);

		// 4. Check for post_id column in signatures
		if ( $sigs_exists ) {
			$columns = $wpdb->get_col( "DESCRIBE {$sigs_table}", 0 );
			$has_post_id = in_array( 'post_id', $columns, true );

			$results['checks']['post_id_column'] = array(
				'name'    => 'post_id column in signatures',
				'status'  => $has_post_id ? 'ok' : 'warning',
				'message' => $has_post_id ? 'Column exists' : 'Needs migration',
			);

			if ( ! $has_post_id ) {
				$results['warnings'][] = 'Signatures table needs post_id column. Run schema migration.';
			}
		}

		// 5. Check for orphaned signatures
		if ( $sigs_exists ) {
			$orphans = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$sigs_table} WHERE post_id IS NULL"
			);

			if ( $orphans > 0 ) {
				$results['warnings'][] = sprintf(
					'Found %d signatures without post_id. Run backfill.',
					$orphans
				);
			}
		}

		// Check for blockers
		if ( ! empty( $results['blockers'] ) ) {
			$results['passed'] = false;
		}

		return $results;
	}

	/**
	 * Run full migration
	 *
	 * @param bool $dry_run Whether to do a dry run.
	 * @return array Migration results.
	 */
	public static function runMigration( bool $dry_run = false ): array {
		$results = array(
			'dry_run'     => $dry_run,
			'steps'       => array(),
			'errors'      => array(),
			'success'     => true,
		);

		// Preflight
		$preflight = self::preflightChecks();
		$results['preflight'] = $preflight;

		if ( ! $preflight['passed'] ) {
			$results['success'] = false;
			$results['errors'][] = 'Preflight checks failed';
			return $results;
		}

		// Step 1: Create/update tables
		$results['steps']['schema'] = self::migrateSchema( $dry_run );

		// Step 2: Migrate signatures metakey
		$results['steps']['metakeys'] = self::migrateMetakeys( $dry_run );

		// Step 3: Backfill post_id
		$results['steps']['backfill'] = self::backfillPostIds( $dry_run );

		// Step 4: Sync CPT to index
		$results['steps']['sync'] = self::syncAllDocuments( $dry_run );

		// Check for errors
		foreach ( $results['steps'] as $step => $step_result ) {
			if ( ! empty( $step_result['errors'] ) ) {
				$results['success'] = false;
				$results['errors'] = array_merge( $results['errors'], $step_result['errors'] );
			}
		}

		// Update status if not dry run
		if ( ! $dry_run && $results['success'] ) {
			self::completePhase( 'phase_3' );
		}

		return $results;
	}

	/**
	 * Migrate database schema
	 *
	 * @param bool $dry_run Dry run.
	 * @return array Results.
	 */
	private static function migrateSchema( bool $dry_run ): array {
		$results = array(
			'action'  => 'schema_migration',
			'dry_run' => $dry_run,
			'changes' => array(),
			'errors'  => array(),
		);

		if ( $dry_run ) {
			$results['changes'][] = 'Would run DocumentsSchema::migrate()';
			return $results;
		}

		$migration = DocumentsSchema::migrate();
		$results['changes'] = $migration;

		return $results;
	}

	/**
	 * Migrate signature metakeys
	 *
	 * @param bool $dry_run Dry run.
	 * @return array Results.
	 */
	private static function migrateMetakeys( bool $dry_run ): array {
		global $wpdb;

		$results = array(
			'action'   => 'metakey_migration',
			'dry_run'  => $dry_run,
			'migrated' => 0,
			'errors'   => array(),
		);

		$canonical_key = '_apollo_doc_signatures';
		$legacy_key    = '_apollo_document_signatures';

		// Find posts with legacy key
		$posts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				$legacy_key
			)
		);

		foreach ( $posts as $post_id ) {
			$canonical = get_post_meta( $post_id, $canonical_key, true );
			$legacy    = get_post_meta( $post_id, $legacy_key, true );

			if ( empty( $canonical ) && ! empty( $legacy ) ) {
				if ( ! $dry_run ) {
					update_post_meta( $post_id, $canonical_key, $legacy );
				}
				++$results['migrated'];
			}
		}

		return $results;
	}

	/**
	 * Backfill post_id in signatures table
	 *
	 * @param bool $dry_run Dry run.
	 * @return array Results.
	 */
	private static function backfillPostIds( bool $dry_run ): array {
		$results = array(
			'action'     => 'post_id_backfill',
			'dry_run'    => $dry_run,
			'updated'    => 0,
			'errors'     => array(),
		);

		if ( $dry_run ) {
			global $wpdb;
			$sigs_table = $wpdb->prefix . 'apollo_document_signatures';
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$sigs_table} WHERE post_id IS NULL" );
			$results['updated'] = (int) $count;
			return $results;
		}

		$results['updated'] = DocumentsSchema::backfillPostIds();

		return $results;
	}

	/**
	 * Sync all documents to index table
	 *
	 * @param bool $dry_run Dry run.
	 * @return array Results.
	 */
	private static function syncAllDocuments( bool $dry_run ): array {
		$results = array(
			'action'  => 'document_sync',
			'dry_run' => $dry_run,
			'synced'  => 0,
			'errors'  => array(),
		);

		$posts = get_posts( array(
			'post_type'   => 'apollo_document',
			'post_status' => 'any',
			'numberposts' => -1,
		) );

		foreach ( $posts as $post ) {
			if ( ! $dry_run ) {
				DocumentsRepository::syncToIndex( $post->ID );
			}
			++$results['synced'];
		}

		return $results;
	}

	/**
	 * Cleanup legacy data (FASE 11 final step)
	 *
	 * @param bool $force Force cleanup even if migration not complete.
	 * @return array Cleanup results.
	 */
	public static function cleanupLegacy( bool $force = false ): array {
		global $wpdb;

		$results = array(
			'action'  => 'legacy_cleanup',
			'removed' => array(),
			'errors'  => array(),
		);

		// Safety check
		if ( ! $force && ! self::isComplete() ) {
			$results['errors'][] = 'Migration not complete. Use --force to override.';
			return $results;
		}

		// 1. Remove legacy metakey
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_document_signatures'"
		);
		$results['removed']['legacy_metakey'] = $deleted;

		// 2. Remove orphan index entries (no matching post)
		$docs_table = $wpdb->prefix . 'apollo_documents';
		$deleted    = $wpdb->query(
			"DELETE d FROM {$docs_table} d
			 LEFT JOIN {$wpdb->posts} p ON d.post_id = p.ID
			 WHERE p.ID IS NULL"
		);
		$results['removed']['orphan_index_entries'] = $deleted;

		// 3. Remove orphan signatures (no matching post)
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';
		$deleted    = $wpdb->query(
			"DELETE s FROM {$sigs_table} s
			 LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID
			 WHERE s.post_id IS NOT NULL AND p.ID IS NULL"
		);
		$results['removed']['orphan_signatures'] = $deleted;

		// Mark phase complete
		self::completePhase( 'phase_6' );

		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::info( 'legacy_cleanup_completed', $results, ApolloLogger::CAT_SYNC );
		}

		return $results;
	}

	/**
	 * Get migration report
	 *
	 * @return array Report data.
	 */
	public static function getReport(): array {
		global $wpdb;

		$status = self::getStatus();
		$preflight = self::preflightChecks();

		// Count various metrics
		$docs_table = $wpdb->prefix . 'apollo_documents';
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';

		$cpt_count = array_sum( (array) wp_count_posts( 'apollo_document' ) );

		$index_count = 0;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$docs_table}'" ) === $docs_table ) {
			$index_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$docs_table}" );
		}

		$canonical_sigs = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_doc_signatures'"
		);

		$legacy_sigs = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_document_signatures'"
		);

		return array(
			'status'           => $status,
			'preflight'        => $preflight,
			'current_phase'    => $status['current_phase'],
			'is_complete'      => self::isComplete(),
			'needs_legacy'     => self::needsLegacyCompat(),
			'metrics'          => array(
				'cpt_documents'     => $cpt_count,
				'index_entries'     => $index_count,
				'canonical_sigs'    => $canonical_sigs,
				'legacy_sigs'       => $legacy_sigs,
				'sync_diff'         => abs( $cpt_count - $index_count ),
			),
			'recommendations'  => self::getRecommendations( $status, $preflight ),
		);
	}

	/**
	 * Get recommendations based on current state
	 *
	 * @param array $status    Migration status.
	 * @param array $preflight Preflight results.
	 * @return array Recommendations.
	 */
	private static function getRecommendations( array $status, array $preflight ): array {
		$recs = array();

		if ( ! empty( $preflight['warnings'] ) ) {
			foreach ( $preflight['warnings'] as $warning ) {
				$recs[] = array(
					'type'    => 'warning',
					'message' => $warning,
				);
			}
		}

		if ( ! $status['phase_3_completed'] ) {
			$recs[] = array(
				'type'    => 'action',
				'message' => 'Run: wp apollo dms migrate-signatures',
			);
		}

		if ( $status['phase_3_completed'] && ! $status['phase_6_completed'] ) {
			$recs[] = array(
				'type'    => 'info',
				'message' => 'Migration complete. Consider running cleanup when ready.',
			);
		}

		return $recs;
	}
}
