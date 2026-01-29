<?php
declare(strict_types=1);
namespace Apollo\Modules\Groups;

final class GroupsRepository {
	private const TABLE   = 'apollo_groups';
	private const MEMBERS = 'apollo_group_members';
	private const META    = 'apollo_group_meta';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'name'         => sanitize_text_field( $d['name'] ),
				'slug'         => sanitize_title( $d['slug'] ?? $d['name'] ),
				'description'  => sanitize_textarea_field( $d['description'] ?? '' ),
				'type_id'      => isset( $d['type_id'] ) ? (int) $d['type_id'] : null,
				'status'       => in_array( $d['status'] ?? '', array( 'public', 'private', 'hidden' ) ) ? $d['status'] : 'public',
				'creator_id'   => (int) ( $d['creator_id'] ?? get_current_user_id() ),
				'parent_id'    => isset( $d['parent_id'] ) ? (int) $d['parent_id'] : null,
				'enable_forum' => (int) ( $d['enable_forum'] ?? 0 ),
			)
		);
		$groupId = (int) $wpdb->insert_id;
		if ( $groupId && ! empty( $d['creator_id'] ) ) {
			self::addMember( $groupId, (int) $d['creator_id'], 'admin' );
		}
		return $groupId;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['member_count'] = self::countMembers( $id );
			$r['avatar_url']   = self::getMeta( $id, 'avatar_url' );
			$r['cover_url']    = self::getMeta( $id, 'cover_url' );
		}
		return $r ?: null;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE slug=%s', $slug ), ARRAY_A );
		if ( $r ) {
			$r['member_count'] = self::countMembers( (int) $r['id'] );}
		return $r ?: null;
	}

	public static function search( array $args = array() ): array {
		global $wpdb;
		$defaults = array(
			'search'  => '',
			'status'  => 'public',
			'type_id' => null,
			'orderby' => 'name',
			'order'   => 'ASC',
			'limit'   => 20,
			'offset'  => 0,
		);
		$a        = array_merge( $defaults, $args );
		$where    = array( '1=1' );
		$params   = array();
		if ( $a['search'] ) {
			$where[] = '(name LIKE %s OR description LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $a['search'] ) . '%';
			$params  = array_merge( $params, array( $like, $like ) );
		}
		if ( $a['status'] ) {
			$where[]  = 'status=%s';
			$params[] = $a['status'];}
		if ( $a['type_id'] ) {
			$where[]  = 'type_id=%d';
			$params[] = (int) $a['type_id'];}
		$w        = \implode( ' AND ', $where );
		$ob       = in_array( $a['orderby'], array( 'name', 'created_at', 'id' ) ) ? $a['orderby'] : 'name';
		$o        = \strtoupper( $a['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$params[] = $a['limit'];
		$params[] = $a['offset'];
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . " WHERE {$w} ORDER BY {$ob} {$o} LIMIT %d OFFSET %d",
				...$params
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as &$r ) {
			$r['member_count'] = self::countMembers( (int) $r['id'] );}
		return $rows;
	}

	public static function getUserGroups( int $userId ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.*,m.role FROM {$wpdb->prefix}" . self::TABLE . " g
			 INNER JOIN {$wpdb->prefix}" . self::MEMBERS . " m ON m.group_id=g.id
			 WHERE m.user_id=%d AND m.status='active' ORDER BY g.name",
				$userId
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as &$r ) {
			$r['member_count'] = self::countMembers( (int) $r['id'] );}
		return $rows;
	}

	public static function addMember( int $groupId, int $userId, string $role = 'member' ): bool {
		global $wpdb;
		$validRoles = array( 'admin', 'moderator', 'member' );
		if ( ! in_array( $role, $validRoles ) ) {
			$role = 'member';
		}
		return $wpdb->replace(
			$wpdb->prefix . self::MEMBERS,
			array(
				'group_id' => $groupId,
				'user_id'  => $userId,
				'role'     => $role,
				'status'   => 'active',
			)
		) !== false;
	}

	public static function removeMember( int $groupId, int $userId ): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . self::MEMBERS,
			array(
				'group_id' => $groupId,
				'user_id'  => $userId,
			)
		) !== false;
	}

	public static function updateMemberRole( int $groupId, int $userId, string $role ): bool {
		global $wpdb;
		$validRoles = array( 'admin', 'moderator', 'member' );
		if ( ! in_array( $role, $validRoles ) ) {
			return false;
		}
		return $wpdb->update(
			$wpdb->prefix . self::MEMBERS,
			array( 'role' => $role ),
			array(
				'group_id' => $groupId,
				'user_id'  => $userId,
			)
		) !== false;
	}

	public static function isMember( int $groupId, int $userId ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}" . self::MEMBERS . " WHERE group_id=%d AND user_id=%d AND status='active'",
				$groupId,
				$userId
			)
		);
	}

	public static function getMemberRole( int $groupId, int $userId ): ?string {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT role FROM {$wpdb->prefix}" . self::MEMBERS . " WHERE group_id=%d AND user_id=%d AND status='active'",
				$groupId,
				$userId
			)
		);
	}

	public static function isAdmin( int $groupId, int $userId ): bool {
		return self::getMemberRole( $groupId, $userId ) === 'admin';
	}

	public static function isModerator( int $groupId, int $userId ): bool {
		$role = self::getMemberRole( $groupId, $userId );
		return in_array( $role, array( 'admin', 'moderator' ) );
	}

	public static function getMembers( int $groupId, ?string $role = null, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$roleWhere = $role ? 'AND m.role=%s' : '';
		$params    = array( $groupId );
		if ( $role ) {
			$params[] = $role;
		}
		$params[] = $limit;
		$params[] = $offset;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.*,u.display_name,u.user_email FROM {$wpdb->prefix}" . self::MEMBERS . " m
			 LEFT JOIN {$wpdb->users} u ON u.ID=m.user_id
			 WHERE m.group_id=%d AND m.status='active' {$roleWhere} ORDER BY m.role,u.display_name LIMIT %d OFFSET %d",
				...$params
			),
			ARRAY_A
		) ?: array();
	}

	public static function countMembers( int $groupId ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::MEMBERS . " WHERE group_id=%d AND status='active'",
				$groupId
			)
		);
	}

	public static function canAccess( int $groupId, int $userId ): bool {
		$group = self::get( $groupId );
		if ( ! $group ) {
			return false;
		}
		if ( $group['status'] === 'public' ) {
			return true;
		}
		if ( $group['status'] === 'hidden' || $group['status'] === 'private' ) {
			return self::isMember( $groupId, $userId );
		}
		return false;
	}

	public static function setMeta( int $groupId, string $key, mixed $value ): bool {
		global $wpdb;
		return $wpdb->replace(
			$wpdb->prefix . self::META,
			array(
				'group_id'   => $groupId,
				'meta_key'   => sanitize_key( $key ),
				'meta_value' => maybe_serialize( $value ),
			)
		) !== false;
	}

	public static function getMeta( int $groupId, string $key, mixed $default = null ): mixed {
		global $wpdb;
		$val = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}" . self::META . ' WHERE group_id=%d AND meta_key=%s',
				$groupId,
				$key
			)
		);
		return $val !== null ? maybe_unserialize( $val ) : $default;
	}

	public static function deleteMeta( int $groupId, string $key ): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . self::META,
			array(
				'group_id' => $groupId,
				'meta_key' => $key,
			)
		) !== false;
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'name', 'slug', 'description', 'type_id', 'status', 'parent_id', 'enable_forum' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::MEMBERS, array( 'group_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . self::META, array( 'group_id' => $id ) );
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}
}
