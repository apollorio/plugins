<?php
namespace Apollo\Application\Classifieds;

/**
 * Moderation use case (stub)
 *
 * Handles classified ad modow.
 * TODO: Implement ad approval/rejection workflow.
 */
class Moderation {

	/**
	 * Execute ad mod
	 * TODO: implement mod
	 */
	public function execute( $ad, $action, $moderator, $reason = '' ) {
		// TODO: implement mod
		// 1. Check modsions
		// 2. Validate action (approve, reject, flag)
		// 3. Update ad status
		// 4. Notify ad creator
		// 5. Log mod
	}
}
