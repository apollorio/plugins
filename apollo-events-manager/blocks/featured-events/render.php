<?php
/**
 * Apollo Featured Events Block - Server-Side Render
 *
 * Renders featured events in a carousel or grid layout.
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
$layout             = $attributes['layout'] ?? 'carousel';
$limit              = $attributes['limit'] ?? 5;
$autoplay           = $attributes['autoplay'] ?? true;
$autoplay_speed     = $attributes['autoplaySpeed'] ?? 5000;
$show_dots          = $attributes['showDots'] ?? true;
$show_arrows        = $attributes['showArrows'] ?? true;
$show_date          = $attributes['showDate'] ?? true;
$show_location      = $attributes['showLocation'] ?? true;
$show_ticket_button = $attributes['showTicketButton'] ?? true;
$aspect_ratio       = $attributes['aspectRatio'] ?? '21/9';
$class_name         = $attributes['className'] ?? '';

// Query featured events.
$query_args = array(
	'post_type'      => 'event_listing',
	'post_status'    => 'publish',
	'posts_per_page' => 'hero' === $layout ? 1 : (int) $limit,
	'meta_query'     => array(
		array(
			'key'     => '_event_featured',
			'value'   => '1',
			'compare' => '=',
		),
		array(
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
		),
	),
	'meta_key'       => '_event_start_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
);

$events_query = new WP_Query( $query_args );

if ( ! $events_query->have_posts() ) {
	return;
}

// Generate unique ID.
$carousel_id = 'apollo-featured-' . wp_unique_id();

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-featured-events-block',
	'apollo-featured-events',
	"apollo-featured-events--{$layout}",
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $wrapper_classes ),
		'id'    => $carousel_id,
		'style' => "--aspect-ratio: {$aspect_ratio};",
	)
);
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( 'carousel' === $layout ) : ?>
	<div class="apollo-featured-carousel swiper"
		data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
		data-autoplay-speed="<?php echo esc_attr( $autoplay_speed ); ?>">
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
		$event_location = get_post_meta( $event_id, '_event_location', true );
		$event_city     = get_post_meta( $event_id, '_event_city', true );
		$tickets_url    = get_post_meta( $event_id, '_tickets_ext', true );

		// Get featured image if no banner.
		if ( empty( $event_banner ) && has_post_thumbnail( $event_id ) ) {
			$event_banner = get_the_post_thumbnail_url( $event_id, 'full' );
		}

		// Format date.
		$formatted_date = '';
		if ( $event_date ) {
			$date_obj = DateTime::createFromFormat( 'Y-m-d', $event_date );
			if ( $date_obj ) {
				$formatted_date = wp_date( 'D, j M Y', $date_obj->getTimestamp() );
				if ( $event_time ) {
					$formatted_date .= ' · ' . esc_html( $event_time );
				}
			}
		}

		$card_classes = array( 'apollo-featured-event-card' );
		if ( 'carousel' === $layout ) {
			$card_classes[] = 'swiper-slide';
		}
		?>

		<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" data-event-id="<?php echo absint( $event_id ); ?>">
			<a href="<?php the_permalink(); ?>" class="apollo-featured-event-card__link">
				<div class="apollo-featured-event-card__image">
					<?php if ( $event_banner ) : ?>
						<img
							src="<?php echo esc_url( $event_banner ); ?>"
							alt="<?php echo esc_attr( $event_title ); ?>"
							loading="lazy"
						/>
					<?php else : ?>
						<div class="apollo-featured-event-card__placeholder">
							<i class="ri-calendar-event-line"></i>
						</div>
					<?php endif; ?>

					<div class="apollo-featured-event-card__overlay">
						<span class="apollo-featured-event-card__badge">
							<i class="ri-star-fill"></i>
							<?php esc_html_e( 'Destaque', 'apollo-events-manager' ); ?>
						</span>

						<div class="apollo-featured-event-card__content">
							<h3 class="apollo-featured-event-card__title">
								<?php echo esc_html( $event_title ); ?>
							</h3>

							<div class="apollo-featured-event-card__meta">
								<?php if ( $show_date && $formatted_date ) : ?>
									<span class="apollo-featured-event-card__date">
										<i class="ri-calendar-event-fill"></i>
										<?php echo esc_html( $formatted_date ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $show_location && ( $event_location || $event_city ) ) : ?>
									<span class="apollo-featured-event-card__location">
										<i class="ri-map-pin-fill"></i>
										<?php echo esc_html( $event_location ?: $event_city ); ?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $show_ticket_button ) : ?>
								<div class="apollo-featured-event-card__actions">
									<span class="apollo-btn apollo-btn--primary apollo-btn--glow">
										<?php esc_html_e( 'Ver Evento', 'apollo-events-manager' ); ?>
										<i class="ri-arrow-right-line"></i>
									</span>
									<?php if ( $tickets_url ) : ?>
										<span class="apollo-btn apollo-btn--outline apollo-btn--light">
											<i class="ri-ticket-fill"></i>
											<?php esc_html_e( 'Ingressos', 'apollo-events-manager' ); ?>
										</span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</a>
		</article>

	<?php endwhile; ?>

	<?php if ( 'carousel' === $layout ) : ?>
		</div><!-- .swiper-wrapper -->

		<?php if ( $show_arrows ) : ?>
			<div class="apollo-featured-carousel__nav">
				<button class="swiper-button-prev apollo-featured-carousel__btn" aria-label="<?php esc_attr_e( 'Anterior', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-left-line"></i>
				</button>
				<button class="swiper-button-next apollo-featured-carousel__btn" aria-label="<?php esc_attr_e( 'Próximo', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-right-line"></i>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( $show_dots ) : ?>
			<div class="swiper-pagination apollo-featured-carousel__dots"></div>
		<?php endif; ?>
	</div><!-- .apollo-featured-carousel -->
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>

<style>
#<?php echo esc_attr( $carousel_id ); ?> {
	--aspect-ratio: <?php echo esc_attr( $aspect_ratio ); ?>;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__image {
	aspect-ratio: var(--aspect-ratio, 21/9);
	position: relative;
	overflow: hidden;
	border-radius: 16px;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__placeholder {
	width: 100%;
	height: 100%;
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 4rem;
	color: rgba(255, 255, 255, 0.3);
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__overlay {
	position: absolute;
	inset: 0;
	background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 100%);
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	padding: 1.5rem;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__badge {
	align-self: flex-start;
	background: rgba(255, 255, 255, 0.2);
	backdrop-filter: blur(10px);
	padding: 0.5rem 1rem;
	border-radius: 50px;
	font-size: 0.75rem;
	font-weight: 600;
	color: #fff;
	display: flex;
	align-items: center;
	gap: 0.375rem;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__badge i {
	color: #fbbf24;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__content {
	color: #fff;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__title {
	font-size: clamp(1.5rem, 4vw, 2.5rem);
	font-weight: 700;
	margin: 0 0 0.75rem;
	line-height: 1.2;
	text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__meta {
	display: flex;
	flex-wrap: wrap;
	gap: 1rem;
	font-size: 0.9375rem;
	margin-bottom: 1rem;
	opacity: 0.9;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__meta span {
	display: flex;
	align-items: center;
	gap: 0.375rem;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 0.75rem;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__link {
	text-decoration: none;
	display: block;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-btn--glow {
	box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-btn--light {
	border-color: rgba(255, 255, 255, 0.5);
	color: #fff;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-carousel__btn {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	width: 48px;
	height: 48px;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.2);
	backdrop-filter: blur(10px);
	border: none;
	color: #fff;
	font-size: 1.25rem;
	cursor: pointer;
	z-index: 10;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: center;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-carousel__btn:hover {
	background: rgba(255, 255, 255, 0.3);
}

#<?php echo esc_attr( $carousel_id ); ?> .swiper-button-prev {
	left: 1rem;
}

#<?php echo esc_attr( $carousel_id ); ?> .swiper-button-next {
	right: 1rem;
}

#<?php echo esc_attr( $carousel_id ); ?> .swiper-button-prev::after,
#<?php echo esc_attr( $carousel_id ); ?> .swiper-button-next::after {
	display: none;
}

#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-carousel__dots {
	position: absolute;
	bottom: 1rem;
	left: 50%;
	transform: translateX(-50%);
	z-index: 10;
}

#<?php echo esc_attr( $carousel_id ); ?> .swiper-pagination-bullet {
	width: 10px;
	height: 10px;
	background: rgba(255, 255, 255, 0.5);
	opacity: 1;
}

#<?php echo esc_attr( $carousel_id ); ?> .swiper-pagination-bullet-active {
	background: #fff;
	width: 24px;
	border-radius: 5px;
}

/* Grid layout */
#<?php echo esc_attr( $carousel_id ); ?>.apollo-featured-events--grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 1.5rem;
}

#<?php echo esc_attr( $carousel_id ); ?>.apollo-featured-events--grid .apollo-featured-event-card__image {
	aspect-ratio: 16/9;
}

@media (max-width: 768px) {
	#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-carousel__btn {
		width: 36px;
		height: 36px;
	}

	#<?php echo esc_attr( $carousel_id ); ?> .apollo-featured-event-card__actions {
		flex-direction: column;
	}
}
</style>

<?php if ( 'carousel' === $layout ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	if (typeof Swiper !== 'undefined') {
		const container = document.querySelector('#<?php echo esc_js( $carousel_id ); ?> .apollo-featured-carousel');
		if (!container) return;

		const autoplay = container.dataset.autoplay === 'true';
		const autoplaySpeed = parseInt(container.dataset.autoplaySpeed, 10) || 5000;

		new Swiper(container, {
			slidesPerView: 1,
			spaceBetween: 0,
			loop: true,
			autoplay: autoplay ? {
				delay: autoplaySpeed,
				disableOnInteraction: false,
			} : false,
			navigation: {
				nextEl: '#<?php echo esc_js( $carousel_id ); ?> .swiper-button-next',
				prevEl: '#<?php echo esc_js( $carousel_id ); ?> .swiper-button-prev',
			},
			pagination: {
				el: '#<?php echo esc_js( $carousel_id ); ?> .swiper-pagination',
				clickable: true,
			},
			effect: 'fade',
			fadeEffect: {
				crossFade: true,
			},
		});
	}
});
</script>
<?php endif; ?>
