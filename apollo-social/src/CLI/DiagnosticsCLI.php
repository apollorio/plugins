<?php
/**
 * Apollo Diagnostics CLI Commands
 *
 * WP-CLI commands for diagnostics and debugging.
 *
 * Commands:
 * - wp apollo diag status  - Show full diagnostics
 * - wp apollo diag flags   - Show feature flags
 * - wp apollo diag routes  - Show registered routes
 *
 * @package Apollo\CLI
 * @since   2.3.0
 */

declare(strict_types=1);

namespace Apollo\CLI;

use Apollo\Schema;
use Apollo\Infrastructure\FeatureFlags;
use Apollo\Infrastructure\Http\Apollo_Router;
use WP_CLI;

// Only load in CLI context.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Apollo diagnostics and debugging.
 *
 * ## EXAMPLES
 *
 *     # Show full diagnostics
 *     wp apollo diag status
 *
 *     # Show feature flags
 *     wp apollo diag flags
 *
 *     # Show routes
 *     wp apollo diag routes
 */
class DiagnosticsCLI {

	/**
	 * Show full diagnostics status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo diag status
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function status( $args, $assoc_args ) {
		WP_CLI::log( '╔══════════════════════════════════════════════════════════╗' );
		WP_CLI::log( '║            Apollo Social Diagnostics                      ║' );
		WP_CLI::log( '╚══════════════════════════════════════════════════════════╝' );
		WP_CLI::log( '' );

		// Schema status.
		$this->showSchemaStatus();

		// Feature flags.
		$this->showFeatureFlags();

		// Router status.
		$this->showRouterStatus();

		WP_CLI::success( 'Diagnostics complete.' );
	}

	/**
	 * Show feature flags status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo diag flags
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function flags( $args, $assoc_args ) {
		$this->showFeatureFlags();
	}

	/**
	 * Show registered routes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo diag routes
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function routes( $args, $assoc_args ) {
		$this->showRouterStatus();
	}

	/**
	 * Display schema status.
	 */
	private function showSchemaStatus(): void {
		WP_CLI::log( '📦 Schema Status:' );
		WP_CLI::log( str_repeat( '─', 60 ) );

		if ( ! class_exists( Schema::class ) ) {
			WP_CLI::warning( '  Schema class not found.' );
			return;
		}

		$schema = new Schema();
		$status = $schema->getStatus();

		WP_CLI::log( sprintf( '  Version (stored):  %s', $status['version_stored'] ) );
		WP_CLI::log( sprintf( '  Version (current): %s', $status['version_current'] ) );
		WP_CLI::log( sprintf( '  Needs upgrade:     %s', $status['needs_upgrade'] ? 'YES' : 'No' ) );
		WP_CLI::log( '' );

		$total   = 0;
		$missing = 0;

		foreach ( $status['modules'] as $module => $tables ) {
			foreach ( $tables as $table => $exists ) {
				++$total;
				if ( ! $exists ) {
					++$missing;
				}
			}
		}

		WP_CLI::log( sprintf( '  Tables: %d/%d present', $total - $missing, $total ) );
		WP_CLI::log( '' );
	}

	/**
	 * Display feature flags.
	 */
	private function showFeatureFlags(): void {
		WP_CLI::log( '🚩 Feature Flags:' );
		WP_CLI::log( str_repeat( '─', 60 ) );

		if ( ! class_exists( FeatureFlags::class ) ) {
			WP_CLI::warning( '  FeatureFlags class not found.' );
			return;
		}

		$initialized = FeatureFlags::isInitialized();
		WP_CLI::log( sprintf( '  Initialized: %s', $initialized ? 'YES' : 'NO (fail-closed active!)' ) );
		WP_CLI::log( '' );

		$features = FeatureFlags::getAllFeatures();
		$enabled  = 0;
		$disabled = 0;

		$rows = array();
		foreach ( $features as $name => $data ) {
			$status = $data['enabled'] ? '✅ Enabled' : '⛔ Disabled';

			if ( $data['enabled'] ) {
				++$enabled;
			} else {
				++$disabled;
			}

			$rows[] = array(
				'Feature' => $name,
				'Status'  => $status,
				'Default' => $data['default'] ? 'ON' : 'OFF',
			);
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'Feature', 'Status', 'Default' ) );

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '  Summary: %d enabled, %d disabled', $enabled, $disabled ) );
		WP_CLI::log( '' );
	}

	/**
	 * Display router status.
	 */
	private function showRouterStatus(): void {
		WP_CLI::log( '🛣️  Router Status:' );
		WP_CLI::log( str_repeat( '─', 60 ) );

		if ( ! class_exists( Apollo_Router::class ) ) {
			WP_CLI::warning( '  Apollo_Router class not found.' );
			return;
		}

		$inventory = Apollo_Router::getInventory();

		WP_CLI::log( sprintf( '  Rules Version: %s', $inventory['version'] ?? 'N/A' ) );
		WP_CLI::log( sprintf( '  Route Prefix:  %s', $inventory['prefix'] ?? 'N/A' ) );
		WP_CLI::log( sprintf( '  Total Routes:  %d', $inventory['total_routes'] ?? 0 ) );
		WP_CLI::log( '' );

		if ( ! empty( $inventory['routes_by_module'] ) ) {
			WP_CLI::log( '  Routes by Module:' );
			foreach ( $inventory['routes_by_module'] as $module => $routes ) {
				$count = count( $routes );
				WP_CLI::log( sprintf( '    • %s: %d routes', $module, $count ) );
			}
		}

		if ( ! empty( $inventory['blocked_routes'] ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( '  ⚠️  Blocked Routes:' );
			foreach ( $inventory['blocked_routes'] as $route ) {
				WP_CLI::log( sprintf( '    ✗ %s', $route ) );
			}
		}

		WP_CLI::log( '' );
	}

	/**
	 * Toggle a feature flag.
	 *
	 * ## OPTIONS
	 *
	 * <feature>
	 * : The feature name to toggle.
	 *
	 * [--enable]
	 * : Enable the feature.
	 *
	 * [--disable]
	 * : Disable the feature.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo diag toggle chat --enable
	 *     wp apollo diag toggle chat --disable
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function toggle( $args, $assoc_args ) {
		$feature = $args[0] ?? null;

		if ( ! $feature ) {
			WP_CLI::error( 'Please specify a feature name.' );
		}

		if ( ! class_exists( FeatureFlags::class ) ) {
			WP_CLI::error( 'FeatureFlags class not available.' );
		}

		$enable  = isset( $assoc_args['enable'] );
		$disable = isset( $assoc_args['disable'] );

		if ( $enable && $disable ) {
			WP_CLI::error( 'Cannot use both --enable and --disable.' );
		}

		if ( ! $enable && ! $disable ) {
			// Just show current status.
			$status = FeatureFlags::isEnabled( $feature ) ? 'enabled' : 'disabled';
			WP_CLI::log( sprintf( 'Feature "%s" is currently %s.', $feature, $status ) );
			return;
		}

		if ( $enable ) {
			FeatureFlags::enable( $feature );
			WP_CLI::success( sprintf( 'Feature "%s" enabled.', $feature ) );
		} else {
			FeatureFlags::disable( $feature );
			WP_CLI::success( sprintf( 'Feature "%s" disabled.', $feature ) );
		}
	}
}

// Register commands.
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'apollo diag', DiagnosticsCLI::class );
}
