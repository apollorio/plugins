<?php
declare(strict_types=1);
namespace Apollo\Modules\Adverts;

final class MyAdvertsRepository {
	private const T_ADVERTS='apollo_adverts';
	private const T_FAVORITES='apollo_advert_favorites';
	private const T_VIEWS='apollo_advert_views';
	
	public static function getUserAdverts(int $userId, string $status='', int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		if($status){
			return $wpdb->get_results($wpdb->prepare(
				"SELECT *,(SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_VIEWS." WHERE advert_id=a.id) as views,(SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_FAVORITES." WHERE advert_id=a.id) as saves FROM {$t} a WHERE a.user_id=%d AND a.status=%s ORDER BY a.created_at DESC LIMIT %d OFFSET %d",
				$userId,$status,$limit,$offset
			),ARRAY_A)??[];
		}
		return $wpdb->get_results($wpdb->prepare(
			"SELECT *,(SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_VIEWS." WHERE advert_id=a.id) as views,(SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_FAVORITES." WHERE advert_id=a.id) as saves FROM {$t} a WHERE a.user_id=%d ORDER BY a.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getUserAdvertsCount(int $userId, string $status=''): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		if($status){
			return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND status=%s",$userId,$status));
		}
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d",$userId));
	}

	public static function getUserAdvertStats(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		$v=$wpdb->prefix.self::T_VIEWS;
		$f=$wpdb->prefix.self::T_FAVORITES;
		return [
			'total'=>self::getUserAdvertsCount($userId),
			'active'=>self::getUserAdvertsCount($userId,'active'),
			'pending'=>self::getUserAdvertsCount($userId,'pending'),
			'expired'=>self::getUserAdvertsCount($userId,'expired'),
			'sold'=>self::getUserAdvertsCount($userId,'sold'),
			'total_views'=>(int)$wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$v} WHERE advert_id IN(SELECT id FROM {$t} WHERE user_id=%d)",$userId
			)),
			'total_saves'=>(int)$wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$f} WHERE advert_id IN(SELECT id FROM {$t} WHERE user_id=%d)",$userId
			))
		];
	}

	public static function getFavorites(int $userId, int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		$f=$wpdb->prefix.self::T_FAVORITES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,f.created_at as saved_at FROM {$t} a JOIN {$f} f ON a.id=f.advert_id WHERE f.user_id=%d AND a.status='active' ORDER BY f.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getFavoritesCount(int $userId): int {
		global $wpdb;
		$f=$wpdb->prefix.self::T_FAVORITES;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$f} WHERE user_id=%d",$userId));
	}

	public static function addFavorite(int $userId, int $advertId): bool {
		global $wpdb;
		$f=$wpdb->prefix.self::T_FAVORITES;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$f} WHERE user_id=%d AND advert_id=%d",$userId,$advertId));
		if($exists)return true;
		return (bool)$wpdb->insert($f,[
			'user_id'=>$userId,
			'advert_id'=>$advertId,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s']);
	}

	public static function removeFavorite(int $userId, int $advertId): bool {
		global $wpdb;
		$f=$wpdb->prefix.self::T_FAVORITES;
		return (bool)$wpdb->delete($f,['user_id'=>$userId,'advert_id'=>$advertId],['%d','%d']);
	}

	public static function isFavorite(int $userId, int $advertId): bool {
		global $wpdb;
		$f=$wpdb->prefix.self::T_FAVORITES;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$f} WHERE user_id=%d AND advert_id=%d",$userId,$advertId));
	}

	public static function markAsSold(int $advertId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		return (bool)$wpdb->update($t,
			['status'=>'sold','sold_at'=>gmdate('Y-m-d H:i:s')],
			['id'=>$advertId,'user_id'=>$userId],
			['%s','%s'],['%d','%d']
		);
	}

	public static function republish(int $advertId, int $userId, int $daysActive=30): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		$expiresAt=gmdate('Y-m-d H:i:s',strtotime("+{$daysActive} days"));
		return (bool)$wpdb->update($t,
			['status'=>'active','expires_at'=>$expiresAt,'republished_at'=>gmdate('Y-m-d H:i:s')],
			['id'=>$advertId,'user_id'=>$userId],
			['%s','%s','%s'],['%d','%d']
		);
	}

	public static function pause(int $advertId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		return (bool)$wpdb->update($t,['status'=>'paused'],['id'=>$advertId,'user_id'=>$userId],['%s'],['%d','%d']);
	}

	public static function resume(int $advertId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		return (bool)$wpdb->update($t,['status'=>'active'],['id'=>$advertId,'user_id'=>$userId],['%s'],['%d','%d']);
	}

	public static function getMessages(int $userId, int $limit=50): array {
		global $wpdb;
		$m=$wpdb->prefix.'apollo_advert_messages';
		$t=$wpdb->prefix.self::T_ADVERTS;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT am.*,a.title as advert_title,u.display_name as sender_name FROM {$m} am JOIN {$t} a ON am.advert_id=a.id JOIN {$wpdb->users} u ON am.sender_id=u.ID WHERE a.user_id=%d OR am.sender_id=%d ORDER BY am.created_at DESC LIMIT %d",
			$userId,$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getUnreadMessagesCount(int $userId): int {
		global $wpdb;
		$m=$wpdb->prefix.'apollo_advert_messages';
		$t=$wpdb->prefix.self::T_ADVERTS;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$m} am JOIN {$t} a ON am.advert_id=a.id WHERE a.user_id=%d AND am.is_read=0 AND am.sender_id!=%d",
			$userId,$userId
		));
	}

	public static function getExpiringAdverts(int $userId, int $days=7): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_ADVERTS;
		$threshold=gmdate('Y-m-d H:i:s',strtotime("+{$days} days"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d AND status='active' AND expires_at IS NOT NULL AND expires_at<=%s ORDER BY expires_at ASC",
			$userId,$threshold
		),ARRAY_A)??[];
	}

	public static function getDashboard(int $userId): array {
		return [
			'stats'=>self::getUserAdvertStats($userId),
			'recent'=>self::getUserAdverts($userId,'',5),
			'favorites'=>self::getFavorites($userId,5),
			'expiring'=>self::getExpiringAdverts($userId,7),
			'unread_messages'=>self::getUnreadMessagesCount($userId)
		];
	}
}
