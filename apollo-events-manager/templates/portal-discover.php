<?php
/**
 * FILE: apollo-events-manager/templates/portal-discover.php
 * Optimized Events Discovery Portal
 * - Removed 200+ lines of duplicated DJ/Local logic (now in Helper)
 * - Better caching strategy with proper TTL
 * - Consolidated meta prefetching
 * - Proper error boundaries
 */

defined('ABSPATH') || exit;

// Load helper (only place that does)
require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

// ============================================================
// STEP 1: Disable WordPress cruft
// ============================================================
add_filter('show_admin_bar', '__return_false', 999);
remove_action('wp_head', '_admin_bar_bump_cb');

add_action('wp_enqueue_scripts', function() {
    global $wp_styles, $wp_scripts;
    
    $allowed_styles = [
        'remixicon', 'apollo-shadcn-components', 'apollo-event-modal-css',
        'leaflet-css', 'apollo-infinite-scroll-css', 'apollo-uni-css', 'apollo-social-pwa'
    ];
    $allowed_scripts = [
        'jquery-core', 'jquery-migrate', 'apollo-loading-animation', 'apollo-base-js',
        'leaflet', 'apollo-events-portal', 'apollo-motion-event-card', 'apollo-motion-modal',
        'apollo-infinite-scroll', 'apollo-motion-dashboard', 'apollo-motion-context-menu',
        'apollo-character-counter', 'apollo-form-validation', 'apollo-image-modal',
        'apollo-events-favorites', 'framer-motion', 'apollo-social-pwa'
    ];
    
    if (is_object($wp_styles)) {
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles, true)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }
        }
    }
    
    if (is_object($wp_scripts)) {
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts, true)) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }
    }
}, 999);

array_map(function($action) {
    remove_action('wp_head', $action);
}, ['wp_generator', 'wlwmanifest_link', 'rsd_link', 'wp_shortlink_wp_head',
    'adjacent_posts_rel_link_wp_head', 'feed_links', 'feed_links_extra',
    'rest_output_link_wp_head', 'wp_oembed_add_discovery_links',
    'print_emoji_detection_script']);
remove_action('wp_print_styles', 'print_emoji_styles');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <title>Discover Events - Apollo::rio</title>
    <link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
    <link href="https://assets.apollo.rio.br/uni.css?ver=<?php echo date('Y-m'); ?>" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body class="apollo-canvas-mode">

<!-- FIXED HEADER -->
<header class="site-header">
    <div class="menu-h-apollo-blur"></div>
    <a href="<?php echo home_url('/'); ?>" class="menu-apollo-logo"></a>
    <nav class="main-nav">
        <a class="a-hover off"><span id="agoraH"><?php echo esc_html(apollo_get_placeholder('APOLLO_PLACEHOLDER_CURRENT_TIME')); ?></span> RJ</a>
        <a href="<?php echo home_url('/eventos/'); ?>" class="ario-eve" title="Portal de Eventos">
            Eventos<i class="ri-arrow-right-up-line"></i>
        </a>
        <div class="menu-h-lista">
            <?php if (is_user_logged_in()):
                $user = wp_get_current_user();
            ?>
                <button class="menu-h-apollo-button caption" id="userMenuTrigger">
                    <?php echo esc_html($user->display_name); ?>
                </button>
                <div class="list">
                    <div class="item ok"><i class="ri-global-line"></i> Explorer</div>
                    <hr>
                    <div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
                    <div class="item ok"><a href="<?php echo wp_logout_url(home_url()); ?>"><i class="ri-logout-box-r-line"></i> Logout</a></div>
                </div>
            <?php else: ?>
                <button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
                <div class="list">
                    <div class="item ok"><i class="ri-global-line"></i> Explorer</div>
                    <hr>
                    <div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
                </div>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main class="main-container">
    <div class="event-manager-shortcode-wrapper discover-events-now-shortcode">
        <!-- HERO -->
        <section class="hero-section">
            <h1 class="title-page">Descubra os Próximos Eventos</h1>
            <p class="subtitle-page">Um novo <mark>hub digital que conecta cultura,</mark> tecnologia e experiências em tempo real... <mark>O futuro da cultura carioca começa aqui!</mark></p>
        </section>

        <!-- FILTERS -->
        <div class="filters-and-search">
            <div class="menutags event_types" role="group" aria-label="Filtros de eventos">
                <button type="button" class="menutag event-category active" data-slug="all" aria-pressed="true">
                    <span class="xxall">Todos</span>
                </button>
                <?php
                // Categories
                $cats = get_terms(['taxonomy' => 'event_listing_category', 'hide_empty' => false]);
                if (!is_wp_error($cats)) {
                    foreach ($cats as $cat) {
                        echo sprintf('<button type="button" class="menutag event-category" data-slug="%s">%s</button>',
                            esc_attr($cat->slug), esc_html($cat->name));
                    }
                }
                
                // Locals
                $locals = get_posts([
                    'post_type' => 'event_local', 'posts_per_page' => 5,
                    'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC'
                ]);
                foreach ($locals as $local) {
                    $name = apollo_get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
                    echo sprintf('<button type="button" class="menutag event-category event-local-filter" data-slug="%s" data-filter-type="local">%s</button>',
                        esc_attr($local->post_name), esc_html($name));
                }
                ?>
                
                <!-- DATE PICKER -->
                <div class="date-chip" id="eventDatePicker">
                    <button type="button" class="date-arrow" id="datePrev" aria-label="Mês anterior">‹</button>
                    <span class="date-display" id="dateDisplay" aria-live="polite"><?php echo date_i18n('M'); ?></span>
                    <button type="button" class="date-arrow" id="dateNext" aria-label="Próximo mês">›</button>
                </div>
                
                <!-- LAYOUT TOGGLE -->
                <button type="button" class="layout-toggle" id="wpem-event-toggle-layout" 
                        title="Alternar layout" aria-pressed="false" data-layout="card">
                    <i class="ri-building-3-fill"></i>
                    <span class="visually-hidden">Alternar layout</span>
                </button>
            </div>
        </div>

        <!-- SEARCH -->
        <div class="controls-bar" id="apollo-controls-bar">
            <form class="box-search" role="search" id="eventSearchForm">
                <label for="eventSearchInput" class="visually-hidden">Procurar</label>
                <i class="ri-search-line"></i>
                <input type="text" name="search_keywords" id="eventSearchInput" 
                       placeholder="" inputmode="search" autocomplete="off">
                <input type="hidden" name="post_type" value="event_listing">
            </form>
        </div>

        <p class="afasta-2b"></p>

        <!-- EVENT GRID -->
        <div class="event_listings card-view">
            <?php
            // OPTIMIZED QUERY with caching
            $cache_key = 'apollo_all_event_ids_' . date('Ymd');
            $bypass_cache = defined('APOLLO_PORTAL_DEBUG_BYPASS_CACHE') && APOLLO_PORTAL_DEBUG_BYPASS_CACHE;
            $event_ids = $bypass_cache ? false : get_transient($cache_key);
            
            if (false === $event_ids) {
                $query = new WP_Query([
                    'post_type' => 'event_listing', 'posts_per_page' => -1,
                    'post_status' => 'publish', 'meta_key' => '_event_start_date',
                    'orderby' => 'meta_value', 'order' => 'ASC',
                    'update_post_meta_cache' => true, 'update_post_term_cache' => true,
                    'no_found_rows' => true
                ]);
                
                if (is_wp_error($query)) {
                    error_log('Apollo: WP_Query error: ' . $query->get_error_message());
                    $event_ids = [];
                } else {
                    $event_ids = array_map('absint', wp_list_pluck($query->posts, 'ID'));
                    $cache_ttl = defined('APOLLO_PORTAL_CACHE_TTL') ? absint(APOLLO_PORTAL_CACHE_TTL) : (2 * MINUTE_IN_SECONDS);
                    set_transient($cache_key, $event_ids, $cache_ttl);
                }
            }
            
            if (empty($event_ids)) {
                echo '<div class="no-events-found" role="alert"><i class="ri-calendar-event-line"></i><p>Nenhum evento encontrado.</p></div>';
            } else {
                $events = get_posts([
                    'post_type' => 'event_listing', 'post_status' => 'publish',
                    'post__in' => $event_ids, 'orderby' => 'post__in',
                    'posts_per_page' => count($event_ids), 'update_post_meta_cache' => true,
                    'update_post_term_cache' => true, 'no_found_rows' => true
                ]);
                
                // Prefetch all meta at once
                update_meta_cache('post', $event_ids);
                update_post_term_cache(wp_list_pluck($events, 'ID'), 'event_listing_category');
                
                foreach ($events as $post) {
                    $id = $post->ID;
                    $date_info = Apollo_Event_Data_Helper::parse_event_date(
                        apollo_get_post_meta($id, '_event_start_date', true)
                    );
                    $local = Apollo_Event_Data_Helper::get_local_data($id);
                    $djs = Apollo_Event_Data_Helper::get_dj_lineup($id);
                    $banner = Apollo_Event_Data_Helper::get_banner_url($id);
                    $cats = wp_get_post_terms($id, 'event_listing_category');
                    $cat_slug = !is_wp_error($cats) && $cats ? $cats[0]->slug : 'general';
                    $tags = wp_get_post_terms($id, 'event_sounds');
                    $tags = !is_wp_error($tags) ? $tags : [];
                    
                    ?>
                    <a href="<?php echo esc_url(get_permalink($id)); ?>" 
                       class="event_listing" 
                       data-event-id="<?php echo esc_attr($id); ?>" 
                       data-category="<?php echo esc_attr($cat_slug); ?>"
                       data-local-slug="<?php echo esc_attr($local ? $local['slug'] : ''); ?>"
                       data-month-str="<?php echo esc_attr($date_info['month_pt']); ?>"
                       data-event-start-date="<?php echo esc_attr($date_info['iso_date']); ?>">
                        
                        <div class="box-date-event">
                            <span class="date-day"><?php echo esc_html($date_info['day']); ?></span>
                            <span class="date-month"><?php echo esc_html($date_info['month_pt']); ?></span>
                        </div>
                        
                        <div class="picture">
                            <img src="<?php echo esc_url($banner); ?>" 
                                 alt="<?php echo esc_attr($post->post_title); ?>" 
                                 loading="lazy" decoding="async">
                            
                            <?php if (!empty($tags)): ?>
                            <div class="event-card-tags">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                <span><?php echo esc_html($tag->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-line">
                            <div class="box-info-event">
                                <h2 class="event-li-title afasta-bmin"><?php echo esc_html($post->post_title); ?></h2>
                                
                                <p class="event-li-detail of-dj afasta-bmin">
                                    <i class="ri-sound-module-fill"></i>
                                    <span><?php echo wp_kses_post(Apollo_Event_Data_Helper::format_dj_display($djs)); ?></span>
                                </p>
                                
                                <?php if ($local && $local['name']): ?>
                                <p class="event-li-detail of-location afasta-bmin">
                                    <i class="ri-map-pin-2-line"></i>
                                    <span class="event-location-name"><?php echo esc_html($local['name']); ?></span>
                                    <?php if ($local['region']): ?>
                                    <span class="event-location-area" style="opacity:0.5;">&nbsp;(<?php echo esc_html($local['region']); ?>)</span>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php
                }
            }
            ?>
        </div>

        <!-- BANNER -->
        <?php
        $latest = get_posts(['post_type' => 'post', 'posts_per_page' => 1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC']);
        if ($latest):
            $post = $latest[0];
            $banner_img = get_the_post_thumbnail_url($post->ID, 'full') ?: 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070';
            $excerpt = wp_trim_words($post->post_excerpt ?: $post->post_content, 30, '...');
        ?>
        <section class="banner-ario-1-wrapper" style="margin-top:80px;">
            <img src="<?php echo esc_url($banner_img); ?>" class="ban-ario-1-img" alt="<?php echo esc_attr($post->post_title); ?>">
            <div class="ban-ario-1-content">
                <h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
                <h2 class="ban-ario-1-titl"><?php echo esc_html($post->post_title); ?></h2>
                <p class="ban-ario-1-txt"><?php echo esc_html($excerpt); ?></p>
                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="ban-ario-1-btn">
                    Saiba Mais <i class="ri-arrow-right-long-line"></i>
                </a>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <div id="apollo-event-modal" class="apollo-event-modal" aria-hidden="true"></div>
</main>

<!-- DARK MODE TOGGLE -->
<div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Alternar modo escuro">
    <i class="ri-sun-line"></i>
    <i class="ri-moon-line"></i>
</div>

<script src="https://assets.apollo.rio.br/base.js?ver=<?php echo date('Y-m'); ?>"></script>
<?php wp_footer(); ?>
</body>
</html>