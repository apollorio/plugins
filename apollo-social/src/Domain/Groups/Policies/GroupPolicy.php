<?php
namespace Apollo\Domain\Groups\Policies;

use Apollo\Domain\Entities\User;
use Apollo\Domain\Entities\GroupEntity;

/**
 * Group Policy - Define access rules for groups
 */
class GroupPolicy {

	private $config;

	public function __construct() {
		$this->config = include APOLLO_SOCIAL_PLUGIN_DIR . 'config/groups.php';
	}

	/**
	 * Can user view group content?
	 *
	 * @param GroupEntity $group The group to check
	 * @param User|null   $user The user (optional, null for guests)
	 */
	public function canView( GroupEntity $group, ?User $user = null ): bool {
		$type_config = $this->getTypeConfig( $group->type );

		switch ( $type_config['visibility'] ) {
			case 'public':
				return true;

			case 'private':
				// Núcleo: apenas membros podem ver conteúdo
				return $user && $this->isMember( $user, $group );

			default:
				return false;
		}
	}

	/**
	 * Can user join group?
	 */
	public function canJoin( User $user, GroupEntity $group ): bool {
		if ( ! $user->isLoggedIn() ) {
			return false;
		}

		// Already a member
		if ( $this->isMember( $user, $group ) ) {
			return false;
		}

		$type_config = $this->getTypeConfig( $group->type );

		switch ( $type_config['join'] ) {
			case 'open':
				return true;

			case 'invite_only':
				return false;
			// Must be invited

			case 'request':
				return true;
			// Can request to join

			default:
				return false;
		}
	}

	/**
	 * Can user invite others to group?
	 */
	public function canInvite( User $user, GroupEntity $group ): bool {
		if ( ! $user->isLoggedIn() ) {
			return false;
		}

		$type_config = $this->getTypeConfig( $group->type );

		switch ( $type_config['invite'] ) {
			case 'any_member':
				return $this->isMember( $user, $group );

			case 'insiders_only':
				return $this->isMember( $user, $group );

			case 'moderators':
				return $this->isModerator( $user, $group );

			default:
				return false;
		}
	}

	/**
	 * Can user post in specific scope?
	 */
	public function canPost( User $user, GroupEntity $group, string $scope ): bool {
		if ( ! $user->isLoggedIn() ) {
			return false;
		}

		if ( ! $this->isMember( $user, $group ) ) {
			return false;
		}

		$type_config = $this->getTypeConfig( $group->type );
		$posting     = $type_config['posting'] ?? array();

		// Check if scope is allowed for this group type
		if ( isset( $posting['scopes'] ) && ! in_array( $scope, $posting['scopes'] ) ) {
			return false;
		}

		// Check user role
		$user_role = $this->getUserRoleInGroup( $user, $group );
		return in_array( $user_role, $posting['roles'] ?? array() );
	}

	/**
	 * Get configuration for group type
	 */
	private function getTypeConfig( string $type ): array {
		return $this->config[ $type ] ?? array();
	}

	/**
	 * Check if user is member of group (mock implementation)
	 */
	private function isMember( User $user, GroupEntity $group ): bool {
		// TODO: Implement real membership check
		// For now, mock based on user ID and group ID
		return ( $user->id % 3 ) === ( $group->id % 3 );
	}

	/**
	 * Check if user is moderator of group (mock implementation)
	 */
	private function isModerator( User $user, GroupEntity $group ): bool {
		// TODO: Implement real moderator check
		return $user->hasRole( 'administrator' ) || ( $user->id === $group->created_by );
	}

	/**
	 * Get user role in group (mock implementation)
	 */
	private function getUserRoleInGroup( User $user, GroupEntity $group ): string {
		// TODO: Implement real role detection
		if ( $this->isModerator( $user, $group ) ) {
			return 'moderator';
		}

		if ( $this->isMember( $user, $group ) ) {
			return 'member';
		}

		return 'guest';
	}
}
