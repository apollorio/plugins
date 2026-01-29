<?php
/**
 * Template Part: Event Card - Official Apollo Design
 * ===================================================
 * Path: apollo-events-manager/templates/parts/event/card.php
 *
 * Berlin Brutalism event card with corner cutout design.
 * Mobile-first, responsive, supports Apollo partner badges and coupons.
 *
 * CONTEXT CONTRACT (Required Variables):
 * --------------------------------------
 * @var int    $event_id       Event post ID
 * @var string $event_title    Event display title
 * @var string $event_url      Event permalink or external URL
 * @var string $event_image    Event banner/image URL
 * @var string $event_date     Event start date (Y-m-d or timestamp)
 * @var string $event_location Venue/location name
 * @var array  $event_djs      Array of DJ names
 * @var array  $event_sounds   Array of sound/genre terms
 * @var string $event_coupon   Coupon code (optional)
 * @var bool   $is_apollo      Is Apollo partner event
 * @var bool   $is_external    External link (opens in new tab)
 * @var int    $delay_index    Animation delay index (100, 200, 300)
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// =============================================================================
// DEFAULT VALUES & SANITIZATION
// =============================================================================

$event_id       = $event_id ?? 0;
$event_title    = $event_title ?? __( 'Evento', 'apollo-events-manager' );
$event_url      = $event_url ?? get_permalink( $event_id );
$event_image    = $event_image ?? '';
$event_date     = $event_date ?? '';
$event_location = $event_location ?? '';
$event_djs      = $event_djs ?? array();
$event_sounds   = $event_sounds ?? array();
$event_coupon   = $event_coupon ?? '';
$is_apollo      = $is_apollo ?? false;
$is_external    = $is_external ?? false;
$delay_index    = $delay_index ?? 100;

// =============================================================================
// DATE PARSING
// =============================================================================

$date_day   = '';
$date_month = '';

if ( ! empty( $event_date ) ) {
	// Handle different date formats
	if ( is_numeric( $event_date ) ) {
		$timestamp = $event_date;
	} else {
		$timestamp = strtotime( $event_date );
	}

	if ( $timestamp ) {
		$date_day   = date_i18n( 'd', $timestamp );
		$date_month = date_i18n( 'M', $timestamp );
	}
}

// =============================================================================
// BUILD CSS CLASSES
// =============================================================================

$card_classes = array( 'a-eve-card', 'reveal-up' );

// Animation delay
if ( $delay_index ) {
	$card_classes[] = 'delay-' . absint( $delay_index );
}

// Apollo partner badge
if ( $is_apollo ) {
	$card_classes[] = 'apollo';
}

// Coupon indicator
if ( ! empty( $event_coupon ) ) {
	$card_classes[] = 'coup-apollo';
}

$card_class_string = implode( ' ', $card_classes );

// =============================================================================
// BUILD DATA ATTRIBUTES
// =============================================================================

$data_attrs = array();

if ( ! empty( $event_coupon ) ) {
	$data_attrs['data-coupon'] = esc_attr( $event_coupon );
}

if ( $event_id ) {
	$data_attrs['data-event-id'] = absint( $event_id );
}

$data_string = '';
foreach ( $data_attrs as $key => $value ) {
	$data_string .= sprintf( ' %s="%s"', $key, $value );
}

// =============================================================================
// LINK ATTRIBUTES
// =============================================================================

$link_target = $is_external ? ' target="_blank" rel="noopener noreferrer"' : '';

// =============================================================================
// FALLBACK IMAGE
// =============================================================================

if ( empty( $event_image ) ) {
	$event_image = defined( 'APOLLO_APRIO_URL' )
		? APOLLO_APRIO_URL . 'assets/img/placeholder-event.webp'
		: plugins_url( 'assets/img/placeholder-event.webp', dirname( __DIR__, 2 ) );
}

// =============================================================================
// FORMAT DJs LIST
// =============================================================================

$djs_display = '';
if ( ! empty( $event_djs ) ) {
	if ( is_array( $event_djs ) ) {
		$djs_display = implode( ', ', array_slice( $event_djs, 0, 3 ) );
		if ( count( $event_djs ) > 3 ) {
			$djs_display .= ' +';
		}
	} else {
		$djs_display = $event_djs;
	}
}

// =============================================================================
// FORMAT SOUNDS/GENRES
// =============================================================================

$sounds_display = array();
if ( ! empty( $event_sounds ) ) {
	if ( is_array( $event_sounds ) ) {
		$sounds_display = array_slice( $event_sounds, 0, 3 );
	} else {
		$sounds_display = array( $event_sounds );
	}
}

?>
<a href="<?php echo esc_url( $event_url ); ?>"
   class="<?php echo esc_attr( $card_class_string ); ?>"
   <?php echo $data_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
   <?php echo $link_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $date_day && $date_month ) : ?>
	<div class="a-eve-date">
		<span class="a-eve-date-day"><?php echo esc_html( $date_day ); ?></span>
		<span class="a-eve-date-month"><?php echo esc_html( $date_month ); ?></span>
	</div>
	<?php endif; ?>

	<div class="a-eve-media">
		<img src="<?php echo esc_url( $event_image ); ?>"
		     alt="<?php echo esc_attr( $event_title ); ?>"
		     loading="lazy"
		     decoding="async">

		<?php if ( ! empty( $sounds_display ) ) : ?>
		<div class="a-eve-tags">
			<?php foreach ( $sounds_display as $sound ) : ?>
			<span class="a-eve-tag"><?php echo esc_html( $sound ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="a-eve-content">
		<h2 class="a-eve-title"><?php echo esc_html( $event_title ); ?></h2>

		<?php if ( ! empty( $djs_display ) ) : ?>
		<p class="a-eve-meta">
			<i class="ri-sound-module-fill"></i>
			<span><?php echo esc_html( $djs_display ); ?></span>
		</p>
		<?php endif; ?>

		<?php if ( ! empty( $event_location ) ) : ?>
		<p class="a-eve-meta">
			<i class="ri-map-pin-2-line"></i>
			<span><?php echo esc_html( $event_location ); ?></span>
		</p>
		<?php endif; ?>

		<?php if ( ! empty( $sounds_display ) ) : ?>
		<p class="a-eve-meta">
			<i class="ri-music-2-line"></i>
			<span><?php echo esc_html( implode( ', ', $sounds_display ) ); ?></span>
		</p>
		<?php endif; ?>
	</div>
</a>
