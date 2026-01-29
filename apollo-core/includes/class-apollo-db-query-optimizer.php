<?php
/**
 * Apollo Database Query Optimizer
 * PHASE 7: Performance Optimization - Database Queries
 * Optimizes database queries used by ViewModels and templates
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

namespace Apollo_Core;

use WP_Query;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Database Query Optimizer Class
 *
 * Handles optimization of database queries for better performance.
 *
 * @package Apollo_Core
 */
class DB_Query_Optimizer {

	/**
	 * Initialize query optimizations
	 */
	public static function init(): void {
		add_filter( 'posts_clauses', array( self::class, 'optimize_event_queries' ), 10, 2 );
		add_filter( 'users_pre_query', array( self::class, 'optimize_user_queries' ), 10, 2 );
		add_action( 'pre_get_posts', array( self::class, 'optimize_main_query' ) );
		add_filter( 'query', array( self::class, 'optimize_custom_queries' ) );
	}

	/**
	 * Optimize event listing queries
	 *
	 * @param array    $clauses The query clauses.
	 * @param WP_Query $query   The query object.
	 * @return array
	 */
	public static function optimize_event_queries( array $clauses, WP_Query $query ): array {
		global $wpdb;

		// Only optimize event_listing post type queries.
		if ( ! isset( $query->query_vars['post_type'] ) ||
			'event_listing' !== $query->query_vars['post_type'] ) {
			return $clauses;
		}

		// Add efficient indexes to WHERE clause.
		if ( ! empty( $clauses['where'] ) ) {
			// Ensure post_status is indexed properly.
			$clauses['where'] = str_replace(
				"post_status = 'publish'",
				"post_status = 'publish' AND post_type = 'event_listing'",
				$clauses['where']
			);
		}

		// Optimize JOINs for meta queries.
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			$clauses['join'] = self::optimize_meta_joins( $clauses['join'], $query->query_vars['meta_query'] );
		}

		// Add query hints for better execution plans.
		if ( isset( $query->query_vars['posts_per_page'] ) &&
			$query->query_vars['posts_per_page'] > 50 ) {
			// For large result sets, use different optimization.
			add_filter(
				'query',
				function ( $sql ) {
					if ( false !== strpos( $sql, 'SQL_CALC_FOUND_ROWS' ) ) {
						// Remove SQL_CALC_FOUND_ROWS for performance on large datasets.
						$sql = str_replace( 'SQL_CALC_FOUND_ROWS', '', $sql );
					}
					return $sql;
				}
			);
		}

		return $clauses;
	}

	/**
	 * Optimize user queries
	 *
	 * @param mixed         $results The query results.
	 * @param WP_User_Query $query   The query object.
	 * @return mixed
	 */
	public static function optimize_user_queries( mixed $results, WP_User_Query $query ): mixed {
		// Only optimize if this is a user query we care about.
		if ( ! isset( $query->query_vars['apollo_optimized'] ) ) {
			return $results;
		}

		// Add query optimizations for user meta.
		add_filter(
			'query',
			function ( $sql ) {
				global $wpdb;

				// Ensure proper indexing on user queries.
				// Use $wpdb->users for multisite compatibility.
				$users_table = $wpdb->users;
				if ( false !== strpos( $sql, $users_table ) ) {
					// Add FORCE INDEX hint if needed.
					$sql = str_replace(
						"FROM {$users_table}",
						"FROM {$users_table} FORCE INDEX (PRIMARY)",
						$sql
					);
				}
				return $sql;
			}
		);

		return $results;
	}

	/**
	 * Optimize main WordPress query
	 *
	 * @param WP_Query $query The query object.
	 */
	public static function optimize_main_query( WP_Query $query ): void {
		if ( ! $query->is_main_query() || ! is_single() ) {
			return;
		}

		// Optimize single post queries.
		if ( $query->is_single && isset( $query->query_vars['post_type'] ) ) {
			$post_type = $query->query_vars['post_type'];

			if ( 'event_listing' === $post_type ) {
				// Preload related data for events.
				add_action(
					'wp',
					function () use ( $query ) {
						if ( $query->have_posts() ) {
							$post = $query->post;

							// Preload event meta in one query.
							$meta_keys = array(
								'event_date',
								'event_time',
								'event_location',
								'event_price',
								'event_capacity',
								'event_views',
							);

							$meta_values  = get_post_meta( $post->ID );
							$preload_meta = array_intersect_key( $meta_values, array_flip( $meta_keys ) );

							// Cache the preloaded meta.
							wp_cache_set( "event_meta_{$post->ID}", $preload_meta, 'apollo_events', HOUR_IN_SECONDS );
						}
					}
				);
			}
		}
	}

	/**
	 * Optimize custom queries
	 *
	 * @param string $sql The SQL query.
	 * @return string
	 */
	public static function optimize_custom_queries( string $sql ): string {
		// Skip optimization for non-SELECT queries.
		if ( 0 !== stripos( trim( $sql ), 'SELECT' ) ) {
			return $sql;
		}

		// Note: Removed COUNT optimization as it was breaking WordPress core queries.
		// that use table aliases (e.g., COUNT(post.ID) with no 'post' table/alias)

		// Add query timeout for long-running queries.
		if ( strlen( $sql ) > 1000 ) {
			// Add MAX_EXECUTION_TIME hint (MySQL 5.7+).
			$sql = 'SET SESSION MAX_EXECUTION_TIME=30000; ' . $sql;
		}

		return $sql;
	}

	/**
	 * Optimize meta query JOINs
	 *
	 * @param string $join      The JOIN clause.
	 * @param array  $meta_query The meta query array.
	 * @return string
	 */
	private static function optimize_meta_joins( string $join, array $meta_query ): string {
		global $wpdb;

		// Analyze meta query for optimization opportunities.
		$meta_keys = array();
		foreach ( $meta_query as $meta ) {
			if ( isset( $meta['key'] ) ) {
				$meta_keys[] = $meta['key'];
			}
		}

		// For multiple meta keys, ensure efficient JOIN order.
		if ( count( $meta_keys ) > 1 ) {
			// Add table aliases for better optimization.
			$join = str_replace(
				'wp_postmeta',
				'wp_postmeta pm1',
				$join
			);

			// Add additional JOINs with proper aliases.
			$meta_keys_count = count( $meta_keys );
			for ( $i = 2; $i <= $meta_keys_count; $i++ ) {
				$join .= " INNER JOIN {$wpdb->postmeta} pm{$i} ON pm{$i}.post_id = pm1.post_id";
			}
		}

		return $join;
	}

	/**
	 * Create optimized database indexes
	 *
	 * @since 3.1.0 Added postmeta indexes for event queries
	 */
	public static function create_optimized_indexes(): void {
		global $wpdb;

		// Check if we've already created indexes to avoid redundant operations.
		$indexes_version = get_option( 'apollo_db_indexes_version', '0' );
		if ( version_compare( $indexes_version, '3.1.0', '>=' ) ) {
			return;
		}

		// Standard MySQL indexes (no WHERE clause - MariaDB/MySQL compatible).
		$indexes = array(
			// Event listing indexes on posts table.
			"ALTER TABLE {$wpdb->posts} ADD INDEX idx_apollo_post_type_status (post_type, post_status, post_date)",
			"ALTER TABLE {$wpdb->posts} ADD INDEX idx_apollo_post_modified (post_type, post_modified)",

			// Critical: Postmeta indexes for event date queries (HIGH priority fix).
			"ALTER TABLE {$wpdb->postmeta} ADD INDEX idx_apollo_meta_key_value (meta_key(50), meta_value(50))",

			// User meta indexes for common queries.
			"ALTER TABLE {$wpdb->usermeta} ADD INDEX idx_apollo_usermeta_key (meta_key(50), user_id)",
		);

		foreach ( $indexes as $index_sql ) {
			// Suppress errors for indexes that already exist.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $index_sql );
		}

		// Update version flag.
		update_option( 'apollo_db_indexes_version', '3.1.0' );
	}

	/**
	 * Check if a specific index exists
	 *
	 * @param string $table      Table name.
	 * @param string $index_name Index name.
	 * @return bool
	 */
	public static function index_exists( string $table, string $index_name ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s",
				$table,
				$index_name
			)
		);

		return (bool) $result;
	}

	/**
	 * Get query performance statistics
	 */
	public static function get_query_stats(): array {
		global $wpdb, $wp_object_cache;

		$stats = array(
			'total_queries' => $wpdb->num_queries,
			'query_time'    => timer_stop( 0, 3 ),
			'cache_hits'    => 0,
			'cache_misses'  => 0,
			'memory_usage'  => memory_get_peak_usage( true ) / 1024 / 1024, // MB.
		);

		// Get cache statistics if available.
		if ( method_exists( $wp_object_cache, 'getStats' ) ) {
			$cache_stats = $wp_object_cache->getStats();
			// Parse cache stats if available.
		}

		// Get slow queries (queries taking longer than 0.5 seconds).
		$slow_queries          = get_option( 'apollo_slow_queries', array() );
		$stats['slow_queries'] = count( $slow_queries );

		return $stats;
	}

	/**
	 * Log slow queries for analysis
	 *
	 * @param string $query The SQL query.
	 * @param float  $time  The execution time.
	 * @return void
	 */
	public static function log_slow_query( string $query, float $time ): void {
		if ( $time < 0.5 ) { // Only log queries slower than 0.5 seconds.
			return;
		}

		$slow_queries   = get_option( 'apollo_slow_queries', array() );
		$slow_queries[] = array(
			'query'     => substr( $query, 0, 500 ), // Truncate long queries.
			'time'      => $time,
			'timestamp' => current_time( 'timestamp' ),
			'backtrace' => wp_debug_backtrace_summary(),
		);

		// Keep only last 100 slow queries.
		if ( count( $slow_queries ) > 100 ) {
			$slow_queries = array_slice( $slow_queries, -100 );
		}

		update_option( 'apollo_slow_queries', $slow_queries );
	}

	/**
	 * Prefetch related data to reduce N+1 queries
	 *
	 * @param array  $post_ids The post IDs.
	 * @param string $type     The type of data to prefetch.
	 * @return void
	 */
	public static function prefetch_related_data( array $post_ids, string $type = 'event' ): void {
		if ( empty( $post_ids ) ) {
			return;
		}

		switch ( $type ) {
			case 'event':
				// Prefetch event meta in bulk.
				$meta_keys       = array( 'event_date', 'event_location', 'event_price', 'event_capacity' );
				$prefetched_meta = array();

				foreach ( $meta_keys as $key ) {
					$values = get_post_meta_multiple( $post_ids, $key );
					foreach ( $values as $post_id => $value ) {
						$prefetched_meta[ $post_id ][ $key ] = $value;
					}
				}

				// Cache prefetched data.
				foreach ( $prefetched_meta as $post_id => $meta ) {
					wp_cache_set( "event_meta_{$post_id}", $meta, 'apollo_events', HOUR_IN_SECONDS );
				}
				break;

			case 'user':
				// Prefetch user meta in bulk.
				$user_meta_keys       = array( 'profile_views', 'last_active', 'user_rating' );
				$prefetched_user_meta = array();

				foreach ( $user_meta_keys as $key ) {
					$values = get_user_meta_multiple( $post_ids, $key );
					foreach ( $values as $user_id => $value ) {
						$prefetched_user_meta[ $user_id ][ $key ] = $value;
					}
				}

				// Cache prefetched data.
				foreach ( $prefetched_user_meta as $user_id => $meta ) {
					wp_cache_set( "user_meta_{$user_id}", $meta, 'apollo_users', HOUR_IN_SECONDS );
				}
				break;
		}
	}
}

// Helper function for bulk meta retrieval.
/**
 * Get post meta for multiple posts in a single query.
 *
 * @param array  $post_ids The post IDs.
 * @param string $key      The meta key.
 * @return array
 */
function get_post_meta_multiple( array $post_ids, string $key ): array {
	global $wpdb;

	if ( empty( $post_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	$sql          = $wpdb->prepare(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$placeholders})",
		array_merge( array( $key ), $post_ids )
	);

	$results     = $wpdb->get_results( $sql );
	$meta_values = array();

	foreach ( $results as $result ) {
		$meta_values[ $result->post_id ] = maybe_unserialize( $result->meta_value );
	}

	return $meta_values;
}

/**
 * Get user meta for multiple users in a single query.
 *
 * @param array  $user_ids The user IDs.
 * @param string $key      The meta key.
 * @return array
 */
function get_user_meta_multiple( array $user_ids, string $key ): array {
	global $wpdb;

	if ( empty( $user_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
	$sql          = $wpdb->prepare(
		"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ({$placeholders})",
		array_merge( array( $key ), $user_ids )
	);

	$results     = $wpdb->get_results( $sql );
	$meta_values = array();

	foreach ( $results as $result ) {
		$meta_values[ $result->user_id ] = maybe_unserialize( $result->meta_value );
	}

	return $meta_values;
}

// Hook into query execution to log slow queries.
add_filter(
	'query',
	function ( $query ) {
		$start_time = microtime( true );

		// Execute the query and measure time.
		add_action(
			'shutdown',
			function () use ( $query, $start_time ) {
				$end_time       = microtime( true );
				$execution_time = $end_time - $start_time;

				if ( class_exists( DB_Query_Optimizer::class ) ) {
					DB_Query_Optimizer::log_slow_query( $query, $execution_time );
				}
			},
			999
		);

		return $query;
	}
);

// Initialize query optimizations.
if ( class_exists( DB_Query_Optimizer::class ) ) {
	DB_Query_Optimizer::init();

	// Create optimized indexes on plugin activation.
	register_activation_hook( __FILE__, array( DB_Query_Optimizer::class, 'create_optimized_indexes' ) );
}
