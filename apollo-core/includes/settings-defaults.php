<?php
declare(strict_types=1);

/**
 * Apollo Core - Moderation Settings Defaults
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get default mod settings
 *
 * @return array Default settings array
 */
function apollo_get_default_mod_settings(): array {
	return array(
		'mods'              => array(),
		'enabled_caps'      => array(
			'publish_events'      => false,
			'publish_locals'      => false,
			'publish_djs'         => false,
			'publish_nucleos'     => false,
			'publish_comunidades' => false,
			'edit_posts'          => true,
			'edit_classifieds'    => true,
		),
		'audit_log_enabled' => true,
		'version'           => '1.0.0',
	);
}

/**
 * Get current mod settings with defaults fallback
 *
 * @return array Settings array
 */
function apollo_get_mod_settings(): array {
	$settings = get_option( 'apollo_mod_settings', array() );
	return wp_parse_args( $settings, apollo_get_default_mod_settings() );
}

/**
 * Update mod settings
 *
 * @param array $settings Settings array to save.
 * @return bool True on success, false on failure.
 */
function apollo_update_mod_settings( $settings ) {
	$defaults = apollo_get_default_mod_settings();
	$settings = wp_parse_args( $settings, $defaults );

	// Validate structure.
	if ( ! is_array( $settings['mods'] ) ) {
		$settings['mods'] = array();
	}

	if ( ! is_array( $settings['enabled_caps'] ) ) {
		$settings['enabled_caps'] = $defaults['enabled_caps'];
	}

	return update_option( 'apollo_mod_settings', $settings );
}

/**
 * Check if capability is enabled for apollo role
 *
 * @param string $capability Capability to check.
 * @return bool True if enabled, false otherwise.
 */
function apollo_is_cap_enabled( $capability ) {
	$settings = apollo_get_mod_settings();
	return ! empty( $settings['enabled_caps'][ $capability ] );
}
