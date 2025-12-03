<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - Role Management
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create apollo role and assign capabilities
 */
function apollo_create_roles() {
	// Create apollo role (moderator role).
	if ( ! get_role( 'apollo' ) ) {
		$editor = get_role( 'editor' );
		if ( $editor ) {
			add_role(
				'apollo',
				__( 'Apollo Moderator', 'apollo-core' ),
				$editor->capabilities
			);
		}
	}

	// Add apollo-specific capabilities.
	$apollo = get_role( 'apollo' );
	if ( $apollo ) {
		$apollo->add_cap( 'moderate_apollo_content' );
		$apollo->add_cap( 'edit_apollo_users' );
		$apollo->add_cap( 'view_moderation_queue' );
		$apollo->add_cap( 'send_user_notifications' );
	}

	// Add admin-only capabilities to administrator role.
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'manage_apollo_mod_settings' );
		$admin->add_cap( 'suspend_users' );
		$admin->add_cap( 'block_users' );
		$admin->add_cap( 'moderate_apollo_content' );
		$admin->add_cap( 'edit_apollo_users' );
		$admin->add_cap( 'view_moderation_queue' );
		$admin->add_cap( 'send_user_notifications' );
	}
}

/**
 * Remove apollo role and capabilities
 * Called on plugin uninstall
 */
function apollo_remove_roles() {
	// Remove apollo role.
	remove_role( 'apollo' );

	// Remove capabilities from administrator.
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->remove_cap( 'manage_apollo_mod_settings' );
		$admin->remove_cap( 'suspend_users' );
		$admin->remove_cap( 'block_users' );
		$admin->remove_cap( 'moderate_apollo_content' );
		$admin->remove_cap( 'edit_apollo_users' );
		$admin->remove_cap( 'view_moderation_queue' );
		$admin->remove_cap( 'send_user_notifications' );
	}
}

/**
 * Check if user has apollo role
 *
 * @param int $user_id User ID. Default current user.
 * @return bool True if user has apollo role.
 */
function apollo_user_is_moderator( $user_id = null ) {
	if ( is_null( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	return in_array( 'apollo', $user->roles, true ) || in_array( 'administrator', $user->roles, true );
}

/**
 * Assign apollo role to user
 *
 * @param int $user_id User ID.
 * @return bool True on success, false on failure.
 */
function apollo_assign_moderator_role( $user_id ) {
	$user = new WP_User( $user_id );
	if ( ! $user->exists() ) {
		return false;
	}

	$user->add_role( 'apollo' );
	return true;
}

/**
 * Remove apollo role from user
 *
 * @param int $user_id User ID.
 * @return bool True on success, false on failure.
 */
function apollo_remove_moderator_role( $user_id ) {
	$user = new WP_User( $user_id );
	if ( ! $user->exists() ) {
		return false;
	}

	$user->remove_role( 'apollo' );
	return true;
}
