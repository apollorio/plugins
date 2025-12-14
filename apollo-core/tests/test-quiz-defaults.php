<?php

// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - Default Quiz Questions Tests
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

/**
 * Test default quiz questions functionality
 */
class Apollo_Quiz_Defaults_Test extends WP_UnitTestCase
{
    /**
     * Setup test
     */
    public function setUp(): void
    {
        parent::setUp();

        // Clear quiz schemas before each test.
        delete_option(APOLLO_QUIZ_SCHEMAS_OPTION);
        delete_option(APOLLO_QUIZ_VERSION_OPTION);
    }

    /**
     * Test default quiz questions structure
     */
    public function test_default_quiz_questions_structure()
    {
        $defaults = apollo_get_default_quiz_questions();

        $this->assertIsArray($defaults);
        $this->assertCount(5, $defaults, 'Should have exactly 5 default questions');

        // Verify each question has required fields.
        foreach ($defaults as $id => $question) {
            $this->assertArrayHasKey('id', $question);
            $this->assertArrayHasKey('title', $question);
            $this->assertArrayHasKey('answers', $question);
            $this->assertArrayHasKey('correct', $question);
            $this->assertArrayHasKey('mandatory', $question);
            $this->assertArrayHasKey('explanation', $question);
            $this->assertArrayHasKey('max_retries', $question);
            $this->assertArrayHasKey('active', $question);
            $this->assertArrayHasKey('created_at', $question);

            // Verify data types.
            $this->assertIsString($question['title']);
            $this->assertIsArray($question['answers']);
            $this->assertIsArray($question['correct']);
            $this->assertIsBool($question['mandatory']);
            $this->assertIsString($question['explanation']);
            $this->assertIsInt($question['max_retries']);
            $this->assertIsBool($question['active']);

            // Verify answers structure (A-E).
            $this->assertArrayHasKey('A', $question['answers']);
            $this->assertArrayHasKey('B', $question['answers']);
            $this->assertArrayHasKey('C', $question['answers']);
            $this->assertArrayHasKey('D', $question['answers']);
            $this->assertArrayHasKey('E', $question['answers']);
        }
    }

    /**
     * Test specific question IDs
     */
    public function test_default_question_ids()
    {
        $defaults = apollo_get_default_quiz_questions();

        $expected_ids = [ 'q1', 'q2', 'q3', 'q4', 'q5' ];

        foreach ($expected_ids as $id) {
            $this->assertArrayHasKey($id, $defaults, "Should have question $id");
        }
    }

    /**
     * Test seeding default questions
     */
    public function test_seed_default_quiz_questions()
    {
        // Seed questions.
        $result = apollo_seed_default_quiz_questions();

        $this->assertTrue($result, 'Seeding should return true on first run');

        // Verify option was created.
        $schemas = get_option(APOLLO_QUIZ_SCHEMAS_OPTION);

        $this->assertIsArray($schemas);
        $this->assertArrayHasKey('new_user', $schemas);
        $this->assertArrayHasKey('questions', $schemas['new_user']);
        $this->assertCount(5, $schemas['new_user']['questions']);

        // Verify quiz is enabled.
        $this->assertTrue($schemas['new_user']['enabled']);
    }

    /**
     * Test seeding is idempotent
     */
    public function test_seed_is_idempotent()
    {
        // Seed first time.
        $result1 = apollo_seed_default_quiz_questions();
        $this->assertTrue($result1);

        // Seed second time.
        $result2 = apollo_seed_default_quiz_questions();
        $this->assertFalse($result2, 'Seeding should return false on subsequent runs');

        // Verify still only 5 questions.
        $schemas = get_option(APOLLO_QUIZ_SCHEMAS_OPTION);
        $this->assertCount(5, $schemas['new_user']['questions']);
    }

    /**
     * Test maximum 5 active questions enforced
     */
    public function test_max_five_active_questions()
    {
        apollo_seed_default_quiz_questions();

        $schemas      = get_option(APOLLO_QUIZ_SCHEMAS_OPTION);
        $active_count = 0;

        foreach ($schemas['new_user']['questions'] as $question) {
            if (! empty($question['active'])) {
                ++$active_count;
            }
        }

        $this->assertLessThanOrEqual(5, $active_count, 'Should have max 5 active questions');
        $this->assertEquals(5, $active_count, 'All 5 default questions should be active');
    }

    /**
     * Test activation seeds questions
     */
    public function test_activation_seeds_questions()
    {
        // Simulate activation.
        apollo_migrate_quiz_schema();

        // Verify questions were seeded.
        $schemas = get_option(APOLLO_QUIZ_SCHEMAS_OPTION);

        $this->assertIsArray($schemas);
        $this->assertArrayHasKey('new_user', $schemas);
        $this->assertCount(5, $schemas['new_user']['questions']);
    }

    /**
     * Test get default question by ID
     */
    public function test_get_default_quiz_question()
    {
        $question = apollo_get_default_quiz_question('q1');

        $this->assertIsArray($question);
        $this->assertEquals('q1', $question['id']);
        $this->assertStringContainsString('avaliar o trabalho', $question['title']);

        // Test non-existent question.
        $invalid = apollo_get_default_quiz_question('q999');
        $this->assertFalse($invalid);
    }

    /**
     * Test is default quiz question
     */
    public function test_is_default_quiz_question()
    {
        $this->assertTrue(apollo_is_default_quiz_question('q1'));
        $this->assertTrue(apollo_is_default_quiz_question('q2'));
        $this->assertTrue(apollo_is_default_quiz_question('q3'));
        $this->assertTrue(apollo_is_default_quiz_question('q4'));
        $this->assertTrue(apollo_is_default_quiz_question('q5'));

        $this->assertFalse(apollo_is_default_quiz_question('q999'));
        $this->assertFalse(apollo_is_default_quiz_question('custom_q'));
    }

    /**
     * Test question content (verify ethical/pedagogical)
     */
    public function test_question_content_quality()
    {
        $defaults = apollo_get_default_quiz_questions();

        // Q1: Feedback construtivo.
        $this->assertStringContainsString('feedback construtivo', $defaults['q1']['title']);
        $this->assertEquals([ 'A' ], $defaults['q1']['correct']);

        // Q2: Conversar em privado.
        $this->assertStringContainsString('discordar', $defaults['q2']['title']);
        $this->assertEquals([ 'C' ], $defaults['q2']['correct']);
        $this->assertStringContainsString('privado', $defaults['q2']['answers']['C']);

        // Q3: Aprender com feedback.
        $this->assertStringContainsString('aprender', $defaults['q3']['title']);
        $this->assertEquals([ 'A' ], $defaults['q3']['correct']);

        // Q4: Comunicação e respeito.
        $this->assertStringContainsString('colaborar', $defaults['q4']['title']);
        $this->assertEquals([ 'A' ], $defaults['q4']['correct']);
        $this->assertStringContainsString('Comunicação', $defaults['q4']['answers']['A']);

        // Q5: Ajudar colegas.
        $this->assertStringContainsString('ajuda', $defaults['q5']['title']);
        $this->assertEquals([ 'A' ], $defaults['q5']['correct']);
    }

    /**
     * Test all default questions have max_retries of 3
     */
    public function test_default_max_retries()
    {
        $defaults = apollo_get_default_quiz_questions();

        foreach ($defaults as $id => $question) {
            $this->assertEquals(3, $question['max_retries'], "Question $id should have max_retries = 3");
        }
    }

    /**
     * Test mandatory flag distribution
     */
    public function test_mandatory_flag_distribution()
    {
        $defaults = apollo_get_default_quiz_questions();

        // Q1, Q2, Q3 should be mandatory.
        $this->assertTrue($defaults['q1']['mandatory']);
        $this->assertTrue($defaults['q2']['mandatory']);
        $this->assertTrue($defaults['q3']['mandatory']);

        // Q4, Q5 should be optional.
        $this->assertFalse($defaults['q4']['mandatory']);
        $this->assertFalse($defaults['q5']['mandatory']);
    }

    /**
     * Test all default questions are active
     */
    public function test_all_defaults_active()
    {
        $defaults = apollo_get_default_quiz_questions();

        foreach ($defaults as $id => $question) {
            $this->assertTrue($question['active'], "Question $id should be active");
        }
    }

    /**
     * Test empty answers are handled correctly
     */
    public function test_empty_answers_ignored()
    {
        $defaults = apollo_get_default_quiz_questions();

        // Q1 has empty E.
        $this->assertEmpty($defaults['q1']['answers']['E']);

        // Q3 has empty D and E.
        $this->assertEmpty($defaults['q3']['answers']['D']);
        $this->assertEmpty($defaults['q3']['answers']['E']);

        // Q5 has empty D and E.
        $this->assertEmpty($defaults['q5']['answers']['D']);
        $this->assertEmpty($defaults['q5']['answers']['E']);
    }

    /**
     * Test explanations are present
     */
    public function test_explanations_present()
    {
        $defaults = apollo_get_default_quiz_questions();

        foreach ($defaults as $id => $question) {
            $this->assertNotEmpty($question['explanation'], "Question $id should have explanation");
            $this->assertGreaterThan(20, strlen($question['explanation']), "Question $id explanation should be meaningful");
        }
    }

    /**
     * Test schema version is set after seeding
     */
    public function test_schema_version_set()
    {
        apollo_seed_default_quiz_questions();

        $version = get_option(APOLLO_QUIZ_VERSION_OPTION);
        $this->assertEquals('1.0.0', $version);
    }

    /**
     * Test seeded questions can be retrieved via API
     */
    public function test_seeded_questions_retrievable()
    {
        apollo_seed_default_quiz_questions();

        $active_questions = apollo_get_active_quiz_questions('new_user');

        $this->assertCount(5, $active_questions);
        $this->assertArrayHasKey('q1', $active_questions);
        $this->assertArrayHasKey('q5', $active_questions);
    }
}
