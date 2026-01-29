<?php
/**
 * Apollo Email - Public API
 *
 * This file provides public exports for the email service.
 * Other plugins can safely require this file to use email functionality.
 *
 * @package ApolloEmail
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure plugin is loaded.
if ( ! defined( 'APOLLO_EMAIL_VERSION' ) ) {
	return;
}

/**
 * Get email service instance
 *
 * @return \ApolloEmail\UnifiedEmailService
 */
function apollo_email_service() {
	return \ApolloEmail\UnifiedEmailService::get_instance();
}

/**
 * Send an email
 *
 * @param string $to      Recipient email address.
 * @param string $subject Email subject.
 * @param string $body    Email body (HTML).
 * @param array  $args    Optional arguments (priority, template, etc.).
 *
 * @return bool True on success, false on failure.
 */
function apollo_send_email( string $to, string $subject, string $body, array $args = [] ): bool {
	return apollo_email_service()->send( $to, $subject, $body, $args );
}

/**
 * Queue an email for background sending
 *
 * @param array $email_data Email data (recipient_email, subject, body, priority, etc.).
 *
 * @return int|false Queue ID on success, false on failure.
 */
function apollo_queue_email( array $email_data ) {
	return \ApolloEmail\Queue\QueueManager::get_instance()->enqueue( $email_data );
}

/**
 * Render an email template
 *
 * @param string $template_slug Template slug.
 * @param array  $data          Template data (placeholders).
 *
 * @return string Rendered template HTML.
 */
function apollo_render_email_template( string $template_slug, array $data = [] ): string {
	return \ApolloEmail\Templates\TemplateManager::get_instance()->render( $template_slug, $data );
}

/**
 * Get user email preferences
 *
 * @param int $user_id User ID.
 *
 * @return array User email preferences.
 */
function apollo_get_email_preferences( int $user_id ): array {
	return \ApolloEmail\Preferences\PreferenceManager::get_instance()->get_user_preferences( $user_id );
}
