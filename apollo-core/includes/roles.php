<?php

declare(strict_types=1);

/**
 * Apollo Core - Role Management (Legacy Wrapper)
 *
 * @package Apollo_Core
 * @since 3.0.0
 * @deprecated 4.0.0 Use Apollo_Roles_Manager instead
 *
 * UNIFIED ROLE SYSTEM - Uses standard WordPress roles with custom labels:
 * - administrator → label: 'apollo'
 * - editor → label: 'MOD'
 * - author → label: 'cult::rio'
 * - contributor → label: 'cena::rio'
 * - subscriber → label: 'clubber'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup capabilities on standard WordPress roles
 *
 * @deprecated 4.0.0 Use Apollo_Roles_Manager::setup_capabilities() instead
 */
function apollo_create_roles() {
	// Delegate to centralized Roles Manager
	if ( class_exists( 'Apollo_Roles_Manager' ) ) {
		Apollo_Roles_Manager::init();
		return;
	}

	// Fallback: Add Apollo-specific capabilities to standard WP roles
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->add_cap( 'moderate_apollo_content' );
		$editor->add_cap( 'edit_apollo_users' );
		$editor->add_cap( 'view_mod_queue' );
		$editor->add_cap( 'send_user_notifications' );
	}

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'manage_apollo_mod_settings' );
		$admin->add_cap( 'suspend_users' );
		$admin->add_cap( 'block_users' );
		$admin->add_cap( 'moderate_apollo_content' );
		$admin->add_cap( 'edit_apollo_users' );
		$admin->add_cap( 'view_mod_queue' );
		$admin->add_cap( 'send_user_notifications' );
	}
}

/**
 * Remove apollo capabilities
 * Called on plugin uninstall
 */
function apollo_remove_roles() {
	// Remove capabilities from standard roles (no custom roles to remove)
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->remove_cap( 'moderate_apollo_content' );
		$editor->remove_cap( 'edit_apollo_users' );
		$editor->remove_cap( 'view_mod_queue' );
		$editor->remove_cap( 'send_user_notifications' );
	}

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->remove_cap( 'manage_apollo_mod_settings' );
		$admin->remove_cap( 'suspend_users' );
		$admin->remove_cap( 'block_users' );
		$admin->remove_cap( 'moderate_apollo_content' );
		$admin->remove_cap( 'edit_apollo_users' );
		$admin->remove_cap( 'view_mod_queue' );
		$admin->remove_cap( 'send_user_notifications' );
	}
}

/**
 * Check if user has moderator capabilities (editor or higher)
 *
 * @param int $user_id User ID. Default current user.
 * @return bool True if user has moderator role.
 */
function apollo_user_is_moderator( $user_id = null ) {
	if ( is_null( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	// Check for editor or administrator (standard WP roles)
	return in_array( 'editor', $user->roles, true ) || in_array( 'administrator', $user->roles, true );
}

/**
 * Assign editor role to user (moderator level)
 *
 * @param int $user_id User ID.
 * @return bool True on success, false on failure.
 */
function apollo_assign_moderator_role( $user_id ) {
	$user = new WP_User( $user_id );
	if ( ! $user->exists() ) {
		return false;
	}

	$user->add_role( 'editor' );

	return true;
}

/**
 * Remove editor role from user
 *
 * @param int $user_id User ID.
 * @return bool True on success, false on failure.
 */
function apollo_remove_moderator_role( $user_id ) {
	$user = new WP_User( $user_id );
	if ( ! $user->exists() ) {
		return false;
	}

	$user->remove_role( 'editor' );

	return true;
}
