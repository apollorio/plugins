<?php
/**
 * Template: Portal de Eventos Apollo
 * Baseado 100% no CodePen raxqVGR
 * URL: https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR
 * 
 * STRICT MODE: Este template é SEMPRE usado para /eventos/, independente do tema.
 */

defined('ABSPATH') || exit;

if (!function_exists('apollo_eve_parse_start_date')) {
    /**
     * Aceita _event_start_date em "Y-m-d", "Y-m-d H:i:s" ou o que strtotime() aceitar.
     * Retorna array com:
     *  - 'timestamp'
     *  - 'day'        => "22"
     *  - 'month_pt'   => "jan", "fev", ...
     *  - 'iso_date'   => "Y-m-d"
     *  - 'iso_dt'     => "Y-m-d H:i:s"
     */
    function apollo_eve_parse_start_date($raw) {
        $raw = trim((string) $raw);
        
        if ($raw === '') {
            return [
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            ];
        }
        
        // 1) tenta parser direto
        $ts = strtotime($raw);
        
        // 2) fallback: se vier só "Y-m-d", garante datetime
        if (!$ts) {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            if ($dt instanceof DateTime) {
                $ts = $dt->getTimestamp();
            }
        }
        
        if (!$ts) {
            // nada deu certo
            return [
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            ];
        }
        
        $pt_months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $month_idx = (int) date_i18n('n', $ts) - 1;
        
        return [
            'timestamp' => $ts,
            'day'       => date_i18n('d', $ts),
            'month_pt'  => $pt_months[$month_idx] ?? '',
            'iso_date'  => date_i18n('Y-m-d', $ts),
            'iso_dt'    => date_i18n('Y-m-d H:i:s', $ts),
        ];
    }
}

get_header(); // Use WordPress header
?>

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
                // ============================================
                // PERFORMANCE: Query otimizada com cache
                // ============================================
                $cache_key = 'apollo_upcoming_events_' . date('Ymd');
                $events_data = get_transient($cache_key);
                
                if (false === $events_data) {
                    // Query otimizada: LIMITE de 50 eventos (não -1)
                    $now_mysql = current_time('mysql');
                    $args = array(
                        'post_type'      => 'event_listing',
                        'posts_per_page' => 50, // LIMITE: próximos 50 eventos
                        'post_status'    => 'publish',
                        'meta_key'       => '_event_start_date',
                        'orderby'        => 'meta_value',
                        'order'          => 'ASC',
                        'meta_query'     => array(
                            array(
                                'key'     => '_event_start_date',
                                'value'   => $now_mysql,
                                'compare' => '>=',
                                'type'    => 'DATETIME',
                            ),
                        ),
                    );
                    
                    $events_query = new WP_Query($args);
                    
                    // PRÉ-CARREGAR TODOS OS METAS (evita N+1 queries)
                    if ($events_query->have_posts()) {
                        $event_ids = wp_list_pluck($events_query->posts, 'ID');
                        update_meta_cache('post', $event_ids);
                    }
                    
                    // Salvar em transient por 5 minutos
                    set_transient($cache_key, $events_query, 5 * MINUTE_IN_SECONDS);
                    $events_data = $events_query;
                } else {
                    $events_query = $events_data;
                }
                
                // Error handling: prevent white screen if DB fails
                if (is_wp_error($events_query)) {
                    error_log('❌ Apollo: WP_Query error in portal-discover: ' . $events_query->get_error_message());
                    echo '<p class="no-events-found">Erro ao carregar eventos. Tente novamente.</p>';
                } elseif ($events_query->have_posts()) {
                    while ($events_query->have_posts()) {
                        $events_query->the_post();
                        $event_id = get_the_ID();
                        
                        // -------- META BÁSICA (já está no cache) --------
                        $start_date_raw   = get_post_meta($event_id, '_event_start_date', true);
                        $event_location_r = get_post_meta($event_id, '_event_location', true);
                        $event_banner     = get_post_meta($event_id, '_event_banner', true);
                        
                        // -------- DATA (usa helper, tolerante a formatos) --------
                        $date_info  = apollo_eve_parse_start_date($start_date_raw);
                        $day        = $date_info['day'];
                        $month_pt   = $date_info['month_pt'];
                        
                        // ============================================
                        // DJs: LÓGICA ROBUSTA COM FALLBACKS
                        // ============================================
                        $djs_names = array();
                        
                        // Tentativa 1: _timetable
                        $timetable = get_post_meta($event_id, '_timetable', true);
                        if (!empty($timetable) && is_array($timetable)) {
                            foreach ($timetable as $slot) {
                                if (empty($slot['dj'])) {
                                    continue;
                                }
                                
                                if (is_numeric($slot['dj'])) {
                                    // É um post de DJ
                                    $dj_name = get_post_meta($slot['dj'], '_dj_name', true);
                                    if (!$dj_name) {
                                        $dj_post = get_post($slot['dj']);
                                        $dj_name = $dj_post ? $dj_post->post_title : '';
                                    }
                                } else {
                                    // É string direta
                                    $dj_name = (string) $slot['dj'];
                                }
                                
                                if (!empty($dj_name)) {
                                    $djs_names[] = trim($dj_name);
                                }
                            }
                        }
                        
                        // Tentativa 2: _dj_name direto (fallback)
                        if (empty($djs_names)) {
                            $dj_meta = get_post_meta($event_id, '_dj_name', true);
                            if ($dj_meta) {
                                $djs_names[] = trim($dj_meta);
                            }
                        }
                        
                        // Tentativa 3: Buscar relationships (se usa meta _event_djs)
                        if (empty($djs_names)) {
                            $related_djs = get_post_meta($event_id, '_event_djs', true);
                            if (is_array($related_djs)) {
                                foreach ($related_djs as $dj_id) {
                                    $dj_name = get_post_meta($dj_id, '_dj_name', true);
                                    if ($dj_name) {
                                        $djs_names[] = trim($dj_name);
                                    }
                                }
                            }
                        }
                        
                        // DEBUG: log DJs encontrados
                        if (empty($djs_names)) {
                            error_log("❌ Apollo: Evento #{$event_id} sem DJs");
                        }
                        
                        // Remover duplicados e valores vazios
                        $djs_names = array_values(array_unique(array_filter($djs_names)));
                        
                        // Formatar display de DJs - SEMPRE tem algo
                        if (!empty($djs_names)) {
                            $max_visible  = 3;
                            $visible      = array_slice($djs_names, 0, $max_visible);
                            $remaining    = max(count($djs_names) - $max_visible, 0);
                            
                            $dj_display = esc_html($visible[0]);
                            if (count($visible) > 1) {
                                $rest = array_slice($visible, 1);
                                $dj_display .= ', ' . esc_html(implode(', ', $rest));
                            }
                            if ($remaining > 0) {
                                $dj_display .= ' +' . $remaining;
                            }
                        } else {
                            $dj_display = 'Line-up em breve';
                        }
                        
                        // ============================================
                        // LOCAL: LÓGICA ROBUSTA COM VALIDAÇÃO
                        // ============================================
                        $event_location      = '';
                        $event_location_area = '';
                        
                        if (!empty($event_location_r)) {
                            if (strpos($event_location_r, '|') !== false) {
                                list($event_location, $event_location_area) = array_map('trim', explode('|', $event_location_r, 2));
                            } else {
                                $event_location = trim($event_location_r);
                            }
                        }
                        
                        // DEBUG: log local
                        if (empty($event_location)) {
                            error_log("⚠️ Apollo: Evento #{$event_id} sem local");
                        }
                        
                        // -------- CATEGORIA / TAGS --------
                        $categories = wp_get_post_terms($event_id, 'event_listing_category');
                        if (is_wp_error($categories)) {
                            $categories = array();
                        }
                        $category_slug = !empty($categories) ? $categories[0]->slug : 'general';
                        
                        $tags = wp_get_post_terms($event_id, 'event_sounds');
                        if (is_wp_error($tags)) {
                            $tags = array();
                        }
                        
                        // -------- BANNER --------
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
                        
                        <a href="#"
                           class="event_listing"
                           data-event-id="<?php echo esc_attr($event_id); ?>"
                           data-category="<?php echo esc_attr($category_slug); ?>"
                           data-month-str="<?php echo esc_attr($month_pt); ?>">
                            
                            <!-- Date box outside .picture -->
                            <div class="box-date-event">
                                <span class="date-day"><?php echo esc_html($day); ?></span>
                                <span class="date-month"><?php echo esc_html($month_pt); ?></span>
                            </div>
                            
                            <div class="picture">
                                <img src="<?php echo esc_url($banner_url); ?>"
                                     alt="<?php echo esc_attr(get_the_title()); ?>"
                                     loading="lazy">
                                
                                <?php if (!empty($tags)): ?>
                                    <div class="event-card-tags">
                                        <?php
                                        $tag_count = 0;
                                        foreach ($tags as $tag):
                                            if ($tag_count >= 3) {
                                                break;
                                            }
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
                                    
                                    <!-- DJs - SEMPRE EXIBIDO -->
                                    <p class="event-li-detail of-dj mb04rem">
                                        <i class="ri-sound-module-fill"></i>
                                        <span><?php echo esc_html($dj_display); ?></span>
                                    </p>
                                    
                                    <!-- Local - EXIBIDO SE EXISTIR -->
                                    <?php if (!empty($event_location)): ?>
                                    <p class="event-li-detail of-location mb04rem">
                                        <i class="ri-map-pin-2-line"></i>
                                        <span class="event-location-name"><?php echo esc_html($event_location); ?></span>
                                        <?php if (!empty($event_location_area)): ?>
                                            <span class="event-location-area">(<?php echo esc_html($event_location_area); ?>)</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
                }
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
        
        <!-- Apollo Event Modal Container (for lightbox JS) -->
        <div id="apollo-event-modal" class="apollo-event-modal" aria-hidden="true"></div>
        
    </main>

    <!-- DARK MODE TOGGLE -->
    <div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Alternar modo escuro">
        <i class="ri-sun-line"></i>
        <i class="ri-moon-line"></i>
    </div>

<?php get_footer(); // Use WordPress footer ?>

