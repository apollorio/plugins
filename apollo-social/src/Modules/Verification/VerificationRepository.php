<?php
declare(strict_types=1);
namespace Apollo\Modules\Verification;

final class VerificationRepository {
	private const TABLE = 'apollo_verifications';

	public static function request( int $userId, string $type, array $documents = array() ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( self::hasPendingRequest( $userId, $type ) ) {
			return 0;}
		$wpdb->insert(
			$t,
			array(
				'user_id'    => $userId,
				'type'       => $type,
				'documents'  => wp_json_encode( $documents ),
				'status'     => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
		$id = (int) $wpdb->insert_id;
		if ( $id ) {
			do_action( 'apollo_verification_requested', $userId, $type, $id );}
		return $id;
	}

	public static function approve( int $requestId, int $approvedBy, string $notes = '' ): bool {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $requestId ), ARRAY_A );
		if ( ! $req || $req['status'] !== 'pending' ) {
			return false;}
		$result = $wpdb->update(
			$t,
			array(
				'status'      => 'approved',
				'reviewed_by' => $approvedBy,
				'reviewed_at' => current_time( 'mysql' ),
				'notes'       => $notes,
			),
			array( 'id' => $requestId ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		if ( $result !== false ) {
			update_user_meta( (int) $req['user_id'], 'apollo_verified_' . $req['type'], 1 );
			update_user_meta( (int) $req['user_id'], 'apollo_verified_at', current_time( 'mysql' ) );
			self::assignVerificationBadge( (int) $req['user_id'], $req['type'] );
			do_action( 'apollo_user_verified', (int) $req['user_id'], $req['type'] );
		}
		return $result !== false;
	}

	public static function reject( int $requestId, int $rejectedBy, string $reason = '' ): bool {
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
				'id'     => $requestId,
				'status' => 'pending',
			),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $result !== false ) {
			$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $requestId ), ARRAY_A );
			if ( $req ) {
				do_action( 'apollo_verification_rejected', (int) $req['user_id'], $req['type'], $reason );}
		}
		return $result !== false;
	}

	public static function revoke( int $userId, string $type, int $revokedBy, string $reason = '' ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		$wpdb->insert(
			$t,
			array(
				'user_id'     => $userId,
				'type'        => $type,
				'status'      => 'revoked',
				'reviewed_by' => $revokedBy,
				'reviewed_at' => current_time( 'mysql' ),
				'notes'       => $reason,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
		delete_user_meta( $userId, 'apollo_verified_' . $type );
		self::removeVerificationBadge( $userId, $type );
		do_action( 'apollo_verification_revoked', $userId, $type, $reason );
		return true;
	}

	public static function isVerified( int $userId, string $type = 'identity' ): bool {
		return (bool) get_user_meta( $userId, 'apollo_verified_' . $type, true );
	}

	public static function getVerificationTypes( int $userId ): array {
		$types    = array( 'identity', 'email', 'phone', 'business', 'creator' );
		$verified = array();
		foreach ( $types as $type ) {
			if ( self::isVerified( $userId, $type ) ) {
				$verified[] = $type;}
		}
		return $verified;
	}

	public static function hasPendingRequest( int $userId, string $type ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND type=%s AND status='pending'", $userId, $type ) );
	}

	public static function getPendingRequests( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.*,u.display_name,u.user_email FROM {$t} v JOIN {$wpdb->users} u ON v.user_id=u.ID WHERE v.status='pending' ORDER BY v.created_at ASC LIMIT %d OFFSET %d",
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

	public static function getUserHistory( int $userId ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.*,reviewer.display_name as reviewer_name FROM {$t} v LEFT JOIN {$wpdb->users} reviewer ON v.reviewed_by=reviewer.ID WHERE v.user_id=%d ORDER BY v.created_at DESC",
				$userId
			),
			ARRAY_A
		) ?? array();
	}

	public static function getVerifiedUsers( string $type = 'identity', int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID,u.display_name,um.meta_value as verified_at FROM {$wpdb->users} u JOIN {$wpdb->usermeta} um ON u.ID=um.user_id WHERE um.meta_key=%s AND um.meta_value=1 LIMIT %d OFFSET %d",
				'apollo_verified_' . $type,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getStats(): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return array(
			'pending'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='pending'" ),
			'approved' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='approved'" ),
			'rejected' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='rejected'" ),
			'revoked'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='revoked'" ),
			'by_type'  => $wpdb->get_results( "SELECT type,status,COUNT(*) as cnt FROM {$t} GROUP BY type,status", ARRAY_A ),
		);
	}

	public static function getVerificationBadge( string $type ): ?int {
		$badges = array(
			'identity' => 1,
			'email'    => 2,
			'phone'    => 3,
			'business' => 4,
			'creator'  => 5,
		);
		return $badges[ $type ] ?? null;
	}

	private static function assignVerificationBadge( int $userId, string $type ): void {
		$badgeId = self::getVerificationBadge( $type );
		if ( ! $badgeId ) {
			return;}
		global $wpdb;
		$t      = $wpdb->prefix . 'apollo_user_badges';
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND badge_id=%d", $userId, $badgeId ) );
		if ( ! $exists ) {
			$wpdb->insert(
				$t,
				array(
					'user_id'    => $userId,
					'badge_id'   => $badgeId,
					'awarded_at' => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
		}
	}

	private static function removeVerificationBadge( int $userId, string $type ): void {
		$badgeId = self::getVerificationBadge( $type );
		if ( ! $badgeId ) {
			return;}
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_user_badges';
		$wpdb->delete(
			$t,
			array(
				'user_id'  => $userId,
				'badge_id' => $badgeId,
			),
			array( '%d', '%d' )
		);
	}
}
