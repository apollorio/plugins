<?php
/**
 * Chat Schema
 *
 * Creates and manages Chat module database tables.
 *
 * Tables:
 * - wp_apollo_chat_conversations
 * - wp_apollo_chat_messages
 * - wp_apollo_chat_participants
 *
 * @package Apollo\Modules\Chat
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Chat;

use Apollo\Contracts\SchemaModuleInterface;
use WP_Error;

/**
 * Chat Schema - Database migrations for Chat module.
 */
class ChatSchema implements SchemaModuleInterface {

	/** @var string Conversations table */
	private const TABLE_CONVERSATIONS = 'apollo_chat_conversations';

	/** @var string Messages table */
	private const TABLE_MESSAGES = 'apollo_chat_messages';

	/** @var string Participants table */
	private const TABLE_PARTICIPANTS = 'apollo_chat_participants';

	/**
	 * Install Chat schema (idempotent).
	 *
	 * @return true|WP_Error
	 */
	public function install() {
		try {
			$this->createConversationsTable();
			$this->createMessagesTable();
			$this->createParticipantsTable();
			return true;
		} catch ( \Throwable $e ) {
			return new WP_Error( 'chat_schema_install_failed', $e->getMessage() );
		}
	}

	/**
	 * Upgrade Chat schema.
	 *
	 * @param string $fromVersion Current version.
	 * @param string $toVersion   Target version.
	 * @return true|WP_Error
	 */
	public function upgrade( string $fromVersion, string $toVersion ) {
		// Re-run install to ensure tables exist.
		return $this->install();
	}

	/**
	 * Get table status.
	 *
	 * @return array<string, bool>
	 */
	public function getStatus(): array {
		global $wpdb;

		$conv = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$msgs = $wpdb->prefix . self::TABLE_MESSAGES;
		$part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		return array(
			'conversations' => $wpdb->get_var( "SHOW TABLES LIKE '{$conv}'" ) === $conv,
			'messages'      => $wpdb->get_var( "SHOW TABLES LIKE '{$msgs}'" ) === $msgs,
			'participants'  => $wpdb->get_var( "SHOW TABLES LIKE '{$part}'" ) === $part,
		);
	}

	/**
	 * Uninstall Chat schema.
	 */
	public function uninstall(): void {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . self::TABLE_MESSAGES );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . self::TABLE_PARTICIPANTS );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . self::TABLE_CONVERSATIONS );
	}

	/**
	 * Create conversations table.
	 */
	private function createConversationsTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type enum('direct','group','nucleo','comunidade','classified','supplier') NOT NULL DEFAULT 'direct',
			title varchar(255) NULL,
			context_type varchar(50) NULL,
			context_id bigint(20) unsigned NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY type_idx (type),
			KEY context_idx (context_type,context_id),
			KEY updated_idx (updated_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create messages table.
	 */
	private function createMessagesTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE_MESSAGES;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			sender_id bigint(20) unsigned NOT NULL,
			message_type enum('text','image','file','system') NOT NULL DEFAULT 'text',
			content longtext NOT NULL,
			metadata longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY conversation_idx (conversation_id),
			KEY sender_idx (sender_id),
			KEY created_idx (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create participants table.
	 */
	private function createParticipantsTable(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE_PARTICIPANTS;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role enum('owner','admin','member') NOT NULL DEFAULT 'member',
			last_read_at datetime NULL,
			joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY conv_user_uk (conversation_id,user_id),
			KEY conversation_idx (conversation_id),
			KEY user_idx (user_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
