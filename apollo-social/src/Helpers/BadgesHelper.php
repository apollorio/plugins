<?php
/**
 * Badges Helper - Canonical function for user badges
 *
 * @package ApolloSocial
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get user badges for social display
 *
 * This is the canonical function that reads membership/badge data
 * and returns a normalized array for template rendering.
 *
 * @param int $user_id User ID.
 * @return array Array of badge data: [['class' => 'apollo', 'label' => 'Producer'], ...]
 */
function apollo_social_get_user_badges( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return array();
	}

	// Read badges from user meta (new system: multiple badges)
	$badges_raw = get_user_meta( $user_id, '_apollo_badges', true );
	
	// Fallback: if no badges array, check legacy single membership
	if ( empty( $badges_raw ) || ! is_array( $badges_raw ) ) {
		$membership_slug = get_user_meta( $user_id, '_apollo_membership', true );
		if ( ! empty( $membership_slug ) && function_exists( 'apollo_get_membership_data' ) ) {
			$membership_data = apollo_get_membership_data( $membership_slug );
			if ( $membership_data ) {
				// Convert single membership to badge format
				$badges_raw = array( $membership_slug );
			}
		}
	}

	if ( empty( $badges_raw ) || ! is_array( $badges_raw ) ) {
		return array();
	}

	// Map membership slugs to badge display data
	$badge_map = apollo_social_get_badge_map();

	$badges = array();
	foreach ( $badges_raw as $slug ) {
		$slug = sanitize_key( $slug );
		if ( isset( $badge_map[ $slug ] ) ) {
			$badges[] = $badge_map[ $slug ];
		}
	}

	return $badges;
}

/**
 * Get badge mapping: membership slug → display data
 *
 * Maps membership types to CSS classes and labels for frontend rendering.
 *
 * @return array Badge map: ['slug' => ['class' => '...', 'label' => '...'], ...]
 */
function apollo_social_get_badge_map() {
	// Get membership types from apollo-core
	$memberships = array();
	if ( function_exists( 'apollo_get_memberships' ) ) {
		$memberships = apollo_get_memberships();
	}

	// Default mapping (fallback if apollo_get_memberships not available)
	$default_map = array(
		'apollo'        => array(
			'class' => 'apollo',
			'label' => 'Producer',
		),
		'dj'            => array(
			'class' => 'green',
			'label' => 'DJ',
		),
		'prod'          => array(
			'class' => 'apollo',
			'label' => 'Producer',
		),
		'host'          => array(
			'class' => 'green',
			'label' => 'Host',
		),
		'govern'        => array(
			'class' => 'blue',
			'label' => 'Govern',
		),
		'business-pers' => array(
			'class' => 'yellow',
			'label' => 'Business',
		),
		'nao-verificado' => array(
			'class' => 'muted',
			'label' => 'Não Verificado',
		),
	);

	// Build map from actual membership data
	$badge_map = array();
	foreach ( $memberships as $slug => $data ) {
		// Map membership color to CSS class
		$color_class = apollo_social_map_color_to_class( $data['color'] ?? '#9AA0A6' );
		
		$badge_map[ $slug ] = array(
			'class' => $color_class,
			'label' => $data['frontend_label'] ?? $data['label'] ?? ucfirst( $slug ),
		);
	}

	// Merge with defaults (defaults take precedence for known slugs)
	$badge_map = array_merge( $default_map, $badge_map );

	return $badge_map;
}

/**
 * Map membership color hex to CSS class name
 *
 * Maps the color from membership definition to the CSS classes
 * defined in the reference markup (apollo, green, blue, etc.).
 *
 * @param string $color Hex color code (e.g., '#FF8C42').
 * @return string CSS class name.
 */
function apollo_social_map_color_to_class( $color ) {
	$color = strtolower( trim( $color ) );

	// Map common colors to classes
	$color_map = array(
		'#ff8c42' => 'apollo',        // Orange/Apollo
		'#f97316' => 'apollo',        // Orange variant
		'#8a2be2' => 'purple',        // Purple (DJ, Prod, Host)
		'#007bff' => 'blue',          // Blue (Govern)
		'#167cf9' => 'blue',          // Blue variant
		'#63c720' => 'green',        // Green (DJ)
		'#ffd700' => 'yellow',       // Yellow (Business)
		'#edd815' => 'yellow',       // Yellow variant
		'#9aa0a6' => 'muted',        // Gray (Não Verificado)
		'#d90d21' => 'red',          // Red
		'#d615b6' => 'pink',         // Pink
		'#9820c7' => 'purple',       // Purple variant
	);

	// Direct match
	if ( isset( $color_map[ $color ] ) ) {
		return $color_map[ $color ];
	}

	// Fallback: try to match by color similarity (basic)
	// Orange → apollo
	if ( strpos( $color, 'ff8c' ) !== false || strpos( $color, 'f973' ) !== false ) {
		return 'apollo';
	}
	// Green → green
	if ( strpos( $color, '63c7' ) !== false || strpos( $color, 'c720' ) !== false ) {
		return 'green';
	}
	// Blue → blue
	if ( strpos( $color, '007b' ) !== false || strpos( $color, '167c' ) !== false ) {
		return 'blue';
	}
	// Purple → purple
	if ( strpos( $color, '8a2b' ) !== false || strpos( $color, '9820' ) !== false ) {
		return 'purple';
	}

	// Default fallback
	return 'apollo';
}

