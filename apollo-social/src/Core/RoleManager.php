<?php
namespace Apollo\Core;

/**
 * Role Manager
 * Renames WordPress roles for Apollo ecosystem
 * Changes only display names, keeps slugs unchanged
 */
class RoleManager {

	/**
	 * Role name mappings
	 * Format: [wp_slug => display_name]
	 */
	private $role_names = array(
		'subscriber'    => 'Clubber',
		'contributor'   => 'Cena::rio',
		'author'        => 'Cena::rj',
		'editor'        => 'Apollo::rio',
		'administrator' => 'Apollo',
	);

	public function register() {
		// Hook into role name display
		add_filter( 'wp_roles', array( $this, 'renameRoles' ) );
		add_filter( 'get_role', array( $this, 'renameRoleObject' ), 10, 2 );

		// Add custom capabilities for Clubber (Subscriber)
		add_action( 'init', array( $this, 'addClubberCapabilities' ) );

		// Ensure cena-rio role exists with Contributor capabilities
		add_action( 'init', array( $this, 'ensureCenaRioRole' ) );
	}

	/**
	 * Rename roles in wp_roles object
	 */
	public function renameRoles( $wp_roles ) {
		foreach ( $this->role_names as $slug => $name ) {
			if ( isset( $wp_roles->roles[ $slug ] ) ) {
				$wp_roles->roles[ $slug ]['name'] = $name;
			}
		}

		return $wp_roles;
	}

	/**
	 * Rename role object when retrieved
	 */
	public function renameRoleObject( $role, $role_name ) {
		if ( isset( $this->role_names[ $role_name ] ) && $role ) {
			$role->name = $this->role_names[ $role_name ];
		}

		return $role;
	}

	/**
	 * Add capabilities for Clubber (Subscriber) to submit events as draft
	 */
	public function addClubberCapabilities() {
		$role = get_role( 'subscriber' );

		if ( $role ) {
			// Allow Clubbers to submit events as draft
			$role->add_cap( 'apollo_submit_event' );
			$role->add_cap( 'apollo_create_draft_event' );
		}
	}

	/**
	 * Ensure cena-rio role exists with Contributor capabilities
	 */
	public function ensureCenaRioRole() {
		// Check if role exists
		if ( ! get_role( 'cena-rio' ) ) {
			// Get Contributor role as base
			$contributor = get_role( 'contributor' );

			if ( $contributor ) {
				// Create cena-rio role with Contributor capabilities
				add_role(
					'cena-rio',
					'Cena::rio',
					$contributor->capabilities
				);
			}
		}
	}

	/**
	 * Get role display name
	 */
	public function getRoleDisplayName( $role_slug ) {
		return isset( $this->role_names[ $role_slug ] )
			? $this->role_names[ $role_slug ]
			: ucfirst( $role_slug );
	}
}
