<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

final class WallPostsRepository {
	private const TABLE = 'apollo_activity';

	public static function create( int $authorId, int $profileId, string $content, array $meta = array() ): ?int {
		if ( $authorId !== $profileId && ! self::canPost( $authorId, $profileId ) ) {
			return null;}
		global $wpdb;
		$r = $wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'user_id'     => $authorId,
				'profile_id'  => $profileId,
				'type'        => 'wall_post',
				'content'     => wp_kses_post( $content ),
				'visibility'  => $meta['visibility'] ?? 'public',
				'attachments' => isset( $meta['attachments'] ) ? json_encode( $meta['attachments'] ) : null,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		if ( $r ) {
			$id = (int) $wpdb->insert_id;
			if ( $authorId !== $profileId ) {
				do_action(
					'apollo_notify',
					$profileId,
					'wall_post',
					sprintf( '%s postou em seu mural', get_userdata( $authorId )->display_name ),
					array(
						'actor_id'    => $authorId,
						'object_type' => 'wall_post',
						'object_id'   => $id,
						'link'        => home_url( "/members/{$profileId}/activity/{$id}/" ),
					)
				);
			}
			do_action( 'apollo_award_points', $authorId, 3, 'wall_post', 'Postou no mural' );
			return $id;
		}
		return null;
	}

	public static function getForProfile( int $profileId, int $viewerId, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t          = $wpdb->prefix . self::TABLE;
		$visibility = self::getVisibilityFilter( $viewerId, $profileId );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*,u.display_name as author_name FROM {$t} a JOIN {$wpdb->users} u ON a.user_id=u.ID WHERE a.profile_id=%d AND a.type='wall_post' AND a.is_hidden=0 {$visibility} ORDER BY a.created_at DESC LIMIT %d OFFSET %d",
				$profileId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function delete( int $postId, int $userId ): bool {
		global $wpdb;
		$t    = $wpdb->prefix . self::TABLE;
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT user_id,profile_id FROM {$t} WHERE id=%d", $postId ), ARRAY_A );
		if ( ! $post ) {
			return false;}
		if ( (int) $post['user_id'] !== $userId && (int) $post['profile_id'] !== $userId && ! current_user_can( 'manage_options' ) ) {
			return false;}
		$wpdb->delete( $wpdb->prefix . 'apollo_activity_comments', array( 'activity_id' => $postId ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'apollo_activity_likes', array( 'activity_id' => $postId ), array( '%d' ) );
		return (bool) $wpdb->delete( $t, array( 'id' => $postId ), array( '%d' ) );
	}

	public static function update( int $postId, int $userId, string $content ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->update(
			$t,
			array(
				'content'    => wp_kses_post( $content ),
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'      => $postId,
				'user_id' => $userId,
				'type'    => 'wall_post',
			)
		);
	}

	public static function canPost( int $authorId, int $profileId ): bool {
		if ( $authorId === $profileId ) {
			return true;}
		$setting = get_user_meta( $profileId, 'apollo_wall_posts_setting', true ) ?: 'friends';
		return match ( $setting ) {
			'everyone'=>true,
			'friends'=>self::areFriends( $authorId, $profileId ),
			'close_friends'=>self::areCloseFriends( $authorId, $profileId ),
			'none'=>false,
			default=>self::areFriends( $authorId, $profileId )
		};
	}

	private static function areFriends( int $userId1, int $userId2 ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_connections';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d AND status='accepted'", $userId1, $userId2 ) );
	}

	private static function areCloseFriends( int $userId1, int $userId2 ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_close_friends';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d", $userId1, $userId2 ) );
	}

	private static function getVisibilityFilter( int $viewerId, int $profileId ): string {
		if ( $viewerId === $profileId || current_user_can( 'manage_options' ) ) {
			return '';}
		if ( self::areCloseFriends( $viewerId, $profileId ) ) {
			return "AND a.visibility IN ('public','friends','close_friends')";}
		if ( self::areFriends( $viewerId, $profileId ) ) {
			return "AND a.visibility IN ('public','friends')";}
		return "AND a.visibility='public'";
	}

	public static function getRecent( int $userId, int $limit = 10 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*,u.display_name as profile_name FROM {$t} a JOIN {$wpdb->users} u ON a.profile_id=u.ID WHERE a.user_id=%d AND a.type='wall_post' ORDER BY a.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getReceivedCount( int $profileId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE profile_id=%d AND user_id!=%d AND type='wall_post'", $profileId, $profileId ) );
	}
}
