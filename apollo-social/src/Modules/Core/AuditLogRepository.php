<?php
declare(strict_types=1);
namespace Apollo\Modules\Core;

final class AuditLogRepository {
	private const TABLE='apollo_audit_logs';

	public static function log(string $action, int $userId=0, string $objectType='', int $objectId=0, array $data=[], string $ip=''): int|false {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$wpdb->insert($t,[
			'user_id'=>$userId,
			'action'=>$action,
			'object_type'=>$objectType,
			'object_id'=>$objectId,
			'data'=>json_encode($data),
			'ip_address'=>$ip?:($_SERVER['REMOTE_ADDR']??''),
			'user_agent'=>\substr($_SERVER['HTTP_USER_AGENT']??'',0,500),
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%s','%d','%s','%s','%s','%s']);
		return $wpdb->insert_id?:false;
	}

	public static function logLogin(int $userId, bool $success, string $method='password'): int|false {
		return self::log($success?'login_success':'login_failed',$userId,'user',$userId,[
			'method'=>$method,
			'success'=>$success
		]);
	}

	public static function logLogout(int $userId): int|false {
		return self::log('logout',$userId,'user',$userId,[]);
	}

	public static function logPasswordChange(int $userId, bool $byAdmin=false): int|false {
		return self::log('password_change',$userId,'user',$userId,['by_admin'=>$byAdmin]);
	}

	public static function logEmailChange(int $userId, string $oldEmail, string $newEmail): int|false {
		return self::log('email_change',$userId,'user',$userId,['old'=>$oldEmail,'new'=>$newEmail]);
	}

	public static function logProfileUpdate(int $userId, array $changedFields): int|false {
		return self::log('profile_update',$userId,'user',$userId,['fields'=>$changedFields]);
	}

	public static function logContentCreated(int $userId, string $type, int $contentId): int|false {
		return self::log('content_created',$userId,$type,$contentId,[]);
	}

	public static function logContentDeleted(int $userId, string $type, int $contentId, array $snapshot=[]): int|false {
		return self::log('content_deleted',$userId,$type,$contentId,['snapshot'=>$snapshot]);
	}

	public static function logModerationAction(int $moderatorId, string $action, string $targetType, int $targetId, string $reason=''): int|false {
		return self::log('moderation_'.$action,$moderatorId,$targetType,$targetId,['reason'=>$reason]);
	}

	public static function logAdminAction(int $adminId, string $action, array $details=[]): int|false {
		return self::log('admin_'.$action,$adminId,'admin',0,$details);
	}

	public static function logSecurityEvent(int $userId, string $event, array $details=[]): int|false {
		return self::log('security_'.$event,$userId,'security',0,$details);
	}

	public static function get(int $logId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$logId),ARRAY_A);
	}

	public static function getByUser(int $userId, int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getByAction(string $action, int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT l.*,u.display_name FROM {$t} l LEFT JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE l.action=%s ORDER BY l.created_at DESC LIMIT %d OFFSET %d",
			$action,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getByObject(string $objectType, int $objectId, int $limit=50): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT l.*,u.display_name FROM {$t} l LEFT JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE l.object_type=%s AND l.object_id=%d ORDER BY l.created_at DESC LIMIT %d",
			$objectType,$objectId,$limit
		),ARRAY_A)??[];
	}

	public static function search(array $filters, int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$where=['1=1'];
		$args=[];
		if(!empty($filters['user_id'])){
			$where[]='user_id=%d';
			$args[]=$filters['user_id'];
		}
		if(!empty($filters['action'])){
			$where[]='action=%s';
			$args[]=$filters['action'];
		}
		if(!empty($filters['action_like'])){
			$where[]='action LIKE %s';
			$args[]=$filters['action_like'].'%';
		}
		if(!empty($filters['object_type'])){
			$where[]='object_type=%s';
			$args[]=$filters['object_type'];
		}
		if(!empty($filters['ip_address'])){
			$where[]='ip_address=%s';
			$args[]=$filters['ip_address'];
		}
		if(!empty($filters['date_from'])){
			$where[]='created_at>=%s';
			$args[]=$filters['date_from'];
		}
		if(!empty($filters['date_to'])){
			$where[]='created_at<=%s';
			$args[]=$filters['date_to'];
		}
		$whereStr=\implode(' AND ',$where);
		$args[]=$limit;
		$args[]=$offset;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT l.*,u.display_name FROM {$t} l LEFT JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE {$whereStr} ORDER BY l.created_at DESC LIMIT %d OFFSET %d",
			...$args
		),ARRAY_A)??[];
	}

	public static function getLoginHistory(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d AND action IN('login_success','login_failed','logout') ORDER BY created_at DESC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getSecurityEvents(int $limit=100): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT l.*,u.display_name FROM {$t} l LEFT JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE l.action LIKE 'security_%' OR l.action='login_failed' ORDER BY l.created_at DESC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getStats(int $days=30): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$days} days"));
		$actions=$wpdb->get_results($wpdb->prepare(
			"SELECT action,COUNT(*) as count FROM {$t} WHERE created_at>=%s GROUP BY action ORDER BY count DESC LIMIT 20",
			$since
		),ARRAY_A)??[];
		$daily=$wpdb->get_results($wpdb->prepare(
			"SELECT DATE(created_at) as date,COUNT(*) as count FROM {$t} WHERE created_at>=%s GROUP BY DATE(created_at) ORDER BY date ASC",
			$since
		),ARRAY_A)??[];
		return [
			'total'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE created_at>=%s",$since)),
			'failed_logins'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE action='login_failed' AND created_at>=%s",$since)),
			'by_action'=>$actions,
			'daily'=>$daily
		];
	}

	public static function getUniqueActions(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_col("SELECT DISTINCT action FROM {$t} ORDER BY action ASC");
	}

	public static function getSuspiciousActivity(int $threshold=5, int $minutes=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$minutes} minutes"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT ip_address,COUNT(*) as attempts FROM {$t} WHERE action='login_failed' AND created_at>=%s GROUP BY ip_address HAVING attempts>=%d ORDER BY attempts DESC",
			$since,$threshold
		),ARRAY_A)??[];
	}

	public static function cleanup(int $olderThanDays=365): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$threshold=gmdate('Y-m-d H:i:s',strtotime("-{$olderThanDays} days"));
		return (int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE created_at<%s",$threshold));
	}
}
