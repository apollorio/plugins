<?php
/**
 * Apollo Centralized Audit Logging
 *
 * Provides audit logging functionality for all Apollo plugins.
 * Logs security events, admin actions, and system changes.
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Audit Log class
 */
class Audit_Log {

	/**
	 * Log an audit event
	 *
	 * @param string $event_type Type of event (login, upload, setting_change, etc.).
	 * @param array  $data Event data including actor_id, target_type, target_id, message, context, severity.
	 * @return bool|int Insert ID or false on failure.
	 */
	public static function log_event( string $event_type, array $data = array() ): bool|int {
		global $wpdb;

		// Check if audit logging is enabled.
		$settings = get_option( 'apollo_mod_settings', array() );
		if ( empty( $settings['audit_log_enabled'] ) ) {
			return false;
		}

		$defaults = array(
			'actor_id'    => get_current_user_id(),
			'target_type' => null,
			'target_id'   => null,
			'severity'    => 'info',
			'message'     => '',
			'context'     => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		// Hash IP for privacy.
		$ip_hash = null;
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_hash = hash( 'sha256', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
		}

		// Prepare context as JSON.
		$context_json = wp_json_encode( $data['context'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Audit logs stored in custom table.
		$result = $wpdb->insert(
			$wpdb->prefix . 'apollo_audit_log',
			array(
				'event_type'    => sanitize_key( $event_type ),
				'actor_id'      => (int) $data['actor_id'],
				'actor_ip_hash' => $ip_hash,
				'target_type'   => $data['target_type'] ? sanitize_key( $data['target_type'] ) : null,
				'target_id'     => $data['target_id'] ? (int) $data['target_id'] : null,
				'severity'      => in_array( $data['severity'], array( 'info', 'warning', 'critical' ), true ) ? $data['severity'] : 'info',
				'message'       => sanitize_text_field( $data['message'] ),
				'context'       => $context_json,
			),
			array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Audit log failures should be logged.
			error_log( 'Apollo Audit Log: Failed to insert audit event: ' . $wpdb->last_error );

			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get audit log entries
	 *
	 * @param array $args Query arguments.
	 * @return array Audit log entries.
	 */
	public static function get_entries( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'event_type' => '',
			'actor_id'   => '',
			'severity'   => '',
			'limit'      => 50,
			'offset'     => 0,
			'orderby'    => 'created_at',
			'order'      => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array();
		$values = array();

		if ( ! empty( $args['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$values[] = $args['event_type'];
		}

		if ( ! empty( $args['actor_id'] ) ) {
			$where[]  = 'actor_id = %d';
			$values[] = (int) $args['actor_id'];
		}

		if ( ! empty( $args['severity'] ) ) {
			$where[]  = 'severity = %s';
			$values[] = $args['severity'];
		}

		$where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
		// Sanitize orderby to prevent SQL injection.
		$allowed_orderby = array( 'created_at', 'event_type', 'severity', 'actor_id' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$order_clause    = "ORDER BY {$orderby} {$order}";

		// Build query with proper prepared statement.
		$table_name = $wpdb->prefix . 'apollo_audit_log';
		if ( ! empty( $values ) ) {
			$limit  = (int) $args['limit'];
			$offset = (int) $args['offset'];
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic table name and WHERE clause.
			$query  = "SELECT * FROM {$table_name} {$where_clause} {$order_clause} LIMIT %d OFFSET %d";
			$params = array_merge( $values, array( $limit, $offset ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Audit logs require direct queries for security.
			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
		} else {
			// Build safe query with table name validation.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic table name validated, order clause validated.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} {$order_clause} LIMIT %d OFFSET %d",
					$args['limit'],
					$args['offset']
				)
			);
		}

		// Decode context JSON.
		foreach ( $results as $result ) {
			if ( ! empty( $result->context ) ) {
				$result->context = json_decode( $result->context, true );
			} else {
				$result->context = array();
			}
		}

		return $results;
	}

	/**
	 * Clean up old audit log entries
	 *
	 * @param int $days_retention Days to keep entries (default 90).
	 * @return int Number of deleted entries.
	 */
	public static function cleanup( int $days_retention = 90 ): int {
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_retention} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup operation for audit logs.
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}apollo_audit_log WHERE created_at < %s",
				$cutoff_date
			)
		);

		return (int) $result;
	}
}
