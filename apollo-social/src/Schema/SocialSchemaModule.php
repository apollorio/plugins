<?php
/**
 * Social Schema Module
 * Implementa SchemaModuleInterface para integração com SchemaOrchestrator.
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Schema;

use Apollo_Core\Schema\SchemaModuleInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class SocialSchemaModule implements SchemaModuleInterface {
	private const MODULE_NAME = 'social';
	private const VERSION = '1.0.0';
	private const VERSION_OPTION = 'apollo_social_schema_version';

	public function getModuleName(): string { return self::MODULE_NAME; }
	public function getVersion(): string { return self::VERSION; }
	public function getVersionOption(): string { return self::VERSION_OPTION; }
	public function getStoredVersion(): string { return get_option(self::VERSION_OPTION, '0.0.0'); }
	public function needsUpgrade(): bool { return version_compare($this->getStoredVersion(), self::VERSION, '<'); }

	public function getTables(): array {
		return [
			'apollo_social_groups',
			'apollo_social_feed',
			'apollo_social_likes',
			'apollo_social_documents',
			'apollo_social_signatures',
			'apollo_supplier_views',
			'apollo_classified_views',
			'apollo_newsletter_subscribers',
			'apollo_newsletter_campaigns',
			'apollo_cena_signatures',
		];
	}

	public function getIndexes(): array {
		return [
			'apollo_social_likes' => ['user_id_idx', 'post_id_idx', 'created_at_idx'],
			'apollo_social_feed' => ['user_id_idx', 'group_id_idx', 'created_at_idx'],
			'apollo_supplier_views' => ['supplier_id_idx', 'user_id_idx', 'viewed_at_idx'],
			'apollo_classified_views' => ['classified_id_idx', 'user_id_idx', 'viewed_at_idx'],
		];
	}

	public function install(): void {
		// Chame aqui a lógica de criação de tabelas do social
		// Exemplo: Apollo_Social\Schema\install_social_tables();
		update_option(self::VERSION_OPTION, self::VERSION);
	}
	public function upgrade(string $from_version): void {
		$this->install();
	}
	public function uninstall(): void {
		delete_option(self::VERSION_OPTION);
	}
}
