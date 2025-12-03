<?php
namespace Apollo\Domain\Events;

/**
 * Event reference (stub)
 *
 * References events from WP Event Manager with Apollo-specific extensions.
 * TODO: Define event integration, season binding and Apollo-specific metadata.
 */
class EventRef {

	/**
	 * WP Event Manager post ID
	 * TODO: implement property and getters/setters
	 */
	protected $wp_event_id;

	/**
	 * Season binding (optional)
	 * TODO: implement season association
	 */
	protected $season;

	/**
	 * Get event URL (from WP Event Manager)
	 * TODO: implement event URL retrieval from WP Event Manager
	 */
	public function getUrl() {
		// TODO: implement event URL generation logic
	}

	/**
	 * Get season-filtered events
	 * TODO: implement season filtering logic
	 */
	public static function getBySeason( $season ) {
		// TODO: implement season-based event filtering logic
	}
}
