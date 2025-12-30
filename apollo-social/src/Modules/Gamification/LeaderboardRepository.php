<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;

final class LeaderboardRepository {
	private const TABLE='apollo_leaderboards';
	private const ENTRIES='apollo_leaderboard_entries';

	public static function getGlobal(string $period='all', int $limit=100): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_points';
		$dateFilter=self::getPeriodFilter($period);
		return $wpdb->get_results($wpdb->prepare(
			"SELECT p.user_id,SUM(p.points) as total_points,u.display_name,um.meta_value as avatar,
			(SELECT COUNT(*) FROM {$wpdb->prefix}apollo_user_achievements WHERE user_id=p.user_id) as badges,
			(SELECT name FROM {$wpdb->prefix}apollo_ranks r WHERE r.min_points<=SUM(p.points) ORDER BY r.min_points DESC LIMIT 1) as rank_name
			FROM {$t} p 
			JOIN {$wpdb->users} u ON p.user_id=u.ID 
			LEFT JOIN {$wpdb->usermeta} um ON p.user_id=um.user_id AND um.meta_key='apollo_avatar'
			{$dateFilter}
			GROUP BY p.user_id 
			ORDER BY total_points DESC 
			LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getByCategory(string $category, string $period='all', int $limit=50): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_points';
		$dateFilter=self::getPeriodFilter($period,'p.');
		return $wpdb->get_results($wpdb->prepare(
			"SELECT p.user_id,SUM(p.points) as total_points,u.display_name
			FROM {$t} p 
			JOIN {$wpdb->users} u ON p.user_id=u.ID 
			WHERE p.category=%s {$dateFilter}
			GROUP BY p.user_id 
			ORDER BY total_points DESC 
			LIMIT %d",
			$category,$limit
		),ARRAY_A)??[];
	}

	public static function getFriendsLeaderboard(int $userId, int $limit=20): array {
		global $wpdb;
		$c=$wpdb->prefix.'apollo_connections';
		$p=$wpdb->prefix.'apollo_user_points';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT p.user_id,SUM(p.points) as total_points,u.display_name
			FROM {$c} c
			JOIN {$p} p ON c.friend_id=p.user_id
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			WHERE c.user_id=%d AND c.status='accepted'
			GROUP BY p.user_id
			UNION
			SELECT %d as user_id,SUM(points) as total_points,(SELECT display_name FROM {$wpdb->users} WHERE ID=%d) as display_name
			FROM {$p} WHERE user_id=%d
			ORDER BY total_points DESC
			LIMIT %d",
			$userId,$userId,$userId,$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getGroupLeaderboard(int $groupId, string $period='all', int $limit=50): array {
		global $wpdb;
		$m=$wpdb->prefix.'apollo_group_members';
		$p=$wpdb->prefix.'apollo_user_points';
		$dateFilter=self::getPeriodFilter($period,'p.');
		return $wpdb->get_results($wpdb->prepare(
			"SELECT p.user_id,SUM(p.points) as total_points,u.display_name
			FROM {$m} gm
			JOIN {$p} p ON gm.user_id=p.user_id
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			WHERE gm.group_id=%d {$dateFilter}
			GROUP BY p.user_id
			ORDER BY total_points DESC
			LIMIT %d",
			$groupId,$limit
		),ARRAY_A)??[];
	}

	public static function getUserRank(int $userId, string $period='all'): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_points';
		$userPoints=self::getUserPoints($userId,$period);
		$dateFilter=self::getPeriodFilter($period);
		$count=$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM (
				SELECT user_id,SUM(points) as total FROM {$t} {$dateFilter} GROUP BY user_id HAVING total>%d
			) ranks",
			$userPoints
		));
		return (int)$count+1;
	}

	public static function getUserPoints(int $userId, string $period='all'): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_points';
		$dateFilter=self::getPeriodFilter($period);
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COALESCE(SUM(points),0) FROM {$t} WHERE user_id=%d {$dateFilter}",
			$userId
		));
	}

	public static function getTopEarners(string $period='week', int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_points';
		$dateFilter=self::getPeriodFilter($period);
		return $wpdb->get_results($wpdb->prepare(
			"SELECT user_id,SUM(points) as earned,u.display_name
			FROM {$t} p
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			{$dateFilter}
			GROUP BY user_id
			ORDER BY earned DESC
			LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getMostActive(string $period='week', int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_points';
		$dateFilter=self::getPeriodFilter($period);
		return $wpdb->get_results($wpdb->prepare(
			"SELECT user_id,COUNT(*) as actions,u.display_name
			FROM {$t} p
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			{$dateFilter}
			GROUP BY user_id
			ORDER BY actions DESC
			LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getRankDistribution(): array {
		global $wpdb;
		$r=$wpdb->prefix.'apollo_ranks';
		$p=$wpdb->prefix.'apollo_user_points';
		$ranks=$wpdb->get_results("SELECT id,name,min_points FROM {$r} ORDER BY min_points ASC",ARRAY_A);
		$result=[];
		for($i=0;$i<count($ranks);$i++){
			$minPts=(int)$ranks[$i]['min_points'];
			$maxPts=isset($ranks[$i+1])?(int)$ranks[$i+1]['min_points']-1:999999999;
			$count=$wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM (
					SELECT user_id,SUM(points) as total FROM {$p} GROUP BY user_id HAVING total>=%d AND total<=%d
				) x",
				$minPts,$maxPts
			));
			$result[]=['rank'=>$ranks[$i]['name'],'count'=>(int)$count,'min'=>$minPts,'max'=>$maxPts];
		}
		return $result;
	}

	public static function getCompetitions(bool $activeOnly=true): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_competitions';
		$now=current_time('mysql');
		$where=$activeOnly?"WHERE start_date<=%s AND end_date>=%s":'';
		$args=$activeOnly?[$now,$now]:[];
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} {$where} ORDER BY end_date ASC",
			...$args
		),ARRAY_A)??[];
	}

	public static function createCompetition(array $data): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_competitions';
		$wpdb->insert($t,[
			'name'=>sanitize_text_field($data['name']),
			'description'=>wp_kses_post($data['description']??''),
			'type'=>$data['type']??'points',
			'category'=>$data['category']??null,
			'start_date'=>$data['start_date'],
			'end_date'=>$data['end_date'],
			'prizes'=>wp_json_encode($data['prizes']??[]),
			'rules'=>wp_json_encode($data['rules']??[]),
			'status'=>'active',
			'created_by'=>get_current_user_id(),
			'created_at'=>current_time('mysql')
		],['%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s']);
		return (int)$wpdb->insert_id;
	}

	public static function getCompetitionLeaderboard(int $competitionId, int $limit=50): array {
		global $wpdb;
		$comp=$wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_competitions WHERE id=%d",
			$competitionId
		),ARRAY_A);
		if(!$comp){return [];}
		$p=$wpdb->prefix.'apollo_user_points';
		$catFilter=$comp['category']?"AND category=%s":'';
		$args=[$comp['start_date'],$comp['end_date']];
		if($comp['category']){$args[]=$comp['category'];}
		$args[]=$limit;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT user_id,SUM(points) as score,u.display_name
			FROM {$p} p
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			WHERE created_at>=%s AND created_at<=%s {$catFilter}
			GROUP BY user_id
			ORDER BY score DESC
			LIMIT %d",
			...$args
		),ARRAY_A)??[];
	}

	public static function finalizeCompetition(int $competitionId): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_competitions';
		$winners=self::getCompetitionLeaderboard($competitionId,10);
		$wpdb->update($t,['status'=>'completed','winners'=>wp_json_encode($winners)],['id'=>$competitionId],['%s','%s'],['%d']);
		do_action('apollo_competition_finalized',$competitionId,$winners);
		return $winners;
	}

	public static function getStreaks(int $limit=20): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT user_id,meta_value as streak,u.display_name
			FROM {$wpdb->usermeta} um
			JOIN {$wpdb->users} u ON um.user_id=u.ID
			WHERE meta_key='apollo_login_streak'
			ORDER BY CAST(meta_value AS UNSIGNED) DESC
			LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getMilestones(int $userId): array {
		$points=self::getUserPoints($userId);
		$milestones=[100,500,1000,2500,5000,10000,25000,50000,100000];
		$reached=[];$next=null;
		foreach($milestones as $m){
			if($points>=$m){$reached[]=$m;}
			elseif($next===null){$next=$m;break;}
		}
		return ['points'=>$points,'reached'=>$reached,'next'=>$next,'progress'=>$next?round($points/$next*100,1):100];
	}

	private static function getPeriodFilter(string $period, string $prefix=''): string {
		$col=$prefix?"{$prefix}created_at":'created_at';
		return match($period){
			'today'=>"WHERE {$col}>=CURDATE()",
			'week'=>"WHERE {$col}>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)",
			'month'=>"WHERE {$col}>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)",
			'year'=>"WHERE {$col}>=DATE_SUB(CURDATE(),INTERVAL 1 YEAR)",
			default=>''
		};
	}
}
