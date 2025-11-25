<?php
declare(strict_types=1);

/**
 * Apollo Core - Registration Quiz Tests
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

/**
 * Test registration quiz functionality
 */
class Apollo_Registration_Quiz_Test extends WP_UnitTestCase {
	/**
	 * Test quiz schema initialization
	 */
	public function test_quiz_schema_initialization() {
		apollo_init_quiz_schemas();

		$schema = apollo_get_quiz_schema( 'new_user' );
		
		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'enabled', $schema );
		$this->assertArrayHasKey( 'questions', $schema );
	}

	/**
	 * Test adding quiz question
	 */
	public function test_add_quiz_question() {
		$question = array(
			'title'       => 'What is electronic music?',
			'answers'     => array(
				'A' => 'Music made with computers',
				'B' => 'Classical music',
				'C' => 'Rock music',
			),
			'correct'     => array( 'A' ),
			'mandatory'   => true,
			'explanation' => 'Electronic music is made with electronic instruments.',
			'max_retries' => 5,
			'active'      => true,
		);

		$question_id = apollo_save_quiz_question( 'new_user', $question );
		
		$this->assertNotFalse( $question_id );
		$this->assertIsInt( $question_id );

		// Verify question was saved.
		$schema = apollo_get_quiz_schema( 'new_user' );
		$this->assertArrayHasKey( $question_id, $schema['questions'] );
		$this->assertEquals( 'What is electronic music?', $schema['questions'][ $question_id ]['title'] );
	}

	/**
	 * Test max 5 active questions limit
	 */
	public function test_max_active_questions_limit() {
		// Add 5 active questions.
		for ( $i = 1; $i <= 5; $i++ ) {
			$question = array(
				'title'       => "Question $i",
				'answers'     => array( 'A' => 'Answer A', 'B' => 'Answer B' ),
				'correct'     => array( 'A' ),
				'active'      => true,
			);
			
			$result = apollo_save_quiz_question( 'new_user', $question );
			$this->assertNotFalse( $result, "Should be able to add question $i" );
		}

		// Try to add 6th active question - should fail.
		$question = array(
			'title'       => 'Question 6',
			'answers'     => array( 'A' => 'Answer A' ),
			'correct'     => array( 'A' ),
			'active'      => true,
		);
		
		$result = apollo_save_quiz_question( 'new_user', $question );
		$this->assertFalse( $result, 'Should not be able to add 6th active question' );
	}

	/**
	 * Test quiz answer validation
	 */
	public function test_quiz_answer_validation() {
		// Add test question.
		$question_data = array(
			'title'       => 'Select multiple correct answers',
			'answers'     => array(
				'A' => 'Correct 1',
				'B' => 'Incorrect',
				'C' => 'Correct 2',
			),
			'correct'     => array( 'A', 'C' ),
			'active'      => true,
		);
		
		$question_id = apollo_save_quiz_question( 'new_user', $question_data );

		// Test correct answer.
		$passed = apollo_validate_quiz_answer( $question_id, array( 'A', 'C' ), 'new_user' );
		$this->assertTrue( $passed, 'Correct answers should pass' );

		// Test incorrect answer.
		$passed = apollo_validate_quiz_answer( $question_id, array( 'A', 'B' ), 'new_user' );
		$this->assertFalse( $passed, 'Incorrect answers should fail' );

		// Test partial answer.
		$passed = apollo_validate_quiz_answer( $question_id, array( 'A' ), 'new_user' );
		$this->assertFalse( $passed, 'Partial answers should fail' );
	}

	/**
	 * Test recording quiz attempts
	 */
	public function test_record_quiz_attempt() {
		// Create test user.
		$user_id = $this->factory->user->create();

		// Create test question.
		$question = array(
			'title'   => 'Test question',
			'answers' => array( 'A' => 'Answer A', 'B' => 'Answer B' ),
			'correct' => array( 'A' ),
			'active'  => true,
		);
		
		$question_id = apollo_save_quiz_question( 'new_user', $question );

		// Record attempt.
		$attempt_id = apollo_record_quiz_attempt( $user_id, $question_id, array( 'A' ), true );
		
		$this->assertNotFalse( $attempt_id );

		// Verify attempt was recorded.
		$attempts = apollo_get_user_attempts( $user_id, $question_id );
		$this->assertNotEmpty( $attempts );
		$this->assertCount( 1, $attempts );
		$this->assertEquals( 1, $attempts[0]->passed );
	}

	/**
	 * Test attempt count limit
	 */
	public function test_attempt_count_limit() {
		$user_id = $this->factory->user->create();

		// Create question with max 3 retries.
		$question = array(
			'title'       => 'Limited retries',
			'answers'     => array( 'A' => 'Correct', 'B' => 'Wrong' ),
			'correct'     => array( 'A' ),
			'max_retries' => 3,
			'active'      => true,
		);
		
		$question_id = apollo_save_quiz_question( 'new_user', $question );

		// Make 3 failed attempts.
		for ( $i = 1; $i <= 3; $i++ ) {
			apollo_record_quiz_attempt( $user_id, $question_id, array( 'B' ), false );
		}

		// Verify attempt count.
		$count = apollo_get_attempt_count( $user_id, $question_id );
		$this->assertEquals( 3, $count );
	}

	/**
	 * Test quiz processing for registration
	 */
	public function test_process_quiz_submission() {
		// Create 2 active questions.
		$q1 = array(
			'title'   => 'Question 1',
			'answers' => array( 'A' => 'Correct', 'B' => 'Wrong' ),
			'correct' => array( 'A' ),
			'active'  => true,
		);
		
		$q2 = array(
			'title'   => 'Question 2',
			'answers' => array( 'A' => 'Wrong', 'B' => 'Correct' ),
			'correct' => array( 'B' ),
			'active'  => true,
		);
		
		$q1_id = apollo_save_quiz_question( 'new_user', $q1 );
		$q2_id = apollo_save_quiz_question( 'new_user', $q2 );

		// Enable quiz.
		apollo_set_quiz_enabled( 'new_user', true );

		// Test all correct answers.
		$answers = array(
			$q1_id => array( 'A' ),
			$q2_id => array( 'B' ),
		);
		
		$result = apollo_process_quiz_submission( $answers, 'new_user', 0 );
		
		$this->assertTrue( $result['success'] );
		$this->assertTrue( $result['passed'] );

		// Test some incorrect answers.
		$answers = array(
			$q1_id => array( 'B' ), // Wrong.
			$q2_id => array( 'B' ), // Correct.
		);
		
		$result = apollo_process_quiz_submission( $answers, 'new_user', 0 );
		
		$this->assertTrue( $result['success'] );
		$this->assertFalse( $result['passed'] );
		$this->assertFalse( $result['results'][ $q1_id ]['passed'] );
		$this->assertTrue( $result['results'][ $q2_id ]['passed'] );
	}

	/**
	 * Test registration with quiz pass
	 */
	public function test_registration_with_quiz_pass() {
		// Create active question.
		$question = array(
			'title'   => 'Registration quiz',
			'answers' => array( 'A' => 'Correct' ),
			'correct' => array( 'A' ),
			'active'  => true,
		);
		
		$question_id = apollo_save_quiz_question( 'new_user', $question );
		
		// Enable quiz but don't require pass.
		$schema = apollo_get_quiz_schema( 'new_user' );
		$schema['enabled'] = true;
		$schema['require_pass'] = false;
		apollo_save_quiz_schema( 'new_user', $schema );

		// Create user with correct quiz answer.
		$user_data = array(
			'user_login'      => 'testuser',
			'user_email'      => 'test@example.com',
			'user_pass'       => 'password123',
			'quiz_answers'    => array( $question_id => array( 'A' ) ),
		);

		$result = apollo_process_new_user_form( $user_data );
		
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_id', $result );

		// Verify quiz status.
		$status = apollo_get_user_quiz_status( $result['user_id'] );
		$this->assertEquals( 'passed', $status );
	}

	/**
	 * Test registration with quiz fail
	 */
	public function test_registration_with_quiz_fail() {
		// Create active question.
		$question = array(
			'title'   => 'Registration quiz',
			'answers' => array( 'A' => 'Correct', 'B' => 'Wrong' ),
			'correct' => array( 'A' ),
			'active'  => true,
		);
		
		$question_id = apollo_save_quiz_question( 'new_user', $question );
		
		// Enable quiz but don't require pass (allow registration with failed quiz).
		$schema = apollo_get_quiz_schema( 'new_user' );
		$schema['enabled'] = true;
		$schema['require_pass'] = false;
		apollo_save_quiz_schema( 'new_user', $schema );

		// Create user with incorrect quiz answer.
		$user_data = array(
			'user_login'      => 'testuser2',
			'user_email'      => 'test2@example.com',
			'user_pass'       => 'password123',
			'quiz_answers'    => array( $question_id => array( 'B' ) ),
		);

		$result = apollo_process_new_user_form( $user_data );
		
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_id', $result );

		// Verify quiz status.
		$status = apollo_get_user_quiz_status( $result['user_id'] );
		$this->assertEquals( 'failed', $status );
	}

	/**
	 * Test user quiz status meta
	 */
	public function test_user_quiz_status() {
		$user_id = $this->factory->user->create();

		// Set passed status.
		apollo_set_user_quiz_status( $user_id, 'passed' );
		$status = apollo_get_user_quiz_status( $user_id );
		$this->assertEquals( 'passed', $status );

		// Set failed status.
		apollo_set_user_quiz_status( $user_id, 'failed' );
		$status = apollo_get_user_quiz_status( $user_id );
		$this->assertEquals( 'failed', $status );
	}

	/**
	 * Test rate limiting
	 */
	public function test_quiz_rate_limiting() {
		$ip = '192.168.1.1';
		$user_id = 123;

		// First 10 attempts should be allowed.
		for ( $i = 1; $i <= 10; $i++ ) {
			$allowed = apollo_check_quiz_rate_limit( $ip, $user_id, 10 );
			$this->assertTrue( $allowed, "Attempt $i should be allowed" );
		}

		// 11th attempt should be blocked.
		$allowed = apollo_check_quiz_rate_limit( $ip, $user_id, 10 );
		$this->assertFalse( $allowed, '11th attempt should be blocked' );
	}

	/**
	 * Test get active quiz questions
	 */
	public function test_get_active_quiz_questions() {
		// Add 3 questions, 2 active.
		$q1 = array(
			'title'   => 'Active Q1',
			'answers' => array( 'A' => 'Answer' ),
			'correct' => array( 'A' ),
			'active'  => true,
		);
		
		$q2 = array(
			'title'   => 'Inactive Q2',
			'answers' => array( 'A' => 'Answer' ),
			'correct' => array( 'A' ),
			'active'  => false,
		);
		
		$q3 = array(
			'title'   => 'Active Q3',
			'answers' => array( 'A' => 'Answer' ),
			'correct' => array( 'A' ),
			'active'  => true,
		);
		
		apollo_save_quiz_question( 'new_user', $q1 );
		apollo_save_quiz_question( 'new_user', $q2 );
		apollo_save_quiz_question( 'new_user', $q3 );

		$active = apollo_get_active_quiz_questions( 'new_user' );
		
		$this->assertCount( 2, $active );
	}
}


