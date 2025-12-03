<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - REST API Moderation Endpoints
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register moderation REST routes
 */
function apollo_register_moderation_rest_routes() {
	// Approve content endpoint.
	register_rest_route(
		'apollo/v1',
		'/moderation/approve',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'apollo_rest_approve_content',
			'permission_callback' => 'apollo_rest_can_moderate',
			'args'                => array(
				'post_id' => array(
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param );
					},
					'sanitize_callback' => 'absint',
				),
				'note'    => array(
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);

	// Suspend user endpoint.
	register_rest_route(
		'apollo/v1',
		'/users/suspend',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'apollo_rest_suspend_user',
			'permission_callback' => function () {
				return is_user_logged_in() && current_user_can( 'suspend_users' );
			},
			'args'                => array(
				'user_id' => array(
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param );
					},
					'sanitize_callback' => 'absint',
				),
				'days'    => array(
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
					'sanitize_callback' => 'absint',
				),
				'reason'  => array(
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);

	// Block user endpoint.
	register_rest_route(
		'apollo/v1',
		'/users/block',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'apollo_rest_block_user',
			'permission_callback' => function () {
				return is_user_logged_in() && current_user_can( 'block_users' );
			},
			'args'                => array(
				'user_id' => array(
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param );
					},
					'sanitize_callback' => 'absint',
				),
				'reason'  => array(
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'apollo_register_moderation_rest_routes' );

/**
 * Permission callback: can moderate
 *
 * @return bool|WP_Error
 */
function apollo_rest_can_moderate() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			__( 'You must be logged in to perform this action.', 'apollo-core' ),
			array( 'status' => 401 )
		);
	}

	if ( ! current_user_can( 'moderate_apollo_content' ) ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to moderate content.', 'apollo-core' ),
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * REST callback: Approve content
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function apollo_rest_approve_content( $request ) {
	try {
		$post_id = $request->get_param( 'post_id' );
		$note    = $request->get_param( 'note' );

		// Verify post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		// Check if post is draft.
		if ( ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			return new WP_Error(
				'post_not_draft',
				__( 'Only draft or pending posts can be approved.', 'apollo-core' ),
				array( 'status' => 400 )
			);
		}

		// Check if publishing this post type is enabled.
		$enabled_cap = apollo_map_post_type_to_capability( $post->post_type );
		if ( ! $enabled_cap || ! apollo_is_cap_enabled( $enabled_cap ) ) {
			return new WP_Error(
				'capability_disabled',
				sprintf(
				/* translators: %s: post type */
					__( 'Publishing %s is not currently enabled.', 'apollo-core' ),
					$post->post_type
				),
				array( 'status' => 403 )
			);
		}

		// Publish the post.
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Log action.
		apollo_mod_log_action(
			get_current_user_id(),
			'approve_post',
			$post->post_type,
			$post_id,
			array( 'note' => $note )
		);

		// Get updated post.
		$updated_post = get_post( $post_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Content approved and published successfully.', 'apollo-core' ),
				'post'    => array(
					'id'     => $updated_post->ID,
					'title'  => $updated_post->post_title,
					'status' => $updated_post->post_status,
					'link'   => get_permalink( $updated_post->ID ),
				),
			),
			200
		);
	} catch ( Exception $e ) {
		error_log(
			sprintf(
				'[Apollo Core] Content approval error - Post: %d, Message: %s',
				$post_id ?? 0,
				$e->getMessage()
			)
		);

		return new WP_Error(
			'approval_failed',
			__( 'Failed to approve content. Please try again.', 'apollo-core' ),
			array(
				'status'     => 500,
				'debug_info' => WP_DEBUG ? $e->getMessage() : null,
			)
		);
	}//end try
}

/**
 * REST callback: Suspend user
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function apollo_rest_suspend_user( $request ) {
	$user_id = $request->get_param( 'user_id' );
	$days    = $request->get_param( 'days' );
	$reason  = $request->get_param( 'reason' );

	// Verify user exists.
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return new WP_Error(
			'user_not_found',
			__( 'User not found.', 'apollo-core' ),
			array( 'status' => 404 )
		);
	}

	// Cannot suspend administrators.
	if ( in_array( 'administrator', $user->roles, true ) ) {
		return new WP_Error(
			'cannot_suspend_admin',
			__( 'Cannot suspend an administrator.', 'apollo-core' ),
			array( 'status' => 403 )
		);
	}

	// Set suspension meta.
	$suspended_until = time() + ( $days * DAY_IN_SECONDS );
	update_user_meta( $user_id, '_apollo_suspended_until', $suspended_until );
	update_user_meta( $user_id, '_apollo_suspension_reason', $reason );

	// Log action.
	apollo_mod_log_action(
		get_current_user_id(),
		'suspend_user',
		'user',
		$user_id,
		array(
			'days'   => $days,
			'until'  => gmdate( 'Y-m-d H:i:s', $suspended_until ),
			'reason' => $reason,
		)
	);

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of days */
				__( 'User suspended for %d days.', 'apollo-core' ),
				$days
			),
			'user'    => array(
				'id'              => $user_id,
				'suspended_until' => gmdate( 'Y-m-d H:i:s', $suspended_until ),
				'reason'          => $reason,
			),
		),
		200
	);
}

/**
 * REST callback: Block user
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function apollo_rest_block_user( $request ) {
	$user_id = $request->get_param( 'user_id' );
	$reason  = $request->get_param( 'reason' );

	// Verify user exists.
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return new WP_Error(
			'user_not_found',
			__( 'User not found.', 'apollo-core' ),
			array( 'status' => 404 )
		);
	}

	// Cannot block administrators.
	if ( in_array( 'administrator', $user->roles, true ) ) {
		return new WP_Error(
			'cannot_block_admin',
			__( 'Cannot block an administrator.', 'apollo-core' ),
			array( 'status' => 403 )
		);
	}

	// Set blocked meta.
	update_user_meta( $user_id, '_apollo_blocked', 1 );
	update_user_meta( $user_id, '_apollo_block_reason', $reason );

	// Log action.
	apollo_mod_log_action(
		get_current_user_id(),
		'block_user',
		'user',
		$user_id,
		array( 'reason' => $reason )
	);

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => __( 'User blocked successfully.', 'apollo-core' ),
			'user'    => array(
				'id'     => $user_id,
				'reason' => $reason,
			),
		),
		200
	);
}

/**
 * Map post type to enabled capability key
 *
 * @param string $post_type Post type.
 * @return string|false Capability key or false if not mapped.
 */
function apollo_map_post_type_to_capability( $post_type ) {
	$map = array(
		'event_listing'      => 'publish_events',
		'event_local'        => 'publish_locals',
		'event_dj'           => 'publish_djs',
		'apollo_nucleo'      => 'publish_nucleos',
		'apollo_comunidade'  => 'publish_comunidades',
		'apollo_social_post' => 'edit_posts',
		'post'               => 'edit_posts',
		'apollo_classified'  => 'edit_classifieds',
	);

	return isset( $map[ $post_type ] ) ? $map[ $post_type ] : false;
}
