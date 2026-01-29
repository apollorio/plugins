<?php

declare(strict_types=1);
/**
 * Apollo Multisite Routes Helper
 *
 * Provides multisite support with fallback to single site
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Multisite Routes
 */
class Apollo_Multisite_Routes {

	/**
	 * Get route URL with multisite support
	 *
	 * @param string     $type Route type (dj, evento, etc).
	 * @param int|string $id Item ID or slug.
	 * @return string URL.
	 */
	public static function get_route_url( string $type, $id ): string {
		// Check if multisite is enabled and active.
		if ( is_multisite() && get_option( 'apollo_multisite_enabled', false ) ) {
			$host = parse_url( home_url(), PHP_URL_HOST );
			if ( $host ) {
				$subdomain = $type . '.' . $host;
				return "https://{$subdomain}/{$id}";
			}
		}

		// Fallback to single site structure.
		return home_url( "/{$type}/{$id}" );
	}

	/**
	 * Get DJ URL
	 *
	 * @param int|string $dj_id DJ ID or slug.
	 * @return string URL.
	 */
	public static function get_dj_url( $dj_id ): string {
		return self::get_route_url( 'dj', $dj_id );
	}

	/**
	 * Get Event URL
	 *
	 * @param int|string $event_id Event ID or slug.
	 * @return string URL.
	 */
	public static function get_event_url( $event_id ): string {
		return self::get_route_url( 'evento', $event_id );
	}

	/**
	 * Check if multisite mode is enabled
	 *
	 * @return bool True if multisite and enabled.
	 */
	public static function is_multisite_enabled(): bool {
		return is_multisite() && get_option( 'apollo_multisite_enabled', false );
	}
}
