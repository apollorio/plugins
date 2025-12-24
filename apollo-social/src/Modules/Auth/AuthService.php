<?php
/**
 * Auth Service
 *
 * Handles user authentication and roles
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auth Service Class
 */
class Apollo_Auth_Service {

	/**
	 * Initialize auth hooks
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_custom_roles' ) );
	}

	/**
	 * Add custom user roles
	 */
	public static function add_custom_roles() {
		add_role(
			'apollo_member',
			'Apollo Member',
			array(
				'read' => true,
			)
		);
	}
}

// Initialize
Apollo_Auth_Service::init();
