<?php
/**
 * Events Schema Module
 * Implementa SchemaModuleInterface para integração com SchemaOrchestrator.
 *
 * @package Apollo_Events_Manager
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Schema;

use Apollo_Core\Schema\SchemaModuleInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class EventsSchemaModule implements SchemaModuleInterface {
	private const MODULE_NAME = 'events';
	private const VERSION = '1.0.0';
	private const VERSION_OPTION = 'apollo_events_schema_version';

	public function getModuleName(): string { return self::MODULE_NAME; }
	public function getVersion(): string { return self::VERSION; }
	public function getVersionOption(): string { return self::VERSION_OPTION; }
	public function getStoredVersion(): string { return get_option(self::VERSION_OPTION, '0.0.0'); }
	public function needsUpgrade(): bool { return version_compare($this->getStoredVersion(), self::VERSION, '<'); }

	public function getTables(): array {
		return [
			'apollo_events',
			'apollo_event_dj_slots',
			'apollo_event_meta',
			'apollo_event_bookmarks',
			'apollo_analytics',
			'aprio_compatibilidade_users_meetings',
		];
	}

	public function getIndexes(): array {
		return [
			'apollo_event_bookmarks' => ['user_id_idx', 'event_id_idx', 'created_at_idx'],
			'apollo_analytics' => ['event_id_idx', 'user_id_idx', 'action_type_idx', 'created_at_idx'],
		];
	}

	public function install(): void {
		// Chame aqui a lógica de criação de tabelas do events
		// Exemplo: Apollo_Events_Manager\Schema\install_events_tables();
		update_option(self::VERSION_OPTION, self::VERSION);
	}
	public function upgrade(string $from_version): void {
		$this->install();
	}
	public function uninstall(): void {
		delete_option(self::VERSION_OPTION);
	}
}
