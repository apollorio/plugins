<?php
// phpcs:ignoreFile

/**
 * Event Card Template
 * DESIGN LIBRARY: Matches approved HTML from 'events discover event-card.html'
 * Uses uni.css classes for consistent styling
 * UPDATED: Now uses Apollo_Event_Data_Helper for correct data retrieval with tooltips
 *
 * @package Apollo_Events_Manager
 * @version 3.1.0 - Helper Integration + Tooltips
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

// Load helper class
require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

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

// Use helper for correct data retrieval
$dj_names = Apollo_Event_Data_Helper::get_dj_lineup($event_id);
$local_data = Apollo_Event_Data_Helper::get_local_data($event_id);
$banner_url = Apollo_Event_Data_Helper::get_banner_url($event_id);

// Get event date
$event_date = get_post_meta($event_id, '_event_start_date', true);
$parsed_date = Apollo_Event_Data_Helper::parse_event_date($event_date);

// Format date components (matches design: day + lowercase month abbrev)
$day       = $parsed_date['day'] ?: '--';
$month     = $parsed_date['month_pt'] ?: '---';
$month_str = strtolower($month);

// Get genres/sounds from taxonomy
$genres_array = [];
$terms_sounds = wp_get_post_terms($event_id, 'event_sounds', [ 'fields' => 'names' ]);
if (! is_wp_error($terms_sounds) && ! empty($terms_sounds)) {
    $genres_array = $terms_sounds;
}

// Fallback to meta if no taxonomy terms
if (empty($genres_array)) {
    $event_genres = get_post_meta($event_id, '_event_genres', true);
    if ($event_genres) {
        if (is_array($event_genres)) {
            $genres_array = $event_genres;
        } else {
            $genres_array = array_map('trim', explode(',', $event_genres));
        }
    }
}

// Format DJ display using helper
$dj_display = '';
$dj_tooltip = '';
if (! empty($dj_names)) {
    $dj_display = implode(', ', array_slice($dj_names, 0, 2));
    if (count($dj_names) > 2) {
        $dj_display .= ' +' . (count($dj_names) - 2);
        $dj_tooltip = esc_attr(implode(', ', $dj_names)); // Full list for tooltip
    } else {
        $dj_tooltip = esc_attr(implode(', ', $dj_names));
    }
} else {
    $dj_display = __('Line-up em breve', 'apollo-events-manager');
}

// Location display with tooltip
$location_display = '';
$location_tooltip = '';
if ($local_data) {
    $location_display = $local_data['name'];
    // Build comprehensive tooltip with address
    $tooltip_parts = [ $local_data['name'] ];
    if (! empty($local_data['address'])) {
        $tooltip_parts[] = $local_data['address'];
    }
    if (! empty($local_data['region'])) {
        $tooltip_parts[] = $local_data['region'];
    }
    $location_tooltip = esc_attr(implode(' - ', $tooltip_parts));
} else {
    // Fallback to meta
    $event_venue = get_post_meta($event_id, '_event_venue', true);
    $event_location = get_post_meta($event_id, '_event_location', true);
    $location_display = $event_venue ?: $event_location;
    if (! $location_display) {
        $location_display = __('Local a confirmar', 'apollo-events-manager');
    }
    $location_tooltip = esc_attr($location_display);
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
        data-month-str="<?php echo esc_attr($month_str); ?>"
        title="<?php echo esc_attr($event_title); ?>">

        <!-- [ELEMENT::DATE_BOX] Event Date Badge -->
        <div class="box-date-event" title="<?php echo esc_attr($parsed_date['iso_date'] ?: $event_date); ?>">
                <span class="date-day"><?php echo esc_html($day); ?></span>
                <span class="date-month"><?php echo esc_html($month); ?></span>
        </div>

        <!-- [ELEMENT::EVENT_IMAGE] Event Thumbnail -->
        <div class="picture">
                <img src="<?php echo esc_url($banner_url); ?>"
                        alt="<?php echo esc_attr($event_title); ?>"
                        loading="lazy"
                        title="<?php echo esc_attr($event_title); ?>">

                <!-- [ELEMENT::EVENT_TAGS] Category Tags -->
                <?php if (! empty($genres_array)) : ?>
                        <div class="event-card-tags">
                                <?php foreach (array_slice($genres_array, 0, 3) as $genre) : ?>
                                        <span title="<?php echo esc_attr($genre); ?>"><?php echo esc_html($genre); ?></span>
                                <?php endforeach; ?>
                        </div>
                <?php endif; ?>
        </div>

        <!-- [ELEMENT::EVENT_INFO] Event Details -->
        <div class="event-line">
                <div class="box-info-event">
                        <!-- [ELEMENT::EVENT_TITLE] -->
                        <h2 class="event-li-title afasta-bmin" title="<?php echo esc_attr($event_title); ?>"><?php echo esc_html($event_title); ?></h2>

                        <!-- [ELEMENT::DJ_NAME] with tooltip -->
                        <?php if ($dj_display) : ?>
                                <p class="event-li-detail of-dj afasta-bmin"
                                   title="<?php echo esc_attr($dj_tooltip); ?>"
                                   data-tooltip="<?php echo esc_attr($dj_tooltip); ?>">
                                        <i class="ri-sound-module-fill" aria-hidden="true"></i>
                                        <span><?php echo esc_html($dj_display); ?></span>
                                </p>
                        <?php endif; ?>

                        <!-- [ELEMENT::VENUE_NAME] with tooltip -->
                        <p class="event-li-detail of-location afasta-bmin"
                           title="<?php echo esc_attr($location_tooltip); ?>"
                           data-tooltip="<?php echo esc_attr($location_tooltip); ?>">
                                <i class="ri-map-pin-2-line" aria-hidden="true"></i>
                                <span id="local_nome_<?php echo esc_attr($event_id); ?>"><?php echo esc_html($location_display); ?></span>
                        </p>
                </div>
        </div>
</a>

