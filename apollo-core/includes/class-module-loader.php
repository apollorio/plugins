<?php

declare(strict_types=1);

namespace Apollo_Core;

/**
 * Module Loader
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Loader class
 */
class Module_Loader {

	/**
	 * Loaded modules
	 *
	 * @var array
	 */
	private array $modules = array();

	/**
	 * Modules directory
	 *
	 * @var string
	 */
	private string $modules_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->modules_dir = APOLLO_CORE_PLUGIN_DIR . 'modules/';
	}

	/**
	 * Load all modules
	 *
	 * @return void
	 */
	public function load(): void {
		if ( ! is_dir( $this->modules_dir ) ) {
			return;
		}

		// Get all module directories.
		$module_dirs = glob( $this->modules_dir . '*', GLOB_ONLYDIR );

		if ( empty( $module_dirs ) ) {
			return;
		}

		foreach ( $module_dirs as $module_dir ) {
			$this->load_module( $module_dir );
		}

		do_action( 'apollo_core_modules_loaded', $this->modules );
	}

	/**
	 * Load single module
	 *
	 * @param string $module_dir Module directory path.
	 * @return void
	 */
	private function load_module( string $module_dir ): void {
		$bootstrap_file = $module_dir . '/bootstrap.php';

		if ( ! file_exists( $bootstrap_file ) ) {
			return;
		}

		$module_slug = basename( $module_dir );

		// Check if module is enabled.
		if ( ! $this->is_module_enabled( $module_slug ) ) {
			return;
		}

		// Load bootstrap file.
		require_once $bootstrap_file;

		// Store loaded module.
		$this->modules[ $module_slug ] = array(
			'path'      => $module_dir,
			'bootstrap' => $bootstrap_file,
		);

		do_action( 'apollo_core_module_loaded', $module_slug, $module_dir );
	}

	/**
	 * Check if module is enabled
	 *
	 * @param string $module_slug Module slug.
	 * @return bool
	 */
	private function is_module_enabled( string $module_slug ): bool {
		$settings        = get_option( 'apollo_mod_settings', array() );
		$enabled_modules = isset( $settings['enabled_modules'] ) ? $settings['enabled_modules'] : array( 'events', 'social', 'mod' );

		return in_array( $module_slug, $enabled_modules, true );
	}

	/**
	 * Get loaded modules
	 *
	 * @return array
	 */
	public function get_modules(): array {
		return $this->modules;
	}

	/**
	 * Check if module is loaded
	 *
	 * @param string $module_slug Module slug.
	 * @return bool
	 */
	public function is_module_loaded( string $module_slug ): bool {
		return isset( $this->modules[ $module_slug ] );
	}
}
