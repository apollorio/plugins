<?php

/**
 * REST Security Helper
 *
 * Standardized security utilities for REST API endpoints across Apollo Suite.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Security;

use WP_REST_Request;
use WP_Error;

// Backwards compatibility alias
class_alias(RestSecurity::class, 'Apollo_Core\Security\RestSecurity');

if (! defined('ABSPATH')) {
	exit;
}

/**
 * REST API security helper for Apollo Suite.
 *
 * Provides standardized permission callbacks and security utilities
 * that all Apollo plugins should use for consistent security posture.
 *
 * Usage in register_rest_route():
 * ```php
 * register_rest_route('apollo/v1', '/resource', [
 *     'methods' => 'POST',
 *     'callback' => [$this, 'handlePost'],
 *     'permission_callback' => RestSecurity::writeCallback('edit_posts'),
 * ]);
 * ```
 */
class RestSecurity
{

	/**
	 * Rate limit transient prefix.
	 */
	private const RATE_LIMIT_PREFIX = 'apollo_rate_';

	/**
	 * Default rate limit (requests per minute).
	 */
	private const DEFAULT_RATE_LIMIT = 60;

	/**
	 * Strict rate limit for sensitive operations.
	 */
	private const STRICT_RATE_LIMIT = 10;

	/**
	 * Require authentication.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return true|WP_Error True if authenticated, error otherwise.
	 */
	public static function requireAuth(WP_REST_Request $request): bool|WP_Error
	{
		if (! is_user_logged_in()) {
			return new WP_Error(
				'rest_not_logged_in',
				__('Authentication required.', 'apollo-core'),
				array('status' => 401)
			);
		}
		return true;
	}

	/**
	 * Require valid nonce.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return true|WP_Error True if valid nonce, error otherwise.
	 */
	public static function requireNonce(WP_REST_Request $request): bool|WP_Error
	{
		$nonce = $request->get_header('X-WP-Nonce');

		if (! $nonce) {
			return new WP_Error(
				'rest_missing_nonce',
				__('Missing security token.', 'apollo-core'),
				array('status' => 403)
			);
		}

		if (! wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error(
				'rest_invalid_nonce',
				__('Invalid security token.', 'apollo-core'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Require specific capability.
	 *
	 * @param string          $cap     The capability to check.
	 * @param WP_REST_Request $request The request object.
	 * @return true|WP_Error True if user has capability, error otherwise.
	 */
	public static function requireCap(string $cap, WP_REST_Request $request): bool|WP_Error
	{
		if (! current_user_can($cap)) {
			return new WP_Error(
				'rest_forbidden',
				__('You do not have permission to perform this action.', 'apollo-core'),
				array('status' => 403)
			);
		}
		return true;
	}

	/**
	 * Apply rate limiting.
	 *
	 * @param WP_REST_Request $request   The request object.
	 * @param int             $limit     Max requests per minute.
	 * @param string          $key_suffix Optional suffix for rate limit key.
	 * @return true|WP_Error True if within limit, error otherwise.
	 */
	public static function rateLimit(WP_REST_Request $request, int $limit = self::DEFAULT_RATE_LIMIT, string $key_suffix = ''): bool|WP_Error
	{
		$user_id = get_current_user_id();
		$ip      = self::getClientIp();

		// Use user ID if logged in, otherwise IP.
		$identifier = $user_id > 0 ? "user_{$user_id}" : 'ip_' . \md5($ip);
		$key        = self::RATE_LIMIT_PREFIX . $identifier . $key_suffix;

		$count = (int) get_transient($key);

		if ($count >= $limit) {
			return new WP_Error(
				'rest_rate_limited',
				__('Too many requests. Please try again later.', 'apollo-core'),
				array(
					'status'      => 429,
					'retry_after' => 60,
				)
			);
		}

		set_transient($key, $count + 1, MINUTE_IN_SECONDS);

		return true;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address or empty string.
	 */
	private static function getClientIp(): string
	{
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',  // Proxies.
			'HTTP_X_REAL_IP',        // Nginx.
			'REMOTE_ADDR',           // Standard.
		);

		foreach ($headers as $header) {
			if (! empty($_SERVER[$header])) {
				$ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
				// Handle comma-separated IPs (X-Forwarded-For).
				if (\strpos($ip, ',') !== false) {
					$ip = \trim(\explode(',', $ip)[0]);
				}
				if (\filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Create a permission callback for write operations.
	 *
	 * Combines authentication, nonce verification, and capability check.
	 *
	 * @param string $capability Required capability (default: 'edit_posts').
	 * @return callable Permission callback function.
	 */
	public static function writeCallback(string $capability = 'edit_posts'): callable
	{
		return function (WP_REST_Request $request) use ($capability) {
			// 1. Check authentication.
			$auth = self::requireAuth($request);
			if (is_wp_error($auth)) {
				return $auth;
			}

			// 2. Verify nonce.
			$nonce = self::requireNonce($request);
			if (is_wp_error($nonce)) {
				return $nonce;
			}

			// 3. Check capability.
			return self::requireCap($capability, $request);
		};
	}

	/**
	 * Create a permission callback for read operations requiring auth.
	 *
	 * Requires authentication but not nonce (safe for GET requests).
	 *
	 * @param string|null $capability Optional capability to check.
	 * @return callable Permission callback function.
	 */
	public static function readAuthCallback(?string $capability = null): callable
	{
		return function (WP_REST_Request $request) use ($capability) {
			// 1. Check authentication.
			$auth = self::requireAuth($request);
			if (is_wp_error($auth)) {
				return $auth;
			}

			// 2. Check capability if specified.
			if ($capability) {
				return self::requireCap($capability, $request);
			}

			return true;
		};
	}

	/**
	 * Create a permission callback for public endpoints with rate limiting.
	 *
	 * Use for endpoints that must be public but need abuse protection.
	 *
	 * @param int    $rate_limit Max requests per minute.
	 * @param string $key_suffix Rate limit key suffix.
	 * @return callable Permission callback function.
	 */
	public static function publicRateLimitedCallback(int $rate_limit = self::DEFAULT_RATE_LIMIT, string $key_suffix = ''): callable
	{
		return function (WP_REST_Request $request) use ($rate_limit, $key_suffix) {
			return self::rateLimit($request, $rate_limit, $key_suffix);
		};
	}

	/**
	 * Create a permission callback for admin-only operations.
	 *
	 * Requires authentication, nonce, and manage_options capability.
	 *
	 * @return callable Permission callback function.
	 */
	public static function adminCallback(): callable
	{
		return self::writeCallback('manage_options');
	}

	/**
	 * Create a permission callback for moderation operations.
	 *
	 * Requires authentication, nonce, and moderate_comments capability.
	 *
	 * @return callable Permission callback function.
	 */
	public static function moderatorCallback(): callable
	{
		return self::writeCallback('moderate_comments');
	}

	/**
	 * Validate that request contains required fields.
	 *
	 * @param WP_REST_Request $request        The request object.
	 * @param array           $required_fields Array of required field names.
	 * @return true|WP_Error True if all fields present, error otherwise.
	 */
	public static function validateRequired(WP_REST_Request $request, array $required_fields): bool|WP_Error
	{
		$params  = $request->get_params();
		$missing = array();

		foreach ($required_fields as $field) {
			if (! isset($params[$field]) || '' === $params[$field]) {
				$missing[] = $field;
			}
		}

		if (! empty($missing)) {
			return new WP_Error(
				'rest_missing_params',
				\sprintf(
					/* translators: %s: comma-separated list of missing field names */
					__('Missing required fields: %s', 'apollo-core'),
					\implode(', ', $missing)
				),
				array('status' => 400)
			);
		}

		return true;
	}

	/**
	 * Log security event to audit log.
	 *
	 * @param string $event_type Event type identifier.
	 * @param string $message    Human-readable message.
	 * @param array  $context    Additional context data.
	 * @return void
	 */
	public static function logSecurityEvent(string $event_type, string $message, array $context = array()): void
	{
		if (\function_exists('apollo_log_audit_event')) {
			apollo_log_audit_event($event_type, $message, $context);
		} elseif (defined('WP_DEBUG') && WP_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			\error_log("[Apollo Security] {$event_type}: {$message}");
		}
	}
}
