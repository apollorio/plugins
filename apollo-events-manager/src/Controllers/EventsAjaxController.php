<?php
/**
 * Events AJAX Controller
 *
 * Handles all AJAX endpoints for Apollo Events Manager.
 * Extracted from the main plugin class to follow SRP.
 *
 * @package Apollo\Events\Controllers
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Controllers;

/**
 * Manages all AJAX handlers for events.
 */
final class EventsAjaxController {

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Event filtering
		add_action( 'wp_ajax_apollo_filter_events', array( $this, 'filterEvents' ) );
		add_action( 'wp_ajax_nopriv_apollo_filter_events', array( $this, 'filterEvents' ) );

		// Event single loading
		add_action( 'wp_ajax_apollo_load_event_single', array( $this, 'loadEventSingle' ) );
		add_action( 'wp_ajax_nopriv_apollo_load_event_single', array( $this, 'loadEventSingle' ) );

		// Favorites (authenticated only)
		add_action( 'wp_ajax_apollo_toggle_favorite', array( $this, 'toggleFavorite' ) );

		// Comments
		add_action( 'wp_ajax_apollo_submit_event_comment', array( $this, 'submitComment' ) );

		// Moderation (admin only)
		add_action( 'wp_ajax_apollo_mod_approve_event', array( $this, 'approveEvent' ) );
		add_action( 'wp_ajax_apollo_mod_reject_event', array( $this, 'rejectEvent' ) );

		// Modal
		add_action( 'wp_ajax_apollo_get_event_modal', array( $this, 'getEventModal' ) );
		add_action( 'wp_ajax_nopriv_apollo_get_event_modal', array( $this, 'getEventModal' ) );

		// Profile saving
		add_action( 'wp_ajax_apollo_save_profile', array( $this, 'saveProfile' ) );
	}

	/**
	 * Filter events AJAX handler.
	 *
	 * @return void
	 */
	public function filterEvents(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		$filters = $this->sanitizeFilters( $_POST );

		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $filters['per_page'] ?? 12 ),
			'paged'          => absint( $filters['page'] ?? 1 ),
			'orderby'        => sanitize_key( $filters['orderby'] ?? 'date' ),
			'order'          => 'ASC' === strtoupper( $filters['order'] ?? 'DESC' ) ? 'ASC' : 'DESC',
		);

		// Date range filter
		if ( ! empty( $filters['date_from'] ) || ! empty( $filters['date_to'] ) ) {
			$meta_query = array( 'relation' => 'AND' );

			if ( ! empty( $filters['date_from'] ) ) {
				$meta_query[] = array(
					'key'     => '_event_start_date',
					'value'   => $filters['date_from'],
					'compare' => '>=',
					'type'    => 'DATE',
				);
			}

			if ( ! empty( $filters['date_to'] ) ) {
				$meta_query[] = array(
					'key'     => '_event_start_date',
					'value'   => $filters['date_to'],
					'compare' => '<=',
					'type'    => 'DATE',
				);
			}

			$query_args['meta_query'] = $meta_query;
		}

		// Category filter
		if ( ! empty( $filters['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', (array) $filters['category'] ),
				),
			);
		}

		// Location filter
		if ( ! empty( $filters['location'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_event_location',
				'value'   => sanitize_text_field( $filters['location'] ),
				'compare' => 'LIKE',
			);
		}

		// Search
		if ( ! empty( $filters['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $filters['search'] );
		}

		$query  = new \WP_Query( $query_args );
		$events = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$events[] = $this->formatEventData( get_the_ID() );
			}
			wp_reset_postdata();
		}

		wp_send_json_success(
			array(
				'events'     => $events,
				'total'      => $query->found_posts,
				'pages'      => $query->max_num_pages,
				'page'       => $filters['page'] ?? 1,
				'hasMore'    => ( $filters['page'] ?? 1 ) < $query->max_num_pages,
			)
		);
	}

	/**
	 * Load event single content via AJAX.
	 *
	 * @return void
	 */
	public function loadEventSingle(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento não encontrado', 'apollo-events-manager' ) ) );
		}

		ob_start();
		$template_path = APOLLO_APRIO_PATH . 'templates/single-event-content.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'  => $html,
				'event' => $this->formatEventData( $event_id ),
			)
		);
	}

	/**
	 * Toggle event favorite status.
	 *
	 * @return void
	 */
	public function toggleFavorite(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Faça login para favoritar', 'apollo-events-manager' ) ) );
		}

		$event_id = absint( $_POST['event_id'] ?? 0 );
		$user_id  = get_current_user_id();

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events-manager' ) ) );
		}

		$favorites = get_user_meta( $user_id, '_apollo_favorite_events', true );
		$favorites = is_array( $favorites ) ? $favorites : array();

		$is_favorite = in_array( $event_id, $favorites, true );

		if ( $is_favorite ) {
			$favorites = array_diff( $favorites, array( $event_id ) );
			$action    = 'removed';
		} else {
			$favorites[] = $event_id;
			$action      = 'added';
		}

		update_user_meta( $user_id, '_apollo_favorite_events', array_unique( $favorites ) );

		// Update event favorite count
		$count = (int) get_post_meta( $event_id, '_favorite_count', true );
		$count = 'added' === $action ? $count + 1 : max( 0, $count - 1 );
		update_post_meta( $event_id, '_favorite_count', $count );

		wp_send_json_success(
			array(
				'action'     => $action,
				'isFavorite' => 'added' === $action,
				'count'      => $count,
			)
		);
	}

	/**
	 * Submit event comment.
	 *
	 * @return void
	 */
	public function submitComment(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Faça login para comentar', 'apollo-events-manager' ) ) );
		}

		$event_id = absint( $_POST['event_id'] ?? 0 );
		$content  = sanitize_textarea_field( $_POST['content'] ?? '' );
		$user     = wp_get_current_user();

		if ( ! $event_id || ! $content ) {
			wp_send_json_error( array( 'message' => __( 'Dados inválidos', 'apollo-events-manager' ) ) );
		}

		$comment_data = array(
			'comment_post_ID'      => $event_id,
			'comment_content'      => $content,
			'user_id'              => $user->ID,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_approved'     => 1,
		);

		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => __( 'Erro ao enviar comentário', 'apollo-events-manager' ) ) );
		}

		wp_send_json_success(
			array(
				'commentId' => $comment_id,
				'message'   => __( 'Comentário enviado!', 'apollo-events-manager' ),
			)
		);
	}

	/**
	 * Approve event (moderation).
	 *
	 * @return void
	 */
	public function approveEvent(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão', 'apollo-events-manager' ) ) );
		}

		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events-manager' ) ) );
		}

		$result = wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		update_post_meta( $event_id, '_moderation_status', 'approved' );
		update_post_meta( $event_id, '_moderation_date', current_time( 'mysql' ) );
		update_post_meta( $event_id, '_moderated_by', get_current_user_id() );

		wp_send_json_success( array( 'message' => __( 'Evento aprovado!', 'apollo-events-manager' ) ) );
	}

	/**
	 * Reject event (moderation).
	 *
	 * @return void
	 */
	public function rejectEvent(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão', 'apollo-events-manager' ) ) );
		}

		$event_id = absint( $_POST['event_id'] ?? 0 );
		$reason   = sanitize_textarea_field( $_POST['reason'] ?? '' );

		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events-manager' ) ) );
		}

		$result = wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		update_post_meta( $event_id, '_moderation_status', 'rejected' );
		update_post_meta( $event_id, '_moderation_reason', $reason );
		update_post_meta( $event_id, '_moderation_date', current_time( 'mysql' ) );
		update_post_meta( $event_id, '_moderated_by', get_current_user_id() );

		wp_send_json_success( array( 'message' => __( 'Evento rejeitado', 'apollo-events-manager' ) ) );
	}

	/**
	 * Get event modal content.
	 *
	 * @return void
	 */
	public function getEventModal(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento não encontrado', 'apollo-events-manager' ) ) );
		}

		ob_start();
		$template_path = APOLLO_APRIO_PATH . 'templates/event-modal.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'  => $html,
				'event' => $this->formatEventData( $event_id ),
			)
		);
	}

	/**
	 * Save user profile.
	 *
	 * @return void
	 */
	public function saveProfile(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Não autorizado', 'apollo-events-manager' ) ) );
		}

		$user_id   = get_current_user_id();
		$user_data = array( 'ID' => $user_id );

		// Sanitize and update basic fields
		if ( isset( $_POST['display_name'] ) ) {
			$user_data['display_name'] = sanitize_text_field( $_POST['display_name'] );
		}

		$result = wp_update_user( $user_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Update meta fields
		$meta_fields = array( 'bio', 'city', 'social_links' );
		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = is_array( $_POST[ $field ] )
					? array_map( 'sanitize_text_field', $_POST[ $field ] )
					: sanitize_textarea_field( $_POST[ $field ] );
				update_user_meta( $user_id, '_apollo_' . $field, $value );
			}
		}

		wp_send_json_success( array( 'message' => __( 'Perfil atualizado!', 'apollo-events-manager' ) ) );
	}

	/**
	 * Sanitize filter inputs.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	private function sanitizeFilters( array $input ): array {
		return array(
			'page'      => absint( $input['page'] ?? 1 ),
			'per_page'  => min( 100, absint( $input['per_page'] ?? 12 ) ),
			'orderby'   => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'     => sanitize_key( $input['order'] ?? 'DESC' ),
			'date_from' => sanitize_text_field( $input['date_from'] ?? '' ),
			'date_to'   => sanitize_text_field( $input['date_to'] ?? '' ),
			'category'  => sanitize_text_field( $input['category'] ?? '' ),
			'location'  => sanitize_text_field( $input['location'] ?? '' ),
			'search'    => sanitize_text_field( $input['search'] ?? '' ),
		);
	}

	/**
	 * Format event data for JSON response.
	 *
	 * @param int $event_id Event ID.
	 * @return array<string, mixed>
	 */
	private function formatEventData( int $event_id ): array {
		$post       = get_post( $event_id );
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$thumbnail  = get_the_post_thumbnail_url( $event_id, 'medium' );

		return array(
			'id'          => $event_id,
			'title'       => get_the_title( $event_id ),
			'excerpt'     => wp_trim_words( get_the_excerpt( $event_id ), 20 ),
			'permalink'   => get_permalink( $event_id ),
			'startDate'   => $start_date,
			'startDateFormatted' => $start_date ? wp_date( 'd/m/Y', strtotime( $start_date ) ) : '',
			'location'    => get_post_meta( $event_id, '_event_location', true ),
			'thumbnail'   => $thumbnail ?: '',
			'categories'  => wp_get_post_terms( $event_id, 'event_listing_category', array( 'fields' => 'names' ) ),
			'isFavorite'  => $this->isUserFavorite( $event_id ),
			'favoriteCount' => (int) get_post_meta( $event_id, '_favorite_count', true ),
		);
	}

	/**
	 * Check if event is user's favorite.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	private function isUserFavorite( int $event_id ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$favorites = get_user_meta( get_current_user_id(), '_apollo_favorite_events', true );
		return is_array( $favorites ) && in_array( $event_id, $favorites, true );
	}
}
