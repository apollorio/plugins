<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class MemberTypesRepository {
	private const TABLE = 'apollo_member_types';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'name'          => sanitize_text_field( $d['name'] ),
				'slug'          => sanitize_title( $d['slug'] ?? $d['name'] ),
				'description'   => sanitize_textarea_field( $d['description'] ?? '' ),
				'icon'          => sanitize_text_field( $d['icon'] ?? '' ),
				'color'         => sanitize_hex_color( $d['color'] ?? '#3498db' ) ?: '#3498db',
				'badge_image'   => isset( $d['badge_image'] ) ? esc_url_raw( $d['badge_image'] ) : null,
				'is_selectable' => (int) ( $d['is_selectable'] ?? 1 ),
				'sort_order'    => (int) ( $d['sort_order'] ?? 0 ),
				'settings'      => isset( $d['settings'] ) ? wp_json_encode( $d['settings'] ) : null,
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['settings'] = json_decode( $r['settings'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE slug=%s', $slug ), ARRAY_A );
		if ( $r ) {
			$r['settings'] = json_decode( $r['settings'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getAll( bool $selectableOnly = false ): array {
		global $wpdb;
		$w    = $selectableOnly ? 'WHERE is_selectable=1' : '';
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " {$w} ORDER BY sort_order,name", ARRAY_A ) ?: array();
		foreach ( $rows as &$r ) {
			$r['settings'] = json_decode( $r['settings'] ?? '', true );}
		return $rows;
	}

	public static function assignToUser( int $userId, int $typeId ): bool {
		return update_user_meta( $userId, 'apollo_member_type', $typeId ) !== false;
	}

	public static function removeFromUser( int $userId ): bool {
		return delete_user_meta( $userId, 'apollo_member_type' );
	}

	public static function getUserType( int $userId ): ?array {
		$typeId = (int) get_user_meta( $userId, 'apollo_member_type', true );
		return $typeId ? self::get( $typeId ) : null;
	}

	public static function getUsersByType( int $typeId, int $limit = 100, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.* FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} um ON um.user_id=u.ID AND um.meta_key='apollo_member_type' AND um.meta_value=%s
			 ORDER BY u.display_name LIMIT %d OFFSET %d",
				(string) $typeId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?: array();
	}

	public static function countByType( int $typeId ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key='apollo_member_type' AND meta_value=%s",
				(string) $typeId
			)
		);
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'name', 'slug', 'description', 'icon', 'color', 'badge_image', 'is_selectable', 'sort_order' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['settings'] ) ) {
			$u['settings'] = wp_json_encode( $d['settings'] );
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key'   => 'apollo_member_type',
				'meta_value' => (string) $id,
			)
		);
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}
}
