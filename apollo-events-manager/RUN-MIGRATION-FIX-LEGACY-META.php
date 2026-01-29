<?php
/**
 * AUTO-RUN Migration for Legacy Meta Keys
 * Fixes HIGH PRIORITY issue: _event_djs → _event_dj_ids, _event_local → _event_local_ids
 *
 * USAGE:
 * 1. Via browser (admin only): /wp-content/plugins/apollo-events-manager/RUN-MIGRATION-FIX-LEGACY-META.php
 * 2. Via WP-CLI: wp eval-file RUN-MIGRATION-FIX-LEGACY-META.php
 *
 * @package Apollo_Events_Manager
 * @version 2.0.1
 */

// Load WordPress
if (php_sapi_name() === 'cli') {
    // WP-CLI mode - WordPress already loaded
    if (!function_exists('get_option')) {
        die("Error: WordPress not loaded. Run via: wp eval-file RUN-MIGRATION-FIX-LEGACY-META.php\n");
    }
} else {
    // Browser mode
    require_once dirname(__DIR__, 3) . '/wp-load.php';
    if (! current_user_can('administrator')) {
        die('Access denied - Admin only');
    }
}

echo "=== APOLLO LEGACY META MIGRATION ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

// Check if migration already completed
$migration_flag = get_option('apollo_meta_migration_v2_completed', false);
if ($migration_flag) {
    echo "✅ Migration already completed on: " . get_option('apollo_meta_migration_v2_date') . "\n";
    echo "To re-run, delete option: apollo_meta_migration_v2_completed\n";
    exit;
}

$stats = [
    'djs_migrated'    => 0,
    'locals_migrated' => 0,
    'events_checked'  => 0,
    'errors'          => [],
];

// Get all events
$events = get_posts([
    'post_type'      => 'event_listing',
    'posts_per_page' => -1,
    'post_status'    => 'any',
]);

$stats['events_checked'] = count($events);
echo "Found {$stats['events_checked']} events to check\n\n";

foreach ($events as $event) {
    $id = $event->ID;

    // Migrate _event_djs → _event_dj_ids
    if (metadata_exists('post', $id, '_event_djs') && ! metadata_exists('post', $id, '_event_dj_ids')) {
        $old = get_post_meta($id, '_event_djs', true);

        // Normalize to array
        if (!is_array($old)) {
            $old = $old ? [$old] : [];
        }

        // Convert to integer strings
        $dj_ids = array_map('intval', $old);
        $dj_ids = array_filter($dj_ids); // Remove zeros
        $dj_ids = array_values($dj_ids); // Re-index

        if (update_post_meta($id, '_event_dj_ids', $dj_ids)) {
            delete_post_meta($id, '_event_djs');
            $stats['djs_migrated']++;
            echo "✓ Event #{$id}: Migrated " . count($dj_ids) . " DJ(s)\n";
        } else {
            $stats['errors'][] = "Event #{$id}: Failed to migrate DJs";
        }
    }

    // Migrate _event_local → _event_local_ids
    if (metadata_exists('post', $id, '_event_local') && ! metadata_exists('post', $id, '_event_local_ids')) {
        $old = get_post_meta($id, '_event_local', true);

        if (is_numeric($old) && $old > 0) {
            if (update_post_meta($id, '_event_local_ids', intval($old))) {
                delete_post_meta($id, '_event_local');
                $stats['locals_migrated']++;
                echo "✓ Event #{$id}: Migrated Local #{$old}\n";
            } else {
                $stats['errors'][] = "Event #{$id}: Failed to migrate Local";
            }
        } elseif ($old) {
            $stats['errors'][] = "Event #{$id}: _event_local value '{$old}' not numeric";
        }
    }
}

echo "\n=== MIGRATION SUMMARY ===\n";
echo "Events checked: {$stats['events_checked']}\n";
echo "DJs migrated: {$stats['djs_migrated']}\n";
echo "Locals migrated: {$stats['locals_migrated']}\n";
echo "Errors: " . count($stats['errors']) . "\n";

if (!empty($stats['errors'])) {
    echo "\nERRORS:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - {$error}\n";
    }
}

// Mark migration as completed
update_option('apollo_meta_migration_v2_completed', true);
update_option('apollo_meta_migration_v2_date', date('Y-m-d H:i:s'));
update_option('apollo_meta_migration_v2_stats', $stats);

echo "\n✅ Migration completed successfully!\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";
