<?php
/**
 * REST API Tests
 *
 * @package Apollo_Core
 */

/**
 * REST API test class
 */
class Apollo_Core_REST_API_Test extends WP_UnitTestCase {
	/**
	 * Test health check endpoint
	 */
	public function test_health_check_endpoint() {
		$request  = new WP_REST_Request( 'GET', '/apollo/v1/health' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Health check should return 200' );

		$data = $response->get_data();
		$this->assertEquals( 'ok', $data['status'], 'Health check status should be ok' );
		$this->assertEquals( APOLLO_CORE_VERSION, $data['version'], 'Health check should return correct version' );
		$this->assertIsArray( $data['modules'], 'Health check should return modules array' );
	}

	/**
	 * Test events endpoint
	 */
	public function test_events_endpoint() {
		// Create test event.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'event_listing',
				'post_status' => 'publish',
				'post_title'  => 'Test Event',
			)
		);

		$request  = new WP_REST_Request( 'GET', '/apollo/v1/events' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Events endpoint should return 200' );

		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Events endpoint should return success' );
		$this->assertIsArray( $data['data'], 'Events endpoint should return data array' );
		$this->assertGreaterThan( 0, $data['total'], 'Events endpoint should return total > 0' );
	}

	/**
	 * Test single event endpoint
	 */
	public function test_single_event_endpoint() {
		// Create test event.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'event_listing',
				'post_status' => 'publish',
				'post_title'  => 'Test Event',
			)
		);

		$request  = new WP_REST_Request( 'GET', '/apollo/v1/events/' . $event_id );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Single event endpoint should return 200' );

		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Single event endpoint should return success' );
		$this->assertEquals( $event_id, $data['data']['id'], 'Event ID should match' );
	}

	/**
	 * Test feed endpoint
	 */
	public function test_feed_endpoint() {
		// Create test posts.
		$this->factory->post->create(
			array(
				'post_type'   => 'apollo_social_post',
				'post_status' => 'publish',
			)
		);

		$this->factory->post->create(
			array(
				'post_type'   => 'event_listing',
				'post_status' => 'publish',
			)
		);

		$request  = new WP_REST_Request( 'GET', '/apollo/v1/feed' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Feed endpoint should return 200' );

		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Feed endpoint should return success' );
		$this->assertIsArray( $data['data'], 'Feed endpoint should return data array' );
		$this->assertGreaterThanOrEqual( 2, $data['total'], 'Feed should contain at least 2 items' );
	}

	/**
	 * Test permission callback
	 */
	public function test_permission_callback() {
		// Test logged out.
		$request  = new WP_REST_Request( 'POST', '/apollo/v1/events' );
		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status(), 'Creating event should require login' );

		// Test logged in.
		$user_id = $this->factory->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/events' );
		$request->set_param( 'title', 'Test Event' );
		$request->set_param( 'content', 'Test content' );
		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status(), 'Creating event should work when logged in' );
	}

	/**
	 * Test like endpoint
	 */
	public function test_like_endpoint() {
		// Create test user.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Create test post.
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo_social_post',
				'post_status' => 'publish',
			)
		);

		// Like post.
		$request = new WP_REST_Request( 'POST', '/apollo/v1/like' );
		$request->set_param( 'content_id', $post_id );
		$request->set_param( 'content_type', 'apollo_social_post' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Like endpoint should return 200' );

		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Like endpoint should return success' );
		$this->assertTrue( $data['liked'], 'Post should be liked' );

		// Unlike post.
		$request = new WP_REST_Request( 'POST', '/apollo/v1/like' );
		$request->set_param( 'content_id', $post_id );
		$request->set_param( 'content_type', 'apollo_social_post' );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertFalse( $data['liked'], 'Post should be unliked' );
	}
}

