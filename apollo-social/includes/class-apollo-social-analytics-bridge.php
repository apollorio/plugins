<?php
/**
 * Apollo Social Analytics Bridge
 *
 * Integrates Apollo Social actions with Apollo Core Advanced Analytics.
 * Tracks: follows, likes, shares, profile views, group interactions.
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Social Analytics Bridge
 */
class Apollo_Social_Analytics_Bridge {

	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Only init if Apollo Core Analytics is available.
		if ( ! class_exists( '\Apollo_Core\Analytics' ) ) {
			return;
		}

		// Profile views.
		add_action( 'apollo_social_profile_viewed', array( __CLASS__, 'track_profile_view' ), 10, 2 );

		// Follow/unfollow.
		add_action( 'apollo_social_user_followed', array( __CLASS__, 'track_follow' ), 10, 2 );
		add_action( 'apollo_social_user_unfollowed', array( __CLASS__, 'track_unfollow' ), 10, 2 );

		// Likes.
		add_action( 'apollo_social_post_liked', array( __CLASS__, 'track_like' ), 10, 3 );
		add_action( 'apollo_social_post_unliked', array( __CLASS__, 'track_unlike' ), 10, 3 );

		// Shares.
		add_action( 'apollo_social_content_shared', array( __CLASS__, 'track_share' ), 10, 3 );

		// Comments.
		add_action( 'apollo_social_comment_added', array( __CLASS__, 'track_comment' ), 10, 3 );

		// Group interactions.
		add_action( 'apollo_social_group_joined', array( __CLASS__, 'track_group_join' ), 10, 2 );
		add_action( 'apollo_social_group_left', array( __CLASS__, 'track_group_leave' ), 10, 2 );

		// Messages.
		add_action( 'apollo_social_message_sent', array( __CLASS__, 'track_message' ), 10, 2 );

		// Trigger profile view on user page load.
		add_action( 'apollo_user_page_loaded', array( __CLASS__, 'trigger_profile_view' ), 10, 2 );
	}

	/**
	 * Track profile view
	 *
	 * @param int $viewed_user_id The user whose profile was viewed.
	 * @param int $viewer_user_id The user viewing the profile.
	 */
	public static function track_profile_view( int $viewed_user_id, int $viewer_user_id ): void {
		// Don't track self-views.
		if ( $viewed_user_id === $viewer_user_id ) {
			return;
		}

		\Apollo_Core\Analytics::track_event(
			'profile_view',
			$viewer_user_id,
			0,
			array(
				'viewed_user_id'   => $viewed_user_id,
				'viewed_user_name' => get_userdata( $viewed_user_id )->display_name ?? '',
			)
		);

		// Update user stats.
		\Apollo_Core\Analytics::track_social_interaction( 'profile_view', $viewer_user_id, $viewed_user_id, 'user' );
	}

	/**
	 * Track follow action
	 *
	 * @param int $follower_id The user doing the following.
	 * @param int $followed_id The user being followed.
	 */
	public static function track_follow( int $follower_id, int $followed_id ): void {
		\Apollo_Core\Analytics::track_social_interaction( 'follow', $follower_id, $followed_id, 'user' );
	}

	/**
	 * Track unfollow action
	 *
	 * @param int $follower_id The user doing the unfollowing.
	 * @param int $followed_id The user being unfollowed.
	 */
	public static function track_unfollow( int $follower_id, int $followed_id ): void {
		\Apollo_Core\Analytics::track_event(
			'unfollow',
			$follower_id,
			0,
			array(
				'unfollowed_user_id' => $followed_id,
			)
		);
	}

	/**
	 * Track like action
	 *
	 * @param int    $user_id  User who liked.
	 * @param int    $post_id  Post that was liked.
	 * @param string $post_type Post type.
	 */
	public static function track_like( int $user_id, int $post_id, string $post_type = 'post' ): void {
		$author_id = get_post_field( 'post_author', $post_id );

		\Apollo_Core\Analytics::track_social_interaction( 'like', $user_id, (int) $author_id, 'user' );

		\Apollo_Core\Analytics::track_event(
			'content_liked',
			$user_id,
			$post_id,
			array(
				'post_type'  => $post_type,
				'post_title' => get_the_title( $post_id ),
				'author_id'  => $author_id,
			)
		);
	}

	/**
	 * Track unlike action
	 *
	 * @param int    $user_id  User who unliked.
	 * @param int    $post_id  Post that was unliked.
	 * @param string $post_type Post type.
	 */
	public static function track_unlike( int $user_id, int $post_id, string $post_type = 'post' ): void {
		\Apollo_Core\Analytics::track_event(
			'content_unliked',
			$user_id,
			$post_id,
			array(
				'post_type' => $post_type,
			)
		);
	}

	/**
	 * Track share action
	 *
	 * @param int    $user_id  User who shared.
	 * @param int    $post_id  Post that was shared.
	 * @param string $platform Platform shared to (facebook, twitter, etc.).
	 */
	public static function track_share( int $user_id, int $post_id, string $platform = 'unknown' ): void {
		$author_id = get_post_field( 'post_author', $post_id );

		\Apollo_Core\Analytics::track_social_interaction( 'share', $user_id, (int) $author_id, 'user' );

		\Apollo_Core\Analytics::track_event(
			'content_shared',
			$user_id,
			$post_id,
			array(
				'platform'   => $platform,
				'post_title' => get_the_title( $post_id ),
				'author_id'  => $author_id,
			)
		);
	}

	/**
	 * Track comment action
	 *
	 * @param int $user_id    User who commented.
	 * @param int $post_id    Post that was commented on.
	 * @param int $comment_id The comment ID.
	 */
	public static function track_comment( int $user_id, int $post_id, int $comment_id ): void {
		$author_id = get_post_field( 'post_author', $post_id );

		\Apollo_Core\Analytics::track_social_interaction( 'comment', $user_id, (int) $author_id, 'user' );

		\Apollo_Core\Analytics::track_event(
			'comment_added',
			$user_id,
			$post_id,
			array(
				'comment_id' => $comment_id,
				'post_title' => get_the_title( $post_id ),
				'author_id'  => $author_id,
			)
		);
	}

	/**
	 * Track group join
	 *
	 * @param int $user_id  User joining.
	 * @param int $group_id Group being joined.
	 */
	public static function track_group_join( int $user_id, int $group_id ): void {
		\Apollo_Core\Analytics::track_event(
			'group_joined',
			$user_id,
			$group_id,
			array(
				'group_title' => get_the_title( $group_id ),
			)
		);
	}

	/**
	 * Track group leave
	 *
	 * @param int $user_id  User leaving.
	 * @param int $group_id Group being left.
	 */
	public static function track_group_leave( int $user_id, int $group_id ): void {
		\Apollo_Core\Analytics::track_event(
			'group_left',
			$user_id,
			$group_id,
			array(
				'group_title' => get_the_title( $group_id ),
			)
		);
	}

	/**
	 * Track message sent
	 *
	 * @param int $sender_id   User sending message.
	 * @param int $receiver_id User receiving message.
	 */
	public static function track_message( int $sender_id, int $receiver_id ): void {
		\Apollo_Core\Analytics::track_event(
			'message_sent',
			$sender_id,
			0,
			array(
				'receiver_id' => $receiver_id,
			)
		);
	}

	/**
	 * Trigger profile view when user page loads
	 *
	 * @param int $viewed_user_id The user whose page is being viewed.
	 * @param int $viewer_user_id The current user viewing.
	 */
	public static function trigger_profile_view( int $viewed_user_id, int $viewer_user_id ): void {
		/**
		 * Fires when a user profile is viewed.
		 *
		 * @param int $viewed_user_id User whose profile is viewed.
		 * @param int $viewer_user_id User viewing the profile.
		 */
		do_action( 'apollo_social_profile_viewed', $viewed_user_id, $viewer_user_id );
	}
}

// Initialize the bridge.
add_action( 'plugins_loaded', array( 'Apollo_Social_Analytics_Bridge', 'init' ), 20 );

/**
 * Helper function to check if a user is following another user
 *
 * @param int $follower_id The potential follower.
 * @param int $followed_id The user potentially being followed.
 * @return bool
 */
function apollo_is_following( int $follower_id, int $followed_id ): bool {
	// Check user meta for following relationship.
	$following = get_user_meta( $follower_id, '_apollo_following', true );

	if ( ! is_array( $following ) ) {
		return false;
	}

	return in_array( $followed_id, $following, true );
}

/**
 * Helper function to get followers count
 *
 * @param int $user_id User ID.
 * @return int
 */
function apollo_get_followers_count( int $user_id ): int {
	$count = get_user_meta( $user_id, '_apollo_followers_count', true );
	return $count ? (int) $count : 0;
}

/**
 * Helper function to get following count
 *
 * @param int $user_id User ID.
 * @return int
 */
function apollo_get_following_count( int $user_id ): int {
	$following = get_user_meta( $user_id, '_apollo_following', true );
	return is_array( $following ) ? count( $following ) : 0;
}
