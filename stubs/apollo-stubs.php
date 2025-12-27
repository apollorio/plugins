<?php
declare( strict_types=1 );

/**
 * Apollo-specific function stubs.
 * Add only functions actually used in your codebase.
 * Update signatures based on real usage.
 */

/**
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function apollo_get_config( string $key, $default = null ) {
	return $default;
}

/**
 * @param string $asset
 * @param array<string, mixed> $args
 * @return string
 */
function apollo_asset_url( string $asset, array $args = array() ) : string {
	return '';
}

/**
 * @param string $message
 * @param array<string, mixed> $context
 * @return void
 */
function apollo_log( string $message, array $context = array() ) : void {}

/**
 * @param string $action
 * @return void
 */
function apollo_mod_log_action( string $action ) : void {}

/**
 * @return array<mixed>
 */
function apollo_get_mod_log() : array {
	return array();
}
