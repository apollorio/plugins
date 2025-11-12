<?php

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_toggle_favorite', function () {
    wp_send_json_error();
});

