<?php
/**
 * Run Smoke Tests
 *
 * Execute this file directly to run smoke tests:
 * php run-smoke-tests.php
 *
 * @package Apollo_Core
 */

// Load WordPress
// From apollo-core/tests, go up to wp-content/plugins, then up to wp-content
$wp_load = dirname( __DIR__, 3 ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	// Try alternative path (if running from different location)
	$wp_load = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
}
if ( ! file_exists( $wp_load ) ) {
	die( "Error: wp-load.php not found. Tried: {$wp_load}\n" );
}
require_once $wp_load;

// Load test class
require_once __DIR__ . '/test-smoke-security.php';

// Run tests
$results = Apollo_Smoke_Tests::run_all();

// Output results
echo "=== Apollo Smoke Tests ===\n\n";

foreach ( $results as $test_name => $result ) {
	$status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
	echo "{$status}: {$test_name}\n";
	echo "  {$result['message']}\n\n";
}

// Summary
$passed = count( array_filter( $results, fn( $r ) => $r['passed'] ) );
$total  = count( $results );

echo "\n=== Summary ===\n";
echo "Passed: {$passed}/{$total}\n";

if ( $passed === $total ) {
	echo "✅ All tests passed!\n";
	exit( 0 );
} else {
	echo "❌ Some tests failed. Please review the output above.\n";
	exit( 1 );
}
