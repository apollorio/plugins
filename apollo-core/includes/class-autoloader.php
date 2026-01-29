<?php

declare(strict_types=1);

namespace Apollo_Core;

/**
 * PSR-4 Autoloader
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Core Autoloader
 */
class Autoloader {

	/**
	 * Namespace prefix
	 *
	 * @var string
	 */
	private string $namespace_prefix = 'Apollo_Core\\';

	/**
	 * Base directory
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->base_dir = APOLLO_CORE_PLUGIN_DIR . 'includes/';
	}

	/**
	 * Register autoloader
	 *
	 * @return void
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Autoload classes
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public function autoload( string $class ): void {
		// Check if class uses our namespace.
		$len = strlen( $this->namespace_prefix );
		if ( 0 !== strncmp( $this->namespace_prefix, $class, $len ) ) {
			return;
		}

		// Get relative class name.
		$relative_class = substr( $class, $len );

		// Convert namespace to file path.
		$file = $this->base_dir . 'class-' . strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), $relative_class ) ) . '.php';

		// Require file if exists.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
