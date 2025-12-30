<?php
/**
 * Core Loader
 *
 * Loads all core classes for the modular architecture.
 *
 * @package Apollo_Events_Manager
 * @subpackage Core
 * @since 1.0.0
 */

declare( strict_types = 1 );

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core path.
$core_path = plugin_dir_path( __FILE__ );

// Load core classes in order.
require_once $core_path . 'class-module-interface.php';
require_once $core_path . 'class-abstract-module.php';
require_once $core_path . 'class-module-registry.php';
require_once $core_path . 'class-bootloader.php';
require_once $core_path . 'class-assets-manager.php';
require_once $core_path . 'class-modules-admin.php';

/**
 * Initialize the modular system.
 *
 * @since 1.0.0
 *
 * @return void
 */
function apollo_em_init_modular_system(): void {
	// Initialize the bootloader.
	$bootloader = \Apollo\Events\Core\Bootloader::get_instance();
	$bootloader->boot();

	// Initialize admin settings if in admin.
	if ( is_admin() ) {
		$modules_admin = \Apollo\Events\Core\Modules_Admin::get_instance();
		$modules_admin->init();
	}
}

// Hook into plugins_loaded to initialize.
add_action( 'plugins_loaded', 'apollo_em_init_modular_system', 15 );

/**
 * Get the module registry instance.
 *
 * Helper function for accessing the registry.
 *
 * @since 1.0.0
 *
 * @return \Apollo\Events\Core\Module_Registry The registry instance.
 */
function apollo_em_get_registry(): \Apollo\Events\Core\Module_Registry {
	return \Apollo\Events\Core\Module_Registry::get_instance();
}

/**
 * Check if a module is active.
 *
 * Helper function for quick module status checks.
 *
 * @since 1.0.0
 *
 * @param string $module_id The module ID to check.
 *
 * @return bool True if active.
 */
function apollo_em_is_module_active( string $module_id ): bool {
	return apollo_em_get_registry()->is_active( $module_id );
}

/**
 * Get the assets manager instance.
 *
 * Helper function for accessing the assets manager.
 *
 * @since 1.0.0
 *
 * @return \Apollo\Events\Core\Assets_Manager The assets manager instance.
 */
function apollo_em_get_assets(): \Apollo\Events\Core\Assets_Manager {
	return \Apollo\Events\Core\Assets_Manager::get_instance();
}
