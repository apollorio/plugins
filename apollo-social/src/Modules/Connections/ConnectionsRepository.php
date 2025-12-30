<?php
declare(strict_types=1);
namespace Apollo\Modules\Connections;

final class ConnectionsRepository {
	private const TABLE='apollo_connections';
	private const CLOSE='apollo_close_friends';
	private const MAX_CLOSE_FRIENDS=10;

	public static function sendRequest(int $userId,int $friendId): bool {
		if($userId===$friendId)return false;
		global $wpdb;
		$existing=$wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
			$userId,$friendId,$friendId,$userId
		),ARRAY_A);
		if($existing)return false;
		return $wpdb->insert($wpdb->prefix.self::TABLE,[
			'user_id'=>$userId,'friend_id'=>$friendId,'status'=>'pending','initiated_by'=>$userId
		])!==false;
	}

	public static function acceptRequest(int $userId,int $friendId): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,
			['status'=>'accepted','accepted_at'=>current_time('mysql')],
			['user_id'=>$friendId,'friend_id'=>$userId,'status'=>'pending']
		)!==false;
	}

	public static function rejectRequest(int $userId,int $friendId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['user_id'=>$friendId,'friend_id'=>$userId,'status'=>'pending'])!==false;
	}

	public static function unfriend(int $userId,int $friendId): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::CLOSE,['user_id'=>$userId,'friend_id'=>$friendId]);
		$wpdb->delete($wpdb->prefix.self::CLOSE,['user_id'=>$friendId,'friend_id'=>$userId]);
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}".self::TABLE." WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
			$userId,$friendId,$friendId,$userId
		));
		return true;
	}

	public static function block(int $userId,int $friendId): bool {
		global $wpdb;
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}".self::TABLE." WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
			$userId,$friendId,$friendId,$userId
		));
		return $wpdb->insert($wpdb->prefix.self::TABLE,[
			'user_id'=>$userId,'friend_id'=>$friendId,'status'=>'blocked','initiated_by'=>$userId
		])!==false;
	}

	public static function unblock(int $userId,int $friendId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['user_id'=>$userId,'friend_id'=>$friendId,'status'=>'blocked'])!==false;
	}

	public static function getStatus(int $userId,int $friendId): ?string {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT status FROM {$wpdb->prefix}".self::TABLE." WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
			$userId,$friendId,$friendId,$userId
		));
	}

	public static function areFriends(int $userId,int $friendId): bool {
		return self::getStatus($userId,$friendId)==='accepted';
	}

	public static function getFriends(int $userId,int $limit=50,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_col($wpdb->prepare(
			"SELECT CASE WHEN user_id=%d THEN friend_id ELSE user_id END as fid
			 FROM {$wpdb->prefix}".self::TABLE."
			 WHERE (user_id=%d OR friend_id=%d) AND status='accepted'
			 LIMIT %d OFFSET %d",
			$userId,$userId,$userId,$limit,$offset
		))?:[];
	}

	public static function countFriends(int $userId): int {
		global $wpdb;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." WHERE (user_id=%d OR friend_id=%d) AND status='accepted'",
			$userId,$userId
		));
	}

	public static function getPendingRequests(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT c.*,u.display_name,u.user_email FROM {$wpdb->prefix}".self::TABLE." c
			 LEFT JOIN {$wpdb->users} u ON u.ID=c.user_id
			 WHERE c.friend_id=%d AND c.status='pending' ORDER BY c.created_at DESC",
			$userId
		),ARRAY_A)?:[];
	}

	public static function getSentRequests(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT c.*,u.display_name,u.user_email FROM {$wpdb->prefix}".self::TABLE." c
			 LEFT JOIN {$wpdb->users} u ON u.ID=c.friend_id
			 WHERE c.user_id=%d AND c.status='pending' ORDER BY c.created_at DESC",
			$userId
		),ARRAY_A)?:[];
	}

	public static function getBlockedUsers(int $userId): array {
		global $wpdb;
		return $wpdb->get_col($wpdb->prepare(
			"SELECT friend_id FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d AND status='blocked'",
			$userId
		))?:[];
	}

	public static function addCloseFriend(int $userId,int $friendId): bool {
		if(!self::areFriends($userId,$friendId))return false;
		global $wpdb;
		$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}".self::CLOSE." WHERE user_id=%d",$userId));
		if($count>=self::MAX_CLOSE_FRIENDS)return false;
		return $wpdb->replace($wpdb->prefix.self::CLOSE,['user_id'=>$userId,'friend_id'=>$friendId])!==false;
	}

	public static function removeCloseFriend(int $userId,int $friendId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::CLOSE,['user_id'=>$userId,'friend_id'=>$friendId])!==false;
	}

	public static function getCloseFriends(int $userId): array {
		global $wpdb;
		return $wpdb->get_col($wpdb->prepare("SELECT friend_id FROM {$wpdb->prefix}".self::CLOSE." WHERE user_id=%d",$userId))?:[];
	}

	public static function isCloseFriend(int $userId,int $friendId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$wpdb->prefix}".self::CLOSE." WHERE user_id=%d AND friend_id=%d",$userId,$friendId));
	}

	public static function getBubble(int $userId): array {
		return self::getCloseFriends($userId);
	}

	public static function removeAllConnections(int $userId): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::CLOSE,['user_id'=>$userId]);
		$wpdb->delete($wpdb->prefix.self::CLOSE,['friend_id'=>$userId]);
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d OR friend_id=%d",
			$userId,$userId
		));
		return true;
	}
}
