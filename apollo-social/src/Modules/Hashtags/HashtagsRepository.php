<?php
declare(strict_types=1);
namespace Apollo\Modules\Hashtags;

final class HashtagsRepository {
	private const T_HASHTAGS = 'apollo_hashtags';
	private const T_USAGE    = 'apollo_hashtag_usage';

	public static function findOrCreate( string $tag ): int {
		global $wpdb;
		$t        = $wpdb->prefix . self::T_HASHTAGS;
		$tag      = \strtolower( \trim( \str_replace( '#', '', $tag ) ) );
		$slug     = sanitize_title( $tag );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE slug=%s", $slug ) );
		if ( $existing ) {
			return (int) $existing;
		}
		$wpdb->insert(
			$t,
			array(
				'name'       => $tag,
				'slug'       => $slug,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s' )
		);
		return $wpdb->insert_id ?: 0;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_HASHTAGS;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $id ), ARRAY_A );
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_HASHTAGS;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE slug=%s", $slug ), ARRAY_A );
	}

	public static function attach( int $hashtagId, string $objectType, int $objectId, int $userId = 0 ): bool {
		global $wpdb;
		$u      = $wpdb->prefix . self::T_USAGE;
		$h      = $wpdb->prefix . self::T_HASHTAGS;
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$u} WHERE hashtag_id=%d AND object_type=%s AND object_id=%d",
				$hashtagId,
				$objectType,
				$objectId
			)
		);
		if ( $exists ) {
			return true;
		}
		$wpdb->insert(
			$u,
			array(
				'hashtag_id'  => $hashtagId,
				'object_type' => $objectType,
				'object_id'   => $objectId,
				'user_id'     => $userId,
				'created_at'  => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%d', '%d', '%s' )
		);
		$wpdb->query( $wpdb->prepare( "UPDATE {$h} SET use_count=use_count+1 WHERE id=%d", $hashtagId ) );
		return true;
	}

	public static function detach( int $hashtagId, string $objectType, int $objectId ): bool {
		global $wpdb;
		$u      = $wpdb->prefix . self::T_USAGE;
		$h      = $wpdb->prefix . self::T_HASHTAGS;
		$result = $wpdb->delete(
			$u,
			array(
				'hashtag_id'  => $hashtagId,
				'object_type' => $objectType,
				'object_id'   => $objectId,
			),
			array( '%d', '%s', '%d' )
		);
		if ( $result ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$h} SET use_count=GREATEST(use_count-1,0) WHERE id=%d", $hashtagId ) );
		}
		return (bool) $result;
	}

	public static function parseAndAttach( string $content, string $objectType, int $objectId, int $userId = 0 ): array {
		$tags     = self::extractFromContent( $content );
		$attached = array();
		foreach ( $tags as $tag ) {
			$id = self::findOrCreate( $tag );
			if ( $id ) {
				self::attach( $id, $objectType, $objectId, $userId );
				$attached[] = array(
					'id'  => $id,
					'tag' => $tag,
				);
			}
		}
		return $attached;
	}

	public static function extractFromContent( string $content ): array {
		preg_match_all( '/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u', $content, $matches );
		return array_unique( $matches[1] ?? array() );
	}

	public static function syncForObject( string $objectType, int $objectId, array $tags, int $userId = 0 ): void {
		global $wpdb;
		$u       = $wpdb->prefix . self::T_USAGE;
		$current = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT hashtag_id FROM {$u} WHERE object_type=%s AND object_id=%d",
				$objectType,
				$objectId
			)
		);
		$newIds  = array();
		foreach ( $tags as $tag ) {
			$id = self::findOrCreate( $tag );
			if ( $id ) {
				$newIds[] = $id;
			}
		}
		$toRemove = array_diff( $current, $newIds );
		foreach ( $toRemove as $id ) {
			self::detach( (int) $id, $objectType, $objectId );
		}
		$toAdd = array_diff( $newIds, $current );
		foreach ( $toAdd as $id ) {
			self::attach( $id, $objectType, $objectId, $userId );
		}
	}

	public static function getForObject( string $objectType, int $objectId ): array {
		global $wpdb;
		$u = $wpdb->prefix . self::T_USAGE;
		$h = $wpdb->prefix . self::T_HASHTAGS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT h.* FROM {$h} h JOIN {$u} u ON h.id=u.hashtag_id WHERE u.object_type=%s AND u.object_id=%d",
				$objectType,
				$objectId
			),
			ARRAY_A
		) ?? array();
	}

	public static function getTrending( int $hours = 24, int $limit = 10 ): array {
		global $wpdb;
		$u     = $wpdb->prefix . self::T_USAGE;
		$h     = $wpdb->prefix . self::T_HASHTAGS;
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$hours} hours" ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT h.*,COUNT(u.id) as recent_count FROM {$h} h JOIN {$u} u ON h.id=u.hashtag_id WHERE u.created_at>=%s GROUP BY h.id ORDER BY recent_count DESC LIMIT %d",
				$since,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getPopular( int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_HASHTAGS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE use_count>0 ORDER BY use_count DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function search( string $query, int $limit = 10 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_HASHTAGS;
		$q = $wpdb->esc_like( \str_replace( '#', '', $query ) ) . '%';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE name LIKE %s OR slug LIKE %s ORDER BY use_count DESC LIMIT %d",
				$q,
				$q,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getObjectsByHashtag( int $hashtagId, string $objectType = '', int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$u = $wpdb->prefix . self::T_USAGE;
		if ( $objectType ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT object_type,object_id,user_id,created_at FROM {$u} WHERE hashtag_id=%d AND object_type=%s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$hashtagId,
					$objectType,
					$limit,
					$offset
				),
				ARRAY_A
			) ?? array();
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT object_type,object_id,user_id,created_at FROM {$u} WHERE hashtag_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$hashtagId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function follow( int $userId, int $hashtagId ): bool {
		global $wpdb;
		$t      = $wpdb->prefix . 'apollo_hashtag_follows';
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND hashtag_id=%d", $userId, $hashtagId ) );
		if ( $exists ) {
			return true;
		}
		return (bool) $wpdb->insert(
			$t,
			array(
				'user_id'    => $userId,
				'hashtag_id' => $hashtagId,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%s' )
		);
	}

	public static function unfollow( int $userId, int $hashtagId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_hashtag_follows';
		return (bool) $wpdb->delete(
			$t,
			array(
				'user_id'    => $userId,
				'hashtag_id' => $hashtagId,
			),
			array( '%d', '%d' )
		);
	}

	public static function isFollowing( int $userId, int $hashtagId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_hashtag_follows';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND hashtag_id=%d", $userId, $hashtagId ) );
	}

	public static function getFollowed( int $userId ): array {
		global $wpdb;
		$f = $wpdb->prefix . 'apollo_hashtag_follows';
		$h = $wpdb->prefix . self::T_HASHTAGS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT h.* FROM {$h} h JOIN {$f} f ON h.id=f.hashtag_id WHERE f.user_id=%d ORDER BY h.name ASC",
				$userId
			),
			ARRAY_A
		) ?? array();
	}

	public static function cleanupUnused( int $minAge = 30 ): int {
		global $wpdb;
		$t         = $wpdb->prefix . self::T_HASHTAGS;
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$minAge} days" ) );
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$t} WHERE use_count=0 AND created_at<%s", $threshold ) );
	}
}
