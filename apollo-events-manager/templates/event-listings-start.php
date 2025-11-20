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
                    data-layout="list" title="Events List View">
                <i class="ri-list-check-2" aria-hidden="true"></i>
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
        
        // ✅ Error handling para WP_Query
        if (is_wp_error($events)) {
            error_log('Apollo: WP_Query error em event-listings-start: ' . $events->get_error_message());
            echo '<p class="error">Erro ao carregar eventos. Tente novamente.</p>';
            return;
        }
        
        if (!$events->have_posts()) {
            echo '<p>Nenhum evento encontrado.</p>';
            return;
        }
        
        if ($events->have_posts()) :
            while ($events->have_posts()) : $events->the_post();
                $event_id = get_the_ID();
                
                // === NORMALIZE START DATE ===
                $start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
                
                // Fallback: try alternative meta keys if empty
                if (empty($start_date)) {
                    $start_date = apollo_get_post_meta($event_id, 'event_start_date', true);
                    if (empty($start_date)) {
                        $start_date = apollo_get_post_meta($event_id, 'start_date', true);
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
                
                // Check layout preference (grid or list)
                $layout_mode = 'grid'; // Default
                if (isset($_COOKIE['apollo_events_layout'])) {
                    $layout_mode = sanitize_text_field($_COOKIE['apollo_events_layout']);
                } elseif (function_exists('get_stored_layout')) {
                    // Try to get from JS localStorage via inline script
                    $layout_mode = 'grid'; // Fallback
                }
                
                // Use appropriate template based on layout
                if ($layout_mode === 'list') {
                    // List view template
                    $list_view_path = defined('APOLLO_WPEM_PATH') 
                        ? APOLLO_WPEM_PATH . 'templates/event-list-view.php'
                        : plugin_dir_path(__FILE__) . 'event-list-view.php';
                    
                    if (file_exists($list_view_path)) {
                        $event_object = get_post($event_id);
                        include $list_view_path;
                    } else {
                        // Fallback to card view if list template doesn't exist
                        $event_card_path = defined('APOLLO_WPEM_PATH') 
                            ? APOLLO_WPEM_PATH . 'templates/event-card.php'
                            : plugin_dir_path(__FILE__) . 'event-card.php';
                        if (file_exists($event_card_path)) {
                            include $event_card_path;
                        }
                    }
                } else {
                    // Grid view template (default)
                    // ✅ CONSOLIDATED: Use event-card.php template instead of duplicated code
                    // All logic (DJs, Local, Date, Banner) is now centralized in event-card.php
                    // This eliminates ~200 lines of duplicated code
                    $event_card_path = defined('APOLLO_WPEM_PATH') 
                        ? APOLLO_WPEM_PATH . 'templates/event-card.php'
                        : plugin_dir_path(__FILE__) . 'event-card.php';
                    
                    if (file_exists($event_card_path)) {
                        include $event_card_path;
                    } else {
                        // Fallback if event-card.php doesn't exist
                        echo '<!-- ERROR: event-card.php template not found -->';
                    }
                }
            endwhile;
            wp_reset_postdata();
        else:
            echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
        endif;
        ?>