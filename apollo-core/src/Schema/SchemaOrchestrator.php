<?php

/**
 * Schema Orchestrator
 *
 * Central coordinator for all Apollo Suite schema operations.
 * Ensures deterministic installation/upgrade order across plugins.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Schema;

if (! defined('ABSPATH')) {
	exit;
}

// Backwards compatibility alias
if (! class_exists('Apollo_Core\Schema\SchemaOrchestrator')) {
	class_alias(SchemaOrchestrator::class, 'Apollo_Core\Schema\SchemaOrchestrator');
}

/**
 * Orchestrates schema operations across all Apollo plugins.
 *
 * This class:
 * - Maintains a registry of schema modules from all plugins
 * - Runs install/upgrade in deterministic order
 * - Tracks suite-wide schema version
 * - Provides WP-CLI integration for schema management
 */
class SchemaOrchestrator
{

	/**
	 * Option name for suite-wide schema version.
	 */
	public const SUITE_VERSION_OPTION = 'apollo_suite_schema_version';

	/**
	 * Current suite schema version.
	 * Increment when ANY plugin schema changes.
	 */
	public const CURRENT_SUITE_VERSION = '1.0.0';

	/**
	 * Deterministic order for module installation.
	 * Core must come first, then social, then events, then rio.
	 */
	private const MODULE_ORDER = array('core', 'social', 'events', 'rio');

	/**
	 * Registered schema modules.
	 *
	 * @var array<string, SchemaModuleInterface>
	 */
	private array $modules = array();

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {}

	/**
	 * Register a schema module.
	 *
	 * @param SchemaModuleInterface $module The module to register.
	 * @return self For chaining.
	 */
	public function registerModule(SchemaModuleInterface $module): self
	{
		$this->modules[$module->getModuleName()] = $module;
		return $this;
	}

	/**
	 * Get a registered module by name.
	 *
	 * @param string $name Module name.
	 * @return SchemaModuleInterface|null The module or null if not found.
	 */
	public function getModule(string $name): ?SchemaModuleInterface
	{
		return $this->modules[$name] ?? null;
	}

	/**
	 * Get all registered modules in deterministic order.
	 *
	 * @return array<string, SchemaModuleInterface>
	 */
	public function getModulesInOrder(): array
	{
		$ordered = array();
		foreach (self::MODULE_ORDER as $name) {
			if (isset($this->modules[$name])) {
				$ordered[$name] = $this->modules[$name];
			}
		}
		// Add any modules not in the predefined order at the end.
		foreach ($this->modules as $name => $module) {
			if (! isset($ordered[$name])) {
				$ordered[$name] = $module;
			}
		}
		return $ordered;
	}

	/**
	 * Install all module schemas.
	 *
	 * Runs install() on each module in deterministic order.
	 * Safe to call multiple times (idempotent via dbDelta).
	 *
	 * @return array<string, bool> Results per module.
	 */
	public function installAll(): array
	{
		$results = array();
		foreach ($this->getModulesInOrder() as $name => $module) {
			try {
				$module->install();
				$results[$name] = true;
			} catch (\Exception $e) {
				$results[$name] = false;
				if (defined('WP_DEBUG') && WP_DEBUG) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log("[Apollo Schema] Install failed for {$name}: " . $e->getMessage());
				}
			}
		}
		update_option(self::SUITE_VERSION_OPTION, self::CURRENT_SUITE_VERSION);
		return $results;
	}

	/**
	 * Upgrade all module schemas.
	 *
	 * Runs upgrade() on each module that needs it, in deterministic order.
	 *
	 * @param bool $dry_run If true, only report what would be done.
	 * @return array<string, array{needed: bool, from: string, to: string, success?: bool}>
	 */
	public function upgradeAll(bool $dry_run = false): array
	{
		$results       = array();
		$suite_version = get_option(self::SUITE_VERSION_OPTION, '0.0.0');

		foreach ($this->getModulesInOrder() as $name => $module) {
			$stored  = $module->getStoredVersion();
			$current = $module->getVersion();
			$needed  = $module->needsUpgrade();

			$results[$name] = array(
				'needed' => $needed,
				'from'   => $stored,
				'to'     => $current,
			);

			if ($needed && ! $dry_run) {
				try {
					$module->upgrade($stored);
					$results[$name]['success'] = true;
				} catch (\Exception $e) {
					$results[$name]['success'] = false;
					$results[$name]['error']   = $e->getMessage();
					if (defined('WP_DEBUG') && WP_DEBUG) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log("[Apollo Schema] Upgrade failed for {$name}: " . $e->getMessage());
					}
				}
			}
		}

		if (! $dry_run) {
			update_option(self::SUITE_VERSION_OPTION, self::CURRENT_SUITE_VERSION);
		}

		return $results;
	}

	/**
	 * Get status of all modules.
	 *
	 * @return array<string, array{version: string, stored: string, needs_upgrade: bool, tables: array}>
	 */
	public function getStatus(): array
	{
		$status = array();
		foreach ($this->getModulesInOrder() as $name => $module) {
			$status[$name] = array(
				'version'       => $module->getVersion(),
				'stored'        => $module->getStoredVersion(),
				'needs_upgrade' => $module->needsUpgrade(),
				'tables'        => $module->getTables(),
				'indexes'       => $module->getIndexes(),
			);
		}
		return $status;
	}

	/**
	 * Get suite-wide version.
	 *
	 * @return string
	 */
	public function getSuiteVersion(): string
	{
		return get_option(self::SUITE_VERSION_OPTION, '0.0.0');
	}

	/**
	 * Check if any module needs upgrade.
	 *
	 * @return bool
	 */
	public function needsUpgrade(): bool
	{
		foreach ($this->modules as $module) {
			if ($module->needsUpgrade()) {
				return true;
			}
		}
		return version_compare($this->getSuiteVersion(), self::CURRENT_SUITE_VERSION, '<');
	}

	/**
	 * Get all tables across all modules.
	 *
	 * @return array<string, string> Table name => owner module.
	 */
	public function getAllTables(): array
	{
		$tables = array();
		foreach ($this->modules as $name => $module) {
			foreach ($module->getTables() as $table) {
				$tables[$table] = $name;
			}
		}
		return $tables;
	}

	/**
	 * Verify all tables exist in database.
	 *
	 * @return array<string, bool> Table name => exists.
	 */
	public function verifyTables(): array
	{
		global $wpdb;

		$results = array();
		foreach ($this->getAllTables() as $table => $owner) {
			$full_name = $wpdb->prefix . $table;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$exists            = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					$full_name
				)
			) > 0;
			$results[$table] = $exists;
		}
		return $results;
	}

	/**
	 * Initialize the orchestrator.
	 *
	 * Call this early in plugin loading to allow other plugins to register.
	 *
	 * @return void
	 */
	public static function init(): void
	{
		// Allow plugins to register their schema modules.
		add_action(
			'plugins_loaded',
			function () {
				/**
				 * Action to register schema modules with the orchestrator.
				 *
				 * @param SchemaOrchestrator $orchestrator The orchestrator instance.
				 */
				do_action('apollo_register_schema_modules', self::getInstance());
			},
			5
		);

		// Handle activation of any Apollo plugin.
		add_action(
			'apollo_plugin_activated',
			function () {
				$orchestrator = self::getInstance();
				if (! empty($orchestrator->modules)) {
					$orchestrator->installAll();
				}
			}
		);
	}
}
