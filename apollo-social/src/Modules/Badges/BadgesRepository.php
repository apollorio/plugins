<?php
declare(strict_types=1);
namespace Apollo\Modules\Badges;

final class BadgesRepository {
	private const TABLE='apollo_badges';
	private const USER_TABLE='apollo_user_badges';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'name'=>sanitize_text_field($d['name']),
			'slug'=>sanitize_title($d['slug']??$d['name']),
			'description'=>sanitize_textarea_field($d['description']??''),
			'icon'=>sanitize_text_field($d['icon']??''),
			'image_url'=>isset($d['image_url'])?esc_url_raw($d['image_url']):null,
			'color'=>sanitize_hex_color($d['color']??'#3498db')?:'#3498db',
			'category'=>sanitize_key($d['category']??''),
			'rarity'=>in_array($d['rarity']??'',['common','uncommon','rare','epic','legendary'])?$d['rarity']:'common',
			'points_value'=>(int)($d['points_value']??0),
			'is_active'=>(int)($d['is_active']??1),
			'sort_order'=>(int)($d['sort_order']??0)
		]);
		return(int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A)?:null;
	}

	public static function getBySlug(string $slug): ?array {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE slug=%s",$slug),ARRAY_A)?:null;
	}

	public static function getAll(bool $activeOnly=true): array {
		global $wpdb;
		$w=$activeOnly?'WHERE is_active=1':'';
		return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".self::TABLE." {$w} ORDER BY sort_order,name",ARRAY_A)?:[];
	}

	public static function getByCategory(string $category,bool $activeOnly=true): array {
		global $wpdb;
		$w=$activeOnly?'AND is_active=1':'';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE category=%s {$w} ORDER BY sort_order,name",$category
		),ARRAY_A)?:[];
	}

	public static function awardToUser(int $userId,int $badgeId,?string $reason=null): bool {
		global $wpdb;
		$exists=(bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}".self::USER_TABLE." WHERE user_id=%d AND badge_id=%d",$userId,$badgeId
		));
		if($exists)return true;
		$result=$wpdb->insert($wpdb->prefix.self::USER_TABLE,[
			'user_id'=>$userId,'badge_id'=>$badgeId,'awarded_reason'=>$reason?sanitize_text_field($reason):null
		]);
		if($result){
			$badge=self::get($badgeId);
			if($badge&&$badge['points_value']>0){
				\Apollo\Modules\Gamification\PointsRepository::add($userId,(int)$badge['points_value'],'badge_awarded','default',null,null,'Earned badge: '.$badge['name']);
			}
			do_action('apollo_badge_awarded',$userId,$badgeId);
		}
		return $result!==false;
	}

	public static function revokeFromUser(int $userId,int $badgeId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::USER_TABLE,['user_id'=>$userId,'badge_id'=>$badgeId])!==false;
	}

	public static function getUserBadges(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT b.*,ub.awarded_at,ub.awarded_reason FROM {$wpdb->prefix}".self::TABLE." b
			 INNER JOIN {$wpdb->prefix}".self::USER_TABLE." ub ON ub.badge_id=b.id
			 WHERE ub.user_id=%d ORDER BY ub.awarded_at DESC",
			$userId
		),ARRAY_A)?:[];
	}

	public static function hasBadge(int $userId,int $badgeId): bool {
		global $wpdb;
		return(bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}".self::USER_TABLE." WHERE user_id=%d AND badge_id=%d",$userId,$badgeId
		));
	}

	public static function countUserBadges(int $userId): int {
		global $wpdb;
		return(int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::USER_TABLE." WHERE user_id=%d",$userId
		));
	}

	public static function getUsersWithBadge(int $badgeId,int $limit=100,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT u.*,ub.awarded_at FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->prefix}".self::USER_TABLE." ub ON ub.user_id=u.ID
			 WHERE ub.badge_id=%d ORDER BY ub.awarded_at DESC LIMIT %d OFFSET %d",
			$badgeId,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function getCategories(): array {
		global $wpdb;
		return $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}".self::TABLE." WHERE category!='' AND is_active=1 ORDER BY category")?:[];
	}

	public static function update(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['name','slug','description','icon','image_url','color','category','rarity','points_value','is_active','sort_order'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		return $wpdb->update($wpdb->prefix.self::TABLE,$u,['id'=>$id])!==false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::USER_TABLE,['badge_id'=>$id]);
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}
}
