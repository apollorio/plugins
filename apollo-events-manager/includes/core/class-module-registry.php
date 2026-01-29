<?php
/**
 * Module Registry
 *
 * Central registry for all Apollo Events Manager modules.
 * Handles module registration, activation status, and dependency resolution.
 *
 * @package Apollo_Events_Manager
 * @subpackage Core
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Events\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Module_Registry
 *
 * Singleton registry for managing all plugin modules.
 *
 * @since 1.0.0
 */
final class Module_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Module_Registry|null
	 */
	private static ?Module_Registry $instance = null;

	/**
	 * Registered modules.
	 *
	 * @var array<string, Module_Interface>
	 */
	private array $modules = array();

	/**
	 * Active module IDs.
	 *
	 * @var array<string>
	 */
	private array $active_modules = array();

	/**
	 * Option name for storing enabled modules.
	 *
	 * @var string
	 */
	private const OPTION_ENABLED_MODULES = 'apollo_em_modules_enabled';

	/**
	 * Option name for module settings.
	 *
	 * @var string
	 */
	private const OPTION_MODULE_SETTINGS = 'apollo_em_module_settings';

	/**
	 * Private constructor (singleton pattern).
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_active_modules();
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserialization.
	 *
	 * @since 1.0.0
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_Registry The registry instance.
	 */
	public static function get_instance(): Module_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load active modules from database.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_active_modules(): void {
		$enabled = get_option( self::OPTION_ENABLED_MODULES, array() );

		if ( is_array( $enabled ) ) {
			$this->active_modules = $enabled;
		}
	}

	/**
	 * Register a module.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_Interface $module The module instance to register.
	 *
	 * @return bool True if registered successfully.
	 */
	public function register( Module_Interface $module ): bool {
		$module_id = $module->get_id();

		// Prevent duplicate registration.
		if ( isset( $this->modules[ $module_id ] ) ) {
			$this->log( sprintf( 'Module already registered: %s', $module_id ), 'warning' );
			return false;
		}

		$this->modules[ $module_id ] = $module;

		// Auto-enable if default enabled and not yet in active list.
		if ( $module->is_default_enabled() && ! in_array( $module_id, $this->active_modules, true ) ) {
			$this->active_modules[] = $module_id;
			$this->save_active_modules();
		}

		return true;
	}

	/**
	 * Get a registered module by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return Module_Interface|null The module or null if not found.
	 */
	public function get( string $module_id ): ?Module_Interface {
		return $this->modules[ $module_id ] ?? null;
	}

	/**
	 * Get all registered modules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Module_Interface> All registered modules.
	 */
	public function get_all(): array {
		return $this->modules;
	}

	/**
	 * Get all active (enabled) modules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Module_Interface> Active modules only.
	 */
	public function get_active(): array {
		$active = array();

		foreach ( $this->modules as $id => $module ) {
			if ( $this->is_active( $id ) ) {
				$active[ $id ] = $module;
			}
		}

		return $active;
	}

	/**
	 * Check if a module is active.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return bool True if active.
	 */
	public function is_active( string $module_id ): bool {
		// Check if module exists.
		if ( ! isset( $this->modules[ $module_id ] ) ) {
			return false;
		}

		// Check if explicitly enabled.
		if ( in_array( $module_id, $this->active_modules, true ) ) {
			// Verify dependencies are met.
			return $this->dependencies_met( $module_id );
		}

		return false;
	}

	/**
	 * Check if module dependencies are met.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return bool True if all dependencies are active.
	 */
	public function dependencies_met( string $module_id ): bool {
		$module = $this->get( $module_id );

		if ( null === $module ) {
			return false;
		}

		$dependencies = $module->get_dependencies();

		foreach ( $dependencies as $dep_id ) {
			// Check if dependency is registered and in active list.
			if ( ! isset( $this->modules[ $dep_id ] ) ) {
				return false;
			}

			if ( ! in_array( $dep_id, $this->active_modules, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Activate a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id The module ID to activate.
	 *
	 * @return bool True if activated successfully.
	 */
	public function activate( string $module_id ): bool {
		$module = $this->get( $module_id );

		if ( null === $module ) {
			$this->log( sprintf( 'Cannot activate non-existent module: %s', $module_id ), 'error' );
			return false;
		}

		// Check dependencies.
		if ( ! $this->dependencies_met( $module_id ) ) {
			$this->log( sprintf( 'Cannot activate %s: dependencies not met', $module_id ), 'error' );
			return false;
		}

		// Already active.
		if ( in_array( $module_id, $this->active_modules, true ) ) {
			return true;
		}

		// Add to active list.
		$this->active_modules[] = $module_id;
		$this->save_active_modules();

		// Call module activation hook.
		$module->on_activate();

		/**
		 * Fires after a module is activated.
		 *
		 * @since 1.0.0
		 *
		 * @param string           $module_id The activated module ID.
		 * @param Module_Interface $module    The module instance.
		 */
		do_action( 'apollo_em_module_activated', $module_id, $module );

		return true;
	}

	/**
	 * Deactivate a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id The module ID to deactivate.
	 *
	 * @return bool True if deactivated successfully.
	 */
	public function deactivate( string $module_id ): bool {
		$module = $this->get( $module_id );

		if ( null === $module ) {
			return false;
		}

		// Check if other modules depend on this one.
		foreach ( $this->modules as $id => $other_module ) {
			if ( $id === $module_id ) {
				continue;
			}

			if ( in_array( $module_id, $other_module->get_dependencies(), true ) ) {
				if ( $this->is_active( $id ) ) {
					$this->log(
						sprintf( 'Cannot deactivate %s: required by %s', $module_id, $id ),
						'error'
					);
					return false;
				}
			}
		}

		// Remove from active list.
		$key = array_search( $module_id, $this->active_modules, true );

		if ( false !== $key ) {
			unset( $this->active_modules[ $key ] );
			$this->active_modules = array_values( $this->active_modules );
			$this->save_active_modules();
		}

		// Call module deactivation hook.
		$module->on_deactivate();

		/**
		 * Fires after a module is deactivated.
		 *
		 * @since 1.0.0
		 *
		 * @param string           $module_id The deactivated module ID.
		 * @param Module_Interface $module    The module instance.
		 */
		do_action( 'apollo_em_module_deactivated', $module_id, $module );

		return true;
	}

	/**
	 * Save active modules to database.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if saved successfully.
	 */
	private function save_active_modules(): bool {
		return update_option( self::OPTION_ENABLED_MODULES, $this->active_modules );
	}

	/**
	 * Get module info for admin display.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Module info array.
	 */
	public function get_modules_info(): array {
		$info = array();

		foreach ( $this->modules as $id => $module ) {
			$info[ $id ] = array(
				'id'              => $id,
				'name'            => $module->get_name(),
				'description'     => $module->get_description(),
				'version'         => $module->get_version(),
				'dependencies'    => $module->get_dependencies(),
				'default_enabled' => $module->is_default_enabled(),
				'is_active'       => $this->is_active( $id ),
				'deps_met'        => $this->dependencies_met( $id ),
				'settings_schema' => $module->get_settings_schema(),
			);
		}

		return $info;
	}

	/**
	 * Log a message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level.
	 *
	 * @return void
	 */
	private function log( string $message, string $level = 'debug' ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '[Apollo Events][Registry][%s] %s', strtoupper( $level ), $message ) );
	}

	/**
	 * Reset the registry (for testing purposes).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->modules        = array();
		$this->active_modules = array();
		self::$instance       = null;
	}
}
