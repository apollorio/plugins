<?php
/**
 * Moderation Service (Stub)
 *
 * @package Apollo\Modules\Moderation\Services
 */

namespace Apollo\Modules\Moderation\Services;

/**
 * Stub class - full implementation pending repair.
 */
class ModerationService {

	/**
	 * Submit for moderation (stub)
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type.
	 * @param array  $data        Data array.
	 * @return array
	 */
	public function submit( int $entity_id, string $entity_type, array $data = array() ): array {
		return array(
			'success' => false,
			'message' => __( 'Moderation service temporarily unavailable.', 'apollo-social' ),
		);
	}

	/**
	 * Get pending count (stub)
	 *
	 * @return int
	 */
	public function getPendingCount(): int {
		return 0;
	}
}
