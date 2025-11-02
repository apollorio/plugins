<?php 
/**
 * Template for event listing content - SAME structure as event-card.php
 * Used in AJAX filtering
 */

// Get event data
$event_id = get_the_ID();
$event_title = get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$start_date = get_post_meta($event_id, '_event_start_date', true);
$event_banner = get_post_meta($event_id, '_event_banner', true);

// Get categories
$categories = wp_get_post_terms($event_id, 'event_listing_category');
$categories = is_wp_error($categories) ? array() : $categories;
$category_slug = !empty($categories) && is_array($categories) ? $categories[0]->slug : 'general';

// Get sounds/genres
$sounds = wp_get_post_terms($event_id, 'event_sounds');
$sounds = is_wp_error($sounds) ? array() : $sounds;

// ✅ CORRECT: Get local
$local_id = get_post_meta($event_id, '_event_local_ids', true);
if (empty($local_id)) {
    $local_id = get_post_meta($event_id, '_event_local', true);
}
$local_name = '';

if (!empty($local_id) && is_numeric($local_id)) {
    $local_post = get_post($local_id);
    if ($local_post && $local_post->post_status === 'publish') {
        $local_name = get_post_meta($local_id, '_local_name', true);
        if (empty($local_name)) {
            $local_name = $local_post->post_title;
        }
    }
}

if (empty($local_name)) {
    $local_name = get_post_meta($event_id, '_event_location', true);
}

// ✅ CORRECT: Get DJs
$djs_names = array();
$dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
if (!empty($dj_ids_raw)) {
    $dj_ids = maybe_unserialize($dj_ids_raw);
    if (is_array($dj_ids)) {
        foreach ($dj_ids as $dj_id) {
            $dj_id = intval($dj_id);
            $dj_post = get_post($dj_id);
            if ($dj_post && $dj_post->post_status === 'publish') {
                $dj_name = get_post_meta($dj_id, '_dj_name', true);
                if (empty($dj_name)) {
                    $dj_name = $dj_post->post_title;
                }
                if (!empty($dj_name)) {
                    $djs_names[] = $dj_name;
                }
            }
        }
    }
}

// Format date
$day = '';
$month_str = '';
if ($start_date) {
    $timestamp = strtotime($start_date);
    if ($timestamp && $timestamp > 0) {
        $day = date('j', $timestamp);
        $month = date('M', $timestamp);
        $month_lower = strtolower($month);
        $month_map = array(
            'jan' => 'jan', 'feb' => 'fev', 'mar' => 'mar',
            'apr' => 'abr', 'may' => 'mai', 'jun' => 'jun',
            'jul' => 'jul', 'aug' => 'ago', 'sep' => 'set',
            'oct' => 'out', 'nov' => 'nov', 'dec' => 'dez'
        );
        $month_str = isset($month_map[$month_lower]) ? $month_map[$month_lower] : $month_lower;
    }
}

// ✅ CORRECT: Banner is URL
$banner_url = '';
if ($event_banner && filter_var($event_banner, FILTER_VALIDATE_URL)) {
    $banner_url = $event_banner;
} elseif ($event_banner && is_numeric($event_banner)) {
    $banner_url = wp_get_attachment_url($event_banner);
}
if (!$banner_url) {
    $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
}
?>

<a href="<?php echo get_permalink(); ?>" 
   class="event_listing" 
   data-event-id="<?php echo $event_id; ?>" 
   data-category="<?php echo esc_attr($category_slug); ?>" 
   data-month-str="<?php echo esc_attr($month_str); ?>">
   
    <!-- Date Box -->
    <div class="box-date-event">
        <span class="date-day"><?php echo $day ?: '--'; ?></span>
        <span class="date-month"><?php echo $month_str ?: '---'; ?></span>
    </div>
    
    <!-- Event Image -->
    <div class="picture">
        <img src="<?php echo esc_url($banner_url); ?>" 
             alt="<?php echo esc_attr($event_title); ?>" 
             loading="lazy">
        
        <!-- Genre Tags -->
        <div class="event-card-tags">
            <?php
            if (!empty($sounds)) {
                $max_tags = 3;
                $count = 0;
                foreach ($sounds as $sound) {
                    if ($count >= $max_tags) break;
                    echo '<span>' . esc_html($sound->name) . '</span>';
                    $count++;
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Event Info -->
    <div class="event-line">
        <div class="box-info-event">
            <h2 class="event-li-title mb04rem"><?php echo esc_html($event_title); ?></h2>
            
            <?php if (!empty($djs_names)) : ?>
            <p class="event-li-detail of-dj mb04rem">
                <i class="ri-sound-module-fill"></i>
                <span><?php echo esc_html(implode(', ', $djs_names)); ?></span>
            </p>
            <?php endif; ?>
            
            <?php if ($local_name) : ?>
            <p class="event-li-detail of-location mb04rem">
                <i class="ri-map-pin-2-line"></i>
                <span><?php echo esc_html($local_name); ?></span>
            </p>
            <?php endif; ?>
        </div>
    </div>
</a>
