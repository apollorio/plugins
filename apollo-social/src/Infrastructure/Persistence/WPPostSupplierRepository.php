<?php
/**
 * WP Post Supplier Repository
 *
 * WordPress Custom Post Type implementation of the Supplier Repository.
 * Handles all database operations using WordPress APIs.
 *
 * @package Apollo\Infrastructure\Persistence
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Infrastructure\Persistence;

use Apollo\Domain\Suppliers\Supplier;
use Apollo\Domain\Suppliers\SupplierRepositoryInterface;
use Apollo\Domain\Suppliers\SupplierService;
use WP_Query;

/**
 * Class WPPostSupplierRepository
 *
 * @since 1.0.0
 */
class WPPostSupplierRepository implements SupplierRepositoryInterface {

	/**
	 * Post type name.
	 */
	public const POST_TYPE = 'apollo_supplier';

	/**
	 * Category taxonomy.
	 */
	public const TAX_CATEGORY = 'apollo_supplier_category';

	/**
	 * Region taxonomy.
	 */
	public const TAX_REGION = 'apollo_supplier_region';

	/**
	 * Neighborhood taxonomy.
	 */
	public const TAX_NEIGHBORHOOD = 'apollo_supplier_neighborhood';

	/**
	 * Event type taxonomy.
	 */
	public const TAX_EVENT_TYPE = 'apollo_supplier_event_type';

	/**
	 * Supplier type taxonomy.
	 */
	public const TAX_TYPE = 'apollo_supplier_type';

	/**
	 * Mode taxonomy.
	 */
	public const TAX_MODE = 'apollo_supplier_mode';

	/**
	 * Badge taxonomy.
	 */
	public const TAX_BADGE = 'apollo_supplier_badge';

	/**
	 * Meta key prefix.
	 */
	public const META_PREFIX = '_apollo_supplier_';

	/**
	 * Find a supplier by ID.
	 *
	 * @param int $supplier_id The ID of the supplier.
	 *
	 * @return Supplier|null The supplier object or null if not found.
	 *
	 * @since 1.0.0
	 */
	public function find( int $supplier_id ): ?Supplier {
		$post = get_post( $supplier_id );

		if ( null === $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return $this->map_post_to_supplier( $post );
	}

	/**
	 * Get all suppliers.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return array<Supplier> Array of Supplier objects.
	 *
	 * @since 1.0.0
	 */
	public function find_all( array $args = array() ): array {
		$query_args = $this->build_query_args( $args );
		$query      = new WP_Query( $query_args );
		$suppliers  = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$suppliers[] = $this->map_post_to_supplier( $post );
			}
		}

		wp_reset_postdata();

		return $suppliers;
	}

	/**
	 * Get suppliers by category.
	 *
	 * @param string $category Category slug.
	 *
	 * @return array<Supplier> Array of Supplier objects.
	 *
	 * @since 1.0.0
	 */
	public function find_by_category( string $category ): array {
		return $this->find_all( array( 'category' => $category ) );
	}

	/**
	 * Get suppliers by region.
	 *
	 * @param string $region Region slug.
	 *
	 * @return array<Supplier> Array of Supplier objects.
	 *
	 * @since 1.0.0
	 */
	public function find_by_region( string $region ): array {
		return $this->find_all( array( 'region' => $region ) );
	}

	/**
	 * Create a new supplier.
	 *
	 * @param array<string, mixed> $data Supplier data.
	 *
	 * @return int|false The new supplier ID or false on failure.
	 *
	 * @since 1.0.0
	 */
	public function create( array $data ) {
		$post_data = array(
			'post_type'    => self::POST_TYPE,
			'post_title'   => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'post_content' => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'post_status'  => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'pending',
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) || 0 === $post_id ) {
			return false;
		}

		$this->save_meta( $post_id, $data );
		$this->save_taxonomies( $post_id, $data );

		// Handle featured image.
		if ( ! empty( $data['logo_attachment_id'] ) ) {
			set_post_thumbnail( $post_id, absint( $data['logo_attachment_id'] ) );
		}

		return $post_id;
	}

	/**
	 * Update an existing supplier.
	 *
	 * @param int                  $supplier_id The supplier ID.
	 * @param array<string, mixed> $data        Updated data.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function update( int $supplier_id, array $data ): bool {
		$post = get_post( $supplier_id );

		if ( null === $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		$post_data = array( 'ID' => $supplier_id );

		if ( isset( $data['name'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $data['name'] );
		}

		if ( isset( $data['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $data['description'] );
		}

		if ( isset( $data['status'] ) ) {
			$post_data['post_status'] = sanitize_key( $data['status'] );
		}

		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		$this->save_meta( $supplier_id, $data );
		$this->save_taxonomies( $supplier_id, $data );

		// Handle featured image.
		if ( isset( $data['logo_attachment_id'] ) ) {
			if ( $data['logo_attachment_id'] > 0 ) {
				set_post_thumbnail( $supplier_id, absint( $data['logo_attachment_id'] ) );
			} else {
				delete_post_thumbnail( $supplier_id );
			}
		}

		return true;
	}

	/**
	 * Delete a supplier.
	 *
	 * @param int $supplier_id The supplier ID.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function delete( int $supplier_id ): bool {
		$post = get_post( $supplier_id );

		if ( null === $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		$result = wp_delete_post( $supplier_id, true );

		return false !== $result;
	}

	/**
	 * Count suppliers.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return int Total count.
	 *
	 * @since 1.0.0
	 */
	public function count( array $args = array() ): int {
		$query_args                   = $this->build_query_args( $args );
		$query_args['posts_per_page'] = -1;
		$query_args['fields']         = 'ids';

		$query = new WP_Query( $query_args );

		return $query->found_posts;
	}

	/**
	 * Get filter options with counts.
	 *
	 * @return array<string, array<string, mixed>> Filter options grouped by type.
	 *
	 * @since 1.0.0
	 */
	public function get_filter_options(): array {
		$options = array();

		// Categories.
		$options['categories'] = $this->get_taxonomy_options( self::TAX_CATEGORY );

		// Regions.
		$options['regions'] = $this->get_taxonomy_options( self::TAX_REGION );

		// Event types.
		$options['event_types'] = $this->get_taxonomy_options( self::TAX_EVENT_TYPE );

		// Supplier types.
		$options['supplier_types'] = $this->get_taxonomy_options( self::TAX_TYPE );

		// Modes.
		$options['modes'] = $this->get_taxonomy_options( self::TAX_MODE );

		// Badges.
		$options['badges'] = $this->get_taxonomy_options( self::TAX_BADGE );

		// Price tiers.
		$options['price_tiers'] = $this->get_price_tier_options();

		return $options;
	}

	/**
	 * Build WP_Query arguments from filter parameters.
	 *
	 * @param array<string, mixed> $args Filter parameters.
	 *
	 * @return array<string, mixed> WP_Query arguments.
	 *
	 * @since 1.0.0
	 */
	private function build_query_args( array $args ): array {
		$query_args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => isset( $args['post_status'] ) ? $args['post_status'] : 'publish',
			'posts_per_page' => isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( isset( $args['paged'] ) ) {
			$query_args['paged'] = $args['paged'];
		}

		// Build taxonomy query.
		$tax_query = array();

		if ( ! empty( $args['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_CATEGORY,
				'field'    => 'slug',
				'terms'    => $args['category'],
			);
		}

		if ( ! empty( $args['region'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_REGION,
				'field'    => 'slug',
				'terms'    => $args['region'],
			);
		}

		if ( ! empty( $args['event_type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_EVENT_TYPE,
				'field'    => 'slug',
				'terms'    => $args['event_type'],
			);
		}

		if ( ! empty( $args['supplier_type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_TYPE,
				'field'    => 'slug',
				'terms'    => $args['supplier_type'],
			);
		}

		if ( ! empty( $args['mode'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_MODE,
				'field'    => 'slug',
				'terms'    => $args['mode'],
			);
		}

		if ( ! empty( $args['badge'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_BADGE,
				'field'    => 'slug',
				'terms'    => $args['badge'],
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation']   = 'AND';
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		// Build meta query.
		$meta_query = array();

		if ( ! empty( $args['price_tier'] ) ) {
			$meta_query[] = array(
				'key'     => self::META_PREFIX . 'price_tier',
				'value'   => $args['price_tier'],
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation']   = 'AND';
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Search.
		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = $args['search'];
		}

		// Ordering.
		if ( ! empty( $args['orderby'] ) ) {
			if ( 'rating' === $args['orderby'] ) {
				$query_args['meta_key'] = self::META_PREFIX . 'rating_avg'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
			} elseif ( 'reviews' === $args['orderby'] ) {
				$query_args['meta_key'] = self::META_PREFIX . 'reviews_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
			} else {
				$query_args['orderby'] = $args['orderby'];
			}
		}

		if ( ! empty( $args['order'] ) ) {
			$query_args['order'] = $args['order'];
		}

		return $query_args;
	}

	/**
	 * Map WP_Post to Supplier entity.
	 *
	 * @param \WP_Post $post WordPress post object.
	 *
	 * @return Supplier
	 *
	 * @since 1.0.0
	 */
	private function map_post_to_supplier( \WP_Post $post ): Supplier {
		$data = array(
			'id'          => $post->ID,
			'name'        => $post->post_title,
			'description' => $post->post_content,
			'status'      => $post->post_status,
		);

		// Get thumbnail.
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$data['logo_url'] = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
		}

		// Get banner.
		$banner_id = get_post_meta( $post->ID, self::META_PREFIX . 'banner_id', true );
		if ( $banner_id ) {
			$data['banner_url'] = wp_get_attachment_image_url( $banner_id, 'large' );
		}

		// Get taxonomies.
		$categories = wp_get_post_terms( $post->ID, self::TAX_CATEGORY );
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$data['category']       = $categories[0]->slug;
			$data['category_label'] = $categories[0]->name;

			// Check for subcategory (child term).
			if ( $categories[0]->parent > 0 ) {
				$data['subcategory'] = $categories[0]->slug;
				$parent              = get_term( $categories[0]->parent, self::TAX_CATEGORY );
				if ( ! is_wp_error( $parent ) ) {
					$data['category']       = $parent->slug;
					$data['category_label'] = $parent->name;
				}
			}
		}

		$regions = wp_get_post_terms( $post->ID, self::TAX_REGION );
		if ( ! is_wp_error( $regions ) && ! empty( $regions ) ) {
			$data['region'] = $regions[0]->slug;
		}

		$neighborhoods = wp_get_post_terms( $post->ID, self::TAX_NEIGHBORHOOD );
		if ( ! is_wp_error( $neighborhoods ) && ! empty( $neighborhoods ) ) {
			$data['neighborhood'] = $neighborhoods[0]->name;
		}

		$event_types = wp_get_post_terms( $post->ID, self::TAX_EVENT_TYPE );
		if ( ! is_wp_error( $event_types ) && ! empty( $event_types ) ) {
			$data['event_types'] = wp_list_pluck( $event_types, 'slug' );
		}

		$supplier_types = wp_get_post_terms( $post->ID, self::TAX_TYPE );
		if ( ! is_wp_error( $supplier_types ) && ! empty( $supplier_types ) ) {
			$data['supplier_type'] = $supplier_types[0]->slug;
		}

		$modes = wp_get_post_terms( $post->ID, self::TAX_MODE );
		if ( ! is_wp_error( $modes ) && ! empty( $modes ) ) {
			$data['modes'] = wp_list_pluck( $modes, 'slug' );
		}

		$badges = wp_get_post_terms( $post->ID, self::TAX_BADGE );
		if ( ! is_wp_error( $badges ) && ! empty( $badges ) ) {
			$data['badges'] = wp_list_pluck( $badges, 'slug' );
		}

		// Get all tags from all taxonomies for display.
		$all_tags = array();
		if ( ! empty( $data['category_label'] ) ) {
			$all_tags[] = $data['category_label'];
		}
		foreach ( $event_types as $term ) {
			if ( ! is_wp_error( $term ) ) {
				$all_tags[] = $term->name;
			}
		}
		foreach ( $modes as $term ) {
			if ( ! is_wp_error( $term ) ) {
				$all_tags[] = $term->name;
			}
		}
		$data['tags'] = array_slice( $all_tags, 0, 4 );

		// Get meta values.
		$meta_keys = array(
			'price_tier',
			'capacity_max',
			'contact_email',
			'contact_phone',
			'contact_whatsapp',
			'contact_instagram',
			'contact_website',
			'linked_user_id',
			'rating_avg',
			'reviews_count',
			'is_verified',
		);

		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $post->ID, self::META_PREFIX . $key, true );
			if ( '' !== $value ) {
				$data[ $key ] = $value;
			}
		}

		return new Supplier( $data );
	}

	/**
	 * Save supplier meta data.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Data array.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function save_meta( int $post_id, array $data ): void {
		$meta_fields = array(
			'price_tier'        => 'absint',
			'capacity_max'      => 'absint',
			'contact_email'     => 'sanitize_email',
			'contact_phone'     => 'sanitize_text_field',
			'contact_whatsapp'  => 'sanitize_text_field',
			'contact_instagram' => 'sanitize_text_field',
			'contact_website'   => 'esc_url_raw',
			'linked_user_id'    => 'absint',
			'rating_avg'        => 'floatval',
			'reviews_count'     => 'absint',
			'is_verified'       => 'rest_sanitize_boolean',
			'banner_id'         => 'absint',
		);

		foreach ( $meta_fields as $key => $sanitize_callback ) {
			if ( isset( $data[ $key ] ) ) {
				$value = call_user_func( $sanitize_callback, $data[ $key ] );
				update_post_meta( $post_id, self::META_PREFIX . $key, $value );
			}
		}
	}

	/**
	 * Save supplier taxonomies.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Data array.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function save_taxonomies( int $post_id, array $data ): void {
		// Category.
		if ( isset( $data['category'] ) ) {
			wp_set_object_terms( $post_id, sanitize_key( $data['category'] ), self::TAX_CATEGORY );
		}

		// Region.
		if ( isset( $data['region'] ) ) {
			wp_set_object_terms( $post_id, sanitize_key( $data['region'] ), self::TAX_REGION );
		}

		// Neighborhood.
		if ( isset( $data['neighborhood'] ) ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $data['neighborhood'] ), self::TAX_NEIGHBORHOOD );
		}

		// Event types (multiple).
		if ( isset( $data['event_types'] ) && is_array( $data['event_types'] ) ) {
			$terms = array_map( 'sanitize_key', $data['event_types'] );
			wp_set_object_terms( $post_id, $terms, self::TAX_EVENT_TYPE );
		}

		// Supplier type.
		if ( isset( $data['supplier_type'] ) ) {
			wp_set_object_terms( $post_id, sanitize_key( $data['supplier_type'] ), self::TAX_TYPE );
		}

		// Modes (multiple).
		if ( isset( $data['modes'] ) && is_array( $data['modes'] ) ) {
			$terms = array_map( 'sanitize_key', $data['modes'] );
			wp_set_object_terms( $post_id, $terms, self::TAX_MODE );
		}

		// Badges (multiple).
		if ( isset( $data['badges'] ) && is_array( $data['badges'] ) ) {
			$terms = array_map( 'sanitize_key', $data['badges'] );
			wp_set_object_terms( $post_id, $terms, self::TAX_BADGE );
		}
	}

	/**
	 * Get taxonomy options with counts.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array<string, mixed>
	 *
	 * @since 1.0.0
	 */
	private function get_taxonomy_options( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array( 'options' => array() );
		}

		$options = array();
		foreach ( $terms as $term ) {
			$options[] = array(
				'value' => $term->slug,
				'label' => $term->name,
				'count' => $term->count,
			);
		}

		return array( 'options' => $options );
	}

	/**
	 * Get price tier options with counts.
	 *
	 * @return array<string, mixed>
	 *
	 * @since 1.0.0
	 */
	private function get_price_tier_options(): array {
		global $wpdb;

		$options = array();

		for ( $tier = 1; $tier <= 3; $tier++ ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s AND pm.meta_value = %s
					AND p.post_type = %s AND p.post_status = 'publish'",
					self::META_PREFIX . 'price_tier',
					$tier,
					self::POST_TYPE
				)
			);

			$options[] = array(
				'value' => (string) $tier,
				'label' => \str_repeat( '$', $tier ),
				'count' => (int) $count,
			);
		}

		return array( 'options' => $options );
	}
}
