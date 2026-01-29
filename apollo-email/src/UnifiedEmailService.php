<?php
/**
 * Unified Email Service
 *
 * Main email service class. Handles sending emails, queue management, templates, etc.
 * This is the CANONICAL email service for Apollo platform.
 *
 * @package ApolloEmail
 */

declare(strict_types=1);

namespace ApolloEmail;

use ApolloEmail\Queue\QueueManager;
use ApolloEmail\Templates\TemplateManager;
use ApolloEmail\Security\SecurityLogger;

/**
 * Unified Email Service Class
 */
class UnifiedEmailService {

	/**
	 * Service instance (singleton)
	 *
	 * @var UnifiedEmailService|null
	 */
	private static ?UnifiedEmailService $instance = null;

	/**
	 * Queue manager
	 *
	 * @var QueueManager
	 */
	private QueueManager $queue_manager;

	/**
	 * Template manager
	 *
	 * @var TemplateManager
	 */
	private TemplateManager $template_manager;

	/**
	 * Security logger
	 *
	 * @var SecurityLogger
	 */
	private SecurityLogger $security_logger;

	/**
	 * Get service instance (singleton)
	 *
	 * @return UnifiedEmailService
	 */
	public static function get_instance(): UnifiedEmailService {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->queue_manager    = QueueManager::get_instance();
		$this->template_manager = TemplateManager::get_instance();
		$this->security_logger  = SecurityLogger::get_instance();
	}

	/**
	 * Send an email
	 *
	 * @param string $to      Recipient email address.
	 * @param string $subject Email subject.
	 * @param string $body    Email body (HTML).
	 * @param array  $args    Optional arguments.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function send( string $to, string $subject, string $body, array $args = [] ): bool {
		// Validate email address.
		if ( ! is_email( $to ) ) {
			$this->security_logger->log(
				'invalid_email',
				'error',
				sprintf( 'Invalid email address: %s', $to )
			);
			return false;
		}

		// Apply filters.
		$to      = apply_filters( 'apollo_email/recipient', $to, $args );
		$subject = apply_filters( 'apollo_email/subject', $subject, $args );
		$body    = apply_filters( 'apollo_email/body', $body, $args );

		// Before send hook.
		do_action( 'apollo_email/before_send', $to, $subject, $body, $args );

		// Use template if specified.
		if ( isset( $args['template'] ) ) {
			$body = $this->template_manager->render( $args['template'], $args['template_data'] ?? [] );
		}

		// Set headers.
		$headers = $args['headers'] ?? [
			'Content-Type: text/html; charset=UTF-8',
		];

		// Send email.
		$result = wp_mail( $to, $subject, $body, $headers );

		// Log result.
		$this->security_logger->log(
			'email_sent',
			$result ? 'info' : 'error',
			sprintf(
				'Email %s to %s: %s',
				$result ? 'sent' : 'failed',
				$to,
				$subject
			),
			[
				'to'      => $to,
				'subject' => $subject,
				'result'  => $result,
			]
		);

		// After send hook.
		do_action( 'apollo_email/after_send', $to, $subject, $body, $result, $args );

		return $result;
	}

	/**
	 * Queue an email for background processing
	 *
	 * @param string $to      Recipient email.
	 * @param string $subject Subject.
	 * @param string $body    Body.
	 * @param array  $args    Optional args.
	 *
	 * @return int|false Queue ID on success, false on failure.
	 */
	public function queue( string $to, string $subject, string $body, array $args = [] ) {
		return $this->queue_manager->enqueue(
			[
				'recipient_email' => $to,
				'subject'         => $subject,
				'body'            => $body,
				'priority'        => $args['priority'] ?? 'normal',
				'template'        => $args['template'] ?? null,
			]
		);
	}

	/**
	 * Send bulk emails (queued)
	 *
	 * @param array $recipients Array of recipients.
	 * @param string $subject   Subject.
	 * @param string $body      Body.
	 * @param array  $args      Optional args.
	 *
	 * @return int Number of emails queued.
	 */
	public function send_bulk( array $recipients, string $subject, string $body, array $args = [] ): int {
		$queued = 0;

		foreach ( $recipients as $recipient ) {
			$to = is_array( $recipient ) ? $recipient['email'] : $recipient;

			if ( $this->queue( $to, $subject, $body, $args ) ) {
				$queued++;
			}
		}

		return $queued;
	}

	/**
	 * Send email with template
	 *
	 * @param string $to            Recipient.
	 * @param string $template_slug Template slug.
	 * @param array  $data          Template data.
	 * @param array  $args          Optional args.
	 *
	 * @return bool
	 */
	public function send_with_template( string $to, string $template_slug, array $data = [], array $args = [] ): bool {
		$body = $this->template_manager->render( $template_slug, $data );

		// Extract subject from template or use provided.
		$subject = $args['subject'] ?? $this->template_manager->get_subject( $template_slug, $data );

		return $this->send( $to, $subject, $body, $args );
	}
}
