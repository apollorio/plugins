<?php
/**
 * Apollo Events Grid Block - Server-Side Render
 *
 * Renders the events grid block using the same template loader as shortcodes.
 *
 * @package Apollo_Events_Manager
 * @subpackage Blocks
 * @since 2.0.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract attributes with defaults.
$layout          = $attributes['layout'] ?? 'grid';
$columns         = $attributes['columns'] ?? 3;
$limit           = $attributes['limit'] ?? 6;
$category        = $attributes['category'] ?? '';
$type            = $attributes['type'] ?? '';
$sounds          = $attributes['sounds'] ?? '';
$season          = $attributes['season'] ?? '';
$orderby         = $attributes['orderby'] ?? 'event_date';
$order           = $attributes['order'] ?? 'asc';
$featured        = $attributes['featured'] ?? false;
$show_image      = $attributes['showImage'] ?? true;
$show_date       = $attributes['showDate'] ?? true;
$show_location   = $attributes['showLocation'] ?? true;
$show_excerpt    = $attributes['showExcerpt'] ?? false;
$show_pagination = $attributes['showPagination'] ?? false;
$class_name      = $attributes['className'] ?? '';

// Build query args.
$query_args = array(
	'post_type'      => 'event_listing',
	'post_status'    => 'publish',
	'posts_per_page' => (int) $limit,
	'order'          => strtoupper( $order ),
);

// Handle ordering.
if ( 'event_date' === $orderby ) {
	$query_args['meta_key'] = '_event_start_date';
	$query_args['orderby']  = 'meta_value';
} elseif ( 'rand' === $orderby ) {
	$query_args['orderby'] = 'rand';
} else {
	$query_args['orderby'] = $orderby;
}

// Build tax query.
$tax_query = array();

if ( ! empty( $category ) ) {
	$tax_query[] = array(
		'taxonomy' => 'event_listing_category',
		'field'    => 'term_id',
		'terms'    => array_map( 'intval', explode( ',', $category ) ),
	);
}

if ( ! empty( $type ) ) {
	$tax_query[] = array(
		'taxonomy' => 'event_listing_type',
		'field'    => 'term_id',
		'terms'    => array_map( 'intval', explode( ',', $type ) ),
	);
}

if ( ! empty( $sounds ) ) {
	$tax_query[] = array(
		'taxonomy' => 'event_sounds',
		'field'    => 'term_id',
		'terms'    => array_map( 'intval', explode( ',', $sounds ) ),
	);
}

if ( ! empty( $season ) ) {
	$tax_query[] = array(
		'taxonomy' => 'event_season',
		'field'    => 'term_id',
		'terms'    => array_map( 'intval', explode( ',', $season ) ),
	);
}

if ( ! empty( $tax_query ) ) {
	$tax_query['relation']   = 'AND';
	$query_args['tax_query'] = $tax_query;
}

// Build meta query.
$meta_query = array();

if ( $featured ) {
	$meta_query[] = array(
		'key'     => '_event_featured',
		'value'   => '1',
		'compare' => '=',
	);
}

// Filter to only upcoming events by default.
$meta_query[] = array(
	'relation' => 'OR',
	array(
		'key'     => '_event_start_date',
		'value'   => current_time( 'Y-m-d' ),
		'compare' => '>=',
		'type'    => 'DATE',
	),
	array(
		'key'     => '_event_end_date',
		'value'   => current_time( 'Y-m-d' ),
		'compare' => '>=',
		'type'    => 'DATE',
	),
);

if ( ! empty( $meta_query ) ) {
	$meta_query['relation']   = 'AND';
	$query_args['meta_query'] = $meta_query;
}

// Handle pagination.
if ( $show_pagination ) {
	$paged               = get_query_var( 'paged' ) ?: 1;
	$query_args['paged'] = $paged;
}

// Execute query.
$events_query = new WP_Query( $query_args );

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-events-grid-block',
	'apollo-events-grid',
	"apollo-events-grid--{$layout}",
	"apollo-events-grid--cols-{$columns}",
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $wrapper_classes ),
	)
);

// Start output.
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $events_query->have_posts() ) : ?>

		<?php if ( 'carousel' === $layout ) : ?>
		<div class="apollo-events-carousel swiper" data-columns="<?php echo esc_attr( $columns ); ?>">
			<div class="swiper-wrapper">
		<?php endif; ?>

		<?php
		while ( $events_query->have_posts() ) :
			$events_query->the_post();
			$event_id = get_the_ID();

			// Get event meta.
			$event_title    = get_post_meta( $event_id, '_event_title', true ) ?: get_the_title();
			$event_banner   = get_post_meta( $event_id, '_event_banner', true );
			$event_date     = get_post_meta( $event_id, '_event_start_date', true );
			$event_time     = get_post_meta( $event_id, '_event_start_time', true );
			$event_end_date = get_post_meta( $event_id, '_event_end_date', true );
			$event_location = get_post_meta( $event_id, '_event_location', true );
			$event_city     = get_post_meta( $event_id, '_event_city', true );
			$tickets_url    = get_post_meta( $event_id, '_tickets_ext', true );
			$is_featured    = get_post_meta( $event_id, '_event_featured', true );

			// Format date.
			$formatted_date = '';
			if ( $event_date ) {
				$date_obj = DateTime::createFromFormat( 'Y-m-d', $event_date );
				if ( $date_obj ) {
					$formatted_date = wp_date( get_option( 'date_format' ), $date_obj->getTimestamp() );
					if ( $event_time ) {
						$formatted_date .= ' ' . esc_html( $event_time );
					}
				}
			}

			// Get featured image if no banner.
			if ( empty( $event_banner ) && has_post_thumbnail( $event_id ) ) {
				$event_banner = get_the_post_thumbnail_url( $event_id, 'large' );
			}

			// Card classes.
			$card_classes = array( 'apollo-event-card' );
			if ( $is_featured ) {
				$card_classes[] = 'apollo-event-card--featured';
			}
			if ( 'carousel' === $layout ) {
				$card_classes[] = 'swiper-slide';
			}
			?>

			<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" data-event-id="<?php echo absint( $event_id ); ?>">
				<?php if ( $show_image ) : ?>
					<div class="apollo-event-card__image">
						<a href="<?php the_permalink(); ?>">
							<?php if ( $event_banner ) : ?>
								<img
									src="<?php echo esc_url( $event_banner ); ?>"
									alt="<?php echo esc_attr( $event_title ); ?>"
									loading="lazy"
								/>
							<?php else : ?>
								<div class="apollo-event-card__image-placeholder">
									<i class="ri-calendar-event-line"></i>
								</div>
							<?php endif; ?>
						</a>
						<?php if ( $is_featured ) : ?>
							<span class="apollo-event-card__badge apollo-event-card__badge--featured">
								<i class="ri-star-fill"></i>
								<?php esc_html_e( 'Destaque', 'apollo-events-manager' ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="apollo-event-card__content">
					<h3 class="apollo-event-card__title">
						<a href="<?php the_permalink(); ?>">
							<?php echo esc_html( $event_title ); ?>
						</a>
					</h3>

					<div class="apollo-event-card__meta">
						<?php if ( $show_date && $formatted_date ) : ?>
							<span class="apollo-event-card__meta-item apollo-event-card__date">
								<i class="ri-calendar-line"></i>
								<?php echo esc_html( $formatted_date ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $show_location && ( $event_location || $event_city ) ) : ?>
							<span class="apollo-event-card__meta-item apollo-event-card__location">
								<i class="ri-map-pin-line"></i>
								<?php echo esc_html( $event_location ?: $event_city ); ?>
							</span>
						<?php endif; ?>
					</div>

					<?php if ( $show_excerpt && has_excerpt() ) : ?>
						<p class="apollo-event-card__excerpt">
							<?php echo wp_kses_post( get_the_excerpt() ); ?>
						</p>
					<?php endif; ?>

					<div class="apollo-event-card__actions">
						<a href="<?php the_permalink(); ?>" class="apollo-btn apollo-btn--primary apollo-btn--sm">
							<?php esc_html_e( 'Ver Evento', 'apollo-events-manager' ); ?>
						</a>
						<?php if ( $tickets_url ) : ?>
							<a href="<?php echo esc_url( $tickets_url ); ?>" class="apollo-btn apollo-btn--outline apollo-btn--sm" target="_blank" rel="noopener noreferrer">
								<i class="ri-ticket-line"></i>
								<?php esc_html_e( 'Ingressos', 'apollo-events-manager' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</article>

		<?php endwhile; ?>

		<?php if ( 'carousel' === $layout ) : ?>
			</div><!-- .swiper-wrapper -->
			<div class="swiper-button-prev"></div>
			<div class="swiper-button-next"></div>
			<div class="swiper-pagination"></div>
		</div><!-- .apollo-events-carousel -->
		<?php endif; ?>

		<?php
		// Pagination.
		if ( $show_pagination && $events_query->max_num_pages > 1 ) :
			?>
			<nav class="apollo-pagination" aria-label="<?php esc_attr_e( 'Navegação de eventos', 'apollo-events-manager' ); ?>">
				<?php
				echo paginate_links(
					array(
						'total'     => $events_query->max_num_pages,
						'current'   => $paged ?? 1,
						'prev_text' => '<i class="ri-arrow-left-line"></i> ' . __( 'Anterior', 'apollo-events-manager' ),
						'next_text' => __( 'Próximo', 'apollo-events-manager' ) . ' <i class="ri-arrow-right-line"></i>',
						'type'      => 'list',
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>
		<div class="apollo-events-grid__empty">
			<div class="apollo-empty-state">
				<i class="ri-calendar-todo-line apollo-empty-state__icon"></i>
				<p class="apollo-empty-state__text">
					<?php esc_html_e( 'Nenhum evento encontrado.', 'apollo-events-manager' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php if ( 'carousel' === $layout ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	if (typeof Swiper !== 'undefined') {
		new Swiper('.apollo-events-carousel', {
			slidesPerView: 1,
			spaceBetween: 24,
			navigation: {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev',
			},
			pagination: {
				el: '.swiper-pagination',
				clickable: true,
			},
			breakpoints: {
				640: { slidesPerView: 2 },
				1024: { slidesPerView: <?php echo esc_js( $columns ); ?> },
			},
		});
	}
});
</script>
<?php endif; ?>
