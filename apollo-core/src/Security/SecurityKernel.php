<?php

declare(strict_types=1);

/**
 * Apollo Security Kernel - Ultra-Modern Security Layer
 *
 * Provides centralized security utilities following WordPress VIP standards.
 * This file implements all security primitives used across the Apollo ecosystem.
 *
 * @package Apollo\Core\Security
 * @since   2.1.0
 * @author  Apollo Team
 */

namespace Apollo\Core\Security;

// Prevent direct file access.
defined('ABSPATH') || exit;

/**
 * Security Kernel - Centralized security utilities.
 *
 * @since 2.1.0
 */
final class SecurityKernel
{

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Nonce action prefix.
	 *
	 * @var string
	 */
	private const NONCE_PREFIX = 'apollo_';

	/**
	 * Allowed HTML for rich text.
	 *
	 * @var array<string, array<string, bool>>
	 */
	private static array $allowed_html = [];

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct()
	{
		self::$allowed_html = self::build_allowed_html();
	}

	// =========================================================================
	// INPUT SANITIZATION
	// =========================================================================

	/**
	 * Sanitize string input.
	 *
	 * @param mixed  $value   Input value.
	 * @param string $default Default value.
	 * @return string
	 */
	public static function string(mixed $value, string $default = ''): string
	{
		if (! is_string($value) && ! is_numeric($value)) {
			return $default;
		}
		$sanitized = sanitize_text_field((string) $value);
		return '' !== $sanitized ? $sanitized : $default;
	}

	/**
	 * Sanitize textarea input (preserves newlines).
	 *
	 * @param mixed  $value   Input value.
	 * @param string $default Default value.
	 * @return string
	 */
	public static function textarea(mixed $value, string $default = ''): string
	{
		if (! is_string($value)) {
			return $default;
		}
		$sanitized = sanitize_textarea_field($value);
		return '' !== $sanitized ? $sanitized : $default;
	}

	/**
	 * Sanitize integer input.
	 *
	 * @param mixed $value   Input value.
	 * @param int   $default Default value.
	 * @return int
	 */
	public static function int(mixed $value, int $default = 0): int
	{
		if (is_numeric($value)) {
			return (int) $value;
		}
		return $default;
	}

	/**
	 * Sanitize positive integer (absint).
	 *
	 * @param mixed $value   Input value.
	 * @param int   $default Default value (must be >= 0).
	 * @return int
	 */
	public static function absint(mixed $value, int $default = 0): int
	{
		if (is_numeric($value)) {
			return absint($value);
		}
		return max(0, $default);
	}

	/**
	 * Sanitize float input.
	 *
	 * @param mixed $value   Input value.
	 * @param float $default Default value.
	 * @return float
	 */
	public static function float(mixed $value, float $default = 0.0): float
	{
		if (is_numeric($value)) {
			return (float) $value;
		}
		return $default;
	}

	/**
	 * Sanitize boolean input.
	 *
	 * @param mixed $value Input value.
	 * @return bool
	 */
	public static function bool(mixed $value): bool
	{
		return rest_sanitize_boolean($value);
	}

	/**
	 * Sanitize email input.
	 *
	 * @param mixed  $value   Input value.
	 * @param string $default Default value.
	 * @return string
	 */
	public static function email(mixed $value, string $default = ''): string
	{
		if (! is_string($value)) {
			return $default;
		}
		$sanitized = sanitize_email($value);
		return is_email($sanitized) ? $sanitized : $default;
	}

	/**
	 * Sanitize URL input.
	 *
	 * @param mixed         $value     Input value.
	 * @param string        $default   Default value.
	 * @param string[]|null $protocols Allowed protocols.
	 * @return string
	 */
	public static function url(mixed $value, string $default = '', ?array $protocols = null): string
	{
		if (! is_string($value)) {
			return $default;
		}
		$protocols  = $protocols ?? ['http', 'https'];
		$sanitized  = esc_url_raw($value, $protocols);
		return '' !== $sanitized ? $sanitized : $default;
	}

	/**
	 * Sanitize slug/key input.
	 *
	 * @param mixed  $value   Input value.
	 * @param string $default Default value.
	 * @return string
	 */
	public static function key(mixed $value, string $default = ''): string
	{
		if (! is_string($value)) {
			return $default;
		}
		$sanitized = sanitize_key($value);
		return '' !== $sanitized ? $sanitized : $default;
	}

	/**
	 * Sanitize filename.
	 *
	 * @param mixed  $value   Input value.
	 * @param string $default Default value.
	 * @return string
	 */
	public static function filename(mixed $value, string $default = ''): string
	{
		if (! is_string($value)) {
			return $default;
		}
		$sanitized = sanitize_file_name($value);
		return '' !== $sanitized ? $sanitized : $default;
	}

	/**
	 * Sanitize HTML content (KSES).
	 *
	 * @param mixed  $value   Input value.
	 * @param string $context Context: 'post', 'data', 'strip'.
	 * @return string
	 */
	public static function html(mixed $value, string $context = 'post'): string
	{
		if (! is_string($value)) {
			return '';
		}

		return match ($context) {
			'post'  => wp_kses_post($value),
			'data'  => wp_kses_data($value),
			'strip' => wp_strip_all_tags($value),
			default => wp_kses($value, self::$allowed_html),
		};
	}

	/**
	 * Sanitize array of strings.
	 *
	 * @param mixed    $value    Input value.
	 * @param string[] $default  Default value.
	 * @param callable $sanitizer Optional sanitizer callback.
	 * @return string[]
	 */
	public static function stringArray(mixed $value, array $default = [], ?callable $sanitizer = null): array
	{
		if (! is_array($value)) {
			return $default;
		}
		$sanitizer = $sanitizer ?? 'sanitize_text_field';
		return array_map($sanitizer, array_filter($value, 'is_string'));
	}

	/**
	 * Sanitize array of integers.
	 *
	 * @param mixed $value   Input value.
	 * @param int[] $default Default value.
	 * @return int[]
	 */
	public static function intArray(mixed $value, array $default = []): array
	{
		if (! is_array($value)) {
			return $default;
		}
		return array_map('absint', array_filter($value, 'is_numeric'));
	}

	/**
	 * Sanitize JSON string to array.
	 *
	 * @param mixed        $value   Input value.
	 * @param array<mixed> $default Default value.
	 * @return array<mixed>
	 */
	public static function json(mixed $value, array $default = []): array
	{
		if (is_array($value)) {
			return $value;
		}
		if (! is_string($value)) {
			return $default;
		}
		$decoded = json_decode($value, true);
		return is_array($decoded) ? $decoded : $default;
	}

	// =========================================================================
	// OUTPUT ESCAPING
	// =========================================================================

	/**
	 * Escape for HTML output.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function escHtml(mixed $value): string
	{
		return esc_html((string) $value);
	}

	/**
	 * Escape for HTML attribute output.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function escAttr(mixed $value): string
	{
		return esc_attr((string) $value);
	}

	/**
	 * Escape for URL output.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function escUrl(mixed $value): string
	{
		return esc_url((string) $value);
	}

	/**
	 * Escape for JavaScript string output.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function escJs(mixed $value): string
	{
		return esc_js((string) $value);
	}

	/**
	 * Escape for textarea output.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function escTextarea(mixed $value): string
	{
		return esc_textarea((string) $value);
	}

	/**
	 * Escape for XML output.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function escXml(mixed $value): string
	{
		return esc_xml((string) $value);
	}

	// =========================================================================
	// NONCE OPERATIONS
	// =========================================================================

	/**
	 * Create a nonce.
	 *
	 * @param string $action Action name (will be prefixed).
	 * @return string
	 */
	public static function createNonce(string $action): string
	{
		return wp_create_nonce(self::NONCE_PREFIX . $action);
	}

	/**
	 * Verify a nonce.
	 *
	 * @param string $nonce  Nonce value.
	 * @param string $action Action name (will be prefixed).
	 * @return bool
	 */
	public static function verifyNonce(string $nonce, string $action): bool
	{
		return false !== wp_verify_nonce($nonce, self::NONCE_PREFIX . $action);
	}

	/**
	 * Get nonce from request.
	 *
	 * @param string $key     Nonce key in request.
	 * @param string $method  HTTP method: 'GET', 'POST', 'REQUEST'.
	 * @return string
	 */
	public static function getNonce(string $key = '_wpnonce', string $method = 'REQUEST'): string
	{
		$input = match (strtoupper($method)) {
			'GET'   => $_GET,
			'POST'  => $_POST,
			default => $_REQUEST,
		};
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return isset($input[$key]) ? sanitize_text_field(wp_unslash($input[$key])) : '';
	}

	/**
	 * Verify AJAX nonce and die on failure.
	 *
	 * @param string $action    Action name (will be prefixed).
	 * @param string $nonce_key Nonce field key.
	 * @return void
	 */
	public static function checkAjaxNonce(string $action, string $nonce_key = '_wpnonce'): void
	{
		check_ajax_referer(self::NONCE_PREFIX . $action, $nonce_key);
	}

	/**
	 * Verify admin nonce and die on failure.
	 *
	 * @param string $action    Action name (will be prefixed).
	 * @param string $nonce_key Nonce field key.
	 * @return void
	 */
	public static function checkAdminNonce(string $action, string $nonce_key = '_wpnonce'): void
	{
		check_admin_referer(self::NONCE_PREFIX . $action, $nonce_key);
	}

	/**
	 * Output nonce field.
	 *
	 * @param string $action  Action name (will be prefixed).
	 * @param string $name    Field name.
	 * @param bool   $referer Include referer field.
	 * @return void
	 */
	public static function nonceField(string $action, string $name = '_wpnonce', bool $referer = true): void
	{
		wp_nonce_field(self::NONCE_PREFIX . $action, $name, $referer);
	}

	// =========================================================================
	// CAPABILITY CHECKS
	// =========================================================================

	/**
	 * Check if current user has capability.
	 *
	 * @param string $capability Capability to check.
	 * @param mixed  ...$args    Additional args for capability check.
	 * @return bool
	 */
	public static function can(string $capability, mixed ...$args): bool
	{
		return current_user_can($capability, ...$args);
	}

	/**
	 * Check capability and die on failure (for AJAX).
	 *
	 * @param string $capability Capability to check.
	 * @param string $message    Error message.
	 * @return void
	 */
	public static function requireCap(string $capability, string $message = 'Permission denied.'): void
	{
		if (! self::can($capability)) {
			wp_send_json_error(['message' => $message], 403);
			exit;
		}
	}

	/**
	 * Verify user is logged in.
	 *
	 * @param string $message Error message on failure.
	 * @return void
	 */
	public static function requireAuth(string $message = 'Authentication required.'): void
	{
		if (! is_user_logged_in()) {
			wp_send_json_error(['message' => $message], 401);
			exit;
		}
	}

	/**
	 * Verify user owns a resource.
	 *
	 * @param int    $user_id     User ID who owns the resource.
	 * @param string $message     Error message on failure.
	 * @param string $bypass_cap  Capability that bypasses ownership check.
	 * @return void
	 */
	public static function requireOwnership(int $user_id, string $message = 'Access denied.', string $bypass_cap = 'manage_options'): void
	{
		$current_user_id = get_current_user_id();
		if ($current_user_id !== $user_id && ! self::can($bypass_cap)) {
			wp_send_json_error(['message' => $message], 403);
			exit;
		}
	}

	// =========================================================================
	// REQUEST INPUT HELPERS
	// =========================================================================

	/**
	 * Get sanitized value from GET.
	 *
	 * @param string $key      Input key.
	 * @param mixed  $default  Default value.
	 * @param string $type     Type: 'string', 'int', 'bool', 'array', 'email', 'url'.
	 * @return mixed
	 */
	public static function get(string $key, mixed $default = '', string $type = 'string'): mixed
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::sanitizeInput($_GET[$key] ?? null, $default, $type);
	}

	/**
	 * Get sanitized value from POST.
	 *
	 * @param string $key      Input key.
	 * @param mixed  $default  Default value.
	 * @param string $type     Type: 'string', 'int', 'bool', 'array', 'email', 'url'.
	 * @return mixed
	 */
	public static function post(string $key, mixed $default = '', string $type = 'string'): mixed
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return self::sanitizeInput($_POST[$key] ?? null, $default, $type);
	}

	/**
	 * Get sanitized value from REQUEST.
	 *
	 * @param string $key      Input key.
	 * @param mixed  $default  Default value.
	 * @param string $type     Type: 'string', 'int', 'bool', 'array', 'email', 'url'.
	 * @return mixed
	 */
	public static function request(string $key, mixed $default = '', string $type = 'string'): mixed
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::sanitizeInput($_REQUEST[$key] ?? null, $default, $type);
	}

	/**
	 * Sanitize input by type.
	 *
	 * @param mixed  $value   Input value.
	 * @param mixed  $default Default value.
	 * @param string $type    Type.
	 * @return mixed
	 */
	private static function sanitizeInput(mixed $value, mixed $default, string $type): mixed
	{
		if (null === $value) {
			return $default;
		}

		// Unslash input.
		$value = wp_unslash($value);

		return match ($type) {
			'int'       => self::int($value, (int) $default),
			'absint'    => self::absint($value, (int) $default),
			'float'     => self::float($value, (float) $default),
			'bool'      => self::bool($value),
			'email'     => self::email($value, (string) $default),
			'url'       => self::url($value, (string) $default),
			'key'       => self::key($value, (string) $default),
			'html'      => self::html($value),
			'textarea'  => self::textarea($value, (string) $default),
			'array'     => self::json($value, is_array($default) ? $default : []),
			'int_array' => self::intArray($value, is_array($default) ? $default : []),
			default     => self::string($value, (string) $default),
		};
	}

	// =========================================================================
	// SQL SECURITY (Delegation to SafeQuery)
	// =========================================================================

	/**
	 * Get safe table name with prefix.
	 *
	 * @param string $table Table name without prefix.
	 * @return string
	 */
	public static function table(string $table): string
	{
		global $wpdb;
		$allowed_tables = [
			'apollo_favorites',
			'apollo_activity',
			'apollo_chat_messages',
			'apollo_chat_rooms',
			'apollo_chat_participants',
			'apollo_mod_log',
			'apollo_signatures',
			'apollo_verification',
			'apollo_gamification_log',
			'apollo_notifications',
			'apollo_analytics',
		];

		if (! in_array($table, $allowed_tables, true)) {
			// Fallback to WordPress core tables
			$table_property = $table;
			if (isset($wpdb->$table_property)) {
				return $wpdb->$table_property;
			}
			// Invalid table - trigger warning in dev
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error("Invalid table name: {$table}", E_USER_WARNING);
			}
			return $wpdb->prefix . sanitize_key($table);
		}

		return $wpdb->prefix . $table;
	}

	/**
	 * Prepare SQL query safely.
	 *
	 * @param string       $query Query with placeholders.
	 * @param mixed        ...$args Arguments for placeholders.
	 * @return string|null
	 */
	public static function prepare(string $query, mixed ...$args): ?string
	{
		global $wpdb;
		if (empty($args)) {
			return $query;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->prepare($query, ...$args);
	}

	// =========================================================================
	// RATE LIMITING
	// =========================================================================

	/**
	 * Check rate limit.
	 *
	 * @param string $key       Rate limit key.
	 * @param int    $limit     Maximum requests.
	 * @param int    $window    Time window in seconds.
	 * @param string $identifier Optional identifier (default: IP).
	 * @return bool True if within limit.
	 */
	public static function checkRateLimit(string $key, int $limit = 60, int $window = 60, string $identifier = ''): bool
	{
		if ('' === $identifier) {
			$identifier = self::getClientIp();
		}

		$transient_key = 'apollo_rl_' . md5($key . '_' . $identifier);
		$current       = (array) get_transient($transient_key);

		// Clean old entries.
		$now     = time();
		$current = array_filter($current, fn($time) => $time > ($now - $window));

		if (count($current) >= $limit) {
			return false;
		}

		$current[] = $now;
		set_transient($transient_key, $current, $window);

		return true;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public static function getClientIp(): string
	{
		$headers = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		];

		foreach ($headers as $header) {
			if (! empty($_SERVER[$header])) {
				$ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
				// Handle comma-separated IPs (X-Forwarded-For).
				if (str_contains($ip, ',')) {
					$ip = trim(explode(',', $ip)[0]);
				}
				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	// =========================================================================
	// CSRF PROTECTION
	// =========================================================================

	/**
	 * Generate CSRF token.
	 *
	 * @return string
	 */
	public static function generateCsrfToken(): string
	{
		$token = wp_generate_password(32, false);
		set_transient('apollo_csrf_' . $token, get_current_user_id(), HOUR_IN_SECONDS);
		return $token;
	}

	/**
	 * Validate CSRF token.
	 *
	 * @param string $token Token to validate.
	 * @return bool
	 */
	public static function validateCsrfToken(string $token): bool
	{
		$stored_user_id = get_transient('apollo_csrf_' . $token);
		if (false === $stored_user_id) {
			return false;
		}
		delete_transient('apollo_csrf_' . $token);
		return (int) $stored_user_id === get_current_user_id();
	}

	// =========================================================================
	// PRIVATE HELPERS
	// =========================================================================

	/**
	 * Build allowed HTML tags array.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function build_allowed_html(): array
	{
		return [
			'a'      => [
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
				'class'  => true,
			],
			'br'     => [],
			'em'     => ['class' => true],
			'strong' => ['class' => true],
			'p'      => ['class' => true],
			'span'   => ['class' => true, 'style' => true],
			'div'    => ['class' => true, 'id' => true],
			'ul'     => ['class' => true],
			'ol'     => ['class' => true],
			'li'     => ['class' => true],
			'img'    => [
				'src'    => true,
				'alt'    => true,
				'class'  => true,
				'width'  => true,
				'height' => true,
			],
		];
	}
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Get SecurityKernel instance.
 *
 * @return SecurityKernel
 */
function apollo_security(): SecurityKernel
{
	return SecurityKernel::instance();
}

/**
 * Sanitize input shorthand.
 *
 * @param mixed  $value   Input value.
 * @param string $type    Type: 'string', 'int', 'bool', 'email', 'url', 'key'.
 * @param mixed  $default Default value.
 * @return mixed
 */
function apollo_sanitize(mixed $value, string $type = 'string', mixed $default = ''): mixed
{
	return match ($type) {
		'int'      => SecurityKernel::int($value, (int) $default),
		'absint'   => SecurityKernel::absint($value, (int) $default),
		'float'    => SecurityKernel::float($value, (float) $default),
		'bool'     => SecurityKernel::bool($value),
		'email'    => SecurityKernel::email($value, (string) $default),
		'url'      => SecurityKernel::url($value, (string) $default),
		'key'      => SecurityKernel::key($value, (string) $default),
		'html'     => SecurityKernel::html($value),
		'textarea' => SecurityKernel::textarea($value, (string) $default),
		'array'    => SecurityKernel::json($value, is_array($default) ? $default : []),
		default    => SecurityKernel::string($value, (string) $default),
	};
}

/**
 * Escape output shorthand.
 *
 * @param mixed  $value Input value.
 * @param string $type  Type: 'html', 'attr', 'url', 'js', 'textarea'.
 * @return string
 */
function apollo_esc(mixed $value, string $type = 'html'): string
{
	return match ($type) {
		'attr'     => SecurityKernel::escAttr($value),
		'url'      => SecurityKernel::escUrl($value),
		'js'       => SecurityKernel::escJs($value),
		'textarea' => SecurityKernel::escTextarea($value),
		default    => SecurityKernel::escHtml($value),
	};
}
