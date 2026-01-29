<?php
/**
 * Module Bootloader
 *
 * Handles the loading and initialization of all active modules.
 * This is the main entry point for the modular architecture.
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
 * Class Bootloader
 *
 * Boots the modular system and initializes active modules.
 *
 * @since 1.0.0
 */
final class Bootloader {

	/**
	 * Singleton instance.
	 *
	 * @var Bootloader|null
	 */
	private static ?Bootloader $instance = null;

	/**
	 * Module registry instance.
	 *
	 * @var Module_Registry
	 */
	private Module_Registry $registry;

	/**
	 * Whether the bootloader has run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Private constructor (singleton pattern).
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->registry = Module_Registry::get_instance();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Bootloader The bootloader instance.
	 */
	public static function get_instance(): Bootloader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the modular system.
	 *
	 * This should be called once during plugin initialization.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		// Register core modules.
		$this->register_core_modules();

		// Allow third-party modules to register.
		$this->allow_external_modules();

		// Initialize active modules.
		$this->initialize_active_modules();

		// Register WordPress hooks for modules.
		$this->register_module_hooks();

		$this->log( 'Bootloader complete.' );
	}

	/**
	 * Register core modules that ship with the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_core_modules(): void {
		$modules_dir = APOLLO_APRIO_PATH . 'includes/modules/';

		// Define core modules to load.
		$core_modules = array(
			'core-events'   => 'Core_Events_Module',
			'rest-api'      => 'REST_API_Module',
			'interest'      => 'Interest_Module',
			'calendar'      => 'Calendar_Module',
			'filter-bar'    => 'Filter_Bar_Module',
			'lists'         => 'Lists_Module',
			'speakers'      => 'Speakers_Module',
			'reviews'       => 'Reviews_Module',
			'tickets'       => 'Tickets_Module',
			'photos'        => 'Photos_Module',
			'community'     => 'Community_Module',
			'notifications' => 'Notifications_Module',
		);

		foreach ( $core_modules as $module_id => $class_name ) {
			$file_path = $modules_dir . $module_id . '/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

			if ( file_exists( $file_path ) ) {
				require_once $file_path;

				$full_class_name = 'Apollo\\Events\\Modules\\' . $class_name;

				if ( class_exists( $full_class_name ) ) {
					$module = new $full_class_name();

					if ( $module instanceof Module_Interface ) {
						$this->registry->register( $module );
					}
				}
			}
		}

		/**
		 * Fires after core modules are registered.
		 *
		 * @since 1.0.0
		 *
		 * @param Module_Registry $registry The module registry.
		 */
		do_action( 'apollo_em_core_modules_registered', $this->registry );
	}

	/**
	 * Allow external/third-party modules to register.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function allow_external_modules(): void {
		/**
		 * Fires when external modules can register themselves.
		 *
		 * Third-party plugins can hook here to add their own modules.
		 *
		 * @since 1.0.0
		 *
		 * @param Module_Registry $registry The module registry.
		 */
		do_action( 'apollo_em_register_modules', $this->registry );
	}

	/**
	 * Initialize all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function initialize_active_modules(): void {
		$active_modules = $this->registry->get_active();

		foreach ( $active_modules as $module_id => $module ) {
			try {
				$module->init();
				$this->log( sprintf( 'Module initialized: %s', $module_id ) );
			} catch ( \Exception $e ) {
				$this->log(
					sprintf( 'Failed to initialize module %s: %s', $module_id, $e->getMessage() ),
					'error'
				);
			}
		}

		/**
		 * Fires after all active modules are initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, Module_Interface> $active_modules The active modules.
		 */
		do_action( 'apollo_em_modules_initialized', $active_modules );
	}

	/**
	 * Register WordPress hooks for module lifecycle.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_module_hooks(): void {
		// Register assets on appropriate hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 20 );

		// Register REST routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register shortcodes.
		add_action( 'init', array( $this, 'register_shortcodes' ), 15 );

		// Register blocks.
		add_action( 'init', array( $this, 'register_blocks' ), 15 );

		// Register metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ) );
	}

	/**
	 * Register frontend assets for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_frontend_assets(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->register_assets();
		}
	}

	/**
	 * Enqueue frontend assets for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->enqueue_assets( 'frontend' );
		}
	}

	/**
	 * Register admin assets for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_assets(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->register_assets();
		}
	}

	/**
	 * Enqueue admin assets for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->enqueue_assets( 'admin' );
		}
	}

	/**
	 * Register REST routes for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->register_rest_routes();
		}
	}

	/**
	 * Register shortcodes for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->register_shortcodes();
		}
	}

	/**
	 * Register Gutenberg blocks for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->register_blocks();
		}
	}

	/**
	 * Register metaboxes for all active modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		foreach ( $this->registry->get_active() as $module ) {
			$module->register_metaboxes();
		}
	}

	/**
	 * Get the module registry.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_Registry The registry instance.
	 */
	public function get_registry(): Module_Registry {
		return $this->registry;
	}

	/**
	 * Check if a module is active.
	 *
	 * Helper method for quick module status checks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id The module ID to check.
	 *
	 * @return bool True if active.
	 */
	public function is_module_active( string $module_id ): bool {
		return $this->registry->is_active( $module_id );
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

		if ( ! defined( 'APOLLO_DEBUG' ) || ! APOLLO_DEBUG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '[Apollo Events][Bootloader][%s] %s', strtoupper( $level ), $message ) );
	}
}
