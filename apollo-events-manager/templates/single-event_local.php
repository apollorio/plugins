<?php
/**
 * Wrapper Template: Single Venue/Local (CPT: event_local)
 *
 * This is a WRAPPER template that prepares data and delegates rendering
 * to the core template: apollo-core/templates/core-local-single.php
 *
 * @package Apollo_Events_Manager
 * @since   2.0.0
 *
 * DATA PREPARATION ONLY - NO HTML OUTPUT IN THIS FILE
 */

defined( 'ABSPATH' ) || exit;

if ( ! have_posts() || get_post_type() !== 'event_local' ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

the_post();
global $post;

/**
 * ------------------------------------------------------------------------
 * HELPER FUNCTION
 * ------------------------------------------------------------------------
 */
if ( ! function_exists( 'apollo_local_get_post_meta' ) ) {
	/**
	 * Wrapper for get_post_meta with optional fallback.
	 */
	function apollo_local_get_post_meta( $post_id, $key, $single = true ) {
		// Try with function if available
		if ( function_exists( 'apollo_get_post_meta' ) ) {
			return apollo_get_post_meta( $post_id, $key, $single );
		}
		return get_post_meta( $post_id, $key, $single );
	}
}

if ( ! function_exists( 'apollo_local_build_upcoming_events' ) ) {
	/**
	 * Get upcoming events for a venue.
	 */
	function apollo_local_build_upcoming_events( $local_id, $limit = 5 ) {
		$events = array();

		// Query events linked to this venue
		$query_args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_event_local_ids',
					'value'   => $local_id,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_event_local_id',
					'value'   => $local_id,
					'compare' => '=',
				),
				array(
					'key'     => '_event_venue_id',
					'value'   => $local_id,
					'compare' => '=',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
		);

		$posts = get_posts( $query_args );

		foreach ( $posts as $event ) {
			$event_id   = $event->ID;
			$start_date = get_post_meta( $event_id, '_event_start_date', true );
			$start_time = get_post_meta( $event_id, '_event_start_time', true );
			$end_time   = get_post_meta( $event_id, '_event_end_time', true );

			// Parse date
			$date_day   = '';
			$date_month = '';
			if ( $start_date ) {
				$timestamp = strtotime( $start_date );
				if ( $timestamp ) {
					$date_day = date( 'd', $timestamp );
					// Portuguese month abbreviations
					$months_pt  = array(
						1  => 'Jan',
						2  => 'Fev',
						3  => 'Mar',
						4  => 'Abr',
						5  => 'Mai',
						6  => 'Jun',
						7  => 'Jul',
						8  => 'Ago',
						9  => 'Set',
						10 => 'Out',
						11 => 'Nov',
						12 => 'Dez',
					);
					$month_num  = (int) date( 'n', $timestamp );
					$date_month = isset( $months_pt[ $month_num ] ) ? $months_pt[ $month_num ] : date( 'M', $timestamp );
				}
			}

			// Tags (sounds)
			$tags      = array();
			$tag_terms = get_the_terms( $event_id, 'event_sounds' );
			if ( is_array( $tag_terms ) ) {
				foreach ( array_slice( $tag_terms, 0, 2 ) as $term ) {
					$tags[] = $term->name;
				}
			}

			$events[] = array(
				'id'         => $event_id,
				'title'      => get_the_title( $event_id ),
				'permalink'  => get_permalink( $event_id ),
				'thumbnail'  => get_the_post_thumbnail_url( $event_id, 'medium' ) ?: ( ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ? APOLLO_CORE_PLUGIN_URL . 'assets/img/' : APOLLO_APRIO_URL . 'assets/img/' ) . 'placeholder-event.webp' ),
				'date_day'   => $date_day,
				'date_month' => $date_month,
				'start_time' => $start_time,
				'end_time'   => $end_time,
				'tags'       => $tags,
			);
		}

		return $events;
	}
}

if ( ! function_exists( 'apollo_local_get_testimonials' ) ) {
	/**
	 * Get testimonials/reviews for a venue.
	 */
	function apollo_local_get_testimonials( $local_id ) {
		$testimonials = array();

		// Check for custom testimonials meta
		$reviews = get_post_meta( $local_id, '_local_testimonials', true );
		if ( is_array( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$testimonials[] = array(
					'user_id' => $review['user_id'] ?? 0,
					'name'    => $review['name'] ?? '',
					'avatar'  => $review['avatar'] ?? '',
					'rating'  => $review['rating'] ?? 5,
					'text'    => $review['text'] ?? '',
				);
			}
		}

		// Could also query comments if using WP comments for reviews
		// $comments = get_comments(['post_id' => $local_id]);

		return $testimonials;
	}
}

// =============================================================================
// DATA EXTRACTION
// =============================================================================
$local_id    = $post->ID;
$local       = $post;
$title       = get_the_title( $local_id );
$description = apply_filters( 'the_content', get_the_content() );

// Address / Location
$local_address = apollo_local_get_post_meta( $local_id, '_local_address', true ) ?: '';
$local_city    = apollo_local_get_post_meta( $local_id, '_local_city', true ) ?: '';
$local_state   = apollo_local_get_post_meta( $local_id, '_local_state', true ) ?: '';

// Format full address
$address_parts = array_filter( array( $local_address, $local_city, $local_state ) );
$address       = implode( ', ', $address_parts );

// Coordinates - try multiple meta keys
$lat = apollo_local_get_post_meta( $local_id, '_local_latitude', true );
if ( ! $lat ) {
	$lat = apollo_local_get_post_meta( $local_id, '_local_lat', true );
}
$lng = apollo_local_get_post_meta( $local_id, '_local_longitude', true );
if ( ! $lng ) {
	$lng = apollo_local_get_post_meta( $local_id, '_local_lng', true );
}
$lat = $lat ? (float) $lat : null;
$lng = $lng ? (float) $lng : null;

// Social Links
$website_url   = apollo_local_get_post_meta( $local_id, '_local_website', true ) ?: '';
$instagram_url = apollo_local_get_post_meta( $local_id, '_local_instagram', true ) ?: '';
$facebook_url  = apollo_local_get_post_meta( $local_id, '_local_facebook', true ) ?: '';

// Gallery Images (max 5)
$gallery_images = array();
for ( $i = 1; $i <= 5; $i++ ) {
	$img_url = apollo_local_get_post_meta( $local_id, '_local_image_' . $i, true );
	if ( $img_url ) {
		$gallery_images[] = $img_url;
	}
}

// Featured image fallback
$thumbnail_url = get_the_post_thumbnail_url( $local_id, 'large' );
if ( empty( $gallery_images ) && $thumbnail_url ) {
	$gallery_images[] = $thumbnail_url;
}
if ( ! $thumbnail_url ) {
	$thumbnail_url = ! empty( $gallery_images ) ? $gallery_images[0] : ( ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ? APOLLO_CORE_PLUGIN_URL . 'assets/img/' : APOLLO_APRIO_URL . 'assets/img/' ) . 'placeholder-venue.webp' );
}

// Venue Type Label
$venue_types = get_the_terms( $local_id, 'event_listing_type' );
$venue_type  = 'Venue';
if ( is_array( $venue_types ) && ! empty( $venue_types ) ) {
	$venue_type = $venue_types[0]->name;
}

// Upcoming Events
$upcoming_events = apollo_local_build_upcoming_events( $local_id, 5 );

// Testimonials
$testimonials = apollo_local_get_testimonials( $local_id );

// Print mode
$is_print = isset( $_GET['print'] ) || isset( $_GET['pdf'] );

// =============================================================================
// BUILD CONTEXT
// =============================================================================
$core_context = array(
	// Post data
	'local'           => $local,
	'local_id'        => $local_id,
	'title'           => $title,
	'description'     => $description,

	// Media
	'thumbnail_url'   => $thumbnail_url,
	'gallery_images'  => $gallery_images,

	// Location
	'address'         => $address,
	'venue_type'      => $venue_type,
	'lat'             => $lat,
	'lng'             => $lng,

	// Social
	'website_url'     => $website_url,
	'instagram_url'   => $instagram_url,
	'facebook_url'    => $facebook_url,

	// Related content
	'upcoming_events' => $upcoming_events,
	'testimonials'    => $testimonials,

	// Flags
	'is_print'        => $is_print,
);

// Allow filtering context before rendering
$core_context = apply_filters( 'apollo_local_single_context', $core_context, $local_id );

// =============================================================================
// LOAD CORE TEMPLATE
// =============================================================================
if ( class_exists( 'Apollo_Template_Loader' ) ) {
	Apollo_Template_Loader::load( 'core-local-single', $core_context );
} else {
	// Fallback: direct include
	$core_template = dirname( __DIR__, 2 ) . '/apollo-core/templates/core-local-single.php';
	if ( file_exists( $core_template ) ) {
		extract( $core_context, EXTR_SKIP );
		include $core_template;
	} else {
		echo '<p>Error: Core template not found.</p>';
	}
}
