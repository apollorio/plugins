<?php
declare(strict_types=1);
namespace Apollo\Modules\Moderation;

final class PendingUsersRepository {
	private const TABLE='apollo_pending_users';

	public static function add(int $userId,string $reason='manual_approval',?array $data=null): bool {
		global $wpdb;
		return $wpdb->replace($wpdb->prefix.self::TABLE,[
			'user_id'=>$userId,
			'reason'=>sanitize_key($reason),
			'submitted_data'=>$data?wp_json_encode($data):null,
			'status'=>'pending'
		])!==false;
	}

	public static function approve(int $userId,?string $notes=null): bool {
		global $wpdb;
		$result=$wpdb->update($wpdb->prefix.self::TABLE,[
			'status'=>'approved',
			'reviewed_by'=>get_current_user_id(),
			'review_notes'=>$notes?sanitize_textarea_field($notes):null,
			'reviewed_at'=>current_time('mysql')
		],['user_id'=>$userId,'status'=>'pending']);
		if($result){
			wp_update_user(['ID'=>$userId,'role'=>get_option('default_role','subscriber')]);
			do_action('apollo_user_approved',$userId,get_current_user_id());
		}
		return $result!==false;
	}

	public static function reject(int $userId,?string $notes=null): bool {
		global $wpdb;
		$result=$wpdb->update($wpdb->prefix.self::TABLE,[
			'status'=>'rejected',
			'reviewed_by'=>get_current_user_id(),
			'review_notes'=>$notes?sanitize_textarea_field($notes):null,
			'reviewed_at'=>current_time('mysql')
		],['user_id'=>$userId,'status'=>'pending']);
		if($result){
			do_action('apollo_user_rejected',$userId,get_current_user_id(),$notes);
		}
		return $result!==false;
	}

	public static function isPending(int $userId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d AND status='pending'",$userId
		));
	}

	public static function getStatus(int $userId): ?string {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d",$userId));
	}

	public static function getInfo(int $userId): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d",$userId),ARRAY_A);
		if($r)$r['submitted_data']=json_decode($r['submitted_data']??'',true);
		return $r?:null;
	}

	public static function getPending(int $limit=50,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT p.*,u.display_name,u.user_email,u.user_registered
			 FROM {$wpdb->prefix}".self::TABLE." p
			 LEFT JOIN {$wpdb->users} u ON u.ID=p.user_id
			 WHERE p.status='pending' ORDER BY p.created_at ASC LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function countPending(): int {
		global $wpdb;
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." WHERE status='pending'");
	}

	public static function delete(int $userId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['user_id'=>$userId])!==false;
	}
}
