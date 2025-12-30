<?php
/**
 * Schema Module Interface
 *
 * All Apollo plugins must implement this interface to participate
 * in the centralized schema orchestration.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for schema modules across Apollo Suite.
 *
 * Each plugin (core, social, rio, events-manager) implements this interface
 * to register its schema with the central orchestrator.
 */
interface SchemaModuleInterface {

	/**
	 * Get the module name (unique identifier).
	 *
	 * @return string Module name (e.g., 'core', 'social', 'events', 'rio').
	 */
	public function getModuleName(): string;

	/**
	 * Get the current schema version for this module.
	 *
	 * @return string Semantic version (e.g., '2.3.0').
	 */
	public function getVersion(): string;

	/**
	 * Get the option name used to store this module's schema version.
	 *
	 * @return string WordPress option name.
	 */
	public function getVersionOption(): string;

	/**
	 * Install the module's schema (idempotent via dbDelta).
	 *
	 * This method should:
	 * - Create all required tables using dbDelta
	 * - Be safe to run multiple times (idempotent)
	 * - NOT call flush_rewrite_rules()
	 *
	 * @return void
	 */
	public function install(): void;

	/**
	 * Upgrade the module's schema from a previous version.
	 *
	 * This method should:
	 * - Check current version and apply incremental migrations
	 * - Be safe to run multiple times (idempotent)
	 * - Update the version option after successful upgrade
	 * - NOT call flush_rewrite_rules()
	 *
	 * @param string $from_version The version upgrading from.
	 * @return void
	 */
	public function upgrade( string $from_version ): void;

	/**
	 * Get list of tables owned by this module.
	 *
	 * @return array<string> Table names WITHOUT $wpdb->prefix.
	 */
	public function getTables(): array;

	/**
	 * Get list of indexes owned by this module.
	 *
	 * @return array<string, array<string>> Table => indexes mapping.
	 */
	public function getIndexes(): array;

	/**
	 * Check if this module needs an upgrade.
	 *
	 * @return bool True if upgrade is needed.
	 */
	public function needsUpgrade(): bool;

	/**
	 * Get the stored version for this module.
	 *
	 * @return string Stored version or '0.0.0' if not set.
	 */
	public function getStoredVersion(): string;
}
