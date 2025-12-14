<?php

declare(strict_types=1);

/**
 * Apollo Core - WP-CLI Commands
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Apollo Core WP-CLI commands
 */
class Apollo_Core_CLI_Commands {

	/**
	 * Test database connectivity and Apollo tables
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo db-test
	 *
	 * @when after_wp_load
	 */
	public function db_test( $args, $assoc_args ) {
		WP_CLI::log( WP_CLI::colorize( '%B=== Apollo Core Database Test ===%n' ) );
		WP_CLI::log( '' );

		$errors = array();

		// Test 1: Database connectivity.
		WP_CLI::log( '1. Testing database connectivity...' );
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$test_query = $wpdb->get_var( 'SELECT 1' );
		if ( '1' === $test_query ) {
			WP_CLI::success( 'Database connection OK' );
		} else {
			$errors[] = 'Database connection failed';
			WP_CLI::error( 'Database connection failed', false );
		}

		// Test 2: Check apollo_mod_log table.
		WP_CLI::log( '' );
		WP_CLI::log( '2. Checking apollo_mod_log table...' );
		$table_name = $wpdb->prefix . 'apollo_mod_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		if ( $table_exists ) {
			WP_CLI::success( "Table $table_name exists" );

			// Check row count.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
			WP_CLI::log( "  Rows: $row_count" );
		} else {
			$errors[] = "Table $table_name does not exist";
			WP_CLI::error( "Table $table_name does not exist", false );
		}

		// Test 3: Check apollo_mod_settings option.
		WP_CLI::log( '' );
		WP_CLI::log( '3. Checking apollo_mod_settings option...' );
		$settings = get_option( 'apollo_mod_settings' );

		if ( $settings ) {
			WP_CLI::success( 'apollo_mod_settings option exists' );
			WP_CLI::log( '  Structure:' );
			WP_CLI::log( '    - Moderators: ' . count( $settings['mods'] ) );
			WP_CLI::log( '    - Enabled caps: ' . count( array_filter( $settings['enabled_caps'] ) ) );
			WP_CLI::log( '    - Audit log: ' . ( $settings['audit_log_enabled'] ? 'enabled' : 'disabled' ) );
			WP_CLI::log( '    - Version: ' . $settings['version'] );
		} else {
			$errors[] = 'apollo_mod_settings option does not exist';
			WP_CLI::error( 'apollo_mod_settings option does not exist', false );
		}

		// Test 4: Check apollo role.
		WP_CLI::log( '' );
		WP_CLI::log( '4. Checking apollo role...' );
		$role = get_role( 'apollo' );

		if ( $role ) {
			WP_CLI::success( 'apollo role exists' );
			WP_CLI::log( '  Capabilities:' );
			$apollo_caps = array(
				'moderate_apollo_content',
				'edit_apollo_users',
				'view_mod_queue',
				'send_user_notifications',
			);
			foreach ( $apollo_caps as $cap ) {
				$has_cap = $role->has_cap( $cap );
				WP_CLI::log( '    - ' . $cap . ': ' . ( $has_cap ? WP_CLI::colorize( '%G✓%n' ) : WP_CLI::colorize( '%R✗%n' ) ) );
				if ( ! $has_cap ) {
					$errors[] = "apollo role missing capability: $cap";
				}
			}
		} else {
			$errors[] = 'apollo role does not exist';
			WP_CLI::error( 'apollo role does not exist', false );
		}

		// Summary.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%B=== Test Summary ===%n' ) );
		if ( empty( $errors ) ) {
			WP_CLI::success( 'All tests passed!' );
			exit( 0 );
		} else {
			WP_CLI::error( count( $errors ) . ' test(s) failed:', false );
			foreach ( $errors as $error ) {
				WP_CLI::log( '  - ' . $error );
			}
			exit( 1 );
		}
	}

	/**
	 * View recent mod log
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Number of entries to show. Default 20.
	 *
	 * [--actor=<user_id>]
	 * : Filter by actor user ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo mod-log
	 *     wp apollo mod-log --limit=50
	 *     wp apollo mod-log --actor=1
	 *
	 * @when after_wp_load
	 */
	public function mod_log( $args, $assoc_args ) {
		$limit    = isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : 20;
		$actor_id = isset( $assoc_args['actor'] ) ? absint( $assoc_args['actor'] ) : null;

		$query_args = array(
			'limit' => $limit,
		);

		if ( $actor_id ) {
			$query_args['actor_id'] = $actor_id;
		}

		$logs = apollo_get_mod_log( $query_args );

		if ( empty( $logs ) ) {
			WP_CLI::warning( 'No log entries found.' );

			return;
		}

		$table_data = array();
		foreach ( $logs as $log ) {
			$table_data[] = array(
				'ID'     => $log->id,
				'Date'   => $log->created_at,
				'Actor'  => $log->actor_id . ' (' . $log->actor_role . ')',
				'Action' => $log->action,
				'Target' => $log->target_type . ':' . $log->target_id,
			);
		}

		WP_CLI\Utils\format_items( 'table', $table_data, array( 'ID', 'Date', 'Actor', 'Action', 'Target' ) );
	}
}

WP_CLI::add_command( 'apollo', 'Apollo_Core_CLI_Commands' );
