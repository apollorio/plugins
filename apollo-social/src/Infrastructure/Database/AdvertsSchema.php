<?php
declare(strict_types=1);
namespace Apollo\Infrastructure\Database;

final class AdvertsSchema {

	public static function install(): void {
		self::createAdvertsTable();
		self::createAdvertImagesTable();
		self::createAdvertCategoriesTable();
		self::createAdvertFavoritesTable();
		self::createAdvertViewsTable();
		self::createAdvertMessagesTable();
		self::createAdvertReportsTable();
	}

	public static function upgrade( string $from, string $to ): void {
		self::install();
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables = array(
			'apollo_adverts',
			'apollo_advert_images',
			'apollo_advert_categories',
			'apollo_advert_favorites',
			'apollo_advert_views',
			'apollo_advert_messages',
			'apollo_advert_reports',
		);
		foreach ( $tables as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$t}" );}
	}

	private static function createAdvertsTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_adverts';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			slug varchar(280) NOT NULL,
			description longtext,
			price decimal(12,2) DEFAULT NULL,
			price_type enum('fixed','negotiable','free','contact','auction') NOT NULL DEFAULT 'fixed',
			currency varchar(3) NOT NULL DEFAULT 'BRL',
			category_id bigint(20) unsigned DEFAULT NULL,
			subcategory_id bigint(20) unsigned DEFAULT NULL,
			`condition` enum('new','like_new','good','fair','parts') NOT NULL DEFAULT 'new',
			city varchar(100) DEFAULT NULL,
			state varchar(100) DEFAULT NULL,
			country varchar(2) NOT NULL DEFAULT 'BR',
			zip varchar(20) DEFAULT NULL,
			latitude decimal(10,8) DEFAULT NULL,
			longitude decimal(11,8) DEFAULT NULL,
			contact_phone varchar(20) DEFAULT NULL,
			contact_whatsapp varchar(20) DEFAULT NULL,
			contact_email varchar(255) DEFAULT NULL,
			user_id bigint(20) unsigned NOT NULL,
			status enum('draft','pending','published','rejected','sold','expired','archived') NOT NULL DEFAULT 'pending',
			rejection_reason text,
			featured tinyint(1) NOT NULL DEFAULT 0,
			featured_until datetime DEFAULT NULL,
			views int(11) NOT NULL DEFAULT 0,
			favorites_count int(11) NOT NULL DEFAULT 0,
			expires_at datetime DEFAULT NULL,
			approved_at datetime DEFAULT NULL,
			custom_fields longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY user_idx (user_id),
			KEY category_idx (category_id),
			KEY subcategory_idx (subcategory_id),
			KEY status_idx (status),
			KEY featured_idx (featured),
			KEY location_idx (city,state,country),
			KEY coords_idx (latitude,longitude),
			KEY price_idx (price),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function createAdvertImagesTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_advert_images';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			advert_id bigint(20) unsigned NOT NULL,
			image_url varchar(500) NOT NULL,
			thumbnail_url varchar(500) DEFAULT NULL,
			alt_text varchar(255) DEFAULT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			is_primary tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY advert_idx (advert_id),
			KEY primary_idx (is_primary),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function createAdvertCategoriesTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_advert_categories';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			icon varchar(50) DEFAULT NULL,
			color varchar(7) DEFAULT '#6b7280',
			parent_id bigint(20) unsigned DEFAULT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			advert_count int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			custom_fields longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY parent_idx (parent_id),
			KEY sort_idx (sort_order),
			KEY active_idx (is_active)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function createAdvertFavoritesTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_advert_favorites';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			advert_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY advert_user_uk (advert_id,user_id),
			KEY user_idx (user_id)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function createAdvertViewsTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_advert_views';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			advert_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(500) DEFAULT NULL,
			referer varchar(500) DEFAULT NULL,
			viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY advert_idx (advert_id),
			KEY user_idx (user_id),
			KEY viewed_idx (viewed_at)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function createAdvertMessagesTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_advert_messages';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			advert_id bigint(20) unsigned NOT NULL,
			sender_id bigint(20) unsigned NOT NULL,
			receiver_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			message text NOT NULL,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			read_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY advert_idx (advert_id),
			KEY sender_idx (sender_id),
			KEY receiver_idx (receiver_id),
			KEY parent_idx (parent_id),
			KEY read_idx (is_read)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function createAdvertReportsTable(): void {
		global $wpdb;
		$t   = $wpdb->prefix . 'apollo_advert_reports';
		$c   = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			advert_id bigint(20) unsigned NOT NULL,
			reporter_id bigint(20) unsigned NOT NULL,
			reason enum('spam','fake','prohibited','wrong_category','scam','other') NOT NULL,
			description text,
			status enum('pending','reviewed','dismissed','actioned') NOT NULL DEFAULT 'pending',
			reviewed_by bigint(20) unsigned DEFAULT NULL,
			reviewed_at datetime DEFAULT NULL,
			action_taken text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY advert_idx (advert_id),
			KEY reporter_idx (reporter_id),
			KEY status_idx (status)
		) {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
