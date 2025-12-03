<?php
namespace Apollo\Domain\Users;

/**
 * User entity (stub)
 *
 * Represents a user with Apollo-specific attributes and methods.
 * TODO: Define user properties, profile data and social features.
 */
class UserEntity {

	/**
	 * User ID
	 * TODO: implement property and getters/setters
	 */
	protected $id;

	/**
	 * User login/username
	 * TODO: implement property and validation
	 */
	protected $login;

	/**
	 * Get user profile URL
	 * TODO: implement URL generation for /id/{id|login}
	 */
	public function getProfileUrl() {
		// TODO: implement profile URL generation logic
	}

	/**
	 * Check if user can be viewed by current user
	 * TODO: implement visibility logic using policies
	 */
	public function canBeViewedBy( $viewer ) {
		// TODO: implement visibility checking logic
	}
}
