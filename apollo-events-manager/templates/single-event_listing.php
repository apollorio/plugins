<?php
/**
 * Template Apollo: Single Event Listing Override
 * Replaces WP Event Manager default single template
 * 
 * ✅ Uses correct database meta keys:
 * - _event_dj_ids (serialized array)
 * - _event_local_ids (numeric)
 * - _event_banner (URL, not attachment ID)
 */

defined('ABSPATH') || exit;

// Load our complete standalone template
include APOLLO_WPEM_PATH . 'templates/single-event-standalone.php';
