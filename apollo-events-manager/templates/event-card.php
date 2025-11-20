<?php 
// phpcs:ignoreFile
/**
 * Template Apollo: Event Card
 *
 * Used in the events listing loop
 */

/* phpcs:disable Generic.Files.LineLength, WordPress.Files.FileHeader, Squiz.Commenting.FileComment */

// Resolve the current event without relying exclusively on the global $post
$event_object = isset($event) && $event instanceof WP_Post ? $event : get_post();
$event_id = $event_object instanceof WP_Post ? $event_object->ID : get_the_ID();

if (!$event_id) {
    return;
}

// DEBUG: Show all event meta for admins (enabled via APOLLO_EVENTS_DEBUG)
// To enable, add to wp-config.php (or another bootstrap file):
//   define('APOLLO_EVENTS_DEBUG', true);
// Default: disabled. Output is still restricted to administrators.
if (defined('APOLLO_EVENTS_DEBUG') && constant('APOLLO_EVENTS_DEBUG') && current_user_can('administrator')) {
    echo '<pre style="background:#000;color:#0f0;padding:20px;overflow:auto;max-height:300px;">';
    echo "Event ID: " . $event_id . "\n\n";
    // Use print_r for readable array output; admins only.
    $all_meta = apollo_get_post_meta($event_id, '', false); // Get all meta keys
    print_r($all_meta);
    echo '</pre>';
}

// Get event data
$event_title = apollo_get_post_meta($event_id, '_event_title', true)
    ?: get_the_title($event_id);
$start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
$event_banner = apollo_get_post_meta($event_id, '_event_banner', true);

// ✅ PRODUCTION: Debug comments removed

// Get categories with proper error handling
$categories = wp_get_post_terms($event_id, 'event_listing_category');
$categories = is_wp_error($categories) ? array() : $categories;
$category_slug = !empty($categories) && is_array($categories) ? $categories[0]->slug : 'general';

// Get sounds/genres with proper error handling
$sounds = wp_get_post_terms($event_id, 'event_sounds');
$sounds = is_wp_error($sounds) ? array() : $sounds;

// ✅ CORRECT: Get local with comprehensive validation
// Verificar se função existe antes de usar
$local_id = function_exists('apollo_get_primary_local_id') 
    ? apollo_get_primary_local_id($event_id) 
    : 0;

if (!$local_id) {
    // Fallback: usar meta key direto
    $local_ids_meta = apollo_get_post_meta($event_id, '_event_local_ids', true);
    if (!empty($local_ids_meta)) {
        $local_id = is_array($local_ids_meta) ? (int) reset($local_ids_meta) : (int) $local_ids_meta;
    }
    
    // Fallback legacy
    if (!$local_id) {
        $legacy = apollo_get_post_meta($event_id, '_event_local', true);
        $local_id = $legacy ? (int) $legacy : 0;
    }
}

// DEBUG: Show what we're getting (admin only)
if (current_user_can('administrator')) {
    $local_ids_raw = apollo_get_post_meta($event_id, '_event_local_ids', true);
    $local_legacy = apollo_get_post_meta($event_id, '_event_local', true);
    echo '<!-- DEBUG Event ' . $event_id . ' Local IDs Raw: ' . esc_html(print_r($local_ids_raw, true)) . ' -->';
    echo '<!-- DEBUG Event ' . $event_id . ' Local Legacy: ' . esc_html($local_legacy) . ' -->';
    echo '<!-- DEBUG Event ' . $event_id . ' Local ID Final: ' . esc_html($local_id) . ' -->';
}

$local_name = '';
$local_region = '';

if ($local_id) {
    $local_post = get_post($local_id);

    if ($local_post && $local_post->post_status === 'publish') {
        $local_name = apollo_get_post_meta($local_id, '_local_name', true);
        if (empty($local_name)) {
            $local_name = $local_post->post_title;
        }

        $local_city = apollo_get_post_meta($local_id, '_local_city', true);
        $local_state = apollo_get_post_meta($local_id, '_local_state', true);
        $local_region = $local_city && $local_state ? "{$local_city}, {$local_state}" :
                       ($local_city ? $local_city : ($local_state ? $local_state : ''));
        
        // DEBUG
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG Event ' . $event_id . ' Local Post Title: ' . esc_html($local_post->post_title) . ' -->';
            echo '<!-- DEBUG Event ' . $event_id . ' Local Name Meta: ' . esc_html($local_name) . ' -->';
        }
    } else {
        // DEBUG
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG Event ' . $event_id . ' Local Post NOT FOUND or NOT PUBLISHED (ID: ' . $local_id . ') -->';
        }
    }
}

// Fallback to direct location meta
if (empty($local_name)) {
    $local_name = apollo_get_post_meta($event_id, '_event_location', true);
    // DEBUG
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG Event ' . $event_id . ' Using Fallback Location: ' . esc_html($local_name) . ' -->';
    }
}

// ✅ CORRECT: Get DJs from _event_dj_ids (primary) or _timetable (fallback)
$djs_names = array();

// Try _event_dj_ids first (serialized array)
$dj_ids_raw = apollo_get_post_meta($event_id, '_event_dj_ids', true);
$dj_ids = apollo_aem_parse_ids($dj_ids_raw);

// DEBUG: Show what we're getting (admin only)
if (current_user_can('administrator')) {
    echo '<!-- DEBUG Event ' . $event_id . ' DJ IDs Raw: ' . esc_html(print_r($dj_ids_raw, true)) . ' -->';
    echo '<!-- DEBUG Event ' . $event_id . ' DJ IDs Parsed: ' . esc_html(implode(', ', $dj_ids)) . ' -->';
}

if (!empty($dj_ids)) {
    foreach ($dj_ids as $dj_id) {
        $dj_post = get_post($dj_id);
        if ($dj_post && $dj_post->post_status === 'publish') {
            $dj_name = apollo_get_post_meta($dj_id, '_dj_name', true);
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
    $event_timetable = apollo_get_post_meta($event_id, '_event_timetable', true);
    $timetable = apollo_sanitize_timetable($event_timetable);
    if (empty($timetable)) {
        $legacy_timetable = apollo_get_post_meta($event_id, '_timetable', true);
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
                $dj_name = apollo_get_post_meta($dj_id, '_dj_name', true);
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
$iso_date = '';
if ($start_date) {
    $timestamp = strtotime($start_date);
    if ($timestamp && $timestamp > 0) {
        $day = date('j', $timestamp); // Use 'j' for day without leading zero
        $month = date('M', $timestamp);
        $iso_date = date('Y-m-d', $timestamp);

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

// Get local slug for filtering and display
$local_slug = '';
$local_display_name = '';
if ($local_id) {
    $local_post = get_post($local_id);
    if ($local_post) {
        $local_slug = $local_post->post_name;
        if (empty($local_slug)) {
            $local_slug = sanitize_title($local_name);
        }
        // Mostrar o nome real quando disponível; manter slug apenas como fallback
        $local_display_name = !empty($local_name) ? $local_name : $local_slug;
    }
}
// Fallback: use sanitized local name as slug
if (empty($local_slug) && !empty($local_name)) {
    $local_slug = sanitize_title($local_name);
    if (empty($local_display_name)) {
        $local_display_name = $local_name;
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

// ✅ FORCE: Usar configuração de fallback do admin ou animação de loading
if (!$banner_url) {
    $use_loading_animation = get_option('apollo_events_use_loading_animation', true);
    
    if ($use_loading_animation) {
        // Usar data URI com animação de loading (evita requisição extra)
        $banner_url = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Crect fill="%23f0f0f0" width="100" height="100"/%3E%3C/svg%3E';
    } else {
        // Usar URL configurada no admin
        $banner_url = get_option('apollo_events_fallback_banner_url', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070');
    }
    
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG: Using ' . ($use_loading_animation ? 'loading animation' : 'fallback banner') . ' -->';
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

<a href="<?php echo esc_url(get_permalink($event_id)); ?>" 
   class="event_listing" 
   data-motion-card="true"
   data-event-id="<?php echo esc_attr($event_id); ?>" 
   data-category="<?php echo esc_attr($category_slug); ?>" 
   data-local-slug="<?php echo esc_attr($local_slug); ?>"
   data-month-str="<?php echo esc_attr($month_str); ?>"
   data-event-start-date="<?php echo esc_attr($iso_date); ?>"
   style="display: block;">
   
    <!-- Date box outside .picture -->
    <div class="box-date-event">
        <span class="date-day"><?php echo esc_html($day ?: '--'); ?></span>
        <span class="date-month"><?php echo esc_html($month_str ?: '---'); ?></span>
    </div>
    
    <div class="picture">
        <img src="<?php echo esc_url($banner_url); ?>" 
             alt="<?php echo esc_attr($event_title); ?>" 
             loading="lazy">
        
        <?php if (!empty($sounds)): ?>
        <div class="event-card-tags">
            <?php
            $max_tags = 3;
            $count = 0;
            foreach ($sounds as $sound) {
                if ($count >= $max_tags) {
                    break;
                }
                echo '<span>' . esc_html($sound->name) . '</span>';
                $count++;
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="event-line">
        <div class="box-info-event">
            <h2 class="event-li-title afasta-bmin font-extrabold" style="mix-blend-mode:difference!important;"><?php echo esc_html($event_title); ?></h2>
            
            <!-- DJs - SEMPRE EXIBIDO -->
            <p class="event-li-detail of-dj afasta-bmin">
                <i class="ri-sound-module-fill"></i>
                <?php if (!empty($djs_names)) : ?>
                    <span class="font-extrabold">
                        <?php
                        echo esc_html($djs_names[0]);
                        if (count($djs_names) > 1) {
                            echo ', ' . esc_html(implode(', ', array_slice($djs_names, 1)));
                        }
                        ?>
                    </span>
                <?php else : ?>
                    <span><?php esc_html_e('Line-up em breve', 'apollo-events-manager'); ?></span>
                <?php endif; ?>
            </p>
            
            <!-- Local - EXIBIDO SE EXISTIR -->
            <?php if ($local_display_name) : ?>
            <p class="event-li-detail of-location afasta-bmin">
                <i class="ri-map-pin-2-line"></i>
                <span class="event-location-name" style="text-transform: capitalize!important;"><?php echo esc_html($local_display_name); ?></span>
                <?php if ($local_region) : ?>
                <span class="event-location-area" style="text-transform: capitalize!important;opacity: 0.5;">&nbsp;(<?php echo esc_html($local_region); ?>)</span>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</a>
<?php /* phpcs:enable Generic.Files.LineLength, WordPress.Files.FileHeader */ ?>