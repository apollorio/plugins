<?php 

/**
 * Template Apollo: Single Event Page (Lightbox Version)
 * Used in lightbox modal - no HTML document structure
 */

// Get event data
$event_id = get_the_ID();
$event_title = apollo_get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$event_banner = apollo_get_post_meta($event_id, '_event_banner', true);
$event_video_url = apollo_get_post_meta($event_id, '_event_video_url', true);
$event_start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
$event_end_date = apollo_get_post_meta($event_id, '_event_end_date', true);
$event_start_time = apollo_get_post_meta($event_id, '_event_start_time', true);
$event_end_time = apollo_get_post_meta($event_id, '_event_end_time', true);
$event_description = apollo_get_post_meta($event_id, '_event_description', true) ?: get_the_content();
$event_tickets_ext = apollo_get_post_meta($event_id, '_tickets_ext', true);
$event_cupom_ario = apollo_get_post_meta($event_id, '_cupom_ario', true);
$event_3_imagens_promo = apollo_get_post_meta($event_id, '_3_imagens_promo', true);
// ✅ CORRECT: Use _event_timetable with maybe_unserialize()
$event_timetable_raw = apollo_get_post_meta($event_id, '_event_timetable', true);
$event_timetable = !empty($event_timetable_raw) ? maybe_unserialize($event_timetable_raw) : array();
// Fallback to legacy _timetable if empty
if (empty($event_timetable) || !is_array($event_timetable)) {
    $legacy_timetable = apollo_get_post_meta($event_id, '_timetable', true);
    $event_timetable = !empty($legacy_timetable) ? maybe_unserialize($legacy_timetable) : array();
}
$event_imagem_final = apollo_get_post_meta($event_id, '_imagem_final', true);

// ✅ CORRECT: Get local ID from _event_local_ids (int)
$event_local_id = 0;
// Verificar se função existe antes de usar
if (function_exists('apollo_get_primary_local_id')) {
    $event_local_id = apollo_get_primary_local_id($event_id);
}

if (!$event_local_id) {
    // Fallback: usar meta key direto
    $local_ids_meta = apollo_get_post_meta($event_id, '_event_local_ids', true);
    if (!empty($local_ids_meta)) {
        $event_local_id = is_array($local_ids_meta) ? (int) reset($local_ids_meta) : (int) $local_ids_meta;
    }
    
    // Fallback legacy
    if (!$event_local_id) {
        $legacy = apollo_get_post_meta($event_id, '_event_local', true);
        $event_local_id = $legacy ? (int) $legacy : 0;
    }
}

// Local coordinates with comprehensive fallback
$local_lat = '';
$local_lng = '';

// Try local coordinates (multiple meta key variations)
if (!empty($event_local_id) && is_numeric($event_local_id)) {
    $local_lat = apollo_get_post_meta($event_local_id, '_local_latitude', true);
    if (empty($local_lat)) $local_lat = apollo_get_post_meta($event_local_id, '_local_lat', true);

    $local_lng = apollo_get_post_meta($event_local_id, '_local_longitude', true);
    if (empty($local_lng)) $local_lng = apollo_get_post_meta($event_local_id, '_local_lng', true);
}

// Fallback to event coordinates
if (empty($local_lat)) {
    $local_lat = apollo_get_post_meta($event_id, '_event_latitude', true);
    if (empty($local_lat)) $local_lat = apollo_get_post_meta($event_id, 'geolocation_lat', true);
}

if (empty($local_lng)) {
    $local_lng = apollo_get_post_meta($event_id, '_event_longitude', true);
    if (empty($local_lng)) $local_lng = apollo_get_post_meta($event_id, 'geolocation_long', true);
}

// Validate coordinates are numeric
$local_lat = is_numeric($local_lat) ? floatval($local_lat) : 0;
$local_lng = is_numeric($local_lng) ? floatval($local_lng) : 0;

// Sounds/genres
$event_sounds = wp_get_post_terms($event_id, 'event_sounds');
if (is_wp_error($event_sounds)) $event_sounds = [];

// Get categories
$event_categories = wp_get_post_terms($event_id, 'event_listing_category');
if (is_wp_error($event_categories)) $event_categories = [];

// Get tags (if taxonomy exists)
$event_tags = array();
if (taxonomy_exists('event_listing_tag')) {
    $event_tags = wp_get_post_terms($event_id, 'event_listing_tag');
    if (is_wp_error($event_tags)) $event_tags = [];
}

// Combine all tags: categories first, then sounds, then tags
$all_event_tags = array();
if (!empty($event_categories)) {
    $all_event_tags = array_merge($all_event_tags, $event_categories);
}
if (!empty($event_sounds)) {
    $all_event_tags = array_merge($all_event_tags, $event_sounds);
}
if (!empty($event_tags)) {
    $all_event_tags = array_merge($all_event_tags, $event_tags);
}

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

// Banner/Video - Fallback sequence: event_banner → featured_image → gradient
// ✅ CORRECT: Banner is URL string, NOT attachment ID
$event_banner_url = '';
$use_gradient_fallback = false;

if ($event_banner) {
    // Try as URL first (correct format)
    if (filter_var($event_banner, FILTER_VALIDATE_URL)) {
        $event_banner_url = $event_banner;
    } elseif (is_numeric($event_banner)) {
        // Fallback: if numeric, treat as attachment ID
        $event_banner_url = wp_get_attachment_url($event_banner);
    } else {
        // Try as string URL even if filter_var fails
        $event_banner_url = is_string($event_banner) ? $event_banner : '';
    }
}
if (!$event_banner_url && has_post_thumbnail()) {
    $event_banner_url = get_the_post_thumbnail_url($event_id, 'full');
}
if (!$event_banner_url) {
    // Final fallback: use gradient
    $use_gradient_fallback = true;
}

// YouTube processing - comprehensive URL pattern matching
$event_youtube_embed = '';
if ($event_video_url) {
    $video_id = '';

    // Try all YouTube URL patterns
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $event_video_url, $matches)) {
        $video_id = $matches[1];
    }

    if (!empty($video_id)) {
        $event_youtube_embed = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&modestbranding=1";
    }
}

// Favorites snapshot (real user data)
$favorites_snapshot = function_exists('apollo_get_event_favorites_snapshot')
    ? apollo_get_event_favorites_snapshot($event_id)
    : null;

$favorites_count = 0;
$favorite_avatars = [];
$favorite_remaining = 0;
$user_has_favorited = false;

if (is_array($favorites_snapshot)) {
    $favorites_count = isset($favorites_snapshot['count']) ? (int) $favorites_snapshot['count'] : 0;
    $favorite_avatars = isset($favorites_snapshot['avatars']) && is_array($favorites_snapshot['avatars'])
        ? $favorites_snapshot['avatars']
        : [];
    $favorite_remaining = isset($favorites_snapshot['remaining']) ? (int) $favorites_snapshot['remaining'] : 0;
    $user_has_favorited = !empty($favorites_snapshot['current_user_has_favorited']);
} else {
    $favorites_meta = apollo_get_post_meta($event_id, '_favorites_count', true);
    $favorites_count = is_numeric($favorites_meta) ? max(0, (int) $favorites_meta) : 0;

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
    : [];

// Garantir que Tailwind/ShadCN está carregado
if (function_exists('apollo_shadcn_init')) {
    apollo_shadcn_init();
} elseif (class_exists('Apollo_ShadCN_Loader')) {
    Apollo_ShadCN_Loader::get_instance();
}
?>

<div class="mobile-container">
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if ($event_youtube_embed): ?>
        <!-- YouTube Video -->
        <div class="video-cover">
            <iframe src="<?php echo esc_url($event_youtube_embed); ?>" allow="autoplay; fullscreen" allowfullscreen frameborder="0"></iframe>
        </div>
        <?php elseif ($use_gradient_fallback): ?>
        <!-- Gradient Fallback -->
        <div class="hero-image" style="background: linear-gradient(25deg, #eee, #fff);"></div>
        <?php else: ?>
        <!-- Event Banner or Featured Image -->
        <div class="hero-image" style="background-image: url('<?php echo esc_url($event_banner_url); ?>');"></div>
        <?php endif; ?>

        <div class="hero-overlay"></div>
        <div class="hero-content">
            <?php if (!empty($all_event_tags)): ?>
                <?php foreach (array_slice($all_event_tags, 0, 4) as $tag): ?>
                    <?php if (is_object($tag) && isset($tag->name)): ?>
                    <span class="event-tag-pill">
                        <i class="ri-megaphone-fill"></i> <?php echo esc_html($tag->name); ?>
                    </span>
                    <?php endif; ?>
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
                <div class="quick-action-icon">
                    <i class="<?php echo $user_has_favorited ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
                </div>
                <span class="quick-action-label">Interesse</span>
            </a>
        </div>

        <!-- RSVP Row -->
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
                        if ($favorites_count === 0) {
                            echo esc_html__('Seja o primeiro a demonstrar interesse', 'apollo-events-manager');
                        } else {
                            echo esc_html($favorites_count) . ' ' . esc_html__('interessados', 'apollo-events-manager');
                        }
                    ?></span>
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
        <?php if (!empty($lineup_entries)): ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
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
                                $time_parts = [];
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
            <h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
            <div class="lineup-placeholder" style="padding:40px 20px;text-align:center;background:var(--bg-surface,#f5f5f5);border-radius:12px;">
                <i class="ri-disc-line" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                <p style="color:var(--text-secondary,#999);margin:0;">Line-up em breve</p>
            </div>
        </section>
        <?php endif; ?>

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

                <?php 
                // Get coupon code - use meta value if exists, otherwise default to "APOLLO"
                $coupon_code_single = !empty($event_cupom_ario) && is_string($event_cupom_ario) ? strtoupper(trim($event_cupom_ario)) : 'APOLLO';
                ?>
                <div class="apollo-coupon-detail" data-coupon-code="<?php echo esc_attr($coupon_code_single); ?>">
                    <i class="ri-coupon-3-line"></i>
                    <span>Verifique se o cupom <strong><?php echo esc_html($coupon_code_single); ?></strong> está ativo com desconto</span>
                    <button class="copy-code-mini" onclick="copyPromoCode(this)">
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

    // Copy promo code function is defined in apollo-events-portal.js
    // No need to redefine here
});
</script>
// Get local images
$local_images = [];
if (!empty($event_local_id) && is_numeric($event_local_id)) {
    for ($i = 1; $i <= 5; $i++) {
        $img = apollo_get_post_meta($event_local_id, '_local_image_' . $i, true);
        if ($img) $local_images[] = $img;
    }
}

// Get sounds/genres
$sounds = wp_get_post_terms($event_id, 'event_sounds');
$sounds = is_wp_error($sounds) ? [] : $sounds;

// Get categories
$event_categories_2 = wp_get_post_terms($event_id, 'event_listing_category');
if (is_wp_error($event_categories_2)) $event_categories_2 = [];

// Get tags (if taxonomy exists)
$event_tags_2 = array();
if (taxonomy_exists('event_listing_tag')) {
    $event_tags_2 = wp_get_post_terms($event_id, 'event_listing_tag');
    if (is_wp_error($event_tags_2)) $event_tags_2 = [];
}

// Combine all tags: categories first, then sounds, then tags
$all_event_tags_2 = array();
if (!empty($event_categories_2)) {
    $all_event_tags_2 = array_merge($all_event_tags_2, $event_categories_2);
}
if (!empty($sounds)) {
    $all_event_tags_2 = array_merge($all_event_tags_2, $sounds);
}
if (!empty($event_tags_2)) {
    $all_event_tags_2 = array_merge($all_event_tags_2, $event_tags_2);
}

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
// ✅ CORRECT: Banner is URL string, NOT attachment ID
$banner_url = '';
if ($event_banner) {
    // Try as URL first (correct format)
    if (filter_var($event_banner, FILTER_VALIDATE_URL)) {
        $banner_url = $event_banner;
    } elseif (is_numeric($event_banner)) {
        // Fallback: if numeric, treat as attachment ID
        $banner_url = wp_get_attachment_url($event_banner);
    } else {
        // Try as string URL even if filter_var fails
        $banner_url = is_string($event_banner) ? $event_banner : '';
    }
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
            <?php if (!empty($all_event_tags_2)): ?>
                <?php foreach (array_slice($all_event_tags_2, 0, 4) as $tag): ?>
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
            <a
                href="#"
                class="quick-action apollo-favorite-trigger"
                id="favoriteTriggerSecondary"
                data-apollo-favorite
                data-event-id="<?php echo esc_attr($event_id); ?>"
                data-favorited="<?php echo $user_has_favorited ? '1' : '0'; ?>"
                data-apollo-favorite-count=".apollo-favorite-count"
                data-apollo-favorite-avatars=".apollo-favorite-avatars-container"
                data-apollo-favorite-icon-active="ri-rocket-fill"
                data-apollo-favorite-icon-inactive="ri-rocket-line"
                aria-pressed="<?php echo $user_has_favorited ? 'true' : 'false'; ?>"
            >
                <div class="quick-action-icon">
                    <i class="<?php echo $user_has_favorited ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
                </div>
                <span class="quick-action-label">Interesse</span>
            </a>
        </div>

        <!-- RSVP Row -->
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
                        class="apollo-favorite-count"
                        data-count-prefix=""
                        data-count-suffix="<?php echo esc_attr__(' interessados', 'apollo-events-manager'); ?>"
                        data-count-zero="<?php echo esc_attr__('Seja o primeiro a demonstrar interesse', 'apollo-events-manager'); ?>"
                    ><?php
                        if ($favorites_count === 0) {
                            echo esc_html__('Seja o primeiro a demonstrar interesse', 'apollo-events-manager');
                        } else {
                            echo esc_html($favorites_count) . ' ' . esc_html__('interessados', 'apollo-events-manager');
                        }
                    ?></span>
                </p>
            </div>
        </div>

        <!-- Info Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="ri-brain-ai-3-fill"></i> Info
            </h2>
            <div class="info-card">
                <p class="info-text"><?php echo wp_kses_post(wpautop($description)); ?></p>
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
        <?php if (!empty($lineup_entries)): ?>
        <section class="section" id="route_LINE">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <div class="lineup-list">
                <?php foreach ($lineup_entries as $entry): ?>
                <div class="lineup-card">
                    <?php if (!empty($entry['photo'])): ?>
                    <img src="<?php echo esc_url($entry['photo']); ?>" 
                         alt="<?php echo esc_attr($entry['name']); ?>" 
                         class="lineup-avatar-img">
                    <?php else: ?>
                    <div class="lineup-avatar-fallback">
                        <?php echo esc_html(mb_substr($entry['name'], 0, 2)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <?php if (!empty($entry['permalink'])): ?>
                            <a href="<?php echo esc_url($entry['permalink']); ?>" 
                               target="_blank" 
                               class="dj-link">
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
                                $time_parts = [];
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
            <?php if ($local_lat && $local_lng && $local_lat !== 0 && $local_lng !== 0) : ?>
            <div class="map-view" id="eventMap" 
                 style="margin:00px auto 0px auto; z-index:0; width:100%; height:285px; border-radius:12px;"
                 data-lat="<?php echo esc_attr($local_lat); ?>"
                 data-lng="<?php echo esc_attr($local_lng); ?>">
            </div>
            
            <script>
            // Initialize Leaflet map
            (function() {
                if (typeof L === 'undefined') {
                    console.error('❌ Leaflet library not loaded!');
                    return;
                }
                
                console.log('✅ Leaflet loaded. Coords:', <?php echo esc_js($local_lat); ?>, <?php echo esc_js($local_lng); ?>);
                
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
                marker.bindPopup('<?php echo esc_js($event_local_title); ?>');
                
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
            <?php else: ?>
            <!-- Map Placeholder -->
            <div class="map-placeholder" style="height:285px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:12px;">
                <p style="color:#999;text-align:center;">
                    <i class="ri-map-pin-line" style="font-size:2rem;"></i><br>
                    Mapa disponível em breve
                </p>
            </div>
            <?php endif; ?>

            <!-- Route Controls -->
            <?php if ($local_lat && $local_lng && $local_lat !== 0 && $local_lng !== 0) : ?>
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
                
                <?php if ($event_cupom_ario) : ?>
                <?php 
                // Get coupon code - use meta value if exists, otherwise default to "APOLLO"
                $coupon_code_single = !empty($event_cupom_ario) && is_string($event_cupom_ario) ? strtoupper(trim($event_cupom_ario)) : 'APOLLO';
                ?>
                <div class="apollo-coupon-detail" data-coupon-code="<?php echo esc_attr($coupon_code_single); ?>">
                    <i class="ri-coupon-3-line"></i>
                    <span>Verifique se o cupom <strong><?php echo esc_html($coupon_code_single); ?></strong> está ativo com desconto</span>
                    <button class="copy-code-mini" onclick="copyPromoCode(this)">
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

