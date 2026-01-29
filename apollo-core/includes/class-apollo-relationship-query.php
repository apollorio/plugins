<?php
/**
 * Apollo Relationship Query Builder
 *
 * Provides fluent API for querying and managing relationships between posts.
 * Handles all storage types and bidirectional synchronization.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Relationship_Query
 *
 * Query and manage relationships between posts.
 */
class Apollo_Relationship_Query {

	/**
	 * Get related items
	 *
	 * @param int    $post_id      Post ID or User ID.
	 * @param string $relationship Relationship name.
	 * @param array  $args         Additional query args.
	 * @return array Array of related post/user IDs or WP_Post/WP_User objects.
	 */
	public static function get_related( int $post_id, string $relationship, array $args = array() ): array {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return array();
		}

		// Check if this is a reverse relationship.
		if ( ! empty( $definition['is_reverse'] ) ) {
			return self::get_reverse_related( $post_id, $relationship, $args );
		}

		$ids = self::get_related_ids( $post_id, $definition );

		if ( empty( $ids ) ) {
			return array();
		}

		// Return just IDs if requested.
		if ( ! empty( $args['return'] ) && $args['return'] === 'ids' ) {
			return $ids;
		}

		// Get full objects.
		return self::get_objects( $ids, $definition['to'], $args );
	}

	/**
	 * Get reverse related items (items that reference this post)
	 *
	 * @param int    $post_id      Post ID or User ID.
	 * @param string $relationship Relationship name.
	 * @param array  $args         Additional query args.
	 * @return array Array of related IDs or objects.
	 */
	public static function get_reverse_related( int $post_id, string $relationship, array $args = array() ): array {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return array();
		}

		// Get the inverse relationship.
		$inverse_name = $definition['inverse'] ?? null;
		$inverse_def  = $inverse_name ? Apollo_Relationships::get( $inverse_name ) : null;

		if ( ! $inverse_def ) {
			// No inverse defined, try direct query.
			return self::query_reverse( $post_id, $definition, $args );
		}

		return self::query_reverse( $post_id, $definition, $args );
	}

	/**
	 * Get related IDs from meta/storage
	 *
	 * @param int   $post_id    Post ID or User ID.
	 * @param array $definition Relationship definition.
	 * @return array<int> Array of related IDs.
	 */
	private static function get_related_ids( int $post_id, array $definition ): array {
		$storage  = $definition['storage'] ?? Apollo_Relationships::STORAGE_SERIALIZED_ARRAY;
		$meta_key = $definition['meta_key'] ?? '';
		$from     = $definition['from'] ?? '';

		// Handle user relationships.
		if ( $from === 'user' ) {
			$user_meta_key = $definition['user_meta'] ?? $meta_key;
			$value         = \get_user_meta( $post_id, $user_meta_key, true );
		} elseif ( $storage === 'post_author' ) {
			// Post author is the user ID.
			$post = \get_post( $post_id );
			return $post ? array( (int) $post->post_author ) : array();
		} else {
			$value = \get_post_meta( $post_id, $meta_key, true );
		}

		return self::parse_stored_ids( $value, $storage );
	}

	/**
	 * Parse stored IDs based on storage type
	 *
	 * @param mixed  $value   Stored value.
	 * @param string $storage Storage type.
	 * @return array<int> Array of IDs.
	 */
	private static function parse_stored_ids( $value, string $storage ): array {
		if ( empty( $value ) ) {
			return array();
		}

		switch ( $storage ) {
			case Apollo_Relationships::STORAGE_SINGLE_ID:
				return array( (int) $value );

			case Apollo_Relationships::STORAGE_SERIALIZED_ARRAY:
				if ( \is_string( $value ) ) {
					$value = \maybe_unserialize( $value );
				}
				return \is_array( $value ) ? \array_map( 'intval', $value ) : array();

			case Apollo_Relationships::STORAGE_JSON_ARRAY:
				if ( \is_string( $value ) ) {
					$value = \json_decode( $value, true );
				}
				return \is_array( $value ) ? \array_map( 'intval', $value ) : array();

			case Apollo_Relationships::STORAGE_CSV:
				$parts = \explode( ',', (string) $value );
				return \array_map( 'intval', \array_filter( $parts ) );

			case Apollo_Relationships::STORAGE_TAXONOMY:
				// Value is term ID(s).
				if ( \is_array( $value ) ) {
					return \array_map( 'intval', $value );
				}
				return array( (int) $value );

			default:
				return \is_array( $value ) ? \array_map( 'intval', $value ) : array();
		}
	}

	/**
	 * Query for posts that reference this post (reverse lookup)
	 *
	 * @param int   $post_id    Post ID being referenced.
	 * @param array $definition Relationship definition.
	 * @param array $args       Query args.
	 * @return array Found posts/users.
	 */
	private static function query_reverse( int $post_id, array $definition, array $args = array() ): array {
		$meta_key = $definition['meta_key'] ?? '';
		$storage  = $definition['storage'] ?? Apollo_Relationships::STORAGE_SERIALIZED_ARRAY;
		$from     = $definition['to'] ?? ''; // Swap: we're querying what points TO us.

		if ( empty( $meta_key ) ) {
			return array();
		}

		// Handle user-to-user relationships.
		if ( $from === 'user' ) {
			return self::query_reverse_users( $post_id, $meta_key, $storage, $args );
		}

		// Query posts that have this ID in their meta.
		$query_args = array(
			'post_type'      => $from,
			'post_status'    => 'publish',
			'posts_per_page' => $args['limit'] ?? 100,
			'fields'         => 'ids',
		);

		// Different query strategies based on storage.
		switch ( $storage ) {
			case Apollo_Relationships::STORAGE_SINGLE_ID:
				$query_args['meta_query'] = array(
					array(
						'key'     => $meta_key,
						'value'   => $post_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				);
				break;

			case Apollo_Relationships::STORAGE_SERIALIZED_ARRAY:
				// LIKE query for serialized arrays.
				$query_args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => $meta_key,
						'value'   => \sprintf( 's:%d:"%d"', \strlen( (string) $post_id ), $post_id ),
						'compare' => 'LIKE',
					),
					array(
						'key'     => $meta_key,
						'value'   => \sprintf( 'i:%d;', $post_id ),
						'compare' => 'LIKE',
					),
				);
				break;

			case Apollo_Relationships::STORAGE_JSON_ARRAY:
				// LIKE query for JSON.
				$query_args['meta_query'] = array(
					array(
						'key'     => $meta_key,
						'value'   => $post_id,
						'compare' => 'LIKE',
					),
				);
				break;

			case Apollo_Relationships::STORAGE_CSV:
				// REGEXP for CSV.
				$query_args['meta_query'] = array(
					array(
						'key'     => $meta_key,
						'value'   => \sprintf( '(^|,)%d(,|$)', $post_id ),
						'compare' => 'REGEXP',
					),
				);
				break;
		}

		$query = new \WP_Query( $query_args );
		$ids   = $query->posts;

		if ( ! empty( $args['return'] ) && $args['return'] === 'ids' ) {
			return $ids;
		}

		return self::get_objects( $ids, $from, $args );
	}

	/**
	 * Query reverse for user relationships
	 *
	 * @param int    $user_id  User ID.
	 * @param string $meta_key Meta key.
	 * @param string $storage  Storage type.
	 * @param array  $args     Query args.
	 * @return array Users.
	 */
	private static function query_reverse_users( int $user_id, string $meta_key, string $storage, array $args = array() ): array {
		global $wpdb;

		$like_value = '';

		switch ( $storage ) {
			case Apollo_Relationships::STORAGE_SERIALIZED_ARRAY:
				// Build LIKE patterns for serialized arrays.
				$like1 = $wpdb->esc_like( \sprintf( 's:%d:"%d"', \strlen( (string) $user_id ), $user_id ) );
				$like2 = $wpdb->esc_like( \sprintf( 'i:%d;', $user_id ) );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$user_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM {$wpdb->usermeta}
						WHERE meta_key = %s
						AND (meta_value LIKE %s OR meta_value LIKE %s)",
						$meta_key,
						'%' . $like1 . '%',
						'%' . $like2 . '%'
					)
				);
				break;

			default:
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$user_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM {$wpdb->usermeta}
						WHERE meta_key = %s
						AND meta_value LIKE %s",
						$meta_key,
						'%' . $wpdb->esc_like( (string) $user_id ) . '%'
					)
				);
		}

		$user_ids = \array_map( 'intval', $user_ids );

		if ( ! empty( $args['return'] ) && $args['return'] === 'ids' ) {
			return $user_ids;
		}

		return self::get_objects( $user_ids, 'user', $args );
	}

	/**
	 * Get post/user objects from IDs
	 *
	 * @param array  $ids       Array of IDs.
	 * @param string $type      Post type or 'user'.
	 * @param array  $args      Additional args.
	 * @return array Objects.
	 */
	private static function get_objects( array $ids, $type, array $args = array() ): array {
		if ( empty( $ids ) ) {
			return array();
		}

		// Handle array of types (polymorphic).
		if ( \is_array( $type ) ) {
			$type = $type[0]; // Use first type for now.
		}

		if ( $type === 'user' ) {
			$users = array();
			foreach ( $ids as $id ) {
				$user = \get_userdata( $id );
				if ( $user ) {
					$users[] = $user;
				}
			}
			return $users;
		}

		// Get posts.
		$query_args = array(
			'post_type'      => $type,
			'post__in'       => $ids,
			'posts_per_page' => \count( $ids ),
			'orderby'        => 'post__in',
			'post_status'    => $args['status'] ?? 'publish',
		);

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Connect two items
	 *
	 * @param int    $from_id      Source ID.
	 * @param int    $to_id        Target ID.
	 * @param string $relationship Relationship name.
	 * @return bool Success.
	 */
	public static function connect( int $from_id, int $to_id, string $relationship ): bool {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return false;
		}

		// Get current related IDs.
		$current_ids = self::get_related_ids( $from_id, $definition );

		// Add new ID if not already connected.
		if ( \in_array( $to_id, $current_ids, true ) ) {
			return true; // Already connected.
		}

		$current_ids[] = $to_id;

		// Save.
		$saved = self::save_related_ids( $from_id, $current_ids, $definition );

		if ( $saved ) {
			// Handle bidirectional sync.
			if ( ! empty( $definition['bidirectional'] ) ) {
				$inverse = $definition['inverse'] ?? null;
				if ( $inverse && ! Apollo_Relationships::is_reverse( $inverse ) ) {
					$inverse_def = Apollo_Relationships::get( $inverse );
					if ( $inverse_def ) {
						$inverse_ids = self::get_related_ids( $to_id, $inverse_def );
						if ( ! \in_array( $from_id, $inverse_ids, true ) ) {
							$inverse_ids[] = $from_id;
							self::save_related_ids( $to_id, $inverse_ids, $inverse_def );
						}
					}
				}
			}

			// Emit event.
			if ( \class_exists( Apollo_Event_Bus::class ) ) {
				Apollo_Event_Bus::emit(
					'apollo.relationship.connected',
					array(
						'from_id'      => $from_id,
						'to_id'        => $to_id,
						'relationship' => $relationship,
					)
				);
			}
		}

		return $saved;
	}

	/**
	 * Disconnect two items
	 *
	 * @param int    $from_id      Source ID.
	 * @param int    $to_id        Target ID.
	 * @param string $relationship Relationship name.
	 * @return bool Success.
	 */
	public static function disconnect( int $from_id, int $to_id, string $relationship ): bool {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return false;
		}

		// Get current related IDs.
		$current_ids = self::get_related_ids( $from_id, $definition );

		// Remove ID.
		$key = \array_search( $to_id, $current_ids, true );
		if ( false === $key ) {
			return true; // Not connected.
		}

		unset( $current_ids[ $key ] );
		$current_ids = \array_values( $current_ids );

		// Save.
		$saved = self::save_related_ids( $from_id, $current_ids, $definition );

		if ( $saved ) {
			// Handle bidirectional sync.
			if ( ! empty( $definition['bidirectional'] ) ) {
				$inverse = $definition['inverse'] ?? null;
				if ( $inverse && ! Apollo_Relationships::is_reverse( $inverse ) ) {
					$inverse_def = Apollo_Relationships::get( $inverse );
					if ( $inverse_def ) {
						$inverse_ids = self::get_related_ids( $to_id, $inverse_def );
						$inverse_key = \array_search( $from_id, $inverse_ids, true );
						if ( false !== $inverse_key ) {
							unset( $inverse_ids[ $inverse_key ] );
							self::save_related_ids( $to_id, \array_values( $inverse_ids ), $inverse_def );
						}
					}
				}
			}

			// Emit event.
			if ( \class_exists( Apollo_Event_Bus::class ) ) {
				Apollo_Event_Bus::emit(
					'apollo.relationship.disconnected',
					array(
						'from_id'      => $from_id,
						'to_id'        => $to_id,
						'relationship' => $relationship,
					)
				);
			}
		}

		return $saved;
	}

	/**
	 * Sync relationships (replace all related items)
	 *
	 * @param int    $post_id      Source ID.
	 * @param array  $related_ids  New related IDs.
	 * @param string $relationship Relationship name.
	 * @return bool Success.
	 */
	public static function sync( int $post_id, array $related_ids, string $relationship ): bool {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return false;
		}

		$related_ids = \array_map( 'intval', $related_ids );
		$related_ids = \array_filter( $related_ids );
		$related_ids = \array_unique( $related_ids );

		// Get current for comparison.
		$current_ids = self::get_related_ids( $post_id, $definition );

		// Calculate differences.
		$to_add    = \array_diff( $related_ids, $current_ids );
		$to_remove = \array_diff( $current_ids, $related_ids );

		// Save new list.
		$saved = self::save_related_ids( $post_id, $related_ids, $definition );

		if ( $saved ) {
			// Handle bidirectional sync.
			if ( ! empty( $definition['bidirectional'] ) ) {
				$inverse = $definition['inverse'] ?? null;
				if ( $inverse ) {
					// Remove from old connections.
					foreach ( $to_remove as $old_id ) {
						self::disconnect( $post_id, $old_id, $relationship );
					}
					// Add to new connections.
					foreach ( $to_add as $new_id ) {
						self::connect( $post_id, $new_id, $relationship );
					}
				}
			}

			// Emit event.
			if ( \class_exists( Apollo_Event_Bus::class ) ) {
				Apollo_Event_Bus::emit(
					'apollo.relationship.synced',
					array(
						'post_id'      => $post_id,
						'relationship' => $relationship,
						'added'        => $to_add,
						'removed'      => $to_remove,
					)
				);
			}
		}

		return $saved;
	}

	/**
	 * Save related IDs to storage
	 *
	 * @param int   $post_id    Source ID.
	 * @param array $ids        Related IDs.
	 * @param array $definition Relationship definition.
	 * @return bool Success.
	 */
	private static function save_related_ids( int $post_id, array $ids, array $definition ): bool {
		$storage  = $definition['storage'] ?? Apollo_Relationships::STORAGE_SERIALIZED_ARRAY;
		$meta_key = $definition['meta_key'] ?? '';
		$from     = $definition['from'] ?? '';

		if ( empty( $meta_key ) || $storage === 'post_author' ) {
			return false; // Cannot modify post_author this way.
		}

		// Format value based on storage type.
		$value = self::format_for_storage( $ids, $storage );

		// Save.
		if ( $from === 'user' ) {
			$user_meta_key = $definition['user_meta'] ?? $meta_key;
			\update_user_meta( $post_id, $user_meta_key, $value );
		} else {
			\update_post_meta( $post_id, $meta_key, $value );
		}

		return true;
	}

	/**
	 * Format IDs for storage
	 *
	 * @param array  $ids     IDs to store.
	 * @param string $storage Storage type.
	 * @return mixed Formatted value.
	 */
	private static function format_for_storage( array $ids, string $storage ) {
		switch ( $storage ) {
			case Apollo_Relationships::STORAGE_SINGLE_ID:
				return ! empty( $ids ) ? $ids[0] : 0;

			case Apollo_Relationships::STORAGE_SERIALIZED_ARRAY:
				return $ids; // WordPress will serialize.

			case Apollo_Relationships::STORAGE_JSON_ARRAY:
				return \wp_json_encode( $ids );

			case Apollo_Relationships::STORAGE_CSV:
				return \implode( ',', $ids );

			default:
				return $ids;
		}
	}

	/**
	 * Check if two items are connected
	 *
	 * @param int    $from_id      Source ID.
	 * @param int    $to_id        Target ID.
	 * @param string $relationship Relationship name.
	 * @return bool
	 */
	public static function is_connected( int $from_id, int $to_id, string $relationship ): bool {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return false;
		}

		$current_ids = self::get_related_ids( $from_id, $definition );
		return \in_array( $to_id, $current_ids, true );
	}

	/**
	 * Count related items
	 *
	 * @param int    $post_id      Source ID.
	 * @param string $relationship Relationship name.
	 * @return int Count.
	 */
	public static function count( int $post_id, string $relationship ): int {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return 0;
		}

		$ids = self::get_related_ids( $post_id, $definition );
		return \count( $ids );
	}

	/**
	 * Get relationship with pagination
	 *
	 * @param int    $post_id      Source ID.
	 * @param string $relationship Relationship name.
	 * @param int    $page         Page number.
	 * @param int    $per_page     Items per page.
	 * @return array Paginated result with 'items', 'total', 'page', 'pages'.
	 */
	public static function paginate( int $post_id, string $relationship, int $page = 1, int $per_page = 10 ): array {
		$definition = Apollo_Relationships::get( $relationship );

		if ( ! $definition ) {
			return array(
				'items'    => array(),
				'total'    => 0,
				'page'     => $page,
				'pages'    => 0,
				'per_page' => $per_page,
			);
		}

		$all_ids = self::get_related_ids( $post_id, $definition );
		$total   = \count( $all_ids );
		$pages   = (int) \ceil( $total / $per_page );

		$offset = ( $page - 1 ) * $per_page;
		$ids    = \array_slice( $all_ids, $offset, $per_page );

		$items = self::get_objects( $ids, $definition['to'], array() );

		return array(
			'items'    => $items,
			'total'    => $total,
			'page'     => $page,
			'pages'    => $pages,
			'per_page' => $per_page,
		);
	}
}
