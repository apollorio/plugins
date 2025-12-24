<?php
/**
 * WordPress Core Fix Helper
 *
 * Provides workarounds for WordPress core loading issues without modifying core files.
 * This ensures plugins work even if WordPress core has loading order problems.
 *
 * @package Apollo_Core
 * @version 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure WordPress core functions are available
 * This is a workaround for cases where WordPress core files aren't loaded in the correct order
 */
function apollo_ensure_wp_core_functions() {
	// Only run if we're in WordPress context.
	if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) ) {
		return;
	}

	// Check if wp_is_valid_utf8 exists, if not, try to load utf8.php
	if ( ! function_exists( 'wp_is_valid_utf8' ) ) {
		$utf8_file = ABSPATH . WPINC . '/utf8.php';
		if ( file_exists( $utf8_file ) && ! function_exists( 'wp_is_valid_utf8' ) ) {
			// Only load if function still doesn't exist after file check.
			// This prevents double-loading.
			require_once $utf8_file;
		}
	}

	// Verify core functions are available.
	$required_functions = array(
		'wp_is_valid_utf8',
		'sanitize_title',
		'esc_html',
		'esc_attr',
	);

	$missing = array();
	foreach ( $required_functions as $func ) {
		if ( ! function_exists( $func ) ) {
			$missing[] = $func;
		}
	}

	// If critical functions are missing, log warning.
	if ( ! empty( $missing ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(
			sprintf(
				'Apollo Core: WordPress core functions missing: %s. This may indicate a corrupted WordPress installation.',
				implode( ', ', $missing )
			)
		);
	}
}

// Run early to ensure core functions are available.
// Use muplugins_loaded if available, otherwise plugins_loaded with high priority.
if ( did_action( 'muplugins_loaded' ) ) {
	apollo_ensure_wp_core_functions();
} else {
	add_action( 'muplugins_loaded', 'apollo_ensure_wp_core_functions', 1 );
	add_action( 'plugins_loaded', 'apollo_ensure_wp_core_functions', 1 );
}
