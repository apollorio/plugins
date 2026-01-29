<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;

final class RanksRepository {
	private const TABLE = 'apollo_ranks';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'name'           => sanitize_text_field( $d['name'] ),
				'slug'           => sanitize_title( $d['slug'] ?? $d['name'] ),
				'points_type'    => sanitize_key( $d['points_type'] ?? 'default' ),
				'min_points'     => (int) ( $d['min_points'] ?? 0 ),
				'max_points'     => isset( $d['max_points'] ) ? (int) $d['max_points'] : null,
				'icon'           => sanitize_text_field( $d['icon'] ?? '' ),
				'color'          => sanitize_hex_color( $d['color'] ?? '#6b7280' ),
				'badge_image_id' => isset( $d['badge_image_id'] ) ? (int) $d['badge_image_id'] : null,
				'perks'          => isset( $d['perks'] ) ? wp_json_encode( $d['perks'] ) : null,
				'sort_order'     => (int) ( $d['sort_order'] ?? 0 ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['perks'] = json_decode( $r['perks'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getAll( string $type = 'default' ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE points_type=%s ORDER BY min_points ASC',
				$type
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as &$r ) {
			$r['perks'] = json_decode( $r['perks'] ?? '', true );
		}
		return $rows;
	}

	public static function getUserRank( int $userId, string $type = 'default' ): ?array {
		$points = PointsRepository::getBalance( $userId, $type );
		global $wpdb;
		$r = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE points_type=%s AND min_points<=%d ORDER BY min_points DESC LIMIT 1',
				$type,
				$points
			),
			ARRAY_A
		);
		if ( $r ) {
			$r['perks'] = json_decode( $r['perks'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getNextRank( int $userId, string $type = 'default' ): ?array {
		$current = self::getUserRank( $userId, $type );
		if ( ! $current ) {
			return null;
		}
		global $wpdb;
		$r = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE points_type=%s AND min_points>%d ORDER BY min_points ASC LIMIT 1',
				$type,
				(int) $current['min_points']
			),
			ARRAY_A
		);
		if ( $r ) {
			$r['perks'] = json_decode( $r['perks'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getProgressToNextRank( int $userId, string $type = 'default' ): array {
		$points  = PointsRepository::getBalance( $userId, $type );
		$current = self::getUserRank( $userId, $type );
		$next    = self::getNextRank( $userId, $type );
		if ( ! $next ) {
			return array(
				'current'       => $current,
				'next'          => null,
				'progress'      => 100,
				'points_needed' => 0,
			);
		}
		$needed = (int) $next['min_points'] - $points;
		$range  = (int) $next['min_points'] - (int) ( $current['min_points'] ?? 0 );
		$prog   = $range > 0 ? round( ( ( $points - ( $current['min_points'] ?? 0 ) ) / $range ) * 100 ) : 0;
		return array(
			'current'       => $current,
			'next'          => $next,
			'progress'      => min( 100, max( 0, $prog ) ),
			'points_needed' => max( 0, $needed ),
		);
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'name', 'slug', 'points_type', 'min_points', 'max_points', 'icon', 'color', 'badge_image_id', 'sort_order' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['perks'] ) ) {
			$u['perks'] = wp_json_encode( $d['perks'] );
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}
}
