<?php
declare(strict_types=1);

/**
 * Social Module Bootstrap
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define module constants.
define( 'APOLLO_SOCIAL_MODULE_DIR', __DIR__ . '/' );

/**
 * Social Module class
 */
class Apollo_Social_Module {
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
		// Register apollo_social_post CPT.
		register_post_type(
			'apollo_social_post',
			array(
				'labels'              => array(
					'name'          => __( 'Social Posts', 'apollo-core' ),
					'singular_name' => __( 'Social Post', 'apollo-core' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'show_in_rest'        => true,
				'capability_type'     => 'post',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author', 'comments' ),
				'menu_icon'           => 'dashicons-format-status',
				'rewrite'             => array( 'slug' => 'posts' ),
			)
		);

		// Register user_page CPT.
		register_post_type(
			'user_page',
			array(
				'labels'              => array(
					'name'          => __( 'User Pages', 'apollo-core' ),
					'singular_name' => __( 'User Page', 'apollo-core' ),
				),
				'public'              => true,
				'show_in_rest'        => true,
				'capability_type'     => 'post',
				'supports'            => array( 'title', 'editor', 'author', 'comments' ),
				'menu_icon'           => 'dashicons-admin-users',
				'rewrite'             => array( 'slug' => 'profile' ),
			)
		);
	}

	/**
	 * Register REST routes
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Apollo_Core_Rest_Bootstrap::get_namespace(),
			'/feed',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_feed' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'per_page' => array(
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			Apollo_Core_Rest_Bootstrap::get_namespace(),
			'/posts',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_post' ),
				'permission_callback' => array( 'Apollo_Core_Permissions', 'rest_logged_in' ),
				'args'                => array(
					'content' => array(
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);

		register_rest_route(
			Apollo_Core_Rest_Bootstrap::get_namespace(),
			'/like',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'toggle_like' ),
				'permission_callback' => array( 'Apollo_Core_Permissions', 'rest_logged_in' ),
				'args'                => array(
					'content_id'   => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'content_type' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Get unified feed
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_feed( $request ) {
		$per_page = $request->get_param( 'per_page' );

		// Query mixed content (social posts + events).
		$query = new WP_Query(
			array(
				'post_type'      => array( 'apollo_social_post', 'event_listing' ),
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$feed = array();
		foreach ( $query->posts as $post ) {
			$feed[] = array(
				'id'      => $post->ID,
				'type'    => $post->post_type,
				'title'   => $post->post_title,
				'content' => wp_trim_words( $post->post_content, 50 ),
				'date'    => $post->post_date,
				'author'  => array(
					'id'   => $post->post_author,
					'name' => get_the_author_meta( 'display_name', $post->post_author ),
				),
				'link'    => get_permalink( $post->ID ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $feed,
				'total'   => $query->found_posts,
			),
			200
		);
	}

	/**
	 * Create social post
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function create_post( $request ) {
		$content = $request->get_param( 'content' );

		// Verify nonce.
		if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'apollo-core' ),
				),
				403
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'apollo_social_post',
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $post_id->get_error_message(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id' => $post_id,
				),
				'message' => __( 'Post created successfully.', 'apollo-core' ),
			),
			201
		);
	}

	/**
	 * Toggle like
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function toggle_like( $request ) {
		$content_id   = $request->get_param( 'content_id' );
		$content_type = $request->get_param( 'content_type' );
		$user_id      = get_current_user_id();

		// Get user likes.
		$user_likes = get_user_meta( $user_id, '_apollo_likes', true );
		if ( ! is_array( $user_likes ) ) {
			$user_likes = array();
		}

		if ( ! isset( $user_likes[ $content_type ] ) ) {
			$user_likes[ $content_type ] = array();
		}

		// Toggle like.
		$is_liked = in_array( $content_id, $user_likes[ $content_type ], true );

		if ( $is_liked ) {
			$user_likes[ $content_type ] = array_diff( $user_likes[ $content_type ], array( $content_id ) );
		} else {
			$user_likes[ $content_type ][] = $content_id;
		}

		update_user_meta( $user_id, '_apollo_likes', $user_likes );

		return new WP_REST_Response(
			array(
				'success' => true,
				'liked'   => ! $is_liked,
			),
			200
		);
	}
}

// Initialize module.
Apollo_Social_Module::init();

