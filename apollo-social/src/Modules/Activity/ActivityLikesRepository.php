<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

final class ActivityLikesRepository {
	private const TABLE     = 'apollo_activity_likes';
	private const REACTIONS = array( 'like', 'love', 'haha', 'wow', 'sad', 'angry' );

	public static function add( int $activityId, int $userId, string $reaction = 'like' ): bool {
		if ( ! in_array( $reaction, self::REACTIONS, true ) ) {
			$reaction = 'like';}
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$exists = $wpdb->get_row( $wpdb->prepare( "SELECT id,reaction_type FROM {$t} WHERE activity_id=%d AND user_id=%d", $activityId, $userId ), ARRAY_A );
		if ( $exists ) {
			if ( $exists['reaction_type'] === $reaction ) {
				return true;}
			return (bool) $wpdb->update( $t, array( 'reaction_type' => $reaction ), array( 'id' => $exists['id'] ) );
		}
		$r = $wpdb->insert(
			$t,
			array(
				'activity_id'   => $activityId,
				'user_id'       => $userId,
				'reaction_type' => $reaction,
			),
			array( '%d', '%d', '%s' )
		);
		if ( $r ) {
			self::updateActivityCount( $activityId );
			do_action( 'apollo_activity_liked', $activityId, $userId, $reaction );
			do_action( 'apollo_award_points', $userId, 1, 'activity_like', 'Curtiu uma atividade' );
		}
		return (bool) $r;
	}

	public static function remove( int $activityId, int $userId ): bool {
		global $wpdb;
		$r = $wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array(
				'activity_id' => $activityId,
				'user_id'     => $userId,
			),
			array( '%d', '%d' )
		);
		if ( $r ) {
			self::updateActivityCount( $activityId );}
		return (bool) $r;
	}

	public static function toggle( int $activityId, int $userId, string $reaction = 'like' ): array {
		if ( self::hasLiked( $activityId, $userId ) ) {
			self::remove( $activityId, $userId );
			return array(
				'action' => 'removed',
				'count'  => self::getCount( $activityId ),
			);
		}
		self::add( $activityId, $userId, $reaction );
		return array(
			'action' => 'added',
			'count'  => self::getCount( $activityId ),
		);
	}

	public static function hasLiked( int $activityId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE activity_id=%d AND user_id=%d", $activityId, $userId ) );
	}

	public static function getUserReaction( int $activityId, int $userId ): ?string {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_var( $wpdb->prepare( "SELECT reaction_type FROM {$t} WHERE activity_id=%d AND user_id=%d", $activityId, $userId ) );
	}

	public static function getCount( int $activityId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE activity_id=%d", $activityId ) );
	}

	public static function getCountByReaction( int $activityId ): array {
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT reaction_type,COUNT(*) as count FROM {$t} WHERE activity_id=%d GROUP BY reaction_type",
				$activityId
			),
			ARRAY_A
		) ?? array();
		$counts = array_fill_keys( self::REACTIONS, 0 );
		foreach ( $rows as $r ) {
			$counts[ $r['reaction_type'] ] = (int) $r['count'];}
		return $counts;
	}

	public static function getUsers( int $activityId, int $limit = 50, ?string $reaction = null ): array {
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$where  = 'l.activity_id=%d';
		$params = array( $activityId );
		if ( $reaction ) {
			$where   .= ' AND l.reaction_type=%s';
			$params[] = $reaction;}
		$params[] = $limit;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*,u.display_name,u.user_login FROM {$t} l JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE {$where} ORDER BY l.created_at DESC LIMIT %d",
				...$params
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUserLikes( int $userId, int $limit = 50 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*,a.content,a.user_id as author_id FROM {$t} l JOIN {$wpdb->prefix}apollo_activity a ON l.activity_id=a.id WHERE l.user_id=%d ORDER BY l.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getRecentLikers( int $activityId, int $limit = 5 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID,u.display_name FROM {$t} l JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE l.activity_id=%d ORDER BY l.created_at DESC LIMIT %d",
				$activityId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getMutualLikes( int $userId1, int $userId2, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l1.activity_id FROM {$t} l1 JOIN {$t} l2 ON l1.activity_id=l2.activity_id WHERE l1.user_id=%d AND l2.user_id=%d LIMIT %d",
				$userId1,
				$userId2,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getMostLiked( int $limit = 10, int $days = 7 ): array {
		global $wpdb;
		$t     = $wpdb->prefix . self::TABLE;
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT activity_id,COUNT(*) as like_count FROM {$t} WHERE created_at>=%s GROUP BY activity_id ORDER BY like_count DESC LIMIT %d",
				$since,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getAvailableReactions(): array {
		return array_map(
			fn( $r )=>array(
				'type'  => $r,
				'emoji' => self::getEmoji( $r ),
			),
			self::REACTIONS
		);
	}

	private static function getEmoji( string $reaction ): string {
		return match ( $reaction ) {
			'like'=>'👍', 'love'=>'❤️', 'haha'=>'😂', 'wow'=>'😮', 'sad'=>'😢', 'angry'=>'😠', default=>'👍'
		};
	}

	private static function updateActivityCount( int $activityId ): void {
		global $wpdb;
		$count = self::getCount( $activityId );
		$wpdb->update( $wpdb->prefix . 'apollo_activity', array( 'like_count' => $count ), array( 'id' => $activityId ) );
	}

	public static function deleteForActivity( int $activityId ): int {
		global $wpdb;
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}" . self::TABLE . ' WHERE activity_id=%d', $activityId ) );
	}

	public static function getLikeStats( int $userId ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return array(
			'given'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE user_id=%d", $userId ) ),
			'received'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} l JOIN {$wpdb->prefix}apollo_activity a ON l.activity_id=a.id WHERE a.user_id=%d", $userId ) ),
			'by_reaction' => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT reaction_type,COUNT(*) as count FROM {$t} WHERE user_id=%d GROUP BY reaction_type",
					$userId
				),
				ARRAY_A
			) ?? array(),
		);
	}
}
