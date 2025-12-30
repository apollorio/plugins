<?php
declare(strict_types=1);
namespace Apollo\Modules\Moderation;

final class SpammerRepository {
	private const TABLE='apollo_spammer_list';

	public static function markAsSpammer(int $userId,string $reason='',?array $evidence=null): bool {
		if($userId<=0)return false;
		global $wpdb;
		$wpdb->replace($wpdb->prefix.self::TABLE,[
			'user_id'=>$userId,
			'marked_by'=>get_current_user_id(),
			'reason'=>sanitize_textarea_field($reason),
			'evidence'=>$evidence?wp_json_encode($evidence):null
		]);
		update_user_meta($userId,'apollo_is_spammer',1);
		do_action('apollo_user_marked_spammer',$userId,get_current_user_id(),$reason);
		return true;
	}

	public static function unmarkSpammer(int $userId): bool {
		global $wpdb;
		delete_user_meta($userId,'apollo_is_spammer');
		do_action('apollo_user_unmarked_spammer',$userId,get_current_user_id());
		return $wpdb->delete($wpdb->prefix.self::TABLE,['user_id'=>$userId])!==false;
	}

	public static function isSpammer(int $userId): bool {
		return (bool)get_user_meta($userId,'apollo_is_spammer',true);
	}

	public static function getSpammerInfo(int $userId): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d",$userId),ARRAY_A);
		if($r)$r['evidence']=json_decode($r['evidence']??'',true);
		return $r?:null;
	}

	public static function getAll(int $limit=100,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT s.*,u.display_name,u.user_email,m.display_name as marked_by_name
			 FROM {$wpdb->prefix}".self::TABLE." s
			 LEFT JOIN {$wpdb->users} u ON u.ID=s.user_id
			 LEFT JOIN {$wpdb->users} m ON m.ID=s.marked_by
			 ORDER BY s.marked_at DESC LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function count(): int {
		global $wpdb;
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE);
	}
}
