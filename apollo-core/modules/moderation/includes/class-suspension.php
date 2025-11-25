<?php
declare(strict_types=1);

/**
 * User Suspension and Blocking
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suspension class
 */
class Apollo_Moderation_Suspension {
	/**
	 * Initialize
	 */
	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'check_suspension' ), 30, 3 );
		add_action( 'wp_login', array( __CLASS__, 'clear_expired_suspension' ), 10, 2 );
	}

	/**
	 * Check if user is suspended or blocked
	 *
	 * @param WP_User|WP_Error|null $user User object.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error
	 */
	public static function check_suspension( $user, $username, $password ) {
		if ( is_a( $user, 'WP_User' ) ) {
			// Check if blocked.
			$blocked = get_user_meta( $user->ID, '_apollo_blocked', true );
			if ( $blocked ) {
				return new WP_Error(
					'blocked',
					__( 'Your account has been blocked by an administrator. Please contact support.', 'apollo-core' ),
					array( 'status' => 403 )
				);
			}

			// Check if suspended.
			$suspended_until = get_user_meta( $user->ID, '_apollo_suspended_until', true );
			if ( $suspended_until && time() < intval( $suspended_until ) ) {
				return new WP_Error(
					'suspended',
					sprintf(
						/* translators: %s: Date until suspended */
						__( 'Your account is suspended until %s.', 'apollo-core' ),
						gmdate( 'Y-m-d H:i:s', intval( $suspended_until ) )
					),
					array( 'status' => 403 )
				);
			}
		}

		return $user;
	}

	/**
	 * Clear expired suspension on login
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user User object.
	 */
	public static function clear_expired_suspension( $user_login, $user ) {
		$suspended_until = get_user_meta( $user->ID, '_apollo_suspended_until', true );
		if ( $suspended_until && time() >= intval( $suspended_until ) ) {
			delete_user_meta( $user->ID, '_apollo_suspended_until' );
		}
	}

	/**
	 * Suspend user
	 *
	 * @param int    $user_id User ID.
	 * @param int    $days Days to suspend.
	 * @param string $reason Reason for suspension.
	 * @param int    $actor_id Actor user ID.
	 * @return bool
	 */
	public static function suspend_user( $user_id, $days, $reason = '', $actor_id = null ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		// Check permission.
		if ( ! user_can( $actor_id, 'suspend_users' ) ) {
			return false;
		}

		$until = time() + ( $days * DAY_IN_SECONDS );
		update_user_meta( $user_id, '_apollo_suspended_until', $until );

		// Log action.
		Apollo_Moderation_Audit_Log::log_action(
			$actor_id,
			'suspend_user',
			'user',
			$user_id,
			array(
				'days'   => $days,
				'until'  => gmdate( 'Y-m-d H:i:s', $until ),
				'reason' => $reason,
			)
		);

		return true;
	}

	/**
	 * Block user
	 *
	 * @param int    $user_id User ID.
	 * @param string $reason Reason for block.
	 * @param int    $actor_id Actor user ID.
	 * @return bool
	 */
	public static function block_user( $user_id, $reason = '', $actor_id = null ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		// Check permission.
		if ( ! user_can( $actor_id, 'block_users' ) ) {
			return false;
		}

		update_user_meta( $user_id, '_apollo_blocked', 1 );

		// Log action.
		Apollo_Moderation_Audit_Log::log_action(
			$actor_id,
			'block_user',
			'user',
			$user_id,
			array( 'reason' => $reason )
		);

		return true;
	}

	/**
	 * Unblock user
	 *
	 * @param int $user_id User ID.
	 * @param int $actor_id Actor user ID.
	 * @return bool
	 */
	public static function unblock_user( $user_id, $actor_id = null ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		// Check permission.
		if ( ! user_can( $actor_id, 'block_users' ) ) {
			return false;
		}

		delete_user_meta( $user_id, '_apollo_blocked' );

		// Log action.
		Apollo_Moderation_Audit_Log::log_action(
			$actor_id,
			'unblock_user',
			'user',
			$user_id,
			array()
		);

		return true;
	}
}

