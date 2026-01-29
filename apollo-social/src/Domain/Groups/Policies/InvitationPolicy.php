<?php

namespace Apollo\Domain\Groups\Policies;

use Apollo\Contracts\Policy;

/**
 * Invitation policy (stub)
 *
 * Defines rules for group invitations.
 * TODO: Implement invitation creation, acceptance and management policies.
 */
class InvitationPolicy implements Policy {

	/**
	 * Check if user can create invitation
	 * TODO: implement invitation creation permissions
	 */
	public function canCreate( $user, $group, $target ) {
		// TODO: implement creation permission logic
	}

	/**
	 * Check if user can accept invitation
	 * TODO: implement invitation acceptance permissions
	 */
	public function canAccept( $user, $invitation ) {
		// TODO: implement acceptance permission logic
	}
}
