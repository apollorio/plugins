<?php
declare(strict_types=1);
namespace Apollo\Modules\Subscriptions;

final class SubscriptionsSchema {
	private const VERSION='1.0.0';

	public static function install(): void {
		global $wpdb;
		$charset=$wpdb->get_charset_collate();
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		self::createPlansTable($wpdb,$charset);
		self::createSubscriptionsTable($wpdb,$charset);
		self::createHistoryTable($wpdb,$charset);
		self::createCouponsTable($wpdb,$charset);
		self::createCouponUsageTable($wpdb,$charset);

		update_option('apollo_subscriptions_schema_version',self::VERSION);
	}

	private static function createPlansTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_subscription_plans';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(100) NOT NULL,
			slug VARCHAR(100) NOT NULL,
			description TEXT,
			price DECIMAL(10,2) NOT NULL DEFAULT 0,
			currency VARCHAR(3) DEFAULT 'BRL',
			duration_days INT UNSIGNED NOT NULL DEFAULT 30,
			features JSON,
			limits JSON,
			badge_id BIGINT UNSIGNED,
			member_type_id BIGINT UNSIGNED,
			is_active TINYINT(1) DEFAULT 1,
			is_featured TINYINT(1) DEFAULT 0,
			sort_order INT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_slug (slug),
			INDEX idx_active (is_active),
			INDEX idx_order (sort_order)
		) {$c};");
	}

	private static function createSubscriptionsTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_subscriptions';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			plan_id BIGINT UNSIGNED NOT NULL,
			status ENUM('active','paused','cancelled','expired','replaced') DEFAULT 'active',
			start_date DATETIME NOT NULL,
			end_date DATETIME NOT NULL,
			paused_at DATETIME,
			cancelled_at DATETIME,
			payment_method VARCHAR(50),
			transaction_id VARCHAR(255),
			amount_paid DECIMAL(10,2) DEFAULT 0,
			currency VARCHAR(3) DEFAULT 'BRL',
			coupon_id BIGINT UNSIGNED,
			discount_amount DECIMAL(10,2) DEFAULT 0,
			auto_renew TINYINT(1) DEFAULT 1,
			renewal_notified TINYINT(1) DEFAULT 0,
			created_at DATETIME NOT NULL,
			INDEX idx_user (user_id),
			INDEX idx_plan (plan_id),
			INDEX idx_status (status),
			INDEX idx_end (end_date),
			INDEX idx_created (created_at)
		) {$c};");
	}

	private static function createHistoryTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_subscription_history';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			subscription_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(50) NOT NULL,
			details TEXT,
			created_at DATETIME NOT NULL,
			INDEX idx_sub (subscription_id),
			INDEX idx_action (action),
			INDEX idx_created (created_at)
		) {$c};");
	}

	private static function createCouponsTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_subscription_coupons';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			code VARCHAR(50) NOT NULL,
			discount_type ENUM('percent','fixed') DEFAULT 'percent',
			discount_value DECIMAL(10,2) NOT NULL,
			plan_ids JSON,
			max_uses INT UNSIGNED,
			uses_count INT UNSIGNED DEFAULT 0,
			min_amount DECIMAL(10,2),
			valid_from DATETIME,
			valid_until DATETIME,
			is_active TINYINT(1) DEFAULT 1,
			created_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_code (code),
			INDEX idx_active (is_active),
			INDEX idx_valid (valid_from,valid_until)
		) {$c};");
	}

	private static function createCouponUsageTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_coupon_usage';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			coupon_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			subscription_id BIGINT UNSIGNED NOT NULL,
			discount_applied DECIMAL(10,2) NOT NULL,
			used_at DATETIME NOT NULL,
			UNIQUE KEY uniq_usage (coupon_id,subscription_id),
			INDEX idx_coupon (coupon_id),
			INDEX idx_user (user_id)
		) {$c};");
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables=['apollo_subscription_plans','apollo_subscriptions','apollo_subscription_history','apollo_subscription_coupons','apollo_coupon_usage'];
		foreach($tables as $t){
			$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");
		}
		delete_option('apollo_subscriptions_schema_version');
	}
}
