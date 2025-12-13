<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - REST Forms Tests
 *
 * Tests for forms REST API endpoints
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/tests/test-rest-forms.php
 */

class Test_Apollo_REST_Forms extends WP_UnitTestCase {

	protected $user_id;

	public function setUp() {
		parent::setUp();

		// Create test user.
		$this->user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Initialize schemas.
		apollo_init_form_schemas();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test get schema endpoint
	 */
	public function test_get_schema_endpoint() {
		$request = new WP_REST_Request( 'GET', '/apollo/v1/forms/schema' );
		$request->set_param( 'form_type', 'new_user' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'schema', $data );
		$this->assertIsArray( $data['schema'] );
		$this->assertNotEmpty( $data['schema'] );
	}

	/**
	 * Test submit form endpoint - validation error
	 */
	public function test_submit_form_validation_error() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_type', 'new_user' );
		$request->set_param(
			'data',
			array(
				'user_login' => '', // Empty required field.
				'user_email' => 'test@example.com',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'errors', $data );
	}

	/**
	 * Test submit form endpoint - success
	 */
	public function test_submit_form_success() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_type', 'new_user' );
		$request->set_param(
			'data',
			array(
				'user_login'        => 'newtestuser',
				'user_email'        => 'newtest@example.com',
				'user_pass'         => 'password123',
				'instagram_user_id' => 'instagram_user',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );

		// Check user created.
		$user = get_user_by( 'login', 'newtestuser' );
		$this->assertInstanceOf( 'WP_User', $user );

		// Check Instagram ID saved.
		$instagram_id = get_user_meta( $user->ID, '_apollo_instagram_id', true );
		$this->assertEquals( 'instagram_user', $instagram_id );
	}

	/**
	 * Test submit form without nonce
	 */
	public function test_submit_form_without_nonce() {
		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_param( 'form_type', 'new_user' );
		$request->set_param( 'data', array() );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test submit CPT form
	 */
	public function test_submit_cpt_form() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_type', 'cpt_event' );
		$request->set_param(
			'data',
			array(
				'post_title'        => 'Test Event',
				'post_content'      => 'Test event description',
				'_event_start_date' => '2025-12-01',
				'instagram_user_id' => 'event_instagram',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		// Check post created.
		$post_id = $data['data']['post_id'];
		$post    = get_post( $post_id );

		$this->assertEquals( 'event_listing', $post->post_type );
		$this->assertEquals( 'Test Event', $post->post_title );
		$this->assertEquals( 'draft', $post->post_status ); // Should be draft for mod.

		// Check meta saved.
		$start_date = get_post_meta( $post_id, '_event_start_date', true );
		$this->assertEquals( '2025-12-01', $start_date );

		// Check Instagram ID saved.
		$instagram_id = get_post_meta( $post_id, '_apollo_instagram_id', true );
		$this->assertEquals( 'event_instagram', $instagram_id );
	}

	/**
	 * Test invalid form type
	 */
	public function test_invalid_form_type() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_type', 'invalid_type' );
		$request->set_param( 'data', array() );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test email validation
	 */
	public function test_email_field_validation() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_type', 'new_user' );
		$request->set_param(
			'data',
			array(
				'user_login' => 'testuser',
				'user_email' => 'invalid-email', // Invalid email.
				'user_pass'  => 'password123',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'user_email', $data['errors'] );
	}

	/**
	 * Test Instagram field validation
	 */
	public function test_instagram_field_validation() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/apollo/v1/forms/submit' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'form_type', 'new_user' );
		$request->set_param(
			'data',
			array(
				'user_login'        => 'testuser2',
				'user_email'        => 'test2@example.com',
				'user_pass'         => 'password123',
				'instagram_user_id' => 'invalid instagram!', // Invalid Instagram.
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'instagram_user_id', $data['errors'] );
	}
}
