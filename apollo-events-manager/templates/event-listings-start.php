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
        <h1 class="title-page">Experience Tomorrow's Events</h1>
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