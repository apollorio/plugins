<?php

namespace Apollo\Modules\Auth;

/**
 * User Roles - Label Translation
 *
 * @deprecated 4.0.0 Use Apollo_Roles_Manager::translate_role_names() instead
 *
 * UNIFIED ROLE LABELS (from capability.unify.md):
 * - administrator → apollo
 * - editor → MOD
 * - author → cult::rio
 * - contributor → cena::rio
 * - subscriber → clubber
 */
class UserRoles {

	public function register() {
		// Delegate to Apollo_Roles_Manager if available
		if ( class_exists( 'Apollo_Roles_Manager' ) ) {
			// Apollo_Roles_Manager handles label translation via translate_role_names filter
			return;
		}

		// Fallback: register our own label translation
		add_action( 'init', array( $this, 'modifyUserRoles' ) );
	}

	/**
	 * Modify role display names (labels only, NOT slugs)
	 *
	 * @deprecated Use Apollo_Roles_Manager::translate_role_names()
	 */
	public function modifyUserRoles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			return;
		}

		// Unified role labels per capability.unify.md
		$role_labels = array(
			'subscriber'    => 'clubber',
			'contributor'   => 'cena::rio',
			'author'        => 'cult::rio',
			'editor'        => 'MOD',
			'administrator' => 'apollo',
		);

		foreach ( $role_labels as $slug => $label ) {
			if ( isset( $wp_roles->roles[ $slug ] ) ) {
				$wp_roles->roles[ $slug ]['name'] = $label;
				$wp_roles->role_names[ $slug ]    = $label;
			}
		}
	}
}
