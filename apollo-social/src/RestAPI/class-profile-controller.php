<?php
/**
 * Apollo User Profile REST Controller
 *
 * REST API controller for user profile endpoints.
 *
 * @package Apollo_Social
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\RestAPI;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load base controller.
if ( ! class_exists( 'Apollo_Core\REST_API\Apollo_REST_Controller' ) ) {
	return;
}

/**
 * Class Profile_Controller
 *
 * Handles user profile operations via REST API.
 *
 * @since 2.0.0
 */
class Profile_Controller extends \Apollo_Core\REST_API\Apollo_REST_Controller {

	/**
	 * REST base.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $rest_base = 'users';

	/**
	 * Register routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes(): void {
		// GET /users/{id}/profile - Get user profile.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/profile',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_profile' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'ID do usuário.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// PUT /users/{id}/profile - Update user profile.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/profile',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_profile' ),
				'permission_callback' => array( $this, 'check_update_profile_permission' ),
				'args'                => $this->get_profile_update_params(),
			)
		);

		// GET /users/{id}/activity - Get user activity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/activity',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_activity' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'description'       => __( 'ID do usuário.', 'apollo-social' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_collection_params()
				),
			)
		);

		// GET /users/{id}/favorites - Get user favorites.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/favorites',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_favorites' ),
				'permission_callback' => array( $this, 'check_favorites_permission' ),
				'args'                => array(
					'id'   => array(
						'description'       => __( 'ID do usuário.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'type' => array(
						'description' => __( 'Tipo de favorito.', 'apollo-social' ),
						'type'        => 'string',
						'default'     => 'events',
						'enum'        => array( 'events', 'venues', 'all' ),
					),
				),
			)
		);

		// POST /users/{id}/follow - Toggle follow.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/follow',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_follow' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'ID do usuário a seguir.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /users/{id}/followers - Get user followers.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/followers',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_followers' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'description'       => __( 'ID do usuário.', 'apollo-social' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_collection_params()
				),
			)
		);

		// GET /users/{id}/following - Get users being followed.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/following',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_following' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'description'       => __( 'ID do usuário.', 'apollo-social' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_collection_params()
				),
			)
		);
	}

	/**
	 * Get user profile.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_profile( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$user = get_userdata( $id );

		if ( ! $user ) {
			return $this->error(
				'user_not_found',
				__( 'Usuário não encontrado.', 'apollo-social' ),
				404
			);
		}

		$profile = $this->prepare_profile_for_response( $user, $request );

		return $this->success( $profile );
	}

	/**
	 * Update user profile.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_profile( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$user = get_userdata( $id );

		if ( ! $user ) {
			return $this->error(
				'user_not_found',
				__( 'Usuário não encontrado.', 'apollo-social' ),
				404
			);
		}

		$user_data = array( 'ID' => $id );

		// Update display name.
		$display_name = $request->get_param( 'display_name' );
		if ( $display_name !== null ) {
			$user_data['display_name'] = $this->sanitize_text( $display_name );
		}

		// Update description.
		$description = $request->get_param( 'description' );
		if ( $description !== null ) {
			$user_data['description'] = $this->sanitize_textarea( $description );
		}

		// Update user website.
		$user_url = $request->get_param( 'user_url' );
		if ( $user_url !== null ) {
			$user_data['user_url'] = esc_url_raw( $user_url );
		}

		$updated = wp_update_user( $user_data );

		if ( is_wp_error( $updated ) ) {
			return $this->error(
				'update_failed',
				$updated->get_error_message(),
				500
			);
		}

		// Update custom meta.
		$meta_fields = array(
			'location'    => '_apollo_user_location',
			'phone'       => '_apollo_user_phone',
			'instagram'   => '_apollo_user_instagram',
			'facebook'    => '_apollo_user_facebook',
			'twitter'     => '_apollo_user_twitter',
			'music_style' => '_apollo_user_music_style',
		);

		foreach ( $meta_fields as $param => $meta_key ) {
			$value = $request->get_param( $param );
			if ( $value !== null ) {
				update_user_meta( $id, $meta_key, $this->sanitize_text( $value ) );
			}
		}

		$user    = get_userdata( $id );
		$profile = $this->prepare_profile_for_response( $user, $request );

		return $this->success(
			$profile,
			__( 'Perfil atualizado com sucesso.', 'apollo-social' )
		);
	}

	/**
	 * Get user activity.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_activity( $request ) {
		global $wpdb;

		$id         = (int) $request->get_param( 'id' );
		$pagination = $this->get_pagination_params( $request );
		$table_name = $wpdb->prefix . 'apollo_activities';

		// Count total.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
				$id
			)
		);

		// Get activities.
		$activities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$id,
				$pagination['per_page'],
				$pagination['offset']
			)
		);

		$items = array();
		foreach ( $activities as $activity ) {
			$items[] = array(
				'id'         => (int) $activity->id,
				'type'       => $activity->type,
				'content'    => $activity->content,
				'created_at' => $activity->created_at,
				'time_ago'   => human_time_diff( strtotime( $activity->created_at ) ) . ' atrás',
			);
		}

		return $this->paginated_response(
			$items,
			$total,
			$pagination['page'],
			$pagination['per_page'],
			$request
		);
	}

	/**
	 * Get user favorites.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_favorites( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$type = $request->get_param( 'type' );

		$favorites = array(
			'events' => array(),
			'venues' => array(),
		);

		if ( $type === 'events' || $type === 'all' ) {
			$event_ids = get_user_meta( $id, '_apollo_favorite_events', true ) ?: array();
			foreach ( (array) $event_ids as $event_id ) {
				$post = get_post( $event_id );
				if ( $post && $post->post_status === 'publish' ) {
					$favorites['events'][] = array(
						'id'         => $post->ID,
						'title'      => get_the_title( $post ),
						'link'       => get_permalink( $post ),
						'thumbnail'  => get_the_post_thumbnail_url( $post, 'thumbnail' ),
						'start_date' => get_post_meta( $post->ID, '_event_start_date', true ),
					);
				}
			}
		}

		if ( $type === 'venues' || $type === 'all' ) {
			$venue_ids = get_user_meta( $id, '_apollo_favorite_venues', true ) ?: array();
			foreach ( (array) $venue_ids as $venue_id ) {
				$post = get_post( $venue_id );
				if ( $post && $post->post_status === 'publish' ) {
					$favorites['venues'][] = array(
						'id'        => $post->ID,
						'title'     => get_the_title( $post ),
						'link'      => get_permalink( $post ),
						'thumbnail' => get_the_post_thumbnail_url( $post, 'thumbnail' ),
						'address'   => get_post_meta( $post->ID, '_venue_address', true ),
					);
				}
			}
		}

		if ( $type === 'events' ) {
			return $this->success( $favorites['events'] );
		}

		if ( $type === 'venues' ) {
			return $this->success( $favorites['venues'] );
		}

		return $this->success( $favorites );
	}

	/**
	 * Toggle follow.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_follow( $request ) {
		global $wpdb;

		$target_id  = (int) $request->get_param( 'id' );
		$user_id    = get_current_user_id();
		$table_name = $wpdb->prefix . 'apollo_user_follows';

		// Can't follow yourself.
		if ( $target_id === $user_id ) {
			return $this->error(
				'cannot_follow_self',
				__( 'Você não pode seguir a si mesmo.', 'apollo-social' ),
				400
			);
		}

		// Check if target user exists.
		if ( ! get_userdata( $target_id ) ) {
			return $this->error(
				'user_not_found',
				__( 'Usuário não encontrado.', 'apollo-social' ),
				404
			);
		}

		// Check if already following.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE follower_id = %d AND following_id = %d",
				$user_id,
				$target_id
			)
		);

		if ( $existing ) {
			// Unfollow.
			$wpdb->delete(
				$table_name,
				array(
					'follower_id'  => $user_id,
					'following_id' => $target_id,
				),
				array( '%d', '%d' )
			);
			$following = false;
			$message   = __( 'Você deixou de seguir este usuário.', 'apollo-social' );
		} else {
			// Follow.
			$wpdb->insert(
				$table_name,
				array(
					'follower_id'  => $user_id,
					'following_id' => $target_id,
					'created_at'   => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
			$following = true;
			$message   = __( 'Você está seguindo este usuário.', 'apollo-social' );

			/**
			 * Fires when a user follows another.
			 *
			 * @since 2.0.0
			 *
			 * @param int $user_id   The follower user ID.
			 * @param int $target_id The followed user ID.
			 */
			do_action( 'apollo_user_followed', $user_id, $target_id );
		}

		// Get updated counts.
		$followers_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE following_id = %d",
				$target_id
			)
		);

		return $this->success(
			array(
				'user_id'         => $target_id,
				'following'       => $following,
				'followers_count' => $followers_count,
			),
			$message
		);
	}

	/**
	 * Get followers.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_followers( $request ) {
		global $wpdb;

		$id         = (int) $request->get_param( 'id' );
		$pagination = $this->get_pagination_params( $request );
		$table_name = $wpdb->prefix . 'apollo_user_follows';

		// Count total.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE following_id = %d",
				$id
			)
		);

		// Get followers.
		$followers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT follower_id FROM $table_name WHERE following_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$id,
				$pagination['per_page'],
				$pagination['offset']
			)
		);

		$items = array();
		foreach ( $followers as $follower ) {
			$user = get_userdata( (int) $follower->follower_id );
			if ( $user ) {
				$items[] = $this->prepare_user_summary( $user );
			}
		}

		return $this->paginated_response(
			$items,
			$total,
			$pagination['page'],
			$pagination['per_page'],
			$request
		);
	}

	/**
	 * Get following.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_following( $request ) {
		global $wpdb;

		$id         = (int) $request->get_param( 'id' );
		$pagination = $this->get_pagination_params( $request );
		$table_name = $wpdb->prefix . 'apollo_user_follows';

		// Count total.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE follower_id = %d",
				$id
			)
		);

		// Get following.
		$following = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT following_id FROM $table_name WHERE follower_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$id,
				$pagination['per_page'],
				$pagination['offset']
			)
		);

		$items = array();
		foreach ( $following as $follow ) {
			$user = get_userdata( (int) $follow->following_id );
			if ( $user ) {
				$items[] = $this->prepare_user_summary( $user );
			}
		}

		return $this->paginated_response(
			$items,
			$total,
			$pagination['page'],
			$pagination['per_page'],
			$request
		);
	}

	/**
	 * Check update profile permission.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function check_update_profile_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Você precisa estar logado.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		$id      = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		// Users can only update their own profile unless admin.
		if ( $id !== $user_id && ! current_user_can( 'edit_users' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Você não pode editar este perfil.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check favorites permission.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function check_favorites_permission( $request ) {
		$id      = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		// Public favorites require user meta flag.
		if ( $id !== $user_id ) {
			$public = get_user_meta( $id, '_apollo_favorites_public', true );
			if ( ! $public && ! current_user_can( 'edit_users' ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'Os favoritos deste usuário são privados.', 'apollo-social' ),
					array( 'status' => 403 )
				);
			}
		}

		return true;
	}

	/**
	 * Prepare profile for response.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_User         $user    The user object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function prepare_profile_for_response( \WP_User $user, \WP_REST_Request $request ): array {
		global $wpdb;

		$data = array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'username'     => $user->user_login,
			'description'  => $user->description,
			'user_url'     => $user->user_url,
			'registered'   => $user->user_registered,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 150 ) ),
			'cover'        => get_user_meta( $user->ID, '_apollo_cover_image', true ),
		);

		// Custom meta.
		$meta_fields = array(
			'location'    => '_apollo_user_location',
			'phone'       => '_apollo_user_phone',
			'instagram'   => '_apollo_user_instagram',
			'facebook'    => '_apollo_user_facebook',
			'twitter'     => '_apollo_user_twitter',
			'music_style' => '_apollo_user_music_style',
		);

		$data['meta'] = array();
		foreach ( $meta_fields as $key => $meta_key ) {
			$data['meta'][ $key ] = get_user_meta( $user->ID, $meta_key, true ) ?: '';
		}

		// Stats.
		$table_name = $wpdb->prefix . 'apollo_user_follows';

		$data['stats'] = array(
			'followers' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE following_id = %d",
					$user->ID
				)
			),
			'following' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE follower_id = %d",
					$user->ID
				)
			),
			'events'    => (int) wp_count_posts( 'event_listing' )->publish ?? 0,
		);

		// Check if current user follows.
		$current_user = get_current_user_id();
		if ( $current_user && $current_user !== $user->ID ) {
			$data['is_following'] = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $table_name WHERE follower_id = %d AND following_id = %d",
					$current_user,
					$user->ID
				)
			);
		} else {
			$data['is_following'] = false;
		}

		// Profile URL.
		if ( function_exists( 'bp_core_get_user_domain' ) ) {
			$data['profile_url'] = bp_core_get_user_domain( $user->ID );
		} else {
			$data['profile_url'] = get_author_posts_url( $user->ID );
		}

		return $data;
	}

	/**
	 * Prepare user summary.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_User $user The user object.
	 * @return array
	 */
	protected function prepare_user_summary( \WP_User $user ): array {
		return array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 48 ) ),
			'profile_url'  => function_exists( 'bp_core_get_user_domain' )
				? bp_core_get_user_domain( $user->ID )
				: get_author_posts_url( $user->ID ),
		);
	}

	/**
	 * Get profile update params.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_profile_update_params(): array {
		return array(
			'id'           => array(
				'description'       => __( 'ID do usuário.', 'apollo-social' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'display_name' => array(
				'description'       => __( 'Nome de exibição.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'description'  => array(
				'description'       => __( 'Bio do usuário.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'user_url'     => array(
				'description' => __( 'Website do usuário.', 'apollo-social' ),
				'type'        => 'string',
				'format'      => 'uri',
			),
			'location'     => array(
				'description'       => __( 'Localização.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone'        => array(
				'description'       => __( 'Telefone.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'instagram'    => array(
				'description'       => __( 'Instagram handle.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'facebook'     => array(
				'description' => __( 'Facebook URL.', 'apollo-social' ),
				'type'        => 'string',
				'format'      => 'uri',
			),
			'twitter'      => array(
				'description'       => __( 'Twitter handle.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'music_style'  => array(
				'description'       => __( 'Estilo musical favorito.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
