<?php
/**
 * Apollo Plugin Dependency Resolver
 *
 * Ensures plugins load in correct order and validates dependencies.
 * Prevents activation of plugins with missing requirements.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Dependency_Resolver
 *
 * Manages plugin dependencies and loading order.
 */
class Apollo_Dependency_Resolver {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Dependency_Resolver|null
	 */
	private static ?Apollo_Dependency_Resolver $instance = null;

	/**
	 * Registered plugins
	 *
	 * @var array<string, array>
	 */
	private array $plugins = array();

	/**
	 * Resolved load order
	 *
	 * @var array<string>|null
	 */
	private ?array $load_order = null;

	/**
	 * Plugin status cache
	 *
	 * @var array<string, bool>
	 */
	private array $status_cache = array();

	/**
	 * Known Apollo plugins
	 */
	public const PLUGIN_CORE   = 'apollo-core';
	public const PLUGIN_EVENTS = 'apollo-events-manager';
	public const PLUGIN_SOCIAL = 'apollo-social';
	public const PLUGIN_RIO    = 'apollo-rio';

	/**
	 * Plugin file paths (relative to plugins directory)
	 */
	private const PLUGIN_FILES = array(
		self::PLUGIN_CORE   => 'apollo-core/apollo-core.php',
		self::PLUGIN_EVENTS => 'apollo-events-manager/apollo-events-manager.php',
		self::PLUGIN_SOCIAL => 'apollo-social/apollo-social.php',
		self::PLUGIN_RIO    => 'apollo-rio/apollo-rio.php',
	);

	/**
	 * Default dependencies
	 */
	private const DEFAULT_DEPENDENCIES = array(
		self::PLUGIN_CORE   => array(),
		self::PLUGIN_EVENTS => array( self::PLUGIN_CORE ),
		self::PLUGIN_SOCIAL => array( self::PLUGIN_CORE ),
		self::PLUGIN_RIO    => array( self::PLUGIN_CORE ),
	);

	/**
	 * Minimum versions required
	 */
	private const MIN_VERSIONS = array(
		self::PLUGIN_CORE   => '2.0.0',
		self::PLUGIN_EVENTS => '2.0.0',
		self::PLUGIN_SOCIAL => '2.0.0',
		self::PLUGIN_RIO    => '1.0.0',
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Dependency_Resolver
	 */
	public static function get_instance(): Apollo_Dependency_Resolver {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_default_plugins();
		$this->init_hooks();
	}

	/**
	 * Initialize default plugin definitions
	 *
	 * @return void
	 */
	private function init_default_plugins(): void {
		foreach ( self::DEFAULT_DEPENDENCIES as $plugin => $requires ) {
			$this->plugins[ $plugin ] = array(
				'slug'        => $plugin,
				'file'        => self::PLUGIN_FILES[ $plugin ] ?? '',
				'requires'    => $requires,
				'min_version' => self::MIN_VERSIONS[ $plugin ] ?? '1.0.0',
				'version'     => $this->get_plugin_version( $plugin ),
				'loaded'      => false,
			);
		}
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Validate dependencies on plugin activation.
		\add_action( 'activate_plugin', array( $this, 'on_plugin_activation' ), 1, 1 );

		// Admin notice for missing dependencies.
		\add_action( 'admin_notices', array( $this, 'admin_dependency_notices' ) );

		// Mark plugins as loaded.
		\add_action( 'apollo_core_loaded', fn() => $this->mark_loaded( self::PLUGIN_CORE ) );
		\add_action( 'apollo_events_manager_loaded', fn() => $this->mark_loaded( self::PLUGIN_EVENTS ) );
		\add_action( 'apollo_social_loaded', fn() => $this->mark_loaded( self::PLUGIN_SOCIAL ) );
		\add_action( 'apollo_rio_loaded', fn() => $this->mark_loaded( self::PLUGIN_RIO ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Static API Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Register a plugin dependency
	 *
	 * @param string $plugin   Plugin slug.
	 * @param array  $requires Array of required plugin slugs.
	 * @param string $version  Plugin version.
	 * @return void
	 */
	public static function register_dependency(
		string $plugin,
		array $requires,
		string $version = '1.0.0'
	): void {
		$instance = self::get_instance();

		if ( ! isset( $instance->plugins[ $plugin ] ) ) {
			$instance->plugins[ $plugin ] = array(
				'slug'        => $plugin,
				'file'        => '',
				'requires'    => array(),
				'min_version' => '1.0.0',
				'version'     => $version,
				'loaded'      => false,
			);
		}

		$instance->plugins[ $plugin ]['requires'] = \array_unique(
			\array_merge( $instance->plugins[ $plugin ]['requires'], $requires )
		);
		$instance->plugins[ $plugin ]['version']  = $version;

		// Invalidate cached load order.
		$instance->load_order = null;
	}

	/**
	 * Check if a plugin can be activated
	 *
	 * @param string $plugin Plugin slug.
	 * @return bool
	 */
	public static function can_activate( string $plugin ): bool {
		$missing = self::get_missing_dependencies( $plugin );
		return empty( $missing );
	}

	/**
	 * Get the correct plugin load order
	 *
	 * @return array<string>
	 */
	public static function get_load_order(): array {
		return self::get_instance()->resolve_load_order();
	}

	/**
	 * Get missing dependencies for a plugin
	 *
	 * @param string $plugin Plugin slug.
	 * @return array<string> Missing plugin slugs.
	 */
	public static function get_missing_dependencies( string $plugin ): array {
		$instance = self::get_instance();

		if ( ! isset( $instance->plugins[ $plugin ] ) ) {
			return array();
		}

		$missing  = array();
		$requires = $instance->plugins[ $plugin ]['requires'];

		foreach ( $requires as $required ) {
			if ( ! $instance->is_plugin_active( $required ) ) {
				$missing[] = $required;
			}
		}

		return $missing;
	}

	/**
	 * Check if a plugin is active
	 *
	 * @param string $plugin Plugin slug.
	 * @return bool
	 */
	public static function is_active( string $plugin ): bool {
		return self::get_instance()->is_plugin_active( $plugin );
	}

	/**
	 * Check if a plugin is loaded
	 *
	 * @param string $plugin Plugin slug.
	 * @return bool
	 */
	public static function is_loaded( string $plugin ): bool {
		$instance = self::get_instance();
		return isset( $instance->plugins[ $plugin ] ) && $instance->plugins[ $plugin ]['loaded'];
	}

	/**
	 * Get all registered plugins
	 *
	 * @return array<string, array>
	 */
	public static function get_plugins(): array {
		return self::get_instance()->plugins;
	}

	/*
	|--------------------------------------------------------------------------
	| Instance Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Resolve plugin load order using topological sort
	 *
	 * @return array<string>
	 */
	public function resolve_load_order(): array {
		if ( null !== $this->load_order ) {
			return $this->load_order;
		}

		$sorted       = array();
		$visited      = array();
		$temp_visited = array();

		foreach ( \array_keys( $this->plugins ) as $plugin ) {
			if ( ! isset( $visited[ $plugin ] ) ) {
				$this->topological_sort( $plugin, $visited, $temp_visited, $sorted );
			}
		}

		$this->load_order = $sorted;
		return $this->load_order;
	}

	/**
	 * Topological sort helper
	 *
	 * @param string $plugin       Current plugin.
	 * @param array  $visited      Permanently visited.
	 * @param array  $temp_visited Temporarily visited (cycle detection).
	 * @param array  $sorted       Sorted result.
	 * @return void
	 * @throws \RuntimeException On circular dependency.
	 */
	private function topological_sort(
		string $plugin,
		array &$visited,
		array &$temp_visited,
		array &$sorted
	): void {
		if ( isset( $temp_visited[ $plugin ] ) ) {
			throw new \RuntimeException(
				\sprintf(
					'Circular dependency detected involving plugin: %s',
					$plugin
				)
			);
		}

		if ( isset( $visited[ $plugin ] ) ) {
			return;
		}

		$temp_visited[ $plugin ] = true;

		if ( isset( $this->plugins[ $plugin ] ) ) {
			foreach ( $this->plugins[ $plugin ]['requires'] as $required ) {
				$this->topological_sort( $required, $visited, $temp_visited, $sorted );
			}
		}

		unset( $temp_visited[ $plugin ] );
		$visited[ $plugin ] = true;
		$sorted[]           = $plugin;
	}

	/**
	 * Check if a plugin is active
	 *
	 * @param string $plugin Plugin slug.
	 * @return bool
	 */
	public function is_plugin_active( string $plugin ): bool {
		// Check cache.
		if ( isset( $this->status_cache[ $plugin ] ) ) {
			return $this->status_cache[ $plugin ];
		}

		// Check by class existence.
		$class_checks = array(
			self::PLUGIN_CORE   => 'Apollo_Core',
			self::PLUGIN_EVENTS => 'Apollo_Events_Manager',
			self::PLUGIN_SOCIAL => 'Apollo\\Plugin',
			self::PLUGIN_RIO    => 'Apollo_Rio',
		);

		if ( isset( $class_checks[ $plugin ] ) && \class_exists( $class_checks[ $plugin ] ) ) {
			$this->status_cache[ $plugin ] = true;
			return true;
		}

		// Check by plugin file.
		if ( isset( self::PLUGIN_FILES[ $plugin ] ) ) {
			$is_active                     = \is_plugin_active( self::PLUGIN_FILES[ $plugin ] );
			$this->status_cache[ $plugin ] = $is_active;
			return $is_active;
		}

		// Check by constant.
		$constant_checks = array(
			self::PLUGIN_CORE   => 'APOLLO_CORE_VERSION',
			self::PLUGIN_EVENTS => 'APOLLO_EVENTS_MANAGER_VERSION',
			self::PLUGIN_SOCIAL => 'APOLLO_SOCIAL_VERSION',
			self::PLUGIN_RIO    => 'APOLLO_RIO_VERSION',
		);

		if ( isset( $constant_checks[ $plugin ] ) && \defined( $constant_checks[ $plugin ] ) ) {
			$this->status_cache[ $plugin ] = true;
			return true;
		}

		$this->status_cache[ $plugin ] = false;
		return false;
	}

	/**
	 * Get plugin version
	 *
	 * @param string $plugin Plugin slug.
	 * @return string
	 */
	private function get_plugin_version( string $plugin ): string {
		$constant_map = array(
			self::PLUGIN_CORE   => 'APOLLO_CORE_VERSION',
			self::PLUGIN_EVENTS => 'APOLLO_EVENTS_MANAGER_VERSION',
			self::PLUGIN_SOCIAL => 'APOLLO_SOCIAL_VERSION',
			self::PLUGIN_RIO    => 'APOLLO_RIO_VERSION',
		);

		if ( isset( $constant_map[ $plugin ] ) && \defined( $constant_map[ $plugin ] ) ) {
			return \constant( $constant_map[ $plugin ] );
		}

		return '0.0.0';
	}

	/**
	 * Mark a plugin as loaded
	 *
	 * @param string $plugin Plugin slug.
	 * @return void
	 */
	public function mark_loaded( string $plugin ): void {
		if ( isset( $this->plugins[ $plugin ] ) ) {
			$this->plugins[ $plugin ]['loaded'] = true;

			// Emit event.
			if ( \class_exists( Apollo_Event_Bus::class ) ) {
				Apollo_Event_Bus::emit(
					Apollo_Event_Bus::PLUGIN_ACTIVATED,
					array(
						'plugin'  => $plugin,
						'version' => $this->plugins[ $plugin ]['version'],
					)
				);
			}
		}
	}

	/**
	 * Handle plugin activation
	 *
	 * @param string $plugin Plugin file path.
	 * @return void
	 */
	public function on_plugin_activation( string $plugin ): void {
		// Find plugin slug from file.
		$slug = \array_search( $plugin, self::PLUGIN_FILES, true );

		if ( false === $slug ) {
			return;
		}

		$missing = self::get_missing_dependencies( $slug );

		if ( ! empty( $missing ) ) {
			// Deactivate and show error.
			\deactivate_plugins( $plugin );

			\wp_die(
				\sprintf(
					/* translators: 1: Plugin name, 2: Missing plugins */
					\esc_html__( '%1$s requires the following plugins to be active: %2$s. Please activate them first.', 'apollo-core' ),
					\esc_html( $slug ),
					\esc_html( \implode( ', ', $missing ) )
				),
				\esc_html__( 'Plugin Activation Error', 'apollo-core' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Show admin notices for missing dependencies
	 *
	 * @return void
	 */
	public function admin_dependency_notices(): void {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return;
		}

		foreach ( $this->plugins as $slug => $plugin ) {
			if ( ! $this->is_plugin_active( $slug ) ) {
				continue;
			}

			$missing = self::get_missing_dependencies( $slug );

			if ( ! empty( $missing ) ) {
				\printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					\sprintf(
						/* translators: 1: Plugin name, 2: Missing plugins */
						\esc_html__( '%1$s is missing required dependencies: %2$s. Some features may not work correctly.', 'apollo-core' ),
						\esc_html( $this->get_plugin_name( $slug ) ),
						\esc_html( \implode( ', ', \array_map( array( $this, 'get_plugin_name' ), $missing ) ) )
					)
				);
			}
		}
	}

	/**
	 * Get human-readable plugin name
	 *
	 * @param string $slug Plugin slug.
	 * @return string
	 */
	private function get_plugin_name( string $slug ): string {
		$names = array(
			self::PLUGIN_CORE   => 'Apollo Core',
			self::PLUGIN_EVENTS => 'Apollo Events Manager',
			self::PLUGIN_SOCIAL => 'Apollo Social',
			self::PLUGIN_RIO    => 'Apollo Rio',
		);

		return $names[ $slug ] ?? $slug;
	}

	/**
	 * Check version compatibility
	 *
	 * @param string $plugin      Plugin slug.
	 * @param string $min_version Minimum version required.
	 * @return bool
	 */
	public function check_version( string $plugin, string $min_version ): bool {
		if ( ! isset( $this->plugins[ $plugin ] ) ) {
			return false;
		}

		return \version_compare(
			$this->plugins[ $plugin ]['version'],
			$min_version,
			'>='
		);
	}

	/**
	 * Get dependency graph for debugging
	 *
	 * @return array
	 */
	public function get_dependency_graph(): array {
		$graph = array();

		foreach ( $this->plugins as $slug => $plugin ) {
			$graph[ $slug ] = array(
				'requires'    => $plugin['requires'],
				'required_by' => array(),
				'active'      => $this->is_plugin_active( $slug ),
				'loaded'      => $plugin['loaded'],
				'version'     => $plugin['version'],
			);
		}

		// Calculate required_by.
		foreach ( $this->plugins as $slug => $plugin ) {
			foreach ( $plugin['requires'] as $required ) {
				if ( isset( $graph[ $required ] ) ) {
					$graph[ $required ]['required_by'][] = $slug;
				}
			}
		}

		return $graph;
	}

	/**
	 * Validate entire dependency tree
	 *
	 * @return array Validation results.
	 */
	public function validate(): array {
		$results = array(
			'valid'    => true,
			'errors'   => array(),
			'warnings' => array(),
		);

		foreach ( $this->plugins as $slug => $plugin ) {
			if ( ! $this->is_plugin_active( $slug ) ) {
				continue;
			}

			// Check dependencies.
			$missing = self::get_missing_dependencies( $slug );
			if ( ! empty( $missing ) ) {
				$results['valid']    = false;
				$results['errors'][] = \sprintf(
					'%s is missing dependencies: %s',
					$slug,
					\implode( ', ', $missing )
				);
			}

			// Check versions.
			foreach ( $plugin['requires'] as $required ) {
				if ( isset( self::MIN_VERSIONS[ $required ] ) ) {
					if ( ! $this->check_version( $required, self::MIN_VERSIONS[ $required ] ) ) {
						$results['warnings'][] = \sprintf(
							'%s requires %s version %s or higher',
							$slug,
							$required,
							self::MIN_VERSIONS[ $required ]
						);
					}
				}
			}
		}

		return $results;
	}
}

// Initialize resolver.
Apollo_Dependency_Resolver::get_instance();
