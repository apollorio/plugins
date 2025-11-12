<?php

if (!defined('ABSPATH')) {
    return;
}

add_action('wp_ajax_toggle_favorite', 'aem_handle_favorite_toggle');
add_action('wp_ajax_nopriv_toggle_favorite', 'aem_handle_favorite_toggle');

/**
 * Toggle event favorites for the current user.
 */
function aem_handle_favorite_toggle() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('code' => 'auth'), 401);
    }

    check_ajax_referer('apollo_events_nonce', '_ajax_nonce');

    $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;
    if (!$event_id) {
        wp_send_json_error(array('code' => 'invalid_event'), 400);
    }

    $post = get_post($event_id);
    if (!$post || $post->post_type !== 'event_listing') {
        wp_send_json_error(array('code' => 'invalid_event'), 404);
    }

    $user_id = get_current_user_id();
    $meta_key = 'apollo_favorites';

    $stored = get_user_meta($user_id, $meta_key, true);
    $favorites = is_array($stored) ? array_map('absint', $stored) : array();
    $favorites = array_values(array_unique(array_filter($favorites)));

    $has_favorited = in_array($event_id, $favorites, true);

    if ($has_favorited) {
        $favorites = array_values(
            array_filter(
                $favorites,
                static function ($fav_id) use ($event_id) {
                    return (int) $fav_id !== $event_id;
                }
            )
        );
    } else {
        $favorites[] = $event_id;
    }

    update_user_meta($user_id, $meta_key, $favorites);

    $count = aem_recount_unique_favorites($event_id);
    update_post_meta($event_id, '_favorites_count', $count);

    if (function_exists('aem_events_transient_key')) {
        delete_transient(aem_events_transient_key());
    }

    wp_send_json_success(
        array(
            'fav'   => !$has_favorited,
            'count' => $count,
        )
    );
}

/**
 * Recalculate the number of distinct users that have favorited an event.
 */
function aem_recount_unique_favorites($event_id) {
    global $wpdb;

    $event_id = (int) $event_id;
    if ($event_id <= 0) {
        return 0;
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            'apollo_favorites'
        )
    );

    if (!$rows) {
        return 0;
    }

    $unique_users = array();

    foreach ($rows as $row) {
        $favorite_list = maybe_unserialize($row->meta_value);
        if (!is_array($favorite_list)) {
            continue;
        }

        $favorite_list = array_map('absint', $favorite_list);
        $favorite_list = array_values(array_unique(array_filter($favorite_list)));

        if (in_array($event_id, $favorite_list, true)) {
            $unique_users[(int) $row->user_id] = true;
        }
    }

    $count = count($unique_users);

    return $count > 0 ? $count : 0;
}
