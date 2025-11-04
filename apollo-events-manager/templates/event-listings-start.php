<?php
/**
 * Event Listings Start Wrapper
 * MATCHES ORIGINAL TEMPLATE STRUCTURE
 */

// Get taxonomies
$sounds_terms = get_terms([
    'taxonomy' => 'event_sounds',
    'hide_empty' => false,
]);
$sounds_terms = is_wp_error($sounds_terms) ? [] : $sounds_terms;
?>

<div class="discover-events-now-shortcode event-manager-shortcode-wrapper">
    <!-- Hero Section -->
    <section class="hero-section">
        <h1 class="title-page">Descubra os Próximos Eventos</h1>
        <p class="subtitle-page">
            Um novo <mark>hub digital que conecta cultura,</mark> tecnologia e experiências em tempo real... 
            <mark>O futuro da cultura carioca começa aqui!</mark>
        </p>
    </section>

    <!-- Filters and Search -->
    <div class="filters-and-search">
        <div class="event_types menutags">
            <button class="event-category menutag active" data-slug="all">
                <span class="xxall" id="xxall" style="opacity:1">Todos</span>
            </button>
            
            <?php
            // Get first 4 sounds for filter buttons
            $max_buttons = 4;
            $count = 0;
            foreach ($sounds_terms as $sound) {
                if ($count >= $max_buttons) break;
                printf(
                    '<button class="event-category menutag" data-slug="%s">%s</button>',
                    esc_attr($sound->slug),
                    esc_html($sound->name)
                );
                $count++;
            }
            ?>
            
            <!-- Date Picker -->
            <div class="date-chip" id="eventDatePicker">
                <button class="date-arrow" id="datePrev" type="button" aria-label="Mês anterior">‹</button>
                <span class="date-display" id="dateDisplay" aria-live="polite">
                    <?php echo date_i18n('M'); ?>
                </span>
                <button class="date-arrow" id="dateNext" type="button" aria-label="Próximo mês">›</button>
            </div>
            
            <!-- Layout Toggle -->
            <button class="layout-toggle" id="wpem-event-toggle-layout" type="button" aria-pressed="true" 
                    onclick="toggleLayout(this)" title="Events List View">
                <i class="ri-list-view" aria-hidden="true"></i>
                <span class="visually-hidden">Alternar layout</span>
            </button>
        </div>
        
        <!-- Search Bar -->
        <div class="controls-bar" id="apollo-controls-bar">
            <form class="box-search" id="eventSearchForm" role="search">
                <label class="visually-hidden" for="eventSearchInput">Procurar</label>
                <i class="ri-search-line" aria-hidden="true"></i>
                <input name="search_keywords" autocomplete="off" id="eventSearchInput" 
                       inputmode="search" placeholder="">
                <input name="post_type" type="hidden" value="event_listing">
            </form>
        </div>
    </div>
    
    <!-- Event Listings Container -->
    <div class="event_listings">
        <?php
        // Query events (future events only)
        $events = new WP_Query([
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
        ]);
        
        if ($events->have_posts()) :
            while ($events->have_posts()) : $events->the_post();
                $event_id = get_the_ID();
                
                // === NORMALIZE START DATE ===
                $start_date = get_post_meta($event_id, '_event_start_date', true);
                
                // Fallback: try alternative meta keys if empty
                if (empty($start_date)) {
                    $start_date = get_post_meta($event_id, 'event_start_date', true);
                    if (empty($start_date)) {
                        $start_date = get_post_meta($event_id, 'start_date', true);
                    }
                }
                
                // Ensure Y-m-d format
                if (!empty($start_date)) {
                    try {
                        $dt_test = DateTime::createFromFormat('Y-m-d', $start_date);
                        if (!$dt_test) {
                            // Try other formats
                            $dt_test = DateTime::createFromFormat('d/m/Y', $start_date);
                            if ($dt_test) {
                                $start_date = $dt_test->format('Y-m-d');
                            }
                        }
                    } catch (Exception $e) {
                        // Keep original if parsing fails
                    }
                }
                
                // === FORMAT DATE WITH FALLBACKS ===
                $day = '';
                $mon = '';
                
                // Try helper functions if they exist (for backwards compatibility)
                if (function_exists('apollo_get_day_from_date')) {
                    $day = apollo_get_day_from_date($start_date);
                }
                if (function_exists('apollo_get_month_str_from_date')) {
                    $mon = apollo_get_month_str_from_date($start_date);
                }
                
                // Defensive fallback using DateTime
                if (empty($day) || empty($mon)) {
                    try {
                        if (!empty($start_date)) {
                            $dt = new DateTime($start_date);
                            if (empty($day)) {
                                $day = $dt->format('d');
                            }
                            if (empty($mon)) {
                                $month_abbr = strtolower($dt->format('M'));
                                // Map to Portuguese
                                $month_map = [
                                    'jan' => 'jan', 'feb' => 'fev', 'mar' => 'mar',
                                    'apr' => 'abr', 'may' => 'mai', 'jun' => 'jun',
                                    'jul' => 'jul', 'aug' => 'ago', 'sep' => 'set',
                                    'oct' => 'out', 'nov' => 'nov', 'dec' => 'dez'
                                ];
                                $mon = isset($month_map[$month_abbr]) ? $month_map[$month_abbr] : $month_abbr;
                            }
                        }
                    } catch (Exception $e) {
                        $day = '';
                        $mon = '';
                    }
                }
                
                // === GET DJs ===
                $djs_names = array();
                
                // Try _event_dj_ids first (primary method)
                $dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
                if (!empty($dj_ids_raw)) {
                    $dj_ids = maybe_unserialize($dj_ids_raw);
                    if (is_array($dj_ids)) {
                        foreach ($dj_ids as $dj_id) {
                            $dj_id = intval($dj_id);
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
                        }
                    }
                }
                
                // Fallback: Try _timetable
                if (empty($djs_names)) {
                    $timetable = get_post_meta($event_id, '_timetable', true);
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
                                }
                            }
                        }
                    }
                }
                
                // Remove duplicates
                $djs_names = array_unique($djs_names);
                
                // === GET LOCAL/VENUE ===
                $venue_name = '';
                $venue_city = '';
                
                // Try _event_local_ids first (primary method)
                $local_id = get_post_meta($event_id, '_event_local_ids', true);
                if (empty($local_id)) {
                    $local_id = get_post_meta($event_id, '_event_local', true); // Fallback
                }
                
                if (!empty($local_id) && is_numeric($local_id)) {
                    $local_post = get_post($local_id);
                    if ($local_post && $local_post->post_status === 'publish') {
                        $venue_name = get_post_meta($local_id, '_local_name', true);
                        if (empty($venue_name)) {
                            $venue_name = $local_post->post_title;
                        }
                        $venue_city = get_post_meta($local_id, '_local_city', true);
                    }
                }
                
                // Fallback: direct location meta
                if (empty($venue_name)) {
                    $venue_name = get_post_meta($event_id, '_event_location', true);
                }
                
                // === GET OTHER DATA ===
                $start_time = get_post_meta($event_id, '_event_start_time', true);
                $tags = wp_get_post_terms($event_id, 'event_sounds');
                $tags = is_wp_error($tags) ? array() : $tags;
                $event_type_terms = get_the_terms($event_id, 'event_listing_category');
                $event_type_terms = is_wp_error($event_type_terms) ? array() : $event_type_terms;
                $category_slug = !empty($event_type_terms) ? $event_type_terms[0]->slug : 'general';
                
                // Format month string for data attribute (same as $mon)
                $month_str = $mon;
                ?>
                
                <a href="#"
                   class="event_listing"
                   data-event-id="<?php echo esc_attr($event_id); ?>"
                   data-category="<?php echo esc_attr($category_slug); ?>"
                   data-month-str="<?php echo esc_attr($month_str); ?>"
                   data-event-title="<?php echo esc_attr(get_the_title()); ?>"
                   data-event-date="<?php echo esc_attr($start_date); ?>"
                   data-event-venue="<?php echo esc_attr($venue_name); ?>"
                   data-event-djs="<?php echo esc_attr(implode(', ', $djs_names)); ?>"
                   style="display:block;">
                    
                    <div class="box-date-event">
                        <span class="date-day"><?php echo esc_html($day); ?></span>
                        <span class="date-month"><?php echo esc_html($mon); ?></span>
                    </div>
                    
                    <div class="picture">
                        <?php
                        $banner_url = '';
                        $event_banner = get_post_meta($event_id, '_event_banner', true);
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
                        <img src="<?php echo esc_url($banner_url); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             loading="lazy">
                        
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
                            
                            <p class="event-li-meta">
                                <?php if (!empty($venue_name)): ?>
                                    <span class="event-li-local">
                                        <?php echo esc_html($venue_name); ?>
                                        <?php if (!empty($venue_city)): ?>
                                            · <?php echo esc_html($venue_city); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($djs_names)): ?>
                                    <span class="event-li-djs">
                                        <?php echo esc_html(implode(' · ', $djs_names)); ?>
                                    </span>
                                <?php endif; ?>
                            </p>
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