<?php
/**
 * REST API Module - Apollo Events Manager
 * Provides extended REST endpoints for events management
 *
 * @package Apollo\Events\Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Module Class
 * Adds endpoints: interest, review, track, rsvp
 */
class REST_API_Module extends Abstract_Module {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'apollo/v1';

	/**
	 * Get module ID
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'rest-api';
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'REST API Estendida', 'apollo-events-manager' );
	}

	/**
	 * Get module description
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Endpoints REST para interesse, reviews, tracking e RSVP.', 'apollo-events-manager' );
	}

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function get_version(): string {
		return '1.0.0';
	}

	/**
	 * Is default enabled
	 *
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize module
	 *
	 * @return void
	 */
	public function init(): void {
		// Register REST routes
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Interest (toggle interested/going)
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/interest',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'toggle_interest' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get interest status
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/interest',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_interest_status' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Reviews
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/reviews',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_reviews' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Submit review
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/reviews',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'submit_review' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'rating'  => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return $value >= 1 && $value <= 5;
						},
					),
					'content' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Track view
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_view' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'action' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'view',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function ( $value ) {
							return in_array( $value, array( 'view', 'click', 'share', 'ticket_click' ), true );
						},
					),
				),
			)
		);

		// RSVP
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/rsvp',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'submit_rsvp' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'id'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'status' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function ( $value ) {
							return in_array( $value, array( 'going', 'interested', 'not_going', 'cancel' ), true );
						},
					),
				),
			)
		);

		// Get RSVP status
		register_rest_route(
			$this->namespace,
			'events/(?P<id>\d+)/rsvp',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rsvp_status' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// DJs endpoints
		register_rest_route(
			$this->namespace,
			'djs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_djs' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'per_page' => array(
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
					'search'   => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Locals endpoints
		register_rest_route(
			$this->namespace,
			'locals',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_locals' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'per_page' => array(
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
					'search'   => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Check if user is logged in
	 *
	 * @return bool
	 */
	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * Toggle interest on event
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_interest( $request ) {
		$event_id = absint( $request['id'] );
		$user_id  = get_current_user_id();

		// Validate event exists
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_Error( 'event_not_found', __( 'Evento não encontrado.', 'apollo-events-manager' ), array( 'status' => 404 ) );
		}

		// Get current interested users
		$interested_users = get_post_meta( $event_id, '_event_interested_users', true );
		if ( ! is_array( $interested_users ) ) {
			$interested_users = array();
		}

		$is_interested = in_array( $user_id, $interested_users, true );

		if ( $is_interested ) {
			// Remove interest
			$interested_users = array_diff( $interested_users, array( $user_id ) );
			$message          = __( 'Interesse removido.', 'apollo-events-manager' );
		} else {
			// Add interest
			$interested_users[] = $user_id;
			$message            = __( 'Interesse registrado!', 'apollo-events-manager' );
		}

		// Save
		$interested_users = array_unique( array_filter( array_map( 'absint', $interested_users ) ) );
		update_post_meta( $event_id, '_event_interested_users', $interested_users );

		return new \WP_REST_Response(
			array(
				'success'        => true,
				'is_interested'  => ! $is_interested,
				'total_interest' => count( $interested_users ),
				'message'        => $message,
			),
			200
		);
	}

	/**
	 * Get interest status
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_interest_status( $request ) {
		$event_id = absint( $request['id'] );
		$user_id  = get_current_user_id();

		$interested_users = get_post_meta( $event_id, '_event_interested_users', true );
		if ( ! is_array( $interested_users ) ) {
			$interested_users = array();
		}

		$is_interested = $user_id > 0 && in_array( $user_id, $interested_users, true );

		return new \WP_REST_Response(
			array(
				'event_id'       => $event_id,
				'is_interested'  => $is_interested,
				'total_interest' => count( $interested_users ),
			),
			200
		);
	}

	/**
	 * Get reviews for event
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_reviews( $request ) {
		$event_id = absint( $request['id'] );

		$reviews = get_post_meta( $event_id, '_event_reviews', true );
		if ( ! is_array( $reviews ) ) {
			$reviews = array();
		}

		// Calculate average
		$total = 0;
		$count = count( $reviews );
		$avg   = 0;

		if ( $count > 0 ) {
			foreach ( $reviews as $review ) {
				$total += absint( $review['rating'] ?? 0 );
			}
			$avg = round( $total / $count, 1 );
		}

		// Format reviews for output
		$formatted = array();
		foreach ( $reviews as $review ) {
			$user        = get_userdata( $review['user_id'] ?? 0 );
			$formatted[] = array(
				'user_id'     => absint( $review['user_id'] ?? 0 ),
				'user_name'   => $user ? sanitize_text_field( $user->display_name ) : __( 'Anônimo', 'apollo-events-manager' ),
				'user_avatar' => $user ? esc_url( get_avatar_url( $user->ID ) ) : '',
				'rating'      => absint( $review['rating'] ?? 0 ),
				'content'     => sanitize_textarea_field( $review['content'] ?? '' ),
				'date'        => sanitize_text_field( $review['date'] ?? '' ),
			);
		}

		return new \WP_REST_Response(
			array(
				'event_id'       => $event_id,
				'reviews'        => $formatted,
				'total_reviews'  => $count,
				'average_rating' => $avg,
			),
			200
		);
	}

	/**
	 * Submit review
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function submit_review( $request ) {
		$event_id = absint( $request['id'] );
		$user_id  = get_current_user_id();
		$rating   = absint( $request['rating'] );
		$content  = sanitize_textarea_field( $request['content'] ?? '' );

		// Validate event
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_Error( 'event_not_found', __( 'Evento não encontrado.', 'apollo-events-manager' ), array( 'status' => 404 ) );
		}

		// Get current reviews
		$reviews = get_post_meta( $event_id, '_event_reviews', true );
		if ( ! is_array( $reviews ) ) {
			$reviews = array();
		}

		// Check if user already reviewed
		foreach ( $reviews as $key => $review ) {
			if ( absint( $review['user_id'] ?? 0 ) === $user_id ) {
				// Update existing review
				$reviews[ $key ] = array(
					'user_id' => $user_id,
					'rating'  => $rating,
					'content' => $content,
					'date'    => current_time( 'mysql' ),
				);
				update_post_meta( $event_id, '_event_reviews', $reviews );

				return new \WP_REST_Response(
					array(
						'success' => true,
						'message' => __( 'Review atualizado!', 'apollo-events-manager' ),
						'updated' => true,
					),
					200
				);
			}
		}

		// Add new review
		$reviews[] = array(
			'user_id' => $user_id,
			'rating'  => $rating,
			'content' => $content,
			'date'    => current_time( 'mysql' ),
		);

		update_post_meta( $event_id, '_event_reviews', $reviews );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Review enviado!', 'apollo-events-manager' ),
				'updated' => false,
			),
			201
		);
	}

	/**
	 * Track event view/action
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function track_view( $request ) {
		$event_id = absint( $request['id'] );
		$action   = sanitize_key( $request['action'] ?? 'view' );

		// Rate-limit tracking per IP/event to prevent spam
		$ip_hash  = md5( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
		$rate_key = "track_{$event_id}_{$ip_hash}_{$action}";
		if ( get_transient( $rate_key ) ) {
			return new \WP_REST_Response(
				array(
					'tracked' => false,
					'reason'  => 'rate_limited',
				),
				200
			);
		}
		set_transient( $rate_key, true, MINUTE_IN_SECONDS * 5 );

		// Validate event
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array( 'tracked' => false ),
				200
			);
		}

		// Get tracking data
		$tracking = get_post_meta( $event_id, '_event_tracking', true );
		if ( ! is_array( $tracking ) ) {
			$tracking = array(
				'views'         => 0,
				'clicks'        => 0,
				'shares'        => 0,
				'ticket_clicks' => 0,
			);
		}

		// Increment counter
		switch ( $action ) {
			case 'view':
				$tracking['views'] = ( $tracking['views'] ?? 0 ) + 1;
				break;
			case 'click':
				$tracking['clicks'] = ( $tracking['clicks'] ?? 0 ) + 1;
				break;
			case 'share':
				$tracking['shares'] = ( $tracking['shares'] ?? 0 ) + 1;
				break;
			case 'ticket_click':
				$tracking['ticket_clicks'] = ( $tracking['ticket_clicks'] ?? 0 ) + 1;
				break;
		}

		update_post_meta( $event_id, '_event_tracking', $tracking );

		return new \WP_REST_Response(
			array(
				'tracked' => true,
				'action'  => $action,
				'totals'  => $tracking,
			),
			200
		);
	}

	/**
	 * Submit RSVP
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function submit_rsvp( $request ) {
		$event_id = absint( $request['id'] );
		$user_id  = get_current_user_id();
		$status   = sanitize_key( $request['status'] );

		// Validate event
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_Error( 'event_not_found', __( 'Evento não encontrado.', 'apollo-events-manager' ), array( 'status' => 404 ) );
		}

		// Get current RSVPs
		$rsvps = get_post_meta( $event_id, '_event_rsvps', true );
		if ( ! is_array( $rsvps ) ) {
			$rsvps = array();
		}

		if ( $status === 'cancel' ) {
			// Remove RSVP
			unset( $rsvps[ $user_id ] );
			$message = __( 'RSVP cancelado.', 'apollo-events-manager' );
		} else {
			// Add/update RSVP
			$rsvps[ $user_id ] = array(
				'status' => $status,
				'date'   => current_time( 'mysql' ),
			);
			$message           = __( 'RSVP registrado!', 'apollo-events-manager' );
		}

		update_post_meta( $event_id, '_event_rsvps', $rsvps );

		// Count by status
		$counts = array(
			'going'      => 0,
			'interested' => 0,
			'not_going'  => 0,
		);
		foreach ( $rsvps as $rsvp ) {
			$s = $rsvp['status'] ?? '';
			if ( isset( $counts[ $s ] ) ) {
				++$counts[ $s ];
			}
		}

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'status'      => $status,
				'message'     => $message,
				'total_rsvps' => $counts,
			),
			200
		);
	}

	/**
	 * Get RSVP status
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_rsvp_status( $request ) {
		$event_id = absint( $request['id'] );
		$user_id  = get_current_user_id();

		$rsvps = get_post_meta( $event_id, '_event_rsvps', true );
		if ( ! is_array( $rsvps ) ) {
			$rsvps = array();
		}

		$user_rsvp = $rsvps[ $user_id ] ?? null;

		// Count by status
		$counts = array(
			'going'      => 0,
			'interested' => 0,
			'not_going'  => 0,
		);
		foreach ( $rsvps as $rsvp ) {
			$s = $rsvp['status'] ?? '';
			if ( isset( $counts[ $s ] ) ) {
				++$counts[ $s ];
			}
		}

		return new \WP_REST_Response(
			array(
				'event_id'    => $event_id,
				'user_status' => $user_rsvp ? $user_rsvp['status'] : null,
				'total_rsvps' => $counts,
			),
			200
		);
	}

	/**
	 * Get DJs list
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_djs( $request ) {
		$args = array(
			'post_type'      => 'event_dj',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $request['per_page'] ?? 50 ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $request['search'] ) ) {
			$args['s'] = sanitize_text_field( $request['search'] );
		}

		$query = new \WP_Query( $args );
		$djs   = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$dj    = get_post();
				$djs[] = array(
					'id'        => (int) $dj->ID,
					'name'      => sanitize_text_field( $dj->post_title ),
					'slug'      => sanitize_text_field( $dj->post_name ),
					'permalink' => esc_url( get_permalink( $dj->ID ) ),
					'image'     => esc_url( get_the_post_thumbnail_url( $dj->ID, 'thumbnail' ) ?: '' ),
				);
			}
			wp_reset_postdata();
		}

		return new \WP_REST_Response(
			array(
				'djs'   => $djs,
				'total' => $query->found_posts,
			),
			200
		);
	}

	/**
	 * Get locals list
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_locals( $request ) {
		$args = array(
			'post_type'      => 'event_local',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $request['per_page'] ?? 50 ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $request['search'] ) ) {
			$args['s'] = sanitize_text_field( $request['search'] );
		}

		$query  = new \WP_Query( $args );
		$locals = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$local    = get_post();
				$locals[] = array(
					'id'        => (int) $local->ID,
					'name'      => sanitize_text_field( $local->post_title ),
					'slug'      => sanitize_text_field( $local->post_name ),
					'permalink' => esc_url( get_permalink( $local->ID ) ),
					'address'   => sanitize_text_field( get_post_meta( $local->ID, '_local_address', true ) ?: '' ),
					'city'      => sanitize_text_field( get_post_meta( $local->ID, '_local_city', true ) ?: '' ),
					'image'     => esc_url( get_the_post_thumbnail_url( $local->ID, 'thumbnail' ) ?: '' ),
				);
			}
			wp_reset_postdata();
		}

		return new \WP_REST_Response(
			array(
				'locals' => $locals,
				'total'  => $query->found_posts,
			),
			200
		);
	}
}
