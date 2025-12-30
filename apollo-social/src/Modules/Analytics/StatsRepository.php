<?php
declare(strict_types=1);
namespace Apollo\Modules\Analytics;

final class StatsRepository {
	private const T_VIEWS='apollo_profile_views';
	private const T_ACTIVITY='apollo_activity';
	private const T_POINTS='apollo_points';
	private const T_CONNECTIONS='apollo_connections';
	private const T_GROUPS='apollo_groups';
	private const T_GROUP_MEMBERS='apollo_group_members';

	public static function getSiteStats(): array {
		global $wpdb;
		$today=gmdate('Y-m-d');
		$week=gmdate('Y-m-d',strtotime('-7 days'));
		$month=gmdate('Y-m-d',strtotime('-30 days'));
		return [
			'total_users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
			'new_users_today'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered)=%s",$today)),
			'new_users_week'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered>=%s",$week)),
			'new_users_month'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered>=%s",$month)),
			'total_groups'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_GROUPS),
			'total_activities'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_ACTIVITY),
			'activities_today'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_ACTIVITY." WHERE DATE(created_at)=%s",$today)),
			'total_connections'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_CONNECTIONS." WHERE status='accepted'")
		];
	}

	public static function getUserStats(int $userId): array {
		global $wpdb;
		$c=$wpdb->prefix.self::T_CONNECTIONS;
		$a=$wpdb->prefix.self::T_ACTIVITY;
		$p=$wpdb->prefix.self::T_POINTS;
		$v=$wpdb->prefix.self::T_VIEWS;
		$g=$wpdb->prefix.self::T_GROUP_MEMBERS;
		return [
			'profile_views'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$v} WHERE profile_user_id=%d",$userId)),
			'profile_views_week'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$v} WHERE profile_user_id=%d AND viewed_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",$userId)),
			'connections'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$c} WHERE (user_id=%d OR friend_id=%d) AND status='accepted'",$userId,$userId)),
			'pending_requests'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$c} WHERE friend_id=%d AND status='pending'",$userId)),
			'activity_count'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$a} WHERE user_id=%d",$userId)),
			'points'=>(int)$wpdb->get_var($wpdb->prepare("SELECT points FROM {$p} WHERE user_id=%d",$userId)),
			'groups_joined'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$g} WHERE user_id=%d AND status='active'",$userId)),
			'groups_created'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_GROUPS." WHERE creator_id=%d",$userId))
		];
	}

	public static function getGrowthChart(int $days=30): array {
		global $wpdb;
		$data=[];
		for($i=$days-1;$i>=0;$i--){
			$date=gmdate('Y-m-d',strtotime("-{$i} days"));
			$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered)=%s",$date));
			$data[]=['date'=>$date,'count'=>$count];
		}
		return $data;
	}

	public static function getActivityChart(int $days=30): array {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ACTIVITY;
		$data=[];
		for($i=$days-1;$i>=0;$i--){
			$date=gmdate('Y-m-d',strtotime("-{$i} days"));
			$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$a} WHERE DATE(created_at)=%s",$date));
			$data[]=['date'=>$date,'count'=>$count];
		}
		return $data;
	}

	public static function getTopUsers(string $metric='points', int $limit=10): array {
		global $wpdb;
		return match($metric){
			'points'=>$wpdb->get_results($wpdb->prepare(
				"SELECT u.ID,u.display_name,p.points FROM {$wpdb->users} u
				JOIN {$wpdb->prefix}".self::T_POINTS." p ON u.ID=p.user_id
				ORDER BY p.points DESC LIMIT %d",$limit),ARRAY_A)??[],
			'connections'=>$wpdb->get_results($wpdb->prepare(
				"SELECT u.ID,u.display_name,COUNT(c.id) as total FROM {$wpdb->users} u
				JOIN {$wpdb->prefix}".self::T_CONNECTIONS." c ON u.ID=c.user_id AND c.status='accepted'
				GROUP BY u.ID ORDER BY total DESC LIMIT %d",$limit),ARRAY_A)??[],
			'activity'=>$wpdb->get_results($wpdb->prepare(
				"SELECT u.ID,u.display_name,COUNT(a.id) as total FROM {$wpdb->users} u
				JOIN {$wpdb->prefix}".self::T_ACTIVITY." a ON u.ID=a.user_id
				GROUP BY u.ID ORDER BY total DESC LIMIT %d",$limit),ARRAY_A)??[],
			default=>[]
		};
	}

	public static function getPopularGroups(int $limit=10): array {
		global $wpdb;
		$g=$wpdb->prefix.self::T_GROUPS;
		$m=$wpdb->prefix.self::T_GROUP_MEMBERS;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT g.id,g.name,COUNT(m.id) as member_count FROM {$g} g
			LEFT JOIN {$m} m ON g.id=m.group_id AND m.status='active'
			GROUP BY g.id ORDER BY member_count DESC LIMIT %d",$limit),ARRAY_A)??[];
	}

	public static function getRetentionRate(int $days=7): float {
		global $wpdb;
		$startDate=gmdate('Y-m-d',strtotime("-{$days} days"));
		$total=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered<%s",$startDate));
		if($total===0)return 0.0;
		$active=(int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}".self::T_ACTIVITY." WHERE created_at>=%s",$startDate));
		return round(($active/$total)*100,2);
	}

	public static function getEngagementMetrics(): array {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ACTIVITY;
		$totalUsers=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
		$activeUsers=(int)$wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$a} WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)");
		$avgActivitiesPerUser=(float)$wpdb->get_var("SELECT AVG(cnt) FROM (SELECT COUNT(*) as cnt FROM {$a} WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY user_id) as sub");
		return [
			'total_users'=>$totalUsers,
			'active_users_30d'=>$activeUsers,
			'engagement_rate'=>$totalUsers>0?round(($activeUsers/$totalUsers)*100,2):0,
			'avg_activities_per_user'=>round($avgActivitiesPerUser,2)
		];
	}

	public static function getContentStats(): array {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ACTIVITY;
		return [
			'posts'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$a} WHERE type=%s",'post')),
			'photos'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$a} WHERE type=%s",'photo')),
			'videos'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$a} WHERE type=%s",'video')),
			'comments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}apollo_activity_comments"),
			'likes'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}apollo_activity_likes")
		];
	}

	public static function getOnlineStats(): array {
		global $wpdb;
		$o=$wpdb->prefix.'apollo_online_users';
		$threshold=gmdate('Y-m-d H:i:s',strtotime('-5 minutes'));
		return [
			'online_now'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$o} WHERE last_activity>=%s",$threshold)),
			'online_15m'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$o} WHERE last_activity>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)")),
			'online_1h'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$o} WHERE last_activity>=DATE_SUB(NOW(),INTERVAL 1 HOUR)")),
			'peak_today'=>(int)get_option('apollo_peak_online_today',0)
		];
	}

	public static function updatePeakOnline(int $current): void {
		$peak=(int)get_option('apollo_peak_online_today',0);
		$peakDate=get_option('apollo_peak_online_date','');
		$today=gmdate('Y-m-d');
		if($peakDate!==$today){
			update_option('apollo_peak_online_today',$current);
			update_option('apollo_peak_online_date',$today);
		}elseif($current>$peak){
			update_option('apollo_peak_online_today',$current);
		}
	}

	public static function getAdminDashboard(): array {
		return [
			'site'=>self::getSiteStats(),
			'engagement'=>self::getEngagementMetrics(),
			'content'=>self::getContentStats(),
			'online'=>self::getOnlineStats(),
			'popular_groups'=>self::getPopularGroups(5),
			'top_users'=>self::getTopUsers('points',5),
			'retention_7d'=>self::getRetentionRate(7)
		];
	}
}
