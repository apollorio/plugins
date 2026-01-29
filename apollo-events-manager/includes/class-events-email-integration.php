<?php
/**
 * Apollo Events Manager Email Integration
 *
 * Bridges apollo-events-manager with the unified email system.
 * This file should be placed in apollo-events-manager/includes/
 *
 * @package Apollo_Events_Manager
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events Manager Email Integration
 *
 * Integrates event manager actions with the unified email service.
 */
class Apollo_Events_Email_Integration {

	/**
	 * Initialize integration.
	 */
	public static function init() {
		// Only load if unified email service exists
		if ( ! class_exists( '\\Apollo\\Email\\UnifiedEmailService' ) ) {
			// Fallback: try to load from apollo-social
			$service_file = WP_PLUGIN_DIR . '/apollo-social/src/Email/UnifiedEmailService.php';
			if ( file_exists( $service_file ) ) {
				require_once $service_file;
			} else {
				return; // Can't integrate without the service
			}
		}

		// Hook into existing events manager actions
		add_action( 'apollo_event_reminder', array( __CLASS__, 'send_event_reminder' ), 5, 2 );
		add_action( 'apollo_event_bookmarked', array( __CLASS__, 'send_bookmark_notification' ), 10, 2 );
		add_action( 'apollo_interest_added', array( __CLASS__, 'log_interest' ), 10, 2 );
		add_action( 'apollo_cena_rio_event_approved', array( __CLASS__, 'send_approval_notification' ), 10, 3 );

		// Add filter to use unified service for all event emails
		add_filter( 'apollo_events_should_use_unified_email', '__return_true' );
	}

	/**
	 * Send event reminder using unified service.
	 *
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 */
	public static function send_event_reminder( int $event_id, int $user_id ) {
		$event = get_post( $event_id );
		if ( ! $event ) {
			return;
		}

		\Apollo\Email\UnifiedEmailService::send( array(
			'to'       => $user_id,
			'type'     => 'event_reminder',
			'template' => 'event_reminder',
			'data'     => array(
				'event_id'      => $event_id,
				'event_name'    => $event->post_title,
				'event_url'     => get_permalink( $event_id ),
				'event_date'    => get_post_meta( $event_id, 'event_date', true ),
				'event_time'    => get_post_meta( $event_id, 'event_time', true ),
				'event_venue'   => get_post_meta( $event_id, 'event_venue', true ),
				'event_address' => get_post_meta( $event_id, 'event_address', true ),
				'event_djs'     => get_post_meta( $event_id, 'event_djs', true ),
			),
		) );
	}

	/**
	 * Send bookmark notification using unified service.
	 *
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 */
	public static function send_bookmark_notification( int $event_id, int $user_id ) {
		$event = get_post( $event_id );
		if ( ! $event ) {
			return;
		}

		\Apollo\Email\UnifiedEmailService::send( array(
			'to'       => $user_id,
			'type'     => 'event_bookmark',
			'template' => 'event_bookmark',
			'data'     => array(
				'event_id'   => $event_id,
				'event_name' => $event->post_title,
				'event_url'  => get_permalink( $event_id ),
			),
		) );
	}

	/**
	 * Log interest for future notifications.
	 *
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 */
	public static function log_interest( int $event_id, int $user_id ) {
		// Ensure user is subscribed to event notifications
		$subscriptions = get_user_meta( $user_id, '_apollo_event_subscriptions', true );
		$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

		if ( ! in_array( $event_id, $subscriptions, true ) ) {
			$subscriptions[] = $event_id;
			update_user_meta( $user_id, '_apollo_event_subscriptions', array_unique( $subscriptions ) );
		}
	}

	/**
	 * Send approval notification for Cena::Rio events.
	 *
	 * @param int    $event_id Event ID.
	 * @param int    $user_id  User ID (organizer).
	 * @param string $status   Approval status.
	 */
	public static function send_approval_notification( int $event_id, int $user_id, string $status ) {
		$event = get_post( $event_id );
		if ( ! $event ) {
			return;
		}

		$subject = $status === 'approved'
			? sprintf( __( '✅ Seu evento "%s" foi aprovado!', 'apollo-events' ), $event->post_title )
			: sprintf( __( '❌ Seu evento "%s" precisa de ajustes', 'apollo-events' ), $event->post_title );

		$body = $status === 'approved'
			? self::get_approval_body( $event )
			: self::get_rejection_body( $event );

		\Apollo\Email\UnifiedEmailService::send( array(
			'to'      => $user_id,
			'type'    => 'event_approval',
			'subject' => $subject,
			'body'    => $body,
			'force'   => true, // Always send approval notifications
			'data'    => array(
				'event_id'   => $event_id,
				'event_name' => $event->post_title,
				'event_url'  => get_permalink( $event_id ),
				'status'     => $status,
			),
		) );
	}

	/**
	 * Get approval email body.
	 *
	 * @param WP_Post $event Event post.
	 * @return string
	 */
	private static function get_approval_body( $event ) {
		$event_url = get_permalink( $event->ID );
		return sprintf(
			'<h2>✅ Parabéns!</h2>
			<p>Seu evento <strong>%s</strong> foi aprovado e já está visível na plataforma.</p>
			<p><a href="%s" style="display:inline-block;background:#00d4ff;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;">Ver evento →</a></p>',
			esc_html( $event->post_title ),
			esc_url( $event_url )
		);
	}

	/**
	 * Get rejection email body.
	 *
	 * @param WP_Post $event Event post.
	 * @return string
	 */
	private static function get_rejection_body( $event ) {
		$edit_url = admin_url( 'post.php?post=' . $event->ID . '&action=edit' );
		return sprintf(
			'<h2>📝 Revisão necessária</h2>
			<p>Seu evento <strong>%s</strong> precisa de alguns ajustes antes de ser publicado.</p>
			<p>Por favor, revise as informações e envie novamente.</p>
			<p><a href="%s" style="display:inline-block;background:#f0ad4e;color:#000;padding:12px 24px;border-radius:6px;text-decoration:none;">Editar evento →</a></p>',
			esc_html( $event->post_title ),
			esc_url( $edit_url )
		);
	}
}

// Initialize on plugins_loaded to ensure dependencies are available
add_action( 'plugins_loaded', array( 'Apollo_Events_Email_Integration', 'init' ), 25 );
