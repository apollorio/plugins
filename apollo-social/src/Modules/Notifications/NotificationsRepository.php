<?php
declare(strict_types=1);
namespace Apollo\Modules\Notifications;

final class NotificationsRepository {
	private const TABLE='apollo_notifications';

	public static function create(int $userId, string $type, string $title, array $data=[]): ?int {
		global $wpdb;
		$r=$wpdb->insert($wpdb->prefix.self::TABLE,[
			'user_id'=>$userId,
			'type'=>$type,
			'title'=>$title,
			'content'=>$data['content']??'',
			'link'=>$data['link']??'',
			'actor_id'=>$data['actor_id']??null,
			'object_type'=>$data['object_type']??null,
			'object_id'=>$data['object_id']??null,
			'icon'=>$data['icon']??null,
			'image'=>$data['image']??null,
			'priority'=>$data['priority']??'normal',
			'meta'=>isset($data['meta'])?json_encode($data['meta']):null
		],['%d','%s','%s','%s','%s','%d','%s','%d','%s','%s','%s','%s']);
		if($r){
			self::incrementUnreadCount($userId);
			do_action('apollo_notification_created',$wpdb->insert_id,$userId,$type);
		}
		return $r?(int)$wpdb->insert_id:null;
	}

	public static function notify(int $userId, string $type, string $title, array $data=[]): ?int {
		if(!self::shouldNotify($userId,$type)){return null;}
		$dedup=self::checkDuplicate($userId,$type,$data);
		if($dedup){return null;}
		return self::create($userId,$type,$title,$data);
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$id),ARRAY_A);
		if($row&&$row['meta']){$row['meta']=json_decode($row['meta'],true);}
		return $row;
	}

	public static function getForUser(int $userId, int $limit=20, int $offset=0, bool $unreadOnly=false): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$where='user_id=%d AND is_deleted=0';
		if($unreadOnly){$where.=' AND is_read=0';}
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
		foreach($rows as &$r){if($r['meta']){$r['meta']=json_decode($r['meta'],true);}}
		return $rows;
	}

	public static function markRead(int $id, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$r=$wpdb->update($t,['is_read'=>1,'read_at'=>current_time('mysql')],['id'=>$id,'user_id'=>$userId]);
		if($r){self::decrementUnreadCount($userId);}
		return (bool)$r;
	}

	public static function markAllRead(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND is_read=0",$userId));
		$wpdb->query($wpdb->prepare("UPDATE {$t} SET is_read=1,read_at=%s WHERE user_id=%d AND is_read=0",current_time('mysql'),$userId));
		self::resetUnreadCount($userId);
		return $count;
	}

	public static function delete(int $id, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$notif=$wpdb->get_row($wpdb->prepare("SELECT is_read FROM {$t} WHERE id=%d AND user_id=%d",$id,$userId),ARRAY_A);
		if(!$notif){return false;}
		$r=$wpdb->update($t,['is_deleted'=>1],['id'=>$id,'user_id'=>$userId]);
		if($r&&!$notif['is_read']){self::decrementUnreadCount($userId);}
		return (bool)$r;
	}

	public static function deleteAll(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->query($wpdb->prepare("UPDATE {$t} SET is_deleted=1 WHERE user_id=%d AND is_deleted=0",$userId));
		self::resetUnreadCount($userId);
		return $count;
	}

	public static function getUnreadCount(int $userId): int {
		$count=get_user_meta($userId,'apollo_unread_notifications',true);
		if($count===''){
			$count=self::recalculateUnreadCount($userId);
		}
		return (int)$count;
	}

	private static function recalculateUnreadCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND is_read=0 AND is_deleted=0",$userId));
		update_user_meta($userId,'apollo_unread_notifications',$count);
		return $count;
	}

	private static function incrementUnreadCount(int $userId): void {
		$c=self::getUnreadCount($userId);
		update_user_meta($userId,'apollo_unread_notifications',$c+1);
	}

	private static function decrementUnreadCount(int $userId): void {
		$c=self::getUnreadCount($userId);
		update_user_meta($userId,'apollo_unread_notifications',max(0,$c-1));
	}

	private static function resetUnreadCount(int $userId): void {
		update_user_meta($userId,'apollo_unread_notifications',0);
	}

	private static function shouldNotify(int $userId, string $type): bool {
		$prefs=get_user_meta($userId,'apollo_notification_prefs',true)?:[];
		if(isset($prefs[$type])&&$prefs[$type]===false){return false;}
		return apply_filters('apollo_should_notify',true,$userId,$type);
	}

	private static function checkDuplicate(int $userId, string $type, array $data): bool {
		if(!isset($data['actor_id'])||!isset($data['object_id'])){return false;}
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$window=gmdate('Y-m-d H:i:s',strtotime('-5 minutes'));
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE user_id=%d AND type=%s AND actor_id=%d AND object_id=%d AND created_at>=%s LIMIT 1",
			$userId,$type,$data['actor_id'],$data['object_id'],$window
		));
	}

	public static function getTypes(): array {
		return apply_filters('apollo_notification_types',[
			'friend_request'=>['label'=>'Solicitação de amizade','icon'=>'user-plus'],
			'friend_accepted'=>['label'=>'Amizade aceita','icon'=>'user-check'],
			'group_invite'=>['label'=>'Convite para grupo','icon'=>'users'],
			'group_join'=>['label'=>'Novo membro no grupo','icon'=>'user-plus'],
			'new_message'=>['label'=>'Nova mensagem','icon'=>'message-circle'],
			'mention'=>['label'=>'Menção','icon'=>'at-sign'],
			'activity_like'=>['label'=>'Curtida em atividade','icon'=>'heart'],
			'activity_comment'=>['label'=>'Comentário em atividade','icon'=>'message-square'],
			'badge_earned'=>['label'=>'Badge conquistado','icon'=>'award'],
			'achievement'=>['label'=>'Conquista desbloqueada','icon'=>'trophy'],
			'rank_up'=>['label'=>'Novo rank','icon'=>'trending-up'],
			'event_reminder'=>['label'=>'Lembrete de evento','icon'=>'calendar'],
			'event_update'=>['label'=>'Atualização de evento','icon'=>'calendar'],
			'system'=>['label'=>'Sistema','icon'=>'bell'],
			'admin'=>['label'=>'Administração','icon'=>'shield']
		]);
	}

	public static function notifyMultiple(array $userIds, string $type, string $title, array $data=[]): int {
		$count=0;
		foreach($userIds as $uid){
			if(self::notify((int)$uid,$type,$title,$data)){$count++;}
		}
		return $count;
	}

	public static function cleanOld(int $daysRead=30, int $daysUnread=90): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$readCutoff=gmdate('Y-m-d H:i:s',strtotime("-{$daysRead} days"));
		$unreadCutoff=gmdate('Y-m-d H:i:s',strtotime("-{$daysUnread} days"));
		$deleted=(int)$wpdb->query($wpdb->prepare(
			"DELETE FROM {$t} WHERE (is_read=1 AND created_at<%s) OR (is_read=0 AND created_at<%s)",
			$readCutoff,$unreadCutoff
		));
		return $deleted;
	}

	public static function getUserPreferences(int $userId): array {
		$prefs=get_user_meta($userId,'apollo_notification_prefs',true)?:[];
		$types=self::getTypes();
		$result=[];
		foreach($types as $key=>$config){
			$result[$key]=['enabled'=>$prefs[$key]??true,'label'=>$config['label']];}
		return $result;
	}

	public static function setUserPreferences(int $userId, array $prefs): bool {
		return (bool)update_user_meta($userId,'apollo_notification_prefs',$prefs);
	}

	public static function sendPush(int $userId, string $title, string $body, array $data=[]): bool {
		$token=get_user_meta($userId,'apollo_push_token',true);
		if(!$token){return false;}
		return (bool)apply_filters('apollo_send_push_notification',false,$token,$title,$body,$data);
	}

	public static function sendEmail(int $userId, string $type, array $data=[]): bool {
		$user=get_userdata($userId);
		if(!$user){return false;}
		$emailPrefs=get_user_meta($userId,'apollo_email_notification_prefs',true)?:[];
		if(isset($emailPrefs[$type])&&$emailPrefs[$type]===false){return false;}
		do_action('apollo_notification_email',$user->user_email,$type,$data);
		return true;
	}

	public static function countByType(int $userId, string $type): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND type=%s AND is_deleted=0",$userId,$type));
	}

	public static function getGrouped(int $userId, int $limit=20): array {
		$all=self::getForUser($userId,$limit*2,0,false);
		$grouped=[];
		foreach($all as $n){
			$key=$n['type'].'_'.($n['object_type']??'').'_'.($n['object_id']??'');
			if(!isset($grouped[$key])){$grouped[$key]=$n;$grouped[$key]['actors']=[];$grouped[$key]['count']=0;}
			if($n['actor_id']){$grouped[$key]['actors'][]=$n['actor_id'];$grouped[$key]['count']++;}
		}
		return array_slice(array_values($grouped),0,$limit);
	}
}
