<?php
// phpcs:ignoreFile
/**
 * Plugin Name: APRIO Bookmarks (Favorites Fork)
 * Description: User favorites for Events/DJs.
 * Version: 0.1.0
 * Author: Apollo::rio
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
register_activation_hook( __FILE__, 'favorites_check_versions' );

define( 'FAVORITES_PLUGIN_FILE', __FILE__ );

function favorites_check_versions( $wp = '3.9', $php = '5.3.2' ) {
	global $wp_version;
	if ( version_compare( PHP_VERSION, $php, '<' ) ) {
		$flag = 'PHP';
	} elseif ( version_compare( $wp_version, $wp, '<' ) ) {
		$flag = 'WordPress';
	} else {
		return;
	}
	$version = 'PHP' == $flag ? $php : $wp;

	if ( function_exists( 'deactivate_plugins' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
	}

	wp_die(
		'<p>The <strong>Favorites</strong> plugin requires' . $flag . '  version ' . $version . ' or greater.</p>',
		'Plugin Activation Error',
		array(
			'response'  => 200,
			'back_link' => true,
		)
	);
}

if ( ! class_exists( 'Bootstrap' ) ) :
	favorites_check_versions();
	require_once __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/app/Favorites.php';
	require_once __DIR__ . '/app/API/functions.php';
	Favorites::init();
endif;

add_filter(
	'favorites/post_types',
	function ( $types ) {
		return array( 'event_listing', 'dj' );
	}
);

add_filter( 'favorites/require_login', '__return_true' );

add_action(
	'plugins_loaded',
	function () {
		require_once __DIR__ . '/includes/aprio-hooks.php';
	}
);

add_action(
	'wp_ajax_wem_toggle_bookmark',
	function () {
		// ...after success toggle...
		if ( function_exists( 'bp_activity_add' ) && ! empty( $pid ) ) {
			bp_activity_add(
				array(
					'user_id'   => get_current_user_id(),
					'component' => 'activity',
					'type'      => 'bookmark_event',
					'item_id'   => $pid,
					'content'   => 'marcou <a href="' . get_permalink( $pid ) . '">' . get_the_title( $pid ) . '</a> como favorito',
				)
			);
		}
	}
);
