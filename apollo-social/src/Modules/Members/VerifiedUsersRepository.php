<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class VerifiedUsersRepository {

	public static function verify(int $userId,?string $reason=null,?string $badge=null): bool {
		if($userId<=0)return false;
		update_user_meta($userId,'apollo_verified',1);
		update_user_meta($userId,'apollo_verified_at',current_time('mysql'));
		update_user_meta($userId,'apollo_verified_by',get_current_user_id());
		if($reason)update_user_meta($userId,'apollo_verified_reason',$reason);
		if($badge)update_user_meta($userId,'apollo_verified_badge',$badge);
		do_action('apollo_user_verified',$userId,get_current_user_id());
		return true;
	}

	public static function unverify(int $userId): bool {
		delete_user_meta($userId,'apollo_verified');
		delete_user_meta($userId,'apollo_verified_at');
		delete_user_meta($userId,'apollo_verified_by');
		delete_user_meta($userId,'apollo_verified_reason');
		delete_user_meta($userId,'apollo_verified_badge');
		do_action('apollo_user_unverified',$userId,get_current_user_id());
		return true;
	}

	public static function isVerified(int $userId): bool {
		return (bool)get_user_meta($userId,'apollo_verified',true);
	}

	public static function getVerificationInfo(int $userId): ?array {
		if(!self::isVerified($userId))return null;
		return[
			'verified_at'=>get_user_meta($userId,'apollo_verified_at',true),
			'verified_by'=>(int)get_user_meta($userId,'apollo_verified_by',true),
			'reason'=>get_user_meta($userId,'apollo_verified_reason',true),
			'badge'=>get_user_meta($userId,'apollo_verified_badge',true)
		];
	}

	public static function getVerifiedUsers(int $limit=100,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT u.* FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} um ON um.user_id=u.ID AND um.meta_key='apollo_verified' AND um.meta_value='1'
			 ORDER BY u.display_name LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function countVerified(): int {
		global $wpdb;
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key='apollo_verified' AND meta_value='1'");
	}

	public static function getVerifiedUserIds(): array {
		global $wpdb;
		return $wpdb->get_col("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='apollo_verified' AND meta_value='1'")?:[];
	}
}
