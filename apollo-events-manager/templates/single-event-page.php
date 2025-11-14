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
$timetable = apollo_sanitize_timetable(get_post_meta($event_id, '_event_timetable', true));
if (empty($timetable)) {
    $timetable = apollo_sanitize_timetable(get_post_meta($event_id, '_timetable', true));
}
$final_image = get_post_meta($event_id, '_imagem_final', true);

// Local data with comprehensive validation
$local_id = apollo_get_primary_local_id($event_id);
if (!$local_id) {
    $legacy   = get_post_meta($event_id, '_event_local', true);
    $local_id = $legacy ? (int) $legacy : 0;
}

$local_name = '';
$local_address = '';
$local_images = [];
$local_lat = '';
$local_long = '';

// Only process if we have a valid local ID
if ($local_id) {
    $local_post = get_post($local_id);

    if ($local_post && $local_post->post_status === 'publish') {
        $temp_name = get_post_meta($local_id, '_local_name', true);
        if (!empty($temp_name)) {
            $local_name = $temp_name;
        } else {
            $local_name = $local_post->post_title;
        }

        $local_address_meta = get_post_meta($local_id, '_local_address', true);
        if (!empty($local_address_meta)) {
            $local_address = $local_address_meta;
        } else {
            $local_city = get_post_meta($local_id, '_local_city', true);
            $local_state = get_post_meta($local_id, '_local_state', true);
            if ($local_city || $local_state) {
                $local_address = trim($local_city . ($local_city && $local_state ? ', ' : '') . $local_state);
            }
        }

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

$apollo_modal_context = isset($GLOBALS['apollo_modal_context']) && is_array($GLOBALS['apollo_modal_context'])
    ? $GLOBALS['apollo_modal_context']
    : null;

if ($apollo_modal_context) {
    if (!empty($apollo_modal_context['local_name'])) {
        $local_name = $apollo_modal_context['local_name'];
    }
    if (!empty($apollo_modal_context['local_address'])) {
        $local_address = $apollo_modal_context['local_address'];
    }
    if (!empty($apollo_modal_context['local_images']) && is_array($apollo_modal_context['local_images'])) {
        $local_images = array_filter($apollo_modal_context['local_images']);
    }
    if (isset($apollo_modal_context['local_lat']) && $apollo_modal_context['local_lat'] !== '') {
        $local_lat = $apollo_modal_context['local_lat'];
    }
    if (isset($apollo_modal_context['local_long']) && $apollo_modal_context['local_long'] !== '') {
        $local_long = $apollo_modal_context['local_long'];
    }
}

if ($local_name === '') {
    $fallback_location = get_post_meta($event_id, '_event_location', true);
    if (!empty($fallback_location)) {
        $local_name = $fallback_location;
    }
}

if ($local_address === '') {
    $event_address = get_post_meta($event_id, '_event_address', true);
    if (!empty($event_address)) {
        $local_address = $event_address;
    }
}

$event_permalink = $apollo_modal_context && !empty($apollo_modal_context['event_url'])
    ? $apollo_modal_context['event_url']
    : get_permalink($event_id);
$is_modal_context = !empty($apollo_modal_context) && !empty($apollo_modal_context['is_modal']);

$local_address_display = $local_address !== ''
    ? $local_address
    : __('Endereço não informado. Atualize o Local para exibir os detalhes.', 'apollo-events-manager');

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

// YouTube embed
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

// Count favorites
$favorites_snapshot = function_exists('apollo_get_event_favorites_snapshot')
    ? apollo_get_event_favorites_snapshot($event_id)
    : null;

$favorites_count = 0;
$favorite_avatars = array();
$favorite_remaining = 0;
$user_has_favorited = false;

if (is_array($favorites_snapshot)) {
    $favorites_count = isset($favorites_snapshot['count']) ? (int) $favorites_snapshot['count'] : 0;
    $favorite_avatars = isset($favorites_snapshot['avatars']) && is_array($favorites_snapshot['avatars'])
        ? $favorites_snapshot['avatars']
        : array();
    $favorite_remaining = isset($favorites_snapshot['remaining']) ? (int) $favorites_snapshot['remaining'] : 0;
    $user_has_favorited = !empty($favorites_snapshot['current_user_has_favorited']);
} else {
    $favorites_count_meta = get_post_meta($event_id, '_favorites_count', true);
    $favorites_count = is_numeric($favorites_count_meta) ? max(0, (int) $favorites_count_meta) : 0;

    if (is_user_logged_in()) {
        $user_favorites = get_user_meta(get_current_user_id(), 'apollo_favorites', true);
        if (is_array($user_favorites)) {
            $user_favorites = array_map('intval', $user_favorites);
            $user_has_favorited = in_array((int) $event_id, $user_favorites, true);
        }
    }
}

$lineup_entries = function_exists('apollo_get_event_lineup')
    ? apollo_get_event_lineup($event_id)
    : array();
?>

<div
    class="mobile-container"
    data-apollo-event-url="<?php echo esc_url($event_permalink); ?>"
    <?php if ($is_modal_context): ?> data-apollo-modal="1"<?php endif; ?>
    <?php if ($local_lat !== ''): ?> data-local-lat="<?php echo esc_attr($local_lat); ?>"<?php endif; ?>
    <?php if ($local_long !== ''): ?> data-local-lng="<?php echo esc_attr($local_long); ?>"<?php endif; ?>
    <?php if (!empty($local_name)): ?> data-local-name="<?php echo esc_attr($local_name); ?>"<?php endif; ?>
>
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
            <a
                href="#"
                class="quick-action apollo-favorite-trigger"
                id="favoriteTrigger"
                data-apollo-favorite
                data-event-id="<?php echo esc_attr($event_id); ?>"
                data-favorited="<?php echo $user_has_favorited ? '1' : '0'; ?>"
                data-apollo-favorite-count=".apollo-favorite-count"
                data-apollo-favorite-avatars=".apollo-favorite-avatars-container"
                data-apollo-favorite-icon-active="ri-rocket-fill"
                data-apollo-favorite-icon-inactive="ri-rocket-line"
                aria-pressed="<?php echo $user_has_favorited ? 'true' : 'false'; ?>"
            >
                <div class="quick-action-icon"><i class="<?php echo $user_has_favorited ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i></div>
                <span class="quick-action-label">Interesse</span>
            </a>
        </div>

        <!-- RSVP Row - Only show if favorites_count > 1 -->
        <?php if ($favorites_count > 1): ?>
        <div class="rsvp-row">
            <div class="avatars-explosion apollo-favorite-avatars-container" data-apollo-avatar-container>
                <div class="apollo-favorite-avatars" data-apollo-avatar-list>
                    <?php foreach ($favorite_avatars as $avatar):
                        $avatar_url = isset($avatar['avatar']) ? $avatar['avatar'] : '';
                        $avatar_name = isset($avatar['name']) ? $avatar['name'] : '';
                        $avatar_initials = isset($avatar['initials']) ? $avatar['initials'] : '';
                        $avatar_classes = 'avatar' . (empty($avatar_url) ? ' avatar-initials' : '');
                    ?>
                    <div
                        class="<?php echo esc_attr($avatar_classes); ?>"
                        <?php if (!empty($avatar_url)): ?>style="background-image: url('<?php echo esc_url($avatar_url); ?>')"<?php endif; ?>
                        <?php if (!empty($avatar_name)): ?>title="<?php echo esc_attr($avatar_name); ?>" aria-label="<?php echo esc_attr($avatar_name); ?>"<?php endif; ?>
                    >
                        <?php if (empty($avatar_url) && !empty($avatar_initials)): ?>
                            <?php echo esc_html($avatar_initials); ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="avatar-count" data-apollo-avatar-count<?php echo $favorite_remaining > 0 ? '' : ' style="display:none;"'; ?>>
                    <?php if ($favorite_remaining > 0): ?>
                        +<?php echo esc_html($favorite_remaining); ?>
                    <?php endif; ?>
                </div>
                <p class="interested-text" style="margin: 0 8px 0px 20px;">
                    <i class="ri-bar-chart-2-fill"></i>
                    <span
                        id="result"
                        class="apollo-favorite-count"
                        data-count-prefix=""
                        data-count-suffix="<?php echo esc_attr__(' interessados', 'apollo-events-manager'); ?>"
                        data-count-zero="<?php echo esc_attr__('Seja o primeiro a demonstrar interesse', 'apollo-events-manager'); ?>"
                    ><?php
                        echo esc_html($favorites_count) . ' ' . esc_html__('interessados', 'apollo-events-manager');
                    ?></span>
                </p>
            </div>
        </div>
        <?php endif; ?>

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
        <?php if (!empty($lineup_entries)): ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <div class="lineup-list">
                <?php foreach ($lineup_entries as $entry): ?>
                <div class="lineup-card">
                    <?php if (!empty($entry['photo'])): ?>
                    <img src="<?php echo esc_url($entry['photo']); ?>" alt="<?php echo esc_attr($entry['name']); ?>" class="lineup-avatar-img">
                    <?php else: ?>
                    <div class="lineup-avatar-fallback">
                        <?php echo esc_html(mb_substr($entry['name'], 0, 2)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <?php if (!empty($entry['permalink'])): ?>
                            <a href="<?php echo esc_url($entry['permalink']); ?>" target="_blank" class="dj-link">
                                <?php echo esc_html($entry['name']); ?>
                            </a>
                            <?php else: ?>
                            <?php echo esc_html($entry['name']); ?>
                            <?php endif; ?>
                        </h3>
                        <?php if ($entry['from'] !== '' || $entry['to'] !== ''): ?>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span>
                                <?php
                                $time_parts = array();
                                if ($entry['from'] !== '') {
                                    $time_parts[] = $entry['from'];
                                }
                                if ($entry['to'] !== '') {
                                    $time_parts[] = $entry['to'];
                                }
                                echo esc_html(implode(' - ', $time_parts));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <div class="lineup-placeholder" style="padding:40px 20px;text-align:center;background:var(--bg-surface,#f5f5f5);border-radius:12px;">
                <i class="ri-disc-line" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                <p style="color:var(--text-secondary,#999);margin:0;">Line-up em breve</p>
            </div>
        </section>
        <?php endif; ?>

        <!-- Venue Section -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($local_name); ?>
            </h2>
            <p class="local-endereco"><?php echo esc_html($local_address_display); ?></p>
            
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
        <button class="bottom-btn secondary" id="bottomShareBtn" type="button" data-share-button>
            <i class="ri-share-forward-line"></i>
        </button>
    </div>
</div>

<script src="https://assets.apollo.rio.br/event-page.js"></script>

