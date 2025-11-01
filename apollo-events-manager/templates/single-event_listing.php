<?php
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
 * Single Event Page Template - FIXED VERSION
 * Defensive data handling with proper validation
 */

$event_id = get_the_ID();

// === SAFE DATA RETRIEVAL WITH FALLBACKS ===

// Basic event data
$event_title = get_post_meta($event_id, '_event_title', true);
if (empty($event_title)) {
    $event_title = get_the_title();
}

$event_banner = get_post_meta($event_id, '_event_banner', true);
$video_url = get_post_meta($event_id, '_event_video_url', true);
$start_date = get_post_meta($event_id, '_event_start_date', true);
$start_time = get_post_meta($event_id, '_event_start_time', true);
$end_time = get_post_meta($event_id, '_event_end_time', true);
$description = get_post_meta($event_id, '_event_description', true);
if (empty($description)) {
    $description = get_the_content();
}

$tickets_url = get_post_meta($event_id, '_tickets_ext', true);
$cupom_ario = get_post_meta($event_id, '_cupom_ario', true);
$promo_images = get_post_meta($event_id, '_3_imagens_promo', true);
$timetable = get_post_meta($event_id, '_timetable', true);
$final_image = get_post_meta($event_id, '_imagem_final', true);

// === LOCAL DATA WITH VALIDATION ===
$local_id = get_post_meta($event_id, '_event_local', true);
if (empty($local_id)) {
    $local_id = get_post_meta($event_id, '_event_local_ids', true);
}

$local_name = get_post_meta($event_id, '_event_location', true);
$local_address = '';
$local_images = [];
$local_lat = '';
$local_long = '';

// Only process if we have a valid local ID
if (!empty($local_id) && is_numeric($local_id)) {
    $local_post = get_post($local_id);
    
    if ($local_post && $local_post->post_status === 'publish') {
        // Get local name
        $temp_name = get_post_meta($local_id, '_local_name', true);
        if (!empty($temp_name)) {
            $local_name = $temp_name;
        } else {
            $local_name = $local_post->post_title;
        }
        
        // Get address
        $local_address = get_post_meta($local_id, '_local_address', true);
        
        // Get coordinates - try multiple meta keys
        $local_lat = get_post_meta($local_id, '_local_latitude', true);
        if (empty($local_lat)) {
            $local_lat = get_post_meta($local_id, '_local_lat', true);
        }
        
        $local_long = get_post_meta($local_id, '_local_longitude', true);
        if (empty($local_long)) {
            $local_long = get_post_meta($local_id, '_local_lng', true);
        }
        
        // Get local images (up to 5)
        for ($i = 1; $i <= 5; $i++) {
            $img = get_post_meta($local_id, '_local_image_' . $i, true);
            if (!empty($img)) {
                $local_images[] = $img;
            }
        }
    }
}

// Fallback to event coordinates if local coords not found
if (empty($local_lat)) {
    $local_lat = get_post_meta($event_id, '_event_latitude', true);
    if (empty($local_lat)) {
        $local_lat = get_post_meta($event_id, 'geolocation_lat', true);
    }
}

if (empty($local_long)) {
    $local_long = get_post_meta($event_id, '_event_longitude', true);
    if (empty($local_long)) {
        $local_long = get_post_meta($event_id, 'geolocation_long', true);
    }
}

// === GET SOUNDS/GENRES WITH ERROR HANDLING ===
$sounds = wp_get_post_terms($event_id, 'event_sounds', array('fields' => 'all'));
if (is_wp_error($sounds) || empty($sounds)) {
    $sounds = [];
}

// === FORMAT DATE ===
$day = '';
$month = '';
$year = '';
if (!empty($start_date)) {
    $timestamp = strtotime($start_date);
    if ($timestamp !== false) {
        $day = date('d', $timestamp);
        $month_num = date('M', $timestamp);
        $year = date('y', $timestamp);
        
        $month_map = [
            'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 
            'Apr' => 'Abr', 'May' => 'Mai', 'Jun' => 'Jun',
            'Jul' => 'Jul', 'Aug' => 'Ago', 'Sep' => 'Set',
            'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
        ];
        $month = isset($month_map[$month_num]) ? $month_map[$month_num] : $month_num;
    }
}

// === GET BANNER WITH FALLBACKS ===
$banner_url = '';
if (!empty($event_banner)) {
    if (is_numeric($event_banner)) {
        $banner_url = wp_get_attachment_url($event_banner);
    } else {
        $banner_url = $event_banner;
    }
}

if (empty($banner_url) && has_post_thumbnail()) {
    $banner_url = get_the_post_thumbnail_url($event_id, 'full');
}

if (empty($banner_url)) {
    $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
}

// === PROCESS YOUTUBE URL ===
$youtube_embed = '';
if (!empty($video_url)) {
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $video_url, $id)) {
        $video_id = $id[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $video_url, $id)) {
        $video_id = $id[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $video_url, $id)) {
        $video_id = $id[1];
    }
    
    if (!empty($video_id)) {
        $youtube_embed = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&modestbranding=1";
    }
}

// Count favorites
$favorites_count = 40; // Default fallback
?>

<div class="mobile-container">
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if (!empty($youtube_embed)): ?>
        <div class="video-cover">
            <iframe src="<?php echo esc_url($youtube_embed); ?>" allow="autoplay; fullscreen" allowfullscreen frameborder="0"></iframe>
        </div>
        <?php else: ?>
        <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($event_title); ?>">
        <?php endif; ?>
        
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="event-tag-pill">
                <i class="ri-megaphone-fill"></i> Novidade
            </span>
            
            <h1 class="hero-title"><?php echo esc_html($event_title); ?></h1>
            
            <div class="hero-meta">
                <?php if (!empty($day) && !empty($month) && !empty($year)): ?>
                <div class="hero-meta-item">
                    <i class="ri-calendar-line"></i>
                    <span><?php echo $day . ' ' . $month . " '" . $year; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($start_time) && !empty($end_time)): ?>
                <div class="hero-meta-item">
                    <i class="ri-time-line"></i>
                    <span id="Hora"><?php echo esc_html($start_time) . ' — ' . esc_html($end_time); ?></span>
                    <font style="opacity:.7;font-weight:300; font-size:.81rem;">(GMT-03h00)</font>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($local_name)): ?>
                <div class="hero-meta-item">
                    <i class="ri-map-pin-line"></i>
                    <span><?php echo esc_html($local_name); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Event Body -->
    <div class="event-body">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#route_TICKETS" class="quick-action">
                <div class="quick-action-icon"><i class="ri-ticket-2-line"></i></div>
                <span class="quick-action-label">TICKETS</span>
            </a>
            <a href="#route_LINE" class="quick-action">
                <div class="quick-action-icon"><i class="ri-draft-line"></i></div>
                <span class="quick-action-label">Line-up</span>
            </a>
            <a href="#route_ROUTE" class="quick-action">
                <div class="quick-action-icon"><i class="ri-treasure-map-line"></i></div>
                <span class="quick-action-label">ROUTE</span>
            </a>
            <a href="#" class="quick-action" id="favoriteTrigger">
                <div class="quick-action-icon"><i class="ri-rocket-line"></i></div>
                <span class="quick-action-label">Interesse</span>
            </a>
        </div>

        <!-- RSVP Row -->
        <div class="rsvp-row">
            <div class="avatars-explosion">
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/1.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/2.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/3.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/4.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/5.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/6.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/7.jpg')"></div>
                <div class="avatar-count">+35</div>
                <p class="interested-text" style="margin: 0 8px 0px 20px;">
                    <i class="ri-bar-chart-2-fill"></i> <span id="result"><?php echo $favorites_count; ?> interessados</span>
                </p>
            </div>
        </div>

        <!-- Info Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="ri-brain-ai-3-fill"></i> Info
            </h2>
            <div class="info-card">
                <p class="info-text"><?php echo wpautop($description); ?></p>
            </div>
            
            <!-- Music Tags Marquee -->
            <?php if (!empty($sounds)): ?>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php 
                    for ($i = 0; $i < 8; $i++):
                        foreach ($sounds as $sound):
                            if (isset($sound->name)):
                    ?>
                    <span class="music-tag"><?php echo esc_html($sound->name); ?></span>
                    <?php 
                            endif;
                        endforeach;
                    endfor;
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Promo Gallery -->
        <?php if (!empty($promo_images) && is_array($promo_images)): ?>
        <div class="promo-gallery-slider">
            <div class="promo-track" id="promoTrack">
                <?php 
                $image_count = 0;
                foreach ($promo_images as $img_id):
                    if ($image_count >= 5) break;
                    
                    $img_url = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                    if (!empty($img_url)):
                ?>
                <div class="promo-slide" style="border-radius:12px">
                    <img src="<?php echo esc_url($img_url); ?>" alt="Promo <?php echo $image_count + 1; ?>">
                </div>
                <?php 
                        $image_count++;
                    endif;
                endforeach;
                ?>
            </div>
            <?php if ($image_count > 1): ?>
            <div class="promo-controls">
                <button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
                <button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- DJ Lineup -->
        <?php if (!empty($timetable) && is_array($timetable)): ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <div class="lineup-list">
                <?php foreach ($timetable as $slot):
                    // Support multiple timetable formats
                    $dj_id = isset($slot['dj']) ? $slot['dj'] : (isset($slot['dj_id']) ? $slot['dj_id'] : null);
                    $time_in = isset($slot['dj_time_in']) ? $slot['dj_time_in'] : (isset($slot['time_in']) ? $slot['time_in'] : '');
                    $time_out = isset($slot['dj_time_out']) ? $slot['dj_time_out'] : (isset($slot['time_out']) ? $slot['time_out'] : '');
                    
                    if (empty($dj_id)) continue;
                    
                    // Get DJ data
                    $dj_name = '';
                    $dj_photo_url = '';
                    $dj_permalink = '#';
                    
                    if (is_numeric($dj_id)) {
                        $dj_post = get_post($dj_id);
                        if ($dj_post && $dj_post->post_status === 'publish') {
                            $dj_name = get_post_meta($dj_id, '_dj_name', true);
                            if (empty($dj_name)) {
                                $dj_name = $dj_post->post_title;
                            }
                            
                            $dj_photo = get_post_meta($dj_id, '_photo', true);
                            if (is_numeric($dj_photo)) {
                                $dj_photo_url = wp_get_attachment_url($dj_photo);
                            } elseif (!empty($dj_photo)) {
                                $dj_photo_url = $dj_photo;
                            }
                            
                            if (empty($dj_photo_url) && has_post_thumbnail($dj_id)) {
                                $dj_photo_url = get_the_post_thumbnail_url($dj_id, 'medium');
                            }
                            
                            $dj_permalink = get_permalink($dj_id);
                        }
                    } else {
                        // If dj_id is a string (DJ name), use it directly
                        $dj_name = $dj_id;
                    }
                    
                    if (empty($dj_name)) continue;
                ?>
                <div class="lineup-card">
                    <?php if (!empty($dj_photo_url)): ?>
                    <img src="<?php echo esc_url($dj_photo_url); ?>" alt="<?php echo esc_attr($dj_name); ?>" class="lineup-avatar-img">
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <a href="<?php echo esc_url($dj_permalink); ?>" target="_blank" class="dj-link">
                                <?php echo esc_html($dj_name); ?>
                            </a>
                        </h3>
                        <?php if (!empty($time_in) && !empty($time_out)): ?>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span><?php echo esc_html($time_in) . ' - ' . esc_html($time_out); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Venue Section -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($local_name); ?>
            </h2>
            <?php if (!empty($local_address)): ?>
            <p class="local-endereco"><?php echo esc_html($local_address); ?></p>
            <?php endif; ?>
            
            <!-- Local Images Slider -->
            <?php if (!empty($local_images)): ?>
            <div class="local-images-slider" style="min-height:450px;">
                <div class="local-images-track" id="localTrack" style="min-height:500px;">
                    <?php 
                    $img_count = 0;
                    foreach ($local_images as $img):
                        if ($img_count >= 5) break;
                        
                        $img_url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                        if (!empty($img_url)):
                    ?>
                    <div class="local-image" style="min-height:450px;">
                        <img src="<?php echo esc_url($img_url); ?>" alt="Local Image <?php echo $img_count + 1; ?>">
                    </div>
                    <?php 
                            $img_count++;
                        endif;
                    endforeach;
                    ?>
                </div>
                <?php if ($img_count > 1): ?>
                <div class="slider-nav" id="localDots"></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Map View -->
            <?php if (!empty($local_lat) && !empty($local_long)): ?>
            <div class="map-view" id="eventMap" 
                 style="margin:0 auto; z-index:0; width:100%; height:285px; border-radius:12px;"
                 data-lat="<?php echo esc_attr($local_lat); ?>"
                 data-lng="<?php echo esc_attr($local_long); ?>">
            </div>
            
            <script>
            (function() {
                if (typeof L !== 'undefined') {
                    var mapEl = document.getElementById('eventMap');
                    if (mapEl) {
                        var lat = parseFloat(mapEl.dataset.lat);
                        var lng = parseFloat(mapEl.dataset.lng);
                        if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                            var map = L.map('eventMap').setView([lat, lng], 15);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap',
                                maxZoom: 19
                            }).addTo(map);
                            L.marker([lat, lng]).addTo(map).bindPopup('<?php echo esc_js($local_name); ?>');
                        }
                    }
                }
            })();
            </script>
            
            <!-- Route Controls -->
            <div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">
                <div class="route-input">
                    <i class="ri-map-pin-line"></i>
                    <input type="text" id="origin-input" placeholder="Seu endereço de partida">
                </div>
                <button id="route-btn" class="route-button">
                    <i class="ri-send-plane-line"></i>
                </button>
            </div>
            
            <script>
            document.getElementById('route-btn')?.addEventListener('click', function() {
                var origin = document.getElementById('origin-input').value;
                if (origin) {
                    var url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + 
                              '&destination=<?php echo esc_js($local_lat); ?>,<?php echo esc_js($local_long); ?>';
                    window.open(url, '_blank');
                } else {
                    alert('Digite seu endereço de partida');
                }
            });
            </script>
            <?php else: ?>
            <div class="map-view" style="margin:0 auto; z-index:0; background:green; width:100%; height:285px; border-radius:12px; background-image:url('https://img.freepik.com/premium-vector/city-map-scheme-background-flat-style-vector-illustration_833641-2300.jpg'); background-size:cover; background-repeat:no-repeat; background-position:center;">
            </div>
            <?php endif; ?>
        </section>

        <!-- Tickets Section -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos
            </h2>
            
            <div class="tickets-grid">
                <?php if (!empty($tickets_url)): ?>
                <a href="<?php echo esc_url($tickets_url); ?>?ref=apollo.rio.br" class="ticket-card" target="_blank">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name">
                            <span id="changingword" style="opacity: 1;">Biglietti</span>
                        </h3>
                        <span class="ticket-cta">Seguir para Bilheteria Digital →</span>
                    </div>
                </a>
                <?php else: ?>
                <div class="ticket-card disabled">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name">Biglietti</h3>
                        <span class="ticket-cta">Em breve</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Apollo Coupon -->
                <?php if (!empty($cupom_ario)): ?>
                <div class="apollo-coupon-detail">
                    <i class="ri-coupon-3-line"></i>
                    <span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
                    <button class="copy-code-mini" onclick="copyPromoCode()">
                        <i class="ri-file-copy-fill"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Final Event Image -->
        <?php if (!empty($final_image)): 
            $final_img_url = is_numeric($final_image) ? wp_get_attachment_url($final_image) : $final_image;
            if (!empty($final_img_url)):
        ?>
        <section class="section">
            <div class="secondary-image" style="margin-bottom:3rem;">
                <img src="<?php echo esc_url($final_img_url); ?>" alt="Event Final Photo">
            </div>
        </section>
        <?php 
            endif;
        endif; 
        ?>
        
        <!-- Protection Notice -->
        <section class="section">
            <div class="respaldo_eve">
                *A organização e execução deste evento cabem integralmente aos seus idealizadores.
            </div>
        </section>
    </div>

    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <a href="#route_TICKETS" class="bottom-btn primary" id="bottomTicketBtn">
            <i class="ri-ticket-fill"></i>
            <span id="changingword">Tickets</span>
        </a>
        <button class="bottom-btn secondary" id="bottomShareBtn">
            <i class="ri-share-forward-line"></i>
        </button>
    </div>
</div>

<script src="https://assets.apollo.rio.br/event-page.js"></script>