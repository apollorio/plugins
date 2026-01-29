<?php
/**
 * Apollo Unified Email Service
 *
 * Central email service that unifies email sending across all Apollo plugins:
 * - apollo-core
 * - apollo-social
 * - apollo-events-manager
 * - apollo-rio
 *
 * Provides a single API for sending emails with user preference checking,
 * template resolution, and logging.
 *
 * @package Apollo_Social
 * @since   2.0.0
 */

namespace Apollo\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load dependencies.
if ( file_exists( APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/tabs/class-user-email-tab.php' ) ) {
	require_once APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/tabs/class-user-email-tab.php';
}

/**
 * Unified Email Service
 *
 * Usage:
 *   UnifiedEmailService::send([
 *       'to' => $user_id_or_email,
 *       'type' => 'event_changed', // Must match a notification type
 *       'subject' => 'Event Updated!',
 *       'body' => 'The event has been updated...',
 *       'template' => 'event_update', // Optional: template key from EmailHub
 *       'data' => ['event_name' => 'Party', ...], // Placeholder data
 *   ]);
 */
class UnifiedEmailService {

	/**
	 * Email types and their corresponding user preference keys.
	 */
	const TYPE_PREFS_MAP = array(
		// Apollo::Rio
		'apollo_news'                => 'apollo_news',
		'new_event'                  => 'new_events_registered',
		'weekly_notifications'       => 'weekly_notifications',
		'weekly_messages'            => 'weekly_messages_unanswered',

		// Events
		'event_status'               => 'event_status_reminder',
		'event_lineup'               => 'event_lineup_updates',
		'event_changed'              => 'event_changed_interest',
		'event_cancelled'            => 'event_cancelled',
		'event_response'             => 'event_invite_response',
		'event_djs_update'           => 'event_djs_update',
		'event_category_update'      => 'event_category_update',

		// Classifieds
		'classified_message'         => 'classifieds_messages',

		// Community & Groups
		'community_invite'           => 'community_invites',
		'nucleo_invite'              => 'nucleo_invites',
		'nucleo_approval'            => 'nucleo_approvals',

		// Documents
		'document_signature'         => 'document_signatures',

		// Legacy mappings
		'event_reminder'             => 'event_status_reminder',
		'event_update'               => 'event_changed_interest',
		'event_bookmark'             => 'event_changed_interest',
		'weekly_digest'              => 'weekly_notifications',
	);

	/**
	 * Admin notification settings mapping.
	 */
	const ADMIN_SETTINGS_MAP = array(
		'event_changed'         => 'enable_event_changed',
		'event_cancelled'       => 'enable_event_cancelled',
		'event_response'        => 'enable_event_response',
		'event_djs_update'      => 'enable_event_djs_update',
		'event_category_update' => 'enable_event_category_update',
	);

	/**
	 * Send an email respecting user preferences.
	 *
	 * @param array $args Email arguments.
	 * @return bool|array True on success, array with error on failure.
	 */
	public static function send( array $args ) {
		$defaults = array(
			'to'          => '',
			'type'        => '',
			'subject'     => '',
			'body'        => '',
			'template'    => '',
			'data'        => array(),
			'force'       => false, // Bypass preference check
			'headers'     => array(),
			'attachments' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		// Resolve recipient
		$recipient = self::resolve_recipient( $args['to'] );
		if ( ! $recipient ) {
			return array( 'error' => 'invalid_recipient', 'message' => 'Invalid recipient' );
		}

		$user_id = $recipient['user_id'];
		$email   = $recipient['email'];

		// Check admin settings (global toggle)
		if ( ! $args['force'] && ! self::is_admin_enabled( $args['type'] ) ) {
			return array( 'error' => 'admin_disabled', 'message' => 'Notification type disabled by admin' );
		}

		// Check user preferences
		if ( ! $args['force'] && $user_id && ! self::is_user_enabled( $user_id, $args['type'] ) ) {
			return array( 'error' => 'user_disabled', 'message' => 'User has disabled this notification type' );
		}

		// Rate limiting check
		if ( $user_id && ! self::check_rate_limit( $user_id ) ) {
			return array( 'error' => 'rate_limited', 'message' => 'User has reached daily email limit' );
		}

		// Resolve template if provided
		if ( $args['template'] ) {
			$template_content = self::get_template_content( $args['template'], $args['data'] );
			if ( $template_content ) {
				$args['subject'] = $template_content['subject'];
				$args['body']    = $template_content['body'];
			}
		}

		// Process placeholders in subject and body
		$args['subject'] = self::process_placeholders( $args['subject'], $args['data'], $user_id );
		$args['body']    = self::process_placeholders( $args['body'], $args['data'], $user_id );

		// Build headers
		$headers = self::build_headers( $args['headers'] );

		// Send email
		$sent = wp_mail( $email, $args['subject'], $args['body'], $headers, $args['attachments'] );

		// Log the email
		self::log_email( $user_id, $email, $args['type'], $args['subject'], $sent );

		// Increment rate limit counter
		if ( $user_id && $sent ) {
			self::increment_rate_limit( $user_id );
		}

		// Fire action for integrations
		do_action( 'apollo_email_sent', $email, $args['type'], $args['subject'], $sent, $user_id );

		return $sent;
	}

	/**
	 * Send email to multiple users.
	 *
	 * @param array $user_ids Array of user IDs.
	 * @param array $args     Email arguments (without 'to').
	 * @return array Results keyed by user ID.
	 */
	public static function send_batch( array $user_ids, array $args ): array {
		$results = array();

		foreach ( $user_ids as $user_id ) {
			$args['to']           = $user_id;
			$results[ $user_id ] = self::send( $args );
		}

		return $results;
	}

	/**
	 * Queue email for later sending (uses EmailQueueRepository if available).
	 *
	 * @param array  $args       Email arguments.
	 * @param string $schedule   When to send (datetime string or relative like '+1 hour').
	 * @return int|false Queue ID or false on failure.
	 */
	public static function queue( array $args, string $schedule = '' ) {
		// Check if EmailQueueRepository exists
		if ( class_exists( '\\Apollo\\Social\\Modules\\Email\\EmailQueueRepository' ) ) {
			$repository = new \Apollo\Social\Modules\Email\EmailQueueRepository();

			$recipient = self::resolve_recipient( $args['to'] );
			if ( ! $recipient ) {
				return false;
			}

			return $repository->enqueue( array(
				'recipient_email' => $recipient['email'],
				'to_user_id'      => $recipient['user_id'],
				'subject'         => $args['subject'] ?? '',
				'body'            => $args['body'] ?? '',
				'template'        => $args['template'] ?? '',
				'template_data'   => $args['data'] ?? array(),
				'scheduled_at'    => $schedule ? gmdate( 'Y-m-d H:i:s', strtotime( $schedule ) ) : null,
			) );
		}

		// Fallback: schedule with WP Cron
		$timestamp = $schedule ? strtotime( $schedule ) : time() + 300;
		wp_schedule_single_event( $timestamp, 'apollo_send_queued_email', array( $args ) );

		return true;
	}

	/**
	 * Notify all users interested in an event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $type     Notification type.
	 * @param array  $args     Additional email arguments.
	 * @return array Results.
	 */
	public static function notify_event_interested( int $event_id, string $type, array $args = array() ): array {
		$interested_users = self::get_event_interested_users( $event_id );

		if ( empty( $interested_users ) ) {
			return array();
		}

		// Get event data for placeholders
		$event = get_post( $event_id );
		if ( ! $event ) {
			return array();
		}

		$event_data = array(
			'event_id'      => $event_id,
			'event_name'    => $event->post_title,
			'event_url'     => get_permalink( $event_id ),
			'event_date'    => get_post_meta( $event_id, 'event_date', true ),
			'event_time'    => get_post_meta( $event_id, 'event_time', true ),
			'event_venue'   => get_post_meta( $event_id, 'event_venue', true ),
			'event_djs'     => get_post_meta( $event_id, 'event_djs', true ),
			'event_address' => get_post_meta( $event_id, 'event_address', true ),
		);

		$args['type'] = $type;
		$args['data'] = array_merge( $event_data, $args['data'] ?? array() );

		return self::send_batch( $interested_users, $args );
	}

	/**
	 * Resolve recipient from user ID or email.
	 *
	 * @param mixed $recipient User ID, email string, or WP_User object.
	 * @return array|null Array with 'user_id' and 'email' or null.
	 */
	private static function resolve_recipient( $recipient ): ?array {
		if ( empty( $recipient ) ) {
			return null;
		}

		// WP_User object
		if ( $recipient instanceof \WP_User ) {
			return array(
				'user_id' => $recipient->ID,
				'email'   => $recipient->user_email,
			);
		}

		// User ID
		if ( is_numeric( $recipient ) ) {
			$user = get_userdata( (int) $recipient );
			if ( $user ) {
				return array(
					'user_id' => $user->ID,
					'email'   => $user->user_email,
				);
			}
			return null;
		}

		// Email string
		if ( is_email( $recipient ) ) {
			$user = get_user_by( 'email', $recipient );
			return array(
				'user_id' => $user ? $user->ID : 0,
				'email'   => $recipient,
			);
		}

		return null;
	}

	/**
	 * Check if notification type is enabled by admin.
	 *
	 * @param string $type Notification type.
	 * @return bool
	 */
	private static function is_admin_enabled( string $type ): bool {
		// Check if there's an admin setting for this type
		if ( ! isset( self::ADMIN_SETTINGS_MAP[ $type ] ) ) {
			return true; // No admin control = always enabled
		}

		$settings_key = self::ADMIN_SETTINGS_MAP[ $type ];
		$settings     = get_option( \Apollo\Admin\EmailNotificationsAdmin::OPTION_KEY, array() );

		return $settings[ $settings_key ] ?? true;
	}

	/**
	 * Check if user has enabled this notification type.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Notification type.
	 * @return bool
	 */
	private static function is_user_enabled( int $user_id, string $type ): bool {
		if ( ! $user_id ) {
			return true;
		}

		// Map type to preference key
		$pref_key = self::TYPE_PREFS_MAP[ $type ] ?? $type;

		// Use User Email Tab if available
		if ( class_exists( '\\Apollo_User_Email_Tab' ) ) {
			return \Apollo_User_Email_Tab::is_enabled( $user_id, $pref_key );
		}

		// Fallback to direct meta check
		$prefs = get_user_meta( $user_id, '_apollo_email_prefs', true );
		if ( ! is_array( $prefs ) ) {
			$prefs = array();
		}

		return $prefs[ $pref_key ] ?? true;
	}

	/**
	 * Check rate limit for user.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if under limit.
	 */
	private static function check_rate_limit( int $user_id ): bool {
		$settings = get_option( \Apollo\Admin\EmailNotificationsAdmin::OPTION_KEY, array() );
		$max_per_day = $settings['max_emails_per_user_per_day'] ?? 20;

		$count = (int) get_transient( "apollo_email_count_{$user_id}_day" );

		return $count < $max_per_day;
	}

	/**
	 * Increment rate limit counter.
	 *
	 * @param int $user_id User ID.
	 */
	private static function increment_rate_limit( int $user_id ): void {
		$key   = "apollo_email_count_{$user_id}_day";
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, DAY_IN_SECONDS );
	}

	/**
	 * Get template content from EmailHub.
	 *
	 * @param string $template_key Template key.
	 * @param array  $data         Placeholder data.
	 * @return array|null Array with 'subject' and 'body' or null.
	 */
	private static function get_template_content( string $template_key, array $data ): ?array {
		$templates = get_option( 'apollo_email_templates', array() );

		if ( isset( $templates[ $template_key ] ) ) {
			return array(
				'subject' => $templates[ $template_key ]['subject'] ?? '',
				'body'    => $templates[ $template_key ]['body'] ?? '',
			);
		}

		return null;
	}

	/**
	 * Process placeholders in text.
	 *
	 * @param string $text    Text with placeholders.
	 * @param array  $data    Placeholder data.
	 * @param int    $user_id User ID for user placeholders.
	 * @return string
	 */
	private static function process_placeholders( string $text, array $data, int $user_id = 0 ): string {
		// User placeholders
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$text = str_replace(
					array(
						'[user-name]',
						'[display-name]',
						'[user-email]',
						'[user-id]',
						'[first-name]',
						'[last-name]',
					),
					array(
						$user->user_login,
						$user->display_name,
						$user->user_email,
						$user->ID,
						get_user_meta( $user_id, 'first_name', true ),
						get_user_meta( $user_id, 'last_name', true ),
					),
					$text
				);
			}
		}

		// Site placeholders
		$text = str_replace(
			array(
				'[site-name]',
				'[site-url]',
				'[login-url]',
				'[current-year]',
			),
			array(
				get_bloginfo( 'name' ),
				home_url(),
				wp_login_url(),
				gmdate( 'Y' ),
			),
			$text
		);

		// Event placeholders
		$event_placeholders = array(
			'event_name'    => '[event-name]',
			'event_date'    => '[event-date]',
			'event_time'    => '[event-time]',
			'event_venue'   => '[event-venue]',
			'event_address' => '[event-address]',
			'event_url'     => '[event-url]',
			'event_djs'     => '[event-djs]',
		);

		foreach ( $event_placeholders as $key => $placeholder ) {
			if ( isset( $data[ $key ] ) ) {
				$text = str_replace( $placeholder, $data[ $key ], $text );
			}
		}

		// Custom data placeholders ({{key}} format)
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				$text = str_replace( '{{' . $key . '}}', $value, $text );
				$text = str_replace( '{{NAME EVENT}}', $data['event_name'] ?? '', $text );
			}
		}

		return $text;
	}

	/**
	 * Build email headers.
	 *
	 * @param array $custom_headers Custom headers.
	 * @return array
	 */
	private static function build_headers( array $custom_headers = array() ): array {
		$settings = get_option( 'apollo_email_hub_settings', array() );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		// From name and email
		$from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );
		$from_email = $settings['from_email'] ?? get_option( 'admin_email' );
		$headers[]  = "From: {$from_name} <{$from_email}>";

		// Reply-To
		if ( ! empty( $settings['reply_to'] ) ) {
			$headers[] = "Reply-To: {$settings['reply_to']}";
		}

		return array_merge( $headers, $custom_headers );
	}

	/**
	 * Log sent email.
	 *
	 * @param int    $user_id User ID.
	 * @param string $email   Email address.
	 * @param string $type    Email type.
	 * @param string $subject Email subject.
	 * @param bool   $sent    Whether email was sent.
	 */
	private static function log_email( int $user_id, string $email, string $type, string $subject, bool $sent ): void {
		// Use security log if available
		if ( class_exists( '\\Apollo\\Social\\Security\\EmailSecurityLog' ) ) {
			\Apollo\Social\Security\EmailSecurityLog::log( array(
				'user_id' => $user_id,
				'email'   => $email,
				'type'    => $type,
				'subject' => $subject,
				'status'  => $sent ? 'sent' : 'failed',
			) );
			return;
		}

		// Fallback: basic logging
		$logs = get_option( 'apollo_email_logs', array() );

		// Keep only last 1000 logs
		if ( count( $logs ) > 1000 ) {
			$logs = array_slice( $logs, -500 );
		}

		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => $user_id,
			'email'     => $email,
			'type'      => $type,
			'subject'   => $subject,
			'status'    => $sent ? 'sent' : 'failed',
		);

		update_option( 'apollo_email_logs', $logs, false );
	}

	/**
	 * Get users interested in an event.
	 *
	 * @param int $event_id Event ID.
	 * @return array User IDs.
	 */
	private static function get_event_interested_users( int $event_id ): array {
		global $wpdb;

		// Check event subscriptions meta
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = '_apollo_event_subscriptions'
				AND meta_value LIKE %s",
				'%"' . $event_id . '"%'
			)
		);

		// Also check interests meta
		$interest_users = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = '_apollo_interests'
				AND meta_value LIKE %s",
				'%"' . $event_id . '"%'
			)
		);

		return array_unique( array_merge( $user_ids, $interest_users ) );
	}
}

// Hook for queued emails
add_action( 'apollo_send_queued_email', function( $args ) {
	UnifiedEmailService::send( $args );
} );
