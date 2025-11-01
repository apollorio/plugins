<?php 
/**
 * 🔍 DEBUG MODE - Remove after debugging!
 * Shows all database values for current post
 */

// Only show to logged-in administrators
if (current_user_can('administrator')) {
    $debug_id = get_the_ID();
    
    echo '<div style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:999999;overflow:auto;background:rgba(0,0,0,0.95);padding:20px;">';
    echo '<button onclick="this.parentElement.remove()" style="position:sticky;top:10px;right:10px;float:right;background:#ff4444;color:#fff;border:none;padding:10px 20px;cursor:pointer;border-radius:5px;font-weight:bold;">✕ CLOSE DEBUG</button>';
    
    echo '<div style="background:#1e1e1e;color:#d4d4d4;padding:30px;border-radius:10px;font-family:Consolas,Monaco,monospace;font-size:14px;line-height:1.6;">';
    
    // ====================
    // POST INFORMATION
    // ====================
    echo '<h2 style="color:#4ec9b0;border-bottom:2px solid #4ec9b0;padding-bottom:10px;margin-top:0;">📄 POST INFORMATION</h2>';
    
    $post = get_post($debug_id);
    echo '<table style="width:100%;border-collapse:collapse;margin-bottom:30px;">';
    echo '<tr style="background:#2d2d2d;"><td style="padding:8px;color:#9cdcfe;font-weight:bold;width:200px;">Post ID:</td><td style="padding:8px;color:#ce9178;">' . $debug_id . '</td></tr>';
    echo '<tr><td style="padding:8px;color:#9cdcfe;font-weight:bold;">Post Title:</td><td style="padding:8px;color:#ce9178;">' . esc_html($post->post_title) . '</td></tr>';
    echo '<tr style="background:#2d2d2d;"><td style="padding:8px;color:#9cdcfe;font-weight:bold;">Post Type:</td><td style="padding:8px;color:#ce9178;">' . $post->post_type . '</td></tr>';
    echo '<tr><td style="padding:8px;color:#9cdcfe;font-weight:bold;">Post Status:</td><td style="padding:8px;color:#ce9178;">' . $post->post_status . '</td></tr>';
    echo '</table>';
    
    // ====================
    // ALL POST META
    // ====================
    echo '<h2 style="color:#4ec9b0;border-bottom:2px solid #4ec9b0;padding-bottom:10px;">🗄️ ALL POST META (Database Values)</h2>';
    echo '<div style="background:#0d1117;padding:20px;border-radius:5px;border:1px solid #30363d;margin-bottom:30px;">';
    
    $all_meta = get_post_meta($debug_id);
    
    if (empty($all_meta)) {
        echo '<p style="color:#f85149;">⚠️ NO META DATA FOUND!</p>';
    } else {
        echo '<table style="width:100%;border-collapse:collapse;">';
        echo '<thead><tr style="background:#161b22;border-bottom:2px solid #30363d;">';
        echo '<th style="padding:10px;text-align:left;color:#58a6ff;font-weight:bold;">Meta Key</th>';
        echo '<th style="padding:10px;text-align:left;color:#58a6ff;font-weight:bold;">Value</th>';
        echo '<th style="padding:10px;text-align:left;color:#58a6ff;font-weight:bold;">Type</th>';
        echo '</tr></thead><tbody>';
        
        $row_color = false;
        foreach ($all_meta as $key => $value) {
            $bg = $row_color ? '#0d1117' : '#161b22';
            $row_color = !$row_color;
            
            // Get single value
            $single_value = isset($value[0]) ? $value[0] : '';
            
            // Determine type and format value
            $type = gettype($single_value);
            $formatted_value = '';
            
            if (is_array($single_value)) {
                $formatted_value = '<pre style="margin:0;color:#79c0ff;max-height:200px;overflow:auto;">' . esc_html(print_r($single_value, true)) . '</pre>';
                $type = 'array (' . count($single_value) . ' items)';
            } elseif (is_object($single_value)) {
                $formatted_value = '<pre style="margin:0;color:#79c0ff;max-height:200px;overflow:auto;">' . esc_html(print_r($single_value, true)) . '</pre>';
                $type = 'object';
            } elseif (is_bool($single_value)) {
                $formatted_value = '<span style="color:#79c0ff;">' . ($single_value ? 'true' : 'false') . '</span>';
                $type = 'boolean';
            } elseif (is_numeric($single_value)) {
                $formatted_value = '<span style="color:#79c0ff;">' . $single_value . '</span>';
                $type = 'numeric';
            } elseif (strlen($single_value) > 100) {
                $formatted_value = '<div style="max-height:100px;overflow:auto;color:#a5d6ff;">' . esc_html(substr($single_value, 0, 200)) . '...</div>';
                $type = 'string (long)';
            } else {
                $formatted_value = '<span style="color:#a5d6ff;">' . esc_html($single_value) . '</span>';
                $type = 'string';
            }
            
            // Highlight important meta keys
            $key_color = '#7ee787'; // default green
            if (strpos($key, '_event_') === 0) {
                $key_color = '#f85149'; // red for event keys
            } elseif (strpos($key, '_local_') === 0) {
                $key_color = '#d29922'; // orange for local keys
            } elseif (strpos($key, '_dj_') === 0) {
                $key_color = '#bc8cff'; // purple for DJ keys
            }
            
            echo '<tr style="background:' . $bg . ';border-bottom:1px solid #21262d;">';
            echo '<td style="padding:10px;color:' . $key_color . ';font-weight:bold;word-break:break-all;"><code>' . esc_html($key) . '</code></td>';
            echo '<td style="padding:10px;">' . $formatted_value . '</td>';
            echo '<td style="padding:10px;color:#8b949e;font-style:italic;">' . $type . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '</div>';
    
    // ====================
    // TAXONOMIES
    // ====================
    echo '<h2 style="color:#4ec9b0;border-bottom:2px solid #4ec9b0;padding-bottom:10px;">🏷️ TAXONOMIES & TERMS</h2>';
    echo '<div style="background:#0d1117;padding:20px;border-radius:5px;border:1px solid #30363d;margin-bottom:30px;">';
    
    $taxonomies = get_object_taxonomies($post->post_type);
    
    if (empty($taxonomies)) {
        echo '<p style="color:#f85149;">⚠️ NO TAXONOMIES REGISTERED FOR THIS POST TYPE!</p>';
    } else {
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($debug_id, $taxonomy);
            
            echo '<h3 style="color:#d2a8ff;margin-top:20px;">' . esc_html($taxonomy) . '</h3>';
            
            if (is_wp_error($terms)) {
                echo '<p style="color:#f85149;">ERROR: ' . esc_html($terms->get_error_message()) . '</p>';
            } elseif (empty($terms)) {
                echo '<p style="color:#8b949e;font-style:italic;">No terms assigned</p>';
            } else {
                echo '<ul style="list-style:none;padding:0;margin:10px 0;">';
                foreach ($terms as $term) {
                    echo '<li style="padding:5px;background:#161b22;margin:5px 0;border-radius:3px;">';
                    echo '<span style="color:#79c0ff;">ID:</span> <span style="color:#a5d6ff;">' . $term->term_id . '</span> | ';
                    echo '<span style="color:#79c0ff;">Slug:</span> <span style="color:#a5d6ff;">' . esc_html($term->slug) . '</span> | ';
                    echo '<span style="color:#79c0ff;">Name:</span> <span style="color:#a5d6ff;">' . esc_html($term->name) . '</span>';
                    echo '</li>';
                }
                echo '</ul>';
            }
        }
    }
    
    echo '</div>';
    
    // ====================
    // RELATED POSTS (Local, DJs)
    // ====================
    echo '<h2 style="color:#4ec9b0;border-bottom:2px solid #4ec9b0;padding-bottom:10px;">🔗 RELATED POSTS</h2>';
    echo '<div style="background:#0d1117;padding:20px;border-radius:5px;border:1px solid #30363d;margin-bottom:30px;">';
    
    // Check for local
    $local_id = get_post_meta($debug_id, '_event_local', true);
    if (empty($local_id)) {
        $local_id = get_post_meta($debug_id, '_event_local_ids', true);
    }
    
    echo '<h3 style="color:#d2a8ff;">Event Local/Venue:</h3>';
    if ($local_id && is_numeric($local_id)) {
        $local_post = get_post($local_id);
        if ($local_post) {
            echo '<p style="color:#7ee787;">✓ Found Local Post</p>';
            echo '<ul style="list-style:none;padding:0;margin:10px 0;">';
            echo '<li style="padding:5px;background:#161b22;margin:5px 0;border-radius:3px;">';
            echo '<span style="color:#79c0ff;">ID:</span> <span style="color:#a5d6ff;">' . $local_id . '</span><br>';
            echo '<span style="color:#79c0ff;">Title:</span> <span style="color:#a5d6ff;">' . esc_html($local_post->post_title) . '</span><br>';
            echo '<span style="color:#79c0ff;">Status:</span> <span style="color:#a5d6ff;">' . $local_post->post_status . '</span>';
            echo '</li></ul>';
            
            // Show local meta
            echo '<h4 style="color:#f0883e;margin-top:10px;">Local Meta Data:</h4>';
            $local_meta = get_post_meta($local_id);
            echo '<pre style="background:#161b22;padding:10px;border-radius:3px;color:#a5d6ff;max-height:200px;overflow:auto;">';
            print_r($local_meta);
            echo '</pre>';
        } else {
            echo '<p style="color:#f85149;">⚠️ Local ID found but post does not exist!</p>';
        }
    } else {
        echo '<p style="color:#8b949e;font-style:italic;">No local assigned</p>';
    }
    
    // Check for DJs in timetable
    echo '<h3 style="color:#d2a8ff;margin-top:20px;">DJs (from Timetable):</h3>';
    $timetable = get_post_meta($debug_id, '_timetable', true);
    
    if (!empty($timetable) && is_array($timetable)) {
        echo '<p style="color:#7ee787;">✓ Found Timetable Data</p>';
        echo '<pre style="background:#161b22;padding:10px;border-radius:3px;color:#a5d6ff;max-height:300px;overflow:auto;">';
        print_r($timetable);
        echo '</pre>';
    } else {
        echo '<p style="color:#8b949e;font-style:italic;">No timetable data found</p>';
    }
    
    echo '</div>';
    
    // ====================
    // QUICK COPY COMMANDS
    // ====================
    echo '<h2 style="color:#4ec9b0;border-bottom:2px solid #4ec9b0;padding-bottom:10px;">📋 QUICK COPY COMMANDS</h2>';
    echo '<div style="background:#0d1117;padding:20px;border-radius:5px;border:1px solid #30363d;">';
    echo '<p style="color:#8b949e;">Copy these commands to test in your templates:</p>';
    
    echo '<div style="background:#161b22;padding:15px;border-radius:5px;margin:10px 0;border-left:3px solid #58a6ff;">';
    echo '<p style="margin:0;color:#7ee787;font-weight:bold;">Get Event Title:</p>';
    echo '<code style="color:#a5d6ff;">get_post_meta(' . $debug_id . ', \'_event_title\', true)</code>';
    echo '</div>';
    
    echo '<div style="background:#161b22;padding:15px;border-radius:5px;margin:10px 0;border-left:3px solid #58a6ff;">';
    echo '<p style="margin:0;color:#7ee787;font-weight:bold;">Get Event Date:</p>';
    echo '<code style="color:#a5d6ff;">get_post_meta(' . $debug_id . ', \'_event_start_date\', true)</code>';
    echo '</div>';
    
    echo '<div style="background:#161b22;padding:15px;border-radius:5px;margin:10px 0;border-left:3px solid #58a6ff;">';
    echo '<p style="margin:0;color:#7ee787;font-weight:bold;">Get Local ID:</p>';
    echo '<code style="color:#a5d6ff;">get_post_meta(' . $debug_id . ', \'_event_local\', true)</code>';
    echo '</div>';
    
    echo '</div>';
    
    echo '</div></div>';
    
    // STOP EXECUTION - Don't render the actual template
    echo '<script>console.log("🔍 Debug mode active - template rendering stopped");</script>';
    exit;
}
?>
/**
 * Template Apollo: Event Card
 *
 * Used in the events listing loop
 */

// DEBUG: Show all event meta for admins
if (current_user_can('administrator')) {
    echo '<pre style="background:#000;color:#0f0;padding:20px;overflow:auto;max-height:300px;">';
    echo "Event ID: " . get_the_ID() . "\n\n";
    print_r(get_post_meta(get_the_ID()));
    echo '</pre>';
}

// Get event data
$event_id = get_the_ID();
$event_title = get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$start_date = get_post_meta($event_id, '_event_start_date', true);
$event_banner = get_post_meta($event_id, '_event_banner', true);

// Get categories with proper error handling
$categories = wp_get_post_terms($event_id, 'event_listing_category');
$categories = is_wp_error($categories) ? array() : $categories;
$category_slug = !empty($categories) && is_array($categories) ? $categories[0]->slug : 'general';

// Get sounds/genres with proper error handling
$sounds = wp_get_post_terms($event_id, 'event_sounds');
$sounds = is_wp_error($sounds) ? array() : $sounds;

// Get local with comprehensive validation
$local_id = get_post_meta($event_id, '_event_local', true);
$local_name = '';
$local_region = '';

if (!empty($local_id) && is_numeric($local_id)) {
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

// Get DJs from timetable with comprehensive validation
$timetable = get_post_meta($event_id, '_timetable', true);
$djs_names = array();
if (!empty($timetable) && is_array($timetable)) {
    foreach ($timetable as $slot) {
        if (isset($slot['dj']) && !empty($slot['dj'])) {
            $dj_id = $slot['dj'];

            if (is_numeric($dj_id)) {
                $dj_post = get_post($dj_id);
                if ($dj_post && $dj_post->post_status === 'publish') {
                    $dj_name = get_post_meta($dj_id, '_dj_name', true);
                    if (empty($dj_name)) {
                        $dj_name = $dj_post->post_title;
                    }
                } else {
                    continue; // Skip invalid DJ posts
                }
            } else {
                $dj_name = $dj_id; // If it's already a name string
            }

            if (!empty($dj_name)) {
                $djs_names[] = $dj_name;
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
}

// Get banner image
$banner_url = '';
if ($event_banner) {
    if (is_numeric($event_banner)) {
        $banner_url = wp_get_attachment_url($event_banner);
    } else {
        $banner_url = $event_banner;
    }
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
        <span class="date-day"><?php echo $day; ?></span>
        <span class="date-month"><?php echo $month_str; ?></span>
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
                <span id="Local_nome"><?php echo esc_html($local_name); ?></span> 
                <?php if ($local_region): ?>
                <span style="opacity:.5" id="local_regiao"><?php echo esc_html($local_region); ?></span>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</a>

