<?php
/**
 * AJAX Handlers for HUB::rio Linktree Editor
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get HUB state for current user
 */
add_action( 'wp_ajax_apollo_hub_get_state', 'apollo_hub_ajax_get_state' );
function apollo_hub_ajax_get_state() {
	check_ajax_referer( 'apollo_hub_editor', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Not logged in' ) );
	}

	$user_id = get_current_user_id();

	// Load profile data
	$profile = array(
		'avatar'            => get_user_meta( $user_id, '_apollo_hub_avatar', true ) ?: '',
		'avatarStyle'       => get_user_meta( $user_id, '_apollo_hub_avatar_style', true ) ?: 'rounded',
		'avatarBorder'      => get_user_meta( $user_id, '_apollo_hub_avatar_border', true ) === '1',
		'avatarBorderWidth' => intval( get_user_meta( $user_id, '_apollo_hub_avatar_border_width', true ) ) ?: 4,
		'avatarBorderColor' => get_user_meta( $user_id, '_apollo_hub_avatar_border_color', true ) ?: '#ffffff',
		'name'              => get_user_meta( $user_id, '_apollo_hub_name', true ) ?: '@' . wp_get_current_user()->user_login,
		'bio'               => get_user_meta( $user_id, '_apollo_hub_bio', true ) ?: '',
		'bg'                => get_user_meta( $user_id, '_apollo_hub_bg', true ) ?: '',
		'texture'           => get_user_meta( $user_id, '_apollo_hub_texture', true ) ?: 'none',
	);

	// Load blocks data
	$blocks_json = get_user_meta( $user_id, '_apollo_hub_blocks', true );
	$blocks      = $blocks_json ? json_decode( $blocks_json, true ) : array();

	// Ensure blocks is array
	if ( ! is_array( $blocks ) ) {
		$blocks = array();
	}

	wp_send_json_success( array(
		'profile' => $profile,
		'blocks'  => $blocks,
	) );
}

/**
 * Save HUB state for current user
 */
add_action( 'wp_ajax_apollo_hub_save_state', 'apollo_hub_ajax_save_state' );
function apollo_hub_ajax_save_state() {
	check_ajax_referer( 'apollo_hub_editor', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Not logged in' ) );
	}

	$user_id = get_current_user_id();

	// Get posted state
	$state_raw = isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : '';
	$state     = json_decode( $state_raw, true );

	if ( ! is_array( $state ) ) {
		wp_send_json_error( array( 'message' => 'Invalid state data' ) );
	}

	// Save profile meta
	if ( isset( $state['profile'] ) && is_array( $state['profile'] ) ) {
		$profile = $state['profile'];

		update_user_meta( $user_id, '_apollo_hub_avatar', sanitize_text_field( $profile['avatar'] ?? '' ) );
		update_user_meta( $user_id, '_apollo_hub_avatar_style', sanitize_text_field( $profile['avatarStyle'] ?? 'rounded' ) );
		update_user_meta( $user_id, '_apollo_hub_avatar_border', ! empty( $profile['avatarBorder'] ) ? '1' : '0' );
		update_user_meta( $user_id, '_apollo_hub_avatar_border_width', intval( $profile['avatarBorderWidth'] ?? 4 ) );
		update_user_meta( $user_id, '_apollo_hub_avatar_border_color', sanitize_hex_color( $profile['avatarBorderColor'] ?? '#ffffff' ) );
		update_user_meta( $user_id, '_apollo_hub_name', sanitize_text_field( $profile['name'] ?? '' ) );
		update_user_meta( $user_id, '_apollo_hub_bio', sanitize_textarea_field( $profile['bio'] ?? '' ) );
		update_user_meta( $user_id, '_apollo_hub_bg', esc_url_raw( $profile['bg'] ?? '' ) );
		update_user_meta( $user_id, '_apollo_hub_texture', sanitize_text_field( $profile['texture'] ?? 'none' ) );
	}

	// Save blocks as JSON
	if ( isset( $state['blocks'] ) && is_array( $state['blocks'] ) ) {
		$blocks_json = wp_json_encode( $state['blocks'] );
		update_user_meta( $user_id, '_apollo_hub_blocks', $blocks_json );
	}

	wp_send_json_success( array( 'message' => 'State saved successfully' ) );
}

/**
 * Get events for HUB selector (internal events from Apollo)
 */
add_action( 'wp_ajax_apollo_hub_get_events', 'apollo_hub_ajax_get_events' );
function apollo_hub_ajax_get_events() {
	check_ajax_referer( 'apollo_hub_editor', 'nonce' );

	$events = array();

	// Query Apollo events (event_listing CPT)
	$args = array(
		'post_type'      => 'event_listing',
		'posts_per_page' => 50,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			$events[] = array(
				'id'    => $post_id,
				'title' => get_the_title(),
				'date'  => get_the_date( 'd M' ),
				'url'   => get_permalink(),
				'thumb' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '',
			);
		}
		wp_reset_postdata();
	}

	wp_send_json_success( $events );
}

/**
 * Get recent posts for HUB Latest News widget
 */
add_action( 'wp_ajax_apollo_hub_get_posts', 'apollo_hub_ajax_get_posts' );
function apollo_hub_ajax_get_posts() {
	check_ajax_referer( 'apollo_hub_editor', 'nonce' );

	$posts = array();

	$args = array(
		'post_type'      => 'post',
		'posts_per_page' => 20,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			$posts[] = array(
				'id'      => $post_id,
				'title'   => get_the_title(),
				'date'    => get_the_date( 'd/m/Y' ),
				'url'     => get_permalink(),
				'excerpt' => wp_trim_words( get_the_excerpt(), 15, '...' ),
				'thumb'   => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '',
			);
		}
		wp_reset_postdata();
	}

	wp_send_json_success( $posts );
}
