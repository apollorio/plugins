<?php
/**
 * Email Security Log
 *
 * Handles email rate limiting and logging for security purposes.
 *
 * @package Apollo\Security
 * @since 2.4.0
 */

namespace Apollo\Security;

/**
 * Class EmailSecurityLog
 *
 * Provides email rate limiting and logging functionality.
 */
class EmailSecurityLog {
	/**
	 * Check if core security logger is available.
	 *
	 * @return bool
	 */
	private static function coreLoggerAvailable(): bool {
		return class_exists( '\\Apollo_Core\\Security\\Email_Security_Log' );
	}

	/**
	 * Option name for storing email logs.
	 *
	 * @var string
	 */
	const OPTION_LOGS = 'apollo_email_security_logs';

	/**
	 * Option name for storing email stats.
	 *
	 * @var string
	 */
	const OPTION_STATS = 'apollo_email_security_stats';

	/**
	 * Rate limit: maximum emails per hour per user.
	 *
	 * @var int
	 */
	const RATE_LIMIT_HOUR = 50;

	/**
	 * Rate limit: maximum emails per day per user.
	 *
	 * @var int
	 */
	const RATE_LIMIT_DAY = 200;

	/**
	 * Check if a user is rate limited.
	 *
	 * @param int $user_id User ID to check.
	 * @return bool True if rate limited, false otherwise.
	 */
	public static function isRateLimited( $user_id ) {
		if ( self::coreLoggerAvailable() ) {
			return \Apollo_Core\Security\Email_Security_Log::isRateLimited( (int) $user_id );
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		$user_key    = 'apollo_email_count_' . $user_id;
		$hour_count  = (int) get_transient( $user_key . '_hour' );
		$day_count   = (int) get_transient( $user_key . '_day' );

		if ( $hour_count >= self::RATE_LIMIT_HOUR ) {
			self::logRateLimitHit( $user_id, 'hourly' );
			return true;
		}

		if ( $day_count >= self::RATE_LIMIT_DAY ) {
			self::logRateLimitHit( $user_id, 'daily' );
			return true;
		}

		return false;
	}

	/**
	 * Log that an email was sent successfully.
	 *
	 * @param string $to           Recipient email.
	 * @param string $subject      Email subject.
	 * @param string $template_key Template key used.
	 * @return void
	 */
	public static function logEmailSent( $to, $subject, $template_key = '' ) {
		if ( self::coreLoggerAvailable() ) {
			\Apollo_Core\Security\Email_Security_Log::logEmailSent( (string) $to, (string) $subject, (string) $template_key );
			return;
		}

		$user_id = get_current_user_id();

		// Update rate limit counters.
		self::incrementCounter( $user_id );

		// Log the email.
		self::addLog( array(
			'type'     => 'sent',
			'to'       => $to,
			'subject'  => $subject,
			'template' => $template_key,
			'user_id'  => $user_id,
			'time'     => current_time( 'mysql' ),
		) );

		// Update stats.
		self::updateStats( 'sent' );
	}

	/**
	 * Log that an email failed to send.
	 *
	 * @param string $to           Recipient email.
	 * @param string $subject      Email subject.
	 * @param string $error        Error message.
	 * @param string $template_key Template key used.
	 * @return void
	 */
	public static function logEmailFailed( $to, $subject, $error = '', $template_key = '' ) {
		if ( self::coreLoggerAvailable() ) {
			\Apollo_Core\Security\Email_Security_Log::logEmailFailed( (string) $to, (string) $subject, (string) $error, (string) $template_key );
			return;
		}

		self::addLog( array(
			'type'     => 'failed',
			'to'       => $to,
			'subject'  => $subject,
			'template' => $template_key,
			'error'    => $error,
			'user_id'  => get_current_user_id(),
			'time'     => current_time( 'mysql' ),
		) );

		self::updateStats( 'failed' );
	}

	/**
	 * Get email logs.
	 *
	 * @param int    $page     Page number.
	 * @param int    $per_page Items per page.
	 * @param string $type     Filter by type (sent/failed).
	 * @return array Array with 'logs' and 'total'.
	 */
	public static function getLogs( $page = 1, $per_page = 20, $type = '' ) {
		if ( self::coreLoggerAvailable() ) {
			// For compatibilidade: se $page for array (erro de chamada), extrair valores corretos.
			if ( is_array( $page ) ) {
				$args = $page;
				$page = isset( $args['page'] ) ? (int) $args['page'] : 1;
				$per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
				$type = isset( $args['type'] ) ? $args['type'] : '';
			}

			$core_logs = \Apollo_Core\Security\Email_Security_Log::getLogs(
				array(
					'type'     => (string) $type,
					'per_page' => (int) $per_page,
					'page'     => (int) $page,
				)
			);

			return array(
				'logs'  => $core_logs['items'] ?? array(),
				'total' => $core_logs['total'] ?? 0,
			);
		}

		   $all_logs = get_option( self::OPTION_LOGS, array() );

		   if ( ! is_array( $all_logs ) ) {
			   $all_logs = array();
		   }

		   // For compatibilidade: se $page for array (erro de chamada), extrair valores corretos.
		   if ( is_array( $page ) ) {
			   $args = $page;
			   $page = isset($args['page']) ? (int)$args['page'] : 1;
			   $per_page = isset($args['per_page']) ? (int)$args['per_page'] : 20;
			   $type = isset($args['type']) ? $args['type'] : '';
		   }

		   $page = (int)$page;
		   $per_page = (int)$per_page;

		   // Filter by type if specified.
		   if ( ! empty( $type ) ) {
			   $all_logs = array_filter( $all_logs, function( $log ) use ( $type ) {
				   return isset( $log['type'] ) && $log['type'] === $type;
			   } );
		   }

		   // Sort by time descending.
		   usort( $all_logs, function( $a, $b ) {
			   return strtotime( $b['time'] ?? '' ) - strtotime( $a['time'] ?? '' );
		   } );

		   $total  = count( $all_logs );
		   $offset = ( $page - 1 ) * $per_page;
		   $logs   = array_slice( $all_logs, $offset, $per_page );

		   return array(
			   'logs'  => $logs,
			   'total' => $total,
		   );
	}

	/**
	 * Get email statistics.
	 *
	 * @param string $period Period (today/week/month/all).
	 * @return array Stats array.
	 */
	public static function getStats( $period = 'today' ) {
		if ( self::coreLoggerAvailable() ) {
			$core_stats = \Apollo_Core\Security\Email_Security_Log::getStats( (string) $period );
			$rate_limited = \Apollo_Core\Security\Email_Security_Log::getLogs(
				array(
					'type'     => \Apollo_Core\Security\Email_Security_Log::TYPE_RATE_LIMITED,
					'per_page' => 1,
					'page'     => 1,
				)
			);

			return array(
				'sent'         => (int) ( $core_stats['total_sent'] ?? 0 ),
				'failed'       => (int) ( $core_stats['total_failed'] ?? 0 ),
				'rate_limited' => (int) ( $rate_limited['total'] ?? 0 ),
			);
		}

		$stats = get_option( self::OPTION_STATS, array() );

		if ( ! is_array( $stats ) ) {
			$stats = array();
		}

		$default = array(
			'sent'         => 0,
			'failed'       => 0,
			'rate_limited' => 0,
		);

		$period_key = self::getPeriodKey( $period );

		return isset( $stats[ $period_key ] ) ? array_merge( $default, $stats[ $period_key ] ) : $default;
	}

	/**
	 * Increment the email counter for rate limiting.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	private static function incrementCounter( $user_id ) {
		if ( empty( $user_id ) ) {
			return;
		}

		$user_key = 'apollo_email_count_' . $user_id;

		// Hourly counter.
		$hour_count = (int) get_transient( $user_key . '_hour' );
		set_transient( $user_key . '_hour', $hour_count + 1, HOUR_IN_SECONDS );

		// Daily counter.
		$day_count = (int) get_transient( $user_key . '_day' );
		set_transient( $user_key . '_day', $day_count + 1, DAY_IN_SECONDS );
	}

	/**
	 * Add a log entry.
	 *
	 * @param array $entry Log entry data.
	 * @return void
	 */
	private static function addLog( $entry ) {
		$logs = get_option( self::OPTION_LOGS, array() );

		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		// Add new entry.
		array_unshift( $logs, $entry );

		// Keep only last 1000 entries.
		$logs = array_slice( $logs, 0, 1000 );

		update_option( self::OPTION_LOGS, $logs, false );
	}

	/**
	 * Update statistics.
	 *
	 * @param string $type Stat type (sent/failed/rate_limited).
	 * @return void
	 */
	private static function updateStats( $type ) {
		$stats      = get_option( self::OPTION_STATS, array() );
		$period_key = self::getPeriodKey( 'today' );

		if ( ! isset( $stats[ $period_key ] ) ) {
			$stats[ $period_key ] = array(
				'sent'         => 0,
				'failed'       => 0,
				'rate_limited' => 0,
			);
		}

		if ( isset( $stats[ $period_key ][ $type ] ) ) {
			$stats[ $period_key ][ $type ]++;
		}

		// Cleanup old stats (keep last 30 days).
		$stats = self::cleanupOldStats( $stats );

		update_option( self::OPTION_STATS, $stats, false );
	}

	/**
	 * Log rate limit hit.
	 *
	 * @param int    $user_id User ID.
	 * @param string $limit_type Limit type (hourly/daily).
	 * @return void
	 */
	private static function logRateLimitHit( $user_id, $limit_type ) {
		self::addLog( array(
			'type'       => 'rate_limited',
			'user_id'    => $user_id,
			'limit_type' => $limit_type,
			'time'       => current_time( 'mysql' ),
		) );

		self::updateStats( 'rate_limited' );
	}

	/**
	 * Get period key for stats.
	 *
	 * @param string $period Period name.
	 * @return string Period key.
	 */
	private static function getPeriodKey( $period ) {
		switch ( $period ) {
			case 'today':
				return gmdate( 'Y-m-d' );
			case 'week':
				return gmdate( 'Y-W' );
			case 'month':
				return gmdate( 'Y-m' );
			default:
				return 'all';
		}
	}

	/**
	 * Cleanup old stats entries.
	 *
	 * @param array $stats Stats array.
	 * @return array Cleaned stats.
	 */
	private static function cleanupOldStats( $stats ) {
		$cutoff = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		foreach ( array_keys( $stats ) as $key ) {
			// Only cleanup date-based keys.
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $key ) && $key < $cutoff ) {
				unset( $stats[ $key ] );
			}
		}

		return $stats;
	}
}
