<?php

namespace Apollo\Domain\Groups\Repositories;

use Apollo\Domain\Entities\GroupEntity;

/**
 * Groups Repository
 * Data access layer for Apollo Groups
 */
class GroupsRepository {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'apollo_groups';
	}

	/**
	 * Get all groups with filters
	 */
	public function findAll( array $filters = array() ): array {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		// Filter by type
		if ( ! empty( $filters['type'] ) ) {
			$where[]  = 'type = %s';
			$params[] = $filters['type'];
		}

		// Filter by status
		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $filters['status'];
		}

		// Filter by season_slug
		if ( ! empty( $filters['season_slug'] ) ) {
			$where[]  = 'season_slug = %s';
			$params[] = $filters['season_slug'];
		}

		// Search
		if ( ! empty( $filters['search'] ) ) {
			$where[]     = '(title LIKE %s OR description LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$params[]    = $search_term;
			$params[]    = $search_term;
		}

		// Filter by creator
		if ( ! empty( $filters['creator_id'] ) ) {
			$where[]  = 'creator_id = %d';
			$params[] = (int) $filters['creator_id'];
		}

		// Visibility filter (public groups only for non-admins)
		if ( empty( $filters['include_private'] ) && ! current_user_can( 'manage_options' ) ) {
			$where[]  = 'visibility = %s';
			$params[] = 'public';
		}

		$where_clause = implode( ' AND ', $where );

		$allowed_order_columns = array( 'created_at', 'updated_at', 'title', 'status' );
		$order_column          = $filters['order_by'] ?? 'created_at';
		$order_direction       = strtoupper( $filters['order_dir'] ?? 'DESC' );

		if ( ! in_array( $order_column, $allowed_order_columns, true ) ) {
			$order_column = 'created_at';
		}

		if ( ! in_array( $order_direction, array( 'ASC', 'DESC' ), true ) ) {
			$order_direction = 'DESC';
		}

		$order_clause = sprintf( '%s %s', $order_column, $order_direction );

		$limit  = isset( $filters['limit'] ) ? max( 1, min( (int) $filters['limit'], 500 ) ) : 100;
		$offset = isset( $filters['offset'] ) ? max( 0, (int) $filters['offset'] ) : 0;

		$sql      = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_clause} LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		$prepared = $wpdb->prepare( $sql, $params );
		$results  = $wpdb->get_results( $prepared, ARRAY_A );

		$groups = array();
		foreach ( $results as $row ) {
			$groups[] = $this->mapToEntity( $row );
		}

		return $groups;
	}

	/**
	 * Get group by ID
	 */
	public function findById( int $id ): ?GroupEntity {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->mapToEntity( $row );
	}

	/**
	 * Get group by slug
	 */
	public function findBySlug( string $slug ): ?GroupEntity {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE slug = %s",
				$slug
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->mapToEntity( $row );
	}

	/**
	 * Create new group
	 */
	public function create( array $data ): int {
		global $wpdb;

		$defaults = array(
			'title'        => '',
			'slug'         => '',
			'description'  => '',
			'type'         => 'comunidade',
			'status'       => 'draft',
			'visibility'   => 'public',
			'season_slug'  => null,
			'creator_id'   => get_current_user_id(),
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
			'published_at' => null,
		);

		$data = array_merge( $defaults, $data );

		// Generate slug if not provided
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = $this->generateSlug( $data['title'] );
		}

		// Ensure slug is unique
		$data['slug'] = $this->ensureUniqueSlug( $data['slug'] );

		$result = $wpdb->insert( $this->table_name, $data );

		if ( $result === false ) {
			error_log( 'GroupsRepository::create failed: ' . $wpdb->last_error );
			return 0;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update group
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		// Update published_at if status changed to published
		if ( isset( $data['status'] ) && $data['status'] === 'published' && empty( $data['published_at'] ) ) {
			$existing = $this->findById( $id );
			if ( $existing && $existing->getStatus() !== 'published' ) {
				$data['published_at'] = current_time( 'mysql' );
			}
		}

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id ),
			null,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete group
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get groups count
	 */
	public function count( array $filters = array() ): int {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $filters['type'] ) ) {
			$where[]  = 'type = %s';
			$params[] = $filters['type'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $filters['status'];
		}

		if ( ! empty( $filters['creator_id'] ) ) {
			$where[]  = 'creator_id = %d';
			$params[] = (int) $filters['creator_id'];
		}

		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
				$params
			);
		} else {
			$sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Get members count for a group
	 */
	public function getMembersCount( int $group_id ): int {
		global $wpdb;

		$members_table = $wpdb->prefix . 'apollo_group_members';

		// Check if members table exists
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$members_table
			)
		);

		if ( ! $table_exists ) {
			return 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$members_table} WHERE group_id = %d AND status = 'active'",
				$group_id
			)
		);
	}

	/**
	 * Add user to group
	 */
	public function addMember( int $group_id, int $user_id, string $role = 'member' ): bool {
		global $wpdb;

		$members_table = $wpdb->prefix . 'apollo_group_members';

		// Check if already a member
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$members_table} WHERE group_id = %d AND user_id = %d",
				$group_id,
				$user_id
			)
		);

		if ( $existing ) {
			// Update status to active if was left/banned
			return $wpdb->update(
				$members_table,
				array(
					'status'    => 'active',
					'role'      => $role,
					'joined_at' => current_time( 'mysql' ),
				),
				array(
					'group_id' => $group_id,
					'user_id'  => $user_id,
				),
				array( '%s', '%s', '%s' ),
				array( '%d', '%d' )
			) !== false;
		}

		// Insert new member
		return $wpdb->insert(
			$members_table,
			array(
				'group_id'  => $group_id,
				'user_id'   => $user_id,
				'role'      => $role,
				'status'    => 'active',
				'joined_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		) !== false;
	}

	/**
	 * Remove user from group
	 */
	public function removeMember( int $group_id, int $user_id ): bool {
		global $wpdb;

		$members_table = $wpdb->prefix . 'apollo_group_members';

		return $wpdb->update(
			$members_table,
			array(
				'status'  => 'left',
				'left_at' => current_time( 'mysql' ),
			),
			array(
				'group_id' => $group_id,
				'user_id'  => $user_id,
			),
			array( '%s', '%s' ),
			array( '%d', '%d' )
		) !== false;
	}

	/**
	 * Check if user is member of group
	 */
	public function isMember( int $group_id, int $user_id ): bool {
		global $wpdb;

		$members_table = $wpdb->prefix . 'apollo_group_members';

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$members_table} WHERE group_id = %d AND user_id = %d AND status = 'active'",
				$group_id,
				$user_id
			)
		);

		return $count > 0;
	}

	/**
	 * Map database row to GroupEntity
	 */
	private function mapToEntity( array $row ): GroupEntity {
		return new GroupEntity(
			array(
				'id'            => (int) $row['id'],
				'title'         => $row['title'],
				'slug'          => $row['slug'],
				'description'   => $row['description'] ?? '',
				'type'          => $row['type'],
				'status'        => $row['status'] ?? 'draft',
				'visibility'    => $row['visibility'] ?? 'public',
				'season_slug'   => $row['season_slug'] ?? null,
				'creator_id'    => (int) $row['creator_id'],
				'created_at'    => $row['created_at'],
				'updated_at'    => $row['updated_at'] ?? null,
				'published_at'  => $row['published_at'] ?? null,
				'members_count' => $this->getMembersCount( (int) $row['id'] ),
			)
		);
	}

	/**
	 * Generate slug from title
	 */
	private function generateSlug( string $title ): string {
		$slug = sanitize_title( $title );
		return $slug;
	}

	/**
	 * Ensure slug is unique
	 */
	private function ensureUniqueSlug( string $slug, int $exclude_id = 0 ): string {
		global $wpdb;

		$base_slug = $slug;
		$counter   = 1;

		while ( true ) {
			$sql    = "SELECT id FROM {$this->table_name} WHERE slug = %s";
			$params = array( $slug );

			if ( $exclude_id > 0 ) {
				$sql     .= ' AND id != %d';
				$params[] = $exclude_id;
			}

			$existing = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

			if ( ! $existing ) {
				return $slug;
			}

			$slug = $base_slug . '-' . $counter;
			++$counter;
		}
	}
}
