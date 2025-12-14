<?php

declare(strict_types=1);

/**
 * Audit Log
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit Log class
 */
class Apollo_Moderation_Audit_Log {

	/**
	 * Initialize
	 */
	public static function init() {
		// Ensure audit log schema exists immediately when the module loads.
		self::create_table();
	}

	/**
	 * Create audit log table
	 */
	public static function create_table() {
		if ( function_exists( 'apollo_create_db_tables' ) ) {
			apollo_create_db_tables();
		}
	}

	/**
	 * Log mod action
	 *
	 * @param int    $actor_id Actor user ID.
	 * @param string $action Action name.
	 * @param string $target_type Target type (post_type, 'user', etc).
	 * @param int    $target_id Target ID.
	 * @param array  $details Additional details.
	 * @return bool|int Insert ID or false on failure.
	 */
	public static function log_action( $actor_id, $action, $target_type, $target_id, $details = [] ) {
		// Check if audit log is enabled.
		$settings = get_option( 'apollo_mod_settings', [] );
		if ( empty( $settings['audit_log_enabled'] ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_log';

		$user       = get_userdata( $actor_id );
		$actor_role = $user ? implode( ',', $user->roles ) : 'unknown';

		$result = $wpdb->insert(
			$table,
			[
				'actor_id'    => $actor_id,
				'actor_role'  => $actor_role,
				'action'      => $action,
				'target_type' => $target_type,
				'target_id'   => $target_id,
				'details'     => wp_json_encode( $details ),
				'created_at'  => current_time( 'mysql', 1 ),
			],
			[ '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get audit log for target
	 *
	 * @param string $target_type Target type.
	 * @param int    $target_id Target ID.
	 * @param int    $limit Limit.
	 * @return array
	 */
	public static function get_log_for_target( $target_type, $target_id, $limit = 50 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE target_type = %s AND target_id = %d
				ORDER BY created_at DESC
				LIMIT %d",
				$target_type,
				$target_id,
				$limit
			)
		);

		// Parse JSON details.
		foreach ( $results as &$row ) {
			$row->details = json_decode( $row->details, true );
		}

		return $results;
	}

	/**
	 * Get audit log for actor
	 *
	 * @param int $actor_id Actor user ID.
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function get_log_for_actor( $actor_id, $limit = 100 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE actor_id = %d
				ORDER BY created_at DESC
				LIMIT %d",
				$actor_id,
				$limit
			)
		);

		// Parse JSON details.
		foreach ( $results as &$row ) {
			$row->details = json_decode( $row->details, true );
		}

		return $results;
	}

	/**
	 * Get recent audit log
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function get_recent_log( $limit = 100 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				ORDER BY created_at DESC
				LIMIT %d",
				$limit
			)
		);

		// Parse JSON details.
		foreach ( $results as &$row ) {
			$row->details = json_decode( $row->details, true );
		}

		return $results;
	}
}
