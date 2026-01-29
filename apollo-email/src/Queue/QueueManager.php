<?php
/**
 * Email Queue Manager
 *
 * Manages background email processing queue.
 *
 * @package ApolloEmail\Queue
 */

declare(strict_types=1);

namespace ApolloEmail\Queue;

/**
 * Queue Manager Class
 */
class QueueManager {

	/**
	 * Instance
	 *
	 * @var QueueManager|null
	 */
	private static ?QueueManager $instance = null;

	/**
	 * Get instance
	 *
	 * @return QueueManager
	 */
	public static function get_instance(): QueueManager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Schedule cron for queue processing.
		add_action( 'apollo_email_process_queue', array( $this, 'process_queue' ) );

		if ( ! wp_next_scheduled( 'apollo_email_process_queue' ) ) {
			wp_schedule_event( time(), 'every_minute', 'apollo_email_process_queue' );
		}
	}

	/**
	 * Add email to queue
	 *
	 * @param array $email_data Email data.
	 *
	 * @return int|false Queue ID or false on failure.
	 */
	public function enqueue( array $email_data ) {
		global $wpdb;

		$defaults = [
			'recipient_id'    => null,
			'recipient_email' => '',
			'subject'         => '',
			'body'            => '',
			'template'        => null,
			'priority'        => 'normal',
			'status'          => 'pending',
			'scheduled_at'    => current_time( 'mysql' ),
		];

		$email_data = wp_parse_args( $email_data, $defaults );

		// Validate.
		if ( empty( $email_data['recipient_email'] ) || ! is_email( $email_data['recipient_email'] ) ) {
			return false;
		}

		// Insert.
		$result = $wpdb->insert(
			$wpdb->prefix . 'apollo_email_queue',
			$email_data,
			[
				'%d', // recipient_id
				'%s', // recipient_email
				'%s', // subject
				'%s', // body
				'%s', // template
				'%s', // priority
				'%s', // status
				'%s', // scheduled_at
			]
		);

		if ( ! $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Process email queue (called by cron)
	 */
	public function process_queue(): void {
		global $wpdb;

		// Get pending emails (limit 10 per batch, ordered by priority).
		$emails = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}apollo_email_queue
			WHERE status = 'pending'
			AND scheduled_at <= NOW()
			ORDER BY
				FIELD(priority, 'urgent', 'high', 'normal', 'low'),
				scheduled_at ASC
			LIMIT 10",
			ARRAY_A
		);

		if ( empty( $emails ) ) {
			return;
		}

		$processor = QueueProcessor::get_instance();

		foreach ( $emails as $email ) {
			$processor->process( $email );
		}

		do_action( 'apollo_email/queue/processed', count( $emails ) );
	}

	/**
	 * Get queue statistics
	 *
	 * @return array
	 */
	public function get_stats(): array {
		global $wpdb;

		return [
			'pending'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'pending'" ),
			'processing' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'processing'" ),
			'sent'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'sent'" ),
			'failed'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'failed'" ),
			'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue" ),
		];
	}
}
