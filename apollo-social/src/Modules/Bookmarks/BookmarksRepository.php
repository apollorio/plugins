<?php
declare(strict_types=1);
namespace Apollo\Modules\Bookmarks;

final class BookmarksRepository {
	private const TABLE       = 'apollo_bookmarks';
	private const COLLECTIONS = 'apollo_bookmark_collections';

	public static function add( int $userId, string $objectType, int $objectId, int $collectionId = 0 ): int|false {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( self::exists( $userId, $objectType, $objectId ) ) {
			return false;
		}
		$wpdb->insert(
			$t,
			array(
				'user_id'       => $userId,
				'object_type'   => $objectType,
				'object_id'     => $objectId,
				'collection_id' => $collectionId,
				'created_at'    => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%d', '%d', '%s' )
		);
		return $wpdb->insert_id ?: false;
	}

	public static function remove( int $userId, string $objectType, int $objectId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->delete(
			$t,
			array(
				'user_id'     => $userId,
				'object_type' => $objectType,
				'object_id'   => $objectId,
			),
			array( '%d', '%s', '%d' )
		);
	}

	public static function exists( int $userId, string $objectType, int $objectId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$t} WHERE user_id=%d AND object_type=%s AND object_id=%d",
				$userId,
				$objectType,
				$objectId
			)
		);
	}

	public static function toggle( int $userId, string $objectType, int $objectId ): array {
		if ( self::exists( $userId, $objectType, $objectId ) ) {
			self::remove( $userId, $objectType, $objectId );
			return array( 'bookmarked' => false );
		}
		self::add( $userId, $objectType, $objectId );
		return array( 'bookmarked' => true );
	}

	public static function getUserBookmarks( int $userId, string $objectType = '', int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( $objectType ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$t} WHERE user_id=%d AND object_type=%s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$userId,
					$objectType,
					$limit,
					$offset
				),
				ARRAY_A
			) ?? array();
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE user_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$userId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getCount( int $userId, string $objectType = '' ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( $objectType ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND object_type=%s",
					$userId,
					$objectType
				)
			);
		}
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE user_id=%d", $userId ) );
	}

	public static function createCollection( int $userId, string $name, string $description = '', bool $isPrivate = true ): int|false {
		global $wpdb;
		$t    = $wpdb->prefix . self::COLLECTIONS;
		$slug = sanitize_title( $name ) . '-' . wp_generate_password( 4, false );
		$wpdb->insert(
			$t,
			array(
				'user_id'     => $userId,
				'name'        => $name,
				'slug'        => $slug,
				'description' => $description,
				'is_private'  => $isPrivate ? 1 : 0,
				'created_at'  => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);
		return $wpdb->insert_id ?: false;
	}

	public static function updateCollection( int $collectionId, int $userId, array $data ): bool {
		global $wpdb;
		$t      = $wpdb->prefix . self::COLLECTIONS;
		$update = array();
		$format = array();
		if ( isset( $data['name'] ) ) {
			$update['name'] = $data['name'];
			$format[]       = '%s';}
		if ( isset( $data['description'] ) ) {
			$update['description'] = $data['description'];
			$format[]              = '%s';}
		if ( isset( $data['is_private'] ) ) {
			$update['is_private'] = $data['is_private'] ? 1 : 0;
			$format[]             = '%d';}
		if ( empty( $update ) ) {
			return false;
		}
		return (bool) $wpdb->update(
			$t,
			$update,
			array(
				'id'      => $collectionId,
				'user_id' => $userId,
			),
			$format,
			array( '%d', '%d' )
		);
	}

	public static function deleteCollection( int $collectionId, int $userId ): bool {
		global $wpdb;
		$c = $wpdb->prefix . self::COLLECTIONS;
		$b = $wpdb->prefix . self::TABLE;
		$wpdb->update(
			$b,
			array( 'collection_id' => 0 ),
			array(
				'collection_id' => $collectionId,
				'user_id'       => $userId,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
		return (bool) $wpdb->delete(
			$c,
			array(
				'id'      => $collectionId,
				'user_id' => $userId,
			),
			array( '%d', '%d' )
		);
	}

	public static function getCollections( int $userId ): array {
		global $wpdb;
		$c = $wpdb->prefix . self::COLLECTIONS;
		$b = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,(SELECT COUNT(*) FROM {$b} WHERE collection_id=c.id) as item_count FROM {$c} c WHERE c.user_id=%d ORDER BY c.name ASC",
				$userId
			),
			ARRAY_A
		) ?? array();
	}

	public static function getCollectionItems( int $collectionId, int $userId, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE collection_id=%d AND user_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$collectionId,
				$userId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function moveToCollection( int $bookmarkId, int $userId, int $collectionId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->update(
			$t,
			array( 'collection_id' => $collectionId ),
			array(
				'id'      => $bookmarkId,
				'user_id' => $userId,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	public static function getObjectBookmarkCount( string $objectType, int $objectId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$t} WHERE object_type=%s AND object_id=%d",
				$objectType,
				$objectId
			)
		);
	}

	public static function getRecentBookmarkers( string $objectType, int $objectId, int $limit = 5 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$t} WHERE object_type=%s AND object_id=%d ORDER BY created_at DESC LIMIT %d",
				$objectType,
				$objectId,
				$limit
			)
		);
	}

	public static function clearUserBookmarks( int $userId, string $objectType = '' ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( $objectType ) {
			return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$t} WHERE user_id=%d AND object_type=%s", $userId, $objectType ) );
		}
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$t} WHERE user_id=%d", $userId ) );
	}
}
