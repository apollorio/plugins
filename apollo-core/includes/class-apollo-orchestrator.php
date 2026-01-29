<?php
/**
 * Apollo Plugin Orchestrator
 *
 * Controls the loading order and initialization of all Apollo plugins.
 * Ensures proper dependency resolution and coordinated startup.
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
 * Class Apollo_Orchestrator
 *
 * Orchestrates plugin loading and initialization.
 */
class Apollo_Orchestrator {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Loaded plugins registry.
	 *
	 * @var array<string, array>
	 */
	private static array $loaded_plugins = array();

	/**
	 * Plugin initialization status.
	 *
	 * @var array<string, string>
	 */
	private static array $init_status = array();

	/**
	 * Boot timestamp.
	 *
	 * @var float
	 */
	private static float $boot_time = 0;

	/**
	 * Plugin definitions.
	 *
	 * @var array<string, array>
	 */
	private const PLUGIN_DEFINITIONS = array(
		'apollo-core'           => array(
			'file'         => 'apollo-core/apollo-core.php',
			'class'        => 'Apollo_Core',
			'dependencies' => array(),
			'priority'     => 1,
			'provides'     => array( 'core', 'rest-base', 'relationships', 'event-bus' ),
		),
		'apollo-events-manager' => array(
			'file'         => 'apollo-events-manager/apollo-events-manager.php',
			'class'        => 'Apollo_Events_Manager',
			'dependencies' => array( 'apollo-core' ),
			'priority'     => 5,
			'provides'     => array( 'events', 'djs', 'locals', 'calendar' ),
		),
		'apollo-social'         => array(
			'file'         => 'apollo-social/apollo-social.php',
			'class'        => 'Apollo_Social',
			'dependencies' => array( 'apollo-core' ),
			'priority'     => 5,
			'provides'     => array( 'social', 'activity', 'groups', 'chat', 'classifieds' ),
		),
		'apollo-rio'            => array(
			'file'         => 'apollo-rio/apollo-rio.php',
			'class'        => 'Apollo_Rio',
			'dependencies' => array( 'apollo-core', 'apollo-events-manager' ),
			'priority'     => 20,
			'provides'     => array( 'pwa', 'offline', 'push-notifications' ),
		),
	);

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot the orchestrator.
	 *
	 * @return void
	 */
	public static function boot(): void {
		self::$boot_time = \microtime( true );

		// 1. Load core first.
		self::load_core();

		// 2. Detect active companion plugins.
		$companions = self::detect_companions();

		// 3. Resolve load order based on dependencies.
		$load_order = self::resolve_load_order( $companions );

		// 4. Load plugins in order.
		foreach ( $load_order as $plugin_slug ) {
			self::load_plugin( $plugin_slug );
		}

		// 5. Fire ready hooks.
		\add_action( 'init', array( self::class, 'on_init' ), 0 );
		\add_action( 'plugins_loaded', array( self::class, 'on_plugins_loaded' ), 999 );
	}

	/**
	 * Load Apollo Core.
	 *
	 * @return void
	 */
	private static function load_core(): void {
		$core_loaded = false;

		// Check if core class exists (already loaded by WordPress).
		if ( \class_exists( 'Apollo_Core\\Apollo_Core', false ) ) {
			$core_loaded = true;
		}

		// Load core components.
		self::load_core_components();

		self::$loaded_plugins['apollo-core'] = array(
			'loaded_at' => \microtime( true ),
			'version'   => APOLLO_CORE_VERSION ?? '2.0.0',
			'status'    => 'loaded',
		);

		self::$init_status['apollo-core'] = 'loaded';
	}

	/**
	 * Load core components in order.
	 *
	 * @return void
	 */
	private static function load_core_components(): void {
		$components = array(
			// Base classes first.
			'class-apollo-hook-priorities',
			'class-apollo-event-bus',
			'class-apollo-dependency-resolver',
			// Schema classes.
			'class-apollo-relationships',
			'class-apollo-relationship-query',
			'class-apollo-relationship-rest',
			'class-apollo-relationship-integrity',
			// REST API.
			'class-apollo-rest-namespace',
			'class-apollo-rest-registry',
			'class-apollo-rest-compat',
			'class-apollo-rest-response',
		);

		$base_path = \dirname( __DIR__ ) . '/includes/';

		foreach ( $components as $component ) {
			$file = $base_path . $component . '.php';
			if ( \file_exists( $file ) && ! \class_exists( self::get_class_from_file( $component ), false ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Get class name from file name.
	 *
	 * @param string $file_name File name without extension.
	 * @return string Class name.
	 */
	private static function get_class_from_file( string $file_name ): string {
		// Remove 'class-' prefix and convert to class name.
		$class = \str_replace( 'class-', '', $file_name );
		$class = \str_replace( '-', '_', $class );
		$class = \ucwords( $class, '_' );
		return 'Apollo_Core\\' . $class;
	}

	/**
	 * Detect active companion plugins.
	 *
	 * @return array<string> Array of active plugin slugs.
	 */
	public static function detect_companions(): array {
		$active_plugins = \get_option( 'active_plugins', array() );
		$companions     = array();

		foreach ( self::PLUGIN_DEFINITIONS as $slug => $definition ) {
			if ( $slug === 'apollo-core' ) {
				continue; // Core is handled separately.
			}

			$plugin_file = $definition['file'];

			// Check if plugin is active.
			foreach ( $active_plugins as $active_plugin ) {
				if ( \strpos( $active_plugin, $slug ) !== false ) {
					$companions[] = $slug;
					break;
				}
			}
		}

		return $companions;
	}

	/**
	 * Resolve plugin load order based on dependencies.
	 *
	 * @param array $plugins Array of plugin slugs.
	 * @return array Ordered array of plugin slugs.
	 */
	public static function resolve_load_order( array $plugins ): array {
		if ( empty( $plugins ) ) {
			return array();
		}

		// Build dependency graph.
		$graph     = array();
		$in_degree = array();

		foreach ( $plugins as $plugin ) {
			if ( ! isset( self::PLUGIN_DEFINITIONS[ $plugin ] ) ) {
				continue;
			}

			$definition           = self::PLUGIN_DEFINITIONS[ $plugin ];
			$graph[ $plugin ]     = array();
			$in_degree[ $plugin ] = 0;

			foreach ( $definition['dependencies'] as $dep ) {
				if ( $dep === 'apollo-core' ) {
					continue; // Core is always loaded first.
				}
				if ( \in_array( $dep, $plugins, true ) ) {
					$graph[ $dep ][] = $plugin;
					++$in_degree[ $plugin ];
				}
			}
		}

		// Topological sort (Kahn's algorithm).
		$queue = array();
		foreach ( $in_degree as $plugin => $degree ) {
			if ( 0 === $degree ) {
				$queue[] = $plugin;
			}
		}

		$result = array();
		while ( ! empty( $queue ) ) {
			// Sort by priority.
			\usort(
				$queue,
				function ( $a, $b ) {
					$priority_a = self::PLUGIN_DEFINITIONS[ $a ]['priority'] ?? 10;
					$priority_b = self::PLUGIN_DEFINITIONS[ $b ]['priority'] ?? 10;
					return $priority_a <=> $priority_b;
				}
			);

			$plugin   = \array_shift( $queue );
			$result[] = $plugin;

			foreach ( $graph[ $plugin ] ?? array() as $dependent ) {
				--$in_degree[ $dependent ];
				if ( 0 === $in_degree[ $dependent ] ) {
					$queue[] = $dependent;
				}
			}
		}

		// Check for circular dependencies.
		if ( \count( $result ) !== \count( $plugins ) ) {
			$missing = \array_diff( $plugins, $result );
			\error_log( 'Apollo Orchestrator: Circular dependency detected in: ' . \implode( ', ', $missing ) );
		}

		return $result;
	}

	/**
	 * Load a specific plugin.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool Success.
	 */
	public static function load_plugin( string $slug ): bool {
		if ( ! isset( self::PLUGIN_DEFINITIONS[ $slug ] ) ) {
			return false;
		}

		if ( isset( self::$loaded_plugins[ $slug ] ) ) {
			return true; // Already loaded.
		}

		$definition = self::PLUGIN_DEFINITIONS[ $slug ];

		// Check dependencies.
		foreach ( $definition['dependencies'] as $dep ) {
			if ( ! isset( self::$loaded_plugins[ $dep ] ) ) {
				self::$init_status[ $slug ] = 'missing_dependency:' . $dep;
				return false;
			}
		}

		// Mark as loading.
		self::$init_status[ $slug ] = 'loading';

		// Fire pre-load hook.
		\do_action( 'apollo_before_load_plugin', $slug, $definition );

		// Record load.
		self::$loaded_plugins[ $slug ] = array(
			'loaded_at'  => \microtime( true ),
			'definition' => $definition,
			'status'     => 'loaded',
		);

		self::$init_status[ $slug ] = 'loaded';

		// Fire post-load hook.
		\do_action( 'apollo_after_load_plugin', $slug, $definition );
		\do_action( "apollo_{$slug}_loaded" );

		return true;
	}

	/**
	 * On init hook.
	 *
	 * @return void
	 */
	public static function on_init(): void {
		// Initialize all loaded plugins.
		foreach ( self::$loaded_plugins as $slug => $info ) {
			\do_action( "apollo_{$slug}_init" );
		}

		// Fire global init.
		\do_action( 'apollo_init', self::$loaded_plugins );
	}

	/**
	 * On plugins_loaded hook.
	 *
	 * @return void
	 */
	public static function on_plugins_loaded(): void {
		$boot_duration = \microtime( true ) - self::$boot_time;

		// Fire ready hook.
		\do_action(
			'apollo_all_plugins_loaded',
			array(
				'plugins'       => self::$loaded_plugins,
				'boot_duration' => $boot_duration,
			)
		);

		// Log boot time in debug mode.
		if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			\error_log(
				\sprintf(
					'Apollo Orchestrator: Booted %d plugins in %.4f seconds',
					\count( self::$loaded_plugins ),
					$boot_duration
				)
			);
		}
	}

	/**
	 * Get loaded plugins.
	 *
	 * @return array
	 */
	public static function get_loaded_plugins(): array {
		return self::$loaded_plugins;
	}

	/**
	 * Get initialization status.
	 *
	 * @return array
	 */
	public static function get_init_status(): array {
		return self::$init_status;
	}

	/**
	 * Check if a plugin is loaded.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public static function is_loaded( string $slug ): bool {
		return isset( self::$loaded_plugins[ $slug ] );
	}

	/**
	 * Check if a feature is available.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public static function has_feature( string $feature ): bool {
		foreach ( self::$loaded_plugins as $slug => $info ) {
			$definition = self::PLUGIN_DEFINITIONS[ $slug ] ?? array();
			$provides   = $definition['provides'] ?? array();

			if ( \in_array( $feature, $provides, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get plugin providing a feature.
	 *
	 * @param string $feature Feature name.
	 * @return string|null Plugin slug or null.
	 */
	public static function get_feature_provider( string $feature ): ?string {
		foreach ( self::$loaded_plugins as $slug => $info ) {
			$definition = self::PLUGIN_DEFINITIONS[ $slug ] ?? array();
			$provides   = $definition['provides'] ?? array();

			if ( \in_array( $feature, $provides, true ) ) {
				return $slug;
			}
		}

		return null;
	}

	/**
	 * Get all available features.
	 *
	 * @return array<string>
	 */
	public static function get_available_features(): array {
		$features = array();

		foreach ( self::$loaded_plugins as $slug => $info ) {
			$definition = self::PLUGIN_DEFINITIONS[ $slug ] ?? array();
			$provides   = $definition['provides'] ?? array();
			$features   = \array_merge( $features, $provides );
		}

		return \array_unique( $features );
	}

	/**
	 * Get boot statistics.
	 *
	 * @return array
	 */
	public static function get_boot_stats(): array {
		$stats = array(
			'total_plugins' => \count( self::$loaded_plugins ),
			'boot_time'     => self::$boot_time,
			'boot_duration' => \microtime( true ) - self::$boot_time,
			'plugins'       => array(),
			'features'      => self::get_available_features(),
			'init_status'   => self::$init_status,
		);

		foreach ( self::$loaded_plugins as $slug => $info ) {
			$stats['plugins'][ $slug ] = array(
				'loaded_at' => $info['loaded_at'] - self::$boot_time,
				'status'    => $info['status'],
			);
		}

		return $stats;
	}

	/**
	 * Register a custom plugin.
	 *
	 * @param string $slug       Plugin slug.
	 * @param array  $definition Plugin definition.
	 * @return void
	 */
	public static function register_plugin( string $slug, array $definition ): void {
		// This allows third-party plugins to integrate.
		\add_filter(
			'apollo_plugin_definitions',
			function ( $definitions ) use ( $slug, $definition ) {
				$definitions[ $slug ] = \wp_parse_args(
					$definition,
					array(
						'file'         => '',
						'class'        => '',
						'dependencies' => array( 'apollo-core' ),
						'priority'     => 50,
						'provides'     => array(),
					)
				);
				return $definitions;
			}
		);
	}

	/**
	 * Get plugin definition.
	 *
	 * @param string $slug Plugin slug.
	 * @return array|null
	 */
	public static function get_plugin_definition( string $slug ): ?array {
		$definitions = \apply_filters( 'apollo_plugin_definitions', self::PLUGIN_DEFINITIONS );
		return $definitions[ $slug ] ?? null;
	}

	/**
	 * Get all plugin definitions.
	 *
	 * @return array
	 */
	public static function get_all_definitions(): array {
		return \apply_filters( 'apollo_plugin_definitions', self::PLUGIN_DEFINITIONS );
	}
}
