<?php

if (!defined('ABSPATH')) exit;

function aem_events_transient_key() {
    return 'apollo_events:list:futuro';
}

add_action('save_post_event_listing', function ($post_id) {
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    delete_transient(aem_events_transient_key());
}, 20);

