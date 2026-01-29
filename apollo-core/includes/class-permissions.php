<?php
/**
 * Permissions Helper
 *
 * Provides helper methods for checking user permissions and capabilities.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permissions class.
 */
class Permissions {

	/**
	 * Check if user can approve events
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_approve_events( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return user_can( $user_id, 'edit_others_posts' );
	}

	/**
	 * Check if user can access CENA RIO
	 *
	 * Uses standard WordPress contributor role (cena::rio label)
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_access_cena_rio( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Check capability.
		if ( user_can( $user_id, 'apollo_access_cena_rio' ) ) {
			return true;
		}

		// Check standard WordPress role: contributor (cena::rio)
		$user = get_userdata( $user_id );
		if ( $user && in_array( 'contributor', $user->roles, true ) ) {
			return true;
		}

		// Check if admin.
		return user_can( $user_id, 'manage_options' );
	}

	/**
	 * Check if user can sign documents
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_sign_documents( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// All logged-in users can sign.
		return $user_id > 0;
	}

	/**
	 * Check if user can manage lists
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_manage_lists( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return user_can( $user_id, 'edit_posts' );
	}

	/**
	 * Check if user can view DJ stats
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_view_dj_stats( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Check capability.
		if ( user_can( $user_id, 'apollo_view_dj_stats' ) ) {
			return true;
		}

		// Check role.
		$user = get_userdata( $user_id );
		if ( $user && in_array( 'dj', $user->roles, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if user can create nucleo
	 *
	 * Uses standard WordPress contributor role (cena::rio label)
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_create_nucleo( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Nucleo creation restricted to contributor role (cena::rio).
		$user = get_userdata( $user_id );
		if ( $user && in_array( 'contributor', $user->roles, true ) ) {
			return true;
		}

		// Admins can always create.
		return user_can( $user_id, 'manage_options' );
	}

	/**
	 * Check if user can create community
	 *
	 * @param int|null $user_id User ID. Default current user.
	 * @return bool
	 */
	public static function can_create_community( ?int $user_id = null ): bool {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Communities can be created by most roles.
		return user_can( $user_id, 'apollo_create_community' ) || user_can( $user_id, 'edit_posts' );
	}

	/**
	 * Check if user is co-author of post
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_co_author( int $user_id, int $post_id ): bool {
		$co_authors = get_post_meta( $post_id, '_event_co_authors', true );

		if ( ! is_array( $co_authors ) ) {
			$co_authors = get_post_meta( $post_id, '_local_co_authors', true );
		}

		if ( is_array( $co_authors ) && in_array( $user_id, $co_authors, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * REST permission callback: logged in
	 *
	 * @return bool|WP_Error
	 */
	public static function rest_logged_in() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * REST permission callback: can approve
	 *
	 * @return bool|WP_Error
	 */
	public static function rest_can_approve() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! self::can_approve_events() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to approve content.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * REST permission callback: can access CENA RIO
	 *
	 * @return bool|WP_Error
	 */
	public static function rest_can_access_cena_rio() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! self::can_access_cena_rio() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access CENA RIO.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
