<?php
declare(strict_types=1);
namespace Apollo\Modules\AccountSettings;

final class AccountSettingsRepository {

	public static function get(int $userId, string $key, $default=null) {
		$val=get_user_meta($userId,"apollo_setting_{$key}",true);
		return $val!==''?$val:$default;
	}

	public static function set(int $userId, string $key, $value): bool {
		return update_user_meta($userId,"apollo_setting_{$key}",$value)!==false;
	}

	public static function delete(int $userId, string $key): bool {
		return delete_user_meta($userId,"apollo_setting_{$key}");
	}

	public static function getAll(int $userId): array {
		$defaults=self::getDefaults();
		$settings=[];
		foreach($defaults as $key=>$default){
			$settings[$key]=self::get($userId,$key,$default);
		}
		return $settings;
	}

	public static function update(int $userId, array $settings): bool {
		$allowed=array_keys(self::getDefaults());
		foreach($settings as $key=>$value){
			if(in_array($key,$allowed,true)){
				self::set($userId,$key,$value);
			}
		}
		return true;
	}

	public static function getDefaults(): array {
		return [
			'email_notifications'=>true,
			'email_friend_request'=>true,
			'email_friend_accepted'=>true,
			'email_new_message'=>true,
			'email_mentions'=>true,
			'email_group_invite'=>true,
			'email_group_activity'=>false,
			'email_newsletter'=>true,
			'push_notifications'=>true,
			'push_friend_request'=>true,
			'push_new_message'=>true,
			'push_mentions'=>true,
			'push_likes'=>true,
			'push_comments'=>true,
			'profile_visibility'=>'public',
			'activity_visibility'=>'public',
			'friends_visibility'=>'public',
			'groups_visibility'=>'public',
			'photos_visibility'=>'public',
			'can_message'=>'everyone',
			'can_friend_request'=>'everyone',
			'can_mention'=>'everyone',
			'show_online_status'=>true,
			'show_last_active'=>true,
			'show_profile_views'=>true,
			'show_birthday'=>'friends',
			'show_email'=>'none',
			'show_phone'=>'none',
			'language'=>'pt_BR',
			'timezone'=>'America/Sao_Paulo',
			'date_format'=>'d/m/Y',
			'time_format'=>'H:i',
			'two_factor_enabled'=>false,
			'login_alerts'=>true,
			'session_timeout'=>1440,
			'export_data_allowed'=>true,
			'delete_account_allowed'=>true
		];
	}

	public static function getEmailPreferences(int $userId): array {
		return [
			'notifications'=>self::get($userId,'email_notifications',true),
			'friend_request'=>self::get($userId,'email_friend_request',true),
			'friend_accepted'=>self::get($userId,'email_friend_accepted',true),
			'new_message'=>self::get($userId,'email_new_message',true),
			'mentions'=>self::get($userId,'email_mentions',true),
			'group_invite'=>self::get($userId,'email_group_invite',true),
			'group_activity'=>self::get($userId,'email_group_activity',false),
			'newsletter'=>self::get($userId,'email_newsletter',true)
		];
	}

	public static function getPushPreferences(int $userId): array {
		return [
			'enabled'=>self::get($userId,'push_notifications',true),
			'friend_request'=>self::get($userId,'push_friend_request',true),
			'new_message'=>self::get($userId,'push_new_message',true),
			'mentions'=>self::get($userId,'push_mentions',true),
			'likes'=>self::get($userId,'push_likes',true),
			'comments'=>self::get($userId,'push_comments',true)
		];
	}

	public static function getPrivacySettings(int $userId): array {
		return [
			'profile'=>self::get($userId,'profile_visibility','public'),
			'activity'=>self::get($userId,'activity_visibility','public'),
			'friends'=>self::get($userId,'friends_visibility','public'),
			'groups'=>self::get($userId,'groups_visibility','public'),
			'photos'=>self::get($userId,'photos_visibility','public'),
			'message'=>self::get($userId,'can_message','everyone'),
			'friend_request'=>self::get($userId,'can_friend_request','everyone'),
			'mention'=>self::get($userId,'can_mention','everyone'),
			'online_status'=>self::get($userId,'show_online_status',true),
			'last_active'=>self::get($userId,'show_last_active',true),
			'profile_views'=>self::get($userId,'show_profile_views',true),
			'birthday'=>self::get($userId,'show_birthday','friends'),
			'email'=>self::get($userId,'show_email','none'),
			'phone'=>self::get($userId,'show_phone','none')
		];
	}

	public static function getSecuritySettings(int $userId): array {
		return [
			'two_factor'=>self::get($userId,'two_factor_enabled',false),
			'login_alerts'=>self::get($userId,'login_alerts',true),
			'session_timeout'=>self::get($userId,'session_timeout',1440),
			'active_sessions'=>self::getActiveSessions($userId)
		];
	}

	public static function enableTwoFactor(int $userId): string {
		$secret=self::generateTwoFactorSecret();
		update_user_meta($userId,'apollo_2fa_secret',$secret);
		self::set($userId,'two_factor_enabled',true);
		return $secret;
	}

	public static function disableTwoFactor(int $userId): bool {
		delete_user_meta($userId,'apollo_2fa_secret');
		return self::set($userId,'two_factor_enabled',false);
	}

	public static function verifyTwoFactor(int $userId, string $code): bool {
		$secret=get_user_meta($userId,'apollo_2fa_secret',true);
		if(!$secret){return false;}
		$timeSlice=floor(time()/30);
		for($i=-1;$i<=1;$i++){
			$calcCode=self::getTOTPCode($secret,$timeSlice+$i);
			if($calcCode===$code){return true;}
		}
		return false;
	}

	public static function getActiveSessions(int $userId): array {
		$sessions=get_user_meta($userId,'session_tokens',true);
		if(!is_array($sessions)){return [];}
		$result=[];
		foreach($sessions as $token=>$data){
			$result[]=[
				'token_hash'=>\substr(md5($token),0,8),
				'login'=>$data['login']??0,
				'expiration'=>$data['expiration']??0,
				'ip'=>$data['ip']??'',
				'ua'=>$data['ua']??''
			];
		}
		return $result;
	}

	public static function terminateSession(int $userId, string $tokenHash): bool {
		$sessions=get_user_meta($userId,'session_tokens',true);
		if(!is_array($sessions)){return false;}
		foreach($sessions as $token=>$data){
			if(\substr(md5($token),0,8)===$tokenHash){
				unset($sessions[$token]);
				update_user_meta($userId,'session_tokens',$sessions);
				return true;
			}
		}
		return false;
	}

	public static function terminateAllSessions(int $userId, string $exceptCurrent=''): int {
		$sessions=get_user_meta($userId,'session_tokens',true);
		if(!is_array($sessions)){return 0;}
		$count=0;
		foreach($sessions as $token=>$data){
			if($exceptCurrent&&\substr(md5($token),0,8)===$exceptCurrent){continue;}
			unset($sessions[$token]);
			$count++;
		}
		update_user_meta($userId,'session_tokens',$sessions);
		return $count;
	}

	public static function changePassword(int $userId, string $currentPass, string $newPass): array {
		$user=get_userdata($userId);
		if(!$user){return ['success'=>false,'error'=>'user_not_found'];}
		if(!wp_check_password($currentPass,$user->user_pass,$userId)){
			return ['success'=>false,'error'=>'wrong_password'];
		}
		if(strlen($newPass)<8){
			return ['success'=>false,'error'=>'password_too_short'];
		}
		wp_set_password($newPass,$userId);
		self::logSecurityEvent($userId,'password_changed');
		return ['success'=>true];
	}

	public static function changeEmail(int $userId, string $newEmail, string $password): array {
		$user=get_userdata($userId);
		if(!$user){return ['success'=>false,'error'=>'user_not_found'];}
		if(!wp_check_password($password,$user->user_pass,$userId)){
			return ['success'=>false,'error'=>'wrong_password'];
		}
		if(!is_email($newEmail)){
			return ['success'=>false,'error'=>'invalid_email'];
		}
		if(email_exists($newEmail)&&email_exists($newEmail)!==$userId){
			return ['success'=>false,'error'=>'email_exists'];
		}
		$code=wp_generate_password(32,false);
		update_user_meta($userId,'apollo_pending_email',$newEmail);
		update_user_meta($userId,'apollo_email_confirm_code',$code);
		do_action('apollo_email_change_requested',$userId,$newEmail,$code);
		return ['success'=>true,'pending'=>true];
	}

	public static function confirmEmailChange(int $userId, string $code): bool {
		$stored=get_user_meta($userId,'apollo_email_confirm_code',true);
		if(!$stored||$stored!==$code){return false;}
		$newEmail=get_user_meta($userId,'apollo_pending_email',true);
		if(!$newEmail){return false;}
		wp_update_user(['ID'=>$userId,'user_email'=>$newEmail]);
		delete_user_meta($userId,'apollo_pending_email');
		delete_user_meta($userId,'apollo_email_confirm_code');
		self::logSecurityEvent($userId,'email_changed');
		return true;
	}

	public static function requestAccountDeletion(int $userId, string $password, string $reason=''): array {
		$user=get_userdata($userId);
		if(!$user){return ['success'=>false,'error'=>'user_not_found'];}
		if(!wp_check_password($password,$user->user_pass,$userId)){
			return ['success'=>false,'error'=>'wrong_password'];
		}
		$code=wp_generate_password(32,false);
		update_user_meta($userId,'apollo_delete_request_code',$code);
		update_user_meta($userId,'apollo_delete_request_reason',$reason);
		update_user_meta($userId,'apollo_delete_request_at',current_time('mysql'));
		do_action('apollo_account_deletion_requested',$userId,$code);
		return ['success'=>true,'pending'=>true];
	}

	public static function confirmAccountDeletion(int $userId, string $code): bool {
		$stored=get_user_meta($userId,'apollo_delete_request_code',true);
		if(!$stored||$stored!==$code){return false;}
		do_action('apollo_before_account_deletion',$userId);
		require_once ABSPATH.'wp-admin/includes/user.php';
		wp_delete_user($userId);
		return true;
	}

	public static function exportUserData(int $userId): array {
		$user=get_userdata($userId);
		if(!$user){return [];}
		return [
			'user'=>[
				'id'=>$user->ID,
				'username'=>$user->user_login,
				'email'=>$user->user_email,
				'display_name'=>$user->display_name,
				'registered'=>$user->user_registered
			],
			'profile'=>self::getAll($userId),
			'meta'=>get_user_meta($userId),
			'exported_at'=>current_time('mysql')
		];
	}

	private static function generateTwoFactorSecret(): string {
		$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$secret='';
		for($i=0;$i<16;$i++){$secret.=$chars[random_int(0,31)];}
		return $secret;
	}

	private static function getTOTPCode(string $secret, int $timeSlice): string {
		$key=self::base32Decode($secret);
		$time=pack('N*',0).pack('N*',$timeSlice);
		$hash=hash_hmac('sha1',$time,$key,true);
		$offset=ord($hash[19])&0xf;
		$code=((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|(ord($hash[$offset+3])&0xff);
		return str_pad((string)($code%1000000),6,'0',STR_PAD_LEFT);
	}

	private static function base32Decode(string $input): string {
		$map='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$input=strtoupper($input);
		$buffer=0;$bitsLeft=0;$result='';
		for($i=0;$i<strlen($input);$i++){
			$val=strpos($map,$input[$i]);
			if($val===false){continue;}
			$buffer=($buffer<<5)|$val;
			$bitsLeft+=5;
			if($bitsLeft>=8){
				$bitsLeft-=8;
				$result.=chr(($buffer>>$bitsLeft)&0xff);
			}
		}
		return $result;
	}

	private static function logSecurityEvent(int $userId, string $event): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_security_log';
		$wpdb->insert($t,[
			'user_id'=>$userId,
			'event'=>$event,
			'ip'=>$_SERVER['REMOTE_ADDR']??'',
			'user_agent'=>$_SERVER['HTTP_USER_AGENT']??'',
			'created_at'=>current_time('mysql')
		],['%d','%s','%s','%s','%s']);
	}
}
