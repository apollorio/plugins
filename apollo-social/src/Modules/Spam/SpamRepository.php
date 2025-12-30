<?php
declare(strict_types=1);
namespace Apollo\Modules\Spam;

final class SpamRepository {
	private const TABLE='apollo_spam_users';

	public static function markAsSpammer(int $userId, int $markedBy, string $reason=''): bool {
		global $wpdb;
		if(self::isSpammer($userId)){return true;}
		$t=$wpdb->prefix.self::TABLE;
		$result=$wpdb->insert($t,[
			'user_id'=>$userId,
			'marked_by'=>$markedBy,
			'reason'=>sanitize_text_field($reason),
			'marked_at'=>current_time('mysql')
		],['%d','%d','%s','%s']);
		if($result){
			update_user_meta($userId,'apollo_is_spammer',1);
			update_user_meta($userId,'apollo_spam_marked_at',current_time('mysql'));
			self::hideSpammerContent($userId);
			do_action('apollo_user_marked_spammer',$userId,$markedBy,$reason);
		}
		return $result!==false;
	}

	public static function unmarkSpammer(int $userId, int $unmarkedBy): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$result=$wpdb->delete($t,['user_id'=>$userId],['%d'])!==false;
		if($result){
			delete_user_meta($userId,'apollo_is_spammer');
			delete_user_meta($userId,'apollo_spam_marked_at');
			self::restoreSpammerContent($userId);
			do_action('apollo_user_unmarked_spammer',$userId,$unmarkedBy);
		}
		return $result;
	}

	public static function isSpammer(int $userId): bool {
		return (bool)get_user_meta($userId,'apollo_is_spammer',true);
	}

	public static function getSpammers(int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT s.*,u.display_name,u.user_email,marker.display_name as marked_by_name FROM {$t} s JOIN {$wpdb->users} u ON s.user_id=u.ID LEFT JOIN {$wpdb->users} marker ON s.marked_by=marker.ID ORDER BY s.marked_at DESC LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getSpammerCount(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}");
	}

	public static function getSpammerDetails(int $userId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$row=$wpdb->get_row($wpdb->prepare(
			"SELECT s.*,marker.display_name as marked_by_name FROM {$t} s LEFT JOIN {$wpdb->users} marker ON s.marked_by=marker.ID WHERE s.user_id=%d",
			$userId
		),ARRAY_A);
		return $row?:null;
	}

	public static function reportSpam(int $reporterId, int $userId, string $reason, int $contentId=0, string $contentType=''): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_reports';
		$wpdb->insert($t,[
			'reporter_id'=>$reporterId,
			'user_id'=>$userId,
			'content_id'=>$contentId,
			'content_type'=>$contentType,
			'reason'=>sanitize_text_field($reason),
			'status'=>'pending',
			'created_at'=>current_time('mysql')
		],['%d','%d','%d','%s','%s','%s','%s']);
		$id=(int)$wpdb->insert_id;
		self::checkAutoMark($userId);
		return $id;
	}

	public static function getPendingReports(int $limit=50): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_reports';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT r.*,reporter.display_name as reporter_name,reported.display_name as user_name FROM {$t} r JOIN {$wpdb->users} reporter ON r.reporter_id=reporter.ID JOIN {$wpdb->users} reported ON r.user_id=reported.ID WHERE r.status='pending' ORDER BY r.created_at ASC LIMIT %d",
			$limit
		),ARRAY_A)??[];
	}

	public static function resolveReport(int $reportId, int $resolvedBy, string $action, string $notes=''): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_reports';
		return $wpdb->update($t,[
			'status'=>'resolved',
			'action'=>$action,
			'resolved_by'=>$resolvedBy,
			'resolved_at'=>current_time('mysql'),
			'notes'=>$notes
		],['id'=>$reportId],['%s','%s','%d','%s','%s'],['%d'])!==false;
	}

	public static function getReportStats(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_reports';
		return [
			'total'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d",$userId)),
			'pending'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND status='pending'",$userId)),
			'confirmed'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND action='spam'",$userId)),
			'dismissed'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND action='not_spam'",$userId))
		];
	}

	public static function addToBlacklist(string $value, string $type, int $addedBy): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_blacklist';
		return $wpdb->insert($t,[
			'value'=>$value,
			'type'=>$type,
			'added_by'=>$addedBy,
			'created_at'=>current_time('mysql')
		],['%s','%s','%d','%s'])!==false;
	}

	public static function removeFromBlacklist(int $id): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_blacklist';
		return $wpdb->delete($t,['id'=>$id],['%d'])!==false;
	}

	public static function isBlacklisted(string $value, string $type): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_blacklist';
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE value=%s AND type=%s",$value,$type));
	}

	public static function getBlacklist(string $type='all'): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spam_blacklist';
		$where=$type!=='all'?$wpdb->prepare(" WHERE type=%s",$type):'';
		return $wpdb->get_results("SELECT * FROM {$t}{$where} ORDER BY created_at DESC",ARRAY_A)??[];
	}

	public static function checkContent(string $content): array {
		$issues=[];
		$blacklist=self::getBlacklist('word');
		foreach($blacklist as $item){
			if(stripos($content,$item['value'])!==false){
				$issues[]=['type'=>'blacklisted_word','value'=>$item['value']];
			}
		}
		$urlCount=preg_match_all('/https?:\/\/[^\s]+/',$content);
		if($urlCount>3){$issues[]=['type'=>'too_many_urls','count'=>$urlCount];}
		$emailCount=preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i',$content);
		if($emailCount>2){$issues[]=['type'=>'too_many_emails','count'=>$emailCount];}
		return $issues;
	}

	public static function getSpamScore(int $userId): int {
		$score=0;
		$stats=self::getReportStats($userId);
		$score+=$stats['confirmed']*20;
		$score+=$stats['pending']*5;
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activities';
		$recentPosts=(int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND created_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR)",
			$userId
		));
		if($recentPosts>10){$score+=($recentPosts-10)*2;}
		$regDate=get_userdata($userId)->user_registered??null;
		if($regDate&&strtotime($regDate)>strtotime('-24 hours')){$score+=10;}
		return min(100,$score);
	}

	private static function checkAutoMark(int $userId): void {
		$threshold=(int)get_option('apollo_spam_auto_mark_threshold',5);
		$stats=self::getReportStats($userId);
		if($stats['pending']>=$threshold){
			self::markAsSpammer($userId,0,'Auto-marcado: múltiplos reports');
		}
	}

	private static function hideSpammerContent(int $userId): void {
		global $wpdb;
		$wpdb->update($wpdb->prefix.'apollo_activities',['is_hidden'=>1],['user_id'=>$userId],['%d'],['%d']);
		$wpdb->update($wpdb->prefix.'apollo_forum_topics',['is_closed'=>1],['user_id'=>$userId],['%d'],['%d']);
		$wpdb->update($wpdb->prefix.'apollo_activity_comments',['is_hidden'=>1],['user_id'=>$userId],['%d'],['%d']);
	}

	private static function restoreSpammerContent(int $userId): void {
		global $wpdb;
		$wpdb->update($wpdb->prefix.'apollo_activities',['is_hidden'=>0],['user_id'=>$userId],['%d'],['%d']);
	}
}
