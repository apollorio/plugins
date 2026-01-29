<?php

/**
 * Rate Limiting Trait
 *
 * Provides rate limiting functionality for communication operations
 *
 * @package Apollo\Communication
 */

namespace Apollo\Communication\Traits;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Rate Limiting Trait
 */
if (! trait_exists('Apollo\Communication\Traits\RateLimitingTrait')) {
	trait RateLimitingTrait
	{

		/**
		 * Rate limit storage
		 *
		 * @var array
		 */
		private static $rate_limits = array();

		/**
		 * Check if operation is rate limited
		 *
		 * @param string $key     Unique identifier for the operation.
		 * @param int    $limit   Maximum operations allowed.
		 * @param int    $window  Time window in seconds.
		 * @return bool True if rate limited, false otherwise.
		 */
		protected function is_rate_limited($key, $limit = 10, $window = 60)
		{
			$now = time();

			if (! isset(self::$rate_limits[$key])) {
				self::$rate_limits[$key] = array();
			}

			// Clean old entries.
			self::$rate_limits[$key] = array_filter(
				self::$rate_limits[$key],
				function ($timestamp) use ($now, $window) {
					return ($now - $timestamp) < $window;
				}
			);

			// Check if limit exceeded.
			if (count(self::$rate_limits[$key]) >= $limit) {
				return true;
			}

			// Add current operation.
			self::$rate_limits[$key][] = $now;

			return false;
		}

		/**
		 * Get remaining operations for rate limit
		 *
		 * @param string $key     Unique identifier for the operation.
		 * @param int    $limit   Maximum operations allowed.
		 * @param int    $window  Time window in seconds.
		 * @return int Remaining operations.
		 */
		protected function get_rate_limit_remaining($key, $limit = 10, $window = 60)
		{
			$now = time();

			if (! isset(self::$rate_limits[$key])) {
				return $limit;
			}

			// Clean old entries.
			self::$rate_limits[$key] = array_filter(
				self::$rate_limits[$key],
				function ($timestamp) use ($now, $window) {
					return ($now - $timestamp) < $window;
				}
			);

			return max(0, $limit - count(self::$rate_limits[$key]));
		}
	}
}
