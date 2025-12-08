<?php

namespace Apollo\Infrastructure\Adapters;

/**
 * WPAdverts Adapter
 *
 * Read-only adapter for WPAdverts plugin
 * Does NOT modify WPAdverts core - uses public APIs only
 *
 * @package Apollo\Infrastructure\Adapters
 */
class WPAdvertsAdapter {

	/**
	 * Check if WPAdverts is active
	 */
	public static function isActive(): bool {
		return class_exists( 'Adverts' ) || function_exists( 'adverts_config' );
	}

	/**
	 * List ads with filters
	 *
	 * @param array $args Query arguments
	 * @return array List of ads
	 */
	public static function listAds( array $args = [] ): array {
		if ( ! self::isActive() ) {
			return [];
		}

		$defaults = [
			'post_type'      => 'advert',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => 1,
		];

		$query_args = wp_parse_args( $args, $defaults );

		$query = new \WP_Query( $query_args );

		$ads = [];
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$ads[] = self::getAd( get_the_ID() );
			}
			wp_reset_postdata();
		}

		return [
			'ads'   => $ads,
			'total' => $query->found_posts,
			'pages' => $query->max_num_pages,
		];
	}

	/**
	 * Get single ad by ID
	 *
	 * @param int $ad_id Ad post ID
	 * @return array|null Ad data or null if not found
	 */
	public static function getAd( int $ad_id ): ?array {
		if ( ! self::isActive() ) {
			return null;
		}

		$post = get_post( $ad_id );
		if ( ! $post || $post->post_type !== 'advert' ) {
			return null;
		}

		// Get ad meta (WPAdverts stores data in post meta)
		$meta = get_post_meta( $ad_id );

		// Extract common fields
		$ad = [
			'id'          => $ad_id,
			'title'       => get_the_title( $ad_id ),
			'content'     => get_the_content( null, false, $ad_id ),
			'permalink'   => get_permalink( $ad_id ),
			'author_id'   => $post->post_author,
			'author_name' => get_the_author_meta( 'display_name', $post->post_author ),
			'date'        => $post->post_date,
			'modified'    => $post->post_modified,
			'status'      => $post->post_status,
		];

		// Extract WPAdverts specific meta
		$ad['price']    = isset( $meta['adverts_price'] ) ? $meta['adverts_price'][0] : null;
		$ad['location'] = isset( $meta['adverts_location'] ) ? $meta['adverts_location'][0] : null;
		$ad['gallery']  = isset( $meta['adverts_gallery'] ) ? maybe_unserialize( $meta['adverts_gallery'][0] ) : [];
		$ad['category'] = isset( $meta['adverts_category'] ) ? $meta['adverts_category'][0] : null;

		// Get featured image
		if ( has_post_thumbnail( $ad_id ) ) {
			$ad['thumbnail']       = get_the_post_thumbnail_url( $ad_id, 'medium' );
			$ad['thumbnail_large'] = get_the_post_thumbnail_url( $ad_id, 'large' );
		}

		// Get all meta for flexibility
		$ad['meta'] = [];
		foreach ( $meta as $key => $value ) {
			if ( strpos( $key, 'adverts_' ) === 0 ) {
				$ad['meta'][ $key ] = maybe_unserialize( $value[0] );
			}
		}

		return $ad;
	}

	/**
	 * Check if current user can perform action
	 *
	 * @param string $capability Capability to check
	 * @return bool
	 */
	public static function currentUserCan( string $capability ): bool {
		if ( ! self::isActive() ) {
			return false;
		}

		// Respect WPAdverts capabilities
		// Common capabilities: 'publish_adverts', 'edit_adverts', 'delete_adverts'
		return current_user_can( $capability );
	}

	/**
	 * Get ad categories
	 *
	 * @return array List of categories
	 */
	public static function getCategories(): array {
		if ( ! self::isActive() ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'advert_category',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$categories = [];
		foreach ( $terms as $term ) {
			$categories[] = [
				'id'    => $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => $term->count,
			];
		}

		return $categories;
	}
}
