<?php
declare(strict_types=1);
namespace Apollo\Modules\Tags;

final class TagsRepository {
	private const TABLE     = 'apollo_user_tags';
	private const REL_TABLE = 'apollo_user_tag_relations';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'name'        => sanitize_text_field( $d['name'] ),
				'slug'        => sanitize_title( $d['slug'] ?? $d['name'] ),
				'color'       => sanitize_hex_color( $d['color'] ?? '#6b7280' ),
				'icon'        => sanitize_text_field( $d['icon'] ?? '' ),
				'description' => sanitize_textarea_field( $d['description'] ?? '' ),
				'is_system'   => (int) ( $d['is_system'] ?? 0 ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		return $r ?: null;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE slug=%s', $slug ), ARRAY_A );
		return $r ?: null;
	}

	public static function getAll( bool $includeSystem = true ): array {
		global $wpdb;
		$w = $includeSystem ? '' : 'WHERE is_system=0';
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " {$w} ORDER BY name", ARRAY_A ) ?: array();
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		if ( isset( $d['name'] ) ) {
			$u['name'] = sanitize_text_field( $d['name'] );
		}
		if ( isset( $d['slug'] ) ) {
			$u['slug'] = sanitize_title( $d['slug'] );
		}
		if ( isset( $d['color'] ) ) {
			$u['color'] = sanitize_hex_color( $d['color'] );
		}
		if ( isset( $d['icon'] ) ) {
			$u['icon'] = sanitize_text_field( $d['icon'] );
		}
		if ( isset( $d['description'] ) ) {
			$u['description'] = sanitize_textarea_field( $d['description'] );
		}
		if ( empty( $u ) ) {
			return false;
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::REL_TABLE, array( 'tag_id' => $id ) );
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}

	public static function assignToUser( int $userId, int $tagId, ?int $assignedBy = null ): bool {
		global $wpdb;
		$r = $wpdb->replace(
			$wpdb->prefix . self::REL_TABLE,
			array(
				'user_id'     => $userId,
				'tag_id'      => $tagId,
				'assigned_by' => $assignedBy ?: get_current_user_id(),
			)
		);
		return $r !== false;
	}

	public static function removeFromUser( int $userId, int $tagId ): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . self::REL_TABLE,
			array(
				'user_id' => $userId,
				'tag_id'  => $tagId,
			)
		) !== false;
	}

	public static function getUserTags( int $userId ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM {$wpdb->prefix}" . self::TABLE . " t
			 INNER JOIN {$wpdb->prefix}" . self::REL_TABLE . ' r ON t.id=r.tag_id
			 WHERE r.user_id=%d ORDER BY t.name',
				$userId
			),
			ARRAY_A
		) ?: array();
	}

	public static function getUsersByTag( int $tagId, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}" . self::REL_TABLE . ' WHERE tag_id=%d LIMIT %d OFFSET %d',
				$tagId,
				$limit,
				$offset
			)
		) ?: array();
	}

	public static function countUsersByTag( int $tagId ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::REL_TABLE . ' WHERE tag_id=%d',
				$tagId
			)
		);
	}
}
