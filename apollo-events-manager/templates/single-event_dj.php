<?php
/**
 * Template: Single DJ Page
 * Baseado 100% no CodePen YPwezXX + wBMZYwY (Vinyl)
 * 
 * - Removido Bulma CSS
 * - Usa uni.css
 * - Sistema de vinil com SoundCloud/Spotify API
 * - Bento Grid sem border-radius (linhas estilo Tetris)
 * - Analytics básico (país, mobile/desktop)
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

get_header();

$dj_id = get_the_ID();
if (get_post_type($dj_id) !== 'event_dj') {
    wp_die(__('Este template é apenas para DJs.', 'apollo-events-manager'));
}

// Add data attribute for front-beacon.js
?>
<div data-event-id="<?php echo esc_attr($dj_id); ?>" style="display:none;"></div>
<?php

// ============================================
// GET DJ DATA
// ============================================
$dj_name = get_the_title();
$dj_bio = get_post_meta($dj_id, '_dj_bio', true) ?: get_the_content();
$dj_image = get_post_meta($dj_id, '_dj_image', true);
if (empty($dj_image) && has_post_thumbnail()) {
    $dj_image = get_the_post_thumbnail_url($dj_id, 'large');
}

// Social Links
$dj_website = get_post_meta($dj_id, '_dj_website', true);
$dj_instagram = get_post_meta($dj_id, '_dj_instagram', true);
$dj_facebook = get_post_meta($dj_id, '_dj_facebook', true);
$dj_soundcloud = get_post_meta($dj_id, '_dj_soundcloud', true);
$dj_bandcamp = get_post_meta($dj_id, '_dj_bandcamp', true);
$dj_spotify = get_post_meta($dj_id, '_dj_spotify', true);
$dj_youtube = get_post_meta($dj_id, '_dj_youtube', true);
$dj_mixcloud = get_post_meta($dj_id, '_dj_mixcloud', true);
$dj_beatport = get_post_meta($dj_id, '_dj_beatport', true);
$dj_resident_advisor = get_post_meta($dj_id, '_dj_resident_advisor', true);
$dj_twitter = get_post_meta($dj_id, '_dj_twitter', true);
$dj_tiktok = get_post_meta($dj_id, '_dj_tiktok', true);

// Professional Content
$dj_set_url = get_post_meta($dj_id, '_dj_set_url', true);
$dj_mix_url = get_post_meta($dj_id, '_dj_mix_url', true);
$dj_media_kit_url = get_post_meta($dj_id, '_dj_media_kit_url', true);
$dj_rider_url = get_post_meta($dj_id, '_dj_rider_url', true);

// Original Projects
$dj_project_1 = get_post_meta($dj_id, '_dj_original_project_1', true);
$dj_project_2 = get_post_meta($dj_id, '_dj_original_project_2', true);
$dj_project_3 = get_post_meta($dj_id, '_dj_original_project_3', true);

// Get related events (where this DJ appears)
$related_events = get_posts(array(
    'post_type' => 'event_listing',
    'posts_per_page' => 10,
    'meta_query' => array(
        array(
            'key' => '_event_dj_ids',
            'value' => serialize(strval($dj_id)),
            'compare' => 'LIKE'
        )
    ),
    'orderby' => 'meta_value',
    'meta_key' => '_event_start_date',
    'order' => 'DESC'
));

// Separate future and past events
$future_events = array();
$past_events = array();
$today = date('Y-m-d');

foreach ($related_events as $event) {
    $event_date = get_post_meta($event->ID, '_event_start_date', true);
    if ($event_date >= $today) {
        $future_events[] = $event;
    } else {
        $past_events[] = $event;
    }
}

// ============================================
// ANALYTICS - Track view
// ============================================
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Detect device type
$is_mobile = wp_is_mobile();
$device_type = $is_mobile ? 'mobile' : 'desktop';

// Detect country (simple IP-based, can be enhanced with GeoIP)
$country = 'Unknown';
if (function_exists('geoip_detect2_get_info_from_ip')) {
    $geoip = geoip_detect2_get_info_from_ip($user_ip);
    $country = $geoip->country->name ?? 'Unknown';
} elseif (function_exists('geoip_country_code_by_name')) {
    $country_code = @geoip_country_code_by_name($user_ip);
    $country = $country_code ? $country_code : 'Unknown';
}

// Store analytics (simple meta-based tracking)
$analytics_key = '_dj_view_' . date('Y-m-d');
$views_today = get_post_meta($dj_id, $analytics_key, true);
update_post_meta($dj_id, $analytics_key, ($views_today ? intval($views_today) + 1 : 1));

// Store device/country info (last view)
update_post_meta($dj_id, '_dj_last_view_device', $device_type);
update_post_meta($dj_id, '_dj_last_view_country', $country);
update_post_meta($dj_id, '_dj_last_view_date', current_time('mysql'));

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($dj_name); ?> | Apollo Events</title>
    
    <!-- 1. UNI.CSS FIRST (CRITICAL - defines root variables) -->
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
    
    <!-- 2. RemixIcon (after uni.css) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">
    
    <!-- SoundCloud Widget API -->
    <script src="https://w.soundcloud.com/player/api.js"></script>
    
    <!-- Spotify Embed API -->
    <script src="https://open.spotify.com/embed/iframe-api/v1"></script>
    
    <style>
    /* ============================================
       BENTO GRID - Sem border-radius, linhas estilo Tetris
       ============================================ */
    .bento-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 0;
        margin: 0;
        padding: 0;
        border: 2px solid #000;
    }
    
    .bento-cell {
        border: 2px solid #000;
        border-radius: 0;
        padding: 20px;
        background: var(--bg-primary, #fff);
        position: relative;
        overflow: hidden;
    }
    
    /* Linhas de borda começando das bordas da página */
    .bento-cell::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: #000;
        z-index: 1;
    }
    
    .bento-cell::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 2px;
        height: 100%;
        background: #000;
        z-index: 1;
    }
    
    /* ============================================
       VINYL RECORD PLAYER - CodePen wBMZYwY
       ============================================ */
    .vinyl-container {
        position: relative;
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
        aspect-ratio: 1;
    }
    
    .vinyl-record {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: radial-gradient(circle, #1a1a1a 0%, #000 100%);
        position: relative;
        transition: transform 0.3s ease;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }
    
    .vinyl-record.is-playing {
        animation: spin 3s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .vinyl-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20%;
        height: 20%;
        background: #fff;
        border-radius: 50%;
        border: 3px solid #000;
        z-index: 2;
    }
    
    .vinyl-grooves {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: repeating-conic-gradient(
            from 0deg,
            transparent 0deg 2deg,
            rgba(255, 255, 255, 0.1) 2deg 4deg
        );
    }
    
    .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: rgba(0, 0, 0, 0.8);
        border: 3px solid #fff;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .play-button:hover {
        background: rgba(0, 0, 0, 0.9);
        transform: translate(-50%, -50%) scale(1.1);
    }
    
    .play-button.is-playing {
        background: rgba(255, 0, 0, 0.8);
    }
    
    .play-button i {
        color: #fff;
        font-size: 24px;
        margin-left: 3px; /* Offset for play icon */
    }
    
    .player-embed {
        display: none;
        width: 100%;
        height: 166px;
        border: none;
        margin-top: 20px;
    }
    
    .player-embed.active {
        display: block;
    }
    
    /* ============================================
       DJ HERO SECTION
       ============================================ */
    .dj-hero {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 0;
        border: 2px solid #000;
        border-radius: 0;
        margin-bottom: 0;
    }
    
    .dj-hero-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-right: 2px solid #000;
    }
    
    .dj-hero-info {
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .dj-hero-info h1 {
        font-size: 3rem;
        margin: 0 0 20px 0;
        font-weight: 700;
    }
    
    .dj-hero-info .dj-bio {
        font-size: 1.1rem;
        line-height: 1.6;
        color: var(--text-secondary, #666);
        margin-bottom: 30px;
    }
    
    .dj-social-links {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .dj-social-links a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--bg-secondary, #f5f5f5);
        border: 2px solid #000;
        border-radius: 0;
        text-decoration: none;
        color: var(--text-primary, #000);
        transition: all 0.2s ease;
        font-weight: 500;
    }
    
    .dj-social-links a:hover {
        background: #000;
        color: #fff;
        transform: translateY(-2px);
    }
    
    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 768px) {
        .dj-hero {
            grid-template-columns: 1fr;
        }
        
        .dj-hero-image {
            border-right: none;
            border-bottom: 2px solid #000;
        }
        
        .bento-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* ============================================
       ANALYTICS BADGE
       ============================================ */
    .analytics-badge {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.8);
        color: #fff;
        padding: 10px 15px;
        border-radius: 0;
        border: 2px solid #fff;
        font-size: 0.85rem;
        z-index: 1000;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .analytics-badge i {
        font-size: 1.2em;
    }
    </style>
</head>
<body <?php body_class('apollo-dj-single'); ?>>

<main class="page-wrap" style="max-width: 1400px; margin: 0 auto; padding: 0;">
    
    <!-- DJ Hero Section -->
    <section class="dj-hero">
        <?php if ($dj_image): ?>
            <img src="<?php echo esc_url($dj_image); ?>" alt="<?php echo esc_attr($dj_name); ?>" class="dj-hero-image">
        <?php endif; ?>
        
        <div class="dj-hero-info">
            <h1><?php echo esc_html($dj_name); ?></h1>
            
            <?php if ($dj_bio): ?>
                <div class="dj-bio">
                    <?php echo wp_kses_post(wpautop($dj_bio)); ?>
                </div>
            <?php endif; ?>
            
            <div class="dj-social-links">
                <?php if ($dj_website): ?>
                    <a href="<?php echo esc_url($dj_website); ?>" target="_blank" rel="noopener">
                        <i class="ri-global-line"></i> Website
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_instagram): ?>
                    <a href="<?php echo esc_url($dj_instagram); ?>" target="_blank" rel="noopener">
                        <i class="ri-instagram-line"></i> Instagram
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_facebook): ?>
                    <a href="<?php echo esc_url($dj_facebook); ?>" target="_blank" rel="noopener">
                        <i class="ri-facebook-line"></i> Facebook
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_soundcloud): ?>
                    <a href="<?php echo esc_url($dj_soundcloud); ?>" target="_blank" rel="noopener">
                        <i class="ri-soundcloud-line"></i> SoundCloud
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_spotify): ?>
                    <a href="<?php echo esc_url($dj_spotify); ?>" target="_blank" rel="noopener">
                        <i class="ri-spotify-line"></i> Spotify
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_bandcamp): ?>
                    <a href="<?php echo esc_url($dj_bandcamp); ?>" target="_blank" rel="noopener">
                        <i class="ri-music-line"></i> Bandcamp
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_youtube): ?>
                    <a href="<?php echo esc_url($dj_youtube); ?>" target="_blank" rel="noopener">
                        <i class="ri-youtube-line"></i> YouTube
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_mixcloud): ?>
                    <a href="<?php echo esc_url($dj_mixcloud); ?>" target="_blank" rel="noopener">
                        <i class="ri-cloud-line"></i> Mixcloud
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_twitter): ?>
                    <a href="<?php echo esc_url($dj_twitter); ?>" target="_blank" rel="noopener">
                        <i class="ri-twitter-x-line"></i> Twitter
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_tiktok): ?>
                    <a href="<?php echo esc_url($dj_tiktok); ?>" target="_blank" rel="noopener">
                        <i class="ri-tiktok-line"></i> TikTok
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Bento Grid -->
    <div class="bento-grid">
        
        <!-- Vinyl Player Cell -->
        <?php if ($dj_set_url || $dj_mix_url): ?>
        <div class="bento-cell">
            <h2 style="margin: 0 0 20px 0; font-size: 1.5rem;">
                <i class="ri-disc-line"></i> Player
            </h2>
            
            <div class="vinyl-container">
                <div class="vinyl-record" id="vinylRecord">
                    <div class="vinyl-grooves"></div>
                    <div class="vinyl-center"></div>
                </div>
                <button class="play-button" id="playButton" aria-label="Play/Pause">
                    <i class="ri-play-fill" id="playIcon"></i>
                </button>
            </div>
            
            <!-- Player Embed (hidden by default) -->
            <div id="playerContainer">
                <?php
                $player_url = $dj_set_url ?: $dj_mix_url;
                $service = '';
                
                // Detect service
                if (strpos($player_url, 'soundcloud.com') !== false) {
                    $service = 'soundcloud';
                    // Extract SoundCloud URL
                    preg_match('/(?:soundcloud\.com\/|snd\.sc\/)([^\/\?]+)/', $player_url, $matches);
                    if (!empty($matches[1])) {
                        $sc_url = 'https://soundcloud.com/' . $matches[1];
                        ?>
                        <iframe id="sc-player" 
                                src="https://w.soundcloud.com/player/?url=<?php echo urlencode($sc_url); ?>&color=%23ff5500&auto_play=false&hide_related=false&show_comments=true&show_user=true&show_reposts=false&show_teaser=true&visual=true"
                                class="player-embed"
                                allow="autoplay"></iframe>
                        <?php
                    }
                } elseif (strpos($player_url, 'spotify.com') !== false || strpos($player_url, 'open.spotify.com') !== false) {
                    $service = 'spotify';
                    // Extract Spotify URI
                    preg_match('/(?:spotify\.com\/(?:track|album|playlist)\/|spotify:)([a-zA-Z0-9]+)/', $player_url, $matches);
                    if (!empty($matches[1])) {
                        $spotify_uri = 'spotify:track:' . $matches[1];
                        ?>
                        <iframe id="spotify-player"
                                src="https://open.spotify.com/embed/track/<?php echo esc_attr($matches[1]); ?>?utm_source=generator"
                                class="player-embed"
                                allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                                loading="lazy"></iframe>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Original Projects -->
        <?php if ($dj_project_1 || $dj_project_2 || $dj_project_3): ?>
        <div class="bento-cell">
            <h2 style="margin: 0 0 20px 0; font-size: 1.5rem;">
                <i class="ri-folder-music-line"></i> Projetos Originais
            </h2>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php if ($dj_project_1): ?>
                    <li style="margin-bottom: 15px;">
                        <a href="<?php echo esc_url($dj_project_1); ?>" target="_blank" rel="noopener" style="text-decoration: none; color: var(--text-primary, #000); font-weight: 500;">
                            <i class="ri-external-link-line"></i> Projeto 1
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($dj_project_2): ?>
                    <li style="margin-bottom: 15px;">
                        <a href="<?php echo esc_url($dj_project_2); ?>" target="_blank" rel="noopener" style="text-decoration: none; color: var(--text-primary, #000); font-weight: 500;">
                            <i class="ri-external-link-line"></i> Projeto 2
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($dj_project_3): ?>
                    <li style="margin-bottom: 15px;">
                        <a href="<?php echo esc_url($dj_project_3); ?>" target="_blank" rel="noopener" style="text-decoration: none; color: var(--text-primary, #000); font-weight: 500;">
                            <i class="ri-external-link-line"></i> Projeto 3
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Professional Downloads -->
        <div class="bento-cell">
            <h2 style="margin: 0 0 20px 0; font-size: 1.5rem;">
                <i class="ri-download-line"></i> Downloads
            </h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if ($dj_media_kit_url): ?>
                    <a href="<?php echo esc_url($dj_media_kit_url); ?>" target="_blank" rel="noopener" 
                       style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 20px; background: var(--bg-secondary, #f5f5f5); border: 2px solid #000; border-radius: 0; text-decoration: none; color: var(--text-primary, #000); font-weight: 500;">
                        <i class="ri-file-download-line"></i> Media Kit
                    </a>
                <?php endif; ?>
                
                <?php if ($dj_rider_url): ?>
                    <a href="<?php echo esc_url($dj_rider_url); ?>" target="_blank" rel="noopener"
                       style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 20px; background: var(--bg-secondary, #f5f5f5); border: 2px solid #000; border-radius: 0; text-decoration: none; color: var(--text-primary, #000); font-weight: 500;">
                        <i class="ri-file-download-line"></i> Rider
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upcoming Events -->
        <?php if (!empty($future_events)): ?>
        <div class="bento-cell">
            <h2 style="margin: 0 0 20px 0; font-size: 1.5rem;">
                <i class="ri-calendar-event-line"></i> Próximos Eventos
            </h2>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($future_events as $event): 
                    $event_date = get_post_meta($event->ID, '_event_start_date', true);
                    $event_location = get_post_meta($event->ID, '_event_location', true);
                ?>
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color, #ddd);">
                        <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" 
                           style="text-decoration: none; color: var(--text-primary, #000);">
                            <strong><?php echo esc_html($event->post_title); ?></strong><br>
                            <small style="color: var(--text-secondary, #666);">
                                <?php echo esc_html($event_date ? date_i18n('d/m/Y', strtotime($event_date)) : ''); ?>
                                <?php if ($event_location): ?>
                                    • <?php echo esc_html($event_location); ?>
                                <?php endif; ?>
                            </small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Past Events -->
        <?php if (!empty($past_events)): ?>
        <div class="bento-cell">
            <h2 style="margin: 0 0 20px 0; font-size: 1.5rem;">
                <i class="ri-history-line"></i> Eventos Passados
            </h2>
            <ul style="list-style: none; padding: 0; margin: 0; max-height: 400px; overflow-y: auto;">
                <?php foreach (array_slice($past_events, 0, 10) as $event): 
                    $event_date = get_post_meta($event->ID, '_event_start_date', true);
                    $event_location = get_post_meta($event->ID, '_event_location', true);
                ?>
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color, #ddd);">
                        <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" 
                           style="text-decoration: none; color: var(--text-primary, #000);">
                            <strong><?php echo esc_html($event->post_title); ?></strong><br>
                            <small style="color: var(--text-secondary, #666);">
                                <?php echo esc_html($event_date ? date_i18n('d/m/Y', strtotime($event_date)) : ''); ?>
                                <?php if ($event_location): ?>
                                    • <?php echo esc_html($event_location); ?>
                                <?php endif; ?>
                            </small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
    </div>
    
</main>

<!-- Analytics Badge -->
<div class="analytics-badge">
    <i class="ri-bar-chart-line"></i>
    <span><?php echo esc_html($country); ?> • <?php echo esc_html($device_type); ?></span>
</div>

<script>
(function() {
    'use strict';
    
    const playButton = document.getElementById('playButton');
    const vinylRecord = document.getElementById('vinylRecord');
    const playIcon = document.getElementById('playIcon');
    const playerContainer = document.getElementById('playerContainer');
    
    if (!playButton || !vinylRecord) return;
    
    let isPlaying = false;
    let scWidget = null;
    let spotifyController = null;
    
    // Detect service and initialize
    const scPlayer = document.getElementById('sc-player');
    const spotifyPlayer = document.getElementById('spotify-player');
    
    // Initialize SoundCloud Widget
    if (scPlayer && typeof SC !== 'undefined') {
        scWidget = SC.Widget(scPlayer);
        
        scWidget.bind(SC.Widget.Events.READY, function() {
            console.log('SoundCloud widget ready');
        });
        
        scWidget.bind(SC.Widget.Events.PLAY, function() {
            isPlaying = true;
            vinylRecord.classList.add('is-playing');
            playButton.classList.add('is-playing');
            playIcon.className = 'ri-pause-fill';
        });
        
        scWidget.bind(SC.Widget.Events.PAUSE, function() {
            isPlaying = false;
            vinylRecord.classList.remove('is-playing');
            playButton.classList.remove('is-playing');
            playIcon.className = 'ri-play-fill';
        });
    }
    
    // Initialize Spotify Embed
    if (spotifyPlayer && typeof Spotify !== 'undefined') {
        window.onSpotifyIframeApiReady = (IFrameAPI) => {
            const element = spotifyPlayer;
            const options = {
                width: '100%',
                height: '166',
            };
            const callback = (EmbedController) => {
                spotifyController = EmbedController;
                console.log('Spotify controller ready');
            };
            IFrameAPI.createController(element, options, callback);
        };
    }
    
    // Play button click handler
    playButton.addEventListener('click', function() {
        if (!isPlaying) {
            // Show player if hidden
            if (playerContainer) {
                const embed = playerContainer.querySelector('.player-embed');
                if (embed) {
                    embed.classList.add('active');
                }
            }
            
            // Play SoundCloud
            if (scWidget) {
                scWidget.play();
            }
            
            // Play Spotify
            if (spotifyController) {
                spotifyController.togglePlay();
            }
            
            isPlaying = true;
            vinylRecord.classList.add('is-playing');
            playButton.classList.add('is-playing');
            playIcon.className = 'ri-pause-fill';
        } else {
            // Pause SoundCloud
            if (scWidget) {
                scWidget.pause();
            }
            
            // Pause Spotify
            if (spotifyController) {
                spotifyController.togglePlay();
            }
            
            isPlaying = false;
            vinylRecord.classList.remove('is-playing');
            playButton.classList.remove('is-playing');
            playIcon.className = 'ri-play-fill';
        }
    });
})();
</script>

<?php get_footer(); ?>

