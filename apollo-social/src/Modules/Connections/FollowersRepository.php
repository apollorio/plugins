<?php
declare(strict_types=1);
namespace Apollo\Modules\Connections;

final class FollowersRepository {
	private const TABLE='apollo_followers';

	public static function follow(int $userId, int $followerId): bool {
		if($userId===$followerId){return false;}
		if(!self::canFollow($userId,$followerId)){return false;}
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE user_id=%d AND follower_id=%d",$userId,$followerId));
		if($exists){return true;}
		$r=$wpdb->insert($t,['user_id'=>$userId,'follower_id'=>$followerId],['%d','%d']);
		if($r){
			self::updateCounts($userId,$followerId);
			do_action('apollo_user_followed',$userId,$followerId);
			do_action('apollo_award_points',$followerId,5,'follow_user','Seguiu um usuário');
		}
		return (bool)$r;
	}

	public static function unfollow(int $userId, int $followerId): bool {
		global $wpdb;
		$r=$wpdb->delete($wpdb->prefix.self::TABLE,['user_id'=>$userId,'follower_id'=>$followerId],['%d','%d']);
		if($r){
			self::updateCounts($userId,$followerId);
			do_action('apollo_user_unfollowed',$userId,$followerId);
		}
		return (bool)$r;
	}

	public static function isFollowing(int $userId, int $followerId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE user_id=%d AND follower_id=%d",$userId,$followerId));
	}

	public static function getFollowers(int $userId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT f.*,u.display_name,u.user_login,u.user_email FROM {$t} f JOIN {$wpdb->users} u ON f.follower_id=u.ID WHERE f.user_id=%d ORDER BY f.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getFollowing(int $followerId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT f.*,u.display_name,u.user_login,u.user_email FROM {$t} f JOIN {$wpdb->users} u ON f.user_id=u.ID WHERE f.follower_id=%d ORDER BY f.created_at DESC LIMIT %d OFFSET %d",
			$followerId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getFollowerIds(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT follower_id FROM {$t} WHERE user_id=%d",$userId)));
	}

	public static function getFollowingIds(int $followerId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$t} WHERE follower_id=%d",$followerId)));
	}

	public static function getFollowerCount(int $userId): int {
		$count=get_user_meta($userId,'apollo_followers_count',true);
		if($count===''){$count=self::recalculateFollowerCount($userId);}
		return (int)$count;
	}

	public static function getFollowingCount(int $userId): int {
		$count=get_user_meta($userId,'apollo_following_count',true);
		if($count===''){$count=self::recalculateFollowingCount($userId);}
		return (int)$count;
	}

	private static function recalculateFollowerCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d",$userId));
		update_user_meta($userId,'apollo_followers_count',$count);
		return $count;
	}

	private static function recalculateFollowingCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE follower_id=%d",$userId));
		update_user_meta($userId,'apollo_following_count',$count);
		return $count;
	}

	private static function updateCounts(int $userId, int $followerId): void {
		self::recalculateFollowerCount($userId);
		self::recalculateFollowingCount($followerId);
	}

	public static function getMutual(int $userId1, int $userId2): bool {
		return self::isFollowing($userId1,$userId2)&&self::isFollowing($userId2,$userId1);
	}

	public static function getMutualFollowers(int $userId1, int $userId2, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT f1.follower_id,u.display_name FROM {$t} f1 JOIN {$t} f2 ON f1.follower_id=f2.follower_id JOIN {$wpdb->users} u ON f1.follower_id=u.ID WHERE f1.user_id=%d AND f2.user_id=%d AND f1.follower_id!=%d AND f1.follower_id!=%d LIMIT %d",
			$userId1,$userId2,$userId1,$userId2,$limit
		),ARRAY_A)??[];
	}

	public static function getSuggestedToFollow(int $userId, int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$following=self::getFollowingIds($userId);
		$following[]=$userId;
		$in=\implode(',',array_map('intval',$following));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT f.user_id,COUNT(*) as mutual_count,u.display_name FROM {$t} f JOIN {$wpdb->users} u ON f.user_id=u.ID WHERE f.follower_id IN ({$in}) AND f.user_id NOT IN ({$in}) GROUP BY f.user_id ORDER BY mutual_count DESC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getTopFollowed(int $limit=10): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT u.ID,u.display_name,CAST(m.meta_value AS UNSIGNED) as followers FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.ID=m.user_id AND m.meta_key='apollo_followers_count' ORDER BY followers DESC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	private static function canFollow(int $userId, int $followerId): bool {
		global $wpdb;
		$block=$wpdb->prefix.'apollo_block_list';
		$blocked=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$block} WHERE (user_id=%d AND blocked_id=%d) OR (user_id=%d AND blocked_id=%d) LIMIT 1",
			$userId,$followerId,$followerId,$userId
		));
		return !$blocked;
	}

	public static function removeAllFollowers(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE user_id=%d",$userId));
		update_user_meta($userId,'apollo_followers_count',0);
		return $count;
	}

	public static function removeAllFollowing(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE follower_id=%d",$userId));
		update_user_meta($userId,'apollo_following_count',0);
		return $count;
	}

	public static function getNewFollowers(int $userId, int $since, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$date=gmdate('Y-m-d H:i:s',$since);
		return $wpdb->get_results($wpdb->prepare(
			"SELECT f.*,u.display_name FROM {$t} f JOIN {$wpdb->users} u ON f.follower_id=u.ID WHERE f.user_id=%d AND f.created_at>=%s ORDER BY f.created_at DESC LIMIT %d",
			$userId,$date,$limit
		),ARRAY_A)??[];
	}

	public static function getFollowerGrowth(int $userId, int $days=30): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT DATE(created_at) as date,COUNT(*) as count FROM {$t} WHERE user_id=%d AND created_at>=DATE_SUB(NOW(),INTERVAL %d DAY) GROUP BY DATE(created_at) ORDER BY date ASC",
			$userId,$days
		),ARRAY_A)??[];
	}

	public static function getFeedUserIds(int $userId): array {
		$following=self::getFollowingIds($userId);
		$following[]=$userId;
		return $following;
	}
}
