<?php

// phpcs:ignoreFile
/**
 * Migration Script: Migrate legacy favorites to Interest Module
 * Run via CLI or browser (admin only)
 *
 * Migrates:
 * - apollo_favorites (user_meta) → _user_interested_events (user_meta)
 * - _apollo_favorited_users (post_meta) → _event_interested_users (post_meta)
 */

namespace Apollo\Events;

// wp-load is located at app/public/wp-load.php; plugin __DIR__ is .../wp-content/plugins/apollo-events-manager
require_once dirname(__DIR__, 4) . '/wp-load.php';

if (php_sapi_name() !== 'cli') {
    if (! current_user_can('administrator')) {
        die('Access denied - Admin only');
    }
}

echo '<pre>Migration started: ' . date('Y-m-d H:i:s') . "\n";

$processed = [
    'users_migrated'     => 0,
    'events_migrated'    => 0,
    'errors'             => [],
];

// Phase 1: Migrate user favorites
echo "Phase 1: Migrating user favorites...\n";

global $wpdb;
$user_favorites = $wpdb->get_results(
    "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'apollo_favorites'",
    ARRAY_A
);

foreach ($user_favorites as $row) {
    $user_id = (int) $row['user_id'];
    $favorites = maybe_unserialize($row['meta_value']);

    if (! is_array($favorites)) {
        $favorites = [];
    }

    // Normalize to integers
    $favorites = array_map('intval', $favorites);
    $favorites = array_filter($favorites);

    if (! empty($favorites)) {
        // Check if user already has interest data
        $existing = get_user_meta($user_id, '_user_interested_events', true);
        if (is_array($existing)) {
            $favorites = array_unique(array_merge($existing, $favorites));
        }

        $ok = update_user_meta($user_id, '_user_interested_events', $favorites);
        if ($ok !== false) {
            ++$processed['users_migrated'];
            echo "Migrated user $user_id: " . count($favorites) . " favorites\n";
        } else {
            $processed['errors'][] = "Failed to migrate user $user_id";
        }
    }
}

// Phase 2: Migrate event favorited users
echo "Phase 2: Migrating event favorited users...\n";

$event_favorites = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_apollo_favorited_users'",
    ARRAY_A
);

foreach ($event_favorites as $row) {
    $event_id = (int) $row['post_id'];
    $favorited_users = maybe_unserialize($row['meta_value']);

    if (! is_array($favorited_users)) {
        $favorited_users = [];
    }

    // Normalize to integers
    $favorited_users = array_map('intval', $favorited_users);
    $favorited_users = array_filter($favorited_users);

    if (! empty($favorited_users)) {
        // Check if event already has interest data
        $existing = get_post_meta($event_id, '_event_interested_users', true);
        if (is_array($existing)) {
            $favorited_users = array_unique(array_merge($existing, $favorited_users));
        }

        $ok = update_post_meta($event_id, '_event_interested_users', $favorited_users);
        if ($ok !== false) {
            ++$processed['events_migrated'];
            echo "Migrated event $event_id: " . count($favorited_users) . " users\n";
        } else {
            $processed['errors'][] = "Failed to migrate event $event_id";
        }
    }
}

// Phase 3: Update favorites count using transients
echo "Phase 3: Updating favorites count...\n";

$events = get_posts([
    'post_type' => 'event_listing',
    'posts_per_page' => -1,
    'post_status' => 'any',
]);

foreach ($events as $event) {
    $interested_users = get_post_meta($event->ID, '_event_interested_users', true);
    if (is_array($interested_users)) {
        $count = count($interested_users);
        update_post_meta($event->ID, '_favorites_count', $count);

        // Set transient for caching
        $transient_key = 'apollo_favorites_count_' . $event->ID;
        set_transient($transient_key, $count, HOUR_IN_SECONDS);
    }
}

// Clear caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "Cache flushed\n";
}

echo "\nSummary:\n";
echo 'Users migrated: ' . $processed['users_migrated'] . "\n";
echo 'Events migrated: ' . $processed['events_migrated'] . "\n";
if (! empty($processed['errors'])) {
    echo "Errors:\n" . implode("\n", $processed['errors']) . "\n";
}

echo 'Migration finished: ' . date('Y-m-d H:i:s') . "\n";

return 0;
