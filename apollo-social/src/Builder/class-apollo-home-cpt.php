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

// Load BlockRegistry and Block classes
require_once __DIR__ . '/Blocks/BlockInterface.php';
require_once __DIR__ . '/Blocks/AbstractBlock.php';
require_once __DIR__ . '/Blocks/BlockRegistry.php';

// Autoload block types
foreach ( glob( __DIR__ . '/Blocks/Types/*Block.php' ) as $block_file ) {
	require_once $block_file;
}

/**
 * Class Apollo_Home_CPT
 *
 * Data Model:
 * - CPT: apollo_home (one per user)
 * - Meta: _apollo_builder_content (JSON layout) - LEGACY Habbo-style
 * - Meta: _apollo_hub_content (JSON layout) - NEW HUB::rio Linktree-style
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

		// Initialize BlockRegistry (singleton)
		\Apollo_Social\Builder\Blocks\BlockRegistry::get_instance();
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

		// HUB::rio Linktree-style layout (Schema v2)
		register_post_meta(
			self::POST_TYPE,
			APOLLO_HUB_META_CONTENT,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_hub_layout' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'profile' => array(
								'type'       => 'object',
								'properties' => array(
									'name'    => array( 'type' => 'string' ),
									'bio'     => array( 'type' => 'string' ),
									'avatar'  => array( 'type' => 'string' ),
									'bg'      => array( 'type' => 'string' ),
									'primary' => array( 'type' => 'string' ),
									'accent'  => array( 'type' => 'string' ),
								),
							),
							'blocks'  => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'id'   => array( 'type' => 'string' ),
										'type' => array(
											'type' => 'string',
											'enum' => array( 'header', 'link', 'event', 'marquee', 'cards', 'divider', 'text', 'image', 'video', 'social', 'newsletter' ),
										),
									),
								),
							),
						),
					),
				),
				'description'       => 'HUB::rio Linktree layout: {profile: {...}, blocks: [{id, type, ...props}]}',
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

	/**
	 * Sanitize HUB::rio layout JSON
	 *
	 * @param string $json_string JSON layout
	 * @return string Sanitized JSON
	 */
	public static function sanitize_hub_layout( $json_string ) {
		$decoded = json_decode( $json_string, true );

		if ( ! is_array( $decoded ) ) {
			return wp_json_encode( self::get_default_hub_layout() );
		}

		// Validate structure
		if ( ! isset( $decoded['profile'] ) || ! is_array( $decoded['profile'] ) ) {
			$decoded['profile'] = array();
		}

		if ( ! isset( $decoded['blocks'] ) || ! is_array( $decoded['blocks'] ) ) {
			$decoded['blocks'] = array();
		}

		// Sanitize profile
		$profile = $decoded['profile'];
		$decoded['profile'] = array(
			'name'    => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : '',
			'bio'     => isset( $profile['bio'] ) ? sanitize_textarea_field( $profile['bio'] ) : '',
			'avatar'  => isset( $profile['avatar'] ) ? esc_url_raw( $profile['avatar'] ) : '',
			'bg'      => isset( $profile['bg'] ) ? esc_url_raw( $profile['bg'] ) : '',
			'primary' => isset( $profile['primary'] ) ? sanitize_hex_color( $profile['primary'] ) : '#18181b',
			'accent'  => isset( $profile['accent'] ) ? sanitize_hex_color( $profile['accent'] ) : '#F97316',
		);

		// Sanitize blocks
		$allowed_types = array( 'header', 'link', 'event', 'marquee', 'cards', 'divider', 'text', 'image', 'video', 'social', 'newsletter' );
		$clean_blocks = array();

		foreach ( $decoded['blocks'] as $block ) {
			if ( ! is_array( $block ) || ! isset( $block['type'] ) || ! in_array( $block['type'], $allowed_types, true ) ) {
				continue;
			}

			$clean_block = array(
				'id'   => isset( $block['id'] ) ? sanitize_key( $block['id'] ) : 'b' . uniqid(),
				'type' => sanitize_key( $block['type'] ),
			);

			// Sanitize type-specific props
			switch ( $block['type'] ) {
				case 'header':
				case 'text':
				case 'marquee':
					if ( isset( $block['text'] ) ) {
						$clean_block['text'] = sanitize_text_field( $block['text'] );
					}
					break;

				case 'link':
					$clean_block['title'] = isset( $block['title'] ) ? sanitize_text_field( $block['title'] ) : '';
					$clean_block['sub']   = isset( $block['sub'] ) ? sanitize_text_field( $block['sub'] ) : '';
					$clean_block['url']   = isset( $block['url'] ) ? esc_url_raw( $block['url'] ) : '';
					$clean_block['icon']  = isset( $block['icon'] ) ? sanitize_text_field( $block['icon'] ) : 'link-s';
					break;

				case 'event':
					$clean_block['title'] = isset( $block['title'] ) ? sanitize_text_field( $block['title'] ) : '';
					$clean_block['day']   = isset( $block['day'] ) ? sanitize_text_field( $block['day'] ) : '';
					$clean_block['month'] = isset( $block['month'] ) ? sanitize_text_field( $block['month'] ) : '';
					$clean_block['url']   = isset( $block['url'] ) ? esc_url_raw( $block['url'] ) : '';
					break;

				case 'image':
					$clean_block['img']     = isset( $block['img'] ) ? esc_url_raw( $block['img'] ) : '';
					$clean_block['caption'] = isset( $block['caption'] ) ? sanitize_text_field( $block['caption'] ) : '';
					break;

				case 'video':
					$clean_block['url'] = isset( $block['url'] ) ? esc_url_raw( $block['url'] ) : '';
					break;

				case 'cards':
					if ( isset( $block['cards'] ) && is_array( $block['cards'] ) ) {
						$clean_cards = array();
						foreach ( $block['cards'] as $card ) {
							if ( is_array( $card ) ) {
								$clean_cards[] = array(
									'title' => isset( $card['title'] ) ? sanitize_text_field( $card['title'] ) : '',
									'img'   => isset( $card['img'] ) ? esc_url_raw( $card['img'] ) : '',
								);
							}
						}
						$clean_block['cards'] = $clean_cards;
					}
					break;

				case 'social':
					if ( isset( $block['icons'] ) && is_array( $block['icons'] ) ) {
						$clean_icons = array();
						foreach ( $block['icons'] as $icon ) {
							if ( is_array( $icon ) ) {
								$clean_icons[] = array(
									'icon' => isset( $icon['icon'] ) ? sanitize_text_field( $icon['icon'] ) : 'link-s',
									'url'  => isset( $icon['url'] ) ? esc_url_raw( $icon['url'] ) : '',
								);
							}
						}
						$clean_block['icons'] = $clean_icons;
					}
					break;

				case 'newsletter':
					$clean_block['title']       = isset( $block['title'] ) ? sanitize_text_field( $block['title'] ) : '';
					$clean_block['placeholder'] = isset( $block['placeholder'] ) ? sanitize_text_field( $block['placeholder'] ) : '';
					$clean_block['button']      = isset( $block['button'] ) ? sanitize_text_field( $block['button'] ) : '';
					break;
			}

			$clean_blocks[] = $clean_block;
		}

		$decoded['blocks'] = $clean_blocks;

		return wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Get default HUB::rio layout
	 *
	 * @return array Default layout structure
	 */
	public static function get_default_hub_layout() {
		return array(
			'profile' => array(
				'name'    => '',
				'bio'     => '',
				'avatar'  => '',
				'bg'      => '',
				'primary' => '#18181b',
				'accent'  => '#F97316',
			),
			'blocks'  => array(
				array(
					'id'   => 'b0',
					'type' => 'header',
					'text' => __( 'Bem-vindo', 'apollo-social' ),
				),
				array(
					'id'    => 'b1',
					'type'  => 'link',
					'title' => __( 'Meu Primeiro Link', 'apollo-social' ),
					'sub'   => __( 'Clique para editar', 'apollo-social' ),
					'url'   => '#',
					'icon'  => 'link-s',
				),
			),
		);
	}

	/**
	 * Get HUB::rio layout for a home post
	 *
	 * @param int $post_id Post ID
	 * @return array Layout array with profile and blocks
	 */
	public static function get_hub_layout( $post_id ) {
		$json = get_post_meta( $post_id, APOLLO_HUB_META_CONTENT, true );

		if ( empty( $json ) ) {
			return self::get_default_hub_layout();
		}

		$layout = json_decode( $json, true );

		if ( ! is_array( $layout ) || ! isset( $layout['profile'] ) || ! isset( $layout['blocks'] ) ) {
			return self::get_default_hub_layout();
		}

		return $layout;
	}

	/**
	 * Save HUB::rio layout
	 *
	 * @param int   $post_id Post ID
	 * @param array $layout Layout array
	 * @return bool Success
	 */
	public static function save_hub_layout( $post_id, $layout ) {
		$json      = wp_json_encode( $layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$sanitized = self::sanitize_hub_layout( $json );

		$result = update_post_meta( $post_id, APOLLO_HUB_META_CONTENT, $sanitized );

		// Mark as using HUB::rio (schema v2)
		update_post_meta( $post_id, '_apollo_hub_schema_version', APOLLO_HUB_SCHEMA_VERSION );

		return $result !== false;
	}
}
