<?php
declare(strict_types=1);
namespace Apollo\Modules\Polls;

final class PollsRepository {
	private const T_POLLS   = 'apollo_polls';
	private const T_OPTIONS = 'apollo_poll_options';
	private const T_VOTES   = 'apollo_poll_votes';

	public static function create( int $userId, string $question, array $options, array $settings = array() ): int|false {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		$o = $wpdb->prefix . self::T_OPTIONS;
		$wpdb->insert(
			$p,
			array(
				'user_id'         => $userId,
				'question'        => $question,
				'multiple_choice' => ! empty( $settings['multiple'] ) ? 1 : 0,
				'anonymous'       => ! empty( $settings['anonymous'] ) ? 1 : 0,
				'expires_at'      => $settings['expires_at'] ?? null,
				'visibility'      => $settings['visibility'] ?? 'public',
				'activity_id'     => $settings['activity_id'] ?? null,
				'group_id'        => $settings['group_id'] ?? null,
				'status'          => 'active',
				'created_at'      => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
		$pollId = $wpdb->insert_id;
		if ( ! $pollId ) {
			return false;
		}
		foreach ( $options as $i => $text ) {
			$wpdb->insert(
				$o,
				array(
					'poll_id'     => $pollId,
					'option_text' => $text,
					'sort_order'  => $i,
				),
				array( '%d', '%s', '%d' )
			);
		}
		return $pollId;
	}

	public static function vote( int $pollId, int $userId, int|array $optionIds ): bool {
		global $wpdb;
		$v    = $wpdb->prefix . self::T_VOTES;
		$poll = self::get( $pollId );
		if ( ! $poll || $poll['status'] !== 'active' ) {
			return false;
		}
		if ( $poll['expires_at'] && strtotime( $poll['expires_at'] ) < time() ) {
			return false;
		}
		if ( self::hasVoted( $pollId, $userId ) ) {
			return false;
		}
		$optionIds = is_array( $optionIds ) ? $optionIds : array( $optionIds );
		if ( ! $poll['multiple_choice'] && count( $optionIds ) > 1 ) {
			$optionIds = array( $optionIds[0] );
		}
		foreach ( $optionIds as $optionId ) {
			$wpdb->insert(
				$v,
				array(
					'poll_id'    => $pollId,
					'option_id'  => $optionId,
					'user_id'    => $userId,
					'created_at' => gmdate( 'Y-m-d H:i:s' ),
				),
				array( '%d', '%d', '%d', '%s' )
			);
		}
		self::updateCounts( $pollId );
		return true;
	}

	public static function removeVote( int $pollId, int $userId ): bool {
		global $wpdb;
		$v      = $wpdb->prefix . self::T_VOTES;
		$result = $wpdb->delete(
			$v,
			array(
				'poll_id' => $pollId,
				'user_id' => $userId,
			),
			array( '%d', '%d' )
		);
		if ( $result ) {
			self::updateCounts( $pollId );
		}
		return (bool) $result;
	}

	public static function hasVoted( int $pollId, int $userId ): bool {
		global $wpdb;
		$v = $wpdb->prefix . self::T_VOTES;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$v} WHERE poll_id=%d AND user_id=%d", $pollId, $userId ) );
	}

	public static function getUserVotes( int $pollId, int $userId ): array {
		global $wpdb;
		$v = $wpdb->prefix . self::T_VOTES;
		return $wpdb->get_col( $wpdb->prepare( "SELECT option_id FROM {$v} WHERE poll_id=%d AND user_id=%d", $pollId, $userId ) );
	}

	public static function get( int $pollId ): ?array {
		global $wpdb;
		$p    = $wpdb->prefix . self::T_POLLS;
		$poll = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p} WHERE id=%d", $pollId ), ARRAY_A );
		if ( ! $poll ) {
			return null;
		}
		$poll['options'] = self::getOptions( $pollId );
		return $poll;
	}

	public static function getOptions( int $pollId ): array {
		global $wpdb;
		$o = $wpdb->prefix . self::T_OPTIONS;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$o} WHERE poll_id=%d ORDER BY sort_order ASC", $pollId ), ARRAY_A ) ?? array();
	}

	public static function getResults( int $pollId, int $viewerId = 0 ): array {
		$poll = self::get( $pollId );
		if ( ! $poll ) {
			return array();
		}
		$options = $poll['options'];
		$total   = (int) $poll['total_votes'];
		$result  = array();
		foreach ( $options as $opt ) {
			$votes = (int) $opt['votes'];
			$pct   = $total > 0 ? round( ( $votes / $total ) * 100, 1 ) : 0;
			$entry = array(
				'id'         => $opt['id'],
				'text'       => $opt['option_text'],
				'votes'      => $votes,
				'percentage' => $pct,
			);
			if ( ! $poll['anonymous'] ) {
				$entry['voters'] = self::getOptionVoters( (int) $opt['id'], 5 );
			}
			$result[] = $entry;
		}
		return array(
			'poll'        => $poll,
			'results'     => $result,
			'total_votes' => $total,
			'user_voted'  => $viewerId > 0 ? self::hasVoted( $pollId, $viewerId ) : false,
			'user_votes'  => $viewerId > 0 ? self::getUserVotes( $pollId, $viewerId ) : array(),
		);
	}

	public static function getOptionVoters( int $optionId, int $limit = 10 ): array {
		global $wpdb;
		$v = $wpdb->prefix . self::T_VOTES;
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$v} WHERE option_id=%d LIMIT %d", $optionId, $limit ) );
	}

	private static function updateCounts( int $pollId ): void {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		$o = $wpdb->prefix . self::T_OPTIONS;
		$v = $wpdb->prefix . self::T_VOTES;
		$wpdb->query( $wpdb->prepare( "UPDATE {$o} SET votes=(SELECT COUNT(*) FROM {$v} WHERE option_id={$o}.id) WHERE poll_id=%d", $pollId ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$p} SET total_votes=(SELECT COUNT(DISTINCT user_id) FROM {$v} WHERE poll_id=%d) WHERE id=%d", $pollId, $pollId ) );
	}

	public static function close( int $pollId, int $userId ): bool {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		return (bool) $wpdb->update(
			$p,
			array( 'status' => 'closed' ),
			array(
				'id'      => $pollId,
				'user_id' => $userId,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);
	}

	public static function delete( int $pollId, int $userId ): bool {
		global $wpdb;
		$p    = $wpdb->prefix . self::T_POLLS;
		$o    = $wpdb->prefix . self::T_OPTIONS;
		$v    = $wpdb->prefix . self::T_VOTES;
		$poll = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM {$p} WHERE id=%d", $pollId ), ARRAY_A );
		if ( ! $poll || (int) $poll['user_id'] !== $userId ) {
			return false;
		}
		$wpdb->delete( $v, array( 'poll_id' => $pollId ), array( '%d' ) );
		$wpdb->delete( $o, array( 'poll_id' => $pollId ), array( '%d' ) );
		return (bool) $wpdb->delete( $p, array( 'id' => $pollId ), array( '%d' ) );
	}

	public static function getByUser( int $userId, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$p} WHERE user_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d", $userId, $limit, $offset ), ARRAY_A ) ?? array();
	}

	public static function getByActivity( int $activityId ): ?array {
		global $wpdb;
		$p      = $wpdb->prefix . self::T_POLLS;
		$pollId = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$p} WHERE activity_id=%d", $activityId ) );
		return $pollId ? self::get( (int) $pollId ) : null;
	}

	public static function getByGroup( int $groupId, int $limit = 10 ): array {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$p} WHERE group_id=%d AND status='active' ORDER BY created_at DESC LIMIT %d", $groupId, $limit ), ARRAY_A ) ?? array();
	}

	public static function getActive( int $limit = 10 ): array {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$p} WHERE status='active' AND (expires_at IS NULL OR expires_at>NOW()) ORDER BY total_votes DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function expireOld(): int {
		global $wpdb;
		$p = $wpdb->prefix . self::T_POLLS;
		return (int) $wpdb->query( "UPDATE {$p} SET status='expired' WHERE status='active' AND expires_at IS NOT NULL AND expires_at<NOW()" );
	}
}
