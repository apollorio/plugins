<?php

declare(strict_types=1);

/**
 * Apollo Core - Default Quiz Questions
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get default quiz questions for new_user registration
 *
 * @return array Default questions
 */
function apollo_get_default_quiz_questions(): array {
	return array(
		'q1' => array(
			'id'          => 'q1',
			'title'       => __( 'Você costuma avaliar o trabalho de outras pessoas de forma construtiva?', 'apollo-core' ),
			'answers'     => array(
				'A' => __( 'Sim, sempre tento dar feedback construtivo', 'apollo-core' ),
				'B' => __( 'Às vezes, depende da situação', 'apollo-core' ),
				'C' => __( 'Raramente', 'apollo-core' ),
				'D' => __( 'Não, prefiro não opinar', 'apollo-core' ),
				'E' => '',
			),
			'correct'     => array( 'A' ),
			'mandatory'   => true,
			'explanation' => __( 'Dar feedback construtivo ajuda a comunidade a crescer. Se escolher outra opção, pense em como transformar críticas em sugestões úteis.', 'apollo-core' ),
			'max_retries' => 3,
			'active'      => true,
			'created_at'  => current_time( 'mysql' ),
		),
		'q2' => array(
			'id'          => 'q2',
			'title'       => __( 'Se você discordar do trabalho de alguém, qual é a melhor atitude?', 'apollo-core' ),
			'answers'     => array(
				'A' => __( 'Ignorar e seguir em frente', 'apollo-core' ),
				'B' => __( 'Criticar publicamente sem contexto', 'apollo-core' ),
				'C' => __( 'Conversar em privado e oferecer sugestões', 'apollo-core' ),
				'D' => __( 'Compartilhar para ridicularizar', 'apollo-core' ),
				'E' => '',
			),
			'correct'     => array( 'C' ),
			'mandatory'   => true,
			'explanation' => __( 'Conversar em privado e oferecer sugestões é mais respeitoso e eficaz para melhorar o trabalho alheio.', 'apollo-core' ),
			'max_retries' => 3,
			'active'      => true,
			'created_at'  => current_time( 'mysql' ),
		),
		'q3' => array(
			'id'          => 'q3',
			'title'       => __( 'Você está disposto a aprender e melhorar habilidades quando recebe feedback?', 'apollo-core' ),
			'answers'     => array(
				'A' => __( 'Sim, busco aprender com feedback', 'apollo-core' ),
				'B' => __( 'Só se for de alguém que eu respeito', 'apollo-core' ),
				'C' => __( 'Não, não costumo mudar', 'apollo-core' ),
				'D' => '',
				'E' => '',
			),
			'correct'     => array( 'A' ),
			'mandatory'   => true,
			'explanation' => __( 'Abertura ao aprendizado é essencial para crescimento profissional e comunitário.', 'apollo-core' ),
			'max_retries' => 3,
			'active'      => true,
			'created_at'  => current_time( 'mysql' ),
		),
		'q4' => array(
			'id'          => 'q4',
			'title'       => __( 'Ao colaborar em projetos, qual comportamento você considera essencial?', 'apollo-core' ),
			'answers'     => array(
				'A' => __( 'Comunicação clara e respeito', 'apollo-core' ),
				'B' => __( 'Fazer tudo sozinho para garantir qualidade', 'apollo-core' ),
				'C' => __( 'Ignorar opiniões divergentes', 'apollo-core' ),
				'D' => __( 'Priorizar fama sobre trabalho em equipe', 'apollo-core' ),
				'E' => '',
			),
			'correct'     => array( 'A' ),
			'mandatory'   => false,
			'explanation' => __( 'Comunicação e respeito são pilares de colaboração saudável.', 'apollo-core' ),
			'max_retries' => 3,
			'active'      => true,
			'created_at'  => current_time( 'mysql' ),
		),
		'q5' => array(
			'id'          => 'q5',
			'title'       => __( 'Se alguém pedir ajuda, você costuma:', 'apollo-core' ),
			'answers'     => array(
				'A' => __( 'Ajudar quando possível e orientar', 'apollo-core' ),
				'B' => __( 'Dizer que não tem tempo e ignorar', 'apollo-core' ),
				'C' => __( 'Pedir algo em troca imediatamente', 'apollo-core' ),
				'D' => '',
				'E' => '',
			),
			'correct'     => array( 'A' ),
			'mandatory'   => false,
			'explanation' => __( 'Apoiar colegas fortalece a comunidade; pequenas ajudas fazem diferença.', 'apollo-core' ),
			'max_retries' => 3,
			'active'      => true,
			'created_at'  => current_time( 'mysql' ),
		),
	);
}

/**
 * Seed default quiz questions (idempotent)
 *
 * @return bool True if seeded, false if already exists
 */
function apollo_seed_default_quiz_questions(): bool {
	$schemas = get_option( APOLLO_QUIZ_SCHEMAS_OPTION, array() );

	// Check if new_user schema already has questions.
	if ( isset( $schemas['new_user']['questions'] ) && ! empty( $schemas['new_user']['questions'] ) ) {
		// Already seeded.
		return false;
	}

	// Get default questions.
	$default_questions = apollo_get_default_quiz_questions();

	// Initialize new_user schema if not present.
	if ( ! isset( $schemas['new_user'] ) ) {
		$schemas['new_user'] = array(
			'enabled'      => true,
			'require_pass' => false,
			'questions'    => array(),
		);
	}

	// Add default questions.
	$schemas['new_user']['questions'] = $default_questions;
	$schemas['new_user']['enabled']   = true;

	// Ensure only 5 active questions.
	$active_count = 0;
	foreach ( $schemas['new_user']['questions'] as $id => &$question ) {
		if ( ! empty( $question['active'] ) ) {
			++$active_count;
			if ( $active_count > 5 ) {
				// Deactivate extras.
				$question['active'] = false;
			}
		}
	}

	// Save schema.
	$result = update_option( APOLLO_QUIZ_SCHEMAS_OPTION, $schemas );

	// Update version.
	if ( $result ) {
		update_option( APOLLO_QUIZ_VERSION_OPTION, '1.0.0' );

		// Log seeding action if audit log is available.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				get_current_user_id() ?: 0,
				'quiz_default_seeded',
				'quiz_schema',
				0,
				array(
					'form_type'       => 'new_user',
					'questions_count' => count( $default_questions ),
					'active_count'    => min( $active_count, 5 ),
				)
			);
		}
	}

	return $result;
}

/**
 * Get default quiz question by ID
 *
 * @param string $question_id Question ID.
 * @return array|false Question data or false if not found.
 */
function apollo_get_default_quiz_question( $question_id ) {
	$defaults = apollo_get_default_quiz_questions();

	return isset( $defaults[ $question_id ] ) ? $defaults[ $question_id ] : false;
}

/**
 * Check if question is a default question
 *
 * @param string $question_id Question ID.
 * @return bool True if default question.
 */
function apollo_is_default_quiz_question( $question_id ) {
	$defaults = apollo_get_default_quiz_questions();

	return isset( $defaults[ $question_id ] );
}
