<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;

final class CompetitionsRepository {
	private const TABLE='apollo_competitions';
	private const PARTS='apollo_competition_participants';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'name'=>sanitize_text_field($d['name']),
			'slug'=>sanitize_title($d['slug']??$d['name']),
			'description'=>sanitize_textarea_field($d['description']??''),
			'type'=>in_array($d['type']??'',['leaderboard','challenge','race'])?$d['type']:'leaderboard',
			'metric'=>sanitize_key($d['metric']),
			'start_at'=>$d['start_at'],
			'end_at'=>$d['end_at'],
			'prizes'=>isset($d['prizes'])?wp_json_encode($d['prizes']):null,
			'rules'=>isset($d['rules'])?wp_json_encode($d['rules']):null,
			'status'=>'upcoming'
		]);
		return (int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A);
		if($r){$r['prizes']=json_decode($r['prizes']??'',true);$r['rules']=json_decode($r['rules']??'',true);}
		return $r?:null;
	}

	public static function getActive(): array {
		global $wpdb;
		$now=current_time('mysql');
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE start_at<=%s AND end_at>=%s AND status='active' ORDER BY end_at",$now,$now
		),ARRAY_A)?:[];
		foreach($rows as &$r){$r['prizes']=json_decode($r['prizes']??'',true);$r['rules']=json_decode($r['rules']??'',true);}
		return $rows;
	}

	public static function getUpcoming(): array {
		global $wpdb;
		$now=current_time('mysql');
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE start_at>%s AND status='upcoming' ORDER BY start_at",$now
		),ARRAY_A)?:[];
		foreach($rows as &$r){$r['prizes']=json_decode($r['prizes']??'',true);$r['rules']=json_decode($r['rules']??'',true);}
		return $rows;
	}

	public static function join(int $compId,int $userId): bool {
		global $wpdb;
		$comp=self::get($compId);
		if(!$comp||$comp['status']!=='active')return false;
		return $wpdb->replace($wpdb->prefix.self::PARTS,['competition_id'=>$compId,'user_id'=>$userId,'score'=>0])!==false;
	}

	public static function leave(int $compId,int $userId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::PARTS,['competition_id'=>$compId,'user_id'=>$userId])!==false;
	}

	public static function updateScore(int $compId,int $userId,int $score): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::PARTS,['score'=>$score],['competition_id'=>$compId,'user_id'=>$userId])!==false;
	}

	public static function incrementScore(int $compId,int $userId,int $amount=1): bool {
		global $wpdb;
		return $wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}".self::PARTS." SET score=score+%d WHERE competition_id=%d AND user_id=%d",
			$amount,$compId,$userId
		))!==false;
	}

	public static function getLeaderboard(int $compId,int $limit=100): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT p.*,u.display_name FROM {$wpdb->prefix}".self::PARTS." p
			 LEFT JOIN {$wpdb->users} u ON u.ID=p.user_id
			 WHERE p.competition_id=%d ORDER BY p.score DESC LIMIT %d",
			$compId,$limit
		),ARRAY_A)?:[];
	}

	public static function getUserPosition(int $compId,int $userId): ?int {
		global $wpdb;
		$score=$wpdb->get_var($wpdb->prepare(
			"SELECT score FROM {$wpdb->prefix}".self::PARTS." WHERE competition_id=%d AND user_id=%d",$compId,$userId
		));
		if($score===null)return null;
		return 1+(int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::PARTS." WHERE competition_id=%d AND score>%d",$compId,(int)$score
		));
	}

	public static function getUserScore(int $compId,int $userId): int {
		global $wpdb;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT score FROM {$wpdb->prefix}".self::PARTS." WHERE competition_id=%d AND user_id=%d",$compId,$userId
		))?:0;
	}

	public static function isParticipant(int $compId,int $userId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}".self::PARTS." WHERE competition_id=%d AND user_id=%d",$compId,$userId
		));
	}

	public static function updateRanks(int $compId): void {
		global $wpdb;
		$wpdb->query($wpdb->prepare(
			"SET @rank=0; UPDATE {$wpdb->prefix}".self::PARTS." SET rank_position=(@rank:=@rank+1) WHERE competition_id=%d ORDER BY score DESC",
			$compId
		));
	}

	public static function activateScheduled(): int {
		global $wpdb;
		$now=current_time('mysql');
		return (int)$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}".self::TABLE." SET status='active' WHERE status='upcoming' AND start_at<=%s",$now
		));
	}

	public static function endFinished(): int {
		global $wpdb;
		$now=current_time('mysql');
		return (int)$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}".self::TABLE." SET status='ended' WHERE status='active' AND end_at<%s",$now
		));
	}

	public static function update(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['name','slug','description','type','metric','start_at','end_at','status'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		if(isset($d['prizes']))$u['prizes']=wp_json_encode($d['prizes']);
		if(isset($d['rules']))$u['rules']=wp_json_encode($d['rules']);
		return $wpdb->update($wpdb->prefix.self::TABLE,$u,['id'=>$id])!==false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::PARTS,['competition_id'=>$id]);
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}
}
