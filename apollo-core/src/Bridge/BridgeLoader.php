<?php
/**
 * Apollo Suite Bridge Loader
 *
 * Central coordinator that bridges apollo-core with all satellite plugins.
 * Provides unified schema, routing, and security integration.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Bridge;

use Apollo_Core\Schema\SchemaOrchestrator;
use Apollo_Core\Schema\CoreSchemaModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bridge Loader - Connects all Apollo plugins as ONE product.
 */
final class BridgeLoader {

	/**
	 * Option for bridge status.
	 */
	public const STATUS_OPTION = 'apollo_bridge_status';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Connected plugins registry.
	 *
	 * @var array<string, array{version: string, active: bool, schema: bool, routes: bool}>
	 */
	private array $connected_plugins = [];

	/**
	 * Whether bridge is initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Initialize the bridge.
	 *
	 * Must be called early (plugins_loaded, priority 1).
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Register core's own schema module first.
		add_action( 'apollo_register_schema_modules', [ $this, 'registerCoreSchema' ], 1 );

		// Allow satellite plugins to register.
		add_action( 'plugins_loaded', [ $this, 'discoverPlugins' ], 5 );

		// Provide hook for satellites to connect.
		add_action( 'plugins_loaded', [ $this, 'initializeBridge' ], 10 );

		// Initialize routing bridge.
		add_action( 'init', [ $this, 'initRoutesBridge' ], 1 );

		$this->initialized = true;
	}

	/**
	 * Register core's schema module.
	 *
	 * @param SchemaOrchestrator $orchestrator The schema orchestrator.
	 * @return void
	 */
	public function registerCoreSchema( SchemaOrchestrator $orchestrator ): void {
		$orchestrator->registerModule( new CoreSchemaModule() );
	}

	/**
	 * Discover installed Apollo satellite plugins.
	 *
	 * @return void
	 */
	public function discoverPlugins(): void {
		// apollo-social
		if ( defined( 'APOLLO_SOCIAL_VERSION' ) ) {
			$this->connected_plugins['social'] = [
				'version' => APOLLO_SOCIAL_VERSION,
				'active'  => true,
				'schema'  => false,
				'routes'  => false,
				'file'    => 'apollo-social/apollo-social.php',
			];
		}

		// apollo-events-manager
		if ( defined( 'APOLLO_EVENTS_MANAGER_VERSION' ) ) {
			$this->connected_plugins['events'] = [
				'version' => APOLLO_EVENTS_MANAGER_VERSION,
				'active'  => true,
				'schema'  => false,
				'routes'  => false,
				'file'    => 'apollo-events-manager/apollo-events-manager.php',
			];
		}

		// apollo-rio
		if ( defined( 'APOLLO_RIO_VERSION' ) ) {
			$this->connected_plugins['rio'] = [
				'version' => APOLLO_RIO_VERSION,
				'active'  => true,
				'schema'  => false,
				'routes'  => false,
				'file'    => 'apollo-rio/apollo-rio.php',
			];
		}

		/**
		 * Action to discover additional Apollo plugins.
		 *
		 * @param BridgeLoader $bridge The bridge instance.
		 */
		do_action( 'apollo_bridge_discover', $this );
	}

	/**
	 * Initialize the bridge after all plugins loaded.
	 *
	 * @return void
	 */
	public function initializeBridge(): void {
		/**
		 * Action for satellites to register with the bridge.
		 *
		 * @param BridgeLoader $bridge The bridge instance.
		 */
		do_action( 'apollo_bridge_init', $this );

		// Update status.
		$this->updateStatus();
	}

	/**
	 * Initialize routes bridge.
	 *
	 * @return void
	 */
	public function initRoutesBridge(): void {
		/**
		 * Action to collect routes from all plugins.
		 *
		 * @param BridgeLoader $bridge The bridge instance.
		 */
		do_action( 'apollo_bridge_routes', $this );
	}

	/**
	 * Register a satellite plugin with the bridge.
	 *
	 * @param string $plugin_key Plugin identifier (social, events, rio).
	 * @param array  $capabilities What the plugin provides (schema, routes).
	 * @return self
	 */
	public function registerPlugin( string $plugin_key, array $capabilities = [] ): self {
		if ( isset( $this->connected_plugins[ $plugin_key ] ) ) {
			$this->connected_plugins[ $plugin_key ]['schema'] = $capabilities['schema'] ?? false;
			$this->connected_plugins[ $plugin_key ]['routes'] = $capabilities['routes'] ?? false;
		}
		return $this;
	}

	/**
	 * Mark a plugin's schema as registered.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @return void
	 */
	public function markSchemaRegistered( string $plugin_key ): void {
		if ( isset( $this->connected_plugins[ $plugin_key ] ) ) {
			$this->connected_plugins[ $plugin_key ]['schema'] = true;
		}
	}

	/**
	 * Mark a plugin's routes as registered.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @return void
	 */
	public function markRoutesRegistered( string $plugin_key ): void {
		if ( isset( $this->connected_plugins[ $plugin_key ] ) ) {
			$this->connected_plugins[ $plugin_key ]['routes'] = true;
		}
	}

	/**
	 * Check if a plugin is connected.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @return bool
	 */
	public function isPluginConnected( string $plugin_key ): bool {
		return isset( $this->connected_plugins[ $plugin_key ] ) &&
			   $this->connected_plugins[ $plugin_key ]['active'];
	}

	/**
	 * Check if a plugin has schema integration.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @return bool
	 */
	public function hasSchemaIntegration( string $plugin_key ): bool {
		return isset( $this->connected_plugins[ $plugin_key ] ) &&
			   $this->connected_plugins[ $plugin_key ]['schema'];
	}

	/**
	 * Check if a plugin has routes integration.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @return bool
	 */
	public function hasRoutesIntegration( string $plugin_key ): bool {
		return isset( $this->connected_plugins[ $plugin_key ] ) &&
			   $this->connected_plugins[ $plugin_key ]['routes'];
	}

	/**
	 * Get all connected plugins.
	 *
	 * @return array<string, array>
	 */
	public function getConnectedPlugins(): array {
		return $this->connected_plugins;
	}

	/**
	 * Get bridge status.
	 *
	 * @return array
	 */
	public function getStatus(): array {
		$orchestrator = SchemaOrchestrator::getInstance();

		return [
			'bridge_version'    => '2.0.0',
			'core_version'      => defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : 'unknown',
			'suite_version'     => $orchestrator->getSuiteVersion(),
			'plugins'           => $this->connected_plugins,
			'schema_modules'    => array_keys( $orchestrator->getModulesInOrder() ),
			'total_tables'      => count( $orchestrator->getAllTables() ),
			'needs_upgrade'     => $orchestrator->needsUpgrade(),
			'all_schemas_ok'    => $this->allSchemasRegistered(),
			'all_routes_ok'     => $this->allRoutesRegistered(),
		];
	}

	/**
	 * Check if all active plugins have schema registered.
	 *
	 * @return bool
	 */
	public function allSchemasRegistered(): bool {
		foreach ( $this->connected_plugins as $plugin ) {
			if ( $plugin['active'] && ! $plugin['schema'] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if all active plugins have routes registered.
	 *
	 * @return bool
	 */
	public function allRoutesRegistered(): bool {
		foreach ( $this->connected_plugins as $plugin ) {
			if ( $plugin['active'] && ! $plugin['routes'] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Update bridge status in database.
	 *
	 * @return void
	 */
	private function updateStatus(): void {
		update_option( self::STATUS_OPTION, [
			'updated_at' => current_time( 'mysql' ),
			'plugins'    => $this->connected_plugins,
		], false );
	}

	/**
	 * Run bridge validation.
	 *
	 * @return array<string, array{status: string, message: string}>
	 */
	public function validate(): array {
		$results = [];
		$orchestrator = SchemaOrchestrator::getInstance();

		// Check 1: Core loaded.
		$results['core_loaded'] = [
			'status'  => defined( 'APOLLO_CORE_VERSION' ) ? 'pass' : 'fail',
			'message' => defined( 'APOLLO_CORE_VERSION' )
				? 'Apollo Core v' . APOLLO_CORE_VERSION . ' loaded'
				: 'Apollo Core not detected',
		];

		// Check 2: Schema orchestrator.
		$modules = $orchestrator->getModulesInOrder();
		$results['schema_orchestrator'] = [
			'status'  => ! empty( $modules ) ? 'pass' : 'warn',
			'message' => count( $modules ) . ' schema modules registered',
		];

		// Check 3: Each plugin schema.
		foreach ( $this->connected_plugins as $key => $plugin ) {
			$results[ "schema_{$key}" ] = [
				'status'  => $plugin['schema'] ? 'pass' : 'warn',
				'message' => $plugin['schema']
					? ucfirst( $key ) . ' schema integrated'
					: ucfirst( $key ) . ' schema NOT integrated',
			];
		}

		// Check 4: Each plugin routes.
		foreach ( $this->connected_plugins as $key => $plugin ) {
			$results[ "routes_{$key}" ] = [
				'status'  => $plugin['routes'] ? 'pass' : 'warn',
				'message' => $plugin['routes']
					? ucfirst( $key ) . ' routes integrated'
					: ucfirst( $key ) . ' routes NOT integrated',
			];
		}

		// Check 5: Tables exist.
		$tables_check = $orchestrator->verifyTables();
		$missing      = array_filter( $tables_check, fn( $exists ) => ! $exists );
		$results['tables_exist'] = [
			'status'  => empty( $missing ) ? 'pass' : 'fail',
			'message' => empty( $missing )
				? 'All ' . count( $tables_check ) . ' tables exist'
				: count( $missing ) . ' tables missing: ' . implode( ', ', array_keys( $missing ) ),
		];

		// Check 6: No upgrade needed.
		$results['no_upgrade_needed'] = [
			'status'  => ! $orchestrator->needsUpgrade() ? 'pass' : 'warn',
			'message' => $orchestrator->needsUpgrade()
				? 'Schema upgrade pending'
				: 'Schema up to date',
		];

		return $results;
	}
}
