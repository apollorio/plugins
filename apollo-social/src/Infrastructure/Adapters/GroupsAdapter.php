<?php

namespace Apollo\Infrastructure\Adapters;

/**
 * Adapter for itthinx Groups plugin integration
 * Provides seamless integration with Groups plugin for ACL management
 */
class GroupsAdapter {

	private $config;
	private $group_mapping;

	public function __construct() {
		$this->config        = config( 'integrations.itthinx_groups' );
		$this->group_mapping = $this->config['group_mapping'] ?? array();

		if ( $this->config['enabled'] ?? false ) {
			$this->init_hooks();
		}
	}

	/**
	 * Initialize WordPress hooks for Groups integration
	 */
	private function init_hooks() {
		// Group creation/deletion sync
		add_action( 'groups_created_group', array( $this, 'on_group_created' ), 10, 2 );
		add_action( 'groups_deleted_group', array( $this, 'on_group_deleted' ), 10, 1 );

		// User group membership sync
		add_action( 'groups_created_user_group', array( $this, 'on_user_added_to_group' ), 10, 2 );
		add_action( 'groups_deleted_user_group', array( $this, 'on_user_removed_from_group' ), 10, 2 );

		// Capability sync
		add_action( 'groups_created_group_capability', array( $this, 'on_capability_added' ), 10, 2 );
		add_action( 'groups_deleted_group_capability', array( $this, 'on_capability_removed' ), 10, 2 );

		// Auto sync if enabled
		if ( $this->config['auto_sync'] ?? false ) {
			add_action( 'wp_scheduled_event', array( $this, 'sync_all_groups' ) );

			if ( ! wp_next_scheduled( 'apollo_groups_sync' ) ) {
				wp_schedule_event( time(), $this->config['sync_frequency'] ?? 'hourly', 'apollo_groups_sync' );
			}
		}
	}

	/**
	 * Check if Groups plugin is active and available
	 */
	public function is_available(): bool {
		return class_exists( 'Groups_Group' ) && class_exists( 'Groups_User_Group' );
	}

	/**
	 * Create Apollo group mapping for Groups plugin group
	 */
	public function on_group_created( $group_id, $group ) {
		if ( ! $this->is_available() ) {
			return;
		}

		$apollo_type = $this->determine_apollo_type( $group );
		if ( ! $apollo_type ) {
			return;
		}

		// Store mapping in group meta
		Groups_Group::update_group_meta( $group_id, 'apollo_type', $apollo_type );
		Groups_Group::update_group_meta( $group_id, 'apollo_sync', true );

		// Trigger Apollo event
		do_action( 'apollo_group_synced', $group_id, $apollo_type, 'created' );
	}

	/**
	 * Handle group deletion
	 */
	public function on_group_deleted( $group_id ) {
		if ( ! $this->is_available() ) {
			return;
		}

		$apollo_type = Groups_Group::get_group_meta( $group_id, 'apollo_type' );
		if ( $apollo_type ) {
			do_action( 'apollo_group_synced', $group_id, $apollo_type, 'deleted' );
		}
	}

	/**
	 * Handle user added to group
	 */
	public function on_user_added_to_group( $user_group_id, $user_group ) {
		if ( ! $this->is_available() ) {
			return;
		}

		$group_id    = $user_group->group_id;
		$user_id     = $user_group->user_id;
		$apollo_type = Groups_Group::get_group_meta( $group_id, 'apollo_type' );

		if ( $apollo_type ) {
			do_action( 'apollo_user_group_changed', $user_id, $group_id, $apollo_type, 'added' );
		}
	}

	/**
	 * Handle user removed from group
	 */
	public function on_user_removed_from_group( $user_group_id, $user_group ) {
		if ( ! $this->is_available() ) {
			return;
		}

		$group_id    = $user_group->group_id;
		$user_id     = $user_group->user_id;
		$apollo_type = Groups_Group::get_group_meta( $group_id, 'apollo_type' );

		if ( $apollo_type ) {
			do_action( 'apollo_user_group_changed', $user_id, $group_id, $apollo_type, 'removed' );
		}
	}

	/**
	 * Handle capability changes
	 */
	public function on_capability_added( $group_capability_id, $group_capability ) {
		$this->sync_group_capabilities( $group_capability->group_id );
	}

	public function on_capability_removed( $group_capability_id, $group_capability ) {
		$this->sync_group_capabilities( $group_capability->group_id );
	}

	/**
	 * Get user's Apollo groups through Groups plugin
	 */
	public function get_user_apollo_groups( $user_id ): array {
		if ( ! $this->is_available() ) {
			return array();
		}

		$apollo_groups = array();
		$user_groups   = Groups_User_Group::get_user_groups( $user_id );

		foreach ( $user_groups as $user_group ) {
			$group_id    = $user_group->group_id;
			$apollo_type = Groups_Group::get_group_meta( $group_id, 'apollo_type' );

			if ( $apollo_type ) {
				$apollo_groups[] = array(
					'id'           => $group_id,
					'type'         => $apollo_type,
					'name'         => Groups_Group::get_group( $group_id )->name,
					'capabilities' => $this->get_group_capabilities( $group_id ),
				);
			}
		}

		return $apollo_groups;
	}

	/**
	 * Check if user has access to specific Apollo group type
	 */
	public function user_has_group_access( $user_id, $group_type ): bool {
		if ( ! $this->is_available() ) {
			return false;
		}

		$user_groups = $this->get_user_apollo_groups( $user_id );

		foreach ( $user_groups as $group ) {
			if ( $group['type'] === $group_type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add user to Apollo group type
	 */
	public function add_user_to_group( $user_id, $group_type ): bool {
		if ( ! $this->is_available() ) {
			return false;
		}

		$group_id = $this->find_or_create_apollo_group( $group_type );
		if ( ! $group_id ) {
			return false;
		}

		return Groups_User_Group::create(
			array(
				'user_id'  => $user_id,
				'group_id' => $group_id,
			)
		);
	}

	/**
	 * Remove user from Apollo group type
	 */
	public function remove_user_from_group( $user_id, $group_type ): bool {
		if ( ! $this->is_available() ) {
			return false;
		}

		$user_groups = $this->get_user_apollo_groups( $user_id );

		foreach ( $user_groups as $group ) {
			if ( $group['type'] === $group_type ) {
				return Groups_User_Group::delete_by_user_group( $user_id, $group['id'] );
			}
		}

		return false;
	}

	/**
	 * Sync all groups with Apollo types
	 */
	public function sync_all_groups() {
		if ( ! $this->is_available() ) {
			return;
		}

		$groups = Groups_Group::get_groups();

		foreach ( $groups as $group ) {
			$apollo_type = Groups_Group::get_group_meta( $group->group_id, 'apollo_type' );
			if ( ! $apollo_type ) {
				$apollo_type = $this->determine_apollo_type( $group );
				if ( $apollo_type ) {
					Groups_Group::update_group_meta( $group->group_id, 'apollo_type', $apollo_type );
					Groups_Group::update_group_meta( $group->group_id, 'apollo_sync', true );
				}
			}
		}

		do_action( 'apollo_groups_sync_completed' );
	}

	/**
	 * Determine Apollo group type from Groups plugin group
	 */
	private function determine_apollo_type( $group ): ?string {
		$name = strtolower( $group->name );

		// Check direct mapping first
		foreach ( $this->group_mapping as $apollo_type => $groups_name ) {
			if ( strpos( $name, strtolower( $groups_name ) ) !== false ) {
				return $apollo_type;
			}
		}

		// Fallback to pattern matching
		if ( strpos( $name, 'comunidade' ) !== false || strpos( $name, 'community' ) !== false ) {
			return 'comunidade';
		}

		if ( strpos( $name, 'nucleo' ) !== false || strpos( $name, 'core' ) !== false ) {
			return 'nucleo';
		}

		if ( strpos( $name, 'season' ) !== false || strpos( $name, 'temporada' ) !== false ) {
			return 'season';
		}

		return null;
	}

	/**
	 * Find or create Apollo group in Groups plugin
	 */
	private function find_or_create_apollo_group( $apollo_type ): ?int {
		if ( ! isset( $this->group_mapping[ $apollo_type ] ) ) {
			return null;
		}

		$groups_name = $this->group_mapping[ $apollo_type ];

		// Try to find existing group
		$groups = Groups_Group::get_groups( array( 'name' => $groups_name ) );
		if ( ! empty( $groups ) ) {
			return $groups[0]->group_id;
		}

		// Create new group
		$group_id = Groups_Group::create(
			array(
				'name'        => $groups_name,
				'description' => "Apollo {$apollo_type} group",
			)
		);

		if ( $group_id ) {
			Groups_Group::update_group_meta( $group_id, 'apollo_type', $apollo_type );
			Groups_Group::update_group_meta( $group_id, 'apollo_sync', true );

			// Add default capability
			$default_cap = $this->config['default_capability'] ?? 'read';
			Groups_Group_Capability::create(
				array(
					'group_id'   => $group_id,
					'capability' => $default_cap,
				)
			);
		}

		return $group_id;
	}

	/**
	 * Get group capabilities
	 */
	private function get_group_capabilities( $group_id ): array {
		if ( ! $this->is_available() ) {
			return array();
		}

		$capabilities       = array();
		$group_capabilities = Groups_Group_Capability::get_group_capabilities( $group_id );

		foreach ( $group_capabilities as $cap ) {
			$capabilities[] = $cap->capability;
		}

		return $capabilities;
	}

	/**
	 * Sync group capabilities
	 */
	private function sync_group_capabilities( $group_id ) {
		$apollo_type = Groups_Group::get_group_meta( $group_id, 'apollo_type' );
		if ( $apollo_type ) {
			$capabilities = $this->get_group_capabilities( $group_id );
			do_action( 'apollo_group_capabilities_synced', $group_id, $apollo_type, $capabilities );
		}
	}

	/**
	 * Get configuration
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Update configuration
	 */
	public function update_config( array $config ): bool {
		$this->config = array_merge( $this->config, $config );

		return update_option( 'apollo_groups_config', $this->config );
	}
}
