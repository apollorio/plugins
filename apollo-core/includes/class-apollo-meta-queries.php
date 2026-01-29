<?php
/**
 * Apollo Meta Queries
 *
 * Optimized meta queries using canonical keys only.
 * Provides query builders and index recommendations.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Meta_Queries
 *
 * Optimized meta query helpers.
 */
class Apollo_Meta_Queries {

	/**
	 * Cache group
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'apollo_meta_queries';

	/**
	 * Cache expiration (1 hour)
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 3600;

	// =========================================================================
	// EVENT QUERIES
	// =========================================================================

	/**
	 * Get upcoming events
	 *
	 * @param array $args Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_upcoming_events( array $args = array() ): array {
		$today = \current_time( 'Y-m-d' );

		$defaults = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'meta_query'     => array(
				'relation'    => 'AND',
				'date_clause' => array(
					'key'     => Apollo_Meta_Keys::EVENT_START_DATE,
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'orderby'        => array( 'date_clause' => 'ASC' ),
		);

		return self::execute_query( $defaults, $args, 'upcoming_events' );
	}

	/**
	 * Get events in date range
	 *
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @param array  $args       Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_events_in_range( string $start_date, string $end_date, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => Apollo_Meta_Keys::EVENT_START_DATE,
					'value'   => $end_date,
					'compare' => '<=',
					'type'    => 'DATE',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => Apollo_Meta_Keys::EVENT_END_DATE,
						'value'   => $start_date,
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => Apollo_Meta_Keys::EVENT_END_DATE,
						'compare' => 'NOT EXISTS',
					),
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => Apollo_Meta_Keys::EVENT_START_DATE,
			'order'          => 'ASC',
		);

		$cache_key = 'events_range_' . $start_date . '_' . $end_date;

		return self::execute_query( $defaults, $args, $cache_key );
	}

	/**
	 * Get featured events
	 *
	 * @param int   $limit Number of events.
	 * @param array $args  Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_featured_events( int $limit = 10, array $args = array() ): array {
		$today = \current_time( 'Y-m-d' );

		$defaults = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => Apollo_Meta_Keys::EVENT_FEATURED,
					'value' => '1',
				),
				array(
					'key'     => Apollo_Meta_Keys::EVENT_START_DATE,
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => Apollo_Meta_Keys::EVENT_START_DATE,
			'order'          => 'ASC',
		);

		return self::execute_query( $defaults, $args, 'featured_events_' . $limit );
	}

	/**
	 * Get events by location (geo query)
	 *
	 * @param float $lat      Center latitude.
	 * @param float $lng      Center longitude.
	 * @param float $radius   Radius in km.
	 * @param array $args     Optional WP_Query args.
	 * @return array WP_Post objects with distance.
	 */
	public static function get_events_by_location( float $lat, float $lng, float $radius = 50, array $args = array() ): array {
		global $wpdb;

		// Haversine formula for distance calculation.
		// Using raw SQL for performance on geo queries.
		$today = \current_time( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title,
				lat.meta_value AS latitude,
				lng.meta_value AS longitude,
				date_meta.meta_value AS start_date,
				(
					6371 * acos(
						cos(radians(%f)) * cos(radians(CAST(lat.meta_value AS DECIMAL(10,7))))
						* cos(radians(CAST(lng.meta_value AS DECIMAL(10,7))) - radians(%f))
						+ sin(radians(%f)) * sin(radians(CAST(lat.meta_value AS DECIMAL(10,7))))
					)
				) AS distance
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} lat ON p.ID = lat.post_id AND lat.meta_key = %s
			INNER JOIN {$wpdb->postmeta} lng ON p.ID = lng.post_id AND lng.meta_key = %s
			INNER JOIN {$wpdb->postmeta} date_meta ON p.ID = date_meta.post_id AND date_meta.meta_key = %s
			WHERE p.post_type = 'event_listing'
				AND p.post_status = 'publish'
				AND date_meta.meta_value >= %s
				AND lat.meta_value != ''
				AND lng.meta_value != ''
			HAVING distance <= %f
			ORDER BY distance ASC
			LIMIT 100",
				$lat,
				$lng,
				$lat,
				Apollo_Meta_Keys::EVENT_LATITUDE,
				Apollo_Meta_Keys::EVENT_LONGITUDE,
				Apollo_Meta_Keys::EVENT_START_DATE,
				$today,
				$radius
			)
		);

		// Get full post objects.
		$posts = array();
		foreach ( $results as $row ) {
			$post = \get_post( $row->ID );
			if ( $post ) {
				$post->distance = \round( (float) $row->distance, 2 );
				$posts[]        = $post;
			}
		}

		return $posts;
	}

	/**
	 * Get events with specific DJs
	 *
	 * @param array $dj_ids DJ post IDs.
	 * @param array $args   Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_events_with_djs( array $dj_ids, array $args = array() ): array {
		if ( empty( $dj_ids ) ) {
			return array();
		}

		// Build serialized value patterns for LIKE queries.
		$meta_query = array( 'relation' => 'OR' );

		foreach ( $dj_ids as $dj_id ) {
			// Match serialized array containing the ID.
			$meta_query[] = array(
				'key'     => Apollo_Meta_Keys::EVENT_DJ_IDS,
				'value'   => \sprintf( 'i:%d;', (int) $dj_id ),
				'compare' => 'LIKE',
			);
			$meta_query[] = array(
				'key'     => Apollo_Meta_Keys::EVENT_DJ_IDS,
				'value'   => \sprintf( '"%d"', (int) $dj_id ),
				'compare' => 'LIKE',
			);
		}

		$defaults = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value',
			'meta_key'       => Apollo_Meta_Keys::EVENT_START_DATE,
			'order'          => 'DESC',
		);

		$cache_key = 'events_djs_' . \md5( \implode( '_', $dj_ids ) );

		return self::execute_query( $defaults, $args, $cache_key );
	}

	/**
	 * Get events at venue
	 *
	 * @param int   $local_id Venue post ID.
	 * @param array $args     Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_events_at_venue( int $local_id, array $args = array() ): array {
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => Apollo_Meta_Keys::EVENT_LOCAL_IDS,
				'value'   => \sprintf( 'i:%d;', $local_id ),
				'compare' => 'LIKE',
			),
			array(
				'key'     => Apollo_Meta_Keys::EVENT_LOCAL_IDS,
				'value'   => \sprintf( '"%d"', $local_id ),
				'compare' => 'LIKE',
			),
		);

		$defaults = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value',
			'meta_key'       => Apollo_Meta_Keys::EVENT_START_DATE,
			'order'          => 'DESC',
		);

		return self::execute_query( $defaults, $args, 'events_venue_' . $local_id );
	}

	// =========================================================================
	// DJ QUERIES
	// =========================================================================

	/**
	 * Get DJs with social links
	 *
	 * @param string $platform Social platform (instagram, soundcloud, etc).
	 * @param array  $args     Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_djs_with_social( string $platform, array $args = array() ): array {
		$key_map = array(
			'instagram'        => Apollo_Meta_Keys::DJ_INSTAGRAM,
			'facebook'         => Apollo_Meta_Keys::DJ_FACEBOOK,
			'twitter'          => Apollo_Meta_Keys::DJ_TWITTER,
			'tiktok'           => Apollo_Meta_Keys::DJ_TIKTOK,
			'soundcloud'       => Apollo_Meta_Keys::DJ_SOUNDCLOUD,
			'mixcloud'         => Apollo_Meta_Keys::DJ_MIXCLOUD,
			'spotify'          => Apollo_Meta_Keys::DJ_SPOTIFY,
			'youtube'          => Apollo_Meta_Keys::DJ_YOUTUBE,
			'bandcamp'         => Apollo_Meta_Keys::DJ_BANDCAMP,
			'beatport'         => Apollo_Meta_Keys::DJ_BEATPORT,
			'resident_advisor' => Apollo_Meta_Keys::DJ_RESIDENT_ADVISOR,
		);

		$meta_key = $key_map[ $platform ] ?? null;

		if ( ! $meta_key ) {
			return array();
		}

		$defaults = array(
			'post_type'      => 'event_dj',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => $meta_key,
					'value'   => '',
					'compare' => '!=',
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		return self::execute_query( $defaults, $args, 'djs_social_' . $platform );
	}

	/**
	 * Search DJs by name
	 *
	 * @param string $search Search term.
	 * @param array  $args   Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function search_djs( string $search, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'event_dj',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'meta_query'     => array(
				array(
					'key'     => Apollo_Meta_Keys::DJ_NAME,
					'value'   => $search,
					'compare' => 'LIKE',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => Apollo_Meta_Keys::DJ_NAME,
			'order'          => 'ASC',
		);

		// Also search by post title.
		$defaults['_search_by_title'] = $search;

		return self::execute_query( $defaults, $args, null );
	}

	// =========================================================================
	// CLASSIFIED QUERIES
	// =========================================================================

	/**
	 * Get classifieds by price range
	 *
	 * @param float $min_price Minimum price.
	 * @param float $max_price Maximum price.
	 * @param array $args      Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_classifieds_by_price( float $min_price, float $max_price, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_classified',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation'     => 'AND',
				'price_clause' => array(
					'key'     => Apollo_Meta_Keys::CLASSIFIED_PRICE,
					'value'   => array( $min_price, $max_price ),
					'compare' => 'BETWEEN',
					'type'    => 'DECIMAL(10,2)',
				),
			),
			'orderby'        => array( 'price_clause' => 'ASC' ),
		);

		$cache_key = 'classifieds_price_' . $min_price . '_' . $max_price;

		return self::execute_query( $defaults, $args, $cache_key );
	}

	/**
	 * Get most viewed classifieds
	 *
	 * @param int   $limit Number to return.
	 * @param array $args  Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_popular_classifieds( int $limit = 10, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_classified',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => Apollo_Meta_Keys::CLASSIFIED_VIEWS,
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		);

		return self::execute_query( $defaults, $args, 'popular_classifieds_' . $limit );
	}

	/**
	 * Get classifieds by season
	 *
	 * @param int   $season_id Season term ID.
	 * @param array $args      Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_classifieds_by_season( int $season_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_classified',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => Apollo_Meta_Keys::CLASSIFIED_SEASON_ID,
					'value' => $season_id,
					'type'  => 'NUMERIC',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return self::execute_query( $defaults, $args, 'classifieds_season_' . $season_id );
	}

	// =========================================================================
	// SUPPLIER QUERIES
	// =========================================================================

	/**
	 * Get verified suppliers
	 *
	 * @param array $args Optional WP_Query args.
	 * @return array WP_Post objects.
	 */
	public static function get_verified_suppliers( array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_supplier',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => Apollo_Meta_Keys::SUPPLIER_VERIFIED,
					'value' => '1',
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		return self::execute_query( $defaults, $args, 'verified_suppliers' );
	}

	/**
	 * Get suppliers by location
	 *
	 * @param float $lat    Center latitude.
	 * @param float $lng    Center longitude.
	 * @param float $radius Radius in km.
	 * @return array WP_Post objects with distance.
	 */
	public static function get_suppliers_by_location( float $lat, float $lng, float $radius = 50 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID,
				(
					6371 * acos(
						cos(radians(%f)) * cos(radians(CAST(lat.meta_value AS DECIMAL(10,7))))
						* cos(radians(CAST(lng.meta_value AS DECIMAL(10,7))) - radians(%f))
						+ sin(radians(%f)) * sin(radians(CAST(lat.meta_value AS DECIMAL(10,7))))
					)
				) AS distance
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} lat ON p.ID = lat.post_id AND lat.meta_key = %s
			INNER JOIN {$wpdb->postmeta} lng ON p.ID = lng.post_id AND lng.meta_key = %s
			WHERE p.post_type = 'apollo_supplier'
				AND p.post_status = 'publish'
				AND lat.meta_value != ''
				AND lng.meta_value != ''
			HAVING distance <= %f
			ORDER BY distance ASC
			LIMIT 100",
				$lat,
				$lng,
				$lat,
				Apollo_Meta_Keys::SUPPLIER_LATITUDE,
				Apollo_Meta_Keys::SUPPLIER_LONGITUDE,
				$radius
			)
		);

		$posts = array();
		foreach ( $results as $row ) {
			$post = \get_post( $row->ID );
			if ( $post ) {
				$post->distance = \round( (float) $row->distance, 2 );
				$posts[]        = $post;
			}
		}

		return $posts;
	}

	// =========================================================================
	// UTILITY METHODS
	// =========================================================================

	/**
	 * Execute query with caching
	 *
	 * @param array       $defaults  Default args.
	 * @param array       $args      User args.
	 * @param string|null $cache_key Cache key (null to skip cache).
	 * @return array WP_Post objects.
	 */
	private static function execute_query( array $defaults, array $args, ?string $cache_key ): array {
		$query_args = \wp_parse_args( $args, $defaults );

		// Try cache first.
		if ( $cache_key ) {
			$full_cache_key = $cache_key . '_' . \md5( \serialize( $query_args ) );
			$cached         = \wp_cache_get( $full_cache_key, self::CACHE_GROUP );

			if ( false !== $cached ) {
				return $cached;
			}
		}

		$query   = new \WP_Query( $query_args );
		$results = $query->posts;

		// Cache results.
		if ( $cache_key ) {
			\wp_cache_set( $full_cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );
		}

		return $results;
	}

	/**
	 * Clear meta query cache
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		\wp_cache_flush_group( self::CACHE_GROUP );
	}

	/**
	 * Get recommended database indexes
	 *
	 * @return array SQL statements for indexes.
	 */
	public static function get_recommended_indexes(): array {
		global $wpdb;

		return array(
			'event_start_date'  => \sprintf(
				"CREATE INDEX idx_event_start_date ON %s (meta_key, meta_value(10)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::EVENT_START_DATE
			),
			'event_latitude'    => \sprintf(
				"CREATE INDEX idx_event_latitude ON %s (meta_key, meta_value(15)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::EVENT_LATITUDE
			),
			'event_longitude'   => \sprintf(
				"CREATE INDEX idx_event_longitude ON %s (meta_key, meta_value(15)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::EVENT_LONGITUDE
			),
			'event_featured'    => \sprintf(
				"CREATE INDEX idx_event_featured ON %s (meta_key, meta_value(1)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::EVENT_FEATURED
			),
			'classified_price'  => \sprintf(
				"CREATE INDEX idx_classified_price ON %s (meta_key, meta_value(10)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::CLASSIFIED_PRICE
			),
			'classified_views'  => \sprintf(
				"CREATE INDEX idx_classified_views ON %s (meta_key, meta_value(10)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::CLASSIFIED_VIEWS
			),
			'supplier_verified' => \sprintf(
				"CREATE INDEX idx_supplier_verified ON %s (meta_key, meta_value(1)) WHERE meta_key = '%s'",
				$wpdb->postmeta,
				Apollo_Meta_Keys::SUPPLIER_VERIFIED
			),
		);
	}

	/**
	 * Check if recommended indexes exist
	 *
	 * @return array Status of each index.
	 */
	public static function check_indexes(): array {
		global $wpdb;

		$status = array();

		// Get existing indexes on postmeta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta}" );

		$existing_names = \array_column( $indexes, 'Key_name' );

		foreach ( \array_keys( self::get_recommended_indexes() ) as $name ) {
			$index_name      = 'idx_' . $name;
			$status[ $name ] = \in_array( $index_name, $existing_names, true );
		}

		return $status;
	}
}

// Hook cache invalidation.
\add_action(
	'save_post',
	function ( int $post_id ): void {
		$post_type = \get_post_type( $post_id );

		$apollo_types = array(
			'event_listing',
			'event_dj',
			'event_local',
			'apollo_classified',
			'apollo_supplier',
		);

		if ( \in_array( $post_type, $apollo_types, true ) ) {
			Apollo_Meta_Queries::clear_cache();
		}
	},
	100
);

\add_action(
	'deleted_post',
	function ( int $post_id ): void {
		Apollo_Meta_Queries::clear_cache();
	},
	100
);
