<?php
/**
 * Apollo Events Manager - Unified Shortcodes
 *
 * PSR-4 compliant shortcodes that use Apollo Core Template Loader.
 * Provides standardized event display shortcodes with template integration.
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Shortcodes;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EventShortcodes
 *
 * Unified event shortcodes with template loader integration.
 */
class EventShortcodes {

	/**
	 * Singleton instance
	 *
	 * @var EventShortcodes|null
	 */
	private static ?EventShortcodes $instance = null;

	/**
	 * Default shortcode attributes
	 *
	 * @var array<string, mixed>
	 */
	private array $default_atts = array(
		'limit'    => 6,
		'category' => '',
		'type'     => '',
		'sound'    => '',
		'orderby'  => 'event_date',
		'order'    => 'ASC',
		'layout'   => 'grid',
		'columns'  => 3,
		'featured' => '',
		'upcoming' => 'true',
	);

	/**
	 * Get singleton instance
	 *
	 * @return EventShortcodes
	 */
	public static function get_instance(): EventShortcodes {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->register_shortcodes();
	}

	/**
	 * Register all shortcodes
	 *
	 * @return void
	 */
	private function register_shortcodes(): void {
		// Main events shortcode (enhanced version).
		add_shortcode( 'apollo_events', array( $this, 'render_events' ) );

		// Single event display.
		add_shortcode( 'apollo_event_single', array( $this, 'render_event_single' ) );

		// Calendar view.
		add_shortcode( 'apollo_event_calendar', array( $this, 'render_event_calendar' ) );

		// Featured events.
		add_shortcode( 'apollo_featured_events', array( $this, 'render_featured_events' ) );

		// Upcoming events widget.
		add_shortcode( 'apollo_upcoming_events', array( $this, 'render_upcoming_events' ) );

		// Event card (single inline card).
		add_shortcode( 'apollo_event_card', array( $this, 'render_event_card' ) );
	}

	// =========================================================================
	// [apollo_events] - Main Events Grid/List/Carousel
	// =========================================================================

	/**
	 * Render events shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_events( $atts = array() ): string {
		$atts = shortcode_atts( $this->default_atts, $atts, 'apollo_events' );

		// Enqueue assets.
		$this->enqueue_assets();

		// Build query.
		$query = $this->build_events_query( $atts );

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return $this->render_empty_state( __( 'Nenhum evento encontrado.', 'apollo-events-manager' ) );
		}

		// Prepare events data.
		$events = $this->prepare_events_data( $query );
		wp_reset_postdata();

		// Render based on layout.
		return match ( $atts['layout'] ) {
			'list'     => $this->render_events_list( $events, $atts ),
			'carousel' => $this->render_events_carousel( $events, $atts ),
			default    => $this->render_events_grid( $events, $atts ),
		};
	}

	/**
	 * Build WP_Query for events
	 *
	 * @param array $atts Shortcode attributes.
	 * @return WP_Query
	 */
	private function build_events_query( array $atts ): WP_Query {
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
				'terms'    => \array_map( 'sanitize_text_field', \explode( ',', $atts['category'] ) ),
			);
		}

		// Filter by type.
		if ( ! empty( $atts['type'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_listing_type',
				'field'    => 'slug',
				'terms'    => \array_map( 'sanitize_text_field', \explode( ',', $atts['type'] ) ),
			);
		}

		// Filter by sound.
		if ( ! empty( $atts['sound'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_sounds',
				'field'    => 'slug',
				'terms'    => \array_map( 'sanitize_text_field', \explode( ',', $atts['sound'] ) ),
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
		} else {
			$args['orderby'] = sanitize_key( $atts['orderby'] );
		}
		$args['order'] = \strtoupper( $atts['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		// Add relation if multiple queries.
		if ( count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}
		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		return new WP_Query( $args );
	}

	/**
	 * Prepare events data for templates
	 *
	 * @param WP_Query $query Query object.
	 * @return array
	 */
	private function prepare_events_data( WP_Query $query ): array {
		$events = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$event_id = get_the_ID();

			$events[] = array(
				'id'             => $event_id,
				'title'          => get_the_title(),
				'excerpt'        => wp_trim_words( get_the_excerpt(), 20 ),
				'permalink'      => get_permalink(),
				'thumbnail'      => $this->get_event_image( $event_id ),
				'start_date'     => get_post_meta( $event_id, '_event_start_date', true ),
				'start_time'     => get_post_meta( $event_id, '_event_start_time', true ),
				'end_date'       => get_post_meta( $event_id, '_event_end_date', true ),
				'location'       => get_post_meta( $event_id, '_event_location', true ),
				'city'           => get_post_meta( $event_id, '_event_city', true ),
				'featured'       => (bool) get_post_meta( $event_id, '_event_featured', true ),
				'tickets_url'    => get_post_meta( $event_id, '_tickets_ext', true ),
				'categories'     => wp_get_object_terms( $event_id, 'event_listing_category', array( 'fields' => 'names' ) ),
				'sounds'         => wp_get_object_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) ),
				'formatted_date' => $this->format_event_date( $event_id ),
			);
		}

		return $events;
	}

	/**
	 * Render events grid layout
	 *
	 * @param array $events Events data.
	 * @param array $atts   Attributes.
	 * @return string
	 */
	private function render_events_grid( array $events, array $atts ): string {
		$columns = absint( $atts['columns'] );
		$columns = min( max( $columns, 1 ), 4 );

		ob_start();
		?>
		<div class="apollo-events-grid" data-columns="<?php echo esc_attr( (string) $columns ); ?>">
			<div class="apollo-grid apollo-grid--<?php echo esc_attr( (string) $columns ); ?>">
				<?php foreach ( $events as $event ) : ?>
					<?php echo $this->render_event_card_html( $event ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render events list layout
	 *
	 * @param array $events Events data.
	 * @param array $atts   Attributes.
	 * @return string
	 */
	private function render_events_list( array $events, array $atts ): string {
		ob_start();
		?>
		<div class="apollo-events-list">
			<?php foreach ( $events as $event ) : ?>
				<article class="apollo-event-item apollo-card apollo-card--horizontal">
					<?php if ( $event['thumbnail'] ) : ?>
						<div class="apollo-card__media apollo-card__media--side">
							<img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy">
						</div>
					<?php endif; ?>
					<div class="apollo-card__body">
						<div class="apollo-card__meta">
							<?php if ( $event['start_date'] ) : ?>
								<span class="apollo-badge apollo-badge--primary">
									<i class="ri-calendar-line"></i>
									<?php echo esc_html( $event['formatted_date'] ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $event['location'] ) : ?>
								<span class="apollo-badge apollo-badge--outline">
									<i class="ri-map-pin-line"></i>
									<?php echo esc_html( $event['location'] ); ?>
								</span>
							<?php endif; ?>
						</div>
						<h3 class="apollo-card__title">
							<a href="<?php echo esc_url( $event['permalink'] ); ?>">
								<?php echo esc_html( $event['title'] ); ?>
							</a>
						</h3>
						<p class="apollo-card__description"><?php echo esc_html( $event['excerpt'] ); ?></p>
						<div class="apollo-card__actions">
							<a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-btn apollo-btn--primary apollo-btn--sm">
								<?php esc_html_e( 'Ver Detalhes', 'apollo-events-manager' ); ?>
							</a>
							<?php if ( $event['tickets_url'] ) : ?>
								<a href="<?php echo esc_url( $event['tickets_url'] ); ?>" class="apollo-btn apollo-btn--outline apollo-btn--sm" target="_blank" rel="noopener">
									<i class="ri-ticket-line"></i>
									<?php esc_html_e( 'Ingressos', 'apollo-events-manager' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render events carousel layout
	 *
	 * @param array $events Events data.
	 * @param array $atts   Attributes.
	 * @return string
	 */
	private function render_events_carousel( array $events, array $atts ): string {
		$carousel_id = 'apollo-carousel-' . wp_unique_id();

		ob_start();
		?>
		<div class="apollo-events-carousel" id="<?php echo esc_attr( $carousel_id ); ?>">
			<div class="apollo-carousel__container">
				<div class="apollo-carousel__track">
					<?php foreach ( $events as $event ) : ?>
						<div class="apollo-carousel__slide">
							<?php echo $this->render_event_card_html( $event ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<button type="button" class="apollo-carousel__prev" aria-label="<?php esc_attr_e( 'Anterior', 'apollo-events-manager' ); ?>">
				<i class="ri-arrow-left-s-line"></i>
			</button>
			<button type="button" class="apollo-carousel__next" aria-label="<?php esc_attr_e( 'Próximo', 'apollo-events-manager' ); ?>">
				<i class="ri-arrow-right-s-line"></i>
			</button>
			<div class="apollo-carousel__dots"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render single event card HTML
	 *
	 * @param array $event Event data.
	 * @return string
	 */
	private function render_event_card_html( array $event ): string {
		$event_id    = $event['id'] ?? 0;
		$delay_index = wp_rand( 100, 300 );

		// Parse date
		$date_day   = '';
		$date_month = '';
		if ( ! empty( $event['start_date'] ) ) {
			$timestamp  = strtotime( $event['start_date'] );
			$date_day   = wp_date( 'd', $timestamp );
			$date_month = wp_date( 'M', $timestamp );
		}

		// Build card classes
		$card_classes = array( 'a-eve-card', 'reveal-up', 'delay-' . $delay_index );
		if ( $event['featured'] ?? false ) {
			$card_classes[] = 'apollo';
		}

		ob_start();
		?>
		<a href="<?php echo esc_url( $event['permalink'] ); ?>"
		   class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
		   data-idx="<?php echo absint( $event_id ); ?>">

			<?php if ( $date_day && $date_month ) : ?>
			<div class="a-eve-date">
				<span class="a-eve-date-day"><?php echo esc_html( $date_day ); ?></span>
				<span class="a-eve-date-month"><?php echo esc_html( $date_month ); ?></span>
			</div>
			<?php endif; ?>

			<div class="a-eve-media">
				<?php if ( $event['thumbnail'] ) : ?>
				<img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy" decoding="async">
				<?php endif; ?>

				<?php if ( ! empty( $event['sounds'] ) ) : ?>
				<div class="a-eve-tags">
					<?php foreach ( array_slice( $event['sounds'], 0, 3 ) as $sound ) : ?>
					<span class="a-eve-tag"><?php echo esc_html( $sound ); ?></span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<div class="a-eve-content">
				<h2 class="a-eve-title"><?php echo esc_html( $event['title'] ); ?></h2>

				<?php if ( ! empty( $event['djs'] ) ) : ?>
				<p class="a-eve-meta">
					<i class="ri-sound-module-fill"></i>
					<span><?php echo esc_html( implode( ', ', array_slice( $event['djs'], 0, 3 ) ) ); ?></span>
				</p>
				<?php endif; ?>

				<?php if ( $event['location'] || $event['city'] ) : ?>
				<p class="a-eve-meta">
					<i class="ri-map-pin-2-line"></i>
					<span><?php echo esc_html( $event['location'] ?: $event['city'] ); ?></span>
				</p>
				<?php endif; ?>

				<?php if ( ! empty( $event['sounds'] ) ) : ?>
				<p class="a-eve-meta">
					<i class="ri-music-2-line"></i>
					<span><?php echo esc_html( implode( ', ', array_slice( $event['sounds'], 0, 3 ) ) ); ?></span>
				</p>
				<?php endif; ?>
			</div>
		</a>
		<?php
		return ob_get_clean();
	}


	// =========================================================================
	// [apollo_event_single] - Single Event Display
	// =========================================================================

	/**
	 * Render single event shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_event_single( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'layout' => 'full',
			),
			$atts,
			'apollo_event_single'
		);

		$event_id = absint( $atts['id'] );

		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		$post = get_post( $event_id );

		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return $this->render_empty_state( __( 'Evento não encontrado.', 'apollo-events-manager' ) );
		}

		$this->enqueue_assets();

		// Use Integration Bridge if available.
		if ( \function_exists( 'apollo_render_event' ) ) {
			return \apollo_render_event( $event_id, array( 'layout' => $atts['layout'] ) );
		}

		// Fallback rendering.
		$event = $this->get_single_event_data( $event_id );

		return $this->render_single_event_html( $event, $atts['layout'] );
	}

	/**
	 * Get single event data
	 *
	 * @param int $event_id Event ID.
	 * @return array
	 */
	private function get_single_event_data( int $event_id ): array {
		$post = get_post( $event_id );

		return array(
			'id'          => $event_id,
			'title'       => get_the_title( $event_id ),
			'content'     => apply_filters( 'the_content', $post->post_content ),
			'excerpt'     => get_the_excerpt( $event_id ),
			'permalink'   => get_permalink( $event_id ),
			'thumbnail'   => $this->get_event_image( $event_id ),
			'banner'      => get_post_meta( $event_id, '_event_banner', true ),
			'start_date'  => get_post_meta( $event_id, '_event_start_date', true ),
			'end_date'    => get_post_meta( $event_id, '_event_end_date', true ),
			'start_time'  => get_post_meta( $event_id, '_event_start_time', true ),
			'end_time'    => get_post_meta( $event_id, '_event_end_time', true ),
			'location'    => get_post_meta( $event_id, '_event_location', true ),
			'address'     => get_post_meta( $event_id, '_event_address', true ),
			'city'        => get_post_meta( $event_id, '_event_city', true ),
			'latitude'    => get_post_meta( $event_id, '_event_latitude', true ),
			'longitude'   => get_post_meta( $event_id, '_event_longitude', true ),
			'tickets_url' => get_post_meta( $event_id, '_tickets_ext', true ),
			'video_url'   => get_post_meta( $event_id, '_event_video_url', true ),
			'featured'    => (bool) get_post_meta( $event_id, '_event_featured', true ),
			'categories'  => wp_get_object_terms( $event_id, 'event_listing_category', array( 'fields' => 'all' ) ),
			'sounds'      => wp_get_object_terms( $event_id, 'event_sounds', array( 'fields' => 'all' ) ),
			'dj_ids'      => get_post_meta( $event_id, '_event_dj_ids', true ) ?: array(),
			'local_ids'   => get_post_meta( $event_id, '_event_local_ids', true ) ?: array(),
		);
	}

	/**
	 * Render single event HTML
	 *
	 * @param array  $event  Event data.
	 * @param string $layout Layout type.
	 * @return string
	 */
	private function render_single_event_html( array $event, string $layout ): string {
		ob_start();
		?>
		<article class="apollo-event-single apollo-event-single--<?php echo esc_attr( $layout ); ?>">
			<?php if ( $event['banner'] || $event['thumbnail'] ) : ?>
				<div class="apollo-event-single__hero">
					<img src="<?php echo esc_url( $event['banner'] ?: $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>">
				</div>
			<?php endif; ?>

			<div class="apollo-event-single__content">
				<header class="apollo-event-single__header">
					<h1 class="apollo-event-single__title"><?php echo esc_html( $event['title'] ); ?></h1>

					<div class="apollo-event-single__meta">
						<?php if ( $event['start_date'] ) : ?>
							<div class="apollo-event-single__date">
								<i class="ri-calendar-event-line"></i>
								<span><?php echo esc_html( wp_date( 'd \d\e F \d\e Y', strtotime( $event['start_date'] ) ) ); ?></span>
								<?php if ( $event['start_time'] ) : ?>
									<span>às <?php echo esc_html( $event['start_time'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $event['location'] || $event['address'] ) : ?>
							<div class="apollo-event-single__location">
								<i class="ri-map-pin-line"></i>
								<span><?php echo esc_html( $event['location'] ?: $event['address'] ); ?></span>
								<?php if ( $event['city'] ) : ?>
									<span>- <?php echo esc_html( $event['city'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $event['categories'] ) || ! empty( $event['sounds'] ) ) : ?>
						<div class="apollo-event-single__tags">
							<?php foreach ( $event['categories'] as $term ) : ?>
								<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="apollo-tag apollo-tag--category">
									<?php echo esc_html( $term->name ); ?>
								</a>
							<?php endforeach; ?>
							<?php foreach ( $event['sounds'] as $term ) : ?>
								<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="apollo-tag apollo-tag--sound">
									<?php echo esc_html( $term->name ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</header>

				<div class="apollo-event-single__body">
					<?php echo wp_kses_post( $event['content'] ); ?>
				</div>

				<?php if ( $event['tickets_url'] ) : ?>
					<div class="apollo-event-single__cta">
						<a href="<?php echo esc_url( $event['tickets_url'] ); ?>" class="apollo-btn apollo-btn--primary apollo-btn--lg" target="_blank" rel="noopener">
							<i class="ri-ticket-line"></i>
							<?php esc_html_e( 'Comprar Ingressos', 'apollo-events-manager' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php if ( $event['latitude'] && $event['longitude'] ) : ?>
					<div class="apollo-event-single__map" data-lat="<?php echo esc_attr( $event['latitude'] ); ?>" data-lng="<?php echo esc_attr( $event['longitude'] ); ?>">
						<div id="apollo-event-map-<?php echo esc_attr( (string) $event['id'] ); ?>" class="apollo-map" style="height: 300px;"></div>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_event_calendar] - Calendar View
	// =========================================================================

	/**
	 * Render event calendar shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_event_calendar( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'type'     => '',
				'months'   => 3,
			),
			$atts,
			'apollo_event_calendar'
		);

		$this->enqueue_assets();
		wp_enqueue_script( 'apollo-calendar' );

		// Get events for the next X months.
		$start_date = current_time( 'Y-m-d' );
		$end_date   = wp_date( 'Y-m-d', strtotime( '+' . absint( $atts['months'] ) . ' months' ) );

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$query  = new WP_Query( $args );
		$events = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$event_id   = get_the_ID();
			$start_date = get_post_meta( $event_id, '_event_start_date', true );

			$events[] = array(
				'id'        => $event_id,
				'title'     => get_the_title(),
				'start'     => $start_date,
				'end'       => get_post_meta( $event_id, '_event_end_date', true ) ?: $start_date,
				'url'       => get_permalink(),
				'className' => 'apollo-calendar-event',
			);
		}
		wp_reset_postdata();

		$calendar_id = 'apollo-calendar-' . wp_unique_id();

		ob_start();
		?>
		<div class="apollo-event-calendar" id="<?php echo esc_attr( $calendar_id ); ?>">
			<div class="apollo-calendar__header">
				<button type="button" class="apollo-btn apollo-btn--ghost" data-action="prev">
					<i class="ri-arrow-left-s-line"></i>
				</button>
				<h3 class="apollo-calendar__title"></h3>
				<button type="button" class="apollo-btn apollo-btn--ghost" data-action="next">
					<i class="ri-arrow-right-s-line"></i>
				</button>
			</div>
			<div class="apollo-calendar__body"></div>
		</div>
		<script type="application/json" id="<?php echo esc_attr( $calendar_id ); ?>-data">
			<?php echo wp_json_encode( $events ); ?>
		</script>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_featured_events] - Featured Events
	// =========================================================================

	/**
	 * Render featured events shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_featured_events( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit'   => 4,
				'layout'  => 'grid',
				'columns' => 2,
			),
			$atts,
			'apollo_featured_events'
		);

		$atts['featured'] = 'true';
		$atts['upcoming'] = 'true';

		return $this->render_events( $atts );
	}

	// =========================================================================
	// [apollo_upcoming_events] - Upcoming Events Widget
	// =========================================================================

	/**
	 * Render upcoming events shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_upcoming_events( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit'  => 5,
				'layout' => 'list',
			),
			$atts,
			'apollo_upcoming_events'
		);

		$atts['upcoming'] = 'true';
		$atts['orderby']  = 'event_date';
		$atts['order']    = 'ASC';

		return $this->render_events( $atts );
	}

	// =========================================================================
	// [apollo_event_card] - Single Event Card
	// =========================================================================

	/**
	 * Render single event card shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_event_card( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'apollo_event_card'
		);

		$event_id = absint( $atts['id'] );

		if ( ! $event_id ) {
			return '';
		}

		$post = get_post( $event_id );

		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return '';
		}

		$this->enqueue_assets();

		$event = array(
			'id'             => $event_id,
			'title'          => get_the_title( $event_id ),
			'excerpt'        => wp_trim_words( get_the_excerpt( $event_id ), 20 ),
			'permalink'      => get_permalink( $event_id ),
			'thumbnail'      => $this->get_event_image( $event_id ),
			'start_date'     => get_post_meta( $event_id, '_event_start_date', true ),
			'start_time'     => get_post_meta( $event_id, '_event_start_time', true ),
			'location'       => get_post_meta( $event_id, '_event_location', true ),
			'city'           => get_post_meta( $event_id, '_event_city', true ),
			'featured'       => (bool) get_post_meta( $event_id, '_event_featured', true ),
			'tickets_url'    => get_post_meta( $event_id, '_tickets_ext', true ),
			'sounds'         => wp_get_object_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) ),
			'formatted_date' => $this->format_event_date( $event_id ),
		);

		return $this->render_event_card_html( $event );
	}

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Get event image (banner or thumbnail)
	 *
	 * @param int $event_id Event ID.
	 * @return string
	 */
	private function get_event_image( int $event_id ): string {
		$banner = get_post_meta( $event_id, '_event_banner', true );

		if ( $banner ) {
			return $banner;
		}

		return get_the_post_thumbnail_url( $event_id, 'large' ) ?: '';
	}

	/**
	 * Format event date
	 *
	 * @param int $event_id Event ID.
	 * @return string
	 */
	private function format_event_date( int $event_id ): string {
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$start_time = get_post_meta( $event_id, '_event_start_time', true );

		if ( ! $start_date ) {
			return '';
		}

		$formatted = wp_date( 'd M Y', strtotime( $start_date ) );

		if ( $start_time ) {
			$formatted .= ' • ' . $start_time;
		}

		return $formatted;
	}

	/**
	 * Render empty state
	 *
	 * @param string $message Message to display.
	 * @return string
	 */
	private function render_empty_state( string $message ): string {
		return sprintf(
			'<div class="apollo-empty-state"><i class="ri-calendar-line"></i><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Enqueue required assets
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		// Enqueue global Apollo assets via CDN loader.
		if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
			apollo_enqueue_global_assets();
		} else {
			// Fallback: CDN loader script auto-loads all needed styles (index.css, icons, etc.)
			wp_enqueue_script( 'apollo-cdn-loader', 'https://assets.apollo.rio.br/index.min.js', array(), '4.3.0', true );
		}

		// RemixIcon is included in CDN styles/index.css - no need to load separately
	}
}

// Initialize on plugins_loaded.
add_action(
	'init',
	function () {
		EventShortcodes::get_instance();
	},
	20
);
