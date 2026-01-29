<?php
/**
 * Template Manager
 *
 * Manages email templates.
 *
 * @package ApolloEmail\Templates
 */

declare(strict_types=1);

namespace ApolloEmail\Templates;

/**
 * Template Manager Class
 */
class TemplateManager {

	/**
	 * Instance
	 *
	 * @var TemplateManager|null
	 */
	private static ?TemplateManager $instance = null;

	/**
	 * Get instance
	 *
	 * @return TemplateManager
	 */
	public static function get_instance(): TemplateManager {
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
	 * Render email template
	 *
	 * @param string $template_slug Template slug.
	 * @param array  $data          Template data (placeholders).
	 *
	 * @return string Rendered HTML.
	 */
	public function render( string $template_slug, array $data = [] ): string {
		// Apply filters.
		$data = apply_filters( 'apollo_email/template/placeholders', $data, $template_slug );

		// Get template content.
		$template_content = $this->get_template_content( $template_slug );

		// Replace placeholders.
		$rendered = $this->replace_placeholders( $template_content, $data );

		// Apply filter.
		$rendered = apply_filters( 'apollo_email/template/rendered', $rendered, $template_slug, $data );

		return $rendered;
	}

	/**
	 * Get template content
	 *
	 * @param string $template_slug Template slug.
	 *
	 * @return string Template HTML.
	 */
	private function get_template_content( string $template_slug ): string {
		// Try to load from CPT first.
		$template_post = get_page_by_path( $template_slug, OBJECT, 'email_template' );

		if ( $template_post ) {
			return $template_post->post_content;
		}

		// Fallback to file-based templates.
		$template_file = APOLLO_EMAIL_PLUGIN_DIR . 'templates/' . $template_slug . '.php';

		if ( file_exists( $template_file ) ) {
			ob_start();
			include $template_file;
			return ob_get_clean();
		}

		// Default template.
		return $this->get_default_template();
	}

	/**
	 * Replace placeholders in template
	 *
	 * @param string $content Template content.
	 * @param array  $data    Data array.
	 *
	 * @return string Content with replaced placeholders.
	 */
	private function replace_placeholders( string $content, array $data ): string {
		// Standard placeholders.
		$placeholders = [
			'[site-name]'  => get_bloginfo( 'name' ),
			'[site-url]'   => get_site_url(),
			'[admin-email]' => get_option( 'admin_email' ),
			'[current-year]' => gmdate( 'Y' ),
		];

		// Merge with provided data.
		foreach ( $data as $key => $value ) {
			$placeholders[ '[' . $key . ']' ] = $value;
		}

		// Replace.
		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
	}

	/**
	 * Get template subject
	 *
	 * @param string $template_slug Template slug.
	 * @param array  $data          Data.
	 *
	 * @return string Subject line.
	 */
	public function get_subject( string $template_slug, array $data = [] ): string {
		// Try to get from CPT.
		$template_post = get_page_by_path( $template_slug, OBJECT, 'email_template' );

		if ( $template_post ) {
			$subject = get_post_meta( $template_post->ID, '_email_subject', true );
			if ( $subject ) {
				return $this->replace_placeholders( $subject, $data );
			}
		}

		// Default subject.
		return get_bloginfo( 'name' ) . ' - Notification';
	}

	/**
	 * Get default template
	 *
	 * @return string Default HTML template.
	 */
	private function get_default_template(): string {
		return '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>[site-name]</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			background-color: #f4f4f4;
			margin: 0;
			padding: 0;
		}
		.email-container {
			max-width: 600px;
			margin: 20px auto;
			background: white;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		}
		.email-header {
			background: #007cba;
			color: white;
			padding: 20px;
			text-align: center;
		}
		.email-body {
			padding: 30px;
			line-height: 1.6;
			color: #333;
		}
		.email-footer {
			background: #f9f9f9;
			padding: 20px;
			text-align: center;
			font-size: 12px;
			color: #666;
		}
		.button {
			display: inline-block;
			padding: 12px 24px;
			background: #007cba;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			margin: 10px 0;
		}
	</style>
</head>
<body>
	<div class="email-container">
		<div class="email-header">
			<h1>[site-name]</h1>
		</div>
		<div class="email-body">
			<!-- Email content goes here -->
			<p>This is a test email from Apollo Email Service.</p>
		</div>
		<div class="email-footer">
			<p>&copy; [current-year] [site-name]. All rights reserved.</p>
			<p><a href="[site-url]">Visit our website</a></p>
		</div>
	</div>
</body>
</html>
		';
	}
}
