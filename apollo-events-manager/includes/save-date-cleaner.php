<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Save-Date Duplicity Control
 *
 * Automatically removes duplicate save-date posts when events are saved
 * Uses similarity matching to detect duplicates
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Clean up duplicate save-date posts when event is saved
 *
 * @param int     $post_id Post ID
 * @param WP_Post $post Post object
 * @param bool    $update Whether this is an update
 */
function apollo_cleanup_duplicate_save_dates( $post_id, $post, $update ) {
	// Only process event_listing posts
	if ( $post->post_type !== 'event_listing' ) {
		return;
	}

	// Skip autosave/revisions
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Get event date and name
	$date = get_post_meta( $post_id, '_event_start_date', true );
	if ( empty( $date ) ) {
		return;
	}

	// Normalize date to Y-m-d format
	$date_normalized = date( 'Y-m-d', strtotime( $date ) );
	if ( ! $date_normalized ) {
		return;
	}

	$event_name = get_the_title( $post_id );
	if ( empty( $event_name ) ) {
		return;
	}

	// Normalize name (lowercase, remove spaces)
	$event_name_normalized = strtolower( preg_replace( '/\s+/', '', $event_name ) );

	// Check if save_date post type exists
	if ( ! post_type_exists( 'save_date' ) ) {
		return; 
		// Save-date CPT not registered
	}

	// Find save-date posts with same date
	$save_dates = get_posts(
		array(
			'post_type'      => 'save_date',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'     => 'date',
					'value'   => $date_normalized,
					'compare' => '=',
				),
			),
		)
	);

	if ( empty( $save_dates ) ) {
		return; 
		// No save-dates with this date
	}

	// Check similarity with each save-date
	foreach ( $save_dates as $save_date ) {
		$save_date_name = get_the_title( $save_date->ID );
		if ( empty( $save_date_name ) ) {
			continue;
		}

		// Normalize save-date name
		$save_date_name_normalized = strtolower( preg_replace( '/\s+/', '', $save_date_name ) );

		// Calculate similarity percentage
		similar_text( $event_name_normalized, $save_date_name_normalized, $percent );

		// If similarity >= 80%, delete the save-date (duplicate)
		if ( $percent >= 80 ) {
			$deleted = wp_delete_post( $save_date->ID, true ); 
			// Force delete (skip trash)

			if ( $deleted ) {
				error_log(
					sprintf(
						'✅ Apollo: Deleted duplicate save-date #%d ("%s") - %.1f%% similar to event #%d ("%s")',
						$save_date->ID,
						$save_date_name,
						$percent,
						$post_id,
						$event_name
					)
				);
			}
		}
	}//end foreach
}

// Hook into event save
add_action( 'save_post_event_listing', 'apollo_cleanup_duplicate_save_dates', 10, 3 );

/**
 * Manual cleanup function (can be called via admin action)
 *
 * @return array Results of cleanup
 */
function apollo_manual_save_date_cleanup() {
	if ( ! post_type_exists( 'save_date' ) ) {
		return array(
			'success' => false,
			'message' => __( 'Save-date post type not registered.', 'apollo-events-manager' ),
			'deleted' => 0,
		);
	}

	$all_events = get_posts(
		array(
			'post_type'      => 'event_listing',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);

	$deleted_count = 0;
	$checked_count = 0;

	foreach ( $all_events as $event ) {
		++$checked_count;
		$date = get_post_meta( $event->ID, '_event_start_date', true );
		if ( empty( $date ) ) {
			continue;
		}

		$date_normalized = date( 'Y-m-d', strtotime( $date ) );
		if ( ! $date_normalized ) {
			continue;
		}

		$event_name_normalized = strtolower( preg_replace( '/\s+/', '', get_the_title( $event->ID ) ) );

		$save_dates = get_posts(
			array(
				'post_type'      => 'save_date',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'meta_query'     => array(
					array(
						'key'     => 'date',
						'value'   => $date_normalized,
						'compare' => '=',
					),
				),
			)
		);

		foreach ( $save_dates as $save_date ) {
			$save_date_name_normalized = strtolower( preg_replace( '/\s+/', '', get_the_title( $save_date->ID ) ) );
			similar_text( $event_name_normalized, $save_date_name_normalized, $percent );

			if ( $percent >= 80 ) {
				if ( wp_delete_post( $save_date->ID, true ) ) {
					++$deleted_count;
				}
			}
		}
	}//end foreach

	return array(
		'success' => true,
		'message' => sprintf(
			__( 'Checked %1$d events, deleted %2$d duplicate save-dates.', 'apollo-events-manager' ),
			$checked_count,
			$deleted_count
		),
		'deleted' => $deleted_count,
		'checked' => $checked_count,
	);
}
