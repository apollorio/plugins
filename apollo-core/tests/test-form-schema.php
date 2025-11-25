<?php
/**
 * Apollo Core - Form Schema Tests
 *
 * Tests for form schema management
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/tests/test-form-schema.php
 */

class Test_Apollo_Form_Schema extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		// Delete existing schemas.
		delete_option( 'apollo_form_schemas' );
		delete_option( 'apollo_form_schema_version' );
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test getting default schema
	 */
	public function test_get_default_schema() {
		$schema = apollo_get_default_form_schema( 'new_user' );

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		// Check required fields exist.
		$field_keys = array_column( $schema, 'key' );
		$this->assertContains( 'user_login', $field_keys );
		$this->assertContains( 'user_email', $field_keys );
		$this->assertContains( 'instagram_user_id', $field_keys );
	}

	/**
	 * Test saving and retrieving schema
	 */
	public function test_save_and_get_schema() {
		$test_schema = array(
			array(
				'key'        => 'test_field',
				'label'      => 'Test Field',
				'type'       => 'text',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 1,
			),
		);

		// Save schema.
		$result = apollo_save_form_schema( 'new_user', $test_schema );
		$this->assertTrue( $result );

		// Retrieve schema.
		$retrieved = apollo_get_form_schema( 'new_user' );
		$this->assertEquals( $test_schema, $retrieved );
	}

	/**
	 * Test schema validation
	 */
	public function test_schema_validation() {
		// Valid schema.
		$valid_schema = array(
			array(
				'key'        => 'test',
				'label'      => 'Test',
				'type'       => 'text',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 1,
			),
		);
		$this->assertTrue( apollo_validate_form_schema( $valid_schema ) );

		// Invalid schema - missing key.
		$invalid_schema = array(
			array(
				'label'    => 'Test',
				'type'     => 'text',
				'required' => false,
			),
		);
		$this->assertFalse( apollo_validate_form_schema( $invalid_schema ) );

		// Invalid schema - invalid type.
		$invalid_type_schema = array(
			array(
				'key'        => 'test',
				'label'      => 'Test',
				'type'       => 'invalid_type',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 1,
			),
		);
		$this->assertFalse( apollo_validate_form_schema( $invalid_type_schema ) );
	}

	/**
	 * Test field value validation
	 */
	public function test_field_value_validation() {
		// Required field validation.
		$required_field = array(
			'key'        => 'test',
			'label'      => 'Test Field',
			'type'       => 'text',
			'required'   => true,
			'visible'    => true,
			'default'    => '',
			'validation' => '',
			'order'      => 1,
		);

		$result = apollo_validate_field_value( '', $required_field );
		$this->assertInstanceOf( 'WP_Error', $result );

		$result = apollo_validate_field_value( 'value', $required_field );
		$this->assertTrue( $result );

		// Email validation.
		$email_field = array(
			'key'        => 'email',
			'label'      => 'Email',
			'type'       => 'email',
			'required'   => true,
			'visible'    => true,
			'default'    => '',
			'validation' => '',
			'order'      => 1,
		);

		$result = apollo_validate_field_value( 'invalid', $email_field );
		$this->assertInstanceOf( 'WP_Error', $result );

		$result = apollo_validate_field_value( 'test@example.com', $email_field );
		$this->assertTrue( $result );

		// Instagram validation.
		$instagram_field = array(
			'key'        => 'instagram',
			'label'      => 'Instagram',
			'type'       => 'instagram',
			'required'   => false,
			'visible'    => true,
			'default'    => '',
			'validation' => '/^[A-Za-z0-9_]{1,30}$/',
			'order'      => 1,
		);

		$result = apollo_validate_field_value( 'valid_username', $instagram_field );
		$this->assertTrue( $result );

		$result = apollo_validate_field_value( 'invalid username!', $instagram_field );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test Instagram ID uniqueness check
	 */
	public function test_instagram_id_uniqueness() {
		// Create test user with Instagram ID.
		$user_id_1 = $this->factory->user->create();
		update_user_meta( $user_id_1, '_apollo_instagram_id', 'testuser' );

		// Check uniqueness.
		$this->assertFalse( apollo_is_instagram_id_unique( 'testuser' ) );
		$this->assertTrue( apollo_is_instagram_id_unique( 'testuser', $user_id_1 ) ); // Exclude self.
		$this->assertTrue( apollo_is_instagram_id_unique( 'different_user' ) );
	}

	/**
	 * Test schema initialization
	 */
	public function test_schema_initialization() {
		// Initialize schemas.
		apollo_init_form_schemas();

		// Check option created.
		$schemas = get_option( 'apollo_form_schemas' );
		$this->assertIsArray( $schemas );
		$this->assertArrayHasKey( 'new_user', $schemas );
		$this->assertArrayHasKey( 'cpt_event', $schemas );

		// Check version.
		$version = get_option( 'apollo_form_schema_version' );
		$this->assertEquals( '1.0.0', $version );
	}
}

