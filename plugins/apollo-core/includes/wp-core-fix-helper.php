<?php
/**
 * WordPress Core Fix Helper
 *
 * Provides workarounds for WordPress core loading issues without modifying core files.
 * This ensures plugins work even if WordPress core has loading order problems.
 * @package Apollo_Core
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define missing core functions if they don't exist
 * This prevents fatal errors when core functions are missing
 */
if ( ! function_exists( 'wp_is_valid_utf8' ) ) {
    /**
     * Checks if a string is valid UTF-8.
     *
     * @param string $string The string to check.
     * @return bool True if the string is valid UTF-8, false otherwise.
     */
    function wp_is_valid_utf8( $string ) {
        if ( function_exists( 'mb_check_encoding' ) ) {
            return mb_check_encoding( $string, 'UTF-8' );
        }

        // Fallback: simple regex check
        return (bool) preg_match( '//u', $string );
    }
}

/**
 * Ensure WordPress core functions are available
 * This is a workaround for cases where WordPress core files aren't loaded in the correct order
 */
function apollo_ensure_wp_core_functions() {
    // Only run if we're in WordPress context
    if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) ) {
        return;
    }

    // Verify core functions are available
    $required_functions = [
        'wp_is_valid_utf8',
        'sanitize_title',
        'esc_html',
        'esc_attr',
    ];

    $missing = [];
    foreach ( $required_functions as $func ) {
        if ( ! function_exists( $func ) ) {
            $missing[] = $func;
        }
    }

    if ( ! empty( $missing ) ) {
        error_log( 'Apollo Core: Missing WordPress core functions: ' . implode( ', ', $missing ) );
    }
}

// Hook to plugins_loaded to ensure functions are available
add_action( 'plugins_loaded', 'apollo_ensure_wp_core_functions', 1 );