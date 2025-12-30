<?php
/**
 * REST Security Handler - Centralized nonce, capability, and rate limiting
 *
 * @package Apollo\Api
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Api;

use WP_Error;
use WP_REST_Request;

/**
 * RestSecurity - Enforces auth, nonce, caps, and rate limits
 */
final class RestSecurity {

	const NONCE_ACTION = 'apollo_rest_nonce';
	const NONCE_HEADER = 'X-WP-Nonce';

	/**
	 * Verify REST request authentication and nonce
	 *
	 * Usage in permission_callback:
	 *   'permission_callback' => fn($r) => RestSecurity::verify($r, 'apollo_post_activity')
	 *
	 * @param WP_REST_Request $request REST request
	 * @param string          $capability Optional capability to check
	 * @return true|WP_Error
	 */
	public static function verify( WP_REST_Request $request, string $capability = '' ) {
		// 1. Check user is logged in
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'rest_not_logged_in', 'You must be logged in.', array( 'status' => 401 ) );
		}

		// 2. Verify nonce (only for POST/PUT/PATCH/DELETE)
		$method = $request->get_method();
		if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			$nonce = $request->get_header( self::NONCE_HEADER );
			if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				return new WP_Error( 'rest_invalid_nonce', 'Invalid nonce.', array( 'status' => 403 ) );
			}
		}

		// 3. Check capability (if provided)
		if ( ! empty( $capability ) && ! user_can( $user_id, $capability ) ) {
			return new WP_Error( 'rest_forbidden', 'You do not have permission for this action.', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Rate limit an action per user+group
	 *
	 * @param int    $user_id User ID
	 * @param string $action Action name (e.g., 'invite', 'join')
	 * @param int    $group_id Group ID
	 * @param int    $limit_per_hour Max attempts per hour
	 * @return true|WP_Error
	 */
	public static function rateLimitByUserGroup( int $user_id, string $action, int $group_id, int $limit_per_hour = 10 ): bool {
		$transient_key = "apollo_rate_{$action}_{$user_id}_{$group_id}";
		$count = (int) get_transient( $transient_key );

		if ( $count >= $limit_per_hour ) {
			return new WP_Error(
				'rate_limit_exceeded',
				sprintf( 'Too many %s attempts. Please wait before trying again.', $action ),
				array( 'status' => 429 )
			);
		}

		// Increment and set transient (1 hour expiry)
		set_transient( $transient_key, $count + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Check if member endpoint can expose full list
	 * (avoid leaking complete member list to unauthorized users)
	 *
	 * @param WP_REST_Request $request Request
	 * @param int             $group_id Group ID
	 * @return true|WP_Error
	 */
	public static function canViewMembers( WP_REST_Request $request, int $group_id ) {
		global $wpdb;

		$user_id = get_current_user_id();

		// Unauthenticated: deny
		if ( ! $user_id ) {
			return new WP_Error( 'rest_not_logged_in', 'You must be logged in.', array( 'status' => 401 ) );
		}

		// Check if user is group member
		$is_member = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}apollo_group_members
			WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		) );

		if ( ! $is_member ) {
			return new WP_Error( 'rest_forbidden', 'Only group members can view member lists.', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Validate and sanitize invite data
	 *
	 * @param array $data POST data
	 * @return array|WP_Error
	 */
	public static function validateInviteData( array $data ) {
		$user_id = absint( $data['user_id'] ?? 0 );
		$group_id = absint( $data['group_id'] ?? 0 );

		if ( ! $user_id || ! $group_id ) {
			return new WP_Error( 'missing_params', 'user_id and group_id are required.' );
		}

		$invited = get_user_by( 'id', $user_id );
		if ( ! $invited ) {
			return new WP_Error( 'user_not_found', 'User does not exist.' );
		}

		return array( 'user_id' => $user_id, 'group_id' => $group_id );
	}

	/**
	 * Get nonce for frontend use
	 *
	 * @return string
	 */
	public static function getNonce(): string {
		return wp_create_nonce( self::NONCE_ACTION );
	}
}
