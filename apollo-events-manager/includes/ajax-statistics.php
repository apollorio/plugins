<?php
/**
 * AJAX Handlers for Event Statistics
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// Track event view via AJAX
add_action('wp_ajax_apollo_track_event_view', 'apollo_ajax_track_event_view');
add_action('wp_ajax_nopriv_apollo_track_event_view', 'apollo_ajax_track_event_view');

function apollo_ajax_track_event_view() {
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'apollo_track_event_view')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
        return;
    }
    
    $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'page';
    
    if (!$event_id) {
        wp_send_json_error(array('message' => 'Invalid event ID'));
        return;
    }
    
    // Verify event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event_listing') {
        wp_send_json_error(array('message' => 'Event not found'));
        return;
    }
    
    // Track view
    if (class_exists('Apollo_Event_Statistics')) {
        $result = Apollo_Event_Statistics::track_event_view($event_id, $type);
        if ($result) {
            wp_send_json_success(array(
                'message' => 'View tracked',
                'stats' => Apollo_Event_Statistics::get_event_stats($event_id)
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to track view'));
        }
    } else {
        wp_send_json_error(array('message' => 'Statistics class not available'));
    }
}

// Get event statistics via AJAX
add_action('wp_ajax_apollo_get_event_stats', 'apollo_ajax_get_event_stats');
add_action('wp_ajax_nopriv_apollo_get_event_stats', 'apollo_ajax_get_event_stats');

function apollo_ajax_get_event_stats() {
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'apollo_get_event_stats')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
        return;
    }
    
    $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
    
    if (!$event_id) {
        wp_send_json_error(array('message' => 'Invalid event ID'));
        return;
    }
    
    // Verify event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event_listing') {
        wp_send_json_error(array('message' => 'Event not found'));
        return;
    }
    
    // Get statistics (use CPT if available, fallback to meta)
    $stats = array();
    if (class_exists('Apollo_Event_Stat_CPT')) {
        $stats = Apollo_Event_Stat_CPT::get_stats($event_id);
    } elseif (class_exists('Apollo_Event_Statistics')) {
        $stats = Apollo_Event_Statistics::get_event_stats($event_id);
    }
    
    if (!empty($stats)) {
        wp_send_json_success(array('stats' => $stats));
    } else {
        wp_send_json_error(array('message' => 'No statistics available'));
    }
}

