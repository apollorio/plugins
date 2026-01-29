<?php

namespace Apollo\Modules\Badges\Adapters;

/**
 * BadgeOS adapter (stub)
 */
class BadgeOSAdapter {

	public function awardBadge( $badge_id, $user_id, $reason = '' ) {
		// TODO: award BadgeOS badge to user
	}

	public function getUserBadges( $user_id ) {
		// TODO: get all badges for user from BadgeOS
	}

	public function checkToggleSettings( $user_id ) {
		// TODO: check if badges are enabled for user's unions
	}
}
