<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

final class MentionsRepository {
	private const TABLE='apollo_mentions';
	private const PATTERN='/@([a-zA-Z0-9_]+)/';

	public static function extract(string $content): array {
		preg_match_all(self::PATTERN,$content,$matches);
		if(empty($matches[1])){return [];}
		$usernames=array_unique($matches[1]);
		global $wpdb;
		$placeholders=\implode(',',array_fill(0,count($usernames),'%s'));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT ID,user_login,display_name FROM {$wpdb->users} WHERE user_login IN ({$placeholders})",
			...$usernames
		),ARRAY_A)??[];
	}

	public static function process(int $activityId, string $activityType, int $authorId, string $content): int {
		$users=self::extract($content);
		if(empty($users)){return 0;}
		$count=0;
		foreach($users as $user){
			if((int)$user['ID']===$authorId){continue;}
			if(self::create($activityId,$activityType,(int)$user['ID'],$authorId)){
				self::notifyUser((int)$user['ID'],$authorId,$activityType,$activityId);
				$count++;
			}
		}
		return $count;
	}

	public static function create(int $activityId, string $activityType, int $userId, int $mentionedBy): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$exists=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$t} WHERE activity_id=%d AND activity_type=%s AND user_id=%d",
			$activityId,$activityType,$userId
		));
		if($exists){return false;}
		return (bool)$wpdb->insert($t,[
			'activity_id'=>$activityId,
			'activity_type'=>$activityType,
			'user_id'=>$userId,
			'mentioned_by'=>$mentionedBy
		],['%d','%s','%d','%d']);
	}

	public static function getForUser(int $userId, int $limit=20, int $offset=0, bool $unreadOnly=false): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$where='m.user_id=%d';
		if($unreadOnly){$where.=' AND m.is_read=0';}
		return $wpdb->get_results($wpdb->prepare(
			"SELECT m.*,u.display_name as mentioner_name,u.user_login as mentioner_login FROM {$t} m JOIN {$wpdb->users} u ON m.mentioned_by=u.ID WHERE {$where} ORDER BY m.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function markRead(int $id, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (bool)$wpdb->update($t,['is_read'=>1,'read_at'=>current_time('mysql')],['id'=>$id,'user_id'=>$userId]);
	}

	public static function markAllRead(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->query($wpdb->prepare(
			"UPDATE {$t} SET is_read=1,read_at=%s WHERE user_id=%d AND is_read=0",
			current_time('mysql'),$userId
		));
	}

	public static function getUnreadCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND is_read=0",$userId));
	}

	public static function delete(int $activityId, string $activityType): bool {
		global $wpdb;
		return (bool)$wpdb->delete($wpdb->prefix.self::TABLE,['activity_id'=>$activityId,'activity_type'=>$activityType],['%d','%s']);
	}

	public static function getMentionedUsers(int $activityId, string $activityType): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT m.user_id,u.display_name,u.user_login FROM {$t} m JOIN {$wpdb->users} u ON m.user_id=u.ID WHERE m.activity_id=%d AND m.activity_type=%s",
			$activityId,$activityType
		),ARRAY_A)??[];
	}

	public static function getUserMentionStats(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return [
			'total_received'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d",$userId)),
			'unread'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND is_read=0",$userId)),
			'total_sent'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE mentioned_by=%d",$userId)),
			'last_mention'=>$wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$t} WHERE user_id=%d ORDER BY created_at DESC LIMIT 1",$userId))
		];
	}

	public static function getMostMentioned(int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT user_id,COUNT(*) as mention_count,u.display_name FROM {$t} m JOIN {$wpdb->users} u ON m.user_id=u.ID GROUP BY user_id ORDER BY mention_count DESC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getMostActiveMentioners(int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT mentioned_by,COUNT(*) as mention_count,u.display_name FROM {$t} m JOIN {$wpdb->users} u ON m.mentioned_by=u.ID GROUP BY mentioned_by ORDER BY mention_count DESC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function getRecentMentionsBetween(int $userId1, int $userId2, int $limit=10): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE (user_id=%d AND mentioned_by=%d) OR (user_id=%d AND mentioned_by=%d) ORDER BY created_at DESC LIMIT %d",
			$userId1,$userId2,$userId2,$userId1,$limit
		),ARRAY_A)??[];
	}

	public static function renderWithLinks(string $content): string {
		return preg_replace_callback(self::PATTERN,function($m){
			$user=get_user_by('login',$m[1]);
			if(!$user){return $m[0];}
			$url=home_url('/members/'.$user->user_nicename.'/');
			return sprintf('<a href="%s" class="mention" data-user-id="%d">@%s</a>',esc_url($url),esc_attr($user->ID),esc_html($m[1]));
		},$content);
	}

	public static function autocomplete(string $query, int $limit=10, ?int $excludeUserId=null): array {
		global $wpdb;
		$like=$wpdb->esc_like($query).'%';
		$exclude=$excludeUserId?' AND ID!='.(int)$excludeUserId:'';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT ID,user_login,display_name FROM {$wpdb->users} WHERE (user_login LIKE %s OR display_name LIKE %s){$exclude} ORDER BY display_name ASC LIMIT %d",
			$like,$like,$limit
		),ARRAY_A)??[];
	}

	private static function notifyUser(int $userId, int $actorId, string $type, int $activityId): void {
		do_action('apollo_notify',$userId,'mention','Você foi mencionado',[
			'actor_id'=>$actorId,
			'object_type'=>$type,
			'object_id'=>$activityId,
			'link'=>home_url('/activity/'.$activityId.'/')
		]);
	}

	public static function cleanOld(int $days=90): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$cutoff=gmdate('Y-m-d H:i:s',strtotime("-{$days} days"));
		return (int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE is_read=1 AND created_at<%s",$cutoff));
	}
}
