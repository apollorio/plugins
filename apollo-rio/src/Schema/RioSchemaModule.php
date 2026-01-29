<?php
/**
 * Rio Schema Module
 * Implementa SchemaModuleInterface para integração com SchemaOrchestrator.
 *
 * NOTA: apollo-rio é um plugin de PWA/SEO e não registra tabelas customizadas.
 * Este módulo existe apenas para consistência arquitetural com a suíte Apollo.
 *
 * @package Apollo_Rio
 * @since 1.0.0
 * @created 7 Jan 2026
 */

declare(strict_types=1);

namespace Apollo_Rio\Schema;

use Apollo_Core\Schema\SchemaModuleInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class RioSchemaModule implements SchemaModuleInterface {
	private const MODULE_NAME = 'rio';
	private const VERSION = '1.0.0';
	private const VERSION_OPTION = 'apollo_rio_schema_version';

	public function getModuleName(): string { return self::MODULE_NAME; }
	public function getVersion(): string { return self::VERSION; }
	public function getVersionOption(): string { return self::VERSION_OPTION; }
	public function getStoredVersion(): string { return get_option(self::VERSION_OPTION, '0.0.0'); }
	public function needsUpgrade(): bool { return version_compare($this->getStoredVersion(), self::VERSION, '<'); }

	/**
	 * Rio não registra tabelas customizadas.
	 * Mantido para consistência com SchemaOrchestrator.
	 */
	public function getTables(): array {
		return [];
	}

	public function getIndexes(): array {
		return [];
	}

	public function install(): void {
		// Rio não tem tabelas para instalar
		// Apenas atualiza versão para tracking
		update_option(self::VERSION_OPTION, self::VERSION);
	}

	public function upgrade(string $from_version): void {
		$this->install();
	}

	public function uninstall(): void {
		delete_option(self::VERSION_OPTION);
	}
}
