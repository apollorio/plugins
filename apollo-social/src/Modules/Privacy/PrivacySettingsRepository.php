<?php
declare(strict_types=1);
namespace Apollo\Modules\Privacy;

final class PrivacySettingsRepository {
	private const DEFAULTS=[
		'profile_visibility'=>'public',
		'activity_visibility'=>'friends',
		'friends_list_visibility'=>'public',
		'groups_visibility'=>'public',
		'online_status'=>'everyone',
		'last_active'=>'friends',
		'allow_friend_requests'=>true,
		'allow_messages'=>'friends',
		'allow_wall_posts'=>'friends',
		'allow_mentions'=>true,
		'show_in_directory'=>true,
		'searchable'=>true,
		'allow_profile_comments'=>'friends',
		'email_on_friend_request'=>true,
		'email_on_message'=>true,
		'email_on_mention'=>true,
		'email_on_activity_reply'=>true,
		'email_digest'=>'daily',
		'push_notifications'=>true,
		'show_birthday'=>'friends',
		'show_email'=>'none',
		'show_phone'=>'none',
		'two_factor_enabled'=>false,
		'data_download_enabled'=>true,
		'allow_data_collection'=>true
	];

	public static function get(int $userId, string $key): mixed {
		$all=self::getAll($userId);
		return $all[$key]??self::DEFAULTS[$key]??null;
	}

	public static function set(int $userId, string $key, mixed $value): bool {
		if(!array_key_exists($key,self::DEFAULTS)){return false;}
		$type=gettype(self::DEFAULTS[$key]);
		$value=match($type){
			'boolean'=>(bool)$value,
			'integer'=>(int)$value,
			'string'=>(string)$value,
			default=>$value
		};
		$all=self::getAll($userId);
		$all[$key]=$value;
		return (bool)update_user_meta($userId,'apollo_privacy_settings',json_encode($all));
	}

	public static function setMultiple(int $userId, array $settings): bool {
		$all=self::getAll($userId);
		foreach($settings as $k=>$v){
			if(array_key_exists($k,self::DEFAULTS)){
				$type=gettype(self::DEFAULTS[$k]);
				$all[$k]=match($type){
					'boolean'=>(bool)$v,'integer'=>(int)$v,'string'=>(string)$v,default=>$v
				};
			}
		}
		return (bool)update_user_meta($userId,'apollo_privacy_settings',json_encode($all));
	}

	public static function getAll(int $userId): array {
		$stored=get_user_meta($userId,'apollo_privacy_settings',true);
		$parsed=$stored?json_decode($stored,true):[];
		return array_merge(self::DEFAULTS,$parsed??[]);
	}

	public static function reset(int $userId): bool {
		return (bool)delete_user_meta($userId,'apollo_privacy_settings');
	}

	public static function getDefaults(): array {
		return self::DEFAULTS;
	}

	public static function canView(int $viewerId, int $profileId, string $what): bool {
		if($viewerId===$profileId){return true;}
		if(current_user_can('manage_options')){return true;}
		$setting=self::get($profileId,$what.'_visibility');
		return match($setting){
			'public'=>true,
			'members'=>is_user_logged_in(),
			'friends'=>self::areFriends($viewerId,$profileId),
			'close_friends'=>self::areCloseFriends($viewerId,$profileId),
			'none'=>false,
			default=>true
		};
	}

	public static function canMessage(int $senderId, int $recipientId): bool {
		if($senderId===$recipientId){return false;}
		$setting=self::get($recipientId,'allow_messages');
		return match($setting){
			'everyone'=>true,
			'members'=>is_user_logged_in(),
			'friends'=>self::areFriends($senderId,$recipientId),
			'none'=>false,
			default=>true
		};
	}

	public static function canSendFriendRequest(int $senderId, int $recipientId): bool {
		if($senderId===$recipientId){return false;}
		return (bool)self::get($recipientId,'allow_friend_requests');
	}

	public static function canMention(int $mentionerId, int $targetId): bool {
		return (bool)self::get($targetId,'allow_mentions');
	}

	public static function canSeeOnlineStatus(int $viewerId, int $userId): bool {
		if($viewerId===$userId){return true;}
		$setting=self::get($userId,'online_status');
		return match($setting){
			'everyone'=>true,
			'members'=>is_user_logged_in(),
			'friends'=>self::areFriends($viewerId,$userId),
			'none'=>false,
			default=>true
		};
	}

	public static function canSeeLastActive(int $viewerId, int $userId): bool {
		if($viewerId===$userId){return true;}
		$setting=self::get($userId,'last_active');
		return match($setting){
			'everyone'=>true,
			'friends'=>self::areFriends($viewerId,$userId),
			'none'=>false,
			default=>true
		};
	}

	public static function isInDirectory(int $userId): bool {
		return (bool)self::get($userId,'show_in_directory');
	}

	public static function isSearchable(int $userId): bool {
		return (bool)self::get($userId,'searchable');
	}

	private static function areFriends(int $userId1, int $userId2): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_connections';
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d AND status='accepted' LIMIT 1",
			$userId1,$userId2
		));
	}

	private static function areCloseFriends(int $userId1, int $userId2): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_close_friends';
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d LIMIT 1",
			$userId1,$userId2
		));
	}

	public static function getEmailPreference(int $userId, string $type): bool {
		return (bool)self::get($userId,'email_on_'.$type);
	}

	public static function getDigestFrequency(int $userId): string {
		return (string)self::get($userId,'email_digest');
	}

	public static function hasPushEnabled(int $userId): bool {
		return (bool)self::get($userId,'push_notifications');
	}

	public static function exportUserData(int $userId): array {
		return [
			'privacy_settings'=>self::getAll($userId),
			'exported_at'=>current_time('mysql')
		];
	}

	public static function deleteUserData(int $userId): bool {
		delete_user_meta($userId,'apollo_privacy_settings');
		delete_user_meta($userId,'apollo_notification_prefs');
		delete_user_meta($userId,'apollo_email_notification_prefs');
		return true;
	}

	public static function getFieldVisibility(int $userId, string $field): string {
		$key='show_'.$field;
		if(array_key_exists($key,self::DEFAULTS)){
			return (string)self::get($userId,$key);
		}
		return 'public';
	}

	public static function setFieldVisibility(int $userId, string $field, string $visibility): bool {
		return self::set($userId,'show_'.$field,$visibility);
	}
}
