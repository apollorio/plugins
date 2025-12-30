<?php
/**
 * Apollo Schema Facade
 *
 * Single authoritative entry point for all database schema operations.
 * Orchestrates module-specific schemas with versioned migrations.
 *
 * @package Apollo
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo;

use Apollo\Contracts\SchemaModuleInterface;
use Apollo\Modules\Documents\DocumentsSchema;
use Apollo\Modules\Chat\ChatSchema;
use Apollo\Modules\Likes\LikesSchema;
use Apollo\Infrastructure\Database\CoreSchema;
use WP_Error;

/**
 * Schema Manager - Facade for all Apollo database operations.
 */
final class Schema {

	/** @var string Schema version option key */
	public const VERSION_OPTION = 'apollo_schema_version';

	/** @var string Current schema version */
	public const CURRENT_VERSION = '2.2.0';

	/** @var array<string, SchemaModuleInterface> Registered module schemas */
	private array $modules = array();

	/** @var bool Whether modules have been loaded */
	private bool $loaded = false;

	/**
	 * Constructor - lazy loads modules.
	 */
	public function __construct() {
		// Modules loaded on first use.
	}

	/**
	 * Install all schemas (idempotent).
	 *
	 * Called on plugin activation.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function install() {
		$this->loadModules();

		try {
			// Core tables first.
			if ( class_exists( CoreSchema::class ) ) {
				$core = new CoreSchema();
				$core->install();
			}

			// Module tables.
			foreach ( $this->modules as $name => $module ) {
				$result = $module->install();
				if ( is_wp_error( $result ) ) {
					$this->logError( "Install failed for module: {$name}", $result );
					return $result;
				}
			}

			update_option( self::VERSION_OPTION, self::CURRENT_VERSION );

			return true;

		} catch ( \Throwable $e ) {
			$this->logError( 'Schema install exception', $e );
			return new WP_Error(
				'apollo_schema_install_failed',
				$e->getMessage(),
				array( 'exception' => get_class( $e ) )
			);
		}
	}

	/**
	 * Upgrade schemas (version-gated, idempotent).
	 *
	 * Called on plugins_loaded when version differs.
	 *
	 * @return true|WP_Error True on success or no upgrade needed, WP_Error on failure.
	 */
	public function upgrade() {
		$stored = get_option( self::VERSION_OPTION, '0.0.0' );

		// Already up to date.
		if ( version_compare( $stored, self::CURRENT_VERSION, '>=' ) ) {
			return true;
		}

		$this->loadModules();

		try {
			// Core upgrades first.
			if ( class_exists( CoreSchema::class ) ) {
				$core = new CoreSchema();
				$core->upgrade( $stored, self::CURRENT_VERSION );
			}

			// Module upgrades.
			foreach ( $this->modules as $name => $module ) {
				$result = $module->upgrade( $stored, self::CURRENT_VERSION );
				if ( is_wp_error( $result ) ) {
					$this->logError( "Upgrade failed for module: {$name}", $result );
					return $result;
				}
			}

			update_option( self::VERSION_OPTION, self::CURRENT_VERSION );

			return true;

		} catch ( \Throwable $e ) {
			$this->logError( 'Schema upgrade exception', $e );
			return new WP_Error(
				'apollo_schema_upgrade_failed',
				$e->getMessage(),
				array( 'exception' => get_class( $e ) )
			);
		}
	}

	/**
	 * Check if upgrade is needed.
	 *
	 * @return bool True if stored version < current version.
	 */
	public function needsUpgrade(): bool {
		$stored = get_option( self::VERSION_OPTION, '0.0.0' );
		return version_compare( $stored, self::CURRENT_VERSION, '<' );
	}

	/**
	 * Get current stored schema version.
	 *
	 * @return string Stored version.
	 */
	public function getStoredVersion(): string {
		return get_option( self::VERSION_OPTION, '0.0.0' );
	}

	/**
	 * Get installation status for all tables.
	 *
	 * @return array<string, mixed> Status report.
	 */
	public function getStatus(): array {
		$this->loadModules();

		$status = array(
			'version_stored'  => $this->getStoredVersion(),
			'version_current' => self::CURRENT_VERSION,
			'needs_upgrade'   => $this->needsUpgrade(),
			'modules'         => array(),
		);

		foreach ( $this->modules as $name => $module ) {
			$status['modules'][ $name ] = $module->getStatus();
		}

		return $status;
	}

	/**
	 * Uninstall all schemas (optional, no destructive drops by default).
	 *
	 * @param bool $drop_tables Whether to actually drop tables.
	 * @return true|WP_Error
	 */
	public function uninstall( bool $drop_tables = false ) {
		if ( ! $drop_tables ) {
			// Safe default: just remove version option.
			delete_option( self::VERSION_OPTION );
			return true;
		}

		$this->loadModules();

		try {
			foreach ( $this->modules as $module ) {
				$module->uninstall();
			}

			if ( class_exists( CoreSchema::class ) ) {
				$core = new CoreSchema();
				$core->uninstall();
			}

			delete_option( self::VERSION_OPTION );

			return true;

		} catch ( \Throwable $e ) {
			$this->logError( 'Schema uninstall exception', $e );
			return new WP_Error(
				'apollo_schema_uninstall_failed',
				$e->getMessage()
			);
		}
	}

	/**
	 * Load all module schemas.
	 */
	private function loadModules(): void {
		if ( $this->loaded ) {
			return;
		}

		// Documents module.
		if ( class_exists( DocumentsSchema::class ) ) {
			$this->modules['documents'] = new DocumentsSchema();
		}

		// Chat module.
		if ( class_exists( ChatSchema::class ) ) {
			$this->modules['chat'] = new ChatSchema();
		}

		// Likes module.
		if ( class_exists( LikesSchema::class ) ) {
			$this->modules['likes'] = new LikesSchema();
		}

		/**
		 * Filter to register additional module schemas.
		 *
		 * @param array<string, SchemaModuleInterface> $modules Registered modules.
		 */
		$this->modules = apply_filters( 'apollo_schema_modules', $this->modules );

		$this->loaded = true;
	}

	/**
	 * Log schema error.
	 *
	 * @param string          $message Error message.
	 * @param \Throwable|WP_Error $error   Error object.
	 */
	private function logError( string $message, $error ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$details = $error instanceof WP_Error
			? $error->get_error_message()
			: $error->getMessage();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( "Apollo Schema: {$message} - {$details}" );
	}
}
