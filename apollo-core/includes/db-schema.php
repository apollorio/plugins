<?php
/**
 * Apollo Core - Database Schema
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create Apollo Core database tables
 *
 * @return void
 */
function create_db_tables(): void {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Moderation log table.
	$table_mod_log = $wpdb->prefix . 'apollo_mod_log';
	$sql_mod_log   = "CREATE TABLE $table_mod_log (
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
	dbDelta( $sql_mod_log );

	// Audit log table (FASE 1 - more detailed for security events).
	$table_audit_log = $wpdb->prefix . 'apollo_audit_log';
	$sql_audit_log   = "CREATE TABLE $table_audit_log (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_type varchar(50) NOT NULL,
		actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
		actor_ip_hash varchar(64) DEFAULT NULL,
		target_type varchar(50) DEFAULT NULL,
		target_id bigint(20) unsigned DEFAULT NULL,
		severity enum('info','warning','critical') DEFAULT 'info',
		message text,
		context longtext,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY event_type_idx (event_type),
		KEY actor_id_idx (actor_id),
		KEY severity_idx (severity),
		KEY created_at_idx (created_at)
	) $charset_collate;";
	dbDelta( $sql_audit_log );

	// =========================================================================.
	// APOLLO ADVANCED ANALYTICS TABLES.
	// =========================================================================.

	// Page views table - tracks all page views with detailed metrics.
	$table_pageviews = $wpdb->prefix . 'apollo_analytics_pageviews';
	$sql_pageviews   = "CREATE TABLE $table_pageviews (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		session_id varchar(64) NOT NULL,
		user_id bigint(20) unsigned DEFAULT 0,
		page_url varchar(2048) NOT NULL,
		page_title varchar(500) DEFAULT NULL,
		page_type varchar(50) DEFAULT 'page',
		post_id bigint(20) unsigned DEFAULT NULL,
		referrer varchar(2048) DEFAULT NULL,
		user_agent varchar(500) DEFAULT NULL,
		device_type enum('desktop','tablet','mobile') DEFAULT 'desktop',
		browser varchar(50) DEFAULT NULL,
		os varchar(50) DEFAULT NULL,
		screen_width int(5) unsigned DEFAULT NULL,
		screen_height int(5) unsigned DEFAULT NULL,
		viewport_width int(5) unsigned DEFAULT NULL,
		viewport_height int(5) unsigned DEFAULT NULL,
		country_code varchar(2) DEFAULT NULL,
		region varchar(100) DEFAULT NULL,
		city varchar(100) DEFAULT NULL,
		ip_hash varchar(64) DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY session_idx (session_id),
		KEY user_idx (user_id),
		KEY page_type_idx (page_type),
		KEY post_id_idx (post_id),
		KEY created_at_idx (created_at),
		KEY device_idx (device_type)
	) $charset_collate;";
	dbDelta( $sql_pageviews );

	// User interactions table - clicks, scrolls, form interactions.
	$table_interactions = $wpdb->prefix . 'apollo_analytics_interactions';
	$sql_interactions   = "CREATE TABLE $table_interactions (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		session_id varchar(64) NOT NULL,
		pageview_id bigint(20) unsigned DEFAULT NULL,
		user_id bigint(20) unsigned DEFAULT 0,
		interaction_type enum('click','scroll','hover','form_focus','form_submit','video_play','video_pause','download','outbound_link','custom') NOT NULL,
		element_tag varchar(50) DEFAULT NULL,
		element_id varchar(100) DEFAULT NULL,
		element_class varchar(255) DEFAULT NULL,
		element_text varchar(500) DEFAULT NULL,
		element_href varchar(2048) DEFAULT NULL,
		position_x int(10) DEFAULT NULL,
		position_y int(10) DEFAULT NULL,
		scroll_depth int(3) unsigned DEFAULT NULL,
		scroll_direction enum('up','down') DEFAULT NULL,
		viewport_percent int(3) unsigned DEFAULT NULL,
		time_on_page int(10) unsigned DEFAULT 0,
		extra_data longtext,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY session_idx (session_id),
		KEY pageview_idx (pageview_id),
		KEY user_idx (user_id),
		KEY interaction_type_idx (interaction_type),
		KEY created_at_idx (created_at)
	) $charset_collate;";
	dbDelta( $sql_interactions );

	// Session table - aggregated session data.
	$table_sessions = $wpdb->prefix . 'apollo_analytics_sessions';
	$sql_sessions   = "CREATE TABLE $table_sessions (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		session_id varchar(64) NOT NULL,
		user_id bigint(20) unsigned DEFAULT 0,
		first_pageview_id bigint(20) unsigned DEFAULT NULL,
		landing_page varchar(2048) DEFAULT NULL,
		exit_page varchar(2048) DEFAULT NULL,
		referrer varchar(2048) DEFAULT NULL,
		utm_source varchar(255) DEFAULT NULL,
		utm_medium varchar(255) DEFAULT NULL,
		utm_campaign varchar(255) DEFAULT NULL,
		utm_term varchar(255) DEFAULT NULL,
		utm_content varchar(255) DEFAULT NULL,
		device_type enum('desktop','tablet','mobile') DEFAULT 'desktop',
		browser varchar(50) DEFAULT NULL,
		os varchar(50) DEFAULT NULL,
		country_code varchar(2) DEFAULT NULL,
		pageviews_count int(10) unsigned DEFAULT 1,
		interactions_count int(10) unsigned DEFAULT 0,
		max_scroll_depth int(3) unsigned DEFAULT 0,
		total_time_seconds int(10) unsigned DEFAULT 0,
		is_bounce tinyint(1) DEFAULT 0,
		ip_hash varchar(64) DEFAULT NULL,
		started_at datetime DEFAULT CURRENT_TIMESTAMP,
		ended_at datetime DEFAULT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY session_id_unique (session_id),
		KEY user_idx (user_id),
		KEY started_at_idx (started_at),
		KEY device_idx (device_type),
		KEY is_bounce_idx (is_bounce)
	) $charset_collate;";
	dbDelta( $sql_sessions );

	// User stats summary table - per-user aggregated statistics.
	$table_user_stats = $wpdb->prefix . 'apollo_analytics_user_stats';
	$sql_user_stats   = "CREATE TABLE $table_user_stats (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		stat_date date NOT NULL,
		profile_views int(10) unsigned DEFAULT 0,
		event_views int(10) unsigned DEFAULT 0,
		post_views int(10) unsigned DEFAULT 0,
		total_clicks int(10) unsigned DEFAULT 0,
		total_scrolls int(10) unsigned DEFAULT 0,
		avg_time_on_content int(10) unsigned DEFAULT 0,
		unique_visitors int(10) unsigned DEFAULT 0,
		returning_visitors int(10) unsigned DEFAULT 0,
		social_shares int(10) unsigned DEFAULT 0,
		comments_received int(10) unsigned DEFAULT 0,
		likes_received int(10) unsigned DEFAULT 0,
		followers_gained int(10) unsigned DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY user_date_unique (user_id, stat_date),
		KEY user_idx (user_id),
		KEY stat_date_idx (stat_date)
	) $charset_collate;";
	dbDelta( $sql_user_stats );

	// Content stats table - per-content aggregated statistics.
	$table_content_stats = $wpdb->prefix . 'apollo_analytics_content_stats';
	$sql_content_stats   = "CREATE TABLE $table_content_stats (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) unsigned NOT NULL,
		post_type varchar(50) NOT NULL,
		author_id bigint(20) unsigned NOT NULL,
		stat_date date NOT NULL,
		views int(10) unsigned DEFAULT 0,
		unique_views int(10) unsigned DEFAULT 0,
		avg_time_seconds int(10) unsigned DEFAULT 0,
		avg_scroll_depth int(3) unsigned DEFAULT 0,
		clicks int(10) unsigned DEFAULT 0,
		shares int(10) unsigned DEFAULT 0,
		comments int(10) unsigned DEFAULT 0,
		likes int(10) unsigned DEFAULT 0,
		bounce_rate decimal(5,2) DEFAULT 0.00,
		PRIMARY KEY (id),
		UNIQUE KEY post_date_unique (post_id, stat_date),
		KEY post_id_idx (post_id),
		KEY author_idx (author_id),
		KEY post_type_idx (post_type),
		KEY stat_date_idx (stat_date)
	) $charset_collate;";
	dbDelta( $sql_content_stats );

	// Heatmap data table - aggregated click/scroll positions.
	$table_heatmap = $wpdb->prefix . 'apollo_analytics_heatmap';
	$sql_heatmap   = "CREATE TABLE $table_heatmap (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		page_url_hash varchar(64) NOT NULL,
		page_url varchar(2048) NOT NULL,
		position_x_percent decimal(5,2) NOT NULL,
		position_y_percent decimal(5,2) NOT NULL,
		interaction_type enum('click','scroll_stop','hover') DEFAULT 'click',
		count int(10) unsigned DEFAULT 1,
		device_type enum('desktop','tablet','mobile') DEFAULT 'desktop',
		stat_date date NOT NULL,
		PRIMARY KEY (id),
		KEY page_hash_idx (page_url_hash),
		KEY stat_date_idx (stat_date),
		KEY device_idx (device_type)
	) $charset_collate;";
	dbDelta( $sql_heatmap );

	// User stats visibility settings.
	$table_stats_settings = $wpdb->prefix . 'apollo_analytics_settings';
	$sql_stats_settings   = "CREATE TABLE $table_stats_settings (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		setting_key varchar(100) NOT NULL,
		setting_value longtext,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY user_setting_unique (user_id, setting_key),
		KEY user_idx (user_id)
	) $charset_collate;";
	dbDelta( $sql_stats_settings );
}

/**
 * Log mod action to database
 *
 * @param int    $actor_id    User ID performing the action.
 * @param string $action      Action name (e.g., 'approve_post', 'suspend_user').
 * @param string $target_type Type of target (e.g., 'post', 'user', 'event_listing').
 * @param int    $target_id   ID of target object.
 * @param array  $details     Additional details as associative array.
 * @return int|false Insert ID on success, false on failure.
 */
function apollo_mod_log_action( int $actor_id, string $action, string $target_type, int $target_id, array $details = array() ): int|false {
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
 * Get mod log entries
 *
 * @param array $args Query arguments.
 * @return array Array of log entries.
 */
function apollo_get_mod_log( array $args = array() ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_log';

	// Check if table exists, if not create it.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( ! $table_exists ) {
		create_db_tables();
	}

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
	$where  = array( '1=1' );
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
function apollo_cleanup_mod_log( int $days = 90 ): int|false {
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
