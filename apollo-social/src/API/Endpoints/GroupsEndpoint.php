<?php
/**
 * P0-7: Groups REST API Endpoint
 *
 * Handles creation of groups (comunidade/nucleo).
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GroupsEndpoint {

	/**
	 * Register REST routes
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register routes
	 */
	public function registerRoutes(): void {
		// Create group
		register_rest_route(
			'apollo/v1',
			'/groups',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'createGroup' ),
				'permission_callback' => array( $this, 'permissionCheck' ),
				'args'                => array(
					'title'       => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'Group title.', 'apollo-social' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'type'        => array(
						'required'    => true,
						'type'        => 'string',
						'enum'        => array( 'comunidade', 'nucleo' ),
						'description' => __( 'Group type.', 'apollo-social' ),
					),
					'description' => array(
						'required'    => false,
						'type'        => 'string',
						'description' => __( 'Group description.', 'apollo-social' ),
					),
					'visibility'  => array(
						'required' => false,
						'type'     => 'string',
						'enum'     => array( 'public', 'private', 'members_only' ),
						'default'  => 'public',
					),
				),
			)
		);
	}

	/**
	 * Permission check
	 */
	public function permissionCheck( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to create groups.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		$type = $request->get_param( 'type' );

		// P0-7: Only cena-rio role can create nucleo
		if ( $type === 'nucleo' && ! current_user_can( 'manage_options' ) && ! user_can( get_current_user_id(), 'cena_rio' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Only CENA RIO members can create núcleos.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * P0-7: Create group
	 */
	public function createGroup( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$title       = $request->get_param( 'title' );
		$type        = $request->get_param( 'type' );
		$description = $request->get_param( 'description' );
		$visibility  = $request->get_param( 'visibility' ) ?: 'public';

		if ( empty( $title ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Group title is required.', 'apollo-social' ),
				),
				400
			);
		}

		// Generate slug
		$slug          = sanitize_title( $title );
		$original_slug = $slug;
		$counter       = 1;

		// Ensure unique slug
		$table_name = $wpdb->prefix . 'apollo_groups';
		while ( $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE slug = %s",
				$slug
			)
		) > 0 ) {
			$slug = $original_slug . '-' . $counter;
			++$counter;
		}

		// Insert group
		$result = $wpdb->insert(
			$table_name,
			array(
				'title'       => $title,
				'slug'        => $slug,
				'description' => $description,
				'type'        => $type,
				'status'      => 'pending_review',
				// P0-7: Require moderation
												'visibility' => $visibility,
				'creator_id'  => get_current_user_id(),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( $result === false ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error creating group.', 'apollo-social' ),
				),
				500
			);
		}

		$group_id = $wpdb->insert_id;

		// Add creator as admin member
		$members_table = $wpdb->prefix . 'apollo_group_members';
		$wpdb->insert(
			$members_table,
			array(
				'group_id'  => $group_id,
				'user_id'   => get_current_user_id(),
				'role'      => 'admin',
				'status'    => 'active',
				'joined_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		// P0-7: Add to moderation queue
		$moderation_table = $wpdb->prefix . 'apollo_moderation_queue';
		$wpdb->insert(
			$moderation_table,
			array(
				'content_id'    => $group_id,
				'content_type'  => 'apollo_group',
				'content_title' => $title,
				'author_id'     => get_current_user_id(),
				'status'        => 'pending',
				'submitted_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'     => $group_id,
					'title'  => $title,
					'slug'   => $slug,
					'status' => 'pending_review',
				),
				'message' => __( 'Group created successfully. It will be reviewed before publication.', 'apollo-social' ),
			),
			201
		);
	}
}
