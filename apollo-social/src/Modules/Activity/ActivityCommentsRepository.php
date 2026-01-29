<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

final class ActivityCommentsRepository {
	private const TABLE = 'apollo_activity_comments';

	public static function create( int $activityId, int $userId, string $content, ?int $parentId = null ): ?int {
		global $wpdb;
		$content = wp_kses_post( $content );
		if ( empty( \trim( $content ) ) ) {
			return null;}
		$r = $wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'activity_id' => $activityId,
				'user_id'     => $userId,
				'parent_id'   => $parentId,
				'content'     => $content,
			),
			array( '%d', '%d', '%d', '%s' )
		);
		if ( $r ) {
			self::updateActivityCommentCount( $activityId );
			$id = (int) $wpdb->insert_id;
			do_action( 'apollo_activity_comment_created', $id, $activityId, $userId );
			do_action( 'apollo_award_points', $userId, 2, 'activity_comment', 'Comentou em uma atividade' );
			return $id;
		}
		return null;
	}

	public static function update( int $id, int $userId, string $content ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->update(
			$t,
			array(
				'content'    => wp_kses_post( $content ),
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'      => $id,
				'user_id' => $userId,
			)
		);
	}

	public static function delete( int $id, int $userId ): bool {
		global $wpdb;
		$t       = $wpdb->prefix . self::TABLE;
		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT activity_id FROM {$t} WHERE id=%d AND user_id=%d", $id, $userId ), ARRAY_A );
		if ( ! $comment ) {
			return false;}
		$wpdb->delete( $t, array( 'parent_id' => $id ), array( '%d' ) );
		$r = $wpdb->delete(
			$t,
			array(
				'id'      => $id,
				'user_id' => $userId,
			),
			array( '%d', '%d' )
		);
		if ( $r ) {
			self::updateActivityCommentCount( (int) $comment['activity_id'] );}
		return (bool) $r;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $id ), ARRAY_A );
	}

	public static function getForActivity( int $activityId, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,u.display_name,u.user_login FROM {$t} c JOIN {$wpdb->users} u ON c.user_id=u.ID WHERE c.activity_id=%d AND c.is_hidden=0 ORDER BY c.created_at ASC LIMIT %d OFFSET %d",
				$activityId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getThreaded( int $activityId, int $limit = 50 ): array {
		$all      = self::getForActivity( $activityId, $limit * 2, 0 );
		$threaded = array();
		$map      = array();
		foreach ( $all as $c ) {
			$c['replies']    = array();
			$map[ $c['id'] ] = $c;}
		foreach ( $all as $c ) {
			if ( $c['parent_id'] && isset( $map[ $c['parent_id'] ] ) ) {
				$map[ $c['parent_id'] ]['replies'][] = $map[ $c['id'] ];
			} elseif ( ! $c['parent_id'] ) {
				$threaded[] = $map[ $c['id'] ];
			}
		}
		return array_slice( $threaded, 0, $limit );
	}

	public static function getReplies( int $parentId, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,u.display_name FROM {$t} c JOIN {$wpdb->users} u ON c.user_id=u.ID WHERE c.parent_id=%d AND c.is_hidden=0 ORDER BY c.created_at ASC LIMIT %d",
				$parentId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getCount( int $activityId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE activity_id=%d AND is_hidden=0", $activityId ) );
	}

	public static function getByUser( int $userId, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE user_id=%d AND is_hidden=0 ORDER BY created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function hide( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->update( $wpdb->prefix . self::TABLE, array( 'is_hidden' => 1 ), array( 'id' => $id ) );
	}

	public static function unhide( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->update( $wpdb->prefix . self::TABLE, array( 'is_hidden' => 0 ), array( 'id' => $id ) );
	}

	private static function updateActivityCommentCount( int $activityId ): void {
		global $wpdb;
		$count = self::getCount( $activityId );
		$wpdb->update( $wpdb->prefix . 'apollo_activity', array( 'comment_count' => $count ), array( 'id' => $activityId ) );
	}

	public static function like( int $commentId, int $userId ): bool {
		global $wpdb;
		$t      = $wpdb->prefix . 'apollo_comment_likes';
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE comment_id=%d AND user_id=%d", $commentId, $userId ) );
		if ( $exists ) {
			return true;}
		$r = $wpdb->insert(
			$t,
			array(
				'comment_id' => $commentId,
				'user_id'    => $userId,
			),
			array( '%d', '%d' )
		);
		if ( $r ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}" . self::TABLE . ' SET likes_count=likes_count+1 WHERE id=%d', $commentId ) );
		}
		return (bool) $r;
	}

	public static function unlike( int $commentId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_comment_likes';
		$r = $wpdb->delete(
			$t,
			array(
				'comment_id' => $commentId,
				'user_id'    => $userId,
			),
			array( '%d', '%d' )
		);
		if ( $r ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}" . self::TABLE . ' SET likes_count=GREATEST(likes_count-1,0) WHERE id=%d', $commentId ) );
		}
		return (bool) $r;
	}

	public static function hasLiked( int $commentId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_comment_likes';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE comment_id=%d AND user_id=%d", $commentId, $userId ) );
	}

	public static function getRecent( int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,u.display_name FROM {$t} c JOIN {$wpdb->users} u ON c.user_id=u.ID WHERE c.is_hidden=0 ORDER BY c.created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function deleteForActivity( int $activityId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$t} WHERE activity_id=%d", $activityId ) );
	}
}
