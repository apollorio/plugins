<?php
declare(strict_types=1);
namespace Apollo\Api;

final class AjaxHandlers {

	public static function register(): void {
		$actions=[
			'apollo_send_friend_request'=>[self::class,'sendFriendRequest'],
			'apollo_accept_friend'=>[self::class,'acceptFriend'],
			'apollo_reject_friend'=>[self::class,'rejectFriend'],
			'apollo_remove_friend'=>[self::class,'removeFriend'],
			'apollo_block_user'=>[self::class,'blockUser'],
			'apollo_add_close_friend'=>[self::class,'addCloseFriend'],
			'apollo_remove_close_friend'=>[self::class,'removeCloseFriend'],
			'apollo_post_activity'=>[self::class,'postActivity'],
			'apollo_delete_activity'=>[self::class,'deleteActivity'],
			'apollo_toggle_favorite'=>[self::class,'toggleFavorite'],
			'apollo_dismiss_notice'=>[self::class,'dismissNotice'],
			'apollo_mark_mentions_read'=>[self::class,'markMentionsRead'],
			'apollo_update_settings'=>[self::class,'updateSettings'],
			'apollo_join_group'=>[self::class,'joinGroup'],
			'apollo_leave_group'=>[self::class,'leaveGroup'],
			'apollo_join_competition'=>[self::class,'joinCompetition'],
			'apollo_search_members'=>[self::class,'searchMembers'],
			'apollo_get_online_users'=>[self::class,'getOnlineUsers'],
			'apollo_mark_interested'=>[self::class,'markInterested'],
			'apollo_forum_new_topic'=>[self::class,'newForumTopic'],
			'apollo_forum_reply'=>[self::class,'forumReply'],
			'apollo_save_profile_field'=>[self::class,'saveProfileField']
		];
		foreach($actions as $action=>$callback){
			add_action("wp_ajax_{$action}",$callback);
		}
	}

	private static function verify(): int {
		if(!check_ajax_referer('apollo_nonce','nonce',false)){
			wp_send_json_error(['message'=>'Invalid nonce'],403);
		}
		$userId=get_current_user_id();
		if(!$userId){
			wp_send_json_error(['message'=>'Not authenticated'],401);
		}
		return $userId;
	}

	public static function sendFriendRequest(): void {
		$userId=self::verify();
		$friendId=(int)($_POST['friend_id']??0);
		if($friendId<=0){wp_send_json_error(['message'=>'Invalid user'],400);}
		$result=\Apollo\Modules\Connections\ConnectionsRepository::sendRequest($userId,$friendId);
		$result?wp_send_json_success(['message'=>'Request sent']):wp_send_json_error(['message'=>'Failed to send request'],500);
	}

	public static function acceptFriend(): void {
		$userId=self::verify();
		$friendId=(int)($_POST['friend_id']??0);
		$result=\Apollo\Modules\Connections\ConnectionsRepository::acceptRequest($friendId,$userId);
		$result?wp_send_json_success(['message'=>'Friend added']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function rejectFriend(): void {
		$userId=self::verify();
		$friendId=(int)($_POST['friend_id']??0);
		$result=\Apollo\Modules\Connections\ConnectionsRepository::rejectRequest($friendId,$userId);
		$result?wp_send_json_success(['message'=>'Request rejected']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function removeFriend(): void {
		$userId=self::verify();
		$friendId=(int)($_POST['friend_id']??0);
		$result=\Apollo\Modules\Connections\ConnectionsRepository::removeFriend($userId,$friendId);
		$result?wp_send_json_success(['message'=>'Friend removed']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function blockUser(): void {
		$userId=self::verify();
		$targetId=(int)($_POST['user_id']??0);
		$result=\Apollo\Modules\Connections\ConnectionsRepository::blockUser($userId,$targetId);
		$result?wp_send_json_success(['message'=>'User blocked']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function addCloseFriend(): void {
		$userId=self::verify();
		$friendId=(int)($_POST['friend_id']??0);
		$result=\Apollo\Modules\Connections\ConnectionsRepository::addCloseFriend($userId,$friendId);
		$result?wp_send_json_success(['message'=>'Added to close friends']):wp_send_json_error(['message'=>'Maximum 10 close friends allowed'],400);
	}

	public static function removeCloseFriend(): void {
		$userId=self::verify();
		$friendId=(int)($_POST['friend_id']??0);
		$result=\Apollo\Modules\Connections\ConnectionsRepository::removeCloseFriend($userId,$friendId);
		$result?wp_send_json_success(['message'=>'Removed from close friends']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function postActivity(): void {
		$userId=self::verify();
		$content=sanitize_textarea_field($_POST['content']??'');
		if(empty($content)){wp_send_json_error(['message'=>'Content required'],400);}
		$activityId=\Apollo\Modules\Activity\ActivityRepository::create([
			'user_id'=>$userId,'action'=>'status_update','component'=>'activity','type'=>'status',
			'content'=>$content,'privacy'=>sanitize_key($_POST['privacy']??'public')
		]);
		$activityId?wp_send_json_success(['id'=>$activityId]):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function deleteActivity(): void {
		$userId=self::verify();
		$activityId=(int)($_POST['activity_id']??0);
		$activity=\Apollo\Modules\Activity\ActivityRepository::get($activityId);
		if(!$activity||((int)$activity['user_id']!==$userId&&!current_user_can('moderate_comments'))){
			wp_send_json_error(['message'=>'Not allowed'],403);
		}
		$result=\Apollo\Modules\Activity\ActivityRepository::delete($activityId);
		$result?wp_send_json_success(['message'=>'Deleted']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function toggleFavorite(): void {
		$userId=self::verify();
		$itemType=sanitize_key($_POST['item_type']??'');
		$itemId=(int)($_POST['item_id']??0);
		$isFav=\Apollo\Modules\Activity\ActivityRepository::isFavorite($userId,$itemType,$itemId);
		if($isFav){
			\Apollo\Modules\Activity\ActivityRepository::removeFavorite($userId,$itemType,$itemId);
			wp_send_json_success(['favorited'=>false]);
		}else{
			\Apollo\Modules\Activity\ActivityRepository::addFavorite($userId,$itemType,$itemId);
			wp_send_json_success(['favorited'=>true]);
		}
	}

	public static function dismissNotice(): void {
		$userId=self::verify();
		$noticeId=(int)($_POST['notice_id']??0);
		$result=\Apollo\Modules\Notices\NoticesRepository::dismiss($userId,$noticeId);
		$result?wp_send_json_success([]):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function markMentionsRead(): void {
		$userId=self::verify();
		$mentionId=(int)($_POST['mention_id']??0);
		if($mentionId){
			$result=\Apollo\Modules\Activity\ActivityRepository::markMentionRead($mentionId);
		}else{
			$result=\Apollo\Modules\Activity\ActivityRepository::markAllMentionsRead($userId);
		}
		$result?wp_send_json_success([]):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function updateSettings(): void {
		$userId=self::verify();
		$settings=isset($_POST['settings'])&&is_array($_POST['settings'])?$_POST['settings']:[];
		$sanitized=[];
		$defaults=\Apollo\Modules\Members\UserSettingsRepository::getDefaults();
		foreach($settings as $key=>$value){
			if(array_key_exists($key,$defaults)){
				$sanitized[sanitize_key($key)]=is_bool($defaults[$key])?(bool)$value:sanitize_text_field($value);
			}
		}
		$result=\Apollo\Modules\Members\UserSettingsRepository::setMultiple($userId,$sanitized);
		$result?wp_send_json_success(['message'=>'Settings saved']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function joinGroup(): void {
		$userId=self::verify();
		$groupId=(int)($_POST['group_id']??0);
		$group=\Apollo\Modules\Groups\GroupsRepository::get($groupId);
		if(!$group){wp_send_json_error(['message'=>'Group not found'],404);}
		if($group['status']==='hidden'){wp_send_json_error(['message'=>'Cannot join hidden group'],403);}
		$result=\Apollo\Modules\Groups\GroupsRepository::addMember($groupId,$userId);
		$result?wp_send_json_success(['message'=>'Joined group']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function leaveGroup(): void {
		$userId=self::verify();
		$groupId=(int)($_POST['group_id']??0);
		$result=\Apollo\Modules\Groups\GroupsRepository::removeMember($groupId,$userId);
		$result?wp_send_json_success(['message'=>'Left group']):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function joinCompetition(): void {
		$userId=self::verify();
		$compId=(int)($_POST['competition_id']??0);
		$result=\Apollo\Modules\Gamification\CompetitionsRepository::join($compId,$userId);
		$result?wp_send_json_success(['message'=>'Joined competition']):wp_send_json_error(['message'=>'Competition not active'],400);
	}

	public static function searchMembers(): void {
		self::verify();
		$search=sanitize_text_field($_POST['search']??'');
		$members=\Apollo\Modules\Members\MembersDirectoryRepository::search(['search'=>$search,'limit'=>20]);
		wp_send_json_success(['members'=>$members]);
	}

	public static function getOnlineUsers(): void {
		self::verify();
		$users=\Apollo\Modules\Members\OnlineUsersRepository::getOnlineUsers(50);
		wp_send_json_success(['users'=>$users,'count'=>count($users)]);
	}

	public static function markInterested(): void {
		$userId=self::verify();
		$eventId=(int)($_POST['event_id']??0);
		$action=sanitize_key($_POST['action']??'add');
		if($action==='remove'){
			$result=\Apollo\Modules\MyData\MyDataRepository::unmarkEventInterested($userId,$eventId);
		}else{
			$result=\Apollo\Modules\MyData\MyDataRepository::markEventInterested($userId,$eventId);
		}
		$result?wp_send_json_success([]):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function newForumTopic(): void {
		$userId=self::verify();
		$title=sanitize_text_field($_POST['title']??'');
		$content=wp_kses_post($_POST['content']??'');
		$groupId=(int)($_POST['group_id']??0);
		if(empty($title)||empty($content)){wp_send_json_error(['message'=>'Title and content required'],400);}
		if($groupId&&!\Apollo\Modules\Groups\GroupsRepository::isMember($groupId,$userId)){
			wp_send_json_error(['message'=>'Not a member'],403);
		}
		$topicId=\Apollo\Modules\Forum\ForumRepository::createTopic([
			'title'=>$title,'content'=>$content,'author_id'=>$userId,'group_id'=>$groupId?:null
		]);
		$topicId?wp_send_json_success(['id'=>$topicId]):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function forumReply(): void {
		$userId=self::verify();
		$topicId=(int)($_POST['topic_id']??0);
		$content=wp_kses_post($_POST['content']??'');
		if(empty($content)){wp_send_json_error(['message'=>'Content required'],400);}
		$topic=\Apollo\Modules\Forum\ForumRepository::getTopic($topicId);
		if(!$topic){wp_send_json_error(['message'=>'Topic not found'],404);}
		if($topic['is_closed']){wp_send_json_error(['message'=>'Topic is closed'],403);}
		$replyId=\Apollo\Modules\Forum\ForumRepository::createReply([
			'topic_id'=>$topicId,'content'=>$content,'author_id'=>$userId
		]);
		$replyId?wp_send_json_success(['id'=>$replyId]):wp_send_json_error(['message'=>'Failed'],500);
	}

	public static function saveProfileField(): void {
		$userId=self::verify();
		$fieldId=(int)($_POST['field_id']??0);
		$value=$_POST['value']??'';
		$result=\Apollo\Modules\Profiles\ProfileFieldsRepository::setFieldValue($userId,$fieldId,$value);
		$result?wp_send_json_success(['completeness'=>\Apollo\Modules\Profiles\ProfileFieldsRepository::calculateCompleteness($userId)]):wp_send_json_error(['message'=>'Failed'],500);
	}
}
