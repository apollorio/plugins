<?php
/**
 * FILE: apollo-events-manager/templates/portal-discover.php
 * PHASE D: Events Discovery Portal Wrapper
 *
 * This wrapper handles data preparation and delegates rendering
 * to the core template: apollo-core/templates/core-events-listing.php
 *
 * Data Contract: See docs/conversion-map.md section 1
 */

defined( 'ABSPATH' ) || exit;

// Load helper (maintains existing contract)
require_once plugin_dir_path( __FILE__ ) . '../includes/helpers/event-data-helper.php';

// Create ViewModel for events listing
$viewModel = null;
if ( class_exists( 'Apollo_ViewModel_Factory' ) && class_exists( 'Apollo_Event_Data_Helper' ) ) {
	$viewModel = Apollo_ViewModel_Factory::create_from_data(
		Apollo_Event_Data_Helper::get_cached_event_ids( true ),
		'events_listing'
	);
}

// Get template data from ViewModel or use defaults
$template_data = array();
if ( $viewModel && method_exists( $viewModel, 'get_template_data' ) ) {
	$template_data = $viewModel->get_template_data();
}

// Ensure all required keys exist with defaults
$template_data = wp_parse_args(
	$template_data,
	array(
		'page_title'       => __( 'Discover Events', 'apollo-events-manager' ),
		'current_user'     => is_user_logged_in() ? wp_get_current_user() : null,
		'navigation_links' => array(),
		'hero_title'       => __( 'Experience Tomorrow\'s Events', 'apollo-events-manager' ),
		'hero_subtitle'    => __( 'Um novo hub digital que conecta cultura, tecnologia e experiências em tempo real...', 'apollo-events-manager' ),
		'hero_background'  => '',
		'period_filters'   => array(),
		'category_filters' => apollo_get_event_category_filters(),
		'current_month'    => date_i18n( 'F Y' ),
		'event_sections'   => array(),
		'banner'           => apollo_get_latest_post_banner(),
		'show_bottom_bar'  => false,
		'bottom_bar_data'  => array(),
	)
);

// If event_sections is empty, try to build it from raw events
if ( empty( $template_data['event_sections'] ) ) {
	$template_data['event_sections'] = apollo_build_event_sections_from_query();
}

// Create template loader instance
$template_loader = new Apollo_Template_Loader();

// Prepare context for core template
$core_context = array_merge(
	$template_data,
	array(
		'template_loader' => $template_loader,
		'is_print'        => isset( $_GET['print'] ) && $_GET['print'] === '1',
	)
);

// Delegate to core template - NO MORE HTML HERE
Apollo_Template_Loader::load( 'core-events-listing', $core_context );

/**
 * Build category filters from event_listing_type taxonomy
 */
function apollo_get_event_category_filters() {
	$filters = array(
		array(
			'slug'   => 'all',
			'label'  => __( 'All', 'apollo-events-manager' ),
			'url'    => '#',
			'active' => true,
			'type'   => 'button',
		),
	);

	$terms = get_terms(
		array(
			'taxonomy'   => 'event_listing_type',
			'hide_empty' => true,
			'number'     => 10,
		)
	);

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$filters[] = array(
				'slug'   => $term->slug,
				'label'  => $term->name,
				'url'    => get_term_link( $term ),
				'active' => false,
				'type'   => 'button',
			);
		}
	}

	return $filters;
}

/**
 * Build event sections from WP_Query if ViewModel didn't provide them
 */
function apollo_build_event_sections_from_query() {
	$sections = array();

	// Get upcoming events
	$args = array(
		'post_type'      => 'event_listing',
		'posts_per_page' => 12,
		'post_status'    => 'publish',
		'meta_key'       => '_event_start_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_event_start_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);

	$query  = new WP_Query( $args );
	$events = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$event_id = get_the_ID();

			$start_date = get_post_meta( $event_id, '_event_start_date', true );
			$timestamp  = $start_date ? strtotime( $start_date ) : time();

			// Get venue
			$venue_name = '';
			$local_ids  = get_post_meta( $event_id, '_event_local_ids', true );
			if ( is_array( $local_ids ) && ! empty( $local_ids ) ) {
				$venue_name = get_the_title( $local_ids[0] );
			}

			// Get DJs
			$djs    = array();
			$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
			if ( is_array( $dj_ids ) ) {
				foreach ( array_slice( $dj_ids, 0, 3 ) as $dj_id ) {
					$djs[] = array(
						'id'   => $dj_id,
						'name' => get_the_title( $dj_id ),
					);
				}
			}

			// Get tags
			$tags  = array();
			$terms = get_the_terms( $event_id, 'event_sounds' );
			if ( is_array( $terms ) ) {
				foreach ( array_slice( $terms, 0, 2 ) as $term ) {
					$tags[] = array(
						'name' => $term->name,
						'slug' => $term->slug,
					);
				}
			}

			// Get category
			$category  = '';
			$cat_terms = get_the_terms( $event_id, 'event_listing_type' );
			if ( is_array( $cat_terms ) && ! empty( $cat_terms ) ) {
				$category = $cat_terms[0]->slug;
			}

			$events[] = array(
				'id'            => $event_id,
				'title'         => get_the_title(),
				'permalink'     => get_permalink(),
				'thumbnail_url' => get_the_post_thumbnail_url( $event_id, 'medium_large' ),
				'day'           => date_i18n( 'd', $timestamp ),
				'month'         => strtoupper( date_i18n( 'M', $timestamp ) ),
				'tags'          => $tags,
				'djs'           => $djs,
				'venue_name'    => $venue_name,
				'category'      => $category,
			);
		}
		wp_reset_postdata();
	}

	if ( ! empty( $events ) ) {
		$sections[] = array(
			'slug'       => 'upcoming',
			'title'      => __( 'Próximos Eventos', 'apollo-events-manager' ),
			'icon'       => 'ri-calendar-event-line',
			'show_title' => true,
			'grid_class' => '',
			'events'     => $events,
		);
	}

	return $sections;
}

/**
 * Get latest post banner data
 *
 * @return array|null Banner data or null if no post found
 */
function apollo_get_latest_post_banner() {
	$latest_post = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( empty( $latest_post ) ) {
		return null;
	}

	$post    = $latest_post[0];
	$post_id = $post->ID;

	// Get featured image or fallback
	$post_image = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( ! $post_image ) {
		$post_image = 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop';
	}

	// Get first category for subtitle
	$categories    = get_the_category( $post_id );
	$post_category = ! empty( $categories ) ? $categories[0]->name : __( 'Destaque do Mês', 'apollo-events-manager' );

	// Get excerpt
	$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post ) : wp_trim_words( $post->post_content, 30 );

	return array(
		'image'    => $post_image,
		'title'    => get_the_title( $post ),
		'subtitle' => $post_category,
		'excerpt'  => $excerpt,
		'url'      => get_permalink( $post ),
		'cta_text' => __( 'Saiba Mais', 'apollo-events-manager' ),
	);
}
