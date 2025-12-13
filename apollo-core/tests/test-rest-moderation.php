<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - REST Moderation Tests
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

class Test_Apollo_REST_Moderation extends WP_UnitTestCase {
	protected $moderator_id;
	protected $admin_id;
	protected $user_id;
	protected $post_id;

	public function setUp() {
		parent::setUp();

		// Create moderator user.
		$this->moderator_id = $this->factory->user->create(
			array(
				'role' => 'apollo',
			)
		);

		// Create admin user.
		$this->admin_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Create regular user.
		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		// Create draft post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'   => 'event_listing',
				'post_status' => 'draft',
				'post_title'  => 'Test Event',
			)
		);

		// Set enabled capabilities.
		$settings                                   = apollo_get_default_mod_settings();
		$settings['enabled_caps']['publish_events'] = true;
		update_option( 'apollo_mod_settings', $settings );
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test approve endpoint permission check
	 */
	public function test_approve_permission_denied() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/modaprovar' );
		$request->set_param( 'post_id', $this->post_id );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test approve endpoint with moderator
	 */
	public function test_approve_success() {
		wp_set_current_user( $this->moderator_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/modaprovar' );
		$request->set_param( 'post_id', $this->post_id );
		$request->set_param( 'note', 'Approved by test' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		// Check post status changed.
		$post = get_post( $this->post_id );
		$this->assertEquals( 'publish', $post->post_status );
	}

	/**
	 * Test approve with disabled capability
	 */
	public function test_approve_capability_disabled() {
		wp_set_current_user( $this->moderator_id );

		// Disable publish_events.
		$settings                                   = apollo_get_mod_settings();
		$settings['enabled_caps']['publish_events'] = false;
		update_option( 'apollo_mod_settings', $settings );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/modaprovar' );
		$request->set_param( 'post_id', $this->post_id );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test suspend user endpoint
	 */
	public function test_suspend_user_success() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/users/suspender/' );
		$request->set_param( 'user_id', $this->user_id );
		$request->set_param( 'days', 7 );
		$request->set_param( 'reason', 'Test suspension' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		// Check user meta.
		$suspended_until = get_user_meta( $this->user_id, '_apollo_suspended_until', true );
		$this->assertNotEmpty( $suspended_until );
		$this->assertGreaterThan( time(), $suspended_until );
	}

	/**
	 * Test suspend user permission denied
	 */
	public function test_suspend_user_permission_denied() {
		wp_set_current_user( $this->moderator_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/users/suspender/' );
		$request->set_param( 'user_id', $this->user_id );
		$request->set_param( 'days', 7 );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test block user endpoint
	 */
	public function test_block_user_success() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/users/bloquear/' );
		$request->set_param( 'user_id', $this->user_id );
		$request->set_param( 'reason', 'Test block' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		// Check user meta.
		$blocked = get_user_meta( $this->user_id, '_apollo_blocked', true );
		$this->assertEquals( 1, $blocked );
	}

	/**
	 * Test cannot suspend admin
	 */
	public function test_cannot_suspend_admin() {
		wp_set_current_user( $this->admin_id );

		$another_admin = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$request = new WP_REST_Request( 'POST', '/apollo/v1/users/suspender/' );
		$request->set_param( 'user_id', $another_admin );
		$request->set_param( 'days', 7 );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test audit log entry created
	 */
	public function test_audit_log_created() {
		wp_set_current_user( $this->admin_id );

		// Suspend user.
		$request = new WP_REST_Request( 'POST', '/apollo/v1/users/suspender/' );
		$request->set_param( 'user_id', $this->user_id );
		$request->set_param( 'days', 7 );
		$request->set_param( 'reason', 'Test audit' );

		rest_do_request( $request );

		// Check log.
		$logs = apollo_get_mod_log(
			array(
				'action'    => 'suspend_user',
				'target_id' => $this->user_id,
				'limit'     => 1,
			)
		);

		$this->assertNotEmpty( $logs );
		$this->assertEquals( 'suspend_user', $logs[0]->action );
		$this->assertEquals( $this->user_id, $logs[0]->target_id );
		$this->assertEquals( $this->admin_id, $logs[0]->actor_id );
	}
}
