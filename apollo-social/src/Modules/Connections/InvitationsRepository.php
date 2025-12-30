<?php
declare(strict_types=1);
namespace Apollo\Modules\Connections;

final class InvitationsRepository {
	private const TABLE='apollo_invitations';

	public static function create(int $inviterId, string $email, ?string $name=null, ?int $groupId=null): ?int {
		global $wpdb;
		$existing=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}".self::TABLE." WHERE email=%s AND status='pending'",
			$email
		));
		if($existing){return null;}
		$code=self::generateCode();
		$r=$wpdb->insert($wpdb->prefix.self::TABLE,[
			'inviter_id'=>$inviterId,
			'email'=>$email,
			'name'=>$name,
			'group_id'=>$groupId,
			'code'=>$code,
			'expires_at'=>gmdate('Y-m-d H:i:s',strtotime('+7 days'))
		],['%d','%s','%s','%d','%s','%s']);
		if($r){
			self::sendInvitationEmail($email,$name,$inviterId,$code,$groupId);
			do_action('apollo_invitation_sent',$wpdb->insert_id,$inviterId,$email);
		}
		return $r?(int)$wpdb->insert_id:null;
	}

	public static function createBulk(int $inviterId, array $emails, ?int $groupId=null): array {
		$results=['sent'=>0,'skipped'=>0,'failed'=>0];
		foreach($emails as $email){
			$email=sanitize_email($email);
			if(!is_email($email)){$results['failed']++;continue;}
			if(email_exists($email)){$results['skipped']++;continue;}
			$id=self::create($inviterId,$email,null,$groupId);
			if($id){$results['sent']++;}else{$results['skipped']++;}
		}
		return $results;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$id),ARRAY_A);
	}

	public static function getByCode(string $code): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE code=%s",$code),ARRAY_A);
	}

	public static function validate(string $code): array {
		$inv=self::getByCode($code);
		if(!$inv){return ['valid'=>false,'error'=>'invalid_code'];}
		if($inv['status']!=='pending'){return ['valid'=>false,'error'=>'already_used'];}
		if(strtotime($inv['expires_at'])<time()){return ['valid'=>false,'error'=>'expired'];}
		return ['valid'=>true,'invitation'=>$inv];
	}

	public static function accept(string $code, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$inv=self::getByCode($code);
		if(!$inv||$inv['status']!=='pending'){return false;}
		$r=$wpdb->update($t,[
			'status'=>'accepted',
			'accepted_user_id'=>$userId,
			'accepted_at'=>current_time('mysql')
		],['code'=>$code],['%s','%d','%s'],['%s']);
		if($r){
			if($inv['group_id']){self::addToGroup($userId,(int)$inv['group_id']);}
			self::createConnection((int)$inv['inviter_id'],$userId);
			do_action('apollo_invitation_accepted',$inv['id'],$userId,$inv['inviter_id']);
			do_action('apollo_award_points',(int)$inv['inviter_id'],25,'invitation_accepted','Convite aceito');
		}
		return (bool)$r;
	}

	public static function cancel(int $id, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (bool)$wpdb->update($t,['status'=>'cancelled'],['id'=>$id,'inviter_id'=>$userId,'status'=>'pending']);
	}

	public static function resend(int $id): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$inv=self::get($id);
		if(!$inv||$inv['status']!=='pending'){return false;}
		$newCode=self::generateCode();
		$wpdb->update($t,[
			'code'=>$newCode,
			'resent_count'=>(int)$inv['resent_count']+1,
			'expires_at'=>gmdate('Y-m-d H:i:s',strtotime('+7 days'))
		],['id'=>$id]);
		self::sendInvitationEmail($inv['email'],$inv['name'],(int)$inv['inviter_id'],$newCode,$inv['group_id']?(int)$inv['group_id']:null);
		return true;
	}

	public static function getByInviter(int $inviterId, ?string $status=null, int $limit=50): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		if($status){
			return $wpdb->get_results($wpdb->prepare(
				"SELECT * FROM {$t} WHERE inviter_id=%d AND status=%s ORDER BY created_at DESC LIMIT %d",
				$inviterId,$status,$limit
			),ARRAY_A)??[];
		}
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE inviter_id=%d ORDER BY created_at DESC LIMIT %d",
			$inviterId,$limit
		),ARRAY_A)??[];
	}

	public static function getByEmail(string $email): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE email=%s ORDER BY created_at DESC",$email),ARRAY_A)??[];
	}

	public static function getPending(int $limit=100): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE status='pending' AND expires_at>%s ORDER BY created_at ASC LIMIT %d",
			current_time('mysql'),$limit
		),ARRAY_A)??[];
	}

	public static function getStats(int $inviterId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return [
			'total'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE inviter_id=%d",$inviterId)),
			'pending'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE inviter_id=%d AND status='pending'",$inviterId)),
			'accepted'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE inviter_id=%d AND status='accepted'",$inviterId)),
			'expired'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE inviter_id=%d AND status='pending' AND expires_at<%s",$inviterId,current_time('mysql')))
		];
	}

	public static function expireOld(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->query($wpdb->prepare(
			"UPDATE {$t} SET status='expired' WHERE status='pending' AND expires_at<%s",
			current_time('mysql')
		));
	}

	public static function getTopInviters(int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT inviter_id,COUNT(*) as total,SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) as accepted FROM {$t} GROUP BY inviter_id ORDER BY accepted DESC,total DESC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	private static function generateCode(): string {
		return bin2hex(\random_bytes(16));
	}

	private static function sendInvitationEmail(string $email, ?string $name, int $inviterId, string $code, ?int $groupId): void {
		$inviter=get_userdata($inviterId);
		$inviterName=$inviter?$inviter->display_name:'Alguém';
		$link=add_query_arg(['invitation'=>$code],home_url('/register'));
		$subject=sprintf('%s convidou você para participar da comunidade',esc_html($inviterName));
		$message="Olá".($name?" {$name}":'').",\n\n";
		$message.="{$inviterName} está convidando você para fazer parte da nossa comunidade.\n\n";
		if($groupId){
			global $wpdb;
			$group=$wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}apollo_groups WHERE id=%d",$groupId));
			if($group){$message.="Você foi convidado para o grupo: {$group}\n\n";}
		}
		$message.="Clique no link abaixo para aceitar o convite:\n{$link}\n\n";
		$message.="Este convite expira em 7 dias.\n\nAbraços,\nEquipe";
		wp_mail($email,$subject,$message);
	}

	private static function addToGroup(int $userId, int $groupId): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_members';
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE group_id=%d AND user_id=%d",$groupId,$userId));
		if(!$exists){
			$wpdb->insert($t,['group_id'=>$groupId,'user_id'=>$userId,'role'=>'member'],['%d','%d','%s']);
		}
	}

	private static function createConnection(int $inviterId, int $invitedId): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_connections';
		$exists=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE (user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d)",
			$inviterId,$invitedId,$invitedId,$inviterId
		));
		if(!$exists){
			$wpdb->insert($t,['user_id'=>$inviterId,'friend_id'=>$invitedId,'status'=>'accepted'],['%d','%d','%s']);
		}
	}
}
