<?php

declare(strict_types=1);

/**
 * Apollo Email Service - Centralized Email Sending
 *
 * Modular, non-WooCommerce email service for Apollo ecosystem.
 * Handles template loading, variable replacement, and email sending.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */
class Apollo_Email_Service {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Send email with template support
	 *
	 * @param array $message_data Message data array.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send( array $message_data ) {
		// Validate required fields.
		if ( empty( $message_data['to'] ) || ! is_email( $message_data['to'] ) ) {
			return new WP_Error( 'invalid_recipient', __( 'Invalid recipient email address', 'apollo-core' ) );
		}

		// Load template if flow specified.
		if ( ! empty( $message_data['flow'] ) ) {
			$flow_config = $this->get_flow_config( $message_data['flow'] );
			if ( is_wp_error( $flow_config ) ) {
				return $flow_config;
			}

			// Merge flow config with message data.
			$message_data = array_merge( $flow_config, $message_data );
		}

		// Process template if provided.
		if ( ! empty( $message_data['template_slug'] ) ) {
			$template_html = $this->load_template( $message_data['template_slug'], $message_data['variables'] ?? array() );
			if ( is_wp_error( $template_html ) ) {
				return $template_html;
			}
			$message_data['body_html'] = $template_html;
		}

		// Replace variables in subject and body.
		if ( ! empty( $message_data['variables'] ) ) {
			$message_data['subject'] = $this->replace_variables( $message_data['subject'] ?? '', $message_data['variables'] );
			if ( ! empty( $message_data['body_html'] ) ) {
				$message_data['body_html'] = $this->replace_variables( $message_data['body_html'], $message_data['variables'] );
			}
			if ( ! empty( $message_data['body_text'] ) ) {
				$message_data['body_text'] = $this->replace_variables( $message_data['body_text'], $message_data['variables'] );
			}
		}

		// Ensure HTML body exists.
		if ( empty( $message_data['body_html'] ) && ! empty( $message_data['body_text'] ) ) {
			$message_data['body_html'] = wpautop( esc_html( $message_data['body_text'] ) );
		}

		// Prepare headers.
		$headers = $message_data['headers'] ?? array();
		if ( ! in_array( 'Content-Type: text/html; charset=UTF-8', $headers, true ) ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		// Send email.
		$result = wp_mail(
			$message_data['to'],
			$message_data['subject'] ?? '',
			$message_data['body_html'] ?? '',
			$headers,
			$message_data['attachments'] ?? array()
		);

		// Log result.
		$this->log_email_sent( $message_data, $result );

		// Fire action.
		do_action( 'apollo_email_sent', $message_data, $result );

		return $result ? true : new WP_Error( 'send_failed', __( 'Failed to send email', 'apollo-core' ) );
	}

	/**
	 * Load email template
	 *
	 * @param string $template_slug Template slug or ID.
	 * @param array  $variables     Variables to replace.
	 * @return string|WP_Error Template HTML or error.
	 */
	public function load_template( string $template_slug, array $variables = array() ): string|WP_Error {
		// Try to load from CPT first.
		$template_post = get_page_by_path( $template_slug, OBJECT, 'apollo_email_template' );
		if ( ! $template_post && is_numeric( $template_slug ) ) {
			$template_post = get_post( (int) $template_slug );
		}

		if ( $template_post && $template_post->post_type === 'apollo_email_template' ) {
			$html = $template_post->post_content;
		} else {
			// Fallback to default templates.
			$html = $this->get_default_template( $template_slug );
			if ( empty( $html ) ) {
				return new WP_Error( 'template_not_found', __( 'Template not found', 'apollo-core' ) );
			}
		}

		// Replace variables.
		return $this->replace_variables( $html, $variables );
	}

	/**
	 * Get flow configuration
	 *
	 * @param string $flow_slug Flow slug.
	 * @return array|WP_Error Flow config or error.
	 */
	public function get_flow_config( string $flow_slug ): array|WP_Error {
		$flows = get_option( 'apollo_email_flows', array() );

		if ( ! isset( $flows[ $flow_slug ] ) ) {
			return new WP_Error( 'flow_not_found', __( 'Email flow not configured', 'apollo-core' ) );
		}

		$flow = $flows[ $flow_slug ];

		// Check if flow is enabled.
		if ( empty( $flow['enabled'] ) ) {
			return new WP_Error( 'flow_disabled', __( 'Email flow is disabled', 'apollo-core' ) );
		}

		return $flow;
	}

	/**
	 * Replace variables in text
	 *
	 * @param string $text      Text with variables.
	 * @param array  $variables Variable values.
	 * @return string Text with variables replaced.
	 */
	private function replace_variables( string $text, array $variables ): string {
		foreach ( $variables as $key => $value ) {
			// Support both {{key}} and [key] formats.
			$text = str_replace( '{{' . $key . '}}', esc_html( $value ), $text );
			$text = str_replace( '[' . $key . ']', esc_html( $value ), $text );
		}

		return $text;
	}

	/**
	 * Get default template (fallback)
	 *
	 * @param string $template_slug Template slug.
	 * @return string Template HTML.
	 */
	private function get_default_template( string $template_slug ): string {
		$defaults = array(
			'registration_confirm' => $this->get_default_registration_template(),
			'producer_notify'      => $this->get_default_producer_template(),
			'general'              => $this->get_default_general_template(),
		);

		return $defaults[ $template_slug ] ?? '';
	}

	/**
	 * Get default registration template
	 *
	 * @return string
	 */
	private function get_default_registration_template(): string {
		return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
	<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
		<h1 style="color: #f97316;">Bem-vindo ao Apollo::Rio!</h1>
		<p>Olá {{user_name}},</p>
		<p>Obrigado por se registrar. Clique no link abaixo para confirmar sua conta:</p>
		<p><a href="{{confirm_url}}" style="background: #f97316; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Confirmar Conta</a></p>
		<p>Se você não criou esta conta, pode ignorar este email.</p>
		<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
		<p style="font-size: 12px; color: #999;">{{site_name}} - Sistema Apollo</p>
	</div>
</body>
</html>
HTML;
	}

	/**
	 * Get default producer notification template
	 *
	 * @return string
	 */
	private function get_default_producer_template(): string {
		return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
	<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
		<h1 style="color: #f97316;">Novo Evento: {{event_title}}</h1>
		<p>Olá {{producer_name}},</p>
		<p>Um novo evento foi criado ou atualizado:</p>
		<ul>
			<li><strong>Evento:</strong> {{event_title}}</li>
			<li><strong>Data:</strong> {{event_date}}</li>
			<li><strong>Local:</strong> {{event_venue}}</li>
		</ul>
		<p><a href="{{event_url}}" style="background: #f97316; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Ver Evento</a></p>
		<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
		<p style="font-size: 12px; color: #999;">{{site_name}} - Sistema Apollo</p>
	</div>
</body>
</html>
HTML;
	}

	/**
	 * Get default general template
	 *
	 * @return string
	 */
	private function get_default_general_template(): string {
		return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
	<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
		{{content}}
		<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
		<p style="font-size: 12px; color: #999;">{{site_name}} - Sistema Apollo</p>
	</div>
</body>
</html>
HTML;
	}

	/**
	 * Send test email
	 *
	 * @param string $flow_slug  Flow slug.
	 * @param string $test_email Test email address.
	 * @return bool|WP_Error
	 */
	public function send_test( string $flow_slug, string $test_email ): bool|WP_Error {
		if ( ! is_email( $test_email ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid test email address', 'apollo-core' ) );
		}

		// Get sample variables for the flow.
		$sample_variables = $this->get_sample_variables( $flow_slug );

		return $this->send(
			array(
				'to'        => $test_email,
				'flow'      => $flow_slug,
				'variables' => $sample_variables,
			)
		);
	}

	/**
	 * Get sample variables for testing
	 *
	 * @param string $flow_slug Flow slug.
	 * @return array Sample variables.
	 */
	public function get_sample_variables( string $flow_slug ): array {
		$samples = array(
			'registration_confirm' => array(
				'user_name'   => 'João Silva',
				'confirm_url' => home_url( '/confirmar?token=test123' ),
				'site_name'   => get_bloginfo( 'name' ),
			),
			'producer_notify'      => array(
				'producer_name' => 'Maria Santos',
				'event_title'   => 'Sunset Sessions Vol. 5',
				'event_date'    => 'Sábado, 25 de Março de 2024',
				'event_venue'   => 'Club Rio',
				'event_url'     => home_url( '/evento/sunset-sessions' ),
				'site_name'     => get_bloginfo( 'name' ),
			),
		);

		return $samples[ $flow_slug ] ?? array(
			'site_name' => get_bloginfo( 'name' ),
			'content'   => 'Este é um email de teste do sistema Apollo.',
		);
	}

	/**
	 * Log email sent
	 *
	 * @param array $message_data Message data.
	 * @param bool  $result       Send result.
	 * @return void
	 */
	private function log_email_sent( array $message_data, bool $result ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_data = array(
			'flow'      => $message_data['flow'] ?? 'manual',
			'to'        => $this->mask_email( $message_data['to'] ?? '' ),
			'subject'   => $message_data['subject'] ?? '',
			'success'   => $result,
			'timestamp' => current_time( 'mysql' ),
		);

		error_log( '[Apollo Email] ' . wp_json_encode( $log_data ) );
	}

	/**
	 * Mask email for logging
	 *
	 * @param string $email Email address.
	 * @return string Masked email.
	 */
	private function mask_email( string $email ): string {
		if ( ! is_email( $email ) ) {
			return 'invalid';
		}

		$parts = explode( '@', $email );
		if ( count( $parts ) !== 2 ) {
			return $email;
		}

		$local  = $parts[0];
		$domain = $parts[1];

		$masked_local = substr( $local, 0, 2 ) . '***';

		return $masked_local . '@' . $domain;
	}
}
