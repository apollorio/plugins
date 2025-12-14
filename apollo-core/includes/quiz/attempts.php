<?php

declare(strict_types=1);

/**
 * Apollo Core - Quiz Attempts Manager
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Record quiz attempt
 *
 * @param int   $user_id      User ID (0 for anonymous during registration).
 * @param int   $question_id  Question ID.
 * @param array $answers      User's answers.
 * @param bool  $passed       Whether user passed.
 * @return int|false Attempt ID on success, false on failure
 */
function apollo_record_quiz_attempt( $user_id, $question_id, $answers, $passed ) {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_quiz_attempts';

	// Get current attempt number for this user/question.
	$attempt_number = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT MAX(attempt_number) FROM $table WHERE user_id = %d AND question_id = %d",
			$user_id,
			$question_id
		)
	);

	$attempt_number = $attempt_number ? $attempt_number + 1 : 1;

	// Insert attempt.
	$result = $wpdb->insert(
		$table,
		[
			'user_id'        => absint( $user_id ),
			'question_id'    => absint( $question_id ),
			'answers'        => wp_json_encode( $answers ),
			'passed'         => $passed ? 1 : 0,
			'attempt_number' => $attempt_number,
			'created_at'     => current_time( 'mysql' ),
		],
		[ '%d', '%d', '%s', '%d', '%d', '%s' ]
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Get user attempts for a question
 *
 * @param int $user_id     User ID.
 * @param int $question_id Question ID.
 * @return array Attempts
 */
function apollo_get_user_attempts( $user_id, $question_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_quiz_attempts';

	$attempts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d AND question_id = %d ORDER BY attempt_number ASC",
			$user_id,
			$question_id
		)
	);

	// Parse JSON answers.
	foreach ( $attempts as &$attempt ) {
		$attempt->answers = json_decode( $attempt->answers, true );
	}

	return $attempts;
}

/**
 * Get all users who attempted a question
 *
 * @param int $question_id Question ID.
 * @return array Users with their attempts
 */
function apollo_get_question_attempts( $question_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_quiz_attempts';

	// Get unique users.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT user_id FROM $table WHERE question_id = %d",
			$question_id
		)
	);

	$results = [];

	foreach ( $user_ids as $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			continue;
		}

		$attempts = apollo_get_user_attempts( $user_id, $question_id );

		// Get latest attempt status.
		$latest = end( $attempts );

		$results[] = [
			'user_id'        => $user_id,
			'user_name'      => $user->display_name,
			'user_email'     => $user->user_email,
			'total_attempts' => count( $attempts ),
			'passed'         => (bool) $latest->passed,
			'latest_attempt' => $latest,
			'all_attempts'   => $attempts,
		];
	}//end foreach

	return $results;
}

/**
 * Check if user passed quiz question
 *
 * @param int $user_id     User ID.
 * @param int $question_id Question ID.
 * @return bool True if passed
 */
function apollo_user_passed_question( $user_id, $question_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_quiz_attempts';

	$passed = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT passed FROM $table WHERE user_id = %d AND question_id = %d ORDER BY created_at DESC LIMIT 1",
			$user_id,
			$question_id
		)
	);

	return (bool) $passed;
}

/**
 * Get user's attempt count for question
 *
 * @param int $user_id     User ID.
 * @param int $question_id Question ID.
 * @return int Attempt count
 */
function apollo_get_attempt_count( $user_id, $question_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_quiz_attempts';

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE user_id = %d AND question_id = %d",
			$user_id,
			$question_id
		)
	);
}

/**
 * Validate quiz answers
 *
 * @param int    $question_id Question ID.
 * @param array  $user_answers User's selected answers.
 * @param string $form_type   Form type.
 * @return bool True if correct
 */
function apollo_validate_quiz_answer( $question_id, $user_answers, $form_type ) {
	$schema = apollo_get_quiz_schema( $form_type );

	if ( ! isset( $schema['questions'][ $question_id ] ) ) {
		return false;
	}

	$question = $schema['questions'][ $question_id ];
	$correct  = $question['correct'] ?? [];

	// Sort both arrays to compare.
	sort( $user_answers );
	sort( $correct );

	return $user_answers === $correct;
}

/**
 * Process quiz submission for registration
 *
 * @param array  $quiz_answers Quiz answers keyed by question_id.
 * @param string $form_type    Form type.
 * @param int    $user_id      User ID (0 for pre-registration).
 * @return array Result with pass/fail status and details
 */
function apollo_process_quiz_submission( $quiz_answers, $form_type, $user_id = 0 ) {
	$active_questions = apollo_get_active_quiz_questions( $form_type );

	if ( empty( $active_questions ) ) {
		return [
			'success' => true,
			'passed'  => true,
			'results' => [],
		];
	}

	$results    = [];
	$all_passed = true;

	foreach ( $active_questions as $question_id => $question ) {
		$user_answers = $quiz_answers[ $question_id ] ?? [];

		// Check attempts limit.
		if ( $user_id > 0 ) {
			$attempt_count = apollo_get_attempt_count( $user_id, $question_id );
			$max_retries   = $question['max_retries'] ?? 5;

			if ( $attempt_count >= $max_retries ) {
				$results[ $question_id ] = [
					'passed'        => false,
					'max_reached'   => true,
					'attempt_count' => $attempt_count,
				];
				$all_passed              = false;

				continue;
			}
		}

		// Validate answer.
		$passed = apollo_validate_quiz_answer( $question_id, $user_answers, $form_type );

		// Record attempt if user exists.
		if ( $user_id > 0 ) {
			apollo_record_quiz_attempt( $user_id, $question_id, $user_answers, $passed );
		}

		$results[ $question_id ] = [
			'passed'      => $passed,
			'explanation' => $question['explanation'] ?? '',
			'correct'     => $question['correct'],
		];

		if ( ! $passed ) {
			$all_passed = false;
		}
	}//end foreach

	return [
		'success' => true,
		'passed'  => $all_passed,
		'results' => $results,
	];
}

/**
 * Set user quiz status meta
 *
 * @param int    $user_id User ID.
 * @param string $status  Status ('passed', 'failed', 'pending').
 * @return bool True on success
 */
function apollo_set_user_quiz_status( $user_id, $status ) {
	return update_user_meta( $user_id, '_apollo_quiz_status', sanitize_key( $status ) );
}

/**
 * Get user quiz status
 *
 * @param int $user_id User ID.
 * @return string Status
 */
function apollo_get_user_quiz_status( $user_id ) {
	return get_user_meta( $user_id, '_apollo_quiz_status', true ) ?: 'pending';
}

/**
 * Rate limit check for quiz attempts
 *
 * @param string $ip        IP address.
 * @param int    $user_id   User ID.
 * @param int    $max_attempts Max attempts per hour.
 * @return bool True if allowed
 */
function apollo_check_quiz_rate_limit( $ip, $user_id = 0, $max_attempts = 10 ) {
	$transient_key = 'apollo_quiz_rate_' . md5( $ip . $user_id );
	$attempts      = get_transient( $transient_key );

	if ( false === $attempts ) {
		set_transient( $transient_key, 1, HOUR_IN_SECONDS );

		return true;
	}

	if ( $attempts >= $max_attempts ) {
		return false;
	}

	set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

	return true;
}
