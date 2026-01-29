<?php
declare(strict_types=1);
namespace Apollo\Modules\Messaging;

final class MessagingRepository {
	private const THREADS  = 'apollo_message_threads';
	private const MESSAGES = 'apollo_messages';
	private const PARTS    = 'apollo_message_participants';

	public static function createThread( array $participantIds, ?string $subject = null ): int {
		global $wpdb;
		if ( count( $participantIds ) < 2 ) {
			return 0;
		}
		sort( $participantIds );
		$hash     = md5( \implode( '-', $participantIds ) );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}" . self::THREADS . ' WHERE participants_hash=%s', $hash ) );
		if ( $existing ) {
			return (int) $existing;
		}
		$wpdb->insert(
			$wpdb->prefix . self::THREADS,
			array(
				'subject'           => $subject ? sanitize_text_field( $subject ) : null,
				'participants_hash' => $hash,
				'participant_count' => count( $participantIds ),
			)
		);
		$threadId = (int) $wpdb->insert_id;
		foreach ( $participantIds as $uid ) {
			$wpdb->insert(
				$wpdb->prefix . self::PARTS,
				array(
					'thread_id' => $threadId,
					'user_id'   => (int) $uid,
				)
			);
		}
		return $threadId;
	}

	public static function sendMessage( int $threadId, int $senderId, string $content ): int {
		global $wpdb;
		if ( ! self::isParticipant( $threadId, $senderId ) ) {
			return 0;
		}
		$wpdb->insert(
			$wpdb->prefix . self::MESSAGES,
			array(
				'thread_id' => $threadId,
				'sender_id' => $senderId,
				'content'   => wp_kses_post( $content ),
			)
		);
		$msgId = (int) $wpdb->insert_id;
		$wpdb->update(
			$wpdb->prefix . self::THREADS,
			array(
				'last_message_at' => current_time( 'mysql' ),
				'last_message_id' => $msgId,
			),
			array( 'id' => $threadId )
		);
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}" . self::PARTS . ' SET is_read=0 WHERE thread_id=%d AND user_id!=%d',
				$threadId,
				$senderId
			)
		);
		return $msgId;
	}

	public static function getThread( int $threadId ): ?array {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::THREADS . ' WHERE id=%d', $threadId ), ARRAY_A ) ?: null;
	}

	public static function getUserThreads( int $userId, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*,p.is_read,p.last_read_at FROM {$wpdb->prefix}" . self::THREADS . " t
			 INNER JOIN {$wpdb->prefix}" . self::PARTS . ' p ON p.thread_id=t.id
			 WHERE p.user_id=%d ORDER BY t.last_message_at DESC LIMIT %d OFFSET %d',
				$userId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?: array();
	}

	public static function getMessages( int $threadId, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.*,u.display_name as sender_name FROM {$wpdb->prefix}" . self::MESSAGES . " m
			 LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id
			 WHERE m.thread_id=%d ORDER BY m.created_at DESC LIMIT %d OFFSET %d",
				$threadId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?: array();
	}

	public static function getParticipants( int $threadId ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,u.display_name,u.user_email FROM {$wpdb->prefix}" . self::PARTS . " p
			 LEFT JOIN {$wpdb->users} u ON u.ID=p.user_id WHERE p.thread_id=%d",
				$threadId
			),
			ARRAY_A
		) ?: array();
	}

	public static function isParticipant( int $threadId, int $userId ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}" . self::PARTS . ' WHERE thread_id=%d AND user_id=%d',
				$threadId,
				$userId
			)
		);
	}

	public static function markRead( int $threadId, int $userId ): bool {
		global $wpdb;
		return $wpdb->update(
			$wpdb->prefix . self::PARTS,
			array(
				'is_read'      => 1,
				'last_read_at' => current_time( 'mysql' ),
			),
			array(
				'thread_id' => $threadId,
				'user_id'   => $userId,
			)
		) !== false;
	}

	public static function countUnread( int $userId ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::PARTS . ' WHERE user_id=%d AND is_read=0',
				$userId
			)
		);
	}

	public static function deleteThread( int $threadId ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::MESSAGES, array( 'thread_id' => $threadId ) );
		$wpdb->delete( $wpdb->prefix . self::PARTS, array( 'thread_id' => $threadId ) );
		return $wpdb->delete( $wpdb->prefix . self::THREADS, array( 'id' => $threadId ) ) !== false;
	}
}
