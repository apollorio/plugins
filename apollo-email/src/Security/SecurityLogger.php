<?php
/**
 * Security Logger
 *
 * Logs security-related email events.
 *
 * @package ApolloEmail\Security
 */

declare(strict_types=1);

namespace ApolloEmail\Security;

/**
 * Security Logger Class
 */
class SecurityLogger {

	/**
	 * Instance
	 *
	 * @var SecurityLogger|null
	 */
	private static ?SecurityLogger $instance = null;

	/**
	 * Get instance
	 *
	 * @return SecurityLogger
	 */
	public static function get_instance(): SecurityLogger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Initialize.
	}

	/**
	 * Log a security event
	 *
	 * @param string $event_type Event type.
	 * @param string $severity   Severity (info, warning, error, critical).
	 * @param string $description Description.
	 * @param array  $metadata   Optional metadata.
	 */
	public function log( string $event_type, string $severity, string $description, array $metadata = [] ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'apollo_email_security_log',
			[
				'event_type'  => $event_type,
				'severity'    => $severity,
				'user_id'     => get_current_user_id(),
				'ip_address'  => $this->get_client_ip(),
				'description' => $description,
				'metadata'    => wp_json_encode( $metadata ),
				'created_at'  => current_time( 'mysql' ),
			],
			[
				'%s', // event_type
				'%s', // severity
				'%d', // user_id
				'%s', // ip_address
				'%s', // description
				'%s', // metadata
				'%s', // created_at
			]
		);

		// Critical events - send alert.
		if ( 'critical' === $severity ) {
			$this->send_critical_alert( $event_type, $description );
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string IP address.
	 */
	private function get_client_ip(): string {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '0.0.0.0';
	}

	/**
	 * Send critical alert
	 *
	 * @param string $event_type Event type.
	 * @param string $description Description.
	 */
	private function send_critical_alert( string $event_type, string $description ): void {
		// Send email to admin.
		$admin_email = get_option( 'admin_email' );

		wp_mail(
			$admin_email,
			sprintf( '[CRITICAL] Apollo Email Security Alert: %s', $event_type ),
			sprintf(
				"Critical security event detected:\n\nEvent: %s\nDescription: %s\nTime: %s\nIP: %s",
				$event_type,
				$description,
				current_time( 'mysql' ),
				$this->get_client_ip()
			)
		);
	}
}
