<?php
declare(strict_types=1);
namespace Apollo\Modules\Albums;

final class AlbumsSchema {
	private const VERSION='1.0.0';

	public static function install(): void {
		global $wpdb;
		$charset=$wpdb->get_charset_collate();
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		self::createAlbumsTable($wpdb,$charset);
		self::createPhotosTable($wpdb,$charset);
		self::createPhotoLikesTable($wpdb,$charset);
		self::createPhotoCommentsTable($wpdb,$charset);
		self::createAlbumContributorsTable($wpdb,$charset);
		self::createPhotoTagsTable($wpdb,$charset);

		update_option('apollo_albums_schema_version',self::VERSION);
	}

	private static function createAlbumsTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_photo_albums';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(255) NOT NULL,
			description TEXT,
			privacy ENUM('public','friends','private') DEFAULT 'public',
			cover_photo_id BIGINT UNSIGNED,
			photo_count INT UNSIGNED DEFAULT 0,
			view_count INT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			INDEX idx_user (user_id),
			INDEX idx_privacy (privacy),
			INDEX idx_updated (updated_at)
		) {$c};");
	}

	private static function createPhotosTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_photos';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			album_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			attachment_id BIGINT UNSIGNED NOT NULL,
			caption VARCHAR(500),
			sort_order INT UNSIGNED DEFAULT 0,
			view_count INT UNSIGNED DEFAULT 0,
			like_count INT UNSIGNED DEFAULT 0,
			comment_count INT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL,
			INDEX idx_album (album_id),
			INDEX idx_user (user_id),
			INDEX idx_created (created_at)
		) {$c};");
	}

	private static function createPhotoLikesTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_photo_likes';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			photo_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_like (photo_id,user_id),
			INDEX idx_photo (photo_id),
			INDEX idx_user (user_id)
		) {$c};");
	}

	private static function createPhotoCommentsTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_photo_comments';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			photo_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			parent_id BIGINT UNSIGNED DEFAULT 0,
			content TEXT NOT NULL,
			is_hidden TINYINT(1) DEFAULT 0,
			created_at DATETIME NOT NULL,
			INDEX idx_photo (photo_id),
			INDEX idx_user (user_id),
			INDEX idx_parent (parent_id)
		) {$c};");
	}

	private static function createAlbumContributorsTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_album_contributors';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			album_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			added_by BIGINT UNSIGNED NOT NULL,
			added_at DATETIME NOT NULL,
			UNIQUE KEY uniq_contrib (album_id,user_id),
			INDEX idx_album (album_id),
			INDEX idx_user (user_id)
		) {$c};");
	}

	private static function createPhotoTagsTable($wpdb,string $c): void {
		$t=$wpdb->prefix.'apollo_photo_tags';
		dbDelta("CREATE TABLE {$t}(
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			photo_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			tagged_by BIGINT UNSIGNED NOT NULL,
			position_x DECIMAL(5,2),
			position_y DECIMAL(5,2),
			created_at DATETIME NOT NULL,
			UNIQUE KEY uniq_tag (photo_id,user_id),
			INDEX idx_photo (photo_id),
			INDEX idx_user (user_id)
		) {$c};");
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables=['apollo_photo_albums','apollo_photos','apollo_photo_likes','apollo_photo_comments','apollo_album_contributors','apollo_photo_tags'];
		foreach($tables as $t){
			$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");
		}
		delete_option('apollo_albums_schema_version');
	}
}
