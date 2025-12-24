<?php
/**
 * Wrapper Template: Single Event (CPT: event_listing)
 *
 * This is a WRAPPER template that prepares data and delegates rendering
 * to the core template: apollo-core/templates/core-event-single.php
 *
 * @package Apollo_Events_Manager
 * @since   2.0.0
 *
 * DATA PREPARATION ONLY - NO HTML OUTPUT IN THIS FILE
 */

defined( 'ABSPATH' ) || exit;

global $post;

if ( ! have_posts() ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

/**
 * ------------------------------------------------------------------------
 * ENQUEUE (Leaflet for maps)
 * ------------------------------------------------------------------------
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! wp_style_is( 'leaflet', 'enqueued' ) ) {
			wp_enqueue_style(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				array(),
				'1.9.4'
			);
		}

		if ( ! wp_script_is( 'leaflet', 'enqueued' ) ) {
			wp_enqueue_script(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				array(),
				'1.9.4',
				true
			);
		}
	},
	20
);

/**
 * ------------------------------------------------------------------------
 * HELPER FUNCTIONS (kept here for compatibility)
 * ------------------------------------------------------------------------
 */

if ( ! function_exists( 'apollo_safe_text' ) ) {
	/**
	 * Safe text output.
	 */
	function apollo_safe_text( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		return wp_kses_post( (string) $value );
	}
}

if ( ! function_exists( 'apollo_safe_url' ) ) {
	/**
	 * Safe URL output.
	 */
	function apollo_safe_url( $url ) {
		$url = (string) $url;
		if ( $url === '' ) {
			return '';
		}
		return esc_url( $url );
	}
}

if ( ! function_exists( 'apollo_youtube_id_from_url' ) ) {
	/**
	 * Extract YouTube ID from url.
	 */
	function apollo_youtube_id_from_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return '';
		}

		// Already an ID.
		if ( preg_match( '~^[a-zA-Z0-9_-]{10,15}$~', $url ) ) {
			return $url;
		}

		$patterns = array(
			'~youtube\.com/watch\?v=([^&]+)~',
			'~youtu\.be/([^?&/]+)~',
			'~youtube\.com/embed/([^?&/]+)~',
			'~youtube\.com/shorts/([^?&/]+)~',
		);
		foreach ( $patterns as $p ) {
			if ( preg_match( $p, $url, $m ) ) {
				return $m[1];
			}
		}
		return '';
	}
}

if ( ! function_exists( 'apollo_build_youtube_embed_url' ) ) {
	/**
	 * Build YouTube embed URL with autoplay parameters.
	 */
	function apollo_build_youtube_embed_url( $youtube_url_or_id ) {
		$vid = apollo_youtube_id_from_url( $youtube_url_or_id );
		if ( $vid === '' ) {
			return '';
		}

		$params = array(
			'autoplay'       => '1',
			'mute'           => '1',
			'controls'       => '0',
			'loop'           => '1',
			'playlist'       => $vid,
			'playsinline'    => '1',
			'modestbranding' => '1',
			'rel'            => '0',
			'fs'             => '0',
			'disablekb'      => '1',
			'iv_load_policy' => '3',
			'origin'         => home_url(),
		);
		return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $vid ) . '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}
}

if ( ! function_exists( 'apollo_event_get_coords' ) ) {
	/**
	 * Get event coordinates (lat/lng).
	 */
	function apollo_event_get_coords( $event_id ) {
		$event_id = (int) $event_id;

		$candidates = array(
			array( '_event_lat', '_event_lng' ),
			array( '_event_location_lat', '_event_location_lng' ),
			array( 'event_lat', 'event_lng' ),
			array( 'lat', 'lng' ),
		);

		$lat = null;
		$lng = null;

		foreach ( $candidates as $pair ) {
			$la = get_post_meta( $event_id, $pair[0], true );
			$ln = get_post_meta( $event_id, $pair[1], true );
			if ( $la !== '' && $ln !== '' ) {
				$lat = (float) $la;
				$lng = (float) $ln;
				break;
			}
		}

		// Venue fallback.
		if ( $lat === null || $lng === null ) {
			$venue_id = (int) get_post_meta( $event_id, '_event_venue_id', true );
			if ( ! $venue_id ) {
				$venue_id = (int) get_post_meta( $event_id, '_event_local_id', true );
			}
			if ( $venue_id ) {
				$la = get_post_meta( $venue_id, '_venue_lat', true );
				$ln = get_post_meta( $venue_id, '_venue_lng', true );
				if ( $la !== '' && $ln !== '' ) {
					$lat = (float) $la;
					$lng = (float) $ln;
				}
			}
		}

		$coords = array(
			'lat' => $lat,
			'lng' => $lng,
		);

		$coords = apply_filters( 'apollo_event_map_coords', $coords, $event_id );

		if ( ! isset( $coords['lat'], $coords['lng'] ) ) {
			$coords = array(
				'lat' => null,
				'lng' => null,
			);
		}

		return $coords;
	}
}

if ( ! function_exists( 'apollo_event_get_dj_slots' ) ) {
	/**
	 * Get DJ lineup/slots for an event.
	 */
	function apollo_event_get_dj_slots( $event_id ) {
		$event_id = (int) $event_id;

		$slots = get_post_meta( $event_id, '_event_dj_slots', true );
		if ( ! is_array( $slots ) ) {
			$slots = array();
		}

		$clean = array();

		foreach ( $slots as $slot ) {
			if ( ! is_array( $slot ) ) {
				continue;
			}

			$dj_id = isset( $slot['dj_id'] ) ? (int) $slot['dj_id'] : ( isset( $slot['dj'] ) ? (int) $slot['dj'] : 0 );
			if ( ! $dj_id ) {
				continue;
			}

			$start = isset( $slot['start'] ) ? sanitize_text_field( (string) $slot['start'] ) : ( isset( $slot['from'] ) ? sanitize_text_field( (string) $slot['from'] ) : '' );
			$end   = isset( $slot['end'] ) ? sanitize_text_field( (string) $slot['end'] ) : ( isset( $slot['to'] ) ? sanitize_text_field( (string) $slot['to'] ) : '' );

			$clean[] = array(
				'dj_id' => $dj_id,
				'start' => $start,
				'end'   => $end,
			);
		}

		// If empty, fallback to simple list.
		if ( empty( $clean ) ) {
			$djs = get_post_meta( $event_id, '_event_dj_ids', true );
			if ( ! is_array( $djs ) || empty( $djs ) ) {
				$djs = get_post_meta( $event_id, '_event_djs', true );
			}
			if ( is_array( $djs ) ) {
				foreach ( $djs as $dj_id ) {
					$dj_id = (int) $dj_id;
					if ( $dj_id > 0 ) {
						$clean[] = array(
							'dj_id' => $dj_id,
							'start' => '',
							'end'   => '',
						);
					}
				}
			}
		}

		$clean = apply_filters( 'apollo_event_dj_slots', $clean, $event_id );

		if ( ! is_array( $clean ) ) {
			$clean = array();
		}

		// Sort by order if available, then by start time.
		usort(
			$clean,
			function ( $a, $b ) {
				$a_order = isset( $a['order'] ) ? (int) $a['order'] : 0;
				$b_order = isset( $b['order'] ) ? (int) $b['order'] : 0;
				if ( $a_order > 0 && $b_order > 0 ) {
					return $a_order <=> $b_order;
				}
				if ( $a_order > 0 ) {
					return -1;
				}
				if ( $b_order > 0 ) {
					return 1;
				}

				$as = isset( $a['start'] ) ? $a['start'] : '';
				$bs = isset( $b['start'] ) ? $b['start'] : '';
				if ( $as === '' && $bs === '' ) {
					return 0;
				}
				if ( $as === '' ) {
					return 1;
				}
				if ( $bs === '' ) {
					return -1;
				}
				return strcmp( $as, $bs );
			}
		);

		return $clean;
	}
}

if ( ! function_exists( 'apollo_initials' ) ) {
	/**
	 * Get initials from a name.
	 */
	function apollo_initials( $name ) {
		$name = trim( (string) $name );
		if ( $name === '' ) {
			return 'DJ';
		}
		$parts   = preg_split( '/\s+/', $name );
		$letters = '';
		foreach ( $parts as $p ) {
			$letters .= mb_substr( $p, 0, 1 );
			if ( mb_strlen( $letters ) >= 2 ) {
				break;
			}
		}
		return mb_strtoupper( $letters );
	}
}

if ( ! function_exists( 'apollo_event_build_venue_data' ) ) {
	/**
	 * Build venue data array from event.
	 */
	function apollo_event_build_venue_data( $event_id ) {
		$venue_id = (int) get_post_meta( $event_id, '_event_venue_id', true );
		if ( ! $venue_id ) {
			$venue_id = (int) get_post_meta( $event_id, '_event_local_id', true );
		}

		$venue = array(
			'id'      => 0,
			'name'    => '',
			'address' => '',
			'lat'     => null,
			'lng'     => null,
			'images'  => array(),
		);

		// Try getting venue from linked post.
		if ( $venue_id && get_post_status( $venue_id ) === 'publish' ) {
			$venue['id']   = $venue_id;
			$venue['name'] = get_the_title( $venue_id );

			$venue['address'] = get_post_meta( $venue_id, '_venue_address', true );
			if ( ! $venue['address'] ) {
				$venue['address'] = get_post_meta( $venue_id, '_local_address', true );
			}

			$venue['lat'] = get_post_meta( $venue_id, '_venue_lat', true );
			$venue['lng'] = get_post_meta( $venue_id, '_venue_lng', true );
			if ( ! $venue['lat'] ) {
				$venue['lat'] = get_post_meta( $venue_id, '_local_lat', true );
			}
			if ( ! $venue['lng'] ) {
				$venue['lng'] = get_post_meta( $venue_id, '_local_lng', true );
			}

			// Venue images.
			$venue_gallery = get_post_meta( $venue_id, '_venue_gallery', true );
			if ( is_array( $venue_gallery ) ) {
				$venue['images'] = array_slice( $venue_gallery, 0, 5 );
			}
		}

		// Fallback to event-level fields.
		if ( ! $venue['name'] ) {
			$venue['name'] = get_post_meta( $event_id, '_event_venue_name', true );
			if ( ! $venue['name'] ) {
				$venue['name'] = get_post_meta( $event_id, '_event_local_name', true );
			}
		}

		if ( ! $venue['address'] ) {
			$venue['address'] = get_post_meta( $event_id, '_event_address', true );
		}

		// Coords fallback from event.
		if ( ! $venue['lat'] || ! $venue['lng'] ) {
			$coords         = apollo_event_get_coords( $event_id );
			$venue['lat']   = $coords['lat'];
			$venue['lng']   = $coords['lng'];
		}

		// Venue images fallback from event.
		if ( empty( $venue['images'] ) ) {
			$event_venue_gallery = get_post_meta( $event_id, '_event_venue_gallery', true );
			if ( is_array( $event_venue_gallery ) ) {
				$venue['images'] = array_slice( $event_venue_gallery, 0, 5 );
			}
		}

		return $venue;
	}
}

if ( ! function_exists( 'apollo_event_build_dj_slots_with_details' ) ) {
	/**
	 * Build DJ slots array with full details (name, thumbnail, permalink).
	 */
	function apollo_event_build_dj_slots_with_details( $event_id ) {
		$raw_slots = apollo_event_get_dj_slots( $event_id );
		$slots     = array();

		foreach ( $raw_slots as $slot ) {
			$dj_id = $slot['dj_id'];
			$dj    = get_post( $dj_id );

			if ( ! $dj || $dj->post_status !== 'publish' ) {
				continue;
			}

			$thumbnail = get_the_post_thumbnail_url( $dj_id, 'thumbnail' );

			$slots[] = array(
				'dj_id'     => $dj_id,
				'name'      => get_the_title( $dj_id ),
				'thumbnail' => $thumbnail ?: '',
				'permalink' => get_permalink( $dj_id ),
				'start'     => $slot['start'],
				'end'       => $slot['end'],
			);
		}

		return $slots;
	}
}

if ( ! function_exists( 'apollo_event_build_tags' ) ) {
	/**
	 * Build event tags array with icons.
	 */
	function apollo_event_build_tags( $event_id ) {
		$tags  = array();
		$terms = get_the_terms( $event_id, 'event_listing_type' );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$icon_class = 'ri-star-fill';
				switch ( $term->slug ) {
					case 'featured':
						$icon_class = 'ri-verified-badge-fill';
						break;
					case 'recommended':
						$icon_class = 'ri-award-fill';
						break;
					case 'hot':
						$icon_class = 'ri-fire-fill';
						break;
					case 'novo':
					case 'new':
						$icon_class = 'ri-fire-fill';
						break;
				}

				$tags[] = array(
					'name' => $term->name,
					'icon' => $icon_class,
					'slug' => $term->slug,
				);
			}
		}

		return $tags;
	}
}

if ( ! function_exists( 'apollo_event_build_sounds' ) ) {
	/**
	 * Build sounds/genres array.
	 */
	function apollo_event_build_sounds( $event_id ) {
		$sounds = array();
		$terms  = get_the_terms( $event_id, 'event_sounds' );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $t ) {
				if ( $t && isset( $t->name ) ) {
					$sounds[] = $t->name;
				}
			}
		}

		return $sounds;
	}
}

if ( ! function_exists( 'apollo_event_format_date' ) ) {
	/**
	 * Format event date for display.
	 */
	function apollo_event_format_date( $event_id ) {
		$date_display = get_post_meta( $event_id, '_event_date_display', true );
		if ( $date_display ) {
			return $date_display;
		}

		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		if ( $start_date ) {
			$timestamp = strtotime( $start_date );
			if ( $timestamp ) {
				// Format: "25 Out '25"
				$months_pt = array(
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
				$day       = date( 'd', $timestamp );
				$month_num = (int) date( 'n', $timestamp );
				$year      = date( 'y', $timestamp );
				$month     = isset( $months_pt[ $month_num ] ) ? $months_pt[ $month_num ] : date( 'M', $timestamp );

				return "{$day} {$month} '{$year}";
			}
		}

		return '';
	}
}

/**
 * ------------------------------------------------------------------------
 * MAIN LOOP - DATA PREPARATION
 * ------------------------------------------------------------------------
 */
while ( have_posts() ) :
	the_post();

	$event_id = get_the_ID();
	$event    = get_post( $event_id );

	// -------------------------
	// Core fields
	// -------------------------
	$title       = get_the_title();
	$description = get_the_content();

	// Thumbnail/Banner.
	$thumbnail_url = get_the_post_thumbnail_url( $event_id, 'large' );
	$banner_url    = get_post_meta( $event_id, '_event_banner_url', true );
	if ( ! $banner_url ) {
		$banner_url = $thumbnail_url;
	}
	if ( ! $thumbnail_url ) {
		$thumbnail_url = 'https://assets.apollo.rio.br/img/default-event.jpg';
	}
	if ( ! $banner_url ) {
		$banner_url = $thumbnail_url;
	}

	// Video.
	$youtube_url = get_post_meta( $event_id, '_event_youtube_url', true );
	if ( ! $youtube_url ) {
		$youtube_url = get_post_meta( $event_id, '_event_video_url', true );
	}
	$video_url = apollo_build_youtube_embed_url( $youtube_url );

	// Dates and times.
	$start_date     = get_post_meta( $event_id, '_event_start_date', true );
	$formatted_date = apollo_event_format_date( $event_id );
	$start_time     = get_post_meta( $event_id, '_event_start_time', true );
	$end_time       = get_post_meta( $event_id, '_event_end_time', true );

	// Venue data.
	$venue = apollo_event_build_venue_data( $event_id );

	// Tickets.
	$ticket_url    = get_post_meta( $event_id, '_event_ticket_url', true );
	$ticket_coupon = get_post_meta( $event_id, '_event_coupon_code', true );
	if ( ! $ticket_coupon ) {
		$ticket_coupon = 'APOLLO';
	}

	// DJ lineup.
	$dj_slots = apollo_event_build_dj_slots_with_details( $event_id );

	// Tags.
	$tags = apollo_event_build_tags( $event_id );

	// Sounds/genres.
	$sounds = apollo_event_build_sounds( $event_id );

	// Gallery images.
	$gallery_images = get_post_meta( $event_id, '_event_promo_gallery', true );
	if ( ! is_array( $gallery_images ) ) {
		$gallery_images = array();
	}
	$gallery_images = array_slice( $gallery_images, 0, 5 );

	// Interested users.
	$interested_users = array();
	$total_interested = 0;
	if ( function_exists( 'apollo_event_get_interested_user_ids' ) ) {
		$interested_ids   = apollo_event_get_interested_user_ids( $event_id );
		$total_interested = count( $interested_ids );

		foreach ( array_slice( $interested_ids, 0, 10 ) as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$interested_users[] = array(
					'ID'         => $user_id,
					'name'       => $user->display_name,
					'avatar_url' => get_avatar_url( $user_id, array( 'size' => 80 ) ),
				);
			}
		}
	}

	// Final image.
	$final_image_url = get_post_meta( $event_id, '_event_final_image', true );

	// Print mode.
	$is_print = isset( $_GET['print'] ) || isset( $_GET['pdf'] );

	// -------------------------
	// Build context for core template
	// -------------------------
	$core_context = array(
		// Post data.
		'event'            => $event,
		'event_id'         => $event_id,
		'title'            => $title,
		'description'      => $description,

		// Media.
		'thumbnail_url'    => $thumbnail_url,
		'banner_url'       => $banner_url,
		'video_url'        => $video_url,
		'gallery_images'   => $gallery_images,
		'final_image_url'  => $final_image_url,

		// Dates.
		'start_date'       => $start_date,
		'formatted_date'   => $formatted_date,
		'start_time'       => $start_time,
		'end_time'         => $end_time,

		// Venue.
		'venue'            => $venue,

		// Tickets.
		'ticket_url'       => $ticket_url,
		'ticket_coupon'    => $ticket_coupon,

		// Lineup.
		'dj_slots'         => $dj_slots,

		// Tags and sounds.
		'tags'             => $tags,
		'sounds'           => $sounds,

		// Social.
		'interested_users' => $interested_users,
		'total_interested' => $total_interested,

		// Flags.
		'is_print'         => $is_print,
	);

	// Allow filtering context before rendering.
	$core_context = apply_filters( 'apollo_event_single_context', $core_context, $event_id );

	// -------------------------
	// Load core template
	// -------------------------
	if ( class_exists( 'Apollo_Template_Loader' ) ) {
		Apollo_Template_Loader::load( 'core-event-single', $core_context );
	} else {
		// Fallback: direct include.
		$core_template = dirname( __DIR__, 2 ) . '/apollo-core/templates/core-event-single.php';
		if ( file_exists( $core_template ) ) {
			extract( $core_context, EXTR_SKIP );
			include $core_template;
		} else {
			echo '<p>Error: Core template not found.</p>';
		}
	}

endwhile;
