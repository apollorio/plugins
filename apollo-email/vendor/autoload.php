<?php
/**
 * Composer autoloader fallback
 *
 * This file is loaded if composer autoload is not available.
 * It provides a simple PSR-4 autoloader for the plugin.
 *
 * @package ApolloEmail
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if composer autoload exists.
$composer_autoload = APOLLO_EMAIL_PLUGIN_DIR . 'vendor/autoload.php';

if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
	return;
}

/**
 * Simple PSR-4 autoloader for ApolloEmail namespace
 *
 * @param string $class Class name to load.
 */
spl_autoload_register(
	function ( $class ) {
		// Only autoload classes in ApolloEmail namespace.
		if ( strpos( $class, 'ApolloEmail\\' ) !== 0 ) {
			return;
		}

		// Convert namespace to file path.
		$relative_class = str_replace( 'ApolloEmail\\', '', $class );
		$file = APOLLO_EMAIL_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		// Load the file if it exists.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
