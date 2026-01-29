<?php
declare(strict_types=1);

namespace Apollo\Communication\Traits;

/**
 * Validation Trait
 *
 * Provides validation methods for form and data validation
 */
trait ValidationTrait {

	private function validate_required($value, $params, array $field): bool {
		return !empty($value) || $value === '0' || $value === 0;
	}

	private function validate_email($value, $params, array $field): bool {
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}

	private function validate_url($value, $params, array $field): bool {
		return filter_var($value, FILTER_VALIDATE_URL) !== false;
	}

	private function validate_min_length($value, $params, array $field): bool {
		return strlen((string) $value) >= (int) $params;
	}

	private function validate_max_length($value, $params, array $field): bool {
		return strlen((string) $value) <= (int) $params;
	}

	private function validate_pattern($value, $params, array $field): bool {
		return preg_match($params, (string) $value) === 1;
	}

	private function validate_numeric($value, $params, array $field): bool {
		return is_numeric($value);
	}

	private function validate_date($value, $params, array $field): bool {
		return strtotime($value) !== false;
	}

	private function validate_unique($value, $params, array $field): bool {
		global $wpdb;

		$table = $params['table'] ?? '';
		$column = $params['column'] ?? $field['name'];
		$exclude_id = $params['exclude_id'] ?? null;

		if (!$table || !$column) {
			return true;
		}

		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE {$column} = %s",
			$value
		);

		if ($exclude_id) {
			$query .= $wpdb->prepare(" AND id != %d", $exclude_id);
		}

		$count = $wpdb->get_var($query);
		return (int) $count === 0;
	}

	private function get_validation_error_message(string $rule, $params): string {
		$messages = [
			'required' => 'This field is required',
			'email' => 'Please enter a valid email address',
			'url' => 'Please enter a valid URL',
			'min_length' => "Minimum length is {$params} characters",
			'max_length' => "Maximum length is {$params} characters",
			'pattern' => 'Invalid format',
			'numeric' => 'Please enter a valid number',
			'date' => 'Please enter a valid date',
			'unique' => 'This value is already taken'
		];

		return $messages[$rule] ?? 'Validation failed';
	}
}
