<?php
declare(strict_types=1);
namespace Apollo\Modules\Moderation;

final class ContentModerationRepository {
	private const T_QUEUE   = 'apollo_moderation_queue';
	private const T_ACTIONS = 'apollo_moderation_actions';
	private const T_RULES   = 'apollo_moderation_rules';

	public static function addToQueue( string $contentType, int $contentId, int $authorId, string $content, string $reason = 'auto' ): int|false {
		global $wpdb;
		$t      = $wpdb->prefix . self::T_QUEUE;
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$t} WHERE content_type=%s AND content_id=%d AND status='pending'",
				$contentType,
				$contentId
			)
		);
		if ( $exists ) {
			return (int) $exists;
		}
		$wpdb->insert(
			$t,
			array(
				'content_type'    => $contentType,
				'content_id'      => $contentId,
				'author_id'       => $authorId,
				'content_preview' => mb_substr( \strip_tags( $content ), 0, 500 ),
				'reason'          => $reason,
				'status'          => 'pending',
				'created_at'      => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		return $wpdb->insert_id ?: false;
	}

	public static function approve( int $queueId, int $moderatorId, string $notes = '' ): bool {
		return self::processItem( $queueId, $moderatorId, 'approved', $notes );
	}

	public static function reject( int $queueId, int $moderatorId, string $notes = '', bool $deleteContent = false ): bool {
		$result = self::processItem( $queueId, $moderatorId, 'rejected', $notes );
		if ( $result && $deleteContent ) {
			$item = self::getQueueItem( $queueId );
			if ( $item ) {
				self::deleteContent( $item['content_type'], (int) $item['content_id'] );
			}
		}
		return $result;
	}

	public static function escalate( int $queueId, int $moderatorId, string $notes = '' ): bool {
		return self::processItem( $queueId, $moderatorId, 'escalated', $notes );
	}

	private static function processItem( int $queueId, int $moderatorId, string $action, string $notes ): bool {
		global $wpdb;
		$q    = $wpdb->prefix . self::T_QUEUE;
		$a    = $wpdb->prefix . self::T_ACTIONS;
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$q} WHERE id=%d", $queueId ), ARRAY_A );
		if ( ! $item ) {
			return false;
		}
		$wpdb->update(
			$q,
			array(
				'status'          => $action,
				'moderator_id'    => $moderatorId,
				'reviewed_at'     => gmdate( 'Y-m-d H:i:s' ),
				'moderator_notes' => $notes,
			),
			array( 'id' => $queueId ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		$wpdb->insert(
			$a,
			array(
				'queue_id'     => $queueId,
				'content_type' => $item['content_type'],
				'content_id'   => $item['content_id'],
				'author_id'    => $item['author_id'],
				'moderator_id' => $moderatorId,
				'action'       => $action,
				'notes'        => $notes,
				'created_at'   => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		return true;
	}

	public static function getQueueItem( int $id ): ?array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_QUEUE;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $id ), ARRAY_A );
	}

	public static function getQueue( string $status = 'pending', int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_QUEUE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT q.*,u.display_name as author_name FROM {$t} q LEFT JOIN {$wpdb->users} u ON q.author_id=u.ID WHERE q.status=%s ORDER BY q.created_at ASC LIMIT %d OFFSET %d",
				$status,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getQueueCount( string $status = 'pending' ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::T_QUEUE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE status=%s", $status ) );
	}

	public static function getQueueStats(): array {
		global $wpdb;
		$t      = $wpdb->prefix . self::T_QUEUE;
		$stats  = $wpdb->get_results( "SELECT status,COUNT(*) as count FROM {$t} GROUP BY status", ARRAY_A ) ?? array();
		$result = array(
			'pending'   => 0,
			'approved'  => 0,
			'rejected'  => 0,
			'escalated' => 0,
		);
		foreach ( $stats as $s ) {
			$result[ $s['status'] ] = (int) $s['count'];
		}
		return $result;
	}

	public static function getModeratorActions( int $moderatorId, int $limit = 50 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::T_ACTIONS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE moderator_id=%d ORDER BY created_at DESC LIMIT %d",
				$moderatorId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUserModerationHistory( int $userId ): array {
		global $wpdb;
		$t       = $wpdb->prefix . self::T_ACTIONS;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT action,COUNT(*) as count FROM {$t} WHERE author_id=%d GROUP BY action",
				$userId
			),
			ARRAY_A
		) ?? array();
		$history = array(
			'approved'  => 0,
			'rejected'  => 0,
			'escalated' => 0,
			'total'     => 0,
		);
		foreach ( $results as $r ) {
			$history[ $r['action'] ] = (int) $r['count'];
			$history['total']       += (int) $r['count'];
		}
		return $history;
	}

	public static function createRule( string $type, string $pattern, string $action, int $priority = 0 ): int|false {
		global $wpdb;
		$t = $wpdb->prefix . self::T_RULES;
		$wpdb->insert(
			$t,
			array(
				'type'       => $type,
				'pattern'    => $pattern,
				'action'     => $action,
				'priority'   => $priority,
				'is_active'  => 1,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s' )
		);
		return $wpdb->insert_id ?: false;
	}

	public static function getRules( bool $activeOnly = true ): array {
		global $wpdb;
		$t     = $wpdb->prefix . self::T_RULES;
		$where = $activeOnly ? 'WHERE is_active=1' : '';
		return $wpdb->get_results( "SELECT * FROM {$t} {$where} ORDER BY priority DESC", ARRAY_A ) ?? array();
	}

	public static function toggleRule( int $ruleId, bool $active ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::T_RULES;
		return (bool) $wpdb->update( $t, array( 'is_active' => $active ? 1 : 0 ), array( 'id' => $ruleId ), array( '%d' ), array( '%d' ) );
	}

	public static function deleteRule( int $ruleId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::T_RULES;
		return (bool) $wpdb->delete( $t, array( 'id' => $ruleId ), array( '%d' ) );
	}

	public static function checkContent( string $content, string $contentType ): array {
		$rules = self::getRules();
		$flags = array();
		foreach ( $rules as $rule ) {
			if ( $rule['type'] === 'keyword' ) {
				if ( stripos( $content, $rule['pattern'] ) !== false ) {
					$flags[] = array(
						'rule_id' => $rule['id'],
						'type'    => 'keyword',
						'pattern' => $rule['pattern'],
						'action'  => $rule['action'],
					);
				}
			} elseif ( $rule['type'] === 'regex' ) {
				if ( preg_match( '/' . $rule['pattern'] . '/iu', $content ) ) {
					$flags[] = array(
						'rule_id' => $rule['id'],
						'type'    => 'regex',
						'pattern' => $rule['pattern'],
						'action'  => $rule['action'],
					);
				}
			} elseif ( $rule['type'] === 'url' ) {
				if ( preg_match( '/https?:\/\/[^\s]*' . preg_quote( $rule['pattern'], '/' ) . '/i', $content ) ) {
					$flags[] = array(
						'rule_id' => $rule['id'],
						'type'    => 'url',
						'pattern' => $rule['pattern'],
						'action'  => $rule['action'],
					);
				}
			}
		}
		return $flags;
	}

	public static function autoModerate( string $contentType, int $contentId, int $authorId, string $content ): string {
		$flags = self::checkContent( $content, $contentType );
		if ( empty( $flags ) ) {
			return 'pass';
		}
		$actions = array_column( $flags, 'action' );
		if ( in_array( 'block', $actions, true ) ) {
			return 'block';
		}
		if ( in_array( 'queue', $actions, true ) ) {
			self::addToQueue( $contentType, $contentId, $authorId, $content, 'auto:' . json_encode( $flags ) );
			return 'queue';
		}
		return 'pass';
	}

	private static function deleteContent( string $type, int $id ): void {
		global $wpdb;
		match ( $type ) {
			'activity'=>$wpdb->delete( $wpdb->prefix . 'apollo_activity', array( 'id' => $id ), array( '%d' ) ),
			'comment'=>$wpdb->delete( $wpdb->prefix . 'apollo_activity_comments', array( 'id' => $id ), array( '%d' ) ),
			'message'=>$wpdb->update( $wpdb->prefix . 'apollo_messages', array( 'is_deleted' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ),
			default=>null
		};
	}

	public static function bulkProcess( array $queueIds, int $moderatorId, string $action, string $notes = '' ): array {
		$results = array(
			'success' => 0,
			'failed'  => 0,
		);
		foreach ( $queueIds as $id ) {
			$ok = match ( $action ) {
				'approve'=>self::approve( $id, $moderatorId, $notes ),
				'reject'=>self::reject( $id, $moderatorId, $notes ),
				'escalate'=>self::escalate( $id, $moderatorId, $notes ),
				default=>false
			};
			$ok ? $results['success']++ : $results['failed']++;
		}
		return $results;
	}
}
