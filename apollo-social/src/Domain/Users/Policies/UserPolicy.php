<?php

namespace Apollo\Domain\Users\Policies;

use Apollo\Contracts\Policy;

/**
 * User policy (stub)
 *
 * Defines rules for user profile access and actions.
 * TODO: Implement visibility, edit and interaction policies.
 */
class UserPolicy implements Policy
{
    /**
     * Check if user can view profile
     * TODO: implement profile viewing permissions
     */
    public function canView($user, $target)
    {
        // TODO: implement view permission logic
    }

    /**
     * Check if user can edit profile
     * TODO: implement profile editing permissions
     */
    public function canEdit($user, $target)
    {
        // TODO: implement edit permission logic
    }
}
