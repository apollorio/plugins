<?php
declare(strict_types=1);
namespace Apollo\Modules\Groups;

final class GroupTypesRepository {
	private const TABLE='apollo_group_types';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'name'=>sanitize_text_field($d['name']),
			'slug'=>sanitize_title($d['slug']??$d['name']),
			'description'=>sanitize_textarea_field($d['description']??''),
			'icon'=>sanitize_text_field($d['icon']??''),
			'color'=>sanitize_hex_color($d['color']??'#3498db')?:'#3498db',
			'can_create_roles'=>isset($d['can_create_roles'])?wp_json_encode($d['can_create_roles']):null,
			'settings'=>isset($d['settings'])?wp_json_encode($d['settings']):null
		]);
		return (int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A);
		if($r){
			$r['can_create_roles']=json_decode($r['can_create_roles']??'',true);
			$r['settings']=json_decode($r['settings']??'',true);
		}
		return $r?:null;
	}

	public static function getBySlug(string $slug): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE slug=%s",$slug),ARRAY_A);
		if($r){
			$r['can_create_roles']=json_decode($r['can_create_roles']??'',true);
			$r['settings']=json_decode($r['settings']??'',true);
		}
		return $r?:null;
	}

	public static function getAll(): array {
		global $wpdb;
		$rows=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}".self::TABLE." ORDER BY name",ARRAY_A)?:[];
		foreach($rows as &$r){
			$r['can_create_roles']=json_decode($r['can_create_roles']??'',true);
			$r['settings']=json_decode($r['settings']??'',true);
		}
		return $rows;
	}

	public static function canUserCreate(int $userId,int $typeId): bool {
		$type=self::get($typeId);
		if(!$type)return false;
		if(empty($type['can_create_roles']))return true;
		$user=get_userdata($userId);
		if(!$user)return false;
		return count(array_intersect($user->roles,$type['can_create_roles']))>0;
	}

	public static function update(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['name','slug','description','icon','color'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		if(isset($d['can_create_roles']))$u['can_create_roles']=wp_json_encode($d['can_create_roles']);
		if(isset($d['settings']))$u['settings']=wp_json_encode($d['settings']);
		return $wpdb->update($wpdb->prefix.self::TABLE,$u,['id'=>$id])!==false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}
}
