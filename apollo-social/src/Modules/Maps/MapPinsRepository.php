<?php
declare(strict_types=1);
namespace Apollo\Modules\Maps;

final class MapPinsRepository {
	private const TABLE='apollo_map_pins';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'title'=>sanitize_text_field($d['title']),
			'description'=>sanitize_textarea_field($d['description']??''),
			'latitude'=>(float)$d['latitude'],
			'longitude'=>(float)$d['longitude'],
			'address'=>sanitize_text_field($d['address']??''),
			'category'=>sanitize_key($d['category']??''),
			'icon'=>sanitize_text_field($d['icon']??''),
			'color'=>sanitize_hex_color($d['color']??'#e74c3c')?:'#e74c3c',
			'link_url'=>isset($d['link_url'])?esc_url_raw($d['link_url']):null,
			'object_type'=>sanitize_key($d['object_type']??''),
			'object_id'=>isset($d['object_id'])?(int)$d['object_id']:null,
			'meta'=>isset($d['meta'])?wp_json_encode($d['meta']):null,
			'is_active'=>(int)($d['is_active']??1)
		]);
		return (int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A);
		if($r)$r['meta']=json_decode($r['meta']??'',true);
		return $r?:null;
	}

	public static function getAll(bool $activeOnly=true): array {
		global $wpdb;
		$w=$activeOnly?'WHERE is_active=1':'';
		$rows=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}".self::TABLE." {$w} ORDER BY title",ARRAY_A)?:[];
		foreach($rows as &$r){$r['meta']=json_decode($r['meta']??'',true);}
		return $rows;
	}

	public static function getByCategory(string $category,bool $activeOnly=true): array {
		global $wpdb;
		$w=$activeOnly?'AND is_active=1':'';
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE category=%s {$w} ORDER BY title",$category
		),ARRAY_A)?:[];
		foreach($rows as &$r){$r['meta']=json_decode($r['meta']??'',true);}
		return $rows;
	}

	public static function getByObject(string $objectType,int $objectId): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE object_type=%s AND object_id=%d",$objectType,$objectId
		),ARRAY_A);
		if($r)$r['meta']=json_decode($r['meta']??'',true);
		return $r?:null;
	}

	public static function getInBounds(float $swLat,float $swLng,float $neLat,float $neLng,?string $category=null): array {
		global $wpdb;
		$catWhere=$category?"AND category=%s":'';
		$params=[$swLat,$neLat,$swLng,$neLng];
		if($category)$params[]=$category;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE is_active=1 AND latitude BETWEEN %f AND %f AND longitude BETWEEN %f AND %f {$catWhere}",
			...$params
		),ARRAY_A)?:[];
	}

	public static function getNearby(float $lat,float $lng,float $radiusKm=10,int $limit=50): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT *,(6371*acos(cos(radians(%f))*cos(radians(latitude))*cos(radians(longitude)-radians(%f))+sin(radians(%f))*sin(radians(latitude)))) AS distance
			 FROM {$wpdb->prefix}".self::TABLE." WHERE is_active=1 HAVING distance<=%f ORDER BY distance LIMIT %d",
			$lat,$lng,$lat,$radiusKm,$limit
		),ARRAY_A)?:[];
	}

	public static function getCategories(): array {
		global $wpdb;
		return $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}".self::TABLE." WHERE category!='' AND is_active=1 ORDER BY category")?:[];
	}

	public static function update(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['title','description','latitude','longitude','address','category','icon','color','link_url','object_type','object_id','is_active'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		if(isset($d['meta']))$u['meta']=wp_json_encode($d['meta']);
		return $wpdb->update($wpdb->prefix.self::TABLE,$u,['id'=>$id])!==false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}

	public static function deleteByObject(string $objectType,int $objectId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['object_type'=>$objectType,'object_id'=>$objectId])!==false;
	}

	public static function toGeoJson(array $pins): array {
		$features=[];
		foreach($pins as $p){
			$features[]=[
				'type'=>'Feature',
				'geometry'=>['type'=>'Point','coordinates'=>[(float)$p['longitude'],(float)$p['latitude']]],
				'properties'=>['id'=>$p['id'],'title'=>$p['title'],'description'=>$p['description']??'','category'=>$p['category']??'','icon'=>$p['icon']??'','color'=>$p['color']??'','link'=>$p['link_url']??'']
			];
		}
		return['type'=>'FeatureCollection','features'=>$features];
	}
}
