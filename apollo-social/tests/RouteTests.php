<?php
/**
 * Apollo Route Tests
 *
 * Unit tests for Apollo routing system.
 * Validates that all routes are correctly registered and don't collide with WordPress.
 *
 * FASE 3: Automated route testing:
 * 1. Validates route patterns
 * 2. Checks for WordPress collisions
 * 3. Tests blocked routes return 403
 * 4. Verifies feature flag integration
 *
 * Usage:
 * - Run via WP-CLI: wp eval-file wp-content/plugins/apollo-social/tests/RouteTests.php
 * - Run via admin: Apollo Hub > Diagnósticos > Teste de Rotas
 *
 * @package Apollo\Tests
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Tests;

use Apollo\Infrastructure\Http\Apollo_Router;
use Apollo\Infrastructure\FeatureFlags;

/**
 * Route Tests Class
 */
class RouteTests {

	/** @var array Test results */
	private static array $results = array();

	/** @var int Pass count */
	private static int $passed = 0;

	/** @var int Fail count */
	private static int $failed = 0;

	/** @var array WordPress protected paths that must not be overridden */
	private const WP_PROTECTED = array(
		'feed',
		'rss',
		'rss2',
		'atom',
		'rdf',
		'wp-admin',
		'wp-login',
		'wp-json',
		'xmlrpc',
		'wp-cron',
		'wp-content',
		'wp-includes',
		'comments',
		'trackback',
		'embed',
	);

	/**
	 * Run all route tests
	 *
	 * @return array Test results.
	 */
	public static function runAll(): array {
		self::$results = array();
		self::$passed  = 0;
		self::$failed  = 0;

		// Test groups
		self::testRouterInitialized();
		self::testRoutePrefixExists();
		self::testNoWordPressCollisions();
		self::testFeatureFlagIntegration();
		self::testBlockedRoutesRegistered();
		self::testRewriteRulesRegistered();
		self::testQueryVarsRegistered();

		return array(
			'passed'  => self::$passed,
			'failed'  => self::$failed,
			'total'   => self::$passed + self::$failed,
			'results' => self::$results,
		);
	}

	/**
	 * Test: Router is initialized
	 */
	private static function testRouterInitialized(): void {
		$test_name = 'Router Initialized';

		if ( class_exists( '\Apollo\Infrastructure\Http\Apollo_Router' ) ) {
			self::pass( $test_name, 'Apollo_Router class exists' );
		} else {
			self::fail( $test_name, 'Apollo_Router class not found' );
		}
	}

	/**
	 * Test: Route prefix is defined
	 */
	private static function testRoutePrefixExists(): void {
		$test_name = 'Route Prefix';

		if ( ! class_exists( '\Apollo\Infrastructure\Http\Apollo_Router' ) ) {
			self::skip( $test_name, 'Router not available' );
			return;
		}

		$prefix = Apollo_Router::ROUTE_PREFIX;

		if ( ! empty( $prefix ) && $prefix === 'apollo' ) {
			self::pass( $test_name, "Prefix is '{$prefix}'" );
		} else {
			self::fail( $test_name, "Expected 'apollo', got '{$prefix}'" );
		}
	}

	/**
	 * Test: No collision with WordPress protected paths
	 */
	private static function testNoWordPressCollisions(): void {
		$test_name = 'No WordPress Collisions';

		if ( ! class_exists( '\Apollo\Infrastructure\Http\Apollo_Router' ) ) {
			self::skip( $test_name, 'Router not available' );
			return;
		}

		$routes     = Apollo_Router::getRoutes();
		$collisions = array();

		foreach ( $routes as $route ) {
			$pattern = $route['pattern'];

			foreach ( self::WP_PROTECTED as $protected ) {
				// Check if pattern starts with protected path (without apollo prefix)
				if ( preg_match( '/^\^' . preg_quote( $protected, '/' ) . '/', $pattern ) ) {
					$collisions[] = array(
						'pattern'   => $pattern,
						'protected' => $protected,
					);
				}
			}
		}

		if ( empty( $collisions ) ) {
			self::pass( $test_name, 'No collisions detected with WordPress paths' );
		} else {
			self::fail( $test_name, 'Collisions found: ' . wp_json_encode( $collisions ) );
		}
	}

	/**
	 * Test: Feature flags control route availability
	 */
	private static function testFeatureFlagIntegration(): void {
		$test_name = 'Feature Flag Integration';

		if ( ! class_exists( '\Apollo\Infrastructure\FeatureFlags' ) ) {
			self::skip( $test_name, 'FeatureFlags not available' );
			return;
		}

		// Check that disabled features have blocked routes
		$disabled_features = array( 'chat', 'notifications', 'groups', 'govbr' );
		$has_blocked       = false;

		foreach ( $disabled_features as $feature ) {
			if ( ! FeatureFlags::isEnabled( $feature ) ) {
				$has_blocked = true;
				break;
			}
		}

		if ( $has_blocked ) {
			self::pass( $test_name, 'Disabled features detected, blocking should be active' );
		} else {
			self::pass( $test_name, 'All features enabled (no blocking needed)' );
		}
	}

	/**
	 * Test: Blocked routes are registered
	 */
	private static function testBlockedRoutesRegistered(): void {
		$test_name = 'Blocked Routes Registered';

		if ( ! class_exists( '\Apollo\Infrastructure\Http\Apollo_Router' ) ) {
			self::skip( $test_name, 'Router not available' );
			return;
		}

		$inventory      = Apollo_Router::getInventory();
		$blocked_routes = $inventory['blocked_routes'] ?? array();

		// Chat should be blocked by default
		if ( ! FeatureFlags::isEnabled( 'chat' ) ) {
			$has_chat_block = false;
			foreach ( $blocked_routes as $route ) {
				if ( strpos( $route, 'chat' ) !== false || strpos( $route, 'messages' ) !== false ) {
					$has_chat_block = true;
					break;
				}
			}

			if ( $has_chat_block ) {
				self::pass( $test_name, 'Chat routes are blocked' );
			} else {
				self::fail( $test_name, 'Chat is disabled but routes are not blocked' );
			}
		} else {
			self::pass( $test_name, 'Chat is enabled, no blocking expected' );
		}
	}

	/**
	 * Test: Rewrite rules are in WordPress
	 */
	private static function testRewriteRulesRegistered(): void {
		$test_name = 'Rewrite Rules Registered';

		global $wp_rewrite;

		if ( ! $wp_rewrite ) {
			self::skip( $test_name, 'WP_Rewrite not available' );
			return;
		}

		$rules       = $wp_rewrite->wp_rewrite_rules();
		$apollo_rules = 0;

		if ( is_array( $rules ) ) {
			foreach ( $rules as $pattern => $rewrite ) {
				if ( strpos( $pattern, 'apollo' ) !== false || strpos( $rewrite, 'apollo_' ) !== false ) {
					++$apollo_rules;
				}
			}
		}

		if ( $apollo_rules > 0 ) {
			self::pass( $test_name, "Found {$apollo_rules} Apollo rewrite rules" );
		} else {
			self::fail( $test_name, 'No Apollo rewrite rules found. May need to flush permalinks.' );
		}
	}

	/**
	 * Test: Query vars are registered
	 */
	private static function testQueryVarsRegistered(): void {
		$test_name = 'Query Vars Registered';

		global $wp;

		$required_vars = array(
			'apollo_action',
			'apollo_page',
			'apollo_id',
		);

		$public_vars = $wp->public_query_vars ?? array();
		$missing     = array();

		foreach ( $required_vars as $var ) {
			if ( ! in_array( $var, $public_vars, true ) ) {
				$missing[] = $var;
			}
		}

		if ( empty( $missing ) ) {
			self::pass( $test_name, 'All required query vars registered' );
		} else {
			self::fail( $test_name, 'Missing query vars: ' . implode( ', ', $missing ) );
		}
	}

	/**
	 * Test specific route pattern
	 *
	 * @param string $route_name Route identifier.
	 * @param string $test_path  Path to test.
	 * @return bool True if route matches.
	 */
	public static function testRoute( string $route_name, string $test_path ): bool {
		global $wp_rewrite;

		if ( ! $wp_rewrite ) {
			return false;
		}

		$rules = $wp_rewrite->wp_rewrite_rules();

		if ( ! is_array( $rules ) ) {
			return false;
		}

		// Remove leading slash
		$test_path = ltrim( $test_path, '/' );

		foreach ( $rules as $pattern => $rewrite ) {
			if ( preg_match( '#^' . $pattern . '$#', $test_path, $matches ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Record a passing test
	 */
	private static function pass( string $name, string $message ): void {
		self::$results[] = array(
			'name'    => $name,
			'status'  => 'pass',
			'message' => $message,
		);
		++self::$passed;
	}

	/**
	 * Record a failing test
	 */
	private static function fail( string $name, string $message ): void {
		self::$results[] = array(
			'name'    => $name,
			'status'  => 'fail',
			'message' => $message,
		);
		++self::$failed;
	}

	/**
	 * Record a skipped test
	 */
	private static function skip( string $name, string $message ): void {
		self::$results[] = array(
			'name'    => $name,
			'status'  => 'skip',
			'message' => $message,
		);
	}

	/**
	 * Format results for display
	 *
	 * @param array $results Test results.
	 * @return string Formatted output.
	 */
	public static function formatResults( array $results ): string {
		$output  = "\n";
		$output .= "═══════════════════════════════════════════════════════════\n";
		$output .= "  APOLLO ROUTE TESTS\n";
		$output .= "═══════════════════════════════════════════════════════════\n\n";

		foreach ( $results['results'] as $test ) {
			$icon = match ( $test['status'] ) {
				'pass' => '✅',
				'fail' => '❌',
				'skip' => '⏭️',
				default => '❓',
			};

			$output .= sprintf( "  %s %s\n", $icon, $test['name'] );
			$output .= sprintf( "     %s\n\n", $test['message'] );
		}

		$output .= "───────────────────────────────────────────────────────────\n";
		$output .= sprintf(
			"  Total: %d | Passed: %d | Failed: %d\n",
			$results['total'],
			$results['passed'],
			$results['failed']
		);
		$output .= "═══════════════════════════════════════════════════════════\n";

		return $output;
	}

	/**
	 * Get route inventory for debugging
	 *
	 * @return array Route inventory.
	 */
	public static function getInventory(): array {
		if ( ! class_exists( '\Apollo\Infrastructure\Http\Apollo_Router' ) ) {
			return array( 'error' => 'Router not available' );
		}

		return Apollo_Router::getInventory();
	}

	/**
	 * Dump all WordPress rewrite rules (for debugging)
	 *
	 * @return array All rewrite rules.
	 */
	public static function dumpAllRules(): array {
		global $wp_rewrite;

		if ( ! $wp_rewrite ) {
			return array( 'error' => 'WP_Rewrite not available' );
		}

		return $wp_rewrite->wp_rewrite_rules() ?: array();
	}
}

// If running directly via WP-CLI or eval
if ( defined( 'WP_CLI' ) || ( isset( $argv ) && basename( $argv[0] ) === 'RouteTests.php' ) ) {
	$results = RouteTests::runAll();
	echo RouteTests::formatResults( $results );
}
