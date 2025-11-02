<?php
/**
 * Single Event Page Template
 * Based 100% on CodePen EaPpjXP
 * URL: https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP
 */

$event_id = get_the_ID();

// Get event data
$event_title = get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$event_banner = get_post_meta($event_id, '_event_banner', true);
$video_url = get_post_meta($event_id, '_event_video_url', true);
$start_date = get_post_meta($event_id, '_event_start_date', true);
$start_time = get_post_meta($event_id, '_event_start_time', true);
$end_time = get_post_meta($event_id, '_event_end_time', true);
$description = get_post_meta($event_id, '_event_description', true) ?: get_the_content();
$tickets_url = get_post_meta($event_id, '_tickets_ext', true);
$cupom_ario = get_post_meta($event_id, '_cupom_ario', true);
$promo_images = get_post_meta($event_id, '_3_imagens_promo', true);
$timetable = get_post_meta($event_id, '_timetable', true);
$final_image = get_post_meta($event_id, '_imagem_final', true);

// Local data with comprehensive validation
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
        $temp_name = get_post_meta($local_id, '_local_name', true);
        if (!empty($temp_name)) {
            $local_name = $temp_name;
        } else {
            $local_name = $local_post->post_title;
        }

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

// Fallback to event coordinates
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

// Get sounds/genres
$sounds = wp_get_post_terms($event_id, 'event_sounds');
if (is_wp_error($sounds)) $sounds = [];

// Format date
$day = $month = $year = '';
if ($start_date) {
    $timestamp = strtotime($start_date);
    $day = date('d', $timestamp);
    $month_num = date('M', $timestamp);
    $year = date('y', $timestamp);
    
    $month_map = [
        'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 
        'Apr' => 'Abr', 'May' => 'Mai', 'Jun' => 'Jun',
        'Jul' => 'Jul', 'Aug' => 'Ago', 'Sep' => 'Set',
        'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
    ];
    $month = $month_map[$month_num] ?? $month_num;
}

// Get banner
$banner_url = '';
if ($event_banner) {
    $banner_url = is_numeric($event_banner) ? wp_get_attachment_url($event_banner) : $event_banner;
}
if (!$banner_url && has_post_thumbnail()) {
    $banner_url = get_the_post_thumbnail_url($event_id, 'full');
}
if (!$banner_url) {
    $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
}

// Process YouTube URL
$youtube_embed = '';
if ($video_url) {
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $video_url, $id)) {
        $video_id = $id[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $video_url, $id)) {
        $video_id = $id[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $video_url, $id)) {
        $video_id = $id[1];
    }
    if ($video_id) {
        $youtube_embed = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&modestbranding=1";
    }
}

// Count favorites (sample - replace with real user data)
$favorites_count = 40;
?>

<div class="mobile-container">
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if ($youtube_embed): ?>
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
                <div class="hero-meta-item">
                    <i class="ri-calendar-line"></i>
                    <span><?php echo $day . ' ' . $month . " '" . $year; ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-time-line"></i>
                    <span id="Hora"><?php echo $start_time . ' — ' . $end_time; ?></span>
                    <font style="opacity:.7;font-weight:300; font-size:.81rem;">(GMT-03h00)</font>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-map-pin-line"></i>
                    <span><?php echo esc_html($local_name); ?></span>
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
                <div class="avatar" style="background-image: url('https://media.licdn.com/dms/image/v2/D4D03AQGzWYcqE-3_-g/profile-displayphoto-scale_400_400/B4DZnPDzn2HwAo-/0/1760115506685?e=2147483647&v=beta&t=c7G7ZKFojPnnYYUu0VB7AkWzf582ydzKs6UyEvc_yXc')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/8.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/8.jpg')"></div>
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
            
            <!-- Music Tags Marquee (8x repetition for infinite scroll) -->
            <?php if (!empty($sounds)): ?>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php 
                    for ($i = 0; $i < 8; $i++):
                        foreach ($sounds as $sound):
                    ?>
                    <span class="music-tag"><?php echo esc_html($sound->name); ?></span>
                    <?php 
                        endforeach;
                    endfor;
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Promo Gallery (max 5 images) -->
        <?php if ($promo_images && is_array($promo_images)): ?>
        <div class="promo-gallery-slider">
            <div class="promo-track" id="promoTrack">
                <?php 
                $image_count = 0;
                foreach ($promo_images as $img_id):
                    if ($image_count >= 5) break;
                    $img_url = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                    if ($img_url):
                ?>
                <div class="promo-slide" style="border-radius:12px">
                    <img src="<?php echo esc_url($img_url); ?>">
                </div>
                <?php 
                        $image_count++;
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
        if (empty($dj_lineup) && !empty($timetable) && is_array($timetable)) {
            $dj_lineup = $timetable;
        }
        ?>
        
        <?php if (!empty($dj_lineup)): ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <div class="lineup-list">
                <?php foreach ($dj_lineup as $slot):
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
                    <?php if ($dj_photo_url): ?>
                    <img src="<?php echo esc_url($dj_photo_url); ?>" alt="<?php echo esc_attr($dj_name); ?>" class="lineup-avatar-img">
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <a href="<?php echo esc_url($dj_permalink); ?>" target="_blank" class="dj-link">
                                <?php echo esc_html($dj_name); ?>
                            </a>
                        </h3>
                        <?php if ($time_in && $time_out): ?>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span><?php echo $time_in . ' - ' . $time_out; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                endforeach;
                ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Venue Section -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($local_name); ?>
            </h2>
            <p class="local-endereco"><?php echo esc_html($local_address); ?></p>
            
            <!-- Local Images Slider (max 5) -->
            <?php if (!empty($local_images)): ?>
            <div class="local-images-slider" style="min-height:450px;">
                <div class="local-images-track" id="localTrack" style="min-height:500px;">
                    <?php 
                    $img_count = 0;
                    foreach ($local_images as $img):
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

            <!-- Map View -->
            <?php if ($local_lat && $local_long && $local_lat !== 0 && $local_long !== 0): ?>
            <div class="map-view" id="eventMap" 
                 style="margin:0 auto; z-index:0; width:100%; height:285px; border-radius:12px;"
                 data-lat="<?php echo esc_attr($local_lat); ?>"
                 data-lng="<?php echo esc_attr($local_long); ?>">
            </div>
            
            <script>
            (function() {
                if (typeof L !== 'undefined') {
                    console.log('✅ Leaflet loaded. Coords:', <?php echo $local_lat; ?>, <?php echo $local_long; ?>);
                    var mapEl = document.getElementById('eventMap');
                    if (mapEl) {
                        var lat = parseFloat(mapEl.dataset.lat);
                        var lng = parseFloat(mapEl.dataset.lng);
                        if (lat && lng) {
                            var map = L.map('eventMap').setView([lat, lng], 15);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap',
                                maxZoom: 19
                            }).addTo(map);
                            L.marker([lat, lng]).addTo(map).bindPopup('<?php echo esc_js($local_name); ?>');
                        }
                    }
                } else {
                    console.error('❌ Leaflet library not loaded!');
                }
            })();
            </script>
            <?php else: ?>
            <!-- Map Placeholder -->
            <div class="map-placeholder" style="height:285px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:12px;margin:0 auto;width:100%;">
                <p style="color:#999;text-align:center;">
                    <i class="ri-map-pin-line" style="font-size:2rem;"></i><br>
                    Mapa disponível em breve
                </p>
            </div>
            <script>
            console.log('⚠️ Map not displayed - no coordinates found for event <?php echo $event_id; ?>');
            </script>
            <?php endif; ?>

            <!-- Route Controls -->
            <?php if ($local_lat && $local_long && $local_lat !== 0 && $local_long !== 0): ?>
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
                              '&destination=<?php echo $local_lat; ?>,<?php echo $local_long; ?>&travelmode=driving';
                    window.open(url, '_blank');
                } else {
                    alert('Digite seu endereço de partida');
                }
            });
            </script>
            <?php endif; ?>
        </section>

        <!-- Tickets Section -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos
            </h2>
            
            <div class="tickets-grid">
                <?php if ($tickets_url): ?>
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
                <?php if ($cupom_ario): ?>
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
        <?php if ($final_image): 
            $final_img_url = is_numeric($final_image) ? wp_get_attachment_url($final_image) : $final_image;
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

<script src="https://assets.apollo.rio.br/event-page.js"></script>

