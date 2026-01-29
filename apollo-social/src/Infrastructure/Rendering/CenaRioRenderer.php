<?php

/**
 * P0-10: CENA RIO Page Renderer
 *
 * Renders CENA RIO page with monthly calendar and event management.
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\Infrastructure\Rendering;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CenaRioRenderer {

	/**
	 * P0-10: Render CENA RIO page
	 */
	public function render() {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return array(
				'title'       => 'Acesso Negado',
				'content'     => '<p>Você precisa estar logado para acessar esta página.</p>',
				'breadcrumbs' => array( 'Apollo Social', 'CENA RIO' ),
				'data'        => array(),
			);
		}

		// Check if user has contributor role (cena::rio) or higher
		$user                = wp_get_current_user();
		$has_cena_rio_access = in_array( 'contributor', $user->roles, true ) || current_user_can( 'edit_posts' ) || current_user_can( 'manage_options' );

		if ( ! $has_cena_rio_access ) {
			return array(
				'title'       => 'Acesso Restrito',
				'content'     => '<p>Acesso restrito à indústria. Você precisa ser cena::rio ou superior para acessar esta página.</p>',
				'breadcrumbs' => array( 'Apollo Social', 'CENA RIO' ),
				'data'        => array(),
			);
		}

		// P0-10: Get current month (from query var or default to current month)
		$current_month = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : gmdate( 'Y-m' );

		// P0-10: Get events for calendar (draft + publish)
		$calendar_events = $this->getCalendarEvents( $current_month );

		// P0-10: Get pending events for mod (if user is MOD/ADMIN)
		$pending_events = array();
		if ( current_user_can( 'edit_others_posts' ) ) {
			$pending_events = $this->getPendingEvents();
		}

		// P0-10: Get event plans (cena_event_plan CPT)
		$event_plans = array();
		if ( class_exists( '\Apollo\CenaRio\CenaRioModule' ) ) {
			$event_plans = \Apollo\CenaRio\CenaRioModule::getEventPlans( $current_user_id, 10 );
		}

		return array(
			'title'       => 'CENA::rio',
			'content'     => '',
			'breadcrumbs' => array( 'Apollo Social', 'CENA RIO' ),
			'data'        => array(
				'user'           => array(
					'id'                => $current_user_id,
					'name'              => $user->display_name,
					'avatar'            => get_avatar_url( $current_user_id ),
					'has_cena_rio_role' => $has_cena_rio_access,
					'is_mod'            => current_user_can( 'edit_others_posts' ),
				),
				'calendar'       => array(
					'current_month' => $current_month,
					'events'        => $calendar_events,
				),
				'pending_events' => $pending_events,
				'event_plans'    => $event_plans,
			),
		);
	}

	/**
	 * P0-10: Get events for calendar (grouped by date)
	 */
	private function getCalendarEvents( $month ) {
		if ( ! post_type_exists( 'event_listing' ) ) {
			return array();
		}

		$start_date = $month . '-01';
		$end_date   = gmdate( 'Y-m-t', strtotime( $start_date ) );

		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'meta_query'     => array(
					array(
						'key'     => '_event_start_date',
						'value'   => array( $start_date, $end_date ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				),
				'orderby'        => 'meta_value',
				'meta_key'       => '_event_start_date',
				'order'          => 'ASC',
			)
		);

		$events_by_date = array();

		foreach ( $query->posts as $event ) {
			$start_date_meta = get_post_meta( $event->ID, '_event_start_date', true );
			$start_time      = get_post_meta( $event->ID, '_event_start_time', true );
			$ticket_url      = get_post_meta( $event->ID, '_event_ticket_url', true );
			$local_id        = get_post_meta( $event->ID, '_event_local_id', true );
			$local_name      = $local_id ? get_the_title( $local_id ) : '';

			// P0-10: Check if ticket URL is confirmed
			$ticket_confirmed = get_post_meta( $event->ID, '_event_ticket_confirmed', true );

			if ( ! $start_date_meta ) {
				continue;
			}

			$date_key = gmdate( 'Y-m-d', strtotime( $start_date_meta ) );

			if ( ! isset( $events_by_date[ $date_key ] ) ) {
				$events_by_date[ $date_key ] = array();
			}

			$events_by_date[ $date_key ][] = array(
				'id'               => $event->ID,
				'title'            => $event->post_title,
				'status'           => $event->post_status,
				'date'             => $start_date_meta,
				'time'             => $start_time,
				'local'            => $local_name,
				'ticket_url'       => $ticket_url,
				'ticket_confirmed' => (bool) $ticket_confirmed,
				'permalink'        => get_permalink( $event->ID ),
			);
		}//end foreach

		return $events_by_date;
	}

	/**
	 * P0-10: Get pending events for mod
	 */
	private function getPendingEvents() {
		if ( ! post_type_exists( 'event_listing' ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => 20,
				'post_status'    => array( 'draft', 'pending' ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$events = array();

		foreach ( $query->posts as $event ) {
			$start_date       = get_post_meta( $event->ID, '_event_start_date', true );
			$ticket_url       = get_post_meta( $event->ID, '_event_ticket_url', true );
			$ticket_confirmed = get_post_meta( $event->ID, '_event_ticket_confirmed', true );

			$events[] = array(
				'id'               => $event->ID,
				'title'            => $event->post_title,
				'status'           => $event->post_status,
				'date'             => $start_date,
				'ticket_url'       => $ticket_url,
				'ticket_confirmed' => (bool) $ticket_confirmed,
				'author'           => array(
					'id'   => $event->post_author,
					'name' => get_the_author_meta( 'display_name', $event->post_author ),
				),
				'created'          => $event->post_date,
			);
		}

		return $events;
	}
}
