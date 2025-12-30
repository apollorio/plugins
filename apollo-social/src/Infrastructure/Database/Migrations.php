<?php
/**
 * Apollo Migrations - Non-destructive schema updates
 *
 * This class manages incremental migrations to avoid data loss.
 * Each migration is idempotent and version-gated.
 *
 * @package Apollo\Infrastructure\Database
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Infrastructure\Database;

/**
 * Migration Manager
 *
 * Usage:
 *   $migrations = new Migrations();
 *   $migrations->runPending();
 */
class Migrations {

	/** @var string Option key for migration version */
	private const VERSION_OPTION = 'apollo_migration_version';

	/** @var string Current target version */
	private const TARGET_VERSION = '2.3.0';

	/**
	 * Run all pending migrations
	 *
	 * @return bool True if all migrations succeeded
	 */
	public static function runPending(): bool {
		$current = get_option( self::VERSION_OPTION, '2.0.0' );

		// Migrations registry (version => callable)
		$migrations = array(
			'2.1.0' => array( __CLASS__, 'migrate_2_1_0' ),
			'2.2.0' => array( __CLASS__, 'migrate_2_2_0' ),
			'2.3.0' => array( __CLASS__, 'migrate_2_3_0' ),
		);

		$success = true;
		foreach ( $migrations as $version => $callable ) {
			if ( version_compare( $current, $version, '<' ) ) {
				try {
					if ( ! \call_user_func( $callable ) ) {
						$success = false;
						break;
					}
					update_option( self::VERSION_OPTION, $version );
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( "✅ Apollo: Migration $version completed" );
					}
				} catch ( \Exception $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( "❌ Apollo: Migration $version failed - " . $e->getMessage() );
					}
					$success = false;
					break;
				}
			}
		}

		if ( $success ) {
			update_option( self::VERSION_OPTION, self::TARGET_VERSION );
		}

		return $success;
	}

	/**
	 * Migration 2.1.0: Reserved for future schema changes
	 *
	 * @return bool
	 */
	private static function migrate_2_1_0(): bool {
		// Currently no-op; placeholder for future migrations
		return true;
	}

	/**
	 * Migration 2.2.0: Add explicit type column to groups table
	 *
	 * Changes:
	 * 1. Alter wp_apollo_groups: add column `group_type` (enum: 'comuna', 'nucleo')
	 * 2. Backfill: default to 'comuna' for all existing groups
	 * 3. Index on group_type for performance
	 *
	 * @return bool
	 */
	private static function migrate_2_2_0(): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_groups';

		// Check if column already exists (idempotent)
		$result = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'group_type'" );
		if ( ! empty( $result ) ) {
			return true; // Already migrated
		}

		// Disable foreign key checks temporarily
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS=0' );

		try {
			// 1. Add group_type column
			$wpdb->query(
				"ALTER TABLE {$table} ADD COLUMN `group_type`
				ENUM('comuna','nucleo','season') NOT NULL DEFAULT 'comuna'
				AFTER `type_id`"
			);

			// 2. Backfill existing groups: use type_id to infer group_type
			// If type_id exists and is non-null, check wp_apollo_group_types for 'nucleo'
			$wpdb->query(
				"UPDATE {$table} t
				SET t.group_type = 'nucleo'
				WHERE t.type_id IS NOT NULL
				AND EXISTS (
					SELECT 1 FROM {$wpdb->prefix}apollo_group_types gt
					WHERE gt.id = t.type_id AND gt.name LIKE '%nucleo%' COLLATE utf8mb4_general_ci
				)"
			);

			// 3. Add index for group_type
			$wpdb->query(
				"ALTER TABLE {$table} ADD INDEX `group_type_idx` (`group_type`)"
			);

			// Re-enable foreign key checks
			$wpdb->query( 'SET FOREIGN_KEY_CHECKS=1' );

			return true;
		} catch ( \Exception $e ) {
			$wpdb->query( 'SET FOREIGN_KEY_CHECKS=1' );
			throw $e;
		}
	}

	/**
	 * Get current migration version
	 *
	 * @return string
	 */
	public static function getCurrentVersion(): string {
		return get_option( self::VERSION_OPTION, '2.0.0' );
	}

	/**
	 * Reset migrations (dev only)
	 *
	 * @return void
	 */
	public static function reset(): void {
		delete_option( self::VERSION_OPTION );
	}

	/**
	 * Migration 2.3.0: Add indexes and unique keys for critical tables
	 *
	 * Changes:
	 * 1. Add unique key on (group_id, user_id) for group_members (one row per member)
	 * 2. Add index on (user_id) for group_members (find groups by user)
	 * 3. Add index on (role) for group_members (find members by role)
	 * 4. Add index on (owner_id) for groups (find groups by owner)
	 * 5. Add index on (group_type, visibility) for groups (filter groups)
	 * 6. Add index on (inviter_id) for invites (find pending invites)
	 * 7. Add unique key on invites (group_id, invitee_id) where status != 'rejected'
	 *
	 * All additions are idempotent (check existence first).
	 *
	 * @return bool
	 */
	private static function migrate_2_3_0(): bool {
		global $wpdb;

		try {
			// ===== Groups Table =====
			$groups_tbl = $wpdb->prefix . 'apollo_groups';

			// Check and add owner_id index
			$index_exists = $wpdb->get_var(
				"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
				WHERE TABLE_NAME = '{$groups_tbl}' AND COLUMN_NAME = 'owner_id' AND INDEX_NAME = 'owner_id_idx'"
			);
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE {$groups_tbl} ADD INDEX `owner_id_idx` (`owner_id`)" );
			}

			// Check and add type+visibility index
			$index_exists = $wpdb->get_var(
				"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
				WHERE TABLE_NAME = '{$groups_tbl}' AND COLUMN_NAME = 'group_type' AND INDEX_NAME = 'type_visibility_idx'"
			);
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE {$groups_tbl} ADD INDEX `type_visibility_idx` (`group_type`, `visibility`)" );
			}

			// ===== Group Members Table =====
			$members_tbl = $wpdb->prefix . 'apollo_group_members';

			// Check and add unique key on (group_id, user_id)
			$constraint_exists = $wpdb->get_var(
				"SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.STATISTICS
				WHERE TABLE_NAME = '{$members_tbl}' AND CONSTRAINT_NAME = 'uq_group_user'"
			);
			if ( ! $constraint_exists ) {
				$wpdb->query(
					"ALTER TABLE {$members_tbl} ADD UNIQUE KEY `uq_group_user` (`group_id`, `user_id`)"
				);
			}

			// Check and add user_id index
			$index_exists = $wpdb->get_var(
				"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
				WHERE TABLE_NAME = '{$members_tbl}' AND COLUMN_NAME = 'user_id' AND INDEX_NAME = 'user_id_idx'"
			);
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE {$members_tbl} ADD INDEX `user_id_idx` (`user_id`)" );
			}

			// Check and add role index
			$index_exists = $wpdb->get_var(
				"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
				WHERE TABLE_NAME = '{$members_tbl}' AND COLUMN_NAME = 'role' AND INDEX_NAME = 'role_idx'"
			);
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE {$members_tbl} ADD INDEX `role_idx` (`role`)" );
			}

			// ===== Group Invites Table =====
			$invites_tbl = $wpdb->prefix . 'apollo_group_invites';

			// Check if invites table exists before altering
			$table_exists = $wpdb->get_var(
				"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$invites_tbl}'"
			);

			if ( $table_exists ) {
				// Check and add inviter_id index
				$index_exists = $wpdb->get_var(
					"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
					WHERE TABLE_NAME = '{$invites_tbl}' AND COLUMN_NAME = 'inviter_id' AND INDEX_NAME = 'inviter_id_idx'"
				);
				if ( ! $index_exists ) {
					$wpdb->query( "ALTER TABLE {$invites_tbl} ADD INDEX `inviter_id_idx` (`inviter_id`)" );
				}

				// Check and add unique key on (group_id, invitee_id)
				$constraint_exists = $wpdb->get_var(
					"SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.STATISTICS
					WHERE TABLE_NAME = '{$invites_tbl}' AND CONSTRAINT_NAME = 'uq_group_invitee'"
				);
				if ( ! $constraint_exists ) {
					$wpdb->query(
						"ALTER TABLE {$invites_tbl} ADD UNIQUE KEY `uq_group_invitee` (`group_id`, `invitee_id`)"
					);
				}
			}

			// ===== Documents Table (if exists) =====
			$docs_tbl = $wpdb->prefix . 'apollo_documents';

			$table_exists = $wpdb->get_var(
				"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$docs_tbl}'"
			);

			if ( $table_exists ) {
				// Check and add author_id index
				$index_exists = $wpdb->get_var(
					"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
					WHERE TABLE_NAME = '{$docs_tbl}' AND COLUMN_NAME = 'author_id' AND INDEX_NAME = 'author_id_idx'"
				);
				if ( ! $index_exists ) {
					$wpdb->query( "ALTER TABLE {$docs_tbl} ADD INDEX `author_id_idx` (`author_id`)" );
				}

				// Check and add status index
				$index_exists = $wpdb->get_var(
					"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
					WHERE TABLE_NAME = '{$docs_tbl}' AND COLUMN_NAME = 'status' AND INDEX_NAME = 'status_idx'"
				);
				if ( ! $index_exists ) {
					$wpdb->query( "ALTER TABLE {$docs_tbl} ADD INDEX `status_idx` (`status`)" );
				}
			}

			// ===== Document Signatures Table (if exists) =====
			$sigs_tbl = $wpdb->prefix . 'apollo_document_signatures';

			$table_exists = $wpdb->get_var(
				"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$sigs_tbl}'"
			);

			if ( $table_exists ) {
				// Check and add document_id + signer_id index
				$index_exists = $wpdb->get_var(
					"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
					WHERE TABLE_NAME = '{$sigs_tbl}' AND COLUMN_NAME = 'document_id' AND INDEX_NAME = 'doc_signer_idx'"
				);
				if ( ! $index_exists ) {
					$wpdb->query(
						"ALTER TABLE {$sigs_tbl} ADD INDEX `doc_signer_idx` (`document_id`, `signer_id`)"
					);
				}

				// Check and add status index
				$index_exists = $wpdb->get_var(
					"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
					WHERE TABLE_NAME = '{$sigs_tbl}' AND COLUMN_NAME = 'status' AND INDEX_NAME = 'sig_status_idx'"
				);
				if ( ! $index_exists ) {
					$wpdb->query( "ALTER TABLE {$sigs_tbl} ADD INDEX `sig_status_idx` (`status`)" );
				}
			}

			return true;
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Migration 2.3.0 error: " . $e->getMessage() );
			}
			return false;
		}
	}
}
