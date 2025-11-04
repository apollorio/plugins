<?php
/**
 * Single Event Standalone Template
 * For direct page access (not lightbox)
 * Based 100% on CodePen EaPpjXP
 */

defined('ABSPATH') || exit;

$event_id = get_the_ID();

// === ALL EVENT VARIABLES ===
$event_title = get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$event_banner = get_post_meta($event_id, '_event_banner', true);
$event_video_url = get_post_meta($event_id, '_event_video_url', true);
$event_start_date = get_post_meta($event_id, '_event_start_date', true);
$event_end_date = get_post_meta($event_id, '_event_end_date', true);
$event_start_time = get_post_meta($event_id, '_event_start_time', true);
$event_end_time = get_post_meta($event_id, '_event_end_time', true);
$event_description = get_post_meta($event_id, '_event_description', true) ?: get_the_content();
$event_tickets_ext = get_post_meta($event_id, '_tickets_ext', true);
$event_cupom_ario = get_post_meta($event_id, '_cupom_ario', true);
$event_3_imagens_promo = get_post_meta($event_id, '_3_imagens_promo', true);
$event_timetable = get_post_meta($event_id, '_timetable', true);
$event_imagem_final = get_post_meta($event_id, '_imagem_final', true);

// === LOCAL VARIABLES WITH VALIDATION ===
// ✅ CORRECT: Use _event_local_ids first
$event_local_id = get_post_meta($event_id, '_event_local_ids', true);
if (empty($event_local_id)) {
    $event_local_id = get_post_meta($event_id, '_event_local', true); // Fallback
}

$event_local_title = get_post_meta($event_id, '_event_location', true);
$event_local_address = '';
$event_local_regiao = '';
$event_local_images = [];
$event_local_latitude = '';
$event_local_longitude = '';

// Only process if we have a valid local ID
if (!empty($event_local_id) && is_numeric($event_local_id)) {
    $local_post = get_post($event_local_id);

    if ($local_post && $local_post->post_status === 'publish') {
        $temp_title = get_post_meta($event_local_id, '_local_name', true);
        if (!empty($temp_title)) {
            $event_local_title = $temp_title;
        } else {
            $event_local_title = $local_post->post_title;
        }

        $event_local_address = get_post_meta($event_local_id, '_local_address', true);

        // Get coordinates - try multiple meta keys
        $event_local_latitude = get_post_meta($event_local_id, '_local_latitude', true);
        if (empty($event_local_latitude)) {
            $event_local_latitude = get_post_meta($event_local_id, '_local_lat', true);
        }

        $event_local_longitude = get_post_meta($event_local_id, '_local_longitude', true);
        if (empty($event_local_longitude)) {
            $event_local_longitude = get_post_meta($event_local_id, '_local_lng', true);
        }

        $event_local_city = get_post_meta($event_local_id, '_local_city', true);
        $event_local_state = get_post_meta($event_local_id, '_local_state', true);
        $event_local_regiao = $event_local_city ? $event_local_city : ($event_local_state ?: '');

        // Get local images (up to 5)
        for ($i = 1; $i <= 5; $i++) {
            $img = get_post_meta($event_local_id, '_local_image_' . $i, true);
            if (!empty($img)) {
                $event_local_images[] = $img;
            }
        }
    }
}

// Fallback to event coordinates
if (!$event_local_latitude) $event_local_latitude = get_post_meta($event_id, '_event_latitude', true) ?: get_post_meta($event_id, 'geolocation_lat', true);
if (!$event_local_longitude) $event_local_longitude = get_post_meta($event_id, '_event_longitude', true) ?: get_post_meta($event_id, 'geolocation_long', true);

// === SOUNDS/GENRES ===
$event_sounds = wp_get_post_terms($event_id, 'event_sounds');
if (is_wp_error($event_sounds)) $event_sounds = [];

// === DATE FORMATTING ===
$event_day = '';
$event_month = '';
$event_year = '';
if ($event_start_date) {
    $timestamp = strtotime($event_start_date);
    $event_day = date('d', $timestamp);
    $month_abbr = date('M', $timestamp);
    $event_year = date('y', $timestamp);
    
    $month_map = [
        'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 
        'Apr' => 'Abr', 'May' => 'Mai', 'Jun' => 'Jun',
        'Jul' => 'Jul', 'Aug' => 'Ago', 'Sep' => 'Set',
        'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
    ];
    $event_month = $month_map[$month_abbr] ?? $month_abbr;
}

// === BANNER/VIDEO ===
// ✅ CORRECT: Banner is URL, not attachment ID
$event_banner_url = '';
if ($event_banner && filter_var($event_banner, FILTER_VALIDATE_URL)) {
    $event_banner_url = $event_banner; // It's already a URL!
} elseif ($event_banner && is_numeric($event_banner)) {
    $event_banner_url = wp_get_attachment_url($event_banner); // Fallback if numeric
}
if (!$event_banner_url && has_post_thumbnail()) {
    $event_banner_url = get_the_post_thumbnail_url($event_id, 'full');
}
if (!$event_banner_url) {
    $event_banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
}

// ✅ CORRECT YOUTUBE PROCESSING
$event_youtube_embed = '';
if ($event_video_url) {
    $video_id = '';
    
    // Try all YouTube URL patterns
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $event_video_url, $matches)) {
        $video_id = $matches[1];
    }
    
    // Debug log (only if WP_DEBUG_LOG enabled)
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && !empty($video_id)) {
        error_log("✅ YouTube Video ID extracted for event {$event_id}: {$video_id}");
    }
    
    if (!empty($video_id)) {
        $event_youtube_embed = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&modestbranding=1";
    } else if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log("❌ YouTube Video ID NOT extracted for event {$event_id} from URL: {$event_video_url}");
    }
}

// === FAVORITES COUNT ===
$event_favorites_count = function_exists('favorites_get_count') ? favorites_get_count($event_id) : 40;

// === EVENT TAGS ===
$event_tags = wp_get_post_terms($event_id, 'event_listing_tag');
if (is_wp_error($event_tags)) $event_tags = [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
    <title><?php echo esc_html($event_title); ?> @ <?php echo $event_day . ' ' . $event_month; ?> - Apollo::rio</title>
    <link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
    
    <?php wp_head(); ?>
</head>
<body>
<div class="mobile-container">
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if ($event_youtube_embed): ?>
        <div class="video-cover">
            <iframe src="<?php echo esc_url($event_youtube_embed); ?>" allow="autoplay; fullscreen" allowfullscreen frameborder="0"></iframe>
        </div>
        <?php else: ?>
        <img src="<?php echo esc_url($event_banner_url); ?>" alt="<?php echo esc_attr($event_title); ?>">
        <?php endif; ?>
        
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <?php if (!empty($event_tags)): ?>
                <?php foreach (array_slice($event_tags, 0, 4) as $tag): ?>
                <span class="event-tag-pill">
                    <i class="ri-megaphone-fill"></i> <?php echo esc_html($tag->name); ?>
                </span>
                <?php endforeach; ?>
            <?php else: ?>
            <span class="event-tag-pill">
                <i class="ri-megaphone-fill"></i> Novidade
            </span>
            <?php endif; ?>
            
            <h1 class="hero-title"><?php echo esc_html($event_title); ?></h1>
            
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <i class="ri-calendar-line"></i>
                    <span><?php echo esc_html($event_day . ' ' . $event_month . " '" . $event_year); ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-time-line"></i>
                    <span id="Hora"><?php echo esc_html($event_start_time . ' — ' . $event_end_time); ?></span>
                    <font style="opacity:.7;font-weight:300; font-size:.81rem;">(GMT-03h00)</font>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-map-pin-line"></i>
                    <span class="event_local"><?php echo esc_html($event_local_title); ?></span>
                    <?php if ($event_local_regiao): ?>
                    <span style="opacity:.5" class="event_local_regiao">(<?php echo esc_html($event_local_regiao); ?>)</span>
                    <?php endif; ?>
                </div>
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
            <?php 
            // ✅ FAVORITES INFRASTRUCTURE
            $user_favorited = false; // TODO: Check user meta when social features ready
            ?>
            <a href="#" class="quick-action apollo-favorite-trigger" id="favoriteTrigger" data-event-id="<?php echo $event_id; ?>">
                <div class="quick-action-icon">
                    <i class="<?php echo $user_favorited ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
                </div>
                <span class="quick-action-label">Interesse</span>
            </a>
        </div>

        <!-- RSVP Row -->
        <div class="rsvp-row">
            <div class="avatars-explosion">
                <?php
                // Get users who favorited (if Favorites plugin active)
                $favorited_users = function_exists('favorites_get_users') ? favorites_get_users($event_id) : [];
                
                if (!empty($favorited_users)):
                    $shown = 0;
                    foreach ($favorited_users as $user_id):
                        if ($shown >= 10) break;
                        $avatar_url = get_avatar_url($user_id, ['size' => 100]);
                ?>
                <div class="avatar" style="background-image: url('<?php echo esc_url($avatar_url); ?>')"></div>
                <?php 
                        $shown++;
                    endforeach;
                    $remaining = max(0, count($favorited_users) - 10);
                else:
                    // Sample avatars
                    for ($i = 1; $i <= 10; $i++):
                        $gender = ($i % 2 == 0) ? 'women' : 'men';
                ?>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/<?php echo $gender; ?>/<?php echo $i; ?>.jpg')"></div>
                <?php 
                    endfor;
                    $remaining = 35;
                endif;
                ?>
                <div class="avatar-count">+<?php echo $remaining; ?></div>
                <p class="interested-text" style="margin: 0 8px 0px 20px;">
                    <i class="ri-bar-chart-2-fill"></i> 
                    <span id="result"><?php echo $event_favorites_count; ?> interessados</span>
                </p>
            </div>
        </div>

        <!-- Info Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="ri-brain-ai-3-fill"></i> Info
            </h2>
            <div class="info-card">
                <p class="info-text"><?php echo wp_kses_post(wpautop($event_description)); ?></p>
            </div>
            
            <!-- Music Tags Marquee (8x repetition) -->
            <?php if (!empty($event_sounds)): ?>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php for ($rep = 0; $rep < 8; $rep++): ?>
                        <?php foreach ($event_sounds as $sound): ?>
                        <span class="music-tag"><?php echo esc_html($sound->name); ?></span>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Promo Gallery (max 5) -->
        <?php if ($event_3_imagens_promo && is_array($event_3_imagens_promo)): ?>
        <div class="promo-gallery-slider">
            <div class="promo-track" id="promoTrack">
                <?php 
                $img_count = 0;
                foreach ($event_3_imagens_promo as $img_id):
                    if ($img_count >= 5) break;
                    $img_url = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                    if ($img_url):
                ?>
                <div class="promo-slide" style="border-radius:12px">
                    <img src="<?php echo esc_url($img_url); ?>">
                </div>
                <?php 
                        $img_count++;
                    endif;
                endforeach;
                ?>
            </div>
            <div class="promo-controls">
                <button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
                <button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- DJ Lineup -->
        <?php
        // ✅ CORRECT: Try _event_dj_ids first, then _timetable
        $dj_lineup = [];
        
        // Try _event_dj_ids (serialized array)
        $dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
        if (!empty($dj_ids_raw)) {
            $dj_ids = maybe_unserialize($dj_ids_raw);
            if (is_array($dj_ids)) {
                foreach ($dj_ids as $dj_id) {
                    $dj_lineup[] = ['dj' => intval($dj_id)];
                }
            }
        }
        
        // Fallback: Use _timetable if available
        if (empty($dj_lineup) && !empty($event_timetable) && is_array($event_timetable)) {
            $dj_lineup = $event_timetable;
        }
        ?>
        
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <?php if (!empty($dj_lineup)): ?>
            <div class="lineup-list">
                <?php foreach ($dj_lineup as $slot):
                    // Support multiple timetable formats
                    $dj_id = isset($slot['dj']) ? $slot['dj'] : (isset($slot['dj_id']) ? $slot['dj_id'] : null);
                    $dj_time_in = isset($slot['dj_time_in']) ? $slot['dj_time_in'] : (isset($slot['time_in']) ? $slot['time_in'] : '');
                    $dj_time_out = isset($slot['dj_time_out']) ? $slot['dj_time_out'] : (isset($slot['time_out']) ? $slot['time_out'] : '');

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
                            if (empty($dj_photo)) {
                                $dj_photo = get_post_meta($dj_id, '_dj_image', true);
                            }

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
                    <?php if ($dj_photo_url): ?>
                    <img src="<?php echo esc_url($dj_photo_url); ?>" alt="<?php echo esc_attr($dj_name); ?>" class="lineup-avatar-img">
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <a href="<?php echo esc_url($dj_permalink); ?>" target="_blank" class="dj-link">
                                <?php echo esc_html($dj_name); ?>
                            </a>
                        </h3>
                        <?php if ($dj_time_in && $dj_time_out): ?>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span><?php echo esc_html($dj_time_in . ' - ' . $dj_time_out); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="lineup-placeholder" style="padding:40px 20px;text-align:center;background:var(--bg-surface,#f5f5f5);border-radius:12px;">
                <i class="ri-disc-line" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                <p style="color:var(--text-secondary,#999);margin:0;">Line-up em breve</p>
            </div>
            <?php endif; ?>
        </section>

        <!-- Local Section -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($event_local_title); ?>
            </h2>
            <p class="local-endereco"><?php echo esc_html($event_local_address); ?></p>
            
            <!-- Local Images Slider (max 5) -->
            <?php if (!empty($event_local_images)): ?>
            <div class="local-images-slider" style="min-height:450px;">
                <div class="local-images-track" id="localTrack" style="min-height:500px;">
                    <?php 
                    $img_count = 0;
                    foreach ($event_local_images as $img):
                        if ($img_count >= 5) break;
                        $img_url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                        if ($img_url):
                    ?>
                    <div class="local-image" style="min-height:450px;">
                        <img src="<?php echo esc_url($img_url); ?>">
                    </div>
                    <?php 
                            $img_count++;
                        endif;
                    endforeach;
                    ?>
                </div>
                <div class="slider-nav" id="localDots"></div>
            </div>
            <?php endif; ?>

            <!-- Map View (OpenStreetMap) -->
            <?php
            // ✅ COMPREHENSIVE COORDINATE FALLBACK
            $map_lat = $map_lng = 0;
            
            // Tentativa 1: Local vinculado (múltiplas variações)
            if (!empty($event_local_id) && is_numeric($event_local_id)) {
                foreach (['_local_latitude','_local_lat'] as $k) {
                    if ($v = get_post_meta($event_local_id, $k, true)) { 
                        $map_lat = $v; 
                        break; 
                    }
                }
                foreach (['_local_longitude','_local_lng'] as $k) {
                    if ($v = get_post_meta($event_local_id, $k, true)) { 
                        $map_lng = $v; 
                        break; 
                    }
                }
            }
            
            // Tentativa 2: Campos no evento (fallback)
            if (!$map_lat) {
                foreach (['_event_latitude','geolocation_lat'] as $k) {
                    if ($v = get_post_meta($event_id, $k, true)) { 
                        $map_lat = $v; 
                        break; 
                    }
                }
            }
            if (!$map_lng) {
                foreach (['_event_longitude','geolocation_long'] as $k) {
                    if ($v = get_post_meta($event_id, $k, true)) { 
                        $map_lng = $v; 
                        break; 
                    }
                }
            }
            
            // Sanitiza e valida
            $map_lat = is_numeric($map_lat) ? floatval($map_lat) : 0;
            $map_lng = is_numeric($map_lng) ? floatval($map_lng) : 0;
            ?>
            
            <?php if ($map_lat && $map_lng && $map_lat !== 0 && $map_lng !== 0): ?>
            <div id="eventMap" data-lat="<?php echo $map_lat; ?>" data-lng="<?php echo $map_lng; ?>" style="height:320px;border-radius:12px;margin:0 auto;z-index:0;width:100%;"></div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                if (typeof L === 'undefined') { 
                    console.error('❌ Leaflet library not loaded!'); 
                    return; 
                }
                console.log('✅ Leaflet loaded. Initializing map with coords:', <?php echo floatval($map_lat);?>, <?php echo floatval($map_lng);?>);
                
                try {
                    var m = L.map('eventMap').setView([<?php echo floatval($map_lat);?>, <?php echo floatval($map_lng);?>], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    }).addTo(m);
                    L.marker([<?php echo floatval($map_lat);?>, <?php echo floatval($map_lng);?>])
                        .addTo(m)
                        .bindPopup('<?php echo esc_js($event_local_title); ?>');
                    console.log('✅ Map rendered successfully');
                } catch(e) {
                    console.error('❌ Map render error:', e);
                }
            }, {once:true});
            </script>
            <?php else: ?>
            <div class="map-placeholder" style="height:285px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:12px;margin:0 auto;width:100%;">
                <p style="color:#999;text-align:center;padding:20px;">
                    <i class="ri-map-pin-line" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    Mapa disponível em breve
                </p>
            </div>
            <script>
            console.log('⚠️ Map not displayed - no coordinates found for event <?php echo absint($event_id); ?>');
            </script>
            <?php endif; ?>

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
                <?php if ($event_local_latitude && $event_local_longitude): ?>
                if (origin) {
                    var url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + 
                              '&destination=<?php echo floatval($event_local_latitude); ?>,<?php echo floatval($event_local_longitude); ?>'+
                              '&travelmode=driving';
                    window.open(url, '_blank');
                } else {
                    alert('Digite seu endereço de partida');
                }
                <?php else: ?>
                alert('Coordenadas do local não disponíveis');
                <?php endif; ?>
            });
            </script>
        </section>

        <!-- Tickets Section -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos
            </h2>
            
            <div class="tickets-grid">
                <?php if ($event_tickets_ext): ?>
                <a href="<?php echo esc_url($event_tickets_ext); ?>?ref=apollo.rio.br" class="ticket-card" target="_blank">
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
                <?php if ($event_cupom_ario): ?>
                <div class="apollo-coupon-detail">
                    <i class="ri-coupon-3-line"></i>
                    <span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
                    <button class="copy-code-mini" onclick="copyPromoCode()">
                        <i class="ri-file-copy-fill"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Other Accesses -->
                <a href="" target="_blank">
                    <div class="ticket-card disabled">
                        <div class="ticket-icon"><i class="ri-list-check"></i></div>
                        <div class="ticket-info">
                            <h3 class="ticket-name">Acessos Diversos</h3>
                            <span class="ticket-cta">Seguir para Acessos Diversos →</span>
                        </div>
                    </div>
                </a>
            </div>
        </section>

        <!-- Final Event Image -->
        <?php if ($event_imagem_final): 
            $final_img_url = is_numeric($event_imagem_final) ? wp_get_attachment_url($event_imagem_final) : $event_imagem_final;
        ?>
        <section class="section">
            <div class="secondary-image" style="margin-bottom:3rem;">
                <img src="<?php echo esc_url($final_img_url); ?>" alt="Event Final Photo">
            </div>
        </section>
        <?php endif; ?>
        
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

<?php wp_footer(); ?>
<script src="https://assets.apollo.rio.br/event-page.js"></script>

<!-- ✅ FAVORITES TOGGLE LOGIC -->
<script>
(function() {
    var favBtn = document.getElementById('favoriteTrigger');
    if (!favBtn) return;
    
    favBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var eventId = this.dataset.eventId;
        var icon = this.querySelector('i');
        
        // TODO: Implement AJAX call to save to database
        console.log('Favorite toggle for event:', eventId);
        
        // Placeholder animation
        if (icon.classList.contains('ri-rocket-line')) {
            icon.classList.remove('ri-rocket-line');
            icon.classList.add('ri-rocket-fill');
            console.log('✅ Event favorited (placeholder)');
            // TODO: Increment _favorites_count meta
        } else {
            icon.classList.remove('ri-rocket-fill');
            icon.classList.add('ri-rocket-line');
            console.log('❌ Event unfavorited (placeholder)');
            // TODO: Decrement _favorites_count meta
        }
    });
    
    console.log('✅ Favorites system initialized (placeholder mode)');
})();
</script>
</body>
</html>

