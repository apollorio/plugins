<?php
/**
 * Apollo Schema CLI Commands
 *
 * WP-CLI commands for schema management.
 *
 * Commands:
 * - wp apollo schema status   - Show schema status
 * - wp apollo schema install  - Force install all tables
 * - wp apollo schema upgrade  - Force upgrade
 * - wp apollo schema version  - Show version info
 *
 * @package Apollo\CLI
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\CLI;

use Apollo\Schema;
use WP_CLI;
use WP_CLI\Utils;

// Only load in CLI context.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Manage Apollo database schema.
 *
 * ## EXAMPLES
 *
 *     # Show schema status
 *     wp apollo schema status
 *
 *     # Force install all tables
 *     wp apollo schema install
 *
 *     # Upgrade schema
 *     wp apollo schema upgrade
 */
class SchemaCLI {

	/**
	 * Show schema status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo schema status
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function status( $args, $assoc_args ) {
		$schema = new Schema();
		$status = $schema->getStatus();

		WP_CLI::log( '=== Apollo Schema Status ===' );
		WP_CLI::log( '' );
		WP_CLI::log( sprintf( 'Stored Version:  %s', $status['version_stored'] ) );
		WP_CLI::log( sprintf( 'Current Version: %s', $status['version_current'] ) );
		WP_CLI::log( sprintf( 'Needs Upgrade:   %s', $status['needs_upgrade'] ? 'Yes' : 'No' ) );
		WP_CLI::log( '' );

		WP_CLI::log( '--- Module Tables ---' );
		foreach ( $status['modules'] as $module => $tables ) {
			WP_CLI::log( sprintf( '[%s]', ucfirst( $module ) ) );
			foreach ( $tables as $table => $exists ) {
				$icon = $exists ? '✓' : '✗';
				WP_CLI::log( sprintf( '  %s %s', $icon, $table ) );
			}
		}

		WP_CLI::success( 'Status complete.' );
	}

	/**
	 * Force install all schema tables.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo schema install --yes
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function install( $args, $assoc_args ) {
		WP_CLI::confirm( 'This will run schema install. Continue?', $assoc_args );

		$schema = new Schema();
		$result = $schema->install();

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( 'Install failed: ' . $result->get_error_message() );
			return;
		}

		WP_CLI::success( 'Schema installed successfully.' );
		$this->status( array(), array() );
	}

	/**
	 * Force upgrade schema.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo schema upgrade --yes
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function upgrade( $args, $assoc_args ) {
		$schema = new Schema();

		if ( ! $schema->needsUpgrade() ) {
			WP_CLI::log( 'Schema already up to date.' );
			return;
		}

		WP_CLI::confirm( 'This will run schema upgrade. Continue?', $assoc_args );

		$result = $schema->upgrade();

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( 'Upgrade failed: ' . $result->get_error_message() );
			return;
		}

		WP_CLI::success( 'Schema upgraded successfully.' );
		$this->status( array(), array() );
	}

	/**
	 * Show version information.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo schema version
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function version( $args, $assoc_args ) {
		$schema = new Schema();

		WP_CLI::log( sprintf( 'Stored:  %s', $schema->getStoredVersion() ) );
		WP_CLI::log( sprintf( 'Current: %s', Schema::CURRENT_VERSION ) );

		if ( $schema->needsUpgrade() ) {
			WP_CLI::warning( 'Upgrade needed. Run: wp apollo schema upgrade' );
		} else {
			WP_CLI::success( 'Schema is up to date.' );
		}
	}

	/**
	 * Reset schema version (for testing).
	 *
	 * ## OPTIONS
	 *
	 * <version>
	 * : Version to set (e.g., 0.0.0).
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo schema reset 0.0.0 --yes
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function reset( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Please specify a version.' );
			return;
		}

		WP_CLI::confirm( 'This will reset the schema version. Continue?', $assoc_args );

		update_option( Schema::VERSION_OPTION, $args[0] );

		WP_CLI::success( sprintf( 'Schema version reset to %s.', $args[0] ) );
	}
}

// Register commands.
WP_CLI::add_command( 'apollo schema', __NAMESPACE__ . '\SchemaCLI' );
