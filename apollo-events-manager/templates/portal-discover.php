<?php
/**
 * Template: Discover Events Portal
 * Based 100% on CodePen vELzbrx
 * URL: https://codepen.io/Rafael-Valle-the-looper/pen/vELzbrx
 */

defined('ABSPATH') || exit;

// Force CSS inline
$css_url = 'https://assets.apollo.rio.br/uni.css';
$css_content = wp_remote_retrieve_body(wp_remote_get($css_url, ['timeout' => 10]));
if (!$css_content || is_wp_error($css_content)) {
    // Fallback to local
    $local_css = APOLLO_WPEM_PATH . 'assets/uni.css';
    if (file_exists($local_css)) {
        $css_content = file_get_contents($local_css);
    }
}
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
    
    <?php
    // Force CSS inline injection
    $css_url = 'https://assets.apollo.rio.br/uni.css';
    $css_response = wp_remote_get($css_url, ['timeout' => 15]);
    
    if (!is_wp_error($css_response)) {
        $css_content = wp_remote_retrieve_body($css_response);
        if ($css_content) {
            echo '<style type="text/css">' . $css_content . '</style>';
        }
    } else {
        // Fallback to local
        $local_css_path = APOLLO_WPEM_PATH . 'assets/uni.css';
        if (file_exists($local_css_path)) {
            echo '<style type="text/css">' . file_get_contents($local_css_path) . '</style>';
        }
    }
    ?>
    
    <?php wp_head(); ?>
</head>
<body>

    <!-- FIXED HEADER -->
    <header class="site-header">
        <div class="menu-h-apollo-blur"></div>
        <a href="<?php echo home_url('/'); ?>" class="menu-apollo-logo"></a>
        <nav class="main-nav">
            <a class="a-hover off"><span id="agoraH">--:--</span> RJ</a>
            <a href="<?php echo home_url('/eventos/'); ?>" class="ario-eve" title="Portal de Eventos">
                Eventos<i class="ri-arrow-right-up-line"></i>
            </a>
            
            <!-- User Menu Dropdown -->
            <div class="menu-h-lista">
                <?php if (is_user_logged_in()): 
                    $current_user = wp_get_current_user();
                ?>
                    <button class="menu-h-apollo-button caption" id="userMenuTrigger">
                        <?php echo esc_html($current_user->display_name); ?>
                    </button>
                    <div class="list">
                        <div class="item ok"><i class="ri-global-line"></i> Explorer</div>
                        <hr>
                        <div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
                        <div class="item ok">
                            <a href="<?php echo wp_logout_url(home_url()); ?>">
                                <i class="ri-logout-box-r-line"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
                    <div class="list">
                        <div class="item ok"><i class="ri-global-line"></i> Explorer</div>
                        <hr>
                        <div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
                        <div class="item ok"><i class="ri-logout-box-r-line"></i> Logout</div>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <!-- END HEADER -->

    <main class="main-container">
        <div class="event-manager-shortcode-wrapper discover-events-now-shortcode">

            <section class="hero-section">
                <h1 class="title-page">Experience Tomorrow's Events</h1>
                <p class="subtitle-page">Um novo <mark>&nbsp;hub digital que conecta cultura,&nbsp;</mark> tecnologia e experiências em tempo real... <mark>&nbsp;O futuro da cultura carioca começa aqui!&nbsp;</mark></p>
            </section>

            <!-- FILTERS & SEARCH -->
            <div class="filters-and-search">
                <div class="menutags event_types">
                    <button class="menutag event-category active" data-slug="all">
                        <span id="xxall" class="xxall" style="opacity: 1;">Todos</span>
                    </button>
                    <button class="menutag event-category" data-slug="music">House</button>
                    <button class="menutag event-category" data-slug="art-culture">PsyTrance</button>
                    <button class="menutag event-category" data-slug="mainstream">Techno</button>
                    <button class="menutag event-category" data-slug="workshops">D-Edge club</button>
                    
                    <!-- DATE CHIP -->
                    <div class="date-chip" id="eventDatePicker">
                        <button type="button" class="date-arrow" id="datePrev" aria-label="Mês anterior">‹</button>
                        <span class="date-display" id="dateDisplay" aria-live="polite">Out</span>
                        <button type="button" class="date-arrow" id="dateNext" aria-label="Próximo mês">›</button>
                    </div>
                    
                    <!-- LAYOUT TOGGLE -->
                    <button type="button" class="layout-toggle" id="wpem-event-toggle-layout" title="Events List View" aria-pressed="true" onclick="toggleLayout(this)">
                        <i class="ri-list-check-2" aria-hidden="true"></i>
                        <span class="visually-hidden">Alternar layout</span>
                    </button>
                </div>
            </div>

            <!-- CONTROLS BAR (SEARCH) -->
            <div class="controls-bar" id="apollo-controls-bar">
                <form class="box-search" role="search" id="eventSearchForm">
                    <label for="eventSearchInput" class="visually-hidden">Procurar</label>
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input type="text" name="search_keywords" id="eventSearchInput" placeholder="" inputmode="search" autocomplete="off">
                    <input type="hidden" name="post_type" value="event_listing">
                </form>
            </div>
            
            <p style="margin-bottom:15px;"></p>

            <!-- EVENT LISTINGS GRID -->
            <div class="event_listings">
                <?php
                // Get events
                $args = [
                    'post_type' => 'event_listing',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'meta_key' => '_event_start_date',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                    'meta_query' => [
                        [
                            'key' => '_event_start_date',
                            'value' => date('Y-m-d'),
                            'compare' => '>=',
                            'type' => 'DATE'
                        ]
                    ]
                ];
                
                $events_query = new WP_Query($args);
                
                if ($events_query->have_posts()):
                    while ($events_query->have_posts()): $events_query->the_post();
                        $event_id = get_the_ID();
                        
                        // Get data
                        $start_date = get_post_meta($event_id, '_event_start_date', true);
                        $event_location = get_post_meta($event_id, '_event_location', true);
                        $event_banner = get_post_meta($event_id, '_event_banner', true);
                        
                        // Get DJs from timetable with comprehensive validation
                        $timetable = get_post_meta($event_id, '_timetable', true);
                        $djs_names = [];
                        if (!empty($timetable) && is_array($timetable)) {
                            foreach ($timetable as $slot) {
                                if (isset($slot['dj']) && !empty($slot['dj'])) {
                                    $dj_id = $slot['dj'];

                                    if (is_numeric($dj_id)) {
                                        $dj_post = get_post($dj_id);
                                        if ($dj_post && $dj_post->post_status === 'publish') {
                                            $dj_name = get_post_meta($dj_id, '_dj_name', true);
                                            if (empty($dj_name)) {
                                                $dj_name = $dj_post->post_title;
                                            }
                                            if (!empty($dj_name)) {
                                                $djs_names[] = $dj_name;
                                            }
                                        }
                                    } else {
                                        // If dj_id is a string (DJ name), use it directly
                                        $djs_names[] = $dj_id;
                                    }
                                }
                            }
                        }
                        $dj_display = !empty($djs_names) ? implode(', ', $djs_names) : get_post_meta($event_id, '_dj_name', true);
                        
                        // Format date
                        $date_obj = DateTime::createFromFormat('Y-m-d', $start_date);
                        $day = $date_obj ? $date_obj->format('d') : '';
                        $month_num = $date_obj ? $date_obj->format('n') - 1 : 0;
                        $pt_months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
                        $month_pt = $pt_months[$month_num] ?? '';
                        
                        // Get categories
                        $categories = wp_get_post_terms($event_id, 'event_listing_category');
                        if (is_wp_error($categories)) {
                            $categories = [];
                        }
                        $category_slug = !empty($categories) ? $categories[0]->slug : 'general';
                        
                        // Get tags (sounds)
                        $tags = wp_get_post_terms($event_id, 'event_sounds');
                        if (is_wp_error($tags)) {
                            $tags = [];
                        }
                        
                        // Get banner URL
                        $banner_url = '';
                        if ($event_banner) {
                            $banner_url = is_numeric($event_banner) ? wp_get_attachment_url($event_banner) : $event_banner;
                        }
                        if (!$banner_url && has_post_thumbnail()) {
                            $banner_url = get_the_post_thumbnail_url($event_id, 'large');
                        }
                        if (!$banner_url) {
                            $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
                        }
                        ?>
                        
                        <a href="#" class="event_listing" data-event-id="<?php echo esc_attr($event_id); ?>" data-category="<?php echo esc_attr($category_slug); ?>" data-month-str="<?php echo esc_attr($month_pt); ?>">
                            <!-- Date box outside .picture -->
                            <div class="box-date-event">
                                <span class="date-day"><?php echo esc_html($day); ?></span>
                                <span class="date-month"><?php echo esc_html($month_pt); ?></span>
                            </div>
                            
                            <div class="picture">
                                <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                
                                <!-- Event card tags -->
                                <?php if (!empty($tags)): ?>
                                <div class="event-card-tags">
                                    <?php 
                                    $tag_count = 0;
                                    foreach ($tags as $tag):
                                        if ($tag_count >= 3) break;
                                    ?>
                                        <span><?php echo esc_html($tag->name); ?></span>
                                    <?php 
                                        $tag_count++;
                                    endforeach;
                                    ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="event-line">
                                <div class="box-info-event">
                                    <h2 class="event-li-title mb04rem"><?php the_title(); ?></h2>
                                    
                                    <?php if ($dj_display): ?>
                                    <p class="event-li-detail of-dj mb04rem">
                                        <i class="ri-sound-module-fill"></i>
                                        <span><?php echo esc_html($dj_display); ?></span>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($event_location): ?>
                                    <p class="event-li-detail of-location mb04rem">
                                        <i class="ri-map-pin-2-line"></i>
                                        <span><?php echo esc_html($event_location); ?></span>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                    echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
                endif;
                ?>
            </div>
            <!-- END EVENT LISTING GRID -->
            
            <!-- HIGHLIGHT BANNER (from latest blog post) -->
            <?php
            // Get latest post
            $latest_post_args = [
                'post_type' => 'post',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ];
            $latest_post_query = new WP_Query($latest_post_args);
            
            if ($latest_post_query->have_posts()):
                while ($latest_post_query->have_posts()): $latest_post_query->the_post();
                    $banner_image = get_the_post_thumbnail_url(get_the_ID(), 'full');
                    if (!$banner_image) {
                        $banner_image = 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop';
                    }
                    $excerpt = wp_trim_words(get_the_excerpt(), 30, '...');
                    if (!$excerpt) {
                        $excerpt = wp_trim_words(get_the_content(), 30, '...');
                    }
                    ?>
                    <section class="banner-ario-1-wrapper" style="margin-top: 80px;">
                        <img src="<?php echo esc_url($banner_image); ?>" class="ban-ario-1-img" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <div class="ban-ario-1-content">
                            <h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
                            <h2 class="ban-ario-1-titl"><?php the_title(); ?></h2>
                            <p class="ban-ario-1-txt">
                                <?php echo esc_html($excerpt); ?>
                            </p>
                            <a href="<?php the_permalink(); ?>" class="ban-ario-1-btn">
                                Saiba Mais <i class="ri-arrow-right-long-line"></i>
                            </a>
                        </div>
                    </section>
                    <?php
                endwhile;
                wp_reset_postdata();
            else:
                // Fallback se não tiver posts
                ?>
                <section class="banner-ario-1-wrapper" style="margin-top: 80px;">
                    <img src="https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop" class="ban-ario-1-img" alt="Upcoming Festival">
                    <div class="ban-ario-1-content">
                        <h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
                        <h2 class="ban-ario-1-titl">Retrospectiva Clubbe::rio 2026</h2>
                        <p class="ban-ario-1-txt">
                            A Retrospectiva Clubber 2026 está chegando! E em breve vamos liberar as primeiras novidades... Fique ligado, porque essa publicação promete celebrar tudo o que fez o coração da pista bater mais forte! Spoilers?
                        </p>
                        <a href="<?php echo home_url('/blog/'); ?>" class="ban-ario-1-btn">
                            Saiba Mais <i class="ri-arrow-right-long-line"></i>
                        </a>
                    </div>
                </section>
                <?php
            endif;
            ?>

        </div>
    </main>

    <!-- DARK MODE TOGGLE -->
    <div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Toggle dark mode">
        <i class="ri-sun-line"></i>
        <i class="ri-moon-line"></i>
    </div>

    <?php wp_footer(); ?>
    <script src="https://assets.apollo.rio.br/base.js"></script>
</body>
</html>

