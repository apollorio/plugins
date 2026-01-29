<?php

// phpcs:ignoreFile.
declare(strict_types=1);

/**
 * Test REST API Rate Limiting
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

class Apollo_Rate_Limiting_Test extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Clear all rate limit transients.
        global $wpdb;
        // Use prepare for consistency (even though no user input).
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_apollo_rate_limit_%'
        ));
    }

    /**
     * Test basic rate limit check passes
     */
    public function test_rate_limit_allows_first_request()
    {
        $request = new WP_REST_Request('POST', '/apollo/v1/forms/submit');
        $request->set_param('form_type', 'new_user');

        $result = apollo_rest_rate_limit_check($request);

        $this->assertTrue($result, 'First request should be allowed');
    }

    /**
     * Test rate limit blocks after limit exceeded
     */
    public function test_rate_limit_blocks_excessive_requests()
    {
        $request = new WP_REST_Request('POST', '/apollo/v1/forms/submit');
        $request->set_param('form_type', 'new_user');

        // Make 11 requests (limit is 10).
        for ($i = 0; $i < 11; $i++) {
            $result = apollo_rest_rate_limit_check($request);
        }

        $this->assertWPError($result, 'Request over limit should be blocked');
        $this->assertEquals('rate_limit_exceeded', $result->get_error_code());
    }

    /**
     * Test different limits for different endpoints
     */
    public function test_rate_limit_different_endpoints()
    {
        // Quiz endpoint has limit of 5.
        $quiz_request = new WP_REST_Request('POST', '/apollo/v1tentantiva');

        // Make 6 requests.
        for ($i = 0; $i < 6; $i++) {
            $result = apollo_rest_rate_limit_check($quiz_request);
        }

        $this->assertWPError($result, 'Quiz endpoint should have lower limit');
    }

    /**
     * Test rate limit status function
     */
    public function test_get_rate_limit_status()
    {
        $request = new WP_REST_Request('POST', '/apollo/v1/forms/submit');

        // Make 3 requests.
        for ($i = 0; $i < 3; $i++) {
            apollo_rest_rate_limit_check($request);
        }

        $status = apollo_get_rate_limit_status('/apollo/v1/forms/submit');

        $this->assertEquals(3, $status['attempts'], 'Should track 3 attempts');
        $this->assertEquals(7, $status['remaining'], 'Should have 7 remaining (10 - 3)');
    }

    /**
     * Test clear rate limit function
     */
    public function test_clear_rate_limit_admin_only()
    {
        // Non-admin cannot clear.
        $user_id = $this->factory->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user($user_id);

        $result = apollo_clear_rate_limit('/apollo/v1/forms/submit', $user_id);

        $this->assertFalse($result, 'Non-admin should not be able to clear rate limit');
    }

    /**
     * Test clear rate limit as admin
     */
    public function test_clear_rate_limit_as_admin()
    {
        $admin_id = $this->factory->user->create([ 'role' => 'administrator' ]);
        wp_set_current_user($admin_id);

        // Create some rate limit data.
        $request = new WP_REST_Request('POST', '/apollo/v1/forms/submit');
        apollo_rest_rate_limit_check($request);

        // Clear it.
        $result = apollo_clear_rate_limit('/apollo/v1/forms/submit', $admin_id);

        $this->assertTrue($result, 'Admin should be able to clear rate limit');

        // Verify cleared.
        $status = apollo_get_rate_limit_status('/apollo/v1/forms/submit', $admin_id);
        $this->assertEquals(0, $status['attempts'], 'Attempts should be cleared');
    }

    /**
     * Test rate limit resets after 60 seconds
     */
    public function test_rate_limit_resets()
    {
        $request = new WP_REST_Request('POST', '/apollo/v1/forms/submit');

        // Make 5 requests.
        for ($i = 0; $i < 5; $i++) {
            apollo_rest_rate_limit_check($request);
        }

        $status_before = apollo_get_rate_limit_status('/apollo/v1/forms/submit');
        $this->assertEquals(5, $status_before['attempts']);

        // Simulate transient expiration (in real world this would be 60 seconds).
        delete_transient('apollo_rate_limit_' . md5('/apollo/v1/forms/submit_0_unknown'));

        $status_after = apollo_get_rate_limit_status('/apollo/v1/forms/submit');
        $this->assertEquals(0, $status_after['attempts'], 'Rate limit should reset');
    }
}
