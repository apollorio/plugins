<?php
/**
 * Database Schema (Stub)
 *
 * @package Apollo\Infrastructure\Database
 */

namespace Apollo\Infrastructure\Database;

/**
 * Stub class - full implementation pending repair.
 */
class Schema {

	/**
	 * Get statistics (stub)
	 *
	 * @return array
	 */
	public function getStatistics(): array {
		return array(
			'workflow_transitions' => 0,
			'pending_mod'          => 0,
			'total_events'         => 0,
			'events_today'         => 0,
			'signature_requests'   => 0,
			'pending_signatures'   => 0,
		);
	}
}
