<?php
declare(strict_types=1);
namespace Apollo\Modules\Forums;

final class ForumsSchema {
	private const VERSION = '1.0.0';

	public static function install(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		self::createForumsTable( $wpdb, $charset );
		self::createTopicsTable( $wpdb, $charset );
		self::createRepliesTable( $wpdb, $charset );
		self::createSubscriptionsTable( $wpdb, $charset );
		self::createModeratorsTable( $wpdb, $charset );

		update_option( 'apollo_forums_schema_version', self::VERSION );
	}

	private static function createForumsTable( $wpdb, string $c ): void {
		$t = $wpdb->prefix . 'apollo_forums';
		dbDelta(
			"CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			slug VARCHAR(255) NOT NULL,
			description TEXT,
			parent_id BIGINT UNSIGNED DEFAULT 0,
			group_id BIGINT UNSIGNED DEFAULT 0,
			visibility ENUM('public','members','private') DEFAULT 'public',
			sort_order INT UNSIGNED DEFAULT 0,
			topic_count INT UNSIGNED DEFAULT 0,
			is_active TINYINT(1) DEFAULT 1,
			created_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME,
			UNIQUE KEY uniq_slug (slug),
			INDEX idx_parent (parent_id),
			INDEX idx_group (group_id),
			INDEX idx_order (sort_order)
		) {$c};"
		);
	}

	private static function createTopicsTable( $wpdb, string $c ): void {
		$t = $wpdb->prefix . 'apollo_forum_topics';
		dbDelta(
			"CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			forum_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(255) NOT NULL,
			content LONGTEXT NOT NULL,
			is_sticky TINYINT(1) DEFAULT 0,
			is_closed TINYINT(1) DEFAULT 0,
			view_count INT UNSIGNED DEFAULT 0,
			reply_count INT UNSIGNED DEFAULT 0,
			last_reply_at DATETIME,
			last_reply_by BIGINT UNSIGNED,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			INDEX idx_forum (forum_id),
			INDEX idx_user (user_id),
			INDEX idx_sticky (is_sticky),
			INDEX idx_created (created_at),
			INDEX idx_last_reply (last_reply_at)
		) {$c};"
		);
	}

	private static function createRepliesTable( $wpdb, string $c ): void {
		$t = $wpdb->prefix . 'apollo_forum_replies';
		dbDelta(
			"CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			topic_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			content LONGTEXT NOT NULL,
			is_best_answer TINYINT(1) DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME,
			INDEX idx_topic (topic_id),
			INDEX idx_user (user_id),
			INDEX idx_created (created_at)
		) {$c};"
		);
	}

	private static function createSubscriptionsTable( $wpdb, string $c ): void {
		$t = $wpdb->prefix . 'apollo_forum_subscriptions';
		dbDelta(
			"CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			topic_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_sub (topic_id,user_id),
			INDEX idx_topic (topic_id),
			INDEX idx_user (user_id)
		) {$c};"
		);
	}

	private static function createModeratorsTable( $wpdb, string $c ): void {
		$t = $wpdb->prefix . 'apollo_forum_moderators';
		dbDelta(
			"CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			forum_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			added_by BIGINT UNSIGNED NOT NULL,
			added_at DATETIME NOT NULL,
			UNIQUE KEY uniq_mod (forum_id,user_id),
			INDEX idx_forum (forum_id),
			INDEX idx_user (user_id)
		) {$c};"
		);
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables = array( 'apollo_forums', 'apollo_forum_topics', 'apollo_forum_replies', 'apollo_forum_subscriptions', 'apollo_forum_moderators' );
		foreach ( $tables as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$t}" );
		}
		delete_option( 'apollo_forums_schema_version' );
	}
}
