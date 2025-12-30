<?php
declare(strict_types=1);
namespace Apollo\Modules\Security;

final class TwoFactorRepository {
	private const T_SESSIONS='apollo_2fa_sessions';
	private const T_BACKUP_CODES='apollo_2fa_backup_codes';
	private const T_TRUSTED='apollo_2fa_trusted_devices';

	public static function enable(int $userId, string $method='totp'): array {
		$secret=self::generateSecret();
		update_user_meta($userId,'apollo_2fa_secret',$secret);
		update_user_meta($userId,'apollo_2fa_method',$method);
		update_user_meta($userId,'apollo_2fa_pending',1);
		$user=get_userdata($userId);
		$siteName=get_bloginfo('name');
		$qrUrl=sprintf(
			'otpauth://totp/%s:%s?secret=%s&issuer=%s',
			rawurlencode($siteName),
			rawurlencode($user->user_email),
			$secret,
			rawurlencode($siteName)
		);
		return ['secret'=>$secret,'qr_url'=>$qrUrl];
	}

	public static function verify(int $userId, string $code): bool {
		$secret=get_user_meta($userId,'apollo_2fa_secret',true);
		if(!$secret)return false;
		$valid=self::verifyTotpCode($secret,$code);
		if($valid){
			delete_user_meta($userId,'apollo_2fa_pending');
			update_user_meta($userId,'apollo_2fa_enabled',1);
			update_user_meta($userId,'apollo_2fa_enabled_at',gmdate('Y-m-d H:i:s'));
			self::generateBackupCodes($userId);
		}
		return $valid;
	}

	public static function disable(int $userId, string $code): bool {
		if(!self::validateCode($userId,$code))return false;
		delete_user_meta($userId,'apollo_2fa_secret');
		delete_user_meta($userId,'apollo_2fa_method');
		delete_user_meta($userId,'apollo_2fa_enabled');
		delete_user_meta($userId,'apollo_2fa_enabled_at');
		delete_user_meta($userId,'apollo_2fa_pending');
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::T_BACKUP_CODES,['user_id'=>$userId],['%d']);
		$wpdb->delete($wpdb->prefix.self::T_TRUSTED,['user_id'=>$userId],['%d']);
		return true;
	}

	public static function isEnabled(int $userId): bool {
		return (bool)get_user_meta($userId,'apollo_2fa_enabled',true);
	}

	public static function validateCode(int $userId, string $code): bool {
		$code=preg_replace('/\s/','',$code);
		if(strlen($code)===6){
			return self::verifyTotp($userId,$code);
		}
		if(strlen($code)>=8){
			return self::useBackupCode($userId,$code);
		}
		return false;
	}

	public static function verifyTotp(int $userId, string $code): bool {
		$secret=get_user_meta($userId,'apollo_2fa_secret',true);
		if(!$secret)return false;
		return self::verifyTotpCode($secret,$code);
	}

	private static function verifyTotpCode(string $secret, string $code, int $window=1): bool {
		$time=floor(time()/30);
		for($i=-$window;$i<=$window;$i++){
			$calc=self::generateTotpCode($secret,$time+$i);
			if(hash_equals($calc,$code))return true;
		}
		return false;
	}

	private static function generateTotpCode(string $secret, int $time): string {
		$key=self::base32Decode($secret);
		$msg=pack('N*',0).pack('N*',$time);
		$hash=hash_hmac('sha1',$msg,$key,true);
		$offset=ord($hash[19])&0x0f;
		$code=(
			((ord($hash[$offset])&0x7f)<<24)|
			((ord($hash[$offset+1])&0xff)<<16)|
			((ord($hash[$offset+2])&0xff)<<8)|
			(ord($hash[$offset+3])&0xff)
		)%1000000;
		return str_pad((string)$code,6,'0',STR_PAD_LEFT);
	}

	private static function generateSecret(int $length=16): string {
		$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$secret='';
		for($i=0;$i<$length;$i++){
			$secret.=$chars[wp_rand(0,31)];
		}
		return $secret;
	}

	private static function base32Decode(string $b32): string {
		$lut=['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25,'2'=>26,'3'=>27,'4'=>28,'5'=>29,'6'=>30,'7'=>31];
		$b32=\strtoupper($b32);
		$l=strlen($b32);
		$n=0;$j=0;$binary='';
		for($i=0;$i<$l;$i++){
			$n=$n<<5;
			$n=$n+($lut[$b32[$i]]??0);
			$j+=5;
			if($j>=8){
				$j-=8;
				$binary.=chr(($n&(0xFF<<$j))>>$j);
			}
		}
		return $binary;
	}

	public static function generateBackupCodes(int $userId, int $count=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_BACKUP_CODES;
		$wpdb->delete($t,['user_id'=>$userId],['%d']);
		$codes=[];
		for($i=0;$i<$count;$i++){
			$code=\strtoupper(wp_generate_password(8,false));
			$hash=wp_hash_password($code);
			$wpdb->insert($t,[
				'user_id'=>$userId,
				'code_hash'=>$hash,
				'created_at'=>gmdate('Y-m-d H:i:s')
			],['%d','%s','%s']);
			$codes[]=$code;
		}
		return $codes;
	}

	public static function useBackupCode(int $userId, string $code): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_BACKUP_CODES;
		$codes=$wpdb->get_results($wpdb->prepare("SELECT id,code_hash FROM {$t} WHERE user_id=%d AND used_at IS NULL",$userId),ARRAY_A);
		foreach($codes as $c){
			if(wp_check_password($code,$c['code_hash'])){
				$wpdb->update($t,['used_at'=>gmdate('Y-m-d H:i:s')],['id'=>$c['id']],['%s'],['%d']);
				return true;
			}
		}
		return false;
	}

	public static function getRemainingBackupCodes(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_BACKUP_CODES;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND used_at IS NULL",$userId));
	}

	public static function trustDevice(int $userId, string $deviceId, string $deviceName='', int $days=30): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_TRUSTED;
		$hash=wp_hash($deviceId.$userId);
		$expires=gmdate('Y-m-d H:i:s',strtotime("+{$days} days"));
		$existing=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE user_id=%d AND device_hash=%s",$userId,$hash));
		if($existing){
			return (bool)$wpdb->update($t,['expires_at'=>$expires,'last_used'=>gmdate('Y-m-d H:i:s')],['id'=>$existing],['%s','%s'],['%d']);
		}
		return (bool)$wpdb->insert($t,[
			'user_id'=>$userId,
			'device_hash'=>$hash,
			'device_name'=>$deviceName,
			'expires_at'=>$expires,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%s','%s','%s']);
	}

	public static function isTrustedDevice(int $userId, string $deviceId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_TRUSTED;
		$hash=wp_hash($deviceId.$userId);
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE user_id=%d AND device_hash=%s AND expires_at>NOW()",$userId,$hash
		));
	}

	public static function getTrustedDevices(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_TRUSTED;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT id,device_name,created_at,last_used,expires_at FROM {$t} WHERE user_id=%d AND expires_at>NOW() ORDER BY last_used DESC",
			$userId
		),ARRAY_A)??[];
	}

	public static function removeTrustedDevice(int $deviceId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_TRUSTED;
		return (bool)$wpdb->delete($t,['id'=>$deviceId,'user_id'=>$userId],['%d','%d']);
	}

	public static function removeAllTrustedDevices(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_TRUSTED;
		return (int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE user_id=%d",$userId));
	}

	public static function getStatus(int $userId): array {
		return [
			'enabled'=>self::isEnabled($userId),
			'method'=>get_user_meta($userId,'apollo_2fa_method',true)?:'totp',
			'enabled_at'=>get_user_meta($userId,'apollo_2fa_enabled_at',true),
			'backup_codes_remaining'=>self::getRemainingBackupCodes($userId),
			'trusted_devices'=>count(self::getTrustedDevices($userId))
		];
	}

	public static function createSession(int $userId): string {
		global $wpdb;
		$t=$wpdb->prefix.self::T_SESSIONS;
		$token=wp_generate_password(32,false);
		$hash=wp_hash($token);
		$expires=gmdate('Y-m-d H:i:s',strtotime('+10 minutes'));
		$wpdb->insert($t,[
			'user_id'=>$userId,
			'token_hash'=>$hash,
			'expires_at'=>$expires,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%s','%s']);
		return $token;
	}

	public static function validateSession(string $token): ?int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_SESSIONS;
		$hash=wp_hash($token);
		$session=$wpdb->get_row($wpdb->prepare(
			"SELECT user_id FROM {$t} WHERE token_hash=%s AND expires_at>NOW() AND used_at IS NULL",$hash
		),ARRAY_A);
		if(!$session)return null;
		$wpdb->update($t,['used_at'=>gmdate('Y-m-d H:i:s')],['token_hash'=>$hash],['%s'],['%s']);
		return (int)$session['user_id'];
	}

	public static function cleanupExpired(): int {
		global $wpdb;
		$sessions=$wpdb->query("DELETE FROM {$wpdb->prefix}".self::T_SESSIONS." WHERE expires_at<NOW()");
		$devices=$wpdb->query("DELETE FROM {$wpdb->prefix}".self::T_TRUSTED." WHERE expires_at<NOW()");
		return $sessions+$devices;
	}
}
