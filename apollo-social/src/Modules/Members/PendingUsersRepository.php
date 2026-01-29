<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class PendingUsersRepository {
	private const TABLE = 'apollo_pending_users';

	public static function addToPending( int $userId, string $reason = 'manual_approval' ): bool {
		global $wpdb;
		if ( self::isPending( $userId ) ) {
			return true;}
		$t      = $wpdb->prefix . self::TABLE;
		$result = $wpdb->insert(
			$t,
			array(
				'user_id'    => $userId,
				'reason'     => $reason,
				'status'     => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
		if ( $result ) {
			update_user_meta( $userId, 'apollo_pending', 1 );
			update_user_meta( $userId, 'apollo_pending_reason', $reason );
		}
		return $result !== false;
	}

	public static function approve( int $userId, int $approvedBy, string $notes = '' ): bool {
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$result = $wpdb->update(
			$t,
			array(
				'status'      => 'approved',
				'reviewed_by' => $approvedBy,
				'reviewed_at' => current_time( 'mysql' ),
				'notes'       => $notes,
			),
			array(
				'user_id' => $userId,
				'status'  => 'pending',
			),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $result !== false ) {
			delete_user_meta( $userId, 'apollo_pending' );
			delete_user_meta( $userId, 'apollo_pending_reason' );
			$user = get_userdata( $userId );
			if ( $user ) {
				$user->set_role( get_option( 'default_role', 'subscriber' ) );}
			do_action( 'apollo_user_approved', $userId, $approvedBy );
		}
		return $result !== false;
	}

	public static function reject( int $userId, int $rejectedBy, string $reason = '' ): bool {
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$result = $wpdb->update(
			$t,
			array(
				'status'      => 'rejected',
				'reviewed_by' => $rejectedBy,
				'reviewed_at' => current_time( 'mysql' ),
				'notes'       => $reason,
			),
			array(
				'user_id' => $userId,
				'status'  => 'pending',
			),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $result !== false ) {
			update_user_meta( $userId, 'apollo_rejected', 1 );
			update_user_meta( $userId, 'apollo_rejected_reason', $reason );
			do_action( 'apollo_user_rejected', $userId, $rejectedBy, $reason );
		}
		return $result !== false;
	}

	public static function isPending( int $userId ): bool {
		return (bool) get_user_meta( $userId, 'apollo_pending', true );
	}

	public static function isRejected( int $userId ): bool {
		return (bool) get_user_meta( $userId, 'apollo_rejected', true );
	}

	public static function getPending( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,u.display_name,u.user_email,u.user_registered
			FROM {$t} p
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			WHERE p.status='pending'
			ORDER BY p.created_at ASC
			LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getPendingCount(): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='pending'" );
	}

	public static function getHistory( int $limit = 100 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,u.display_name,u.user_email,reviewer.display_name as reviewer_name
			FROM {$t} p
			JOIN {$wpdb->users} u ON p.user_id=u.ID
			LEFT JOIN {$wpdb->users} reviewer ON p.reviewed_by=reviewer.ID
			WHERE p.status IN('approved','rejected')
			ORDER BY p.reviewed_at DESC
			LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUserStatus( int $userId ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT p.*,reviewer.display_name as reviewer_name
			FROM {$t} p
			LEFT JOIN {$wpdb->users} reviewer ON p.reviewed_by=reviewer.ID
			WHERE p.user_id=%d
			ORDER BY p.created_at DESC LIMIT 1",
				$userId
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function bulkApprove( array $userIds, int $approvedBy ): int {
		$count = 0;
		foreach ( $userIds as $userId ) {
			if ( self::approve( (int) $userId, $approvedBy ) ) {
				++$count;}
		}
		return $count;
	}

	public static function bulkReject( array $userIds, int $rejectedBy, string $reason = '' ): int {
		$count = 0;
		foreach ( $userIds as $userId ) {
			if ( self::reject( (int) $userId, $rejectedBy, $reason ) ) {
				++$count;}
		}
		return $count;
	}

	public static function getStats(): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return array(
			'pending'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='pending'" ),
			'approved'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='approved'" ),
			'rejected'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='rejected'" ),
			'today_pending'  => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$t} WHERE status='pending' AND DATE(created_at)=%s",
					gmdate( 'Y-m-d' )
				)
			),
			'avg_wait_hours' => (float) $wpdb->get_var(
				"SELECT AVG(TIMESTAMPDIFF(HOUR,created_at,reviewed_at)) FROM {$t} WHERE status='approved'"
			),
		);
	}

	public static function cleanOld( int $days = 90 ): int {
		global $wpdb;
		$t         = $wpdb->prefix . self::TABLE;
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$t} WHERE status IN('approved','rejected') AND reviewed_at<%s",
				$threshold
			)
		);
	}

	public static function requiresApproval(): bool {
		return (bool) get_option( 'apollo_require_user_approval', false );
	}

	public static function getApprovalReason( int $userId ): string {
		return get_user_meta( $userId, 'apollo_pending_reason', true ) ?: '';
	}
}
