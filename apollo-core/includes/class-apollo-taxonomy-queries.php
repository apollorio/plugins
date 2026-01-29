<?php
/**
 * Apollo Taxonomy Queries
 *
 * Unified query helpers for cross-content taxonomy queries.
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Provides:
 * - Cross-CPT taxonomy queries (e.g., get all content by season)
 * - Optimized term queries
 * - Related content lookups
 */

declare(strict_types=1);

namespace Apollo_Core\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Taxonomy_Queries
 *
 * Static helper methods for taxonomy queries across CPTs.
 */
class Apollo_Taxonomy_Queries {

	/**
	 * Cache group
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'apollo_taxonomy_queries';

	/**
	 * Cache expiration in seconds (1 hour)
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 3600;

	/**
	 * Get events by season
	 *
	 * @param int   $season_id Season term ID.
	 * @param array $args      Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_events_by_season( int $season_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'event_season',
					'field'    => 'term_id',
					'terms'    => $season_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );
		$cache_key  = 'events_season_' . $season_id . '_' . \md5( \serialize( $query_args ) );
		$cached     = \wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$query   = new \WP_Query( $query_args );
		$results = $query->posts;

		\wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $results;
	}

	/**
	 * Get classifieds by season
	 *
	 * @param int   $season_id Season term ID.
	 * @param array $args      Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_classifieds_by_season( int $season_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_classified',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'event_season',
					'field'    => 'term_id',
					'terms'    => $season_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );
		$cache_key  = 'classifieds_season_' . $season_id . '_' . \md5( \serialize( $query_args ) );
		$cached     = \wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$query   = new \WP_Query( $query_args );
		$results = $query->posts;

		\wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $results;
	}

	/**
	 * Get all content by season (events + classifieds)
	 *
	 * @param int   $season_id Season term ID.
	 * @param array $args      Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_all_by_season( int $season_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => array( 'event_listing', 'apollo_classified' ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'event_season',
					'field'    => 'term_id',
					'terms'    => $season_id,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query_args = \wp_parse_args( $args, $defaults );
		$cache_key  = 'all_season_' . $season_id . '_' . \md5( \serialize( $query_args ) );
		$cached     = \wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$query   = new \WP_Query( $query_args );
		$results = $query->posts;

		\wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $results;
	}

	/**
	 * Get cross-content by taxonomy
	 *
	 * Generic method to query any shared taxonomy across multiple CPTs.
	 *
	 * @param string $taxonomy   Taxonomy slug.
	 * @param int    $term_id    Term ID.
	 * @param array  $post_types Array of post types to query.
	 * @param array  $args       Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_cross_content_by_taxonomy(
		string $taxonomy,
		int $term_id,
		array $post_types = array(),
		array $args = array()
	): array {
		// If no post types specified, get all attached to this taxonomy.
		if ( empty( $post_types ) ) {
			$taxonomy_obj = \get_taxonomy( $taxonomy );
			if ( $taxonomy_obj ) {
				$post_types = $taxonomy_obj->object_type;
			}
		}

		if ( empty( $post_types ) ) {
			return array();
		}

		$defaults = array(
			'post_type'      => $post_types,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );
		$cache_key  = 'cross_' . $taxonomy . '_' . $term_id . '_' . \md5( \serialize( $query_args ) );
		$cached     = \wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$query   = new \WP_Query( $query_args );
		$results = $query->posts;

		\wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $results;
	}

	/**
	 * Get suppliers by category
	 *
	 * @param int   $category_id Category term ID.
	 * @param array $args        Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_suppliers_by_category( int $category_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_supplier',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'apollo_supplier_category',
					'field'    => 'term_id',
					'terms'    => $category_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );

		return ( new \WP_Query( $query_args ) )->posts;
	}

	/**
	 * Get suppliers by region
	 *
	 * @param int   $region_id Region term ID.
	 * @param array $args      Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_suppliers_by_region( int $region_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'apollo_supplier',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'apollo_supplier_region',
					'field'    => 'term_id',
					'terms'    => $region_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );

		return ( new \WP_Query( $query_args ) )->posts;
	}

	/**
	 * Get DJs by sound/genre
	 *
	 * @param int   $sound_id Sound/genre term ID.
	 * @param array $args     Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_djs_by_sound( int $sound_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'event_dj',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'event_sounds',
					'field'    => 'term_id',
					'terms'    => $sound_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );

		return ( new \WP_Query( $query_args ) )->posts;
	}

	/**
	 * Get events by sound/genre
	 *
	 * @param int   $sound_id Sound/genre term ID.
	 * @param array $args     Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_events_by_sound( int $sound_id, array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'event_sounds',
					'field'    => 'term_id',
					'terms'    => $sound_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );

		return ( new \WP_Query( $query_args ) )->posts;
	}

	/**
	 * Get related content by shared terms
	 *
	 * Find content that shares terms with a given post.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $taxonomy  Taxonomy to check.
	 * @param array  $args      Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_related_by_taxonomy( int $post_id, string $taxonomy, array $args = array() ): array {
		$terms = \wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( \is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$post      = \get_post( $post_id );
		$post_type = $post ? $post->post_type : 'post';

		// Get all post types attached to this taxonomy for cross-content.
		$taxonomy_obj = \get_taxonomy( $taxonomy );
		$post_types   = $taxonomy_obj ? $taxonomy_obj->object_type : array( $post_type );

		$defaults = array(
			'post_type'      => $post_types,
			'posts_per_page' => 10,
			'post_status'    => 'publish',
			'post__not_in'   => array( $post_id ),
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $terms,
				),
			),
			'orderby'        => 'rand',
		);

		$query_args = \wp_parse_args( $args, $defaults );
		$cache_key  = 'related_' . $post_id . '_' . $taxonomy . '_' . \md5( \serialize( $query_args ) );
		$cached     = \wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$query   = new \WP_Query( $query_args );
		$results = $query->posts;

		\wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $results;
	}

	/**
	 * Get term counts across post types
	 *
	 * @param string $taxonomy   Taxonomy slug.
	 * @param array  $post_types Post types to count.
	 * @return array Array of term data with counts per post type.
	 */
	public static function get_term_counts_by_post_type( string $taxonomy, array $post_types = array() ): array {
		global $wpdb;

		if ( empty( $post_types ) ) {
			$taxonomy_obj = \get_taxonomy( $taxonomy );
			if ( $taxonomy_obj ) {
				$post_types = $taxonomy_obj->object_type;
			}
		}

		if ( empty( $post_types ) ) {
			return array();
		}

		$cache_key = 'term_counts_' . $taxonomy . '_' . \md5( \serialize( $post_types ) );
		$cached    = \wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$results = array();

		$terms = \get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( \is_wp_error( $terms ) ) {
			return array();
		}

		foreach ( $terms as $term ) {
			$term_data = array(
				'term_id' => $term->term_id,
				'name'    => $term->name,
				'slug'    => $term->slug,
				'counts'  => array(),
				'total'   => 0,
			);

			foreach ( $post_types as $post_type ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT p.ID)
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE p.post_type = %s
					AND p.post_status = 'publish'
					AND tt.term_id = %d
					AND tt.taxonomy = %s",
						$post_type,
						$term->term_id,
						$taxonomy
					)
				);

				$term_data['counts'][ $post_type ] = (int) $count;
				$term_data['total']               += (int) $count;
			}

			$results[] = $term_data;
		}

		\wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $results;
	}

	/**
	 * Get season archive data
	 *
	 * Returns season terms with event and classified counts.
	 *
	 * @return array Season data with counts.
	 */
	public static function get_season_archive(): array {
		return self::get_term_counts_by_post_type(
			'event_season',
			array( 'event_listing', 'apollo_classified' )
		);
	}

	/**
	 * Get sounds/genre archive data
	 *
	 * Returns sound terms with event and DJ counts.
	 *
	 * @return array Sound data with counts.
	 */
	public static function get_sounds_archive(): array {
		return self::get_term_counts_by_post_type(
			'event_sounds',
			array( 'event_listing', 'event_dj' )
		);
	}

	/**
	 * Search content by term
	 *
	 * Search within a specific taxonomy term.
	 *
	 * @param string $search_term Search term.
	 * @param string $taxonomy    Taxonomy slug.
	 * @param int    $term_id     Term ID to search within.
	 * @param array  $args        Optional WP_Query args.
	 * @return array Array of WP_Post objects.
	 */
	public static function search_in_term(
		string $search_term,
		string $taxonomy,
		int $term_id,
		array $args = array()
	): array {
		$taxonomy_obj = \get_taxonomy( $taxonomy );
		$post_types   = $taxonomy_obj ? $taxonomy_obj->object_type : array();

		$defaults = array(
			'post_type'      => $post_types,
			's'              => $search_term,
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);

		$query_args = \wp_parse_args( $args, $defaults );

		return ( new \WP_Query( $query_args ) )->posts;
	}

	/**
	 * Clear taxonomy query cache
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		\wp_cache_flush_group( self::CACHE_GROUP );
	}

	/**
	 * Clear cache on term changes
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public static function maybe_clear_cache( int $term_id, int $tt_id, string $taxonomy ): void {
		// Only clear for Apollo-related taxonomies.
		$apollo_taxonomies = array(
			'event_season',
			'event_sounds',
			'event_listing_category',
			'event_listing_type',
			'classified_domain',
			'classified_intent',
			'apollo_supplier_category',
			'apollo_supplier_region',
		);

		if ( \in_array( $taxonomy, $apollo_taxonomies, true ) ) {
			self::clear_cache();
		}
	}
}

// Hook cache invalidation.
\add_action( 'created_term', array( Apollo_Taxonomy_Queries::class, 'maybe_clear_cache' ), 10, 3 );
\add_action( 'edited_term', array( Apollo_Taxonomy_Queries::class, 'maybe_clear_cache' ), 10, 3 );
\add_action( 'delete_term', array( Apollo_Taxonomy_Queries::class, 'maybe_clear_cache' ), 10, 3 );

// Clear cache on post save (term relationships may change).
\add_action(
	'save_post',
	function ( int $post_id ): void {
		$post_type = \get_post_type( $post_id );

		$apollo_post_types = array(
			'event_listing',
			'apollo_classified',
			'apollo_supplier',
			'event_dj',
		);

		if ( \in_array( $post_type, $apollo_post_types, true ) ) {
			Apollo_Taxonomy_Queries::clear_cache();
		}
	},
	100
);
