<?php

declare(strict_types=1);

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
class Apollo_Core_Module_Loader {

	/**
	 * Loaded modules
	 *
	 * @var array
	 */
	private $modules = [];

	/**
	 * Modules directory
	 *
	 * @var string
	 */
	private $modules_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->modules_dir = APOLLO_CORE_PLUGIN_DIR . 'modules/';
	}

	/**
	 * Load all modules
	 */
	public function load() {
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
	 */
	private function load_module( $module_dir ) {
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
		$this->modules[ $module_slug ] = [
			'path'      => $module_dir,
			'bootstrap' => $bootstrap_file,
		];

		do_action( 'apollo_core_module_loaded', $module_slug, $module_dir );
	}

	/**
	 * Check if module is enabled
	 *
	 * @param string $module_slug Module slug.
	 * @return bool
	 */
	private function is_module_enabled( $module_slug ) {
		$settings        = get_option( 'apollo_mod_settings', [] );
		$enabled_modules = isset( $settings['enabled_modules'] ) ? $settings['enabled_modules'] : [ 'events', 'social', 'mod' ];

		return in_array( $module_slug, $enabled_modules, true );
	}

	/**
	 * Get loaded modules
	 *
	 * @return array
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Check if module is loaded
	 *
	 * @param string $module_slug Module slug.
	 * @return bool
	 */
	public function is_module_loaded( $module_slug ) {
		return isset( $this->modules[ $module_slug ] );
	}
}
