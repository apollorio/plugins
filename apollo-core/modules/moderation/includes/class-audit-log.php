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
		// Hook into activation to create table.
		add_action( 'apollo_core_activated', array( __CLASS__, 'create_table' ) );
	}

	/**
	 * Create audit log table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'apollo_mod_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			actor_id bigint(20) unsigned NOT NULL,
			actor_role varchar(50) NOT NULL,
			action varchar(50) NOT NULL,
			target_type varchar(50) NOT NULL,
			target_id bigint(20) unsigned NOT NULL,
			details longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY actor_id_idx (actor_id),
			KEY action_idx (action),
			KEY target_type_idx (target_type),
			KEY target_id_idx (target_id),
			KEY created_at_idx (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log moderation action
	 *
	 * @param int    $actor_id Actor user ID.
	 * @param string $action Action name.
	 * @param string $target_type Target type (post_type, 'user', etc).
	 * @param int    $target_id Target ID.
	 * @param array  $details Additional details.
	 * @return bool|int Insert ID or false on failure.
	 */
	public static function log_action( $actor_id, $action, $target_type, $target_id, $details = array() ) {
		// Check if audit log is enabled.
		$settings = get_option( 'apollo_mod_settings', array() );
		if ( empty( $settings['audit_log_enabled'] ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_log';

		$user       = get_userdata( $actor_id );
		$actor_role = $user ? implode( ',', $user->roles ) : 'unknown';

		$result = $wpdb->insert(
			$table,
			array(
				'actor_id'    => $actor_id,
				'actor_role'  => $actor_role,
				'action'      => $action,
				'target_type' => $target_type,
				'target_id'   => $target_id,
				'details'     => wp_json_encode( $details ),
				'created_at'  => current_time( 'mysql', 1 ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
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

