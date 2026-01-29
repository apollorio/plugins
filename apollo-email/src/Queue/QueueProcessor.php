<?php
/**
 * Email Queue Processor
 *
 * Processes individual queued emails.
 *
 * @package ApolloEmail\Queue
 */

declare(strict_types=1);

namespace ApolloEmail\Queue;

use ApolloEmail\UnifiedEmailService;

/**
 * Queue Processor Class
 */
class QueueProcessor {

	/**
	 * Instance
	 *
	 * @var QueueProcessor|null
	 */
	private static ?QueueProcessor $instance = null;

	/**
	 * Email service
	 *
	 * @var UnifiedEmailService
	 */
	private UnifiedEmailService $email_service;

	/**
	 * Get instance
	 *
	 * @return QueueProcessor
	 */
	public static function get_instance(): QueueProcessor {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->email_service = UnifiedEmailService::get_instance();
	}

	/**
	 * Process a single queued email
	 *
	 * @param array $email Email data from queue.
	 */
	public function process( array $email ): void {
		global $wpdb;

		$email_id = (int) $email['id'];

		// Mark as processing.
		$wpdb->update(
			$wpdb->prefix . 'apollo_email_queue',
			[ 'status' => 'processing' ],
			[ 'id' => $email_id ],
			[ '%s' ],
			[ '%d' ]
		);

		// Send email.
		$result = $this->email_service->send(
			$email['recipient_email'],
			$email['subject'],
			$email['body'],
			[
				'template' => $email['template'],
				'priority' => $email['priority'],
			]
		);

		// Update queue status.
		if ( $result ) {
			$wpdb->update(
				$wpdb->prefix . 'apollo_email_queue',
				[
					'status'  => 'sent',
					'sent_at' => current_time( 'mysql' ),
				],
				[ 'id' => $email_id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			// Failed - increment retry count.
			$retry_count = (int) $email['retry_count'] + 1;
			$max_retries = 3;

			if ( $retry_count >= $max_retries ) {
				// Max retries reached - mark as failed.
				$wpdb->update(
					$wpdb->prefix . 'apollo_email_queue',
					[
						'status'        => 'failed',
						'error_message' => 'Max retries exceeded',
						'retry_count'   => $retry_count,
					],
					[ 'id' => $email_id ],
					[ '%s', '%s', '%d' ],
					[ '%d' ]
				);
			} else {
				// Retry later.
				$wpdb->update(
					$wpdb->prefix . 'apollo_email_queue',
					[
						'status'       => 'pending',
						'retry_count'  => $retry_count,
						'scheduled_at' => gmdate( 'Y-m-d H:i:s', time() + ( 300 * $retry_count ) ), // 5 min * retry count
					],
					[ 'id' => $email_id ],
					[ '%s', '%d', '%s' ],
					[ '%d' ]
				);
			}
		}
	}
}
