<?php
/**
 * Apollo User Dashboard Functions
 * File: inc/apollo-user-dashboard-functions.php
 */

/**
 * Get user dashboard stats
 */
function apollo_get_user_dashboard_stats( $user_id ) {
	return array(
		'events'      => apollo_count_user_events( $user_id ),
		'nucleos'     => apollo_count_user_nucleos( $user_id ),
		'posts'       => apollo_count_user_posts( $user_id ),
		'communities' => apollo_count_user_communities( $user_id ),
	);
}

/**
 * Get user stats
 */
function apollo_get_user_stats( $user_id ) {
	global $wpdb;

	$events = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE 'event_status_%'",
			$user_id
		)
	);

	$nucleos = count( apollo_get_user_nucleos( $user_id ) );

	$posts = count_user_posts( $user_id, 'post' );

	return array(
		'events'  => (int) $events,
		'nucleos' => (int) $nucleos,
		'posts'   => (int) $posts,
	);
}

/**
 * Get user display role
 */
function apollo_get_user_display_role( $user_id ) {
	return get_user_meta( $user_id, 'user_role_display', true ) ?: 'Membro';
}

/**
 * Check user alerts
 */
function apollo_check_user_alerts( $user_id ) {
	$pending_docs = apollo_count_pending_documents( $user_id );
	return $pending_docs > 0;
}

/**
 * Get user events
 */
function apollo_get_user_events( $user_id, $args = array() ) {
	global $wpdb;

	$defaults = array(
		'status'      => array( 'going', 'maybe' ),
		'limit'       => 10,
		'future_only' => true,
	);
	$args     = wp_parse_args( $args, $defaults );

	$event_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT REPLACE(meta_key, 'event_status_', '') as event_id 
        FROM {$wpdb->usermeta} 
        WHERE user_id = %d 
        AND meta_key LIKE 'event_status_%%'
        AND meta_value IN ('" . implode( "','", $args['status'] ) . "')
        LIMIT %d",
			$user_id,
			$args['limit']
		)
	);

	if ( empty( $event_ids ) ) {
		return array();
	}

	$query_args = array(
		'post_type'      => 'event',
		'post__in'       => $event_ids,
		'posts_per_page' => $args['limit'],
		'orderby'        => 'meta_value',
		'meta_key'       => 'event_date',
		'order'          => 'ASC',
	);

	if ( $args['future_only'] ) {
		$query_args['meta_query'] = array(
			array(
				'key'     => 'event_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
	}

	$query = new WP_Query( $query_args );
	return $query->posts;
}

/**
 * Format event datetime
 */
function apollo_format_event_datetime( $date, $time ) {
	if ( ! $date ) {
		return '';
	}

	$date_obj = DateTime::createFromFormat( 'Y-m-d', $date );
	if ( ! $date_obj ) {
		return '';
	}

	$day_names = array( 'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado' );
	$day_name  = $day_names[ $date_obj->format( 'w' ) ];

	return $day_name . ' · ' . $time;
}

/**
 * Get user nucleos
 */
function apollo_get_user_nucleos( $user_id ) {
	$member_ids = get_posts(
		array(
			'post_type'      => 'nucleo_member',
			'meta_query'     => array(
				array(
					'key'   => 'member_user_id',
					'value' => $user_id,
				),
			),
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);

	if ( empty( $member_ids ) ) {
		return array();
	}

	$nucleo_ids = array();
	foreach ( $member_ids as $member_id ) {
		$nucleo_id = get_post_meta( $member_id, 'nucleo_id', true );
		if ( $nucleo_id ) {
			$nucleo_ids[] = $nucleo_id;
		}
	}

	if ( empty( $nucleo_ids ) ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'nucleo',
			'post__in'       => $nucleo_ids,
			'posts_per_page' => -1,
		)
	);
}

/**
 * Count nucleo members
 */
function apollo_count_nucleo_members( $nucleo_id ) {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'nucleo_member'
        AND pm.meta_key = 'nucleo_id'
        AND pm.meta_value = %d
        AND p.post_status = 'publish'",
			$nucleo_id
		)
	);
}

/**
 * Get user communities
 */
function apollo_get_user_communities( $user_id, $limit = -1 ) {
	$member_ids = get_posts(
		array(
			'post_type'      => 'community_member',
			'meta_query'     => array(
				array(
					'key'   => 'member_user_id',
					'value' => $user_id,
				),
			),
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);

	if ( empty( $member_ids ) ) {
		return array();
	}

	$community_ids = array();
	foreach ( $member_ids as $member_id ) {
		$community_id = get_post_meta( $member_id, 'community_id', true );
		if ( $community_id ) {
			$community_ids[] = $community_id;
		}
	}

	if ( empty( $community_ids ) ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'community',
			'post__in'       => $community_ids,
			'posts_per_page' => $limit,
		)
	);
}

/**
 * Count community members
 */
function apollo_count_community_members( $community_id ) {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'community_member'
        AND pm.meta_key = 'community_id'
        AND pm.meta_value = %d
        AND p.post_status = 'publish'",
			$community_id
		)
	);
}

/**
 * Format member count
 */
function apollo_format_member_count( $count ) {
	if ( $count >= 1000 ) {
		return number_format( $count / 1000, 1 ) . 'k Membros';
	}
	return $count . '+ Membros';
}

/**
 * Get pending documents
 */
function apollo_get_user_pending_documents( $user_id, $limit = 10 ) {
	$signed_doc_ids = apollo_get_signed_document_ids( $user_id );

	$args = array(
		'post_type'      => 'document',
		'posts_per_page' => $limit,
		'meta_query'     => array(
			array(
				'key'     => 'requires_signature_from',
				'value'   => serialize( strval( $user_id ) ),
				'compare' => 'LIKE',
			),
		),
	);

	if ( ! empty( $signed_doc_ids ) ) {
		$args['post__not_in'] = $signed_doc_ids;
	}

	$query = new WP_Query( $args );
	return $query->posts;
}

/**
 * Get signed document IDs
 */
function apollo_get_signed_document_ids( $user_id ) {
	global $wpdb;
	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT document_id FROM {$wpdb->prefix}apollo_signatures WHERE user_id = %d",
			$user_id
		)
	);
}

/**
 * Count pending documents
 */
function apollo_count_pending_documents( $user_id ) {
	return count( apollo_get_user_pending_documents( $user_id, -1 ) );
}

/**
 * Get user next event
 */
function apollo_get_user_next_event( $user_id ) {
	$events = apollo_get_user_events(
		$user_id,
		array(
			'limit'       => 1,
			'future_only' => true,
		)
	);
	return ! empty( $events ) ? $events[0] : null;
}

/**
 * Count unread messages
 */
function apollo_count_unread_messages( $user_id ) {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_messages 
        WHERE receiver_id = %d AND is_read = 0",
			$user_id
		)
	);
}

/**
 * Count functions
 */
function apollo_count_user_events( $user_id ) {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE 'event_status_%%'",
			$user_id
		)
	);
}

function apollo_count_user_nucleos( $user_id ) {
	return count( apollo_get_user_nucleos( $user_id ) );
}

function apollo_count_user_posts( $user_id ) {
	return count_user_posts( $user_id, 'post' );
}

function apollo_count_user_communities( $user_id ) {
	return count( apollo_get_user_communities( $user_id ) );
}

/**
 * Get extended user meta
 */
function apollo_get_user_meta_extended( $user_id ) {
	return array(
		'bio'             => get_user_meta( $user_id, 'description', true ),
		'location'        => get_user_meta( $user_id, 'user_location', true ),
		'role_display'    => apollo_get_user_display_role( $user_id ),
		'verified'        => (bool) get_user_meta( $user_id, 'verified', true ),
		'privacy_profile' => get_user_meta( $user_id, 'privacy_profile', true ) ?: 'public',
	);
}
