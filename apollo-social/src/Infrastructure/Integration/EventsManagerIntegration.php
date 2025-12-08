<?php
/**
 * Apollo Events Manager Integration
 *
 * Integrates Apollo Social with event_dj and event_local CPTs from Apollo Events Manager
 * Provides helper functions to read/display DJs and Locals without creating them
 *
 * @package Apollo_Social
 * @version 1.0.0
 */

namespace Apollo\Infrastructure\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EventsManagerIntegration {

	/**
	 * Check if Events Manager CPTs are available
	 *
	 * @return bool
	 */
	public static function isEventsManagerActive(): bool {
		return post_type_exists( 'event_dj' ) && post_type_exists( 'event_local' );
	}

	/**
	 * Get DJ by ID
	 *
	 * @param int $dj_id DJ post ID
	 * @return \WP_Post|null
	 */
	public static function getDJ( int $dj_id ): ?\WP_Post {
		if ( ! self::isEventsManagerActive() ) {
			return null;
		}

		$dj = get_post( $dj_id );

		if ( ! $dj || $dj->post_type !== 'event_dj' ) {
			return null;
		}

		return $dj;
	}

	/**
	 * Get Local/Venue by ID
	 *
	 * @param int $local_id Local post ID
	 * @return \WP_Post|null
	 */
	public static function getLocal( int $local_id ): ?\WP_Post {
		if ( ! self::isEventsManagerActive() ) {
			return null;
		}

		$local = get_post( $local_id );

		if ( ! $local || $local->post_type !== 'event_local' ) {
			return null;
		}

		return $local;
	}

	/**
	 * Get all DJs (for listing)
	 *
	 * @param array $args WP_Query arguments
	 * @return array Array of DJ posts
	 */
	public static function getDJs( array $args = [] ): array {
		if ( ! self::isEventsManagerActive() ) {
			return [];
		}

		$defaults = [
			'post_type'      => 'event_dj',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$query_args = wp_parse_args( $args, $defaults );
		$query      = new \WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Get all Locals/Venues (for listing)
	 *
	 * @param array $args WP_Query arguments
	 * @return array Array of Local posts
	 */
	public static function getLocals( array $args = [] ): array {
		if ( ! self::isEventsManagerActive() ) {
			return [];
		}

		$defaults = [
			'post_type'      => 'event_local',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$query_args = wp_parse_args( $args, $defaults );
		$query      = new \WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Get DJ permalink (ensures single-event_dj.php template is used)
	 *
	 * @param int $dj_id DJ post ID
	 * @return string|false
	 */
	public static function getDJPermalink( int $dj_id ): string|false {
		if ( ! self::isEventsManagerActive() ) {
			return false;
		}

		return get_permalink( $dj_id );
	}

	/**
	 * Get Local permalink
	 *
	 * @param int $local_id Local post ID
	 * @return string|false
	 */
	public static function getLocalPermalink( int $local_id ): string|false {
		if ( ! self::isEventsManagerActive() ) {
			return false;
		}

		return get_permalink( $local_id );
	}

	/**
	 * Get DJ meta data
	 *
	 * @param int    $dj_id DJ post ID
	 * @param string $meta_key Meta key
	 * @param bool   $single Return single value
	 * @return mixed
	 */
	public static function getDJMeta( int $dj_id, string $meta_key, bool $single = true ) {
		if ( ! self::isEventsManagerActive() ) {
			return false;
		}

		return get_post_meta( $dj_id, $meta_key, $single );
	}

	/**
	 * Get Local meta data
	 *
	 * @param int    $local_id Local post ID
	 * @param string $meta_key Meta key
	 * @param bool   $single Return single value
	 * @return mixed
	 */
	public static function getLocalMeta( int $local_id, string $meta_key, bool $single = true ) {
		if ( ! self::isEventsManagerActive() ) {
			return false;
		}

		return get_post_meta( $local_id, $meta_key, $single );
	}
}
