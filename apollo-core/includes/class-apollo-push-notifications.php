<?php
/**
 * Apollo Push Notifications Integration
 *
 * Integrates with "Push Notifications for WP" plugin.
 * Sends push notifications when new events or documents are published.
 *
 * @package Apollo_Core
 * @since   1.0.0
 *
 * SAFETY NOTES:
 * - Only activates if a compatible push plugin is detected
 * - Validates all data before sending notifications
 * - Uses meta flags to prevent duplicate notifications
 * - Logs errors for debugging without exposing to users
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Push_Notifications
 *
 * @tooltip This class provides push notification integration for Apollo.
 *          It requires either "Push Notifications for WP" or "JESWPN Push Notifications" plugin.
 */
class Apollo_Push_Notifications {

	/** @var bool Flag indicating if push plugin is available. */
	private static bool $plugin_available = false;

	/** @var string Which push plugin is active. */
	private static string $active_plugin = '';

	/**
	 * Initialize hooks.
	 *
	 * @tooltip Checks for compatible push notification plugins before registering hooks.
	 *          Will silently skip if no compatible plugin is found.
	 */
	public static function init(): void {
		// Detect which push plugin is available.
		self::detect_push_plugin();

		// Only proceed if a compatible plugin is active.
		if ( ! self::$plugin_available ) {
			// Add admin notice only on plugin page.
			add_action( 'admin_notices', array( __CLASS__, 'maybe_show_plugin_notice' ) );
			return;
		}

		// Register hooks for sending notifications.
		add_action( 'publish_event_listing', array( __CLASS__, 'notify_new_event' ), 10, 2 );
		add_action( 'apollo_document_published', array( __CLASS__, 'notify_new_document' ), 10, 2 );

		// Add admin status indicator.
		add_action( 'admin_notices', array( __CLASS__, 'show_status_notice' ) );
	}

	/**
	 * Detect which push notification plugin is available.
	 */
	private static function detect_push_plugin(): void {
		if ( function_exists( 'pnfw_send_notification' ) ) {
			self::$plugin_available = true;
			self::$active_plugin    = 'pnfw';
		} elseif ( class_exists( 'JESWPN_Push_Notifications' ) ) {
			self::$plugin_available = true;
			self::$active_plugin    = 'jeswpn';
		}
	}

	/**
	 * Show admin notice about push plugin status (only on Apollo pages).
	 */
	public static function show_status_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'apollo' ) === false ) {
			return;
		}

		if ( self::$plugin_available ) {
			$plugin_name = ( 'pnfw' === self::$active_plugin ) ? 'Push Notifications for WP' : 'JESWPN Push Notifications';
			echo '<div class="notice notice-info is-dismissible apollo-push-status">';
			echo '<p><span class="dashicons dashicons-bell" style="color:#0073aa;"></span> ';
			printf(
				/* translators: %s: plugin name */
				esc_html__( 'Apollo Push Notifications: Active via %s', 'apollo-core' ),
				'<strong>' . esc_html( $plugin_name ) . '</strong>'
			);
			echo ' <span class="apollo-tooltip" title="' . esc_attr__( 'Push notifications will be sent when new events are published.', 'apollo-core' ) . '">ℹ️</span>';
			echo '</p></div>';
		}
	}

	/**
	 * Show notice if no push plugin is available (only on plugins page).
	 */
	public static function maybe_show_plugin_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p><span class="dashicons dashicons-bell" style="color:#ffb900;"></span> ';
		echo '<strong>' . esc_html__( 'Apollo Push Notifications:', 'apollo-core' ) . '</strong> ';
		echo esc_html__( 'To enable push notifications, install "Push Notifications for WP" plugin.', 'apollo-core' );
		echo ' <a href="' . esc_url( admin_url( 'plugin-install.php?s=push+notifications+for+wp&tab=search&type=term' ) ) . '">';
		echo esc_html__( 'Install Now', 'apollo-core' ) . '</a>';
		echo '</p></div>';
	}

	/**
	 * Send push notification when a new event is published.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @tooltip Validates post data before sending. Skips if already notified.
	 */
	public static function notify_new_event( $post_id, $post ): void {
		// SAFETY: Validate inputs.
		if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
			self::log_error( 'Invalid post_id for event notification', array( 'post_id' => $post_id ) );
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			self::log_error( 'Invalid post object for event notification', array( 'post_id' => $post_id ) );
			return;
		}

		// SAFETY: Verify post type.
		if ( 'event_listing' !== $post->post_type ) {
			return;
		}

		// SAFETY: Skip revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// SAFETY: Avoid duplicate notifications on updates.
		if ( get_post_meta( $post_id, '_apollo_push_sent', true ) ) {
			return;
		}

		// SAFETY: Validate title exists.
		$title = ! empty( $post->post_title ) ? $post->post_title : __( 'New Event', 'apollo-core' );
		$title = sprintf( __( 'New Event: %s', 'apollo-core' ), sanitize_text_field( $title ) );

		// SAFETY: Sanitize content for message.
		$content = ! empty( $post->post_content ) ? wp_strip_all_tags( $post->post_content ) : '';
		$message = ! empty( $content ) ? wp_trim_words( $content, 20, '...' ) : __( 'Check out this new event!', 'apollo-core' );

		// SAFETY: Validate URL.
		$url = get_permalink( $post_id );
		if ( ! $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$url = home_url( '/events/' );
		}

		$result = self::send_notification( $title, $message, $url );

		if ( $result ) {
			update_post_meta( $post_id, '_apollo_push_sent', current_time( 'mysql' ) );
		}
	}

	/**
	 * Send push notification when a new document is published.
	 *
	 * @param int   $document_id Document ID.
	 * @param array $document    Document data.
	 *
	 * @tooltip Validates document data array before sending notification.
	 */
	public static function notify_new_document( $document_id, $document ): void {
		// SAFETY: Validate document_id.
		if ( ! is_numeric( $document_id ) || $document_id <= 0 ) {
			self::log_error( 'Invalid document_id for notification', array( 'document_id' => $document_id ) );
			return;
		}

		// SAFETY: Validate document is array.
		if ( ! is_array( $document ) ) {
			self::log_error( 'Invalid document data for notification', array( 'document_id' => $document_id ) );
			return;
		}

		// SAFETY: Sanitize title with fallback.
		$doc_title = isset( $document['title'] ) && ! empty( $document['title'] )
			? sanitize_text_field( $document['title'] )
			: __( 'Untitled Document', 'apollo-core' );

		$title   = sprintf( __( 'New Document: %s', 'apollo-core' ), $doc_title );
		$message = __( 'A new document has been published.', 'apollo-core' );

		// SAFETY: Build and validate URL.
		$url = home_url( '/doc/' . absint( $document_id ) );

		self::send_notification( $title, $message, $url );
	}

	/**
	 * Send notification via available push plugin.
	 *
	 * @param string $title   Notification title.
	 * @param string $message Notification body.
	 * @param string $url     Click URL.
	 * @return bool True on success, false on failure.
	 *
	 * @tooltip Wraps notification sending in try-catch to prevent fatal errors.
	 */
	private static function send_notification( string $title, string $message, string $url ): bool {
		// SAFETY: Final validation of all inputs.
		if ( empty( $title ) || empty( $message ) ) {
			self::log_error( 'Empty title or message for notification', compact( 'title', 'message', 'url' ) );
			return false;
		}

		// SAFETY: Ensure URL is valid.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$url = home_url();
		}

		try {
			// Push Notifications for WP (pnfw).
			if ( 'pnfw' === self::$active_plugin && function_exists( 'pnfw_send_notification' ) ) {
				pnfw_send_notification(
					array(
						'title'   => $title,
						'message' => $message,
						'url'     => $url,
					)
				);
				return true;
			}

			// JESWPN Push Notifications alternative.
			if ( 'jeswpn' === self::$active_plugin && class_exists( 'JESWPN_Push_Notifications' ) ) {
				JESWPN_Push_Notifications::send(
					array(
						'title' => $title,
						'body'  => $message,
						'url'   => $url,
					)
				);
				return true;
			}
		} catch ( Exception $e ) {
			self::log_error( 'Push notification send failed', array( 'error' => $e->getMessage() ) );
			return false;
		}

		return false;
	}

	/**
	 * Log errors for debugging (only in debug mode).
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context.
	 */
	private static function log_error( string $message, array $context = array() ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log(
				sprintf(
					'[Apollo Push Notifications] %s | Context: %s',
					$message,
					wp_json_encode( $context )
				)
			);
		}
	}

	/**
	 * Check if push notifications are available.
	 *
	 * @return bool True if a push plugin is active.
	 *
	 * @tooltip Use this method to check availability before relying on push features.
	 */
	public static function is_available(): bool {
		return self::$plugin_available;
	}

	/**
	 * Get active plugin name.
	 *
	 * @return string Plugin identifier or empty string.
	 */
	public static function get_active_plugin(): string {
		return self::$active_plugin;
	}
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', array( 'Apollo_Push_Notifications', 'init' ) );
