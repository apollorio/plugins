<?php
/**
 * Event Card Helper Functions
 * ===========================
 * Path: apollo-events-manager/includes/helpers/event-card-helper.php
 *
 * Helper functions to render event cards using the official Apollo design.
 * Uses template parts for modularity and supports various data sources.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a single event card
 *
 * @param int|WP_Post $event       Event ID or WP_Post object
 * @param array       $args        Additional arguments
 * @return void
 */
function apollo_render_event_card( $event, $args = array() ) {
	// Get post object
	if ( is_numeric( $event ) ) {
		$event = get_post( $event );
	}

	if ( ! $event instanceof WP_Post ) {
		return;
	}

	// Default arguments
	$defaults = array(
		'delay_index'   => 100,
		'show_sounds'   => true,
		'show_djs'      => true,
		'show_location' => true,
		'is_external'   => false,
		'external_url'  => '',
	);

	$args = wp_parse_args( $args, $defaults );

	// Build context from post meta
	$context = apollo_build_event_card_context( $event, $args );

	// Load template part
	apollo_load_event_card_template( $context );
}

/**
 * Build event card context from WP_Post
 *
 * @param WP_Post $event Event post object
 * @param array   $args  Additional arguments
 * @return array Context array for template
 */
function apollo_build_event_card_context( $event, $args = array() ) {
	$event_id = $event->ID;

	// Get event meta
	$event_title    = get_post_meta( $event_id, '_event_title', true ) ?: get_the_title( $event_id );
	$event_banner   = get_post_meta( $event_id, '_event_banner', true );
	$event_date     = get_post_meta( $event_id, '_event_start_date', true );
	$event_location = get_post_meta( $event_id, '_event_location', true );
	$tickets_ext    = get_post_meta( $event_id, '_tickets_ext', true );
	$cupom_ario     = get_post_meta( $event_id, '_cupom_ario', true );

	// Fallback to thumbnail if no banner
	if ( empty( $event_banner ) ) {
		$event_banner = get_the_post_thumbnail_url( $event_id, 'large' );
	}

	// Get location from related local if not set
	if ( empty( $event_location ) ) {
		$local_ids = get_post_meta( $event_id, '_event_local_ids', true );
		if ( $local_ids ) {
			$local_id = is_array( $local_ids ) ? reset( $local_ids ) : $local_ids;
			if ( $local_id ) {
				$local_name = get_post_meta( $local_id, '_local_name', true );
				if ( empty( $local_name ) ) {
					$local_name = get_the_title( $local_id );
				}
				$event_location = $local_name;
			}
		}
	}

	// Get DJs from timetable or DJ IDs
	$event_djs = array();
	$dj_slots  = get_post_meta( $event_id, '_event_dj_slots', true );

	if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) {
		foreach ( $dj_slots as $slot ) {
			if ( isset( $slot['dj_id'] ) && $slot['dj_id'] ) {
				$dj_name = get_post_meta( $slot['dj_id'], '_dj_name', true );
				if ( empty( $dj_name ) ) {
					$dj_name = get_the_title( $slot['dj_id'] );
				}
				if ( $dj_name && ! in_array( $dj_name, $event_djs, true ) ) {
					$event_djs[] = $dj_name;
				}
			}
		}
	}

	// Fallback to legacy DJ IDs
	if ( empty( $event_djs ) ) {
		$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
		if ( $dj_ids ) {
			$dj_ids = maybe_unserialize( $dj_ids );
			if ( is_array( $dj_ids ) ) {
				foreach ( $dj_ids as $dj_id ) {
					$dj_name = get_post_meta( $dj_id, '_dj_name', true );
					if ( empty( $dj_name ) ) {
						$dj_name = get_the_title( $dj_id );
					}
					if ( $dj_name ) {
						$event_djs[] = $dj_name;
					}
				}
			}
		}
	}

	// Get sounds/genres from taxonomy
	$event_sounds = array();
	$sounds_terms = wp_get_post_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $sounds_terms ) && ! empty( $sounds_terms ) ) {
		$event_sounds = $sounds_terms;
	}

	// Determine URL
	$event_url   = get_permalink( $event_id );
	$is_external = false;

	if ( ! empty( $args['external_url'] ) ) {
		$event_url   = $args['external_url'];
		$is_external = true;
	} elseif ( ! empty( $tickets_ext ) ) {
		// If external ticket URL exists, use it
		$event_url   = $tickets_ext;
		$is_external = true;
	}

	// Determine coupon
	$event_coupon = '';
	if ( $cupom_ario ) {
		$event_coupon = 'APOLLORIO'; // Default coupon
	}

	// Check if Apollo partner
	$is_apollo = (bool) get_post_meta( $event_id, '_event_featured', true );

	return array(
		'event_id'       => $event_id,
		'event_title'    => $event_title,
		'event_url'      => $event_url,
		'event_image'    => $event_banner,
		'event_date'     => $event_date,
		'event_location' => $event_location,
		'event_djs'      => $args['show_djs'] ? $event_djs : array(),
		'event_sounds'   => $args['show_sounds'] ? $event_sounds : array(),
		'event_coupon'   => $event_coupon,
		'is_apollo'      => $is_apollo,
		'is_external'    => $is_external,
		'delay_index'    => $args['delay_index'] ?? 100,
	);
}

/**
 * Load event card template with context
 *
 * @param array $context Template context variables
 * @return void
 */
function apollo_load_event_card_template( $context ) {
	// Extract context for template
	extract( $context, EXTR_SKIP );

	// Try Apollo template loader first
	$template_paths = array(
		// Theme override
		get_stylesheet_directory() . '/apollo-events/parts/event/card.php',
		get_template_directory() . '/apollo-events/parts/event/card.php',
		// Plugin path
		APOLLO_APRIO_PLUGIN_DIR . 'templates/parts/event/card.php',
		plugin_dir_path( dirname( __DIR__ ) ) . 'templates/parts/event/card.php',
	);

	foreach ( $template_paths as $path ) {
		if ( file_exists( $path ) ) {
			include $path;
			return;
		}
	}

	// Fallback: inline render if template not found
	apollo_render_event_card_inline( $context );
}

/**
 * Inline fallback render for event card
 *
 * @param array $context Template context
 * @return void
 */
function apollo_render_event_card_inline( $context ) {
	$event_id       = $context['event_id'] ?? 0;
	$event_title    = $context['event_title'] ?? '';
	$event_url      = $context['event_url'] ?? '#';
	$event_image    = $context['event_image'] ?? '';
	$event_date     = $context['event_date'] ?? '';
	$event_location = $context['event_location'] ?? '';
	$event_djs      = $context['event_djs'] ?? array();
	$event_sounds   = $context['event_sounds'] ?? array();
	$is_external    = $context['is_external'] ?? false;
	$delay_index    = $context['delay_index'] ?? 100;

	// Parse date
	$date_day   = '';
	$date_month = '';
	if ( $event_date ) {
		$timestamp  = is_numeric( $event_date ) ? $event_date : strtotime( $event_date );
		$date_day   = date_i18n( 'd', $timestamp );
		$date_month = date_i18n( 'M', $timestamp );
	}

	$target = $is_external ? ' target="_blank" rel="noopener"' : '';
	?>
	<a href="<?php echo esc_url( $event_url ); ?>" class="a-eve-card reveal-up delay-<?php echo absint( $delay_index ); ?>" data-event-id="<?php echo absint( $event_id ); ?>"<?php echo $target; ?>>
		<?php if ( $date_day ) : ?>
		<div class="a-eve-date">
			<span class="a-eve-date-day"><?php echo esc_html( $date_day ); ?></span>
			<span class="a-eve-date-month"><?php echo esc_html( $date_month ); ?></span>
		</div>
		<?php endif; ?>
		<div class="a-eve-media">
			<?php if ( $event_image ) : ?>
			<img src="<?php echo esc_url( $event_image ); ?>" alt="<?php echo esc_attr( $event_title ); ?>" loading="lazy">
			<?php endif; ?>
			<?php if ( $event_sounds ) : ?>
			<div class="a-eve-tags">
				<?php foreach ( array_slice( $event_sounds, 0, 2 ) as $sound ) : ?>
				<span class="a-eve-tag"><?php echo esc_html( $sound ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<div class="a-eve-content">
			<h2 class="a-eve-title"><?php echo esc_html( $event_title ); ?></h2>
			<?php if ( $event_djs ) : ?>
			<p class="a-eve-meta"><i class="ri-sound-module-fill"></i><span><?php echo esc_html( implode( ', ', array_slice( $event_djs, 0, 3 ) ) ); ?></span></p>
			<?php endif; ?>
			<?php if ( $event_location ) : ?>
			<p class="a-eve-meta"><i class="ri-map-pin-2-line"></i><span><?php echo esc_html( $event_location ); ?></span></p>
			<?php endif; ?>
		</div>
	</a>
	<?php
}

/**
 * Render events grid with multiple cards
 *
 * @param array|WP_Query $events Events array or WP_Query object
 * @param array          $args   Grid arguments
 * @return void
 */
function apollo_render_events_grid( $events, $args = array() ) {
	$defaults = array(
		'columns'      => 4,
		'max_events'   => 8,
		'show_sounds'  => true,
		'show_djs'     => true,
		'grid_class'   => 'apollo-events-grid',
		'animate'      => true,
	);

	$args = wp_parse_args( $args, $defaults );

	// Convert WP_Query to array
	if ( $events instanceof WP_Query ) {
		$events = $events->posts;
	}

	// Limit events
	$events = array_slice( $events, 0, $args['max_events'] );

	if ( empty( $events ) ) {
		echo '<p class="apollo-no-events">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-events-manager' ) . '</p>';
		return;
	}

	?>
	<div class="<?php echo esc_attr( $args['grid_class'] ); ?>">
		<?php
		$delay_sequence = array( 100, 200, 300, 100, 200, 300, 100, 200 );
		$index          = 0;

		foreach ( $events as $event ) :
			$delay = $args['animate'] ? $delay_sequence[ $index % count( $delay_sequence ) ] : 0;

			apollo_render_event_card( $event, array(
				'delay_index'   => $delay,
				'show_sounds'   => $args['show_sounds'],
				'show_djs'      => $args['show_djs'],
			) );

			$index++;
		endforeach;
		?>
	</div>
	<?php
}

/**
 * Get events for grid display
 *
 * @param array $args Query arguments
 * @return WP_Query
 */
function apollo_get_events_for_grid( $args = array() ) {
	$defaults = array(
		'post_type'      => 'event_listing',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'meta_key'       => '_event_start_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);

	$query_args = wp_parse_args( $args, $defaults );

	// Apply filters for extensibility
	$query_args = apply_filters( 'apollo_events_grid_query_args', $query_args );

	return new WP_Query( $query_args );
}

/**
 * Shortcode: [apollo_events_grid]
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function apollo_events_grid_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'count'    => 8,
		'columns'  => 4,
		'category' => '',
		'sounds'   => '',
		'season'   => '',
		'featured' => '',
		'animate'  => 'true',
	), $atts, 'apollo_events_grid' );

	// Build query args
	$query_args = array(
		'posts_per_page' => absint( $atts['count'] ),
	);

	// Category filter
	if ( ! empty( $atts['category'] ) ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'event_listing_category',
			'field'    => 'slug',
			'terms'    => explode( ',', $atts['category'] ),
		);
	}

	// Sounds filter
	if ( ! empty( $atts['sounds'] ) ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'event_sounds',
			'field'    => 'slug',
			'terms'    => explode( ',', $atts['sounds'] ),
		);
	}

	// Season filter
	if ( ! empty( $atts['season'] ) ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'event_season',
			'field'    => 'slug',
			'terms'    => explode( ',', $atts['season'] ),
		);
	}

	// Featured filter
	if ( $atts['featured'] === 'true' ) {
		$query_args['meta_query'][] = array(
			'key'   => '_event_featured',
			'value' => '1',
		);
	}

	// Get events
	$events = apollo_get_events_for_grid( $query_args );

	// Enqueue styles
	wp_enqueue_style( 'apollo-event-card' );

	// Render
	ob_start();
	apollo_render_events_grid( $events, array(
		'columns'    => absint( $atts['columns'] ),
		'max_events' => absint( $atts['count'] ),
		'animate'    => $atts['animate'] === 'true',
	) );
	return ob_get_clean();
}
add_shortcode( 'apollo_events_grid', 'apollo_events_grid_shortcode' );

/**
 * Enqueue event card assets
 *
 * @return void
 */
function apollo_enqueue_event_card_assets() {
	// Only enqueue on relevant pages
	if ( ! is_singular( 'event_listing' ) && ! is_post_type_archive( 'event_listing' ) ) {
		// Check if shortcode is used
		global $post;
		if ( $post && ! has_shortcode( $post->post_content, 'apollo_events_grid' ) ) {
			return;
		}
	}

	$plugin_url = defined( 'APOLLO_APRIO_URL' ) ? APOLLO_APRIO_URL : plugin_dir_url( dirname( __DIR__ ) );
	$version    = defined( 'APOLLO_APRIO_VERSION' ) ? APOLLO_APRIO_VERSION : '2.0.0';

	// Register and enqueue CSS
	wp_register_style(
		'apollo-event-card',
		$plugin_url . 'assets/css/event-card.css',
		array(),
		$version
	);

	wp_enqueue_style( 'apollo-event-card' );

	// Ensure Apollo CDN is loaded for icons
	if ( ! wp_script_is( 'apollo-cdn-loader', 'enqueued' ) ) {
		wp_enqueue_script(
			'apollo-cdn-icons',
			'https://assets.apollo.rio.br/icon.js',
			array(),
			null,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'apollo_enqueue_event_card_assets' );

/**
 * Register event card style for manual enqueueing
 *
 * @return void
 */
function apollo_register_event_card_style() {
	$plugin_url = defined( 'APOLLO_APRIO_URL' ) ? APOLLO_APRIO_URL : plugin_dir_url( dirname( __DIR__ ) );
	$version    = defined( 'APOLLO_APRIO_VERSION' ) ? APOLLO_APRIO_VERSION : '2.0.0';

	wp_register_style(
		'apollo-event-card',
		$plugin_url . 'assets/css/event-card.css',
		array(),
		$version
	);
}
add_action( 'init', 'apollo_register_event_card_style' );
