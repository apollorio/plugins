<?php
declare(strict_types=1);
namespace Apollo\Modules\Integration;

final class IntegrationHooksRepository {

	public static function registerTriggers(): void {
		add_action('user_register',[self::class,'onUserRegister'],10,1);
		add_action('wp_login',[self::class,'onUserLogin'],10,2);
		add_action('profile_update',[self::class,'onProfileUpdate'],10,2);
		add_action('delete_user',[self::class,'onUserDelete'],10,1);
		add_action('comment_post',[self::class,'onCommentPost'],10,3);
		add_action('transition_post_status',[self::class,'onPostStatusChange'],10,3);
		add_action('add_attachment',[self::class,'onMediaUpload'],10,1);
		add_action('init',[self::class,'trackOnlineUsers'],10);
	}

	public static function onUserRegister(int $userId): void {
		\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',10,'user_register','User registered');
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'user_register',[]);
	}

	public static function onUserLogin(string $userLogin,\WP_User $user): void {
		\Apollo\Modules\Members\OnlineUsersRepository::updateActivity($user->ID);
		$lastLogin=get_user_meta($user->ID,'apollo_last_login',true);
		$today=current_time('Y-m-d');
		if($lastLogin!==$today){
			\Apollo\Modules\Gamification\PointsRepository::add($user->ID,'default',2,'daily_login','Daily login bonus');
			\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($user->ID,'daily_login',[]);
		}
		update_user_meta($user->ID,'apollo_last_login',$today);
		$streak=(int)get_user_meta($user->ID,'apollo_login_streak',true);
		$lastDate=get_user_meta($user->ID,'apollo_login_streak_date',true);
		$yesterday=date('Y-m-d',strtotime('-1 day'));
		if($lastDate===$yesterday){
			$streak++;
		}elseif($lastDate!==$today){
			$streak=1;
		}
		update_user_meta($user->ID,'apollo_login_streak',$streak);
		update_user_meta($user->ID,'apollo_login_streak_date',$today);
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($user->ID,'login_streak',['streak'=>$streak]);
	}

	public static function onProfileUpdate(int $userId,?\WP_User $oldUser=null): void {
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'profile_update',[]);
		$completeness=\Apollo\Modules\Profiles\ProfileFieldsRepository::calculateCompleteness($userId);
		if($completeness>=100){
			\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'profile_complete',['completeness'=>$completeness]);
		}
	}

	public static function onUserDelete(int $userId): void {
		\Apollo\Modules\Members\OnlineUsersRepository::cleanup(0);
		\Apollo\Modules\Connections\ConnectionsRepository::removeAllConnections($userId);
		\Apollo\Modules\Members\UserSettingsRepository::deleteAll($userId);
	}

	public static function onCommentPost(int $commentId,int|string $approved,array $data): void {
		$comment=get_comment($commentId);
		if(!$comment||!$comment->user_id)return;
		$userId=(int)$comment->user_id;
		\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',3,'comment_post','Posted a comment');
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'comment_post',['comment_id'=>$commentId]);
	}

	public static function onPostStatusChange(string $newStatus,string $oldStatus,$post): void {
		if(!$post||!$post->post_author)return;
		$userId=(int)$post->post_author;
		if($newStatus==='publish'&&$oldStatus!=='publish'){
			$points=$post->post_type==='post'?15:10;
			\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',$points,'post_publish','Published content');
			\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'post_publish',['post_id'=>$post->ID,'post_type'=>$post->post_type]);
		}
	}

	public static function onMediaUpload(int $attachmentId): void {
		$attachment=get_post($attachmentId);
		if(!$attachment||!$attachment->post_author)return;
		$userId=(int)$attachment->post_author;
		\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',5,'media_upload','Uploaded media');
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'media_upload',['attachment_id'=>$attachmentId]);
	}

	public static function trackOnlineUsers(): void {
		if(!is_user_logged_in()||wp_doing_ajax()||wp_doing_cron())return;
		$userId=get_current_user_id();
		$page=$_SERVER['REQUEST_URI']??'';
		\Apollo\Modules\Members\OnlineUsersRepository::updateActivity($userId,$page);
	}

	public static function registerAdditionalTriggers(): void {
		add_action('apollo_friend_accepted',[self::class,'onFriendAccepted'],10,2);
		add_action('apollo_group_joined',[self::class,'onGroupJoined'],10,2);
		add_action('apollo_activity_posted',[self::class,'onActivityPosted'],10,2);
		add_action('apollo_achievement_unlocked',[self::class,'onAchievementUnlocked'],10,2);
	}

	public static function onFriendAccepted(int $userId,int $friendId): void {
		\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',5,'friend_accepted','Made a new friend');
		\Apollo\Modules\Gamification\PointsRepository::add($friendId,'default',5,'friend_accepted','Made a new friend');
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'friend_accepted',['friend_id'=>$friendId]);
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($friendId,'friend_accepted',['friend_id'=>$userId]);
	}

	public static function onGroupJoined(int $userId,int $groupId): void {
		\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',8,'group_joined','Joined a group');
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'group_joined',['group_id'=>$groupId]);
	}

	public static function onActivityPosted(int $userId,int $activityId): void {
		\Apollo\Modules\Gamification\PointsRepository::add($userId,'default',3,'activity_posted','Posted activity update');
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger($userId,'activity_posted',['activity_id'=>$activityId]);
	}

	public static function onAchievementUnlocked(int $userId,int $achievementId): void {
		\Apollo\Modules\Activity\ActivityRepository::create([
			'user_id'=>$userId,'action'=>'achievement_unlocked','component'=>'gamification','type'=>'achievement',
			'content'=>'','item_id'=>$achievementId,'privacy'=>'public'
		]);
	}
}
