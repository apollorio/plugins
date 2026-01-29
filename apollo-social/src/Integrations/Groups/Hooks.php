<?php

namespace Apollo\Integrations\Groups;

/**
 * Groups plugin integration hooks
 *
 * Placeholder for Itthinx Groups plugin integration.
 * This integration is NOT active - feature flag 'groups' is disabled.
 *
 * @since 2.3.0
 * @status STUB - Not implemented, guarded by FeatureFlags::isEnabled('groups')
 */
class Hooks {

	/**
	 * Register integration hooks
	 *
	 * @return void
	 */
	public function register(): void {
		// Not implemented - placeholder for Itthinx Groups integration
		// Potential hooks:
		// - itthinx_groups_user_added_to_group
		// - itthinx_groups_user_removed_from_group
	}

	/**
	 * Sync user added to group
	 *
	 * @param int $user_id  User ID.
	 * @param int $group_id Group ID.
	 * @return void
	 */
	public function syncUserAdded( int $user_id, int $group_id ): void {
		// Not implemented
	}

	/**
	 * Sync user removed from group
	 *
	 * @param int $user_id  User ID.
	 * @param int $group_id Group ID.
	 * @return void
	 */
	public function syncUserRemoved( int $user_id, int $group_id ): void {
		// Not implemented
	}
}
