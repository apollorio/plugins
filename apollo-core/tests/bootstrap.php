<?php

// phpcs:ignoreFile.
declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 *
 * @package Apollo_Core
 */

// Load WordPress test environment.
$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (! file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php\n";
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Load plugin
 */
function apollo_core_manually_load_plugin()
{
    require dirname(__DIR__) . '/apollo-core.php';
}

tests_add_filter('muplugins_loaded', 'apollo_core_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
