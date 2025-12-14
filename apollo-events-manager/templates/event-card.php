<?php
// phpcs:ignoreFile

/**
 * Event Card Template
 * DESIGN LIBRARY: Matches approved HTML from 'events discover event-card.html'
 * Uses uni.css classes for consistent styling
 *
 * @package Apollo_Events_Manager
 * @version 3.0.0 - Design Library Conformance
 *
 * STRUCTURE (matches design-library/original/events discover event-card.html):
 * <a class="event_listing"> (wrapper is anchor)
 *   <div class="box-date-event"> (date badge)
 *   <div class="picture"> (image container)
 *     <img>
 *     <div class="event-card-tags"> (genre tags)
 *   </div>
 *   <div class="event-line">
 *     <div class="box-info-event">
 *       <h2 class="event-li-title">
 *       <p class="event-li-detail of-dj">
 *       <p class="event-li-detail of-location">
 *
 * Expected variables:
 * @var WP_Post $event - The event post object (optional)
 * @var WP_Post $post  - The post object from loop context
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// Get event data with fallbacks - support both $event and $post context
$event_id = 0;
if (isset($event) && is_object($event) && isset($event->ID)) {
    $event_id = (int) $event->ID;
} elseif (isset($post) && is_object($post) && isset($post->ID)) {
    $event_id = (int) $post->ID;
} else {
    $event_id = (int) get_the_ID();
}

if (! $event_id) {
    return;
}

$event_title     = get_the_title($event_id);
$event_permalink = get_permalink($event_id);

// Meta keys - Apollo Events Manager standard
$event_date     = get_post_meta($event_id, '_event_start_date', true);
$event_venue    = get_post_meta($event_id, '_event_venue', true);
$event_location = get_post_meta($event_id, '_event_location', true);
$event_image    = get_post_meta($event_id, '_event_banner', true);
$event_genres   = get_post_meta($event_id, '_event_genres', true);
$event_dj_names = get_post_meta($event_id, '_event_dj_names', true);

// Fallback to featured image if no banner
if (! $event_image) {
    $event_image = get_the_post_thumbnail_url($event_id, 'medium_large');
}
if (! $event_image) {
    $event_image = 'https://assets.apollo.rio.br/img/placeholder-event.jpg';
}

// Format date components (matches design: day + lowercase month abbrev)
$day       = '--';
$month     = '---';
$month_str = '';

if ($event_date) {
    $date_obj = DateTime::createFromFormat('Y-m-d', $event_date);
    if ($date_obj) {
        $day = $date_obj->format('j');
        // Day without leading zero (design uses 25, not 025)
        $month_names = [ 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez' ];
        $month       = $month_names[ (int) $date_obj->format('n') - 1 ];
        $month_str   = strtolower($month);
    }
}

// Parse genres into array for tags display
$genres_array = [];
if ($event_genres) {
    if (is_array($event_genres)) {
        $genres_array = $event_genres;
    } else {
        $genres_array = array_map('trim', explode(',', $event_genres));
    }
}

// Parse DJ names for display
$dj_display = '';
if ($event_dj_names) {
    if (is_array($event_dj_names)) {
        $dj_display = implode(', ', array_slice($event_dj_names, 0, 2));
        if (count($event_dj_names) > 2) {
            $dj_display .= ' +' . (count($event_dj_names) - 2);
        }
    } else {
        $dj_display = esc_html($event_dj_names);
    }
}

// Location display
$location_display = $event_venue ?: $event_location;
if (! $location_display) {
    $location_display = __('Local a confirmar', 'apollo-events-manager');
}

// Get category slug for filtering (data-category attribute)
$category_slug = 'uncategorized';
$terms         = wp_get_post_terms($event_id, 'event_listing_category', [ 'fields' => 'slugs' ]);
if (! is_wp_error($terms) && ! empty($terms)) {
    $category_slug = $terms[0];
}
?>
<a href="<?php echo esc_url($event_permalink); ?>"
	class="event_listing"
	data-event-id="<?php echo esc_attr($event_id); ?>"
	data-category="<?php echo esc_attr($category_slug); ?>"
	data-month-str="<?php echo esc_attr($month_str); ?>">

	<!-- [ELEMENT::DATE_BOX] Event Date Badge -->
	<div class="box-date-event">
		<span class="date-day"><?php echo esc_html($day); ?></span>
		<span class="date-month"><?php echo esc_html($month); ?></span>
	</div>

	<!-- [ELEMENT::EVENT_IMAGE] Event Thumbnail -->
	<div class="picture">
		<img src="<?php echo esc_url($event_image); ?>"
			alt="<?php echo esc_attr($event_title); ?>"
			loading="lazy">

		<!-- [ELEMENT::EVENT_TAGS] Category Tags -->
		<?php if (! empty($genres_array)) : ?>
			<div class="event-card-tags">
				<?php foreach (array_slice($genres_array, 0, 3) as $genre) : ?>
					<span><?php echo esc_html($genre); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- [ELEMENT::EVENT_INFO] Event Details -->
	<div class="event-line">
		<div class="box-info-event">
			<!-- [ELEMENT::EVENT_TITLE] -->
			<h2 class="event-li-title afasta-bmin"><?php echo esc_html($event_title); ?></h2>

			<!-- [ELEMENT::DJ_NAME] -->
			<?php if ($dj_display) : ?>
				<p class="event-li-detail of-dj afasta-bmin">
					<i class="ri-sound-module-fill"></i>
					<span><?php echo esc_html($dj_display); ?></span>
				</p>
			<?php endif; ?>

			<!-- [ELEMENT::VENUE_NAME] -->
			<p class="event-li-detail of-location afasta-bmin">
				<i class="ri-map-pin-2-line"></i>
				<span id="local_nome"><?php echo esc_html($location_display); ?></span>
			</p>
		</div>
	</div>
</a>