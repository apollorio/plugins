<?php
/**
 * Apollo CPT Registry
 *
 * Centralized registry for Custom Post Type registration across Apollo plugins.
 * Prevents duplicate registrations and manages CPT ownership.
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
 * Class Apollo_CPT_Registry
 *
 * Singleton that tracks which plugin registered each CPT.
 * Prevents duplicate registration and provides fallback mechanism.
 */
class Apollo_CPT_Registry {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_CPT_Registry|null
	 */
	private static ?Apollo_CPT_Registry $instance = null;

	/**
	 * Registered CPTs with their owners
	 *
	 * @var array<string, array{owner: string, priority: int, args: array}>
	 */
	private array $registered_cpts = array();

	/**
	 * CPT registration queue (before init hook)
	 *
	 * @var array<string, array{owner: string, priority: int, args: array, taxonomies: array}>
	 */
	private array $registration_queue = array();

	/**
	 * Plugin priority map (higher = takes precedence)
	 *
	 * @var array<string, int>
	 */
	private const PLUGIN_PRIORITIES = array(
		'apollo-events-manager' => 100,
		'apollo-social'         => 90,
		'apollo-rio'            => 80,
		'apollo-core'           => 10, // Fallback priority.
	);

	/**
	 * Canonical CPT configurations
	 *
	 * @var array<string, array{primary_owner: string, fallback_owner: string}>
	 */
	private const CPT_OWNERSHIP = array(
		'event_listing'      => array(
			'primary_owner'  => 'apollo-events-manager',
			'fallback_owner' => 'apollo-core',
		),
		'event_dj'           => array(
			'primary_owner'  => 'apollo-events-manager',
			'fallback_owner' => null, // No fallback - Events Manager only.
		),
		'event_local'        => array(
			'primary_owner'  => 'apollo-events-manager',
			'fallback_owner' => null,
		),
		'apollo_social_post' => array(
			'primary_owner'  => 'apollo-social',
			'fallback_owner' => 'apollo-core',
		),
		'user_page'          => array(
			'primary_owner'  => 'apollo-social',
			'fallback_owner' => 'apollo-core',
		),
		'apollo_supplier'    => array(
			'primary_owner'  => 'apollo-social',
			'fallback_owner' => null, // Canonical name - no fallback.
		),
		'apollo_classified'  => array(
			'primary_owner'  => 'apollo-social',
			'fallback_owner' => null,
		),
		'apollo_document'    => array(
			'primary_owner'  => 'apollo-social',
			'fallback_owner' => null,
		),
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_CPT_Registry
	 */
	public static function get_instance(): Apollo_CPT_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton)
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Process registration queue at init priority 0 (before normal registrations).
		\add_action( 'init', array( $this, 'process_registration_queue' ), 0 );

		// Cleanup duplicate registrations after all plugins have registered.
		\add_action( 'init', array( $this, 'validate_registrations' ), 999 );

		// Log on shutdown for debugging.
		if ( \defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
			\add_action( 'shutdown', array( $this, 'debug_log_registrations' ) );
		}
	}

	/**
	 * Register a CPT through the registry
	 *
	 * @param string $post_type  Post type slug.
	 * @param array  $args       Registration arguments.
	 * @param string $owner      Plugin identifier.
	 * @param int    $priority   Optional. Override priority.
	 * @param array  $taxonomies Optional. Associated taxonomies.
	 * @return bool True if queued/registered, false if blocked.
	 */
	public function register(
		string $post_type,
		array $args,
		string $owner,
		?int $priority = null,
		array $taxonomies = array()
	): bool {
		// Use new service discovery for critical CPTs
		if ( $this->should_use_service_discovery( $post_type ) ) {
			return $this->register_via_service_discovery( $post_type, $args, $owner );
		}

		// Fallback to legacy priority-based registration
		return $this->register_via_priority( $post_type, $args, $owner, $priority, $taxonomies );
	}

	/**
	 * Check if CPT should use service discovery
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	private function should_use_service_discovery( string $post_type ): bool {
		$service_discovery_cpts = array( 'event_listing', 'event_dj', 'event_local', 'apollo_social_post', 'user_page' );
		return in_array( $post_type, $service_discovery_cpts, true );
	}

	/**
	 * Register CPT via service discovery
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $args      Registration arguments.
	 * @param string $owner     Plugin owner.
	 * @return bool
	 */
	private function register_via_service_discovery( string $post_type, array $args, string $owner ): bool {
		if ( ! class_exists( CPT_Service_Discovery_Factory::class ) ) {
			require_once __DIR__ . '/class-apollo-service-discovery.php';
		}

		$result = CPT_Service_Discovery_Factory::register_cpt( $post_type, $args, $owner );

		if ( $result ) {
			$this->registered_cpts[ $post_type ] = array(
				'owner'    => $owner,
				'priority' => 100, // Service discovery gets highest priority
				'args'     => $args,
			);
		}

		return $result;
	}

	/**
	 * Legacy priority-based registration
	 *
	 * @param string $post_type  Post type slug.
	 * @param array  $args       Registration arguments.
	 * @param string $owner      Plugin owner.
	 * @param int    $priority   Priority.
	 * @param array  $taxonomies Taxonomies.
	 * @return bool
	 */
	private function register_via_priority( string $post_type, array $args, string $owner, ?int $priority, array $taxonomies ): bool {
		// Determine priority.
		$priority = $priority ?? ( self::PLUGIN_PRIORITIES[ $owner ] ?? 50 );

		// Check if this CPT should be registered by this owner.
		if ( ! $this->should_register( $post_type, $owner ) ) {
			$this->log(
				\sprintf(
					'CPT "%s" blocked for owner "%s" - primary owner is active.',
					$post_type,
					$owner
				)
			);
			return false;
		}

		// Check if already registered with higher priority.
		if ( isset( $this->registration_queue[ $post_type ] ) ) {
			$existing_priority = $this->registration_queue[ $post_type ]['priority'];
			if ( $existing_priority >= $priority ) {
				$this->log(
					\sprintf(
						'CPT "%s" already queued with higher priority (%d >= %d).',
						$post_type,
						$existing_priority,
						$priority
					)
				);
				return false;
			}
		}

		// Queue for registration.
		$this->registration_queue[ $post_type ] = array(
			'owner'      => $owner,
			'priority'   => $priority,
			'args'       => $args,
			'taxonomies' => $taxonomies,
		);

		$this->log(
			\sprintf(
				'CPT "%s" queued by "%s" with priority %d.',
				$post_type,
				$owner,
				$priority
			)
		);

		return true;
	}

	/**
	 * Check if a CPT should be registered by the given owner
	 *
	 * @param string $post_type Post type slug.
	 * @param string $owner     Plugin identifier.
	 * @return bool
	 */
	public function should_register( string $post_type, string $owner ): bool {
		// If no ownership defined, allow registration.
		if ( ! isset( self::CPT_OWNERSHIP[ $post_type ] ) ) {
			return true;
		}

		$ownership = self::CPT_OWNERSHIP[ $post_type ];

		// If this is the primary owner, always allow.
		if ( $ownership['primary_owner'] === $owner ) {
			return true;
		}

		// If this is the fallback owner, only allow if primary is not active.
		if ( $ownership['fallback_owner'] === $owner ) {
			return ! $this->is_plugin_active( $ownership['primary_owner'] );
		}

		// Unknown owner - block registration.
		return false;
	}

	/**
	 * Check if a companion plugin is active
	 *
	 * @param string $plugin_slug Plugin identifier.
	 * @return bool
	 */
	public function is_plugin_active( string $plugin_slug ): bool {
		if ( ! \function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_files = array(
			'apollo-events-manager' => 'apollo-events-manager/apollo-events-manager.php',
			'apollo-social'         => 'apollo-social/apollo-social.php',
			'apollo-rio'            => 'apollo-rio/apollo-rio.php',
			'apollo-core'           => 'apollo-core/apollo-core.php',
		);

		if ( ! isset( $plugin_files[ $plugin_slug ] ) ) {
			return false;
		}

		// Check by constant first (faster).
		$constants = array(
			'apollo-events-manager' => 'APOLLO_EVENTS_VERSION',
			'apollo-social'         => 'APOLLO_SOCIAL_VERSION',
			'apollo-rio'            => 'APOLLO_RIO_VERSION',
			'apollo-core'           => 'APOLLO_CORE_VERSION',
		);

		if ( isset( $constants[ $plugin_slug ] ) && \defined( $constants[ $plugin_slug ] ) ) {
			return true;
		}

		return \is_plugin_active( $plugin_files[ $plugin_slug ] );
	}

	/**
	 * Process the registration queue
	 *
	 * @return void
	 */
	public function process_registration_queue(): void {
		// Sort by priority (highest first).
		\uasort(
			$this->registration_queue,
			fn( $a, $b ) => $b['priority'] <=> $a['priority']
		);

		foreach ( $this->registration_queue as $post_type => $config ) {
			// Skip if already registered (by WordPress or another mechanism).
			if ( \post_type_exists( $post_type ) ) {
				$this->log(
					\sprintf(
						'CPT "%s" already exists, skipping registration.',
						$post_type
					)
				);
				continue;
			}

			// Register the post type.
			$result = \register_post_type( $post_type, $config['args'] );

			if ( ! \is_wp_error( $result ) ) {
				$this->registered_cpts[ $post_type ] = array(
					'owner'    => $config['owner'],
					'priority' => $config['priority'],
					'args'     => $config['args'],
				);

				// Register associated taxonomies.
				foreach ( $config['taxonomies'] as $taxonomy => $tax_args ) {
					if ( ! \taxonomy_exists( $taxonomy ) ) {
						\register_taxonomy( $taxonomy, $post_type, $tax_args );
					}
				}

				$this->log(
					\sprintf(
						'✅ CPT "%s" registered by "%s".',
						$post_type,
						$config['owner']
					)
				);
			} else {
				$this->log(
					\sprintf(
						'❌ CPT "%s" registration failed: %s',
						$post_type,
						$result->get_error_message()
					),
					'error'
				);
			}
		}

		// Clear the queue.
		$this->registration_queue = array();
	}

	/**
	 * Validate registrations and warn about conflicts
	 *
	 * @return void
	 */
	public function validate_registrations(): void {
		// Check for legacy 'supplier' CPT (should be 'apollo_supplier').
		if ( \post_type_exists( 'supplier' ) && \post_type_exists( 'apollo_supplier' ) ) {
			$this->log(
				'⚠️ CONFLICT: Both "supplier" and "apollo_supplier" CPTs exist. ' .
				'Consider migrating to "apollo_supplier" only.',
				'warning'
			);
		}

		// Check for any CPTs that shouldn't exist.
		$unexpected = array();
		foreach ( self::CPT_OWNERSHIP as $post_type => $ownership ) {
			if ( \post_type_exists( $post_type ) ) {
				$owner = $this->get_owner( $post_type );
				if ( $owner !== $ownership['primary_owner'] && $this->is_plugin_active( $ownership['primary_owner'] ) ) {
					$unexpected[] = $post_type;
				}
			}
		}

		if ( ! empty( $unexpected ) ) {
			$this->log(
				\sprintf(
					'⚠️ CPTs registered by fallback when primary is active: %s',
					\implode( ', ', $unexpected )
				),
				'warning'
			);
		}
	}

	/**
	 * Check if a CPT is registered through this registry
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function is_registered( string $post_type ): bool {
		return isset( $this->registered_cpts[ $post_type ] ) || \post_type_exists( $post_type );
	}

	/**
	 * Get the owner of a registered CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return string|null
	 */
	public function get_owner( string $post_type ): ?string {
		if ( isset( $this->registered_cpts[ $post_type ] ) ) {
			return $this->registered_cpts[ $post_type ]['owner'];
		}

		// Check ownership map for expected owner.
		if ( isset( self::CPT_OWNERSHIP[ $post_type ] ) ) {
			$ownership = self::CPT_OWNERSHIP[ $post_type ];
			if ( $this->is_plugin_active( $ownership['primary_owner'] ) ) {
				return $ownership['primary_owner'];
			}
			return $ownership['fallback_owner'];
		}

		return null;
	}

	/**
	 * Get registration data for a CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return array|null
	 */
	public function get_registration_data( string $post_type ): ?array {
		return $this->registered_cpts[ $post_type ] ?? null;
	}

	/**
	 * Get all registered CPTs
	 *
	 * @return array<string, array>
	 */
	public function get_all_registered(): array {
		return $this->registered_cpts;
	}

	/**
	 * Get CPT ownership configuration
	 *
	 * @return array<string, array>
	 */
	public function get_ownership_map(): array {
		return self::CPT_OWNERSHIP;
	}

	/**
	 * Debug log registrations
	 *
	 * @return void
	 */
	public function debug_log_registrations(): void {
		if ( empty( $this->registered_cpts ) ) {
			return;
		}

		$log = "Apollo CPT Registry Summary:\n";
		foreach ( $this->registered_cpts as $post_type => $data ) {
			$log .= \sprintf(
				"  - %s (owner: %s, priority: %d)\n",
				$post_type,
				$data['owner'],
				$data['priority']
			);
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		\error_log( $log );
	}

	/**
	 * Log a message
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (debug, warning, error).
	 * @return void
	 */
	private function log( string $message, string $level = 'debug' ): void {
		if ( ! \defined( 'APOLLO_DEBUG' ) || ! APOLLO_DEBUG ) {
			return;
		}

		$prefix = match ( $level ) {
			'error'   => '[Apollo CPT Registry ERROR]',
			'warning' => '[Apollo CPT Registry WARNING]',
			default   => '[Apollo CPT Registry]',
		};

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		\error_log( $prefix . ' ' . $message );
	}
}

/**
 * Helper function to get the CPT Registry instance
 *
 * @return Apollo_CPT_Registry
 */
function apollo_cpt_registry(): Apollo_CPT_Registry {
	return Apollo_CPT_Registry::get_instance();
}

/**
 * Helper function to check if a companion plugin is active
 *
 * @param string $plugin_slug Plugin identifier (events, social, rio, or full slug).
 * @return bool
 */
function apollo_companion_active( string $plugin_slug ): bool {
	// Normalize short names to full slugs.
	$slug_map = array(
		'events'         => 'apollo-events-manager',
		'events-manager' => 'apollo-events-manager',
		'social'         => 'apollo-social',
		'rio'            => 'apollo-rio',
		'core'           => 'apollo-core',
	);

	$normalized = $slug_map[ $plugin_slug ] ?? $plugin_slug;

	return Apollo_CPT_Registry::get_instance()->is_plugin_active( $normalized );
}
