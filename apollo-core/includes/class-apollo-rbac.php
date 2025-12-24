<?php

declare(strict_types=1);

namespace Apollo_Core;

/**
 * Apollo Centralized RBAC
 *
 * Manages all Apollo capabilities across plugins
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo RBAC Class
 */
class RBAC {

	/**
	 * All Apollo capabilities
	 */
	const CAPABILITIES = array(
		// Core management.
		'manage_apollo',

		// Security.
		'manage_apollo_security',

		// Uploads.
		'manage_apollo_uploads',
		'apollo_upload_media',

		// Events.
		'manage_apollo_events',

		// Social.
		'manage_apollo_social',

		// Compression.
		'manage_apollo_compression',

		// Audit.
		'manage_apollo_audit',
		'view_apollo_reports',
	);

	/**
	 * Register all capabilities
	 * Capabilities are registered by adding them to roles
	 *
	 * @return void
	 */
	public static function register_capabilities(): void {
		// No explicit registration needed in WordPress.
		// Capabilities are registered when assigned to roles.
	}

	/**
	 * Assign capabilities to roles on activation
	 *
	 * @return void
	 */
	public static function assign_capabilities_to_roles(): void {
		// Administrator gets all capabilities.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::CAPABILITIES as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		// Editor gets events and social management.
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'manage_apollo_events' );
			$editor->add_cap( 'manage_apollo_social' );
		}

		// Author gets basic upload capability.
		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'apollo_upload_media' );
		}
	}

	/**
	 * Remove capabilities on deactivation
	 *
	 * @return void
	 */
	public static function remove_capabilities_from_roles(): void {
		$roles = array( 'administrator', 'editor', 'author' );

		foreach ( $roles as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( self::CAPABILITIES as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Get RBAC matrix (which roles have which capabilities)
	 *
	 * @return array RBAC matrix.
	 */
	public static function get_rbac_matrix(): array {
		$matrix = array();
		$roles  = get_editable_roles();

		foreach ( $roles as $role_slug => $role_data ) {
			$role = get_role( $role_slug );
			if ( ! $role ) {
				continue;
			}

			$matrix[ $role_slug ] = array(
				'name'         => $role_data['name'],
				'capabilities' => array(),
			);

			foreach ( self::CAPABILITIES as $cap ) {
				$matrix[ $role_slug ]['capabilities'][ $cap ] = $role->has_cap( $cap );
			}
		}

		return $matrix;
	}
}
