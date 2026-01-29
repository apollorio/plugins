<?php
declare(strict_types=1);

namespace Apollo\Communication\Traits;

/**
 * Rate Limiting Trait
 *
 * Provides rate limiting functionality for communication systems
 */
trait RateLimitingTrait {

	private array $rate_limits = [
		'email' => ['max' => 10, 'window' => 3600], // 10 emails per hour
		'notification' => ['max' => 50, 'window' => 3600], // 50 notifications per hour
		'form_submission' => ['max' => 20, 'window' => 3600] // 20 form submissions per hour
	];

	private function is_rate_limited(string $identifier, string $type = 'email'): bool {
		$cache_key = "rate_limit_{$type}_{$identifier}";
		$attempts = get_transient($cache_key) ?: [];

		$now = time();
		$window_start = $now - $this->rate_limits[$type]['window'];

		$attempts = array_filter($attempts, function($timestamp) use ($window_start) {
			return $timestamp > $window_start;
		});

		if (count($attempts) >= $this->rate_limits[$type]['max']) {
			return true;
		}

		$attempts[] = $now;
		set_transient($cache_key, $attempts, $this->rate_limits[$type]['window']);

		return false;
	}

	private function get_remaining_attempts(string $identifier, string $type = 'email'): int {
		$cache_key = "rate_limit_{$type}_{$identifier}";
		$attempts = get_transient($cache_key) ?: [];

		$now = time();
		$window_start = $now - $this->rate_limits[$type]['window'];

		$attempts = array_filter($attempts, function($timestamp) use ($window_start) {
			return $timestamp > $window_start;
		});

		return max(0, $this->rate_limits[$type]['max'] - count($attempts));
	}

	private function reset_rate_limit(string $identifier, string $type = 'email'): void {
		$cache_key = "rate_limit_{$type}_{$identifier}";
		delete_transient($cache_key);
	}
}
