<?php

declare(strict_types=1);
/**
 * User ViewModel - Apollo Design System
 *
 * Transforms WordPress user data into approved DOM structures
 * for user profiles, dashboards, and social features.
 *
 * @package ApolloCore\ViewModels
 */

namespace Apollo_Core\ViewModels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_User_ViewModel extends Apollo_Base_ViewModel {
	/**
	 * Transform user data for profile display
	 *
	 * @return void
	 */
	protected function transform() {
		if ( ! $this->data || ! is_object( $this->data ) ) {
			$this->add_error( 'data', 'Invalid user data provided' );
			return;
		}

		$user    = $this->data;
		$user_id = $user->ID ?? 0;

		// Basic user information.
		$this->transformed_data = array(
			'user_id'      => $user_id,
			'display_name' => $this->sanitize_text( $user->display_name ),
			'username'     => $this->sanitize_text( $user->user_login ),
			'email'        => $this->sanitize_text( $user->user_email ),
			'avatar_url'   => $this->get_user_avatar( $user_id, 96 ),
			'profile_url'  => $this->sanitize_url( get_author_posts_url( $user_id ) ),
		);

		// Extended profile information.
		$this->transformed_data['bio']      = $this->sanitize_text( get_user_meta( $user_id, 'description', true ) ?: '' );
		$this->transformed_data['location'] = $this->sanitize_text( get_user_meta( $user_id, 'location', true ) ?: '' );
		$this->transformed_data['website']  = $this->sanitize_url( get_user_meta( $user_id, 'user_url', true ) ?: '' );

		// Social links.
		$social_links = array(
			'instagram' => get_user_meta( $user_id, 'instagram', true ),
			'twitter'   => get_user_meta( $user_id, 'twitter', true ),
			'facebook'  => get_user_meta( $user_id, 'facebook', true ),
			'youtube'   => get_user_meta( $user_id, 'youtube', true ),
		);

		$this->transformed_data['social_links'] = array_filter(
			array_map(
				function ( $url ) {
					return $url ? $this->sanitize_url( $url ) : null;
				},
				$social_links
			)
		);

		// User role/capabilities.
		$this->transformed_data['is_artist']    = user_can( $user_id, 'publish_events' );
		$this->transformed_data['is_organizer'] = user_can( $user_id, 'manage_options' );

		// Stats.
		$this->transformed_data['stats'] = array(
			'events_created'   => count_user_posts( $user_id, 'event_listing' ),
			'events_attending' => intval( get_user_meta( $user_id, '_events_attending_count', true ) ?: 0 ),
			'followers'        => intval( get_user_meta( $user_id, '_followers_count', true ) ?: 0 ),
			'following'        => intval( get_user_meta( $user_id, '_following_count', true ) ?: 0 ),
		);
	}

	/**
	 * Transform user data for dashboard display
	 *
	 * @return array Extended data for dashboard template
	 */
	public function get_dashboard_data() {
		$base_data = $this->get_template_data();

		if ( ! $this->is_valid() ) {
			return $base_data;
		}

		$user    = $this->data;
		$user_id = $user->ID ?? 0;

		// Recent activity.
		$recent_events = get_posts(
			array(
				'author'         => $user_id,
				'post_type'      => 'event_listing',
				'posts_per_page' => 5,
				'post_status'    => 'publish',
			)
		);

		$base_data['recent_events'] = array_map(
			function ( $event ) {
				return array(
					'id'     => $event->ID,
					'title'  => $this->sanitize_text( $event->post_title ),
					'url'    => $this->sanitize_url( get_permalink( $event->ID ) ),
					'date'   => $this->format_date( $event->post_date, 'M j, Y' ),
					'status' => $this->sanitize_text( $event->post_status ),
				);
			},
			$recent_events
		);

		// Upcoming events attending.
		$attending_events = get_user_meta( $user_id, '_attending_events', true );
		if ( $attending_events && is_array( $attending_events ) ) {
			$upcoming_events = get_posts(
				array(
					'post__in'       => $attending_events,
					'post_type'      => 'event_listing',
					'posts_per_page' => 5,
					'meta_query'     => array(
						array(
							'key'     => '_event_date',
							'value'   => current_time( 'Y-m-d H:i:s' ),
							'compare' => '>=',
							'type'    => 'DATETIME',
						),
					),
					'orderby'        => 'meta_value',
					'order'          => 'ASC',
				)
			);

			$base_data['upcoming_events'] = array_map(
				function ( $event ) {
					$viewmodel = new Apollo_Event_ViewModel( $event );
					return $viewmodel->get_template_data();
				},
				$upcoming_events
			);
		}

		// Navigation menu items.
		$base_data['nav_menu'] = array(
			array(
				'text'   => 'My Events',
				'url'    => '#',
				'icon'   => 'ri-calendar-event-line',
				'active' => true,
			),
			array(
				'text' => 'Attending',
				'url'  => '#',
				'icon' => 'ri-user-follow-line',
			),
			array(
				'text' => 'Following',
				'url'  => '#',
				'icon' => 'ri-heart-line',
			),
			array(
				'text' => 'Settings',
				'url'  => '#',
				'icon' => 'ri-settings-line',
			),
		);

		return $base_data;
	}

	/**
	 * Transform user data for profile card display
	 *
	 * @return array Simplified data for profile cards
	 */
	public function get_profile_card_data() {
		$base_data = $this->get_template_data();

		if ( ! $this->is_valid() ) {
			return $base_data;
		}

		// Return only essential data for profile cards.
		return array(
			'user_id'      => $base_data['user_id'],
			'display_name' => $base_data['display_name'],
			'avatar_url'   => $base_data['avatar_url'],
			'profile_url'  => $base_data['profile_url'],
			'bio'          => $this->get_excerpt( $base_data['bio'], 50 ),
			'location'     => $base_data['location'],
			'is_artist'    => $base_data['is_artist'],
			'stats'        => $base_data['stats'],
		);
	}

	/**
	 * Transform multiple users for listing display
	 *
	 * @param array $users Array of user objects
	 * @return array
	 */
	public static function transform_users_listing( $users ) {
		if ( ! is_array( $users ) ) {
			return array();
		}

		return array_map(
			function ( $user ) {
				$viewmodel = new self( $user );
				return $viewmodel->get_profile_card_data();
			},
			$users
		);
	}
}
