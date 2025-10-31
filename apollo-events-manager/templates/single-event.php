<?php
/**
 * Template Apollo: Single Event Page (Lightbox Version)
 * Used in lightbox modal - no HTML document structure
 */

// Get event data
$event_id = get_the_ID();
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

// Local data with comprehensive validation
$event_local_id = get_post_meta($event_id, '_event_local', true);
$event_local_title = get_post_meta($event_id, '_event_location', true);
$event_local_address = '';
$event_local_regiao = '';

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
        $event_local_city = get_post_meta($event_local_id, '_local_city', true);
        $event_local_state = get_post_meta($event_local_id, '_local_state', true);
        $event_local_regiao = $event_local_city && $event_local_state ? "({$event_local_city}, {$event_local_state})" :
                             ($event_local_city ? "({$event_local_city})" : ($event_local_state ? "({$event_local_state})" : ''));
    }
}

// Sounds/genres
$event_sounds = wp_get_post_terms($event_id, 'event_sounds');
if (is_wp_error($event_sounds)) $event_sounds = [];

// Date formatting
$event_day = '';
$event_month = '';
$event_year = '';
if ($event_start_date) {
    $timestamp = strtotime($event_start_date);
    $event_day = date('j', $timestamp);
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

// Banner/Video
$event_banner_url = '';
if ($event_banner) {
    $event_banner_url = is_numeric($event_banner) ? wp_get_attachment_url($event_banner) : $event_banner;
}
if (!$event_banner_url && has_post_thumbnail()) {
    $event_banner_url = get_the_post_thumbnail_url($event_id, 'full');
}
if (!$event_banner_url) {
    $event_banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
}

// YouTube processing
$event_youtube_embed = '';
if ($event_video_url) {
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $event_video_url, $id)) {
        $video_id = $id[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $event_video_url, $id)) {
        $video_id = $id[1];
    }
    if ($video_id) {
        $event_youtube_embed = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&modestbranding=1";
    }
}

// Favorites count
$event_favorites_count = function_exists('favorites_get_count') ? favorites_get_count($event_id) : 40;

// DJs from timetable with comprehensive validation
$event_djs = [];
if (!empty($event_timetable) && is_array($event_timetable)) {
    foreach ($event_timetable as $slot) {
        if (isset($slot['dj']) && !empty($slot['dj'])) {
            $dj_id = $slot['dj'];

            if (is_numeric($dj_id)) {
                $dj_post = get_post($dj_id);
                if ($dj_post && $dj_post->post_status === 'publish') {
                    $dj_name = get_post_meta($dj_id, '_dj_name', true);
                    if (empty($dj_name)) {
                        $dj_name = $dj_post->post_title;
                    }

                    $dj_img = get_post_meta($dj_id, '_dj_image', true);
                    if (empty($dj_img)) {
                        $dj_img = get_the_post_thumbnail_url($dj_id, 'full');
                    }
                } else {
                    continue; // Skip invalid DJ posts
                }
            } else {
                $dj_name = $dj_id;
                $dj_img = '';
            }

            if (!empty($dj_name)) {
                $event_djs[] = [
                    'name' => $dj_name,
                    'image' => $dj_img ?: 'https://via.placeholder.com/100x100',
                    'time_start' => isset($slot['time_start']) ? $slot['time_start'] : '',
                    'time_end' => isset($slot['time_end']) ? $slot['time_end'] : ''
                ];
            }
        }
    }
}
?>

<div class="mobile-container">
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if ($event_youtube_embed): ?>
        <div class="video-cover">
            <iframe src="<?php echo esc_url($event_youtube_embed); ?>" allow="autoplay; fullscreen" allowfullscreen frameborder="0"></iframe>
        </div>
        <?php else: ?>
        <div class="hero-image" style="background-image: url('<?php echo esc_url($event_banner_url); ?>');"></div>
        <?php endif; ?>

        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="event-tag-pill"><i class="ri-megaphone-fill"></i> Novidade</span>
            <h1 class="hero-title"><?php echo esc_html($event_title); ?></h1>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <i class="ri-calendar-line"></i>
                    <span><?php echo $event_day . ' ' . $event_month . ' \'' . $event_year; ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-time-line"></i>
                    <span id="Hora"><?php echo esc_html($event_start_time); ?> — <?php echo esc_html($event_end_time); ?><font style="opacity:.7;font-weight:300; font-size:.81rem; vertical-align: bottom;">(GMT-03h00)</font></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-map-pin-line"></i>
                    <span><?php echo esc_html($event_local_title); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Body -->
    <div class="event-body">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#route_TICKETS" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ri-ticket-2-line"></i>
                </div>
                <span class="quick-action-label">TICKETS</span>
            </a>
            <a href="#route_LINE" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ri-disc-line"></i>
                </div>
                <span class="quick-action-label">Line-up</span>
            </a>
            <a href="#route_ROUTE" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ri-treasure-map-line"></i>
                </div>
                <span class="quick-action-label">ROUTE</span>
            </a>
            <a href="#" class="quick-action" id="favoriteTrigger">
                <div class="quick-action-icon">
                    <i class="ri-rocket-line"></i>
                </div>
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
                <div class="avatar" style="background-image: url('https://media.licdn.com/dms/image/v2/D4DZnPDzn2HwAo-/0/1760115506685?e=2147483647&v=beta&t=c7G7ZKFojPnnYYUu0VB7AkWzf582ydzKs6UyEvc_yXc')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/8.jpg')"></div>
                <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/8.jpg')"></div>
                <div class="avatar-count">+35</div>
                <p class="interested-text" style="margin: 0 8px 0px 20px;">
                    <i class="ri-bar-chart-2-fill"></i> <span id="result"><?php echo $event_favorites_count; ?></span>
                </p>
            </div>
        </div>

        <!-- Info Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="ri-brain-ai-3-fill"></i> Info
            </h2>
            <div class="info-card">
                <p class="info-text"><?php echo wp_kses_post($event_description); ?></p>
            </div>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php
                    $sound_names = array_map(function($sound) { return $sound->name; }, $event_sounds);
                    $sound_names = array_merge($sound_names, $sound_names, $sound_names, $sound_names, $sound_names, $sound_names, $sound_names, $sound_names); // Repeat for infinite scroll
                    foreach ($sound_names as $sound_name) {
                        echo '<span class="music-tag">' . esc_html($sound_name) . '</span>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Promo Gallery -->
        <section class="section">
            <div class="promo-gallery-slider">
                <div class="promo-track" id="promoTrack">
                    <?php
                    $promo_images = is_array($event_3_imagens_promo) ? $event_3_imagens_promo : [];
                    for ($i = 0; $i < 5; $i++) {
                        $img_url = isset($promo_images[$i]) ? $promo_images[$i] : 'https://via.placeholder.com/400x300';
                        echo '<div class="promo-slide" style="border-radius:12px"><img src="' . esc_url($img_url) . '"></div>';
                    }
                    ?>
                </div>
                <div class="promo-controls">
                    <button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
                    <button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
                </div>
            </div>
        </section>

        <!-- DJ Lineup -->
        <section class="section" id="route_LINE">
            <h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
            <div class="lineup-list">
                <?php foreach ($event_djs as $dj): ?>
                <div class="lineup-card">
                    <img src="<?php echo esc_url($dj['image']); ?>" alt="<?php echo esc_attr($dj['name']); ?>" class="lineup-avatar-img">
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <a href="#" target="_blank" class="dj-link"><?php echo esc_html($dj['name']); ?></a>
                        </h3>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span><?php echo esc_html($dj['time_start'] . ' - ' . $dj['time_end']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Venue Section -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($event_local_title); ?>
            </h2>
            <p class="local-endereco"><?php echo esc_html($event_local_address); ?></p>

            <div class="local-images-slider" style="min-height:450px;">
                <div class="local-images-track" id="localTrack" style="min-height:500px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="local-image" style="min-height:450px;">
                        <img src="https://via.placeholder.com/400x300?text=Local+Image+<?php echo $i; ?>">
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="slider-nav" id="localDots"></div>
            </div>

            <div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">
                <div class="route-input">
                    <i class="ri-map-pin-line"></i>
                    <input type="text" id="origin-input" placeholder="Seu endereço de partida">
                </div>
                <button id="route-btn" class="route-button"><i class="ri-send-plane-line"></i></button>
            </div>
        </section>

        <!-- Tickets Section -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos
            </h2>

            <div class="tickets-grid">
                <a href="<?php echo esc_url($event_tickets_ext ?: '#'); ?>" class="ticket-card" target="_blank">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name"><span id="changingword">Biglietti</span></h3>
                        <span class="ticket-cta">Seguir para Bilheteria Digital →</span>
                    </div>
                </a>

                <div class="apollo-coupon-detail">
                    <i class="ri-coupon-3-line"></i>
                    <span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
                    <button class="copy-code-mini" onclick="copyPromoCode()">
                        <i class="ri-file-copy-fill"></i>
                    </button>
                </div>

                <a href="#" target="_blank">
                    <div class="ticket-card disabled">
                        <div class="ticket-icon">
                            <i class="ri-list-check"></i>
                        </div>
                        <div class="ticket-info">
                            <h3 class="ticket-name">Acessos Diversos</h3>
                            <span class="ticket-cta">Seguir para Acessos Diversos →</span>
                        </div>
                    </div>
                </a>
            </div>
        </section>

        <!-- Final Event Image -->
        <section class="section">
            <div class="secondary-image" style="margin-bottom:3rem;">
                <img src="<?php echo esc_url($event_imagem_final ?: 'https://galeria.dismantle.com.br/foto/bonyinc/_MG_1691.jpg'); ?>" alt="Event Final Photo">
            </div>
        </section>

        <!-- Protection -->
        <section class="section">
            <div class="respaldo_eve">
                *A organização e execução deste evento cabem integralmente aos seus idealizadores.
            </div>
        </section>
    </div>

    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <a href="#route_TICKETS" class="bottom-btn primary 1" id="bottomTicketBtn">
            <i class="ri-ticket-fill"></i>
            <span id="changingword">Tickets</span>
        </a>

        <button class="bottom-btn secondary 2" id="bottomShareBtn">
            <i class="ri-share-forward-line"></i>
        </button>
    </div>
</div>

<script>
// Lightbox-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Favorite trigger
    const favTrigger = document.getElementById('favoriteTrigger');
    if (favTrigger) {
        favTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            // Add favorite logic here
            console.log('Favorite clicked');
        });
    }

    // Copy promo code
    window.copyPromoCode = function() {
        navigator.clipboard.writeText('APOLLO').then(function() {
            alert('Código APOLLO copiado!');
        });
    };
});
</script>
    if (!$local_long) $local_long = get_post_meta($local_id, '_local_lng', true);
    
    // Get local images
    for ($i = 1; $i <= 5; $i++) {
        $img = get_post_meta($local_id, '_local_image_' . $i, true);
        if ($img) $local_images[] = $img;
    }
}

// If no local coordinates, try event coordinates (geocoded from event address)
if (!$local_lat || !$local_long) {
    $local_lat = get_post_meta($event_id, '_event_latitude', true);
    $local_long = get_post_meta($event_id, '_event_longitude', true);
    if (!$local_lat) $local_lat = get_post_meta($event_id, 'geolocation_lat', true);
    if (!$local_long) $local_long = get_post_meta($event_id, 'geolocation_long', true);
}

// Get sounds/genres
$sounds = wp_get_post_terms($event_id, 'event_sounds');
$sounds = is_wp_error($sounds) ? [] : $sounds;

// Format dates
$day = $month = $year = '';
if ($start_date) {
    $timestamp = strtotime($start_date);
    $day = date('d', $timestamp);
    $month_num = date('M', $timestamp);
    $year = date('y', $timestamp);
    
    $month_map = array(
        'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 
        'Apr' => 'Abr', 'May' => 'Mai', 'Jun' => 'Jun',
        'Jul' => 'Jul', 'Aug' => 'Ago', 'Sep' => 'Set',
        'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
    );
    $month = $month_map[$month_num] ?? $month_num;
}

// Get banner
$banner_url = '';
if ($event_banner) {
    $banner_url = is_numeric($event_banner) ? wp_get_attachment_url($event_banner) : $event_banner;
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
?>

<div class="mobile-container">
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if ($youtube_embed) : ?>
        <div class="video-cover">
            <iframe
                src="<?php echo esc_url($youtube_embed); ?>"
                allow="autoplay; fullscreen"
                allowfullscreen
                frameborder="0"
            ></iframe>
        </div>
        <?php else : ?>
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
                    <span><?php echo $start_time . ' — ' . $end_time; ?></span>
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
                <div class="quick-action-icon">
                    <i class="ri-ticket-2-line"></i>
                </div>
                <span class="quick-action-label">TICKETS</span>
            </a>
            <a href="#route_LINE" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ri-draft-line"></i>
                </div>
                <span class="quick-action-label">Line-up</span>
            </a>
            <a href="#route_ROUTE" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ri-treasure-map-line"></i>
                </div>
                <span class="quick-action-label">ROUTE</span>
            </a>
            <a href="#" class="quick-action" id="favoriteTrigger">
                <div class="quick-action-icon">
                    <i class="ri-rocket-line"></i>
                </div>
                <span class="quick-action-label">Interesse</span>
            </a>
        </div>

        <!-- RSVP Row -->
        <div class="rsvp-row">
            <div class="avatars-explosion">
                <?php
                // Sample avatars - replace with real user data
                $sample_avatars = array(
                    'https://randomuser.me/api/portraits/men/1.jpg',
                    'https://randomuser.me/api/portraits/women/2.jpg',
                    'https://randomuser.me/api/portraits/men/3.jpg',
                    'https://randomuser.me/api/portraits/women/4.jpg',
                    'https://randomuser.me/api/portraits/men/5.jpg',
                );
                foreach ($sample_avatars as $avatar) :
                ?>
                <div class="avatar" style="background-image: url('<?php echo $avatar; ?>')"></div>
                <?php endforeach; ?>
                <div class="avatar-count">+35</div>
                <p class="interested-text" style="margin: 0 8px 0px 20px;">
                    <i class="ri-bar-chart-2-fill"></i> <span id="result">40 interessados</span>
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
            <?php if (!empty($sounds)) : ?>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php 
                    // Repeat tags 8 times for smooth infinite scroll
                    for ($i = 0; $i < 8; $i++) :
                        foreach ($sounds as $sound) :
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

        <!-- Promo Gallery -->
        <?php if ($promo_images && is_array($promo_images)) : ?>
        <div class="promo-gallery-slider">
            <div class="promo-track" id="promoTrack">
                <?php foreach ($promo_images as $img_id) : 
                    $img_url = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                    if ($img_url) :
                ?>
                <div class="promo-slide" style="border-radius:12px">
                    <img src="<?php echo esc_url($img_url); ?>">
                </div>
                <?php 
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
        <?php if ($timetable && is_array($timetable)) : ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <div class="lineup-list">
                <?php foreach ($timetable as $slot) : 
                    $dj_id = $slot['dj'] ?? null;
                    $time_in = $slot['dj_time_in'] ?? '';
                    $time_out = $slot['dj_time_out'] ?? '';
                    
                    if ($dj_id) :
                        $dj_name = get_post_meta($dj_id, '_dj_name', true);
                        $dj_photo = get_post_meta($dj_id, '_photo', true);
                        $dj_photo_url = is_numeric($dj_photo) ? wp_get_attachment_url($dj_photo) : $dj_photo;
                        $dj_permalink = get_permalink($dj_id);
                ?>
                <div class="lineup-card">
                    <?php if ($dj_photo_url) : ?>
                    <img src="<?php echo esc_url($dj_photo_url); ?>" 
                         alt="<?php echo esc_attr($dj_name); ?>" 
                         class="lineup-avatar-img">
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <a href="<?php echo esc_url($dj_permalink); ?>" 
                               target="_blank" 
                               class="dj-link">
                                <?php echo esc_html($dj_name); ?>
                            </a>
                        </h3>
                        <?php if ($time_in && $time_out) : ?>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span><?php echo $time_in . ' - ' . $time_out; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- local Section -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($local_name); ?>
            </h2>
            <p class="local-endereco"><?php echo esc_html($local_address); ?></p>
            
            <!-- local Images Slider -->
            <?php if (!empty($local_images)) : ?>
            <div class="local-images-slider" style="min-height:450px;">
                <div class="local-images-track" id="localTrack" style="min-height:500px;">
                    <?php foreach ($local_images as $img) : 
                        $img_url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                    ?>
                    <div class="local-image" style="min-height:450px;">
                        <img src="<?php echo esc_url($img_url); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="slider-nav" id="localDots"></div>
            </div>
            <?php endif; ?>

            <!-- Map View -->
            <?php if ($local_lat && $local_long) : ?>
            <div class="map-view" id="eventMap" 
                 style="margin:00px auto 0px auto; z-index:0; width:100%; height:285px; border-radius:12px;"
                 data-lat="<?php echo esc_attr($local_lat); ?>"
                 data-lng="<?php echo esc_attr($local_long); ?>">
            </div>
            
            <script>
            // Initialize Leaflet map
            (function() {
                if (typeof L === 'undefined') {
                    console.error('Leaflet not loaded');
                    return;
                }
                
                var mapEl = document.getElementById('eventMap');
                if (!mapEl) return;
                
                var lat = parseFloat(mapEl.dataset.lat);
                var lng = parseFloat(mapEl.dataset.lng);
                
                if (!lat || !lng) return;
                
                var map = L.map('eventMap').setView([lat, lng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);
                
                var marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup('<?php echo esc_js($local_name); ?>');
                
                // Route button handler
                document.getElementById('route-btn')?.addEventListener('click', function() {
                    var origin = document.getElementById('origin-input').value;
                    if (origin) {
                        var url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + 
                                  '&destination=' + lat + ',' + lng;
                        window.open(url, '_blank');
                    } else {
                        alert('Digite seu endereço de partida');
                    }
                });
            })();
            </script>
            <?php endif; ?>

            <!-- Route Controls -->
            <?php if ($local_lat && $local_long) : ?>
            <div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">
                <div class="route-input">
                    <i class="ri-map-pin-line"></i>
                    <input type="text" id="origin-input" placeholder="Seu endereço de partida">
                </div>
                <button id="route-btn" class="route-button">
                    <i class="ri-send-plane-line"></i>
                </button>
            </div>
            <?php endif; ?>
        </section>

        <!-- Tickets Section -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line"></i> Acessos
            </h2>
            
            <div class="tickets-grid">
                <?php if ($tickets_url) : ?>
                <a href="<?php echo esc_url($tickets_url); ?>?ref=apollo.rio.br" 
                   class="ticket-card" 
                   target="_blank">
                    <div class="ticket-icon">
                        <i class="ri-ticket-line"></i>
                    </div>
                    <div class="ticket-info">
                        <h3 class="ticket-name">
                            <span id="changingword">Biglietti</span>
                        </h3>
                        <span class="ticket-cta">Seguir para Bilheteria Digital →</span>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($cupom_ario) : ?>
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
        <?php if ($final_image) : 
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

