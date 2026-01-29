<?php

declare(strict_types=1);

/**
 * Moderation Module Bootstrap
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define module constants.
if ( ! defined( 'APOLLO_MODERATION_MODULE_DIR' ) ) {
	define( 'APOLLO_MODERATION_MODULE_DIR', __DIR__ . '/' );
}

// Include files.
require_once APOLLO_MODERATION_MODULE_DIR . 'includes/class-roles.php';
require_once APOLLO_MODERATION_MODULE_DIR . 'includes/class-audit-log.php';
require_once APOLLO_MODERATION_MODULE_DIR . 'includes/class-suspension.php';
require_once APOLLO_MODERATION_MODULE_DIR . 'includes/class-admin-ui.php';
require_once APOLLO_MODERATION_MODULE_DIR . 'includes/class-rest-api.php';
require_once APOLLO_MODERATION_MODULE_DIR . 'includes/class-wp-cli.php';

/**
 * Moderation Module class
 */
class Apollo_Moderation_Module {

	/**
	 * Initialize module
	 */
	public static function init() {
		// Initialize components.
		Apollo_Moderation_Roles::init();
		Apollo_Moderation_Audit_Log::init();
		Apollo_Moderation_Suspension::init();
		Apollo_Moderation_Admin_UI::init();
		Apollo_Moderation_REST_API::init();
		Apollo_Moderation_WP_CLI::init();
	}
}

// Initialize module.
Apollo_Moderation_Module::init();
