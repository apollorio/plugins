<?php
declare(strict_types=1);

/**
 * Apollo Core - Membership REST API
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register membership REST routes
 */
function apollo_register_membership_rest_routes() {
	// GET /memberships - Get all membership types (public).
	register_rest_route(
		'apollo/v1',
		'/memberships',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_rest_get_memberships',
			'permission_callback' => '__return_true',
		)
	);

	// POST /memberships/set - Set user membership.
	register_rest_route(
		'apollo/v1',
		'/memberships/set',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_rest_set_membership',
			'permission_callback' => 'apollo_rest_can_edit_users',
			'args'                => array(
				'user_id'         => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'apollo_rest_validate_user_id',
				),
				'membership_slug' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'apollo_rest_validate_membership_slug',
				),
			),
		)
	);

	// POST /memberships/create - Create new membership type (admin only).
	register_rest_route(
		'apollo/v1',
		'/memberships/create',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_rest_create_membership',
			'permission_callback' => 'apollo_rest_can_manage_memberships',
			'args'                => array(
				'slug'           => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
				'label'          => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'frontend_label' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'color'          => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
				),
				'text_color'     => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
				),
			),
		)
	);

	// POST /memberships/update - Update membership type (admin only).
	register_rest_route(
		'apollo/v1',
		'/memberships/update',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_rest_update_membership',
			'permission_callback' => 'apollo_rest_can_manage_memberships',
			'args'                => array(
				'slug'           => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
				'label'          => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'frontend_label' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'color'          => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
				),
				'text_color'     => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
				),
			),
		)
	);

	// DELETE /memberships/delete - Delete membership type (admin only).
	register_rest_route(
		'apollo/v1',
		'/memberships/delete',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_rest_delete_membership',
			'permission_callback' => 'apollo_rest_can_manage_memberships',
			'args'                => array(
				'slug' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);

	// GET /memberships/export - Export memberships as JSON (admin only).
	register_rest_route(
		'apollo/v1',
		'/memberships/export',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_rest_export_memberships',
			'permission_callback' => 'apollo_rest_can_manage_memberships',
		)
	);

	// POST /memberships/import - Import memberships from JSON (admin only).
	register_rest_route(
		'apollo/v1',
		'/memberships/import',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_rest_import_memberships',
			'permission_callback' => 'apollo_rest_can_manage_memberships',
			'args'                => array(
				'data' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'wp_kses_post',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'apollo_register_membership_rest_routes' );

/**
 * Permission callback: check if user can edit users
 *
 * @return bool True if user can edit users.
 */
function apollo_rest_can_edit_users() {
	return current_user_can( 'edit_apollo_users' );
}

/**
 * Permission callback: check if user can manage membership types
 *
 * @return bool True if user can manage options.
 */
function apollo_rest_can_manage_memberships() {
	return current_user_can( 'manage_options' );
}

/**
 * Validate user ID
 *
 * @param int             $param   User ID.
 * @param WP_REST_Request $request Request object.
 * @param string          $key     Parameter key.
 * @return bool True if valid.
 */
function apollo_rest_validate_user_id( $param, $request, $key ) {
	$user = get_userdata( $param );
	return $user !== false;
}

/**
 * Validate membership slug
 *
 * @param string          $param   Membership slug.
 * @param WP_REST_Request $request Request object.
 * @param string          $key     Parameter key.
 * @return bool True if valid.
 */
function apollo_rest_validate_membership_slug( $param, $request, $key ) {
	return apollo_membership_exists( $param );
}

/**
 * REST callback: Get all memberships
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_get_memberships( $request ) {
	$memberships = apollo_get_memberships();
	$version     = get_option( 'apollo_memberships_version', '1.0.0' );

	return new WP_REST_Response(
		array(
			'success'     => true,
			'version'     => $version,
			'memberships' => $memberships,
		),
		200
	);
}

/**
 * REST callback: Set user membership
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_set_membership( $request ) {
	try {
		$user_id         = $request->get_param( 'user_id' );
		$membership_slug = $request->get_param( 'membership_slug' );

		// Get current user for logging.
		$actor_id = get_current_user_id();

		// Set membership.
		$result = apollo_set_user_membership( $user_id, $membership_slug, $actor_id );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to update membership', 'apollo-core' ),
				),
				400
			);
		}

		$user            = get_userdata( $user_id );
		$membership_data = apollo_get_membership_data( $membership_slug );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'message'    => __( 'Membership updated successfully', 'apollo-core' ),
				'user_id'    => $user_id,
				'user_name'  => $user->display_name,
				'membership' => $membership_data,
			),
			200
		);
	} catch ( Exception $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
			error_log(
				sprintf(
					'[Apollo Core] Membership update error - User: %d, Membership: %s, Message: %s',
					$user_id ?? 0,
					$membership_slug ?? 'unknown',
					$e->getMessage()
				)
			);
		}

		return new WP_Error(
			'membership_update_failed',
			__( 'Failed to update membership. Please try again.', 'apollo-core' ),
			array(
				'status'     => 500,
				'debug_info' => WP_DEBUG ? $e->getMessage() : null,
			)
		);
	}//end try
}

/**
 * REST callback: Create new membership type
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_create_membership( $request ) {
	$slug           = $request->get_param( 'slug' );
	$label          = $request->get_param( 'label' );
	$frontend_label = $request->get_param( 'frontend_label' );
	$color          = $request->get_param( 'color' );
	$text_color     = $request->get_param( 'text_color' );

	// Check if slug already exists.
	if ( apollo_membership_exists( $slug ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Membership with this slug already exists', 'apollo-core' ),
			),
			400
		);
	}

	// Get current memberships.
	$memberships = get_option( 'apollo_memberships', array() );

	// Add new membership.
	$memberships[ $slug ] = array(
		'label'          => $label,
		'frontend_label' => $frontend_label,
		'color'          => $color,
		'text_color'     => $text_color,
	);

	// Save.
	$result = apollo_save_memberships( $memberships );

	if ( ! $result ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Failed to create membership', 'apollo-core' ),
			),
			500
		);
	}

	// Log action.
	apollo_mod_log_action(
		get_current_user_id(),
		'membership_type_created',
		'membership',
		0,
		array(
			'slug'  => $slug,
			'label' => $label,
		)
	);

	return new WP_REST_Response(
		array(
			'success'    => true,
			'message'    => __( 'Membership created successfully', 'apollo-core' ),
			'membership' => $memberships[ $slug ],
		),
		201
	);
}

/**
 * REST callback: Update membership type
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_update_membership( $request ) {
	$slug = $request->get_param( 'slug' );

	// Check if exists.
	if ( ! apollo_membership_exists( $slug ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Membership not found', 'apollo-core' ),
			),
			404
		);
	}

	// Cannot edit default nao-verificado.
	$defaults = apollo_get_default_memberships();
	if ( isset( $defaults[ $slug ] ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Cannot edit default membership types', 'apollo-core' ),
			),
			403
		);
	}

	// Get current memberships.
	$memberships = get_option( 'apollo_memberships', array() );

	if ( ! isset( $memberships[ $slug ] ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Only custom memberships can be edited', 'apollo-core' ),
			),
			403
		);
	}

	// Update fields if provided.
	$label          = $request->get_param( 'label' );
	$frontend_label = $request->get_param( 'frontend_label' );
	$color          = $request->get_param( 'color' );
	$text_color     = $request->get_param( 'text_color' );

	if ( $label ) {
		$memberships[ $slug ]['label'] = $label;
	}
	if ( $frontend_label ) {
		$memberships[ $slug ]['frontend_label'] = $frontend_label;
	}
	if ( $color ) {
		$memberships[ $slug ]['color'] = $color;
	}
	if ( $text_color ) {
		$memberships[ $slug ]['text_color'] = $text_color;
	}

	// Save.
	$result = apollo_save_memberships( $memberships );

	if ( ! $result ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Failed to update membership', 'apollo-core' ),
			),
			500
		);
	}

	// Log action.
	apollo_mod_log_action(
		get_current_user_id(),
		'membership_type_updated',
		'membership',
		0,
		array(
			'slug'  => $slug,
			'label' => $memberships[ $slug ]['label'],
		)
	);

	return new WP_REST_Response(
		array(
			'success'    => true,
			'message'    => __( 'Membership updated successfully', 'apollo-core' ),
			'membership' => $memberships[ $slug ],
		),
		200
	);
}

/**
 * REST callback: Delete membership type
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_delete_membership( $request ) {
	$slug = $request->get_param( 'slug' );

	// Cannot delete nao-verificado.
	if ( 'nao-verificado' === $slug ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Cannot delete default membership', 'apollo-core' ),
			),
			403
		);
	}

	// Cannot delete default memberships.
	$defaults = apollo_get_default_memberships();
	if ( isset( $defaults[ $slug ] ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Cannot delete default membership types', 'apollo-core' ),
			),
			403
		);
	}

	// Delete membership.
	$result = apollo_delete_membership( $slug );

	if ( ! $result ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Failed to delete membership', 'apollo-core' ),
			),
			500
		);
	}

	// Log action.
	apollo_mod_log_action(
		get_current_user_id(),
		'membership_type_deleted',
		'membership',
		0,
		array(
			'slug' => $slug,
		)
	);

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => __( 'Membership deleted successfully. Users reassigned to Não Verificado.', 'apollo-core' ),
		),
		200
	);
}

/**
 * REST callback: Export memberships
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_export_memberships( $request ) {
	$json = apollo_export_memberships_json();

	return new WP_REST_Response(
		array(
			'success' => true,
			'data'    => $json,
		),
		200
	);
}

/**
 * REST callback: Import memberships
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_import_memberships( $request ) {
	$json = $request->get_param( 'data' );

	$result = apollo_import_memberships_json( $json );

	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => $result->get_error_message(),
			),
			400
		);
	}

	// Log action.
	apollo_mod_log_action(
		get_current_user_id(),
		'memberships_imported',
		'membership',
		0,
		array(
			'timestamp' => current_time( 'mysql' ),
		)
	);

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => __( 'Memberships imported successfully', 'apollo-core' ),
		),
		200
	);
}
