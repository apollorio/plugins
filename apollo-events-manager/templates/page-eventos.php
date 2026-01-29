<?php
/**
 * Template: Page Eventos - Events Listing
 * =======================================
 * Path: apollo-events-manager/templates/page-eventos.php
 *
 * Events discovery page with grid listing using the OFFICIAL event card.
 * Opens event single page in fullscreen popup modal with exit button.
 *
 * URL Logic:
 * - /eventos/ → Shows grid of all events
 * - /eventos/?event={id} → Shows single event in popup
 * - /evento/{slug}/ → Direct URL opens as full page (no popup)
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

apollo_get_header_for_template('pagx_site');
?>

// Check if requesting specific event via query param (for popup state)
$popup_event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;

// Query events
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$args = array(
    'post_type'      => 'event_listing',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'meta_key'       => '_event_start_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => array(
        array(
            'key'     => '_event_start_date',
            'value'   => date( 'Y-m-d' ),
            'compare' => '>=',
            'type'    => 'DATE',
        ),
    ),
);

// Apply filters from query params - sanitize and unslash input
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public page filters
$category_filter = isset( $_GET['categoria'] ) ? sanitize_text_field( wp_unslash( $_GET['categoria'] ) ) : '';
$sound_filter    = isset( $_GET['sound'] ) ? sanitize_text_field( wp_unslash( $_GET['sound'] ) ) : '';
$month_filter    = isset( $_GET['mes'] ) ? absint( $_GET['mes'] ) : 0;
$year_filter     = isset( $_GET['ano'] ) ? absint( $_GET['ano'] ) : 0;
$search_query    = isset( $_GET['busca'] ) ? sanitize_text_field( wp_unslash( $_GET['busca'] ) ) : '';
// phpcs:enable

// Category filter
if ( $category_filter ) {
    $args['tax_query'][] = array(
        'taxonomy' => 'event_listing_type',
        'field'    => 'slug',
        'terms'    => $category_filter,
    );
}

// Sound/genre filter
if ( $sound_filter ) {
    $args['tax_query'][] = array(
        'taxonomy' => 'event_sounds',
        'field'    => 'slug',
        'terms'    => $sound_filter,
    );
}

// Month/year filter
if ( $month_filter && $year_filter ) {
    $start_date = sprintf( '%04d-%02d-01', $year_filter, $month_filter );
    $end_date   = date( 'Y-m-t', strtotime( $start_date ) );

    $args['meta_query'] = array(
        array(
            'key'     => '_event_start_date',
            'value'   => array( $start_date, $end_date ),
            'compare' => 'BETWEEN',
            'type'    => 'DATE',
        ),
    );
}

// Search filter
if ( $search_query ) {
    $args['s'] = $search_query;
}

$events_query = new WP_Query( $args );

// Get all available filters for UI
$event_types  = get_terms( array( 'taxonomy' => 'event_listing_type', 'hide_empty' => true ) );
$event_sounds = get_terms( array( 'taxonomy' => 'event_sounds', 'hide_empty' => true, 'number' => 20 ) );

// Current month/year for date picker
$current_month = $month_filter ?: (int) date( 'n' );
$current_year  = $year_filter ?: (int) date( 'Y' );
$month_names   = array(
    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
);
?>

<div class="apollo-eventos-page" id="apollo-eventos-page">
    <div class="eventos-container">

        <!-- Hero Section -->
        <section class="eventos-hero">
            <h1 class="eventos-title">
                <i class="ri-calendar-event-line"></i>
                <?php esc_html_e( 'Descubra', 'apollo-events-manager' ); ?>
                <mark><?php esc_html_e( 'Eventos', 'apollo-events-manager' ); ?></mark>
            </h1>
            <p class="eventos-subtitle">
                <?php esc_html_e( 'Explore os melhores eventos da cena eletrônica. Festas, festivais, showcases e muito mais.', 'apollo-events-manager' ); ?>
            </p>
        </section>

        <!-- Filters Section -->
        <section class="eventos-filters">

            <!-- Category Tags -->
            <div class="filter-tags">
                <button type="button"
                        class="filter-tag<?php echo empty( $category_filter ) ? ' active' : ''; ?>"
                        data-category="">
                    <?php esc_html_e( 'Todos', 'apollo-events-manager' ); ?>
                </button>
                <?php foreach ( $event_types as $type ) : ?>
                <button type="button"
                        class="filter-tag<?php echo $category_filter === $type->slug ? ' active' : ''; ?>"
                        data-category="<?php echo esc_attr( $type->slug ); ?>">
                    <?php echo esc_html( $type->name ); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Search & Date Controls -->
            <div class="filter-controls">
                <div class="filter-search">
                    <i class="ri-search-line"></i>
                    <input type="text"
                           id="eventosSearch"
                           placeholder="<?php esc_attr_e( 'Buscar evento, artista, local...', 'apollo-events-manager' ); ?>"
                           value="<?php echo esc_attr( $search_query ); ?>">
                </div>

                <div class="filter-date-picker">
                    <button type="button" class="date-arrow prev" data-direction="prev">
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                    <span class="date-display" data-month="<?php echo esc_attr( $current_month ); ?>" data-year="<?php echo esc_attr( $current_year ); ?>">
                        <?php echo esc_html( $month_names[ $current_month ] . ' ' . $current_year ); ?>
                    </span>
                    <button type="button" class="date-arrow next" data-direction="next">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>
            </div>

        </section>

        <!-- Events Grid -->
        <section class="eventos-grid-section">
            <?php if ( $events_query->have_posts() ) : ?>
            <div class="apollo-events-grid" id="eventsGrid">
                <?php
                $delay_index = 100;
                while ( $events_query->have_posts() ) :
                    $events_query->the_post();
                    $event_id = get_the_ID();

                    // Prepare event card data
                    $event_title    = get_post_meta( $event_id, '_event_title', true ) ?: get_the_title();
                    $event_url      = get_permalink();
                    $event_image    = get_post_meta( $event_id, '_event_banner', true );
                    $event_date     = get_post_meta( $event_id, '_event_start_date', true );
                    $event_location = get_post_meta( $event_id, '_event_location', true );
                    $event_coupon   = get_post_meta( $event_id, '_cupom_ario', true );

                    // Get location from event_local if not set directly
                    if ( empty( $event_location ) ) {
                        $local_ids = get_post_meta( $event_id, '_event_local_ids', true );
                        if ( ! empty( $local_ids ) ) {
                            $local_id = is_array( $local_ids ) ? $local_ids[0] : $local_ids;
                            $event_location = get_post_meta( $local_id, '_local_name', true ) ?: get_the_title( $local_id );
                        }
                    }

                    // Get DJs
                    $event_djs  = array();
                    $dj_slots   = get_post_meta( $event_id, '_event_dj_slots', true );
                    if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) {
                        foreach ( $dj_slots as $slot ) {
                            if ( ! empty( $slot['dj_id'] ) ) {
                                $dj_name = get_post_meta( $slot['dj_id'], '_dj_name', true ) ?: get_the_title( $slot['dj_id'] );
                                if ( $dj_name ) {
                                    $event_djs[] = $dj_name;
                                }
                            }
                        }
                    }

                    // Get sounds/genres
                    $event_sounds = array();
                    $sounds_terms = get_the_terms( $event_id, 'event_sounds' );
                    if ( $sounds_terms && ! is_wp_error( $sounds_terms ) ) {
                        $event_sounds = wp_list_pluck( $sounds_terms, 'name' );
                    }

                    // Check if Apollo partner
                    $is_apollo   = false; // Can be set via meta or taxonomy
                    $is_external = false;

                    // Fallback image
                    if ( empty( $event_image ) ) {
                        $event_image = get_the_post_thumbnail_url( $event_id, 'large' );
                    }

                    // Include the OFFICIAL event card template
                    $template_path = dirname( __FILE__ ) . '/parts/event/card.php';
                    if ( file_exists( $template_path ) ) {
                        include $template_path;
                    }

                    $delay_index += 100;
                    if ( $delay_index > 300 ) {
                        $delay_index = 100;
                    }
                endwhile;
                wp_reset_postdata();
                ?>
            </div>

            <!-- Pagination -->
            <?php if ( $events_query->max_num_pages > 1 ) : ?>
            <div class="eventos-pagination">
                <?php
                echo paginate_links( array(
                    'total'     => $events_query->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
                    'next_text' => '<i class="ri-arrow-right-s-line"></i>',
                ) );
                ?>
            </div>
            <?php endif; ?>

            <?php else : ?>
            <div class="eventos-empty">
                <i class="ri-calendar-event-line"></i>
                <h3><?php esc_html_e( 'Nenhum evento encontrado', 'apollo-events-manager' ); ?></h3>
                <p><?php esc_html_e( 'Não há eventos para os filtros selecionados. Tente ajustar sua busca.', 'apollo-events-manager' ); ?></p>
                <button type="button" class="btn-reset-filters">
                    <?php esc_html_e( 'Limpar Filtros', 'apollo-events-manager' ); ?>
                </button>
            </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<!-- Event Modal (Fullscreen Popup) -->
<div class="apollo-event-modal" id="apolloEventModal" aria-hidden="true">
    <button type="button" class="modal-close" id="modalClose" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-events-manager' ); ?>">
        <i class="ri-close-line"></i>
        <span><?php esc_html_e( 'Fechar', 'apollo-events-manager' ); ?></span>
    </button>
    <button type="button" class="modal-open-page" id="modalOpenPage" aria-label="<?php esc_attr_e( 'Abrir em nova página', 'apollo-events-manager' ); ?>">
        <i class="ri-external-link-line"></i>
    </button>
    <div class="modal-content" id="modalContent">
        <div class="modal-loader">
            <div class="loader-spinner"></div>
        </div>
    </div>
</div>

<?php
/**
 * Assets are enqueued via Apollo_Page_Eventos_Loader class.
 * Script localization is also handled there.
 * No need to duplicate here.
 */

apollo_get_footer_for_template('pagx_site');
