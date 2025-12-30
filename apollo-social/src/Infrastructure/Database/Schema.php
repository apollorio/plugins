<?php

namespace Apollo\Infrastructure\Database;

/**
 * Apollo Database Schema
 *
 * Creates and manages database tables for Apollo Social plugin.
 *
 * @deprecated 2.2.0 Use \Apollo\Schema instead as the single entry point.
 *                   This class is kept for backward compatibility only.
 * @see        \Apollo\Schema
 */
class Schema {

	private string $version = '1.0.0';

	/**
	 * P0-3: Install all Apollo tables with migration safety
	 *
	 * @deprecated 2.2.0 Use \Apollo\Schema::install() instead.
	 */
	 */
	public function install(): void {
		// Check if already installed at current version
		$current_version = $this->getSchemaVersion();
		if ( version_compare( $current_version, $this->version, '>=' ) ) {
			// Already at or above target version, skip
			return;
		}

		$this->createGroupsTable();
		$this->createGroupMembersTable();
		$this->createWorkflowLogTable();
		$this->createModerationQueueTable();
		$this->createAnalyticsTable();
		$this->createSignatureRequestsTable();
		$this->createOnboardingProgressTable();
		$this->createLikesTable();
		// FASE 2: Tabela de curtidas
		$this->createDocumentsTable();
		// P0-9: Tabela de documentos

		// P0-3: Update schema version after successful installation
		$this->updateSchemaVersion();
	}

	/**
	 * Create Apollo groups table
	 */
	private function createGroupsTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'apollo_groups';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            type enum('comunidade', 'nucleo', 'season') NOT NULL DEFAULT 'comunidade',
            status enum('draft', 'pending_review', 'published', 'rejected', 'suspended') NOT NULL DEFAULT 'draft',
            visibility enum('public', 'private', 'members_only') NOT NULL DEFAULT 'public',
            season_slug varchar(100) NULL,
            creator_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NULL ON UPDATE CURRENT_TIMESTAMP,
            published_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug_idx (slug),
            KEY type_idx (type),
            KEY status_idx (status),
            KEY visibility_idx (visibility),
            KEY creator_idx (creator_id),
            KEY season_idx (season_slug),
            KEY created_idx (created_at),
            KEY published_idx (published_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create Apollo group members table
	 */
	private function createGroupMembersTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'apollo_group_members';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            group_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            role enum('member', 'moderator', 'admin') NOT NULL DEFAULT 'member',
            status enum('active', 'pending', 'banned', 'left') NOT NULL DEFAULT 'active',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            left_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY group_user_idx (group_id, user_id),
            KEY group_idx (group_id),
            KEY user_idx (user_id),
            KEY status_idx (status),
            KEY role_idx (role)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create workflow log table
	 */
	private function createWorkflowLogTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_workflow_log';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            content_id bigint(20) unsigned NOT NULL,
            content_type varchar(50) NOT NULL,
            from_state varchar(50) NOT NULL,
            to_state varchar(50) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            reason text,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY content_idx (content_id, content_type),
            KEY user_idx (user_id),
            KEY state_idx (from_state, to_state),
            KEY created_idx (created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create modtable
	 */
	private function createModerationQueueTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_mod_queue';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            content_id bigint(20) unsigned NOT NULL,
            content_type varchar(50) NOT NULL,
            content_title varchar(255) NOT NULL,
            author_id bigint(20) unsigned NOT NULL,
            status enum('pending', 'approved', 'rejected', 'escalated') NOT NULL DEFAULT 'pending',
            priority tinyint(1) unsigned NOT NULL DEFAULT 1,
            assigned_moderator_id bigint(20) unsigned NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            reviewed_at datetime NULL,
            reviewer_id bigint(20) unsigned NULL,
            review_notes text,
            metadata longtext,
            PRIMARY KEY (id),
            KEY content_idx (content_id, content_type),
            KEY author_idx (author_id),
            KEY status_idx (status),
            KEY priority_idx (priority),
            KEY moderator_idx (assigned_moderator_id),
            KEY submitted_idx (submitted_at),
            KEY reviewed_idx (reviewed_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create analytics table
	 */
	private function createAnalyticsTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_analytics';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
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
            PRIMARY KEY (id),
            KEY event_idx (event_type, event_name),
            KEY user_idx (user_id),
            KEY session_idx (session_id),
            KEY created_idx (created_at),
            KEY url_idx (url(191))
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create signature requests table
	 */
	private function createSignatureRequestsTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_signature_requests';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_token varchar(100) NOT NULL UNIQUE,
            requester_id bigint(20) unsigned NOT NULL,
            signer_name varchar(255) NOT NULL,
            signer_phone varchar(20) NOT NULL,
            signer_instagram varchar(100),
            document_title varchar(255) NOT NULL,
            document_hash varchar(64) NOT NULL,
            signature_type enum('canvas', 'typed') NOT NULL,
            signature_data longtext,
            evidence_pack longtext,
            status enum('pending', 'signed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            signed_at datetime NULL,
            expires_at datetime NOT NULL,
            metadata longtext,
            PRIMARY KEY (id),
            UNIQUE KEY token_idx (request_token),
            KEY requester_idx (requester_id),
            KEY status_idx (status),
            KEY created_idx (created_at),
            KEY expires_idx (expires_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create onboarding progress table
	 */
	private function createOnboardingProgressTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_onboarding_progress';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            step_number tinyint(2) unsigned NOT NULL,
            step_name varchar(100) NOT NULL,
            status enum('pending', 'completed', 'skipped') NOT NULL DEFAULT 'pending',
            step_data longtext,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_step_idx (user_id, step_number),
            KEY user_idx (user_id),
            KEY status_idx (status),
            KEY completed_idx (completed_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * FASE 2: Create likes table
	 */
	private function createLikesTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'apollo_likes';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            content_type varchar(50) NOT NULL,
            content_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            liked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY content_user_idx (content_type, content_id, user_id),
            KEY content_idx (content_type, content_id),
            KEY user_idx (user_id),
            KEY liked_at_idx (liked_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Update Apollo groups table to include status column
	 */
	public function updateGroupsTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_groups';

		// Validate table name matches expected pattern
		if ( ! preg_match( '/^' . preg_quote( $wpdb->prefix, '/' ) . 'apollo_\w+$/', $table_name ) ) {
			error_log( 'Apollo Schema: Invalid table name detected: ' . $table_name );
			return;
		}

		// Check if status column exists
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table_name} LIKE %s",
				'status'
			)
		);

		if ( empty( $column_exists ) ) {
			// Use esc_sql for table name (safe after validation)
			$safe_table = esc_sql( $table_name );
			$result1    = $wpdb->query( "ALTER TABLE {$safe_table} ADD COLUMN status enum('draft', 'pending_review', 'published', 'rejected', 'suspended') NOT NULL DEFAULT 'draft' AFTER visibility" );
			if ( false === $result1 ) {
				error_log( 'Apollo Schema: Failed to add status column - ' . $wpdb->last_error );
				return;
			}
			$result2 = $wpdb->query( "ALTER TABLE {$safe_table} ADD INDEX status_idx (status)" );
			if ( false === $result2 ) {
				error_log( 'Apollo Schema: Failed to add status index - ' . $wpdb->last_error );
			}
		}

		// Update existing records to published if they don't have a status
		$safe_table = esc_sql( $table_name );
		$wpdb->query( "UPDATE {$safe_table} SET status = 'published' WHERE status = '' OR status IS NULL" );
	}

	/**
	 * Update Apollo ads table to include status column
	 */
	public function updateAdsTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_ads';

		// Validate table name matches expected pattern
		if ( ! preg_match( '/^' . preg_quote( $wpdb->prefix, '/' ) . 'apollo_\w+$/', $table_name ) ) {
			error_log( 'Apollo Schema: Invalid table name detected: ' . $table_name );
			return;
		}

		// Check if status column exists
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table_name} LIKE %s",
				'status'
			)
		);

		if ( empty( $column_exists ) ) {
			// Use esc_sql for table name (safe after validation)
			$safe_table = esc_sql( $table_name );
			$result1    = $wpdb->query( "ALTER TABLE {$safe_table} ADD COLUMN status enum('draft', 'pending_review', 'published', 'rejected', 'expired') NOT NULL DEFAULT 'draft' AFTER price" );
			if ( false === $result1 ) {
				error_log( 'Apollo Schema: Failed to add status column - ' . $wpdb->last_error );
				return;
			}
			$result2 = $wpdb->query( "ALTER TABLE {$safe_table} ADD INDEX status_idx (status)" );
			if ( false === $result2 ) {
				error_log( 'Apollo Schema: Failed to add status index - ' . $wpdb->last_error );
			}
		}

		// Update existing records to published if they don't have a status
		$safe_table = esc_sql( $table_name );
		$wpdb->query( "UPDATE {$safe_table} SET status = 'published' WHERE status = '' OR status IS NULL" );
	}

	/**
	 * Create user verification tokens table
	 */
	public function createVerificationTokensTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_verification_tokens';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            platform varchar(50) NOT NULL,
            username varchar(100) NOT NULL,
            token varchar(100) NOT NULL,
            status enum('pending', 'verified', 'failed', 'expired') NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            verified_at datetime NULL,
            expires_at datetime NOT NULL,
            metadata longtext,
            PRIMARY KEY (id),
            UNIQUE KEY user_platform_idx (user_id, platform),
            KEY platform_idx (platform),
            KEY username_idx (username),
            KEY token_idx (token),
            KEY status_idx (status),
            KEY expires_idx (expires_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * P0-9: Create documents table
	 */
	private function createDocumentsTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_documents';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            file_id varchar(100) NOT NULL UNIQUE,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            type enum('document', 'spreadsheet') NOT NULL DEFAULT 'document',
            content longtext,
            status enum('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NULL ON UPDATE CURRENT_TIMESTAMP,
            metadata longtext,
            PRIMARY KEY (id),
            UNIQUE KEY file_id_idx (file_id),
            KEY user_idx (user_id),
            KEY type_idx (type),
            KEY status_idx (status),
            KEY created_idx (created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get current schema version
	 */
	public function getSchemaVersion(): string {
		return get_option( 'apollo_schema_version', '0.0.0' );
	}

	/**
	 * Update schema version
	 */
	private function updateSchemaVersion(): void {
		update_option( 'apollo_schema_version', $this->version );
	}

	/**
	 * Check if schema needs update
	 */
	public function needsUpdate(): bool {
		return version_compare( $this->getSchemaVersion(), $this->version, '<' );
	}

	/**
	 * Run schema migrations
	 */
	public function migrate(): void {
		$current_version = $this->getSchemaVersion();

		// Migration from 0.0.0 to 1.0.0
		if ( version_compare( $current_version, '1.0.0', '<' ) ) {
			$this->install();
			$this->updateGroupsTable();
			$this->updateAdsTable();
			$this->createVerificationTokensTable();
		}

		$this->updateSchemaVersion();
	}

	/**
	 * Drop all Apollo tables (for uninstall)
	 */
	public function uninstall(): void {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'apollo_workflow_log',
			$wpdb->prefix . 'apollo_mod_queue',
			$wpdb->prefix . 'apollo_groups',
			$wpdb->prefix . 'apollo_group_members',
			$wpdb->prefix . 'apollo_analytics',
			$wpdb->prefix . 'apollo_signature_requests',
			$wpdb->prefix . 'apollo_onboarding_progress',
			$wpdb->prefix . 'apollo_verification_tokens',
			$wpdb->prefix . 'apollo_likes',
			$wpdb->prefix . 'apollo_documents',
			$wpdb->prefix . 'apollo_ads',
		];

		foreach ( $tables as $table ) {
			// Validate table name matches expected pattern
			if ( ! preg_match( '/^' . preg_quote( $wpdb->prefix, '/' ) . 'apollo_\w+$/', $table ) ) {
				error_log( 'Apollo Schema: Invalid table name in uninstall: ' . $table );
				continue;
			}
			// Use esc_sql for table name (safe after validation)
			$safe_table = esc_sql( $table );
			$result     = $wpdb->query( "DROP TABLE IF EXISTS {$safe_table}" );
			if ( false === $result ) {
				error_log( 'Apollo Schema: Failed to drop table ' . $table . ' - ' . $wpdb->last_error );
			}
		}

		// Remove schema version option
		delete_option( 'apollo_schema_version' );
	}

	/**
	 * Get table creation status
	 */
	public function getInstallationStatus(): array {
		global $wpdb;

		$tables = [
			'workflow_log'        => $wpdb->prefix . 'apollo_workflow_log',
			'mod    => $wpdb->prefix . 'apollo_mod_quemod
			'analytics'           => $wpdb->prefix . 'apollo_analytics',
			'signature_requests'  => $wpdb->prefix . 'apollo_signature_requests',
			'onboarding_progress' => $wpdb->prefix . 'apollo_onboarding_progress',
			'verification_tokens' => $wpdb->prefix . 'apollo_verification_tokens',
		];

		$status = [];
		foreach ( $tables as $name => $table ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table
				)
			) === $table;

			$status[ $name ] = $exists;
		}

		$status['schema_version'] = $this->getSchemaVersion();
		$status['needs_update']   = $this->needsUpdate();

		return $status;
	}

	/**
	 * Get database statistics
	 */
	public function getStatistics(): array {
		global $wpdb;

		$stats = [];

		// Workflow log statistics
		$stats['workflow_transitions'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_workflow_log"
		);

		// Moderation queue statistics
		$stats['pending_modpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_modWHERE status = 'pending'"
		);

		// Analytics statistics
		$stats['total_events'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_analytics"
		);

		$stats['events_today'] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_analytics WHERE DATE(created_at) = %s",
				current_time( 'Y-m-d' )
			)
		);

		// Signature requests statistics
		$stats['signature_requests'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_signature_requests"
		);

		$stats['pending_signatures'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_signature_requests WHERE status = 'pending'"
		);

		// Onboarding statistics
		$stats['users_in_onboarding'] = $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}apollo_onboarding_progress WHERE status = 'pending'"
		);

		$stats['completed_onboarding'] = $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}apollo_onboarding_progress WHERE step_number = 7 AND status = 'completed'"
		);

		return $stats;
	}
}
