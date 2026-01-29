<?php
/**
 * Apollo Event Calendar Block - Server-Side Render
 *
 * Renders events in a calendar view with monthly navigation.
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
$category         = $attributes['category'] ?? '';
$type             = $attributes['type'] ?? '';
$sounds           = $attributes['sounds'] ?? '';
$season           = $attributes['season'] ?? '';
$show_navigation  = $attributes['showNavigation'] ?? true;
$show_weekdays    = $attributes['showWeekdays'] ?? true;
$show_event_count = $attributes['showEventCount'] ?? true;
$show_event_list  = $attributes['showEventList'] ?? true;
$initial_month    = $attributes['initialMonth'] ?? '';
$initial_year     = $attributes['initialYear'] ?? '';
$class_name       = $attributes['className'] ?? '';

// Determine current month/year.
$current_month = ! empty( $initial_month ) ? (int) $initial_month : (int) current_time( 'm' );
$current_year  = ! empty( $initial_year ) ? (int) $initial_year : (int) current_time( 'Y' );

// Get month bounds.
$first_day_of_month = new DateTime( "{$current_year}-{$current_month}-01" );
$last_day_of_month  = new DateTime( $first_day_of_month->format( 'Y-m-t' ) );
$days_in_month      = (int) $first_day_of_month->format( 't' );
$first_weekday      = (int) $first_day_of_month->format( 'w' ); // 0 = Sunday.

// Query events for this month.
$query_args = array(
	'post_type'      => 'event_listing',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'meta_query'     => array(
		'relation' => 'AND',
		array(
			'key'     => '_event_start_date',
			'value'   => $first_day_of_month->format( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		),
		array(
			'key'     => '_event_start_date',
			'value'   => $last_day_of_month->format( 'Y-m-d' ),
			'compare' => '<=',
			'type'    => 'DATE',
		),
	),
	'orderby'        => 'meta_value',
	'meta_key'       => '_event_start_date',
	'order'          => 'ASC',
);

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

$events_query = new WP_Query( $query_args );

// Organize events by day.
$events_by_day = array();
if ( $events_query->have_posts() ) {
	while ( $events_query->have_posts() ) {
		$events_query->the_post();
		$event_id   = get_the_ID();
		$event_date = get_post_meta( $event_id, '_event_start_date', true );

		if ( $event_date ) {
			$day = (int) date( 'j', strtotime( $event_date ) );
			if ( ! isset( $events_by_day[ $day ] ) ) {
				$events_by_day[ $day ] = array();
			}
			$events_by_day[ $day ][] = array(
				'id'       => $event_id,
				'title'    => get_post_meta( $event_id, '_event_title', true ) ?: get_the_title(),
				'time'     => get_post_meta( $event_id, '_event_start_time', true ),
				'location' => get_post_meta( $event_id, '_event_location', true ),
				'link'     => get_permalink(),
			);
		}
	}
	wp_reset_postdata();
}

// Month names in Portuguese.
$month_names = array(
	1  => __( 'Janeiro', 'apollo-events-manager' ),
	2  => __( 'Fevereiro', 'apollo-events-manager' ),
	3  => __( 'Março', 'apollo-events-manager' ),
	4  => __( 'Abril', 'apollo-events-manager' ),
	5  => __( 'Maio', 'apollo-events-manager' ),
	6  => __( 'Junho', 'apollo-events-manager' ),
	7  => __( 'Julho', 'apollo-events-manager' ),
	8  => __( 'Agosto', 'apollo-events-manager' ),
	9  => __( 'Setembro', 'apollo-events-manager' ),
	10 => __( 'Outubro', 'apollo-events-manager' ),
	11 => __( 'Novembro', 'apollo-events-manager' ),
	12 => __( 'Dezembro', 'apollo-events-manager' ),
);

$weekday_names = array(
	__( 'Dom', 'apollo-events-manager' ),
	__( 'Seg', 'apollo-events-manager' ),
	__( 'Ter', 'apollo-events-manager' ),
	__( 'Qua', 'apollo-events-manager' ),
	__( 'Qui', 'apollo-events-manager' ),
	__( 'Sex', 'apollo-events-manager' ),
	__( 'Sáb', 'apollo-events-manager' ),
);

// Previous/next month links.
$prev_month = $current_month - 1;
$prev_year  = $current_year;
if ( $prev_month < 1 ) {
	$prev_month = 12;
	--$prev_year;
}

$next_month = $current_month + 1;
$next_year  = $current_year;
if ( $next_month > 12 ) {
	$next_month = 1;
	++$next_year;
}

$today       = (int) current_time( 'j' );
$today_month = (int) current_time( 'm' );
$today_year  = (int) current_time( 'Y' );

// Generate unique ID for this calendar.
$calendar_id = 'apollo-calendar-' . wp_unique_id();

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-event-calendar-block',
	'apollo-event-calendar',
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'         => implode( ' ', $wrapper_classes ),
		'id'            => $calendar_id,
		'data-month'    => $current_month,
		'data-year'     => $current_year,
		'data-category' => $category,
		'data-type'     => $type,
		'data-sounds'   => $sounds,
		'data-season'   => $season,
	)
);
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $show_navigation ) : ?>
	<div class="apollo-event-calendar__header">
		<button
			type="button"
			class="apollo-event-calendar__nav-btn apollo-event-calendar__nav-btn--prev"
			data-month="<?php echo esc_attr( $prev_month ); ?>"
			data-year="<?php echo esc_attr( $prev_year ); ?>"
			aria-label="<?php esc_attr_e( 'Mês anterior', 'apollo-events-manager' ); ?>"
		>
			<i class="ri-arrow-left-s-line"></i>
		</button>
		<h3 class="apollo-event-calendar__title">
			<?php echo esc_html( $month_names[ $current_month ] . ' ' . $current_year ); ?>
		</h3>
		<button
			type="button"
			class="apollo-event-calendar__nav-btn apollo-event-calendar__nav-btn--next"
			data-month="<?php echo esc_attr( $next_month ); ?>"
			data-year="<?php echo esc_attr( $next_year ); ?>"
			aria-label="<?php esc_attr_e( 'Próximo mês', 'apollo-events-manager' ); ?>"
		>
			<i class="ri-arrow-right-s-line"></i>
		</button>
	</div>
	<?php endif; ?>

	<?php if ( $show_weekdays ) : ?>
	<div class="apollo-event-calendar__weekdays">
		<?php foreach ( $weekday_names as $weekday ) : ?>
			<div class="apollo-event-calendar__weekday">
				<?php echo esc_html( $weekday ); ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="apollo-calendar-grid">
		<?php
		// Empty cells before first day.
		for ( $i = 0; $i < $first_weekday; $i++ ) :
			?>
			<div class="apollo-calendar-day apollo-calendar-day--empty"></div>
		<?php endfor; ?>

		<?php
		// Days of the month.
		for ( $day = 1; $day <= $days_in_month; $day++ ) :
			$is_today    = ( $day === $today && $current_month === $today_month && $current_year === $today_year );
			$has_events  = isset( $events_by_day[ $day ] );
			$event_count = $has_events ? count( $events_by_day[ $day ] ) : 0;
			$day_classes = array( 'apollo-calendar-day' );

			if ( $is_today ) {
				$day_classes[] = 'apollo-calendar-day--today';
			}
			if ( $has_events ) {
				$day_classes[] = 'apollo-calendar-day--has-events';
			}
			?>
			<div
				class="<?php echo esc_attr( implode( ' ', $day_classes ) ); ?>"
				<?php if ( $has_events ) : ?>
					data-day="<?php echo esc_attr( $day ); ?>"
					data-events='<?php echo esc_attr( wp_json_encode( $events_by_day[ $day ] ) ); ?>'
					role="button"
					tabindex="0"
				<?php endif; ?>
			>
				<span class="apollo-calendar-day__number"><?php echo esc_html( $day ); ?></span>
				<?php if ( $show_event_count && $has_events ) : ?>
					<span class="apollo-calendar-day__count"><?php echo esc_html( $event_count ); ?></span>
				<?php endif; ?>
			</div>
		<?php endfor; ?>
	</div>

	<?php if ( $show_event_list ) : ?>
	<div class="apollo-event-calendar__events" aria-live="polite">
		<div class="apollo-event-calendar__events-header">
			<h4 class="apollo-event-calendar__events-title">
				<?php esc_html_e( 'Eventos do dia', 'apollo-events-manager' ); ?>
			</h4>
		</div>
		<div class="apollo-event-calendar__events-list">
			<p class="apollo-event-calendar__events-empty">
				<?php esc_html_e( 'Clique em um dia para ver os eventos.', 'apollo-events-manager' ); ?>
			</p>
		</div>
	</div>
	<?php endif; ?>
</div>

<script>
(function() {
	const calendar = document.getElementById('<?php echo esc_js( $calendar_id ); ?>');
	if (!calendar) return;

	const eventsList = calendar.querySelector('.apollo-event-calendar__events-list');
	const days = calendar.querySelectorAll('.apollo-calendar-day--has-events');

	days.forEach(day => {
		const handleClick = () => {
			// Remove active class from all days.
			days.forEach(d => d.classList.remove('apollo-calendar-day--active'));
			day.classList.add('apollo-calendar-day--active');

			// Get events data.
			const events = JSON.parse(day.dataset.events || '[]');
			const dayNum = day.dataset.day;

			if (eventsList && events.length > 0) {
				let html = `<h5 class="apollo-event-calendar__day-title"><?php echo esc_js( __( 'Dia', 'apollo-events-manager' ) ); ?> ${dayNum}</h5>`;
				html += '<ul class="apollo-event-calendar__event-items">';

				events.forEach(event => {
					html += `
						<li class="apollo-event-calendar__event-item">
							<a href="${event.link}" class="apollo-event-calendar__event-link">
								<span class="apollo-event-calendar__event-time">${event.time || ''}</span>
								<span class="apollo-event-calendar__event-title">${event.title}</span>
								${event.location ? `<span class="apollo-event-calendar__event-location"><i class="ri-map-pin-line"></i> ${event.location}</span>` : ''}
							</a>
						</li>
					`;
				});

				html += '</ul>';
				eventsList.innerHTML = html;
			}
		};

		day.addEventListener('click', handleClick);
		day.addEventListener('keypress', (e) => {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				handleClick();
			}
		});
	});

	// Navigation buttons (would need AJAX for full implementation).
	const navButtons = calendar.querySelectorAll('.apollo-event-calendar__nav-btn');
	navButtons.forEach(btn => {
		btn.addEventListener('click', function() {
			const month = this.dataset.month;
			const year = this.dataset.year;

			// For now, reload the page with new month/year params.
			// In a full implementation, this would use AJAX.
			const url = new URL(window.location.href);
			url.searchParams.set('apollo_cal_month', month);
			url.searchParams.set('apollo_cal_year', year);
			window.location.href = url.toString();
		});
	});
})();
</script>

<style>
#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-grid {
	display: grid;
	grid-template-columns: repeat(7, 1fr);
	gap: 4px;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day {
	aspect-ratio: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	background: #f8fafc;
	border-radius: 8px;
	font-size: 0.875rem;
	color: #475569;
	position: relative;
	transition: all 0.2s ease;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--empty {
	background: transparent;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--has-events {
	background: #eef2ff;
	color: #4f46e5;
	font-weight: 600;
	cursor: pointer;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--has-events:hover,
#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--active {
	background: #4f46e5;
	color: #fff;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--today {
	box-shadow: inset 0 0 0 2px #4f46e5;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day__count {
	position: absolute;
	top: 4px;
	right: 4px;
	background: #4f46e5;
	color: #fff;
	font-size: 0.625rem;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}

#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--has-events:hover .apollo-calendar-day__count,
#<?php echo esc_attr( $calendar_id ); ?> .apollo-calendar-day--active .apollo-calendar-day__count {
	background: #fff;
	color: #4f46e5;
}
</style>
