<?php
/**
 * Social Module Bootstrap
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Bootstrap file for module.
 *
 * Resolves: Duplicate apollo_social_post and user_page CPT registration
 * Priority: Hook priority 5 (after CPT Registry at 0, before default 10)
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo_Core\Apollo_Identifiers as ID;

// Define module constants.
if ( ! defined( 'APOLLO_SOCIAL_MODULE_DIR' ) ) {
	define( 'APOLLO_SOCIAL_MODULE_DIR', __DIR__ . '/' );
}

/**
 * Social Module class
 *
 * Provides fallback social CPTs when Apollo Social is not active.
 */
class Apollo_Social_Module {

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
	 * Only registers when Apollo Social is NOT active.
	 *
	 * Resolves:
	 * - apollo_social_post duplicate (apollo-social/src/Infrastructure/PostTypes/SocialPostType.php:81)
	 * - user_page duplicate (apollo-social/src/Modules/UserPages/UserPageRegistrar.php:54)
	 *
	 * Priority: 5 (Core fallback, yields to Apollo Social at priority 90)
	 */
	public static function register_post_types() {
		// Check if Apollo Social is active.
		$social_active = self::is_apollo_social_active();

		// Register apollo_social_post CPT (only if Apollo Social is not active).
		if ( ! $social_active && ! post_type_exists( 'apollo_social_post' ) ) {
			self::register_social_post_cpt();
		}

		// Register user_page CPT (only if Apollo Social is not active).
		if ( ! $social_active && ! post_type_exists( 'user_page' ) ) {
			self::register_user_page_cpt();
		}
	}

	/**
	 * Register apollo_social_post CPT
	 *
	 * @return void
	 */
	private static function register_social_post_cpt(): void {
		$args = array(
			'labels'          => array(
				'name'          => __( 'Social Posts', 'apollo-core' ),
				'singular_name' => __( 'Social Post', 'apollo-core' ),
			),
			'public'          => true,
			'has_archive'     => true,
			'show_in_rest'    => true,
			'capability_type' => 'post',
			'supports'        => array( 'title', 'editor', 'thumbnail', 'author', 'comments' ),
			'menu_icon'       => 'dashicons-format-status',
			// Use constant to match Apollo Social slug for consistency.
			'rewrite'         => array( 'slug' => ID::REWRITE_SOCIAL_POST ),
		);

		// Use CPT Registry if available.
		if ( class_exists( '\\Apollo_Core\\Apollo_CPT_Registry' ) ) {
			$registry = \Apollo_Core\Apollo_CPT_Registry::get_instance();
			$registry->register( ID::CPT_SOCIAL_POST, $args, self::OWNER, 10 );
			return;
		}

		// Direct registration fallback.
		register_post_type( ID::CPT_SOCIAL_POST, $args );
	}

	/**
	 * Register user_page CPT
	 *
	 * @return void
	 */
	private static function register_user_page_cpt(): void {
		$args = array(
			'labels'          => array(
				'name'          => __( 'User Pages', 'apollo-core' ),
				'singular_name' => __( 'User Page', 'apollo-core' ),
			),
			'public'          => true,
			'show_in_rest'    => true,
			'capability_type' => 'post',
			'supports'        => array( 'title', 'editor', 'author', 'comments' ),
			'menu_icon'       => 'dashicons-admin-users',
			// Use constant to match Apollo Social slug for consistency.
			'rewrite'         => array(
				'slug'       => ID::REWRITE_USER_PAGE,
				'with_front' => false,
			),
		);

		// Use CPT Registry if available.
		if ( class_exists( '\\Apollo_Core\\Apollo_CPT_Registry' ) ) {
			$registry = \Apollo_Core\Apollo_CPT_Registry::get_instance();
			$registry->register( ID::CPT_USER_PAGE, $args, self::OWNER, 10 );
			return;
		}

		// Direct registration fallback - only if not already registered.
		if ( ! post_type_exists( ID::CPT_USER_PAGE ) ) {
			register_post_type( ID::CPT_USER_PAGE, $args );
		}
	}

	/**
	 * Check if Apollo Social is active
	 *
	 * @return bool
	 */
	private static function is_apollo_social_active(): bool {
		// Use CPT Registry helper if available.
		if ( function_exists( '\\Apollo_Core\\apollo_companion_active' ) ) {
			return \Apollo_Core\apollo_companion_active( 'apollo-social' );
		}

		// Check by constant first (fastest).
		if ( defined( 'APOLLO_SOCIAL_VERSION' ) || defined( 'APOLLO_SOCIAL_PATH' ) ) {
			return true;
		}

		// Check by class.
		if ( class_exists( 'Apollo\\Plugin' ) || class_exists( 'Apollo_Social' ) ) {
			return true;
		}

		// Check by plugin file.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'apollo-social/apollo-social.php' );
	}

	/**
	 * Register REST routes
	 */
	public static function register_rest_routes() {
		register_rest_route(
			ID::rest_ns(),
			'explore',
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
			\Apollo_Core\Rest_Bootstrap::get_namespace(),
			'posts',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_post' ),
				'permission_callback' => array( '\Apollo_Core\Permissions', 'rest_logged_in' ),
				'args'                => array(
					'content' => array(
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);

		register_rest_route(
			\Apollo_Core\Rest_Bootstrap::get_namespace(),
			'wow',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'toggle_like' ),
				'permission_callback' => array( '\Apollo_Core\Permissions', 'rest_logged_in' ),
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
