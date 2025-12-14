<?php
/**
 * Smoke Tests: Security and Basic Functionality
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Smoke Tests
 */
class Apollo_Smoke_Tests {

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
			'audit_log_table'          => self::test_audit_log_table(),
			'rbac_capabilities'        => self::test_rbac_capabilities(),
			'webp_helper'              => self::test_webp_helper(),
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
			$file = WP_PLUGIN_DIR . "/{$plugin}/{$plugin}.php";
			if ( ! file_exists( $file ) ) {
				$failed[] = "File not found: {$file}";
				continue;
			}

			$content = file_get_contents( $file );
			if ( strpos( $content, "defined( 'ABSPATH' )" ) === false ) {
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
		$file = WP_PLUGIN_DIR . '/apollo-secure-upload/apollo-secure-upload.php';
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
		$tests = array(
			'hardening'       => array(
				'file' => WP_PLUGIN_DIR . '/apollo-hardening/apollo-hardening.php',
				'cap'  => 'manage_apollo_security',
			),
			'secure-upload'   => array(
				'file' => WP_PLUGIN_DIR . '/apollo-secure-upload/apollo-secure-upload.php',
				'cap'  => 'manage_apollo_uploads',
			),
			'webp-compressor' => array(
				'file' => WP_PLUGIN_DIR . '/apollo-webp-compressor/apollo-webp-compressor.php',
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
	 * Test: Audit log table exists
	 *
	 * @return array Test result.
	 */
	public static function test_audit_log_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_audit_log';

		// Check if table exists
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $table !== $exists ) {
			return array(
				'passed'  => false,
				'message' => 'Audit log table does not exist. Run apollo_create_db_tables() on activation.',
			);
		}

		// Test insert
		if ( class_exists( 'Apollo_Audit_Log' ) ) {
			$result = Apollo_Audit_Log::log_event(
				'test_event',
				array(
					'message'  => 'Smoke test',
					'severity' => 'info',
				)
			);

			return array(
				'passed'  => false !== $result,
				'message' => false !== $result ? 'Audit log table exists and can insert' : 'Audit log table exists but cannot insert',
			);
		}

		return array(
			'passed'  => false,
			'message' => 'Apollo_Audit_Log class not found',
		);
	}

	/**
	 * Test: RBAC capabilities are registered
	 *
	 * @return array Test result.
	 */
	public static function test_rbac_capabilities() {
		if ( ! class_exists( 'Apollo_RBAC' ) ) {
			return array(
				'passed'  => false,
				'message' => 'Apollo_RBAC class not found',
			);
		}

		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return array(
				'passed'  => false,
				'message' => 'Administrator role not found',
			);
		}

		$required_caps = array(
			'manage_apollo_security',
			'manage_apollo_uploads',
			'manage_apollo_compression',
		);

		$missing = array();
		foreach ( $required_caps as $cap ) {
			if ( ! $admin->has_cap( $cap ) ) {
				$missing[] = $cap;
			}
		}

		return array(
			'passed'  => empty( $missing ),
			'message' => empty( $missing ) ? 'All required capabilities are registered' : 'Missing capabilities: ' . implode( ', ', $missing ),
		);
	}

	/**
	 * Test: WebP helper doesn't depend on external library
	 *
	 * @return array Test result.
	 */
	public static function test_webp_helper() {
		$file = WP_PLUGIN_DIR . '/apollo-webp-compressor/includes/ConvertHelper.php';
		if ( ! file_exists( $file ) ) {
			return array(
				'passed'  => false,
				'message' => 'ConvertHelper.php not found',
			);
		}

		$content      = file_get_contents( $file );
		$has_external = strpos( $content, 'WebPConvert\\WebPConvert' ) !== false;

		return array(
			'passed'  => ! $has_external,
			'message' => $has_external ? 'ConvertHelper still depends on external library' : 'ConvertHelper uses only GD/Imagick',
		);
	}
}


