<?php
declare(strict_types=1);

/**
 * Check if Apollo CPTs are registered correctly.
 * Access via: /wp-content/plugins/apollo-events-manager/check-cpts.php
 *
 * @package Apollo_Events_Manager
 */

// Prevent direct access without WP.
if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

echo '<h1>Apollo Events Manager - CPT Check</h1>';

// Check if post types exist.
$post_types = array( 'event_listing', 'event_dj', 'event_local' );
$taxonomies = array( 'event_listing_category', 'event_listing_type', 'event_sounds' );

echo '<h2>Custom Post Types:</h2>';
echo '<ul>';
foreach ( $post_types as $pt ) {
	$obj = get_post_type_object( $pt );
	if ( $obj ) {
		echo '<li><strong>' . esc_html( $pt ) . '</strong>: ✅ Registered - show_in_menu: ' . ( $obj->show_in_menu ? 'true' : 'false' ) . '</li>';
	} else {
		echo '<li><strong>' . esc_html( $pt ) . '</strong>: ❌ NOT REGISTERED</li>';
	}
}
echo '</ul>';

echo '<h2>Taxonomies:</h2>';
echo '<ul>';
foreach ( $taxonomies as $taxonomy_slug ) {
	$obj = get_taxonomy( $taxonomy_slug );
	if ( $obj ) {
		echo '<li><strong>' . esc_html( $taxonomy_slug ) . '</strong>: ✅ Registered</li>';
	} else {
		echo '<li><strong>' . esc_html( $taxonomy_slug ) . '</strong>: ❌ NOT REGISTERED</li>';
	}
}
echo '</ul>';

echo '<h2>Debug Info:</h2>';
if ( isset( $_SERVER['REQUEST_URI'] ) ) {
	echo '<p>Current URL: ' . esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '</p>';
}
echo '<p>Plugin Path: ' . esc_html( plugin_dir_path( __FILE__ ) ) . '</p>';
echo '<p>APOLLO_DEBUG: ' . ( defined( 'APOLLO_DEBUG' ) ? 'true' : 'false' ) . '</p>';

// Manual flush option.
if ( isset( $_GET['flush'] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Debug script, manual action.
	flush_rewrite_rules( false );
	echo '<p><strong>Rewrite rules flushed!</strong></p>';
	echo "<p><a href='?'>← Back</a></p>";
} else {
	echo "<p><a href='?flush=1'>Flush Rewrite Rules</a></p>";
}
