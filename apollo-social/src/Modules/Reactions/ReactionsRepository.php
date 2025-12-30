<?php
declare(strict_types=1);
namespace Apollo\Modules\Reactions;

final class ReactionsRepository {
	private const TABLE='apollo_reactions';
	private const TYPES=['like','love','haha','wow','sad','angry','care'];

	public static function react(int $userId, string $objectType, int $objectId, string $reactionType): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		if(!in_array($reactionType,self::TYPES,true))return false;
		$existing=$wpdb->get_row($wpdb->prepare(
			"SELECT id,reaction_type FROM {$t} WHERE user_id=%d AND object_type=%s AND object_id=%d",
			$userId,$objectType,$objectId
		),ARRAY_A);
		if($existing){
			if($existing['reaction_type']===$reactionType){
				return (bool)$wpdb->delete($t,['id'=>$existing['id']],['%d']);
			}
			return (bool)$wpdb->update($t,['reaction_type'=>$reactionType,'updated_at'=>gmdate('Y-m-d H:i:s')],['id'=>$existing['id']],['%s','%s'],['%d']);
		}
		return (bool)$wpdb->insert($t,[
			'user_id'=>$userId,
			'object_type'=>$objectType,
			'object_id'=>$objectId,
			'reaction_type'=>$reactionType,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%d','%s','%s']);
	}

	public static function remove(int $userId, string $objectType, int $objectId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (bool)$wpdb->delete($t,['user_id'=>$userId,'object_type'=>$objectType,'object_id'=>$objectId],['%d','%s','%d']);
	}

	public static function getUserReaction(int $userId, string $objectType, int $objectId): ?string {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT reaction_type FROM {$t} WHERE user_id=%d AND object_type=%s AND object_id=%d",
			$userId,$objectType,$objectId
		));
	}

	public static function getReactionCounts(string $objectType, int $objectId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$results=$wpdb->get_results($wpdb->prepare(
			"SELECT reaction_type,COUNT(*) as count FROM {$t} WHERE object_type=%s AND object_id=%d GROUP BY reaction_type",
			$objectType,$objectId
		),ARRAY_A)??[];
		$counts=array_fill_keys(self::TYPES,0);
		$total=0;
		foreach($results as $r){
			$counts[$r['reaction_type']]=(int)$r['count'];
			$total+=(int)$r['count'];
		}
		return ['counts'=>$counts,'total'=>$total];
	}

	public static function getTotalCount(string $objectType, int $objectId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE object_type=%s AND object_id=%d",
			$objectType,$objectId
		));
	}

	public static function getReactors(string $objectType, int $objectId, string $reactionType='', int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		if($reactionType){
			return $wpdb->get_results($wpdb->prepare(
				"SELECT r.user_id,r.reaction_type,r.created_at,u.display_name FROM {$t} r JOIN {$wpdb->users} u ON r.user_id=u.ID WHERE r.object_type=%s AND r.object_id=%d AND r.reaction_type=%s ORDER BY r.created_at DESC LIMIT %d OFFSET %d",
				$objectType,$objectId,$reactionType,$limit,$offset
			),ARRAY_A)??[];
		}
		return $wpdb->get_results($wpdb->prepare(
			"SELECT r.user_id,r.reaction_type,r.created_at,u.display_name FROM {$t} r JOIN {$wpdb->users} u ON r.user_id=u.ID WHERE r.object_type=%s AND r.object_id=%d ORDER BY r.created_at DESC LIMIT %d OFFSET %d",
			$objectType,$objectId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getSummary(string $objectType, int $objectId, int $viewerId=0): array {
		$counts=self::getReactionCounts($objectType,$objectId);
		$summary=['total'=>$counts['total'],'counts'=>$counts['counts'],'top'=>[],'user_reaction'=>null];
		arsort($counts['counts']);
		$top=array_slice(array_filter($counts['counts']),0,3,true);
		$summary['top']=array_keys($top);
		if($viewerId>0){
			$summary['user_reaction']=self::getUserReaction($viewerId,$objectType,$objectId);
		}
		if($counts['total']>0){
			$summary['recent_reactors']=array_column(self::getReactors($objectType,$objectId,'',3),'display_name');
		}
		return $summary;
	}

	public static function getUserReactions(int $userId, int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function bulkGetUserReactions(int $userId, string $objectType, array $objectIds): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		if(empty($objectIds))return[];
		$placeholders=\implode(',',\array_fill(0,\count($objectIds),'%d'));
		$results=$wpdb->get_results($wpdb->prepare(
			"SELECT object_id,reaction_type FROM {$t} WHERE user_id=%d AND object_type=%s AND object_id IN({$placeholders})",
			array_merge([$userId,$objectType],$objectIds)
		),ARRAY_A)??[];
		$map=[];
		foreach($results as $r){
			$map[(int)$r['object_id']]=$r['reaction_type'];
		}
		return $map;
	}

	public static function bulkGetCounts(string $objectType, array $objectIds): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		if(empty($objectIds))return[];
		$placeholders=\implode(',',\array_fill(0,\count($objectIds),'%d'));
		$results=$wpdb->get_results($wpdb->prepare(
			"SELECT object_id,reaction_type,COUNT(*) as count FROM {$t} WHERE object_type=%s AND object_id IN({$placeholders}) GROUP BY object_id,reaction_type",
			array_merge([$objectType],$objectIds)
		),ARRAY_A)??[];
		$map=[];
		foreach($results as $r){
			$oid=(int)$r['object_id'];
			if(!isset($map[$oid]))$map[$oid]=array_fill_keys(self::TYPES,0);
			$map[$oid][$r['reaction_type']]=(int)$r['count'];
		}
		return $map;
	}

	public static function getTypes(): array {
		return self::TYPES;
	}

	public static function hasReacted(int $userId, string $objectType, int $objectId): bool {
		return self::getUserReaction($userId,$objectType,$objectId)!==null;
	}

	public static function getMostReactedObjects(string $objectType, int $days=7, int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$days} days"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT object_id,COUNT(*) as total FROM {$t} WHERE object_type=%s AND created_at>=%s GROUP BY object_id ORDER BY total DESC LIMIT %d",
			$objectType,$since,$limit
		),ARRAY_A)??[];
	}

	public static function getUserStats(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$given=$wpdb->get_results($wpdb->prepare(
			"SELECT reaction_type,COUNT(*) as count FROM {$t} WHERE user_id=%d GROUP BY reaction_type",
			$userId
		),ARRAY_A)??[];
		$givenMap=array_fill_keys(self::TYPES,0);
		foreach($given as $g)$givenMap[$g['reaction_type']]=(int)$g['count'];
		return [
			'given'=>$givenMap,
			'total_given'=>array_sum($givenMap),
			'most_used'=>array_search(max($givenMap),$givenMap,true)?:null
		];
	}
}
