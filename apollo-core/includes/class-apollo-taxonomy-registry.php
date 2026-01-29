<?php
/**
 * Apollo Taxonomy Registry
 *
 * Centralized registry for taxonomy registration across Apollo plugins.
 * Supports sharing taxonomies across multiple CPTs and prevents duplicates.
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Resolves: Taxonomy conflicts between apollo-events-manager, apollo-social, and apollo-core
 * - event_season shared between event_listing and apollo_classified
 * - supplier_category vs apollo_supplier_category unification
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Taxonomy_Registry
 *
 * Tracks all taxonomy registrations and supports cross-plugin sharing.
 */
class Apollo_Taxonomy_Registry {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Taxonomy_Registry|null
	 */
	private static ?Apollo_Taxonomy_Registry $instance = null;

	/**
	 * Registered taxonomies with their configuration
	 *
	 * @var array<string, array{owner: string, object_types: array, args: array, attached_by: array}>
	 */
	private array $registered_taxonomies = array();

	/**
	 * Registration queue (before init hook)
	 *
	 * @var array<string, array{owner: string, object_types: array, args: array, priority: int}>
	 */
	private array $registration_queue = array();

	/**
	 * Secondary attachments queue
	 *
	 * @var array<string, array{taxonomy: string, post_type: string, plugin: string}>
	 */
	private array $attachment_queue = array();

	/**
	 * Plugin priority map
	 *
	 * @var array<string, int>
	 */
	private const PLUGIN_PRIORITIES = array(
		'apollo-events-manager' => 100,
		'apollo-social'         => 90,
		'apollo-rio'            => 80,
		'apollo-core'           => 10,
	);

	/**
	 * Canonical taxonomy ownership
	 *
	 * @var array<string, array{primary_owner: string, shared_with: array}>
	 */
	private const TAXONOMY_OWNERSHIP = array(
		// Events Manager taxonomies
		'event_listing_category'   => array(
			'primary_owner' => 'apollo-events-manager',
			'shared_with'   => array(),
		),
		'event_listing_type'       => array(
			'primary_owner' => 'apollo-events-manager',
			'shared_with'   => array(),
		),
		'event_listing_tag'        => array(
			'primary_owner' => 'apollo-events-manager',
			'shared_with'   => array(),
		),
		'event_sounds'             => array(
			'primary_owner' => 'apollo-events-manager',
			'shared_with'   => array( 'event_dj' ), // Also attached to DJs
		),
		'event_season'             => array(
			'primary_owner' => 'apollo-events-manager',
			'shared_with'   => array( 'apollo_classified' ), // Shared with classifieds
		),

		// Social taxonomies
		'classified_domain'        => array(
			'primary_owner' => 'apollo-social',
			'shared_with'   => array(),
		),
		'classified_intent'        => array(
			'primary_owner' => 'apollo-social',
			'shared_with'   => array(),
		),
		'apollo_supplier_category' => array(
			'primary_owner' => 'apollo-social',
			'shared_with'   => array(),
		),
		'apollo_supplier_region'   => array(
			'primary_owner' => 'apollo-social',
			'shared_with'   => array(),
		),
		'apollo_post_category'     => array(
			'primary_owner' => 'apollo-social',
			'shared_with'   => array(),
		),

		// Legacy (migrated to apollo_supplier_*)
		'supplier_category'        => array(
			'primary_owner' => 'apollo-core',
			'shared_with'   => array(),
			'deprecated'    => true,
			'migrate_to'    => 'apollo_supplier_category',
		),
		'supplier_tag'             => array(
			'primary_owner' => 'apollo-core',
			'shared_with'   => array(),
			'deprecated'    => true,
			'migrate_to'    => 'apollo_supplier_tag',
		),
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Taxonomy_Registry
	 */
	public static function get_instance(): Apollo_Taxonomy_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
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
		// Process registration queue at init priority 1 (after CPT Registry at 0).
		\add_action( 'init', array( $this, 'process_registration_queue' ), 1 );

		// Process secondary attachments at init priority 15 (after all taxonomies registered).
		\add_action( 'init', array( $this, 'process_attachment_queue' ), 15 );

		// Validate registrations at end of init.
		\add_action( 'init', array( $this, 'validate_registrations' ), 999 );

		// Fire hook when taxonomies are ready.
		\add_action( 'init', array( $this, 'fire_taxonomies_ready' ), 20 );
	}

	/**
	 * Register a taxonomy through the registry
	 *
	 * @param string       $taxonomy     Taxonomy slug.
	 * @param string|array $object_types Post type(s) to attach.
	 * @param array        $args         Registration arguments.
	 * @param string       $owner        Plugin identifier.
	 * @param int|null     $priority     Optional. Override priority.
	 * @return bool True if queued/registered.
	 */
	public function register(
		string $taxonomy,
		$object_types,
		array $args,
		string $owner,
		?int $priority = null
	): bool {
		// Normalize object types to array.
		$object_types = (array) $object_types;

		// Determine priority.
		$priority = $priority ?? ( self::PLUGIN_PRIORITIES[ $owner ] ?? 50 );

		// Check if this taxonomy should be registered by this owner.
		if ( ! $this->should_register( $taxonomy, $owner ) ) {
			$this->log(
				\sprintf(
					'Taxonomy "%s" blocked for owner "%s" - primary owner is active.',
					$taxonomy,
					$owner
				)
			);
			return false;
		}

		// Check for deprecation warning.
		if ( $this->is_deprecated( $taxonomy ) ) {
			$migrate_to = self::TAXONOMY_OWNERSHIP[ $taxonomy ]['migrate_to'] ?? 'unknown';
			$this->log(
				\sprintf(
					'⚠️ Taxonomy "%s" is deprecated. Consider migrating to "%s".',
					$taxonomy,
					$migrate_to
				),
				'warning'
			);
		}

		// Check if already queued with higher priority.
		if ( isset( $this->registration_queue[ $taxonomy ] ) ) {
			$existing_priority = $this->registration_queue[ $taxonomy ]['priority'];
			if ( $existing_priority >= $priority ) {
				// Merge object types instead of blocking.
				$this->registration_queue[ $taxonomy ]['object_types'] = \array_unique(
					\array_merge(
						$this->registration_queue[ $taxonomy ]['object_types'],
						$object_types
					)
				);
				$this->log(
					\sprintf(
						'Taxonomy "%s" merged object types from "%s".',
						$taxonomy,
						$owner
					)
				);
				return true;
			}
		}

		// Queue for registration.
		$this->registration_queue[ $taxonomy ] = array(
			'owner'        => $owner,
			'object_types' => $object_types,
			'args'         => $args,
			'priority'     => $priority,
		);

		$this->log(
			\sprintf(
				'Taxonomy "%s" queued by "%s" with priority %d.',
				$taxonomy,
				$owner,
				$priority
			)
		);

		return true;
	}

	/**
	 * Attach a taxonomy to an additional post type
	 *
	 * Use this for cross-plugin taxonomy sharing.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $post_type Post type to attach.
	 * @param string $plugin    Plugin requesting attachment.
	 * @return bool
	 */
	public function attach_to_post_type( string $taxonomy, string $post_type, string $plugin ): bool {
		// Queue for later processing (after taxonomies are registered).
		$this->attachment_queue[] = array(
			'taxonomy'  => $taxonomy,
			'post_type' => $post_type,
			'plugin'    => $plugin,
		);

		$this->log(
			\sprintf(
				'Taxonomy "%s" attachment to "%s" queued by "%s".',
				$taxonomy,
				$post_type,
				$plugin
			)
		);

		return true;
	}

	/**
	 * Check if taxonomy should be registered by this owner
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $owner    Plugin identifier.
	 * @return bool
	 */
	public function should_register( string $taxonomy, string $owner ): bool {
		// If no ownership defined, allow registration.
		if ( ! isset( self::TAXONOMY_OWNERSHIP[ $taxonomy ] ) ) {
			return true;
		}

		$ownership = self::TAXONOMY_OWNERSHIP[ $taxonomy ];

		// If this is the primary owner, always allow.
		if ( $ownership['primary_owner'] === $owner ) {
			return true;
		}

		// If this is a shared taxonomy and primary is not active, allow fallback.
		if ( ! $this->is_plugin_active( $ownership['primary_owner'] ) ) {
			return true;
		}

		// Primary owner is active, block duplicate registration.
		return false;
	}

	/**
	 * Check if a taxonomy is deprecated
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return bool
	 */
	public function is_deprecated( string $taxonomy ): bool {
		return isset( self::TAXONOMY_OWNERSHIP[ $taxonomy ]['deprecated'] )
			&& self::TAXONOMY_OWNERSHIP[ $taxonomy ]['deprecated'] === true;
	}

	/**
	 * Check if a plugin is active
	 *
	 * @param string $plugin_slug Plugin identifier.
	 * @return bool
	 */
	private function is_plugin_active( string $plugin_slug ): bool {
		// Use CPT Registry if available.
		if ( \function_exists( '\\Apollo_Core\\apollo_companion_active' ) ) {
			return apollo_companion_active( $plugin_slug );
		}

		// Fallback to direct check.
		$plugin_files = array(
			'apollo-events-manager' => 'apollo-events-manager/apollo-events-manager.php',
			'apollo-social'         => 'apollo-social/apollo-social.php',
			'apollo-rio'            => 'apollo-rio/apollo-rio.php',
			'apollo-core'           => 'apollo-core/apollo-core.php',
		);

		if ( ! isset( $plugin_files[ $plugin_slug ] ) ) {
			return false;
		}

		if ( ! \function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
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

		foreach ( $this->registration_queue as $taxonomy => $config ) {
			// Skip if already registered.
			if ( \taxonomy_exists( $taxonomy ) ) {
				// Just record it.
				$this->registered_taxonomies[ $taxonomy ] = array(
					'owner'        => $config['owner'],
					'object_types' => $config['object_types'],
					'args'         => $config['args'],
					'attached_by'  => array( $config['owner'] ),
				);

				$this->log(
					\sprintf(
						'Taxonomy "%s" already exists, recorded as owned by "%s".',
						$taxonomy,
						$config['owner']
					)
				);
				continue;
			}

			// Register the taxonomy.
			$result = \register_taxonomy(
				$taxonomy,
				$config['object_types'],
				$config['args']
			);

			if ( ! \is_wp_error( $result ) ) {
				$this->registered_taxonomies[ $taxonomy ] = array(
					'owner'        => $config['owner'],
					'object_types' => $config['object_types'],
					'args'         => $config['args'],
					'attached_by'  => array( $config['owner'] ),
				);

				$this->log(
					\sprintf(
						'✅ Taxonomy "%s" registered by "%s".',
						$taxonomy,
						$config['owner']
					)
				);
			} else {
				$this->log(
					\sprintf(
						'❌ Taxonomy "%s" registration failed: %s',
						$taxonomy,
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
	 * Process secondary attachments
	 *
	 * @return void
	 */
	public function process_attachment_queue(): void {
		foreach ( $this->attachment_queue as $attachment ) {
			$taxonomy  = $attachment['taxonomy'];
			$post_type = $attachment['post_type'];
			$plugin    = $attachment['plugin'];

			// Check if taxonomy exists.
			if ( ! \taxonomy_exists( $taxonomy ) ) {
				$this->log(
					\sprintf(
						'Cannot attach taxonomy "%s" - does not exist.',
						$taxonomy
					),
					'warning'
				);
				continue;
			}

			// Check if post type exists.
			if ( ! \post_type_exists( $post_type ) ) {
				$this->log(
					\sprintf(
						'Cannot attach taxonomy "%s" to "%s" - post type does not exist.',
						$taxonomy,
						$post_type
					),
					'warning'
				);
				continue;
			}

			// Check if already attached.
			$tax_object = \get_taxonomy( $taxonomy );
			if ( $tax_object && \in_array( $post_type, (array) $tax_object->object_type, true ) ) {
				$this->log(
					\sprintf(
						'Taxonomy "%s" already attached to "%s".',
						$taxonomy,
						$post_type
					)
				);
				continue;
			}

			// Attach taxonomy to post type.
			$result = \register_taxonomy_for_object_type( $taxonomy, $post_type );

			if ( $result ) {
				// Update registered taxonomies record.
				if ( isset( $this->registered_taxonomies[ $taxonomy ] ) ) {
					$this->registered_taxonomies[ $taxonomy ]['object_types'][] = $post_type;
					$this->registered_taxonomies[ $taxonomy ]['attached_by'][]  = $plugin;
				}

				$this->log(
					\sprintf(
						'✅ Taxonomy "%s" attached to "%s" by "%s".',
						$taxonomy,
						$post_type,
						$plugin
					)
				);
			}
		}

		// Clear the queue.
		$this->attachment_queue = array();
	}

	/**
	 * Fire taxonomies ready hook
	 *
	 * @return void
	 */
	public function fire_taxonomies_ready(): void {
		/**
		 * Action: apollo_taxonomies_ready
		 *
		 * Fired when all Apollo taxonomies are registered.
		 * Plugins should use this hook to attach shared taxonomies.
		 *
		 * @param Apollo_Taxonomy_Registry $registry The registry instance.
		 */
		\do_action( 'apollo_taxonomies_ready', $this );
	}

	/**
	 * Validate registrations
	 *
	 * @return void
	 */
	public function validate_registrations(): void {
		// Check for legacy taxonomies that should be migrated.
		foreach ( self::TAXONOMY_OWNERSHIP as $taxonomy => $ownership ) {
			if ( ! empty( $ownership['deprecated'] ) && \taxonomy_exists( $taxonomy ) ) {
				$this->log(
					\sprintf(
						'⚠️ Deprecated taxonomy "%s" is still in use. Migrate to "%s".',
						$taxonomy,
						$ownership['migrate_to'] ?? 'unknown'
					),
					'warning'
				);
			}
		}
	}

	/**
	 * Get attached post types for a taxonomy
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public function get_attached_types( string $taxonomy ): array {
		if ( isset( $this->registered_taxonomies[ $taxonomy ] ) ) {
			return $this->registered_taxonomies[ $taxonomy ]['object_types'];
		}

		// Fallback to WordPress API.
		$tax_object = \get_taxonomy( $taxonomy );
		if ( $tax_object ) {
			return (array) $tax_object->object_type;
		}

		return array();
	}

	/**
	 * Get owner of a taxonomy
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return string|null
	 */
	public function get_owner( string $taxonomy ): ?string {
		if ( isset( $this->registered_taxonomies[ $taxonomy ] ) ) {
			return $this->registered_taxonomies[ $taxonomy ]['owner'];
		}

		// Check ownership map.
		if ( isset( self::TAXONOMY_OWNERSHIP[ $taxonomy ] ) ) {
			return self::TAXONOMY_OWNERSHIP[ $taxonomy ]['primary_owner'];
		}

		return null;
	}

	/**
	 * Check if a taxonomy is registered
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return bool
	 */
	public function is_registered( string $taxonomy ): bool {
		return isset( $this->registered_taxonomies[ $taxonomy ] ) || \taxonomy_exists( $taxonomy );
	}

	/**
	 * Get all registered taxonomies
	 *
	 * @return array<string, array>
	 */
	public function get_all_registered(): array {
		return $this->registered_taxonomies;
	}

	/**
	 * Get taxonomy ownership map
	 *
	 * @return array<string, array>
	 */
	public function get_ownership_map(): array {
		return self::TAXONOMY_OWNERSHIP;
	}

	/**
	 * Get shared taxonomies for a plugin
	 *
	 * @param string $plugin Plugin identifier.
	 * @return array<string>
	 */
	public function get_shared_taxonomies_for_plugin( string $plugin ): array {
		$shared = array();

		foreach ( self::TAXONOMY_OWNERSHIP as $taxonomy => $ownership ) {
			// Skip if this plugin is the primary owner.
			if ( $ownership['primary_owner'] === $plugin ) {
				continue;
			}

			// Check if taxonomy is shared with post types this plugin might use.
			if ( ! empty( $ownership['shared_with'] ) ) {
				$shared[ $taxonomy ] = $ownership['shared_with'];
			}
		}

		return $shared;
	}

	/**
	 * Log a message
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level.
	 * @return void
	 */
	private function log( string $message, string $level = 'debug' ): void {
		if ( ! \defined( 'APOLLO_DEBUG' ) || ! APOLLO_DEBUG ) {
			return;
		}

		$prefix = match ( $level ) {
			'error'   => '[Apollo Taxonomy Registry ERROR]',
			'warning' => '[Apollo Taxonomy Registry WARNING]',
			default   => '[Apollo Taxonomy Registry]',
		};

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		\error_log( $prefix . ' ' . $message );
	}
}

/**
 * Get Apollo Taxonomy Registry instance
 *
 * @return Apollo_Taxonomy_Registry
 */
function apollo_taxonomy_registry(): Apollo_Taxonomy_Registry {
	return Apollo_Taxonomy_Registry::get_instance();
}

/**
 * Register a taxonomy through the Apollo registry
 *
 * @param string       $taxonomy     Taxonomy slug.
 * @param string|array $object_types Post type(s).
 * @param array        $args         Registration args.
 * @param string       $owner        Plugin identifier.
 * @return bool
 */
function apollo_register_taxonomy( string $taxonomy, $object_types, array $args, string $owner ): bool {
	return Apollo_Taxonomy_Registry::get_instance()->register(
		$taxonomy,
		$object_types,
		$args,
		$owner
	);
}

/**
 * Attach a taxonomy to an additional post type
 *
 * @param string $taxonomy  Taxonomy slug.
 * @param string $post_type Post type.
 * @param string $plugin    Plugin identifier.
 * @return bool
 */
function apollo_attach_taxonomy( string $taxonomy, string $post_type, string $plugin ): bool {
	return Apollo_Taxonomy_Registry::get_instance()->attach_to_post_type(
		$taxonomy,
		$post_type,
		$plugin
	);
}
