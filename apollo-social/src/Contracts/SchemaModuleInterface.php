<?php
/**
 * Schema Module Interface
 *
 * Contract for module-specific schema installers.
 *
 * @package Apollo\Contracts
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Contracts;

use WP_Error;

/**
 * Schema Module Interface
 */
interface SchemaModuleInterface {

	/**
	 * Install module schema (idempotent via dbDelta).
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function install();

	/**
	 * Upgrade module schema from one version to another.
	 *
	 * @param string $fromVersion Current stored version.
	 * @param string $toVersion   Target version.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function upgrade( string $fromVersion, string $toVersion );

	/**
	 * Get module table status.
	 *
	 * @return array<string, bool> Table existence map.
	 */
	public function getStatus(): array;

	/**
	 * Uninstall module schema (drop tables).
	 *
	 * @return void
	 */
	public function uninstall(): void;
}
