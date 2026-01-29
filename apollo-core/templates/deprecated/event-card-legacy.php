<?php
/**
 * Event Card Partial - Apollo Design System
 *
 * Displays an event card with date cutout, image, tags, and content
 *
 * @param array $args {
 *     @type int    $event_id        Event ID
 *     @type string $image_url       Event image URL
 *     @type string $title           Event title
 *     @type string $date_day        Day number (e.g., '25')
 *     @type string $date_month      Month abbreviation (e.g., 'out')
 *     @type array  $tags            Array of tag strings
 *     @type string $dj_name         DJ/Artist name
 *     @type string $venue_name      Venue name
 *     @type string $event_url       Link URL (default: '#')
 *     @type string $category        Event category for filtering
 *     @type string $month_str       Month string for filtering
 * }
 */

// Set defaults.
$args = wp_parse_args(
	$args ?? array(),
	array(
		'event_id'   => 0,
		'image_url'  => '',
		'title'      => '',
		'date_day'   => '',
		'date_month' => '',
		'tags'       => array(),
		'dj_name'    => '',
		'venue_name' => '',
		'event_url'  => '#',
		'category'   => '',
		'month_str'  => '',
	)
);

// Escape all outputs.
$event_id   = intval( $args['event_id'] );
$image_url  = esc_url( $args['image_url'] );
$title      = esc_html( $args['title'] );
$date_day   = esc_html( $args['date_day'] );
$date_month = esc_html( $args['date_month'] );
$dj_name    = esc_html( $args['dj_name'] );
$venue_name = esc_html( $args['venue_name'] );
$event_url  = esc_url( $args['event_url'] );
$category   = esc_attr( $args['category'] );
$month_str  = esc_attr( $args['month_str'] );

// Sanitize tags.
$tags = array_map( 'esc_html', (array) $args['tags'] );
?>

<a href="<?php echo $event_url; ?>"
	class="event_listing"
	data-event-id="<?php echo $event_id; ?>"
	data-category="<?php echo $category; ?>"
	data-month-str="<?php echo $month_str; ?>">

	<!-- Date box positioned outside picture for cutout effect -->
	<?php if ( $date_day && $date_month ) : ?>
		<div class="box-date-event">
			<span class="date-day"><?php echo $date_day; ?></span>
			<span class="date-month"><?php echo $date_month; ?></span>
		</div>
	<?php endif; ?>

	<div class="picture">
		<?php if ( $image_url ) : ?>
			<img src="<?php echo $image_url; ?>"
				alt="<?php echo $title ?: 'Event image'; ?>"
				loading="lazy">
		<?php endif; ?>

		<!-- Event card tags -->
		<?php if ( ! empty( $tags ) ) : ?>
			<div class="event-card-tags">
				<?php foreach ( $tags as $tag ) : ?>
					<span><?php echo $tag; ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="event-line">
		<div class="box-info-event">
			<?php if ( $title ) : ?>
				<h2 class="event-li-title"><?php echo $title; ?></h2>
			<?php endif; ?>

			<?php if ( $dj_name ) : ?>
				<p class="event-li-detail of-dj">
					<i class="ri-sound-module-fill"></i>
					<span><?php echo $dj_name; ?></span>
				</p>
			<?php endif; ?>

			<?php if ( $venue_name ) : ?>
				<p class="event-li-detail of-location">
					<i class="ri-map-pin-2-line"></i>
					<span><?php echo $venue_name; ?></span>
				</p>
			<?php endif; ?>
		</div>
	</div>
</a>

<style>
/* Event Card Styles - APOLLO DESIGN SYSTEM */
.event_listing {
	cursor: pointer;
	position: relative;
	transition: transform 0.4s ease;
	display: block;
	animation: fadeIn 0.5s ease;
	max-width: 300px;
	width: 100%;
}

.event_listing:hover {
	transform: translateY(-5px);
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(10px); }
	to { opacity: 1; transform: translateY(0); }
}

/* Card Picture with Cutout Effect */
.event_listing .picture {
	/* CSS variables for mask shape */
	--r: 12px;
	--s: 12px;
	--x: 48px;
	--y: 42px;

	height: 400px;
	position: relative;
	border-radius: 12px;
	overflow: hidden;
	box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
	border: 1px solid rgba(0, 0, 0, 0.13);
	transition: transform 0.4s ease, box-shadow 0.4s ease;

	border-radius: var(--r);
	/* Complex mask for date cutout effect */
	--_m: calc(2*var(--r)) calc(2*var(--r)) radial-gradient(#000 70%,#0000 72%);
	--_g: conic-gradient(at var(--r) var(--r),#000 75%,#0000 0);
	--_d: (var(--s) + var(--r));
	mask:
		calc(var(--_d) + var(--x)) 0 var(--_m),
		0 calc(var(--_d) + var(--y)) var(--_m),
		radial-gradient(var(--s) at 0 0,#0000 99%,#000 calc(100% + 1px))
		calc(var(--r) + var(--x)) calc(var(--r) + var(--y)),
		var(--_g) calc(var(--_d) + var(--x)) 0,
		var(--_g) 0 calc(var(--_d) + var(--y));
	mask-repeat: no-repeat;
}

.event_listing:hover .picture {
	transform: scale(1.05);
	box-shadow: 0 15px 40px rgba(0, 0, 0, 0.05);
}

.event_listing .picture img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.4s ease;
}

/* Date Box positioned in cutout */
.box-date-event {
	position: absolute;
	top: 5px;
	left: 7px;
	width: 60px;
	height: 54px;
	text-align: center;
	color: rgba(19, 21, 23, 0.85);
	display: flex;
	flex-direction: column;
	justify-content: center;
	line-height: 1.1;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	z-index: 2;
}

.date-day {
	font-size: 1.6em;
	font-weight: 700;
	color: rgba(19, 21, 23, 0.85);
	display: block;
	text-shadow: none;
}

.date-month {
	font-size: 0.9em;
	font-weight: 600;
	text-transform: uppercase;
	color: rgba(19, 21, 23, 0.7);
	margin-left: 0;
	text-shadow: none;
}

/* Event Card Tags */
.event-card-tags {
	position: absolute;
	bottom: 10px;
	right: 10px;
	display: flex;
	gap: 10px;
	pointer-events: none;
	z-index: 3;
}

.event-card-tags span {
	padding: 2px 8px;
	border-radius: 4px;
	border: 1px solid rgba(255, 255, 255, 0.2);
	background: linear-gradient(30deg, rgba(255, 255, 255, 0.1) -49%, rgba(255, 255, 255, 0.35) 160%);
	backdrop-filter: blur(3px);
	font-size: 0.625rem;
	color: rgba(255, 255, 255, 0.8);
	font-weight: 600;
	text-transform: uppercase;
}

/* Card Content */
.event-line {
	padding: 1.25em 0.5rem;
	width: 100%;
	transition: 0.3s ease;
	word-break: break-word;
}

.event-li-title {
	font-size: 1.1rem;
	font-weight: 700;
	color: rgba(19, 21, 23, 0.85);
	line-height: 1.3;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	margin-bottom: 0.5rem;
}

.event-li-detail {
	color: rgba(19, 21, 23, 0.7);
	font-size: 0.84rem;
	display: flex;
	align-items: center;
	gap: 6px;
	margin-bottom: 0.25rem;
}

.event-li-detail > i {
	font-size: 0.9rem;
	flex-shrink: 0;
}

.of-dj, .of-location {
	font-size: 0.82rem;
	font-weight: 400;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
}

/* Mobile responsive adjustments */
@media (max-width: 767px) {
	.event_listing .picture {
		height: 300px;
		--x: 38px;
		--y: 30px;
	}

	.box-date-event {
		width: 50px;
		height: 42px;
	}

	.date-day {
		font-size: 1.4em;
	}

	.date-month {
		font-size: 0.8em;
	}
}
</style>
