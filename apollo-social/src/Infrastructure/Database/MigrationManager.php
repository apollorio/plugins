<?php

/**
 * P0-3: Migration Manager
 *
 * Manages database schema migrations with versioning and rollback support.
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\Infrastructure\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MigrationManager {

	private $current_version;
	private $target_version;
	private $migrations_table;

	public function __construct() {
		global $wpdb;
		$this->migrations_table = $wpdb->prefix . 'apollo_migrations';
		$this->current_version  = $this->getCurrentVersion();
		$this->target_version   = APOLLO_SOCIAL_VERSION;
	}

	/**
	 * P0-3: Get current migration version
	 */
	private function getCurrentVersion(): string {
		return get_option( 'apollo_schema_version', '0.0.0' );
	}

	/**
	 * P0-3: Create migrations tracking table
	 */
	public function createMigrationsTable(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->migrations_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            version varchar(20) NOT NULL,
            migration_name varchar(255) NOT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            execution_time decimal(10,4) NULL,
            status enum('pending', 'running', 'completed', 'failed', 'rolled_back') NOT NULL DEFAULT 'pending',
            error_message text NULL,
            rollback_data longtext NULL,
            PRIMARY KEY (id),
            UNIQUE KEY version_name_idx (version, migration_name),
            KEY version_idx (version),
            KEY status_idx (status),
            KEY executed_idx (executed_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * P0-3: Check if migration needs to run
	 */
	public function needsMigration(): bool {
		return version_compare( $this->current_version, $this->target_version, '<' );
	}

	/**
	 * P0-3: Run pending migrations
	 */
	public function migrate(): array {
		$results = array(
			'success'        => true,
			'migrations_run' => array(),
			'errors'         => array(),
		);

		if ( ! $this->needsMigration() ) {
			return $results;
		}

		// Ensure migrations table exists
		$this->createMigrationsTable();

		// Get migration files
		$migrations = $this->getMigrationFiles();

		foreach ( $migrations as $version => $migration_file ) {
			if ( version_compare( $this->current_version, $version, '<' ) ) {
				$result = $this->runMigration( $version, $migration_file );

				if ( $result['success'] ) {
					$results['migrations_run'][] = $version;
					$this->current_version       = $version;
					update_option( 'apollo_schema_version', $version );
				} else {
					$results['success']  = false;
					$results['errors'][] = array(
						'version' => $version,
						'error'   => $result['error'],
					);

					break;
					// Stop on first error
				}
			}
		}

		return $results;
	}

	/**
	 * P0-3: Get migration files
	 */
	private function getMigrationFiles(): array {
		$migrations_dir = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Infrastructure/Database/migrations/';
		$migrations     = array();

		if ( ! is_dir( $migrations_dir ) ) {
			return $migrations;
		}

		$files = glob( $migrations_dir . '*.php' );

		foreach ( $files as $file ) {
			$basename = basename( $file, '.php' );
			// Format: VERSION_description.php
			if ( preg_match( '/^(\d+\.\d+\.\d+)_(.+)$/', $basename, $matches ) ) {
				$version                = $matches[1];
				$migrations[ $version ] = $file;
			}
		}

		// Sort by version
		uksort( $migrations, 'version_compare' );

		return $migrations;
	}

	/**
	 * P0-3: Run a single migration
	 */
	private function runMigration( string $version, string $file ): array {
		global $wpdb;

		$migration_name = basename( $file, '.php' );
		$start_time     = microtime( true );

		// Check if already executed
		$executed = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->migrations_table} 
            WHERE version = %s AND migration_name = %s AND status = 'completed'",
				$version,
				$migration_name
			)
		);

		if ( $executed > 0 ) {
			return array(
				'success' => true,
				'skipped' => true,
			);
		}

		// Mark as running
		$wpdb->insert(
			$this->migrations_table,
			array(
				'version'        => $version,
				'migration_name' => $migration_name,
				'status'         => 'running',
			),
			array( '%s', '%s', '%s' )
		);

		try {
			// Load and execute migration
			if ( file_exists( $file ) ) {
				require_once $file;

				// Migration file should define a class or function
				// For now, we'll use a simple callback pattern
				if ( function_exists( 'apollo_migration_' . str_replace( '.', '_', $version ) ) ) {
					$callback      = 'apollo_migration_' . str_replace( '.', '_', $version );
					$rollback_data = $callback();
				} else {
					throw new \Exception( "Migration callback not found for version {$version}" );
				}

				$execution_time = microtime( true ) - $start_time;

				// Mark as completed
				$wpdb->update(
					$this->migrations_table,
					array(
						'status'         => 'completed',
						'execution_time' => $execution_time,
						'rollback_data'  => maybe_serialize( $rollback_data ),
					),
					array(
						'version'        => $version,
						'migration_name' => $migration_name,
					),
					array( '%s', '%f', '%s' ),
					array( '%s', '%s' )
				);

				return array(
					'success'       => true,
					'rollback_data' => $rollback_data,
				);
			} else {
				throw new \Exception( "Migration file not found: {$file}" );
			}//end if
		} catch ( \Exception $e ) {
			$execution_time = microtime( true ) - $start_time;

			// Mark as failed
			$wpdb->update(
				$this->migrations_table,
				array(
					'status'         => 'failed',
					'execution_time' => $execution_time,
					'error_message'  => $e->getMessage(),
				),
				array(
					'version'        => $version,
					'migration_name' => $migration_name,
				),
				array( '%s', '%f', '%s' ),
				array( '%s', '%s' )
			);

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}//end try
	}

	/**
	 * P0-3: Rollback a migration
	 */
	public function rollback( string $version ): bool {
		global $wpdb;

		$migration = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->migrations_table} 
            WHERE version = %s AND status = 'completed' 
            ORDER BY executed_at DESC LIMIT 1",
				$version
			)
		);

		if ( ! $migration ) {
			return false;
		}

		$rollback_data = maybe_unserialize( $migration->rollback_data );

		// Execute rollback logic (would need to be defined in migration file)
		// For now, this is a placeholder

		$wpdb->update(
			$this->migrations_table,
			array( 'status' => 'rolled_back' ),
			array( 'id' => $migration->id ),
			array( '%s' ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * P0-3: Get migration status
	 */
	public function getStatus(): array {
		global $wpdb;

		$status = array(
			'current_version' => $this->current_version,
			'target_version'  => $this->target_version,
			'needs_migration' => $this->needsMigration(),
			'migrations'      => array(),
		);

		$migrations = $wpdb->get_results(
			"SELECT version, migration_name, status, executed_at, execution_time, error_message 
            FROM {$this->migrations_table} 
            ORDER BY version DESC"
		);

		foreach ( $migrations as $migration ) {
			$status['migrations'][] = array(
				'version'        => $migration->version,
				'name'           => $migration->migration_name,
				'status'         => $migration->status,
				'executed_at'    => $migration->executed_at,
				'execution_time' => $migration->execution_time,
				'error'          => $migration->error_message,
			);
		}

		return $status;
	}
}
