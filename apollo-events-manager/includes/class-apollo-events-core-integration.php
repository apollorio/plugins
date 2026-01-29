<?php
/**
 * Apollo Events - Core Integration
 *
 * Hooks into Apollo Core's template system and integration bridge.
 * Provides [apollo_events] shortcode that uses Core templates.
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Events_Core_Integration
 *
 * Integrates Apollo Events Manager with Apollo Core's template system.
 */
class Apollo_Events_Core_Integration {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Events_Core_Integration|null
	 */
	private static ?Apollo_Events_Core_Integration $instance = null;

	/**
	 * Integration bridge instance
	 *
	 * @var object|null
	 */
	private ?object $bridge = null;

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Events_Core_Integration
	 */
	public static function get_instance(): Apollo_Events_Core_Integration {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Register with Core Integration Bridge when ready.
		add_action( 'apollo_integration_bridge_ready', array( $this, 'register_with_bridge' ) );

		// Register shortcode.
		add_action( 'init', array( $this, 'register_shortcodes' ), 15 );

		// Add event data transformations.
		add_filter( 'apollo_core_event_data_transform', array( $this, 'transform_event_data' ), 10, 2 );

		// Hook into before/after event display.
		add_action( 'apollo_core_before_event_display', array( $this, 'before_event_display' ), 10, 2 );
		add_action( 'apollo_core_after_event_display', array( $this, 'after_event_display' ), 10, 2 );
	}

	/**
	 * Register with Apollo Core Integration Bridge
	 *
	 * @param object $bridge Integration Bridge instance.
	 * @return void
	 */
	public function register_with_bridge( object $bridge ): void {
		$this->bridge = $bridge;

		if ( method_exists( $bridge, 'register_plugin' ) ) {
			$bridge->register_plugin(
				'events',
				array(
					'version'      => defined( 'APOLLO_APRIO_VERSION' ) ? APOLLO_APRIO_VERSION : '1.0.0',
					'file'         => 'apollo-events-manager/apollo-events-manager.php',
					'path'         => defined( 'APOLLO_APRIO_PATH' ) ? APOLLO_APRIO_PATH : '',
					'url'          => defined( 'APOLLO_APRIO_URL' ) ? APOLLO_APRIO_URL : '',
					'capabilities' => array( 'edit_events', 'manage_events' ),
					'supports'     => array( 'events', 'djs', 'locals', 'statistics' ),
					'hooks'        => array(
						'apollo_core_before_event_display',
						'apollo_core_after_event_display',
						'apollo_core_event_data_transform',
					),
				)
			);
		}
	}

	/**
	 * Register shortcodes
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		// Only register if not already registered.
		if ( ! shortcode_exists( 'apollo_events_grid' ) ) {
			add_shortcode( 'apollo_events_grid', array( $this, 'render_events_grid_shortcode' ) );
		}
	}

	/**
	 * Render [apollo_events_grid] shortcode
	 *
	 * Uses Apollo Core template system to render events grid.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function render_events_grid_shortcode( $atts = array(), $content = '' ): string {
		$atts = shortcode_atts(
			array(
				'limit'        => 12,
				'category'     => '',
				'type'         => '',
				'sound'        => '',
				'featured'     => '',
				'upcoming'     => 'true',
				'orderby'      => 'event_date',
				'order'        => 'ASC',
				'columns'      => 3,
				'show_filters' => 'true',
				'show_map'     => 'false',
				'template'     => 'grid',
			),
			$atts,
			'apollo_events_grid'
		);

		// Query events.
		$events = $this->query_events( $atts );

		// Prepare template data.
		$template_data = array(
			'events'         => $events,
			'atts'           => $atts,
			'total_events'   => count( $events ),
			'columns'        => absint( $atts['columns'] ),
			'show_filters'   => filter_var( $atts['show_filters'], FILTER_VALIDATE_BOOLEAN ),
			'show_map'       => filter_var( $atts['show_map'], FILTER_VALIDATE_BOOLEAN ),
			'filter_options' => $this->get_filter_options(),
		);

		// Use Core Template Loader.
		return $this->render_with_core_template( $atts['template'], $template_data );
	}

	/**
	 * Query events based on shortcode attributes
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array
	 */
	private function query_events( array $atts ): array {
		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'meta_query'     => array(),
			'tax_query'      => array(),
		);

		// Filter by category.
		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_listing_category',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			);
		}

		// Filter by type.
		if ( ! empty( $atts['type'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_listing_type',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
			);
		}

		// Filter by sound.
		if ( ! empty( $atts['sound'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_sounds',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['sound'] ) ),
			);
		}

		// Filter upcoming only.
		if ( 'true' === $atts['upcoming'] ) {
			$args['meta_query'][] = array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		// Filter featured.
		if ( 'true' === $atts['featured'] ) {
			$args['meta_query'][] = array(
				'key'     => '_event_featured',
				'value'   => '1',
				'compare' => '=',
			);
		}

		// Orderby.
		if ( 'event_date' === $atts['orderby'] ) {
			$args['meta_key'] = '_event_start_date';
			$args['orderby']  = 'meta_value';
			$args['order']    = strtoupper( $atts['order'] );
		} else {
			$args['orderby'] = sanitize_key( $atts['orderby'] );
			$args['order']   = strtoupper( $atts['order'] );
		}

		// Add relation if multiple tax queries.
		if ( count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		// Add relation if multiple meta queries.
		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		$query = new \WP_Query( $args );

		$events = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$events[] = $this->prepare_event_data( get_the_ID() );
			}
			wp_reset_postdata();
		}

		return $events;
	}

	/**
	 * Prepare event data for template
	 *
	 * @param int $event_id Event post ID.
	 * @return array
	 */
	private function prepare_event_data( int $event_id ): array {
		$post = get_post( $event_id );

		$data = array(
			'id'          => $event_id,
			'title'       => get_the_title( $event_id ),
			'excerpt'     => get_the_excerpt( $event_id ),
			'permalink'   => get_permalink( $event_id ),
			'thumbnail'   => get_the_post_thumbnail_url( $event_id, 'medium_large' ),
			'banner'      => get_post_meta( $event_id, '_event_banner', true ),
			'start_date'  => get_post_meta( $event_id, '_event_start_date', true ),
			'end_date'    => get_post_meta( $event_id, '_event_end_date', true ),
			'start_time'  => get_post_meta( $event_id, '_event_start_time', true ),
			'end_time'    => get_post_meta( $event_id, '_event_end_time', true ),
			'location'    => get_post_meta( $event_id, '_event_location', true ),
			'city'        => get_post_meta( $event_id, '_event_city', true ),
			'featured'    => (bool) get_post_meta( $event_id, '_event_featured', true ),
			'tickets_url' => get_post_meta( $event_id, '_tickets_ext', true ),
			'categories'  => wp_get_object_terms( $event_id, 'event_listing_category', array( 'fields' => 'names' ) ),
			'sounds'      => wp_get_object_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) ),
			'author_id'   => $post->post_author,
		);

		// Format dates for display.
		if ( ! empty( $data['start_date'] ) ) {
			$data['formatted_date'] = wp_date( get_option( 'date_format' ), strtotime( $data['start_date'] ) );
			$data['formatted_time'] = ! empty( $data['start_time'] ) ? $data['start_time'] : '';
		}

		return $data;
	}

	/**
	 * Get filter options for events grid
	 *
	 * @return array
	 */
	private function get_filter_options(): array {
		return array(
			'categories' => get_terms(
				array(
					'taxonomy'   => 'event_listing_category',
					'hide_empty' => true,
				)
			),
			'types'      => get_terms(
				array(
					'taxonomy'   => 'event_listing_type',
					'hide_empty' => true,
				)
			),
			'sounds'     => get_terms(
				array(
					'taxonomy'   => 'event_sounds',
					'hide_empty' => true,
				)
			),
		);
	}

	/**
	 * Render using Core Template Loader
	 *
	 * @param string $template      Template name.
	 * @param array  $template_data Template data.
	 * @return string
	 */
	private function render_with_core_template( string $template, array $template_data ): string {
		// Map shortcode template to core template file.
		$template_map = array(
			'grid'     => 'core-events-listing',
			'list'     => 'core-events-listing',
			'calendar' => 'cena-rio-calendar',
			'map'      => 'core-events-listing',
		);

		$core_template = $template_map[ $template ] ?? 'core-events-listing';

		// Add template variant.
		$template_data['variant'] = $template;

		// Check if Apollo Core Template Loader exists.
		if ( class_exists( '\Apollo_Template_Loader' ) ) {
			return \Apollo_Template_Loader::load( $core_template, $template_data, true );
		}

		// Fallback: Load template directly.
		$template_path = $this->locate_template( $core_template );

		if ( ! $template_path ) {
			return '<!-- Apollo Events: Template not found -->';
		}

		ob_start();

		// Extract data for template.
		extract( $template_data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		include $template_path;

		return ob_get_clean();
	}

	/**
	 * Locate template in Core or Events plugin
	 *
	 * @param string $template Template name.
	 * @return string|false
	 */
	private function locate_template( string $template ): string|false {
		$template_file = $template . '.php';

		$paths = array(
			// Theme override.
			get_stylesheet_directory() . '/apollo/' . $template_file,
			get_template_directory() . '/apollo/' . $template_file,
			// Core templates (IMMUTABLE - READ ONLY).
			( defined( 'APOLLO_CORE_PLUGIN_DIR' ) ? APOLLO_CORE_PLUGIN_DIR : '' ) . 'templates/' . $template_file,
			// Events templates.
			( defined( 'APOLLO_APRIO_PATH' ) ? APOLLO_APRIO_PATH : '' ) . 'templates/' . $template_file,
		);

		foreach ( $paths as $path ) {
			if ( ! empty( $path ) && file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Transform event data before display
	 *
	 * @param array $event_data Event data.
	 * @param int   $event_id   Event ID.
	 * @return array
	 */
	public function transform_event_data( array $event_data, int $event_id ): array {
		// Add DJ information.
		$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
		if ( ! empty( $dj_ids ) && is_array( $dj_ids ) ) {
			$event_data['djs'] = array();
			foreach ( $dj_ids as $dj_id ) {
				$dj_post = get_post( $dj_id );
				if ( $dj_post && 'event_dj' === $dj_post->post_type ) {
					$event_data['djs'][] = array(
						'id'        => $dj_id,
						'name'      => get_the_title( $dj_id ),
						'image'     => get_post_meta( $dj_id, '_dj_image', true ),
						'permalink' => get_permalink( $dj_id ),
					);
				}
			}
		}

		// Add venue information.
		$local_ids = get_post_meta( $event_id, '_event_local_ids', true );
		if ( ! empty( $local_ids ) && is_array( $local_ids ) ) {
			$event_data['venues'] = array();
			foreach ( $local_ids as $local_id ) {
				$local_post = get_post( $local_id );
				if ( $local_post && 'event_local' === $local_post->post_type ) {
					$event_data['venues'][] = array(
						'id'        => $local_id,
						'name'      => get_the_title( $local_id ),
						'address'   => get_post_meta( $local_id, '_local_address', true ),
						'city'      => get_post_meta( $local_id, '_local_city', true ),
						'permalink' => get_permalink( $local_id ),
					);
				}
			}
		}

		// Add favorites count.
		$event_data['favorites_count'] = absint( get_post_meta( $event_id, '_favorites_count', true ) );

		return $event_data;
	}

	/**
	 * Before event display hook
	 *
	 * @param int   $event_id Event ID.
	 * @param array $args     Display args.
	 * @return void
	 */
	public function before_event_display( int $event_id, array $args = array() ): void {
		// Track event view.
		if ( ! is_admin() && ! wp_doing_ajax() ) {
			$this->track_event_view( $event_id );
		}
	}

	/**
	 * After event display hook
	 *
	 * @param int   $event_id Event ID.
	 * @param array $args     Display args.
	 * @return void
	 */
	public function after_event_display( int $event_id, array $args = array() ): void {
		// Output social buttons if available.
		if ( $this->bridge && method_exists( $this->bridge, 'render_social_buttons' ) ) {
			$this->bridge->render_social_buttons( $event_id, 'event', $args );
		}
	}

	/**
	 * Track event view
	 *
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function track_event_view( int $event_id ): void {
		$views = absint( get_post_meta( $event_id, '_event_views', true ) );
		update_post_meta( $event_id, '_event_views', $views + 1 );
	}
}

// Initialize integration.
add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'APOLLO_CORE_BOOTSTRAPPED' ) || class_exists( 'Apollo_Core' ) ) {
			Apollo_Events_Core_Integration::get_instance();
		}
	},
	15
);
