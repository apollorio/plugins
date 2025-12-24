<?php

/**
 * Apollo Home Custom Post Type
 *
 * CPT for user "Habbo-style" home pages.
 * One post per user, stores layout JSON in post meta.
 *
 * Pattern source: WOW Page Builder uses post_meta for layout storage
 * Pattern source: Live Composer uses CPT with rewrite slugs
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Home_CPT
 *
 * Data Model:
 * - CPT: apollo_home (one per user)
 * - Meta: _apollo_builder_content (JSON layout)
 * - Meta: _apollo_background_texture (texture ID)
 * - Meta: _apollo_trax_url (SoundCloud/Spotify URL)
 * - Comments: Native WP comments = "Depoimentos" (Guestbook)
 */
class Apollo_Home_CPT {

	public const POST_TYPE = 'apollo_home';

	/**
	 * Initialize hooks
	 * Tooltip: Registers CPT on init hook, registers meta on rest_api_init
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
	}

	/**
	 * Register the apollo_home CPT
	 *
	 * Data source: Creates posts in wp_posts table
	 * Rewrite slug: /clubber/{post_name}
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Clubber Homes', 'apollo-social' ),
			'singular_name'      => __( 'Clubber Home', 'apollo-social' ),
			'menu_name'          => __( 'Clubber Homes', 'apollo-social' ),
			'add_new'            => __( 'Add New', 'apollo-social' ),
			'add_new_item'       => __( 'Add New Home', 'apollo-social' ),
			'edit_item'          => __( 'Edit Home', 'apollo-social' ),
			'new_item'           => __( 'New Home', 'apollo-social' ),
			'view_item'          => __( 'View Home', 'apollo-social' ),
			'search_items'       => __( 'Search Homes', 'apollo-social' ),
			'not_found'          => __( 'No homes found', 'apollo-social' ),
			'not_found_in_trash' => __( 'No homes found in trash', 'apollo-social' ),
		);

		$args = array(
			'labels'                       => $labels,
			'public'                       => true,
			'publicly_queryable'           => true,
			'show_ui'                      => true,
			'show_in_menu'                 => false,
			// Hidden from admin menu
							'show_in_rest' => true,
			'query_var'                    => true,
			'rewrite'                      => array(
				'slug'       => 'id',
				'with_front' => false,
			),
			'capability_type'              => 'post',
			'has_archive'                  => false,
			'hierarchical'                 => false,
			'supports'                     => array( 'title', 'comments', 'author' ),
			'menu_icon'                    => 'dashicons-admin-home',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register post meta keys with tooltips
	 *
	 * Data source: wp_postmeta table
	 * Pattern: WOW uses _wow_content, _wow_page_css for layout + CSS
	 */
	public static function register_meta() {
		// Layout JSON (pattern: WOW _wow_content)
		register_post_meta(
			self::POST_TYPE,
			APOLLO_BUILDER_META_CONTENT,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '{"widgets":[]}',
				'sanitize_callback' => 'apollo_builder_sanitize_layout',
				'show_in_rest'      => false,
				// Security: don't expose in REST
												'description' => 'JSON layout: {widgets: [{id, type, x, y, width, height, zIndex, config}]}',
			)
		);

		// Generated CSS (pattern: WOW _wow_page_css)
		register_post_meta(
			self::POST_TYPE,
			APOLLO_BUILDER_META_CSS,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'wp_strip_all_tags',
				'show_in_rest'      => false,
				'description'       => 'Generated CSS for the home page layout',
			)
		);

		// Background texture
		register_post_meta(
			self::POST_TYPE,
			APOLLO_BUILDER_META_BACKGROUND,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'description'       => 'Background texture ID from apollo_builder_textures option',
			)
		);

		// Trax player URL
		register_post_meta(
			self::POST_TYPE,
			APOLLO_BUILDER_META_TRAX,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
				'description'       => 'SoundCloud or Spotify URL for Trax Player widget',
			)
		);
	}

	/**
	 * Get or create home post for user
	 *
	 * Pattern: One apollo_home per user (enforced)
	 * Data source: wp_posts.post_author = user_id
	 *
	 * @param int $user_id User ID
	 * @return WP_Post|null The home post or null on failure
	 */
	public static function get_or_create_home( $user_id ) {
		if ( $user_id <= 0 ) {
			return null;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}

		// Try to find existing
		$existing = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'author'         => $user_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			return get_post( $existing[0] );
		}

		// Create new home
		$post_id = wp_insert_post(
			array(
				'post_type'      => self::POST_TYPE,
				'post_title'     => sprintf( __( '%s\'s Home', 'apollo-social' ), $user->display_name ),
				'post_status'    => 'publish',
				'post_author'    => $user_id,
				'comment_status' => 'open',
			// Enable "Depoimentos"
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Apollo Builder] Failed to create home: ' . $post_id->get_error_message() );
			}

			return null;
		}

		// Set default layout with profile card (always present, cannot delete)
		$default_layout = array(
			'widgets' => array(
				array(
					'id'     => 'profile-card-default',
					'type'   => 'profile-card',
					'x'      => 24,
					'y'      => 24,
					'width'  => 280,
					'height' => 200,
					'zIndex' => 1,
					'config' => array(),
				),
			),
		);

		update_post_meta( $post_id, APOLLO_BUILDER_META_CONTENT, wp_json_encode( $default_layout ) );

		return get_post( $post_id );
	}

	/**
	 * Get home post for user (without creating)
	 *
	 * @param int $user_id User ID
	 * @return WP_Post|null
	 */
	public static function get_home( $user_id ) {
		if ( $user_id <= 0 ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 1,
			)
		);

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * Check if user can edit home
	 *
	 * Pattern: Live Composer capability check
	 *
	 * @param int      $post_id Post ID
	 * @param int|null $user_id User ID (defaults to current)
	 * @return bool
	 */
	public static function user_can_edit( $post_id, $user_id = null ) {
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return false;
		}

		// Owner can edit
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		// Admins can edit
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get layout data for a home
	 *
	 * @param int $post_id Post ID
	 * @return array Layout array with widgets
	 */
	public static function get_layout( $post_id ) {
		$json   = get_post_meta( $post_id, APOLLO_BUILDER_META_CONTENT, true );
		$layout = json_decode( $json, true );

		if ( ! is_array( $layout ) || ! isset( $layout['widgets'] ) ) {
			return array( 'widgets' => array() );
		}

		return $layout;
	}

	/**
	 * Save layout data for a home
	 *
	 * Pattern: WOW wow_page_save() saves to _wow_content
	 *
	 * @param int    $post_id Post ID
	 * @param string $layout_json JSON string
	 * @return bool Success
	 */
	public static function save_layout( $post_id, $layout_json ) {
		// Sanitize
		$sanitized = apollo_builder_sanitize_layout( $layout_json );

		// Save
		$result = update_post_meta( $post_id, APOLLO_BUILDER_META_CONTENT, $sanitized );

		// Mark post as using builder (pattern: WOW _wow_current_post_editor)
		update_post_meta( $post_id, '_apollo_builder_active', 'yes' );

		return $result !== false;
	}
}
