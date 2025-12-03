<?php
namespace Apollo\Domain\Memberships\Policies;

use Apollo\Domain\Entities\User;
use Apollo\Domain\Entities\UnionEntity;

/**
 * Membership Policy - Define access rules for unions and badges
 */
class MembershipPolicy {

	/**
	 * Can user view union content?
	 *
	 * @param UnionEntity $union The union to check
	 * @param User|null   $user The user (optional, null for guests)
	 */
	public function canViewUnion( UnionEntity $union, ?User $user = null ): bool {
		// Uniões são públicas por padrão
		return true;
	}

	/**
	 * Can user toggle badges for union?
	 */
	public function canToggleBadges( User $user, UnionEntity $union ): bool {
		if ( ! $user->isLoggedIn() ) {
			return false;
		}

		// Only administrators or union managers can toggle badges
		if ( $user->hasRole( 'administrator' ) ) {
			return true;
		}

		return $union->hasManager( $user->id );
	}

	/**
	 * Can user join union?
	 */
	public function canJoinUnion( User $user, UnionEntity $union ): bool {
		if ( ! $user->isLoggedIn() ) {
			return false;
		}

		// TODO: Check if already a member
		return true;
	}

	/**
	 * Can user view badges panel?
	 */
	public function canViewBadgesPanel( ?User $user = null, ?UnionEntity $union = null ): bool {
		// Check global badges toggle setting (mock function call)
		$global_badges_enabled = true;
		// get_option('apollo_badges_enabled', true);

		if ( ! $global_badges_enabled ) {
			return false;
		}

		// If specific union context, check union badges toggle
		if ( $union ) {
			return $union->badges_toggle;
		}

		return true;
	}
}
