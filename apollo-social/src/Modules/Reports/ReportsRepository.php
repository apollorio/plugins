<?php
declare(strict_types=1);
namespace Apollo\Modules\Reports;

final class ReportsRepository {
	private const TABLE = 'apollo_reports';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'reporter_id' => (int) $d['reporter_id'],
				'object_type' => sanitize_key( $d['object_type'] ),
				'object_id'   => (int) $d['object_id'],
				'reason'      => sanitize_key( $d['reason'] ),
				'details'     => sanitize_textarea_field( $d['details'] ?? '' ),
				'status'      => 'pending',
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A ) ?: null;
	}

	public static function getPending( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*,u.display_name as reporter_name FROM {$wpdb->prefix}" . self::TABLE . " r
			 LEFT JOIN {$wpdb->users} u ON u.ID=r.reporter_id
			 WHERE r.status='pending' ORDER BY r.created_at LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		) ?: array();
	}

	public static function countPending(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE . " WHERE status='pending'" );
	}

	public static function resolve( int $id, string $action, ?string $notes = null ): bool {
		global $wpdb;
		return $wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'status'          => 'resolved',
				'resolved_by'     => get_current_user_id(),
				'resolved_action' => sanitize_key( $action ),
				'resolved_notes'  => $notes ? sanitize_textarea_field( $notes ) : null,
				'resolved_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $id )
		) !== false;
	}

	public static function dismiss( int $id, ?string $notes = null ): bool {
		global $wpdb;
		return $wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'status'         => 'dismissed',
				'resolved_by'    => get_current_user_id(),
				'resolved_notes' => $notes ? sanitize_textarea_field( $notes ) : null,
				'resolved_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $id )
		) !== false;
	}

	public static function getByObject( string $objectType, int $objectId ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE object_type=%s AND object_id=%d ORDER BY created_at DESC',
				$objectType,
				$objectId
			),
			ARRAY_A
		) ?: array();
	}

	public static function countByObject( string $objectType, int $objectId ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE . ' WHERE object_type=%s AND object_id=%d',
				$objectType,
				$objectId
			)
		);
	}

	public static function hasReported( int $userId, string $objectType, int $objectId ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}" . self::TABLE . ' WHERE reporter_id=%d AND object_type=%s AND object_id=%d',
				$userId,
				$objectType,
				$objectId
			)
		);
	}
}
