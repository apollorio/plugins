<?php
/**
 * SafeQuery - Secure database query wrapper
 *
 * Enforces $wpdb->prepare() usage for all queries with variables.
 * Part of Apollo Security Modernization - Week 1.
 *
 * @package Apollo\Core\Database
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Database;

/**
 * Safe database query wrapper that enforces prepared statements.
 */
final class SafeQuery {

	/**
	 * Allowed table names for dynamic table queries.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_TABLES = array(
		// Apollo Core tables
		'apollo_mod_log',
		'apollo_memberships',
		'apollo_migrations',
		'apollo_cache',

		// Apollo Social tables
		'apollo_groups',
		'apollo_group_members',
		'apollo_group_requests',
		'apollo_group_events',
		'apollo_connections',
		'apollo_blocks',
		'apollo_close_friends',
		'apollo_messages',
		'apollo_notifications',
		'apollo_activity',
		'apollo_reactions',
		'apollo_reports',
		'apollo_user_badges',
		'apollo_verification_codes',
		'apollo_online_users',
		'apollo_user_sessions',
		'apollo_documento_assinaturas',
		'apollo_moderation_queue',
		'apollo_verification_pending',

		// Apollo Events tables
		'apollo_event_stats',
		'apollo_event_registrations',
		'apollo_event_bookmarks',
		'apollo_event_reviews',

		// Apollo Rio tables
		'apollo_rio_pwa_subscriptions',
	);

	/**
	 * Get global $wpdb instance.
	 *
	 * @return \wpdb
	 */
	private static function db(): \wpdb {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Check if a table exists safely.
	 *
	 * @param string $table_name Table name without prefix.
	 * @return bool True if table exists.
	 */
	public static function tableExists( string $table_name ): bool {
		$wpdb = self::db();

		// Validate table name against whitelist
		if ( ! self::isAllowedTable( $table_name ) ) {
			self::logSecurityEvent( 'table_whitelist_violation', $table_name );
			return false;
		}

		$full_table = $wpdb->prefix . $table_name;

		// Use prepare() for the LIKE pattern
		$result = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table )
		);

		return $result === $full_table;
	}

	/**
	 * Check if a table name is in the allowed list.
	 *
	 * @param string $table_name Table name to check.
	 * @return bool True if allowed.
	 */
	public static function isAllowedTable( string $table_name ): bool {
		// Remove prefix if present
		$wpdb   = self::db();
		$prefix = $wpdb->prefix;

		if ( str_starts_with( $table_name, $prefix ) ) {
			$table_name = substr( $table_name, strlen( $prefix ) );
		}

		return in_array( $table_name, self::ALLOWED_TABLES, true );
	}

	/**
	 * Get prefixed table name safely.
	 *
	 * @param string $table_name Table name without prefix.
	 * @return string|null Full table name with prefix, or null if not allowed.
	 */
	public static function getTableName( string $table_name ): ?string {
		if ( ! self::isAllowedTable( $table_name ) ) {
			return null;
		}

		return self::db()->prefix . $table_name;
	}

	/**
	 * Execute a SELECT query safely.
	 *
	 * @param string       $table   Table name without prefix.
	 * @param array<mixed> $where   Where conditions as key => value pairs.
	 * @param array<mixed> $args    Additional query args (orderby, limit, offset).
	 * @return array<object>|null Results or null on error.
	 */
	public static function select( string $table, array $where = array(), array $args = array() ): ?array {
		$wpdb = self::db();

		$full_table = self::getTableName( $table );
		if ( null === $full_table ) {
			return null;
		}

		// Build WHERE clause safely
		$where_clauses  = array();
		$where_values   = array();
		$where_formats  = array();

		foreach ( $where as $column => $value ) {
			$column = sanitize_key( $column );

			if ( is_int( $value ) ) {
				$where_clauses[] = "`{$column}` = %d";
				$where_formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$where_clauses[] = "`{$column}` = %f";
				$where_formats[] = '%f';
			} else {
				$where_clauses[] = "`{$column}` = %s";
				$where_formats[] = '%s';
			}
			$where_values[] = $value;
		}

		// Build query
		$sql = "SELECT * FROM `{$full_table}`";

		if ( ! empty( $where_clauses ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Add ORDER BY
		if ( ! empty( $args['orderby'] ) ) {
			$orderby   = sanitize_key( $args['orderby'] );
			$order     = ( $args['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';
			$sql      .= " ORDER BY `{$orderby}` {$order}";
		}

		// Add LIMIT
		if ( ! empty( $args['limit'] ) ) {
			$limit  = absint( $args['limit'] );
			$offset = absint( $args['offset'] ?? 0 );
			$sql   .= " LIMIT {$offset}, {$limit}";
		}

		// Execute with prepare if we have values
		if ( ! empty( $where_values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$prepared = $wpdb->prepare( $sql, $where_values );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $prepared );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql );
	}

	/**
	 * Insert a row safely.
	 *
	 * @param string       $table  Table name without prefix.
	 * @param array<mixed> $data   Data to insert as column => value.
	 * @param array<mixed> $format Optional format array (%s, %d, %f).
	 * @return int|false Inserted ID or false on failure.
	 */
	public static function insert( string $table, array $data, array $format = array() ) {
		$wpdb = self::db();

		$full_table = self::getTableName( $table );
		if ( null === $full_table ) {
			return false;
		}

		// Sanitize column names
		$sanitized_data = array();
		foreach ( $data as $column => $value ) {
			$sanitized_data[ sanitize_key( $column ) ] = $value;
		}

		$result = $wpdb->insert( $full_table, $sanitized_data, $format );

		return false !== $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update rows safely.
	 *
	 * @param string       $table        Table name without prefix.
	 * @param array<mixed> $data         Data to update as column => value.
	 * @param array<mixed> $where        Where conditions as column => value.
	 * @param array<mixed> $format       Optional format for data.
	 * @param array<mixed> $where_format Optional format for where.
	 * @return int|false Number of rows updated or false on failure.
	 */
	public static function update( string $table, array $data, array $where, array $format = array(), array $where_format = array() ) {
		$wpdb = self::db();

		$full_table = self::getTableName( $table );
		if ( null === $full_table ) {
			return false;
		}

		// Sanitize column names
		$sanitized_data  = array();
		$sanitized_where = array();

		foreach ( $data as $column => $value ) {
			$sanitized_data[ sanitize_key( $column ) ] = $value;
		}

		foreach ( $where as $column => $value ) {
			$sanitized_where[ sanitize_key( $column ) ] = $value;
		}

		return $wpdb->update( $full_table, $sanitized_data, $sanitized_where, $format, $where_format );
	}

	/**
	 * Delete rows safely.
	 *
	 * @param string       $table        Table name without prefix.
	 * @param array<mixed> $where        Where conditions as column => value.
	 * @param array<mixed> $where_format Optional format for where.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public static function delete( string $table, array $where, array $where_format = array() ) {
		$wpdb = self::db();

		$full_table = self::getTableName( $table );
		if ( null === $full_table ) {
			return false;
		}

		// Sanitize column names
		$sanitized_where = array();
		foreach ( $where as $column => $value ) {
			$sanitized_where[ sanitize_key( $column ) ] = $value;
		}

		return $wpdb->delete( $full_table, $sanitized_where, $where_format );
	}

	/**
	 * Count rows safely.
	 *
	 * @param string       $table Table name without prefix.
	 * @param array<mixed> $where Optional where conditions.
	 * @return int Row count.
	 */
	public static function count( string $table, array $where = array() ): int {
		$wpdb = self::db();

		$full_table = self::getTableName( $table );
		if ( null === $full_table ) {
			return 0;
		}

		$sql = "SELECT COUNT(*) FROM `{$full_table}`";

		if ( ! empty( $where ) ) {
			$where_clauses = array();
			$where_values  = array();

			foreach ( $where as $column => $value ) {
				$column          = sanitize_key( $column );
				$where_clauses[] = is_int( $value )
					? "`{$column}` = %d"
					: "`{$column}` = %s";
				$where_values[]  = $value;
			}

			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $where_values ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Log security event for monitoring.
	 *
	 * @param string $event_type Type of security event.
	 * @param string $details    Event details.
	 * @return void
	 */
	private static function logSecurityEvent( string $event_type, string $details ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[Apollo Security] %s: %s (User: %d, IP: %s)',
					$event_type,
					$details,
					get_current_user_id(),
					sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) )
				)
			);
		}

		/**
		 * Fires when a security event occurs.
		 *
		 * @param string $event_type Type of security event.
		 * @param string $details    Event details.
		 * @param int    $user_id    Current user ID.
		 */
		do_action( 'apollo_security_event', $event_type, $details, get_current_user_id() );
	}

	/**
	 * Get all allowed table names.
	 *
	 * @return array<string>
	 */
	public static function getAllowedTables(): array {
		/**
		 * Filter the list of allowed table names.
		 *
		 * @param array<string> $tables List of allowed table names.
		 */
		return apply_filters( 'apollo_allowed_database_tables', self::ALLOWED_TABLES );
	}

	/**
	 * Add a table to the allowed list dynamically.
	 *
	 * @param string $table_name Table name to allow.
	 * @return void
	 */
	public static function allowTable( string $table_name ): void {
		add_filter(
			'apollo_allowed_database_tables',
			function ( array $tables ) use ( $table_name ): array {
				$tables[] = $table_name;
				return array_unique( $tables );
			}
		);
	}
}
