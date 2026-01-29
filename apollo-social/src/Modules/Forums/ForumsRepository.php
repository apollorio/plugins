<?php
declare(strict_types=1);
namespace Apollo\Modules\Forums;

final class ForumsRepository {
	private const FORUMS  = 'apollo_forums';
	private const TOPICS  = 'apollo_forum_topics';
	private const REPLIES = 'apollo_forum_replies';

	public static function createForum( array $data ): int {
		global $wpdb;
		$t     = $wpdb->prefix . self::FORUMS;
		$order = (int) $wpdb->get_var( "SELECT MAX(sort_order) FROM {$t}" ) + 1;
		$wpdb->insert(
			$t,
			array(
				'name'        => sanitize_text_field( $data['name'] ),
				'slug'        => sanitize_title( $data['name'] ),
				'description' => wp_kses_post( $data['description'] ?? '' ),
				'parent_id'   => (int) ( $data['parent_id'] ?? 0 ),
				'group_id'    => (int) ( $data['group_id'] ?? 0 ),
				'visibility'  => $data['visibility'] ?? 'public',
				'sort_order'  => $order,
				'is_active'   => 1,
				'created_by'  => get_current_user_id(),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function updateForum( int $forumId, array $data ): bool {
		global $wpdb;
		$t   = $wpdb->prefix . self::FORUMS;
		$upd = array();
		$fmt = array();
		if ( isset( $data['name'] ) ) {
			$upd['name'] = sanitize_text_field( $data['name'] );
			$fmt[]       = '%s';}
		if ( isset( $data['description'] ) ) {
			$upd['description'] = wp_kses_post( $data['description'] );
			$fmt[]              = '%s';}
		if ( isset( $data['visibility'] ) ) {
			$upd['visibility'] = $data['visibility'];
			$fmt[]             = '%s';}
		if ( isset( $data['is_active'] ) ) {
			$upd['is_active'] = (int) $data['is_active'];
			$fmt[]            = '%d';}
		return $wpdb->update( $t, $upd, array( 'id' => $forumId ), $fmt, array( '%d' ) ) !== false;
	}

	public static function getForum( int $forumId ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::FORUMS;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $forumId ), ARRAY_A );
		return $row ?: null;
	}

	public static function getForums( int $parentId = 0, int $groupId = 0 ): array {
		global $wpdb;
		$t     = $wpdb->prefix . self::FORUMS;
		$top   = $wpdb->prefix . self::TOPICS;
		$where = array( 'f.is_active=1' );
		$args  = array();
		if ( $parentId >= 0 ) {
			$where[] = 'f.parent_id=%d';
			$args[]  = $parentId;}
		if ( $groupId > 0 ) {
			$where[] = 'f.group_id=%d';
			$args[]  = $groupId;}
		$whereStr = \implode( ' AND ', $where );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.*,
			(SELECT COUNT(*) FROM {$top} WHERE forum_id=f.id) as topic_count,
			(SELECT COUNT(*) FROM {$wpdb->prefix}apollo_forum_replies r JOIN {$top} tp ON r.topic_id=tp.id WHERE tp.forum_id=f.id) as reply_count
			FROM {$t} f WHERE {$whereStr} ORDER BY f.sort_order ASC",
				...$args
			),
			ARRAY_A
		) ?? array();
	}

	public static function createTopic( int $forumId, int $userId, array $data ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TOPICS;
		$wpdb->insert(
			$t,
			array(
				'forum_id'      => $forumId,
				'user_id'       => $userId,
				'title'         => sanitize_text_field( $data['title'] ),
				'content'       => wp_kses_post( $data['content'] ),
				'is_sticky'     => (int) ( $data['is_sticky'] ?? 0 ),
				'is_closed'     => 0,
				'view_count'    => 0,
				'reply_count'   => 0,
				'last_reply_at' => null,
				'last_reply_by' => null,
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
		);
		$topicId = (int) $wpdb->insert_id;
		if ( $topicId ) {
			self::updateForumStats( $forumId );
			do_action( 'apollo_forum_topic_created', $topicId, $forumId, $userId );
		}
		return $topicId;
	}

	public static function updateTopic( int $topicId, int $userId, array $data ): bool {
		global $wpdb;
		$t     = $wpdb->prefix . self::TOPICS;
		$topic = self::getTopic( $topicId );
		if ( ! $topic || (int) $topic['user_id'] !== $userId && ! current_user_can( 'manage_options' ) ) {
			return false;}
		$upd = array();
		$fmt = array();
		if ( isset( $data['title'] ) ) {
			$upd['title'] = sanitize_text_field( $data['title'] );
			$fmt[]        = '%s';}
		if ( isset( $data['content'] ) ) {
			$upd['content'] = wp_kses_post( $data['content'] );
			$fmt[]          = '%s';}
		if ( isset( $data['is_sticky'] ) && current_user_can( 'manage_options' ) ) {
			$upd['is_sticky'] = (int) $data['is_sticky'];
			$fmt[]            = '%d';}
		if ( isset( $data['is_closed'] ) && current_user_can( 'manage_options' ) ) {
			$upd['is_closed'] = (int) $data['is_closed'];
			$fmt[]            = '%d';}
		$upd['updated_at'] = current_time( 'mysql' );
		$fmt[]             = '%s';
		return $wpdb->update( $t, $upd, array( 'id' => $topicId ), $fmt, array( '%d' ) ) !== false;
	}

	public static function deleteTopic( int $topicId, int $userId ): bool {
		global $wpdb;
		$topic = self::getTopic( $topicId );
		if ( ! $topic || (int) $topic['user_id'] !== $userId && ! current_user_can( 'manage_options' ) ) {
			return false;}
		$t = $wpdb->prefix . self::TOPICS;
		$r = $wpdb->prefix . self::REPLIES;
		$wpdb->delete( $r, array( 'topic_id' => $topicId ), array( '%d' ) );
		$result = $wpdb->delete( $t, array( 'id' => $topicId ), array( '%d' ) ) !== false;
		if ( $result ) {
			self::updateForumStats( (int) $topic['forum_id'] );}
		return $result;
	}

	public static function getTopic( int $topicId ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::TOPICS;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $topicId ), ARRAY_A );
		return $row ?: null;
	}

	public static function getTopics( int $forumId, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TOPICS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*,u.display_name,
			(SELECT display_name FROM {$wpdb->users} WHERE ID=t.last_reply_by) as last_replier
			FROM {$t} t
			JOIN {$wpdb->users} u ON t.user_id=u.ID
			WHERE t.forum_id=%d
			ORDER BY t.is_sticky DESC, COALESCE(t.last_reply_at,t.created_at) DESC
			LIMIT %d OFFSET %d",
				$forumId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function incrementViewCount( int $topicId ): void {
		global $wpdb;
		$t = $wpdb->prefix . self::TOPICS;
		$wpdb->query( $wpdb->prepare( "UPDATE {$t} SET view_count=view_count+1 WHERE id=%d", $topicId ) );
	}

	public static function createReply( int $topicId, int $userId, string $content ): int {
		global $wpdb;
		$topic = self::getTopic( $topicId );
		if ( ! $topic || $topic['is_closed'] ) {
			return 0;}
		$t = $wpdb->prefix . self::REPLIES;
		$wpdb->insert(
			$t,
			array(
				'topic_id'   => $topicId,
				'user_id'    => $userId,
				'content'    => wp_kses_post( $content ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		$replyId = (int) $wpdb->insert_id;
		if ( $replyId ) {
			self::updateTopicStats( $topicId, $userId );
			self::updateForumStats( (int) $topic['forum_id'] );
			do_action( 'apollo_forum_reply_created', $replyId, $topicId, $userId );
		}
		return $replyId;
	}

	public static function updateReply( int $replyId, int $userId, string $content ): bool {
		global $wpdb;
		$t     = $wpdb->prefix . self::REPLIES;
		$reply = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $replyId ), ARRAY_A );
		if ( ! $reply || (int) $reply['user_id'] !== $userId && ! current_user_can( 'manage_options' ) ) {
			return false;}
		return $wpdb->update(
			$t,
			array(
				'content'    => wp_kses_post( $content ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $replyId ),
			array( '%s', '%s' ),
			array( '%d' )
		) !== false;
	}

	public static function deleteReply( int $replyId, int $userId ): bool {
		global $wpdb;
		$t     = $wpdb->prefix . self::REPLIES;
		$reply = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $replyId ), ARRAY_A );
		if ( ! $reply || (int) $reply['user_id'] !== $userId && ! current_user_can( 'manage_options' ) ) {
			return false;}
		$result = $wpdb->delete( $t, array( 'id' => $replyId ), array( '%d' ) ) !== false;
		if ( $result ) {
			self::recountTopicReplies( (int) $reply['topic_id'] );}
		return $result;
	}

	public static function getReplies( int $topicId, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::REPLIES;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*,u.display_name,um.meta_value as avatar
			FROM {$t} r
			JOIN {$wpdb->users} u ON r.user_id=u.ID
			LEFT JOIN {$wpdb->usermeta} um ON r.user_id=um.user_id AND um.meta_key='apollo_avatar'
			WHERE r.topic_id=%d
			ORDER BY r.created_at ASC
			LIMIT %d OFFSET %d",
				$topicId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getReplyCount( int $topicId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::REPLIES;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE topic_id=%d", $topicId ) );
	}

	public static function subscribeTopic( int $topicId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_forum_subscriptions';
		if ( self::isSubscribed( $topicId, $userId ) ) {
			return true;}
		return $wpdb->insert(
			$t,
			array(
				'topic_id'   => $topicId,
				'user_id'    => $userId,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		) !== false;
	}

	public static function unsubscribeTopic( int $topicId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_forum_subscriptions';
		return $wpdb->delete(
			$t,
			array(
				'topic_id' => $topicId,
				'user_id'  => $userId,
			),
			array( '%d', '%d' )
		) !== false;
	}

	public static function isSubscribed( int $topicId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_forum_subscriptions';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE topic_id=%d AND user_id=%d", $topicId, $userId ) );
	}

	public static function getTopicSubscribers( int $topicId ): array {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_forum_subscriptions';
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$t} WHERE topic_id=%d", $topicId ) ) ?? array();
	}

	public static function search( string $query, int $forumId = 0, int $limit = 20 ): array {
		global $wpdb;
		$t           = $wpdb->prefix . self::TOPICS;
		$q           = '%' . $wpdb->esc_like( $query ) . '%';
		$forumFilter = $forumId > 0 ? $wpdb->prepare( ' AND t.forum_id=%d', $forumId ) : '';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*,u.display_name FROM {$t} t JOIN {$wpdb->users} u ON t.user_id=u.ID WHERE (t.title LIKE %s OR t.content LIKE %s) {$forumFilter} ORDER BY t.created_at DESC LIMIT %d",
				$q,
				$q,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getRecentTopics( int $limit = 10 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TOPICS;
		$f = $wpdb->prefix . self::FORUMS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*,u.display_name,f.name as forum_name FROM {$t} t JOIN {$wpdb->users} u ON t.user_id=u.ID JOIN {$f} f ON t.forum_id=f.id WHERE f.visibility='public' ORDER BY t.created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUserTopics( int $userId, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TOPICS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*,f.name as forum_name FROM {$t} t JOIN {$wpdb->prefix}apollo_forums f ON t.forum_id=f.id WHERE t.user_id=%d ORDER BY t.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUserReplies( int $userId, int $limit = 20 ): array {
		global $wpdb;
		$r = $wpdb->prefix . self::REPLIES;
		$t = $wpdb->prefix . self::TOPICS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*,t.title as topic_title FROM {$r} r JOIN {$t} t ON r.topic_id=t.id WHERE r.user_id=%d ORDER BY r.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getStats(): array {
		global $wpdb;
		$f = $wpdb->prefix . self::FORUMS;
		$t = $wpdb->prefix . self::TOPICS;
		$r = $wpdb->prefix . self::REPLIES;
		return array(
			'forums'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$f} WHERE is_active=1" ),
			'topics'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" ),
			'replies'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$r}" ),
			'today_topics'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE DATE(created_at)=%s", gmdate( 'Y-m-d' ) ) ),
			'today_replies' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$r} WHERE DATE(created_at)=%s", gmdate( 'Y-m-d' ) ) ),
		);
	}

	private static function updateTopicStats( int $topicId, int $userId ): void {
		global $wpdb;
		$t = $wpdb->prefix . self::TOPICS;
		$wpdb->update(
			$t,
			array(
				'reply_count'   => self::getReplyCount( $topicId ),
				'last_reply_at' => current_time( 'mysql' ),
				'last_reply_by' => $userId,
			),
			array( 'id' => $topicId ),
			array( '%d', '%s', '%d' ),
			array( '%d' )
		);
	}

	private static function recountTopicReplies( int $topicId ): void {
		global $wpdb;
		$t     = $wpdb->prefix . self::TOPICS;
		$count = self::getReplyCount( $topicId );
		$wpdb->update( $t, array( 'reply_count' => $count ), array( 'id' => $topicId ), array( '%d' ), array( '%d' ) );
	}

	private static function updateForumStats( int $forumId ): void {
		global $wpdb;
		$f     = $wpdb->prefix . self::FORUMS;
		$t     = $wpdb->prefix . self::TOPICS;
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE forum_id=%d", $forumId ) );
		$wpdb->update(
			$f,
			array(
				'topic_count' => $count,
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'id' => $forumId ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}
}
