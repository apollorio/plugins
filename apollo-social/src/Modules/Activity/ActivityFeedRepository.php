<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

final class ActivityFeedRepository {
	private const TABLE='apollo_activities';

	public static function getGlobalFeed(int $userId=0, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$b=$wpdb->prefix.'apollo_block_list';
		$blockedFilter=$userId?"AND a.user_id NOT IN(SELECT blocked_user_id FROM {$b} WHERE user_id={$userId})":"";
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,um.meta_value as avatar
			FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			LEFT JOIN {$wpdb->usermeta} um ON a.user_id=um.user_id AND um.meta_key='apollo_avatar'
			WHERE a.is_hidden=0 AND a.privacy='public' {$blockedFilter}
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getPersonalFeed(int $userId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$c=$wpdb->prefix.'apollo_connections';
		$g=$wpdb->prefix.'apollo_group_members';
		$b=$wpdb->prefix.'apollo_block_list';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,um.meta_value as avatar FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			LEFT JOIN {$wpdb->usermeta} um ON a.user_id=um.user_id AND um.meta_key='apollo_avatar'
			WHERE a.is_hidden=0
			AND a.user_id NOT IN(SELECT blocked_user_id FROM {$b} WHERE user_id=%d)
			AND (
				a.user_id=%d
				OR (a.privacy='public')
				OR (a.privacy='friends' AND a.user_id IN(SELECT friend_id FROM {$c} WHERE user_id=%d AND status='accepted'))
				OR (a.group_id IN(SELECT group_id FROM {$g} WHERE user_id=%d))
			)
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$userId,$userId,$userId,$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getUserFeed(int $profileId, int $viewerId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$areFriends=self::areFriends($viewerId,$profileId);
		$privacy=$viewerId===$profileId?['public','friends','private']:($areFriends?['public','friends']:['public']);
		$in=\implode("','",array_map('esc_sql',$privacy));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE a.user_id=%d AND a.is_hidden=0 AND a.privacy IN('{$in}')
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$profileId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getGroupFeed(int $groupId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,um.meta_value as avatar FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			LEFT JOIN {$wpdb->usermeta} um ON a.user_id=um.user_id AND um.meta_key='apollo_avatar'
			WHERE a.group_id=%d AND a.is_hidden=0
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$groupId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getMentionsFeed(int $userId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$m=$wpdb->prefix.'apollo_mentions';
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$m} m ON m.activity_id=a.id
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE m.user_id=%d AND a.is_hidden=0
			ORDER BY m.created_at DESC
			LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getFavoritesFeed(int $userId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$f=$wpdb->prefix.'apollo_activity_favorites';
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,f.created_at as favorited_at FROM {$t} a
			JOIN {$f} f ON f.activity_id=a.id
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE f.user_id=%d AND a.is_hidden=0
			ORDER BY f.created_at DESC
			LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getByType(string $type, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE a.type=%s AND a.is_hidden=0 AND a.privacy='public'
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$type,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function addToFavorites(int $activityId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activity_favorites';
		if(self::isFavorited($activityId,$userId)){return true;}
		return $wpdb->insert($t,['activity_id'=>$activityId,'user_id'=>$userId,'created_at'=>current_time('mysql')],['%d','%d','%s'])!==false;
	}

	public static function removeFromFavorites(int $activityId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activity_favorites';
		return $wpdb->delete($t,['activity_id'=>$activityId,'user_id'=>$userId],['%d','%d'])!==false;
	}

	public static function isFavorited(int $activityId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activity_favorites';
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE activity_id=%d AND user_id=%d",$activityId,$userId));
	}

	public static function search(string $query, int $userId=0, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$q='%'.$wpdb->esc_like($query).'%';
		$b=$wpdb->prefix.'apollo_block_list';
		$blockedFilter=$userId?"AND a.user_id NOT IN(SELECT blocked_user_id FROM {$b} WHERE user_id={$userId})":"";
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE a.content LIKE %s AND a.is_hidden=0 AND a.privacy='public' {$blockedFilter}
			ORDER BY a.created_at DESC
			LIMIT %d",
			$q,$limit
		),ARRAY_A)??[];
	}

	public static function getHashtagFeed(string $hashtag, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$h=$wpdb->prefix.'apollo_hashtags';
		$ha=$wpdb->prefix.'apollo_hashtag_activities';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$ha} ha ON ha.activity_id=a.id
			JOIN {$h} h ON h.id=ha.hashtag_id
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE h.tag=%s AND a.is_hidden=0 AND a.privacy='public'
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$hashtag,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getTrending(int $hours=24, int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$l=$wpdb->prefix.'apollo_activity_likes';
		$c=$wpdb->prefix.'apollo_activity_comments';
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$hours} hours"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,
			(SELECT COUNT(*) FROM {$l} WHERE activity_id=a.id) as likes,
			(SELECT COUNT(*) FROM {$c} WHERE activity_id=a.id) as comments,
			((SELECT COUNT(*) FROM {$l} WHERE activity_id=a.id)*2+(SELECT COUNT(*) FROM {$c} WHERE activity_id=a.id)*3) as score
			FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE a.created_at>=%s AND a.is_hidden=0 AND a.privacy='public'
			ORDER BY score DESC
			LIMIT %d",
			$since,$limit
		),ARRAY_A)??[];
	}

	public static function getFollowingFeed(int $userId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$f=$wpdb->prefix.'apollo_followers';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE a.user_id IN(SELECT following_id FROM {$f} WHERE follower_id=%d)
			AND a.is_hidden=0 AND a.privacy IN('public','friends')
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getMediaFeed(int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$t} a
			JOIN {$wpdb->users} u ON a.user_id=u.ID
			WHERE a.type IN('photo','video','album') AND a.is_hidden=0 AND a.privacy='public'
			ORDER BY a.created_at DESC
			LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)??[];
	}

	public static function aggregateStats(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$today=gmdate('Y-m-d');
		$week=gmdate('Y-m-d',strtotime('-7 days'));
		$month=gmdate('Y-m-d',strtotime('-30 days'));
		return [
			'total'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}"),
			'today'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE DATE(created_at)=%s",$today)),
			'week'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE created_at>=%s",$week)),
			'month'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE created_at>=%s",$month)),
			'types'=>$wpdb->get_results("SELECT type,COUNT(*) as cnt FROM {$t} GROUP BY type ORDER BY cnt DESC",ARRAY_A)
		];
	}

	private static function areFriends(int $u1, int $u2): bool {
		if($u1===$u2){return true;}
		global $wpdb;
		$t=$wpdb->prefix.'apollo_connections';
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d AND status='accepted'",$u1,$u2));
	}
}
