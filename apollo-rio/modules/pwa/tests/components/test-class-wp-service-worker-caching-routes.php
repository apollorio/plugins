<?php

// phpcs:ignoreFile
/**
 * Tests for class WP_Service_Worker_Caching_Routes.
 *
 * @package PWA
 */

use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Tests for class WP_Service_Worker_Caching_Routes.
 *
 * @coversDefaultClass WP_Service_Worker_Caching_Routes
 */
class Test_WP_Service_Worker_Caching_Routes extends TestCase
{
    /**
     * Tested instance.
     *
     * @var WP_Service_Worker_Caching_Routes
     */
    private $instance;

    /**
     * Setup.
     *
     * @inheritdoc
     */
    public function set_up()
    {
        parent::set_up();

        $this->instance = new WP_Service_Worker_Caching_Routes();
    }

    /**
     * Test registering a route.
     *
     * @param string $route    Route.
     * @param array  $args     Route arguments.
     * @param array  $expected Expected registered route.
     *
     * @dataProvider data_register
     * @covers ::register()
     * @covers ::get_all()
     */
    public function test_register($route, $args, $expected = null)
    {
        $this->instance->register($route, $args);

        $routes = $this->instance->get_all();
        $this->assertNotEmpty($routes);

        if (! isset($expected)) {
            $expected = array_merge(compact('route'), $args);
        }

        $this->assertEqualSetsWithIndex(
            $expected,
            array_pop($routes)
        );
    }

    /**
     * Get valid routes.
     *
     * @return array List of arguments to pass to test_register().
     */
    public function data_register()
    {
        return [
            'js_or_css' => [
                '\.(?:js|css)$',
                [
                    'strategy'  => WP_Service_Worker_Caching_Routes::STRATEGY_STALE_WHILE_REVALIDATE,
                    'cacheName' => 'static-resources',
                ],
                [
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_STALE_WHILE_REVALIDATE,
                    'cache_name' => 'static-resources',
                    'route'      => '\.(?:js|css)$',
                ],
            ],
            'images' => [
                '\.(?:png|gif|jpg|jpeg|svg)$',
                [
                    'strategy'  => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_FIRST,
                    'cacheName' => 'images',
                ],
                [
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_FIRST,
                    'cache_name' => 'images',
                    'route'      => '\.(?:png|gif|jpg|jpeg|svg)$',
                ],
            ],
            'firebase' => [
                'https://hacker-news.firebaseio.com/v0/*',
                [
                    'strategy'              => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
                    'networkTimeoutSeconds' => 3,
                    'cacheName'             => 'stories',
                ],
                [
                    'strategy'                => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
                    'network_timeout_seconds' => 3,
                    'cache_name'              => 'stories',
                    'route'                   => 'https://hacker-news.firebaseio.com/v0/*',
                ],
            ],
            'googleapis' => [
                '.*(?:googleapis)\.com.*$',
                [
                    'strategy' => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_ONLY,
                ],
            ],
            'gstatic_1' => [
                '.*(?:gstatic)\.com.*$',
                [
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_ONLY,
                    'expiration' => [
                        'maxAgeSeconds' => 3,
                    ],
                    'broadcastUpdate'   => [],
                    'cacheableResponse' => [],
                ],
                [
                    'route'      => '.*(?:gstatic)\.com.*$',
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_ONLY,
                    'expiration' => [
                        'max_age_seconds' => 3,
                    ],
                    'broadcast_update'   => [],
                    'cacheable_response' => [],
                ],
            ],
            'empty_plugin_configs' => [
                '.*(?:gstatic)\.com.*$',
                [
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_ONLY,
                    'expiration' => [
                        'maxAgeSeconds' => 3,
                    ],
                    'broadcastUpdate'   => null,
                    'cacheableResponse' => false,
                ],
                [
                    'route'      => '.*(?:gstatic)\.com.*$',
                    'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_ONLY,
                    'expiration' => [
                        'max_age_seconds' => 3,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test registering with plugins key.
     *
     * @covers ::register()
     * @covers ::normalize_configuration()
     * @expectedIncorrectUsage WP_Service_Worker_Caching_Routes::register
     */
    public function test_register_obsolete_plugins_key()
    {
        $this->instance->register(
            '\.foo$',
            [
                'strategy' => 'cacheOnly',
                'plugins'  => [
                    'expiration' => [
                        'maxEntries'    => 2,
                        'maxAgeSeconds' => 3,
                    ],
                    'broadcastUpdate'   => [],
                    'cacheableResponse' => [],
                ],
            ]
        );

        $routes = $this->instance->get_all();
        $this->assertEqualSetsWithIndex(
            [
                'route'      => '\.foo$',
                'strategy'   => WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_ONLY,
                'expiration' => [
                    'max_entries'     => 2,
                    'max_age_seconds' => 3,
                ],
                'broadcast_update'   => [],
                'cacheable_response' => [],
            ],
            array_pop($routes)
        );
    }

    /**
     * Test registering with unexpected keys.
     *
     * @covers ::register()
     * @covers ::normalize_configuration()
     * @expectedIncorrectUsage WP_Service_Worker_Caching_Routes::register
     */
    public function test_register_unexpected_keys()
    {
        $this->instance->register(
            '\.foo$',
            [
                'strategy'                => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
                'cache_name'              => 'foo',
                'network_timeout_seconds' => 2,
                'foo_bar'                 => 'baz',
            ]
        );

        $routes = $this->instance->get_all();
        $this->assertEqualSetsWithIndex(
            [
                'route'                   => '\.foo$',
                'strategy'                => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
                'network_timeout_seconds' => 2,
                'cache_name'              => 'foo',
                'foo_bar'                 => 'baz',
            ],
            array_pop($routes)
        );
    }

    /**
     * Test registering a route with an invalid route.
     *
     * @covers ::register()
     * @expectedIncorrectUsage WP_Service_Worker_Caching_Routes::register
     */
    public function test_register_invalid_string_route()
    {
        $this->instance->register(3, WP_Service_Worker_Caching_Routes::STRATEGY_STALE_WHILE_REVALIDATE);
    }

    /**
     * Test registering a route with an invalid route.
     *
     * @covers ::register()
     * @expectedIncorrectUsage WP_Service_Worker_Caching_Routes::register
     */
    public function test_register_invalid_empty_route()
    {
        $this->instance->register(null, WP_Service_Worker_Caching_Routes::STRATEGY_STALE_WHILE_REVALIDATE);
    }

    /**
     * Test registering a route with an invalid strategy.
     *
     * @covers ::register()
     * @covers ::normalize_configuration()
     * @expectedIncorrectUsage WP_Service_Worker_Caching_Routes::register
     */
    public function test_register_invalid_strategy()
    {
        $this->instance->register('/\.(?:js|css)$/', 'invalid');
    }

    /**
     * Test registering a route without a strategy.
     *
     * @covers ::register()
     * @covers ::normalize_configuration()
     * @expectedIncorrectUsage WP_Service_Worker_Caching_Routes::register
     */
    public function test_register_missing_strategy()
    {
        $this->instance->register('/\.(?:js|css)$/', []);
    }

    /**
     * Test prepare_strategy_args_for_js_export.
     *
     * @covers ::prepare_strategy_args_for_js_export()
     */
    public function test_prepare_strategy_args_for_js_export()
    {
        $prepared = WP_Service_Worker_Caching_Routes::prepare_strategy_args_for_js_export(
            [
                'strategy'                => WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
                'network_timeout_seconds' => 2,
                'cache_name'              => 'bank-cash',
                'expiration'              => [
                    'max_entries'     => 4,
                    'max_age_seconds' => 20,
                ],
                'broadcast_update' => [],
            ]
        );

        $this->assertStringContainsString(WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST, $prepared);
        $this->assertStringContainsString('"strategy":"NetworkFirst"', $prepared);
        $this->assertStringContainsString('"networkTimeoutSeconds":2', $prepared);
        $this->assertStringContainsString('"cacheName":"bank-cash"', $prepared);
        $this->assertStringContainsString('{"maxEntries":4,"maxAgeSeconds":20}', $prepared);
        $this->assertStringContainsString('broadcastUpdate', $prepared);
        $this->assertStringContainsString('BroadcastUpdatePlugin', $prepared);
        $this->assertStringContainsString('expiration', $prepared);
        $this->assertStringContainsString('ExpirationPlugin', $prepared);
    }
}
