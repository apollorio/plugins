<?php
/**
 * Apollo Structured Logger
 *
 * Sistema de logging estruturado para observabilidade.
 * Registra eventos importantes: documentos, assinaturas, sync, rewrites.
 *
 * @package Apollo\Infrastructure
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Infrastructure;

/**
 * Structured Logger
 *
 * Logs to:
 * - Database table (wp_apollo_logs) for querying
 * - PHP error_log (if WP_DEBUG enabled)
 * - Optional external service via filter
 */
class ApolloLogger {

	/** @var string Table name */
	private const TABLE_NAME = 'apollo_logs';

	/** @var array Log levels */
	public const LEVEL_DEBUG    = 'debug';
	public const LEVEL_INFO     = 'info';
	public const LEVEL_WARNING  = 'warning';
	public const LEVEL_ERROR    = 'error';
	public const LEVEL_CRITICAL = 'critical';

	/** @var array Event categories */
	public const CAT_DOCUMENT   = 'document';
	public const CAT_SIGNATURE  = 'signature';
	public const CAT_SYNC       = 'sync';
	public const CAT_REWRITE    = 'rewrite';
	public const CAT_AUTH       = 'auth';
	public const CAT_FEATURE    = 'feature';
	public const CAT_API        = 'api';

	/**
	 * Create logs table
	 */
	public static function createTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			level VARCHAR(20) NOT NULL DEFAULT 'info',
			category VARCHAR(50) NOT NULL DEFAULT 'general',
			event VARCHAR(100) NOT NULL,
			message TEXT,
			context JSON,
			user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			url VARCHAR(500) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_level (level),
			KEY idx_category (category),
			KEY idx_event (event),
			KEY idx_user_id (user_id),
			KEY idx_created_at (created_at),
			KEY idx_level_category (level, category)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log an event
	 *
	 * @param string $level    Log level.
	 * @param string $event    Event name (e.g., 'document_created', 'signature_failed').
	 * @param array  $context  Additional context data.
	 * @param string $category Event category.
	 * @return int|false Log ID or false on failure.
	 */
	public static function log( string $level, string $event, array $context = array(), string $category = 'general' ) {
		global $wpdb;

		// Build message from context if not provided
		$message = $context['message'] ?? $event;
		unset( $context['message'] );

		// Add standard context
		$context['timestamp'] = current_time( 'mysql', true ); // UTC

		// Get user info
		$user_id = get_current_user_id() ?: ( $context['user_id'] ?? null );

		// Get request info
		$ip_address = self::getClientIp();
		$url        = self::getCurrentUrl();

		$data = array(
			'level'      => $level,
			'category'   => $category,
			'event'      => $event,
			'message'    => $message,
			'context'    => wp_json_encode( $context ),
			'user_id'    => $user_id,
			'ip_address' => $ip_address,
			'url'        => $url,
		);

		// Insert to database
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$result     = $wpdb->insert( $table_name, $data );

		// Also log to PHP error_log if debug enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_message = sprintf(
				'[Apollo %s] [%s] %s: %s %s',
				\strtoupper( $level ),
				$category,
				$event,
				$message,
				! empty( $context ) ? wp_json_encode( $context ) : ''
			);
			error_log( $log_message );
		}

		// Allow external logging via filter
		do_action( 'apollo_log', $level, $event, $context, $category );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Convenience methods for each log level
	 */
	public static function debug( string $event, array $context = array(), string $category = 'general' ) {
		return self::log( self::LEVEL_DEBUG, $event, $context, $category );
	}

	public static function info( string $event, array $context = array(), string $category = 'general' ) {
		return self::log( self::LEVEL_INFO, $event, $context, $category );
	}

	public static function warning( string $event, array $context = array(), string $category = 'general' ) {
		return self::log( self::LEVEL_WARNING, $event, $context, $category );
	}

	public static function error( string $event, array $context = array(), string $category = 'general' ) {
		return self::log( self::LEVEL_ERROR, $event, $context, $category );
	}

	public static function critical( string $event, array $context = array(), string $category = 'general' ) {
		return self::log( self::LEVEL_CRITICAL, $event, $context, $category );
	}

	/**
	 * Log document event
	 */
	public static function logDocument( string $event, int $document_id, array $context = array() ) {
		$context['document_id'] = $document_id;
		return self::info( $event, $context, self::CAT_DOCUMENT );
	}

	/**
	 * Log signature event
	 */
	public static function logSignature( string $event, int $document_id, array $context = array() ) {
		$context['document_id'] = $document_id;
		return self::info( $event, $context, self::CAT_SIGNATURE );
	}

	/**
	 * Log sync divergence (critical for document consistency)
	 */
	public static function logSyncDivergence( string $type, array $context = array() ) {
		return self::warning( 'sync_divergence_' . $type, $context, self::CAT_SYNC );
	}

	/**
	 * Log rewrite/redirect event
	 */
	public static function logRewrite( string $event, array $context = array() ) {
		return self::info( $event, $context, self::CAT_REWRITE );
	}

	/**
	 * Log blocked endpoint access
	 */
	public static function logBlockedAccess( string $endpoint, string $reason, array $context = array() ) {
		$context['endpoint'] = $endpoint;
		$context['reason']   = $reason;
		return self::warning( 'endpoint_blocked', $context, self::CAT_API );
	}

	/**
	 * Query logs
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'level'       => '',
			'category'    => '',
			'event'       => '',
			'user_id'     => 0,
			'date_after'  => '',
			'date_before' => '',
			'limit'       => 100,
			'offset'      => 0,
			'orderby'     => 'created_at',
			'order'       => 'DESC',
		);

		$args       = wp_parse_args( $args, $defaults );
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$where  = array( '1=1' );
		$values = array();

		if ( $args['level'] ) {
			$where[]  = 'level = %s';
			$values[] = $args['level'];
		}

		if ( $args['category'] ) {
			$where[]  = 'category = %s';
			$values[] = $args['category'];
		}

		if ( $args['event'] ) {
			$where[]  = 'event LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['event'] ) . '%';
		}

		if ( $args['user_id'] ) {
			$where[]  = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( $args['date_after'] ) {
			$where[]  = 'created_at >= %s';
			$values[] = $args['date_after'];
		}

		if ( $args['date_before'] ) {
			$where[]  = 'created_at <= %s';
			$values[] = $args['date_before'];
		}

		$orderby = in_array( $args['orderby'], array( 'id', 'level', 'category', 'event', 'created_at' ), true )
			? $args['orderby']
			: 'created_at';
		$order   = \strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT * FROM {$table_name} WHERE " . \implode( ' AND ', $where );
		$sql .= " ORDER BY {$orderby} {$order}";
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Decode JSON context
		foreach ( $results as &$row ) {
			$row['context'] = json_decode( $row['context'], true );
		}

		return $results;
	}

	/**
	 * Get recent logs for admin dashboard
	 *
	 * @param int $limit Number of logs.
	 * @return array
	 */
	public static function getRecent( int $limit = 50 ): array {
		return self::query( array( 'limit' => $limit ) );
	}

	/**
	 * Get logs by category
	 *
	 * @param string $category Category name.
	 * @param int    $limit    Number of logs.
	 * @return array
	 */
	public static function getByCategory( string $category, int $limit = 50 ): array {
		return self::query( array( 'category' => $category, 'limit' => $limit ) );
	}

	/**
	 * Cleanup old logs
	 *
	 * @param int $days_old Delete logs older than this.
	 * @return int Number of deleted rows.
	 */
	public static function cleanup( int $days_old = 30 ): int {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s",
				$cutoff
			)
		);
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private static function getClientIp(): string {
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Handle comma-separated IPs (X-Forwarded-For)
				if ( \strpos( $ip, ',' ) !== false ) {
					$ip = \trim( \explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Get current URL
	 *
	 * @return string
	 */
	private static function getCurrentUrl(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		$protocol = is_ssl() ? 'https' : 'http';
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri      = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		return $protocol . '://' . $host . $uri;
	}
}
