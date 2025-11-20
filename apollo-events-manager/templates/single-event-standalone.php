<?php
/**
 * Template: Single Event Apollo
 * Baseado 100% no CodePen JoGvgaY
 * URL: https://codepen.io/Rafael-Valle-the-looper/pen/JoGvgaY
 * 
 * STRICT MODE: Este template é SEMPRE usado para /evento/{slug}, independente do tema.
 */

defined('ABSPATH') || exit;

get_header(); // Use WordPress header

// TODO 96: ShadCN enhanced standalone
$event_id = get_the_ID();

// === ALL EVENT VARIABLES ===
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
// ✅ CORRECT: Use _event_timetable first, then fallback to _timetable
$event_timetable = apollo_get_post_meta($event_id, '_event_timetable', true);
if (empty($event_timetable)) {
    $event_timetable = apollo_get_post_meta($event_id, '_timetable', true); // Fallback legacy
}
$event_imagem_final = apollo_get_post_meta($event_id, '_imagem_final', true);

// === LOCAL VARIABLES WITH VALIDATION ===
// ✅ CORRECT: Use _event_local_ids first
$event_local_id = apollo_get_post_meta($event_id, '_event_local_ids', true);
// Handle both int and array (for backward compatibility)
if (!empty($event_local_id)) {
    $event_local_id = is_array($event_local_id) ? (int) reset($event_local_id) : (int) $event_local_id;
}
// Fallback legacy
if (empty($event_local_id)) {
    $event_local_id = apollo_get_post_meta($event_id, '_event_local', true);
    $event_local_id = $event_local_id ? (int) $event_local_id : 0;
}

$event_local_title = apollo_get_post_meta($event_id, '_event_location', true);
$event_local_address = '';
$event_local_regiao = '';
$event_local_images = [];
$event_local_latitude = '';
$event_local_longitude = '';

// Only process if we have a valid local ID
if (!empty($event_local_id) && is_numeric($event_local_id)) {
    $local_post = get_post($event_local_id);

    if ($local_post && $local_post->post_status === 'publish') {
        $temp_title = apollo_get_post_meta($event_local_id, '_local_name', true);
        if (!empty($temp_title)) {
            $event_local_title = $temp_title;
        } else {
            $event_local_title = $local_post->post_title;
        }

        $event_local_address = apollo_get_post_meta($event_local_id, '_local_address', true);

        // Get coordinates - try multiple meta keys
        $event_local_latitude = apollo_get_post_meta($event_local_id, '_local_latitude', true);
        if (empty($event_local_latitude)) {
            $event_local_latitude = apollo_get_post_meta($event_local_id, '_local_lat', true);
        }

        $event_local_longitude = apollo_get_post_meta($event_local_id, '_local_longitude', true);
        if (empty($event_local_longitude)) {
            $event_local_longitude = apollo_get_post_meta($event_local_id, '_local_lng', true);
        }

        $event_local_city = apollo_get_post_meta($event_local_id, '_local_city', true);
        $event_local_state = apollo_get_post_meta($event_local_id, '_local_state', true);
        $event_local_regiao = $event_local_city ? $event_local_city : ($event_local_state ?: '');

        // Get local images (up to 5)
        for ($i = 1; $i <= 5; $i++) {
            $img = apollo_get_post_meta($event_local_id, '_local_image_' . $i, true);
            if (!empty($img)) {
                $event_local_images[] = $img;
            }
        }
    }
}

// Fallback to event coordinates
if (!$event_local_latitude) $event_local_latitude = apollo_get_post_meta($event_id, '_event_latitude', true) ?: apollo_get_post_meta($event_id, 'geolocation_lat', true);
if (!$event_local_longitude) $event_local_longitude = apollo_get_post_meta($event_id, '_event_longitude', true) ?: apollo_get_post_meta($event_id, 'geolocation_long', true);

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
// ✅ CORRECT: Banner is URL string, NOT attachment ID
$event_banner_url = '';
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

// === FAVORITES SNAPSHOT ===
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
    $favorites_count_meta = apollo_get_post_meta($event_id, '_favorites_count', true);
    $favorites_count = is_numeric($favorites_count_meta) ? max(0, (int) $favorites_count_meta) : 0;

    // Favorites count já definido via meta key

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

// === EVENT TAGS ===
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
?>

<!-- Apollo Single Event Container (TODO 96: ShadCN-enhanced) -->
<div class="apollo-single mobile-container" data-shadcn-enhanced="true">
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
        
        <!-- Rocket Favorite Button -->
        <button class="event-favorite-rocket" 
                data-apollo-favorite 
                data-event-id="<?php echo esc_attr($event_id); ?>"
                data-favorited="<?php echo is_user_logged_in() && in_array($event_id, (array) get_user_meta(get_current_user_id(), 'apollo_favorites', true)) ? '1' : '0'; ?>"
                data-apollo-favorite-icon=".rocket-icon"
                data-apollo-favorite-icon-active="ri-rocket-fill"
                data-apollo-favorite-icon-inactive="ri-rocket-line"
                aria-label="Marcar como interessado">
            <i class="rocket-icon <?php echo is_user_logged_in() && in_array($event_id, (array) get_user_meta(get_current_user_id(), 'apollo_favorites', true)) ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
        </button>
        
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
            <div class="avatars-explosion apollo-favorite-avatars-container<?php echo empty($favorite_avatars) ? ' is-empty' : ''; ?>" data-apollo-avatar-container>
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

        <!-- Promo Gallery (max 5) - Card Stack Style with Swipe -->
        <?php if ($event_3_imagens_promo && is_array($event_3_imagens_promo)): ?>
        <div class="promo-gallery-card-stack" data-motion-gallery="card-stack" style="position: relative; min-height: 400px; margin: 2rem 0;">
            <?php 
            $img_count = 0;
            foreach ($event_3_imagens_promo as $img_id):
                if ($img_count >= 5) break;
                $img_url = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                if ($img_url):
            ?>
            <div class="gallery-image" 
                 data-index="<?php echo esc_attr($img_count); ?>"
                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.15); cursor: grab; user-select: none;">
                <img src="<?php echo esc_url($img_url); ?>" 
                     alt="Imagem promocional <?php echo esc_attr($img_count + 1); ?>"
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <?php 
                    $img_count++;
                endif;
            endforeach;
            ?>
            
            <!-- Navigation Buttons -->
            <div class="gallery-nav" style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 1rem; z-index: 20;">
                <button class="gallery-prev" style="padding: 0.75rem 1rem; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: transform 0.2s ease;">
                    <i class="ri-arrow-left-s-line" style="font-size: 1.5rem;"></i>
                </button>
                <button class="gallery-next" style="padding: 0.75rem 1rem; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: transform 0.2s ease;">
                    <i class="ri-arrow-right-s-line" style="font-size: 1.5rem;"></i>
                </button>
            </div>
            
            <!-- Counter -->
            <div class="gallery-counter" style="position: absolute; top: 1rem; right: 1rem; padding: 0.5rem 1rem; background: rgba(0,0,0,0.7); color: #fff; border-radius: 20px; font-size: 0.875rem; z-index: 20;">
                <span class="current-index">1</span> / <span class="total-images"><?php echo esc_html($img_count); ?></span>
            </div>
        </div>
        
        <!-- Fallback: Add final image if exists and not in promo -->
        <?php if ($event_imagem_final && !in_array($event_imagem_final, $event_3_imagens_promo)): ?>
        <div class="final-image" style="margin: 2rem 0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.15);" data-reveal="true">
            <?php
            $final_url = is_numeric($event_imagem_final) ? wp_get_attachment_url($event_imagem_final) : $event_imagem_final;
            if ($final_url):
            ?>
            <img src="<?php echo esc_url($final_url); ?>" alt="Imagem final" style="width: 100%; height: auto; display: block;">
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- DJ Lineup -->
        <section class="section" id="route_LINE" data-reveal="true" data-reveal-delay="200">
            <h2 class="section-title">
                <i class="ri-disc-line"></i> Line-up
            </h2>
            <?php if (!empty($lineup_entries)): ?>
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
            <?php else: ?>
            <div class="lineup-placeholder" style="padding:40px 20px;text-align:center;background:var(--bg-surface,#f5f5f5);border-radius:12px;">
                <i class="ri-disc-line" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                <p style="color:var(--text-secondary,#999);margin:0;">Line-up em breve</p>
            </div>
            <?php endif; ?>
        </section>

        <!-- Local Section -->
        <section class="section" id="route_ROUTE" data-reveal="true" data-reveal-delay="300">
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
                    if ($v = apollo_get_post_meta($event_local_id, $k, true)) { 
                        $map_lat = $v; 
                        break; 
                    }
                }
                foreach (['_local_longitude','_local_lng'] as $k) {
                    if ($v = apollo_get_post_meta($event_local_id, $k, true)) { 
                        $map_lng = $v; 
                        break; 
                    }
                }
            }
            
            // Tentativa 2: Campos no evento (fallback)
            if (!$map_lat) {
                foreach (['_event_latitude','geolocation_lat'] as $k) {
                    if ($v = apollo_get_post_meta($event_id, $k, true)) { 
                        $map_lat = $v; 
                        break; 
                    }
                }
            }
            if (!$map_lng) {
                foreach (['_event_longitude','geolocation_long'] as $k) {
                    if ($v = apollo_get_post_meta($event_id, $k, true)) { 
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
            <div id="eventMap" 
                 data-lat="<?php echo esc_attr($map_lat); ?>" 
                 data-lng="<?php echo esc_attr($map_lng); ?>" 
                 data-marker="<?php echo esc_attr($event_local_title); ?>"
                 style="height:320px;border-radius:12px;margin:0 auto;z-index:0;width:100%;"></div>
            <script>
            (function() {
                function initMap() {
                    var mapEl = document.getElementById('eventMap');
                    if (!mapEl) {
                        console.error('❌ Map element not found');
                        return;
                    }
                    
                    var lat = parseFloat(mapEl.dataset.lat);
                    var lng = parseFloat(mapEl.dataset.lng);
                    var markerText = mapEl.dataset.marker || 'Local do Evento';
                    
                    if (!lat || !lng || isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                        console.error('❌ Invalid coordinates:', lat, lng);
                        return;
                    }
                    
                    // ✅ FORCE: Check if Leaflet is loaded
                    if (typeof L === 'undefined') {
                        console.log('⏳ Leaflet not loaded, loading dynamically...');
                        
                        // Load CSS first
                        if (!document.querySelector('link[href*="leaflet.css"]')) {
                            var leafletCSS = document.createElement('link');
                            leafletCSS.rel = 'stylesheet';
                            leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                            document.head.appendChild(leafletCSS);
                        }
                        
                        // Load JS
                        var leafletScript = document.createElement('script');
                        leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        leafletScript.onload = function() {
                            setTimeout(function() {
                                if (typeof L !== 'undefined') {
                                    try {
                                        var m = L.map('eventMap', {
                                            zoomControl: true,
                                            scrollWheelZoom: true
                                        }).setView([lat, lng], 15);
                                        
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                                            maxZoom: 19,
                                            attribution: '© OpenStreetMap'
                                        }).addTo(m);
                                        
                                        L.marker([lat, lng])
                                            .addTo(m)
                                            .bindPopup(markerText);
                                        
                                        setTimeout(function() {
                                            m.invalidateSize();
                                        }, 100);
                                        
                                        console.log('✅ Map rendered successfully (dynamic load)');
                                    } catch(e) {
                                        console.error('❌ Map render error:', e);
                                    }
                                }
                            }, 200);
                        };
                        document.head.appendChild(leafletScript);
                        return;
                    }
                    
                    // Leaflet is loaded, initialize map
                    console.log('✅ Leaflet loaded. Initializing map with coords:', lat, lng);
                    
                    try {
                        // ✅ FORCE: Destroy existing map if it exists
                        if (mapEl._leaflet_id) {
                            try {
                                var existingMap = L.map(mapEl);
                                existingMap.remove();
                            } catch(e) {}
                        }
                        
                        var m = L.map('eventMap', {
                            zoomControl: true,
                            scrollWheelZoom: true
                        }).setView([lat, lng], 15);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                            maxZoom: 19,
                            attribution: '© OpenStreetMap'
                        }).addTo(m);
                        
                        L.marker([lat, lng])
                            .addTo(m)
                            .bindPopup(markerText);
                        
                        // ✅ FORCE: Trigger resize
                        setTimeout(function() {
                            m.invalidateSize();
                        }, 100);
                        
                        console.log('✅ Map rendered successfully');
                    } catch(e) {
                        console.error('❌ Map render error:', e);
                    }
                }
                
                // ✅ FORCE: Multiple initialization strategies
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMap);
                } else {
                    initMap();
                }
                
                // Retry after delay (for modal)
                setTimeout(initMap, 500);
                
                // Listen for modal content loaded
                document.addEventListener('apollo:modal:content:loaded', initMap);
                document.addEventListener('apollo:map:init', initMap);
            })();
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
                <?php 
                // Get coupon code - use meta value if exists, otherwise default to "APOLLO"
                $coupon_code = !empty($event_cupom_ario) && is_string($event_cupom_ario) ? strtoupper(trim($event_cupom_ario)) : 'APOLLO';
                ?>
                <div class="apollo-coupon-detail" data-coupon-code="<?php echo esc_attr($coupon_code); ?>">
                    <i class="ri-coupon-3-line"></i>
                    <span>Verifique se o cupom <strong><?php echo esc_html($coupon_code); ?></strong> está ativo com desconto</span>
                    <button class="copy-code-mini" onclick="copyPromoCode(this)">
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

</div><!-- .apollo-single -->

<?php get_footer(); // Use WordPress footer ?>

