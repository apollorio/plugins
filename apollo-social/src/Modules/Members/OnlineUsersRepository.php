<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class OnlineUsersRepository {
	private const TABLE='apollo_online_users';
	private const THRESHOLD_MINUTES=5;

	public static function updateActivity(int $userId,?string $currentPage=null): bool {
		if($userId<=0)return false;
		global $wpdb;
		return $wpdb->replace($wpdb->prefix.self::TABLE,[
			'user_id'=>$userId,
			'last_activity'=>current_time('mysql'),
			'current_page'=>$currentPage?sanitize_text_field(\substr($currentPage,0,500)):null,
			'ip_address'=>self::getClientIP()
		])!==false;
	}

	private static function getClientIP(): string {
		$keys=['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
		foreach($keys as $k){
			if(!empty($_SERVER[$k])){
				$ip=\explode(',',$_SERVER[$k])[0];
				if(\filter_var(\trim($ip),FILTER_VALIDATE_IP))return \trim($ip);
			}
		}
		return '';
	}

	public static function isOnline(int $userId): bool {
		global $wpdb;
		$threshold=self::THRESHOLD_MINUTES;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}".self::TABLE." WHERE user_id=%d AND last_activity>DATE_SUB(NOW(),INTERVAL %d MINUTE)",
			$userId,$threshold
		));
	}

	public static function getOnlineUsers(int $limit=50): array {
		global $wpdb;
		$threshold=self::THRESHOLD_MINUTES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT o.*,u.display_name,u.user_email FROM {$wpdb->prefix}".self::TABLE." o
			 LEFT JOIN {$wpdb->users} u ON u.ID=o.user_id
			 WHERE o.last_activity>DATE_SUB(NOW(),INTERVAL %d MINUTE) ORDER BY o.last_activity DESC LIMIT %d",
			$threshold,$limit
		),ARRAY_A)?:[];
	}

	public static function getOnlineUserIds(): array {
		global $wpdb;
		$threshold=self::THRESHOLD_MINUTES;
		return $wpdb->get_col($wpdb->prepare(
			"SELECT user_id FROM {$wpdb->prefix}".self::TABLE." WHERE last_activity>DATE_SUB(NOW(),INTERVAL %d MINUTE)",$threshold
		))?:[];
	}

	public static function countOnline(): int {
		global $wpdb;
		$threshold=self::THRESHOLD_MINUTES;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." WHERE last_activity>DATE_SUB(NOW(),INTERVAL %d MINUTE)",$threshold
		));
	}

	public static function getRecentlyActive(int $minutes=60,int $limit=50): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT o.*,u.display_name FROM {$wpdb->prefix}".self::TABLE." o
			 LEFT JOIN {$wpdb->users} u ON u.ID=o.user_id
			 WHERE o.last_activity>DATE_SUB(NOW(),INTERVAL %d MINUTE) ORDER BY o.last_activity DESC LIMIT %d",
			$minutes,$limit
		),ARRAY_A)?:[];
	}

	public static function getOnlineFriends(int $userId,int $limit=20): array {
		global $wpdb;
		$threshold=self::THRESHOLD_MINUTES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT o.*,u.display_name FROM {$wpdb->prefix}".self::TABLE." o
			 LEFT JOIN {$wpdb->users} u ON u.ID=o.user_id
			 INNER JOIN {$wpdb->prefix}apollo_connections c ON
			   ((c.user_id=%d AND c.friend_id=o.user_id) OR (c.friend_id=%d AND c.user_id=o.user_id)) AND c.status='accepted'
			 WHERE o.last_activity>DATE_SUB(NOW(),INTERVAL %d MINUTE) ORDER BY o.last_activity DESC LIMIT %d",
			$userId,$userId,$threshold,$limit
		),ARRAY_A)?:[];
	}

	public static function cleanup(int $olderThanMinutes=1440): int {
		global $wpdb;
		return (int)$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}".self::TABLE." WHERE last_activity<DATE_SUB(NOW(),INTERVAL %d MINUTE)",$olderThanMinutes
		));
	}
}
