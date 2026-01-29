<?php
/**
 * Temporary fix to create apollo_online_users table
 *
 * Access this file via: http://your-site.local/wp-content/plugins/apollo-social/fix-online-users-table.php
 * After running, DELETE THIS FILE for security.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check - only admins can run this
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an administrator.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'apollo_online_users';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

echo '<h1>Apollo Online Users Table Fix</h1>';

if ($table_exists == $table_name) {
    echo '<p style="color: green;">✅ Table <strong>' . esc_html($table_name) . '</strong> already exists!</p>';

    // Show table structure
    $columns = $wpdb->get_results("SHOW FULL COLUMNS FROM $table_name");
    echo '<h2>Current Table Structure:</h2>';
    echo '<table border="1" cellpadding="5"><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
    foreach ($columns as $col) {
        echo '<tr>';
        echo '<td>' . esc_html($col->Field) . '</td>';
        echo '<td>' . esc_html($col->Type) . '</td>';
        echo '<td>' . esc_html($col->Null) . '</td>';
        echo '<td>' . esc_html($col->Key) . '</td>';
        echo '<td>' . esc_html($col->Default ?? 'NULL') . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Show row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p>Total rows: ' . esc_html($count) . '</p>';

} else {
    echo '<p style="color: orange;">⚠️ Table <strong>' . esc_html($table_name) . '</strong> does not exist. Creating now...</p>';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        user_id bigint(20) unsigned NOT NULL,
        last_activity datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        current_page varchar(500) DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        PRIMARY KEY (user_id),
        KEY last_activity_idx (last_activity)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check if created
    $table_exists_now = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if ($table_exists_now == $table_name) {
        echo '<p style="color: green; font-weight: bold;">✅ SUCCESS! Table created successfully!</p>';
        echo '<p>Table structure:</p>';
        $columns = $wpdb->get_results("SHOW FULL COLUMNS FROM $table_name");
        echo '<table border="1" cellpadding="5"><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td>' . esc_html($col->Field) . '</td>';
            echo '<td>' . esc_html($col->Type) . '</td>';
            echo '<td>' . esc_html($col->Null) . '</td>';
            echo '<td>' . esc_html($col->Key) . '</td>';
            echo '<td>' . esc_html($col->Default ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p style="color: red;">❌ ERROR: Table creation failed!</p>';
        echo '<p>Last database error: ' . esc_html($wpdb->last_error) . '</p>';
    }
}

echo '<hr>';
echo '<h2>Next Steps:</h2>';
echo '<ol>';
echo '<li>If the table was created successfully, <strong style="color: red;">DELETE THIS FILE</strong> for security: <code>wp-content/plugins/apollo-social/fix-online-users-table.php</code></li>';
echo '<li>The root cause was fixed in <code>SocialSchema.php</code> - all create*Table() methods are now static</li>';
echo '<li>The table will be created automatically for new installations</li>';
echo '</ol>';

echo '<hr>';
echo '<p><a href="' . admin_url() . '">← Back to WordPress Admin</a></p>';
