<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - REST API Module Loader
 *
 * Loads the REST API module if available
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rest_api_file = __DIR__ . '/rest-api/wpem-rest-api.php';

if ( file_exists( $rest_api_file ) ) {
	require_once $rest_api_file;

	if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
		error_log( 'Apollo Events Manager: REST API module loaded' );
	}
}
