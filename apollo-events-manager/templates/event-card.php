<?php
/**
 * Event Card Template
 * DESIGN LIBRARY: Matches approved HTML from 'events discover event-card.md'
 * Uses uni.css classes for consistent styling
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 * 
 * Expected variables:
 * @var WP_Post $event - The event post object
 * @var array $args - Optional arguments passed to template
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Get event data with fallbacks
$event_id = $event->ID ?? get_the_ID();
$event_title = get_the_title($event_id);
$event_permalink = get_permalink($event_id);

// Meta keys - Apollo Events Manager standard
$event_date = get_post_meta($event_id, '_event_start_date', true);
$event_time = get_post_meta($event_id, '_event_start_time', true);
$event_venue = get_post_meta($event_id, '_event_venue', true);
$event_location = get_post_meta($event_id, '_event_location', true);
$event_image = get_post_meta($event_id, '_event_banner', true);
$event_genres = get_post_meta($event_id, '_event_genres', true);
$event_dj_names = get_post_meta($event_id, '_event_dj_names', true);
$favorites_count = (int) get_post_meta($event_id, '_favorites_count', true);

// Fallback to featured image if no banner
if (!$event_image) {
    $event_image = get_the_post_thumbnail_url($event_id, 'medium_large');
}
if (!$event_image) {
    $event_image = 'https://assets.apollo.rio.br/img/placeholder-event.jpg';
}

// Format date components
$date_obj = null;
$day = '--';
$month = '---';
$day_name = '';

if ($event_date) {
    $date_obj = DateTime::createFromFormat('Y-m-d', $event_date);
    if ($date_obj) {
        $day = $date_obj->format('d');
        $month_names = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
        $month = $month_names[(int)$date_obj->format('n') - 1];
        $day_names = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];
        $day_name = $day_names[(int)$date_obj->format('w')];
    }
}

// Format time
$formatted_time = '';
if ($event_time) {
    $time_obj = DateTime::createFromFormat('H:i', $event_time);
    if (!$time_obj) {
        $time_obj = DateTime::createFromFormat('H:i:s', $event_time);
    }
    if ($time_obj) {
        $formatted_time = $time_obj->format('H\hi');
    } else {
        $formatted_time = esc_html($event_time);
    }
}

// Parse genres into array
$genres_array = [];
if ($event_genres) {
    if (is_array($event_genres)) {
        $genres_array = $event_genres;
    } else {
        $genres_array = array_map('trim', explode(',', $event_genres));
    }
}

// Parse DJ names
$dj_display = '';
if ($event_dj_names) {
    if (is_array($event_dj_names)) {
        $dj_display = implode(', ', array_slice($event_dj_names, 0, 3));
        if (count($event_dj_names) > 3) {
            $dj_display .= ' +' . (count($event_dj_names) - 3);
        }
    } else {
        $dj_display = $event_dj_names;
    }
}

// Location display
$location_display = $event_venue ?: $event_location;
if (!$location_display) {
    $location_display = 'Local a confirmar';
}
?>
<article 
    class="event_listing" 
    data-event-id="<?php echo esc_attr($event_id); ?>"
    data-tooltip="<?php echo esc_attr(sprintf(__('Evento: %s', 'apollo-events-manager'), $event_title)); ?>"
>
    <!-- Event Image -->
    <div class="picture" data-tooltip="<?php esc_attr_e('Imagem do evento', 'apollo-events-manager'); ?>">
        <a href="<?php echo esc_url($event_permalink); ?>" class="event-card-link">
            <img 
                src="<?php echo esc_url($event_image); ?>" 
                alt="<?php echo esc_attr($event_title); ?>"
                loading="lazy"
                data-tooltip="<?php echo esc_attr($event_title); ?>"
            />
        </a>
        
        <!-- Date Badge Overlay -->
        <div class="box-date-event" data-tooltip="<?php echo esc_attr(sprintf(__('Data: %s de %s', 'apollo-events-manager'), $day, $month)); ?>">
            <span class="date-day" data-tooltip="<?php esc_attr_e('Dia do evento', 'apollo-events-manager'); ?>"><?php echo esc_html($day); ?></span>
            <span class="date-month" data-tooltip="<?php esc_attr_e('Mês do evento', 'apollo-events-manager'); ?>"><?php echo esc_html($month); ?></span>
        </div>
        
        <!-- Genre Tags -->
        <?php if (!empty($genres_array)): ?>
        <div class="event-card-tags" data-tooltip="<?php esc_attr_e('Gêneros musicais', 'apollo-events-manager'); ?>">
            <?php foreach (array_slice($genres_array, 0, 2) as $genre): ?>
            <span class="event-tag" data-tooltip="<?php echo esc_attr(sprintf(__('Filtrar por %s', 'apollo-events-manager'), $genre)); ?>">
                <?php echo esc_html($genre); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Favorites Count Badge -->
        <?php if ($favorites_count > 0): ?>
        <div class="event-favorites-badge" data-tooltip="<?php echo esc_attr(sprintf(__('%d pessoas interessadas', 'apollo-events-manager'), $favorites_count)); ?>">
            <i class="ri-heart-3-fill"></i>
            <span><?php echo esc_html($favorites_count); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Event Info Lines -->
    <div class="event-line event-line-title" data-tooltip="<?php esc_attr_e('Título do evento', 'apollo-events-manager'); ?>">
        <a href="<?php echo esc_url($event_permalink); ?>" class="event-li-title">
            <?php echo esc_html($event_title); ?>
        </a>
    </div>
    
    <!-- DJs Line -->
    <?php if ($dj_display): ?>
    <div class="event-line" data-tooltip="<?php esc_attr_e('DJs confirmados', 'apollo-events-manager'); ?>">
        <span class="event-li-detail of-dj">
            <i class="ri-disc-line"></i>
            <span data-tooltip="<?php echo esc_attr(sprintf(__('Line-up: %s', 'apollo-events-manager'), $dj_display)); ?>">
                <?php echo esc_html($dj_display); ?>
            </span>
        </span>
    </div>
    <?php endif; ?>
    
    <!-- Location Line -->
    <div class="event-line" data-tooltip="<?php esc_attr_e('Local do evento', 'apollo-events-manager'); ?>">
        <span class="event-li-detail of-location">
            <i class="ri-map-pin-line"></i>
            <span data-tooltip="<?php echo esc_attr(sprintf(__('Local: %s', 'apollo-events-manager'), $location_display)); ?>">
                <?php echo esc_html($location_display); ?>
            </span>
        </span>
    </div>
    
    <!-- Time Line -->
    <?php if ($formatted_time): ?>
    <div class="event-line" data-tooltip="<?php esc_attr_e('Horário do evento', 'apollo-events-manager'); ?>">
        <span class="event-li-detail of-time">
            <i class="ri-time-line"></i>
            <span data-tooltip="<?php echo esc_attr(sprintf(__('Início: %s', 'apollo-events-manager'), $formatted_time)); ?>">
                <?php echo esc_html($day_name ? $day_name . ' · ' . $formatted_time : $formatted_time); ?>
            </span>
        </span>
    </div>
    <?php endif; ?>
</article>
