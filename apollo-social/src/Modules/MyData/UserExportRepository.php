<?php
declare(strict_types=1);
namespace Apollo\Modules\MyData;

final class UserExportRepository {
	private const T_EXPORTS='apollo_data_exports';
	
	public static function requestExport(int $userId, array $dataTypes=[]): int|false {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		$pending=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE user_id=%d AND status='pending'",$userId
		));
		if($pending)return false;
		if(empty($dataTypes)){
			$dataTypes=['profile','activity','connections','messages','media','settings'];
		}
		$wpdb->insert($t,[
			'user_id'=>$userId,
			'data_types'=>json_encode($dataTypes),
			'status'=>'pending',
			'requested_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%s','%s']);
		return $wpdb->insert_id?:false;
	}

	public static function getExportStatus(int $exportId, int $userId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$t} WHERE id=%d AND user_id=%d",$exportId,$userId
		),ARRAY_A);
	}

	public static function getUserExports(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d ORDER BY requested_at DESC LIMIT 10",$userId
		),ARRAY_A)??[];
	}

	public static function processExport(int $exportId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		$export=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$exportId),ARRAY_A);
		if(!$export||$export['status']!=='pending')return false;
		$wpdb->update($t,['status'=>'processing','started_at'=>gmdate('Y-m-d H:i:s')],['id'=>$exportId],['%s','%s'],['%d']);
		$userId=(int)$export['user_id'];
		$dataTypes=json_decode($export['data_types'],true);
		$data=[];
		foreach($dataTypes as $type){
			$data[$type]=match($type){
				'profile'=>self::exportProfile($userId),
				'activity'=>self::exportActivity($userId),
				'connections'=>self::exportConnections($userId),
				'messages'=>self::exportMessages($userId),
				'media'=>self::exportMedia($userId),
				'settings'=>self::exportSettings($userId),
				'groups'=>self::exportGroups($userId),
				'events'=>self::exportEvents($userId),
				default=>[]
			};
		}
		$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
		$uploadDir=wp_upload_dir();
		$exportDir=$uploadDir['basedir'].'/apollo-exports';
		if(!file_exists($exportDir))wp_mkdir_p($exportDir);
		$filename='export-'.$userId.'-'.time().'.json';
		$filepath=$exportDir.'/'.$filename;
		file_put_contents($filepath,$json);
		$downloadUrl=$uploadDir['baseurl'].'/apollo-exports/'.$filename;
		$expiresAt=gmdate('Y-m-d H:i:s',strtotime('+7 days'));
		$wpdb->update($t,[
			'status'=>'completed',
			'download_url'=>$downloadUrl,
			'file_path'=>$filepath,
			'completed_at'=>gmdate('Y-m-d H:i:s'),
			'expires_at'=>$expiresAt
		],['id'=>$exportId],['%s','%s','%s','%s','%s'],['%d']);
		return true;
	}

	private static function exportProfile(int $userId): array {
		$user=get_userdata($userId);
		if(!$user)return[];
		global $wpdb;
		$meta=$wpdb->get_results($wpdb->prepare(
			"SELECT meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key LIKE 'apollo_%'",$userId
		),ARRAY_A);
		return [
			'user_id'=>$userId,
			'display_name'=>$user->display_name,
			'email'=>$user->user_email,
			'registered'=>$user->user_registered,
			'meta'=>$meta
		];
	}

	private static function exportActivity(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT type,content,created_at FROM {$wpdb->prefix}apollo_activity WHERE user_id=%d ORDER BY created_at DESC",$userId
		),ARRAY_A)??[];
	}

	private static function exportConnections(int $userId): array {
		global $wpdb;
		$c=$wpdb->prefix.'apollo_connections';
		return [
			'friends'=>$wpdb->get_results($wpdb->prepare(
				"SELECT CASE WHEN user_id=%d THEN friend_id ELSE user_id END as friend_id,status,created_at FROM {$c} WHERE (user_id=%d OR friend_id=%d) AND status='accepted'",
				$userId,$userId,$userId
			),ARRAY_A)??[],
			'followers'=>$wpdb->get_col($wpdb->prepare(
				"SELECT follower_id FROM {$wpdb->prefix}apollo_followers WHERE following_id=%d",$userId
			)),
			'following'=>$wpdb->get_col($wpdb->prepare(
				"SELECT following_id FROM {$wpdb->prefix}apollo_followers WHERE follower_id=%d",$userId
			))
		];
	}

	private static function exportMessages(int $userId): array {
		global $wpdb;
		$m=$wpdb->prefix.'apollo_messages';
		$p=$wpdb->prefix.'apollo_message_participants';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT m.content,m.created_at,m.thread_id FROM {$m} m JOIN {$p} p ON m.thread_id=p.thread_id WHERE p.user_id=%d ORDER BY m.created_at DESC LIMIT 1000",
			$userId
		),ARRAY_A)??[];
	}

	private static function exportMedia(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT url,file_type,created_at FROM {$wpdb->prefix}apollo_media_library WHERE user_id=%d",$userId
		),ARRAY_A)??[];
	}

	private static function exportSettings(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT setting_key,setting_value FROM {$wpdb->prefix}apollo_user_settings WHERE user_id=%d",$userId
		),ARRAY_A)??[];
	}

	private static function exportGroups(int $userId): array {
		global $wpdb;
		$g=$wpdb->prefix.'apollo_groups';
		$m=$wpdb->prefix.'apollo_group_members';
		return [
			'member_of'=>$wpdb->get_results($wpdb->prepare(
				"SELECT g.id,g.name,gm.role,gm.created_at as joined_at FROM {$g} g JOIN {$m} gm ON g.id=gm.group_id WHERE gm.user_id=%d",$userId
			),ARRAY_A)??[],
			'created'=>$wpdb->get_results($wpdb->prepare(
				"SELECT id,name,created_at FROM {$g} WHERE creator_id=%d",$userId
			),ARRAY_A)??[]
		];
	}

	private static function exportEvents(int $userId): array {
		global $wpdb;
		$e=$wpdb->prefix.'apollo_events';
		$a=$wpdb->prefix.'apollo_event_attendees';
		return [
			'attending'=>$wpdb->get_results($wpdb->prepare(
				"SELECT e.id,e.title,e.start_date,a.status FROM {$e} e JOIN {$a} a ON e.id=a.event_id WHERE a.user_id=%d",$userId
			),ARRAY_A)??[],
			'organized'=>$wpdb->get_results($wpdb->prepare(
				"SELECT id,title,start_date,end_date FROM {$e} WHERE organizer_id=%d",$userId
			),ARRAY_A)??[]
		];
	}

	public static function downloadExport(int $exportId, int $userId): ?string {
		$export=self::getExportStatus($exportId,$userId);
		if(!$export||$export['status']!=='completed')return null;
		if($export['expires_at']&&strtotime($export['expires_at'])<time()){
			self::deleteExport($exportId,$userId);
			return null;
		}
		return $export['download_url']??null;
	}

	public static function deleteExport(int $exportId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		$export=$wpdb->get_row($wpdb->prepare("SELECT file_path FROM {$t} WHERE id=%d AND user_id=%d",$exportId,$userId),ARRAY_A);
		if($export&&!empty($export['file_path'])&&file_exists($export['file_path'])){
			unlink($export['file_path']);
		}
		return (bool)$wpdb->delete($t,['id'=>$exportId,'user_id'=>$userId],['%d','%d']);
	}

	public static function cleanupExpired(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		$expired=$wpdb->get_results("SELECT id,user_id,file_path FROM {$t} WHERE expires_at<NOW()",ARRAY_A)??[];
		$count=0;
		foreach($expired as $e){
			if(self::deleteExport((int)$e['id'],(int)$e['user_id']))$count++;
		}
		return $count;
	}

	public static function getPendingExports(int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EXPORTS;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE status='pending' ORDER BY requested_at ASC LIMIT %d",$limit
		),ARRAY_A)??[];
	}

	public static function processPendingExports(int $limit=5): int {
		$pending=self::getPendingExports($limit);
		$processed=0;
		foreach($pending as $export){
			if(self::processExport((int)$export['id']))$processed++;
		}
		return $processed;
	}
}
