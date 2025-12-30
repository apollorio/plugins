<?php
declare(strict_types=1);
namespace Apollo\Modules\Stories;

final class StoriesRepository {
	private const T_STORIES='apollo_stories';
	private const T_VIEWS='apollo_story_views';
	private const T_REPLIES='apollo_story_replies';

	public static function create(int $userId, string $mediaUrl, string $mediaType='image', array $data=[]): int|false {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		$expiresAt=gmdate('Y-m-d H:i:s',strtotime('+24 hours'));
		$wpdb->insert($t,[
			'user_id'=>$userId,
			'media_url'=>$mediaUrl,
			'media_type'=>$mediaType,
			'caption'=>$data['caption']??'',
			'background_color'=>$data['background_color']??null,
			'text_overlay'=>$data['text_overlay']??null,
			'link_url'=>$data['link_url']??null,
			'location'=>$data['location']??null,
			'visibility'=>$data['visibility']??'friends',
			'duration'=>$data['duration']??5,
			'expires_at'=>$expiresAt,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
		return $wpdb->insert_id?:false;
	}

	public static function get(int $storyId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND is_deleted=0",$storyId),ARRAY_A);
	}

	public static function delete(int $storyId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return (bool)$wpdb->update($t,['is_deleted'=>1],['id'=>$storyId,'user_id'=>$userId],['%d'],['%d','%d']);
	}

	public static function getUserStories(int $userId, int $viewerId=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		$stories=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d AND expires_at>NOW() AND is_deleted=0 ORDER BY created_at ASC",
			$userId
		),ARRAY_A)??[];
		if($viewerId>0){
			$v=$wpdb->prefix.self::T_VIEWS;
			foreach($stories as &$story){
				$story['viewed']=(bool)$wpdb->get_var($wpdb->prepare(
					"SELECT id FROM {$v} WHERE story_id=%d AND viewer_id=%d",$story['id'],$viewerId
				));
			}
		}
		return $stories;
	}

	public static function getFeed(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		$c=$wpdb->prefix.'apollo_connections';
		$f=$wpdb->prefix.'apollo_followers';
		$v=$wpdb->prefix.self::T_VIEWS;
		$friendIds=$wpdb->get_col($wpdb->prepare(
			"SELECT CASE WHEN user_id=%d THEN friend_id ELSE user_id END FROM {$c} WHERE (user_id=%d OR friend_id=%d) AND status='accepted'",
			$userId,$userId,$userId
		));
		$followingIds=$wpdb->get_col($wpdb->prepare("SELECT following_id FROM {$f} WHERE follower_id=%d",$userId));
		$allIds=array_unique(array_merge($friendIds,$followingIds,[$userId]));
		if(empty($allIds))return [];
		$placeholders=\implode(',',\array_fill(0,\count($allIds),'%d'));
		$users=$wpdb->get_results($wpdb->prepare(
			"SELECT DISTINCT s.user_id,u.display_name,
			(SELECT COUNT(*) FROM {$t} WHERE user_id=s.user_id AND expires_at>NOW() AND is_deleted=0) as story_count,
			(SELECT MAX(created_at) FROM {$t} WHERE user_id=s.user_id AND expires_at>NOW() AND is_deleted=0) as latest,
			(SELECT COUNT(*) FROM {$t} st JOIN {$v} sv ON st.id=sv.story_id WHERE st.user_id=s.user_id AND st.expires_at>NOW() AND st.is_deleted=0 AND sv.viewer_id=%d) as viewed_count
			FROM {$t} s
			JOIN {$wpdb->users} u ON s.user_id=u.ID
			WHERE s.user_id IN({$placeholders}) AND s.expires_at>NOW() AND s.is_deleted=0
			GROUP BY s.user_id
			ORDER BY CASE WHEN s.user_id=%d THEN 0 ELSE 1 END, latest DESC
			LIMIT %d",
			array_merge([$userId],$allIds,[$userId,$limit])
		),ARRAY_A)??[];
		foreach($users as &$user){
			$user['has_unviewed']=(int)$user['story_count']>(int)$user['viewed_count'];
		}
		return $users;
	}

	public static function view(int $storyId, int $viewerId): bool {
		global $wpdb;
		$v=$wpdb->prefix.self::T_VIEWS;
		$story=self::get($storyId);
		if(!$story||(int)$story['user_id']===$viewerId)return false;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$v} WHERE story_id=%d AND viewer_id=%d",$storyId,$viewerId));
		if($exists)return true;
		$wpdb->insert($v,[
			'story_id'=>$storyId,
			'viewer_id'=>$viewerId,
			'viewed_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s']);
		$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}".self::T_STORIES." SET view_count=view_count+1 WHERE id=%d",$storyId));
		return true;
	}

	public static function getViewers(int $storyId, int $limit=50): array {
		global $wpdb;
		$v=$wpdb->prefix.self::T_VIEWS;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT sv.viewer_id,sv.viewed_at,u.display_name FROM {$v} sv JOIN {$wpdb->users} u ON sv.viewer_id=u.ID WHERE sv.story_id=%d ORDER BY sv.viewed_at DESC LIMIT %d",
			$storyId,$limit
		),ARRAY_A)??[];
	}

	public static function getViewCount(int $storyId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT view_count FROM {$t} WHERE id=%d",$storyId));
	}

	public static function reply(int $storyId, int $userId, string $message, string $type='text'): int|false {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REPLIES;
		$story=self::get($storyId);
		if(!$story)return false;
		$wpdb->insert($r,[
			'story_id'=>$storyId,
			'story_owner_id'=>$story['user_id'],
			'sender_id'=>$userId,
			'message'=>$message,
			'type'=>$type,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%d','%s','%s','%s']);
		return $wpdb->insert_id?:false;
	}

	public static function getReplies(int $storyId, int $ownerId): array {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REPLIES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT sr.*,u.display_name FROM {$r} sr JOIN {$wpdb->users} u ON sr.sender_id=u.ID WHERE sr.story_id=%d AND sr.story_owner_id=%d ORDER BY sr.created_at DESC",
			$storyId,$ownerId
		),ARRAY_A)??[];
	}

	public static function markReplyRead(int $replyId, int $ownerId): bool {
		global $wpdb;
		$r=$wpdb->prefix.self::T_REPLIES;
		return (bool)$wpdb->update($r,['is_read'=>1],['id'=>$replyId,'story_owner_id'=>$ownerId],['%d'],['%d','%d']);
	}

	public static function getArchive(int $userId, int $limit=50, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d AND is_deleted=0 ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function highlight(int $storyId, int $userId, string $highlightName): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return (bool)$wpdb->update($t,['is_highlighted'=>1,'highlight_name'=>$highlightName],['id'=>$storyId,'user_id'=>$userId],['%d','%s'],['%d','%d']);
	}

	public static function getHighlights(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT highlight_name,COUNT(*) as count,MIN(created_at) as first,MAX(created_at) as last FROM {$t} WHERE user_id=%d AND is_highlighted=1 AND is_deleted=0 GROUP BY highlight_name ORDER BY last DESC",
			$userId
		),ARRAY_A)??[];
	}

	public static function getHighlightStories(int $userId, string $highlightName): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id=%d AND highlight_name=%s AND is_highlighted=1 AND is_deleted=0 ORDER BY created_at ASC",
			$userId,$highlightName
		),ARRAY_A)??[];
	}

	public static function cleanupExpired(): int {
		global $wpdb;
		$t=$wpdb->prefix.self::T_STORIES;
		$threshold=gmdate('Y-m-d H:i:s',strtotime('-30 days'));
		return (int)$wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE expires_at<%s AND is_highlighted=0",$threshold));
	}
}
