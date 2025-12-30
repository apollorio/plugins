<?php
declare(strict_types=1);
namespace Apollo\Modules\Profiles;

final class ProfileTabsRepository {
	private const TABLE='apollo_profile_tabs';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'name'=>sanitize_text_field($d['name']),
			'slug'=>sanitize_title($d['slug']??$d['name']),
			'icon'=>sanitize_text_field($d['icon']??''),
			'content_type'=>in_array($d['content_type']??'',['fields','shortcode','template','custom'])?$d['content_type']:'fields',
			'content'=>$d['content']??'',
			'visibility'=>$d['visibility']??'everyone',
			'roles_allowed'=>isset($d['roles_allowed'])?wp_json_encode($d['roles_allowed']):null,
			'sort_order'=>(int)($d['sort_order']??0),
			'is_active'=>1
		]);
		return (int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A);
		if($r)$r['roles_allowed']=json_decode($r['roles_allowed']??'',true);
		return $r?:null;
	}

	public static function getBySlug(string $slug): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE slug=%s",$slug),ARRAY_A);
		if($r)$r['roles_allowed']=json_decode($r['roles_allowed']??'',true);
		return $r?:null;
	}

	public static function getAll(bool $activeOnly=true): array {
		global $wpdb;
		$w=$activeOnly?'WHERE is_active=1':'';
		$rows=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}".self::TABLE." {$w} ORDER BY sort_order",ARRAY_A)?:[];
		foreach($rows as &$r)$r['roles_allowed']=json_decode($r['roles_allowed']??'',true);
		return $rows;
	}

	public static function getVisibleForUser(int $viewerId,int $profileUserId): array {
		$tabs=self::getAll();
		$viewer=get_userdata($viewerId);
		$isSelf=$viewerId===$profileUserId;
		$isAdmin=$viewer&&user_can($viewer,'manage_options');
		$isFriend=self::areFriends($viewerId,$profileUserId);
		$visible=[];
		foreach($tabs as $t){
			$v=$t['visibility'];
			if($v==='everyone'||$isAdmin||($v==='self'&&$isSelf)||($v==='members'&&$viewerId>0)||($v==='friends'&&($isFriend||$isSelf))){
				$visible[]=$t;
			}
		}
		return $visible;
	}

	private static function areFriends(int $a,int $b): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}apollo_connections WHERE status='accepted' AND ((user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)) LIMIT 1",
			$a,$b,$b,$a
		));
	}

	public static function update(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['name','slug','icon','content_type','content','visibility','sort_order','is_active'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		if(isset($d['roles_allowed']))$u['roles_allowed']=wp_json_encode($d['roles_allowed']);
		return $wpdb->update($wpdb->prefix.self::TABLE,$u,['id'=>$id])!==false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}

	public static function reorder(array $order): bool {
		global $wpdb;
		foreach($order as $pos=>$id){
			$wpdb->update($wpdb->prefix.self::TABLE,['sort_order'=>$pos],['id'=>(int)$id]);
		}
		return true;
	}
}
