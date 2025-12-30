<?php
declare(strict_types=1);
namespace Apollo\Infrastructure\Database;

final class EventsSchema {

	public static function install(): void {
		self::createEventsTable();
		self::createEventAttendeesTable();
		self::createEventInterestedTable();
		self::createEventCategoriesTable();
		self::createEventMetaTable();
		self::createEventRemindersTable();
		self::createEventTicketsTable();
		self::createEventTicketOrdersTable();
	}

	public static function upgrade(string $from, string $to): void {
		self::install();
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables=['apollo_events','apollo_event_attendees','apollo_event_interested',
			'apollo_event_categories','apollo_event_meta','apollo_event_reminders',
			'apollo_event_tickets','apollo_event_ticket_orders'];
		foreach($tables as $t){$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");}
	}

	private static function createEventsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_events';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description longtext,
			venue varchar(255) DEFAULT NULL,
			address varchar(500) DEFAULT NULL,
			city varchar(100) DEFAULT NULL,
			state varchar(100) DEFAULT NULL,
			country varchar(2) DEFAULT 'BR',
			zip varchar(20) DEFAULT NULL,
			latitude decimal(10,8) DEFAULT NULL,
			longitude decimal(11,8) DEFAULT NULL,
			start_date datetime NOT NULL,
			end_date datetime DEFAULT NULL,
			timezone varchar(50) NOT NULL DEFAULT 'America/Sao_Paulo',
			is_all_day tinyint(1) NOT NULL DEFAULT 0,
			is_recurring tinyint(1) NOT NULL DEFAULT 0,
			recurrence_rule text,
			parent_event_id bigint(20) unsigned DEFAULT NULL,
			max_attendees int(11) DEFAULT NULL,
			registration_deadline datetime DEFAULT NULL,
			price decimal(10,2) NOT NULL DEFAULT 0,
			currency varchar(3) NOT NULL DEFAULT 'BRL',
			cover_image varchar(500) DEFAULT NULL,
			status enum('draft','published','cancelled','completed') NOT NULL DEFAULT 'published',
			visibility enum('public','private','group') NOT NULL DEFAULT 'public',
			organizer_id bigint(20) unsigned NOT NULL,
			group_id bigint(20) unsigned DEFAULT NULL,
			category_id bigint(20) unsigned DEFAULT NULL,
			views int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY organizer_idx (organizer_id),
			KEY group_idx (group_id),
			KEY category_idx (category_id),
			KEY start_date_idx (start_date),
			KEY status_idx (status),
			KEY visibility_idx (visibility),
			KEY location_idx (city,country),
			KEY coords_idx (latitude,longitude)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventAttendeesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_attendees';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			status enum('going','maybe','not_going','waitlist') NOT NULL DEFAULT 'going',
			tickets int(11) NOT NULL DEFAULT 1,
			checked_in tinyint(1) NOT NULL DEFAULT 0,
			checked_in_at datetime DEFAULT NULL,
			notes text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY event_user_uk (event_id,user_id),
			KEY user_idx (user_id),
			KEY status_idx (status),
			KEY checked_idx (checked_in)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventInterestedTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_interested';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY event_user_uk (event_id,user_id),
			KEY user_idx (user_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventCategoriesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_categories';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			icon varchar(50) DEFAULT NULL,
			color varchar(7) DEFAULT '#3b82f6',
			parent_id bigint(20) unsigned DEFAULT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			event_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY parent_idx (parent_id),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventMetaTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_meta';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			PRIMARY KEY (meta_id),
			KEY event_key_idx (event_id,meta_key)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventRemindersTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_reminders';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			remind_at datetime NOT NULL,
			type enum('email','push','both') NOT NULL DEFAULT 'email',
			sent tinyint(1) NOT NULL DEFAULT 0,
			sent_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_idx (event_id),
			KEY user_idx (user_id),
			KEY remind_idx (remind_at,sent)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventTicketsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_tickets';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			name varchar(100) NOT NULL,
			description text,
			price decimal(10,2) NOT NULL DEFAULT 0,
			quantity int(11) DEFAULT NULL,
			sold int(11) NOT NULL DEFAULT 0,
			max_per_order int(11) NOT NULL DEFAULT 10,
			sale_start datetime DEFAULT NULL,
			sale_end datetime DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_idx (event_id),
			KEY active_idx (is_active)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createEventTicketOrdersTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_ticket_orders';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			ticket_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			order_number varchar(32) NOT NULL,
			quantity int(11) NOT NULL DEFAULT 1,
			total decimal(10,2) NOT NULL,
			status enum('pending','paid','cancelled','refunded') NOT NULL DEFAULT 'pending',
			payment_method varchar(50) DEFAULT NULL,
			payment_id varchar(100) DEFAULT NULL,
			attendee_name varchar(255) DEFAULT NULL,
			attendee_email varchar(255) DEFAULT NULL,
			qr_code varchar(255) DEFAULT NULL,
			used tinyint(1) NOT NULL DEFAULT 0,
			used_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY order_number_uk (order_number),
			KEY event_idx (event_id),
			KEY ticket_idx (ticket_id),
			KEY user_idx (user_id),
			KEY status_idx (status),
			KEY qr_idx (qr_code)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
