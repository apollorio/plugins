<?php
declare(strict_types=1);
namespace Apollo\Modules\Referral;

final class ReferralRepository {
	private const T_REFERRALS='apollo_referrals';
	private const T_REWARDS='apollo_referral_rewards';
	private const T_SETTINGS='apollo_referral_settings';
	
	public static function generateCode(int $userId): string {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		$existing=$wpdb->get_var($wpdb->prepare("SELECT referral_code FROM {$t} WHERE referrer_id=%d LIMIT 1",$userId));
		if($existing)return $existing;
		do{
			$code=strtoupper(substr(md5((string)$userId.time().wp_rand()),0,8));
			$codeExists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE referral_code=%s",$code));
		}while($codeExists);
		update_user_meta($userId,'apollo_referral_code',$code);
		return $code;
	}

	public static function getUserCode(int $userId): string {
		$code=get_user_meta($userId,'apollo_referral_code',true);
		if(!$code)$code=self::generateCode($userId);
		return $code;
	}

	public static function getReferralLink(int $userId): string {
		$code=self::getUserCode($userId);
		return add_query_arg('ref',$code,home_url('/register'));
	}

	public static function recordReferral(int $referrerId, int $referredId, string $code=''): int|false {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		if($referrerId===$referredId)return false;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE referred_id=%d",$referredId));
		if($exists)return false;
		$wpdb->insert($t,[
			'referrer_id'=>$referrerId,
			'referred_id'=>$referredId,
			'referral_code'=>$code?:self::getUserCode($referrerId),
			'status'=>'pending',
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s','%s','%s']);
		return $wpdb->insert_id?:false;
	}

	public static function completeReferral(int $referredId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		$referral=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE referred_id=%d AND status='pending'",$referredId),ARRAY_A);
		if(!$referral)return false;
		$wpdb->update($t,[
			'status'=>'completed',
			'completed_at'=>gmdate('Y-m-d H:i:s')
		],['id'=>$referral['id']],['%s','%s'],['%d']);
		self::awardPoints((int)$referral['referrer_id'],(int)$referral['referred_id']);
		return true;
	}

	private static function awardPoints(int $referrerId, int $referredId): void {
		$referrerPoints=(int)get_option('apollo_referral_referrer_points',100);
		$referredPoints=(int)get_option('apollo_referral_referred_points',50);
		if($referrerPoints>0){
			global $wpdb;
			$p=$wpdb->prefix.'apollo_points';
			$wpdb->query($wpdb->prepare("INSERT INTO {$p} (user_id,points) VALUES(%d,%d) ON DUPLICATE KEY UPDATE points=points+%d",$referrerId,$referrerPoints,$referrerPoints));
			self::recordReward($referrerId,'referral_bonus',$referrerPoints,$referredId);
		}
		if($referredPoints>0){
			global $wpdb;
			$p=$wpdb->prefix.'apollo_points';
			$wpdb->query($wpdb->prepare("INSERT INTO {$p} (user_id,points) VALUES(%d,%d) ON DUPLICATE KEY UPDATE points=points+%d",$referredId,$referredPoints,$referredPoints));
			self::recordReward($referredId,'signup_bonus',$referredPoints,$referrerId);
		}
	}

	private static function recordReward(int $userId, string $type, int $amount, int $relatedUserId=0): void {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REWARDS;
		$wpdb->insert($r,[
			'user_id'=>$userId,
			'reward_type'=>$type,
			'amount'=>$amount,
			'related_user_id'=>$relatedUserId,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%d','%d','%s']);
	}

	public static function findByCode(string $code): ?int {
		global $wpdb;
		$userId=$wpdb->get_var($wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='apollo_referral_code' AND meta_value=%s",$code
		));
		return $userId?(int)$userId:null;
	}

	public static function getReferrals(int $userId, string $status='', int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		if($status){
			return $wpdb->get_results($wpdb->prepare(
				"SELECT r.*,u.display_name as referred_name,u.user_registered FROM {$t} r JOIN {$wpdb->users} u ON r.referred_id=u.ID WHERE r.referrer_id=%d AND r.status=%s ORDER BY r.created_at DESC LIMIT %d OFFSET %d",
				$userId,$status,$limit,$offset
			),ARRAY_A)??[];
		}
		return $wpdb->get_results($wpdb->prepare(
			"SELECT r.*,u.display_name as referred_name,u.user_registered FROM {$t} r JOIN {$wpdb->users} u ON r.referred_id=u.ID WHERE r.referrer_id=%d ORDER BY r.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getReferralCount(int $userId, string $status=''): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		if($status){
			return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE referrer_id=%d AND status=%s",$userId,$status));
		}
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE referrer_id=%d",$userId));
	}

	public static function getStats(int $userId): array {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REWARDS;
		return [
			'code'=>self::getUserCode($userId),
			'link'=>self::getReferralLink($userId),
			'total_referrals'=>self::getReferralCount($userId),
			'completed'=>self::getReferralCount($userId,'completed'),
			'pending'=>self::getReferralCount($userId,'pending'),
			'total_earned'=>(int)$wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$r} WHERE user_id=%d AND reward_type='referral_bonus'",$userId)),
			'referred_by'=>self::getReferredBy($userId)
		];
	}

	public static function getReferredBy(int $userId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT r.*,u.display_name as referrer_name FROM {$t} r JOIN {$wpdb->users} u ON r.referrer_id=u.ID WHERE r.referred_id=%d",
			$userId
		),ARRAY_A);
	}

	public static function getTopReferrers(int $limit=10, int $days=30): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$days} days"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT r.referrer_id,u.display_name,COUNT(*) as referral_count FROM {$t} r JOIN {$wpdb->users} u ON r.referrer_id=u.ID WHERE r.status='completed' AND r.completed_at>=%s GROUP BY r.referrer_id ORDER BY referral_count DESC LIMIT %d",
			$since,$limit
		),ARRAY_A)??[];
	}

	public static function getRewards(int $userId, int $limit=50): array {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REWARDS;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT rw.*,u.display_name as related_user_name FROM {$r} rw LEFT JOIN {$wpdb->users} u ON rw.related_user_id=u.ID WHERE rw.user_id=%d ORDER BY rw.created_at DESC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getTotalRewards(int $userId): int {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REWARDS;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$r} WHERE user_id=%d",$userId));
	}

	public static function getGlobalStats(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		$r=$wpdb->prefix.self::T_REWARDS;
		return [
			'total_referrals'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}"),
			'completed_referrals'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE status=%s",'completed')),
			'total_rewards_distributed'=>(int)$wpdb->get_var("SELECT SUM(amount) FROM {$r}"),
			'active_referrers'=>(int)$wpdb->get_var("SELECT COUNT(DISTINCT referrer_id) FROM {$t}"),
			'top_referrers'=>self::getTopReferrers(5,30)
		];
	}

	public static function isReferredUser(int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE referred_id=%d",$userId));
	}

	public static function cancelReferral(int $referralId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_REFERRALS;
		return (bool)$wpdb->update($t,['status'=>'cancelled'],['id'=>$referralId,'status'=>'pending'],['%s'],['%d','%s']);
	}
}
