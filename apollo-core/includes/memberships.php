<?php
declare(strict_types=1);

/**
 * Apollo Core - Membership Management
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get default membership types
 *
 * @return array Default memberships array
 */
function apollo_get_default_memberships(): array {
	return array(
		'nao-verificado' => array(
			'label'          => __( 'Não Verificado', 'apollo-core' ),
			'frontend_label' => __( 'Não Verificado', 'apollo-core' ),
			'color'          => '#9AA0A6',
			'text_color'     => '#6E7376',
		),
		'apollo'         => array(
			'label'          => __( 'Apollo', 'apollo-core' ),
			'frontend_label' => __( 'Apollo', 'apollo-core' ),
			'color'          => '#FF8C42',
			'text_color'     => '#7A3E00',
		),
		'prod'           => array(
			'label'          => __( 'Prod', 'apollo-core' ),
			'frontend_label' => __( 'Prod', 'apollo-core' ),
			'color'          => '#8A2BE2',
			'text_color'     => '#4B0082',
		),
		'dj'             => array(
			'label'          => __( 'DJ', 'apollo-core' ),
			'frontend_label' => __( 'DJ', 'apollo-core' ),
			'color'          => '#8A2BE2',
			'text_color'     => '#4B0082',
		),
		'host'           => array(
			'label'          => __( 'Host', 'apollo-core' ),
			'frontend_label' => __( 'Host', 'apollo-core' ),
			'color'          => '#8A2BE2',
			'text_color'     => '#4B0082',
		),
		'govern'         => array(
			'label'          => __( 'Govern', 'apollo-core' ),
			'frontend_label' => __( 'Govern', 'apollo-core' ),
			'color'          => '#007BFF',
			'text_color'     => '#003F7F',
		),
		'business-pers'  => array(
			'label'          => __( 'Business', 'apollo-core' ),
			'frontend_label' => __( 'Business', 'apollo-core' ),
			'color'          => '#FFD700',
			'text_color'     => '#8B6B00',
		),
	);
}

/**
 * Get all membership types (defaults merged with custom)
 *
 * @return array Memberships array
 */
function apollo_get_memberships(): array {
	// Try cache first
	$cached = apollo_cache_get_memberships();
	if ( false !== $cached ) {
		return $cached;
	}

	$custom   = get_option( 'apollo_memberships', array() );
	$defaults = apollo_get_default_memberships();

	$memberships = wp_parse_args( $custom, $defaults );

	// Cache the result
	apollo_cache_memberships( $memberships );

	return $memberships;
}

/**
 * Save membership types
 *
 * @param array $memberships Memberships array to save.
 * @return bool True on success, false on failure.
 */
function apollo_save_memberships( array $memberships ): bool {
	// Validate structure.
	if ( ! is_array( $memberships ) ) {
		return false;
	}

	// Validate each membership.
	foreach ( $memberships as $slug => $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		// Required fields.
		$required = array( 'label', 'frontend_label', 'color', 'text_color' );
		foreach ( $required as $field ) {
			if ( ! isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
				return false;
			}
		}

		// Validate color formats (basic hex validation).
		if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $data['color'] ) ) {
			return false;
		}
		if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $data['text_color'] ) ) {
			return false;
		}
	}//end foreach

	// Ensure nao-verificado always exists.
	if ( ! isset( $memberships['nao-verificado'] ) ) {
		$defaults                      = apollo_get_default_memberships();
		$memberships['nao-verificado'] = $defaults['nao-verificado'];
	}

	// Update option with version.
	$result = update_option( 'apollo_memberships', $memberships );

	if ( $result ) {
		$current_version  = get_option( 'apollo_memberships_version', '1.0.0' );
		$version_parts    = explode( '.', $current_version );
		$version_parts[2] = isset( $version_parts[2] ) ? (int) $version_parts[2] + 1 : 1;
		$new_version      = implode( '.', $version_parts );

		update_option( 'apollo_memberships_version', $new_version );

		// Invalidate cache
		apollo_cache_flush_group( 'apollo_memberships' );
	}

	return $result;
}

/**
 * Get user's current membership
 *
 * @param int $user_id User ID.
 * @return string Membership slug or 'nao-verificado' if not set.
 */
function apollo_get_user_membership( int $user_id ): string {
	$membership = get_user_meta( $user_id, '_apollo_membership', true );

	// Default to nao-verificado if not set.
	if ( empty( $membership ) ) {
		$membership = 'nao-verificado';
	}

	// Validate membership exists.
	$memberships = apollo_get_memberships();
	if ( ! isset( $memberships[ $membership ] ) ) {
		$membership = 'nao-verificado';
	}

	return $membership;
}

/**
 * Set user's membership
 *
 * @param int      $user_id         User ID.
 * @param string   $membership_slug Membership slug.
 * @param int|null $actor_id        ID of user performing the action (for logging).
 * @return bool True on success, false on failure.
 */
function apollo_set_user_membership( int $user_id, string $membership_slug, ?int $actor_id = null ): bool {
	// Validate user exists.
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	// Validate membership exists.
	$memberships = apollo_get_memberships();
	if ( ! isset( $memberships[ $membership_slug ] ) ) {
		return false;
	}

	// Get old membership for logging.
	$old_membership = apollo_get_user_membership( $user_id );

	// Update user meta.
	$result = update_user_meta( $user_id, '_apollo_membership', sanitize_key( $membership_slug ) );

	// Log action.
	if ( $result && function_exists( 'apollo_mod_log_action' ) ) {
		$actor_id = $actor_id ? $actor_id : get_current_user_id();

		apollo_mod_log_action(
			$actor_id,
			'membership_changed',
			'user',
			$user_id,
			array(
				'from'       => $old_membership,
				'to'         => $membership_slug,
				'from_label' => isset( $memberships[ $old_membership ] ) ? $memberships[ $old_membership ]['label'] : $old_membership,
				'to_label'   => $memberships[ $membership_slug ]['label'],
				'timestamp'  => current_time( 'mysql' ),
			)
		);
	}

	return (bool) $result;
}

/**
 * Initialize memberships option on activation
 * This should be called during plugin activation
 */
function apollo_init_memberships_option() {
	$option = get_option( 'apollo_memberships' );

	if ( false === $option ) {
		// Create option with empty array (defaults will be merged when retrieved).
		add_option( 'apollo_memberships', array() );
		add_option( 'apollo_memberships_version', '1.0.0' );
	}
}

/**
 * Assign default membership to existing users without membership
 * This should be called during plugin activation
 */
function apollo_assign_default_memberships() {
	$users = get_users( array( 'fields' => 'ID' ) );

	foreach ( $users as $user_id ) {
		$membership = get_user_meta( $user_id, '_apollo_membership', true );

		if ( empty( $membership ) ) {
			update_user_meta( $user_id, '_apollo_membership', 'nao-verificado' );
		}
	}
}

/**
 * Assign default membership to new user on registration
 * Hooked to user_register
 *
 * @param int $user_id User ID.
 */
function apollo_assign_membership_on_registration( $user_id ) {
	$membership = get_user_meta( $user_id, '_apollo_membership', true );

	// Only set if not already set.
	if ( empty( $membership ) ) {
		update_user_meta( $user_id, '_apollo_membership', 'nao-verificado' );
	}
}
add_action( 'user_register', 'apollo_assign_membership_on_registration', 10, 1 );

/**
 * Get membership data by slug
 *
 * @param string $slug Membership slug.
 * @return array|false Membership data or false if not found.
 */
function apollo_get_membership_data( $slug ) {
	$memberships = apollo_get_memberships();
	return isset( $memberships[ $slug ] ) ? $memberships[ $slug ] : false;
}

/**
 * Check if membership slug exists
 *
 * @param string $slug Membership slug.
 * @return bool True if exists, false otherwise.
 */
function apollo_membership_exists( $slug ) {
	$memberships = apollo_get_memberships();
	return isset( $memberships[ $slug ] );
}

/**
 * Delete a membership type
 * Reassigns all users with this membership to nao-verificado
 *
 * @param string $slug Membership slug.
 * @return bool True on success, false on failure.
 */
function apollo_delete_membership( $slug ) {
	// Cannot delete nao-verificado.
	if ( 'nao-verificado' === $slug ) {
		return false;
	}

	$memberships = apollo_get_memberships();

	if ( ! isset( $memberships[ $slug ] ) ) {
		return false;
	}

	// Reassign users to nao-verificado.
	$users = get_users(
		array(
			'meta_key'   => '_apollo_membership',
			'meta_value' => $slug,
			'fields'     => 'ID',
		)
	);

	foreach ( $users as $user_id ) {
		update_user_meta( $user_id, '_apollo_membership', 'nao-verificado' );
	}

	// Remove from saved memberships.
	unset( $memberships[ $slug ] );

	return apollo_save_memberships( $memberships );
}

/**
 * Export memberships as JSON
 *
 * @return string JSON string.
 */
function apollo_export_memberships_json() {
	$memberships = get_option( 'apollo_memberships', array() );
	$version     = get_option( 'apollo_memberships_version', '1.0.0' );

	$export = array(
		'version'     => $version,
		'exported_at' => current_time( 'mysql' ),
		'memberships' => $memberships,
	);

	return wp_json_encode( $export, JSON_PRETTY_PRINT );
}

/**
 * Import memberships from JSON
 *
 * @param string $json JSON string.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function apollo_import_memberships_json( $json ) {
	$data = json_decode( $json, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error( 'invalid_json', __( 'Invalid JSON format', 'apollo-core' ) );
	}

	if ( ! isset( $data['memberships'] ) || ! is_array( $data['memberships'] ) ) {
		return new WP_Error( 'invalid_structure', __( 'Invalid memberships structure', 'apollo-core' ) );
	}

	// Validate and save.
	$result = apollo_save_memberships( $data['memberships'] );

	if ( ! $result ) {
		return new WP_Error( 'save_failed', __( 'Failed to save memberships', 'apollo-core' ) );
	}

	return true;
}
