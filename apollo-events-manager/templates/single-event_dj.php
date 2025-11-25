<?php
/**
 * Template: Single DJ Page (Strict Mode - 100% Design Conformance)
 * 
 * Design Reference: PAGE-FOR-CPT DJ stylesheet by unicss imported by script
 * Structure: dj-shell > dj-page > dj-content > dj-header > dj-hero > dj-info-grid > dj-footer
 * 
 * FORCED TOOLTIPS: All placeholders show data-tooltip when empty
 * CSS: uni.css (critical first), RemixIcon
 * 
 * @package Apollo_Events_Manager
 * @version 3.0.0 - Strict Mode Refactor
 */

defined('ABSPATH') || exit;

$dj_id = get_the_ID();
if (get_post_type($dj_id) !== 'event_dj') {
    wp_die(__('Este template é apenas para DJs.', 'apollo-events-manager'));
}

// ============================================
// DJ DATA EXTRACTION - All Meta Keys
// ============================================

// Nome Principal (display name)
$dj_name = apollo_get_post_meta($dj_id, '_dj_name', true);
if (empty($dj_name)) {
    $dj_name = get_the_title();
}

// Nome secundário (real name ou alias)
$dj_name_sub = apollo_get_post_meta($dj_id, '_dj_name_sub', true);

// Tagline
$dj_tagline = apollo_get_post_meta($dj_id, '_dj_tagline', true);

// Roles (comma-separated: DJ, Producer, Label Owner)
$dj_roles = apollo_get_post_meta($dj_id, '_dj_roles', true);
$dj_roles_array = !empty($dj_roles) ? array_map('trim', explode(',', $dj_roles)) : array();

// Bio
$dj_bio = apollo_get_post_meta($dj_id, '_dj_bio', true);
if (empty($dj_bio)) {
    $dj_bio = get_the_content();
}
$dj_bio_excerpt = apollo_get_post_meta($dj_id, '_dj_bio_excerpt', true);
if (empty($dj_bio_excerpt) && !empty($dj_bio)) {
    $dj_bio_excerpt = wp_trim_words(strip_tags($dj_bio), 30, '...');
}

// Avatar/Image
$dj_image = apollo_get_post_meta($dj_id, '_dj_image', true);
if (empty($dj_image) && has_post_thumbnail($dj_id)) {
    $dj_image = get_the_post_thumbnail_url($dj_id, 'large');
}
if (!empty($dj_image) && is_numeric($dj_image)) {
    $dj_image = wp_get_attachment_image_url($dj_image, 'large');
}
// Fallback placeholder
if (empty($dj_image)) {
    $dj_image = 'https://assets.apollo.rio.br/placeholder-dj.jpg';
}

// Banner (background)
$dj_banner = apollo_get_post_meta($dj_id, '_dj_banner', true);
if (!empty($dj_banner) && is_numeric($dj_banner)) {
    $dj_banner = wp_get_attachment_image_url($dj_banner, 'full');
}

// Track info for vinyl player
$dj_track_title = apollo_get_post_meta($dj_id, '_dj_track_title', true);
if (empty($dj_track_title)) {
    $dj_track_title = $dj_name; // fallback to DJ name
}

// Redes & Plataformas (Music Links)
$dj_soundcloud = apollo_get_post_meta($dj_id, '_dj_soundcloud', true);
$dj_spotify = apollo_get_post_meta($dj_id, '_dj_spotify', true);
$dj_youtube = apollo_get_post_meta($dj_id, '_dj_youtube', true);
$dj_mixcloud = apollo_get_post_meta($dj_id, '_dj_mixcloud', true);
$dj_beatport = apollo_get_post_meta($dj_id, '_dj_beatport', true);
$dj_bandcamp = apollo_get_post_meta($dj_id, '_dj_bandcamp', true);
$dj_resident_advisor = apollo_get_post_meta($dj_id, '_dj_resident_advisor', true);
$dj_apple_music = apollo_get_post_meta($dj_id, '_dj_apple_music', true);
$dj_tidal = apollo_get_post_meta($dj_id, '_dj_tidal', true);
$dj_deezer = apollo_get_post_meta($dj_id, '_dj_deezer', true);

// Social Links
$dj_instagram = apollo_get_post_meta($dj_id, '_dj_instagram', true);
$dj_twitter = apollo_get_post_meta($dj_id, '_dj_twitter', true);
$dj_tiktok = apollo_get_post_meta($dj_id, '_dj_tiktok', true);
$dj_facebook = apollo_get_post_meta($dj_id, '_dj_facebook', true);
$dj_website = apollo_get_post_meta($dj_id, '_dj_website', true);

// Asset Links (downloads)
$dj_media_kit_url = apollo_get_post_meta($dj_id, '_dj_media_kit_url', true);
$dj_rider_url = apollo_get_post_meta($dj_id, '_dj_rider_url', true);
$dj_press_photos_url = apollo_get_post_meta($dj_id, '_dj_press_photos_url', true);
$dj_epk_url = apollo_get_post_meta($dj_id, '_dj_epk_url', true);

// Projetos Originais
$dj_project_1 = apollo_get_post_meta($dj_id, '_dj_original_project_1', true);
$dj_project_2 = apollo_get_post_meta($dj_id, '_dj_original_project_2', true);
$dj_project_3 = apollo_get_post_meta($dj_id, '_dj_original_project_3', true);
$projects = array_filter(array($dj_project_1, $dj_project_2, $dj_project_3));

// SoundCloud Track URL for player
$dj_soundcloud_track = apollo_get_post_meta($dj_id, '_dj_soundcloud_track', true);
if (empty($dj_soundcloud_track)) {
    $dj_soundcloud_track = $dj_soundcloud; // fallback to profile URL
}

// More Platforms text
$dj_more_platforms = apollo_get_post_meta($dj_id, '_dj_more_platforms', true);

// Normalize social handles
function apollo_normalize_social_url($url, $platform) {
    if (empty($url)) return '';
    
    $platforms = array(
        'instagram' => 'https://instagram.com/',
        'twitter' => 'https://twitter.com/',
        'tiktok' => 'https://tiktok.com/@',
        'facebook' => 'https://facebook.com/',
    );
    
    // If it's already a full URL, return as-is
    if (strpos($url, 'http') === 0) {
        return $url;
    }
    
    // Remove @ if present
    $handle = ltrim($url, '@');
    
    return isset($platforms[$platform]) ? $platforms[$platform] . $handle : $url;
}

$instagram_url = apollo_normalize_social_url($dj_instagram, 'instagram');
$twitter_url = apollo_normalize_social_url($dj_twitter, 'twitter');
$tiktok_url = apollo_normalize_social_url($dj_tiktok, 'tiktok');
$facebook_url = apollo_normalize_social_url($dj_facebook, 'facebook');

// Build link arrays for templates
$music_links = array();
if ($dj_soundcloud) $music_links[] = array('url' => $dj_soundcloud, 'icon' => 'ri-soundcloud-fill', 'label' => 'SoundCloud', 'active' => true);
if ($dj_spotify) $music_links[] = array('url' => $dj_spotify, 'icon' => 'ri-spotify-fill', 'label' => 'Spotify', 'active' => false);
if ($dj_youtube) $music_links[] = array('url' => $dj_youtube, 'icon' => 'ri-youtube-fill', 'label' => 'YouTube', 'active' => false);
if ($dj_apple_music) $music_links[] = array('url' => $dj_apple_music, 'icon' => 'ri-apple-fill', 'label' => 'Apple Music', 'active' => false);
if ($dj_mixcloud) $music_links[] = array('url' => $dj_mixcloud, 'icon' => 'ri-disc-fill', 'label' => 'Mixcloud', 'active' => false);
if ($dj_beatport) $music_links[] = array('url' => $dj_beatport, 'icon' => 'ri-music-2-fill', 'label' => 'Beatport', 'active' => false);
if ($dj_bandcamp) $music_links[] = array('url' => $dj_bandcamp, 'icon' => 'ri-headphone-fill', 'label' => 'Bandcamp', 'active' => false);
if ($dj_resident_advisor) $music_links[] = array('url' => $dj_resident_advisor, 'icon' => 'ri-calendar-event-fill', 'label' => 'RA', 'active' => false);

$social_links = array();
if ($instagram_url) $social_links[] = array('url' => $instagram_url, 'icon' => 'ri-instagram-fill', 'label' => 'Instagram');
if ($twitter_url) $social_links[] = array('url' => $twitter_url, 'icon' => 'ri-twitter-x-fill', 'label' => 'X/Twitter');
if ($tiktok_url) $social_links[] = array('url' => $tiktok_url, 'icon' => 'ri-tiktok-fill', 'label' => 'TikTok');
if ($facebook_url) $social_links[] = array('url' => $facebook_url, 'icon' => 'ri-facebook-fill', 'label' => 'Facebook');
if ($dj_website) $social_links[] = array('url' => $dj_website, 'icon' => 'ri-global-fill', 'label' => 'Website');

$asset_links = array();
if ($dj_media_kit_url) $asset_links[] = array('url' => $dj_media_kit_url, 'icon' => 'ri-file-download-fill', 'label' => 'Media Kit');
if ($dj_rider_url) $asset_links[] = array('url' => $dj_rider_url, 'icon' => 'ri-file-list-3-fill', 'label' => 'Rider');
if ($dj_press_photos_url) $asset_links[] = array('url' => $dj_press_photos_url, 'icon' => 'ri-image-fill', 'label' => 'Press Photos');
if ($dj_epk_url) $asset_links[] = array('url' => $dj_epk_url, 'icon' => 'ri-folder-zip-fill', 'label' => 'EPK');

// ============================================
// ANALYTICS - Track view
// ============================================
$user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
$device_type = wp_is_mobile() ? 'mobile' : 'desktop';

$country = 'BR'; // Default
if (function_exists('geoip_detect2_get_info_from_ip')) {
    $geoip = geoip_detect2_get_info_from_ip($user_ip);
    $country = $geoip->country->isoCode ?? 'BR';
}

// Store view count
$analytics_key = '_dj_view_' . date('Y-m-d');
$views_today = (int) apollo_get_post_meta($dj_id, $analytics_key, true);
apollo_update_post_meta($dj_id, $analytics_key, $views_today + 1);

// Prepare DJ_DATA for JavaScript
$dj_data_js = array(
    'name' => $dj_name,
    'nameSub' => $dj_name_sub ?: '',
    'tagline' => $dj_tagline ?: 'Artist & DJ',
    'roles' => $dj_roles_array,
    'avatar' => $dj_image,
    'mediakitUrl' => $dj_media_kit_url ?: '#',
    'projects' => $projects,
    'bioExcerpt' => $dj_bio_excerpt ?: '',
    'bioFull' => $dj_bio ?: '',
    'musicLinks' => $music_links,
    'socialLinks' => $social_links,
    'assetLinks' => $asset_links,
    'morePlatforms' => $dj_more_platforms ?: '',
    'soundcloudTrack' => $dj_soundcloud_track ?: '',
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($dj_name); ?> | Apollo</title>
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo esc_attr($dj_name); ?> | Apollo">
    <meta property="og:description" content="<?php echo esc_attr($dj_bio_excerpt); ?>">
    <meta property="og:image" content="<?php echo esc_url($dj_image); ?>">
    <meta property="og:type" content="profile">
    
    <!-- 1. UNI.CSS FIRST (CRITICAL - defines root variables) -->
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
    
    <!-- 2. RemixIcon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">
    
    <!-- SoundCloud Widget API -->
    <script src="https://w.soundcloud.com/player/api.js"></script>
    
    <?php wp_head(); ?>
</head>
<body <?php body_class('apollo-dj-page'); ?>>

<!-- ============================================ -->
<!-- DJ SHELL - Main Container (Design Spec)     -->
<!-- ============================================ -->
<section class="dj-shell">
    <div id="djPage" class="dj-page">
        <div class="dj-content">
            
            <!-- ========================== -->
            <!-- DJ HEADER with Navigation  -->
            <!-- ========================== -->
            <header class="dj-header">
                <a href="<?php echo esc_url(home_url('/dj/')); ?>" class="dj-link" data-tooltip="Voltar para DJs">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <span class="dj-header-title"><?php echo esc_html($dj_name); ?></span>
                <button class="dj-link" id="shareButton" data-tooltip="Compartilhar perfil">
                    <i class="ri-share-line"></i>
                </button>
            </header>
            
            <!-- ========================== -->
            <!-- DJ HERO Section            -->
            <!-- ========================== -->
            <section id="djHero" class="dj-hero">
                
                <!-- DJ Photo with Avatar -->
                <figure id="djPhoto" class="dj-photo">
                    <img id="dj-avatar" 
                         src="<?php echo esc_url($dj_image); ?>" 
                         alt="Retrato de <?php echo esc_attr($dj_name); ?>"
                         class="dj-avatar-img">
                    
                    <!-- Mediakit Link Badge -->
                    <?php if ($dj_media_kit_url): ?>
                    <a id="mediakit-link" href="<?php echo esc_url($dj_media_kit_url); ?>" 
                       class="dj-mediakit-badge" target="_blank" rel="noopener"
                       data-tooltip="Baixar Media Kit">
                        <i class="ri-download-2-line"></i>
                    </a>
                    <?php endif; ?>
                </figure>
                
                <!-- Hero Info Block -->
                <div class="dj-hero-info">
                    
                    <!-- Tagline (small text above name) -->
                    <p class="dj-tagline" data-tooltip="<?php echo empty($dj_tagline) ? 'Tagline não definida' : ''; ?>">
                        <?php echo esc_html($dj_tagline ?: 'Artist & DJ'); ?>
                    </p>
                    
                    <!-- Main Name -->
                    <h1 class="dj-name-main">
                        <?php echo esc_html($dj_name); ?>
                    </h1>
                    
                    <!-- Sub Name (real name or alias) -->
                    <?php if ($dj_name_sub): ?>
                    <p class="dj-name-sub"><?php echo esc_html($dj_name_sub); ?></p>
                    <?php else: ?>
                    <p class="dj-name-sub dj-placeholder" data-tooltip="Nome secundário não definido">—</p>
                    <?php endif; ?>
                    
                    <!-- Roles -->
                    <div class="dj-roles" data-tooltip="<?php echo empty($dj_roles_array) ? 'Adicione roles no admin' : ''; ?>">
                        <?php if (!empty($dj_roles_array)): ?>
                            <?php foreach ($dj_roles_array as $role): ?>
                                <span class="dj-role-tag"><?php echo esc_html($role); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="dj-role-tag dj-placeholder">DJ</span>
                            <span class="dj-role-tag dj-placeholder">Producer</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Projects -->
                    <div id="dj-projects" class="dj-projects" data-tooltip="<?php echo empty($projects) ? 'Projetos não cadastrados' : ''; ?>">
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $i => $project): ?>
                                <span <?php echo $i === 0 ? 'style="font-weight:800"' : ''; ?>><?php echo esc_html($project); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="dj-placeholder">Projeto Principal</span>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </section>
            
            <!-- ========================== -->
            <!-- VINYL PLAYER Section       -->
            <!-- ========================== -->
            <section id="djPlayerBlock" class="dj-player-block">
                <div id="vinylPlayer" class="vinyl-player" data-tooltip="Clique para tocar/pausar">
                    
                    <!-- Vinyl Disc -->
                    <div class="vinyl-disc">
                        <div class="vinyl-grooves"></div>
                        <div class="vinyl-label">
                            <span id="vinylLabelText" class="vinyl-label-text">
                                <?php 
                                // Split name into lines for label
                                echo implode('<br>', explode(' ', $dj_name)); 
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Tonearm (CRITICAL - must be present per design spec) -->
                    <div class="vinyl-tonearm">
                        <div class="tonearm-base"></div>
                        <div class="tonearm-arm"></div>
                        <div class="tonearm-head"></div>
                    </div>
                    
                    <!-- Play/Pause Toggle Button -->
                    <button id="vinylToggle" class="vinyl-toggle" aria-label="Tocar/Pausar">
                        <i id="vinylIcon" class="ri-play-fill"></i>
                    </button>
                    
                </div>
                
                <!-- Hidden SoundCloud Player -->
                <iframe id="scPlayer" 
                        class="sc-player-hidden"
                        allow="autoplay"
                        style="display:none;"></iframe>
                        
                <!-- Track Title -->
                <p class="dj-track-title" data-tooltip="<?php echo empty($dj_track_title) ? 'Track title não definido' : ''; ?>">
                    <i class="ri-music-2-line"></i>
                    <?php echo esc_html($dj_track_title ?: 'Now Playing'); ?>
                </p>
            </section>
            
            <!-- ========================== -->
            <!-- DJ INFO GRID               -->
            <!-- ========================== -->
            <section class="dj-info-grid">
                
                <!-- Bio Card -->
                <div class="dj-info-card dj-bio-card">
                    <h3 class="dj-info-title">
                        <i class="ri-user-line"></i> Bio
                    </h3>
                    <p id="dj-bio-excerpt" class="dj-bio-excerpt" data-tooltip="<?php echo empty($dj_bio_excerpt) ? 'Bio não cadastrada' : ''; ?>">
                        <?php echo esc_html($dj_bio_excerpt ?: 'Biografia não disponível.'); ?>
                    </p>
                    <?php if (!empty($dj_bio)): ?>
                    <button id="bioToggle" class="dj-link-pill" data-tooltip="Ler biografia completa">
                        <i class="ri-article-line"></i> Ler mais
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Music Links Card -->
                <div class="dj-info-card dj-music-card">
                    <h3 class="dj-info-title">
                        <i class="ri-headphone-line"></i> Música
                    </h3>
                    <div id="music-links" class="dj-links-wrap" data-tooltip="<?php echo empty($music_links) ? 'Nenhum link de música cadastrado' : ''; ?>">
                        <?php if (!empty($music_links)): ?>
                            <?php foreach ($music_links as $link): ?>
                            <a href="<?php echo esc_url($link['url']); ?>" 
                               class="dj-link-pill <?php echo $link['active'] ? 'active' : ''; ?>"
                               target="_blank" rel="noopener noreferrer">
                                <i class="<?php echo esc_attr($link['icon']); ?>"></i>
                                <?php echo esc_html($link['label']); ?>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="dj-placeholder dj-link-pill"><i class="ri-music-2-line"></i> Adicionar links</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Social Links Card -->
                <div class="dj-info-card dj-social-card">
                    <h3 class="dj-info-title">
                        <i class="ri-share-circle-line"></i> Social
                    </h3>
                    <div id="social-links" class="dj-links-wrap" data-tooltip="<?php echo empty($social_links) ? 'Nenhuma rede social cadastrada' : ''; ?>">
                        <?php if (!empty($social_links)): ?>
                            <?php foreach ($social_links as $link): ?>
                            <a href="<?php echo esc_url($link['url']); ?>" 
                               class="dj-link-pill"
                               target="_blank" rel="noopener noreferrer">
                                <i class="<?php echo esc_attr($link['icon']); ?>"></i>
                                <?php echo esc_html($link['label']); ?>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="dj-placeholder dj-link-pill"><i class="ri-user-add-line"></i> Adicionar redes</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Asset Links Card (Downloads) -->
                <div class="dj-info-card dj-assets-card">
                    <h3 class="dj-info-title">
                        <i class="ri-download-cloud-line"></i> Downloads
                    </h3>
                    <div id="asset-links" class="dj-links-wrap" data-tooltip="<?php echo empty($asset_links) ? 'Nenhum arquivo para download' : ''; ?>">
                        <?php if (!empty($asset_links)): ?>
                            <?php foreach ($asset_links as $link): ?>
                            <a href="<?php echo esc_url($link['url']); ?>" 
                               class="dj-link-pill"
                               target="_blank" rel="noopener noreferrer">
                                <i class="<?php echo esc_attr($link['icon']); ?>"></i>
                                <?php echo esc_html($link['label']); ?>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="dj-placeholder dj-link-pill"><i class="ri-file-add-line"></i> Adicionar arquivos</span>
                        <?php endif; ?>
                    </div>
                </div>
                
            </section>
            
            <!-- More Platforms -->
            <?php if ($dj_more_platforms): ?>
            <p id="more-platforms" class="dj-more-platforms">
                <span>More platforms:</span> <?php echo esc_html($dj_more_platforms); ?>
            </p>
            <?php endif; ?>
            
            <!-- ========================== -->
            <!-- DJ FOOTER                  -->
            <!-- ========================== -->
            <footer class="dj-footer">
                <div class="dj-footer-content">
                    <span class="dj-footer-text">
                        <i class="ri-map-pin-line"></i> <?php echo esc_html($country); ?>
                        <span class="dj-footer-divider">•</span>
                        <i class="ri-<?php echo $device_type === 'mobile' ? 'smartphone' : 'computer'; ?>-line"></i> <?php echo ucfirst($device_type); ?>
                    </span>
                    <a href="<?php echo esc_url(home_url()); ?>" class="dj-footer-brand">
                        <i class="ri-music-2-fill"></i> Apollo
                    </a>
                </div>
            </footer>
            
        </div><!-- .dj-content -->
    </div><!-- .dj-page -->
</section><!-- .dj-shell -->

<!-- ============================================ -->
<!-- BIO MODAL (Full Bio Overlay)                -->
<!-- ============================================ -->
<div id="bioBackdrop" class="dj-modal-backdrop" data-open="false">
    <div class="dj-modal">
        <header class="dj-modal-header">
            <h2 id="dj-bio-modal-title" class="dj-modal-title">
                Bio completa · <?php echo esc_html($dj_name); ?>
            </h2>
            <button id="bioClose" class="dj-modal-close" aria-label="Fechar">
                <i class="ri-close-line"></i>
            </button>
        </header>
        <div id="bio-full" class="dj-modal-body">
            <?php echo wp_kses_post(wpautop($dj_bio)); ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- DJ DATA for JavaScript                      -->
<!-- ============================================ -->
<script>
const DJ_DATA = <?php echo wp_json_encode($dj_data_js); ?>;
</script>

<!-- ============================================ -->
<!-- DJ TEMPLATE JAVASCRIPT                      -->
<!-- ============================================ -->
<script>
(function() {
    'use strict';

    let scWidget = null;
    let widgetReady = false;
    const animate = window.Motion?.animate;

    function initDJRoster() {
        // SoundCloud setup
        const scIframe = document.getElementById("scPlayer");
        const scUrl = DJ_DATA.soundcloudTrack;
        
        if (scUrl && scIframe) {
            const encodedUrl = encodeURIComponent(scUrl);
            scIframe.src = `https://w.soundcloud.com/player/?url=${encodedUrl}` +
                "&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false" +
                "&show_user=false&show_reposts=false&show_teaser=false";
        }

        const vinylPlayer = document.getElementById("vinylPlayer");
        const vinylToggle = document.getElementById("vinylToggle");
        const vinylIcon = document.getElementById("vinylIcon");

        function toggleVinylPlayback(e) {
            e.stopPropagation();
            if (!scWidget || !widgetReady) {
                console.log("SoundCloud widget not ready");
                return;
            }
            scWidget.isPaused((paused) => {
                if (paused) {
                    scWidget.play();
                } else {
                    scWidget.pause();
                }
            });
        }

        function initWidget() {
            if (typeof SC === "undefined" || !SC.Widget) {
                setTimeout(initWidget, 100);
                return;
            }

            scWidget = SC.Widget(scIframe);

            scWidget.bind(SC.Widget.Events.READY, () => {
                widgetReady = true;
                console.log("SoundCloud widget ready");
            });

            scWidget.bind(SC.Widget.Events.PLAY, () => {
                vinylPlayer?.classList.add("is-playing");
                vinylPlayer?.classList.remove("is-paused");
                if (vinylIcon) vinylIcon.className = "ri-pause-fill";
            });

            scWidget.bind(SC.Widget.Events.PAUSE, () => {
                vinylPlayer?.classList.remove("is-playing");
                vinylPlayer?.classList.add("is-paused");
                if (vinylIcon) vinylIcon.className = "ri-play-fill";
            });

            scWidget.bind(SC.Widget.Events.FINISH, () => {
                vinylPlayer?.classList.remove("is-playing");
                vinylPlayer?.classList.add("is-paused");
                if (vinylIcon) vinylIcon.className = "ri-play-fill";
            });
        }

        if (scUrl) {
            setTimeout(initWidget, 500);
        }

        // Play/Pause handlers
        if (vinylToggle) vinylToggle.addEventListener("click", toggleVinylPlayback);
        if (vinylPlayer) vinylPlayer.addEventListener("click", toggleVinylPlayback);

        // Bio Modal
        const bioBackdrop = document.getElementById("bioBackdrop");
        const bioToggle = document.getElementById("bioToggle");
        const bioClose = document.getElementById("bioClose");

        if (bioToggle) {
            bioToggle.addEventListener("click", () => {
                bioBackdrop.dataset.open = "true";
                if (animate) animate(bioBackdrop, { opacity: [0, 1] }, { duration: 0.3 });
            });
        }

        if (bioClose) {
            bioClose.addEventListener("click", () => {
                if (animate) {
                    animate(bioBackdrop, { opacity: [1, 0] }, { duration: 0.2 }).finished.then(() => {
                        bioBackdrop.dataset.open = "false";
                    });
                } else {
                    bioBackdrop.dataset.open = "false";
                }
            });
        }

        if (bioBackdrop) {
            bioBackdrop.addEventListener("click", (e) => {
                if (e.target === bioBackdrop && bioClose) bioClose.click();
            });
        }

        // Share Button
        const shareButton = document.getElementById("shareButton");
        if (shareButton) {
            shareButton.addEventListener("click", async () => {
                const shareData = {
                    title: DJ_DATA.name + " | Apollo",
                    text: DJ_DATA.bioExcerpt || `Confira o perfil de ${DJ_DATA.name}`,
                    url: window.location.href
                };
                
                try {
                    if (navigator.share) {
                        await navigator.share(shareData);
                    } else {
                        await navigator.clipboard.writeText(window.location.href);
                        alert("Link copiado!");
                    }
                } catch (err) {
                    console.log("Share failed:", err);
                }
            });
        }

        // Page animations
        if (animate) {
            const djPage = document.getElementById("djPage");
            const djHero = document.getElementById("djHero");
            const djPhoto = document.getElementById("djPhoto");
            const djPlayerBlock = document.getElementById("djPlayerBlock");
            
            if (djPage) animate(djPage, { opacity: [0, 1], y: [20, 0] }, { duration: 0.6, easing: [0.25, 0.8, 0.25, 1] });
            if (djHero) animate(djHero, { opacity: [0, 1], y: [15, 0] }, { duration: 0.5, delay: 0.15, easing: [0.25, 0.8, 0.25, 1] });
            if (djPhoto) animate(djPhoto, { opacity: [0, 1], scale: [0.95, 1] }, { duration: 0.5, delay: 0.2, easing: [0.25, 0.8, 0.25, 1] });
            if (djPlayerBlock) animate(djPlayerBlock, { opacity: [0, 1], y: [15, 0] }, { duration: 0.5, delay: 0.3, easing: [0.25, 0.8, 0.25, 1] });

            if (vinylPlayer) {
                vinylPlayer.addEventListener("mouseenter", () => animate(vinylPlayer, { scale: 1.03 }, { duration: 0.3 }));
                vinylPlayer.addEventListener("mouseleave", () => animate(vinylPlayer, { scale: 1 }, { duration: 0.3 }));
            }
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDJRoster);
    } else {
        initDJRoster();
    }
})();
</script>

<!-- Base JS (tooltips, theme, etc) -->
<script src="https://assets.apollo.rio.br/base.js"></script>

<?php wp_footer(); ?>
</body>
</html>

