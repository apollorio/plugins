<?php
/**
 * Apollo Push Notifications - Legacy Wrapper
 *
 * This file has been replaced by class-apollo-native-push.php which provides
 * a fully self-contained Web Push implementation with NO external plugin dependencies.
 *
 * @package Apollo_Core
 * @since   1.0.0
 * @deprecated Use Apollo_Core\Native_Push instead.
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Push_Notifications
 *
 * @deprecated This class is now a legacy wrapper. The actual implementation is in Native_Push.
 *             This wrapper exists to maintain backward compatibility.
 */
class Push_Notifications {

	/**
	 * Initialize hooks.
	 *
	 * Redirects to native implementation.
	 */
	public static function init(): void {
		// Native_Push is now the primary implementation.
		// No external plugins required.
		// This wrapper is kept for backward compatibility only.

		// Include native push if not already loaded.
		$native_push_file = __DIR__ . '/class-apollo-native-push.php';
		if ( file_exists( $native_push_file ) && ! class_exists( 'Apollo_Core\Native_Push' ) ) {
			require_once $native_push_file;
		}

		// Note: Native_Push::init() is called automatically at the end of its file.
	}

	/**
	 * Send push notification.
	 *
	 * Redirects to native implementation.
	 *
	 * @param string $title   Notification title.
	 * @param string $message Notification body.
	 * @param string $url     Click URL.
	 * @return bool
	 */
	public static function send_notification( string $title, string $message, string $url = '' ): bool {
		if ( class_exists( 'Apollo_Core\Native_Push' ) ) {
			$result = Native_Push::send_notification( $title, $message, $url );
			return $result['sent'] > 0;
		}

		return false;
	}

	/**
	 * Notify about new event.
	 *
	 * Redirects to native implementation.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function notify_new_event( $post_id, $post ): void {
		if ( class_exists( 'Apollo_Core\Native_Push' ) ) {
			Native_Push::notify_new_event( $post_id, $post );
		}
	}

	/**
	 * Notify about new document.
	 *
	 * Redirects to native implementation.
	 *
	 * @param int   $document_id Document ID.
	 * @param array $document    Document data.
	 */
	public static function notify_new_document( $document_id, $document ): void {
		if ( class_exists( 'Apollo_Core\Native_Push' ) ) {
			Native_Push::notify_new_document( $document_id, $document );
		}
	}
}

// Note: No init() call here - Native_Push handles everything.
