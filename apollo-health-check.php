<?php
/**
 * Apollo Health Check Script
 * Tests PHP, MySQL, Xdebug, and environment configuration
 * 
 * Run: php apollo-health-check.php
 * Or visit: http://localhost:10004/wp-content/plugins/apollo-health-check.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         APOLLO DEVELOPMENT ENVIRONMENT HEALTH CHECK         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$checks = [];
$overall_status = true;

// ============================================================
// 1. PHP VERSION CHECK
// ============================================================
echo "📋 PHP Configuration\n";
echo str_repeat("─", 60) . "\n";

$php_version = PHP_VERSION;
$expected_version = '8.2.27';
$php_ok = version_compare($php_version, '8.2.0', '>=');

echo sprintf("   Version: %s %s\n", $php_version, $php_ok ? '✅' : '⚠️');
if ($php_version !== $expected_version) {
    echo sprintf("   Expected: %s (minor difference OK)\n", $expected_version);
}
echo sprintf("   Memory Limit: %s\n", ini_get('memory_limit'));
echo sprintf("   Max Execution: %s seconds\n", ini_get('max_execution_time'));
echo sprintf("   Upload Max: %s\n", ini_get('upload_max_filesize'));
echo sprintf("   Post Max: %s\n", ini_get('post_max_size'));

$checks['PHP'] = $php_ok;
echo "\n";

// ============================================================
// 2. XDEBUG CHECK
// ============================================================
echo "🐛 Xdebug Configuration\n";
echo str_repeat("─", 60) . "\n";

$xdebug_loaded = extension_loaded('xdebug');
if ($xdebug_loaded) {
    $xdebug_version = phpversion('xdebug');
    $xdebug_mode = ini_get('xdebug.mode');
    $xdebug_port = ini_get('xdebug.client_port');
    $xdebug_host = ini_get('xdebug.client_host');
    $xdebug_start = ini_get('xdebug.start_with_request');
    
    echo sprintf("   Status: ACTIVE ✅\n");
    echo sprintf("   Version: %s\n", $xdebug_version);
    echo sprintf("   Mode: %s %s\n", $xdebug_mode, 
        (strpos($xdebug_mode, 'debug') !== false) ? '✅' : '⚠️ (debug not enabled)');
    echo sprintf("   Client: %s:%s\n", $xdebug_host, $xdebug_port);
    echo sprintf("   Start with request: %s %s\n", $xdebug_start, 
        ($xdebug_start === 'yes') ? '✅' : '⚠️');
    
    $xdebug_ok = (strpos($xdebug_mode, 'debug') !== false) && ($xdebug_port == 9003);
} else {
    echo "   Status: NOT LOADED ❌\n";
    echo "   Action: Install/enable Xdebug extension\n";
    $xdebug_ok = false;
}

$checks['Xdebug'] = $xdebug_ok;
echo "\n";

// ============================================================
// 3. MYSQL CONNECTION CHECK
// ============================================================
echo "🗄️  MySQL Database Connection\n";
echo str_repeat("─", 60) . "\n";

$db_config = [
    'host' => 'localhost',
    'port' => 10005,
    'database' => 'local',
    'username' => 'root',
    'password' => 'root'
];

try {
    $mysqli = new mysqli(
        $db_config['host'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database'],
        $db_config['port']
    );
    
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    
    $version_result = $mysqli->query("SELECT VERSION() as version");
    $version_row = $version_result->fetch_assoc();
    $mysql_version = $version_row['version'];
    
    // Test WordPress tables
    $tables_result = $mysqli->query("SHOW TABLES LIKE 'wp_%'");
    $wp_tables_count = $tables_result->num_rows;
    
    echo sprintf("   Status: CONNECTED ✅\n");
    echo sprintf("   Server: %s:%d\n", $db_config['host'], $db_config['port']);
    echo sprintf("   Database: %s\n", $db_config['database']);
    echo sprintf("   MySQL Version: %s\n", $mysql_version);
    echo sprintf("   WordPress Tables: %d found\n", $wp_tables_count);
    echo sprintf("   Character Set: %s\n", $mysqli->character_set_name());
    
    $mysqli->close();
    $mysql_ok = true;
    
} catch (Exception $e) {
    echo sprintf("   Status: FAILED ❌\n");
    echo sprintf("   Error: %s\n", $e->getMessage());
    echo sprintf("   Config: %s:%d\n", $db_config['host'], $db_config['port']);
    echo sprintf("   Database: %s\n", $db_config['database']);
    echo sprintf("   User: %s\n", $db_config['username']);
    $mysql_ok = false;
}

$checks['MySQL'] = $mysql_ok;
echo "\n";

// ============================================================
// 4. WORDPRESS CHECK
// ============================================================
echo "🌐 WordPress Environment\n";
echo str_repeat("─", 60) . "\n";

$wp_config_path = dirname(dirname(dirname(__DIR__))) . '/wp-config.php';
$wp_exists = file_exists($wp_config_path);

if ($wp_exists) {
    // Try to load WP without executing
    $wp_config_content = file_get_contents($wp_config_path);
    
    // Extract DB constants safely
    preg_match("/define\s*\(\s*'DB_NAME'\s*,\s*'([^']+)'/", $wp_config_content, $db_name_match);
    preg_match("/define\s*\(\s*'DB_HOST'\s*,\s*'([^']+)'/", $wp_config_content, $db_host_match);
    
    echo sprintf("   wp-config.php: FOUND ✅\n");
    echo sprintf("   Location: %s\n", $wp_config_path);
    
    if (!empty($db_name_match[1])) {
        echo sprintf("   DB_NAME: %s\n", $db_name_match[1]);
    }
    if (!empty($db_host_match[1])) {
        echo sprintf("   DB_HOST: %s\n", $db_host_match[1]);
    }
    
    $wp_ok = true;
} else {
    echo sprintf("   wp-config.php: NOT FOUND ❌\n");
    echo sprintf("   Expected: %s\n", $wp_config_path);
    $wp_ok = false;
}

$checks['WordPress'] = $wp_ok;
echo "\n";

// ============================================================
// 5. PHP EXTENSIONS CHECK
// ============================================================
echo "🔌 PHP Extensions\n";
echo str_repeat("─", 60) . "\n";

$required_extensions = [
    'mysqli' => 'MySQL support',
    'pdo_mysql' => 'PDO MySQL',
    'curl' => 'HTTP requests',
    'gd' => 'Image manipulation',
    'mbstring' => 'Multibyte strings',
    'xml' => 'XML parsing',
    'zip' => 'ZIP archives',
    'json' => 'JSON support',
    'openssl' => 'SSL/TLS',
    'imagick' => 'Advanced images (optional)'
];

$extensions_ok = true;
foreach ($required_extensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    $symbol = $loaded ? '✅' : ($ext === 'imagick' ? '⚠️' : '❌');
    echo sprintf("   %-15s %s %s\n", $ext, $symbol, $description);
    if (!$loaded && $ext !== 'imagick') {
        $extensions_ok = false;
    }
}

$checks['Extensions'] = $extensions_ok;
echo "\n";

// ============================================================
// 6. FILE PERMISSIONS CHECK
// ============================================================
echo "📁 File System Permissions\n";
echo str_repeat("─", 60) . "\n";

$plugin_dir = __DIR__;
$uploads_dir = dirname(dirname(dirname(__DIR__))) . '/wp-content/uploads';

$dirs_to_check = [
    'Plugin Directory' => $plugin_dir,
    'Uploads Directory' => $uploads_dir,
];

$perms_ok = true;
foreach ($dirs_to_check as $name => $dir) {
    if (file_exists($dir)) {
        $writable = is_writable($dir);
        echo sprintf("   %s: %s\n", $name, $writable ? 'WRITABLE ✅' : 'READ-ONLY ⚠️');
        if (!$writable) {
            $perms_ok = false;
        }
    } else {
        echo sprintf("   %s: NOT FOUND ⚠️\n", $name);
        $perms_ok = false;
    }
}

$checks['Permissions'] = $perms_ok;
echo "\n";

// ============================================================
// OVERALL STATUS
// ============================================================
echo str_repeat("═", 60) . "\n";
echo "📊 OVERALL STATUS\n";
echo str_repeat("═", 60) . "\n\n";

$passed = 0;
$total = count($checks);

foreach ($checks as $name => $status) {
    $symbol = $status ? '✅ PASS' : '❌ FAIL';
    echo sprintf("   %-20s %s\n", $name, $symbol);
    if ($status) $passed++;
}

echo "\n";
echo str_repeat("─", 60) . "\n";
echo sprintf("   Result: %d/%d checks passed\n", $passed, $total);

if ($passed === $total) {
    echo "\n   🎉 ENVIRONMENT READY! All systems operational.\n";
    echo "   🚀 You can start developing with confidence.\n";
    $exit_code = 0;
} else {
    echo "\n   ⚠️  ISSUES DETECTED: Review failures above.\n";
    echo "   🔧 Fix issues before starting development.\n";
    $exit_code = 1;
}

echo "\n";
echo str_repeat("═", 60) . "\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("═", 60) . "\n";

// CLI mode exit with status code
if (PHP_SAPI === 'cli') {
    exit($exit_code);
}
