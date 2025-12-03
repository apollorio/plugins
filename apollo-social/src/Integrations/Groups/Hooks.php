<?php
namespace Apollo\Integrations\Groups;

/**
 * Groups plugin hooks (stub)
 *
 * Handles integration with Itthinx Groups plugin.
 * TODO: Implement hooks for Groups plugin integration.
 */
class Hooks {

	/**
	 * Register integration hooks
	 * TODO: implement hooks registration
	 */
	public function register() {
		// TODO: register hooks for Groups plugin integration
		// add_action('itthinx_groups_user_added_to_group', [$this, 'syncUserAdded']);
		// add_action('itthinx_groups_user_removed_from_group', [$this, 'syncUserRemoved']);
	}

	/**
	 * Sync user added to group
	 * TODO: implement user addition sync
	 */
	public function syncUserAdded( $user_id, $group_id ) {
		// TODO: sync user addition with Apollo groups
	}

	/**
	 * Sync user removed from group
	 * TODO: implement user removal sync
	 */
	public function syncUserRemoved( $user_id, $group_id ) {
		// TODO: sync user removal with Apollo groups
	}
}
