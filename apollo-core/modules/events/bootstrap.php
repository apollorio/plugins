<?php

declare(strict_types=1);

/**
 * Events Module Bootstrap
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Resolves: Duplicate event_listing CPT registration
 * Priority: Hook priority 5 (after CPT Registry at 0, before default 10)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo_Core\Apollo_Identifiers as ID;

// Define module constants.
if ( ! defined( 'APOLLO_EVENTS_MODULE_DIR' ) ) {
	define( 'APOLLO_EVENTS_MODULE_DIR', __DIR__ . '/' );
}

/**
 * Events Module class
 *
 * Provides fallback event_listing CPT when Apollo Events Manager is not active.
 */
class Apollo_Events_Module {

	/**
	 * Module owner identifier for CPT Registry
	 */
	private const OWNER = 'apollo-core';

	/**
	 * Initialize module
	 */
	public static function init() {
		// Register at priority 5 - after CPT Registry processes queue (0) but before normal (10).
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'apollo_core_register_rest_routes', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register custom post types (FALLBACK ONLY)
	 *
	 * Uses CPT Registry to prevent duplicate registration.
	 * Only registers when Apollo Events Manager is NOT active.
	 *
	 * Resolves: event_listing duplicate (apollo-events-manager/includes/post-types.php:98)
	 * Priority: 5 (Core fallback, yields to Events Manager at priority 100)
	 */
	public static function register_post_types() {
		// Use CPT Registry helper function if available.
		if ( function_exists( '\\Apollo_Core\\apollo_companion_active' ) ) {
			if ( \Apollo_Core\apollo_companion_active( 'apollo-events-manager' ) ) {
				// Primary owner is active - skip fallback registration.
				return;
			}
		} else {
			// Fallback check without registry.
			if ( self::is_events_manager_active() ) {
				return;
			}
		}

		// Check if CPT already exists (registered by another mechanism).
		if ( post_type_exists( ID::CPT_EVENT_LISTING ) ) {
			return;
		}

		// Use CPT Registry if available.
		if ( class_exists( '\\Apollo_Core\\Apollo_CPT_Registry' ) ) {
			$registry = \Apollo_Core\Apollo_CPT_Registry::get_instance();

			$registry->register(
				ID::CPT_EVENT_LISTING,
				self::get_event_listing_args(),
				self::OWNER,
				10 // Low priority - yields to Events Manager.
			);

			return;
		}

		// Direct registration fallback (legacy mode) - only if not already registered.
		if ( ! post_type_exists( ID::CPT_EVENT_LISTING ) ) {
			register_post_type( ID::CPT_EVENT_LISTING, self::get_event_listing_args() );
		}

		// NOTE: event_dj and event_local CPTs are ONLY registered by Apollo Events Manager.
		// Do NOT register them here - they require full Events Manager functionality.
	}

	/**
	 * Get event_listing CPT arguments
	 *
	 * @return array
	 */
	private static function get_event_listing_args(): array {
		return array(
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
			// Use 'evento' to match Events Manager slug for consistency.
			'rewrite'         => array(
				'slug'       => ID::REWRITE_EVENT_LISTING,
				'with_front' => false,
			),
		);
	}

	/**
	 * Check if Apollo Events Manager is active (without registry)
	 *
	 * @return bool
	 */
	private static function is_events_manager_active(): bool {
		// Check by constant first (fastest).
		if ( defined( 'APOLLO_EVENTS_VERSION' ) || defined( 'APOLLO_APRIO_PATH' ) ) {
			return true;
		}

		// Check by class.
		if ( class_exists( 'Apollo_Events_Manager' ) || class_exists( 'Apollo_Post_Types' ) ) {
			return true;
		}

		// Check by plugin file.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'apollo-events-manager/apollo-events-manager.php' );
	}

	/**
	 * Register REST routes (FALLBACK ONLY)
	 *
	 * Only registers routes when Apollo Events Manager is NOT active.
	 * This prevents duplicate route registration.
	 *
	 * Resolves: /eventos, /evento/{id} duplicate routes
	 * Canonical: apollo-events-manager/includes/class-rest-api.php
	 */
	public static function register_rest_routes() {
		// Skip if Apollo Events Manager is active - it registers these routes.
		if ( self::is_events_manager_active() ) {
			return;
		}

		register_rest_route(
			ID::rest_ns(),
			ID::REST_ROUTE_EVENTOS,
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
			ID::rest_ns(),
			ID::REST_ROUTE_EVENTO_SINGLE,
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
			ID::rest_ns(),
			ID::REST_ROUTE_EVENTOS,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_event' ),
				'permission_callback' => array( '\\Apollo_Core\\Permissions', 'rest_logged_in' ),
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
