<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Activation Tests
 *
 * @package Apollo_Core
 */

/**
 * Activation test class
 */
class Apollo_Core_Activation_Test extends WP_UnitTestCase {
	/**
	 * Test role creation
	 */
	public function test_roles_created() {
		// Trigger activation.
		Apollo_Core::activate();

		// Check apollo role.
		$this->assertNotNull( get_role( 'apollo' ), 'Apollo role should be created' );

		// Check cena-rio role.
		$this->assertNotNull( get_role( 'cena-rio' ), 'Cena-rio role should be created' );

		// Check dj role.
		$this->assertNotNull( get_role( 'dj' ), 'DJ role should be created' );
	}

	/**
	 * Test option creation
	 */
	public function test_options_created() {
		// Trigger activation.
		Apollo_Core::activate();

		// Check apollo_mod_settings option.
		$settings = get_option( 'apollo_mod_settings' );
		$this->assertIsArray( $settings, 'Settings should be an array' );
		$this->assertArrayHasKey( 'auto_approve_events', $settings, 'Settings should have auto_approve_events key' );
	}

	/**
	 * Test table creation
	 */
	public function test_tables_created() {
		global $wpdb;

		// Trigger activation.
		Apollo_Core::activate();

		// Check apollo_mod_log table.
		$table_name = $wpdb->prefix . 'apollo_mod_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		$this->assertEquals( $table_name, $table_exists, 'apollo_mod_log table should exist' );
	}

	/**
	 * Test idempotency (multiple activations)
	 */
	public function test_activation_idempotent() {
		// Activate multiple times.
		Apollo_Core::activate();
		Apollo_Core::activate();
		Apollo_Core::activate();

		// Should not error and should only create roles once.
		$this->assertNotNull( get_role( 'apollo' ) );
		$this->assertNotNull( get_role( 'cena-rio' ) );
		$this->assertNotNull( get_role( 'dj' ) );
	}

	/**
	 * Test capability assignment
	 */
	public function test_capability_assignment() {
		// Trigger activation.
		Apollo_Core::activate();

		// Check cena-rio capabilities.
		$role = get_role( 'cena-rio' );
		$this->assertTrue( $role->has_cap( 'apollo_access_cena_rio' ), 'Cena-rio should have apollo_access_cena_rio capability' );
		$this->assertTrue( $role->has_cap( 'apollo_create_event_plan' ), 'Cena-rio should have apollo_create_event_plan capability' );

		// Check dj capabilities.
		$role = get_role( 'dj' );
		$this->assertTrue( $role->has_cap( 'apollo_view_dj_stats' ), 'DJ should have apollo_view_dj_stats capability' );
	}
}
