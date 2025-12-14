<?php

/**
 * SEO Handler for Apollo content types
 *
 * Outputs meta tags (title, description, Open Graph, Twitter Cards) for
 * Apollo routes and CPTs based on admin settings.
 *
 * @package Apollo_Rio
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_SEO_Handler
 *
 * Handles SEO meta tag output for Apollo content types.
 */
class Apollo_SEO_Handler {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_SEO_Handler|null
	 */
	private static ?Apollo_SEO_Handler $instance = null;

	/**
	 * Current content type being viewed.
	 *
	 * @var string|null
	 */
	private ?string $current_type = null;

	/**
	 * SEO data for current page.
	 *
	 * @var array
	 */
	private array $seo_data = [];

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_SEO_Handler
	 */
	public static function get_instance(): Apollo_SEO_Handler {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor for singleton pattern.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks(): void {
		// Hook early to detect content type.
		add_action( 'template_redirect', [ $this, 'detect_content_type' ], 5 );
		// Output SEO tags in wp_head.
		add_action( 'wp_head', [ $this, 'output_seo_tags' ], 1 );
		// Filter document title parts.
		add_filter( 'document_title_parts', [ $this, 'filter_title_parts' ], 20 );
	}

	/**
	 * Detect the current content type being viewed.
	 */
	public function detect_content_type(): void {
		// Check Apollo Social routes first.
		$apollo_route = get_query_var( 'apollo_route', '' );
		$apollo_type  = get_query_var( 'apollo_type', '' );
		$apollo_param = get_query_var( 'apollo_param', '' );

		if ( 'user' === $apollo_route ) {
			$this->current_type = 'user';
			$this->seo_data     = $this->get_user_seo_data( $apollo_param );

			return;
		}

		if ( 'group_single' === $apollo_route && in_array( $apollo_type, [ 'comunidade', 'nucleo', 'season' ], true ) ) {
			// Map 'season' to the appropriate admin setting key.
			$this->current_type = ( 'season' === $apollo_type ) ? 'comunidade' : $apollo_type;
			$this->seo_data     = $this->get_group_seo_data( $apollo_param, $apollo_type );

			return;
		}

		if ( 'ad_single' === $apollo_route ) {
			$this->current_type = 'classifieds';
			$this->seo_data     = $this->get_classified_seo_data( $apollo_param );

			return;
		}

		// Check Event CPTs.
		if ( is_singular( 'event_listing' ) ) {
			$this->current_type = 'event';
			$this->seo_data     = $this->get_event_seo_data( get_queried_object_id() );

			return;
		}

		if ( is_singular( 'event_dj' ) ) {
			$this->current_type = 'dj';
			$this->seo_data     = $this->get_dj_seo_data( get_queried_object_id() );

			return;
		}

		if ( is_singular( 'event_local' ) ) {
			$this->current_type = 'local';
			$this->seo_data     = $this->get_local_seo_data( get_queried_object_id() );

			return;
		}
	}

	/**
	 * Output SEO meta tags.
	 */
	public function output_seo_tags(): void {
		if ( empty( $this->current_type ) || empty( $this->seo_data ) ) {
			return;
		}

		// Check if SEO is enabled for this content type.
		if ( ! apollo_is_seo_enabled( $this->current_type ) ) {
			return;
		}

		$title       = $this->seo_data['title'] ?? '';
		$description = $this->seo_data['description'] ?? '';
		$image       = $this->seo_data['image'] ?? '';
		$url         = $this->seo_data['url'] ?? '';
		$type        = $this->seo_data['og_type'] ?? 'website';

		// Meta description.
		if ( ! empty( $description ) ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}

		// Open Graph tags.
		if ( ! empty( $title ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		}
		if ( ! empty( $description ) ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		if ( ! empty( $image ) ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}
		if ( ! empty( $url ) ) {
			echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		}
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:locale" content="pt_BR">' . "\n";

		// Twitter Card tags.
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		if ( ! empty( $title ) ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		}
		if ( ! empty( $description ) ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		if ( ! empty( $image ) ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		}

		// Canonical URL.
		if ( ! empty( $url ) ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
	}

	/**
	 * Filter document title parts.
	 *
	 * @param array $title_parts Title parts array.
	 * @return array Modified title parts.
	 */
	public function filter_title_parts( array $title_parts ): array {
		if ( empty( $this->current_type ) || empty( $this->seo_data['title'] ) ) {
			return $title_parts;
		}

		if ( ! apollo_is_seo_enabled( $this->current_type ) ) {
			return $title_parts;
		}

		$title_parts['title'] = $this->seo_data['title'];

		return $title_parts;
	}

	/**
	 * Get SEO data for a user profile.
	 *
	 * @param string $user_param User ID or login.
	 * @return array SEO data.
	 */
	private function get_user_seo_data( string $user_param ): array {
		if ( empty( $user_param ) ) {
			return [];
		}

		// Try to get user by ID first, then by login.
		$user = is_numeric( $user_param ) ? get_user_by( 'id', (int) $user_param ) : get_user_by( 'login', $user_param );

		if ( ! $user ) {
			return [];
		}

		$display_name = $user->display_name;
		$bio          = get_user_meta( $user->ID, 'description', true );
		$avatar       = get_avatar_url( $user->ID, [ 'size' => 600 ] );

		// Try to get custom profile image.
		$custom_avatar = get_user_meta( $user->ID, 'apollo_profile_image', true );
		if ( ! empty( $custom_avatar ) ) {
			$avatar = $custom_avatar;
		}

		return [
			'title'       => sprintf(
				/* translators: %s: User display name */
				__( '%s - Perfil', 'apollo-rio' ),
				$display_name
			),
			'description' => ! empty( $bio ) ? wp_trim_words( $bio, 25 ) : sprintf(
				/* translators: %s: User display name */
				__( 'Perfil de %s na comunidade', 'apollo-rio' ),
				$display_name
			),
			'image'       => $avatar,
			'url'         => home_url( '/a/' . $user->user_login . '/' ),
			'og_type'     => 'profile',
		];
	}

	/**
	 * Get SEO data for a group page.
	 *
	 * @param string $slug      Group slug.
	 * @param string $type      Group type (comunidade, nucleo, season).
	 * @return array SEO data.
	 */
	private function get_group_seo_data( string $slug, string $type ): array {
		if ( empty( $slug ) ) {
			return [];
		}

		// Get group via Apollo Social Groups system.
		$group = $this->get_group_by_slug( $slug, $type );

		if ( ! $group ) {
			return [];
		}

		$type_labels = [
			'comunidade' => __( 'Comunidade', 'apollo-rio' ),
			'nucleo'     => __( 'Núcleo', 'apollo-rio' ),
			'season'     => __( 'Temporada', 'apollo-rio' ),
		];

		$type_label = $type_labels[ $type ] ?? __( 'Grupo', 'apollo-rio' );

		return [
			'title'       => $group['name'] . ' - ' . $type_label,
			'description' => ! empty( $group['description'] ) ? wp_trim_words( $group['description'], 25 ) : sprintf(
				/* translators: 1: Type label, 2: Group name */
				__( '%1$s %2$s - Conheça mais sobre este grupo', 'apollo-rio' ),
				$type_label,
				$group['name']
			),
			'image'       => $group['avatar'] ?? '',
			'url'         => home_url( '/' . $type . '/' . $slug . '/' ),
			'og_type'     => 'website',
		];
	}

	/**
	 * Get group by slug and type.
	 *
	 * @param string $slug Group slug.
	 * @param string $type Group type.
	 * @return array|null Group data or null.
	 */
	private function get_group_by_slug( string $slug, string $type ): ?array {
		global $wpdb;

		// Query the Apollo groups table.
		$table_name = $wpdb->prefix . 'apollo_groups';

		// Check if table exists.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( ! $table_exists ) {
			return null;
		}

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$group = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE slug = %s AND type = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug,
				$type
			),
			ARRAY_A
		);

		if ( ! $group ) {
			return null;
		}

		// Get group avatar from meta.
		$meta_table = $wpdb->prefix . 'apollo_group_meta';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$avatar = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$meta_table} WHERE group_id = %d AND meta_key = 'avatar' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$group['id']
			)
		);

		return [
			'id'          => $group['id'],
			'name'        => $group['name'],
			'slug'        => $group['slug'],
			'description' => $group['description'] ?? '',
			'avatar'      => ! empty( $avatar ) ? $avatar : '',
		];
	}

	/**
	 * Get SEO data for a classified ad.
	 *
	 * @param string $param Ad slug or ID.
	 * @return array SEO data.
	 */
	private function get_classified_seo_data( string $param ): array {
		// Try to get via WP Adverts or similar classified system.
		$ad = get_page_by_path( $param, OBJECT, 'advert' );

		if ( ! $ad ) {
			// Try by ID.
			$ad = is_numeric( $param ) ? get_post( (int) $param ) : null;
		}

		if ( ! $ad || 'advert' !== $ad->post_type ) {
			return [];
		}

		$image = get_the_post_thumbnail_url( $ad->ID, 'large' );

		return [
			'title'       => $ad->post_title,
			'description' => ! empty( $ad->post_excerpt ) ? $ad->post_excerpt : wp_trim_words( $ad->post_content, 25 ),
			'image'       => ! empty( $image ) ? $image : '',
			'url'         => home_url( '/anuncio/' . $ad->post_name . '/' ),
			'og_type'     => 'product',
		];
	}

	/**
	 * Get SEO data for an event.
	 *
	 * @param int $post_id Event post ID.
	 * @return array SEO data.
	 */
	private function get_event_seo_data( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return [];
		}

		$image      = get_the_post_thumbnail_url( $post_id, 'large' );
		$event_date = get_post_meta( $post_id, '_event_start_date', true );
		$venue_ids  = get_post_meta( $post_id, '_event_local_ids', true );

		$description = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_trim_words( $post->post_content, 25 );

		// Add event date to description if available.
		if ( ! empty( $event_date ) ) {
			$formatted_date = wp_date( 'd/m/Y', strtotime( $event_date ) );
			$description    = $formatted_date . ' - ' . $description;
		}

		return [
			'title'       => $post->post_title,
			'description' => $description,
			'image'       => ! empty( $image ) ? $image : '',
			'url'         => get_permalink( $post_id ),
			'og_type'     => 'event',
		];
	}

	/**
	 * Get SEO data for a DJ.
	 *
	 * @param int $post_id DJ post ID.
	 * @return array SEO data.
	 */
	private function get_dj_seo_data( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return [];
		}

		$image = get_the_post_thumbnail_url( $post_id, 'large' );
		$bio   = get_post_meta( $post_id, '_dj_bio', true );
		$genre = wp_get_post_terms( $post_id, 'event_listing_category', [ 'fields' => 'names' ] );

		$description = ! empty( $bio ) ? wp_trim_words( $bio, 25 ) : wp_trim_words( $post->post_content, 25 );
		if ( ! is_wp_error( $genre ) && ! empty( $genre ) ) {
			$description = implode( ', ', array_slice( $genre, 0, 3 ) ) . ' - ' . $description;
		}

		return [
			'title'       => sprintf(
				/* translators: %s: DJ name */
				__( 'DJ %s', 'apollo-rio' ),
				$post->post_title
			),
			'description' => $description,
			'image'       => ! empty( $image ) ? $image : '',
			'url'         => get_permalink( $post_id ),
			'og_type'     => 'profile',
		];
	}

	/**
	 * Get SEO data for a venue/local.
	 *
	 * @param int $post_id Local post ID.
	 * @return array SEO data.
	 */
	private function get_local_seo_data( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return [];
		}

		$image   = get_the_post_thumbnail_url( $post_id, 'large' );
		$address = get_post_meta( $post_id, '_local_address', true );
		$city    = get_post_meta( $post_id, '_local_city', true );

		$description = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_trim_words( $post->post_content, 25 );

		// Add location to description.
		$location_parts = array_filter( [ $address, $city ] );
		if ( ! empty( $location_parts ) ) {
			$description = implode( ', ', $location_parts ) . ' - ' . $description;
		}

		return [
			'title'       => $post->post_title,
			'description' => $description,
			'image'       => ! empty( $image ) ? $image : '',
			'url'         => get_permalink( $post_id ),
			'og_type'     => 'place',
		];
	}
}

// Initialize the SEO handler.
add_action( 'init', [ 'Apollo_SEO_Handler', 'get_instance' ] );
