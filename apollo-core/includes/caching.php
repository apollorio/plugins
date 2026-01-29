<?php

declare(strict_types=1);

/**
 * Apollo Core - Caching Helper Functions
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get cached data or execute callback and cache result
 *
 * @param string   $key      Cache key.
 * @param callable $callback Callback to execute if cache miss.
 * @param string   $group    Cache group (default: 'apollo_core').
 * @param int      $ttl      Time to live in seconds (default: 1 hour).
 * @return mixed Cached or fresh data.
 */
function apollo_cache_remember( string $key, callable $callback, string $group = 'apollo_core', int $ttl = 3600 ) {
	$cached = wp_cache_get( $key, $group );

	if ( $cached !== false ) {
		return $cached;
	}

	$data = $callback();

	wp_cache_set( $key, $data, $group, $ttl );

	return $data;
}

/**
 * Forget (delete) cached data
 *
 * @param string $key   Cache key.
 * @param string $group Cache group (default: 'apollo_core').
 * @return bool True on success, false on failure.
 */
function apollo_cache_forget( string $key, string $group = 'apollo_core' ): bool {
	return wp_cache_delete( $key, $group );
}

/**
 * Flush all cache in a group
 *
 * @param string $group Cache group.
 * @return void
 */
function apollo_cache_flush_group( string $group ): void {
	// WordPress doesn't have a native flush by group.
	// So we increment a version number.
	$version_key = "apollo_cache_version_{$group}";
	$version     = (int) wp_cache_get( $version_key, 'apollo_core' );
	wp_cache_set( $version_key, $version + 1, 'apollo_core', DAY_IN_SECONDS );
}

/**
 * Get versioned cache key
 *
 * @param string $key   Base cache key.
 * @param string $group Cache group.
 * @return string Versioned key.
 */
function apollo_cache_versioned_key( string $key, string $group ): string {
	$version_key = "apollo_cache_version_{$group}";
	$version     = (int) wp_cache_get( $version_key, 'apollo_core' );

	if ( $version === 0 ) {
		$version = 1;
		wp_cache_set( $version_key, $version, 'apollo_core', DAY_IN_SECONDS );
	}

	return "{$key}_v{$version}";
}

/**
 * Cache quiz schema with versioning
 *
 * @param string $form_type Form type.
 * @param array  $schema    Schema data.
 * @return void
 */
function apollo_cache_quiz_schema( string $form_type, array $schema ): void {
	$key = apollo_cache_versioned_key( "quiz_schema_{$form_type}", 'apollo_quiz' );
	wp_cache_set( $key, $schema, 'apollo_quiz', HOUR_IN_SECONDS );
}

/**
 * Get cached quiz schema
 *
 * @param string $form_type Form type.
 * @return array|false Schema data or false if not cached.
 */
function apollo_cache_get_quiz_schema( string $form_type ) {
	$key = apollo_cache_versioned_key( "quiz_schema_{$form_type}", 'apollo_quiz' );

	return wp_cache_get( $key, 'apollo_quiz' );
}

/**
 * Cache form schema with versioning
 *
 * @param string $form_type Form type.
 * @param array  $schema    Schema data.
 * @return void
 */
function apollo_cache_form_schema( string $form_type, array $schema ): void {
	$key = apollo_cache_versioned_key( "form_schema_{$form_type}", 'apollo_forms' );
	wp_cache_set( $key, $schema, 'apollo_forms', HOUR_IN_SECONDS );
}

/**
 * Get cached form schema
 *
 * @param string $form_type Form type.
 * @return array|false Schema data or false if not cached.
 */
function apollo_cache_get_form_schema( string $form_type ) {
	$key = apollo_cache_versioned_key( "form_schema_{$form_type}", 'apollo_forms' );

	return wp_cache_get( $key, 'apollo_forms' );
}

/**
 * Cache memberships with versioning
 *
 * @param array $memberships Memberships data.
 * @return void
 */
function apollo_cache_memberships( array $memberships ): void {
	$key = apollo_cache_versioned_key( 'memberships', 'apollo_memberships' );
	wp_cache_set( $key, $memberships, 'apollo_memberships', HOUR_IN_SECONDS );
}

/**
 * Get cached memberships
 *
 * @return array|false Memberships data or false if not cached.
 */
function apollo_cache_get_memberships() {
	$key = apollo_cache_versioned_key( 'memberships', 'apollo_memberships' );

	return wp_cache_get( $key, 'apollo_memberships' );
}

/**
 * Cache event meta data
 *
 * @since 3.1.0
 * @param int   $event_id Event post ID.
 * @param array $meta     Meta data to cache.
 * @return void
 */
function apollo_cache_event_meta( int $event_id, array $meta ): void {
	$key = "event_meta_{$event_id}";
	wp_cache_set( $key, $meta, 'apollo_events', HOUR_IN_SECONDS );
}

/**
 * Get cached event meta data
 *
 * @since 3.1.0
 * @param int          $event_id Event post ID.
 * @param string|null  $key      Specific meta key to retrieve, or null for all.
 * @return mixed Cached meta data, specific value, or false if not cached.
 */
function apollo_cache_get_event_meta( int $event_id, ?string $key = null ) {
	$cache_key = "event_meta_{$event_id}";
	$cached    = wp_cache_get( $cache_key, 'apollo_events' );

	if ( false === $cached ) {
		return false;
	}

	if ( null !== $key ) {
		return $cached[ $key ] ?? false;
	}

	return $cached;
}

/**
 * Get event start date with caching
 *
 * @since 3.1.0
 * @param int $event_id Event post ID.
 * @return string|false Event start date or false if not found.
 */
function apollo_get_cached_event_start_date( int $event_id ) {
	// Try cache first.
	$cached = apollo_cache_get_event_meta( $event_id, '_event_start_date' );
	if ( false !== $cached ) {
		return $cached;
	}

	// Fetch from database.
	$start_date = get_post_meta( $event_id, '_event_start_date', true );

	// Cache all event meta for future requests.
	$all_meta = array(
		'_event_start_date'  => $start_date,
		'_event_start_time'  => get_post_meta( $event_id, '_event_start_time', true ),
		'_event_end_date'    => get_post_meta( $event_id, '_event_end_date', true ),
		'_event_end_time'    => get_post_meta( $event_id, '_event_end_time', true ),
		'_event_venue_id'    => get_post_meta( $event_id, '_event_venue_id', true ),
		'_event_local_id'    => get_post_meta( $event_id, '_event_local_id', true ),
		'_event_dj_slots'    => get_post_meta( $event_id, '_event_dj_slots', true ),
		'_event_cover_price' => get_post_meta( $event_id, '_event_cover_price', true ),
	);
	apollo_cache_event_meta( $event_id, $all_meta );

	return $start_date;
}

/**
 * Invalidate event meta cache
 *
 * @since 3.1.0
 * @param int $event_id Event post ID.
 * @return bool
 */
function apollo_cache_invalidate_event_meta( int $event_id ): bool {
	return wp_cache_delete( "event_meta_{$event_id}", 'apollo_events' );
}

/**
 * Bulk cache event meta for multiple events
 *
 * @since 3.1.0
 * @param array $event_ids Array of event post IDs.
 * @return void
 */
function apollo_cache_bulk_event_meta( array $event_ids ): void {
	global $wpdb;

	if ( empty( $event_ids ) ) {
		return;
	}

	// Get all uncached event IDs.
	$uncached_ids = array();
	foreach ( $event_ids as $event_id ) {
		if ( false === wp_cache_get( "event_meta_{$event_id}", 'apollo_events' ) ) {
			$uncached_ids[] = (int) $event_id;
		}
	}

	if ( empty( $uncached_ids ) ) {
		return;
	}

	// Fetch all meta for uncached events in one query.
	$placeholders = implode( ',', array_fill( 0, count( $uncached_ids ), '%d' ) );
	$meta_keys    = array(
		'_event_start_date',
		'_event_start_time',
		'_event_end_date',
		'_event_end_time',
		'_event_venue_id',
		'_event_local_id',
		'_event_dj_slots',
		'_event_cover_price',
	);
	$key_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$sql = $wpdb->prepare(
		"SELECT post_id, meta_key, meta_value
		 FROM {$wpdb->postmeta}
		 WHERE post_id IN ({$placeholders})
		 AND meta_key IN ({$key_placeholders})",
		array_merge( $uncached_ids, $meta_keys )
	);

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( $sql );

	// Organize results by post_id.
	$meta_by_post = array();
	foreach ( $uncached_ids as $id ) {
		$meta_by_post[ $id ] = array();
	}

	foreach ( $results as $row ) {
		$meta_by_post[ $row->post_id ][ $row->meta_key ] = maybe_unserialize( $row->meta_value );
	}

	// Cache each event's meta.
	foreach ( $meta_by_post as $event_id => $meta ) {
		apollo_cache_event_meta( $event_id, $meta );
	}
}

// Hook to invalidate cache when event meta is updated.
add_action(
	'updated_post_meta',
	function ( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( 0 === strpos( $meta_key, '_event_' ) ) {
			apollo_cache_invalidate_event_meta( $post_id );
		}
	},
	10,
	4
);

add_action(
	'added_post_meta',
	function ( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( 0 === strpos( $meta_key, '_event_' ) ) {
			apollo_cache_invalidate_event_meta( $post_id );
		}
	},
	10,
	4
);

add_action(
	'deleted_post_meta',
	function ( $meta_ids, $post_id, $meta_key, $meta_value ) {
		if ( 0 === strpos( $meta_key, '_event_' ) ) {
			apollo_cache_invalidate_event_meta( $post_id );
		}
	},
	10,
	4
);

/**
 * Invalidate all Apollo caches
 *
 * @return void
 */
function apollo_cache_flush_all(): void {
	apollo_cache_flush_group( 'apollo_quiz' );
	apollo_cache_flush_group( 'apollo_forms' );
	apollo_cache_flush_group( 'apollo_memberships' );
	apollo_cache_flush_group( 'apollo_core' );
	apollo_cache_flush_group( 'apollo_events' );
}

/**
 * WP-CLI command to flush Apollo caches
 *
 * Usage: wp apollo cache flush
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'apollo cache flush',
		function () {
			apollo_cache_flush_all();
			WP_CLI::success( 'Apollo caches flushed successfully.' );
		}
	);

	WP_CLI::add_command(
		'apollo cache stats',
		function () {
			WP_CLI::line( 'Apollo Cache Statistics:' );
			WP_CLI::line( '------------------------' );

			// This is a simplified stats - real implementation would need cache plugin support.
			WP_CLI::line( 'Cache groups: apollo_quiz, apollo_forms, apollo_memberships, apollo_core, apollo_events' );
			WP_CLI::line( 'TTL: 1 hour (3600 seconds)' );
			WP_CLI::line( 'Versioning: Enabled' );

			WP_CLI::success( 'Use "wp apollo cache flush" to clear all caches.' );
		}
	);
}//end if
