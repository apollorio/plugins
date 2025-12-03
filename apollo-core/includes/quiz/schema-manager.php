<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - Quiz Schema Manager
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_QUIZ_SCHEMAS_OPTION', 'apollo_quiz_schemas' );
define( 'APOLLO_QUIZ_VERSION_OPTION', 'apollo_quiz_schema_version' );
define( 'APOLLO_INSTA_INFO_OPTION', 'apollo_insta_info' );

/**
 * Get default quiz schemas
 *
 * @return array Default quiz schemas
 */
function apollo_get_default_quiz_schemas(): array {
	return array(
		'new_user' => array(
			'enabled'      => false,
			'require_pass' => false,
			'questions'    => array(),
		),
	);
}

/**
 * Get quiz schema for a form type
 *
 * @param string $form_type Form type.
 * @return array Quiz schema
 */
function apollo_get_quiz_schema( string $form_type ): array {
	// Try cache first
	$cached = apollo_cache_get_quiz_schema( $form_type );
	if ( false !== $cached ) {
		return $cached;
	}

	$schemas  = get_option( APOLLO_QUIZ_SCHEMAS_OPTION, array() );
	$defaults = apollo_get_default_quiz_schemas();

	$schema = array();
	if ( isset( $schemas[ $form_type ] ) ) {
		$schema = wp_parse_args( $schemas[ $form_type ], $defaults[ $form_type ] ?? array() );
	} else {
		$schema = $defaults[ $form_type ] ?? array();
	}

	// Cache the result
	apollo_cache_quiz_schema( $form_type, $schema );

	return $schema;
}

/**
 * Save quiz schema
 *
 * @param string $form_type Form type.
 * @param array  $schema    Schema data.
 * @return bool True on success
 */
function apollo_save_quiz_schema( $form_type, $schema ) {
	$schemas = get_option( APOLLO_QUIZ_SCHEMAS_OPTION, array() );

	// Validate schema structure.
	if ( ! isset( $schema['questions'] ) || ! is_array( $schema['questions'] ) ) {
		return false;
	}

	// Count active questions.
	$active_count = 0;
	foreach ( $schema['questions'] as $question ) {
		if ( ! empty( $question['active'] ) ) {
			++$active_count;
		}
	}

	// Enforce max 5 active questions.
	if ( $active_count > 5 ) {
		return false;
	}

	$schemas[ $form_type ] = $schema;

	$result = update_option( APOLLO_QUIZ_SCHEMAS_OPTION, $schemas );

	if ( $result ) {
		// Increment version.
		$version  = get_option( APOLLO_QUIZ_VERSION_OPTION, '1.0.0' );
		$parts    = explode( '.', $version );
		$parts[2] = isset( $parts[2] ) ? (int) $parts[2] + 1 : 1;
		update_option( APOLLO_QUIZ_VERSION_OPTION, implode( '.', $parts ) );

		// Invalidate cache for this form type
		apollo_cache_flush_group( 'apollo_quiz' );
	}

	return $result;
}

/**
 * Get active quiz questions for form type
 *
 * @param string $form_type Form type.
 * @return array Active questions
 */
function apollo_get_active_quiz_questions( string $form_type ): array {
	$schema = apollo_get_quiz_schema( $form_type );

	if ( empty( $schema['enabled'] ) || empty( $schema['questions'] ) ) {
		return array();
	}

	$active = array();
	foreach ( $schema['questions'] as $id => $question ) {
		if ( ! empty( $question['active'] ) ) {
			$active[ $id ] = $question;
		}
	}

	return $active;
}

/**
 * Add or update quiz question
 *
 * @param string $form_type Form type.
 * @param array  $question  Question data.
 * @param int    $id        Question ID (null for new).
 * @return int|false Question ID on success, false on failure
 */
function apollo_save_quiz_question( $form_type, $question, $id = null ) {
	$schema = apollo_get_quiz_schema( $form_type );

	// Validate question structure.
	$required = array( 'title', 'answers', 'correct' );
	foreach ( $required as $field ) {
		if ( ! isset( $question[ $field ] ) ) {
			return false;
		}
	}

	// Generate ID if new.
	if ( null === $id ) {
		$id = ! empty( $schema['questions'] ) ? max( array_keys( $schema['questions'] ) ) + 1 : 1;
	}

	// Set defaults.
	$question = wp_parse_args(
		$question,
		array(
			'title'       => '',
			'answers'     => array(),
			'correct'     => array(),
			'mandatory'   => true,
			'explanation' => '',
			'max_retries' => 5,
			'active'      => false,
		)
	);

	// If setting active, check limit.
	if ( ! empty( $question['active'] ) ) {
		$active_count = 0;
		foreach ( $schema['questions'] as $qid => $q ) {
			if ( $qid !== $id && ! empty( $q['active'] ) ) {
				++$active_count;
			}
		}

		if ( $active_count >= 5 ) {
			return false; 
			// Max 5 active.
		}
	}

	$schema['questions'][ $id ] = $question;

	$result = apollo_save_quiz_schema( $form_type, $schema );

	return $result ? $id : false;
}

/**
 * Delete quiz question
 *
 * @param string $form_type Form type.
 * @param int    $id        Question ID.
 * @return bool True on success
 */
function apollo_delete_quiz_question( $form_type, $id ) {
	$schema = apollo_get_quiz_schema( $form_type );

	if ( ! isset( $schema['questions'][ $id ] ) ) {
		return false;
	}

	unset( $schema['questions'][ $id ] );

	return apollo_save_quiz_schema( $form_type, $schema );
}

/**
 * Toggle quiz enabled status
 *
 * @param string $form_type Form type.
 * @param bool   $enabled   Enable or disable.
 * @return bool True on success
 */
function apollo_set_quiz_enabled( string $form_type, bool $enabled ): bool {
	$schema            = apollo_get_quiz_schema( $form_type );
	$schema['enabled'] = (bool) $enabled;

	return apollo_save_quiz_schema( $form_type, $schema );
}

/**
 * Get Instagram info content
 *
 * @param string $form_type Form type.
 * @return array Info content
 */
function apollo_get_insta_info( string $form_type ): array {
	$info = get_option( APOLLO_INSTA_INFO_OPTION, array() );

	$defaults = array(
		'title'     => __( 'Conecte seu Instagram', 'apollo-core' ),
		'subtitle'  => __( 'Encontre amigos e compartilhe momentos', 'apollo-core' ),
		'paragraph' => __( 'Seu Instagram ajuda outros usuários a te encontrar e se conectar com você na plataforma.', 'apollo-core' ),
		'quote'     => __( '"A música conecta pessoas" - Apollo', 'apollo-core' ),
	);

	return isset( $info[ $form_type ] ) ? wp_parse_args( $info[ $form_type ], $defaults ) : $defaults;
}

/**
 * Save Instagram info content
 *
 * @param string $form_type Form type.
 * @param array  $content   Content data.
 * @return bool True on success
 */
function apollo_save_insta_info( $form_type, $content ) {
	$info = get_option( APOLLO_INSTA_INFO_OPTION, array() );

	$info[ $form_type ] = array(
		'title'     => sanitize_text_field( $content['title'] ?? '' ),
		'subtitle'  => sanitize_text_field( $content['subtitle'] ?? '' ),
		'paragraph' => wp_kses_post( $content['paragraph'] ?? '' ),
		'quote'     => sanitize_text_field( $content['quote'] ?? '' ),
	);

	return update_option( APOLLO_INSTA_INFO_OPTION, $info );
}

/**
 * Get quiz statistics
 *
 * @param string $form_type Form type.
 * @param int    $question_id Question ID.
 * @return array Stats
 */
function apollo_get_quiz_stats( $form_type, $question_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_quiz_attempts';

	// Total attempts.
	$total_attempts = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM $table WHERE question_id = %d",
			$question_id
		)
	);

	// Passed users.
	$passed_users = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM $table WHERE question_id = %d AND passed = 1",
			$question_id
		)
	);

	// Pass rate.
	$pass_rate = $total_attempts > 0 ? ( $passed_users / $total_attempts ) * 100 : 0;

	return array(
		'total_attempts' => (int) $total_attempts,
		'passed_users'   => (int) $passed_users,
		'failed_users'   => (int) ( $total_attempts - $passed_users ),
		'pass_rate'      => round( $pass_rate, 2 ),
	);
}

/**
 * Initialize quiz schemas option
 */
function apollo_init_quiz_schemas() {
	if ( false === get_option( APOLLO_QUIZ_SCHEMAS_OPTION ) ) {
		add_option( APOLLO_QUIZ_SCHEMAS_OPTION, apollo_get_default_quiz_schemas() );
		add_option( APOLLO_QUIZ_VERSION_OPTION, '1.0.0' );
	}

	if ( false === get_option( APOLLO_INSTA_INFO_OPTION ) ) {
		add_option( APOLLO_INSTA_INFO_OPTION, array() );
	}
}

/**
 * Migrate quiz schema (idempotent)
 */
function apollo_migrate_quiz_schema(): void {
	// Ensure options exist.
	apollo_init_quiz_schemas();

	// Create quiz attempts table if doesn't exist.
	global $wpdb;
	$table_name = $wpdb->prefix . 'apollo_quiz_attempts';

	// Check if table exists using prepare for safety.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

	if ( ! $table_exists ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			question_id bigint(20) unsigned NOT NULL,
			answers longtext NOT NULL,
			passed tinyint(1) NOT NULL DEFAULT 0,
			attempt_number int(11) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY question_id (question_id),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql );
	}//end if

	// Seed default quiz questions if not present.
	if ( function_exists( 'apollo_seed_default_quiz_questions' ) ) {
		apollo_seed_default_quiz_questions();
	}
}
