<?php
/**
 * FASE 2: Likes Endpoint
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LikesEndpoint {

	/**
	 * Register REST routes
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function registerRoutes(): void {
		register_rest_route(
			'apollo/v1',
			'wow',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'toggleLike' ),
				'permission_callback' => array( $this, 'checkPermission' ),
				'args'                => array(
					'content_type' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'Content type.', 'apollo-social' ),
						'sanitize_callback' => function ( $param ) {
							return sanitize_text_field( wp_unslash( $param ) );
						},
						'validate_callback' => function ( $param ) {
							$allowed_types = array( 'apollo_social_post', 'event_listing', 'post', 'apollo_ad' );
							return in_array( $param, $allowed_types, true );
						},
					),
					'content_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'Content ID.', 'apollo-social' ),
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return absint( $param ) > 0;
						},
					),
				),
			)
		);

		register_rest_route(
			'apollo/v1',
			'wow/(?P<content_type>[a-zA-Z0-9_-]+)/(?P<content_id>\d+)',
			array(
				'methods'                              => 'GET',
				'callback'                             => array( $this, 'getLikeStatus' ),
				'permission_callback'                  => '__return_true',
				// Público, mas retorna false se não logado
												'args' => array(
													'content_type' => array(
														'required'          => true,
														'type'              => 'string',
														'description'       => __( 'Content type.', 'apollo-social' ),
														'sanitize_callback' => function ( $param ) {
															return sanitize_text_field( wp_unslash( $param ) );
														},
														'validate_callback' => function ( $param ) {
															$allowed_types = array( 'apollo_social_post', 'event_listing', 'post', 'apollo_ad' );
															return in_array( $param, $allowed_types, true );
														},
													),
													'content_id'   => array(
														'required'          => true,
														'type'              => 'integer',
														'description'       => __( 'Content ID.', 'apollo-social' ),
														'sanitize_callback' => 'absint',
														'validate_callback' => function ( $param ) {
															return absint( $param ) > 0;
														},
													),
												),
			)
		);
	}

	/**
	 * Check if user can like
	 */
	public function checkPermission(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Require basic read capability
		return current_user_can( 'read' );
	}

	/**
	 * Toggle like (adicionar ou remover)
	 */
	public function toggleLike( \WP_REST_Request $request ) {
		// Parameters are already sanitized and validated by REST API args callbacks
		$content_type = $request->get_param( 'content_type' );
		$content_id   = absint( $request->get_param( 'content_id' ) );
		$user_id      = get_current_user_id();

		if ( ! $content_type || ! $content_id || ! $user_id ) {
			return new \WP_Error(
				'invalid_params',
				__( 'Parâmetros inválidos.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'apollo_likes';

		// Verificar se já curtiu
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE content_type = %s AND content_id = %d AND user_id = %d",
				$content_type,
				$content_id,
				$user_id
			)
		);

		if ( $existing ) {
			// Remover like
			$deleted = $wpdb->delete(
				$table_name,
				array(
					'content_type' => $content_type,
					'content_id'   => $content_id,
					'user_id'      => $user_id,
				),
				array( '%s', '%d', '%d' )
			);

			if ( $deleted ) {
				// Atualizar meta cache
				$this->updateLikeCountMeta( $content_type, $content_id );

				return new \WP_REST_Response(
					array(
						'success'    => true,
						'liked'      => false,
						'like_count' => $this->getLikeCount( $content_type, $content_id ),
					),
					200
				);
			}
		} else {
			// Adicionar like
			$inserted = $wpdb->insert(
				$table_name,
				array(
					'content_type' => $content_type,
					'content_id'   => $content_id,
					'user_id'      => $user_id,
					'liked_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%d', '%d', '%s' )
			);

			if ( $inserted ) {
				// Atualizar meta cache
				$this->updateLikeCountMeta( $content_type, $content_id );

				return new \WP_REST_Response(
					array(
						'success'    => true,
						'liked'      => true,
						'like_count' => $this->getLikeCount( $content_type, $content_id ),
					),
					200
				);
			}
		}//end if

		return new \WP_Error(
			'database_error',
			__( 'Erro ao processar like.', 'apollo-social' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Obter status de like
	 */
	public function getLikeStatus( \WP_REST_Request $request ) {
		// Parameters are already sanitized and validated by REST API args callbacks
		$content_type = $request->get_param( 'content_type' );
		$content_id   = absint( $request->get_param( 'content_id' ) );
		$user_id      = get_current_user_id();

		$like_count = $this->getLikeCount( $content_type, $content_id );
		$user_liked = $user_id ? $this->userLiked( $content_type, $content_id, $user_id ) : false;

		return new \WP_REST_Response(
			array(
				'like_count' => $like_count,
				'user_liked' => $user_liked,
			),
			200
		);
	}

	/**
	 * Obter contagem de likes
	 */
	private function getLikeCount( $content_type, $content_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'apollo_likes';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE content_type = %s AND content_id = %d",
				$content_type,
				$content_id
			)
		);
	}

	/**
	 * Verificar se usuário curtiu
	 */
	private function userLiked( $content_type, $content_id, $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'apollo_likes';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE content_type = %s AND content_id = %d AND user_id = %d",
				$content_type,
				$content_id,
				$user_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Atualizar meta cache de contagem
	 */
	private function updateLikeCountMeta( $content_type, $content_id ) {
		$like_count = $this->getLikeCount( $content_type, $content_id );

		if ( $content_type === 'apollo_social_post' || $content_type === 'event_listing' || $content_type === 'post' ) {
			update_post_meta( $content_id, '_apollo_like_count', $like_count );
		}
	}
}
