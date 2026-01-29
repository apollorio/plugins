<?php
/**
 * REST API: Textures Endpoint
 *
 * Provides texture library via REST API
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST endpoints for textures
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'apollo/v1',
			'/textures',
			array(
				'methods'             => 'GET',
				'callback'            => 'apollo_rest_get_textures',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'/textures/search',
			array(
				'methods'             => 'GET',
				'callback'            => 'apollo_rest_search_textures',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && strlen( $param ) <= 100;
						},
					),
				),
			)
		);

		// Stickers endpoint (empty for now)
		register_rest_route(
			'apollo/v1',
			'/stickers',
			array(
				'methods'             => 'GET',
				'callback'            => 'apollo_rest_get_stickers',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/**
 * Get all textures
 *
 * @param WP_REST_Request $request Request object (unused but required by REST API).
 * @return WP_REST_Response
 */
function apollo_rest_get_textures( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	unset( $request ); // Mark as intentionally unused.
	$textures_dir = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/img/textures/';

	if ( ! is_dir( $textures_dir ) ) {
		return rest_ensure_response(
			array(
				'textures' => array(),
				'count'    => 0,
			)
		);
	}

	// Scan directory for PNG files
	$files = array_values(
		array_filter(
			scandir( $textures_dir ),
			function ( $file ) {
				return preg_match( '/\.png$/i', $file );
			}
		)
	);

	// Sort alphabetically
	sort( $files );

	return rest_ensure_response(
		array(
			'textures' => $files,
			'count'    => count( $files ),
			'base_url' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/img/textures/',
		)
	);
}

/**
 * Search textures by filename
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function apollo_rest_search_textures( $request ) {
	$search_term  = $request->get_param( 'q' );
	$textures_dir = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/img/textures/';

	if ( ! is_dir( $textures_dir ) ) {
		return rest_ensure_response(
			array(
				'textures' => array(),
				'count'    => 0,
			)
		);
	}

	// Get all textures first
	$all_files = array_values(
		array_filter(
			scandir( $textures_dir ),
			function ( $file ) {
				return preg_match( '/\.png$/i', $file );
			}
		)
	);

	// Filter by search term if provided
	if ( ! empty( $search_term ) ) {
		$search_term_lower = strtolower( $search_term );
		$all_files         = array_filter(
			$all_files,
			function ( $file ) use ( $search_term_lower ) {
				return strpos( strtolower( $file ), $search_term_lower ) !== false;
			}
		);
		$all_files         = array_values( $all_files );
	}

	// Sort alphabetically
	sort( $all_files );

	return rest_ensure_response(
		array(
			'textures' => $all_files,
			'count'    => count( $all_files ),
			'base_url' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/img/textures/',
			'query'    => $search_term,
		)
	);
}

/**
 * Get all stickers (empty for now - reserved for future)
 *
 * @param WP_REST_Request $request Request object (unused but required by REST API).
 * @return WP_REST_Response
 */
function apollo_rest_get_stickers( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	unset( $request ); // Mark as intentionally unused.
	$stickers_dir = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/img/stickers/';

	if ( ! is_dir( $stickers_dir ) ) {
		return rest_ensure_response(
			array(
				'stickers' => array(),
				'count'    => 0,
				'base_url' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/img/stickers/',
			)
		);
	}

	// Scan directory for PNG files
	$files = array_values(
		array_filter(
			scandir( $stickers_dir ),
			function ( $file ) {
				return preg_match( '/\.png$/i', $file );
			}
		)
	);

	// Sort alphabetically
	sort( $files );

	return rest_ensure_response(
		array(
			'stickers' => $files,
			'count'    => count( $files ),
			'base_url' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/img/stickers/',
		)
	);
}
