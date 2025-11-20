<?php
/**
 * Single Event Page Template
 * Based on Apollo::rio design with uni.css
 * Works as both standalone page and popup modal
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

// Check if this is being loaded in modal context
$is_modal_context = isset($GLOBALS['apollo_modal_context']['is_modal']) && $GLOBALS['apollo_modal_context']['is_modal'];
$event_permalink = isset($GLOBALS['apollo_modal_context']['event_url']) ? $GLOBALS['apollo_modal_context']['event_url'] : get_permalink();

$event_id = get_the_ID();

// Get event data
$event_title = apollo_get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$event_banner = apollo_get_post_meta($event_id, '_event_banner', true);
$video_url = apollo_get_post_meta($event_id, '_event_video_url', true);
$start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
$start_time = apollo_get_post_meta($event_id, '_event_start_time', true);
$end_time = apollo_get_post_meta($event_id, '_event_end_time', true);
$description = apollo_get_post_meta($event_id, '_event_description', true) ?: get_the_content();
$tickets_url = apollo_get_post_meta($event_id, '_tickets_ext', true);
$cupom_ario = apollo_get_post_meta($event_id, '_cupom_ario', true);
$promo_images = apollo_get_post_meta($event_id, '_3_imagens_promo', true);
$timetable = apollo_sanitize_timetable(apollo_get_post_meta($event_id, '_event_timetable', true));
if (empty($timetable)) {
    $timetable = apollo_sanitize_timetable(apollo_get_post_meta($event_id, '_timetable', true));
}
$final_image = apollo_get_post_meta($event_id, '_imagem_final', true);

// Local data with comprehensive validation
// Use unified connection manager (MANDATORY)
$local_id = 0;
if (function_exists('apollo_get_event_local_id')) {
    $local_id = apollo_get_event_local_id($event_id);
} elseif (function_exists('apollo_get_primary_local_id')) {
    $local_id = apollo_get_primary_local_id($event_id);
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
        $temp_name = apollo_get_post_meta($local_id, '_local_name', true);
        if (!empty($temp_name)) {
            $local_name = $temp_name;
        } else {
            $local_name = $local_post->post_title;
        }

        $local_address_meta = apollo_get_post_meta($local_id, '_local_address', true);
        if (!empty($local_address_meta)) {
            $local_address = $local_address_meta;
        } else {
            $local_city = apollo_get_post_meta($local_id, '_local_city', true);
            $local_state = apollo_get_post_meta($local_id, '_local_state', true);
            if ($local_city || $local_state) {
                $local_address = trim($local_city . ($local_city && $local_state ? ', ' : '') . $local_state);
            }
        }

        // Get coordinates - try multiple meta keys
        $local_lat = apollo_get_post_meta($local_id, '_local_latitude', true);
        if (empty($local_lat) || $local_lat === '0' || $local_lat === 0) {
            $local_lat = apollo_get_post_meta($local_id, '_local_lat', true);
        }
        // Sanitize and validate latitude
        $local_lat = is_numeric($local_lat) ? floatval($local_lat) : '';
        if ($local_lat === 0 || $local_lat === '0') {
            $local_lat = '';
        }

        $local_long = apollo_get_post_meta($local_id, '_local_longitude', true);
        if (empty($local_long) || $local_long === '0' || $local_long === 0) {
            $local_long = apollo_get_post_meta($local_id, '_local_lng', true);
        }
        // Sanitize and validate longitude
        $local_long = is_numeric($local_long) ? floatval($local_long) : '';
        if ($local_long === 0 || $local_long === '0') {
            $local_long = '';
        }
        
        // Debug log for admins
        if (current_user_can('administrator')) {
            error_log(sprintf('[Apollo Map] Event %d -> Local %d: lat=%s, lng=%s', $event_id, $local_id, $local_lat, $local_long));
        }

        // Get local images (up to 5)
        for ($i = 1; $i <= 5; $i++) {
            $img = apollo_get_post_meta($local_id, '_local_image_' . $i, true);
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
    $fallback_location = apollo_get_post_meta($event_id, '_event_location', true);
    if (!empty($fallback_location)) {
        $local_name = $fallback_location;
    }
}

if ($local_address === '') {
    $event_address = apollo_get_post_meta($event_id, '_event_address', true);
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
    $local_lat = apollo_get_post_meta($event_id, '_event_latitude', true);
    if (empty($local_lat)) {
        $local_lat = apollo_get_post_meta($event_id, 'geolocation_lat', true);
    }
}

if (empty($local_long)) {
    $local_long = apollo_get_post_meta($event_id, '_event_longitude', true);
    if (empty($local_long)) {
        $local_long = apollo_get_post_meta($event_id, 'geolocation_long', true);
    }
}

// Get sounds/genres - ONLY FOR MARQUEE
$sounds = wp_get_post_terms($event_id, 'event_sounds');
if (is_wp_error($sounds)) $sounds = [];

// Get categories - FOR HERO TAGS
$categories = wp_get_post_terms($event_id, 'event_listing_category');
if (is_wp_error($categories)) $categories = [];

// Get tags (if taxonomy exists) - FOR HERO TAGS
$event_tags = array();
if (taxonomy_exists('event_listing_tag')) {
    $event_tags = wp_get_post_terms($event_id, 'event_listing_tag');
    if (is_wp_error($event_tags)) $event_tags = [];
}

// Get event type - FOR HERO TAGS
$event_type = apollo_get_post_meta($event_id, '_event_type', true);

// HERO TAGS: CATEGORY + TAGS + TYPE (NO SOUNDS!)
// MARQUEE: ONLY SOUNDS

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
    $favorites_count_meta = apollo_get_post_meta($event_id, '_favorites_count', true);
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

// REMOVED: $all_event_tags array
// HERO TAGS: Use $categories, $event_tags, $event_type directly
// MARQUEE: Use $sounds directly
// NO SOUNDS IN HERO TAGS!
?>

<!-- IF STANDALONE PAGE: Full HTML structure -->
<?php if (!$is_modal_context): ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
    <title><?php printf(esc_html__('%s - Apollo::rio', 'apollo-events-manager'), esc_html($event_title)); ?></title>
    <link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
    <link href="https://assets.apollo.rio.br/uni.css" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body>
<?php endif; ?>

<div
    class="mobile-container"
    data-motion-modal="true"
    data-apollo-event-url="<?php echo esc_url($event_permalink); ?>"
    <?php if ($is_modal_context): ?> data-apollo-modal="1"<?php endif; ?>
    <?php if ($local_lat !== ''): ?> data-local-lat="<?php echo esc_attr($local_lat); ?>"<?php endif; ?>
    <?php if ($local_long !== ''): ?> data-local-lng="<?php echo esc_attr($local_long); ?>"<?php endif; ?>
    <?php if (!empty($local_name)): ?> data-local-name="<?php echo esc_attr($local_name); ?>"<?php endif; ?>
    style="max-width: 500px; margin: 0 auto;"
>
    <!-- Hero Media -->
    <div class="hero-media">
        <?php if ($youtube_embed): ?>
        <div class="video-cover">
            <iframe src="<?php echo esc_url($youtube_embed); ?>" allow="autoplay; fullscreen" allowfullscreen frameborder="0" title="<?php echo esc_attr($event_title); ?>"></iframe>
        </div>
        <?php else: ?>
        <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($event_title); ?>" loading="lazy">
        <?php endif; ?>
        
        <div class="hero-overlay"></div>
        
        <!-- Rocket Favorite Button -->
        <?php 
        $is_favorited = false;
        $current_user_id = 0;
        
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
        }
        
        if ($current_user_id > 0) {
            $user_favorites_raw = get_user_meta($current_user_id, 'apollo_favorites', true);
            if (is_array($user_favorites_raw)) {
                $is_favorited = in_array($event_id, $user_favorites_raw);
            }
        }
        ?>
        <button class="event-favorite-rocket" 
                data-apollo-favorite 
                data-event-id="<?php echo esc_attr($event_id); ?>"
                data-favorited="<?php echo $is_favorited ? '1' : '0'; ?>"
                data-apollo-favorite-icon=".rocket-icon"
                data-apollo-favorite-icon-active="ri-rocket-fill"
                data-apollo-favorite-icon-inactive="ri-rocket-line"
                aria-label="<?php esc_attr_e('Marcar como interessado', 'apollo-events-manager'); ?>">
            <i class="rocket-icon <?php echo $is_favorited ? 'ri-rocket-fill' : 'ri-rocket-line'; ?>"></i>
        </button>
        
        <div class="hero-content">
            <section id="listing_types_tags_category"><!-- START TAGS with ICON for speciall example:
<span class="event-tag-pill"><i class="ri-fire-fill"></i> icon to "Novidade"</span>
<span class="event-tag-pill"><i class="ri-award-fill"></i> icon to "Apollo recomenda"</span>
<span class="event-tag-pill"><i class="ri-verified-badge-fill"></i> icon to "Destaque"</span>
<i class="ri-brain-ai-3-fill"></i> for listed category 
 <i class="ri-price-tag-3-line"></i> for listed tag -->
                <?php
                // Category (first one) - icon: ri-brain-ai-3-fill
                if (!empty($categories) && isset($categories[0])): ?>
               <span class="event-tag-pill"><i class="ri-brain-ai-3-fill"></i> <?php echo esc_html($categories[0]->name); ?></span>  
                <?php endif; ?>
                
                <?php
                // Tags (tag0, tag1, tag2, tag3) - icon: ri-price-tag-3-line
                $tag_count = 0;
                if (!empty($event_tags)):
                    foreach ($event_tags as $tag):
                        if ($tag_count >= 4) break;
                ?>
               <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> <?php echo esc_html($tag->name); ?></span>  
                <?php
                        $tag_count++;
                    endforeach;
                endif;
                ?>
                
                <?php
                // Event Type - icon: ri-landscape-ai-fill
                $event_type = apollo_get_post_meta($event_id, '_event_type', true);
                if (!empty($event_type)): ?>
               <span class="event-tag-pill"><i class="ri-landscape-ai-fill"></i> <?php echo esc_html($event_type); ?></span>
                <?php endif; ?>
              </section><!-- END TAGS -->
            
            <h1 class="hero-title"><?php echo esc_html($event_title); ?></h1>
            
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <i class="ri-calendar-line"></i>
                    <span><?php echo esc_html($day . ' ' . $month . " '" . $year); ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-time-line"></i>
                    <span id="Hora"><?php echo esc_html($start_time . ' — ' . $end_time); ?></span>
                    <span style="opacity:.7;font-weight:300; font-size:.81rem;"><?php echo esc_html('(GMT-03h00)'); ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="ri-map-pin-line"></i>
                    <span><?php echo esc_html($local_name); ?></span>
                    <?php 
                    $local_city = '';
                    $local_state = '';
                    if ($local_id) {
                        $local_city = apollo_get_post_meta($local_id, '_local_city', true);
                        $local_state = apollo_get_post_meta($local_id, '_local_state', true);
                    }
                    $local_regiao = $local_city && $local_state ? "({$local_city}, {$local_state})" : 
                                   ($local_city ? "({$local_city})" : ($local_state ? "({$local_state})" : ''));
                    if ($local_regiao): ?>
                    <span style="opacity:0.5"><?php echo esc_html($local_regiao); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Body -->
    <div class="event-body">
        <!-- RSVP Row with Avatar Explosion -->
            <?php if ($favorites_count > 0): ?>
            <div class="rsvp-row">
                <div class="avatars-explosion">
                    <?php 
                    // Show up to 10 avatars
                    $avatar_count = 0;
                    if (!empty($favorite_avatars)) {
                        foreach ($favorite_avatars as $avatar_url) {
                            if ($avatar_count >= 10) break;
                            ?>
                            <div class="avatar" style="background-image: url('<?php echo esc_url($avatar_url); ?>')"></div>
                            <?php
                            $avatar_count++;
                        }
                    }
                    
                    // Show +count if more people
                    if ($favorite_remaining > 0): ?>
                    <div class="avatar-count">+<?php echo esc_html($favorite_remaining); ?></div>
                    <?php endif; ?>
                    
                    <p class="interested-text" style="margin: 0 8px 0px 20px;">
                        <i class="ri-bar-chart-2-fill"></i> 
                        <span id="result"><?php printf(esc_html__('%d interessados', 'apollo-events-manager'), absint($favorites_count)); ?></span>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#route_TICKETS" class="quick-action" aria-label="<?php esc_attr_e('Ver ingressos', 'apollo-events-manager'); ?>">
                <div class="quick-action-icon"><i class="ri-ticket-2-line"></i></div>
                <span class="quick-action-label"><?php esc_html_e('TICKETS', 'apollo-events-manager'); ?></span>
            </a>
            <a href="#route_LINE" class="quick-action" aria-label="<?php esc_attr_e('Ver line-up', 'apollo-events-manager'); ?>">
                <div class="quick-action-icon"><i class="ri-draft-line"></i></div>
                <span class="quick-action-label"><?php esc_html_e('Line-up', 'apollo-events-manager'); ?></span>
            </a>
            <a href="#route_ROUTE" class="quick-action" aria-label="<?php esc_attr_e('Ver rota', 'apollo-events-manager'); ?>">
                <div class="quick-action-icon"><i class="ri-treasure-map-line"></i></div>
                <span class="quick-action-label"><?php esc_html_e('ROUTE', 'apollo-events-manager'); ?></span>
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
                aria-label="<?php esc_attr_e('Marcar interesse', 'apollo-events-manager'); ?>"
            >
                <div class="quick-action-icon"><i class="<?php echo esc_attr($user_has_favorited ? 'ri-rocket-fill' : 'ri-rocket-line'); ?>"></i></div>
                <span class="quick-action-label"><?php esc_html_e('Interesse', 'apollo-events-manager'); ?></span>
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
                <i class="ri-brain-ai-3-fill"></i> <?php esc_html_e('Info', 'apollo-events-manager'); ?>
            </h2>
            <div class="info-card">
                <p class="info-text"><?php echo wp_kses_post(wpautop($description)); ?></p>
            </div>
            
            <!-- Music Tags Marquee (8x repetition for infinite scroll) -->
            <!-- FIXED SPAN CLASS TAGS SOUNDS, TO INFINITE LOOP, IF 1 SOUND, WE REPLICATE IN THIS TILL LAST ONE -->
            <!-- SOUNDS TAGS GOES ONLY ON MARQUEE: $Selected event_sounds -->
            <?php if (!empty($sounds)): ?>
            <div class="music-tags-marquee">
                <div class="music-tags-track">
                    <?php 
                    // Repeat 8 times for infinite scroll
                    // Infinite span mandatory 1-8
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

        <!-- Promo Gallery (max 5 Images) -->
        <?php if ($promo_images && is_array($promo_images)): ?>
        <section class="section">
            <div class="promo-gallery-slider">
                <div class="promo-track" id="promoTrack">
                    <?php 
                    $image_count = 0;
                    foreach ($promo_images as $img_id):
                        if ($image_count >= 5) break;
                        $img_url = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                        if ($img_url):
                            $image_count++;
                    ?>
                    <!-- IMAGE 0<?php echo esc_html($image_count); ?> -->
                    <div class="promo-slide" style="border-radius:12px">
                        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(sprintf(__('Promo image %d', 'apollo-events-manager'), $image_count)); ?>">
                    </div>
                    <?php 
                        endif;
                    endforeach;
                    ?>
                </div>
                <div class="promo-controls">
                    <button class="promo-prev" type="button" aria-label="<?php esc_attr_e('Previous image', 'apollo-events-manager'); ?>"><i class="ri-arrow-left-s-line"></i></button>
                    <button class="promo-next" type="button" aria-label="<?php esc_attr_e('Next image', 'apollo-events-manager'); ?>"><i class="ri-arrow-right-s-line"></i></button>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- DJ Lineup (5 DJs: photo, initials, photo, initials, photo) -->
        <section class="section" id="route_LINE">
            <h2 class="section-title"><i class="ri-disc-line"></i> <?php esc_html_e('Line-up', 'apollo-events-manager'); ?></h2>
            <div class="lineup-list">
                <?php if (!empty($lineup_entries)): ?>
                    <?php foreach ($lineup_entries as $entry): ?>
                    <!-- DJ X - WITH PHOTO -->
                    <div class="lineup-card">
                        <?php if (!empty($entry['photo'])): ?>
                        <img src="<?php echo esc_url($entry['photo']); ?>" alt="[<?php echo esc_attr($entry['name']); ?>]" class="lineup-avatar-img">
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
                                <span><?php echo esc_html($entry['from'] . ($entry['from'] && $entry['to'] ? ' - ' : '') . $entry['to']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- DJ ... till finish list -->
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="lineup-placeholder" style="padding:40px 20px;text-align:center;background:var(--bg-surface,#f5f5f5);border-radius:12px;">
                        <i class="ri-disc-line" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                        <p style="color:var(--text-secondary,#999);margin:0;"><?php esc_html_e('Line-up em breve', 'apollo-events-manager'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Venue Section with 5 Images Infinite Carousel -->
        <section class="section" id="route_ROUTE">
            <h2 class="section-title">
                <i class="ri-map-pin-2-line"></i> <?php echo esc_html($local_name ?: __('Local', 'apollo-events-manager')); ?>
            </h2>
            <p class="local-endereco"><?php echo esc_html($local_address_display); ?></p>
            
            <!-- Images Slider (max 5) -->
            <?php if (!empty($local_images)): ?>
            <div class="local-images-slider" style="min-height:450px;">
                <div class="local-images-track" id="localTrack" style="min-height:500px;">
                    <?php 
                    $img_count = 0;
                    foreach ($local_images as $img):
                        if ($img_count >= 5) break;
                        $img_url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                        if ($img_url):
                            $img_count++;
                            $img_height = ($img_count == 2 || $img_count == 4) ? '400px' : '450px';
                    ?>
                    <!-- SLIDER IMAGE <?php echo esc_html($img_count); ?> of max 5 -->
                    <div class="local-image" style="min-height:<?php echo esc_attr($img_height); ?>;">
                        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(sprintf(__('Local image %d', 'apollo-events-manager'), $img_count)); ?>">
                    </div>
                    <?php 
                        endif;
                    endforeach;
                    ?>
                    <!-- END SLIDER IMAGES -->
                </div>
                <div class="slider-nav" id="localDots"></div>
            </div>
            <?php endif; ?>

            <!-- REPLACE DIV BELOW OR ADAPT IT FOR OPEN STREET MAP BY EVENT LOCAL ADDRESS LAT LONG GENERATED with no controls and no pollution on map -->
            <?php 
            // Validate coordinates more strictly
            $has_valid_coords = false;
            if (!empty($local_lat) && !empty($local_long)) {
                $lat_float = is_numeric($local_lat) ? floatval($local_lat) : 0;
                $lng_float = is_numeric($local_long) ? floatval($local_long) : 0;
                if ($lat_float !== 0.0 && $lng_float !== 0.0 && abs($lat_float) <= 90 && abs($lng_float) <= 180) {
                    $has_valid_coords = true;
                }
            }
            ?>
            <?php if ($has_valid_coords): ?>
            <div class="map-view" id="eventMap" 
                 style="margin:0 auto 0 auto; z-index:0; width:100%; height:285px; border-radius:12px;"
                 data-lat="<?php echo esc_attr($lat_float); ?>"
                 data-lng="<?php echo esc_attr($lng_float); ?>"
                 data-marker="<?php echo esc_attr($local_name); ?>">
            </div>
            
            <script>
            (function() {
                // ✅ CRITICAL: Force map initialization with multiple fallbacks
                var mapInitialized = false;
                
                function initMap() {
                    // Prevent multiple initializations
                    if (mapInitialized) {
                        return;
                    }
                    
                    var mapEl = document.getElementById('eventMap');
                    if (!mapEl) {
                        console.warn('⚠️ Map element not found, retrying...');
                        return;
                    }
                    
                    var lat = parseFloat(mapEl.dataset.lat);
                    var lng = parseFloat(mapEl.dataset.lng);
                    
                    if (!lat || !lng || isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                        console.error('❌ Invalid coordinates:', lat, lng);
                        return;
                    }
                    
                    // Strategy 1: Leaflet already loaded
                    if (typeof L !== 'undefined') {
                        try {
                            console.log('✅ Leaflet available. Initializing map...', lat, lng);
                            
                            // Clean up existing map if any
                            if (mapEl._leaflet_id) {
                                try {
                                    var existingMap = L.map(mapEl);
                                    existingMap.remove();
                                    delete mapEl._leaflet_id;
                                } catch(e) {
                                    console.warn('⚠️ Error removing existing map:', e);
                                }
                            }
                            
                            // Initialize new map
                            var map = L.map('eventMap', {
                                zoomControl: true,
                                scrollWheelZoom: true,
                                doubleClickZoom: true,
                                boxZoom: false,
                                keyboard: true
                            }).setView([lat, lng], 15);
                            
                            // Add tile layer
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap contributors',
                                maxZoom: 19,
                                subdomains: ['a', 'b', 'c']
                            }).addTo(map);
                            
                            // Add marker
                            var marker = L.marker([lat, lng]).addTo(map);
                            marker.bindPopup('<?php echo esc_js($local_name); ?>').openPopup();
                            
                            // CRITICAL: Force resize after a delay (especially for modals)
                            setTimeout(function() {
                                map.invalidateSize();
                                map.setView([lat, lng], 15);
                            }, 300);
                            
                            // Additional resize for modals
                            setTimeout(function() {
                                map.invalidateSize();
                            }, 1000);
                            
                            mapInitialized = true;
                            console.log('✅ Map initialized successfully');
                            
                            // Dispatch custom event
                            mapEl.dispatchEvent(new CustomEvent('apollo:map:initialized', { detail: { map: map } }));
                            
                            return;
                        } catch(e) {
                            console.error('❌ Map initialization error:', e);
                            mapInitialized = false;
                        }
                    }
                    
                    // Strategy 2: Load Leaflet dynamically
                    console.log('⏳ Leaflet not loaded, loading dynamically...');
                    
                    // Load CSS first
                    if (!document.querySelector('link[href*="leaflet.css"]')) {
                        var leafletCSS = document.createElement('link');
                        leafletCSS.rel = 'stylesheet';
                        leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        leafletCSS.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                        leafletCSS.crossOrigin = '';
                        document.head.appendChild(leafletCSS);
                    }
                    
                    // Load JS
                    if (!document.querySelector('script[src*="leaflet.js"]')) {
                        var leafletScript = document.createElement('script');
                        leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        leafletScript.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                        leafletScript.crossOrigin = '';
                        leafletScript.onload = function() {
                            console.log('✅ Leaflet script loaded');
                            // Wait a bit for Leaflet to fully initialize
                            setTimeout(function() {
                                if (typeof L !== 'undefined') {
                                    initMap();
                                } else {
                                    console.error('❌ Leaflet still not available after load');
                                }
                            }, 100);
                        };
                        leafletScript.onerror = function() {
                            console.error('❌ Failed to load Leaflet script');
                        };
                        document.head.appendChild(leafletScript);
                    } else {
                        // Script already loading, wait for it
                        var checkLeaflet = setInterval(function() {
                            if (typeof L !== 'undefined') {
                                clearInterval(checkLeaflet);
                                initMap();
                            }
                        }, 100);
                        
                        // Timeout after 5 seconds
                        setTimeout(function() {
                            clearInterval(checkLeaflet);
                            if (typeof L === 'undefined') {
                                console.error('❌ Leaflet failed to load after timeout');
                            }
                        }, 5000);
                    }
                }
                
                // Multiple initialization strategies
                // Strategy A: DOM ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(initMap, 100);
                    });
                } else {
                    setTimeout(initMap, 100);
                }
                
                // Strategy B: Window load (all assets loaded)
                window.addEventListener('load', function() {
                    setTimeout(initMap, 200);
                });
                
                // Strategy C: Retry after delay (for modals)
                setTimeout(initMap, 500);
                setTimeout(initMap, 1000);
                
                // Strategy D: Modal content loaded event
                document.addEventListener('apollo:modal:content:loaded', function() {
                    setTimeout(initMap, 200);
                });
                
                // Strategy E: Visibility change (for tabs)
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden && !mapInitialized) {
                        setTimeout(initMap, 300);
                    }
                });
            })();
            </script>
            <?php else: ?>
            <!-- Map Placeholder -->
            <div class="map-view" style="margin:0 auto 0 auto; z-index:0; background:#e0e0e0; width:100%; height:285px; border-radius:12px; background-image:url('https://img.freepik.com/premium-vector/city-map-scheme-background-flat-style-vector-illustration_833641-2300.jpg'); background-size: cover; background-repeat: no-repeat; background-position: center center;" aria-label="<?php esc_attr_e('Map placeholder', 'apollo-events-manager'); ?>">
            </div>
            <?php endif; ?>

            <!-- Route Input (Apollo Style - EXACT MATCH) -->
            <?php if ($local_lat && $local_long && $local_lat !== 0 && $local_long !== 0): ?>
            <!-- CHECK IF THERES CHANGE ON JS ON https://assets.apollo.rio.br/event-page.js for route placeholders or meta to match route to events place -->
            <div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">
                <div class="route-input">
                    <i class="ri-map-pin-line"></i>
                    <input type="text" id="origin-input" placeholder="<?php esc_attr_e('Seu endereço de partida', 'apollo-events-manager'); ?>" aria-label="<?php esc_attr_e('Endereço de partida', 'apollo-events-manager'); ?>">
                </div>
                <button id="route-btn" class="route-button" type="button" aria-label="<?php esc_attr_e('Calcular rota', 'apollo-events-manager'); ?>"><i class="ri-send-plane-line"></i></button>
            </div>
            <?php endif; ?>
        </section>

        <!-- Tickets Section - NO PRICES - REDIRECTING TO EXTERNAL TICKET-STORES -->
        <section class="section" id="route_TICKETS">
            <h2 class="section-title">
                <i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> <?php esc_html_e('Acessos', 'apollo-events-manager'); ?>
            </h2>
            
            <!--  Ticket Cards - NO PRICES, only external link direction sender -->
            <div class="tickets-grid">
                <?php if ($tickets_url): ?>
                <a href="<?php echo esc_url($tickets_url); ?>?ref=apollo.rio.br" class="ticket-card" target="_blank" rel="noopener noreferrer">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name"><span id="changingword" style="opacity: 1;"><?php esc_html_e('Ingressos', 'apollo-events-manager'); ?></span></h3>
                        <span class="ticket-cta"><?php esc_html_e('Seguir para Bilheteria Digital', 'apollo-events-manager'); ?> →</span>
                    </div>
                </a>
                <?php else: ?>
                <a href="#" class="ticket-card disabled" aria-disabled="true">
                    <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                    <div class="ticket-info">
                        <h3 class="ticket-name"><span id="changingword" style="opacity: 1;"><?php esc_html_e('Ingressos', 'apollo-events-manager'); ?></span></h3>
                        <span class="ticket-cta"><?php esc_html_e('Em breve', 'apollo-events-manager'); ?></span>
                    </div>
                </a>
                <?php endif; ?>
                <!-- Ticket Card Ends -->
                
                <!-- Apollo Coupon Detail -->
                <?php 
                // Get coupon code - use meta value if exists, otherwise default to "APOLLO"
                $coupon_code = !empty($cupom_ario) && is_string($cupom_ario) ? strtoupper(trim($cupom_ario)) : 'APOLLO';
                ?>
                <div class="apollo-coupon-detail" data-coupon-code="<?php echo esc_attr($coupon_code); ?>">
                    <i class="ri-coupon-3-line"></i>
                    <span><?php printf(esc_html__('Verifique se o cupom %s está ativo com desconto', 'apollo-events-manager'), '<strong>' . esc_html($coupon_code) . '</strong>'); ?></span>
                    <button class="copy-code-mini" type="button" onclick="copyPromoCode(this)" aria-label="<?php printf(esc_attr__('Copiar cupom %s', 'apollo-events-manager'), esc_attr($coupon_code)); ?>">
                        <i class="ri-file-copy-fill"></i>
                    </button>
                </div>
                <!-- Coupon Detail finish-->
                
                <!-- Apollo Other Accesses -->
                <a href="#" class="ticket-card disabled" aria-disabled="true">
                    <div class="ticket-icon">
                        <i class="ri-list-check"></i>
                    </div>
                    <div class="ticket-info">
                        <h3 class="ticket-name"><?php esc_html_e('Acessos Diversos', 'apollo-events-manager'); ?></h3>
                        <span class="ticket-cta"><?php esc_html_e('Seguir para Acessos Diversos', 'apollo-events-manager'); ?> →</span>
                    </div>
                </a>
                <!-- END ALTERNATIVE ACCESSES as List / RSVP -->
            </div>
        </section>

        <!-- Final Event Image -->
        <?php if ($final_image): 
            $final_img_url = is_numeric($final_image) ? wp_get_attachment_url($final_image) : $final_image;
        ?>
        <section class="section">
            <div class="secondary-image" style="margin-bottom:3rem;">
                <img src="<?php echo esc_url($final_img_url); ?>" alt="<?php echo esc_attr($event_title); ?>" loading="lazy">
            </div>
        </section>
        <?php endif; ?>
    </div>
    
    <!-- Protection Notice -->
    <section class="section">
        <div class="respaldo_eve">
            <?php esc_html_e('*A organização e execução deste evento cabem integralmente aos seus idealizadores.', 'apollo-events-manager'); ?>
        </div>
    </section>
    
    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <a href="#route_TICKETS" class="bottom-btn primary 1" id="bottomTicketBtn" aria-label="<?php esc_attr_e('Ver ingressos', 'apollo-events-manager'); ?>">
            <i class="ri-ticket-fill"></i>
            <span id="changingword"><?php esc_html_e('Tickets', 'apollo-events-manager'); ?></span>
        </a>
        
        <?php if ($is_modal_context): ?>
        <a href="<?php echo esc_url(get_permalink($event_id)); ?>" 
           class="bottom-btn secondary 2" 
           target="_blank"
           rel="noopener noreferrer"
           title="<?php esc_attr_e('Abrir como página', 'apollo-events-manager'); ?>"
           aria-label="<?php esc_attr_e('Abrir como página', 'apollo-events-manager'); ?>">
            <i class="ri-external-link-line"></i>
        </a>
        <?php else: ?>
        <button class="bottom-btn secondary 2" id="bottomShareBtn" type="button" aria-label="<?php esc_attr_e('Compartilhar evento', 'apollo-events-manager'); ?>">
            <i class="ri-share-forward-line"></i>
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!$is_modal_context): ?>
<script src="https://assets.apollo.rio.br/event-page.js"></script>
<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>

