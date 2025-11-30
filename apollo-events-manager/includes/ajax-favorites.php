<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('apollo_ajax_handle_toggle_favorite')) {
    function apollo_ajax_handle_toggle_favorite() {
        if (!is_user_logged_in()) {
            wp_send_json_error(
                array('message' => __('Entre na sua conta para salvar favoritos.', 'apollo-events-manager')),
                401
            );
        }

        // Verify nonce
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'apollo_events_nonce')) {
            wp_send_json_error(array('message' => __('Nonce inválido.', 'apollo-events-manager')), 403);
            return;
        }

        // Sanitize input
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Evento inválido.', 'apollo-events-manager')), 400);
        }

        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing') {
            wp_send_json_error(array('message' => __('Evento não encontrado.', 'apollo-events-manager')), 404);
        }

        $user_id = get_current_user_id();

        $user_favorites = get_user_meta($user_id, 'apollo_favorites', true);
        if (!is_array($user_favorites)) {
            $user_favorites = array();
        }

        $user_favorites = array_values(array_unique(array_map('intval', $user_favorites)));

        $already_favorited = in_array($event_id, $user_favorites, true);

        if ($already_favorited) {
            $user_favorites = array_values(array_diff($user_favorites, array($event_id)));
        } else {
            $user_favorites[] = $event_id;
        }

        if (!empty($user_favorites)) {
            update_user_meta($user_id, 'apollo_favorites', $user_favorites);
        } else {
            delete_user_meta($user_id, 'apollo_favorites');
        }

        $favorited_users = apollo_get_event_favorite_user_ids($event_id);

        if ($already_favorited) {
            $favorited_users = array_values(array_diff($favorited_users, array($user_id)));
        } else {
            $favorited_users[] = $user_id;
        }

        $favorited_users = apollo_store_event_favorite_user_ids($event_id, $favorited_users);

        $snapshot = apollo_get_event_favorites_snapshot($event_id);

        wp_send_json_success(array(
            'fav' => $snapshot['current_user_has_favorited'],
            'count' => $snapshot['count'],
            'avatars' => $snapshot['avatars'],
            'remaining' => $snapshot['remaining'],
        ));
    }

    function apollo_ajax_handle_toggle_favorite_nopriv() {
        wp_send_json_error(
            array('message' => __('Entre na sua conta para salvar favoritos.', 'apollo-events-manager')),
            401
        );
    }
}

add_action('wp_ajax_toggle_favorite', 'apollo_ajax_handle_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'apollo_ajax_handle_toggle_favorite_nopriv');

