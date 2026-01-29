<?php
/**
 * Apollo Events REST Controller
 *
 * REST API controller for event endpoints.
 *
 * @package Apollo_Events_Manager
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\RestAPI;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load base controller.
if ( ! class_exists( 'Apollo_Core\REST_API\Apollo_REST_Controller' ) ) {
	return;
}

/**
 * Class Events_Controller
 *
 * Handles CRUD operations for events via REST API.
 *
 * @since 2.0.0
 */
class Events_Controller extends \Apollo_Core\REST_API\Apollo_REST_Controller {

	/**
	 * REST base.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $rest_base = 'events';

	/**
	 * Post type.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected string $post_type = 'event_listing';

	/**
	 * Register routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes(): void {
		// GET /events - List events.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_create_permission' ),
					'args'                => $this->get_event_create_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// GET/PUT/DELETE /events/{id} - Single event operations.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'ID do evento.', 'apollo-events-manager' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_update_permission' ),
					'args'                => $this->get_event_update_params(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_delete_permission' ),
					'args'                => array(
						'id'    => array(
							'description'       => __( 'ID do evento.', 'apollo-events-manager' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'force' => array(
							'description' => __( 'Excluir permanentemente (ignorar lixeira).', 'apollo-events-manager' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// GET /events/calendar - Calendar view.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/calendar',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_calendar' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'month'    => array(
						'description'       => __( 'Mês (1-12).', 'apollo-events-manager' ),
						'type'              => 'integer',
						'default'           => (int) date( 'm' ),
						'minimum'           => 1,
						'maximum'           => 12,
						'sanitize_callback' => 'absint',
					),
					'year'     => array(
						'description'       => __( 'Ano.', 'apollo-events-manager' ),
						'type'              => 'integer',
						'default'           => (int) date( 'Y' ),
						'minimum'           => 2020,
						'maximum'           => 2050,
						'sanitize_callback' => 'absint',
					),
					'category' => array(
						'description'       => __( 'Filtrar por categoria.', 'apollo-events-manager' ),
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => array( $this, 'sanitize_int_array' ),
					),
				),
			)
		);

		// GET /events/upcoming - Upcoming events.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upcoming',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_upcoming' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'limit'    => array(
						'description'       => __( 'Número de eventos.', 'apollo-events-manager' ),
						'type'              => 'integer',
						'default'           => 5,
						'minimum'           => 1,
						'maximum'           => 50,
						'sanitize_callback' => 'absint',
					),
					'category' => array(
						'description'       => __( 'Filtrar por categoria.', 'apollo-events-manager' ),
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => array( $this, 'sanitize_int_array' ),
					),
				),
			)
		);

		// POST /events/{id}/favorite - Toggle favorite.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/favorite',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_favorite' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'ID do evento.', 'apollo-events-manager' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get events collection.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$pagination = $this->get_pagination_params( $request );

		$args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => $pagination['per_page'],
			'offset'         => $pagination['offset'],
			'post_status'    => 'publish',
		);

		// Search.
		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $this->sanitize_text( $search );
		}

		// Order.
		$orderby = $request->get_param( 'orderby' ) ?? 'date';
		$order   = strtoupper( $request->get_param( 'order' ) ?? 'DESC' );

		switch ( $orderby ) {
			case 'event_date':
				$args['meta_key'] = '_event_start_date';
				$args['orderby']  = 'meta_value';
				break;
			case 'title':
				$args['orderby'] = 'title';
				break;
			default:
				$args['orderby'] = 'date';
		}
		$args['order'] = $order;

		// Category filter.
		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'term_id',
					'terms'    => $this->sanitize_int_array( $category ),
				),
			);
		}

		// Featured filter.
		if ( $request->get_param( 'featured' ) ) {
			$args['meta_query'][] = array(
				'key'   => '_event_featured',
				'value' => '1',
			);
		}

		// Upcoming filter.
		if ( $request->get_param( 'upcoming' ) ) {
			$args['meta_query'][] = array(
				'key'     => '_event_start_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		// Set meta_query relation if multiple conditions.
		if ( ! empty( $args['meta_query'] ) && count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		$query = new \WP_Query( $args );

		$events = array();
		foreach ( $query->posts as $post ) {
			$events[] = $this->prepare_event_for_response( $post, $request );
		}

		return $this->paginated_response(
			$events,
			$query->found_posts,
			$pagination['page'],
			$pagination['per_page'],
			$request
		);
	}

	/**
	 * Get single event.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'event_not_found',
				__( 'Evento não encontrado.', 'apollo-events-manager' ),
				404
			);
		}

		if ( $post->post_status !== 'publish' && ! current_user_can( 'edit_post', $id ) ) {
			return $this->error(
				'event_not_published',
				__( 'Este evento não está publicado.', 'apollo-events-manager' ),
				403
			);
		}

		$event = $this->prepare_event_for_response( $post, $request, true );

		return $this->success( $event );
	}

	/**
	 * Create event.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$title   = $this->sanitize_text( $request->get_param( 'title' ) );
		$content = $this->sanitize_html( $request->get_param( 'content' ) );
		$status  = $request->get_param( 'status' ) ?? 'pending';

		if ( empty( $title ) ) {
			return $this->error(
				'event_title_required',
				__( 'O título do evento é obrigatório.', 'apollo-events-manager' ),
				400
			);
		}

		// Only admins can publish directly.
		if ( $status === 'publish' && ! current_user_can( 'publish_posts' ) ) {
			$status = 'pending';
		}

		$post_data = array(
			'post_type'    => $this->post_type,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_author'  => get_current_user_id(),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $this->error(
				'event_create_failed',
				$post_id->get_error_message(),
				500
			);
		}

		// Save event meta.
		$this->save_event_meta( $post_id, $request );

		// Set taxonomies.
		$this->set_event_taxonomies( $post_id, $request );

		// Set featured image.
		$featured_image = $request->get_param( 'featured_image' );
		if ( $featured_image ) {
			set_post_thumbnail( $post_id, $this->sanitize_int( $featured_image ) );
		}

		$post  = get_post( $post_id );
		$event = $this->prepare_event_for_response( $post, $request );

		return $this->success(
			$event,
			__( 'Evento criado com sucesso.', 'apollo-events-manager' ),
			201
		);
	}

	/**
	 * Update event.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'event_not_found',
				__( 'Evento não encontrado.', 'apollo-events-manager' ),
				404
			);
		}

		$post_data = array( 'ID' => $id );

		// Update title if provided.
		$title = $request->get_param( 'title' );
		if ( $title !== null ) {
			$post_data['post_title'] = $this->sanitize_text( $title );
		}

		// Update content if provided.
		$content = $request->get_param( 'content' );
		if ( $content !== null ) {
			$post_data['post_content'] = $this->sanitize_html( $content );
		}

		// Update status if provided.
		$status = $request->get_param( 'status' );
		if ( $status !== null ) {
			if ( $status === 'publish' && ! current_user_can( 'publish_posts' ) ) {
				$status = $post->post_status;
			}
			$post_data['post_status'] = $status;
		}

		$updated = wp_update_post( $post_data, true );

		if ( is_wp_error( $updated ) ) {
			return $this->error(
				'event_update_failed',
				$updated->get_error_message(),
				500
			);
		}

		// Update event meta.
		$this->save_event_meta( $id, $request );

		// Update taxonomies.
		$this->set_event_taxonomies( $id, $request );

		// Update featured image.
		$featured_image = $request->get_param( 'featured_image' );
		if ( $featured_image !== null ) {
			if ( $featured_image ) {
				set_post_thumbnail( $id, $this->sanitize_int( $featured_image ) );
			} else {
				delete_post_thumbnail( $id );
			}
		}

		$post  = get_post( $id );
		$event = $this->prepare_event_for_response( $post, $request );

		return $this->success(
			$event,
			__( 'Evento atualizado com sucesso.', 'apollo-events-manager' )
		);
	}

	/**
	 * Delete event.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$force = $this->sanitize_bool( $request->get_param( 'force' ) );
		$post  = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'event_not_found',
				__( 'Evento não encontrado.', 'apollo-events-manager' ),
				404
			);
		}

		if ( $force ) {
			$deleted = wp_delete_post( $id, true );
		} else {
			$deleted = wp_trash_post( $id );
		}

		if ( ! $deleted ) {
			return $this->error(
				'event_delete_failed',
				__( 'Falha ao excluir o evento.', 'apollo-events-manager' ),
				500
			);
		}

		return $this->success(
			array(
				'id'      => $id,
				'deleted' => true,
			),
			__( 'Evento excluído com sucesso.', 'apollo-events-manager' )
		);
	}

	/**
	 * Get calendar events.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_calendar( $request ) {
		$month = (int) $request->get_param( 'month' );
		$year  = (int) $request->get_param( 'year' );

		$first_day = sprintf( '%04d-%02d-01', $year, $month );
		$last_day  = date( 'Y-m-t', strtotime( $first_day ) );

		$args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => array( $first_day, $last_day ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		// Category filter.
		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'term_id',
					'terms'    => $this->sanitize_int_array( $category ),
				),
			);
		}

		$query = new \WP_Query( $args );

		// Group by date.
		$events_by_date = array();
		foreach ( $query->posts as $post ) {
			$event_date = get_post_meta( $post->ID, '_event_start_date', true );
			if ( ! $event_date ) {
				continue;
			}

			$date_key = date( 'Y-m-d', strtotime( $event_date ) );
			if ( ! isset( $events_by_date[ $date_key ] ) ) {
				$events_by_date[ $date_key ] = array();
			}

			$events_by_date[ $date_key ][] = array(
				'id'         => $post->ID,
				'title'      => get_the_title( $post ),
				'start_time' => get_post_meta( $post->ID, '_event_start_time', true ),
				'link'       => get_permalink( $post ),
				'featured'   => (bool) get_post_meta( $post->ID, '_event_featured', true ),
			);
		}

		return $this->success(
			array(
				'month'  => $month,
				'year'   => $year,
				'events' => $events_by_date,
			)
		);
	}

	/**
	 * Get upcoming events.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_upcoming( $request ) {
		$limit = (int) $request->get_param( 'limit' );

		$args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_key'       => '_event_start_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => date( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);

		// Category filter.
		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'term_id',
					'terms'    => $this->sanitize_int_array( $category ),
				),
			);
		}

		$query = new \WP_Query( $args );

		$events = array();
		foreach ( $query->posts as $post ) {
			$events[] = $this->prepare_event_for_response( $post, $request );
		}

		return $this->success( $events );
	}

	/**
	 * Toggle favorite status.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_favorite( $request ) {
		$event_id = (int) $request->get_param( 'id' );
		$user_id  = get_current_user_id();

		$post = get_post( $event_id );
		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'event_not_found',
				__( 'Evento não encontrado.', 'apollo-events-manager' ),
				404
			);
		}

		$favorites = get_user_meta( $user_id, '_apollo_favorite_events', true );
		if ( ! is_array( $favorites ) ) {
			$favorites = array();
		}

		$is_favorite = in_array( $event_id, $favorites, true );

		if ( $is_favorite ) {
			$favorites   = array_diff( $favorites, array( $event_id ) );
			$is_favorite = false;
			$message     = __( 'Evento removido dos favoritos.', 'apollo-events-manager' );
		} else {
			$favorites[] = $event_id;
			$is_favorite = true;
			$message     = __( 'Evento adicionado aos favoritos.', 'apollo-events-manager' );
		}

		update_user_meta( $user_id, '_apollo_favorite_events', array_values( $favorites ) );

		return $this->success(
			array(
				'event_id'    => $event_id,
				'is_favorite' => $is_favorite,
			),
			$message
		);
	}

	/**
	 * Prepare event for response.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @param bool             $full    Include full content.
	 * @return array
	 */
	protected function prepare_event_for_response( \WP_Post $post, \WP_REST_Request $request, bool $full = false ): array {
		$data = $this->prepare_post_for_response( $post, $request );

		// Add event-specific meta.
		$data['event_meta'] = array(
			'start_date' => get_post_meta( $post->ID, '_event_start_date', true ),
			'end_date'   => get_post_meta( $post->ID, '_event_end_date', true ),
			'start_time' => get_post_meta( $post->ID, '_event_start_time', true ),
			'end_time'   => get_post_meta( $post->ID, '_event_end_time', true ),
			'venue'      => get_post_meta( $post->ID, '_event_venue', true ),
			'address'    => get_post_meta( $post->ID, '_event_address', true ),
			'city'       => get_post_meta( $post->ID, '_event_city', true ),
			'lat'        => (float) get_post_meta( $post->ID, '_event_lat', true ),
			'lng'        => (float) get_post_meta( $post->ID, '_event_lng', true ),
			'price'      => get_post_meta( $post->ID, '_event_price', true ),
			'ticket_url' => get_post_meta( $post->ID, '_event_ticket_url', true ),
			'featured'   => (bool) get_post_meta( $post->ID, '_event_featured', true ),
		);

		// Add taxonomies.
		$data['categories']   = $this->prepare_terms_for_response( $post->ID, 'event_listing_category' );
		$data['event_types']  = $this->prepare_terms_for_response( $post->ID, 'event_listing_type' );
		$data['event_sounds'] = $this->prepare_terms_for_response( $post->ID, 'event_sounds' );

		// Add author info.
		$data['author_info'] = $this->prepare_author_for_response( (int) $post->post_author );

		// Check if favorited by current user.
		if ( is_user_logged_in() ) {
			$favorites           = get_user_meta( get_current_user_id(), '_apollo_favorite_events', true ) ?: array();
			$data['is_favorite'] = in_array( $post->ID, (array) $favorites, true );
		} else {
			$data['is_favorite'] = false;
		}

		// Full content only on single view.
		if ( ! $full ) {
			unset( $data['content'] );
		}

		return $data;
	}

	/**
	 * Save event meta.
	 *
	 * @since 2.0.0
	 *
	 * @param int              $post_id The post ID.
	 * @param \WP_REST_Request $request The request object.
	 * @return void
	 */
	protected function save_event_meta( int $post_id, \WP_REST_Request $request ): void {
		$meta_fields = array(
			'start_date' => array( 'sanitize_date', '_event_start_date' ),
			'end_date'   => array( 'sanitize_date', '_event_end_date' ),
			'start_time' => array( 'sanitize_text', '_event_start_time' ),
			'end_time'   => array( 'sanitize_text', '_event_end_time' ),
			'venue'      => array( 'sanitize_text', '_event_venue' ),
			'address'    => array( 'sanitize_text', '_event_address' ),
			'city'       => array( 'sanitize_text', '_event_city' ),
			'lat'        => array( 'sanitize_float', '_event_lat' ),
			'lng'        => array( 'sanitize_float', '_event_lng' ),
			'price'      => array( 'sanitize_text', '_event_price' ),
			'ticket_url' => array( 'esc_url_raw', '_event_ticket_url' ),
			'featured'   => array( 'sanitize_bool', '_event_featured' ),
		);

		foreach ( $meta_fields as $param => $config ) {
			$value = $request->get_param( $param );
			if ( $value !== null ) {
				$sanitizer = $config[0];
				$meta_key  = $config[1];

				if ( method_exists( $this, $sanitizer ) ) {
					$value = $this->$sanitizer( $value );
				} elseif ( function_exists( $sanitizer ) ) {
					$value = $sanitizer( $value );
				}

				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Set event taxonomies.
	 *
	 * @since 2.0.0
	 *
	 * @param int              $post_id The post ID.
	 * @param \WP_REST_Request $request The request object.
	 * @return void
	 */
	protected function set_event_taxonomies( int $post_id, \WP_REST_Request $request ): void {
		$taxonomies = array(
			'categories'   => 'event_listing_category',
			'event_types'  => 'event_listing_type',
			'event_sounds' => 'event_sounds',
		);

		foreach ( $taxonomies as $param => $taxonomy ) {
			$terms = $request->get_param( $param );
			if ( $terms !== null ) {
				$term_ids = $this->sanitize_int_array( $terms );
				wp_set_object_terms( $post_id, $term_ids, $taxonomy );
			}
		}
	}

	/**
	 * Get event creation parameters.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_event_create_params(): array {
		return array(
			'title'          => array(
				'description'       => __( 'Título do evento.', 'apollo-events-manager' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content'        => array(
				'description'       => __( 'Descrição do evento.', 'apollo-events-manager' ),
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			),
			'status'         => array(
				'description' => __( 'Status de publicação.', 'apollo-events-manager' ),
				'type'        => 'string',
				'default'     => 'pending',
				'enum'        => array( 'publish', 'pending', 'draft' ),
			),
			'start_date'     => array(
				'description' => __( 'Data de início (Y-m-d).', 'apollo-events-manager' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'end_date'       => array(
				'description' => __( 'Data de término (Y-m-d).', 'apollo-events-manager' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'start_time'     => array(
				'description' => __( 'Hora de início (HH:MM).', 'apollo-events-manager' ),
				'type'        => 'string',
			),
			'end_time'       => array(
				'description' => __( 'Hora de término (HH:MM).', 'apollo-events-manager' ),
				'type'        => 'string',
			),
			'venue'          => array(
				'description' => __( 'Nome do local.', 'apollo-events-manager' ),
				'type'        => 'string',
			),
			'address'        => array(
				'description' => __( 'Endereço.', 'apollo-events-manager' ),
				'type'        => 'string',
			),
			'city'           => array(
				'description' => __( 'Cidade.', 'apollo-events-manager' ),
				'type'        => 'string',
			),
			'lat'            => array(
				'description' => __( 'Latitude.', 'apollo-events-manager' ),
				'type'        => 'number',
			),
			'lng'            => array(
				'description' => __( 'Longitude.', 'apollo-events-manager' ),
				'type'        => 'number',
			),
			'price'          => array(
				'description' => __( 'Preço.', 'apollo-events-manager' ),
				'type'        => 'string',
			),
			'ticket_url'     => array(
				'description' => __( 'URL para compra de ingressos.', 'apollo-events-manager' ),
				'type'        => 'string',
				'format'      => 'uri',
			),
			'featured'       => array(
				'description' => __( 'Evento em destaque.', 'apollo-events-manager' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'featured_image' => array(
				'description' => __( 'ID da imagem destacada.', 'apollo-events-manager' ),
				'type'        => 'integer',
			),
			'categories'     => array(
				'description' => __( 'IDs das categorias.', 'apollo-events-manager' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
			),
			'event_types'    => array(
				'description' => __( 'IDs dos tipos de evento.', 'apollo-events-manager' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
			),
			'event_sounds'   => array(
				'description' => __( 'IDs dos estilos musicais.', 'apollo-events-manager' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Get event update parameters.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_event_update_params(): array {
		$params = $this->get_event_create_params();

		// ID is required for updates.
		$params['id'] = array(
			'description'       => __( 'ID do evento.', 'apollo-events-manager' ),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
		);

		// Make title optional for updates.
		$params['title']['required'] = false;

		return $params;
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

		$params['orderby']['enum'][] = 'event_date';

		$params['category'] = array(
			'description'       => __( 'Filtrar por categoria.', 'apollo-events-manager' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => array( $this, 'sanitize_int_array' ),
		);

		$params['featured'] = array(
			'description' => __( 'Apenas eventos em destaque.', 'apollo-events-manager' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		$params['upcoming'] = array(
			'description' => __( 'Apenas eventos futuros.', 'apollo-events-manager' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		return $params;
	}
}
