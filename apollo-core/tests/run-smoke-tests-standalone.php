<?php
/**
 * Run Smoke Tests (Standalone - No WordPress Required)
 *
 * Execute this file directly to run smoke tests without loading WordPress:
 * php run-smoke-tests-standalone.php
 *
 * @package Apollo_Core
 */

// Define ABSPATH to prevent direct access errors.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 3 ) . '/' );
}

// Define WP_PLUGIN_DIR (go up from apollo-core/tests to plugins directory).
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', dirname( __DIR__, 1 ) );
}

/**
 * Apollo Smoke Tests (Standalone)
 */
class Apollo_Smoke_Tests_Standalone {

	/**
	 * Run all smoke tests
	 *
	 * @return array Test results.
	 */
	public static function run_all() {
		$results = array(
			'direct_access_protection' => self::test_direct_access_protection(),
			'nonce_verification'       => self::test_nonce_verification(),
			'capability_checks'        => self::test_capability_checks(),
			'webp_helper'              => self::test_webp_helper(),
			'file_sanitization'        => self::test_file_sanitization(),
			'ajax_security'            => self::test_ajax_security(),
		);

		return $results;
	}

	/**
	 * Test: All plugin files have ABSPATH protection
	 *
	 * @return array Test result.
	 */
	public static function test_direct_access_protection() {
		$plugins = array( 'apollo-hardening', 'apollo-secure-upload', 'apollo-webp-compressor' );
		$failed  = array();

		foreach ( $plugins as $plugin ) {
			// Plugins are at the same level as apollo-core, not inside it.
			$file = dirname( WP_PLUGIN_DIR ) . "/{$plugin}/{$plugin}.php";
			if ( ! file_exists( $file ) ) {
				$failed[] = "File not found: {$file}";
				continue;
			}

			$content = file_get_contents( $file );
			if ( strpos( $content, "defined( 'ABSPATH' )" ) === false && strpos( $content, 'defined( "ABSPATH" )' ) === false ) {
				$failed[] = "{$plugin} missing ABSPATH check";
			}
		}

		return array(
			'passed'  => empty( $failed ),
			'message' => empty( $failed ) ? 'All plugins have ABSPATH protection' : 'Failed: ' . implode( ', ', $failed ),
		);
	}

	/**
	 * Test: Upload handler verifies nonce
	 *
	 * @return array Test result.
	 */
	public static function test_nonce_verification() {
		$file = dirname( WP_PLUGIN_DIR ) . '/apollo-secure-upload/apollo-secure-upload.php';
		if ( ! file_exists( $file ) ) {
			return array(
				'passed'  => false,
				'message' => 'Upload handler file not found',
			);
		}

		$content   = file_get_contents( $file );
		$has_nonce = strpos( $content, 'wp_verify_nonce' ) !== false;

		return array(
			'passed'  => $has_nonce,
			'message' => $has_nonce ? 'Upload handler verifies nonce' : 'Upload handler missing nonce check',
		);
	}

	/**
	 * Test: Admin pages check capabilities
	 *
	 * @return array Test result.
	 */
	public static function test_capability_checks() {
		$plugins_dir = dirname( WP_PLUGIN_DIR );
		$tests       = array(
			'hardening'       => array(
				'file' => $plugins_dir . '/apollo-hardening/apollo-hardening.php',
				'cap'  => 'manage_apollo_security',
			),
			'secure-upload'   => array(
				'file' => $plugins_dir . '/apollo-secure-upload/apollo-secure-upload.php',
				'cap'  => 'manage_apollo_uploads',
			),
			'webp-compressor' => array(
				'file' => $plugins_dir . '/apollo-webp-compressor/apollo-webp-compressor.php',
				'cap'  => 'manage_apollo_compression',
			),
		);

		$failed = array();

		foreach ( $tests as $plugin => $test ) {
			if ( ! file_exists( $test['file'] ) ) {
				$failed[] = "{$plugin}: file not found";
				continue;
			}

			$content = file_get_contents( $test['file'] );
			if ( strpos( $content, $test['cap'] ) === false ) {
				$failed[] = "{$plugin}: missing capability check for {$test['cap']}";
			}
		}

		return array(
			'passed'  => empty( $failed ),
			'message' => empty( $failed ) ? 'All admin pages check capabilities' : 'Failed: ' . implode( ', ', $failed ),
		);
	}

	/**
	 * Test: WebP helper doesn't depend on external library
	 *
	 * @return array Test result.
	 */
	public static function test_webp_helper() {
		$file = dirname( WP_PLUGIN_DIR ) . '/apollo-webp-compressor/includes/ConvertHelper.php';
		if ( ! file_exists( $file ) ) {
			return array(
				'passed'  => false,
				'message' => 'ConvertHelper.php not found',
			);
		}

		$content      = file_get_contents( $file );
		$has_external = strpos( $content, 'WebPConvert\\WebPConvert' ) !== false || strpos( $content, 'WebPConvert\WebPConvert' ) !== false;
		$has_gd       = strpos( $content, 'imagewebp' ) !== false || strpos( $content, 'imagecreatefrom' ) !== false;
		$has_imagick  = strpos( $content, 'Imagick' ) !== false;

		if ( $has_external ) {
			return array(
				'passed'  => false,
				'message' => 'ConvertHelper still depends on external library',
			);
		}

		if ( ! $has_gd && ! $has_imagick ) {
			return array(
				'passed'  => false,
				'message' => 'ConvertHelper missing GD or Imagick implementation',
			);
		}

		return array(
			'passed'  => true,
			'message' => 'ConvertHelper uses only GD/Imagick (no external dependencies)',
		);
	}

	/**
	 * Test: File sanitization is correct
	 *
	 * @return array Test result.
	 */
	public static function test_file_sanitization() {
		$file = dirname( WP_PLUGIN_DIR ) . '/apollo-secure-upload/apollo-secure-upload.php';
		if ( ! file_exists( $file ) ) {
			return array(
				'passed'  => false,
				'message' => 'Upload handler file not found',
			);
		}

		$content = file_get_contents( $file );

		// Check that tmp_name is NOT sanitized with sanitize_text_field.
		$has_bad_sanitization = strpos( $content, 'sanitize_text_field( $tmp_name )' ) !== false ||
								strpos( $content, 'sanitize_text_field( $tmp_name )' ) !== false;

		// Check that name IS sanitized.
		$has_name_sanitization = strpos( $content, 'sanitize_file_name' ) !== false;

		if ( $has_bad_sanitization ) {
			return array(
				'passed'  => false,
				'message' => 'tmp_name is incorrectly sanitized (should not use sanitize_text_field)',
			);
		}

		if ( ! $has_name_sanitization ) {
			return array(
				'passed'  => false,
				'message' => 'File name is not sanitized',
			);
		}

		return array(
			'passed'  => true,
			'message' => 'File sanitization is correct (tmp_name not sanitized, name sanitized)',
		);
	}

	/**
	 * Test: AJAX security (no public access)
	 *
	 * @return array Test result.
	 */
	public static function test_ajax_security() {
		$file = dirname( WP_PLUGIN_DIR ) . '/apollo-secure-upload/apollo-secure-upload.php';
		if ( ! file_exists( $file ) ) {
			return array(
				'passed'  => false,
				'message' => 'Upload handler file not found',
			);
		}

		$content = file_get_contents( $file );

		// Check that wp_ajax_nopriv is NOT present.
		$has_nopriv = strpos( $content, 'wp_ajax_nopriv_apollo_secure_upload' ) !== false;

		if ( $has_nopriv ) {
			return array(
				'passed'  => false,
				'message' => 'Public AJAX access is enabled (security risk)',
			);
		}

		// Check that wp_ajax (authenticated) IS present.
		$has_ajax = strpos( $content, 'wp_ajax_apollo_secure_upload' ) !== false;

		if ( ! $has_ajax ) {
			return array(
				'passed'  => false,
				'message' => 'Authenticated AJAX handler not found',
			);
		}

		return array(
			'passed'  => true,
			'message' => 'AJAX security is correct (only authenticated users)',
		);
	}
}

// Run tests.
$results = Apollo_Smoke_Tests_Standalone::run_all();

// Output results.
echo "=== Apollo Smoke Tests (Standalone) ===\n\n";

foreach ( $results as $test_name => $result ) {
	$status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
	echo "{$status}: {$test_name}\n";
	echo "  {$result['message']}\n\n";
}

// Summary.
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
