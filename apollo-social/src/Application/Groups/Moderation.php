<?php
/**
 * Group Moderation Use Cases (Stub)
 *
 * @package Apollo\Application\Groups
 */

namespace Apollo\Application\Groups;

/**
 * Stub class - full implementation pending repair.
 */
class Moderation {

	/**
	 * Get rejection notice for group
	 *
	 * @param int $group_id Group ID.
	 * @return string|null
	 */
	public function getRejectionNotice( int $group_id ): ?string {
		return get_post_meta( $group_id, '_apollo_rejection_notice', true ) ?: null;
	}

	/**
	 * Submit group for review (stub)
	 *
	 * @param int   $group_id Group ID.
	 * @param array $data     Data array.
	 * @return array
	 */
	public function submitForReview( int $group_id, array $data = array() ): array {
		return array(
			'success' => false,
			'message' => __( 'Moderation service temporarily unavailable.', 'apollo-social' ),
		);
	}

	/**
	 * Approve group (stub)
	 *
	 * @param int    $group_id     Group ID.
	 * @param int    $moderator_id Moderator ID.
	 * @param string $notes        Notes.
	 * @return array
	 */
	public function approveGroup( int $group_id, int $moderator_id, string $notes = '' ): array {
		return array(
			'success' => false,
			'message' => __( 'Moderation service temporarily unavailable.', 'apollo-social' ),
		);
	}

	/**
	 * Reject group (stub)
	 *
	 * @param int    $group_id     Group ID.
	 * @param int    $moderator_id Moderator ID.
	 * @param string $reason       Rejection reason.
	 * @param string $notes        Notes.
	 * @return array
	 */
	public function rejectGroup( int $group_id, int $moderator_id, string $reason, string $notes = '' ): array {
		return array(
			'success' => false,
			'message' => __( 'Moderation service temporarily unavailable.', 'apollo-social' ),
		);
	}
}
