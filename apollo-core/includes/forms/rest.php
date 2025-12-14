<?php

/**
 * Apollo Core - Forms REST API
 *
 * REST endpoints for form submission and schema retrieval
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/includes/forms/rest.php
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register forms REST routes
 */
function apollo_register_forms_rest_routes() {
	// Submit form endpoint.
	register_rest_route(
		'apollo/v1',
		'forms/enviar',
		[
			'methods'                          => WP_REST_Server::CREATABLE,
			'callback'                         => 'apollo_rest_submit_form',
			'permission_callback'              => '__return_true',
			// Public, validated within.
										'args' => [
											'form_type' => [
												'required'          => true,
												'sanitize_callback' => 'sanitize_text_field',
											],
											'data'      => [
												'required'          => true,
												'type'              => 'object',
												'sanitize_callback' => 'apollo_sanitize_form_data',
											],
										],
		]
	);

	// Get schema endpoint.
	register_rest_route(
		'apollo/v1',
		'forms/schema',
		[
			'methods'                          => WP_REST_Server::READABLE,
			'callback'                         => 'apollo_rest_get_form_schema',
			'permission_callback'              => '__return_true',
			// Public.
										'args' => [
											'form_type' => [
												'required'          => true,
												'sanitize_callback' => 'sanitize_text_field',
											],
										],
		]
	);
}
add_action( 'rest_api_init', 'apollo_register_forms_rest_routes' );

/**
 * Recursively sanitize form data.
 *
 * @param mixed $data The form data to sanitize.
 * @return mixed Sanitized data.
 */
function apollo_sanitize_form_data( $data ) {
	if ( is_array( $data ) ) {
		return array_map( 'apollo_sanitize_form_data', $data );
	}

	if ( is_string( $data ) ) {
		// Check if it looks like HTML content.
		if ( preg_match( '/<[^>]+>/', $data ) ) {
			return wp_kses_post( $data );
		}
		// Check if it looks like a URL.
		if ( filter_var( $data, FILTER_VALIDATE_URL ) ) {
			return esc_url_raw( $data );
		}
		// Check if it looks like an email.
		if ( filter_var( $data, FILTER_VALIDATE_EMAIL ) ) {
			return sanitize_email( $data );
		}

		return sanitize_text_field( $data );
	}

	if ( is_int( $data ) ) {
		return absint( $data );
	}

	if ( is_float( $data ) ) {
		return (float) $data;
	}

	if ( is_bool( $data ) ) {
		return (bool) $data;
	}

	return $data;
}

/**
 * REST callback: Submit form
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function apollo_rest_submit_form( $request ) {
	$form_type = $request->get_param( 'form_type' );
	$data      = $request->get_param( 'data' );

	// Verify nonce.
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error(
			'invalid_nonce',
			__( 'Invalid security token.', 'apollo-core' ),
			[ 'status' => 403 ]
		);
	}

	// Get schema.
	$schema = apollo_get_form_schema( $form_type );

	if ( empty( $schema ) ) {
		return new WP_Error(
			'invalid_form_type',
			__( 'Invalid form type.', 'apollo-core' ),
			[ 'status' => 400 ]
		);
	}

	// Check if quiz is enabled and validate answers first (for new_user).
	if ( 'new_user' === $form_type && ! empty( $data['quiz_answers'] ) ) {
		$quiz_result = apollo_process_quiz_submission( $data['quiz_answers'], $form_type, 0 );

		if ( ! $quiz_result['passed'] ) {
			$quiz_schema  = apollo_get_quiz_schema( $form_type );
			$require_pass = $quiz_schema['require_pass'] ?? false;

			if ( $require_pass ) {
				return new WP_Error(
					'quiz_failed',
					__( 'You must pass the quiz to register.', 'apollo-core' ),
					[
						'status'  => 400,
						'results' => $quiz_result['results'],
					]
				);
			}
		}
	}

	// Validate all fields.
	$errors = [];
	foreach ( $schema as $field ) {
		if ( ! $field['visible'] ) {
			continue;
		}

		$value      = isset( $data[ $field['key'] ] ) ? $data[ $field['key'] ] : '';
		$validation = apollo_validate_field_value( $value, $field );

		if ( is_wp_error( $validation ) ) {
			$errors[ $field['key'] ] = $validation->get_error_message();
		}
	}

	// Return validation errors.
	if ( ! empty( $errors ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'errors'  => $errors,
			],
			400
		);
	}

	// Process form based on type with error handling.
	try {
		switch ( $form_type ) {
			case 'new_user':
				$result = apollo_process_new_user_form( $data );

				break;

			case 'cpt_event':
			case 'cpt_local':
			case 'cpt_dj':
				$result = apollo_process_cpt_form( $form_type, $data );

				break;

			default:
				$result = new WP_Error( 'unsupported_form', __( 'Form type not supported yet.', 'apollo-core' ) );

				break;
		}

		// Return result.
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $result->get_error_message(),
				],
				400
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Form submitted successfully.', 'apollo-core' ),
				'data'    => $result,
			],
			200
		);
	} catch ( Exception $e ) {
		// Log the error with context.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
			error_log(
				sprintf(
					'[Apollo Core] Form submission error - Type: %s, Message: %s, File: %s:%d',
					$form_type,
					$e->getMessage(),
					basename( $e->getFile() ),
					$e->getLine()
				)
			);
		}

		// Return user-friendly error
		return new WP_Error(
			'form_submission_failed',
			__( 'Form submission failed. Please try again or contact support if the problem persists.', 'apollo-core' ),
			[
				'status'     => 500,
				'debug_info' => WP_DEBUG ? $e->getMessage() : null,
			]
		);
	}//end try
}

/**
 * Process new user registration form
 *
 * @param array $data Form data.
 * @return array|WP_Error User data or error.
 */
function apollo_process_new_user_form( $data ) {
	// Extract user fields.
	$user_login = isset( $data['user_login'] ) ? sanitize_user( $data['user_login'] ) : '';
	$user_email = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
	$user_pass  = isset( $data['user_pass'] ) ? $data['user_pass'] : '';

	// Create user.
	$user_id = wp_create_user( $user_login, $user_pass, $user_email );

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	// Process quiz answers if provided (record attempts with real user_id).
	if ( isset( $data['quiz_answers'] ) && ! empty( $data['quiz_answers'] ) ) {
		$quiz_result = apollo_process_quiz_submission( $data['quiz_answers'], 'new_user', $user_id );

		// Set quiz status meta.
		if ( $quiz_result['passed'] ) {
			apollo_set_user_quiz_status( $user_id, 'passed' );
		} else {
			apollo_set_user_quiz_status( $user_id, 'failed' );
		}
	}

	// Save additional registration fields.
	if ( isset( $data['social_name'] ) && ! empty( $data['social_name'] ) ) {
		update_user_meta( $user_id, '_apollo_social_name', sanitize_text_field( $data['social_name'] ) );
	}

	if ( isset( $data['whatsapp'] ) && ! empty( $data['whatsapp'] ) ) {
		update_user_meta( $user_id, '_apollo_whatsapp', sanitize_text_field( $data['whatsapp'] ) );
	}

	if ( isset( $data['birthday'] ) && ! empty( $data['birthday'] ) ) {
		update_user_meta( $user_id, '_apollo_birthday', sanitize_text_field( $data['birthday'] ) );
	}

	if ( isset( $data['music_tastes'] ) && is_array( $data['music_tastes'] ) ) {
		$music_tastes = array_map( 'sanitize_text_field', $data['music_tastes'] );
		update_user_meta( $user_id, '_apollo_music_tastes', $music_tastes );
	}

	// Save Instagram ID if provided.
	if ( isset( $data['instagram_user_id'] ) && ! empty( $data['instagram_user_id'] ) ) {
		$instagram_id = sanitize_text_field( $data['instagram_user_id'] );
		// Strip leading @ if present.
		$instagram_id = ltrim( $instagram_id, '@' );

		if ( preg_match( '/^[A-Za-z0-9_]{1,30}$/', $instagram_id ) ) {
			if ( apollo_is_instagram_id_unique( $instagram_id, $user_id ) ) {
				update_user_meta( $user_id, '_apollo_instagram_id', $instagram_id );
			}
		}
	}

	return [
		'user_id'    => $user_id,
		'user_login' => $user_login,
	];
}

/**
 * Process CPT form submission
 *
 * @param string $form_type Form type (cpt_event, cpt_local, cpt_dj).
 * @param array  $data      Form data.
 * @return array|WP_Error Post data or error.
 */
function apollo_process_cpt_form( $form_type, $data ) {
	// Map form type to post type.
	$post_type_map = [
		'cpt_event' => 'event_listing',
		'cpt_local' => 'event_local',
		'cpt_dj'    => 'event_dj',
	];

	$post_type = $post_type_map[ $form_type ];

	// Extract post fields.
	$post_title   = isset( $data['post_title'] ) ? sanitize_text_field( $data['post_title'] ) : '';
	$post_content = isset( $data['post_content'] ) ? wp_kses_post( $data['post_content'] ) : '';

	// Create post as draft.
	$post_id = wp_insert_post(
		[
			'post_type'                               => $post_type,
			'post_title'                              => $post_title,
			'post_content'                            => $post_content,
			'post_status'                             => 'draft',
			// Requires mod.
										'post_author' => get_current_user_id() ? get_current_user_id() : 1,
		],
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Save meta fields (fields starting with _).
	foreach ( $data as $key => $value ) {
		if ( strpos( $key, '_' ) === 0 ) {
			update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
		}
	}

	// Save Instagram ID as post meta if provided.
	if ( isset( $data['instagram_user_id'] ) && ! empty( $data['instagram_user_id'] ) ) {
		$instagram_id = sanitize_text_field( $data['instagram_user_id'] );

		if ( preg_match( '/^[A-Za-z0-9_]{1,30}$/', $instagram_id ) ) {
			update_post_meta( $post_id, '_apollo_instagram_id', $instagram_id );
		}
	}

	return [
		'post_id'   => $post_id,
		'post_type' => $post_type,
		'status'    => 'draft',
	];
}

/**
 * REST callback: Get form schema
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function apollo_rest_get_form_schema( $request ) {
	$form_type = $request->get_param( 'form_type' );

	$schema = apollo_get_form_schema( $form_type );

	// Remove internal fields if needed.
	$public_schema = array_map(
		function ( $field ) {
			return [
				'key'        => $field['key'],
				'label'      => $field['label'],
				'type'       => $field['type'],
				'required'   => $field['required'],
				'visible'    => $field['visible'],
				'default'    => $field['default'],
				'validation' => $field['validation'],
			];
		},
		$schema
	);

	// Add quiz questions if enabled for this form type.
	$quiz_questions = [];
	$quiz_enabled   = false;

	if ( function_exists( 'apollo_get_active_quiz_questions' ) ) {
		$active_questions = apollo_get_active_quiz_questions( $form_type );

		if ( ! empty( $active_questions ) ) {
			$quiz_enabled = true;

			// Format questions for frontend (hide correct answers).
			foreach ( $active_questions as $id => $question ) {
				$quiz_questions[ $id ] = [
					'id'          => $id,
					'title'       => $question['title'],
					'answers'     => $question['answers'],
					'mandatory'   => $question['mandatory'] ?? true,
					'max_retries' => $question['max_retries'] ?? 5,
				];
			}
		}
	}

	// Add Instagram info if available.
	$insta_info = [];
	if ( function_exists( 'apollo_get_insta_info' ) ) {
		$insta_info = apollo_get_insta_info( $form_type );
	}

	return new WP_REST_Response(
		[
			'form_type'      => $form_type,
			'schema'         => $public_schema,
			'quiz_enabled'   => $quiz_enabled,
			'quiz_questions' => $quiz_questions,
			'insta_info'     => $insta_info,
		],
		200
	);
}
