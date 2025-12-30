<?php
declare(strict_types=1);
namespace Apollo\Modules\Forum;

final class ForumRepository {
	private const TOPICS='apollo_forum_topics';
	private const REPLIES='apollo_forum_replies';

	public static function createTopic(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TOPICS,[
			'title'=>sanitize_text_field($d['title']),
			'content'=>wp_kses_post($d['content']),
			'author_id'=>(int)$d['author_id'],
			'group_id'=>isset($d['group_id'])?(int)$d['group_id']:null,
			'status'=>'open',
			'is_sticky'=>0,
			'is_closed'=>0
		]);
		return (int)$wpdb->insert_id;
	}

	public static function getTopic(int $id): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TOPICS." WHERE id=%d",$id),ARRAY_A);
		if($r){
			$r['reply_count']=self::countReplies($id);
			$r['author']=get_userdata((int)$r['author_id']);
		}
		return $r?:null;
	}

	public static function getTopics(array $args=[]): array {
		global $wpdb;
		$defaults=['group_id'=>null,'status'=>'open','search'=>'','orderby'=>'is_sticky DESC, last_activity_at','order'=>'DESC','limit'=>20,'offset'=>0];
		$a=array_merge($defaults,$args);
		$where=['1=1'];$params=[];
		if($a['group_id']){$where[]='group_id=%d';$params[]=(int)$a['group_id'];}
		if($a['status']){$where[]='status=%s';$params[]=$a['status'];}
		if($a['search']){
			$where[]="(title LIKE %s OR content LIKE %s)";
			$like='%'.$wpdb->esc_like($a['search']).'%';
			$params=array_merge($params,[$like,$like]);
		}
		$w=\implode(' AND ',$where);
		$params[]=$a['limit'];$params[]=$a['offset'];
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT t.*,u.display_name as author_name FROM {$wpdb->prefix}".self::TOPICS." t
			 LEFT JOIN {$wpdb->users} u ON u.ID=t.author_id
			 WHERE {$w} ORDER BY {$a['orderby']} {$a['order']} LIMIT %d OFFSET %d",
			...$params
		),ARRAY_A)?:[];
		foreach($rows as &$r){$r['reply_count']=self::countReplies((int)$r['id']);}
		return $rows;
	}

	public static function createReply(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::REPLIES,[
			'topic_id'=>(int)$d['topic_id'],
			'content'=>wp_kses_post($d['content']),
			'author_id'=>(int)$d['author_id'],
			'parent_id'=>isset($d['parent_id'])?(int)$d['parent_id']:null
		]);
		$replyId=(int)$wpdb->insert_id;
		if($replyId){
			$wpdb->update($wpdb->prefix.self::TOPICS,['last_activity_at'=>current_time('mysql'),'reply_count'=>self::countReplies((int)$d['topic_id'])],['id'=>(int)$d['topic_id']]);
		}
		return $replyId;
	}

	public static function getReply(int $id): ?array {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::REPLIES." WHERE id=%d",$id),ARRAY_A)?:null;
	}

	public static function getReplies(int $topicId,int $limit=50,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT r.*,u.display_name as author_name FROM {$wpdb->prefix}".self::REPLIES." r
			 LEFT JOIN {$wpdb->users} u ON u.ID=r.author_id
			 WHERE r.topic_id=%d AND r.status='visible' ORDER BY r.created_at LIMIT %d OFFSET %d",
			$topicId,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function countReplies(int $topicId): int {
		global $wpdb;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::REPLIES." WHERE topic_id=%d AND status='visible'",$topicId
		));
	}

	public static function updateTopic(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		foreach(['title','content','status','is_sticky','is_closed'] as $k){
			if(isset($d[$k]))$u[$k]=$d[$k];
		}
		return $wpdb->update($wpdb->prefix.self::TOPICS,$u,['id'=>$id])!==false;
	}

	public static function updateReply(int $id,array $d): bool {
		global $wpdb;
		$u=[];
		if(isset($d['content']))$u['content']=wp_kses_post($d['content']);
		if(isset($d['status']))$u['status']=$d['status'];
		return $wpdb->update($wpdb->prefix.self::REPLIES,$u,['id'=>$id])!==false;
	}

	public static function deleteTopic(int $id): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::REPLIES,['topic_id'=>$id]);
		return $wpdb->delete($wpdb->prefix.self::TOPICS,['id'=>$id])!==false;
	}

	public static function deleteReply(int $id): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::REPLIES,['id'=>$id])!==false;
	}

	public static function toggleSticky(int $topicId): bool {
		global $wpdb;
		return $wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}".self::TOPICS." SET is_sticky=NOT is_sticky WHERE id=%d",$topicId
		))!==false;
	}

	public static function toggleClosed(int $topicId): bool {
		global $wpdb;
		return $wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}".self::TOPICS." SET is_closed=NOT is_closed WHERE id=%d",$topicId
		))!==false;
	}

	public static function incrementViews(int $topicId): void {
		global $wpdb;
		$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}".self::TOPICS." SET view_count=view_count+1 WHERE id=%d",$topicId));
	}
}
