<?php
declare(strict_types=1);
namespace Apollo\Modules\UserRoles;

final class UserRolesRepository {
	private const TABLE = 'apollo_user_roles';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'name'         => sanitize_text_field( $d['name'] ),
				'slug'         => sanitize_title( $d['slug'] ?? $d['name'] ),
				'description'  => sanitize_textarea_field( $d['description'] ?? '' ),
				'color'        => sanitize_hex_color( $d['color'] ?? '#3498db' ) ?: '#3498db',
				'icon'         => sanitize_text_field( $d['icon'] ?? '' ),
				'priority'     => (int) ( $d['priority'] ?? 0 ),
				'capabilities' => isset( $d['capabilities'] ) ? wp_json_encode( $d['capabilities'] ) : null,
				'restrictions' => isset( $d['restrictions'] ) ? wp_json_encode( $d['restrictions'] ) : null,
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['capabilities'] = json_decode( $r['capabilities'] ?? '', true );
			$r['restrictions'] = json_decode( $r['restrictions'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE slug=%s', $slug ), ARRAY_A );
		if ( $r ) {
			$r['capabilities'] = json_decode( $r['capabilities'] ?? '', true );
			$r['restrictions'] = json_decode( $r['restrictions'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getAll(): array {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' ORDER BY priority DESC,name', ARRAY_A ) ?: array();
		foreach ( $rows as &$r ) {
			$r['capabilities'] = json_decode( $r['capabilities'] ?? '', true );
			$r['restrictions'] = json_decode( $r['restrictions'] ?? '', true );
		}
		return $rows;
	}

	public static function assignToUser( int $userId, int $roleId ): bool {
		$roles = (array) get_user_meta( $userId, 'apollo_custom_roles', true );
		if ( ! in_array( $roleId, $roles ) ) {
			$roles[] = $roleId;
			return update_user_meta( $userId, 'apollo_custom_roles', array_values( array_unique( $roles ) ) ) !== false;
		}
		return true;
	}

	public static function removeFromUser( int $userId, int $roleId ): bool {
		$roles = (array) get_user_meta( $userId, 'apollo_custom_roles', true );
		$roles = array_diff( $roles, array( $roleId ) );
		return update_user_meta( $userId, 'apollo_custom_roles', array_values( $roles ) ) !== false;
	}

	public static function getUserRoles( int $userId ): array {
		$roleIds = (array) get_user_meta( $userId, 'apollo_custom_roles', true );
		$roleIds = array_filter( $roleIds );
		if ( empty( $roleIds ) ) {
			return array();
		}
		$roles = array();
		foreach ( $roleIds as $roleId ) {
			$role = self::get( (int) $roleId );
			if ( $role ) {
				$roles[] = $role;
			}
		}
		return $roles;
	}

	public static function getUserCapabilities( int $userId ): array {
		$roles = self::getUserRoles( $userId );
		$caps  = array();
		foreach ( $roles as $role ) {
			if ( ! empty( $role['capabilities'] ) ) {
				$caps = array_merge( $caps, $role['capabilities'] );
			}
		}
		return array_unique( $caps );
	}

	public static function userCan( int $userId, string $capability ): bool {
		$caps = self::getUserCapabilities( $userId );
		return in_array( $capability, $caps );
	}

	public static function getUserRestrictions( int $userId ): array {
		$roles        = self::getUserRoles( $userId );
		$restrictions = array();
		foreach ( $roles as $role ) {
			if ( ! empty( $role['restrictions'] ) ) {
				foreach ( $role['restrictions'] as $key => $value ) {
					$restrictions[ $key ] = $value;
				}
			}
		}
		return $restrictions;
	}

	public static function getUsersByRole( int $roleId, int $limit = 100, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.* FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} um ON um.user_id=u.ID AND um.meta_key='apollo_custom_roles'
			 WHERE um.meta_value LIKE %s ORDER BY u.display_name LIMIT %d OFFSET %d",
				'%i:' . (int) $roleId . '%',
				$limit,
				$offset
			),
			ARRAY_A
		) ?: array();
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'name', 'slug', 'description', 'color', 'icon', 'priority' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['capabilities'] ) ) {
			$u['capabilities'] = wp_json_encode( $d['capabilities'] );
		}
		if ( isset( $d['restrictions'] ) ) {
			$u['restrictions'] = wp_json_encode( $d['restrictions'] );
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}
}
