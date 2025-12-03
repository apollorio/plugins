<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Core - Instagram Registration Tests
 *
 * Tests for Instagram ID on user registration
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/tests/test-registration-instagram.php
 */

class Test_Apollo_Registration_Instagram extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test Instagram ID saved on user registration
	 */
	public function test_instagram_saved_on_registration() {
		// Simulate POST data.
		$_POST['instagram_user_id'] = 'testuser123';

		// Create user.
		$user_id = wp_create_user( 'testuser', 'password123', 'test@example.com' );

		// Trigger registration hook.
		do_action( 'user_register', $user_id );

		// Check Instagram ID saved.
		$instagram_id = get_user_meta( $user_id, '_apollo_instagram_id', true );
		$this->assertEquals( 'testuser123', $instagram_id );

		// Cleanup.
		unset( $_POST['instagram_user_id'] );
	}

	/**
	 * Test invalid Instagram ID not saved
	 */
	public function test_invalid_instagram_not_saved() {
		// Simulate POST data with invalid Instagram ID.
		$_POST['instagram_user_id'] = 'invalid username!';

		// Create user.
		$user_id = wp_create_user( 'testuser2', 'password123', 'test2@example.com' );

		// Trigger registration hook.
		do_action( 'user_register', $user_id );

		// Check Instagram ID NOT saved.
		$instagram_id = get_user_meta( $user_id, '_apollo_instagram_id', true );
		$this->assertEmpty( $instagram_id );

		// Cleanup.
		unset( $_POST['instagram_user_id'] );
	}

	/**
	 * Test Instagram ID updated on profile update
	 */
	public function test_instagram_updated_on_profile_update() {
		// Create user.
		$user_id = $this->factory->user->create();

		// Simulate POST data.
		$_POST['instagram_user_id'] = 'updated_username';

		// Trigger profile update hook.
		do_action( 'profile_update', $user_id );

		// Check Instagram ID updated.
		$instagram_id = get_user_meta( $user_id, '_apollo_instagram_id', true );
		$this->assertEquals( 'updated_username', $instagram_id );

		// Cleanup.
		unset( $_POST['instagram_user_id'] );
	}

	/**
	 * Test duplicate Instagram ID rejected
	 */
	public function test_duplicate_instagram_rejected() {
		// Create first user with Instagram ID.
		$user_id_1 = $this->factory->user->create();
		update_user_meta( $user_id_1, '_apollo_instagram_id', 'unique_user' );

		// Try to register second user with same Instagram ID.
		$_POST['instagram_user_id'] = 'unique_user';

		$user_id_2 = wp_create_user( 'testuser3', 'password123', 'test3@example.com' );
		do_action( 'user_register', $user_id_2 );

		// Check Instagram ID NOT saved for second user.
		$instagram_id_2 = get_user_meta( $user_id_2, '_apollo_instagram_id', true );
		$this->assertEmpty( $instagram_id_2 );

		// Cleanup.
		unset( $_POST['instagram_user_id'] );
	}

	/**
	 * Test Instagram display function
	 */
	public function test_instagram_display() {
		// Create user with Instagram ID.
		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_apollo_instagram_id', 'displayuser' );

		// Get display output.
		$output = apollo_display_user_instagram( $user_id );

		// Check output contains link and username.
		$this->assertStringContainsString( '@displayuser', $output );
		$this->assertStringContainsString( 'https://instagram.com/displayuser', $output );
		$this->assertStringContainsString( 'class="apollo-instagram-link"', $output );
	}

	/**
	 * Test empty Instagram display
	 */
	public function test_empty_instagram_display() {
		// Create user without Instagram ID.
		$user_id = $this->factory->user->create();

		// Get display output.
		$output = apollo_display_user_instagram( $user_id );

		// Check output is empty.
		$this->assertEmpty( $output );
	}

	/**
	 * Test Instagram ID format validation
	 */
	public function test_instagram_format_validation() {
		$valid_usernames   = array( 'user123', 'test_user', 'User_Name_123', 'a' );
		$invalid_usernames = array( 'user name', 'user!', 'user@', 'a_very_long_username_that_exceeds_thirty_chars' );

		foreach ( $valid_usernames as $username ) {
			$this->assertMatchesRegularExpression( '/^[A-Za-z0-9_]{1,30}$/', $username );
		}

		foreach ( $invalid_usernames as $username ) {
			$this->assertDoesNotMatchRegularExpression( '/^[A-Za-z0-9_]{1,30}$/', $username );
		}
	}
}
