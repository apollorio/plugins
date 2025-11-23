<?php
/**
 * FILE: apollo-events-manager/templates/single-event-standalone.php
 * Single Event Page - CONSOLIDATED (replaces single-event.php, single-event-page.php)
 * - All DJ/Local/Banner logic extracted to Helper
 * - Works as standalone page AND modal content
 * - Better error handling for map initialization
 * - Proper coordinate validation
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

$event_id = get_the_ID();
$is_modal = isset($GLOBALS['apollo_modal_context']) && $GLOBALS['apollo_modal_context']['is_modal'];

// ============================================================
// LOAD ALL EVENT DATA VIA HELPERS
// ============================================================
$event_data = [
    'id'          => $event_id,
    'title'       => apollo_get_post_meta($event_id, '_event_title', true) ?: get_the_title(),
    'description' => apollo_get_post_meta($event_id, '_event_description', true) ?: get_the_content(),
    'banner'      => Apollo_Event_Data_Helper::get_banner_url($event_id),
    'date'        => Apollo_Event_Data_Helper::parse_event_date(apollo_get_post_meta($event_id, '_event_start_date', true)),
    'start_time'  => apollo_get_post_meta($event_id, '_event_start_time', true),
    'end_time'    => apollo_get_post_meta($event_id, '_event_end_time', true),
    'djs'         => Apollo_Event_Data_Helper::get_dj_lineup($event_id),
    'local'       => Apollo_Event_Data_Helper::get_local_data($event_id),
    'coords'      => Apollo_Event_Data_Helper::get_coordinates($event_id, 
        Apollo_Event_Data_Helper::get_local_data($event_id) ? Apollo_Event_Data_Helper::get_local_data($event_id)['id'] : 0),
];

// YouTube processing
$video_id = Apollo_Event_Data_Helper::get_youtube_video_id(
    apollo_get_post_meta($event_id, '_event_video_url', true)
);
$event_data['youtube_embed'] = $video_id ? Apollo_Event_Data_Helper::build_youtube_embed_url($video_id) : '';

// Sounds & tags
$event_data['sounds'] = wp_get_post_terms($event_id, 'event_sounds');
$event_data['sounds'] = !is_wp_error($event_data['sounds']) ? $event_data['sounds'] : [];
$event_data['categories'] = wp_get_post_terms($event_id, 'event_listing_category');
$event_data['categories'] = !is_wp_error($event_data['categories']) ? $event_data['categories'] : [];

// Images & lineup
$event_data['promo_images'] = (array) apollo_get_post_meta($event_id, '_3_imagens_promo', true);
$event_data['final_image'] = apollo_get_post_meta($event_id, '_imagem_final', true);
$event_data['tickets_url'] = apollo_get_post_meta($event_id, '_tickets_ext', true);
$event_data['coupon'] = apollo_get_post_meta($event_id, '_cupom_ario', true) ?: 'APOLLO';
$event_data['lineup'] = function_exists('apollo_get_event_lineup') 
    ? apollo_get_event_lineup($event_id) 
    : [];

// Local images
if ($event_data['local']) {
    for ($i = 1; $i <= 5; $i++) {
        $img = apollo_get_post_meta($event_data['local']['id'], '_local_image_' . $i, true);
        if ($img) $event_data['local']['images'][] = $img;
    }
}

// Favorites
$fav_snapshot = function_exists('apollo_get_event_favorites_snapshot')
    ? apollo_get_event_favorites_snapshot($event_id)
    : null;

if (is_array($fav_snapshot)) {
    $event_data['favorites'] = [
        'count'     => (int) ($fav_snapshot['count'] ?? 0),
        'avatars'   => $fav_snapshot['avatars'] ?? [],
        'remaining' => (int) ($fav_snapshot['remaining'] ?? 0),
        'user_has'  => !empty($fav_snapshot['current_user_has_favorited'])
    ];
} else {
    $event_data['favorites'] = [
        'count'     => max(0, (int) apollo_get_post_meta($event_id, '_favorites_count', true)),
        'avatars'   => [],
        'remaining' => 0,
        'user_has'  => is_user_logged_in() ? in_array($event_id, (array) get_user_meta(get_current_user_id(), 'apollo_favorites', true)) : false
    ];
}

// HTML structure only if NOT modal
if (!$is_modal): ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5,user-scalable=yes">
    <title><?php printf(esc_html__('%s - Apollo::rio', 'apollo-events-manager'), esc_html($event_data['title'])); ?></title>
    <link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
    <link href="https://assets.apollo.rio.br/uni.css" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body>
<?php endif; ?>

<div class="mobile-container" style="max-width:500px;margin:0 auto;">
    
    <!-- ============================================================ -->
    <!-- HERO SECTION                                               -->
    <!-- ============================================================ -->
    <div class="hero-media">
        <?php if (!empty($event_data['youtube_embed'])): ?>
        <div class="video-cover">
            <iframe src="<?php echo esc_url($event_data['youtube_embed']); ?>" 
                    allow="autoplay;fullscreen" allowfullscreen frameborder="0"
                    title="<?php echo esc_attr($event_data['title']); ?>"></iframe>
        </div>
        <?php else: ?>
        <img src="<?php echo esc_url($event_data['banner']); ?>" 
             alt="<?php echo esc_attr($event_data['title']); ?>" loading="lazy">
        <?php endif; ?>
        
        <div class="hero-overlay"></div>
        
        <!-- FAVORITE ROCKET BUTTON -->
        <button class="event-favorite-rocket" 
                data-apollo-favorite 
                data-event-id="<?php echo esc_attr($event_id); ?>"
                data-favorited="<?php echo $event_data['favorites']['user_has'] ? '1' : '0'; ?>"
                aria-label="<?php esc_attr_e('Marcar como interessado', 'apollo-events-manager'); ?>">
            <i class="<?php echo $event_data['favorites']['user_has'] ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
        </button>
        
        <!-- HERO CONTENT -->
        <div class="hero-content">
            <div id="listing_types_tags_category">
                <?php
                if (!empty($event_data['categories'])) {
                    echo sprintf('<span class="event-tag-pill"><i class="ri-brain-ai-3-fill"></i> %s</span>',
                        esc_html($event_data['categories'][0]->name));
                }
                ?>
            </div>
            
            <h1 class="hero-title"><?php echo esc_html($event_data['title']); ?></h1>
            
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <i class="ri-calendar-line"></i>
                    <span><?php printf('%s %s \'%s', 
                        esc_html($event_data['date']['day']),
                        esc_html($event_data['date']['month_pt']),
                        esc_html(date('y', $event_data['date']['timestamp']))); ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-time-line"></i>
                    <span><?php echo esc_html($event_data['start_time'] . ' – ' . $event_data['end_time']); ?></span>
                    <span style="opacity:.7;font-size:.81rem;">(GMT-03h00)</span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-map-pin-line"></i>
                    <span><?php echo esc_html($event_data['local']['name'] ?? 'Local'); ?></span>
                    <?php if ($event_data['local'] && $event_data['local']['region']): ?>
                    <span style="opacity:.5">(<?php echo esc_html($event_data['local']['region']); ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- EVENT BODY                                                  -->
    <!-- ============================================================ -->
    <div class="event-body">
        
        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <a href="#route_TICKETS" class="quick-action">
                <div class="quick-action-icon"><i class="ri-ticket-2-line"></i></div>
                <span class="quick-action-label"><?php esc_html_e('TICKETS', 'apollo-events-manager'); ?></span>
            </a>
            <a href="#route_LINE" class="quick-action">
                <div class="quick-action-icon"><i class="ri-draft-line"></i></div>
                <span class="quick-action-label"><?php esc_html_e('Line-up', 'apollo-events-manager'); ?></span>
            </a>
            <a href="#route_ROUTE" class="quick-action">
                <div class="quick-action-icon"><i class="ri-treasure-map-line"></i></div>
                <span class="quick-action-label"><?php esc_html_e('ROUTE', 'apollo-events-manager'); ?></span>
            </a>
            <a href="#" class="quick-action apollo-favorite-trigger"
               data-apollo-favorite data-event-id="<?php echo esc_attr($event_id); ?>"
               data-favorited="<?php echo $event_data['favorites']['user_has'] ? '1' : '0'; ?>">
                <div class="quick-action-icon">
                    <i class="<?php echo $event_data['favorites']['user_has'] ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
                </div>
                <span class="quick-action-label"><?php esc_html_e('Interesse', 'apollo-events-manager'); ?></span>
            </a>
        </div>

        <!-- FAVORITES ROW -->
        <?php if ($event_data['favorites']['count'] > 0): ?>
        <div class="rsvp-row">
            <div class="avatars-explosion">
                <?php foreach (array_slice($event_data['favorites']['avatars'], 0, 10) as $avatar): ?>
                <div class="avatar" style="background-image: url('<?php echo esc_url($avatar); ?>')"></div>
                <?php endforeach; ?>
                
                <?php if ($event_data['favorites']['remaining'] > 0): ?>
                <div class="avatar-count">+<?php echo esc_html($event_data['favorites']['remaining']); ?></div>
                <?php endif; ?>
                
                <p class="interested-text" style="margin:0 8px 0 20px;">
                    <i class="ri-bar-chart-2-fill"></i>
                    <span><?php printf(esc_html__('%d interessados', 'apollo-events-manager'), 
                        $event_data['favorites']['count']); ?></span>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- INFO SECTION -->
        <section class="section">
            <h2 class="section-title">
                <i class="ri-brain-ai-3-fill"></i> <?php esc_html_e('Info', 'apollo-events-manager'); ?>
            </h2>
            <div class="info-card">
                <p class="info-text"><?php echo wp_kses_post(wpautop($event_data['description'])); ?></p>
            </div>
            
            <!-- SOUNDS MARQUEE (8x repeat) -->
            <?php if (!empty($event_data['sounds'])): ?>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php for ($i = 0; $i < 8; $i++): ?>
                        <?php foreach ($event_data['sounds'] as $sound): ?>
                        <span class="music-tag"><?php echo esc_html($sound->name); ?></span>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- PROMO GALLERY (max 5 images) -->
        <?php if (!empty($event_data['promo_images'])): ?>
        <section class="section">
            <div class="promo-gallery-slider">
                <div class="promo-track" id="promoTrack">
                    <?php foreach (array_slice($event_data['promo_images'], 0, 5) as $img): 
                        $url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                        if ($url):
                    ?>
                    <div class="promo-slide" style="border-radius:12px">
                        <img src="<?php echo esc_url($url); ?>" loading="lazy">
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <div class="promo-controls">
                    <button class="promo-prev" type="button"><i class="ri-arrow-left-s-line"></i></button>
                    <button class="promo-next" type="button"><i class="ri-arrow-right-s-line"></i></button>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- LINE-UP SECTION -->
        <section class="section" id="route_LINE">
            <h2 class="section-title"><i class="ri-disc-line"></i> <?php esc_html_e('Line-up', 'apollo-events-manager'); ?></h2>
            <?php if (!empty($event_data['lineup'])): ?>
            <div class="lineup-list">
                <?php foreach ($event_data['lineup'] as $dj): ?>
                <div class="lineup-card">
                    <?php if (!empty($dj['photo'])): ?>
                    <img src="<?php echo esc_url($dj['photo']); ?>" alt="<?php echo esc_attr($dj['name']); ?>">
                    <?php else: ?>
                    <div class="lineup-avatar-fallback">
                        <?php echo esc_html(mb_substr($dj['name'], 0, 2)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="lineup-info">
                        <h3 class="lineup-name">
                            <?php if (!empty($dj['permalink'])): ?>
                            <a href="<?php echo esc_url($dj['permalink']); ?>" target="_blank">
                                <?php echo esc_html($dj['name']); ?>
                            </a>
                            <?php else: ?>
                            <?php echo esc_html($dj['name']); ?>
                            <?php endif; ?>
                        </h3>
                        <?php if ($dj['from'] || $dj['to']): ?>
                        <div class="lineup-time">
                            <i class="ri-time-line"></i>
                            <span><?php echo esc_html(trim($dj['from'] . ($dj['from'] && $dj['to'] ? ' - ' : '') . $dj['to'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="padding:40px 20px;text-align:center;background:#f5f5f5;border-radius:12px;">
                <i class="ri-disc-line" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                <p style="color:#999;margin:0;"><?php esc_html_e('Line-up em breve', 'apollo-events-manager'); ?></p>
            </div>
            <?php endif; ?>
        </section>

        <!-- LOCAL/MAP SECTION -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($event_data['local']['name'] ?? 'Local'); ?>
            </h2>
            <p class="local-endereco">
                <?php echo esc_html($event_data['local']['address'] ?? __('Endereço não informado', 'apollo-events-manager')); ?>
            </p>
            
            <!-- LOCAL IMAGES -->
            <?php if ($event_data['local'] && !empty($event_data['local']['images'])): ?>
            <div class="local-images-slider">
                <div class="local-images-track" id="localTrack">
                    <?php foreach (array_slice($event_data['local']['images'], 0, 5) as $img): 
                        $url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                        if ($url):
                    ?>
                    <div class="local-image" style="min-height:450px;">
                        <img src="<?php echo esc_url($url); ?>" loading="lazy">
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <div class="slider-nav" id="localDots"></div>
            </div>
            <?php endif; ?>

            <!-- MAP -->
            <?php if ($event_data['coords']['valid']): ?>
            <div id="eventMap" 
                 data-lat="<?php echo esc_attr($event_data['coords']['lat']); ?>"
                 data-lng="<?php echo esc_attr($event_data['coords']['lng']); ?>"
                 data-marker="<?php echo esc_attr($event_data['local']['name'] ?? 'Event'); ?>"
                 style="height:320px;border-radius:12px;margin:1rem auto;width:100%;"></div>
            
            <script>
            (function() {
                function initMap() {
                    var mapEl = document.getElementById('eventMap');
                    if (!mapEl || typeof L === 'undefined') return;
                    
                    var lat = parseFloat(mapEl.dataset.lat);
                    var lng = parseFloat(mapEl.dataset.lng);
                    if (!lat || !lng) return;
                    
                    try {
                        if (mapEl._leaflet_id) {
                            var m = L.map(mapEl);
                            m.remove();
                        }
                        
                        var m = L.map('eventMap', {zoomControl: true, scrollWheelZoom: true})
                            .setView([lat, lng], 15);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 
                            {maxZoom: 19, attribution: '© OpenStreetMap'}).addTo(m);
                        
                        L.marker([lat, lng]).addTo(m).bindPopup(mapEl.dataset.marker);
                        
                        setTimeout(function() { m.invalidateSize(); }, 100);
                    } catch(e) {
                        console.error('Map error:', e);
                    }
                }
                
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMap);
                } else {
                    initMap();
                }
            })();
            </script>
            <?php else: ?>
            <div style="height:285px;background:#e0e0e0;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <p style="color:#999;text-align:center;">
                    <i class="ri-map-pin-line" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    <?php esc_html_e('Mapa disponível em breve', 'apollo-events-manager'); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- ROUTE INPUT -->
            <?php if ($event_data['coords']['valid']): ?>
            <div class="route-controls" style="transform:translateY(-80px);padding:0 0.5rem;">
                <div class="route-input">
                    <i class="ri-map-pin-line"></i>
                    <input type="text" id="origin-input" 
                           placeholder="<?php esc_attr_e('Seu endereço de partida', 'apollo-events-manager'); ?>">
                </div>
                <button id="route-btn" class="route-button" type="button">
                    <i class="ri-send-plane-line"></i>
                </button>
            </div>
            
            <script>
            document.getElementById('route-btn')?.addEventListener('click', function() {
                var origin = document.getElementById('origin-input').value;
                if (origin) {
                    var url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + 
                              '&destination=<?php echo esc_attr($event_data['coords']['lat']); ?>,<?php echo esc_attr($event_data['coords']['lng']); ?>';
                    window.open(url, '_blank');
                }
            });
            </script>
            <?php endif; ?>
        </section>

        <!-- TICKETS SECTION -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line"></i> <?php esc_html_e('Acessos', 'apollo-events-manager'); ?>
            </h2>
            
            <div class="tickets-grid">
                <?php if ($event_data['tickets_url']): ?>
                <a href="<?php echo esc_url($event_data['tickets_url']); ?>?ref=apollo.rio.br" 
                   class="ticket-card apollo-click-out-track" 
                   data-event-id="<?php echo esc_attr($event_id); ?>"
                   target="_blank" rel="noopener noreferrer">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name"><?php esc_html_e('Ingressos', 'apollo-events-manager'); ?></h3>
                        <span class="ticket-cta"><?php esc_html_e('Seguir para Bilheteria Digital', 'apollo-events-manager'); ?> →</span>
                    </div>
                </a>
                <?php else: ?>
                <div class="ticket-card disabled">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name"><?php esc_html_e('Ingressos', 'apollo-events-manager'); ?></h3>
                        <span class="ticket-cta"><?php esc_html_e('Em breve', 'apollo-events-manager'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="apollo-coupon-detail" data-coupon-code="<?php echo esc_attr($event_data['coupon']); ?>">
                    <i class="ri-coupon-3-line"></i>
                    <span><?php printf(esc_html__('Cupom %s pode ter desconto', 'apollo-events-manager'),
                        '<strong>' . esc_html($event_data['coupon']) . '</strong>'); ?></span>
                    <button class="copy-code-mini" type="button" onclick="copyPromoCode(this)">
                        <i class="ri-file-copy-fill"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- FINAL IMAGE -->
        <?php if ($event_data['final_image']): 
            $url = is_numeric($event_data['final_image']) ? wp_get_attachment_url($event_data['final_image']) : $event_data['final_image'];
            if ($url):
        ?>
        <section class="section">
            <div style="margin-bottom:3rem;border-radius:12px;overflow:hidden;">
                <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($event_data['title']); ?>" loading="lazy">
            </div>
        </section>
        <?php endif; endif; ?>

        <!-- PROTECTION NOTICE -->
        <section class="section">
            <div class="respaldo_eve">
                <?php esc_html_e('*A organização e execução deste evento cabem integralmente aos seus idealizadores.', 'apollo-events-manager'); ?>
            </div>
        </section>
    </div>

    <!-- BOTTOM BAR -->
    <div class="bottom-bar">
        <a href="#route_TICKETS" class="bottom-btn primary">
            <i class="ri-ticket-fill"></i>
            <span><?php esc_html_e('Tickets', 'apollo-events-manager'); ?></span>
        </a>
        <button class="bottom-btn secondary" type="button" id="bottomShareBtn">
            <i class="ri-share-forward-line"></i>
        </button>
    </div>
</div>

<?php if (!$is_modal): ?>
<script src="https://assets.apollo.rio.br/event-page.js"></script>
<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>