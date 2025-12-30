<?php
declare(strict_types=1);
namespace Apollo\Modules\Subscriptions;

final class SubscriptionsRepository {
	private const TABLE='apollo_subscriptions';
	private const PLANS='apollo_subscription_plans';
	private const HISTORY='apollo_subscription_history';

	public static function createPlan(array $data): int {
		global $wpdb;
		$t=$wpdb->prefix.self::PLANS;
		$wpdb->insert($t,[
			'name'=>sanitize_text_field($data['name']),
			'slug'=>sanitize_title($data['name']),
			'description'=>wp_kses_post($data['description']??''),
			'price'=>(float)($data['price']??0),
			'currency'=>$data['currency']??'BRL',
			'duration_days'=>(int)($data['duration_days']??30),
			'features'=>wp_json_encode($data['features']??[]),
			'limits'=>wp_json_encode($data['limits']??[]),
			'is_active'=>1,
			'sort_order'=>(int)($data['sort_order']??0),
			'created_at'=>current_time('mysql')
		],['%s','%s','%s','%f','%s','%d','%s','%s','%d','%d','%s']);
		return (int)$wpdb->insert_id;
	}

	public static function updatePlan(int $planId, array $data): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::PLANS;
		$upd=[];$fmt=[];
		if(isset($data['name'])){$upd['name']=sanitize_text_field($data['name']);$fmt[]='%s';}
		if(isset($data['description'])){$upd['description']=wp_kses_post($data['description']);$fmt[]='%s';}
		if(isset($data['price'])){$upd['price']=(float)$data['price'];$fmt[]='%f';}
		if(isset($data['duration_days'])){$upd['duration_days']=(int)$data['duration_days'];$fmt[]='%d';}
		if(isset($data['features'])){$upd['features']=wp_json_encode($data['features']);$fmt[]='%s';}
		if(isset($data['limits'])){$upd['limits']=wp_json_encode($data['limits']);$fmt[]='%s';}
		if(isset($data['is_active'])){$upd['is_active']=(int)$data['is_active'];$fmt[]='%d';}
		return $wpdb->update($t,$upd,['id'=>$planId],$fmt,['%d'])!==false;
	}

	public static function getPlans(bool $activeOnly=true): array {
		global $wpdb;
		$t=$wpdb->prefix.self::PLANS;
		$where=$activeOnly?'WHERE is_active=1':'';
		$rows=$wpdb->get_results("SELECT * FROM {$t} {$where} ORDER BY sort_order ASC, price ASC",ARRAY_A)??[];
		return array_map(fn($r)=>self::decodePlan($r),$rows);
	}

	public static function getPlan(int $planId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::PLANS;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$planId),ARRAY_A);
		return $row?self::decodePlan($row):null;
	}

	public static function subscribe(int $userId, int $planId, string $paymentMethod='', string $transactionId=''): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$plan=self::getPlan($planId);
		if(!$plan){return 0;}
		self::cancelActive($userId);
		$startDate=current_time('mysql');
		$endDate=gmdate('Y-m-d H:i:s',strtotime("+{$plan['duration_days']} days"));
		$wpdb->insert($t,[
			'user_id'=>$userId,
			'plan_id'=>$planId,
			'status'=>'active',
			'start_date'=>$startDate,
			'end_date'=>$endDate,
			'payment_method'=>$paymentMethod,
			'transaction_id'=>$transactionId,
			'amount_paid'=>$plan['price'],
			'currency'=>$plan['currency'],
			'auto_renew'=>1,
			'created_at'=>$startDate
		],['%d','%d','%s','%s','%s','%s','%s','%f','%s','%d','%s']);
		$subId=(int)$wpdb->insert_id;
		if($subId){
			self::logHistory($subId,'subscribed',"Assinatura iniciada: {$plan['name']}");
			update_user_meta($userId,'apollo_subscription_id',$subId);
			update_user_meta($userId,'apollo_subscription_plan',$planId);
			do_action('apollo_user_subscribed',$userId,$planId,$subId);
		}
		return $subId;
	}

	public static function getActive(int $userId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$p=$wpdb->prefix.self::PLANS;
		$now=current_time('mysql');
		$row=$wpdb->get_row($wpdb->prepare(
			"SELECT s.*,p.name as plan_name,p.features,p.limits FROM {$t} s JOIN {$p} p ON s.plan_id=p.id WHERE s.user_id=%d AND s.status='active' AND s.end_date>=%s ORDER BY s.id DESC LIMIT 1",
			$userId,$now
		),ARRAY_A);
		return $row?self::decodeSubscription($row):null;
	}

	public static function isSubscribed(int $userId): bool {
		return self::getActive($userId)!==null;
	}

	public static function hasPlan(int $userId, int $planId): bool {
		$active=self::getActive($userId);
		return $active&&(int)$active['plan_id']===$planId;
	}

	public static function cancel(int $userId, string $reason=''): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$active=self::getActive($userId);
		if(!$active){return false;}
		$wpdb->update($t,['status'=>'cancelled','auto_renew'=>0,'cancelled_at'=>current_time('mysql')],['id'=>(int)$active['id']],['%s','%d','%s'],['%d']);
		self::logHistory((int)$active['id'],'cancelled',$reason?:"Cancelamento solicitado");
		delete_user_meta($userId,'apollo_subscription_id');
		delete_user_meta($userId,'apollo_subscription_plan');
		do_action('apollo_subscription_cancelled',$userId,(int)$active['plan_id']);
		return true;
	}

	public static function pause(int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$active=self::getActive($userId);
		if(!$active){return false;}
		$wpdb->update($t,['status'=>'paused','paused_at'=>current_time('mysql')],['id'=>(int)$active['id']],['%s','%s'],['%d']);
		self::logHistory((int)$active['id'],'paused','Assinatura pausada');
		return true;
	}

	public static function resume(int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE user_id=%d AND status='paused' ORDER BY id DESC LIMIT 1",$userId),ARRAY_A);
		if(!$row){return false;}
		$pausedAt=strtotime($row['paused_at']);
		$remaining=strtotime($row['end_date'])-$pausedAt;
		$newEnd=gmdate('Y-m-d H:i:s',time()+$remaining);
		$wpdb->update($t,['status'=>'active','end_date'=>$newEnd,'paused_at'=>null],['id'=>(int)$row['id']],['%s','%s','%s'],['%d']);
		self::logHistory((int)$row['id'],'resumed','Assinatura retomada');
		return true;
	}

	public static function renew(int $userId): bool {
		$active=self::getActive($userId);
		if(!$active||!$active['auto_renew']){return false;}
		return self::subscribe($userId,(int)$active['plan_id'],$active['payment_method'],'renewal_'.uniqid())>0;
	}

	public static function upgrade(int $userId, int $newPlanId): bool {
		$active=self::getActive($userId);
		if(!$active){return self::subscribe($userId,$newPlanId)>0;}
		$oldPlan=self::getPlan((int)$active['plan_id']);
		$newPlan=self::getPlan($newPlanId);
		if(!$newPlan||$newPlan['price']<=$oldPlan['price']){return false;}
		self::logHistory((int)$active['id'],'upgraded',"Upgrade: {$oldPlan['name']} -> {$newPlan['name']}");
		return self::subscribe($userId,$newPlanId)>0;
	}

	public static function getDaysRemaining(int $userId): int {
		$active=self::getActive($userId);
		if(!$active){return 0;}
		$remaining=strtotime($active['end_date'])-time();
		return max(0,(int)floor($remaining/86400));
	}

	public static function checkFeature(int $userId, string $feature): bool {
		$active=self::getActive($userId);
		if(!$active){return false;}
		$features=$active['features']??[];
		return in_array($feature,$features,true);
	}

	public static function getLimit(int $userId, string $limitKey): int {
		$active=self::getActive($userId);
		if(!$active){return 0;}
		$limits=$active['limits']??[];
		return (int)($limits[$limitKey]??0);
	}

	public static function getHistory(int $userId, int $limit=50): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$h=$wpdb->prefix.self::HISTORY;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT h.*,s.plan_id FROM {$h} h JOIN {$t} s ON h.subscription_id=s.id WHERE s.user_id=%d ORDER BY h.created_at DESC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function processExpiring(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$threshold=gmdate('Y-m-d H:i:s',strtotime('+3 days'));
		$expiring=$wpdb->get_results($wpdb->prepare(
			"SELECT s.*,u.user_email FROM {$t} s JOIN {$wpdb->users} u ON s.user_id=u.ID WHERE s.status='active' AND s.end_date<=%s AND s.renewal_notified=0",
			$threshold
		),ARRAY_A)??[];
		$count=0;
		foreach($expiring as $sub){
			do_action('apollo_subscription_expiring',(int)$sub['user_id'],(int)$sub['id'],$sub['user_email']);
			$wpdb->update($t,['renewal_notified'=>1],['id'=>(int)$sub['id']],['%d'],['%d']);
			$count++;
		}
		return $count;
	}

	public static function processExpired(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$now=current_time('mysql');
		$expired=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE status='active' AND end_date<%s",
			$now
		),ARRAY_A)??[];
		$count=0;
		foreach($expired as $sub){
			if($sub['auto_renew']){
				self::renew((int)$sub['user_id']);
			}else{
				$wpdb->update($t,['status'=>'expired'],['id'=>(int)$sub['id']],['%s'],['%d']);
				self::logHistory((int)$sub['id'],'expired','Assinatura expirada');
				delete_user_meta((int)$sub['user_id'],'apollo_subscription_id');
				delete_user_meta((int)$sub['user_id'],'apollo_subscription_plan');
				do_action('apollo_subscription_expired',(int)$sub['user_id'],(int)$sub['plan_id']);
			}
			$count++;
		}
		return $count;
	}

	public static function getStats(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$active=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='active'");
		$cancelled=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='cancelled'");
		$expired=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='expired'");
		$revenue=(float)$wpdb->get_var("SELECT SUM(amount_paid) FROM {$t} WHERE status IN('active','expired')");
		$monthlyRevenue=(float)$wpdb->get_var($wpdb->prepare(
			"SELECT SUM(amount_paid) FROM {$t} WHERE created_at>=%s",
			gmdate('Y-m-01 00:00:00')
		));
		return compact('active','cancelled','expired','revenue','monthlyRevenue');
	}

	private static function cancelActive(int $userId): void {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$wpdb->update($t,['status'=>'replaced'],['user_id'=>$userId,'status'=>'active'],['%s'],['%d','%s']);
	}

	private static function logHistory(int $subId, string $action, string $details=''): void {
		global $wpdb;
		$t=$wpdb->prefix.self::HISTORY;
		$wpdb->insert($t,['subscription_id'=>$subId,'action'=>$action,'details'=>$details,'created_at'=>current_time('mysql')],['%d','%s','%s','%s']);
	}

	private static function decodePlan(array $row): array {
		$row['features']=json_decode($row['features']??'[]',true)??[];
		$row['limits']=json_decode($row['limits']??'{}',true)??[];
		return $row;
	}

	private static function decodeSubscription(array $row): array {
		$row['features']=json_decode($row['features']??'[]',true)??[];
		$row['limits']=json_decode($row['limits']??'{}',true)??[];
		return $row;
	}
}
