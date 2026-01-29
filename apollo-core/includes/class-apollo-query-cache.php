<?php
/**
 * Apollo Query Cache Manager
 *
 * PHASE 8: Optimization - Query Caching with Transients
 *
 * Provides intelligent caching for WP_Query and custom queries
 * using WordPress transients with automatic cache invalidation.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Cache Manager Class
 *
 * Handles caching of expensive database queries with smart invalidation.
 *
 * @since 2.0.0
 */
class Query_Cache {

	/**
	 * Cache prefix for all Apollo transients.
	 *
	 * @var string
	 */
	private const CACHE_PREFIX = 'apollo_qc_';

	/**
	 * Default cache TTL in seconds (1 hour).
	 *
	 * @var int
	 */
	private const DEFAULT_TTL = HOUR_IN_SECONDS;

	/**
	 * Cache groups for selective invalidation.
	 *
	 * @var array<string, string[]>
	 */
	private static array $cache_groups = array(
		'events'      => array( 'event_listing', 'event_dj', 'event_local' ),
		'classifieds' => array( 'advert' ),
		'social'      => array( 'activity', 'profile' ),
		'users'       => array( 'user' ),
	);

	/**
	 * Initialize cache hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init(): void {
		// Invalidate on post changes.
		add_action( 'save_post', array( self::class, 'invalidate_on_post_save' ), 10, 2 );
		add_action( 'delete_post', array( self::class, 'invalidate_on_post_delete' ) );
		add_action( 'transition_post_status', array( self::class, 'invalidate_on_status_change' ), 10, 3 );

		// Invalidate on user changes.
		add_action( 'profile_update', array( self::class, 'invalidate_user_cache' ) );
		add_action( 'user_register', array( self::class, 'invalidate_user_cache' ) );
		add_action( 'delete_user', array( self::class, 'invalidate_user_cache' ) );

		// Invalidate on term changes.
		add_action( 'edited_term', array( self::class, 'invalidate_term_cache' ), 10, 3 );
		add_action( 'delete_term', array( self::class, 'invalidate_term_cache' ), 10, 3 );

		// Add cache clear hook for admin.
		add_action( 'admin_post_apollo_clear_cache', array( self::class, 'handle_clear_cache' ) );
	}

	/**
	 * Get cached query results or execute and cache.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $key      Unique cache key.
	 * @param callable $callback Query callback to execute on cache miss.
	 * @param string   $group    Cache group for invalidation.
	 * @param int      $ttl      Cache TTL in seconds.
	 * @return mixed Query results.
	 */
	public static function remember( string $key, callable $callback, string $group = 'general', int $ttl = self::DEFAULT_TTL ): mixed {
		$cache_key = self::build_key( $key, $group );

		// Try to get from transient.
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			/**
			 * Filter cached query results.
			 *
			 * @since 2.0.0
			 *
			 * @param mixed  $cached The cached data.
			 * @param string $key    The cache key.
			 * @param string $group  The cache group.
			 */
			return apply_filters( 'apollo_query_cache_hit', $cached, $key, $group );
		}

		// Execute callback on cache miss.
		$result = $callback();

		// Cache the result.
		set_transient( $cache_key, $result, $ttl );

		// Track cache key for group invalidation.
		self::track_key( $cache_key, $group );

		/**
		 * Action fired after caching query results.
		 *
		 * @since 2.0.0
		 *
		 * @param mixed  $result The query result.
		 * @param string $key    The cache key.
		 * @param string $group  The cache group.
		 */
		do_action( 'apollo_query_cache_set', $result, $key, $group );

		return $result;
	}

	/**
	 * Get cached events query.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $args  WP_Query arguments.
	 * @param int                  $ttl   Cache TTL.
	 * @return WP_Query Cached or fresh query.
	 */
	public static function get_events( array $args, int $ttl = self::DEFAULT_TTL ): WP_Query {
		$defaults = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
		);

		$args = wp_parse_args( $args, $defaults );
		$key  = 'events_' . md5( wp_json_encode( $args ) );

		$posts = self::remember(
			$key,
			function () use ( $args ) {
				$query = new WP_Query( $args );
				return array(
					'posts'       => $query->posts,
					'found_posts' => $query->found_posts,
					'max_pages'   => $query->max_num_pages,
				);
			},
			'events',
			$ttl
		);

		// Reconstruct WP_Query from cached data.
		$query                = new WP_Query();
		$query->posts         = $posts['posts'];
		$query->found_posts   = $posts['found_posts'];
		$query->max_num_pages = $posts['max_pages'];
		$query->post_count    = count( $posts['posts'] );

		return $query;
	}

	/**
	 * Get cached classifieds query.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $args WP_Query arguments.
	 * @param int                  $ttl  Cache TTL.
	 * @return WP_Query Cached or fresh query.
	 */
	public static function get_classifieds( array $args, int $ttl = self::DEFAULT_TTL ): WP_Query {
		$defaults = array(
			'post_type'      => 'advert',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
		);

		$args = wp_parse_args( $args, $defaults );
		$key  = 'classifieds_' . md5( wp_json_encode( $args ) );

		$posts = self::remember(
			$key,
			function () use ( $args ) {
				$query = new WP_Query( $args );
				return array(
					'posts'       => $query->posts,
					'found_posts' => $query->found_posts,
					'max_pages'   => $query->max_num_pages,
				);
			},
			'classifieds',
			$ttl
		);

		$query                = new WP_Query();
		$query->posts         = $posts['posts'];
		$query->found_posts   = $posts['found_posts'];
		$query->max_num_pages = $posts['max_pages'];
		$query->post_count    = count( $posts['posts'] );

		return $query;
	}

	/**
	 * Get cached user activity feed.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @param int   $ttl     Cache TTL.
	 * @return array Activity items.
	 */
	public static function get_user_activity( int $user_id, array $args = array(), int $ttl = 300 ): array {
		$key = "user_activity_{$user_id}_" . md5( wp_json_encode( $args ) );

		return self::remember(
			$key,
			function () use ( $user_id, $args ) {
				/**
				 * Filter to get user activity.
				 *
				 * @since 2.0.0
				 *
				 * @param array $activities Default empty array.
				 * @param int   $user_id    User ID.
				 * @param array $args       Query arguments.
				 */
				return apply_filters( 'apollo_get_user_activity', array(), $user_id, $args );
			},
			'social',
			$ttl
		);
	}

	/**
	 * Build a unique cache key.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key   Base key.
	 * @param string $group Cache group.
	 * @return string Full cache key.
	 */
	private static function build_key( string $key, string $group ): string {
		$version = self::get_group_version( $group );
		return self::CACHE_PREFIX . $group . '_' . $key . '_v' . $version;
	}

	/**
	 * Get cache group version for invalidation.
	 *
	 * @since 2.0.0
	 *
	 * @param string $group Cache group.
	 * @return int Version number.
	 */
	private static function get_group_version( string $group ): int {
		$version = get_option( self::CACHE_PREFIX . 'version_' . $group, 1 );
		return (int) $version;
	}

	/**
	 * Increment group version to invalidate all cached items.
	 *
	 * @since 2.0.0
	 *
	 * @param string $group Cache group.
	 * @return void
	 */
	public static function invalidate_group( string $group ): void {
		$current = self::get_group_version( $group );
		update_option( self::CACHE_PREFIX . 'version_' . $group, $current + 1, false );

		/**
		 * Action fired after cache group invalidation.
		 *
		 * @since 2.0.0
		 *
		 * @param string $group The invalidated group.
		 */
		do_action( 'apollo_cache_group_invalidated', $group );
	}

	/**
	 * Track cache key for cleanup.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @return void
	 */
	private static function track_key( string $key, string $group ): void {
		$tracked = get_option( self::CACHE_PREFIX . 'tracked_' . $group, array() );

		if ( ! in_array( $key, $tracked, true ) ) {
			$tracked[] = $key;
			// Keep only last 100 keys per group.
			if ( count( $tracked ) > 100 ) {
				$tracked = array_slice( $tracked, -100 );
			}
			update_option( self::CACHE_PREFIX . 'tracked_' . $group, $tracked, false );
		}
	}

	/**
	 * Invalidate cache on post save.
	 *
	 * @since 2.0.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public static function invalidate_on_post_save( int $post_id, \WP_Post $post ): void {
		self::invalidate_by_post_type( $post->post_type );
	}

	/**
	 * Invalidate cache on post delete.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function invalidate_on_post_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post ) {
			self::invalidate_by_post_type( $post->post_type );
		}
	}

	/**
	 * Invalidate cache on post status change.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public static function invalidate_on_status_change( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only invalidate if transitioning to/from publish.
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			self::invalidate_by_post_type( $post->post_type );
		}
	}

	/**
	 * Invalidate cache by post type.
	 *
	 * @since 2.0.0
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	private static function invalidate_by_post_type( string $post_type ): void {
		foreach ( self::$cache_groups as $group => $types ) {
			if ( in_array( $post_type, $types, true ) ) {
				self::invalidate_group( $group );
				break;
			}
		}
	}

	/**
	 * Invalidate user cache.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function invalidate_user_cache( int $user_id ): void {
		self::invalidate_group( 'users' );
		self::invalidate_group( 'social' );
	}

	/**
	 * Invalidate term cache.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public static function invalidate_term_cache( int $term_id, int $tt_id, string $taxonomy ): void {
		// Invalidate related groups based on taxonomy.
		if ( str_contains( $taxonomy, 'event' ) ) {
			self::invalidate_group( 'events' );
		}
	}

	/**
	 * Handle admin cache clear request.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function handle_clear_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'apollo-core' ) );
		}

		check_admin_referer( 'apollo_clear_cache' );

		// Clear all groups.
		foreach ( array_keys( self::$cache_groups ) as $group ) {
			self::invalidate_group( $group );
		}
		self::invalidate_group( 'general' );

		// Clear tracked keys.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . self::CACHE_PREFIX . '%'
			)
		);

		/**
		 * Action fired after full cache clear.
		 *
		 * @since 2.0.0
		 */
		do_action( 'apollo_cache_cleared' );

		wp_safe_redirect( add_query_arg( 'cache_cleared', '1', wp_get_referer() ) );
		exit;
	}

	/**
	 * Get cache statistics.
	 *
	 * @since 2.0.0
	 *
	 * @return array{groups: array, total_keys: int, memory_estimate: string}
	 */
	public static function get_stats(): array {
		global $wpdb;

		$stats = array(
			'groups'          => array(),
			'total_keys'      => 0,
			'memory_estimate' => '0 KB',
		);

		foreach ( array_keys( self::$cache_groups ) as $group ) {
			$tracked = get_option( self::CACHE_PREFIX . 'tracked_' . $group, array() );
			$version = self::get_group_version( $group );

			$stats['groups'][ $group ] = array(
				'tracked_keys' => count( $tracked ),
				'version'      => $version,
			);

			$stats['total_keys'] += count( $tracked );
		}

		// Estimate memory usage.
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . self::CACHE_PREFIX . '%'
			)
		);

		if ( $result ) {
			$stats['memory_estimate'] = size_format( (int) $result );
		}

		return $stats;
	}
}
