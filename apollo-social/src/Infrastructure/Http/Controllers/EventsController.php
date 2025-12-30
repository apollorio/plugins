<?php
/**
 * Events REST Controller
 *
 * Handles event_listing CPT operations via REST API.
 * Endpoints use Portuguese naming: /eventos, /eva
 *
 * @package Apollo\Infrastructure\Http\Controllers
 * @since 2.1.0
 */

namespace Apollo\Infrastructure\Http\Controllers;

/**
 * Events REST Controller
 *
 * Endpoints:
 * - GET  /eventos           - List events
 * - GET  /eventos/{id}      - Get single event
 * - POST /eventos           - Create event (draft)
 * - PUT  /eventos/{id}      - Update event
 * - DELETE /eventos/{id}    - Delete event
 * - GET  /eventos/proximos  - Upcoming events
 * - GET  /eventos/passados  - Past events
 * - POST /eventos/{id}/confirmar - RSVP to event
 */
class EventsController extends BaseController {

	/**
	 * Post type for events
	 */
	private const POST_TYPE = 'event_listing';

	/**
	 * GET /apollo/v1/eventos
	 *
	 * List events with filters
	 */
	public function index( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 50, max( 1, (int) ( $request->get_param( 'per_page' ) ?? 12 ) ) );
		$search   = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$category = sanitize_text_field( $request->get_param( 'category' ) ?? '' );
		$location = sanitize_text_field( $request->get_param( 'location' ) ?? '' );
		$date_from = sanitize_text_field( $request->get_param( 'date_from' ) ?? '' );
		$date_to   = sanitize_text_field( $request->get_param( 'date_to' ) ?? '' );
		$nucleo_id = (int) $request->get_param( 'nucleo_id' );
		$order     = strtoupper( $request->get_param( 'order' ) ?? 'ASC' );

		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'ASC';
		}

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_date',
			'order'          => $order,
		);

		// Search
		if ( $search ) {
			$args['s'] = $search;
		}

		// Category filter
		if ( $category ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'slug',
					'terms'    => $category,
				),
			);
		}

		// Meta queries
		$meta_query = array();

		// Location filter
		if ( $location ) {
			$meta_query[] = array(
				'key'     => '_event_location',
				'value'   => $location,
				'compare' => 'LIKE',
			);
		}

		// Date range filter
		if ( $date_from ) {
			$meta_query[] = array(
				'key'     => '_event_date',
				'value'   => $date_from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		if ( $date_to ) {
			$meta_query[] = array(
				'key'     => '_event_date',
				'value'   => $date_to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		// Nucleo filter
		if ( $nucleo_id ) {
			$meta_query[] = array(
				'key'     => '_event_nucleo_id',
				'value'   => $nucleo_id,
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query;
		}

		$query  = new \WP_Query( $args );
		$events = array();

		foreach ( $query->posts as $post ) {
			$events[] = $this->eventToArray( $post );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'events'      => $events,
					'total'       => $query->found_posts,
					'page'        => $page,
					'pages'       => $query->max_num_pages,
					'per_page'    => $per_page,
				),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/eventos/{id}
	 *
	 * Get single event
	 */
	public function show( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check if published or user is author/admin
		if ( $post->post_status !== 'publish' ) {
			$user_id = get_current_user_id();
			if ( ! $user_id || ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Evento não encontrado.',
					),
					404
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $this->eventToArray( $post, true ),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/eventos
	 *
	 * Create new event (draft)
	 */
	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();

		$title       = sanitize_text_field( $request->get_param( 'title' ) );
		$description = wp_kses_post( $request->get_param( 'description' ) ?? '' );
		$date        = sanitize_text_field( $request->get_param( 'date' ) ?? '' );
		$time        = sanitize_text_field( $request->get_param( 'time' ) ?? '' );
		$location    = sanitize_text_field( $request->get_param( 'location' ) ?? '' );
		$venue       = sanitize_text_field( $request->get_param( 'venue' ) ?? '' );
		$nucleo_id   = (int) $request->get_param( 'nucleo_id' );
		$category    = sanitize_text_field( $request->get_param( 'category' ) ?? '' );

		if ( empty( $title ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Título é obrigatório.',
				),
				400
			);
		}

		if ( empty( $date ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Data do evento é obrigatória.',
				),
				400
			);
		}

		// Create post
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $description,
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'draft', // Events start as draft
			'post_author'  => $user_id,
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao criar evento.',
				),
				500
			);
		}

		// Save meta fields
		update_post_meta( $post_id, '_event_date', $date );
		update_post_meta( $post_id, '_event_time', $time );
		update_post_meta( $post_id, '_event_location', $location );
		update_post_meta( $post_id, '_event_venue', $venue );

		if ( $nucleo_id ) {
			update_post_meta( $post_id, '_event_nucleo_id', $nucleo_id );
		}

		// Set category
		if ( $category ) {
			wp_set_object_terms( $post_id, $category, 'event_listing_category' );
		}

		$post = get_post( $post_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Evento criado como rascunho.',
				'data'    => $this->eventToArray( $post ),
			),
			201
		);
	}

	/**
	 * PUT /apollo/v1/eventos/{id}
	 *
	 * Update event
	 */
	public function update( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check permission
		if ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para editar este evento.',
				),
				403
			);
		}

		$update_data = array( 'ID' => $id );

		if ( $request->has_param( 'title' ) ) {
			$update_data['post_title'] = sanitize_text_field( $request->get_param( 'title' ) );
		}

		if ( $request->has_param( 'description' ) ) {
			$update_data['post_content'] = wp_kses_post( $request->get_param( 'description' ) );
		}

		if ( $request->has_param( 'status' ) ) {
			$allowed_statuses = array( 'draft', 'pending', 'publish' );
			$status           = sanitize_text_field( $request->get_param( 'status' ) );
			if ( in_array( $status, $allowed_statuses, true ) ) {
				// Only admins can publish directly
				if ( $status === 'publish' && ! current_user_can( 'manage_options' ) ) {
					$status = 'pending';
				}
				$update_data['post_status'] = $status;
			}
		}

		wp_update_post( $update_data );

		// Update meta fields
		$meta_fields = array( 'date', 'time', 'location', 'venue', 'nucleo_id' );
		foreach ( $meta_fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$value = sanitize_text_field( $request->get_param( $field ) );
				update_post_meta( $id, '_event_' . $field, $value );
			}
		}

		// Update category
		if ( $request->has_param( 'category' ) ) {
			$category = sanitize_text_field( $request->get_param( 'category' ) );
			wp_set_object_terms( $id, $category, 'event_listing_category' );
		}

		$post = get_post( $id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Evento atualizado.',
				'data'    => $this->eventToArray( $post ),
			),
			200
		);
	}

	/**
	 * DELETE /apollo/v1/eventos/{id}
	 *
	 * Delete event (trash)
	 */
	public function delete( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check permission
		if ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para excluir este evento.',
				),
				403
			);
		}

		wp_trash_post( $id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Evento movido para lixeira.',
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/eventos/proximos
	 *
	 * Get upcoming events
	 */
	public function proximos( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = min( 50, max( 1, (int) ( $request->get_param( 'limit' ) ?? 12 ) ) );

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);

		$query  = new \WP_Query( $args );
		$events = array();

		foreach ( $query->posts as $post ) {
			$events[] = $this->eventToArray( $post );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $events,
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/eventos/passados
	 *
	 * Get past events
	 */
	public function passados( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = min( 50, max( 1, (int) ( $request->get_param( 'limit' ) ?? 12 ) ) );

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_event_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
		);

		$query  = new \WP_Query( $args );
		$events = array();

		foreach ( $query->posts as $post ) {
			$events[] = $this->eventToArray( $post );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $events,
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/eventos/{id}/confirmar
	 *
	 * RSVP to event (confirm attendance)
	 */
	public function confirmar( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id  = get_current_user_id();
		$event_id = (int) $request->get_param( 'id' );
		$status   = sanitize_text_field( $request->get_param( 'status' ) ?? 'going' ); // going, maybe, not_going

		$post = get_post( $event_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Get current RSVPs
		$rsvps = get_post_meta( $event_id, '_event_rsvps', true );
		if ( ! is_array( $rsvps ) ) {
			$rsvps = array();
		}

		// Update user's RSVP
		$rsvps[ $user_id ] = array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql' ),
		);

		update_post_meta( $event_id, '_event_rsvps', $rsvps );

		// Count RSVPs
		$going_count = 0;
		$maybe_count = 0;
		foreach ( $rsvps as $rsvp ) {
			if ( $rsvp['status'] === 'going' ) {
				++$going_count;
			} elseif ( $rsvp['status'] === 'maybe' ) {
				++$maybe_count;
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Presença confirmada!',
				'data'    => array(
					'status'      => $status,
					'going_count' => $going_count,
					'maybe_count' => $maybe_count,
				),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/eventos/{id}/convidados
	 *
	 * Get event RSVPs/attendees
	 */
	public function convidados( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'id' );
		$status   = sanitize_text_field( $request->get_param( 'status' ) ?? '' );

		$post = get_post( $event_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check if user can view attendees
		$user_id = get_current_user_id();
		$is_author = $user_id && (int) $post->post_author === $user_id;
		$is_admin  = current_user_can( 'manage_options' );

		if ( ! $is_author && ! $is_admin && $post->post_status !== 'publish' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão.',
				),
				403
			);
		}

		$rsvps = get_post_meta( $event_id, '_event_rsvps', true );
		if ( ! is_array( $rsvps ) ) {
			$rsvps = array();
		}

		$attendees = array();
		foreach ( $rsvps as $uid => $rsvp ) {
			// Filter by status if provided
			if ( $status && $rsvp['status'] !== $status ) {
				continue;
			}

			$user = get_userdata( $uid );
			if ( $user ) {
				$attendees[] = array(
					'user_id'      => $uid,
					'display_name' => $user->display_name,
					'avatar_url'   => get_avatar_url( $uid, array( 'size' => 64 ) ),
					'status'       => $rsvp['status'],
					'updated_at'   => $rsvp['updated_at'] ?? null,
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'attendees' => $attendees,
					'total'     => count( $attendees ),
				),
			),
			200
		);
	}

	// =========================================================================
	// Private Helper Methods
	// =========================================================================

	/**
	 * Convert WP_Post to array for API response
	 */
	private function eventToArray( \WP_Post $post, bool $full = false ): array {
		$event_date  = get_post_meta( $post->ID, '_event_date', true );
		$event_time  = get_post_meta( $post->ID, '_event_time', true );
		$location    = get_post_meta( $post->ID, '_event_location', true );
		$venue       = get_post_meta( $post->ID, '_event_venue', true );
		$nucleo_id   = get_post_meta( $post->ID, '_event_nucleo_id', true );

		// Get featured image
		$thumbnail_id  = get_post_thumbnail_id( $post->ID );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';

		// Get categories
		$categories = wp_get_object_terms( $post->ID, 'event_listing_category', array( 'fields' => 'names' ) );

		// Count RSVPs
		$rsvps       = get_post_meta( $post->ID, '_event_rsvps', true );
		$going_count = 0;
		$maybe_count = 0;
		if ( is_array( $rsvps ) ) {
			foreach ( $rsvps as $rsvp ) {
				if ( $rsvp['status'] === 'going' ) {
					++$going_count;
				} elseif ( $rsvp['status'] === 'maybe' ) {
					++$maybe_count;
				}
			}
		}

		// Check if current user RSVP'd
		$user_id     = get_current_user_id();
		$user_status = null;
		if ( $user_id && is_array( $rsvps ) && isset( $rsvps[ $user_id ] ) ) {
			$user_status = $rsvps[ $user_id ]['status'];
		}

		$data = array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'slug'          => $post->post_name,
			'excerpt'       => wp_trim_words( $post->post_content, 30 ),
			'date'          => $event_date,
			'time'          => $event_time,
			'location'      => $location,
			'venue'         => $venue,
			'thumbnail_url' => $thumbnail_url,
			'categories'    => $categories,
			'nucleo_id'     => $nucleo_id ? (int) $nucleo_id : null,
			'going_count'   => $going_count,
			'maybe_count'   => $maybe_count,
			'user_status'   => $user_status,
			'status'        => $post->post_status,
			'author_id'     => (int) $post->post_author,
			'permalink'     => get_permalink( $post->ID ),
		);

		// Add full content for single view
		if ( $full ) {
			$data['content'] = apply_filters( 'the_content', $post->post_content );
			$data['raw_content'] = $post->post_content;

			// Get author info
			$author = get_userdata( $post->post_author );
			if ( $author ) {
				$data['author'] = array(
					'id'           => $author->ID,
					'display_name' => $author->display_name,
					'avatar_url'   => get_avatar_url( $author->ID, array( 'size' => 96 ) ),
				);
			}
		}

		return $data;
	}
}
