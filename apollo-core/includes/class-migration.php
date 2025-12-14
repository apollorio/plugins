<?php

declare(strict_types=1);

/**
 * Migration Script
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration class
 */
class Apollo_Core_Migration {

	/**
	 * Option mappings
	 *
	 * @var array
	 */
	private static $option_mappings = array(
		'apollo_events_version'       => 'apollo_core_version',
		'apollo_social_version'       => 'apollo_core_version',
		'apollo_rio_version'          => 'apollo_core_version',
		'apollo_events_settings'      => 'apollo_mod_settings',
		'apollo_social_settings'      => 'apollo_mod_settings',
		'apollo_canvas_pages_created' => 'apollo_core_canvas_pages_created',
	);

	/**
	 * Meta key mappings
	 *
	 * @var array
	 */
	private static $meta_mappings = array(
		'_apollo_canvas_page' => '_apollo_canvas',
		// Add more mappings as needed.
	);

	/**
	 * Run migration
	 *
	 * @return array Migration results.
	 */
	public static function run() {
		// Check if already migrated.
		if ( get_option( 'apollo_core_migration_completed', false ) ) {
			return array(
				'success' => false,
				'message' => __( 'Migration already completed.', 'apollo-core' ),
			);
		}

		$results = array(
			'options_migrated' => 0,
			'meta_migrated'    => 0,
			'errors'           => array(),
		);

		// Create backup.
		self::create_backup();

		// Migrate options.
		$results['options_migrated'] = self::migrate_options();

		// Migrate meta.
		$results['meta_migrated'] = self::migrate_meta();

		// Mark as completed.
		update_option( 'apollo_core_migration_completed', true );
		update_option( 'apollo_core_migration_version', APOLLO_CORE_VERSION );
		update_option( 'apollo_core_migration_date', current_time( 'mysql' ) );

		return array(
			'success' => true,
			'results' => $results,
		);
	}

	/**
	 * Create backup before migration
	 */
	private static function create_backup() {
		$backup_data = array(
			'options' => array(),
			'date'    => current_time( 'mysql' ),
		);

		// Backup old options.
		foreach ( array_keys( self::$option_mappings ) as $old_key ) {
			$value = get_option( $old_key );
			if ( false !== $value ) {
				$backup_data['options'][ $old_key ] = $value;
			}
		}

		// Store backup.
		update_option( 'apollo_core_migration_backup_' . time(), $backup_data );
	}

	/**
	 * Migrate options
	 *
	 * @return int Number of options migrated.
	 */
	private static function migrate_options() {
		$migrated = 0;

		foreach ( self::$option_mappings as $old_key => $new_key ) {
			$old_value = get_option( $old_key );

			if ( false === $old_value ) {
				continue;
			}

			$existing_value = get_option( $new_key );

			if ( false === $existing_value ) {
				// New option doesn't exist, create it.
				update_option( $new_key, $old_value );
				++$migrated;
			} elseif ( is_array( $old_value ) && is_array( $existing_value ) ) {
				// Merge arrays.
				$merged = array_merge( $existing_value, $old_value );
				update_option( $new_key, $merged );
				++$migrated;
			}
		}

		return $migrated;
	}

	/**
	 * Migrate post meta
	 *
	 * @return int Number of meta migrated.
	 */
	private static function migrate_meta() {
		global $wpdb;
		$migrated = 0;

		foreach ( self::$meta_mappings as $old_key => $new_key ) {
			// Get all posts with old meta key.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					$old_key
				)
			);

			foreach ( $results as $row ) {
				// Check if new meta already exists.
				$existing = get_post_meta( $row->post_id, $new_key, true );

				if ( empty( $existing ) ) {
					// Add new meta.
					update_post_meta( $row->post_id, $new_key, $row->meta_value );
					++$migrated;
				}
			}
		}//end foreach

		return $migrated;
	}

	/**
	 * Rollback migration
	 *
	 * @return bool
	 */
	public static function rollback() {
		// Find most recent backup.
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$backup_option = $wpdb->get_var(
			"SELECT option_name FROM {$wpdb->options} 
			WHERE option_name LIKE 'apollo_core_migration_backup_%' 
			ORDER BY option_name DESC 
			LIMIT 1"
		);

		if ( ! $backup_option ) {
			return false;
		}

		$backup_data = get_option( $backup_option );

		if ( empty( $backup_data['options'] ) ) {
			return false;
		}

		// Restore options.
		foreach ( $backup_data['options'] as $key => $value ) {
			update_option( $key, $value );
		}

		// Clear migration flag.
		delete_option( 'apollo_core_migration_completed' );

		return true;
	}
}
