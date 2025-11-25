<?php
declare(strict_types=1);

/**
 * Apollo Core - Database Schema
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create Apollo Core database tables
 *
 * @return void
 */
function apollo_create_db_tables(): void {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'apollo_mod_log';

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
 * Log moderation action to database
 *
 * @param int    $actor_id    User ID performing the action.
 * @param string $action      Action name (e.g., 'approve_post', 'suspend_user').
 * @param string $target_type Type of target (e.g., 'post', 'user', 'event_listing').
 * @param int    $target_id   ID of target object.
 * @param array  $details     Additional details as associative array.
 * @return int|false Insert ID on success, false on failure.
 */
function apollo_mod_log_action( int $actor_id, string $action, string $target_type, int $target_id, array $details = array() ) {
	// Check if audit log is enabled.
	$settings = apollo_get_mod_settings();
	if ( empty( $settings['audit_log_enabled'] ) ) {
		return false;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_log';

	// Get actor role.
	$user       = get_userdata( $actor_id );
	$actor_role = $user ? implode( ',', $user->roles ) : 'unknown';

	// Prepare details as JSON.
	$details_json = ! empty( $details ) ? wp_json_encode( $details ) : '{}';

	$result = $wpdb->insert(
		$table,
		array(
			'actor_id'    => absint( $actor_id ),
			'actor_role'  => sanitize_text_field( $actor_role ),
			'action'      => sanitize_text_field( $action ),
			'target_type' => sanitize_text_field( $target_type ),
			'target_id'   => absint( $target_id ),
			'details'     => $details_json,
			'created_at'  => current_time( 'mysql', 1 ),
		),
		array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Get moderation log entries
 *
 * @param array $args Query arguments.
 * @return array Array of log entries.
 */
function apollo_get_mod_log( array $args = array() ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_log';

	$defaults = array(
		'actor_id'    => null,
		'action'      => null,
		'target_type' => null,
		'target_id'   => null,
		'limit'       => 100,
		'offset'      => 0,
		'orderby'     => 'created_at',
		'order'       => 'DESC',
	);

	$args = wp_parse_args( $args, $defaults );

	// Build WHERE clause.
	$where = array( '1=1' );
	$values = array();

	if ( $args['actor_id'] ) {
		$where[]  = 'actor_id = %d';
		$values[] = absint( $args['actor_id'] );
	}

	if ( $args['action'] ) {
		$where[]  = 'action = %s';
		$values[] = sanitize_text_field( $args['action'] );
	}

	if ( $args['target_type'] ) {
		$where[]  = 'target_type = %s';
		$values[] = sanitize_text_field( $args['target_type'] );
	}

	if ( $args['target_id'] ) {
		$where[]  = 'target_id = %d';
		$values[] = absint( $args['target_id'] );
	}

	$where_clause = implode( ' AND ', $where );

	// Add ORDER BY and LIMIT.
	$orderby = in_array( $args['orderby'], array( 'id', 'created_at', 'actor_id' ), true ) ? $args['orderby'] : 'created_at';
	$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

	$sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";

	$values[] = absint( $args['limit'] );
	$values[] = absint( $args['offset'] );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

	// Parse JSON details.
	foreach ( $results as &$row ) {
		$row->details = json_decode( $row->details, true );
	}

	return $results;
}

/**
 * Delete old log entries
 *
 * @param int $days Delete entries older than this many days.
 * @return int|false Number of deleted rows or false on failure.
 */
function apollo_cleanup_mod_log( int $days = 90 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_log';

	$date = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $table WHERE created_at < %s",
			$date
		)
	);
}

