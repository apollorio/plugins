<?php
/**
 * Apollo Relationship REST Endpoints
 *
 * Exposes relationship data through the unified REST API namespace.
 * Provides endpoints for querying, connecting, and disconnecting related items.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Relationship_REST
 *
 * REST API endpoints for relationship queries.
 */
class Apollo_Relationship_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'apollo/v1';

	/**
	 * Registered endpoints cache.
	 *
	 * @var array
	 */
	private static array $registered_endpoints = array();

	/**
	 * Initialize REST endpoints.
	 *
	 * @return void
	 */
	public static function init(): void {
		\add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
		\add_filter( 'rest_prepare_apollo-event', array( self::class, 'add_relationship_links' ), 10, 3 );
		\add_filter( 'rest_prepare_apollo-dj', array( self::class, 'add_relationship_links' ), 10, 3 );
		\add_filter( 'rest_prepare_apollo-local', array( self::class, 'add_relationship_links' ), 10, 3 );
		\add_filter( 'rest_prepare_apollo-classified', array( self::class, 'add_relationship_links' ), 10, 3 );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		$schema = Apollo_Relationships::get_schema();

		foreach ( $schema as $name => $definition ) {
			// Skip reverse relationships (they're accessed via the inverse).
			if ( ! empty( $definition['is_reverse'] ) ) {
				continue;
			}

			// Skip if not exposed to REST.
			if ( isset( $definition['rest_exposed'] ) && false === $definition['rest_exposed'] ) {
				continue;
			}

			self::register_relationship_endpoints( $name, $definition );
		}

		// Register schema discovery endpoint.
		\register_rest_route(
			self::NAMESPACE,
			'/relationships',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_relationships_schema' ),
				'permission_callback' => '__return_true',
			)
		);

		// Register specific relationship schema.
		\register_rest_route(
			self::NAMESPACE,
			'/relationships/(?P<name>[a-z_]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_relationship_schema' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'name' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Register endpoints for a specific relationship.
	 *
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @return void
	 */
	private static function register_relationship_endpoints( string $name, array $definition ): void {
		$from_type = $definition['from'] ?? '';
		$to_type   = $definition['to'] ?? '';

		// Skip user-only relationships for now.
		if ( $from_type === 'user' ) {
			self::register_user_relationship_endpoints( $name, $definition );
			return;
		}

		// Get REST base for the post type.
		$from_base = self::get_rest_base( $from_type );
		if ( ! $from_base ) {
			return;
		}

		// Build relationship name for URL.
		$relation_slug = self::get_relation_slug( $name, $definition );

		// GET /apollo/v1/{post_type}/{id}/{relationship}
		\register_rest_route(
			self::NAMESPACE,
			'/' . $from_base . '/(?P<id>[\d]+)/' . $relation_slug,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => function ( $request ) use ( $name ) {
						return self::get_related( $request, $name );
					},
					'permission_callback' => '__return_true',
					'args'                => self::get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => function ( $request ) use ( $name ) {
						return self::connect_items( $request, $name );
					},
					'permission_callback' => function ( $request ) use ( $from_type ) {
						return self::check_edit_permission( $request, $from_type );
					},
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'description' => 'IDs to connect.',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => function ( $request ) use ( $name ) {
						return self::disconnect_items( $request, $name );
					},
					'permission_callback' => function ( $request ) use ( $from_type ) {
						return self::check_edit_permission( $request, $from_type );
					},
					'args'                => array(
						'ids' => array(
							'required'    => false,
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'description' => 'IDs to disconnect. If empty, disconnects all.',
						),
					),
				),
			)
		);

		// PUT /apollo/v1/{post_type}/{id}/{relationship} - Sync (replace all).
		\register_rest_route(
			self::NAMESPACE,
			'/' . $from_base . '/(?P<id>[\d]+)/' . $relation_slug,
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => function ( $request ) use ( $name ) {
					return self::sync_items( $request, $name );
				},
				'permission_callback' => function ( $request ) use ( $from_type ) {
					return self::check_edit_permission( $request, $from_type );
				},
				'args'                => array(
					'ids' => array(
						'required'    => true,
						'type'        => 'array',
						'items'       => array( 'type' => 'integer' ),
						'description' => 'Complete list of related IDs.',
					),
				),
			)
		);

		self::$registered_endpoints[ $name ] = $from_base . '/{id}/' . $relation_slug;
	}

	/**
	 * Register user relationship endpoints.
	 *
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @return void
	 */
	private static function register_user_relationship_endpoints( string $name, array $definition ): void {
		$relation_slug = self::get_relation_slug( $name, $definition );

		// GET /apollo/v1/users/{id}/{relationship}
		\register_rest_route(
			self::NAMESPACE,
			'/users/(?P<id>[\d]+)/' . $relation_slug,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function ( $request ) use ( $name ) {
					return self::get_user_related( $request, $name );
				},
				'permission_callback' => '__return_true',
				'args'                => self::get_collection_params(),
			)
		);

		// GET /apollo/v1/users/me/{relationship}
		\register_rest_route(
			self::NAMESPACE,
			'/users/me/' . $relation_slug,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function ( $request ) use ( $name ) {
					$request->set_param( 'id', \get_current_user_id() );
					return self::get_user_related( $request, $name );
				},
				'permission_callback' => function () {
					return \is_user_logged_in();
				},
				'args'                => self::get_collection_params(),
			)
		);

		self::$registered_endpoints[ $name ] = 'users/{id}/' . $relation_slug;
	}

	/**
	 * Get REST base for a post type.
	 *
	 * @param string $post_type Post type.
	 * @return string|null
	 */
	private static function get_rest_base( string $post_type ): ?string {
		$post_type_obj = \get_post_type_object( $post_type );

		if ( ! $post_type_obj || ! $post_type_obj->show_in_rest ) {
			// Use a default mapping for Apollo types.
			$default_bases = array(
				'apollo-event'      => 'events',
				'apollo-dj'         => 'djs',
				'apollo-local'      => 'locals',
				'apollo-classified' => 'classifieds',
				'apollo-supplier'   => 'suppliers',
				'apollo-social'     => 'social-posts',
			);

			return $default_bases[ $post_type ] ?? null;
		}

		return $post_type_obj->rest_base ?: $post_type;
	}

	/**
	 * Get relation slug from relationship name.
	 *
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @return string
	 */
	private static function get_relation_slug( string $name, array $definition ): string {
		// Custom REST slug.
		if ( ! empty( $definition['rest_slug'] ) ) {
			return $definition['rest_slug'];
		}

		// Extract from name (e.g., 'event_to_dj' -> 'djs').
		$parts = \explode( '_to_', $name );
		if ( isset( $parts[1] ) ) {
			// Pluralize.
			$slug = $parts[1];
			if ( \substr( $slug, -1 ) !== 's' ) {
				$slug .= 's';
			}
			return $slug;
		}

		return \str_replace( '_', '-', $name );
	}

	/**
	 * Get related items callback.
	 *
	 * @param \WP_REST_Request $request      Request object.
	 * @param string           $relationship Relationship name.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_related( \WP_REST_Request $request, string $relationship ) {
		$post_id  = (int) $request->get_param( 'id' );
		$page     = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?? 10 );

		// Verify post exists.
		$post = \get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error(
				'rest_post_not_found',
				__( 'Post not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		$result = Apollo_Relationship_Query::paginate( $post_id, $relationship, $page, $per_page );

		// Transform items to REST format.
		$items = \array_map(
			function ( $item ) {
				return self::prepare_item_for_response( $item );
			},
			$result['items']
		);

		$response = new \WP_REST_Response( $items );

		// Add pagination headers.
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['pages'] );

		// Add link headers.
		$base = \rest_url( self::NAMESPACE . '/' . self::get_rest_base( $post->post_type ) . '/' . $post_id . '/' . self::get_relation_slug( $relationship, Apollo_Relationships::get( $relationship ) ?? array() ) );

		if ( $page > 1 ) {
			$response->link_header( 'prev', \add_query_arg( 'page', $page - 1, $base ) );
		}
		if ( $page < $result['pages'] ) {
			$response->link_header( 'next', \add_query_arg( 'page', $page + 1, $base ) );
		}

		return $response;
	}

	/**
	 * Get user-related items callback.
	 *
	 * @param \WP_REST_Request $request      Request object.
	 * @param string           $relationship Relationship name.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_user_related( \WP_REST_Request $request, string $relationship ) {
		$user_id  = (int) $request->get_param( 'id' );
		$page     = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?? 10 );

		// Verify user exists.
		$user = \get_userdata( $user_id );
		if ( ! $user ) {
			return new \WP_Error(
				'rest_user_not_found',
				__( 'User not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		$result = Apollo_Relationship_Query::paginate( $user_id, $relationship, $page, $per_page );

		// Transform items to REST format.
		$items = \array_map(
			function ( $item ) {
				return self::prepare_item_for_response( $item );
			},
			$result['items']
		);

		$response = new \WP_REST_Response( $items );

		// Add pagination headers.
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['pages'] );

		return $response;
	}

	/**
	 * Connect items callback.
	 *
	 * @param \WP_REST_Request $request      Request object.
	 * @param string           $relationship Relationship name.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function connect_items( \WP_REST_Request $request, string $relationship ) {
		$post_id = (int) $request->get_param( 'id' );
		$ids     = $request->get_param( 'ids' );

		if ( ! \is_array( $ids ) || empty( $ids ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'IDs parameter must be a non-empty array.', 'apollo-core' ),
				array( 'status' => 400 )
			);
		}

		// Verify post exists.
		$post = \get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error(
				'rest_post_not_found',
				__( 'Post not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		$connected = array();
		$failed    = array();

		foreach ( $ids as $to_id ) {
			$to_id = (int) $to_id;
			if ( Apollo_Relationship_Query::connect( $post_id, $to_id, $relationship ) ) {
				$connected[] = $to_id;
			} else {
				$failed[] = $to_id;
			}
		}

		return new \WP_REST_Response(
			array(
				'connected' => $connected,
				'failed'    => $failed,
				'total'     => Apollo_Relationship_Query::count( $post_id, $relationship ),
			),
			200
		);
	}

	/**
	 * Disconnect items callback.
	 *
	 * @param \WP_REST_Request $request      Request object.
	 * @param string           $relationship Relationship name.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function disconnect_items( \WP_REST_Request $request, string $relationship ) {
		$post_id = (int) $request->get_param( 'id' );
		$ids     = $request->get_param( 'ids' );

		// Verify post exists.
		$post = \get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error(
				'rest_post_not_found',
				__( 'Post not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		// If no IDs specified, disconnect all.
		if ( empty( $ids ) ) {
			$current = Apollo_Relationship_Query::get_related( $post_id, $relationship, array( 'return' => 'ids' ) );
			$ids     = $current;
		}

		$disconnected = array();
		$failed       = array();

		foreach ( $ids as $to_id ) {
			$to_id = (int) $to_id;
			if ( Apollo_Relationship_Query::disconnect( $post_id, $to_id, $relationship ) ) {
				$disconnected[] = $to_id;
			} else {
				$failed[] = $to_id;
			}
		}

		return new \WP_REST_Response(
			array(
				'disconnected' => $disconnected,
				'failed'       => $failed,
				'total'        => Apollo_Relationship_Query::count( $post_id, $relationship ),
			),
			200
		);
	}

	/**
	 * Sync items callback.
	 *
	 * @param \WP_REST_Request $request      Request object.
	 * @param string           $relationship Relationship name.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function sync_items( \WP_REST_Request $request, string $relationship ) {
		$post_id = (int) $request->get_param( 'id' );
		$ids     = $request->get_param( 'ids' );

		if ( ! \is_array( $ids ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'IDs parameter must be an array.', 'apollo-core' ),
				array( 'status' => 400 )
			);
		}

		// Verify post exists.
		$post = \get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error(
				'rest_post_not_found',
				__( 'Post not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		$ids     = \array_map( 'intval', $ids );
		$success = Apollo_Relationship_Query::sync( $post_id, $ids, $relationship );

		if ( ! $success ) {
			return new \WP_Error(
				'rest_sync_failed',
				__( 'Failed to sync relationships.', 'apollo-core' ),
				array( 'status' => 500 )
			);
		}

		$related = Apollo_Relationship_Query::get_related( $post_id, $relationship, array( 'return' => 'ids' ) );

		return new \WP_REST_Response(
			array(
				'synced' => true,
				'ids'    => $related,
				'total'  => \count( $related ),
			),
			200
		);
	}

	/**
	 * Check edit permission for a post.
	 *
	 * @param \WP_REST_Request $request   Request object.
	 * @param string           $post_type Post type.
	 * @return bool
	 */
	private static function check_edit_permission( \WP_REST_Request $request, string $post_type ): bool {
		$post_id = (int) $request->get_param( 'id' );
		$post    = \get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		return \current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get collection parameters.
	 *
	 * @return array
	 */
	private static function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Maximum number of items per page.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Prepare item for REST response.
	 *
	 * @param \WP_Post|\WP_User $item Post or User object.
	 * @return array
	 */
	private static function prepare_item_for_response( $item ): array {
		if ( $item instanceof \WP_User ) {
			return array(
				'id'         => $item->ID,
				'name'       => $item->display_name,
				'slug'       => $item->user_nicename,
				'avatar_url' => \get_avatar_url( $item->ID ),
				'_links'     => array(
					'self' => array(
						array( 'href' => \rest_url( self::NAMESPACE . '/users/' . $item->ID ) ),
					),
				),
			);
		}

		if ( $item instanceof \WP_Post ) {
			$rest_base = self::get_rest_base( $item->post_type ) ?? $item->post_type;

			return array(
				'id'        => $item->ID,
				'title'     => \get_the_title( $item ),
				'slug'      => $item->post_name,
				'type'      => $item->post_type,
				'status'    => $item->post_status,
				'link'      => \get_permalink( $item ),
				'thumbnail' => \get_the_post_thumbnail_url( $item, 'thumbnail' ) ?: null,
				'_links'    => array(
					'self' => array(
						array( 'href' => \rest_url( self::NAMESPACE . '/' . $rest_base . '/' . $item->ID ) ),
					),
				),
			);
		}

		return array();
	}

	/**
	 * Get all relationships schema.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function get_relationships_schema( \WP_REST_Request $request ): \WP_REST_Response {
		$schema  = Apollo_Relationships::get_schema();
		$exposed = array();

		foreach ( $schema as $name => $definition ) {
			if ( isset( $definition['rest_exposed'] ) && false === $definition['rest_exposed'] ) {
				continue;
			}

			$exposed[ $name ] = array(
				'from'          => $definition['from'] ?? '',
				'to'            => $definition['to'] ?? '',
				'type'          => $definition['type'] ?? '',
				'bidirectional' => $definition['bidirectional'] ?? false,
				'inverse'       => $definition['inverse'] ?? null,
				'endpoint'      => self::$registered_endpoints[ $name ] ?? null,
			);
		}

		return new \WP_REST_Response(
			array(
				'relationships' => $exposed,
				'namespace'     => self::NAMESPACE,
			)
		);
	}

	/**
	 * Get specific relationship schema.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_relationship_schema( \WP_REST_Request $request ) {
		$name       = $request->get_param( 'name' );
		$definition = Apollo_Relationships::get( $name );

		if ( ! $definition ) {
			return new \WP_Error(
				'rest_relationship_not_found',
				__( 'Relationship not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response(
			array(
				'name'          => $name,
				'from'          => $definition['from'] ?? '',
				'to'            => $definition['to'] ?? '',
				'type'          => $definition['type'] ?? '',
				'storage'       => $definition['storage'] ?? '',
				'meta_key'      => $definition['meta_key'] ?? '',
				'bidirectional' => $definition['bidirectional'] ?? false,
				'inverse'       => $definition['inverse'] ?? null,
				'endpoint'      => self::$registered_endpoints[ $name ] ?? null,
			)
		);
	}

	/**
	 * Add relationship links to post response.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @param \WP_Post          $post     Post object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @return \WP_REST_Response
	 */
	public static function add_relationship_links( \WP_REST_Response $response, \WP_Post $post, \WP_REST_Request $request ): \WP_REST_Response {
		$relationships = Apollo_Relationships::get_for_post_type( $post->post_type );
		$rest_base     = self::get_rest_base( $post->post_type );

		if ( empty( $rest_base ) ) {
			return $response;
		}

		foreach ( $relationships as $name => $definition ) {
			if ( isset( $definition['rest_exposed'] ) && false === $definition['rest_exposed'] ) {
				continue;
			}

			$slug = self::get_relation_slug( $name, $definition );
			$href = \rest_url( self::NAMESPACE . '/' . $rest_base . '/' . $post->ID . '/' . $slug );

			$response->add_link(
				'https://api.apollo.com/rel/' . $slug,
				$href,
				array(
					'embeddable' => true,
					'count'      => Apollo_Relationship_Query::count( $post->ID, $name ),
				)
			);
		}

		return $response;
	}
}
