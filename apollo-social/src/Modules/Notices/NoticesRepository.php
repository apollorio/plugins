<?php
declare(strict_types=1);
namespace Apollo\Modules\Notices;

final class NoticesRepository {
	private const TABLE      = 'apollo_notices';
	private const USER_TABLE = 'apollo_user_notices';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'title'              => sanitize_text_field( $d['title'] ),
				'content'            => wp_kses_post( $d['content'] ),
				'type'               => in_array( $d['type'] ?? '', array( 'info', 'warning', 'success', 'error', 'promo' ) ) ? $d['type'] : 'info',
				'priority'           => (int) ( $d['priority'] ?? 5 ),
				'target_roles'       => isset( $d['target_roles'] ) ? wp_json_encode( $d['target_roles'] ) : null,
				'target_memberships' => isset( $d['target_memberships'] ) ? wp_json_encode( $d['target_memberships'] ) : null,
				'target_conditions'  => isset( $d['target_conditions'] ) ? wp_json_encode( $d['target_conditions'] ) : null,
				'is_dismissible'     => (int) ( $d['is_dismissible'] ?? 1 ),
				'is_active'          => (int) ( $d['is_active'] ?? 1 ),
				'start_at'           => $d['start_at'] ?? null,
				'end_at'             => $d['end_at'] ?? null,
				'created_by'         => get_current_user_id(),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['target_roles']       = json_decode( $r['target_roles'] ?? '', true );
			$r['target_memberships'] = json_decode( $r['target_memberships'] ?? '', true );
			$r['target_conditions']  = json_decode( $r['target_conditions'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getActiveForUser( int $userId ): array {
		global $wpdb;
		$now       = current_time( 'mysql' );
		$notices   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT n.* FROM {$wpdb->prefix}" . self::TABLE . " n
			 LEFT JOIN {$wpdb->prefix}" . self::USER_TABLE . ' un ON un.notice_id=n.id AND un.user_id=%d
			 WHERE n.is_active=1 AND un.notice_id IS NULL
			   AND (n.start_at IS NULL OR n.start_at<=%s) AND (n.end_at IS NULL OR n.end_at>=%s)
			 ORDER BY n.priority DESC,n.created_at DESC',
				$userId,
				$now,
				$now
			),
			ARRAY_A
		) ?: array();
		$user      = $userId ? get_userdata( $userId ) : null;
		$userRoles = $user ? $user->roles : array();
		$filtered  = array();
		foreach ( $notices as $n ) {
			$n['target_roles']       = json_decode( $n['target_roles'] ?? '', true );
			$n['target_memberships'] = json_decode( $n['target_memberships'] ?? '', true );
			$n['target_conditions']  = json_decode( $n['target_conditions'] ?? '', true );
			if ( ! empty( $n['target_roles'] ) && ! array_intersect( $userRoles, $n['target_roles'] ) ) {
				continue;
			}
			$filtered[] = $n;
		}
		return $filtered;
	}

	public static function dismiss( int $userId, int $noticeId ): bool {
		global $wpdb;
		return $wpdb->replace(
			$wpdb->prefix . self::USER_TABLE,
			array(
				'user_id'   => $userId,
				'notice_id' => $noticeId,
			)
		) !== false;
	}

	public static function isDismissed( int $userId, int $noticeId ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}" . self::USER_TABLE . ' WHERE user_id=%d AND notice_id=%d',
				$userId,
				$noticeId
			)
		);
	}

	public static function getAll( bool $activeOnly = true ): array {
		global $wpdb;
		$w    = $activeOnly ? 'WHERE is_active=1' : '';
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " {$w} ORDER BY priority DESC,created_at DESC", ARRAY_A ) ?: array();
		foreach ( $rows as &$r ) {
			$r['target_roles']       = json_decode( $r['target_roles'] ?? '', true );
			$r['target_memberships'] = json_decode( $r['target_memberships'] ?? '', true );
			$r['target_conditions']  = json_decode( $r['target_conditions'] ?? '', true );
		}
		return $rows;
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'title', 'content', 'type', 'priority', 'is_dismissible', 'is_active', 'start_at', 'end_at' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['target_roles'] ) ) {
			$u['target_roles'] = wp_json_encode( $d['target_roles'] );
		}
		if ( isset( $d['target_memberships'] ) ) {
			$u['target_memberships'] = wp_json_encode( $d['target_memberships'] );
		}
		if ( isset( $d['target_conditions'] ) ) {
			$u['target_conditions'] = wp_json_encode( $d['target_conditions'] );
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::USER_TABLE, array( 'notice_id' => $id ) );
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}

	public static function resetDismissals( int $noticeId ): bool {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . self::USER_TABLE, array( 'notice_id' => $noticeId ) ) !== false;
	}
}
