<?php
/**
 * Email Database Schema
 *
 * Creates database tables for email service.
 *
 * @package ApolloEmail\Schema
 */

declare(strict_types=1);

namespace ApolloEmail\Schema;

/**
 * Email Schema Class
 */
class EmailSchema {

	/**
	 * Create all email-related database tables
	 */
	public static function create_tables(): void {
		self::create_email_queue_table();
		self::create_email_log_table();
		self::create_email_security_log_table();
	}

	/**
	 * Create email queue table
	 */
	private static function create_email_queue_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_email_queue';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recipient_id bigint(20) unsigned DEFAULT NULL,
			recipient_email varchar(255) NOT NULL,
			subject text NOT NULL,
			body longtext NOT NULL,
			template varchar(100) DEFAULT NULL,
			priority enum('low','normal','high','urgent') DEFAULT 'normal',
			status enum('pending','processing','sent','failed') DEFAULT 'pending',
			scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime DEFAULT NULL,
			error_message text DEFAULT NULL,
			retry_count int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status_priority (status, priority),
			KEY recipient_id (recipient_id),
			KEY scheduled_at (scheduled_at),
			KEY template (template)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create email log table
	 */
	private static function create_email_log_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_email_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_id bigint(20) unsigned DEFAULT NULL,
			action varchar(50) NOT NULL,
			status enum('success','failed') NOT NULL,
			message text DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email_id (email_id),
			KEY action (action),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create email security log table
	 */
	private static function create_email_security_log_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_email_security_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(50) NOT NULL,
			severity enum('info','warning','error','critical') DEFAULT 'info',
			user_id bigint(20) unsigned DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			description text NOT NULL,
			metadata longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_type (event_type),
			KEY severity (severity),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
