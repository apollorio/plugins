<?php
/**
 * Event Card Partial - Official Apollo Design v2
 * ===============================================
 * PHASE 5: Berlin Brutalism design with corner cutout
 * Matches the official home page card design from Apollo CDN
 *
 * @package Apollo_Events_Manager
 * @version 5.0.0 - Official Design System
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Support both ViewModel data and direct context
if ( isset( $event_data ) && is_array( $event_data ) ) {
	// ViewModel format - convert to new format
	$event_id       = $event_data['id'] ?? 0;
	$event_title    = $event_data['title'] ?? '';
	$event_url      = $event_data['permalink'] ?? '';
	$event_image    = $event_data['banner_url'] ?? '';
	$event_date     = $event_data['date']['iso_date'] ?? '';
	$event_location = $event_data['location_display'] ?? '';
	$event_djs      = array();
	$event_sounds   = $event_data['genres'] ?? array();
	$event_coupon   = '';
	$is_apollo      = false;
	$is_external    = false;
	$delay_index    = $delay_index ?? 100;

	// Parse DJ display into array
	if ( ! empty( $event_data['dj_display'] ) ) {
		$event_djs = array_map( 'trim', explode( ',', $event_data['dj_display'] ) );
	}
} elseif ( ! isset( $event_id ) ) {
	// No data provided
	return;
}

// Load the new template part
$template_path = dirname( __FILE__ ) . '/parts/event/card.php';

if ( file_exists( $template_path ) ) {
	include $template_path;
} else {
	// Inline fallback with new design
	?>
	<a href="<?php echo esc_url( $event_url ); ?>"
	   class="a-eve-card reveal-up delay-<?php echo absint( $delay_index ?? 100 ); ?>"
	   data-event-id="<?php echo absint( $event_id ); ?>">

		<?php
		$date_day = '';
		$date_month = '';
		if ( $event_date ) {
			$ts = is_numeric( $event_date ) ? $event_date : strtotime( $event_date );
			if ( $ts ) {
				$date_day = date_i18n( 'd', $ts );
				$date_month = date_i18n( 'M', $ts );
			}
		}
		?>

		<?php if ( $date_day ) : ?>
		<div class="a-eve-date">
			<span class="a-eve-date-day"><?php echo esc_html( $date_day ); ?></span>
			<span class="a-eve-date-month"><?php echo esc_html( $date_month ); ?></span>
		</div>
		<?php endif; ?>

		<div class="a-eve-media">
			<?php if ( $event_image ) : ?>
			<img src="<?php echo esc_url( $event_image ); ?>"
			     alt="<?php echo esc_attr( $event_title ); ?>"
			     loading="lazy">
			<?php endif; ?>

			<?php if ( ! empty( $event_sounds ) ) : ?>
			<div class="a-eve-tags">
				<?php foreach ( array_slice( (array) $event_sounds, 0, 2 ) as $sound ) : ?>
				<span class="a-eve-tag"><?php echo esc_html( $sound ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="a-eve-content">
			<h2 class="a-eve-title"><?php echo esc_html( $event_title ); ?></h2>

			<?php if ( ! empty( $event_djs ) ) : ?>
			<p class="a-eve-meta">
				<i class="ri-sound-module-fill"></i>
				<span><?php echo esc_html( implode( ', ', array_slice( (array) $event_djs, 0, 3 ) ) ); ?></span>
			</p>
			<?php endif; ?>

			<?php if ( $event_location ) : ?>
			<p class="a-eve-meta">
				<i class="ri-map-pin-2-line"></i>
				<span><?php echo esc_html( $event_location ); ?></span>
			</p>
			<?php endif; ?>
		</div>
	</a>
	<?php
}

