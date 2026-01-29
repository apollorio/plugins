<?php
declare(strict_types=1);
namespace Apollo\Modules\Privacy;

final class BlockListRepository {
	private const TABLE = 'apollo_block_list';

	public static function block( int $userId, int $blockedId, string $reason = '' ): bool {
		if ( $userId === $blockedId ) {
			return false;}
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND blocked_id=%d", $userId, $blockedId ) );
		if ( $exists ) {
			return true;}
		$r = $wpdb->insert(
			$t,
			array(
				'user_id'    => $userId,
				'blocked_id' => $blockedId,
				'reason'     => $reason,
			),
			array( '%d', '%d', '%s' )
		);
		if ( $r ) {
			self::cleanupAfterBlock( $userId, $blockedId );
			do_action( 'apollo_user_blocked', $userId, $blockedId );
		}
		return (bool) $r;
	}

	public static function unblock( int $userId, int $blockedId ): bool {
		global $wpdb;
		$r = $wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array(
				'user_id'    => $userId,
				'blocked_id' => $blockedId,
			),
			array( '%d', '%d' )
		);
		if ( $r ) {
			do_action( 'apollo_user_unblocked', $userId, $blockedId );}
		return (bool) $r;
	}

	public static function isBlocked( int $userId, int $targetId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND blocked_id=%d", $userId, $targetId ) );
	}

	public static function isBlockedBy( int $userId, int $byUserId ): bool {
		return self::isBlocked( $byUserId, $userId );
	}

	public static function hasBlockRelation( int $userId1, int $userId2 ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$t} WHERE (user_id=%d AND blocked_id=%d) OR (user_id=%d AND blocked_id=%d) LIMIT 1",
				$userId1,
				$userId2,
				$userId2,
				$userId1
			)
		);
	}

	public static function getBlockedUsers( int $userId, int $limit = 100 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*,u.display_name,u.user_login FROM {$t} b JOIN {$wpdb->users} u ON b.blocked_id=u.ID WHERE b.user_id=%d ORDER BY b.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getBlockedByUsers( int $userId, int $limit = 100 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*,u.display_name FROM {$t} b JOIN {$wpdb->users} u ON b.user_id=u.ID WHERE b.blocked_id=%d ORDER BY b.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getBlockedIds( int $userId ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT blocked_id FROM {$t} WHERE user_id=%d", $userId ) ) );
	}

	public static function getBlockedByIds( int $userId ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$t} WHERE blocked_id=%d", $userId ) ) );
	}

	public static function getAllBlockRelations( int $userId ): array {
		$blocked   = self::getBlockedIds( $userId );
		$blockedBy = self::getBlockedByIds( $userId );
		return array_unique( array_merge( $blocked, $blockedBy ) );
	}

	public static function getBlockCount( int $userId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE user_id=%d", $userId ) );
	}

	public static function getBlockedByCount( int $userId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE blocked_id=%d", $userId ) );
	}

	private static function cleanupAfterBlock( int $userId, int $blockedId ): void {
		global $wpdb;
		$conn = $wpdb->prefix . 'apollo_connections';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$conn} WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
				$userId,
				$blockedId,
				$blockedId,
				$userId
			)
		);
		$close = $wpdb->prefix . 'apollo_close_friends';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$close} WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
				$userId,
				$blockedId,
				$blockedId,
				$userId
			)
		);
		$followers = $wpdb->prefix . 'apollo_followers';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$followers} WHERE (user_id=%d AND follower_id=%d) OR (user_id=%d AND follower_id=%d)",
				$userId,
				$blockedId,
				$blockedId,
				$userId
			)
		);
		$parts   = $wpdb->prefix . 'apollo_message_participants';
		$threads = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT thread_id FROM {$parts} WHERE user_id=%d", $userId ) );
		if ( $threads ) {
			$in = \implode( ',', array_map( 'intval', $threads ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$parts} SET is_deleted=1 WHERE user_id=%d AND thread_id IN (SELECT thread_id FROM {$parts} WHERE user_id=%d AND thread_id IN ({$in}))",
					$userId,
					$blockedId
				)
			);
		}
		do_action( 'apollo_cleanup_after_block', $userId, $blockedId );
	}

	public static function filterExcludeBlocked( int $userId, array $userIds ): array {
		if ( empty( $userIds ) ) {
			return array();}
		$blocked = self::getAllBlockRelations( $userId );
		return array_values( array_diff( $userIds, $blocked ) );
	}

	public static function canInteract( int $userId, int $targetId ): bool {
		if ( $userId === $targetId ) {
			return true;}
		return ! self::hasBlockRelation( $userId, $targetId );
	}

	public static function canMessage( int $userId, int $targetId ): bool {
		return self::canInteract( $userId, $targetId );
	}

	public static function canViewProfile( int $userId, int $targetId ): bool {
		if ( self::isBlocked( $targetId, $userId ) ) {
			return false;}
		return true;
	}

	public static function getMutualBlocks(): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			"SELECT a.user_id as user1,a.blocked_id as user2 FROM {$t} a JOIN {$t} b ON a.user_id=b.blocked_id AND a.blocked_id=b.user_id WHERE a.user_id<a.blocked_id",
			ARRAY_A
		) ?? array();
	}

	public static function getUsersWhoBlockedMost( int $limit = 10 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id,COUNT(*) as block_count FROM {$t} GROUP BY user_id ORDER BY block_count DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getMostBlockedUsers( int $limit = 10 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT blocked_id,COUNT(*) as blocked_count FROM {$t} GROUP BY blocked_id ORDER BY blocked_count DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function reportBlock( int $userId, int $blockedId, string $reason, array $evidence = array() ): void {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_reports';
		$wpdb->insert(
			$t,
			array(
				'reporter_id'      => $userId,
				'reported_user_id' => $blockedId,
				'content_type'     => 'user',
				'content_id'       => $blockedId,
				'reason'           => 'harassment',
				'description'      => $reason,
				'evidence'         => json_encode( $evidence ),
				'status'           => 'pending',
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}
}
