<?php
// phpcs:ignoreFile
declare(strict_types=1);

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
class Apollo_Core_Autoloader {
	/**
	 * Namespace prefix
	 *
	 * @var string
	 */
	private $namespace_prefix = 'Apollo_Core\\';

	/**
	 * Base directory
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->base_dir = APOLLO_CORE_PLUGIN_DIR . 'includes/';
	}

	/**
	 * Register autoloader
	 */
	public function register() {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Autoload classes
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		// Check if class uses our namespace.
		$len = strlen( $this->namespace_prefix );
		if ( strncmp( $this->namespace_prefix, $class, $len ) !== 0 ) {
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
