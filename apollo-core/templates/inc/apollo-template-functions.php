<?php
/**
 * Apollo Template Functions - Master File
 *
 * Centralized helper functions for all Apollo template parts.
 * Include this file in your theme's functions.php
 *
 * @package Apollo-Rio
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

// =============================================================================
// REST API CONFIGURATION
// =============================================================================

if ( ! defined( 'APOLLO_API_NAMESPACE' ) ) {
	define( 'APOLLO_API_NAMESPACE', 'apollo/v1' );
}
if ( ! defined( 'APOLLO_EVENTS_NAMESPACE' ) ) {
	define( 'APOLLO_EVENTS_NAMESPACE', 'apollo-events/v1' );
}
if ( ! defined( 'APOLLO_CORE_NAMESPACE' ) ) {
	define( 'APOLLO_CORE_NAMESPACE', 'apollo-core/v1' );
}

/**
 * Get REST API base URL
 */
if ( ! function_exists( 'apollo_get_api_url' ) ) {
	function apollo_get_api_url( $endpoint = '', $namespace = APOLLO_API_NAMESPACE ) {
		return rest_url( $namespace . '/' . ltrim( $endpoint, '/' ) );
	}
}

/**
 * Get nonce for REST requests
 */
if ( ! function_exists( 'apollo_get_rest_nonce' ) ) {
	function apollo_get_rest_nonce() {
		return wp_create_nonce( 'wp_rest' );
	}
}

/**
 * Localize script data for REST calls
 */
if ( ! function_exists( 'apollo_get_script_data' ) ) {
	function apollo_get_script_data() {
		return array(
			'api_url'        => rest_url( APOLLO_API_NAMESPACE . '/' ),
			'events_api_url' => rest_url( APOLLO_EVENTS_NAMESPACE . '/' ),
			'core_api_url'   => rest_url( APOLLO_CORE_NAMESPACE . '/' ),
			'nonce'          => apollo_get_rest_nonce(),
			'user_id'        => get_current_user_id(),
			'is_logged_in'   => is_user_logged_in(),
			'home_url'       => home_url( '/' ),
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
		);
	}
}

// =============================================================================
// ACTIVITY STREAM
// =============================================================================

/**
 * Get public activity feed
 * REST: GET /activity
 */
if ( ! function_exists( 'apollo_get_activity_feed' ) ) {
	function apollo_get_activity_feed( $args = array() ) {
		$defaults = array(
		'per_page' => 20,
		'page'     => 1,
		'type'     => 'all',
	);
	$args     = wp_parse_args( $args, $defaults );

	$response = wp_remote_get( add_query_arg( $args, apollo_get_api_url( 'activity' ) ) );

	if ( is_wp_error( $response ) ) {
		return array();
	}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['data'] ?? array();
	}
}

/**
 * Get user's personal activity
 * REST: GET /activity/me
 */
if ( ! function_exists( 'apollo_get_my_activity' ) ) {
	function apollo_get_my_activity( $user_id = null, $limit = 20 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_activity
         WHERE user_id = %d
         ORDER BY created_at DESC
         LIMIT %d",
			$user_id,
				$limit
			)
		);
	}
}

/**
 * Get friends activity feed
 * REST: GET /activity/friends
 */
if ( ! function_exists( 'apollo_get_friends_activity' ) ) {
	function apollo_get_friends_activity( $user_id = null, $limit = 20 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	$friend_ids = apollo_get_user_friend_ids( $user_id );
	if ( empty( $friend_ids ) ) {
		return array();
	}

	global $wpdb;
	$placeholders = implode( ',', array_fill( 0, count( $friend_ids ), '%d' ) );

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_activity
         WHERE user_id IN ($placeholders)
         ORDER BY created_at DESC
         LIMIT %d",
				array_merge( $friend_ids, array( $limit ) )
			)
		);
	}
}

/**
 * Get group activity
 * REST: GET /activity/group/{id}
 */
if ( ! function_exists( 'apollo_get_group_activity' ) ) {
	function apollo_get_group_activity( $group_id, $limit = 20 ) {
	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_activity
         WHERE group_id = %d
         ORDER BY created_at DESC
         LIMIT %d",
			$group_id,
				$limit
			)
		);
	}
}

/**
 * Format activity time
 */
if ( ! function_exists( 'apollo_format_activity_time' ) ) {
	function apollo_format_activity_time( $timestamp ) {
	$diff = time() - strtotime( $timestamp );

	if ( $diff < 60 ) {
		return 'agora';
	}
	if ( $diff < 3600 ) {
		return floor( $diff / 60 ) . ' min';
	}
	if ( $diff < 86400 ) {
		return floor( $diff / 3600 ) . 'h';
	}
	if ( $diff < 604800 ) {
		return floor( $diff / 86400 ) . 'd';
	}

		return date_i18n( 'd M', strtotime( $timestamp ) );
	}
}

// =============================================================================
// MEMBERS / PROFILES
// =============================================================================

/**
 * Get members directory
 * REST: GET /members
 */
if ( ! function_exists( 'apollo_get_members' ) ) {
	function apollo_get_members( $args = array() ) {
	$defaults = array(
		'per_page' => 24,
		'page'     => 1,
		'orderby'  => 'display_name',
		'order'    => 'ASC',
		'search'   => '',
		'role'     => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	$user_query_args = array(
		'number'  => $args['per_page'],
		'paged'   => $args['page'],
		'orderby' => $args['orderby'],
		'order'   => $args['order'],
	);

	if ( ! empty( $args['search'] ) ) {
		$user_query_args['search']         = '*' . $args['search'] . '*';
		$user_query_args['search_columns'] = array( 'display_name', 'user_login', 'user_email' );
	}

	if ( ! empty( $args['role'] ) ) {
		$user_query_args['role'] = $args['role'];
	}

		$query = new WP_User_Query( $user_query_args );
		return $query->get_results();
	}
}

/**
 * Get online members
 * REST: GET /members/online
 */
if ( ! function_exists( 'apollo_get_online_members' ) ) {
	function apollo_get_online_members( $limit = 20 ) {
	global $wpdb;
	$threshold = time() - ( 15 * 60 ); // 15 minutes

	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta}
         WHERE meta_key = 'last_activity'
         AND meta_value > %d
         ORDER BY meta_value DESC
         LIMIT %d",
			$threshold,
			$limit
		)
	);

	if ( empty( $user_ids ) ) {
		return array();
	}

		return get_users( array( 'include' => $user_ids ) );
	}
}

/**
 * Get member profile data
 * REST: GET /members/{id}
 */
if ( ! function_exists( 'apollo_get_member_profile' ) ) {
	function apollo_get_member_profile( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return null;
	}

	return array(
		'id'           => $user->ID,
		'name'         => $user->display_name,
		'username'     => $user->user_login,
		'avatar'       => get_avatar_url( $user->ID, array( 'size' => 200 ) ),
		'cover'        => get_user_meta( $user_id, 'cover_image', true ),
		'bio'          => get_user_meta( $user_id, 'description', true ),
		'location'     => get_user_meta( $user_id, 'user_location', true ),
		'website'      => $user->user_url,
		'verified'     => (bool) get_user_meta( $user_id, 'verified', true ),
		'role_display' => apollo_get_user_display_role( $user_id ),
		'joined'       => $user->user_registered,
		'stats'        => apollo_get_user_stats( $user_id ),
		'social'       => apollo_get_user_social_links( $user_id ),
			'badges'       => apollo_get_user_badges( $user_id ),
		);
	}
}

/**
 * Get user social links
 */
if ( ! function_exists( 'apollo_get_user_social_links' ) ) {
	function apollo_get_user_social_links( $user_id ) {
	return array(
		'instagram'  => get_user_meta( $user_id, 'instagram', true ),
		'twitter'    => get_user_meta( $user_id, 'twitter', true ),
		'facebook'   => get_user_meta( $user_id, 'facebook', true ),
		'linkedin'   => get_user_meta( $user_id, 'linkedin', true ),
			'soundcloud' => get_user_meta( $user_id, 'soundcloud', true ),
		);
	}
}

/**
 * Get user badges
 */
if ( ! function_exists( 'apollo_get_user_badges' ) ) {
	function apollo_get_user_badges( $user_id ) {
		$badges = get_user_meta( $user_id, 'apollo_badges', true );
		return is_array( $badges ) ? $badges : array();
	}
}

/**
 * Get user display role (from previous file)
 */
if ( ! function_exists( 'apollo_get_user_display_role' ) ) {
	function apollo_get_user_display_role( $user_id ) {
		return get_user_meta( $user_id, 'user_role_display', true ) ?: 'Membro';
	}
}

// =============================================================================
// GROUPS (COMUNAS & NUCLEOS)
// =============================================================================

/**
 * Get public comunas
 * REST: GET /comunas
 */
if ( ! function_exists( 'apollo_get_comunas' ) ) {
	function apollo_get_comunas( $args = array() ) {
		$defaults = array(
		'per_page' => 12,
		'page'     => 1,
		'orderby'  => 'title',
		'order'    => 'ASC',
	);
	$args     = wp_parse_args( $args, $defaults );

	return get_posts(
		array(
			'post_type'      => 'apollo_group',
			'posts_per_page' => $args['per_page'],
			'paged'          => $args['page'],
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'meta_query'     => array(
				array(
					'key'   => 'group_type',
					'value' => 'comuna',
				),
			),
			)
		);
	}
}

/**
 * Get single comuna
 * REST: GET /comunas/{id}
 */
if ( ! function_exists( 'apollo_get_comuna' ) ) {
	function apollo_get_comuna( $comuna_id ) {
		$post = get_post( $comuna_id );
	if ( ! $post || get_post_meta( $comuna_id, 'group_type', true ) !== 'comuna' ) {
			return null;
		}
		return apollo_format_group_data( $post );
	}
}

/**
 * Get private nucleos (requires auth)
 * REST: GET /nucleos
 */
if ( ! function_exists( 'apollo_get_nucleos' ) ) {
	function apollo_get_nucleos( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

		// Get nucleos user is member of
		return apollo_get_user_nucleos( $user_id );
	}
}

/**
 * Get single nucleo
 * REST: GET /nucleos/{id}
 */
if ( ! function_exists( 'apollo_get_nucleo' ) ) {
	function apollo_get_nucleo( $nucleo_id ) {
		$post = get_post( $nucleo_id );
		if ( ! $post || get_post_meta( $nucleo_id, 'group_type', true ) !== 'nucleo' ) {
			return null;
		}
		return apollo_format_group_data( $post );
	}
}

/**
 * Format group data
 */
if ( ! function_exists( 'apollo_format_group_data' ) ) {
	function apollo_format_group_data( $post ) {
		$group_id = $post->ID;
	return array(
		'id'           => $group_id,
		'title'        => $post->post_title,
		'slug'         => $post->post_name,
		'description'  => $post->post_content,
		'excerpt'      => $post->post_excerpt,
		'type'         => get_post_meta( $group_id, 'group_type', true ),
		'visibility'   => get_post_meta( $group_id, 'visibility', true ) ?: 'public',
		'cover'        => get_post_meta( $group_id, 'cover_image', true ),
		'avatar'       => get_post_meta( $group_id, 'avatar', true ),
		'member_count' => apollo_count_group_members( $group_id ),
		'owner_id'     => $post->post_author,
		'created'      => $post->post_date,
			'rules'        => get_post_meta( $group_id, 'group_rules', true ),
		);
	}
}

/**
 * Count group members
 */
if ( ! function_exists( 'apollo_count_group_members' ) ) {
	function apollo_count_group_members( $group_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_group_members WHERE group_id = %d",
				$group_id
			)
		);
	}
}

/**
 * Get group members
 * REST: GET /comunas/{id}/members, GET /nucleos/{id}/members
 */
if ( ! function_exists( 'apollo_get_group_members' ) ) {
	function apollo_get_group_members( $group_id, $args = array() ) {
	$defaults = array(
		'limit' => 50,
		'role'  => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	global $wpdb;

	$sql = "SELECT gm.*, u.display_name, u.user_email
            FROM {$wpdb->prefix}apollo_group_members gm
            JOIN {$wpdb->users} u ON gm.user_id = u.ID
            WHERE gm.group_id = %d";

	$params = array( $group_id );

	if ( ! empty( $args['role'] ) ) {
		$sql     .= ' AND gm.role = %s';
		$params[] = $args['role'];
	}

	$sql     .= ' ORDER BY gm.joined_at DESC LIMIT %d';
	$params[] = $args['limit'];

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}
}

/**
 * Check if user is group member
 */
if ( ! function_exists( 'apollo_is_group_member' ) ) {
	function apollo_is_group_member( $group_id, $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}

		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}apollo_group_members
         WHERE group_id = %d AND user_id = %d",
				$group_id,
				$user_id
			)
		);
	}
}

/**
 * Get user's role in group
 */
if ( ! function_exists( 'apollo_get_user_group_role' ) ) {
	function apollo_get_user_group_role( $group_id, $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return null;
	}

	global $wpdb;
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT role FROM {$wpdb->prefix}apollo_group_members
         WHERE group_id = %d AND user_id = %d",
			$group_id,
				$user_id
			)
		);
	}
}

// =============================================================================
// EVENTS
// =============================================================================

/**
 * Get upcoming events
 * REST: GET /eventos/proximos, GET /events/upcoming
 */
if ( ! function_exists( 'apollo_get_upcoming_events' ) ) {
	function apollo_get_upcoming_events( $args = array() ) {
	$defaults = array(
		'per_page' => 12,
		'page'     => 1,
		'category' => '',
		'location' => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	$query_args = array(
		'post_type'      => 'event_listing',
		'posts_per_page' => $args['per_page'],
		'paged'          => $args['page'],
		'meta_key'       => '_event_start_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);

	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'event_listing_category',
				'field'    => 'slug',
				'terms'    => $args['category'],
			),
		);
	}

		$query = new WP_Query( $query_args );
		return $query->posts;
	}
}

/**
 * Get past events
 * REST: GET /eventos/passados
 */
if ( ! function_exists( 'apollo_get_past_events' ) ) {
	function apollo_get_past_events( $args = array() ) {
	$defaults = array(
		'per_page' => 12,
		'page'     => 1,
	);
	$args     = wp_parse_args( $args, $defaults );

	$query = new WP_Query(
		array(
			'post_type'      => 'event_listing',
			'posts_per_page' => $args['per_page'],
			'paged'          => $args['page'],
			'meta_key'       => '_event_start_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
		)
	);

		return $query->posts;
	}
}

/**
 * Get single event data
 * REST: GET /eventos/{id}
 */
if ( ! function_exists( 'apollo_get_event' ) ) {
	function apollo_get_event( $event_id ) {
		$post = get_post( $event_id );
	if ( ! $post || $post->post_type !== 'event_listing' ) {
		return null;
	}

	return array(
		'id'               => $event_id,
		'title'            => $post->post_title,
		'content'          => $post->post_content,
		'excerpt'          => $post->post_excerpt,
		'thumbnail'        => get_the_post_thumbnail_url( $event_id, 'large' ),
		'start_date'       => get_post_meta( $event_id, '_event_start_date', true ),
		'end_date'         => get_post_meta( $event_id, '_event_end_date', true ),
		'start_time'       => get_post_meta( $event_id, '_event_start_time', true ),
		'end_time'         => get_post_meta( $event_id, '_event_end_time', true ),
		'venue'            => get_post_meta( $event_id, '_event_venue', true ),
		'address'          => get_post_meta( $event_id, '_event_address', true ),
		'price'            => get_post_meta( $event_id, '_event_price', true ),
		'rsvp_count'       => apollo_get_event_rsvp_count( $event_id ),
		'interested_count' => apollo_get_event_interest_count( $event_id ),
		'categories'       => wp_get_post_terms( $event_id, 'event_listing_category' ),
		'sounds'           => wp_get_post_terms( $event_id, 'event_sounds' ),
			'organizer'        => get_userdata( $post->post_author ),
		);
	}
}

/**
 * Get event RSVP count
 */
if ( ! function_exists( 'apollo_get_event_rsvp_count' ) ) {
	function apollo_get_event_rsvp_count( $event_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_event_rsvp
         WHERE event_id = %d AND status = 'going'",
				$event_id
			)
		);
	}
}

/**
 * Get event interest count
 */
if ( ! function_exists( 'apollo_get_event_interest_count' ) ) {
	function apollo_get_event_interest_count( $event_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_event_rsvp
         WHERE event_id = %d AND status IN ('going', 'interested')",
				$event_id
			)
		);
	}
}

/**
 * Get user's RSVP status for event
 */
if ( ! function_exists( 'apollo_get_user_event_rsvp' ) ) {
	function apollo_get_user_event_rsvp( $event_id, $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return null;
	}

		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->prefix}apollo_event_rsvp
         WHERE event_id = %d AND user_id = %d",
				$event_id,
				$user_id
			)
		);
	}
}

/**
 * Format event date display
 */
if ( ! function_exists( 'apollo_format_event_date' ) ) {
	function apollo_format_event_date( $start_date, $start_time = '' ) {
	if ( ! $start_date ) {
		return '';
	}

	$date = DateTime::createFromFormat( 'Y-m-d', $start_date );
	if ( ! $date ) {
		return $start_date;
	}

	$days   = array( 'Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb' );
	$months = array( 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez' );

	$output = $days[ $date->format( 'w' ) ] . ', ' . $date->format( 'd' ) . ' ' . $months[ $date->format( 'n' ) - 1 ];

	if ( $start_time ) {
		$output .= ' · ' . $start_time;
	}

		return $output;
	}
}

// =============================================================================
// CONNECTIONS (BOLHA SYSTEM)
// =============================================================================

/**
 * Get user's bubble (close friends)
 * REST: GET /bubble
 */
if ( ! function_exists( 'apollo_get_user_bubble' ) ) {
	function apollo_get_user_bubble( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;
	$friend_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT friend_id FROM {$wpdb->prefix}apollo_connections
         WHERE user_id = %d AND status = 'accepted' AND is_close_friend = 1",
			$user_id
		)
	);

		if ( empty( $friend_ids ) ) {
			return array();
		}
		return get_users( array( 'include' => $friend_ids ) );
	}
}

/**
 * Get user's all friends
 * REST: GET /bolha/listar
 */
if ( ! function_exists( 'apollo_get_user_friends' ) ) {
	function apollo_get_user_friends( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;
	$friend_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT friend_id FROM {$wpdb->prefix}apollo_connections
         WHERE user_id = %d AND status = 'accepted'",
			$user_id
		)
	);

		if ( empty( $friend_ids ) ) {
			return array();
		}
		return get_users( array( 'include' => $friend_ids ) );
	}
}

/**
 * Get user's friend IDs
 */
if ( ! function_exists( 'apollo_get_user_friend_ids' ) ) {
	function apollo_get_user_friend_ids( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;
	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT friend_id FROM {$wpdb->prefix}apollo_connections
         WHERE user_id = %d AND status = 'accepted'",
				$user_id
			)
		);
	}
}

/**
 * Get pending friend requests
 * REST: GET /bolha/pedidos
 */
if ( ! function_exists( 'apollo_get_pending_friend_requests' ) ) {
	function apollo_get_pending_friend_requests( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT c.*, u.display_name, u.user_email
         FROM {$wpdb->prefix}apollo_connections c
         JOIN {$wpdb->users} u ON c.user_id = u.ID
         WHERE c.friend_id = %d AND c.status = 'pending'
         ORDER BY c.created_at DESC",
				$user_id
			)
		);
	}
}

/**
 * Get friendship status between users
 * REST: GET /bolha/status/{id}
 */
if ( ! function_exists( 'apollo_get_friendship_status' ) ) {
	function apollo_get_friendship_status( $target_user_id, $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return 'none';
	}

	if ( $user_id == $target_user_id ) {
		return 'self';
	}

	global $wpdb;
	$status = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT status FROM {$wpdb->prefix}apollo_connections
         WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
			$user_id,
			$target_user_id,
			$target_user_id,
			$user_id
		)
	);

		return $status ?: 'none';
	}
}

/**
 * Get connection stats
 * REST: GET /connections/stats
 */
if ( ! function_exists( 'apollo_get_connection_stats' ) ) {
	function apollo_get_connection_stats( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;

	return array(
		'friends'          => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_connections
             WHERE user_id = %d AND status = 'accepted'",
				$user_id
			)
		),
		'close_friends'    => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_connections
             WHERE user_id = %d AND status = 'accepted' AND is_close_friend = 1",
				$user_id
			)
		),
		'pending_sent'     => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_connections
             WHERE user_id = %d AND status = 'pending'",
				$user_id
			)
		),
		'pending_received' => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_connections
             WHERE friend_id = %d AND status = 'pending'",
				$user_id
			)
		),
		'followers'        => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_follows WHERE following_id = %d",
				$user_id
			)
		),
		'following'        => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_follows WHERE follower_id = %d",
				$user_id
			)
		),
		);
	}
}

// =============================================================================
// CHAT
// =============================================================================

/**
 * Get user's conversations
 * REST: GET /chat/conversations
 */
if ( ! function_exists( 'apollo_get_conversations' ) ) {
	function apollo_get_conversations( $user_id = null ) {
		if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT c.*,
                (SELECT COUNT(*) FROM {$wpdb->prefix}apollo_messages m
                 WHERE m.conversation_id = c.id AND m.receiver_id = %d AND m.is_read = 0) as unread_count
         FROM {$wpdb->prefix}apollo_conversations c
         WHERE c.user_one = %d OR c.user_two = %d
         ORDER BY c.updated_at DESC",
			$user_id,
			$user_id,
				$user_id
			)
		);
	}
}

/**
 * Get conversation messages
 * REST: GET /chat/conversations/{id}
 */
if ( ! function_exists( 'apollo_get_conversation_messages' ) ) {
	function apollo_get_conversation_messages( $conversation_id, $limit = 50 ) {
		global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_messages
         WHERE conversation_id = %d
         ORDER BY created_at DESC
         LIMIT %d",
			$conversation_id,
				$limit
			)
		);
	}
}

/**
 * Get online users for chat
 * REST: GET /chat/online
 */
if ( ! function_exists( 'apollo_get_chat_online_users' ) ) {
	function apollo_get_chat_online_users( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$friends   = apollo_get_user_friends( $user_id );
	$online    = array();
	$threshold = time() - ( 5 * 60 ); // 5 minutes

	foreach ( $friends as $friend ) {
		$last_activity = get_user_meta( $friend->ID, 'last_activity', true );
		if ( $last_activity && $last_activity > $threshold ) {
			$online[] = $friend;
		}
	}

		return $online;
	}
}

// =============================================================================
// DOCUMENTS & SIGNATURES
// =============================================================================

/**
 * Get user's documents
 * REST: GET /documents
 */
if ( ! function_exists( 'apollo_get_user_documents' ) ) {
	function apollo_get_user_documents( $user_id = null, $args = array() ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	$defaults = array(
		'per_page' => 20,
		'page'     => 1,
		'status'   => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	$query_args = array(
		'post_type'      => 'apollo_document',
		'posts_per_page' => $args['per_page'],
		'paged'          => $args['page'],
		'author'         => $user_id,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	// Include documents shared with user
	$query_args['meta_query'] = array(
		'relation' => 'OR',
		array(
			'key'     => 'shared_with',
			'value'   => serialize( (string) $user_id ),
			'compare' => 'LIKE',
		),
	);

		$query = new WP_Query( $query_args );
		return $query->posts;
	}
}

/**
 * Get document details
 * REST: GET /documents/{id}
 */
if ( ! function_exists( 'apollo_get_document' ) ) {
	function apollo_get_document( $doc_id ) {
		$post = get_post( $doc_id );
	if ( ! $post || $post->post_type !== 'apollo_document' ) {
		return null;
	}

	return array(
		'id'                 => $doc_id,
		'title'              => $post->post_title,
		'content'            => $post->post_content,
		'status'             => $post->post_status,
		'author'             => get_userdata( $post->post_author ),
		'file_url'           => get_post_meta( $doc_id, 'file_url', true ),
		'file_type'          => get_post_meta( $doc_id, 'file_type', true ),
		'requires_signature' => (bool) get_post_meta( $doc_id, 'requires_signature', true ),
		'signatories'        => apollo_get_document_signatories( $doc_id ),
		'signatures'         => apollo_get_document_signatures( $doc_id ),
		'created'            => $post->post_date,
			'modified'           => $post->post_modified,
		);
	}
}

/**
 * Get document signatories
 */
if ( ! function_exists( 'apollo_get_document_signatories' ) ) {
	function apollo_get_document_signatories( $doc_id ) {
		$signatories = get_post_meta( $doc_id, 'requires_signature_from', true );
	if ( ! is_array( $signatories ) ) {
		return array();
	}

		return array_map( 'get_userdata', $signatories );
	}
}

/**
 * Get document signatures
 */
if ( ! function_exists( 'apollo_get_document_signatures' ) ) {
	function apollo_get_document_signatures( $doc_id ) {
		global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.*, u.display_name
         FROM {$wpdb->prefix}apollo_signatures s
         JOIN {$wpdb->users} u ON s.user_id = u.ID
         WHERE s.document_id = %d
         ORDER BY s.signed_at ASC",
				$doc_id
			)
		);
	}
}

/**
 * Check if user has signed document
 */
if ( ! function_exists( 'apollo_user_has_signed' ) ) {
	function apollo_user_has_signed( $doc_id, $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}

		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}apollo_signatures
         WHERE document_id = %d AND user_id = %d",
				$doc_id,
				$user_id
			)
		);
	}
}

// =============================================================================
// CLASSIFIEDS / MARKETPLACE
// =============================================================================

/**
 * Get classifieds listings
 * REST: GET /anuncios
 */
if ( ! function_exists( 'apollo_get_classifieds' ) ) {
	function apollo_get_classifieds( $args = array() ) {
	$defaults = array(
		'per_page' => 24,
		'page'     => 1,
		'category' => '',
		'search'   => '',
		'orderby'  => 'date',
		'order'    => 'DESC',
	);
	$args     = wp_parse_args( $args, $defaults );

	$query_args = array(
		'post_type'      => 'advert',
		'posts_per_page' => $args['per_page'],
		'paged'          => $args['page'],
		'orderby'        => $args['orderby'],
		'order'          => $args['order'],
	);

	if ( ! empty( $args['search'] ) ) {
		$query_args['s'] = $args['search'];
	}

	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'advert_category',
				'field'    => 'slug',
				'terms'    => $args['category'],
			),
		);
	}

		$query = new WP_Query( $query_args );
		return $query->posts;
	}
}

/**
 * Get single classified
 * REST: GET /anuncio/{id}
 */
if ( ! function_exists( 'apollo_get_classified' ) ) {
	function apollo_get_classified( $ad_id ) {
		$post = get_post( $ad_id );
	if ( ! $post || $post->post_type !== 'apollo_classified' ) {
		return null;
	}

	return array(
		'id'       => $ad_id,
		'title'    => $post->post_title,
		'content'  => $post->post_content,
		'price'    => get_post_meta( $ad_id, '_classified_price', true ),
		'images'   => apollo_get_classified_images( $ad_id ),
		'category' => wp_get_post_terms( $ad_id, 'classified_domain' ),
		'location' => get_post_meta( $ad_id, '_classified_location_text', true ),
		'contact'  => array(
			'phone' => get_post_meta( $ad_id, '_classified_contact_phone', true ),
			'email' => get_post_meta( $ad_id, '_classified_contact_email', true ),
		),
		'author'   => get_userdata( $post->post_author ),
		'created'  => $post->post_date,
			'views'    => (int) get_post_meta( $ad_id, '_classified_views', true ),
		);
	}
}

/**
 * Get classified images
 */
if ( ! function_exists( 'apollo_get_classified_images' ) ) {
	function apollo_get_classified_images( $ad_id ) {
		$images    = array();
	$thumbnail = get_the_post_thumbnail_url( $ad_id, 'large' );
	if ( $thumbnail ) {
		$images[] = $thumbnail;
	}

	$gallery = get_post_meta( $ad_id, '_classified_gallery', true );
	if ( is_array( $gallery ) ) {
		foreach ( $gallery as $img_id ) {
			$url = wp_get_attachment_url( $img_id );
			if ( $url ) {
				$images[] = $url;
			}
		}
	}

		return $images;
	}
}

// =============================================================================
// GAMIFICATION
// =============================================================================

/**
 * Get user points
 * REST: GET /points/me
 */
if ( ! function_exists( 'apollo_get_user_points' ) ) {
	function apollo_get_user_points( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return 0;
	}

		return (int) get_user_meta( $user_id, 'apollo_points', true );
	}
}

/**
 * Get leaderboard
 * REST: GET /leaderboard
 */
if ( ! function_exists( 'apollo_get_leaderboard' ) ) {
	function apollo_get_leaderboard( $limit = 20 ) {
		global $wpdb;

	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta}
         WHERE meta_key = 'apollo_points'
         ORDER BY CAST(meta_value AS UNSIGNED) DESC
         LIMIT %d",
			$limit
		)
	);

	if ( empty( $user_ids ) ) {
		return array();
	}

	$users = array();
	$rank  = 1;
	foreach ( $user_ids as $uid ) {
		$user = get_userdata( $uid );
		if ( ! $user ) {
			continue;
		}

		$users[] = array(
			'rank'   => $rank++,
			'user'   => $user,
			'points' => apollo_get_user_points( $uid ),
			'avatar' => get_avatar_url( $uid, array( 'size' => 64 ) ),
		);
	}

		return $users;
	}
}

/**
 * Get competitions
 * REST: GET /competitions
 */
if ( ! function_exists( 'apollo_get_competitions' ) ) {
	function apollo_get_competitions( $args = array() ) {
		$defaults = array(
		'status'   => 'active',
		'per_page' => 10,
	);
	$args     = wp_parse_args( $args, $defaults );

	return get_posts(
		array(
			'post_type'      => 'competition',
			'posts_per_page' => $args['per_page'],
			'meta_query'     => array(
				array(
					'key'   => 'status',
					'value' => $args['status'],
				),
			),
			)
		);
	}
}

// =============================================================================
// MODERATION
// =============================================================================

/**
 * Get moderation queue
 * REST: GET /mod/fila
 */
if ( ! function_exists( 'apollo_get_moderation_queue' ) ) {
	function apollo_get_moderation_queue( $limit = 50 ) {
		if ( ! current_user_can( 'moderate_comments' ) ) {
		return array();
	}

	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_moderation_queue
         WHERE status = 'pending'
         ORDER BY created_at ASC
         LIMIT %d",
				$limit
			)
		);
	}
}

/**
 * Get moderation stats
 * REST: GET /mod/stats
 */
if ( ! function_exists( 'apollo_get_moderation_stats' ) ) {
	function apollo_get_moderation_stats() {
		if ( ! current_user_can( 'moderate_comments' ) ) {
		return array();
	}

	global $wpdb;

	return array(
		'pending'         => (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_moderation_queue WHERE status = 'pending'"
		),
		'approved_today'  => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_moderation_queue
             WHERE status = 'approved' AND DATE(resolved_at) = %s",
				current_time( 'Y-m-d' )
			)
		),
		'rejected_today'  => (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_moderation_queue
             WHERE status = 'rejected' AND DATE(resolved_at) = %s",
				current_time( 'Y-m-d' )
			)
		),
		'reports_pending' => (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_reports WHERE status = 'pending'"
		),
	);
}
}

/**
 * Get reports
 * REST: GET /mod/reports
 */
if ( ! function_exists( 'apollo_get_reports' ) ) {
	function apollo_get_reports( $status = 'pending', $limit = 50 ) {
		if ( ! current_user_can( 'moderate_comments' ) ) {
		return array();
	}

	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT r.*, u.display_name as reporter_name
         FROM {$wpdb->prefix}apollo_reports r
         LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID
         WHERE r.status = %s
         ORDER BY r.created_at DESC
         LIMIT %d",
			$status,
				$limit
			)
		);
	}
}

// =============================================================================
// ONBOARDING
// =============================================================================

/**
 * Get onboarding step
 * REST: GET /onboarding/step
 */
if ( ! function_exists( 'apollo_get_onboarding_step' ) ) {
	function apollo_get_onboarding_step( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return 0;
	}

		return (int) get_user_meta( $user_id, 'onboarding_step', true );
	}
}

/**
 * Check if onboarding complete
 * REST: GET /onboarding/status
 */
if ( ! function_exists( 'apollo_is_onboarding_complete' ) ) {
	function apollo_is_onboarding_complete( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}

		return (bool) get_user_meta( $user_id, 'onboarding_complete', true );
	}
}

/**
 * Get onboarding progress
 */
if ( ! function_exists( 'apollo_get_onboarding_progress' ) ) {
	function apollo_get_onboarding_progress( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	return array(
		'current_step'     => apollo_get_onboarding_step( $user_id ),
		'total_steps'      => 5,
		'profile_complete' => ! empty( get_user_meta( $user_id, 'description', true ) ),
		'avatar_set'       => ! empty( get_user_meta( $user_id, 'custom_avatar', true ) ),
		'interests_set'    => ! empty( get_user_meta( $user_id, 'interests', true ) ),
		'first_connection' => count( apollo_get_user_friends( $user_id ) ) > 0,
			'first_group'      => count( apollo_get_user_nucleos( $user_id ) ) > 0 || count( apollo_get_user_communities( $user_id ) ) > 0,
		);
	}
}

// =============================================================================
// UTILITIES
// =============================================================================

/**
 * Format number for display
 */
if ( ! function_exists( 'apollo_format_number' ) ) {
	function apollo_format_number( $num ) {
	if ( $num >= 1000000 ) {
		return number_format( $num / 1000000, 1 ) . 'M';
	}
	if ( $num >= 1000 ) {
		return number_format( $num / 1000, 1 ) . 'k';
	}
		return number_format( $num );
	}
}

/**
 * Get user avatar with fallback
 */
if ( ! function_exists( 'apollo_get_user_avatar' ) ) {
	function apollo_get_user_avatar( $user_id, $size = 64 ) {
		$custom = get_user_meta( $user_id, 'custom_avatar', true );
	if ( $custom ) {
		return $custom;
	}

		return get_avatar_url( $user_id, array( 'size' => $size ) );
	}
}

/**
 * Check if current user can perform action
 */
if ( ! function_exists( 'apollo_user_can' ) ) {
	function apollo_user_can( $action, $object_id = null ) {
		$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}

	switch ( $action ) {
		case 'create_group':
			return current_user_can( 'publish_posts' );
		case 'create_nucleo':
			return current_user_can( 'apollo_create_nucleo' );
		case 'moderate':
			return current_user_can( 'moderate_comments' );
		case 'edit_event':
			return $object_id ? current_user_can( 'edit_post', $object_id ) : false;
			default:
				return current_user_can( $action );
		}
	}
}

/**
 * Enqueue Apollo template scripts and styles
 */
if ( ! function_exists( 'apollo_enqueue_template_assets' ) ) {
	function apollo_enqueue_template_assets() {
		$version = '2.3.0';

	// Core CSS
	wp_enqueue_style(
		'apollo-templates',
		get_template_directory_uri() . '/assets/css/apollo-templates.css',
		array(),
		$version
	);

	// Remix Icon
	wp_enqueue_style(
		'remixicon',
		'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css'
	);

	// Core JS
	wp_enqueue_script(
		'apollo-templates',
		get_template_directory_uri() . '/assets/js/apollo-templates.js',
		array( 'jquery' ),
		$version,
		true
	);

		// Localize script data
		wp_localize_script( 'apollo-templates', 'apolloData', apollo_get_script_data() );
	}
}
add_action( 'wp_enqueue_scripts', 'apollo_enqueue_template_assets' );
