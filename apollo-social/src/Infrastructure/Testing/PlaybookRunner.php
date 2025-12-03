<?php

namespace Apollo\Infrastructure\Testing;

/**
 * Apollo Test Playbook Runner
 *
 * Automated test execution based on the workflow test playbook.
 */
class PlaybookRunner {

	private array $results = array();
	private int $passed    = 0;
	private int $failed    = 0;

	/**
	 * Run complete test playbook
	 */
	public function runPlaybook(): array {
		$this->results = array();
		$this->passed  = 0;
		$this->failed  = 0;

		echo "🧪 Apollo Social - Test Playbook Runner\n";
		echo "=====================================\n\n";

		// 1. Check prerequisites
		$this->checkPrerequisites();

		// 2. Run workflow matrix tests
		$this->runWorkflowMatrix();

		// 3. Run content creation scenarios
		$this->runContentScenarios();

		// 4. Test moderation workflows
		$this->runModerationTests();

		// 5. Test user permissions
		$this->runPermissionTests();

		// Summary
		$this->printSummary();

		return array(
			'passed'  => $this->passed,
			'failed'  => $this->failed,
			'results' => $this->results,
		);
	}

	/**
	 * Check prerequisites
	 */
	private function checkPrerequisites(): void {
		echo "📋 Checking Prerequisites...\n";

		// Check permalinks
		$permalink_structure = get_option( 'permalink_structure' );
		$this->assert(
			! empty( $permalink_structure ),
			'Permalinks configured',
			'Permalinks must be set (not Plain)'
		);

		// Check canvas config
		$canvas_config = include dirname( __DIR__, 3 ) . '/config/canvas.php';
		$this->assert(
			$canvas_config['force_canvas_on_plugin_routes'] ?? false,
			'Canvas force mode enabled',
			'Set force_canvas_on_plugin_routes = true in config/canvas.php'
		);

		// Check required tables
		global $wpdb;
		$tables = array(
			$wpdb->prefix . 'apollo_workflow_log',
			$wpdb->prefix . 'apollo_moderation_queue',
			$wpdb->prefix . 'apollo_groups',
		);

		foreach ( $tables as $table ) {
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
			$this->assert(
				$exists,
				"Table {$table} exists",
				'Run: wp apollo install'
			);
		}

		echo "\n";
	}

	/**
	 * Run workflow matrix tests
	 */
	private function runWorkflowMatrix(): void {
		echo "🔄 Testing Workflow Matrix...\n";

		$test_cases = array(
			// Subscriber tests
			array(
				'role'     => 'subscriber',
				'content'  => 'group',
				'data'     => array( 'type' => 'post' ),
				'expected' => 'published',
			),
			array(
				'role'     => 'subscriber',
				'content'  => 'group',
				'data'     => array( 'type' => 'discussion' ),
				'expected' => 'published',
			),
			array(
				'role'     => 'subscriber',
				'content'  => 'ad',
				'data'     => array(),
				'expected' => 'published',
			),
			array(
				'role'     => 'subscriber',
				'content'  => 'event',
				'data'     => array(),
				'expected' => 'pending_review',
			),
			array(
				'role'     => 'subscriber',
				'content'  => 'group',
				'data'     => array( 'type' => 'nucleo' ),
				'expected' => 'pending_review',
			),
			array(
				'role'     => 'subscriber',
				'content'  => 'group',
				'data'     => array( 'type' => 'comunidade' ),
				'expected' => 'pending_review',
			),

			// Contributor tests
			array(
				'role'     => 'contributor',
				'content'  => 'group',
				'data'     => array( 'type' => 'post' ),
				'expected' => 'draft',
			),
			array(
				'role'     => 'contributor',
				'content'  => 'ad',
				'data'     => array(),
				'expected' => 'draft',
			),
			array(
				'role'     => 'contributor',
				'content'  => 'event',
				'data'     => array(),
				'expected' => 'draft',
			),

			// Author tests
			array(
				'role'     => 'author',
				'content'  => 'group',
				'data'     => array( 'type' => 'post' ),
				'expected' => 'pending_review',
			),
			array(
				'role'     => 'author',
				'content'  => 'ad',
				'data'     => array(),
				'expected' => 'pending_review',
			),
			array(
				'role'     => 'author',
				'content'  => 'event',
				'data'     => array(),
				'expected' => 'published',
			),
			// Direct publish

							// Editor tests
							array(
								'role'     => 'editor',
								'content'  => 'group',
								'data'     => array( 'type' => 'post' ),
								'expected' => 'published',
							),
			array(
				'role'     => 'editor',
				'content'  => 'ad',
				'data'     => array(),
				'expected' => 'published',
			),
			array(
				'role'     => 'editor',
				'content'  => 'event',
				'data'     => array(),
				'expected' => 'published',
			),
			array(
				'role'     => 'editor',
				'content'  => 'group',
				'data'     => array( 'type' => 'nucleo' ),
				'expected' => 'published',
			),

			// Administrator tests
			array(
				'role'     => 'administrator',
				'content'  => 'group',
				'data'     => array( 'type' => 'post' ),
				'expected' => 'published',
			),
			array(
				'role'     => 'administrator',
				'content'  => 'ad',
				'data'     => array(),
				'expected' => 'published',
			),
			array(
				'role'     => 'administrator',
				'content'  => 'event',
				'data'     => array(),
				'expected' => 'published',
			),
		);

		foreach ( $test_cases as $test ) {
			$actual = $this->simulateWorkflowTest( $test['role'], $test['content'], $test['data'] );

			$content_desc = $test['content'];
			if ( ! empty( $test['data']['type'] ) ) {
				$content_desc .= " ({$test['data']['type']})";
			}

			$this->assert(
				$actual === $test['expected'],
				"{$test['role']} → {$content_desc} → {$actual}",
				"Expected: {$test['expected']}, Got: {$actual}"
			);
		}

		echo "\n";
	}

	/**
	 * Run content creation scenarios
	 */
	private function runContentScenarios(): void {
		echo "📝 Testing Content Creation Scenarios...\n";

		// Test A: Social post (Subscriber → published)
		$result = $this->testContentCreation(
			'subscriber',
			'group',
			array(
				'title' => 'Test Social Post',
				'type'  => 'post',
			)
		);
		$this->assert(
			$result['status'] === 'published',
			'Subscriber social post → published',
			"Expected published, got: {$result['status']}"
		);

		// Test B: Classified (Subscriber → published)
		$result = $this->testContentCreation(
			'subscriber',
			'ad',
			array(
				'title'       => 'Test Classified',
				'season_slug' => 'verao-2026',
			)
		);
		$this->assert(
			$result['status'] === 'published',
			'Subscriber classified → published (contract)',
			"Expected published, got: {$result['status']}"
		);

		// Test C: Event (Author → published)
		$result = $this->testContentCreation(
			'author',
			'event',
			array(
				'title' => 'Test Event by Author',
			)
		);
		$this->assert(
			$result['status'] === 'published',
			'Author event → published directly',
			"Expected published, got: {$result['status']}"
		);

		// Test D: Núcleo (any role → pending_review)
		$result = $this->testContentCreation(
			'subscriber',
			'group',
			array(
				'title' => 'Test Núcleo',
				'type'  => 'nucleo',
			)
		);
		$this->assert(
			$result['status'] === 'pending_review',
			'Núcleo creation → pending_review',
			"Expected pending_review, got: {$result['status']}"
		);

		// Test E: Comunidade (any role → pending_review)
		$result = $this->testContentCreation(
			'author',
			'group',
			array(
				'title' => 'Test Comunidade',
				'type'  => 'comunidade',
			)
		);
		$this->assert(
			$result['status'] === 'pending_review',
			'Comunidade creation → pending_review',
			"Expected pending_review, got: {$result['status']}"
		);

		echo "\n";
	}

	/**
	 * Run moderation tests
	 */
	private function runModerationTests(): void {
		echo "⚖️ Testing Moderation Workflows...\n";

		// Create test content in pending state
		$test_group = $this->testContentCreation(
			'subscriber',
			'group',
			array(
				'title' => 'Test Moderation Group',
				'type'  => 'nucleo',
			)
		);

		if ( $test_group['status'] === 'pending_review' ) {
			// Test approval
			$approval_result = $this->testModerationAction(
				$test_group['id'],
				'group',
				'approve',
				array(
					'moderator_role' => 'editor',
				)
			);
			$this->assert(
				$approval_result['success'],
				'Editor can approve pending content',
				"Approval failed: {$approval_result['message']}"
			);

			// Test rejection
			$test_group2 = $this->testContentCreation(
				'subscriber',
				'group',
				array(
					'title' => 'Test Rejection Group',
					'type'  => 'nucleo',
				)
			);

			$rejection_result = $this->testModerationAction(
				$test_group2['id'],
				'group',
				'reject',
				array(
					'moderator_role' => 'editor',
					'reason'         => 'Dados incompletos',
				)
			);
			$this->assert(
				$rejection_result['success'],
				'Editor can reject content with reason',
				"Rejection failed: {$rejection_result['message']}"
			);
		}//end if

		echo "\n";
	}

	/**
	 * Run permission tests
	 */
	private function runPermissionTests(): void {
		echo "🔐 Testing User Permissions...\n";

		$roles = array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' );

		foreach ( $roles as $role ) {
			$permissions = $this->getUserPermissions( $role );

			// Test specific permissions based on role
			switch ( $role ) {
				case 'subscriber':
					$this->assert(
						$permissions['can_create_groups'],
						"{$role} can create groups",
						'Subscribers should be able to create groups'
					);
					$this->assert(
						$permissions['can_create_ads'],
						"{$role} can create ads",
						'Subscribers should be able to create ads'
					);
					break;

				case 'author':
					$this->assert(
						$permissions['can_publish_events'],
						"{$role} can publish events directly",
						'Authors should be able to publish events directly'
					);
					break;

				case 'editor':
					$this->assert(
						$permissions['can_moderate'],
						"{$role} can moderate content",
						'Editors should have moderation capabilities'
					);
					break;
			}//end switch
		}//end foreach

		echo "\n";
	}

	/**
	 * Simulate workflow test
	 */
	private function simulateWorkflowTest( string $role, string $content_type, array $data ): string {
		// Create temporary user
		$user_id = wp_create_user( "test_{$role}_" . time(), 'password', 'test@example.com' );
		$user    = new \WP_User( $user_id );
		$user->set_role( $role );

		// Set current user
		wp_set_current_user( $user_id );

		try {
			$workflow = new \Apollo\Infrastructure\Workflows\ContentWorkflow();
			$result   = $workflow->getInitialState( $content_type, $data );
		} finally {
			// Cleanup
			wp_delete_user( $user_id );
			wp_set_current_user( 0 );
		}

		return $result;
	}

	/**
	 * Test content creation
	 */
	private function testContentCreation( string $role, string $content_type, array $data ): array {
		$user_id = wp_create_user( "test_{$role}_" . time(), 'password', 'test@example.com' );
		$user    = new \WP_User( $user_id );
		$user->set_role( $role );

		wp_set_current_user( $user_id );

		try {
			$workflow = new \Apollo\Infrastructure\Workflows\ContentWorkflow();
			$status   = $workflow->getInitialState( $content_type, $data );

			// Simulate actual content creation
			global $wpdb;

			if ( $content_type === 'group' ) {
				$result = $wpdb->insert(
					$wpdb->prefix . 'apollo_groups',
					array(
						'title'      => $data['title'],
						'type'       => $data['type'] ?? 'post',
						'status'     => $status,
						'author_id'  => $user_id,
						'created_at' => current_time( 'mysql' ),
					)
				);

				return array(
					'success' => $result !== false,
					'id'      => $wpdb->insert_id,
					'status'  => $status,
				);
			}

			// Add other content types as needed
			return array(
				'success' => true,
				'id'      => 0,
				'status'  => $status,
			);

		} finally {
			wp_delete_user( $user_id );
			wp_set_current_user( 0 );
		}//end try
	}

	/**
	 * Test moderation action
	 */
	private function testModerationAction( int $content_id, string $content_type, string $action, array $options ): array {
		$moderator_role = $options['moderator_role'] ?? 'editor';

		$user_id = wp_create_user( 'moderator_' . time(), 'password', 'mod@example.com' );
		$user    = new \WP_User( $user_id );
		$user->set_role( $moderator_role );

		wp_set_current_user( $user_id );

		try {
			$workflow = new \Apollo\Infrastructure\Workflows\ContentWorkflow();

			$new_state = $action === 'approve' ? 'published' : 'rejected';
			$reason    = $options['reason'] ?? '';

			$result = $workflow->transition(
				$content_id,
				$content_type,
				$new_state,
				array(
					'reason' => $reason,
				)
			);

			return $result;

		} finally {
			wp_delete_user( $user_id );
			wp_set_current_user( 0 );
		}//end try
	}

	/**
	 * Get user permissions for role
	 */
	private function getUserPermissions( string $role ): array {
		$user_id = wp_create_user( 'perm_test_' . time(), 'password', 'perm@example.com' );
		$user    = new \WP_User( $user_id );
		$user->set_role( $role );

		wp_set_current_user( $user_id );

		try {
			return array(
				'can_create_groups'  => current_user_can( 'create_apollo_groups' ),
				'can_create_ads'     => current_user_can( 'create_apollo_ads' ),
				'can_publish_events' => current_user_can( 'publish_eva_events' ),
				'can_moderate'       => current_user_can( 'apollo_moderate' ),
			);
		} finally {
			wp_delete_user( $user_id );
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Assert test result
	 */
	private function assert( bool $condition, string $message, string $error_detail = '' ): void {
		if ( $condition ) {
			echo "  ✅ {$message}\n";
			++$this->passed;
		} else {
			echo "  ❌ {$message}";
			if ( $error_detail ) {
				echo " - {$error_detail}";
			}
			echo "\n";
			++$this->failed;
		}

		$this->results[] = array(
			'passed'  => $condition,
			'message' => $message,
			'error'   => $error_detail,
		);
	}

	/**
	 * Print test summary
	 */
	private function printSummary(): void {
		echo "📊 Test Summary\n";
		echo "==============\n";
		echo "✅ Passed: {$this->passed}\n";
		echo "❌ Failed: {$this->failed}\n";
		echo 'Total: ' . ( $this->passed + $this->failed ) . "\n\n";

		if ( $this->failed === 0 ) {
			echo "🎉 All tests passed! Apollo Social is ready for release.\n";
		} else {
			echo "⚠️  Some tests failed. Please review and fix issues before release.\n";
		}
	}
}
