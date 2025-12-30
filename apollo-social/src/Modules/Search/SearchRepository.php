<?php
declare(strict_types=1);
namespace Apollo\Modules\Search;

final class SearchRepository {

	public static function globalSearch(string $query,int $userId=0,int $limit=50): array {
		$results=['members'=>[],'groups'=>[],'activity'=>[],'forum'=>[]];
		$sanitized=sanitize_text_field($query);
		if(strlen($sanitized)<2)return $results;
		$results['members']=\Apollo\Modules\Members\MembersDirectoryRepository::search(['search'=>$sanitized,'limit'=>min(20,$limit)]);
		$results['groups']=\Apollo\Modules\Groups\GroupsRepository::search(['search'=>$sanitized,'limit'=>min(10,$limit)]);
		global $wpdb;
		$like='%'.$wpdb->esc_like($sanitized).'%';
		$results['activity']=$wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$wpdb->prefix}apollo_activity a
			 LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id
			 WHERE a.content LIKE %s AND a.is_spam=0 AND a.privacy='public' ORDER BY a.created_at DESC LIMIT %d",
			$like,min(10,$limit)
		),ARRAY_A)?:[];
		$results['forum']=$wpdb->get_results($wpdb->prepare(
			"SELECT t.*,u.display_name as author_name FROM {$wpdb->prefix}apollo_forum_topics t
			 LEFT JOIN {$wpdb->users} u ON u.ID=t.author_id
			 WHERE (t.title LIKE %s OR t.content LIKE %s) AND t.status='open' ORDER BY t.created_at DESC LIMIT %d",
			$like,$like,min(10,$limit)
		),ARRAY_A)?:[];
		return $results;
	}

	public static function searchMembers(string $query,array $filters=[],int $limit=20,int $offset=0): array {
		$args=array_merge(['search'=>$query,'limit'=>$limit,'offset'=>$offset],$filters);
		return \Apollo\Modules\Members\MembersDirectoryRepository::search($args);
	}

	public static function searchGroups(string $query,?string $type=null,int $limit=20,int $offset=0): array {
		$args=['search'=>$query,'limit'=>$limit,'offset'=>$offset];
		if($type)$args['type_id']=(int)$type;
		return \Apollo\Modules\Groups\GroupsRepository::search($args);
	}

	public static function searchActivity(string $query,?int $userId=null,int $limit=20,int $offset=0): array {
		global $wpdb;
		$like='%'.$wpdb->esc_like($query).'%';
		$userWhere=$userId?"AND a.user_id={$userId}":'';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$wpdb->prefix}apollo_activity a
			 LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id
			 WHERE a.content LIKE %s AND a.is_spam=0 {$userWhere} ORDER BY a.created_at DESC LIMIT %d OFFSET %d",
			$like,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function autocomplete(string $query,string $type='all',int $limit=10): array {
		$results=[];
		$sanitized=sanitize_text_field($query);
		if(strlen($sanitized)<2)return $results;
		global $wpdb;
		$like=$wpdb->esc_like($sanitized).'%';
		if($type==='all'||$type==='members'){
			$users=$wpdb->get_results($wpdb->prepare(
				"SELECT ID,display_name,user_login FROM {$wpdb->users} WHERE display_name LIKE %s OR user_login LIKE %s LIMIT %d",
				$like,$like,$limit
			),ARRAY_A)?:[];
			foreach($users as $u){
				$results[]=['type'=>'member','id'=>$u['ID'],'label'=>$u['display_name'],'value'=>$u['user_login']];
			}
		}
		if($type==='all'||$type==='groups'){
			$groups=$wpdb->get_results($wpdb->prepare(
				"SELECT id,name,slug FROM {$wpdb->prefix}apollo_groups WHERE name LIKE %s AND status='public' LIMIT %d",
				$like,$limit
			),ARRAY_A)?:[];
			foreach($groups as $g){
				$results[]=['type'=>'group','id'=>$g['id'],'label'=>$g['name'],'value'=>$g['slug']];
			}
		}
		if($type==='all'||$type==='tags'){
			$tags=$wpdb->get_results($wpdb->prepare(
				"SELECT id,name,slug FROM {$wpdb->prefix}apollo_user_tags WHERE name LIKE %s LIMIT %d",$like,$limit
			),ARRAY_A)?:[];
			foreach($tags as $t){
				$results[]=['type'=>'tag','id'=>$t['id'],'label'=>$t['name'],'value'=>$t['slug']];
			}
		}
		return array_slice($results,0,$limit);
	}
}
