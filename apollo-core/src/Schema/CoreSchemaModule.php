<?php
/**
 * Core Schema Module
 *
 * Apollo Core's schema module for the orchestrator.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Core schema module.
 *
 * Manages tables for:
 * - Moderation logs
 * - Audit logs
 * - Analytics (pageviews, interactions, sessions, heatmaps)
 * - Quiz attempts
 * - Newsletter
 */
class CoreSchemaModule implements SchemaModuleInterface {

	/**
	 * Module name.
	 */
	private const MODULE_NAME = 'core';

	/**
	 * Current schema version.
	 */
	private const VERSION = '2.0.0';

	/**
	 * Option name for version tracking.
	 */
	private const VERSION_OPTION = 'apollo_core_schema_version';

	/**
	 * Tables owned by this module.
	 */
	private const TABLES = [
		'apollo_mod_log',
		'apollo_audit_log',
		'apollo_analytics_pageviews',
		'apollo_analytics_interactions',
		'apollo_analytics_sessions',
		'apollo_analytics_user_stats',
		'apollo_analytics_content_stats',
		'apollo_analytics_heatmap',
		'apollo_analytics_settings',
		'apollo_quiz_attempts',
		'apollo_newsletter_subscribers',
		'apollo_newsletter_campaigns',
		'apollo_email_security_log',
	];

	/**
	 * {@inheritDoc}
	 */
	public function getModuleName(): string {
		return self::MODULE_NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion(): string {
		return self::VERSION;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersionOption(): string {
		return self::VERSION_OPTION;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getStoredVersion(): string {
		return get_option( self::VERSION_OPTION, '0.0.0' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function needsUpgrade(): bool {
		return version_compare( $this->getStoredVersion(), self::VERSION, '<' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTables(): array {
		return self::TABLES;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIndexes(): array {
		return [
			'apollo_mod_log'                  => [ 'actor_id_idx', 'action_idx', 'target_type_idx', 'target_id_idx', 'created_at_idx' ],
			'apollo_audit_log'                => [ 'event_type_idx', 'actor_id_idx', 'severity_idx', 'created_at_idx' ],
			'apollo_analytics_pageviews'      => [ 'session_idx', 'user_idx', 'page_type_idx', 'post_id_idx', 'created_at_idx', 'device_idx' ],
			'apollo_analytics_interactions'   => [ 'session_idx', 'pageview_idx', 'user_idx', 'interaction_type_idx', 'created_at_idx' ],
			'apollo_analytics_sessions'       => [ 'session_id_idx', 'user_idx', 'started_at_idx' ],
			'apollo_analytics_user_stats'     => [ 'user_idx' ],
			'apollo_analytics_content_stats'  => [ 'content_idx' ],
			'apollo_quiz_attempts'            => [ 'user_idx', 'quiz_idx' ],
			'apollo_newsletter_subscribers'   => [ 'email_idx', 'status_idx' ],
			'apollo_newsletter_campaigns'     => [ 'status_idx', 'scheduled_idx' ],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function install(): void {
		// Delegate to existing db-schema.php function.
		if ( function_exists( 'Apollo_Core\\create_db_tables' ) ) {
			\Apollo_Core\create_db_tables();
		} else {
			// Fallback: include the file if needed.
			$schema_file = APOLLO_CORE_PLUGIN_DIR . 'includes/db-schema.php';
			if ( file_exists( $schema_file ) ) {
				require_once $schema_file;
				if ( function_exists( 'Apollo_Core\\create_db_tables' ) ) {
					\Apollo_Core\create_db_tables();
				}
			}
		}

		// Update version.
		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * {@inheritDoc}
	 */
	public function upgrade( string $from_version ): void {
		// Re-run install (dbDelta is idempotent).
		$this->install();

		// Version-specific migrations.
		if ( version_compare( $from_version, '2.0.0', '<' ) ) {
			$this->migrate_2_0_0();
		}

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Migration to 2.0.0.
	 *
	 * Adds any missing indexes.
	 *
	 * @return void
	 */
	private function migrate_2_0_0(): void {
		global $wpdb;

		// Ensure critical indexes exist on mod_log.
		$table = $wpdb->prefix . 'apollo_mod_log';

		// Check if index exists before adding.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW INDEX FROM {$table} WHERE Key_name = %s",
				'actor_id_idx'
			)
		);

		if ( empty( $existing ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX actor_id_idx (actor_id)" );
		}
	}
}
