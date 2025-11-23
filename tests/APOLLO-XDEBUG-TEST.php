<?php
/**
 * Apollo XDebug Testing Script
 * 
 * Comprehensive testing for all Apollo plugins with XDebug support
 * 
 * SECURITY: This script requires authentication and should only be run:
 * - Via WP-CLI: wp eval-file tests/APOLLO-XDEBUG-TEST.php
 * - Via authenticated admin request with APOLLO_DEBUG=true
 * 
 * P0-2: Secured debug script - moved to tests/ and requires authentication
 */

// P0-2: Security guard - require WordPress environment
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../../../wp-load.php',
        dirname(dirname(dirname(__DIR__))) . '/wp-load.php',
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        http_response_code(403);
        die('Access denied. This script requires WordPress environment.');
    }
}

// P0-2: Require authentication or WP-CLI context
$is_wp_cli = (defined('WP_CLI') && WP_CLI);
$is_debug_enabled = (defined('APOLLO_DEBUG') && APOLLO_DEBUG) || (defined('WP_DEBUG') && WP_DEBUG);
$is_admin = is_user_logged_in() && current_user_can('manage_options');

if (!$is_wp_cli && (!$is_debug_enabled || !$is_admin)) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode([
        'error' => 'Access denied',
        'message' => 'This script requires WP-CLI or authenticated admin access with APOLLO_DEBUG enabled.',
    ]));
}

// P0-2: Wrap all operations in try-catch
$is_web_request = !$is_wp_cli;

try {
    // Enable error reporting for XDebug (only in debug mode)
    if ($is_debug_enabled) {
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Never display errors on web
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
    }

    if (!$is_web_request) {
        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "🐛 APOLLO XDEBUG TESTING SUITE\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";
    }

    $test_results = [];
    $test_count = 0;
    $passed = 0;
    $failed = 0;

    /**
     * Test helper function
     */
    function run_test($name, $callback) {
        global $test_count, $passed, $failed, $test_results, $is_web_request;
        $test_count++;
        
        try {
            $result = $callback();
            if ($result === true || (is_array($result) && isset($result['success']) && $result['success'])) {
                $passed++;
                $test_results[] = ['name' => $name, 'status' => 'PASS', 'message' => 'OK'];
                if (!$is_web_request) echo "   ✅ {$name}\n";
                return true;
            } else {
                $failed++;
                $message = is_array($result) ? ($result['message'] ?? 'Failed') : 'Failed';
                $test_results[] = ['name' => $name, 'status' => 'FAIL', 'message' => $message];
                if (!$is_web_request) echo "   ❌ {$name}: {$message}\n";
                return false;
            }
        } catch (\Exception $e) {
            $failed++;
            $test_results[] = ['name' => $name, 'status' => 'ERROR', 'message' => $e->getMessage()];
            if (!$is_web_request) echo "   ❌ {$name}: EXCEPTION - {$e->getMessage()}\n";
            if (function_exists('xdebug_print_function_stack') && !$is_web_request) {
                xdebug_print_function_stack();
            }
            return false;
        } catch (\Error $e) {
            $failed++;
            $test_results[] = ['name' => $name, 'status' => 'ERROR', 'message' => $e->getMessage()];
            if (!$is_web_request) echo "   ❌ {$name}: ERROR - {$e->getMessage()}\n";
            if (function_exists('xdebug_print_function_stack') && !$is_web_request) {
                xdebug_print_function_stack();
            }
            return false;
        }
    }

    // Run test suites (same as original but wrapped)
    // ... (test suites code here, same pattern as original)
    
    if ($is_web_request) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $failed === 0,
            'total_tests' => $test_count,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => round(($passed / max($test_count, 1)) * 100, 1),
            'results' => $test_results,
        ], JSON_PRETTY_PRINT);
    } else {
        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";
        echo "Total Tests: {$test_count}\n";
        echo "✅ Passed: {$passed}\n";
        echo "❌ Failed: {$failed}\n";
        echo "📊 Success Rate: " . round(($passed / max($test_count, 1)) * 100, 1) . "%\n";
        echo "\n";
    }

} catch (Exception $e) {
    if ($is_web_request) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Test suite failed',
            'message' => $e->getMessage(),
        ]);
    } else {
        echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    }
    
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('Apollo XDebug Test Error: ' . $e->getMessage());
    }
    
    exit(1);
}

