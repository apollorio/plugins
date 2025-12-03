<?php
namespace Apollo\Domain\Groups;

/**
 * Group entity (stub)
 *
 * Represents a group (comunidade, nucleo, season) with common properties.
 * TODO: Define group properties, membership methods and type-specific behaviors.
 */
class GroupEntity {

	/**
	 * Group ID
	 * TODO: implement property and getters/setters
	 */
	protected $id;

	/**
	 * Group slug
	 * TODO: implement property and URL generation
	 */
	protected $slug;

	/**
	 * Group type (comunidade, nucleo, season)
	 * TODO: implement type validation using GroupType enum
	 */
	protected $type;

	/**
	 * Get group URL
	 * TODO: implement URL generation for /{type}/{slug}
	 */
	public function getUrl() {
		// TODO: implement group URL generation logic
	}

	/**
	 * Check if user can join group
	 * TODO: implement join permission logic using policies
	 */
	public function canUserJoin( $user ) {
		// TODO: implement join permission checking logic
	}
}
