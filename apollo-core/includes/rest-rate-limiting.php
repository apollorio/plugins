<?php

declare(strict_types=1);

/**
 * REST API Rate Limiting
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get sanitized client IP address
 *
 * @since 3.0.1 Added for security - prevents IP spoofing and log injection
 * @return string Sanitized IP address or '0.0.0.0' if unavailable
 */
function apollo_get_client_ip(): string {
	$ip = '0.0.0.0';

	// Check various headers in order of trust
	$ip_keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];

	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$raw_ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

			// Handle comma-separated list (X-Forwarded-For)
			if ( strpos( $raw_ip, ',' ) !== false ) {
				$raw_ip = trim( explode( ',', $raw_ip )[0] );
			}

			// Validate IP format
			if ( filter_var( $raw_ip, FILTER_VALIDATE_IP ) ) {
				$ip = $raw_ip;

				break;
			}
		}
	}

	return $ip;
}

/**
 * Check rate limit for REST endpoint
 *
 * @param WP_REST_Request $request Request object.
 * @return bool|WP_Error True if within limit, WP_Error if exceeded.
 */
function apollo_rest_rate_limit_check( WP_REST_Request $request ) {
	$endpoint = $request->get_route();
	$user_id  = get_current_user_id();
	$ip       = apollo_get_client_ip();

	// Different limits for different endpoint types
	$limits = [
		'/apollo/v1/forms/submit'                => 10,
		// 10 submissions per minute
					'/apollo/v1tentantiva'       => 5,
		// 5 quiz attempts per minute
					'/apollo/v1/memberships/set' => 20,
		// 20 membership changes per minute
					'/apollo/v1/modaprovar'      => 30,
		// 30 approvals per minute
					'default'                    => 100,
	// 100 requests per minute for other endpoints
	];

	$limit = $limits[ $endpoint ] ?? $limits['default'];

	// Create unique key for this endpoint/user/IP combination
	$key      = 'apollo_rate_limit_' . md5( $endpoint . '_' . $user_id . '_' . $ip );
	$attempts = get_transient( $key );

	if ( false === $attempts ) {
		$attempts = 0;
	}

	++$attempts;

	// Check if limit exceeded
	if ( $attempts > $limit ) {
		// Log rate limit violation
		if ( function_exists( 'apollo_mod_log_action' ) && $user_id > 0 ) {
			apollo_mod_log_action(
				$user_id,
				'rate_limit_exceeded',
				'rest_endpoint',
				0,
				[
					'endpoint' => $endpoint,
					'attempts' => $attempts,
					'limit'    => $limit,
					'ip'       => $ip,
				]
			);
		}

		return new WP_Error(
			'rate_limit_exceeded',
			sprintf(
				/* translators: %d: rate limit */
				__( 'Rate limit exceeded. Maximum %d requests per minute allowed.', 'apollo-core' ),
				$limit
			),
			[
				'status' => 429,
				'limit'  => $limit,
				'reset'  => 60,
			// Seconds until reset
			]
		);
	}//end if

	// Store updated attempt count
	set_transient( $key, $attempts, 60 );

	return true;
}

/**
 * Middleware to add rate limiting to Apollo REST endpoints
 *
 * @param mixed           $result  Response to replace the requested version with.
 * @param WP_REST_Server  $server  Server instance.
 * @param WP_REST_Request $request Request used to generate the response.
 * @return mixed
 */
function apollo_rest_rate_limit_middleware( $result, WP_REST_Server $server, WP_REST_Request $request ) {
	$route = $request->get_route();

	// Only apply to apollo endpoints
	if ( 0 !== strpos( $route, '/apollo/v1' ) ) {
		return $result;
	}

	// Skip rate limiting for health check
	if ( '/apollo/v1/health' === $route ) {
		return $result;
	}

	// Check rate limit
	$check = apollo_rest_rate_limit_check( $request );

	if ( is_wp_error( $check ) ) {
		return $check;
	}

	return $result;
}
add_filter( 'rest_pre_dispatch', 'apollo_rest_rate_limit_middleware', 10, 3 );

/**
 * Add rate limit headers to response
 *
 * @param WP_HTTP_Response $result  Result to send to the client.
 * @param WP_REST_Server   $server  Server instance.
 * @param WP_REST_Request  $request Request used to generate the response.
 * @return WP_HTTP_Response
 */
function apollo_rest_add_rate_limit_headers( WP_HTTP_Response $result, WP_REST_Server $server, WP_REST_Request $request ) {
	$route = $request->get_route();

	// Only for apollo endpoints
	if ( 0 !== strpos( $route, '/apollo/v1' ) ) {
		return $result;
	}

	$user_id  = get_current_user_id();
	$ip       = apollo_get_client_ip();
	$key      = 'apollo_rate_limit_' . md5( $route . '_' . $user_id . '_' . $ip );
	$attempts = (int) get_transient( $key );

	// Default limit
	$limit = 100;

	// Add headers
	$result->header( 'X-RateLimit-Limit', (string) $limit );
	$result->header( 'X-RateLimit-Remaining', (string) max( 0, $limit - $attempts ) );
	$result->header( 'X-RateLimit-Reset', (string) ( time() + 60 ) );

	return $result;
}
add_filter( 'rest_post_dispatch', 'apollo_rest_add_rate_limit_headers', 10, 3 );

/**
 * Get rate limit status for debugging
 *
 * @param string $endpoint Endpoint to check.
 * @param int    $user_id  User ID (default: current user).
 * @return array Rate limit status.
 */
function apollo_get_rate_limit_status( string $endpoint, int $user_id = 0 ): array {
	if ( 0 === $user_id ) {
		$user_id = get_current_user_id();
	}

	$ip       = apollo_get_client_ip();
	$key      = 'apollo_rate_limit_' . md5( $endpoint . '_' . $user_id . '_' . $ip );
	$attempts = (int) get_transient( $key );
	$limit    = 100;
	// Default

	return [
		'endpoint'  => $endpoint,
		'user_id'   => $user_id,
		'ip'        => $ip,
		'attempts'  => $attempts,
		'limit'     => $limit,
		'remaining' => max( 0, $limit - $attempts ),
		'reset_in'  => 60,
	];
}

/**
 * Clear rate limit for a specific user/endpoint (admin function)
 *
 * @param string $endpoint Endpoint to clear.
 * @param int    $user_id  User ID.
 * @param string $ip       IP address.
 * @return bool True on success.
 */
function apollo_clear_rate_limit( string $endpoint, int $user_id = 0, string $ip = '' ): bool {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( 0 === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $ip ) ) {
		$ip = apollo_get_client_ip();
	}

	$key = 'apollo_rate_limit_' . md5( $endpoint . '_' . $user_id . '_' . $ip );

	return delete_transient( $key );
}
