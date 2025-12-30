<?php
declare(strict_types=1);
namespace Apollo\Modules\Testimonials;

final class TestimonialsRepository {
	private const TABLE='apollo_testimonials';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'author_name'=>sanitize_text_field($d['author_name']),
			'author_title'=>sanitize_text_field($d['author_title']??''),
			'author_company'=>sanitize_text_field($d['author_company']??''),
			'author_image'=>isset($d['author_image'])?esc_url_raw($d['author_image']):null,
			'user_id'=>isset($d['user_id'])?(int)$d['user_id']:null,
			'content'=>wp_kses_post($d['content']),
			'rating'=>max(1,min(5,(int)($d['rating']??5))),
			'category'=>sanitize_key($d['category']??''),
			'product_id'=>isset($d['product_id'])?(int)$d['product_id']:null,
			'video_url'=>isset($d['video_url'])?esc_url_raw($d['video_url']):null,
			'is_featured'=>(int)($d['is_featured']??0),
			'status'=>in_array($d['status']??'',['pending','approved','rejected'])?$d['status']:'pending',
			'sort_order'=>(int)($d['sort_order']??0)
		]);
		return (int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A)?:null;
	}

	public static function getApproved(int $limit=20,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE status='approved' ORDER BY sort_order,created_at DESC LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function getFeatured(int $limit=10): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE status='approved' AND is_featured=1 ORDER BY sort_order,created_at DESC LIMIT %d",$limit
		),ARRAY_A)?:[];
	}

	public static function getByCategory(string $category,int $limit=20,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE status='approved' AND category=%s ORDER BY sort_order,created_at DESC LIMIT %d OFFSET %d",
			$category,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function getPending(int $limit=50,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE status='pending' ORDER BY created_at LIMIT %d OFFSET %d",$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function countPending(): int {
		global $wpdb;
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." WHERE status='pending'");
	}

	public static function approve(int $id): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['status'=>'approved'],['id'=>$id])!==false;
	}

	public static function reject(int $id): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['status'=>'rejected'],['id'=>$id])!==false;
	}

	public static function getAverageRating(?string $category=null): float {
		global $wpdb;
		$w=$category?"AND category='{$wpdb->_real_escape($category)}'":'';
		return (float)$wpdb->get_var("SELECT AVG(rating) FROM {$wpdb->prefix}".self::TABLE." WHERE status='approved' {$w}");
	}

	public static function getCategories(): array {
		global $wpdb;
		return $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}".self::TABLE." WHERE category!='' AND status='approved' ORDER BY category")?:[];
	}

	public static function update(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['author_name','author_title','author_company','author_image','user_id','content','rating','category','product_id','video_url','is_featured','status','sort_order'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		return $wpdb->update($wpdb->prefix.self::TABLE,$u,['id'=>$id])!==false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}

	public static function reorder(array $ids): bool {
		global $wpdb;
		foreach($ids as $order=>$id){
			$wpdb->update($wpdb->prefix.self::TABLE,['sort_order'=>$order],['id'=>(int)$id]);
		}
		return true;
	}
}
