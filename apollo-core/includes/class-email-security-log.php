<?php

declare(strict_types=1);

namespace Apollo\Security;

/**
 * Email Security Logger
 *
 * Comprehensive security logging for all email operations.
 * Tracks sends, failures, suspicious activities, and provides audit trail.
 *
 * @package Apollo_Core
 * @since 1.3.0
 */
class EmailSecurityLog {

	private const TABLE_NAME        = 'apollo_email_security_log';
	private const MAX_LOGS          = 10000;
	private const RATE_LIMIT_WINDOW = 3600;
	// 1 hour
	private const RATE_LIMIT_MAX = 50;
	// Max emails per user per hour

	/**
	 * Log types
	 */
	public const TYPE_SENT             = 'sent';
	public const TYPE_FAILED           = 'failed';
	public const TYPE_BLOCKED          = 'blocked';
	public const TYPE_SUSPICIOUS       = 'suspicious';
	public const TYPE_RATE_LIMITED     = 'rate_limited';
	public const TYPE_TEMPLATE_UPDATED = 'template_updated';
	public const TYPE_TEST_SENT        = 'test_sent';

	/**
	 * Severity levels
	 */
	public const SEVERITY_INFO     = 'info';
	public const SEVERITY_WARNING  = 'warning';
	public const SEVERITY_ERROR    = 'error';
	public const SEVERITY_CRITICAL = 'critical';

	/**
	 * Initialize the logger
	 */
	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'maybeCreateTable' ] );
		add_action( 'apollo_email_sent', [ self::class, 'logEmailSent' ], 10, 4 );
		add_action( 'apollo_email_failed', [ self::class, 'logEmailFailed' ], 10, 4 );
		add_action( 'wp_mail_failed', [ self::class, 'logWpMailFailed' ] );

		// Scheduled cleanup
		if ( ! wp_next_scheduled( 'apollo_email_log_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'apollo_email_log_cleanup' );
		}
		add_action( 'apollo_email_log_cleanup', [ self::class, 'cleanup' ] );
	}

	/**
	 * Create database table if needed
	 */
	public static function maybeCreateTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			$sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                type VARCHAR(20) NOT NULL DEFAULT 'sent',
                severity VARCHAR(20) NOT NULL DEFAULT 'info',
                user_id BIGINT(20) UNSIGNED DEFAULT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                template_key VARCHAR(100) DEFAULT NULL,
                subject VARCHAR(500) DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                error_message TEXT DEFAULT NULL,
                metadata LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_type (type),
                KEY idx_severity (severity),
                KEY idx_user_id (user_id),
                KEY idx_recipient (recipient_email(100)),
                KEY idx_template (template_key),
                KEY idx_created_at (created_at),
                KEY idx_ip_address (ip_address)
            ) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}//end if
	}

	/**
	 * Log an email event
	 *
	 * @param string $type        Log type (use class constants)
	 * @param string $recipient   Recipient email
	 * @param array  $data        Additional data
	 * @param string $severity    Severity level
	 *
	 * @return int|false Insert ID or false on failure
	 */
	public static function log(
		string $type,
		string $recipient,
		array $data = [],
		string $severity = self::SEVERITY_INFO
	) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Sanitize and validate
		$recipient = sanitize_email( $recipient );
		if ( ! is_email( $recipient ) ) {
			$recipient = 'invalid@email.blocked';
			$severity  = self::SEVERITY_WARNING;
		}

		// Get client info
		$ip         = self::getClientIp();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'], 0, 500 ) )
			: '';

		// Prepare insert data
		$insert_data = [
			'type'            => sanitize_key( $type ),
			'severity'        => sanitize_key( $severity ),
			'user_id'         => get_current_user_id() ?: null,
			'recipient_email' => $recipient,
			'template_key'    => isset( $data['template'] ) ? sanitize_key( $data['template'] ) : null,
			'subject'         => isset( $data['subject'] ) ? sanitize_text_field( substr( $data['subject'], 0, 500 ) ) : null,
			'ip_address'      => $ip,
			'user_agent'      => $user_agent,
			'error_message'   => isset( $data['error'] ) ? sanitize_text_field( $data['error'] ) : null,
			'metadata'        => isset( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : null,
			'created_at'      => current_time( 'mysql' ),
		];

		$result = $wpdb->insert(
			$table_name,
			$insert_data,
			[
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( $result === false ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Security audit logging for DB failures.
			error_log( '[Apollo Email Security] Failed to log: ' . $wpdb->last_error );

			return false;
		}

		// Check for suspicious patterns
		self::detectSuspiciousActivity( $type, $recipient, $ip );

		return $wpdb->insert_id;
	}

	/**
	 * Log successful email send
	 */
	public static function logEmailSent( string $to, string $subject, string $template = '', array $metadata = [] ): void {
		self::log(
			self::TYPE_SENT,
			$to,
			[
				'subject'  => $subject,
				'template' => $template,
				'metadata' => $metadata,
			],
			self::SEVERITY_INFO
		);
	}

	/**
	 * Log failed email
	 */
	public static function logEmailFailed( string $to, string $subject, string $error, string $template = '' ): void {
		self::log(
			self::TYPE_FAILED,
			$to,
			[
				'subject'  => $subject,
				'template' => $template,
				'error'    => $error,
			],
			self::SEVERITY_ERROR
		);
	}

	/**
	 * Log WordPress mail failure
	 */
	public static function logWpMailFailed( \WP_Error $error ): void {
		$mail_data = $error->get_error_data();
		$to        = is_array( $mail_data ) && isset( $mail_data['to'] ) ? $mail_data['to'] : 'unknown';

		if ( is_array( $to ) ) {
			$to = implode( ', ', $to );
		}

		self::log(
			self::TYPE_FAILED,
			$to,
			[
				'error'    => $error->get_error_message(),
				'metadata' => [ 'wp_error_code' => $error->get_error_code() ],
			],
			self::SEVERITY_ERROR
		);
	}

	/**
	 * Check if user is rate limited
	 *
	 * @param int    $user_id User ID (0 for guests)
	 * @param string $ip      IP address
	 *
	 * @return bool True if rate limited
	 */
	public static function isRateLimited( int $user_id = 0, string $ip = '' ): bool {
		global $wpdb;

		$table_name   = $wpdb->prefix . self::TABLE_NAME;
		$ip           = $ip ?: self::getClientIp();
		$window_start = gmdate( 'Y-m-d H:i:s', time() - self::RATE_LIMIT_WINDOW );

		// Check by user ID if logged in
		if ( $user_id > 0 ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name}
                 WHERE user_id = %d AND type = %s AND created_at > %s",
					$user_id,
					self::TYPE_SENT,
					$window_start
				)
			);
		} else {
			// Check by IP for guests
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name}
                 WHERE ip_address = %s AND type = %s AND created_at > %s",
					$ip,
					self::TYPE_SENT,
					$window_start
				)
			);
		}//end if

		if ( (int) $count >= self::RATE_LIMIT_MAX ) {
			// Log the rate limit event
			self::log(
				self::TYPE_RATE_LIMITED,
				'rate-limit@blocked',
				[
					'metadata' => [
						'user_id' => $user_id,
						'ip'      => $ip,
						'count'   => $count,
					],
				],
				self::SEVERITY_WARNING
			);

			return true;
		}

		return false;
	}

	/**
	 * Detect suspicious email activity
	 */
	private static function detectSuspiciousActivity( string $type, string $recipient, string $ip ): void {
		global $wpdb;

		$table_name   = $wpdb->prefix . self::TABLE_NAME;
		$window_start = gmdate( 'Y-m-d H:i:s', time() - 300 );
		// Last 5 minutes

		// Check for rapid sending from same IP
		$rapid_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name}
             WHERE ip_address = %s AND created_at > %s",
				$ip,
				$window_start
			)
		);

		if ( (int) $rapid_count > 10 ) {
			self::log(
				self::TYPE_SUSPICIOUS,
				$recipient,
				[
					'error'    => 'Rapid email activity detected',
					'metadata' => [
						'ip'         => $ip,
						'count_5min' => $rapid_count,
					],
				],
				self::SEVERITY_WARNING
			);

			// Notify admins if critical
			if ( (int) $rapid_count > 25 ) {
				self::notifyAdmins(
					'Atividade suspeita de email detectada',
					[
						'IP'                  => $ip,
						'Emails em 5 min'     => $rapid_count,
						'Último destinatário' => $recipient,
					]
				);
			}
		}//end if

		// Check for multiple failed attempts
		$failed_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name}
             WHERE ip_address = %s AND type = %s AND created_at > %s",
				$ip,
				self::TYPE_FAILED,
				$window_start
			)
		);

		if ( (int) $failed_count > 5 ) {
			self::log(
				self::TYPE_SUSPICIOUS,
				$recipient,
				[
					'error'    => 'Multiple email failures from same IP',
					'metadata' => [
						'ip'           => $ip,
						'failed_count' => $failed_count,
					],
				],
				self::SEVERITY_WARNING
			);
		}
	}

	/**
	 * Notify administrators of security events
	 */
	private static function notifyAdmins( string $subject, array $details ): void {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		$body  = "⚠️ Alerta de Segurança - Apollo Email\n\n";
		$body .= "Site: {$site_name}\n";
		$body .= 'Data: ' . current_time( 'd/m/Y H:i:s' ) . "\n\n";
		$body .= "Detalhes:\n";

		foreach ( $details as $key => $value ) {
			$body .= "- {$key}: {$value}\n";
		}

		$body .= "\n--\nApollo Security System";

		wp_mail(
			$admin_email,
			"[{$site_name}] {$subject}",
			$body,
			[ 'Content-Type: text/plain; charset=UTF-8' ]
		);
	}

	/**
	 * Get logs with filtering
	 *
	 * @param array $args Query arguments
	 *
	 * @return array
	 */
	public static function getLogs( array $args = [] ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$defaults = [
			'type'      => '',
			'severity'  => '',
			'user_id'   => 0,
			'recipient' => '',
			'template'  => '',
			'ip'        => '',
			'date_from' => '',
			'date_to'   => '',
			'per_page'  => 50,
			'page'      => 1,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		];

		$args   = wp_parse_args( $args, $defaults );
		$where  = [ '1=1' ];
		$values = [];

		// Build WHERE clause
		if ( $args['type'] ) {
			$where[]  = 'type = %s';
			$values[] = $args['type'];
		}

		if ( $args['severity'] ) {
			$where[]  = 'severity = %s';
			$values[] = $args['severity'];
		}

		if ( $args['user_id'] ) {
			$where[]  = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( $args['recipient'] ) {
			$where[]  = 'recipient_email LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['recipient'] ) . '%';
		}

		if ( $args['template'] ) {
			$where[]  = 'template_key = %s';
			$values[] = $args['template'];
		}

		if ( $args['ip'] ) {
			$where[]  = 'ip_address = %s';
			$values[] = $args['ip'];
		}

		if ( $args['date_from'] ) {
			$where[]  = 'created_at >= %s';
			$values[] = $args['date_from'];
		}

		if ( $args['date_to'] ) {
			$where[]  = 'created_at <= %s';
			$values[] = $args['date_to'];
		}

		$where_sql = implode( ' AND ', $where );

		// Sanitize orderby
		$allowed_orderby = [ 'id', 'type', 'severity', 'recipient_email', 'template_key', 'created_at' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Calculate offset
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Get total count
		$count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values );
		}
		$total = (int) $wpdb->get_var( $count_sql );

		// Get results
		$sql      = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$values[] = $args['per_page'];
		$values[] = $offset;

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );

		return [
			'items' => $results ?: [],
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
			'page'  => $args['page'],
		];
	}

	/**
	 * Get statistics
	 *
	 * @param string $period Period: 'today', 'week', 'month', 'all'
	 *
	 * @return array
	 */
	public static function getStats( string $period = 'today' ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Calculate date range
		switch ( $period ) {
			case 'today':
				$date_start = gmdate( 'Y-m-d 00:00:00' );

				break;
			case 'week':
				$date_start = gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );

				break;
			case 'month':
				$date_start = gmdate( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );

				break;
			default:
				$date_start = '1970-01-01 00:00:00';
		}

		$stats = [
			'total_sent'       => 0,
			'total_failed'     => 0,
			'total_blocked'    => 0,
			'total_suspicious' => 0,
			'by_template'      => [],
			'by_severity'      => [],
			'top_recipients'   => [],
		];

		// Total by type
		$type_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT type, COUNT(*) as count FROM {$table_name}
             WHERE created_at >= %s GROUP BY type",
				$date_start
			),
			ARRAY_A
		);

		foreach ( $type_counts as $row ) {
			$key = 'total_' . $row['type'];
			if ( isset( $stats[ $key ] ) ) {
				$stats[ $key ] = (int) $row['count'];
			}
		}

		// By severity
		$severity_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT severity, COUNT(*) as count FROM {$table_name}
             WHERE created_at >= %s GROUP BY severity",
				$date_start
			),
			ARRAY_A
		);

		foreach ( $severity_counts as $row ) {
			$stats['by_severity'][ $row['severity'] ] = (int) $row['count'];
		}

		// By template
		$template_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT template_key, COUNT(*) as count FROM {$table_name}
             WHERE created_at >= %s AND template_key IS NOT NULL
             GROUP BY template_key ORDER BY count DESC LIMIT 10",
				$date_start
			),
			ARRAY_A
		);

		foreach ( $template_counts as $row ) {
			$stats['by_template'][ $row['template_key'] ] = (int) $row['count'];
		}

		// Top recipients
		$stats['top_recipients'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT recipient_email, COUNT(*) as count FROM {$table_name}
             WHERE created_at >= %s
             GROUP BY recipient_email ORDER BY count DESC LIMIT 10",
				$date_start
			),
			ARRAY_A
		);

		return $stats;
	}

	/**
	 * Cleanup old logs
	 */
	public static function cleanup(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Keep only last MAX_LOGS entries
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		if ( (int) $count > self::MAX_LOGS ) {
			$delete_count = $count - self::MAX_LOGS;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_name} ORDER BY created_at ASC LIMIT %d",
					$delete_count
				)
			);
		}

		// Also delete logs older than 90 days
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s",
				gmdate( 'Y-m-d H:i:s', strtotime( '-90 days' ) )
			)
		);
	}

	/**
	 * Get client IP address safely
	 */
	private static function getClientIp(): string {
		// Check for Cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		// Check for proxy
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip  = trim( $ips[0] );
		}
		// Direct connection
		elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '0.0.0.0';
		}

		// Validate and sanitize
		$ip = sanitize_text_field( $ip );

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '0.0.0.0';
		}

		return $ip;
	}

	/**
	 * Export logs to CSV
	 *
	 * @param array $args Filter arguments
	 *
	 * @return string CSV content
	 */
	public static function exportCsv( array $args = [] ): string {
		$args['per_page'] = 10000;
		// Max export
		$logs = self::getLogs( $args );

		$csv = "ID,Tipo,Severidade,User ID,Email,Template,Assunto,IP,Data\n";

		foreach ( $logs['items'] as $log ) {
			$csv .= sprintf(
				"%d,%s,%s,%s,%s,%s,\"%s\",%s,%s\n",
				$log['id'],
				$log['type'],
				$log['severity'],
				$log['user_id'] ?? '',
				$log['recipient_email'],
				$log['template_key'] ?? '',
				str_replace( '"', '""', $log['subject'] ?? '' ),
				$log['ip_address'] ?? '',
				$log['created_at']
			);
		}

		return $csv;
	}
}

// Initialize
add_action( 'plugins_loaded', [ EmailSecurityLog::class, 'init' ], 5 );
