<?php
declare(strict_types=1);

/**
 * CENA-RIO Roles Manager
 *
 * Manages CENA-ROLE (community) and CENA-MOD (moderator) roles
 * with granular permissions for event submission and mod.
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CENA-RIO Roles class
 */
class Apollo_Cena_Rio_Roles {

	/**
	 * Initialize - called on every page load
	 */
	public static function init(): void {
		// Verify roles exist on admin_init (in case activation didn't run)
		add_action( 'admin_init', array( __CLASS__, 'maybe_setup_roles' ) );
	}

	/**
	 * Conditionally setup roles if they don't exist
	 */
	public static function maybe_setup_roles(): void {
		if ( ! get_role( 'cena_role' ) || ! get_role( 'cena_moderator' ) ) {
			self::setup_roles();
		}
	}

	/**
	 * Activation handler - called from Apollo_Core_Activation
	 */
	public static function activate(): void {
		self::setup_roles();

		// Flush rewrite rules for Canvas routes if class is loaded
		if ( class_exists( 'Apollo_Cena_Rio_Canvas' ) ) {
			Apollo_Cena_Rio_Canvas::flush_rewrite_rules();
		}
	}

	/**
	 * Setup CENA-RIO roles and capabilities
	 * Called on plugin activation
	 */
	public static function setup_roles(): void {
		// Remove existing roles first to ensure clean setup
		remove_role( 'cena_role' );
		remove_role( 'cena_moderator' );

		// Create CENA-ROLE (community member - can only create draft events)
		$cena_role = add_role(
			'cena_role',
			__( 'Cena::Rio Membro', 'apollo-core' ),
			array(
				// Basic WP capabilities
				'read'                            => true,

				// Event listing capabilities (DRAFT ONLY)
				'edit_event_listing'              => true,
				// Can edit own events
												'edit_event_listings' => true,
				// Can edit own events (plural)
												'delete_event_listing' => true,
				// Can delete own drafts

												// CANNOT publish or edit others
												'publish_event_listings' => false,
				'edit_others_event_listings'      => false,
				'delete_published_event_listings' => false,
			)
		);

		if ( $cena_role ) {
			// Log success
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '✅ Apollo: Created cena_role with draft-only permissions' );
			}
		}

		// Create CENA-MODERATOR (can approve and publish events)
		$cena_mod = add_role(
			'cena_moderator',
			__( 'Cena::Rio Moderador', 'apollo-core' ),
			array(
				// Basic WP capabilities
				'read'                            => true,

				// All CENA-ROLE capabilities
				'edit_event_listing'              => true,
				'edit_event_listings'             => true,
				'delete_event_listing'            => true,

				// Plus mod capabilities
				'edit_others_event_listings'      => true,
				'publish_event_listings'          => true,
				'delete_others_event_listings'    => true,
				'delete_published_event_listings' => true,
				'read_private_event_listings'     => true,

				// Custom CENA mod capability
				'apollo_cena_moderate_events'     => true,
			)
		);

		if ( $cena_mod ) {
			// Log success
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '✅ Apollo: Created cena_moderator with full mod permissions' );
			}
		}

		// Add mod capability to administrator
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'apollo_cena_moderate_events' );
		}

		// Add mod capability to existing apollo moderator role
		$apollo_mod = get_role( 'apollo' );
		if ( $apollo_mod ) {
			$apollo_mod->add_cap( 'apollo_cena_moderate_events' );
		}
	}

	/**
	 * Remove CENA-RIO roles
	 * Called on plugin deactivation/uninstall
	 */
	public static function remove_roles(): void {
		remove_role( 'cena_role' );
		remove_role( 'cena_moderator' );

		// Remove custom capability from admin
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->remove_cap( 'apollo_cena_moderate_events' );
		}

		// Remove from apollo moderator
		$apollo_mod = get_role( 'apollo' );
		if ( $apollo_mod ) {
			$apollo_mod->remove_cap( 'apollo_cena_moderate_events' );
		}
	}

	/**
	 * Check if user has CENA-ROLE or higher
	 *
	 * @param int|null $user_id User ID (null = current user).
	 * @return bool True if user has cena_role or higher.
	 */
	public static function user_can_submit( ?int $user_id = null ): bool {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Allow cena_role, cena-rio (legacy), cena_moderator, apollo, editor, administrator
		// Note: cena-rio is legacy role from apollo-social, cena_role is canonical from apollo-core
		$allowed_roles = array( 'cena_role', 'cena-rio', 'cena_moderator', 'apollo', 'editor', 'administrator' );

		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user can moderate CENA events
	 *
	 * @param int|null $user_id User ID (null = current user).
	 * @return bool True if user can moderate.
	 */
	public static function user_can_moderate( ?int $user_id = null ): bool {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		return user_can( $user_id, 'apollo_cena_moderate_events' );
	}
}

// Initialize
Apollo_Cena_Rio_Roles::init();
