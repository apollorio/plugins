<?php
/**
 * Apollo Batch Loader
 *
 * Prevents N+1 query problems by batching related data fetches.
 * Implements DataLoader pattern for efficient database access.
 *
 * @package Apollo\Core\Database
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Database;

/**
 * Batch loader for efficient data fetching.
 *
 * Usage:
 * ```php
 * $loader = new BatchLoader();
 *
 * // Queue IDs for batch loading
 * $loader->queue('user_meta', [1, 2, 3]);
 * $loader->queue('post_meta', [10, 20, 30]);
 *
 * // Load all queued data
 * $loader->loadAll();
 *
 * // Get loaded data
 * $user_meta = $loader->get('user_meta', 1);
 * ```
 */
final class BatchLoader {

	/**
	 * Queued IDs by type.
	 *
	 * @var array<string, array<int>>
	 */
	private array $queue = array();

	/**
	 * Loaded data cache.
	 *
	 * @var array<string, array<int, mixed>>
	 */
	private array $cache = array();

	/**
	 * Registered loaders.
	 *
	 * @var array<string, callable>
	 */
	private array $loaders = array();

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
		$this->registerDefaultLoaders();
	}

	/**
	 * Register default data loaders.
	 *
	 * @return void
	 */
	private function registerDefaultLoaders(): void {
		// User meta loader
		$this->loaders['user_meta'] = function ( array $user_ids ): array {
			global $wpdb;

			if ( empty( $user_ids ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_key, meta_value
					FROM {$wpdb->usermeta}
					WHERE user_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					...$user_ids
				),
				ARRAY_A
			);

			$grouped = array();
			foreach ( $results as $row ) {
				$uid = (int) $row['user_id'];
				if ( ! isset( $grouped[ $uid ] ) ) {
					$grouped[ $uid ] = array();
				}
				$grouped[ $uid ][ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
			}

			return $grouped;
		};

		// Post meta loader
		$this->loaders['post_meta'] = function ( array $post_ids ): array {
			global $wpdb;

			if ( empty( $post_ids ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_key, meta_value
					FROM {$wpdb->postmeta}
					WHERE post_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					...$post_ids
				),
				ARRAY_A
			);

			$grouped = array();
			foreach ( $results as $row ) {
				$pid = (int) $row['post_id'];
				if ( ! isset( $grouped[ $pid ] ) ) {
					$grouped[ $pid ] = array();
				}
				$grouped[ $pid ][ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
			}

			return $grouped;
		};

		// User data loader
		$this->loaders['users'] = function ( array $user_ids ): array {
			global $wpdb;

			if ( empty( $user_ids ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, user_login, user_nicename, user_email, display_name
					FROM {$wpdb->users}
					WHERE ID IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					...$user_ids
				),
				ARRAY_A
			);

			$indexed = array();
			foreach ( $results as $row ) {
				$indexed[ (int) $row['ID'] ] = $row;
			}

			return $indexed;
		};

		// Posts loader
		$this->loaders['posts'] = function ( array $post_ids ): array {
			if ( empty( $post_ids ) ) {
				return array();
			}

			// Use WP_Query for proper caching
			$query = new \WP_Query(
				array(
					'post__in'               => $post_ids,
					'post_type'              => 'any',
					'posts_per_page'         => count( $post_ids ),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);

			$indexed = array();
			foreach ( $query->posts as $post ) {
				$indexed[ $post->ID ] = $post;
			}

			return $indexed;
		};

		// Terms loader
		$this->loaders['terms'] = function ( array $term_ids ): array {
			global $wpdb;

			if ( empty( $term_ids ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.term_id, t.name, t.slug, tt.taxonomy
					FROM {$wpdb->terms} t
					INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
					WHERE t.term_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					...$term_ids
				),
				ARRAY_A
			);

			$indexed = array();
			foreach ( $results as $row ) {
				$indexed[ (int) $row['term_id'] ] = $row;
			}

			return $indexed;
		};

		// Event favorites loader
		$this->loaders['event_favorites'] = function ( array $event_ids ): array {
			global $wpdb;

			if ( empty( $event_ids ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value
					FROM {$wpdb->postmeta}
					WHERE meta_key = '_favorite_count'
					AND post_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					...$event_ids
				),
				ARRAY_A
			);

			$indexed = array();
			foreach ( $results as $row ) {
				$indexed[ (int) $row['post_id'] ] = (int) $row['meta_value'];
			}

			// Fill missing with 0
			foreach ( $event_ids as $id ) {
				if ( ! isset( $indexed[ $id ] ) ) {
					$indexed[ $id ] = 0;
				}
			}

			return $indexed;
		};

		// Group members loader
		$this->loaders['group_members'] = function ( array $group_ids ): array {
			global $wpdb;

			$table = $wpdb->prefix . 'apollo_group_members';

			// Check if table exists
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);

			if ( ! $exists || empty( $group_ids ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $group_ids ), '%d' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT group_id, user_id, role
					FROM {$table}
					WHERE group_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					...$group_ids
				),
				ARRAY_A
			);

			$grouped = array();
			foreach ( $results as $row ) {
				$gid = (int) $row['group_id'];
				if ( ! isset( $grouped[ $gid ] ) ) {
					$grouped[ $gid ] = array();
				}
				$grouped[ $gid ][] = array(
					'user_id' => (int) $row['user_id'],
					'role'    => $row['role'],
				);
			}

			return $grouped;
		};
	}

	/**
	 * Register a custom loader.
	 *
	 * @param string   $type   Loader type name.
	 * @param callable $loader Loader callback (receives array of IDs, returns array keyed by ID).
	 * @return self
	 */
	public function registerLoader( string $type, callable $loader ): self {
		$this->loaders[ $type ] = $loader;
		return $this;
	}

	/**
	 * Queue IDs for batch loading.
	 *
	 * @param string      $type Loader type.
	 * @param array<int>|int $ids  ID or array of IDs to queue.
	 * @return self
	 */
	public function queue( string $type, array|int $ids ): self {
		if ( ! isset( $this->queue[ $type ] ) ) {
			$this->queue[ $type ] = array();
		}

		$ids = (array) $ids;

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, $this->queue[ $type ], true ) ) {
				// Check if already cached
				if ( ! isset( $this->cache[ $type ][ $id ] ) ) {
					$this->queue[ $type ][] = $id;
				}
			}
		}

		return $this;
	}

	/**
	 * Load all queued data.
	 *
	 * @return self
	 */
	public function loadAll(): self {
		foreach ( $this->queue as $type => $ids ) {
			if ( ! empty( $ids ) && isset( $this->loaders[ $type ] ) ) {
				$this->load( $type );
			}
		}

		return $this;
	}

	/**
	 * Load a specific type.
	 *
	 * @param string $type Loader type.
	 * @return self
	 */
	public function load( string $type ): self {
		if ( empty( $this->queue[ $type ] ) || ! isset( $this->loaders[ $type ] ) ) {
			return $this;
		}

		$ids = array_unique( $this->queue[ $type ] );
		$data = call_user_func( $this->loaders[ $type ], $ids );

		if ( ! isset( $this->cache[ $type ] ) ) {
			$this->cache[ $type ] = array();
		}

		// Merge with cache
		foreach ( $data as $id => $value ) {
			$this->cache[ $type ][ $id ] = $value;
		}

		// Mark missing IDs as null
		foreach ( $ids as $id ) {
			if ( ! isset( $this->cache[ $type ][ $id ] ) ) {
				$this->cache[ $type ][ $id ] = null;
			}
		}

		// Clear queue
		$this->queue[ $type ] = array();

		return $this;
	}

	/**
	 * Get loaded data for a specific ID.
	 *
	 * @param string $type Loader type.
	 * @param int    $id   Entity ID.
	 * @return mixed|null
	 */
	public function get( string $type, int $id ): mixed {
		// Auto-load if queued but not loaded
		if ( isset( $this->queue[ $type ] ) && in_array( $id, $this->queue[ $type ], true ) ) {
			$this->load( $type );
		}

		return $this->cache[ $type ][ $id ] ?? null;
	}

	/**
	 * Get all loaded data for a type.
	 *
	 * @param string $type Loader type.
	 * @return array<int, mixed>
	 */
	public function getAll( string $type ): array {
		return $this->cache[ $type ] ?? array();
	}

	/**
	 * Check if data is cached.
	 *
	 * @param string $type Loader type.
	 * @param int    $id   Entity ID.
	 * @return bool
	 */
	public function has( string $type, int $id ): bool {
		return isset( $this->cache[ $type ][ $id ] );
	}

	/**
	 * Clear cache.
	 *
	 * @param string|null $type Optional type to clear. Clears all if null.
	 * @return self
	 */
	public function clear( ?string $type = null ): self {
		if ( null === $type ) {
			$this->cache = array();
			$this->queue = array();
		} else {
			unset( $this->cache[ $type ], $this->queue[ $type ] );
		}

		return $this;
	}

	/**
	 * Prime cache with existing data.
	 *
	 * @param string             $type Loader type.
	 * @param array<int, mixed>  $data Data keyed by ID.
	 * @return self
	 */
	public function prime( string $type, array $data ): self {
		if ( ! isset( $this->cache[ $type ] ) ) {
			$this->cache[ $type ] = array();
		}

		foreach ( $data as $id => $value ) {
			$this->cache[ $type ][ (int) $id ] = $value;
		}

		return $this;
	}

	/**
	 * Get queue status for debugging.
	 *
	 * @return array<string, array{queued: int, cached: int}>
	 */
	public function getStatus(): array {
		$status = array();

		foreach ( array_keys( $this->loaders ) as $type ) {
			$status[ $type ] = array(
				'queued' => count( $this->queue[ $type ] ?? array() ),
				'cached' => count( $this->cache[ $type ] ?? array() ),
			);
		}

		return $status;
	}
}

/**
 * Helper function to get the batch loader instance.
 *
 * @return BatchLoader
 */
function apollo_batch_loader(): BatchLoader {
	return BatchLoader::getInstance();
}
