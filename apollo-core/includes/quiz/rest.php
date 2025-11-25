<?php
declare(strict_types=1);

/**
 * Apollo Core - Quiz REST API
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register quiz REST routes
 */
function apollo_register_quiz_rest_routes() {
	// POST /quiz/attempt - Record a quiz attempt.
	register_rest_route(
		'apollo/v1',
		'/quiz/attempt',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_rest_quiz_attempt',
			'permission_callback' => 'apollo_rest_quiz_attempt_permission',
			'args'                => array(
				'question_id'  => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'answers'      => array(
					'required' => true,
					'type'     => 'array',
				),
				'form_type'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);

	// GET /quiz/stats - Get quiz statistics (admin only).
	register_rest_route(
		'apollo/v1',
		'/quiz/stats',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_rest_quiz_stats',
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'question_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'form_type'   => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);

	// GET /quiz/user-attempts - Get user attempts for a question.
	register_rest_route(
		'apollo/v1',
		'/quiz/user-attempts',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_rest_user_attempts',
			'permission_callback' => function() {
				return is_user_logged_in();
			},
			'args'                => array(
				'question_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'apollo_register_quiz_rest_routes' );

/**
 * Permission callback for quiz attempt
 * Allow during registration (nonce check) or logged-in users
 *
 * @return bool True if allowed
 */
function apollo_rest_quiz_attempt_permission() {
	// If user is logged in, allow.
	if ( is_user_logged_in() ) {
		return true;
	}
	
	// During registration, check nonce.
	$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
	
	if ( wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return true;
	}
	
	return false;
}

/**
 * REST callback: Record quiz attempt
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_quiz_attempt( $request ) {
	try {
		$question_id = $request->get_param( 'question_id' );
		$answers     = $request->get_param( 'answers' );
		$form_type   = $request->get_param( 'form_type' );
		
		// Sanitize answers array.
		$answers = array_map( 'sanitize_text_field', $answers );
	
	// Rate limit check.
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	$user_id = get_current_user_id();
	
	if ( ! apollo_check_quiz_rate_limit( $ip, $user_id ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Too many attempts. Please wait before trying again.', 'apollo-core' ),
			),
			429
		);
	}
	
	// Check if question exists and is active.
	$schema = apollo_get_quiz_schema( $form_type );
	
	if ( ! isset( $schema['questions'][ $question_id ] ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Question not found.', 'apollo-core' ),
			),
			404
		);
	}
	
	$question = $schema['questions'][ $question_id ];
	
	// Check attempt limit.
	if ( $user_id > 0 ) {
		$attempt_count = apollo_get_attempt_count( $user_id, $question_id );
		$max_retries = $question['max_retries'] ?? 5;
		
		if ( $attempt_count >= $max_retries ) {
			return new WP_REST_Response(
				array(
					'success'      => false,
					'message'      => __( 'Maximum retry limit reached.', 'apollo-core' ),
					'max_reached'  => true,
					'attempt_count' => $attempt_count,
				),
				403
			);
		}
	}
	
	// Validate answer.
	$passed = apollo_validate_quiz_answer( $question_id, $answers, $form_type );
	
	// Record attempt if user exists.
	if ( $user_id > 0 ) {
		$attempt_id = apollo_record_quiz_attempt( $user_id, $question_id, $answers, $passed );
		
		if ( ! $attempt_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to record attempt.', 'apollo-core' ),
				),
				500
			);
		}
	}
	
		return new WP_REST_Response(
			array(
				'success'      => true,
				'passed'       => $passed,
				'explanation'  => $passed ? '' : ( $question['explanation'] ?? '' ),
				'attempt_count' => $user_id > 0 ? apollo_get_attempt_count( $user_id, $question_id ) : 0,
				'max_retries'  => $question['max_retries'] ?? 5,
			),
			200
		);
	} catch ( Exception $e ) {
		// Log the error with context
		error_log( sprintf( 
			'[Apollo Core] Quiz attempt error - Question: %s, Form: %s, Message: %s, File: %s:%d', 
			$question_id ?? 'unknown',
			$form_type ?? 'unknown',
			$e->getMessage(), 
			basename( $e->getFile() ),
			$e->getLine()
		) );
		
		// Return user-friendly error
		return new WP_Error(
			'quiz_attempt_failed',
			__( 'Quiz attempt failed. Please try again or contact support if the problem persists.', 'apollo-core' ),
			array( 
				'status' => 500,
				'debug_info' => WP_DEBUG ? $e->getMessage() : null,
			)
		);
	}
}

/**
 * REST callback: Get quiz statistics
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_quiz_stats( $request ) {
	$question_id = $request->get_param( 'question_id' );
	$form_type   = $request->get_param( 'form_type' );
	
	$stats = apollo_get_quiz_stats( $form_type, $question_id );
	$attempts = apollo_get_question_attempts( $question_id );
	
	return new WP_REST_Response(
		array(
			'success'  => true,
			'stats'    => $stats,
			'attempts' => $attempts,
		),
		200
	);
}

/**
 * REST callback: Get user attempts
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function apollo_rest_user_attempts( $request ) {
	$question_id = $request->get_param( 'question_id' );
	$user_id     = get_current_user_id();
	
	$attempts = apollo_get_user_attempts( $user_id, $question_id );
	
	return new WP_REST_Response(
		array(
			'success'  => true,
			'attempts' => $attempts,
		),
		200
	);
}


