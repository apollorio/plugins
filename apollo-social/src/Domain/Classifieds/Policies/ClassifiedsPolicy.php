<?php

namespace Apollo\Domain\Classifieds\Policies;

use Apollo\Domain\Entities\User;
use Apollo\Domain\Entities\GroupEntity;
use Apollo\Domain\Entities\AdEntity;

/**
 * Classifieds Policy - Define access rules for classified ads
 */
class ClassifiedsPolicy
{
    /**
     * Can user create classified ad?
     */
    public function canCreate(User $user, ?GroupEntity $context = null): bool
    {
        if (! $user->isLoggedIn()) {
            return false;
        }

        // If in group context, check group permissions
        if ($context) {
            // For season groups, additional validation will be done by BindSeason
            if ($context->isSeason()) {
                // Season context requires special season_slug validation
                return true;
                // Will be validated by BindSeason
            }

            // For other group types, check if user can post classifieds
            $groupPolicy = new \Apollo\Domain\Groups\Policies\GroupPolicy();

            return $groupPolicy->canPost($user, $context, 'classified');
        }

        // General classified creation (no group context)
        return true;
    }

    /**
     * Can user edit classified ad?
     */
    public function canEdit(User $user, AdEntity $ad): bool
    {
        if (! $user->isLoggedIn()) {
            return false;
        }

        // Owner can always edit
        if ($ad->author_id === $user->id) {
            return true;
        }

        // Administrators can edit any ad
        if ($user->hasRole('administrator')) {
            return true;
        }

        // TODO: Check if user is moderator of the group/context
        return false;
    }

    /**
     * Can user moderate classified ad?
     */
    public function canModerate(User $user, AdEntity $ad): bool
    {
        if (! $user->isLoggedIn()) {
            return false;
        }

        // Administrators can moderate any ad
        if ($user->hasRole('administrator')) {
            return true;
        }

        // TODO: Check if user is moderator of the group/context where ad was posted
        return false;
    }

    /**
     * Can user view classified ad?
     *
     * @param AdEntity  $ad The classified ad to check
     * @param User|null $user The user (optional, null for guests)
     */
    public function canView(AdEntity $ad, ?User $user = null): bool
    {
        // Most classifieds are public
        if ($ad->status === 'active') {
            return true;
        }

        // Draft/private ads only visible to author and moderators
        if ($ad->status === 'draft' || $ad->status === 'private') {
            if (! $user) {
                return false;
            }

            return $ad->author_id === $user->id || $this->canModerate($user, $ad);
        }

        return false;
    }
}
