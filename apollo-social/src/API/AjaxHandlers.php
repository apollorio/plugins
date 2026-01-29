<?php
/**
 * AJAX Handlers - Legacy support with REST-aligned security
 *
 * These handlers provide backward compatibility for AJAX requests.
 * Prefer REST API (/apollo/v1/*) for new code.
 *
 * All handlers require:
 * - Nonce verification (apollo_nonce)
 * - User authentication
 * - Capability checks where needed
 * - Rate limiting for sensitive operations
 *
 * @package Apollo\Api
 * @deprecated 2.2.0 Use REST API instead
 */

declare(strict_types=1);

namespace Apollo\Api;

final class AjaxHandlers {

	public static function register(): void {
		$actions = array(
			'apollo_send_friend_request' => array( self::class, 'sendFriendRequest' ),
			'apollo_accept_friend'       => array( self::class, 'acceptFriend' ),
			'apollo_reject_friend'       => array( self::class, 'rejectFriend' ),
			'apollo_remove_friend'       => array( self::class, 'removeFriend' ),
			'apollo_block_user'          => array( self::class, 'blockUser' ),
			'apollo_add_close_friend'    => array( self::class, 'addCloseFriend' ),
			'apollo_remove_close_friend' => array( self::class, 'removeCloseFriend' ),
			'apollo_post_activity'       => array( self::class, 'postActivity' ),
			'apollo_delete_activity'     => array( self::class, 'deleteActivity' ),
			'apollo_toggle_favorite'     => array( self::class, 'toggleFavorite' ),
			'apollo_dismiss_notice'      => array( self::class, 'dismissNotice' ),
			'apollo_mark_mentions_read'  => array( self::class, 'markMentionsRead' ),
			'apollo_update_settings'     => array( self::class, 'updateSettings' ),
			'apollo_join_group'          => array( self::class, 'joinGroup' ),
			'apollo_leave_group'         => array( self::class, 'leaveGroup' ),
			'apollo_join_competition'    => array( self::class, 'joinCompetition' ),
			'apollo_search_members'      => array( self::class, 'searchMembers' ),
			'apollo_get_online_users'    => array( self::class, 'getOnlineUsers' ),
			'apollo_mark_interested'     => array( self::class, 'markInterested' ),
			'apollo_forum_new_topic'     => array( self::class, 'newForumTopic' ),
			'apollo_forum_reply'         => array( self::class, 'forumReply' ),
			'apollo_save_profile_field'  => array( self::class, 'saveProfileField' ),
		);
		foreach ( $actions as $action => $callback ) {
			add_action( "wp_ajax_{$action}", $callback );
		}
	}

	/**
	 * Verify AJAX request: nonce + auth
	 *
	 * @return int User ID
	 * @throws \Exception
	 */
	private static function verify(): int {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'apollo_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			exit;
		}

		// Check auth
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'Not authenticated' ), 401 );
			exit;
		}

		return $user_id;
	}

	/**
	 * Rate limit check (legacy: simple transient-based)
	 *
	 * @param int    $user_id User ID
	 * @param string $action Action name
	 * @param int    $limit Limit per hour
	 * @return bool True if allowed
	 */
	private static function checkRateLimit( int $user_id, string $action, int $limit = 20 ): bool {
		$key   = "apollo_ajax_rate_{$action}_{$user_id}";
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	public static function sendFriendRequest(): void {
		$user_id   = self::verify();
		$friend_id = (int) ( $_POST['friend_id'] ?? 0 );

		if ( $friend_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		if ( ! self::checkRateLimit( $user_id, 'friend_request', 20 ) ) {
			wp_send_json_error( array( 'message' => 'Too many requests' ), 429 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::sendRequest( $user_id, $friend_id );
		$result ? wp_send_json_success( array( 'message' => 'Request sent' ) ) : wp_send_json_error( array( 'message' => 'Failed to send request' ), 500 );
	}

	public static function acceptFriend(): void {
		$user_id   = self::verify();
		$friend_id = (int) ( $_POST['friend_id'] ?? 0 );

		if ( $friend_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::acceptRequest( $friend_id, $user_id );
		$result ? wp_send_json_success( array( 'message' => 'Friend added' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function rejectFriend(): void {
		$user_id   = self::verify();
		$friend_id = (int) ( $_POST['friend_id'] ?? 0 );

		if ( $friend_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::rejectRequest( $friend_id, $user_id );
		$result ? wp_send_json_success( array( 'message' => 'Request rejected' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function removeFriend(): void {
		$user_id   = self::verify();
		$friend_id = (int) ( $_POST['friend_id'] ?? 0 );

		if ( $friend_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::removeFriend( $user_id, $friend_id );
		$result ? wp_send_json_success( array( 'message' => 'Friend removed' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function blockUser(): void {
		$user_id   = self::verify();
		$target_id = (int) ( $_POST['user_id'] ?? 0 );

		if ( $target_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::blockUser( $user_id, $target_id );
		$result ? wp_send_json_success( array( 'message' => 'User blocked' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function addCloseFriend(): void {
		$user_id   = self::verify();
		$friend_id = (int) ( $_POST['friend_id'] ?? 0 );

		if ( $friend_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		if ( ! self::checkRateLimit( $user_id, 'close_friend', 5 ) ) {
			wp_send_json_error( array( 'message' => 'Too many requests' ), 429 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::addCloseFriend( $user_id, $friend_id );
		$result ? wp_send_json_success( array( 'message' => 'Added to close friends' ) ) : wp_send_json_error( array( 'message' => 'Maximum 10 close friends allowed' ), 400 );
	}

	public static function removeCloseFriend(): void {
		$user_id   = self::verify();
		$friend_id = (int) ( $_POST['friend_id'] ?? 0 );

		if ( $friend_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ), 400 );
			return;
		}

		$result = \Apollo\Modules\Connections\ConnectionsRepository::removeCloseFriend( $user_id, $friend_id );
		$result ? wp_send_json_success( array( 'message' => 'Removed from close friends' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function postActivity(): void {
		$user_id = self::verify();
		$content = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) );
		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Content required' ), 400 );}
		$activityId = \Apollo\Modules\Activity\ActivityRepository::create(
			array(
				'user_id'   => $userId,
				'action'    => 'status_update',
				'component' => 'activity',
				'type'      => 'status',
				'content'   => $content,
				'privacy'   => sanitize_key( $_POST['privacy'] ?? 'public' ),
			)
		);
		$activityId ? wp_send_json_success( array( 'id' => $activityId ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function deleteActivity(): void {
		$userId     = self::verify();
		$activityId = (int) ( $_POST['activity_id'] ?? 0 );
		$activity   = \Apollo\Modules\Activity\ActivityRepository::get( $activityId );
		if ( ! $activity || ( (int) $activity['user_id'] !== $userId && ! current_user_can( 'moderate_comments' ) ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed' ), 403 );
		}
		$result = \Apollo\Modules\Activity\ActivityRepository::delete( $activityId );
		$result ? wp_send_json_success( array( 'message' => 'Deleted' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function toggleFavorite(): void {
		$userId   = self::verify();
		$itemType = sanitize_key( $_POST['item_type'] ?? '' );
		$itemId   = (int) ( $_POST['item_id'] ?? 0 );
		$isFav    = \Apollo\Modules\Activity\ActivityRepository::isFavorite( $userId, $itemType, $itemId );
		if ( $isFav ) {
			\Apollo\Modules\Activity\ActivityRepository::removeFavorite( $userId, $itemType, $itemId );
			wp_send_json_success( array( 'favorited' => false ) );
		} else {
			\Apollo\Modules\Activity\ActivityRepository::addFavorite( $userId, $itemType, $itemId );
			wp_send_json_success( array( 'favorited' => true ) );
		}
	}

	public static function dismissNotice(): void {
		$userId   = self::verify();
		$noticeId = (int) ( $_POST['notice_id'] ?? 0 );
		$result   = \Apollo\Modules\Notices\NoticesRepository::dismiss( $userId, $noticeId );
		$result ? wp_send_json_success( array() ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function markMentionsRead(): void {
		$userId    = self::verify();
		$mentionId = (int) ( $_POST['mention_id'] ?? 0 );
		if ( $mentionId ) {
			$result = \Apollo\Modules\Activity\ActivityRepository::markMentionRead( $mentionId );
		} else {
			$result = \Apollo\Modules\Activity\ActivityRepository::markAllMentionsRead( $userId );
		}
		$result ? wp_send_json_success( array() ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function updateSettings(): void {
		$userId    = self::verify();
		$settings  = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? $_POST['settings'] : array();
		$sanitized = array();
		$defaults  = \Apollo\Modules\Members\UserSettingsRepository::getDefaults();
		foreach ( $settings as $key => $value ) {
			if ( array_key_exists( $key, $defaults ) ) {
				$sanitized[ sanitize_key( $key ) ] = is_bool( $defaults[ $key ] ) ? (bool) $value : sanitize_text_field( $value );
			}
		}
		$result = \Apollo\Modules\Members\UserSettingsRepository::setMultiple( $userId, $sanitized );
		$result ? wp_send_json_success( array( 'message' => 'Settings saved' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function joinGroup(): void {
		$userId  = self::verify();
		$groupId = (int) ( $_POST['group_id'] ?? 0 );
		$group   = \Apollo\Modules\Groups\GroupsRepository::get( $groupId );
		if ( ! $group ) {
			wp_send_json_error( array( 'message' => 'Group not found' ), 404 );}
		if ( $group['status'] === 'hidden' ) {
			wp_send_json_error( array( 'message' => 'Cannot join hidden group' ), 403 );}
		$result = \Apollo\Modules\Groups\GroupsRepository::addMember( $groupId, $userId );
		$result ? wp_send_json_success( array( 'message' => 'Joined group' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function leaveGroup(): void {
		$userId  = self::verify();
		$groupId = (int) ( $_POST['group_id'] ?? 0 );
		$result  = \Apollo\Modules\Groups\GroupsRepository::removeMember( $groupId, $userId );
		$result ? wp_send_json_success( array( 'message' => 'Left group' ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function joinCompetition(): void {
		$userId = self::verify();
		$compId = (int) ( $_POST['competition_id'] ?? 0 );
		$result = \Apollo\Modules\Gamification\CompetitionsRepository::join( $compId, $userId );
		$result ? wp_send_json_success( array( 'message' => 'Joined competition' ) ) : wp_send_json_error( array( 'message' => 'Competition not active' ), 400 );
	}

	public static function searchMembers(): void {
		self::verify();
		$search  = sanitize_text_field( $_POST['search'] ?? '' );
		$members = \Apollo\Modules\Members\MembersDirectoryRepository::search(
			array(
				'search' => $search,
				'limit'  => 20,
			)
		);
		wp_send_json_success( array( 'members' => $members ) );
	}

	public static function getOnlineUsers(): void {
		self::verify();
		$users = \Apollo\Modules\Members\OnlineUsersRepository::getOnlineUsers( 50 );
		wp_send_json_success(
			array(
				'users' => $users,
				'count' => count( $users ),
			)
		);
	}

	public static function markInterested(): void {
		$userId  = self::verify();
		$eventId = (int) ( $_POST['event_id'] ?? 0 );
		$action  = sanitize_key( $_POST['action'] ?? 'add' );
		if ( $action === 'remove' ) {
			$result = \Apollo\Modules\MyData\MyDataRepository::unmarkEventInterested( $userId, $eventId );
		} else {
			$result = \Apollo\Modules\MyData\MyDataRepository::markEventInterested( $userId, $eventId );
		}
		$result ? wp_send_json_success( array() ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function newForumTopic(): void {
		$userId  = self::verify();
		$title   = sanitize_text_field( $_POST['title'] ?? '' );
		$content = wp_kses_post( $_POST['content'] ?? '' );
		$groupId = (int) ( $_POST['group_id'] ?? 0 );
		if ( empty( $title ) || empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Title and content required' ), 400 );}
		if ( $groupId && ! \Apollo\Modules\Groups\GroupsRepository::isMember( $groupId, $userId ) ) {
			wp_send_json_error( array( 'message' => 'Not a member' ), 403 );
		}
		$topicId = \Apollo\Modules\Forum\ForumRepository::createTopic(
			array(
				'title'     => $title,
				'content'   => $content,
				'author_id' => $userId,
				'group_id'  => $groupId ?: null,
			)
		);
		$topicId ? wp_send_json_success( array( 'id' => $topicId ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function forumReply(): void {
		$userId  = self::verify();
		$topicId = (int) ( $_POST['topic_id'] ?? 0 );
		$content = wp_kses_post( $_POST['content'] ?? '' );
		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Content required' ), 400 );}
		$topic = \Apollo\Modules\Forum\ForumRepository::getTopic( $topicId );
		if ( ! $topic ) {
			wp_send_json_error( array( 'message' => 'Topic not found' ), 404 );}
		if ( $topic['is_closed'] ) {
			wp_send_json_error( array( 'message' => 'Topic is closed' ), 403 );}
		$replyId = \Apollo\Modules\Forum\ForumRepository::createReply(
			array(
				'topic_id'  => $topicId,
				'content'   => $content,
				'author_id' => $userId,
			)
		);
		$replyId ? wp_send_json_success( array( 'id' => $replyId ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}

	public static function saveProfileField(): void {
		$userId  = self::verify();
		$fieldId = (int) ( $_POST['field_id'] ?? 0 );
		$value   = $_POST['value'] ?? '';
		$result  = \Apollo\Modules\Profiles\ProfileFieldsRepository::setFieldValue( $userId, $fieldId, $value );
		$result ? wp_send_json_success( array( 'completeness' => \Apollo\Modules\Profiles\ProfileFieldsRepository::calculateCompleteness( $userId ) ) ) : wp_send_json_error( array( 'message' => 'Failed' ), 500 );
	}
}
