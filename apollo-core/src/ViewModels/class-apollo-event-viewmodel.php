<?php
/**
 * Event ViewModel - Apollo Design System
 *
 * Transforms WordPress event data into approved DOM structures
 * for event cards, single event pages, and event listings.
 *
 * @package ApolloCore\ViewModels
 */

class Apollo_Event_ViewModel extends Apollo_Base_ViewModel {
	/**
	 * Transform event data for event card display
	 *
	 * @return void
	 */
	protected function transform() {
		if ( ! $this->data || ! is_object( $this->data ) ) {
			$this->add_error( 'data', 'Invalid event data provided' );
			return;
		}

		$event   = $this->data;
		$post_id = $event->ID ?? 0;

		// Basic event information.
		$this->transformed_data = array(
			'event_id'  => $post_id,
			'title'     => $this->sanitize_text( get_the_title( $post_id ) ),
			'url'       => $this->sanitize_url( get_permalink( $post_id ) ),
			'image_url' => $this->get_featured_image( $post_id, 'large', 'https://via.placeholder.com/400x300?text=Event' ),
			'excerpt'   => $this->get_excerpt( get_the_excerpt( $post_id ) ?: get_the_content( $post_id ) ),
		);

		// Date information.
		$event_date = get_post_meta( $post_id, '_event_date', true );
		if ( $event_date ) {
			$date_obj = date_create( $event_date );
			if ( $date_obj ) {
				$this->transformed_data['date_day']   = $date_obj->format( 'j' );
				$this->transformed_data['date_month'] = strtolower( $date_obj->format( 'M' ) );
				$this->transformed_data['date_full']  = $this->format_date( $event_date, 'l, F j, Y' );
				$this->transformed_data['month_str']  = $this->transformed_data['date_month'];
			}
		}

		// Location information.
		$venue = get_post_meta( $post_id, '_event_venue', true );
		if ( $venue ) {
			$this->transformed_data['venue_name'] = $this->sanitize_text( $venue );
		}

		// DJ/Artist information.
		$dj_name = get_post_meta( $post_id, '_event_artist', true );
		if ( $dj_name ) {
			$this->transformed_data['dj_name'] = $this->sanitize_text( $dj_name );
		}

		// Categories/Tags.
		$categories                     = $this->get_terms( $post_id, 'event_category' );
		$this->transformed_data['tags'] = array_slice( $categories, 0, 3 ); // Limit to 3 tags.

		// Category for filtering.
		$this->transformed_data['category'] = ! empty( $categories ) ? strtolower( $categories[0] ) : 'general';
	}

	/**
	 * Transform event data for single event page display
	 *
	 * @return array Extended data for single event template
	 */
	public function get_single_event_data() {
		$base_data = $this->get_template_data();

		if ( ! $this->is_valid() ) {
			return $base_data;
		}

		$event   = $this->data;
		$post_id = $event->ID ?? 0;

		// Hero section data.
		$base_data['hero'] = array(
			'media_url'  => $this->get_featured_image( $post_id, 'full' ),
			'media_type' => 'image',
			'title'      => $base_data['title'],
			'subtitle'   => $this->sanitize_text( get_post_meta( $post_id, '_event_subtitle', true ) ?: '' ),
		);

		// Event details.
		$base_data['details'] = array(
			'description'   => wp_kses_post( get_the_content( $post_id ) ),
			'date_time'     => $this->format_date( get_post_meta( $post_id, '_event_date', true ), 'l, F j, Y \a\t g:i A' ),
			'venue'         => $base_data['venue_name'] ?? '',
			'venue_address' => $this->sanitize_text( get_post_meta( $post_id, '_event_address', true ) ?: '' ),
			'price'         => $this->sanitize_text( get_post_meta( $post_id, '_event_price', true ) ?: 'Free' ),
		);

		// Lineup information.
		$lineup = get_post_meta( $post_id, '_event_lineup', true );
		if ( $lineup && is_array( $lineup ) ) {
			$base_data['lineup'] = array_map(
				function ( $artist ) {
					return array(
						'name'   => $this->sanitize_text( $artist['name'] ?? '' ),
						'role'   => $this->sanitize_text( $artist['role'] ?? 'DJ' ),
						'avatar' => $this->sanitize_url( $artist['avatar'] ?? '' ),
					);
				},
				$lineup
			);
		}

		// Gallery images.
		$gallery = get_post_meta( $post_id, '_event_gallery', true );
		if ( $gallery && is_array( $gallery ) ) {
			$base_data['gallery'] = array_map(
				function ( $image_id ) {
					return array(
						'url' => $this->sanitize_url( wp_get_attachment_image_url( $image_id, 'large' ) ),
						'alt' => $this->sanitize_attr( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: '' ),
					);
				},
				$gallery
			);
		}

		// Bottom bar configuration.
		$base_data['bottom_bar'] = array(
			'primary_text'    => 'Tickets',
			'primary_url'     => $this->sanitize_url( get_post_meta( $post_id, '_event_ticket_url', true ) ?: '#' ),
			'primary_icon'    => 'ri-ticket-fill',
			'share_text'      => '',
			'share_icon'      => 'ri-share-forward-line',
			'animate_primary' => true,
		);

		return $base_data;
	}

	/**
	 * Transform multiple events for listing display
	 *
	 * @param array $events Array of event objects
	 * @return array
	 */
	public static function transform_events_listing( $events ) {
		if ( ! is_array( $events ) ) {
			return array();
		}

		return array_map(
			function ( $event ) {
				$viewmodel = new self( $event );
				return $viewmodel->get_template_data();
			},
			$events
		);
	}
}
