<?php
/**
 * Backward Compatibility Layer
 *
 * This file provides backward compatibility for old code that references
 * email classes in apollo-social/apollo-core plugins.
 *
 * It creates class aliases pointing to the new apollo-email plugin classes.
 *
 * @package Apollo\Social
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only create aliases if apollo-email plugin is active.
if ( ! defined( 'APOLLO_EMAIL_VERSION' ) ) {
	// Apollo Email plugin not active - show admin notice.
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Apollo Social', 'apollo-social' ); ?>:</strong>
					<?php esc_html_e( 'Email functionality requires Apollo Email plugin. Please activate it.', 'apollo-social' ); ?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * Create class aliases for backward compatibility
 *
 * Old namespace: Apollo\Email
 * New namespace: ApolloEmail
 */

// UnifiedEmailService.
if ( class_exists( 'ApolloEmail\UnifiedEmailService' ) && ! class_exists( 'Apollo\Email\UnifiedEmailService' ) ) {
	class_alias( 'ApolloEmail\UnifiedEmailService', 'Apollo\Email\UnifiedEmailService' );
}

// Queue classes.
if ( class_exists( 'ApolloEmail\Queue\QueueManager' ) && ! class_exists( 'Apollo\Modules\Email\EmailQueueManager' ) ) {
	class_alias( 'ApolloEmail\Queue\QueueManager', 'Apollo\Modules\Email\EmailQueueManager' );
}

if ( class_exists( 'ApolloEmail\Queue\QueueProcessor' ) && ! class_exists( 'Apollo\Modules\Email\EmailQueueProcessor' ) ) {
	class_alias( 'ApolloEmail\Queue\QueueProcessor', 'Apollo\Modules\Email\EmailQueueProcessor' );
}

// Template classes.
if ( class_exists( 'ApolloEmail\Templates\TemplateManager' ) && ! class_exists( 'Apollo\Email\TemplateManager' ) ) {
	class_alias( 'ApolloEmail\Templates\TemplateManager', 'Apollo\Email\TemplateManager' );
}

// Security classes.
if ( class_exists( 'ApolloEmail\Security\SecurityLogger' ) && ! class_exists( 'Apollo\Security\EmailSecurityLog' ) ) {
	class_alias( 'ApolloEmail\Security\SecurityLogger', 'Apollo\Security\EmailSecurityLog' );
}

// Admin classes.
if ( class_exists( 'ApolloEmail\Admin\EmailHubAdmin' ) && ! class_exists( 'Apollo\Admin\EmailHubAdmin' ) ) {
	class_alias( 'ApolloEmail\Admin\EmailHubAdmin', 'Apollo\Admin\EmailHubAdmin' );
}

// Preference classes.
if ( class_exists( 'ApolloEmail\Preferences\PreferenceManager' ) && ! class_exists( 'Apollo\Email\PreferenceManager' ) ) {
	class_alias( 'ApolloEmail\Preferences\PreferenceManager', 'Apollo\Email\PreferenceManager' );
}

/**
 * Add deprecation notices for direct usage of old classes
 */
add_action(
	'init',
	function () {
		// Log deprecation warning if WP_DEBUG_LOG is enabled.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// Check if any old namespace classes are being instantiated.
			if ( class_exists( 'Apollo\Email\UnifiedEmailService' ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					'Apollo\Email\UnifiedEmailService is deprecated. Use ApolloEmail\UnifiedEmailService instead.',
					E_USER_DEPRECATED
				);
			}
		}
	},
	5
);

// Log compatibility layer activation.
if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Apollo Email compatibility layer loaded - old Apollo\Email namespace aliased to ApolloEmail' );
}
