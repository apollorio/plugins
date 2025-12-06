<?php
declare(strict_types=1);

/**
 * Events Module Bootstrap
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define module constants.
define( 'APOLLO_EVENTS_MODULE_DIR', __DIR__ . '/' );

/**
 * Events Module class
 */
class Apollo_Events_Module {
	/**
	 * Initialize module
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'apollo_core_register_rest_routes', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register custom post types
	 */
	public static function register_post_types() {
		// Register event_listing CPT.
		register_post_type(
			'event_listing',
			array(
				'labels'          => array(
					'name'          => __( 'Events', 'apollo-core' ),
					'singular_name' => __( 'Event', 'apollo-core' ),
				),
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'capability_type' => 'post',
				'supports'        => array( 'title', 'editor', 'thumbnail', 'author', 'comments' ),
				'menu_icon'       => 'dashicons-calendar-alt',
				'rewrite'         => array( 'slug' => 'events' ),
			)
		);

		// NOTE: event_dj and event_local CPTs are registered by Apollo Events Manager plugin
		// Do NOT register them here to avoid conflicts and duplication
		// These CPTs belong exclusively to apollo-events-manager/includes/post-types.php
		// Core only provides moderation/forms support for these CPTs when Events Manager is active
	}

	/**
	 * Register REST routes
	 */
	public static function register_rest_routes() {
		// TEMP: Xdebug breakpoint para depuração Apollo.
		if ( function_exists( 'xdebug_break' ) ) {
			xdebug_break();
		}

		register_rest_route(
			Apollo_Core_Rest_Bootstrap::get_namespace(),
			'/events',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_events' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'per_page' => array(
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			Apollo_Core_Rest_Bootstrap::get_namespace(),
			'/events/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_event' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			Apollo_Core_Rest_Bootstrap::get_namespace(),
			'/events',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_event' ),
				'permission_callback' => array( 'Apollo_Core_Permissions', 'rest_logged_in' ),
				'args'                => array(
					'title'   => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content' => array(
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);
	}

	/**
	 * Get events
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_events( $request ) {
		// TEMP: Xdebug breakpoint para depuração Apollo.
		if ( function_exists( 'xdebug_break' ) ) {
			xdebug_break();
		}

		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );

		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
			)
		);

		$events = array();
		foreach ( $query->posts as $post ) {
			$events[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'content' => apply_filters( 'the_content', $post->post_content ),
				'date'    => $post->post_date,
				'link'    => get_permalink( $post->ID ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $events,
				'total'   => $query->found_posts,
				'pages'   => $query->max_num_pages,
			),
			200
		);
	}

	/**
	 * Get single event
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_event( $request ) {
		$event_id = $request->get_param( 'id' );
		$post     = get_post( $event_id );

		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Event not found.', 'apollo-core' ),
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'      => $post->ID,
					'title'   => $post->post_title,
					'content' => apply_filters( 'the_content', $post->post_content ),
					'date'    => $post->post_date,
					'link'    => get_permalink( $post->ID ),
				),
			),
			200
		);
	}

	/**
	 * Create event
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function create_event( $request ) {
		$title   = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );

		$event_id = wp_insert_post(
			array(
				'post_type'    => 'event_listing',
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
			)
		);

		if ( is_wp_error( $event_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $event_id->get_error_message(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'    => $event_id,
					'title' => $title,
				),
				'message' => __( 'Event created successfully.', 'apollo-core' ),
			),
			201
		);
	}
}

// Initialize module.
Apollo_Events_Module::init();
