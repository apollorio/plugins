<?php
declare(strict_types=1);
namespace Apollo\Modules\Team;

final class TeamMembersRepository {
	private const TABLE = 'apollo_team_members';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'user_id'    => isset( $d['user_id'] ) ? (int) $d['user_id'] : null,
				'name'       => sanitize_text_field( $d['name'] ),
				'slug'       => sanitize_title( $d['slug'] ?? $d['name'] ),
				'position'   => sanitize_text_field( $d['position'] ?? '' ),
				'department' => sanitize_text_field( $d['department'] ?? '' ),
				'bio'        => wp_kses_post( $d['bio'] ?? '' ),
				'photo_url'  => isset( $d['photo_url'] ) ? esc_url_raw( $d['photo_url'] ) : null,
				'email'      => isset( $d['email'] ) ? sanitize_email( $d['email'] ) : '',
				'phone'      => sanitize_text_field( $d['phone'] ?? '' ),
				'socials'    => isset( $d['socials'] ) ? wp_json_encode( $d['socials'] ) : null,
				'sort_order' => (int) ( $d['sort_order'] ?? 0 ),
				'is_active'  => (int) ( $d['is_active'] ?? 1 ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['socials'] = json_decode( $r['socials'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE slug=%s', $slug ), ARRAY_A );
		if ( $r ) {
			$r['socials'] = json_decode( $r['socials'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getAll( bool $activeOnly = true ): array {
		global $wpdb;
		$w    = $activeOnly ? 'WHERE is_active=1' : '';
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " {$w} ORDER BY sort_order,name", ARRAY_A ) ?: array();
		foreach ( $rows as &$r ) {
			$r['socials'] = json_decode( $r['socials'] ?? '', true );}
		return $rows;
	}

	public static function getByDepartment( string $department, bool $activeOnly = true ): array {
		global $wpdb;
		$w    = $activeOnly ? 'AND is_active=1' : '';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . " WHERE department=%s {$w} ORDER BY sort_order,name",
				$department
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as &$r ) {
			$r['socials'] = json_decode( $r['socials'] ?? '', true );}
		return $rows;
	}

	public static function getDepartments(): array {
		global $wpdb;
		return $wpdb->get_col( "SELECT DISTINCT department FROM {$wpdb->prefix}" . self::TABLE . " WHERE department!='' AND is_active=1 ORDER BY department" ) ?: array();
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'user_id', 'name', 'slug', 'position', 'department', 'bio', 'photo_url', 'email', 'phone', 'sort_order', 'is_active' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['socials'] ) ) {
			$u['socials'] = wp_json_encode( $d['socials'] );
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}

	public static function reorder( array $ids ): bool {
		global $wpdb;
		foreach ( $ids as $order => $id ) {
			$wpdb->update( $wpdb->prefix . self::TABLE, array( 'sort_order' => $order ), array( 'id' => (int) $id ) );
		}
		return true;
	}
}
