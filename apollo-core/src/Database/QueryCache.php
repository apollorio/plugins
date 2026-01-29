<?php
/**
 * Apollo Query Cache
 *
 * Provides a caching layer for database queries to reduce load.
 * Supports both transient and object cache backends.
 *
 * @package Apollo\Core\Database
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Database;

/**
 * Query caching service.
 */
final class QueryCache {

	/**
	 * Cache group name.
	 */
	private const CACHE_GROUP = 'apollo_queries';

	/**
	 * Default TTL in seconds (1 hour).
	 */
	private const DEFAULT_TTL = 3600;

	/**
	 * Whether object cache is available.
	 *
	 * @var bool
	 */
	private bool $hasObjectCache;

	/**
	 * Cache statistics.
	 *
	 * @var array{hits: int, misses: int, sets: int}
	 */
	private array $stats = array(
		'hits'   => 0,
		'misses' => 0,
		'sets'   => 0,
	);

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hasObjectCache = wp_using_ext_object_cache();
	}

	/**
	 * Get cached value or execute callback and cache result.
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Callback to generate value if not cached.
	 * @param int      $ttl      Time to live in seconds.
	 * @return mixed
	 */
	public function remember( string $key, callable $callback, int $ttl = self::DEFAULT_TTL ): mixed {
		$cached = $this->get( $key );

		if ( null !== $cached ) {
			return $cached;
		}

		$value = $callback();
		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Get a cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Null if not found.
	 */
	public function get( string $key ): mixed {
		$fullKey = $this->buildKey( $key );

		if ( $this->hasObjectCache ) {
			$value = wp_cache_get( $fullKey, self::CACHE_GROUP );
		} else {
			$value = get_transient( $fullKey );
		}

		if ( false === $value ) {
			++$this->stats['misses'];
			return null;
		}

		++$this->stats['hits'];
		return $value;
	}

	/**
	 * Set a cached value.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Time to live in seconds.
	 * @return bool
	 */
	public function set( string $key, mixed $value, int $ttl = self::DEFAULT_TTL ): bool {
		$fullKey = $this->buildKey( $key );

		++$this->stats['sets'];

		if ( $this->hasObjectCache ) {
			return wp_cache_set( $fullKey, $value, self::CACHE_GROUP, $ttl );
		}

		return set_transient( $fullKey, $value, $ttl );
	}

	/**
	 * Delete a cached value.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		$fullKey = $this->buildKey( $key );

		if ( $this->hasObjectCache ) {
			return wp_cache_delete( $fullKey, self::CACHE_GROUP );
		}

		return delete_transient( $fullKey );
	}

	/**
	 * Delete multiple cached values by pattern.
	 *
	 * @param string $pattern Key pattern (supports * wildcard at end).
	 * @return int Number of deleted entries.
	 */
	public function deleteByPattern( string $pattern ): int {
		global $wpdb;

		$deleted = 0;

		if ( $this->hasObjectCache ) {
			// Object cache flush group if available
			if ( function_exists( 'wp_cache_flush_group' ) ) {
				wp_cache_flush_group( self::CACHE_GROUP );
				return -1; // Unknown count
			}
			return 0;
		}

		// For transients, query the database
		$pattern = str_replace( '*', '%', $pattern );
		$fullPattern = '_transient_apollo_qc_' . $pattern;

		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name
				FROM {$wpdb->options}
				WHERE option_name LIKE %s",
				$fullPattern
			)
		);

		foreach ( $keys as $optionName ) {
			$transientKey = str_replace( '_transient_', '', $optionName );
			if ( delete_transient( $transientKey ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Invalidate cache for a specific entity type.
	 *
	 * @param string $type Entity type (post, user, term, etc.).
	 * @param int    $id   Entity ID.
	 * @return int Number of deleted entries.
	 */
	public function invalidate( string $type, int $id ): int {
		return $this->deleteByPattern( "{$type}_{$id}_*" );
	}

	/**
	 * Invalidate all post-related caches.
	 *
	 * @param int $postId Post ID.
	 * @return void
	 */
	public function invalidatePost( int $postId ): void {
		$this->invalidate( 'post', $postId );

		// Also invalidate any list caches
		$this->deleteByPattern( 'posts_list_*' );
	}

	/**
	 * Invalidate all user-related caches.
	 *
	 * @param int $userId User ID.
	 * @return void
	 */
	public function invalidateUser( int $userId ): void {
		$this->invalidate( 'user', $userId );
	}

	/**
	 * Generate cache key for a query.
	 *
	 * @param string              $query Query string or identifier.
	 * @param array<string,mixed> $args  Query arguments.
	 * @return string
	 */
	public function generateKey( string $query, array $args = array() ): string {
		$data = $query . wp_json_encode( $args );
		return md5( $data );
	}

	/**
	 * Build full cache key.
	 *
	 * @param string $key Base key.
	 * @return string
	 */
	private function buildKey( string $key ): string {
		return 'apollo_qc_' . $key;
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array{hits: int, misses: int, sets: int, ratio: float}
	 */
	public function getStats(): array {
		$total = $this->stats['hits'] + $this->stats['misses'];
		$ratio = $total > 0 ? $this->stats['hits'] / $total : 0;

		return array_merge(
			$this->stats,
			array( 'ratio' => round( $ratio, 2 ) )
		);
	}

	/**
	 * Check if object cache is available.
	 *
	 * @return bool
	 */
	public function hasObjectCache(): bool {
		return $this->hasObjectCache;
	}

	/**
	 * Cache common event queries.
	 *
	 * @param string              $key   Query identifier.
	 * @param array<string,mixed> $args  WP_Query arguments.
	 * @param int                 $ttl   Cache TTL.
	 * @return array<\WP_Post>
	 */
	public function cacheEventQuery( string $key, array $args, int $ttl = 300 ): array {
		$cacheKey = 'events_' . $this->generateKey( $key, $args );

		return $this->remember(
			$cacheKey,
			function () use ( $args ) {
				$query = new \WP_Query(
					array_merge(
						$args,
						array(
							'post_type'   => 'event_listing',
							'post_status' => 'publish',
						)
					)
				);
				return $query->posts;
			},
			$ttl
		);
	}

	/**
	 * Cache user-related data.
	 *
	 * @param int      $userId   User ID.
	 * @param string   $key      Data key.
	 * @param callable $callback Data loader.
	 * @param int      $ttl      Cache TTL.
	 * @return mixed
	 */
	public function cacheUserData( int $userId, string $key, callable $callback, int $ttl = 600 ): mixed {
		$cacheKey = "user_{$userId}_{$key}";
		return $this->remember( $cacheKey, $callback, $ttl );
	}

	/**
	 * Register cache invalidation hooks.
	 *
	 * @return void
	 */
	public function registerInvalidationHooks(): void {
		// Invalidate on post changes
		add_action( 'save_post', array( $this, 'invalidatePost' ) );
		add_action( 'delete_post', array( $this, 'invalidatePost' ) );
		add_action( 'transition_post_status', function ( $new, $old, $post ) {
			$this->invalidatePost( $post->ID );
		}, 10, 3 );

		// Invalidate on user changes
		add_action( 'profile_update', array( $this, 'invalidateUser' ) );
		add_action( 'delete_user', array( $this, 'invalidateUser' ) );

		// Invalidate on meta changes
		add_action( 'updated_post_meta', function ( $meta_id, $post_id ) {
			$this->invalidatePost( (int) $post_id );
		}, 10, 2 );

		add_action( 'updated_user_meta', function ( $meta_id, $user_id ) {
			$this->invalidateUser( (int) $user_id );
		}, 10, 2 );
	}
}

/**
 * Helper function to get the query cache instance.
 *
 * @return QueryCache
 */
function apollo_cache(): QueryCache {
	return QueryCache::getInstance();
}
