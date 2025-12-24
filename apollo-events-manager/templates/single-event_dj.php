<?php
/**
 * Template Wrapper: Single DJ Profile (CPT: event_dj)
 * ====================================================
 * Path: apollo-events-manager/templates/single-event_dj.php
 * Core Template: apollo-core/templates/core-dj-single.php
 *
 * This wrapper handles:
 * 1. Post validation and 404 handling
 * 2. Meta data extraction
 * 3. Link arrays building
 * 4. Context preparation
 * 5. Delegation to core template
 *
 * Meta keys used:
 * - _dj_image, _dj_banner: DJ photo fallbacks
 * - _dj_tagline: DJ tagline/subtitle
 * - _dj_roles: Roles string (DJ · Producer · etc.)
 * - _dj_projects: Array or comma-separated projects
 * - _dj_track_title: Featured track title
 * - _dj_soundcloud, _dj_set_url: SoundCloud URLs
 * - _dj_spotify, _dj_youtube, _dj_instagram, _dj_facebook, _dj_twitter, _dj_tiktok
 * - _dj_mixcloud, _dj_beatport, _dj_bandcamp, _dj_resident_advisor, _dj_website
 * - _dj_media_kit_url, _dj_rider_url, _dj_mix_url
 */

defined( 'ABSPATH' ) || exit;

// =============================================================================
// VALIDATION - Ensure valid DJ post
// =============================================================================
if ( ! have_posts() || get_post_type() !== 'event_dj' ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

the_post();
global $post;

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get post meta with apollo prefix handling
 */
function apollo_dj_get_meta( $post_id, $key, $single = true ) {
	if ( function_exists( 'apollo_get_post_meta' ) ) {
		return apollo_get_post_meta( $post_id, $key, $single );
	}
	return get_post_meta( $post_id, $key, $single );
}

/**
 * Build social links array from meta
 *
 * @param int $dj_id DJ post ID
 * @return array Structured links array
 */
function apollo_dj_build_all_links( $dj_id ) {
	$links = array(
		'music'     => array(
			'soundcloud' => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_soundcloud', true ),
				'icon'  => 'ri-soundcloud-line',
				'label' => 'SoundCloud',
			),
			'spotify'    => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_spotify', true ),
				'icon'  => 'ri-spotify-line',
				'label' => 'Spotify',
			),
			'youtube'    => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_youtube', true ),
				'icon'  => 'ri-youtube-line',
				'label' => 'YouTube',
			),
		),
		'social'    => array(
			'instagram' => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_instagram', true ),
				'icon'  => 'ri-instagram-line',
				'label' => 'Instagram',
			),
			'facebook'  => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_facebook', true ),
				'icon'  => 'ri-facebook-circle-line',
				'label' => 'Facebook',
			),
			'twitter'   => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_twitter', true ),
				'icon'  => 'ri-twitter-x-line',
				'label' => 'Twitter',
			),
			'tiktok'    => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_tiktok', true ),
				'icon'  => 'ri-tiktok-line',
				'label' => 'TikTok',
			),
		),
		'platforms' => array(
			'mixcloud' => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_mixcloud', true ),
				'icon'  => 'ri-cloud-line',
				'label' => 'Mixcloud',
			),
			'beatport' => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_beatport', true ),
				'icon'  => 'ri-vip-crown-line',
				'label' => 'Beatport',
			),
			'bandcamp' => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_bandcamp', true ),
				'icon'  => 'ri-album-line',
				'label' => 'Bandcamp',
			),
			'ra'       => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_resident_advisor', true ),
				'icon'  => 'ri-radio-line',
				'label' => 'Resident Advisor',
			),
			'website'  => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_website', true ),
				'icon'  => 'ri-external-link-line',
				'label' => 'Site oficial',
			),
		),
		'assets'    => array(
			'mediakit' => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_media_kit_url', true ),
				'icon'  => 'ri-clipboard-line',
				'label' => 'Media kit',
			),
			'rider'    => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_rider_url', true ),
				'icon'  => 'ri-clipboard-fill',
				'label' => 'Rider',
			),
			'mix'      => array(
				'url'   => apollo_dj_get_meta( $dj_id, '_dj_mix_url', true ),
				'icon'  => 'ri-play-list-2-line',
				'label' => 'Mix / Playlist',
			),
		),
	);

	return $links;
}

/**
 * Filter links array to only include those with URLs
 *
 * @param array $links Array of link items
 * @return array Filtered array
 */
function apollo_dj_filter_links( $links ) {
	return array_filter(
		$links,
		function ( $link ) {
			return ! empty( $link['url'] );
		}
	);
}

/**
 * Build SoundCloud embed URL
 *
 * @param string $soundcloud_url SoundCloud track/set URL
 * @return string Embed iframe src URL
 */
function apollo_dj_build_soundcloud_embed( $soundcloud_url ) {
	if ( empty( $soundcloud_url ) ) {
		return '';
	}
	return 'https://w.soundcloud.com/player/?url=' . rawurlencode( $soundcloud_url ) .
			'&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false' .
			'&show_user=false&show_reposts=false&show_teaser=false';
}

/**
 * Format DJ name for display (can include line breaks)
 *
 * @param string $name DJ name
 * @return string Formatted name with possible <br> tags
 */
function apollo_dj_format_name( $name ) {
	// If name has spaces, allow word-based line break
	$words = explode( ' ', trim( $name ) );
	if ( count( $words ) >= 2 ) {
		return esc_html( $words[0] ) . '<br>' . esc_html( implode( ' ', array_slice( $words, 1 ) ) );
	}
	return esc_html( $name );
}

// =============================================================================
// DATA EXTRACTION
// =============================================================================

$dj_id   = $post->ID;
$dj_name = get_the_title( $dj_id );

// Hero / Identity
$dj_photo_url = get_the_post_thumbnail_url( $dj_id, 'large' );
if ( ! $dj_photo_url ) {
	$dj_photo_url = apollo_dj_get_meta( $dj_id, '_dj_image', true );
}
if ( ! $dj_photo_url ) {
	$dj_photo_url = apollo_dj_get_meta( $dj_id, '_dj_banner', true );
}
if ( ! $dj_photo_url ) {
	$dj_photo_url = ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ? APOLLO_CORE_PLUGIN_URL . 'assets/img/' : APOLLO_APRIO_URL . 'assets/img/' ) . 'placeholder-dj.webp';
}

$dj_tagline = apollo_dj_get_meta( $dj_id, '_dj_tagline', true ) ?: '';
$dj_roles   = apollo_dj_get_meta( $dj_id, '_dj_roles', true ) ?: 'DJ';

// Projects
$dj_projects = apollo_dj_get_meta( $dj_id, '_dj_projects', true );
if ( ! is_array( $dj_projects ) ) {
	$dj_projects = array_filter( array_map( 'trim', explode( ',', (string) $dj_projects ) ) );
}

// Bio
$dj_bio_full    = apply_filters( 'the_content', get_the_content() );
$dj_bio_excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( $dj_bio_full ), 40 );

// Featured Track
$dj_track_title = apollo_dj_get_meta( $dj_id, '_dj_track_title', true ) ?: '';
$dj_soundcloud  = apollo_dj_get_meta( $dj_id, '_dj_soundcloud', true ) ?: '';
$dj_set_url     = apollo_dj_get_meta( $dj_id, '_dj_set_url', true ) ?: $dj_soundcloud;

// Build all links
$all_links = apollo_dj_build_all_links( $dj_id );

// Filter each category
$music_links    = apollo_dj_filter_links( $all_links['music'] );
$social_links   = apollo_dj_filter_links( $all_links['social'] );
$asset_links    = apollo_dj_filter_links( $all_links['assets'] );
$platform_links = apollo_dj_filter_links( $all_links['platforms'] );

// Media kit URL for header button
$media_kit_url = isset( $all_links['assets']['mediakit']['url'] ) ? $all_links['assets']['mediakit']['url'] : '';

// SoundCloud embed URL
$sc_embed_url = apollo_dj_build_soundcloud_embed( $dj_set_url );

// Print mode check
$is_print = isset( $_GET['print'] ) || isset( $_GET['pdf'] );

// =============================================================================
// BUILD CONTEXT FOR CORE TEMPLATE
// =============================================================================

$core_context = array(
	// Identity
	'dj_id'             => $dj_id,
	'dj_name'           => $dj_name,
	'dj_name_formatted' => apollo_dj_format_name( $dj_name ),
	'dj_photo_url'      => $dj_photo_url,
	'dj_tagline'        => $dj_tagline,
	'dj_roles'          => $dj_roles,
	'dj_projects'       => $dj_projects,

	// Bio
	'dj_bio_excerpt'    => $dj_bio_excerpt,
	'dj_bio_full'       => $dj_bio_full,

	// Player
	'dj_track_title'    => $dj_track_title,
	'sc_embed_url'      => $sc_embed_url,

	// Links (pre-filtered)
	'music_links'       => $music_links,
	'social_links'      => $social_links,
	'asset_links'       => $asset_links,
	'platform_links'    => $platform_links,
	'media_kit_url'     => $media_kit_url,

	// Flags
	'is_print'          => $is_print,
);

// =============================================================================
// LOAD CORE TEMPLATE
// =============================================================================

if ( class_exists( 'Apollo_Template_Loader' ) ) {
	Apollo_Template_Loader::load( 'core-dj-single', $core_context );
} else {
	// Fallback: Direct include with extract
	$template_path = dirname( __DIR__, 2 ) . '/apollo-core/templates/core-dj-single.php';
	if ( file_exists( $template_path ) ) {
		extract( $core_context, EXTR_SKIP );
		include $template_path;
	} else {
		wp_die( 'Core template not found: core-dj-single.php' );
	}
}

wp_reset_postdata();
