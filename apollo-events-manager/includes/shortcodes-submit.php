<?php
// phpcs:ignoreFile

if (!defined('ABSPATH')) {
    exit;
}

function apollo_events_shortcode_handler($atts = array(), $content = null)
{
    unset($content);

    global $apollo_events_manager;

    if ($apollo_events_manager instanceof Apollo_Events_Manager_Plugin) {
        $atts = is_array($atts) ? $atts : array();

        return $apollo_events_manager->events_shortcode($atts);
    }

    return '';
}
