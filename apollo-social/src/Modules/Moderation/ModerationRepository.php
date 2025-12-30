<?php
declare(strict_types=1);
namespace Apollo\Modules\Moderation;

final class ModerationRepository {
	private const QUEUE='apollo_mod_queue';

	public static function addToQueue(string $contentType, int $contentId, int $authorId, string $title, array $meta=[]): ?int {
		global $wpdb;
		$exists=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}".self::QUEUE." WHERE content_type=%s AND content_id=%d AND status='pending'",
			$contentType,$contentId
		));
		if($exists){return (int)$exists;}
		$r=$wpdb->insert($wpdb->prefix.self::QUEUE,[
			'content_type'=>$contentType,
			'content_id'=>$contentId,
			'author_id'=>$authorId,
			'title'=>$title,
			'status'=>'pending',
			'priority'=>$meta['priority']??1,
			'metadata'=>json_encode($meta)
		],['%s','%d','%d','%s','%s','%d','%s']);
		return $r?(int)$wpdb->insert_id:null;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$id),ARRAY_A);
		if($row&&$row['metadata']){$row['metadata']=json_decode($row['metadata'],true);}
		return $row;
	}

	public static function getPending(int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT q.*,u.display_name as author_name FROM {$t} q JOIN {$wpdb->users} u ON q.author_id=u.ID WHERE q.status='pending' ORDER BY q.priority DESC,q.submitted_at ASC LIMIT %d OFFSET %d",
			$limit,$offset
		),ARRAY_A)??[];
		foreach($rows as &$r){if($r['metadata']){$r['metadata']=json_decode($r['metadata'],true);}}
		return $rows;
	}

	public static function getByType(string $contentType, string $status='pending', int $limit=50): array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE content_type=%s AND status=%s ORDER BY submitted_at ASC LIMIT %d",
			$contentType,$status,$limit
		),ARRAY_A)??[];
	}

	public static function approve(int $id, int $moderatorId, string $notes=''): bool {
		global $wpdb;
		$item=self::get($id);
		if(!$item){return false;}
		$r=$wpdb->update($wpdb->prefix.self::QUEUE,[
			'status'=>'approved',
			'reviewer_id'=>$moderatorId,
			'review_notes'=>$notes,
			'reviewed_at'=>current_time('mysql')
		],['id'=>$id]);
		if($r){
			self::updateContentStatus($item['content_type'],(int)$item['content_id'],'published');
			do_action('apollo_content_approved',$item['content_type'],$item['content_id'],$moderatorId);
		}
		return (bool)$r;
	}

	public static function reject(int $id, int $moderatorId, string $reason=''): bool {
		global $wpdb;
		$item=self::get($id);
		if(!$item){return false;}
		$r=$wpdb->update($wpdb->prefix.self::QUEUE,[
			'status'=>'rejected',
			'reviewer_id'=>$moderatorId,
			'review_notes'=>$reason,
			'reviewed_at'=>current_time('mysql')
		],['id'=>$id]);
		if($r){
			self::updateContentStatus($item['content_type'],(int)$item['content_id'],'rejected');
			self::notifyAuthor((int)$item['author_id'],$item['content_type'],$reason);
			do_action('apollo_content_rejected',$item['content_type'],$item['content_id'],$moderatorId,$reason);
		}
		return (bool)$r;
	}

	public static function escalate(int $id, int $moderatorId, string $reason=''): bool {
		global $wpdb;
		return (bool)$wpdb->update($wpdb->prefix.self::QUEUE,[
			'status'=>'escalated',
			'priority'=>3,
			'assigned_moderator_id'=>null,
			'review_notes'=>$reason
		],['id'=>$id]);
	}

	public static function assign(int $id, int $moderatorId): bool {
		global $wpdb;
		return (bool)$wpdb->update($wpdb->prefix.self::QUEUE,['assigned_moderator_id'=>$moderatorId],['id'=>$id,'status'=>'pending']);
	}

	public static function unassign(int $id): bool {
		global $wpdb;
		return (bool)$wpdb->update($wpdb->prefix.self::QUEUE,['assigned_moderator_id'=>null],['id'=>$id]);
	}

	public static function getAssigned(int $moderatorId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE assigned_moderator_id=%d AND status='pending' ORDER BY priority DESC,submitted_at ASC LIMIT %d",
			$moderatorId,$limit
		),ARRAY_A)??[];
	}

	public static function countPending(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='pending'");
	}

	public static function countByStatus(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		return $wpdb->get_results("SELECT status,COUNT(*) as count FROM {$t} GROUP BY status",ARRAY_A)??[];
	}

	public static function countByType(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		return $wpdb->get_results("SELECT content_type,COUNT(*) as count FROM {$t} WHERE status='pending' GROUP BY content_type",ARRAY_A)??[];
	}

	public static function getModeratorStats(int $moderatorId, int $days=30): array {
		global $wpdb;
		$t=$wpdb->prefix.self::QUEUE;
		$since=gmdate('Y-m-d H:i:s',strtotime("-{$days} days"));
		return [
			'approved'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE reviewer_id=%d AND status='approved' AND reviewed_at>=%s",$moderatorId,$since)),
			'rejected'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE reviewer_id=%d AND status='rejected' AND reviewed_at>=%s",$moderatorId,$since)),
			'avg_time'=>$wpdb->get_var($wpdb->prepare(
				"SELECT AVG(TIMESTAMPDIFF(MINUTE,submitted_at,reviewed_at)) FROM {$t} WHERE reviewer_id=%d AND reviewed_at IS NOT NULL AND reviewed_at>=%s",
				$moderatorId,$since
			))
		];
	}

	private static function updateContentStatus(string $type, int $id, string $status): void {
		global $wpdb;
		$table=match($type){
			'activity'=>$wpdb->prefix.'apollo_activity',
			'group'=>$wpdb->prefix.'apollo_groups',
			'forum_topic'=>$wpdb->prefix.'apollo_forum_topics',
			'advert'=>$wpdb->prefix.'apollo_adverts',
			'event'=>$wpdb->prefix.'apollo_events',
			'testimonial'=>$wpdb->prefix.'apollo_testimonials',
			default=>null
		};
		if($table){$wpdb->update($table,['status'=>$status],['id'=>$id]);}
	}

	private static function notifyAuthor(int $authorId, string $contentType, string $reason): void {
		do_action('apollo_notify',$authorId,'content_rejected','Seu conteúdo foi rejeitado',[
			'content'=>"Tipo: {$contentType}. Motivo: {$reason}",
			'link'=>home_url('/my-content/')
		]);
	}

	public static function hideActivity(int $activityId, int $moderatorId, string $reason=''): bool {
		global $wpdb;
		$r=$wpdb->update($wpdb->prefix.'apollo_activity',['is_hidden'=>1],['id'=>$activityId]);
		if($r){
			self::logAction('hide_activity',$activityId,$moderatorId,$reason);
			do_action('apollo_activity_hidden',$activityId,$moderatorId);
		}
		return (bool)$r;
	}

	public static function unhideActivity(int $activityId, int $moderatorId): bool {
		global $wpdb;
		$r=$wpdb->update($wpdb->prefix.'apollo_activity',['is_hidden'=>0],['id'=>$activityId]);
		if($r){self::logAction('unhide_activity',$activityId,$moderatorId,'');}
		return (bool)$r;
	}

	public static function deleteActivity(int $activityId, int $moderatorId, string $reason=''): bool {
		global $wpdb;
		$activity=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}apollo_activity WHERE id=%d",$activityId),ARRAY_A);
		if(!$activity){return false;}
		self::logAction('delete_activity',$activityId,$moderatorId,$reason,['backup'=>$activity]);
		$wpdb->delete($wpdb->prefix.'apollo_activity_comments',['activity_id'=>$activityId],['%d']);
		$wpdb->delete($wpdb->prefix.'apollo_activity_likes',['activity_id'=>$activityId],['%d']);
		$wpdb->delete($wpdb->prefix.'apollo_mentions',['activity_id'=>$activityId,'activity_type'=>'activity'],['%d','%s']);
		return (bool)$wpdb->delete($wpdb->prefix.'apollo_activity',['id'=>$activityId],['%d']);
	}

	private static function logAction(string $action, int $contentId, int $moderatorId, string $reason, array $extra=[]): void {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.'apollo_workflow_log',[
			'content_id'=>$contentId,
			'content_type'=>'moderation',
			'from_state'=>'',
			'to_state'=>$action,
			'user_id'=>$moderatorId,
			'reason'=>$reason,
			'metadata'=>json_encode($extra)
		],['%d','%s','%s','%s','%d','%s','%s']);
	}

	public static function getModerators(): array {
		$admins=get_users(['role'=>'administrator','fields'=>['ID','display_name']]);
		$mods=get_users(['role'=>'moderator','fields'=>['ID','display_name']]);
		return array_merge($admins,$mods);
	}

	public static function isModerator(int $userId): bool {
		$user=get_userdata($userId);
		if(!$user){return false;}
		return $user->has_cap('manage_options')||in_array('moderator',$user->roles,true);
	}
}
