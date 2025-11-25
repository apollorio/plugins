<?php
/**
 * Apollo Core - Membership System Tests
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

/**
 * Test membership functionality
 */
class Apollo_Membership_Test extends WP_UnitTestCase {
	/**
	 * Test default memberships option exists after activation
	 */
	public function test_default_memberships_option_exists() {
		// Simulate activation.
		apollo_init_memberships_option();

		$option = get_option( 'apollo_memberships' );
		$this->assertNotFalse( $option, 'apollo_memberships option should exist' );
		$this->assertIsArray( $option, 'apollo_memberships should be an array' );

		$version = get_option( 'apollo_memberships_version' );
		$this->assertNotFalse( $version, 'apollo_memberships_version should exist' );
		$this->assertEquals( '1.0.0', $version );
	}

	/**
	 * Test default membership types are returned
	 */
	public function test_get_default_memberships() {
		$defaults = apollo_get_default_memberships();

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'nao-verificado', $defaults );
		$this->assertArrayHasKey( 'apollo', $defaults );
		$this->assertArrayHasKey( 'dj', $defaults );

		// Validate structure.
		foreach ( $defaults as $slug => $data ) {
			$this->assertArrayHasKey( 'label', $data );
			$this->assertArrayHasKey( 'frontend_label', $data );
			$this->assertArrayHasKey( 'color', $data );
			$this->assertArrayHasKey( 'text_color', $data );
		}
	}

	/**
	 * Test new user receives nao-verificado membership
	 */
	public function test_new_user_gets_default_membership() {
		$user_id = $this->factory->user->create();

		// Manually trigger the hook (factory doesn't always fire hooks).
		apollo_assign_membership_on_registration( $user_id );

		$membership = apollo_get_user_membership( $user_id );
		$this->assertEquals( 'nao-verificado', $membership, 'New user should have nao-verificado membership' );
	}

	/**
	 * Test assigning default memberships to existing users
	 */
	public function test_assign_default_memberships_to_existing_users() {
		// Create users without membership.
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		// Run assignment.
		apollo_assign_default_memberships();

		// Check memberships.
		$membership1 = apollo_get_user_membership( $user1 );
		$membership2 = apollo_get_user_membership( $user2 );

		$this->assertEquals( 'nao-verificado', $membership1 );
		$this->assertEquals( 'nao-verificado', $membership2 );
	}

	/**
	 * Test setting user membership
	 */
	public function test_set_user_membership() {
		$user_id = $this->factory->user->create();

		// Set membership to apollo.
		$result = apollo_set_user_membership( $user_id, 'apollo', get_current_user_id() );
		$this->assertTrue( $result, 'Setting membership should succeed' );

		// Verify membership was set.
		$membership = apollo_get_user_membership( $user_id );
		$this->assertEquals( 'apollo', $membership );
	}

	/**
	 * Test setting invalid membership fails
	 */
	public function test_set_invalid_membership_fails() {
		$user_id = $this->factory->user->create();

		// Try to set invalid membership.
		$result = apollo_set_user_membership( $user_id, 'nonexistent-membership', get_current_user_id() );
		$this->assertFalse( $result, 'Setting invalid membership should fail' );

		// User should still have default.
		apollo_assign_membership_on_registration( $user_id );
		$membership = apollo_get_user_membership( $user_id );
		$this->assertEquals( 'nao-verificado', $membership );
	}

	/**
	 * Test REST endpoint permission for setting membership
	 */
	public function test_rest_set_membership_permission() {
		// Create regular user without edit_apollo_users capability.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$can_edit = current_user_can( 'edit_apollo_users' );
		$this->assertFalse( $can_edit, 'Regular user should not have edit_apollo_users capability' );

		// Create admin user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$can_edit = current_user_can( 'edit_apollo_users' );
		$this->assertTrue( $can_edit, 'Administrator should have edit_apollo_users capability' );
	}

	/**
	 * Test REST endpoint for getting memberships
	 */
	public function test_rest_get_memberships() {
		$request  = new WP_REST_Request( 'GET', '/apollo/v1/memberships' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'memberships', $data );
		$this->assertIsArray( $data['memberships'] );
		$this->assertArrayHasKey( 'nao-verificado', $data['memberships'] );
	}

	/**
	 * Test REST endpoint for setting user membership
	 */
	public function test_rest_set_user_membership() {
		// Create admin user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Create user to modify.
		$user_id = $this->factory->user->create();

		// Make REST request.
		$request = new WP_REST_Request( 'POST', '/apollo/v1/memberships/set' );
		$request->set_param( 'user_id', $user_id );
		$request->set_param( 'membership_slug', 'apollo' );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify membership was set.
		$membership = apollo_get_user_membership( $user_id );
		$this->assertEquals( 'apollo', $membership );
	}

	/**
	 * Test display membership badge returns HTML
	 */
	public function test_display_membership_badge() {
		$user_id = $this->factory->user->create();
		apollo_set_user_membership( $user_id, 'apollo', get_current_user_id() );

		$badge = apollo_display_membership_badge( $user_id );

		$this->assertNotEmpty( $badge );
		$this->assertStringContainsString( 'apollo-membership', $badge );
		$this->assertStringContainsString( 'Apollo', $badge );
	}

	/**
	 * Test display badge with Instagram ID
	 */
	public function test_display_membership_badge_with_instagram() {
		$user_id = $this->factory->user->create();
		apollo_set_user_membership( $user_id, 'apollo', get_current_user_id() );
		update_user_meta( $user_id, '_apollo_instagram_id', 'testuser' );

		$badge = apollo_display_membership_badge( $user_id, array( 'show_instagram' => true ) );

		$this->assertStringContainsString( '@testuser', $badge );
		$this->assertStringContainsString( 'instagram.com/testuser', $badge );
	}

	/**
	 * Test saving custom membership
	 */
	public function test_save_custom_membership() {
		$memberships = apollo_get_memberships();

		// Add custom membership.
		$memberships['vip'] = array(
			'label'          => 'VIP Member',
			'frontend_label' => 'VIP',
			'color'          => '#FFD700',
			'text_color'     => '#8B6B00',
		);

		$result = apollo_save_memberships( $memberships );
		$this->assertTrue( $result );

		// Verify it was saved.
		$saved = apollo_get_memberships();
		$this->assertArrayHasKey( 'vip', $saved );
		$this->assertEquals( 'VIP Member', $saved['vip']['label'] );
	}

	/**
	 * Test membership validation fails for invalid data
	 */
	public function test_save_memberships_validates_structure() {
		// Invalid color format.
		$invalid = array(
			'invalid' => array(
				'label'          => 'Invalid',
				'frontend_label' => 'Invalid',
				'color'          => 'red', // Invalid hex format.
				'text_color'     => '#000000',
			),
		);

		$result = apollo_save_memberships( $invalid );
		$this->assertFalse( $result, 'Saving invalid membership should fail' );
	}

	/**
	 * Test deleting membership reassigns users
	 */
	public function test_delete_membership_reassigns_users() {
		// Create custom membership.
		$memberships         = apollo_get_memberships();
		$memberships['test'] = array(
			'label'          => 'Test',
			'frontend_label' => 'Test',
			'color'          => '#FF0000',
			'text_color'     => '#000000',
		);
		apollo_save_memberships( $memberships );

		// Create user with test membership.
		$user_id = $this->factory->user->create();
		update_option( 'apollo_memberships', $memberships );
		apollo_set_user_membership( $user_id, 'test', get_current_user_id() );

		// Verify user has test membership.
		$this->assertEquals( 'test', apollo_get_user_membership( $user_id ) );

		// Delete membership.
		$result = apollo_delete_membership( 'test' );
		$this->assertTrue( $result );

		// Verify user was reassigned.
		$this->assertEquals( 'nao-verificado', apollo_get_user_membership( $user_id ) );

		// Verify membership no longer exists in saved data.
		$saved = get_option( 'apollo_memberships', array() );
		$this->assertArrayNotHasKey( 'test', $saved );
	}

	/**
	 * Test cannot delete nao-verificado
	 */
	public function test_cannot_delete_default_membership() {
		$result = apollo_delete_membership( 'nao-verificado' );
		$this->assertFalse( $result, 'Should not be able to delete nao-verificado' );
	}

	/**
	 * Test export/import memberships
	 */
	public function test_export_import_memberships() {
		// Add custom membership.
		$memberships                = apollo_get_memberships();
		$memberships['test-export'] = array(
			'label'          => 'Test Export',
			'frontend_label' => 'Test',
			'color'          => '#00FF00',
			'text_color'     => '#000000',
		);
		apollo_save_memberships( $memberships );

		// Export.
		$json = apollo_export_memberships_json();
		$this->assertNotEmpty( $json );

		$data = json_decode( $json, true );
		$this->assertArrayHasKey( 'memberships', $data );
		$this->assertArrayHasKey( 'test-export', $data['memberships'] );

		// Clear option.
		delete_option( 'apollo_memberships' );

		// Import.
		$result = apollo_import_memberships_json( $json );
		$this->assertNotInstanceOf( 'WP_Error', $result );

		// Verify imported.
		$imported = apollo_get_memberships();
		$this->assertArrayHasKey( 'test-export', $imported );
		$this->assertEquals( 'Test Export', $imported['test-export']['label'] );
	}

	/**
	 * Test membership change is logged
	 */
	public function test_membership_change_is_logged() {
		// Enable audit log.
		$settings = apollo_get_mod_settings();
		$settings['audit_log_enabled'] = true;
		apollo_update_mod_settings( $settings );

		$user_id  = $this->factory->user->create();
		$actor_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		// Set membership.
		apollo_set_user_membership( $user_id, 'apollo', $actor_id );

		// Check log.
		$logs = apollo_get_mod_log(
			array(
				'action'      => 'membership_changed',
				'target_type' => 'user',
				'target_id'   => $user_id,
				'limit'       => 1,
			)
		);

		$this->assertNotEmpty( $logs );
		$this->assertEquals( 'membership_changed', $logs[0]->action );
		$this->assertEquals( $actor_id, $logs[0]->actor_id );
		$this->assertEquals( $user_id, $logs[0]->target_id );

		$details = $logs[0]->details;
		$this->assertEquals( 'nao-verificado', $details['from'] );
		$this->assertEquals( 'apollo', $details['to'] );
	}
}


