<?php
declare(strict_types=1);
namespace Apollo\Modules\Integration;

final class IntegrationHooksRepository {

	public static function registerTriggers(): void {
		add_action( 'user_register', array( self::class, 'onUserRegister' ), 10, 1 );
		add_action( 'wp_login', array( self::class, 'onUserLogin' ), 10, 2 );
		add_action( 'profile_update', array( self::class, 'onProfileUpdate' ), 10, 2 );
		add_action( 'delete_user', array( self::class, 'onUserDelete' ), 10, 1 );
		add_action( 'comment_post', array( self::class, 'onCommentPost' ), 10, 3 );
		add_action( 'transition_post_status', array( self::class, 'onPostStatusChange' ), 10, 3 );
		add_action( 'add_attachment', array( self::class, 'onMediaUpload' ), 10, 1 );
		add_action( 'init', array( self::class, 'trackOnlineUsers' ), 10 );
	}

	public static function onUserRegister( int $userId ): void {
		\Apollo\Modules\Gamification\PointsRepository::add( $userId, 10, 'user_register', 'default', null, null, 'User registered' );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'user_register', 1 );
	}

	public static function onUserLogin( string $userLogin, \WP_User $user ): void {
		\Apollo\Modules\Members\OnlineUsersRepository::updateActivity( $user->ID );
		$lastLogin = get_user_meta( $user->ID, 'apollo_last_login', true );
		$today     = current_time( 'Y-m-d' );
		if ( $lastLogin !== $today ) {
			\Apollo\Modules\Gamification\PointsRepository::add( $user->ID, 2, 'daily_login', 'default', null, null, 'Daily login bonus' );
			\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $user->ID, 'daily_login', 1 );
		}
		update_user_meta( $user->ID, 'apollo_last_login', $today );
		$streak    = (int) get_user_meta( $user->ID, 'apollo_login_streak', true );
		$lastDate  = get_user_meta( $user->ID, 'apollo_login_streak_date', true );
		$yesterday = \date( 'Y-m-d', \strtotime( '-1 day' ) );
		if ( $lastDate === $yesterday ) {
			++$streak;
		} elseif ( $lastDate !== $today ) {
			$streak = 1;
		}
		update_user_meta( $user->ID, 'apollo_login_streak', $streak );
		update_user_meta( $user->ID, 'apollo_login_streak_date', $today );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $user->ID, 'login_streak', $streak, array( 'streak' => $streak ) );
	}

	public static function onProfileUpdate( int $userId, ?\WP_User $oldUser = null ): void {
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'profile_update', 1 );
		$completeness = \Apollo\Modules\Profiles\ProfileFieldsRepository::calculateCompleteness( $userId );
		if ( $completeness >= 100 ) {
			\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'profile_complete', 1, array( 'completeness' => $completeness ) );
		}
	}

	public static function onUserDelete( int $userId ): void {
		\Apollo\Modules\Members\OnlineUsersRepository::cleanup( 0 );
		\Apollo\Modules\Connections\ConnectionsRepository::removeAllConnections( $userId );
		\Apollo\Modules\Members\UserSettingsRepository::deleteAll( $userId );
	}

	public static function onCommentPost( int $commentId, int|string $approved, array $data ): void {
		$comment = get_comment( $commentId );
		if ( ! $comment || ! $comment->user_id ) {
			return;
		}
		$userId = (int) $comment->user_id;
		\Apollo\Modules\Gamification\PointsRepository::add( $userId, 3, 'comment_post', 'default', $commentId, 'comment', 'Posted a comment' );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'comment_post', 1, array( 'comment_id' => $commentId ) );
	}

	public static function onPostStatusChange( string $newStatus, string $oldStatus, $post ): void {
		if ( ! $post || ! $post->post_author ) {
			return;
		}
		$userId = (int) $post->post_author;
		if ( $newStatus === 'publish' && $oldStatus !== 'publish' ) {
			$points = $post->post_type === 'post' ? 15 : 10;
			\Apollo\Modules\Gamification\PointsRepository::add( $userId, $points, 'post_publish', 'default', $post->ID, 'post', 'Published content' );
			\Apollo\Modules\Gamification\AchievementsRepository::processTrigger(
				$userId,
				'post_publish',
				1,
				array(
					'post_id'   => $post->ID,
					'post_type' => $post->post_type,
				)
			);
		}
	}

	public static function onMediaUpload( int $attachmentId ): void {
		$attachment = get_post( $attachmentId );
		if ( ! $attachment || ! $attachment->post_author ) {
			return;
		}
		$userId = (int) $attachment->post_author;
		\Apollo\Modules\Gamification\PointsRepository::add( $userId, 5, 'media_upload', 'default', $attachmentId, 'attachment', 'Uploaded media' );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'media_upload', 1, array( 'attachment_id' => $attachmentId ) );
	}

	public static function trackOnlineUsers(): void {
		if ( ! is_user_logged_in() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		$userId = get_current_user_id();
		$page   = $_SERVER['REQUEST_URI'] ?? '';
		\Apollo\Modules\Members\OnlineUsersRepository::updateActivity( $userId, $page );
	}

	public static function registerAdditionalTriggers(): void {
		add_action( 'apollo_friend_accepted', array( self::class, 'onFriendAccepted' ), 10, 2 );
		add_action( 'apollo_group_joined', array( self::class, 'onGroupJoined' ), 10, 2 );
		add_action( 'apollo_activity_posted', array( self::class, 'onActivityPosted' ), 10, 2 );
		add_action( 'apollo_achievement_unlocked', array( self::class, 'onAchievementUnlocked' ), 10, 2 );
	}

	public static function onFriendAccepted( int $userId, int $friendId ): void {
		\Apollo\Modules\Gamification\PointsRepository::add( $userId, 5, 'friend_accepted', 'default', $friendId, 'user', 'Made a new friend' );
		\Apollo\Modules\Gamification\PointsRepository::add( $friendId, 5, 'friend_accepted', 'default', $userId, 'user', 'Made a new friend' );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'friend_accepted', 1, array( 'friend_id' => $friendId ) );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $friendId, 'friend_accepted', 1, array( 'friend_id' => $userId ) );
	}

	public static function onGroupJoined( int $userId, int $groupId ): void {
		\Apollo\Modules\Gamification\PointsRepository::add( $userId, 8, 'group_joined', 'default', $groupId, 'group', 'Joined a group' );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'group_joined', 1, array( 'group_id' => $groupId ) );
	}

	public static function onActivityPosted( int $userId, int $activityId ): void {
		\Apollo\Modules\Gamification\PointsRepository::add( $userId, 3, 'activity_posted', 'default', $activityId, 'activity', 'Posted activity update' );
		\Apollo\Modules\Gamification\AchievementsRepository::processTrigger( $userId, 'activity_posted', 1, array( 'activity_id' => $activityId ) );
	}

	public static function onAchievementUnlocked( int $userId, int $achievementId ): void {
		\Apollo\Modules\Activity\ActivityRepository::create(
			array(
				'user_id'   => $userId,
				'action'    => 'achievement_unlocked',
				'component' => 'gamification',
				'type'      => 'achievement',
				'content'   => '',
				'item_id'   => $achievementId,
				'privacy'   => 'public',
			)
		);
	}
}
