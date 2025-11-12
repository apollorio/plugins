<?php 
/**
 * Template Apollo: Event Card
 *
 * Used in the events listing loop
 */

// DEBUG: Show all event meta for admins (enabled via APOLLO_EVENTS_DEBUG)
// To enable, add to wp-config.php (or another bootstrap file):
//   define('APOLLO_EVENTS_DEBUG', true);
// Default: disabled. Output is still restricted to administrators.
if ( defined('APOLLO_EVENTS_DEBUG') && constant('APOLLO_EVENTS_DEBUG') && current_user_can('administrator') ) {
    echo '<pre style="background:#000;color:#0f0;padding:20px;overflow:auto;max-height:300px;">';
    echo "Event ID: " . get_the_ID() . "\n\n";
    // Use print_r for readable array output; admins only.
    print_r( get_post_meta( get_the_ID() ) );
    echo '</pre>';
}

// Get event data
$event_id = get_the_ID();
$event_title = get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$start_date = get_post_meta($event_id, '_event_start_date', true);
$event_banner = get_post_meta($event_id, '_event_banner', true);

// ✅ PRODUCTION: Debug comments removed

// Get categories with proper error handling
$categories = wp_get_post_terms($event_id, 'event_listing_category');
$categories = is_wp_error($categories) ? array() : $categories;
$category_slug = !empty($categories) && is_array($categories) ? $categories[0]->slug : 'general';

// Get sounds/genres with proper error handling
$sounds = wp_get_post_terms($event_id, 'event_sounds');
$sounds = is_wp_error($sounds) ? array() : $sounds;

// ✅ CORRECT: Get local with comprehensive validation
$local_id = apollo_get_primary_local_id($event_id);
if (!$local_id) {
    $legacy = get_post_meta($event_id, '_event_local', true); // Fallback
    $local_id = $legacy ? (int) $legacy : 0;
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
    }
}

// Fallback to direct location meta
if (empty($local_name)) {
    $local_name = get_post_meta($event_id, '_event_location', true);
}

// ✅ CORRECT: Get DJs from _event_dj_ids (primary) or _timetable (fallback)
$djs_names = array();

// Try _event_dj_ids first (serialized array)
$dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
if (!empty($dj_ids_raw)) {
    $dj_ids = maybe_unserialize($dj_ids_raw);
    if (is_array($dj_ids)) {
        foreach ($dj_ids as $dj_id) {
            $dj_id = intval($dj_id); // Convert string to int
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
$month = '';
$month_str = '';
if ($start_date) {
    $timestamp = strtotime($start_date);
    if ($timestamp && $timestamp > 0) {
        $day = date('j', $timestamp); // Use 'j' for day without leading zero
        $month = date('M', $timestamp);

        // Convert to Portuguese abbreviations
        $month_lower = strtolower($month);
        $month_map = array(
            'jan' => 'jan', 'feb' => 'fev', 'mar' => 'mar',
            'apr' => 'abr', 'may' => 'mai', 'jun' => 'jun',
            'jul' => 'jul', 'aug' => 'ago', 'sep' => 'set',
            'oct' => 'out', 'nov' => 'nov', 'dec' => 'dez'
        );
        $month_str = isset($month_map[$month_lower]) ? $month_map[$month_lower] : $month_lower;
    } else {
        // Debug: Invalid date format
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG: Invalid date format: ' . esc_html($start_date) . ' -->';
        }
    }
} else {
    // Debug: No start date
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG: No _event_start_date meta found -->';
    }
}

// ✅ CORRECT: Banner is URL, not attachment ID
$banner_url = '';
if ($event_banner) {
    // Try as URL first
    if (filter_var($event_banner, FILTER_VALIDATE_URL)) {
        $banner_url = $event_banner;
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG: Banner is valid URL: ' . esc_html($event_banner) . ' -->';
        }
    } elseif (is_numeric($event_banner)) {
        $banner_url = wp_get_attachment_url($event_banner);
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG: Banner is attachment ID: ' . esc_html($event_banner) . ' -> ' . esc_html($banner_url) . ' -->';
        }
    } else {
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG: Banner value is neither URL nor numeric: ' . esc_html($event_banner) . ' (type: ' . gettype($event_banner) . ') -->';
        }
    }
} else {
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG: No _event_banner meta found -->';
    }
}

if (!$banner_url) {
    $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG: Using fallback banner -->';
    }
}

// Debug: Show content analysis for card height
if (current_user_can('administrator')) {
    $content_height_factors = array(
        'djs_count' => count($djs_names),
        'local_length' => strlen($local_name . $local_region),
        'tags_count' => count($sounds),
        'title_length' => strlen($event_title)
    );
    
    echo '<!-- DEBUG: Height factors - DJs: ' . $content_height_factors['djs_count'] . 
         ', Local length: ' . $content_height_factors['local_length'] . 
         ', Tags: ' . $content_height_factors['tags_count'] . 
         ', Title length: ' . $content_height_factors['title_length'] . ' -->';
}

// Calculate content density for visual indicator
$content_density = count($djs_names) + (strlen($local_name . $local_region) > 50 ? 2 : 1) + count($sounds);
$density_class = $content_density > 5 ? 'high-density' : ($content_density > 3 ? 'medium-density' : 'low-density');
?>

<a href="<?php echo get_permalink(); ?>" 
   class="event_listing <?php echo $density_class; ?>" 
   data-event-id="<?php echo $event_id; ?>" 
   data-category="<?php echo esc_attr($category_slug); ?>" 
   data-month-str="<?php echo esc_attr($month_str); ?>"
   data-density="<?php echo $content_density; ?>">
   
    <?php if (current_user_can('administrator')): ?>
    <div class="admin-debug-indicator" style="position:absolute;top:5px;right:5px;background:<?php echo esc_attr($content_density > 5 ? 'red' : ($content_density > 3 ? 'orange' : 'green')); ?>;color:white;padding:2px 5px;border-radius:3px;font-size:10px;z-index:1000;">
        D:<?php echo absint($content_density); ?>
    </div>
    <?php endif; ?>
   
    <!-- Date Box -->
    <div class="box-date-event">
        <span class="date-day"><?php echo esc_html($day ?: '--'); ?></span>
        <span class="date-month"><?php echo esc_html($month_str ?: '---'); ?></span>
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
                <span id="Local_nome"><?php echo esc_html($local_name); ?></span> 
                <?php if ($local_region): ?>
                <span style="opacity:.5" id="local_regiao"><?php echo esc_html($local_region); ?></span>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</a>

