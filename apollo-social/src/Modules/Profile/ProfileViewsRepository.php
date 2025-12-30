<?php
declare(strict_types=1);
namespace Apollo\Modules\Profile;

final class ProfileViewsRepository {
	private const TABLE='apollo_profile_views';
	
	public static function record(int $profileUserId, int $viewerId, string $ip=''): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		if($profileUserId===$viewerId)return false;
		$today=gmdate('Y-m-d');
		$existing=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE profile_user_id=%d AND viewer_id=%d AND DATE(viewed_at)=%s",
			$profileUserId,$viewerId,$today
		));
		if($existing)return false;
		return (bool)$wpdb->insert($t,[
			'profile_user_id'=>$profileUserId,
			'viewer_id'=>$viewerId,
			'ip_address'=>$ip,
			'viewed_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s','%s']);
	}

	public static function recordAnonymous(int $profileUserId, string $ip): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$today=gmdate('Y-m-d');
		$existing=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE profile_user_id=%d AND viewer_id=0 AND ip_address=%s AND DATE(viewed_at)=%s",
			$profileUserId,$ip,$today
		));
		if($existing)return false;
		return (bool)$wpdb->insert($t,[
			'profile_user_id'=>$profileUserId,
			'viewer_id'=>0,
			'ip_address'=>$ip,
			'viewed_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s','%s']);
	}

	public static function getTotalViews(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE profile_user_id=%d",$userId));
	}

	public static function getUniqueViews(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT CASE WHEN viewer_id>0 THEN viewer_id ELSE ip_address END) FROM {$t} WHERE profile_user_id=%d",
			$userId
		));
	}

	public static function getViewsToday(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE profile_user_id=%d AND DATE(viewed_at)=%s",
			$userId,gmdate('Y-m-d')
		));
	}

	public static function getViewsThisWeek(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$weekAgo=gmdate('Y-m-d H:i:s',strtotime('-7 days'));
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE profile_user_id=%d AND viewed_at>=%s",
			$userId,$weekAgo
		));
	}

	public static function getViewsThisMonth(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$monthAgo=gmdate('Y-m-d H:i:s',strtotime('-30 days'));
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE profile_user_id=%d AND viewed_at>=%s",
			$userId,$monthAgo
		));
	}

	public static function getRecentViewers(int $userId, int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT pv.viewer_id,pv.viewed_at,u.display_name FROM {$t} pv LEFT JOIN {$wpdb->users} u ON pv.viewer_id=u.ID WHERE pv.profile_user_id=%d AND pv.viewer_id>0 GROUP BY pv.viewer_id ORDER BY MAX(pv.viewed_at) DESC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getViewHistory(int $userId, int $days=30): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$data=[];
		for($i=$days-1;$i>=0;$i--){
			$date=gmdate('Y-m-d',strtotime("-{$i} days"));
			$count=(int)$wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$t} WHERE profile_user_id=%d AND DATE(viewed_at)=%s",
				$userId,$date
			));
			$data[]=['date'=>$date,'views'=>$count];
		}
		return $data;
	}

	public static function getStats(int $userId): array {
		return [
			'total'=>self::getTotalViews($userId),
			'unique'=>self::getUniqueViews($userId),
			'today'=>self::getViewsToday($userId),
			'week'=>self::getViewsThisWeek($userId),
			'month'=>self::getViewsThisMonth($userId)
		];
	}

	public static function hasViewed(int $viewerId, int $profileUserId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE profile_user_id=%d AND viewer_id=%d",
			$profileUserId,$viewerId
		));
	}

	public static function getMutualViews(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT v1.viewer_id as user_id,u.display_name,MAX(v1.viewed_at) as viewed_me,MAX(v2.viewed_at) as i_viewed FROM {$t} v1 JOIN {$t} v2 ON v1.viewer_id=v2.profile_user_id AND v1.profile_user_id=v2.viewer_id JOIN {$wpdb->users} u ON v1.viewer_id=u.ID WHERE v1.profile_user_id=%d AND v1.viewer_id>0 GROUP BY v1.viewer_id ORDER BY viewed_me DESC LIMIT 20",
			$userId
		),ARRAY_A)??[];
	}

	public static function getWhoIViewed(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT pv.profile_user_id,MAX(pv.viewed_at) as last_viewed,u.display_name FROM {$t} pv JOIN {$wpdb->users} u ON pv.profile_user_id=u.ID WHERE pv.viewer_id=%d GROUP BY pv.profile_user_id ORDER BY last_viewed DESC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getMostViewed(int $days=7, int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$days} days"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT pv.profile_user_id,COUNT(*) as view_count,u.display_name FROM {$t} pv JOIN {$wpdb->users} u ON pv.profile_user_id=u.ID WHERE pv.viewed_at>=%s GROUP BY pv.profile_user_id ORDER BY view_count DESC LIMIT %d",
			$since,$limit
		),ARRAY_A)??[];
	}

	public static function canSeeViewers(int $userId, int $viewerId): bool {
		$privacy=get_user_meta($userId,'apollo_profile_views_privacy',true)?:'friends';
		if($privacy==='everyone')return true;
		if($privacy==='nobody')return false;
		if($privacy==='friends'){
			global $wpdb;
			$c=$wpdb->prefix.'apollo_connections';
			return (bool)$wpdb->get_var($wpdb->prepare(
				"SELECT id FROM {$c} WHERE ((user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)) AND status='accepted'",
				$userId,$viewerId,$viewerId,$userId
			));
		}
		return false;
	}

	public static function cleanup(int $olderThanDays=90): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$threshold=gmdate('Y-m-d H:i:s',strtotime("-{$olderThanDays} days"));
		return (int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE viewed_at<%s",$threshold));
	}
}
