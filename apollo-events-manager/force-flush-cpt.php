<?php
/**
 * Force Flush CPT - Temporary Diagnostic
 * ========================================
 * Path: apollo-events-manager/force-flush-cpt.php
 *
 * USAGE: Access this file via browser after loading a WordPress page
 *        Example: /wp-content/plugins/apollo-events-manager/force-flush-cpt.php?action=flush
 *
 * DELETE THIS FILE AFTER USE!
 *
 * @package Apollo_Events_Manager
 */

// Load WordPress
require_once dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied. You must be an administrator.' );
}

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

echo '<h1>Apollo Events Manager - CPT Diagnostic</h1>';

// Show registered CPTs
echo '<h2>Registered Post Types</h2>';
$post_types = get_post_types( array(), 'objects' );
$apollo_cpts = array();
foreach ( $post_types as $pt ) {
    if ( strpos( $pt->name, 'event' ) !== false || strpos( $pt->name, 'apollo' ) !== false || strpos( $pt->name, 'dj' ) !== false ) {
        $apollo_cpts[ $pt->name ] = $pt;
    }
}

echo '<table border="1" cellpadding="5">';
echo '<tr><th>CPT Slug</th><th>Label</th><th>Public</th><th>Has Archive</th><th>Rewrite Slug</th><th>Show in REST</th></tr>';
foreach ( $apollo_cpts as $name => $pt ) {
    $rewrite = is_array( $pt->rewrite ) ? ( $pt->rewrite['slug'] ?? 'N/A' ) : ( $pt->rewrite ? 'true' : 'false' );
    $archive = $pt->has_archive ? ( is_string( $pt->has_archive ) ? $pt->has_archive : 'true' ) : 'false';
    echo sprintf(
        '<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        esc_html( $name ),
        esc_html( $pt->label ),
        $pt->public ? '✅' : '❌',
        esc_html( $archive ),
        esc_html( $rewrite ),
        $pt->show_in_rest ? '✅' : '❌'
    );
}
echo '</table>';

// Show event_listing counts
echo '<h2>Event Listing Stats</h2>';
$counts = wp_count_posts( 'event_listing' );
echo '<pre>';
print_r( $counts );
echo '</pre>';

// Show recent events
echo '<h2>Recent Events (last 5)</h2>';
$recent_events = get_posts( array(
    'post_type'      => 'event_listing',
    'posts_per_page' => 5,
    'post_status'    => 'any',
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

if ( empty( $recent_events ) ) {
    echo '<p><strong style="color:red;">NO EVENTS FOUND!</strong></p>';
} else {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Date</th><th>Event Date</th></tr>';
    foreach ( $recent_events as $event ) {
        $event_date = get_post_meta( $event->ID, '_event_start_date', true );
        echo sprintf(
            '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $event->ID,
            esc_html( $event->post_title ),
            esc_html( $event->post_status ),
            esc_html( $event->post_date ),
            esc_html( $event_date ?: 'N/A' )
        );
    }
    echo '</table>';
}

// Flush action
if ( $action === 'flush' ) {
    echo '<h2>Flushing Rewrite Rules...</h2>';

    // Re-register the CPTs first
    do_action( 'init' );

    // Flush rewrite rules
    flush_rewrite_rules( true );

    echo '<p style="color:green;font-weight:bold;">✅ Rewrite rules flushed successfully!</p>';
    echo '<p>Now go to <a href="' . home_url( '/eventos/' ) . '">/eventos/</a> to test.</p>';
}

// Check /eventos/ page
echo '<h2>Eventos Page Check</h2>';
$eventos_page = get_page_by_path( 'eventos' );
if ( $eventos_page ) {
    echo '<p>✅ Page "eventos" exists: ID ' . $eventos_page->ID . ' (Status: ' . $eventos_page->post_status . ')</p>';
    $template = get_post_meta( $eventos_page->ID, '_wp_page_template', true );
    echo '<p>Template: ' . ( $template ?: 'default' ) . '</p>';
} else {
    echo '<p style="color:orange;">⚠️ No page with slug "eventos" found. Using post type archive.</p>';
}

// Archive URL check
echo '<h2>Archive URLs</h2>';
echo '<p><strong>event_listing archive URL:</strong> <a href="' . get_post_type_archive_link( 'event_listing' ) . '">' . get_post_type_archive_link( 'event_listing' ) . '</a></p>';

// Buttons
echo '<hr>';
echo '<h2>Actions</h2>';
echo '<p><a href="?action=flush" style="background:#0073aa;color:#fff;padding:10px 20px;text-decoration:none;border-radius:3px;">🔄 Flush Rewrite Rules</a></p>';
echo '<p><a href="' . home_url( '/eventos/' ) . '" target="_blank" style="background:#46b450;color:#fff;padding:10px 20px;text-decoration:none;border-radius:3px;">🌐 Open /eventos/</a></p>';
echo '<p><a href="' . admin_url( 'edit.php?post_type=event_listing' ) . '" target="_blank" style="background:#f56e28;color:#fff;padding:10px 20px;text-decoration:none;border-radius:3px;">📋 Admin: Events</a></p>';

echo '<hr>';
echo '<p style="color:red;"><strong>⚠️ DELETE THIS FILE AFTER USE!</strong></p>';
