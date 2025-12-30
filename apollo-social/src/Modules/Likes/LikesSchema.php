<?php
/**
 * Likes Schema
 *
 * Creates and manages Likes module database table.
 *
 * @package Apollo\Modules\Likes
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Likes;

use Apollo\Contracts\SchemaModuleInterface;
use WP_Error;

/**
 * Likes Schema - Database migrations for Likes/WOW module.
 */
class LikesSchema implements SchemaModuleInterface {

	/** @var string Likes table */
	private const TABLE_LIKES = 'apollo_likes';

	/**
	 * Install Likes schema (idempotent).
	 *
	 * @return true|WP_Error
	 */
	public function install() {
		try {
			$this->createLikesTable();
			return true;
		} catch ( \Throwable $e ) {
			return new WP_Error( 'likes_schema_install_failed', $e->getMessage() );
		}
	}

	/**
	 * Upgrade Likes schema.
	 *
	 * @param string $fromVersion Current version.
	 * @param string $toVersion   Target version.
	 * @return true|WP_Error
	 */
	public function upgrade( string $fromVersion, string $toVersion ) {
		return $this->install();
	}

	/**
	 * Get table status.
	 *
	 * @return array<string, bool>
	 */
	public function getStatus(): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_LIKES;

		return array(
			'likes' => $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table,
		);
	}

	/**
	 * Uninstall Likes schema.
	 */
	public function uninstall(): void {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . self::TABLE_LIKES );
	}

	/**
	 * Create likes table.
	 */
	private function createLikesTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE_LIKES;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			content_type varchar(50) NOT NULL,
			content_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			liked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY content_user_uk (content_type,content_id,user_id),
			KEY content_idx (content_type,content_id),
			KEY user_idx (user_id),
			KEY liked_at_idx (liked_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
