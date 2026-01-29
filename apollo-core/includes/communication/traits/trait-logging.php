<?php
declare(strict_types=1);

namespace Apollo\Communication\Traits;

/**
 * Logging Trait
 *
 * Provides centralized logging functionality for communication systems
 */
trait LoggingTrait {

	private function log(string $action, array $data = [], string $level = 'info'): void {
		$log_entry = [
			'timestamp' => current_time('mysql'),
			'action' => $action,
			'data' => $data,
			'level' => $level,
			'user_id' => get_current_user_id(),
			'ip' => $this->get_client_ip(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
		];

		$log_file = WP_CONTENT_DIR . '/apollo-communication.log';

		$message = sprintf(
			"[%s] %s: %s - User: %s - IP: %s\n",
			$log_entry['timestamp'],
			strtoupper($level),
			$action,
			$log_entry['user_id'] ?: 'guest',
			$log_entry['ip']
		);

		if (!empty($data)) {
			$message .= "Data: " . wp_json_encode($data) . "\n";
		}

		$message .= "---\n";

		error_log($message, 3, $log_file);

		do_action('apollo_communication_log', $log_entry);
	}

	private function get_client_ip(): string {
		return $_SERVER['HTTP_X_FORWARDED_FOR'] ??
			   $_SERVER['HTTP_X_REAL_IP'] ??
			   $_SERVER['REMOTE_ADDR'] ??
			   '';
	}

	private function log_error(string $action, \Throwable $exception, array $context = []): void {
		$this->log($action, array_merge($context, [
			'error' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTraceAsString()
		]), 'error');
	}
}
