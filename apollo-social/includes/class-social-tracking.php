<?php
/**
 * Apollo Social Tracking
 *
 * Comprehensive tracking for Apollo Social interactions with Apollo Core Analytics.
 * Covers: Hub pages, classifieds, posts, reactions, groups (comuna/nucleo).
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social Tracking Class
 */
class Apollo_Social_Tracking {

	/**
	 * Initialize tracking hooks
	 */
	public static function init(): void {
		// Only init if Apollo Core Analytics is available.
		if ( ! class_exists( '\Apollo_Core\Analytics' ) ) {
			return;
		}

		// ========================================
		// REACTIONS / LIKES
		// ========================================
		add_action( 'apollo_social_like_added', array( __CLASS__, 'track_like' ), 10, 3 );
		add_action( 'apollo_social_like_removed', array( __CLASS__, 'track_unlike' ), 10, 3 );
		add_action( 'apollo_reaction_added', array( __CLASS__, 'track_reaction' ), 10, 4 );
		add_action( 'apollo_reaction_removed', array( __CLASS__, 'track_reaction_removed' ), 10, 3 );
		add_action( 'apollo_activity_liked', array( __CLASS__, 'track_activity_like' ), 10, 2 );

		// ========================================
		// FOLLOW / UNFOLLOW
		// ========================================
		add_action( 'apollo_social_follow_added', array( __CLASS__, 'track_follow' ), 10, 2 );
		add_action( 'apollo_social_follow_removed', array( __CLASS__, 'track_unfollow' ), 10, 2 );

		// ========================================
		// COMMENTS
		// ========================================
		add_action( 'wp_insert_comment', array( __CLASS__, 'track_comment' ), 10, 2 );

		// ========================================
		// USER HUB PAGES (user_page CPT)
		// ========================================
		add_action( 'apollo_user_page_loaded', array( __CLASS__, 'track_user_page_view' ), 10, 2 );
		add_action( 'apollo_hub_state_saved', array( __CLASS__, 'track_hub_save' ), 10, 1 );

		// ========================================
		// CLASSIFIEDS (apollo_classified CPT)
		// ========================================
		add_action( 'apollo_classified_created', array( __CLASS__, 'track_classified_created' ), 10, 2 );
		add_action( 'apollo_classified_updated', array( __CLASS__, 'track_classified_updated' ), 10, 2 );
		add_action( 'apollo_classified_deleted', array( __CLASS__, 'track_classified_deleted' ), 10, 2 );
		add_action( 'apollo_classified_reported', array( __CLASS__, 'track_classified_reported' ), 10, 2 );
		add_action( 'apollo_classified_viewed', array( __CLASS__, 'track_classified_view' ), 10, 2 );

		// ========================================
		// SOCIAL POSTS (apollo_social_post CPT)
		// ========================================
		add_action( 'apollo_social_post_created', array( __CLASS__, 'track_social_post_created' ), 10, 2 );
		add_action( 'apollo_social_post_deleted', array( __CLASS__, 'track_social_post_deleted' ), 10, 2 );
		add_action( 'apollo_wall_post_created', array( __CLASS__, 'track_wall_post_created' ), 10, 3 );

		// ========================================
		// GROUPS - COMUNA (Public)
		// ========================================
		add_action( 'apollo_group_created', array( __CLASS__, 'track_group_created' ), 10, 2 );
		add_action( 'apollo_group_joined', array( __CLASS__, 'track_group_joined' ), 10, 2 );
		add_action( 'apollo_group_left', array( __CLASS__, 'track_group_left' ), 10, 2 );
		add_action( 'apollo_group_invite_sent', array( __CLASS__, 'track_group_invite_sent' ), 10, 3 );
		add_action( 'apollo_group_invite_accepted', array( __CLASS__, 'track_group_invite_accepted' ), 10, 3 );

		// ========================================
		// GROUPS - NUCLEO (Private)
		// ========================================
		add_action( 'apollo_nucleo_join_requested', array( __CLASS__, 'track_nucleo_join_request' ), 10, 2 );
		add_action( 'apollo_nucleo_member_approved', array( __CLASS__, 'track_nucleo_member_approved' ), 10, 3 );
		add_action( 'apollo_nucleo_member_rejected', array( __CLASS__, 'track_nucleo_member_rejected' ), 10, 3 );
	}

	// ========================================
	// REACTION TRACKING METHODS
	// ========================================

	/**
	 * Track like action
	 *
	 * @param int    $user_id     User who liked
	 * @param int    $target_id   Target ID (user or post)
	 * @param string $target_type Target type ('user' or 'post')
	 */
	public static function track_like( int $user_id, int $target_id, string $target_type ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_interaction',
			'user_id'  => $user_id,
			'post_id'  => $target_type === 'post' ? $target_id : 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'like',
				'target_type' => $target_type,
				'target_id'   => $target_id,
			),
		) );
	}

	/**
	 * Track unlike action
	 *
	 * @param int    $user_id     User who unliked
	 * @param int    $target_id   Target ID (user or post)
	 * @param string $target_type Target type ('user' or 'post')
	 */
	public static function track_unlike( int $user_id, int $target_id, string $target_type ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_interaction',
			'user_id'  => $user_id,
			'post_id'  => $target_type === 'post' ? $target_id : 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'unlike',
				'target_type' => $target_type,
				'target_id'   => $target_id,
			),
		) );
	}

	/**
	 * Track reaction (emoji reactions: love, haha, wow, sad, angry, care)
	 *
	 * @param string $object_type Type of object (post, comment, activity)
	 * @param int    $object_id   ID of the object
	 * @param int    $user_id     User reacting
	 * @param string $reaction_type Type of reaction (love, haha, wow, etc.)
	 */
	public static function track_reaction( string $object_type, int $object_id, int $user_id, string $reaction_type ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_reaction',
			'user_id'  => $user_id,
			'post_id'  => $object_type === 'post' ? $object_id : 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'        => 'reaction_add',
				'object_type'   => $object_type,
				'object_id'     => $object_id,
				'reaction_type' => $reaction_type,
			),
		) );
	}

	/**
	 * Track reaction removed
	 *
	 * @param string $object_type Type of object
	 * @param int    $object_id   ID of the object
	 * @param int    $user_id     User removing reaction
	 */
	public static function track_reaction_removed( string $object_type, int $object_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_reaction',
			'user_id'  => $user_id,
			'post_id'  => $object_type === 'post' ? $object_id : 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'reaction_remove',
				'object_type' => $object_type,
				'object_id'   => $object_id,
			),
		) );
	}

	/**
	 * Track activity like (from ActivityLikesRepository)
	 *
	 * @param int $activity_id Activity that was liked
	 * @param int $user_id     User who liked
	 */
	public static function track_activity_like( int $activity_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_interaction',
			'user_id'  => $user_id,
			'post_id'  => 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'activity_like',
				'activity_id' => $activity_id,
			),
		) );
	}

	// ========================================
	// FOLLOW TRACKING METHODS
	// ========================================

	/**
	 * Track follow action
	 *
	 * @param int $follower_id User who is following
	 * @param int $target_id   User being followed
	 */
	public static function track_follow( int $follower_id, int $target_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_interaction',
			'user_id'  => $follower_id,
			'post_id'  => 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'follow',
				'target_type' => 'user',
				'target_id'   => $target_id,
			),
		) );
	}

	/**
	 * Track unfollow action
	 *
	 * @param int $follower_id User who is unfollowing
	 * @param int $target_id   User being unfollowed
	 */
	public static function track_unfollow( int $follower_id, int $target_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_interaction',
			'user_id'  => $follower_id,
			'post_id'  => 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'unfollow',
				'target_type' => 'user',
				'target_id'   => $target_id,
			),
		) );
	}

	// ========================================
	// COMMENT TRACKING
	// ========================================

	/**
	 * Track comment action
	 *
	 * @param int        $comment_id Comment ID
	 * @param WP_Comment $comment    Comment object
	 */
	public static function track_comment( int $comment_id, WP_Comment $comment ): void {
		// Only track approved comments
		if ( $comment->comment_approved !== '1' ) {
			return;
		}

		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_interaction',
			'user_id'  => $comment->user_id ?: 0,
			'post_id'  => $comment->comment_post_ID,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'comment',
				'target_type' => 'post',
				'target_id'   => $comment->comment_post_ID,
				'comment_id'  => $comment_id,
			),
		) );
	}

	// ========================================
	// USER HUB PAGE TRACKING
	// ========================================

	/**
	 * Track user page view (public hub profile)
	 *
	 * @param int $viewed_user_id User whose page is viewed
	 * @param int $viewer_user_id User viewing the page
	 */
	public static function track_user_page_view( int $viewed_user_id, int $viewer_user_id ): void {
		// Don't track self-views
		if ( $viewed_user_id === $viewer_user_id ) {
			return;
		}

		\Apollo_Core\Analytics::track( array(
			'type'     => 'user_page_view',
			'user_id'  => $viewer_user_id,
			'post_id'  => 0,
			'plugin'   => 'social',
			'metadata' => array(
				'viewed_user_id'   => $viewed_user_id,
				'viewed_user_name' => get_userdata( $viewed_user_id )->display_name ?? '',
			),
		) );
	}

	/**
	 * Track HUB state saved (linktree-style editor)
	 *
	 * @param int $user_id User who saved their HUB
	 */
	public static function track_hub_save( int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'hub_interaction',
			'user_id'  => $user_id,
			'post_id'  => 0,
			'plugin'   => 'social',
			'metadata' => array(
				'action' => 'hub_saved',
			),
		) );
	}

	// ========================================
	// CLASSIFIEDS TRACKING
	// ========================================

	/**
	 * Track classified created
	 *
	 * @param int $post_id Classified post ID
	 * @param int $user_id User who created
	 */
	public static function track_classified_created( int $post_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'classified_action',
			'user_id'  => $user_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'          => 'created',
				'classified_title' => get_the_title( $post_id ),
			),
		) );
	}

	/**
	 * Track classified updated
	 *
	 * @param int $post_id Classified post ID
	 * @param int $user_id User who updated
	 */
	public static function track_classified_updated( int $post_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'classified_action',
			'user_id'  => $user_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action' => 'updated',
			),
		) );
	}

	/**
	 * Track classified deleted
	 *
	 * @param int $post_id Classified post ID
	 * @param int $user_id User who deleted
	 */
	public static function track_classified_deleted( int $post_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'classified_action',
			'user_id'  => $user_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action' => 'deleted',
			),
		) );
	}

	/**
	 * Track classified reported
	 *
	 * @param int $post_id     Classified post ID
	 * @param int $reporter_id User reporting
	 */
	public static function track_classified_reported( int $post_id, int $reporter_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'classified_action',
			'user_id'  => $reporter_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action' => 'reported',
			),
		) );
	}

	/**
	 * Track classified view
	 *
	 * @param int $post_id   Classified post ID
	 * @param int $viewer_id User viewing (0 for anonymous)
	 */
	public static function track_classified_view( int $post_id, int $viewer_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'classified_view',
			'user_id'  => $viewer_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'          => 'view',
				'classified_title' => get_the_title( $post_id ),
			),
		) );
	}

	// ========================================
	// SOCIAL POSTS TRACKING
	// ========================================

	/**
	 * Track social post created
	 *
	 * @param int $post_id Social post ID
	 * @param int $user_id Author
	 */
	public static function track_social_post_created( int $post_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_post_action',
			'user_id'  => $user_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action' => 'created',
			),
		) );
	}

	/**
	 * Track social post deleted
	 *
	 * @param int $post_id Social post ID
	 * @param int $user_id User who deleted
	 */
	public static function track_social_post_deleted( int $post_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'social_post_action',
			'user_id'  => $user_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action' => 'deleted',
			),
		) );
	}

	/**
	 * Track wall post created
	 *
	 * @param int $post_id      Wall post ID
	 * @param int $author_id    Author of the post
	 * @param int $wall_owner_id Owner of the wall
	 */
	public static function track_wall_post_created( int $post_id, int $author_id, int $wall_owner_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'wall_post_action',
			'user_id'  => $author_id,
			'post_id'  => $post_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'        => 'created',
				'wall_owner_id' => $wall_owner_id,
				'is_own_wall'   => $author_id === $wall_owner_id,
			),
		) );
	}

	// ========================================
	// GROUPS (COMUNA & NUCLEO) TRACKING
	// ========================================

	/**
	 * Track group created
	 *
	 * @param int $group_id Group/post ID
	 * @param int $user_id  Creator user ID
	 */
	public static function track_group_created( int $group_id, int $user_id ): void {
		$group_type = get_post_meta( $group_id, '_group_type', true ) ?: 'comuna';

		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $user_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'created',
				'group_type'  => $group_type, // 'comuna' or 'nucleo'
				'group_title' => get_the_title( $group_id ),
			),
		) );
	}

	/**
	 * Track group joined
	 *
	 * @param int $group_id Group/post ID
	 * @param int $user_id  User who joined
	 */
	public static function track_group_joined( int $group_id, int $user_id ): void {
		$group_type = get_post_meta( $group_id, '_group_type', true ) ?: 'comuna';

		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $user_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'joined',
				'group_type'  => $group_type,
				'group_title' => get_the_title( $group_id ),
			),
		) );
	}

	/**
	 * Track group left
	 *
	 * @param int $group_id Group/post ID
	 * @param int $user_id  User who left
	 */
	public static function track_group_left( int $group_id, int $user_id ): void {
		$group_type = get_post_meta( $group_id, '_group_type', true ) ?: 'comuna';

		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $user_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'left',
				'group_type'  => $group_type,
				'group_title' => get_the_title( $group_id ),
			),
		) );
	}

	/**
	 * Track group invite sent
	 *
	 * @param int $group_id    Group/post ID
	 * @param int $inviter_id  User sending invite
	 * @param int $invitee_id  User being invited
	 */
	public static function track_group_invite_sent( int $group_id, int $inviter_id, int $invitee_id ): void {
		$group_type = get_post_meta( $group_id, '_group_type', true ) ?: 'comuna';

		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $inviter_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'invite_sent',
				'group_type'  => $group_type,
				'invitee_id'  => $invitee_id,
			),
		) );
	}

	/**
	 * Track group invite accepted
	 *
	 * @param int $group_id    Group/post ID
	 * @param int $invitee_id  User accepting invite
	 * @param int $inviter_id  Original inviter
	 */
	public static function track_group_invite_accepted( int $group_id, int $invitee_id, int $inviter_id ): void {
		$group_type = get_post_meta( $group_id, '_group_type', true ) ?: 'comuna';

		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $invitee_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'invite_accepted',
				'group_type'  => $group_type,
				'inviter_id'  => $inviter_id,
			),
		) );
	}

	/**
	 * Track nucleo join request (private group)
	 *
	 * @param int $group_id Group/post ID
	 * @param int $user_id  User requesting to join
	 */
	public static function track_nucleo_join_request( int $group_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $user_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'join_requested',
				'group_type'  => 'nucleo',
				'group_title' => get_the_title( $group_id ),
			),
		) );
	}

	/**
	 * Track nucleo member approved
	 *
	 * @param int $group_id    Group/post ID
	 * @param int $member_id   User being approved
	 * @param int $approver_id Admin approving
	 */
	public static function track_nucleo_member_approved( int $group_id, int $member_id, int $approver_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $approver_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'member_approved',
				'group_type'  => 'nucleo',
				'member_id'   => $member_id,
			),
		) );
	}

	/**
	 * Track nucleo member rejected
	 *
	 * @param int $group_id    Group/post ID
	 * @param int $member_id   User being rejected
	 * @param int $rejector_id Admin rejecting
	 */
	public static function track_nucleo_member_rejected( int $group_id, int $member_id, int $rejector_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'group_action',
			'user_id'  => $rejector_id,
			'post_id'  => $group_id,
			'plugin'   => 'social',
			'metadata' => array(
				'action'      => 'member_rejected',
				'group_type'  => 'nucleo',
				'member_id'   => $member_id,
			),
		) );
	}
}

// Initialize tracking
add_action( 'init', array( 'Apollo_Social_Tracking', 'init' ) );
