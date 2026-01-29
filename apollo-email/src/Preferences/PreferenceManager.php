<?php
/**
 * Email Preference Manager
 *
 * Manages user email notification preferences.
 *
 * @package ApolloEmail\Preferences
 */

declare(strict_types=1);

namespace ApolloEmail\Preferences;

/**
 * Preference Manager Class
 */
class PreferenceManager {

	/**
	 * Instance
	 *
	 * @var PreferenceManager|null
	 */
	private static ?PreferenceManager $instance = null;

	/**
	 * Get instance
	 *
	 * @return PreferenceManager
	 */
	public static function get_instance(): PreferenceManager {
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
	 * Get user email preferences
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array User preferences.
	 */
	public function get_user_preferences( int $user_id ): array {
		$defaults = [
			'notify_events'   => true,
			'notify_messages' => true,
			'notify_docs'     => true,
			'digest_enabled'  => false,
			'digest_frequency' => 'weekly',
		];

		$preferences = get_user_meta( $user_id, '_apollo_email_prefs', true );

		if ( ! is_array( $preferences ) ) {
			$preferences = [];
		}

		return wp_parse_args( $preferences, $defaults );
	}

	/**
	 * Update user email preferences
	 *
	 * @param int   $user_id User ID.
	 * @param array $preferences Preferences array.
	 *
	 * @return bool True on success.
	 */
	public function update_user_preferences( int $user_id, array $preferences ): bool {
		return update_user_meta( $user_id, '_apollo_email_prefs', $preferences );
	}

	/**
	 * Check if user wants notification type
	 *
	 * @param int    $user_id User ID.
	 * @param string $notification_type Notification type.
	 *
	 * @return bool True if enabled.
	 */
	public function user_wants_notification( int $user_id, string $notification_type ): bool {
		$preferences = $this->get_user_preferences( $user_id );
		$key = 'notify_' . $notification_type;

		return isset( $preferences[ $key ] ) && $preferences[ $key ];
	}
}
