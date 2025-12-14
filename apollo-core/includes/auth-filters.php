<?php

declare(strict_types=1);

/**
 * Apollo Core - Authentication Filters
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if user is suspended or blocked during authentication
 *
 * @param WP_User|WP_Error|null $user     User object or WP_Error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error
 */
function apollo_check_user_suspension( $user, $username, $password ) {
	// Only check if we have a valid user.
	if ( ! is_a( $user, 'WP_User' ) ) {
		return $user;
	}

	// Check if user is blocked.
	$is_blocked = get_user_meta( $user->ID, '_apollo_blocked', true );
	if ( $is_blocked ) {
		$reason = get_user_meta( $user->ID, '_apollo_block_reason', true );

		return new WP_Error(
			'apollo_user_blocked',
			sprintf(
				/* translators: %s: block reason */
				__( 'Your account has been blocked. Reason: %s', 'apollo-core' ),
				$reason ? $reason : __( 'No reason provided', 'apollo-core' )
			)
		);
	}

	// Check if user is suspended.
	$suspended_until = get_user_meta( $user->ID, '_apollo_suspended_until', true );
	if ( $suspended_until && time() < intval( $suspended_until ) ) {
		$reason = get_user_meta( $user->ID, '_apollo_suspension_reason', true );

		return new WP_Error(
			'apollo_user_suspended',
			sprintf(
				/* translators: 1: suspension end date, 2: suspension reason */
				__( 'Your account is suspended until %1$s. Reason: %2$s', 'apollo-core' ),
				gmdate( 'Y-m-d H:i:s', intval( $suspended_until ) ),
				$reason ? $reason : __( 'No reason provided', 'apollo-core' )
			)
		);
	}

	// Clear expired suspension.
	if ( $suspended_until && time() >= intval( $suspended_until ) ) {
		delete_user_meta( $user->ID, '_apollo_suspended_until' );
		delete_user_meta( $user->ID, '_apollo_suspension_reason' );
	}

	return $user;
}
add_filter( 'authenticate', 'apollo_check_user_suspension', 30, 3 );

/**
 * Check if currently logged-in user is suspended
 * Logs them out if suspended
 */
function apollo_check_current_user_suspension() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();

	// Check if blocked.
	$is_blocked = get_user_meta( $user_id, '_apollo_blocked', true );
	if ( $is_blocked ) {
		wp_logout();
		wp_redirect( wp_login_url() . '?apollo_error=blocked' );
		exit;
	}

	// Check if suspended.
	$suspended_until = get_user_meta( $user_id, '_apollo_suspended_until', true );
	if ( $suspended_until && time() < intval( $suspended_until ) ) {
		wp_logout();
		wp_redirect( wp_login_url() . '?apollo_error=suspended' );
		exit;
	}

	// Clear expired suspension.
	if ( $suspended_until && time() >= intval( $suspended_until ) ) {
		delete_user_meta( $user_id, '_apollo_suspended_until' );
		delete_user_meta( $user_id, '_apollo_suspension_reason' );
	}
}
add_action( 'init', 'apollo_check_current_user_suspension', 1 );

/**
 * Display custom error messages on login page
 */
function apollo_login_error_messages() {
	if ( isset( $_GET['apollo_error'] ) ) {
		$error = sanitize_text_field( wp_unslash( $_GET['apollo_error'] ) );

		if ( 'blocked' === $error ) {
			return __( 'Your account has been blocked by an administrator.', 'apollo-core' );
		}

		if ( 'suspended' === $error ) {
			return __( 'Your account has been temporarily suspended.', 'apollo-core' );
		}
	}

	return '';
}
add_filter(
	'login_message',
	function ( $message ) {
		$apollo_message = apollo_login_error_messages();
		if ( $apollo_message ) {
			$message .= '<div class="message">' . esc_html( $apollo_message ) . '</div>';
		}

		return $message;
	}
);

/**
 * Check if user can perform action (helper function)
 *
 * @param int $user_id User ID.
 * @return bool True if user can perform actions, false if suspended/blocked.
 */
function apollo_user_can_perform_actions( $user_id = null ) {
	if ( is_null( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Check blocked.
	if ( get_user_meta( $user_id, '_apollo_blocked', true ) ) {
		return false;
	}

	// Check suspended.
	$suspended_until = get_user_meta( $user_id, '_apollo_suspended_until', true );
	if ( $suspended_until && time() < intval( $suspended_until ) ) {
		return false;
	}

	return true;
}

/**
 * Get user suspension status
 *
 * @param int $user_id User ID.
 * @return array Status information.
 */
function apollo_get_user_status( $user_id ) {
	$status = array(
		'is_blocked'      => false,
		'is_suspended'    => false,
		'suspended_until' => null,
		'block_reason'    => null,
		'suspend_reason'  => null,
	);

	// Check blocked.
	if ( get_user_meta( $user_id, '_apollo_blocked', true ) ) {
		$status['is_blocked']   = true;
		$status['block_reason'] = get_user_meta( $user_id, '_apollo_block_reason', true );
	}

	// Check suspended.
	$suspended_until = get_user_meta( $user_id, '_apollo_suspended_until', true );
	if ( $suspended_until && time() < intval( $suspended_until ) ) {
		$status['is_suspended']    = true;
		$status['suspended_until'] = gmdate( 'Y-m-d H:i:s', intval( $suspended_until ) );
		$status['suspend_reason']  = get_user_meta( $user_id, '_apollo_suspension_reason', true );
	}

	return $status;
}
