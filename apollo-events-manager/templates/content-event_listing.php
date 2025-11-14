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

// ✅ CORRECT: Get local with comprehensive validation (same as event-card.php)
$local_id = apollo_get_primary_local_id($event_id);
if (!$local_id) {
    $legacy = get_post_meta($event_id, '_event_local', true); // Fallback
    $local_id = $legacy ? (int) $legacy : 0;
}

// DEBUG: Show what we're getting (admin only)
if (current_user_can('administrator')) {
    $local_ids_raw = get_post_meta($event_id, '_event_local_ids', true);
    $local_legacy = get_post_meta($event_id, '_event_local', true);
    echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Local IDs Raw: ' . esc_html(print_r($local_ids_raw, true)) . ' -->';
    echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Local Legacy: ' . esc_html($local_legacy) . ' -->';
    echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Local ID Final: ' . esc_html($local_id) . ' -->';
}

$local_name = '';
$local_region = '';

if ($local_id) {
    $local_post = get_post($local_id);

    if ($local_post && $local_post->post_status === 'publish') {
        $local_name = get_post_meta($local_id, '_local_name', true);
        if (empty($local_name)) {
            $local_name = $local_post->post_title;
        }

        $local_city = get_post_meta($local_id, '_local_city', true);
        $local_state = get_post_meta($local_id, '_local_state', true);
        $local_region = $local_city && $local_state ? "({$local_city}, {$local_state})" :
                       ($local_city ? "({$local_city})" : ($local_state ? "({$local_state})" : ''));
        
        // DEBUG
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Local Post Title: ' . esc_html($local_post->post_title) . ' -->';
            echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Local Name Meta: ' . esc_html($local_name) . ' -->';
        }
    } else {
        // DEBUG
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Local Post NOT FOUND or NOT PUBLISHED (ID: ' . $local_id . ') -->';
        }
    }
}

// Fallback to direct location meta
if (empty($local_name)) {
    $local_name = get_post_meta($event_id, '_event_location', true);
    // DEBUG
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' Using Fallback Location: ' . esc_html($local_name) . ' -->';
    }
}

// ✅ CORRECT: Get DJs from _event_dj_ids (primary) or _timetable (fallback)
$djs_names = array();

// Try _event_dj_ids first (serialized array)
$dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
$dj_ids = apollo_aem_parse_ids($dj_ids_raw);

// DEBUG: Show what we're getting (admin only)
if (current_user_can('administrator')) {
    echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' DJ IDs Raw: ' . esc_html(print_r($dj_ids_raw, true)) . ' -->';
    echo '<!-- DEBUG [content-event_listing] Event ' . $event_id . ' DJ IDs Parsed: ' . esc_html(implode(', ', $dj_ids)) . ' -->';
}

if (!empty($dj_ids)) {
    foreach ($dj_ids as $dj_id) {
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

// Fallback: Try _timetable (may be buggy numeric or array)
if (empty($djs_names)) {
    $event_timetable = get_post_meta($event_id, '_event_timetable', true);
    $timetable = apollo_sanitize_timetable($event_timetable);
    if (empty($timetable)) {
        $legacy_timetable = get_post_meta($event_id, '_timetable', true);
        $timetable = apollo_sanitize_timetable($legacy_timetable);
    }

    if (!empty($timetable)) {
        foreach ($timetable as $slot) {
            $dj_id = isset($slot['dj']) ? (int) $slot['dj'] : 0;
            if (!$dj_id) {
                continue;
            }

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
            <h2 class="event-li-title afasta-bmin"><?php echo esc_html($event_title); ?></h2>
            
            <?php if (!empty($djs_names)) : ?>
            <p class="event-li-detail of-dj afasta-bmin">
                <i class="ri-sound-module-fill"></i>
                <span><?php echo esc_html(implode(', ', $djs_names)); ?></span>
            </p>
            <?php endif; ?>
            
            <?php if ($local_name) : ?>
            <p class="event-li-detail of-location afasta-bmin">
                <i class="ri-map-pin-2-line"></i>
                <span><?php echo esc_html($local_name); ?></span>
            </p>
            <?php endif; ?>
        </div>
    </div>
</a>
