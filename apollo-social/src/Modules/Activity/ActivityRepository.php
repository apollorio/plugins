<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

final class ActivityRepository {
	private const TABLE='apollo_activity';
	private const MENTIONS='apollo_mentions';
	private const FAVS='apollo_favorites';

	public static function create(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'user_id'=>(int)$d['user_id'],
			'action'=>sanitize_key($d['action']),
			'component'=>sanitize_key($d['component']),
			'type'=>sanitize_key($d['type']),
			'content'=>wp_kses_post($d['content']??''),
			'item_id'=>isset($d['item_id'])?(int)$d['item_id']:null,
			'secondary_item_id'=>isset($d['secondary_item_id'])?(int)$d['secondary_item_id']:null,
			'parent_id'=>isset($d['parent_id'])?(int)$d['parent_id']:null,
			'privacy'=>in_array($d['privacy']??'',['public','friends','private'])?$d['privacy']:'public',
			'is_spam'=>0
		]);
		$actId=(int)$wpdb->insert_id;
		if($actId&&!empty($d['content'])){
			self::extractMentions($actId,(int)$d['user_id'],$d['content']);
		}
		return $actId;
	}

	private static function extractMentions(int $actId,int $authorId,string $content): void {
		if(preg_match_all('/@([a-zA-Z0-9_]+)/',$content,$m)){
			global $wpdb;
			foreach(array_unique($m[1]) as $username){
				$user=get_user_by('login',$username);
				if($user&&$user->ID!==$authorId){
					$wpdb->insert($wpdb->prefix.self::MENTIONS,[
						'user_id'=>$user->ID,'mentioned_by'=>$authorId,'activity_id'=>$actId
					]);
				}
			}
		}
	}

	public static function get(int $id): ?array {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d AND is_spam=0",$id),ARRAY_A)?:null;
	}

	public static function getFeed(array $args=[]): array {
		global $wpdb;
		$defaults=['user_id'=>null,'component'=>null,'type'=>null,'privacy'=>'public','limit'=>20,'offset'=>0,'parent_id'=>null];
		$a=array_merge($defaults,$args);
		$where=['is_spam=0'];$params=[];
		if($a['user_id']){$where[]='user_id=%d';$params[]=(int)$a['user_id'];}
		if($a['component']){$where[]='component=%s';$params[]=$a['component'];}
		if($a['type']){$where[]='type=%s';$params[]=$a['type'];}
		if($a['privacy']){$where[]='privacy=%s';$params[]=$a['privacy'];}
		if($a['parent_id']!==null){$where[]='parent_id=%d';$params[]=(int)$a['parent_id'];}
		$w=implode(' AND ',$where);
		$params[]=$a['limit'];$params[]=$a['offset'];
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,u.user_email FROM {$wpdb->prefix}".self::TABLE." a
			 LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id
			 WHERE {$w} ORDER BY a.created_at DESC LIMIT %d OFFSET %d",...$params
		),ARRAY_A)?:[];
	}

	public static function getUserFeed(int $userId,int $viewerId,int $limit=20,int $offset=0): array {
		global $wpdb;
		$isSelf=$userId===$viewerId;
		$isFriend=self::areFriends($userId,$viewerId);
		$privacy=['public'];
		if($viewerId>0)$privacy[]='members';
		if($isFriend||$isSelf)$privacy[]='friends';
		if($isSelf)$privacy[]='private';
		$in="'".implode("','",$privacy)."'";
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$wpdb->prefix}".self::TABLE." a
			 LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id
			 WHERE a.user_id=%d AND a.is_spam=0 AND a.privacy IN ({$in}) ORDER BY a.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)?:[];
	}

	private static function areFriends(int $a,int $b): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}apollo_connections WHERE status='accepted' AND ((user_id=%d AND friend_id=%d) OR (user_id=%d AND friend_id=%d))",
			$a,$b,$b,$a
		));
	}

	public static function getGroupFeed(int $groupId,int $limit=20,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name FROM {$wpdb->prefix}".self::TABLE." a
			 LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id
			 WHERE a.component='group' AND a.item_id=%d AND a.is_spam=0 ORDER BY a.created_at DESC LIMIT %d OFFSET %d",
			$groupId,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function delete(int $id): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::MENTIONS,['activity_id'=>$id]);
		$wpdb->delete($wpdb->prefix.self::FAVS,['item_type'=>'activity','item_id'=>$id]);
		$wpdb->delete($wpdb->prefix.self::TABLE,['parent_id'=>$id]);
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id])!==false;
	}

	public static function markAsSpam(int $id): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['is_spam'=>1],['id'=>$id])!==false;
	}

	public static function getMentions(int $userId,bool $unreadOnly=false,int $limit=20,int $offset=0): array {
		global $wpdb;
		$w=$unreadOnly?'AND m.is_read=0':'';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT m.*,a.content,a.component,a.type,u.display_name as mentioned_by_name
			 FROM {$wpdb->prefix}".self::MENTIONS." m
			 LEFT JOIN {$wpdb->prefix}".self::TABLE." a ON a.id=m.activity_id
			 LEFT JOIN {$wpdb->users} u ON u.ID=m.mentioned_by
			 WHERE m.user_id=%d {$w} ORDER BY m.created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function markMentionRead(int $mentionId): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::MENTIONS,['is_read'=>1],['id'=>$mentionId])!==false;
	}

	public static function markAllMentionsRead(int $userId): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::MENTIONS,['is_read'=>1],['user_id'=>$userId])!==false;
	}

	public static function countUnreadMentions(int $userId): int {
		global $wpdb;
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::MENTIONS." WHERE user_id=%d AND is_read=0",$userId
		));
	}

	public static function addFavorite(int $userId,string $itemType,int $itemId): bool {
		global $wpdb;
		return $wpdb->replace($wpdb->prefix.self::FAVS,['user_id'=>$userId,'item_type'=>$itemType,'item_id'=>$itemId])!==false;
	}

	public static function removeFavorite(int $userId,string $itemType,int $itemId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::FAVS,['user_id'=>$userId,'item_type'=>$itemType,'item_id'=>$itemId])!==false;
	}

	public static function isFavorite(int $userId,string $itemType,int $itemId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}".self::FAVS." WHERE user_id=%d AND item_type=%s AND item_id=%d",
			$userId,$itemType,$itemId
		));
	}

	public static function getFavorites(int $userId,?string $itemType=null,int $limit=50,int $offset=0): array {
		global $wpdb;
		$w=$itemType?"AND item_type='{$itemType}'":'';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::FAVS." WHERE user_id=%d {$w} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)?:[];
	}
}
