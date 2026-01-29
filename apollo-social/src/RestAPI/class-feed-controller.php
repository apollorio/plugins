<?php
/**
 * Apollo Social Feed REST Controller
 *
 * REST API controller for social feed endpoints.
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
 * Class Feed_Controller
 *
 * Handles social feed operations via REST API.
 *
 * @since 2.0.0
 */
class Feed_Controller extends \Apollo_Core\REST_API\Apollo_REST_Controller {

	/**
	 * REST base.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $rest_base = 'social/feed';

	/**
	 * Register routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes(): void {
		// GET /social/feed - Get feed items.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => $this->get_collection_params(),
			)
		);

		// POST /social/feed - Create activity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'args'                => $this->get_activity_create_params(),
			)
		);

		// GET /social/feed/{id} - Get single activity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'ID da atividade.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// DELETE /social/feed/{id} - Delete activity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'check_delete_activity_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'ID da atividade.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /social/feed/{id}/like - Toggle like.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/like',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_like' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'ID da atividade.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /social/feed/{id}/comment - Add comment.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/comment',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_comment' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'args'                => array(
					'id'      => array(
						'description'       => __( 'ID da atividade.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'content' => array(
						'description'       => __( 'Conteúdo do comentário.', 'apollo-social' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Get feed items.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		global $wpdb;

		$pagination = $this->get_pagination_params( $request );
		$table_name = $wpdb->prefix . 'apollo_activities';

		// Check if table exists.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return $this->success( array() );
		}

		$where  = 'WHERE 1=1';
		$values = array();

		// Filter by type.
		$type = $request->get_param( 'type' );
		if ( ! empty( $type ) ) {
			$where   .= ' AND type = %s';
			$values[] = $this->sanitize_text( $type );
		}

		// Filter by user.
		$user_id = $request->get_param( 'user_id' );
		if ( $user_id ) {
			$where   .= ' AND user_id = %d';
			$values[] = $this->sanitize_int( $user_id );
		}

		// Count total.
		$count_sql = "SELECT COUNT(*) FROM $table_name $where";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values );
		}
		$total = (int) $wpdb->get_var( $count_sql );

		// Get items.
		$sql      = "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$values[] = $pagination['per_page'];
		$values[] = $pagination['offset'];

		$sql        = $wpdb->prepare( $sql, $values );
		$activities = $wpdb->get_results( $sql );

		$items = array();
		foreach ( $activities as $activity ) {
			$items[] = $this->prepare_activity_for_response( $activity, $request );
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
	 * Get single activity.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		global $wpdb;

		$id         = (int) $request->get_param( 'id' );
		$table_name = $wpdb->prefix . 'apollo_activities';

		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id )
		);

		if ( ! $activity ) {
			return $this->error(
				'activity_not_found',
				__( 'Atividade não encontrada.', 'apollo-social' ),
				404
			);
		}

		return $this->success(
			$this->prepare_activity_for_response( $activity, $request, true )
		);
	}

	/**
	 * Create activity.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_activities';
		$user_id    = get_current_user_id();

		$type    = $this->sanitize_text( $request->get_param( 'type' ) ?? 'status' );
		$content = $this->sanitize_textarea( $request->get_param( 'content' ) );

		if ( empty( $content ) ) {
			return $this->error(
				'content_required',
				__( 'O conteúdo é obrigatório.', 'apollo-social' ),
				400
			);
		}

		// Rate limiting - max 10 posts per hour.
		$recent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
				$user_id
			)
		);

		if ( (int) $recent >= 10 ) {
			return $this->error(
				'rate_limit_exceeded',
				__( 'Você atingiu o limite de publicações. Tente novamente mais tarde.', 'apollo-social' ),
				429
			);
		}

		$data = array(
			'user_id'    => $user_id,
			'type'       => $type,
			'content'    => $content,
			'meta'       => wp_json_encode(
				array(
					'object_id'   => $this->sanitize_int( $request->get_param( 'object_id' ) ),
					'object_type' => $this->sanitize_text( $request->get_param( 'object_type' ) ),
				)
			),
			'created_at' => current_time( 'mysql' ),
		);

		$inserted = $wpdb->insert( $table_name, $data, array( '%d', '%s', '%s', '%s', '%s' ) );

		if ( ! $inserted ) {
			return $this->error(
				'create_failed',
				__( 'Falha ao criar atividade.', 'apollo-social' ),
				500
			);
		}

		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $wpdb->insert_id )
		);

		/**
		 * Fires after an activity is created.
		 *
		 * @since 2.0.0
		 *
		 * @param object $activity The activity object.
		 * @param \WP_REST_Request $request The request object.
		 */
		do_action( 'apollo_activity_created', $activity, $request );

		return $this->success(
			$this->prepare_activity_for_response( $activity, $request ),
			__( 'Atividade criada com sucesso.', 'apollo-social' ),
			201
		);
	}

	/**
	 * Delete activity.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		global $wpdb;

		$id         = (int) $request->get_param( 'id' );
		$table_name = $wpdb->prefix . 'apollo_activities';

		$deleted = $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );

		if ( ! $deleted ) {
			return $this->error(
				'delete_failed',
				__( 'Falha ao excluir atividade.', 'apollo-social' ),
				500
			);
		}

		// Delete related likes and comments.
		$wpdb->delete( $wpdb->prefix . 'apollo_activity_likes', array( 'activity_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'apollo_activity_comments', array( 'activity_id' => $id ), array( '%d' ) );

		return $this->success(
			array(
				'id'      => $id,
				'deleted' => true,
			),
			__( 'Atividade excluída com sucesso.', 'apollo-social' )
		);
	}

	/**
	 * Toggle like.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_like( $request ) {
		global $wpdb;

		$activity_id = (int) $request->get_param( 'id' );
		$user_id     = get_current_user_id();
		$table_name  = $wpdb->prefix . 'apollo_activity_likes';

		// Check if already liked.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE activity_id = %d AND user_id = %d",
				$activity_id,
				$user_id
			)
		);

		if ( $existing ) {
			// Unlike.
			$wpdb->delete(
				$table_name,
				array(
					'activity_id' => $activity_id,
					'user_id'     => $user_id,
				),
				array( '%d', '%d' )
			);
			$liked   = false;
			$message = __( 'Curtida removida.', 'apollo-social' );
		} else {
			// Like.
			$wpdb->insert(
				$table_name,
				array(
					'activity_id' => $activity_id,
					'user_id'     => $user_id,
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
			$liked   = true;
			$message = __( 'Curtida adicionada.', 'apollo-social' );
		}

		// Get updated count.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE activity_id = %d",
				$activity_id
			)
		);

		return $this->success(
			array(
				'activity_id' => $activity_id,
				'liked'       => $liked,
				'count'       => $count,
			),
			$message
		);
	}

	/**
	 * Add comment.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function add_comment( $request ) {
		global $wpdb;

		$activity_id = (int) $request->get_param( 'id' );
		$user_id     = get_current_user_id();
		$content     = $this->sanitize_textarea( $request->get_param( 'content' ) );
		$table_name  = $wpdb->prefix . 'apollo_activity_comments';

		if ( empty( $content ) ) {
			return $this->error(
				'content_required',
				__( 'O conteúdo do comentário é obrigatório.', 'apollo-social' ),
				400
			);
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'activity_id' => $activity_id,
				'user_id'     => $user_id,
				'content'     => $content,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return $this->error(
				'comment_failed',
				__( 'Falha ao adicionar comentário.', 'apollo-social' ),
				500
			);
		}

		$comment_id = $wpdb->insert_id;
		$user       = get_userdata( $user_id );

		return $this->success(
			array(
				'id'          => $comment_id,
				'activity_id' => $activity_id,
				'content'     => $content,
				'author'      => array(
					'id'     => $user_id,
					'name'   => $user->display_name,
					'avatar' => get_avatar_url( $user_id, array( 'size' => 40 ) ),
				),
				'created_at'  => current_time( 'c' ),
			),
			__( 'Comentário adicionado.', 'apollo-social' ),
			201
		);
	}

	/**
	 * Check delete permission.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function check_delete_activity_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Você precisa estar logado.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		global $wpdb;
		$id         = (int) $request->get_param( 'id' );
		$user_id    = get_current_user_id();
		$table_name = $wpdb->prefix . 'apollo_activities';

		$activity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id FROM $table_name WHERE id = %d",
				$id
			)
		);

		if ( ! $activity ) {
			return new \WP_Error(
				'activity_not_found',
				__( 'Atividade não encontrada.', 'apollo-social' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $activity->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Você não pode excluir esta atividade.', 'apollo-social' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Prepare activity for response.
	 *
	 * @since 2.0.0
	 *
	 * @param object           $activity The activity object.
	 * @param \WP_REST_Request $request  The request object.
	 * @param bool             $full     Include full details.
	 * @return array
	 */
	protected function prepare_activity_for_response( object $activity, \WP_REST_Request $request, bool $full = false ): array {
		global $wpdb;

		$user    = get_userdata( (int) $activity->user_id );
		$user_id = get_current_user_id();

		$data = array(
			'id'         => (int) $activity->id,
			'type'       => $activity->type,
			'content'    => $activity->content,
			'meta'       => json_decode( $activity->meta ?? '{}', true ),
			'author'     => array(
				'id'     => (int) $activity->user_id,
				'name'   => $user ? $user->display_name : __( 'Usuário', 'apollo-social' ),
				'avatar' => get_avatar_url( (int) $activity->user_id, array( 'size' => 48 ) ),
				'url'    => function_exists( 'bp_core_get_user_domain' )
					? bp_core_get_user_domain( (int) $activity->user_id )
					: get_author_posts_url( (int) $activity->user_id ),
			),
			'created_at' => $activity->created_at,
			'time_ago'   => human_time_diff( strtotime( $activity->created_at ) ) . ' atrás',
		);

		// Likes count.
		$data['likes_count'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_activity_likes WHERE activity_id = %d",
				$activity->id
			)
		);

		// Comments count.
		$data['comments_count'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_activity_comments WHERE activity_id = %d",
				$activity->id
			)
		);

		// Check if current user liked.
		if ( $user_id ) {
			$data['is_liked'] = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}apollo_activity_likes WHERE activity_id = %d AND user_id = %d",
					$activity->id,
					$user_id
				)
			);
		} else {
			$data['is_liked'] = false;
		}

		// Include comments on full view.
		if ( $full ) {
			$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}apollo_activity_comments WHERE activity_id = %d ORDER BY created_at ASC LIMIT 50",
					$activity->id
				)
			);

			$data['comments'] = array();
			foreach ( $comments as $comment ) {
				$comment_user       = get_userdata( (int) $comment->user_id );
				$data['comments'][] = array(
					'id'         => (int) $comment->id,
					'content'    => $comment->content,
					'author'     => array(
						'id'     => (int) $comment->user_id,
						'name'   => $comment_user ? $comment_user->display_name : __( 'Usuário', 'apollo-social' ),
						'avatar' => get_avatar_url( (int) $comment->user_id, array( 'size' => 32 ) ),
					),
					'created_at' => $comment->created_at,
					'time_ago'   => human_time_diff( strtotime( $comment->created_at ) ) . ' atrás',
				);
			}
		}

		return $data;
	}

	/**
	 * Get activity create params.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_activity_create_params(): array {
		return array(
			'type'        => array(
				'description' => __( 'Tipo de atividade.', 'apollo-social' ),
				'type'        => 'string',
				'default'     => 'status',
				'enum'        => array( 'status', 'event', 'review', 'photo' ),
			),
			'content'     => array(
				'description'       => __( 'Conteúdo da atividade.', 'apollo-social' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'object_id'   => array(
				'description'       => __( 'ID do objeto relacionado.', 'apollo-social' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'object_type' => array(
				'description' => __( 'Tipo do objeto relacionado.', 'apollo-social' ),
				'type'        => 'string',
				'enum'        => array( 'event', 'venue', 'post' ),
			),
		);
	}

	/**
	 * Get collection params.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		$params = parent::get_collection_params();

		$params['type'] = array(
			'description' => __( 'Filtrar por tipo de atividade.', 'apollo-social' ),
			'type'        => 'string',
			'enum'        => array( 'status', 'event', 'review', 'photo' ),
		);

		$params['user_id'] = array(
			'description'       => __( 'Filtrar por usuário.', 'apollo-social' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);

		return $params;
	}
}
