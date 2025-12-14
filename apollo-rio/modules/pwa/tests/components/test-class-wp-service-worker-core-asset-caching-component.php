<?php

// phpcs:ignoreFile
/**
 * Tests for class WP_Service_Worker_Core_Asset_Caching_Component.
 *
 * @package PWA
 */

use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Tests for class WP_Service_Worker_Core_Asset_Caching_Component.
 *
 * @coversDefaultClass WP_Service_Worker_Core_Asset_Caching_Component
 */
class Test_WP_Service_Worker_Core_Asset_Caching_Component extends TestCase
{
    /**
     * Get data for test_serve.
     *
     * @return array[]
     */
    public function get_test_serve_data()
    {
        $default_route = '^' . preg_quote(trailingslashit(includes_url()), '/') . '.*';

        return [
            'no_filter' => [
                null,
                [
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
                    'cache_name' => 'core-assets',
                    'expiration' => [
                        'max_entries' => 14,
                    ],
                    'route' => $default_route,
                ],
            ],

            'disabling_filter' => [
                '__return_empty_array',
                null,
            ],

            'filtering_out_strategy' => [
                function ($args) {
                    unset($args['strategy']);

                    return $args;
                },
                null,
            ],

            'filtering_out_route' => [
                function ($args) {
                    unset($args['route']);

                    return $args;
                },
                null,
            ],

            'cache_first_strategy_and_plugin_changes' => [
                function ($args) {
                    $args['strategy']                      = WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_FIRST;
                    $args['expiration']['max_age_seconds'] = MONTH_IN_SECONDS;
                    $args['broadcast_update']              = [];

                    return $args;
                },
                [
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_FIRST,
                    'cache_name' => 'core-assets',
                    'expiration' => [
                        'max_entries'     => 14,
                        'max_age_seconds' => MONTH_IN_SECONDS,
                    ],
                    'broadcast_update' => [],
                    'route'            => $default_route,
                ],
            ],
        ];
    }

    /**
     * Test registering a route.
     *
     * @dataProvider get_test_serve_data
     *
     * @param callable|null $filter_callback Filter callback.
     * @param array|null    $expected_item   Expected item.
     *
     * @covers ::serve()
     */
    public function test_serve($filter_callback, $expected_item)
    {
        if ($filter_callback) {
            add_filter('wp_service_worker_core_asset_caching', $filter_callback);
        }

        $component = new WP_Service_Worker_Core_Asset_Caching_Component();

        $scripts = new WP_Service_Worker_Scripts(
            new WP_Service_Worker_Caching_Routes(),
            new WP_Service_Worker_Precaching_Routes(),
            [
                'core_asset_caching' => $component,
            ]
        );

        $this->assertEmpty($scripts->caching_routes()->get_all());

        $component->serve($scripts);

        $all_scripts = $scripts->caching_routes()->get_all();

        if (empty($expected_item)) {
            $this->assertCount(0, $all_scripts);
        } else {
            $this->assertCount(1, $all_scripts);
            $entry = current($all_scripts);
            $this->assertEquals($entry, $expected_item);
        }
    }

    /**
     * Test get_priority.
     *
     * @covers ::get_priority()
     */
    public function test_get_priority()
    {
        $instance = new WP_Service_Worker_Core_Asset_Caching_Component();
        $this->assertSame(10, $instance->get_priority());
    }
}
