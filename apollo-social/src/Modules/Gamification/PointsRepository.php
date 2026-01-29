<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;

final class PointsRepository {
	private const TABLE = 'apollo_points';
	private const LOG   = 'apollo_points_log';

	public static function getBalance( int $userId, string $type = 'default' ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT balance FROM {$wpdb->prefix}" . self::TABLE . ' WHERE user_id=%d AND points_type=%s',
				$userId,
				$type
			)
		) ?: 0;
	}

	public static function getTotalEarned( int $userId, string $type = 'default' ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT total_earned FROM {$wpdb->prefix}" . self::TABLE . ' WHERE user_id=%d AND points_type=%s',
				$userId,
				$type
			)
		) ?: 0;
	}

	public static function add( int $userId, int $amount, string $trigger, string $type = 'default', ?int $refId = null, ?string $refType = null, ?string $desc = null ): bool {
		if ( $amount <= 0 ) {
			return false;
		}
		global $wpdb;
		$current = self::getBalance( $userId, $type );
		$newBal  = $current + $amount;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}" . self::TABLE . ' (user_id,points_type,balance,total_earned) VALUES (%d,%s,%d,%d)
			 ON DUPLICATE KEY UPDATE balance=balance+%d,total_earned=total_earned+%d',
				$userId,
				$type,
				$amount,
				$amount,
				$amount,
				$amount
			)
		);
		return self::log( $userId, $amount, $newBal, $trigger, $type, $refId, $refType, $desc );
	}

	public static function deduct( int $userId, int $amount, string $trigger, string $type = 'default', ?int $refId = null, ?string $refType = null, ?string $desc = null ): bool {
		if ( $amount <= 0 ) {
			return false;
		}
		global $wpdb;
		$current = self::getBalance( $userId, $type );
		if ( $current < $amount ) {
			return false;
		}
		$newBal = $current - $amount;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}" . self::TABLE . ' SET balance=balance-%d,total_spent=total_spent+%d WHERE user_id=%d AND points_type=%s',
				$amount,
				$amount,
				$userId,
				$type
			)
		);
		return self::log( $userId, -$amount, $newBal, $trigger, $type, $refId, $refType, $desc );
	}

	public static function set( int $userId, int $amount, string $trigger, string $type = 'default', ?string $desc = null ): bool {
		global $wpdb;
		$current = self::getBalance( $userId, $type );
		$diff    = $amount - $current;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}" . self::TABLE . ' (user_id,points_type,balance,total_earned) VALUES (%d,%s,%d,%d)
			 ON DUPLICATE KEY UPDATE balance=%d',
				$userId,
				$type,
				$amount,
				max( 0, $diff ),
				$amount
			)
		);
		return self::log( $userId, $diff, $amount, $trigger, $type, null, null, $desc );
	}

	private static function log( int $userId, int $amount, int $balAfter, string $trigger, string $type, ?int $refId, ?string $refType, ?string $desc ): bool {
		global $wpdb;
		return $wpdb->insert(
			$wpdb->prefix . self::LOG,
			array(
				'user_id'        => $userId,
				'points_type'    => $type,
				'amount'         => $amount,
				'balance_after'  => $balAfter,
				'trigger_name'   => $trigger,
				'reference_type' => $refType,
				'reference_id'   => $refId,
				'description'    => $desc,
				'admin_id'       => is_admin() ? get_current_user_id() : null,
			)
		) !== false;
	}

	public static function getHistory( int $userId, string $type = 'default', int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::LOG . ' WHERE user_id=%d AND points_type=%s ORDER BY created_at DESC LIMIT %d OFFSET %d',
				$userId,
				$type,
				$limit,
				$offset
			),
			ARRAY_A
		) ?: array();
	}

	public static function getLeaderboard( string $type = 'default', int $limit = 100 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,u.display_name,u.user_email FROM {$wpdb->prefix}" . self::TABLE . " p
			 LEFT JOIN {$wpdb->users} u ON u.ID=p.user_id
			 WHERE p.points_type=%s ORDER BY p.balance DESC LIMIT %d",
				$type,
				$limit
			),
			ARRAY_A
		) ?: array();
	}

	public static function getUserRank( int $userId, string $type = 'default' ): int {
		global $wpdb;
		$bal = self::getBalance( $userId, $type );
		return 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE . ' WHERE points_type=%s AND balance>%d',
				$type,
				$bal
			)
		);
	}
}
