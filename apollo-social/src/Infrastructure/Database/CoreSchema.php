<?php
/**
 * Core Schema
 *
 * Creates and manages core Apollo database tables (groups, workflow, moderation, etc).
 * These are always installed regardless of feature flags.
 *
 * @package Apollo\Infrastructure\Database
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Infrastructure\Database;

/**
 * Core Schema - Base tables for Apollo Social.
 */
class CoreSchema {

	/**
	 * Install all core tables (idempotent).
	 *
	 * @return true
	 */
	public function install(): bool {
		$this->createGroupsTable();
		$this->createGroupMembersTable();
		$this->createWorkflowLogTable();
		$this->createModerationQueueTable();
		$this->createAnalyticsTable();
		$this->createSignatureRequestsTable();
		$this->createOnboardingProgressTable();
		$this->createVerificationTokensTable();

		return true;
	}

	/**
	 * Upgrade core tables.
	 *
	 * @param string $fromVersion Current version.
	 * @param string $toVersion   Target version.
	 * @return true
	 */
	public function upgrade( string $fromVersion, string $toVersion ): bool {
		// Re-run install to ensure all columns/indexes exist (dbDelta is idempotent).
		$this->install();

		// Version-specific migrations.
		if ( version_compare( $fromVersion, '1.0.0', '<' ) ) {
			$this->updateGroupsStatusColumn();
			$this->updateAdsStatusColumn();
		}

		return true;
	}

	/**
	 * Uninstall core tables.
	 */
	public function uninstall(): void {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'apollo_workflow_log',
			$wpdb->prefix . 'apollo_mod_queue',
			$wpdb->prefix . 'apollo_groups',
			$wpdb->prefix . 'apollo_group_members',
			$wpdb->prefix . 'apollo_analytics',
			$wpdb->prefix . 'apollo_signature_requests',
			$wpdb->prefix . 'apollo_onboarding_progress',
			$wpdb->prefix . 'apollo_verification_tokens',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Create groups table.
	 */
	private function createGroupsTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_groups';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			type enum('comunidade','nucleo','season') NOT NULL DEFAULT 'comunidade',
			status enum('draft','pending_review','published','rejected','suspended') NOT NULL DEFAULT 'draft',
			visibility enum('public','private','members_only') NOT NULL DEFAULT 'public',
			season_slug varchar(100) NULL,
			creator_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NULL ON UPDATE CURRENT_TIMESTAMP,
			published_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_uk (slug),
			KEY type_idx (type),
			KEY status_idx (status),
			KEY visibility_idx (visibility),
			KEY creator_idx (creator_id),
			KEY season_idx (season_slug)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create group members table.
	 */
	private function createGroupMembersTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_group_members';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role enum('member','moderator','admin') NOT NULL DEFAULT 'member',
			status enum('active','pending','banned','left') NOT NULL DEFAULT 'active',
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			left_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY group_user_uk (group_id,user_id),
			KEY group_idx (group_id),
			KEY user_idx (user_id),
			KEY status_idx (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create workflow log table.
	 */
	private function createWorkflowLogTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_workflow_log';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			content_id bigint(20) unsigned NOT NULL,
			content_type varchar(50) NOT NULL,
			from_state varchar(50) NOT NULL,
			to_state varchar(50) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			reason text,
			metadata longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY content_idx (content_id,content_type),
			KEY user_idx (user_id),
			KEY created_idx (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create moderation queue table.
	 */
	private function createModerationQueueTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_mod_queue';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			content_id bigint(20) unsigned NOT NULL,
			content_type varchar(50) NOT NULL,
			content_title varchar(255) NOT NULL,
			author_id bigint(20) unsigned NOT NULL,
			status enum('pending','approved','rejected','escalated') NOT NULL DEFAULT 'pending',
			priority tinyint(1) unsigned NOT NULL DEFAULT 1,
			assigned_moderator_id bigint(20) unsigned NULL,
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			reviewed_at datetime NULL,
			reviewer_id bigint(20) unsigned NULL,
			review_notes text,
			metadata longtext,
			PRIMARY KEY  (id),
			KEY content_idx (content_id,content_type),
			KEY author_idx (author_id),
			KEY status_idx (status),
			KEY priority_idx (priority)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create analytics table.
	 */
	private function createAnalyticsTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_analytics';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(100) NOT NULL,
			event_name varchar(100) NOT NULL,
			user_id bigint(20) unsigned NULL,
			session_id varchar(100) NOT NULL,
			ip_address varchar(45) NULL,
			user_agent text,
			url varchar(500) NOT NULL,
			referrer varchar(500),
			properties longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY event_idx (event_type,event_name),
			KEY user_idx (user_id),
			KEY session_idx (session_id),
			KEY created_idx (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create signature requests table.
	 */
	private function createSignatureRequestsTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_signature_requests';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_token varchar(100) NOT NULL,
			requester_id bigint(20) unsigned NOT NULL,
			signer_name varchar(255) NOT NULL,
			signer_phone varchar(20) NOT NULL,
			signer_instagram varchar(100),
			document_title varchar(255) NOT NULL,
			document_hash varchar(64) NOT NULL,
			signature_type enum('canvas','typed') NOT NULL,
			signature_data longtext,
			evidence_pack longtext,
			status enum('pending','signed','expired','cancelled') NOT NULL DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			signed_at datetime NULL,
			expires_at datetime NOT NULL,
			metadata longtext,
			PRIMARY KEY  (id),
			UNIQUE KEY token_uk (request_token),
			KEY requester_idx (requester_id),
			KEY status_idx (status),
			KEY expires_idx (expires_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create onboarding progress table.
	 */
	private function createOnboardingProgressTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_onboarding_progress';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			step_number tinyint(2) unsigned NOT NULL,
			step_name varchar(100) NOT NULL,
			status enum('pending','completed','skipped') NOT NULL DEFAULT 'pending',
			step_data longtext,
			completed_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_step_uk (user_id,step_number),
			KEY user_idx (user_id),
			KEY status_idx (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create verification tokens table.
	 */
	private function createVerificationTokensTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'apollo_verification_tokens';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			platform varchar(50) NOT NULL,
			username varchar(100) NOT NULL,
			token varchar(100) NOT NULL,
			status enum('pending','verified','failed','expired') NOT NULL DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			verified_at datetime NULL,
			expires_at datetime NOT NULL,
			metadata longtext,
			PRIMARY KEY  (id),
			UNIQUE KEY user_platform_uk (user_id,platform),
			KEY platform_idx (platform),
			KEY token_idx (token),
			KEY status_idx (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add status column to groups table if missing.
	 */
	private function updateGroupsStatusColumn(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_groups';

		$column_exists = $wpdb->get_results(
			$wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'status' )
		);

		if ( empty( $column_exists ) ) {
			$wpdb->query(
				"ALTER TABLE {$table} ADD COLUMN status enum('draft','pending_review','published','rejected','suspended') NOT NULL DEFAULT 'draft' AFTER visibility"
			);
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX status_idx (status)" );
		}
	}

	/**
	 * Add status column to ads table if missing.
	 */
	private function updateAdsStatusColumn(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_ads';

		// Check if table exists first.
		// SECURITY FIX: Use prepared statement for table existence check.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$column_exists = $wpdb->get_results(
			$wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'status' )
		);

		if ( empty( $column_exists ) ) {
			$wpdb->query(
				"ALTER TABLE {$table} ADD COLUMN status enum('draft','pending_review','published','rejected','expired') NOT NULL DEFAULT 'draft'"
			);
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX status_idx (status)" );
		}
	}
}
