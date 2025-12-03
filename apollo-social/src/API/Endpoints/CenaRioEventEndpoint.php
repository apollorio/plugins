<?php
/**
 * P0-10: CENA RIO Event Endpoint
 *
 * Handles event creation and approval for CENA RIO role.
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

class CenaRioEventEndpoint {

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
		// Create event as 'previsto' (draft)
		register_rest_route(
			'apollo/v1',
			'/cena-rio/event',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'createEvent' ),
				'permission_callback' => array( $this, 'permissionCheck' ),
				'args'                => array(
					'title'       => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'Event title.', 'apollo-social' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'date'        => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Event date (Y-m-d).', 'apollo-social' ),
					),
					'time'        => array(
						'required'    => false,
						'type'        => 'string',
						'description' => __( 'Event time (H:i).', 'apollo-social' ),
					),
					'ticket_url'  => array(
						'required'    => false,
						'type'        => 'string',
						'description' => __( 'Ticket URL.', 'apollo-social' ),
					),
					'local_id'    => array(
						'required'    => false,
						'type'        => 'integer',
						'description' => __( 'Local ID.', 'apollo-social' ),
					),
					'description' => array(
						'required'    => false,
						'type'        => 'string',
						'description' => __( 'Event description.', 'apollo-social' ),
					),
				),
			)
		);

		// Approve event (MOD/ADMIN only)
		register_rest_route(
			'apollo/v1',
			'/cena-rio/event/(?P<id>\d+)/approve',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'approveEvent' ),
				'permission_callback' => array( $this, 'modPermissionCheck' ),
			)
		);
	}

	/**
	 * Permission check (cena-rio role)
	 */
	public function permissionCheck( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		$user = wp_get_current_user();
		if ( ! in_array( 'cena-rio', $user->roles ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Only CENA RIO members can create events.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Mod permission check (MOD/ADMIN only)
	 */
	public function modPermissionCheck( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Only moderators can approve events.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * P0-10: Create event as 'previsto' (draft)
	 */
	public function createEvent( WP_REST_Request $request ): WP_REST_Response {
		if ( ! post_type_exists( 'event_listing' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Event post type not available.', 'apollo-social' ),
				),
				400
			);
		}

		$title       = $request->get_param( 'title' );
		$date        = $request->get_param( 'date' );
		$time        = $request->get_param( 'time' ) ?: '20:00';
		$ticket_url  = $request->get_param( 'ticket_url' );
		$local_id    = $request->get_param( 'local_id' );
		$description = $request->get_param( 'description' );

		if ( empty( $title ) || empty( $date ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Title and date are required.', 'apollo-social' ),
				),
				400
			);
		}

		// Validate date format
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $date );
		if ( ! $date_obj ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid date format. Use Y-m-d.', 'apollo-social' ),
				),
				400
			);
		}

		// P0-10: Create event as draft
		$event_id = wp_insert_post(
			array(
				'post_type'    => 'event_listing',
				'post_title'   => $title,
				'post_content' => $description ?: '',
				'post_status'  => 'draft', 
				// P0-10: Always draft for moderation
												'post_author' => get_current_user_id(),
			)
		);

		if ( is_wp_error( $event_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error creating event.', 'apollo-social' ),
				),
				500
			);
		}

		// Save meta fields
		update_post_meta( $event_id, '_event_start_date', $date );
		update_post_meta( $event_id, '_event_start_time', sanitize_text_field( $time ) );

		if ( $ticket_url ) {
			update_post_meta( $event_id, '_event_ticket_url', esc_url_raw( $ticket_url ) );

			// P0-10: Auto-confirm ticket URL if present (can be verified later)
			update_post_meta( $event_id, '_event_ticket_confirmed', true );
		}

		if ( $local_id ) {
			update_post_meta( $event_id, '_event_local_id', absint( $local_id ) );
		}

		// P0-10: Mark as 'previsto' (planned)
		update_post_meta( $event_id, '_cena_event_status', 'previsto' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'     => $event_id,
					'title'  => $title,
					'status' => 'draft',
				),
				'message' => __( 'Event created successfully. It will be reviewed before publication.', 'apollo-social' ),
			),
			201
		);
	}

	/**
	 * P0-10: Approve event (MOD/ADMIN only)
	 */
	public function approveEvent( WP_REST_Request $request ): WP_REST_Response {
		$event_id = absint( $request->get_param( 'id' ) );

		if ( ! $event_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid event ID.', 'apollo-social' ),
				),
				400
			);
		}

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Event not found.', 'apollo-social' ),
				),
				404
			);
		}

		// P0-10: Publish event
		$result = wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error approving event.', 'apollo-social' ),
				),
				500
			);
		}

		// P0-10: Update status meta
		update_post_meta( $event_id, '_cena_event_status', 'approved' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'     => $event_id,
					'status' => 'publish',
				),
				'message' => __( 'Event approved and published.', 'apollo-social' ),
			),
			200
		);
	}
}
