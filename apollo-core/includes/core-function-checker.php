<?php
/**
 * Core Function Checker
 *
 * Helper functions to verify WordPress core functions are available
 * before using them. Prevents fatal errors.
 *
 * @package Apollo_Core
 * @version 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WordPress core function exists, with fallback
 *
 * @param string        $function_name Function name to check.
 * @param callable|null $fallback Fallback function if core function doesn't exist.
 * @return bool|callable True if exists, fallback if provided, false otherwise.
 */
function apollo_core_function_exists( $function_name, $fallback = null ) {
	if ( function_exists( $function_name ) ) {
		return true;
	}

	if ( null !== $fallback && is_callable( $fallback ) ) {
		return $fallback;
	}

	return false;
}

/**
 * Safe wrapper for wp_is_valid_utf8()
 *
 * @param string $string String to validate.
 * @return bool True if valid UTF-8.
 */
function apollo_wp_is_valid_utf8( $string ) {
	if ( function_exists( 'wp_is_valid_utf8' ) ) {
		return wp_is_valid_utf8( $string );
	}

	// Fallback: basic UTF-8 validation.
	if ( function_exists( 'mb_check_encoding' ) ) {
		return mb_check_encoding( $string, 'UTF-8' );
	}

	// Last resort: simple check.
	return (bool) preg_match( '//u', $string );
}

/**
 * Verify WordPress core is loaded correctly
 *
 * @return bool True if core is loaded correctly.
 */
function apollo_verify_wp_core_loaded() {
	$required_functions = array(
		'add_action',
		'add_filter',
		'get_option',
		'update_option',
		'wp_insert_post',
		'wp_update_post',
		'wp_delete_post',
	);

	foreach ( $required_functions as $func ) {
		if ( ! function_exists( $func ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Get WordPress core function status
 *
 * @return array Status of core functions.
 */
function apollo_get_core_function_status() {
	$functions = array(
		'wp_is_valid_utf8' => function_exists( 'wp_is_valid_utf8' ),
		'wp_scrub_utf8'    => function_exists( 'wp_scrub_utf8' ),
		'sanitize_title'   => function_exists( 'sanitize_title' ),
		'esc_html'         => function_exists( 'esc_html' ),
		'esc_attr'         => function_exists( 'esc_attr' ),
		'esc_url'          => function_exists( 'esc_url' ),
		'wp_kses_post'     => function_exists( 'wp_kses_post' ),
	);

	return $functions;
}
