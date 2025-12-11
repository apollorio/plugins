<?php if ( ! defined( 'ABSPATH' ) ) {
// phpcs:ignoreFile
	exit;}

// Renders Favorites button on single Event page
add_action(
	'single_event_listing_end',
	function () {
		if ( ! function_exists( 'get_favorites_button' ) ) {
			return;
		}
		echo '<div class="aprio-fav">' . get_favorites_button( get_the_ID() ) . '</div>';
	},
	20
);

// Optional: on DJ single
add_action(
	'wp',
	function () {
		if ( is_singular( 'dj' ) && function_exists( 'get_favorites_button' ) ) {
			add_action(
				'wp_head',
				function () {
					echo '<style>.aprio-fav{margin-top:12px}</style>';
				}
			);
			add_action(
				'loop_start',
				function ( $q ) {
					if ( $q->is_main_query() && is_singular( 'dj' ) ) {
						add_action(
							'the_content',
							function ( $c ) {
								return $c . '<div class="aprio-fav">' . get_favorites_button( get_the_ID() ) . '</div>';
							}
						);
					}
				}
			);
		}//end if
	}
);
