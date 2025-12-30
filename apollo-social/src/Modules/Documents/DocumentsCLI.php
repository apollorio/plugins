<?php
/**
 * Apollo DMS CLI Commands
 *
 * FASE 8: WP-CLI commands for document management and reconciliation.
 *
 * Commands:
 * - wp apollo dms reconcile - Detect and fix divergences
 * - wp apollo dms migrate-signatures - Unify metakeys and backfill post_id
 * - wp apollo dms stats - Show document statistics
 * - wp apollo dms audit - Run full audit
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use WP_CLI;
use WP_CLI\Utils;

// Only load in CLI context
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Manage Apollo Documents
 *
 * ## EXAMPLES
 *
 *     # Reconcile documents
 *     wp apollo dms reconcile
 *
 *     # Migrate signatures
 *     wp apollo dms migrate-signatures --dry-run
 *
 *     # Show statistics
 *     wp apollo dms stats
 */
class DocumentsCLI {

	/**
	 * Reconcile documents between CPT and index table.
	 *
	 * Detects and fixes:
	 * - Posts without table entry
	 * - Table entries without posts
	 * - Status divergences
	 *
	 * CPT is the source of truth.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * [--fix]
	 * : Automatically fix divergences.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms reconcile
	 *     wp apollo dms reconcile --dry-run
	 *     wp apollo dms reconcile --fix
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function reconcile( $args, $assoc_args ) {
		global $wpdb;

		$dry_run = Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$fix     = Utils\get_flag_value( $assoc_args, 'fix', false );

		WP_CLI::log( '=== Apollo DMS Reconciliation ===' );
		WP_CLI::log( $dry_run ? '(Dry run - no changes will be made)' : '' );
		WP_CLI::log( '' );

		$docs_table = $wpdb->prefix . 'apollo_documents';
		$results    = array(
			'posts_checked'       => 0,
			'table_rows_checked'  => 0,
			'posts_without_table' => array(),
			'table_without_posts' => array(),
			'status_divergences'  => array(),
			'fixed'               => 0,
		);

		// 1. Get all apollo_document posts
		WP_CLI::log( 'Checking CPT posts...' );

		$posts = get_posts( array(
			'post_type'   => 'apollo_document',
			'post_status' => 'any',
			'numberposts' => -1,
		) );

		$results['posts_checked'] = count( $posts );
		WP_CLI::log( sprintf( 'Found %d apollo_document posts', count( $posts ) ) );

		$post_file_ids = array();

		foreach ( $posts as $post ) {
			$file_id = get_post_meta( $post->ID, '_apollo_doc_file_id', true );

			if ( ! $file_id ) {
				WP_CLI::warning( sprintf( 'Post %d has no file_id', $post->ID ) );
				continue;
			}

			$post_file_ids[ $file_id ] = $post->ID;

			// Check if table entry exists
			$table_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$docs_table} WHERE post_id = %d OR file_id = %s",
					$post->ID,
					$file_id
				),
				ARRAY_A
			);

			if ( ! $table_row ) {
				$results['posts_without_table'][] = array(
					'post_id' => $post->ID,
					'file_id' => $file_id,
				);

				if ( $fix && ! $dry_run ) {
					DocumentsRepository::syncToIndex( $post->ID );
					WP_CLI::success( sprintf( 'Created table entry for post %d', $post->ID ) );
					++$results['fixed'];
				}
			} else {
				// Check status divergence
				$post_state  = get_post_meta( $post->ID, '_apollo_doc_state', true ) ?: 'draft';
				$table_state = $table_row['status'] ?? 'draft';

				if ( $post_state !== $table_state ) {
					$results['status_divergences'][] = array(
						'post_id'     => $post->ID,
						'post_state'  => $post_state,
						'table_state' => $table_state,
					);

					if ( $fix && ! $dry_run ) {
						$wpdb->update(
							$docs_table,
							array( 'status' => $post_state ),
							array( 'post_id' => $post->ID )
						);
						WP_CLI::success( sprintf( 'Fixed status for post %d: %s → %s', $post->ID, $table_state, $post_state ) );
						++$results['fixed'];
					}
				}
			}
		}

		// 2. Check for orphaned table rows
		WP_CLI::log( '' );
		WP_CLI::log( 'Checking table entries...' );

		$table_rows = $wpdb->get_results(
			"SELECT id, post_id, file_id FROM {$docs_table}",
			ARRAY_A
		);

		$results['table_rows_checked'] = count( $table_rows );
		WP_CLI::log( sprintf( 'Found %d table entries', count( $table_rows ) ) );

		foreach ( $table_rows as $row ) {
			$post = get_post( $row['post_id'] );

			if ( ! $post || $post->post_type !== 'apollo_document' ) {
				$results['table_without_posts'][] = array(
					'table_id' => $row['id'],
					'post_id'  => $row['post_id'],
					'file_id'  => $row['file_id'],
				);

				if ( $fix && ! $dry_run ) {
					$wpdb->delete( $docs_table, array( 'id' => $row['id'] ) );
					WP_CLI::success( sprintf( 'Deleted orphan table entry %d', $row['id'] ) );
					++$results['fixed'];
				}
			}
		}

		// 3. Summary
		WP_CLI::log( '' );
		WP_CLI::log( '=== Summary ===' );
		WP_CLI::log( sprintf( 'Posts checked: %d', $results['posts_checked'] ) );
		WP_CLI::log( sprintf( 'Table rows checked: %d', $results['table_rows_checked'] ) );
		WP_CLI::log( sprintf( 'Posts without table entry: %d', count( $results['posts_without_table'] ) ) );
		WP_CLI::log( sprintf( 'Orphan table entries: %d', count( $results['table_without_posts'] ) ) );
		WP_CLI::log( sprintf( 'Status divergences: %d', count( $results['status_divergences'] ) ) );

		if ( $fix ) {
			WP_CLI::log( sprintf( 'Fixed: %d', $results['fixed'] ) );
		}

		$total_issues = count( $results['posts_without_table'] )
			+ count( $results['table_without_posts'] )
			+ count( $results['status_divergences'] );

		if ( $total_issues === 0 ) {
			WP_CLI::success( 'No issues found. Documents are in sync.' );
		} elseif ( $fix && ! $dry_run ) {
			WP_CLI::success( sprintf( 'Fixed %d issues.', $results['fixed'] ) );
		} else {
			WP_CLI::warning( sprintf( 'Found %d issues. Run with --fix to correct.', $total_issues ) );
		}
	}

	/**
	 * Migrate signatures to unified metakey.
	 *
	 * Unifies _apollo_doc_signatures and _apollo_document_signatures.
	 * Also backfills post_id in signatures table.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * [--cleanup]
	 * : Remove legacy metakey after migration.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms migrate-signatures
	 *     wp apollo dms migrate-signatures --dry-run
	 *     wp apollo dms migrate-signatures --cleanup
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function migrate_signatures( $args, $assoc_args ) {
		global $wpdb;

		$dry_run = Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$cleanup = Utils\get_flag_value( $assoc_args, 'cleanup', false );

		WP_CLI::log( '=== Apollo Signature Migration ===' );
		WP_CLI::log( $dry_run ? '(Dry run - no changes will be made)' : '' );
		WP_CLI::log( '' );

		$canonical_key = '_apollo_doc_signatures';
		$legacy_key    = '_apollo_document_signatures';

		$results = array(
			'posts_checked'   => 0,
			'migrated'        => 0,
			'conflicts'       => 0,
			'cleaned'         => 0,
			'post_id_backfill' => 0,
		);

		// 1. Find posts with legacy key
		$posts_with_legacy = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				$legacy_key
			)
		);

		WP_CLI::log( sprintf( 'Found %d posts with legacy signature key', count( $posts_with_legacy ) ) );

		foreach ( $posts_with_legacy as $post_id ) {
			++$results['posts_checked'];

			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== 'apollo_document' ) {
				continue;
			}

			$canonical = get_post_meta( $post_id, $canonical_key, true );
			$legacy    = get_post_meta( $post_id, $legacy_key, true );

			if ( ! empty( $canonical ) && ! empty( $legacy ) ) {
				// Conflict: both keys have data
				++$results['conflicts'];
				WP_CLI::warning( sprintf( 'Post %d has signatures in both keys (conflict)', $post_id ) );

				// Merge (canonical wins for duplicates)
				if ( ! $dry_run ) {
					$merged = self::mergeSignatures( $canonical, $legacy );
					update_post_meta( $post_id, $canonical_key, $merged );
					WP_CLI::log( sprintf( 'Merged signatures for post %d', $post_id ) );
				}
			} elseif ( empty( $canonical ) && ! empty( $legacy ) ) {
				// Migrate legacy to canonical
				if ( ! $dry_run ) {
					update_post_meta( $post_id, $canonical_key, $legacy );
				}
				++$results['migrated'];
				WP_CLI::log( sprintf( 'Migrated signatures for post %d', $post_id ) );
			}

			// Cleanup legacy key
			if ( $cleanup && ! $dry_run ) {
				delete_post_meta( $post_id, $legacy_key );
				++$results['cleaned'];
			}
		}

		// 2. Backfill post_id in signatures table
		WP_CLI::log( '' );
		WP_CLI::log( 'Backfilling post_id in signatures table...' );

		if ( ! $dry_run ) {
			$backfilled = DocumentsSchema::backfillPostIds();
			$results['post_id_backfill'] = $backfilled;
			WP_CLI::log( sprintf( 'Backfilled post_id for %d signatures', $backfilled ) );
		}

		// 3. Summary
		WP_CLI::log( '' );
		WP_CLI::log( '=== Summary ===' );
		WP_CLI::log( sprintf( 'Posts checked: %d', $results['posts_checked'] ) );
		WP_CLI::log( sprintf( 'Migrated: %d', $results['migrated'] ) );
		WP_CLI::log( sprintf( 'Conflicts resolved: %d', $results['conflicts'] ) );
		WP_CLI::log( sprintf( 'Legacy keys cleaned: %d', $results['cleaned'] ) );
		WP_CLI::log( sprintf( 'Post IDs backfilled: %d', $results['post_id_backfill'] ) );

		WP_CLI::success( 'Signature migration complete.' );
	}

	/**
	 * Show document statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms stats
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function stats( $args, $assoc_args ) {
		global $wpdb;

		WP_CLI::log( '=== Apollo DMS Statistics ===' );
		WP_CLI::log( '' );

		// CPT stats
		$post_counts = wp_count_posts( 'apollo_document' );
		$total_posts = array_sum( (array) $post_counts );

		WP_CLI::log( '--- CPT (Source of Truth) ---' );
		WP_CLI::log( sprintf( 'Total documents: %d', $total_posts ) );
		WP_CLI::log( sprintf( '  Draft: %d', $post_counts->draft ?? 0 ) );
		WP_CLI::log( sprintf( '  Pending: %d', $post_counts->pending ?? 0 ) );
		WP_CLI::log( sprintf( '  Private: %d', $post_counts->private ?? 0 ) );
		WP_CLI::log( sprintf( '  Published: %d', $post_counts->publish ?? 0 ) );
		WP_CLI::log( sprintf( '  Trash: %d', $post_counts->trash ?? 0 ) );

		// Table stats
		$docs_table = $wpdb->prefix . 'apollo_documents';
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$docs_table}'" ) === $docs_table ) {
			$table_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$docs_table}" );

			WP_CLI::log( '' );
			WP_CLI::log( '--- Index Table ---' );
			WP_CLI::log( sprintf( 'Total entries: %s', $table_count ) );

			// By status
			$status_counts = $wpdb->get_results(
				"SELECT status, COUNT(*) as count FROM {$docs_table} GROUP BY status",
				ARRAY_A
			);

			foreach ( $status_counts as $row ) {
				WP_CLI::log( sprintf( '  %s: %d', $row['status'], $row['count'] ) );
			}
		}

		// Signatures stats
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$sigs_table}'" ) === $sigs_table ) {
			$sig_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$sigs_table}" );
			$docs_with_sigs = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$sigs_table} WHERE post_id IS NOT NULL" );

			WP_CLI::log( '' );
			WP_CLI::log( '--- Signatures ---' );
			WP_CLI::log( sprintf( 'Total signatures: %s', $sig_count ) );
			WP_CLI::log( sprintf( 'Documents with signatures: %s', $docs_with_sigs ) );

			// Check orphans
			$orphan_sigs = $wpdb->get_var( "SELECT COUNT(*) FROM {$sigs_table} WHERE post_id IS NULL" );
			if ( $orphan_sigs > 0 ) {
				WP_CLI::warning( sprintf( 'Signatures without post_id: %s (run migrate-signatures)', $orphan_sigs ) );
			}
		}

		// Metakey stats
		$canonical_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_doc_signatures'"
		);
		$legacy_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_document_signatures'"
		);

		WP_CLI::log( '' );
		WP_CLI::log( '--- Metakeys ---' );
		WP_CLI::log( sprintf( 'Canonical key (_apollo_doc_signatures): %d', $canonical_count ) );
		WP_CLI::log( sprintf( 'Legacy key (_apollo_document_signatures): %d', $legacy_count ) );

		if ( $legacy_count > 0 ) {
			WP_CLI::warning( 'Legacy metakey still in use. Run migrate-signatures.' );
		}

		WP_CLI::success( 'Statistics complete.' );
	}

	/**
	 * Run full audit.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms audit
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function audit( $args, $assoc_args ) {
		WP_CLI::log( '=== Apollo DMS Full Audit ===' );
		WP_CLI::log( '' );

		// Run stats
		$this->stats( $args, $assoc_args );

		WP_CLI::log( '' );
		WP_CLI::log( '---' );
		WP_CLI::log( '' );

		// Run reconcile in dry-run
		$this->reconcile( $args, array( 'dry-run' => true ) );

		WP_CLI::log( '' );
		WP_CLI::success( 'Audit complete. Run with --fix to correct issues.' );
	}

	/**
	 * Run full migration (FASE 11).
	 *
	 * Runs all migration steps:
	 * - Schema migration (add post_id column)
	 * - Metakey unification
	 * - Post ID backfill
	 * - Document sync
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms migrate --dry-run
	 *     wp apollo dms migrate
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function migrate( $args, $assoc_args ) {
		$dry_run = Utils\get_flag_value( $assoc_args, 'dry-run', false );

		WP_CLI::log( '=== Apollo DMS Full Migration ===' );
		WP_CLI::log( $dry_run ? '(Dry run - no changes will be made)' : '' );
		WP_CLI::log( '' );

		$results = DocumentsMigration::runMigration( $dry_run );

		// Show preflight
		WP_CLI::log( '--- Preflight Checks ---' );
		foreach ( $results['preflight']['checks'] as $name => $check ) {
			$icon = $check['status'] === 'ok' ? '✓' : '⚠';
			WP_CLI::log( sprintf( '  %s %s: %s', $icon, $check['name'], $check['message'] ) );
		}
		WP_CLI::log( '' );

		// Show steps
		WP_CLI::log( '--- Migration Steps ---' );
		foreach ( $results['steps'] as $step => $step_result ) {
			$status = empty( $step_result['errors'] ) ? '✓' : '✗';

			switch ( $step ) {
				case 'schema':
					WP_CLI::log( sprintf( '  %s Schema: %s', $status, json_encode( $step_result['changes'] ) ) );
					break;
				case 'metakeys':
					WP_CLI::log( sprintf( '  %s Metakeys: %d migrated', $status, $step_result['migrated'] ) );
					break;
				case 'backfill':
					WP_CLI::log( sprintf( '  %s Backfill: %d updated', $status, $step_result['updated'] ) );
					break;
				case 'sync':
					WP_CLI::log( sprintf( '  %s Sync: %d synced', $status, $step_result['synced'] ) );
					break;
			}
		}

		WP_CLI::log( '' );

		if ( $results['success'] ) {
			WP_CLI::success( 'Migration ' . ( $dry_run ? 'check' : '' ) . ' complete.' );
		} else {
			WP_CLI::error( 'Migration failed: ' . implode( ', ', $results['errors'] ) );
		}
	}

	/**
	 * Show migration status (FASE 11).
	 *
	 * Shows current migration phase and recommendations.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms status
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function status( $args, $assoc_args ) {
		WP_CLI::log( '=== Apollo DMS Migration Status ===' );
		WP_CLI::log( '' );

		$report = DocumentsMigration::getReport();

		// Current phase
		$phase      = $report['current_phase'];
		$phase_name = DocumentsMigration::PHASES[ $phase ] ?? 'Unknown';
		WP_CLI::log( sprintf( 'Current Phase: %s - %s', $phase, $phase_name ) );
		WP_CLI::log( '' );

		// Phase completion
		WP_CLI::log( '--- Phases ---' );
		foreach ( DocumentsMigration::PHASES as $key => $name ) {
			$completed = $report['status'][ $key . '_completed' ] ?? false;
			$icon      = $completed ? '✓' : '○';
			WP_CLI::log( sprintf( '  %s %s: %s', $icon, $key, $name ) );
		}
		WP_CLI::log( '' );

		// Metrics
		WP_CLI::log( '--- Metrics ---' );
		foreach ( $report['metrics'] as $key => $value ) {
			WP_CLI::log( sprintf( '  %s: %d', $key, $value ) );
		}
		WP_CLI::log( '' );

		// Recommendations
		if ( ! empty( $report['recommendations'] ) ) {
			WP_CLI::log( '--- Recommendations ---' );
			foreach ( $report['recommendations'] as $rec ) {
				$icon = $rec['type'] === 'warning' ? '⚠' : ( $rec['type'] === 'action' ? '→' : 'ℹ' );
				WP_CLI::log( sprintf( '  %s %s', $icon, $rec['message'] ) );
			}
		}

		if ( $report['is_complete'] ) {
			WP_CLI::success( 'Migration is complete!' );
		} else {
			WP_CLI::log( '' );
			WP_CLI::log( 'Next steps:' );
			WP_CLI::log( '  1. Run: wp apollo dms migrate --dry-run' );
			WP_CLI::log( '  2. Run: wp apollo dms migrate' );
			WP_CLI::log( '  3. Run: wp apollo dms cleanup (when ready)' );
		}
	}

	/**
	 * Cleanup legacy data (FASE 11 final).
	 *
	 * Removes:
	 * - Legacy signature metakey
	 * - Orphan index entries
	 * - Orphan signatures
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Run cleanup even if migration not complete.
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms cleanup --yes
	 *     wp apollo dms cleanup --force --yes
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function cleanup( $args, $assoc_args ) {
		$force = Utils\get_flag_value( $assoc_args, 'force', false );

		WP_CLI::log( '=== Apollo DMS Legacy Cleanup ===' );
		WP_CLI::log( '' );

		// Check migration status
		if ( ! DocumentsMigration::isComplete() && ! $force ) {
			WP_CLI::error( 'Migration not complete. Run "wp apollo dms migrate" first, or use --force.' );
			return;
		}

		// Confirmation
		WP_CLI::confirm( 'This will permanently remove legacy data. Continue?', $assoc_args );

		WP_CLI::log( 'Running cleanup...' );
		$results = DocumentsMigration::cleanupLegacy( $force );

		WP_CLI::log( '' );
		WP_CLI::log( '--- Removed ---' );
		foreach ( $results['removed'] as $type => $count ) {
			WP_CLI::log( sprintf( '  %s: %d', $type, $count ) );
		}

		if ( empty( $results['errors'] ) ) {
			WP_CLI::success( 'Cleanup complete.' );
		} else {
			WP_CLI::error( 'Cleanup failed: ' . implode( ', ', $results['errors'] ) );
		}
	}

	/**
	 * Complete a migration phase.
	 *
	 * ## OPTIONS
	 *
	 * <phase>
	 * : Phase to complete (phase_1 to phase_6).
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo dms complete-phase phase_4
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function complete_phase( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Please specify a phase (phase_1 to phase_6).' );
			return;
		}

		$phase = $args[0];

		if ( ! isset( DocumentsMigration::PHASES[ $phase ] ) ) {
			WP_CLI::error( 'Invalid phase. Valid: ' . implode( ', ', array_keys( DocumentsMigration::PHASES ) ) );
			return;
		}

		DocumentsMigration::completePhase( $phase );
		WP_CLI::success( sprintf( 'Phase %s marked as complete.', $phase ) );
	}

	/**
	 * Merge two signature arrays, avoiding duplicates
	 *
	 * @param array $primary   Primary signatures (wins on conflict).
	 * @param array $secondary Secondary signatures.
	 * @return array Merged signatures.
	 */
	private static function mergeSignatures( array $primary, array $secondary ): array {
		$merged = $primary;
		$existing_ids = array();

		// Collect IDs from primary
		foreach ( $primary as $sig ) {
			if ( isset( $sig['signer_user_id'] ) ) {
				$existing_ids[] = 'user_' . $sig['signer_user_id'];
			}
			if ( isset( $sig['signer_cpf_hash'] ) ) {
				$existing_ids[] = 'cpf_' . $sig['signer_cpf_hash'];
			}
		}

		// Add non-duplicate from secondary
		foreach ( $secondary as $sig ) {
			$id = null;
			if ( isset( $sig['signer_user_id'] ) ) {
				$id = 'user_' . $sig['signer_user_id'];
			} elseif ( isset( $sig['signer_cpf_hash'] ) ) {
				$id = 'cpf_' . $sig['signer_cpf_hash'];
			}

			if ( $id && ! in_array( $id, $existing_ids, true ) ) {
				$merged[] = $sig;
			}
		}

		return $merged;
	}
}

// Register commands
WP_CLI::add_command( 'apollo dms', __NAMESPACE__ . '\DocumentsCLI' );
