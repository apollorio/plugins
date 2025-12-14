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
	// WordPress doesn't have a native flush by group
	// So we increment a version number
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
 * Invalidate all Apollo caches
 *
 * @return void
 */
function apollo_cache_flush_all(): void {
	apollo_cache_flush_group( 'apollo_quiz' );
	apollo_cache_flush_group( 'apollo_forms' );
	apollo_cache_flush_group( 'apollo_memberships' );
	apollo_cache_flush_group( 'apollo_core' );
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

			// This is a simplified stats - real implementation would need cache plugin support
			WP_CLI::line( 'Cache groups: apollo_quiz, apollo_forms, apollo_memberships, apollo_core' );
			WP_CLI::line( 'TTL: 1 hour (3600 seconds)' );
			WP_CLI::line( 'Versioning: Enabled' );

			WP_CLI::success( 'Use "wp apollo cache flush" to clear all caches.' );
		}
	);
}//end if
