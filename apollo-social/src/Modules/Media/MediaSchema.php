<?php
declare(strict_types=1);
namespace Apollo\Modules\Media;

final class MediaSchema {
	private const VERSION='1.0.0';

	public static function install(): void {
		global $wpdb;
		$charset=$wpdb->get_charset_collate();
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		self::createOffloadTable($wpdb,$charset);
		self::createMediaLibraryTable($wpdb,$charset);
		self::createMediaFoldersTable($wpdb,$charset);

		update_option('apollo_media_schema_version',self::VERSION);
	}

	private static function createOffloadTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_media_offload';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			attachment_id BIGINT UNSIGNED NOT NULL,
			provider VARCHAR(50) NOT NULL,
			remote_url VARCHAR(500) NOT NULL,
			remote_key VARCHAR(255) NOT NULL,
			file_size BIGINT UNSIGNED DEFAULT 0,
			file_type VARCHAR(100),
			status ENUM('active','error','restored','deleted') DEFAULT 'active',
			error_message TEXT,
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_attachment (attachment_id),
			INDEX idx_provider (provider),
			INDEX idx_status (status)
		) {$c};");
	}

	private static function createMediaLibraryTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_media_library';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			attachment_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			folder_id BIGINT UNSIGNED DEFAULT 0,
			privacy ENUM('public','friends','private') DEFAULT 'public',
			download_count INT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_attachment (attachment_id),
			INDEX idx_user (user_id),
			INDEX idx_folder (folder_id)
		) {$c};");
	}

	private static function createMediaFoldersTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_media_folders';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			parent_id BIGINT UNSIGNED DEFAULT 0,
			sort_order INT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL,
			INDEX idx_user (user_id),
			INDEX idx_parent (parent_id)
		) {$c};");
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables=['apollo_media_offload','apollo_media_library','apollo_media_folders'];
		foreach($tables as $t){
			$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");
		}
		delete_option('apollo_media_schema_version');
	}
}
