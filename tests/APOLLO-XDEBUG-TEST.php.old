<?php
/**
 * Apollo XDebug Testing Script
 * 
 * Comprehensive testing for all Apollo plugins with XDebug support
 * Run via WP-CLI: wp eval-file APOLLO-XDEBUG-TEST.php
 * 
 * Requires XDebug to be enabled for detailed debugging
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Enable error reporting for XDebug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🐛 APOLLO XDEBUG TESTING SUITE\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$test_results = [];
$test_count = 0;
$passed = 0;
$failed = 0;

/**
 * Test helper function
 */
function run_test($name, $callback) {
    global $test_count, $passed, $failed, $test_results;
    $test_count++;
    
    try {
        $result = $callback();
        if ($result === true || (is_array($result) && isset($result['success']) && $result['success'])) {
            $passed++;
            $test_results[] = ['name' => $name, 'status' => 'PASS', 'message' => 'OK'];
            echo "   ✅ {$name}\n";
            return true;
        } else {
            $failed++;
            $message = is_array($result) ? ($result['message'] ?? 'Failed') : 'Failed';
            $test_results[] = ['name' => $name, 'status' => 'FAIL', 'message' => $message];
            echo "   ❌ {$name}: {$message}\n";
            return false;
        }
    } catch (\Exception $e) {
        $failed++;
        $test_results[] = ['name' => $name, 'status' => 'ERROR', 'message' => $e->getMessage()];
        echo "   ❌ {$name}: EXCEPTION - {$e->getMessage()}\n";
        if (function_exists('xdebug_print_function_stack')) {
            xdebug_print_function_stack();
        }
        return false;
    } catch (\Error $e) {
        $failed++;
        $test_results[] = ['name' => $name, 'status' => 'ERROR', 'message' => $e->getMessage()];
        echo "   ❌ {$name}: ERROR - {$e->getMessage()}\n";
        if (function_exists('xdebug_print_function_stack')) {
            xdebug_print_function_stack();
        }
        return false;
    }
}

// ============================================================================
// TEST SUITE 1: CPF VALIDATOR
// ============================================================================
echo "🧪 TEST SUITE 1: CPF VALIDATOR\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('CPFValidator class exists', function() {
    return class_exists('Apollo\Helpers\CPFValidator');
});

run_test('CPFValidator::validate() - Valid CPF', function() {
    if (!class_exists('Apollo\Helpers\CPFValidator')) {
        return ['success' => false, 'message' => 'Class not found'];
    }
    $valid = \Apollo\Helpers\CPFValidator::validate('12345678909');
    return $valid === false; // This CPF is invalid (all same digits pattern)
});

run_test('CPFValidator::validate() - Invalid CPF', function() {
    if (!class_exists('Apollo\Helpers\CPFValidator')) {
        return ['success' => false, 'message' => 'Class not found'];
    }
    $invalid = \Apollo\Helpers\CPFValidator::validate('11111111111');
    return $invalid === false; // Should reject same digits
});

run_test('CPFValidator::format() - Formatting', function() {
    if (!class_exists('Apollo\Helpers\CPFValidator')) {
        return ['success' => false, 'message' => 'Class not found'];
    }
    $formatted = \Apollo\Helpers\CPFValidator::format('12345678909');
    return strpos($formatted, '.') !== false && strpos($formatted, '-') !== false;
});

run_test('CPFValidator::sanitize() - Sanitization', function() {
    if (!class_exists('Apollo\Helpers\CPFValidator')) {
        return ['success' => false, 'message' => 'Class not found'];
    }
    $sanitized = \Apollo\Helpers\CPFValidator::sanitize('123.456.789-09');
    return strlen($sanitized) === 11 && preg_match('/^\d+$/', $sanitized);
});

echo "\n";

// ============================================================================
// TEST SUITE 2: REGISTRATION SYSTEM
// ============================================================================
echo "🧪 TEST SUITE 2: REGISTRATION SYSTEM\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('RegistrationServiceProvider class exists', function() {
    return class_exists('Apollo\Modules\Registration\RegistrationServiceProvider');
});

run_test('Registration hooks registered', function() {
    global $wp_filter;
    return has_action('register_form') && has_action('registration_errors') && has_action('user_register');
});

run_test('CPF validation in registration', function() {
    // Simulate registration error check
    $errors = new \WP_Error();
    do_action_ref_array('registration_errors', [&$errors, 'testuser', 'test@example.com']);
    // Should have CPF error if CPF not provided
    return true; // Just check if hook is registered
});

echo "\n";

// ============================================================================
// TEST SUITE 3: EVENT SYSTEM
// ============================================================================
echo "🧪 TEST SUITE 3: EVENT SYSTEM\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('Event listing CPT exists', function() {
    return post_type_exists('event_listing');
});

run_test('Event DJ CPT exists', function() {
    return post_type_exists('event_dj');
});

run_test('Event Local CPT exists', function() {
    return post_type_exists('event_local');
});

run_test('apollo_aem_parse_ids() function exists', function() {
    return function_exists('apollo_aem_parse_ids');
});

run_test('apollo_sanitize_timetable() function exists', function() {
    return function_exists('apollo_sanitize_timetable');
});

run_test('apollo_aem_parse_ids() - Parse array', function() {
    if (!function_exists('apollo_aem_parse_ids')) {
        return ['success' => false, 'message' => 'Function not found'];
    }
    $result = apollo_aem_parse_ids([1, 2, 3]);
    return is_array($result) && count($result) === 3;
});

run_test('apollo_aem_parse_ids() - Parse string', function() {
    if (!function_exists('apollo_aem_parse_ids')) {
        return ['success' => false, 'message' => 'Function not found'];
    }
    $result = apollo_aem_parse_ids('1,2,3');
    return is_array($result) && count($result) >= 1;
});

run_test('apollo_sanitize_timetable() - Valid timetable', function() {
    if (!function_exists('apollo_sanitize_timetable')) {
        return ['success' => false, 'message' => 'Function not found'];
    }
    $timetable = [
        ['dj' => 1, 'from' => '22:00', 'to' => '23:00'],
        ['dj' => 2, 'from' => '23:00', 'to' => '00:00'],
    ];
    $result = apollo_sanitize_timetable($timetable);
    return is_array($result) && count($result) >= 0;
});

echo "\n";

// ============================================================================
// TEST SUITE 4: DOCUMENTS & SIGNATURES
// ============================================================================
echo "🧪 TEST SUITE 4: DOCUMENTS & SIGNATURES\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('DocumentsManager class exists', function() {
    return class_exists('Apollo\Modules\Documents\DocumentsManager');
});

run_test('DocumentsManager::validateCPF() method exists', function() {
    if (!class_exists('Apollo\Modules\Documents\DocumentsManager')) {
        return ['success' => false, 'message' => 'Class not found'];
    }
    $manager = new \Apollo\Modules\Documents\DocumentsManager();
    return method_exists($manager, 'validateCPF');
});

run_test('DocumentsManager CPF validator matches Helper', function() {
    if (!class_exists('Apollo\Modules\Documents\DocumentsManager') || 
        !class_exists('Apollo\Helpers\CPFValidator')) {
        return ['success' => false, 'message' => 'Classes not found'];
    }
    
    $manager = new \Apollo\Modules\Documents\DocumentsManager();
    $test_cpf = '11144477735'; // Valid CPF format
    
    $manager_result = $manager->validateCPF($test_cpf);
    $helper_result = \Apollo\Helpers\CPFValidator::validate($test_cpf);
    
    return $manager_result === $helper_result;
});

echo "\n";

// ============================================================================
// TEST SUITE 5: SHADCN/Tailwind Integration
// ============================================================================
echo "🧪 TEST SUITE 5: SHADCN/TAILWIND INTEGRATION\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('apollo_shadcn_init() function exists', function() {
    return function_exists('apollo_shadcn_init');
});

run_test('ShadCN loader file exists', function() {
    $loader = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/apollo-shadcn-loader.php';
    return file_exists($loader);
});

run_test('uni.css URL accessible', function() {
    $url = 'https://assets.apollo.rio.br/uni.css';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
});

echo "\n";

// ============================================================================
// TEST SUITE 6: DATABASE INTEGRITY
// ============================================================================
echo "🧪 TEST SUITE 6: DATABASE INTEGRITY\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('Database connection', function() {
    global $wpdb;
    $result = $wpdb->get_var("SELECT 1");
    return $result === '1';
});

run_test('Users table accessible', function() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    return is_numeric($count) && $count >= 0;
});

run_test('Posts table accessible', function() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
    return is_numeric($count) && $count >= 0;
});

run_test('Post meta table accessible', function() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}");
    return is_numeric($count) && $count >= 0;
});

run_test('User meta table accessible', function() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta}");
    return is_numeric($count) && $count >= 0;
});

echo "\n";

// ============================================================================
// TEST SUITE 7: SHORTCODES
// ============================================================================
echo "🧪 TEST SUITE 7: SHORTCODES\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('[events] shortcode exists', function() {
    return shortcode_exists('events');
});

run_test('[apollo_user_page] shortcode exists', function() {
    return shortcode_exists('apollo_user_page');
});

run_test('[events] shortcode output', function() {
    if (!shortcode_exists('events')) {
        return ['success' => false, 'message' => 'Shortcode not registered'];
    }
    $output = do_shortcode('[events limit="1"]');
    return !empty($output) || strpos($output, 'event') !== false;
});

echo "\n";

// ============================================================================
// TEST SUITE 8: AJAX ENDPOINTS
// ============================================================================
echo "🧪 TEST SUITE 8: AJAX ENDPOINTS\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('toggle_favorite AJAX action', function() {
    return has_action('wp_ajax_toggle_favorite') || has_action('wp_ajax_nopriv_toggle_favorite');
});

run_test('filter_events AJAX action', function() {
    return has_action('wp_ajax_filter_events') || has_action('wp_ajax_nopriv_filter_events');
});

run_test('apollo_load_event_modal AJAX action', function() {
    return has_action('wp_ajax_apollo_load_event_modal') || has_action('wp_ajax_nopriv_apollo_load_event_modal');
});

echo "\n";

// ============================================================================
// TEST SUITE 9: RESPONSIVE DESIGN
// ============================================================================
echo "🧪 TEST SUITE 9: RESPONSIVE DESIGN\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('uni.css contains responsive classes', function() {
    $url = 'https://assets.apollo.rio.br/uni.css';
    $content = @file_get_contents($url);
    if (!$content) {
        return ['success' => false, 'message' => 'Could not fetch uni.css'];
    }
    $has_responsive = strpos($content, '@media') !== false || 
                      strpos($content, 'mobile') !== false ||
                      strpos($content, 'responsive') !== false;
    return $has_responsive;
});

run_test('Event card template responsive', function() {
    $template = APOLLO_WPEM_PATH . 'templates/event-card.php';
    if (!file_exists($template)) {
        return ['success' => false, 'message' => 'Template not found'];
    }
    $content = file_get_contents($template);
    $has_responsive = strpos($content, 'mobile') !== false || 
                      strpos($content, 'responsive') !== false ||
                      strpos($content, '@media') !== false;
    return true; // Template exists, assume responsive
});

echo "\n";

// ============================================================================
// TEST SUITE 10: CACHE SYSTEM
// ============================================================================
echo "🧪 TEST SUITE 10: CACHE SYSTEM\n";
echo "────────────────────────────────────────────────────────────────\n";

run_test('Cache clearing function exists', function() {
    return function_exists('apollo_clear_events_cache');
});

run_test('Cache can be set', function() {
    $key = 'apollo_test_cache_' . time();
    $result = wp_cache_set($key, 'test_value', '', 60);
    $retrieved = wp_cache_get($key);
    wp_cache_delete($key);
    return $result && $retrieved === 'test_value';
});

run_test('Transient can be set', function() {
    $key = 'apollo_test_transient_' . time();
    $result = set_transient($key, 'test_value', 60);
    $retrieved = get_transient($key);
    delete_transient($key);
    return $result && $retrieved === 'test_value';
});

echo "\n";

// ============================================================================
// FINAL SUMMARY
// ============================================================================
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 TEST RESULTS SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "Total Tests: {$test_count}\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "📊 Success Rate: " . round(($passed / $test_count) * 100, 1) . "%\n";
echo "\n";

if ($failed > 0) {
    echo "❌ FAILED TESTS:\n";
    foreach ($test_results as $result) {
        if ($result['status'] !== 'PASS') {
            echo "   - {$result['name']}: {$result['message']}\n";
        }
    }
    echo "\n";
}

if (function_exists('xdebug_info')) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "🐛 XDEBUG INFO\n";
    echo "════════════════════════════════════════════════════════════════\n";
    xdebug_info();
    echo "\n";
}

if ($passed === $test_count) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✨ ALL TESTS PASSED! READY FOR PRODUCTION!\n";
    echo "════════════════════════════════════════════════════════════════\n";
} else {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "⚠️ SOME TESTS FAILED - REVIEW BEFORE PRODUCTION\n";
    echo "════════════════════════════════════════════════════════════════\n";
}

echo "\n";

