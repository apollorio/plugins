<?php

namespace Apollo\Modules\Registration;

/**
 * Registration Routes
 * Handles custom registration page routing
 */
class RegistrationRoutes {

	public function register(): void {
		// Override default registration URL
		add_filter( 'register_url', array( $this, 'customRegistrationUrl' ), 10, 1 );

		// Add custom registration page template
		add_action( 'template_redirect', array( $this, 'handleRegistrationPage' ), 1 );
	}

	/**
	 * Custom registration URL
	 */
	public function customRegistrationUrl( string $register_url ): string {
		// Use custom registration page if exists
		$registration_page = get_page_by_path( 'registro' );
		if ( $registration_page && $registration_page->post_status === 'publish' ) {
			return get_permalink( $registration_page->ID );
		}

		// Fallback to default WordPress registration
		return $register_url;
	}

	/**
	 * Handle custom registration page
	 */
	public function handleRegistrationPage(): void {
		// Don't interfere with WordPress core functionality
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() ) {
			return;
		}

		global $wp_query;

		// Check if it's the registration page
		if ( is_page( 'registro' ) || ( isset( $wp_query->query_vars['pagename'] ) && $wp_query->query_vars['pagename'] === 'registro' ) ) {
			$template = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/registration/registration-form.php';
			if ( file_exists( $template ) ) {
				include $template;
				exit;
			}
		}
	}
}
