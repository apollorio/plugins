<?php

// phpcs:ignoreFile.
declare(strict_types=1);

/**
 * Activation Tests
 *
 * @package Apollo_Core
 */

/**
 * Activation test class
 *
 * NOTE: Tests updated to use standard WordPress roles.
 * Apollo now uses: administrator=apollo, editor=MOD, author=cult::rio,
 * contributor=cena::rio, subscriber=clubber
 */
class Apollo_Core_Activation_Test extends WP_UnitTestCase
{
    /**
     * Test role labels (Apollo uses standard WP roles with custom labels)
     */
    public function test_roles_exist()
    {
        // Trigger activation.
        Apollo_Core::activate();

        // Check standard WordPress roles exist (Apollo uses these with custom labels)
        $this->assertNotNull(get_role('administrator'), 'Administrator role (apollo) should exist');
        $this->assertNotNull(get_role('editor'), 'Editor role (MOD) should exist');
        $this->assertNotNull(get_role('author'), 'Author role (cult::rio) should exist');
        $this->assertNotNull(get_role('contributor'), 'Contributor role (cena::rio) should exist');
        $this->assertNotNull(get_role('subscriber'), 'Subscriber role (clubber) should exist');
    }

    /**
     * Test option creation
     */
    public function test_options_created()
    {
        // Trigger activation.
        Apollo_Core::activate();

        // Check apollo_mod_settings option.
        $settings = get_option('apollo_mod_settings');
        $this->assertIsArray($settings, 'Settings should be an array');
        $this->assertArrayHasKey('auto_approve_events', $settings, 'Settings should have auto_approve_events key');
    }

    /**
     * Test table creation
     */
    public function test_tables_created()
    {
        global $wpdb;

        // Trigger activation.
        Apollo_Core::activate();

        // Check apollo_mod_log table.
        $table_name = $wpdb->prefix . 'apollo_mod_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name;

        $this->assertEquals($table_name, $table_exists, 'apollo_mod_log table should exist');
    }

    /**
     * Test idempotency (multiple activations)
     */
    public function test_activation_idempotent()
    {
        // Activate multiple times.
        Apollo_Core::activate();
        Apollo_Core::activate();
        Apollo_Core::activate();

        // Should not error - standard WP roles always exist
        $this->assertNotNull(get_role('administrator'));
        $this->assertNotNull(get_role('contributor'));
        $this->assertNotNull(get_role('subscriber'));
    }

    /**
     * Test capability assignment to standard WordPress roles
     */
    public function test_capability_assignment()
    {
        // Trigger activation.
        Apollo_Core::activate();

        // Check contributor (cena::rio) capabilities.
        $role = get_role('contributor');
        $this->assertTrue($role->has_cap('apollo_submit_event'), 'Contributor (cena::rio) should have apollo_submit_event capability');
        $this->assertTrue($role->has_cap('apollo_create_draft_event'), 'Contributor (cena::rio) should have apollo_create_draft_event capability');

        // Check editor (MOD) capabilities.
        $role = get_role('editor');
        $this->assertTrue($role->has_cap('apollo_cena_moderate_events'), 'Editor (MOD) should have apollo_cena_moderate_events capability');
        $this->assertTrue($role->has_cap('moderate_events'), 'Editor (MOD) should have moderate_events capability');
    }
}
