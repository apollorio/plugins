<?php
/**
 * Lists Module - Apollo Events Manager
 * Grid, Table, and Slider views for events
 *
 * @package Apollo\Events\Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lists Module Class
 * Provides various list views for events
 */
class Lists_Module extends Abstract_Module {

    /**
     * Get module ID
     *
     * @return string
     */
    public function get_id(): string {
        return 'lists';
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function get_name(): string {
        return __( 'Listas e Sliders', 'apollo-events-manager' );
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'Grid, tabela e slider de eventos com múltiplos layouts.', 'apollo-events-manager' );
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function get_version(): string {
        return '1.0.0';
    }

    /**
     * Is default enabled
     *
     * @return bool
     */
    public function is_default_enabled(): bool {
        return true;
    }

    /**
     * Initialize module
     *
     * @return void
     */
    public function init(): void {
        // Nothing special to init
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode( 'apollo_events_grid', [ $this, 'render_grid' ] );
        add_shortcode( 'apollo_events_list', [ $this, 'render_list' ] );
        add_shortcode( 'apollo_events_table', [ $this, 'render_table' ] );
        add_shortcode( 'apollo_events_slider', [ $this, 'render_slider' ] );
        add_shortcode( 'apollo_events_compact', [ $this, 'render_compact' ] );
        add_shortcode( 'apollo_featured_events', [ $this, 'render_featured' ] );
    }

    /**
     * Register assets
     *
     * @return void
     */
    public function register_assets(): void {
        wp_register_style(
            'apollo-lists',
            APOLLO_APRIO_URL . 'assets/css/lists.css',
            [],
            APOLLO_APRIO_VERSION
        );

        wp_register_script(
            'apollo-lists',
            APOLLO_APRIO_URL . 'assets/js/lists.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        wp_localize_script( 'apollo-lists', 'apolloLists', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'apollo_lists_nonce' ),
        ] );
    }

    /**
     * Enqueue assets
     *
     * @return void
     */
    public function enqueue_assets(): void {
        wp_enqueue_style( 'apollo-lists' );
        wp_enqueue_script( 'apollo-lists' );
    }

    /**
     * Query events with common parameters
     *
     * @param array $atts Shortcode attributes.
     * @return \WP_Query
     */
    private function query_events( array $atts ): \WP_Query {
        $args = [
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => absint( $atts['limit'] ?? 12 ),
            'paged'          => absint( $atts['paged'] ?? 1 ),
        ];

        // Order
        $orderby = $atts['orderby'] ?? 'event_date';
        if ( $orderby === 'event_date' ) {
            $args['meta_key'] = '_event_start_date';
            $args['orderby']  = 'meta_value';
            $args['order']    = $atts['order'] ?? 'ASC';
        } else {
            $args['orderby'] = $orderby;
            $args['order']   = $atts['order'] ?? 'DESC';
        }

        // Category filter
        if ( ! empty( $atts['category'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'event_category',
                    'field'    => 'slug',
                    'terms'    => array_map( 'sanitize_text_field', explode( ',', $atts['category'] ) ),
                ],
            ];
        }

        // Upcoming only
        if ( ( $atts['upcoming'] ?? 'yes' ) === 'yes' ) {
            $args['meta_query'][] = [
                'key'     => '_event_start_date',
                'value'   => current_time( 'Y-m-d' ),
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        // Past only
        if ( ( $atts['past'] ?? 'no' ) === 'yes' ) {
            $args['meta_query'][] = [
                'key'     => '_event_start_date',
                'value'   => current_time( 'Y-m-d' ),
                'compare' => '<',
                'type'    => 'DATE',
            ];
        }

        // Local filter
        if ( ! empty( $atts['local_id'] ) ) {
            $args['meta_query'][] = [
                'key'     => '_event_local_ids',
                'value'   => absint( $atts['local_id'] ),
                'compare' => '=',
            ];
        }

        // Featured filter
        if ( ( $atts['featured'] ?? 'no' ) === 'yes' ) {
            $args['meta_query'][] = [
                'key'     => '_event_featured',
                'value'   => '1',
                'compare' => '=',
            ];
        }

        return new \WP_Query( $args );
    }

    /**
     * Get event data for rendering
     *
     * @param \WP_Post $post Event post.
     * @return array
     */
    private function get_event_data( \WP_Post $post ): array {
        $start_date = get_post_meta( $post->ID, '_event_start_date', true );
        $start_time = get_post_meta( $post->ID, '_event_start_time', true );
        $local_id   = get_post_meta( $post->ID, '_event_local_ids', true );

        $local_name = '';
        if ( $local_id ) {
            $local = get_post( $local_id );
            if ( $local ) {
                $local_name = $local->post_title;
            }
        }

        // Parse date
        $parsed = [];
        if ( $start_date ) {
            $ts = strtotime( $start_date );
            if ( $ts ) {
                $months_pt = [ 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez' ];
                $parsed = [
                    'day'      => gmdate( 'd', $ts ),
                    'month'    => $months_pt[ (int) gmdate( 'n', $ts ) - 1 ],
                    'year'     => gmdate( 'Y', $ts ),
                    'weekday'  => gmdate( 'D', $ts ),
                    'full'     => gmdate( 'd/m/Y', $ts ),
                ];
            }
        }

        return [
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'excerpt'    => wp_trim_words( $post->post_content, 20 ),
            'permalink'  => get_permalink( $post->ID ),
            'thumbnail'  => get_the_post_thumbnail_url( $post->ID, 'medium' ),
            'start_date' => $start_date,
            'start_time' => $start_time,
            'date'       => $parsed,
            'local_name' => $local_name,
            'local_id'   => $local_id,
            'categories' => wp_get_post_terms( $post->ID, 'event_category', [ 'fields' => 'names' ] ),
        ];
    }

    /**
     * Render grid shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_grid( $atts ): string {
        $atts = shortcode_atts( [
            'limit'     => 12,
            'columns'   => 3,
            'category'  => '',
            'local_id'  => '',
            'upcoming'  => 'yes',
            'past'      => 'no',
            'orderby'   => 'event_date',
            'order'     => 'ASC',
            'show_date' => 'yes',
            'show_local' => 'yes',
            'class'     => '',
        ], $atts, 'apollo_events_grid' );

        $this->enqueue_assets();

        $query = $this->query_events( $atts );

        ob_start();
        ?>
        <div class="apollo-events-grid apollo-events-grid--cols-<?php echo esc_attr( $atts['columns'] ); ?> <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post();
                    $event = $this->get_event_data( get_post() );
                    ?>
                    <article class="apollo-event-card">
                        <a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-event-card__link">
                            <div class="apollo-event-card__image">
                                <?php if ( $event['thumbnail'] ) : ?>
                                    <img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy">
                                <?php else : ?>
                                    <div class="apollo-event-card__placeholder">
                                        <i class="ri-calendar-event-line"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $atts['show_date'] === 'yes' && ! empty( $event['date'] ) ) : ?>
                                <div class="apollo-event-card__date-badge">
                                    <span class="apollo-event-card__date-day"><?php echo esc_html( $event['date']['day'] ); ?></span>
                                    <span class="apollo-event-card__date-month"><?php echo esc_html( $event['date']['month'] ); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="apollo-event-card__content">
                                <h3 class="apollo-event-card__title"><?php echo esc_html( $event['title'] ); ?></h3>

                                <?php if ( $atts['show_local'] === 'yes' && $event['local_name'] ) : ?>
                                <p class="apollo-event-card__meta">
                                    <i class="ri-map-pin-line"></i>
                                    <?php echo esc_html( $event['local_name'] ); ?>
                                </p>
                                <?php endif; ?>

                                <?php if ( $event['start_time'] ) : ?>
                                <p class="apollo-event-card__meta">
                                    <i class="ri-time-line"></i>
                                    <?php echo esc_html( $event['start_time'] ); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : ?>
                <p class="apollo-events-empty"><?php esc_html_e( 'Nenhum evento encontrado.', 'apollo-events-manager' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render list shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_list( $atts ): string {
        $atts = shortcode_atts( [
            'limit'       => 10,
            'category'    => '',
            'local_id'    => '',
            'upcoming'    => 'yes',
            'past'        => 'no',
            'orderby'     => 'event_date',
            'order'       => 'ASC',
            'show_thumb'  => 'yes',
            'show_excerpt' => 'yes',
            'class'       => '',
        ], $atts, 'apollo_events_list' );

        $this->enqueue_assets();

        $query = $this->query_events( $atts );

        ob_start();
        ?>
        <div class="apollo-events-list <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post();
                    $event = $this->get_event_data( get_post() );
                    ?>
                    <article class="apollo-event-row">
                        <?php if ( $atts['show_thumb'] === 'yes' ) : ?>
                        <div class="apollo-event-row__image">
                            <?php if ( $event['thumbnail'] ) : ?>
                                <img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy">
                            <?php else : ?>
                                <div class="apollo-event-row__placeholder">
                                    <i class="ri-calendar-event-line"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="apollo-event-row__date">
                            <?php if ( ! empty( $event['date'] ) ) : ?>
                            <span class="apollo-event-row__date-day"><?php echo esc_html( $event['date']['day'] ); ?></span>
                            <span class="apollo-event-row__date-month"><?php echo esc_html( $event['date']['month'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="apollo-event-row__content">
                            <h3 class="apollo-event-row__title">
                                <a href="<?php echo esc_url( $event['permalink'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
                            </h3>

                            <div class="apollo-event-row__meta">
                                <?php if ( $event['start_time'] ) : ?>
                                <span><i class="ri-time-line"></i> <?php echo esc_html( $event['start_time'] ); ?></span>
                                <?php endif; ?>
                                <?php if ( $event['local_name'] ) : ?>
                                <span><i class="ri-map-pin-line"></i> <?php echo esc_html( $event['local_name'] ); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ( $atts['show_excerpt'] === 'yes' && $event['excerpt'] ) : ?>
                            <p class="apollo-event-row__excerpt"><?php echo esc_html( $event['excerpt'] ); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="apollo-event-row__action">
                            <a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-btn apollo-btn--outline">
                                <?php esc_html_e( 'Ver Evento', 'apollo-events-manager' ); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : ?>
                <p class="apollo-events-empty"><?php esc_html_e( 'Nenhum evento encontrado.', 'apollo-events-manager' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render table shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_table( $atts ): string {
        $atts = shortcode_atts( [
            'limit'     => 20,
            'category'  => '',
            'local_id'  => '',
            'upcoming'  => 'yes',
            'past'      => 'no',
            'orderby'   => 'event_date',
            'order'     => 'ASC',
            'class'     => '',
        ], $atts, 'apollo_events_table' );

        $this->enqueue_assets();

        $query = $this->query_events( $atts );

        ob_start();
        ?>
        <div class="apollo-events-table-wrapper <?php echo esc_attr( $atts['class'] ); ?>">
            <table class="apollo-events-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Data', 'apollo-events-manager' ); ?></th>
                        <th><?php esc_html_e( 'Evento', 'apollo-events-manager' ); ?></th>
                        <th><?php esc_html_e( 'Local', 'apollo-events-manager' ); ?></th>
                        <th><?php esc_html_e( 'Horário', 'apollo-events-manager' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $query->have_posts() ) : ?>
                        <?php while ( $query->have_posts() ) : $query->the_post();
                            $event = $this->get_event_data( get_post() );
                            ?>
                            <tr>
                                <td class="apollo-events-table__date">
                                    <?php echo esc_html( $event['date']['full'] ?? '—' ); ?>
                                </td>
                                <td class="apollo-events-table__title">
                                    <a href="<?php echo esc_url( $event['permalink'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
                                </td>
                                <td class="apollo-events-table__local">
                                    <?php echo esc_html( $event['local_name'] ?: '—' ); ?>
                                </td>
                                <td class="apollo-events-table__time">
                                    <?php echo esc_html( $event['start_time'] ?: '—' ); ?>
                                </td>
                                <td class="apollo-events-table__action">
                                    <a href="<?php echo esc_url( $event['permalink'] ); ?>">
                                        <i class="ri-arrow-right-line"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="apollo-events-table__empty">
                                <?php esc_html_e( 'Nenhum evento encontrado.', 'apollo-events-manager' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render slider shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_slider( $atts ): string {
        $atts = shortcode_atts( [
            'limit'      => 6,
            'category'   => '',
            'local_id'   => '',
            'upcoming'   => 'yes',
            'past'       => 'no',
            'autoplay'   => 'yes',
            'interval'   => 5000,
            'show_nav'   => 'yes',
            'show_dots'  => 'yes',
            'class'      => '',
        ], $atts, 'apollo_events_slider' );

        $this->enqueue_assets();

        $query = $this->query_events( $atts );

        ob_start();
        ?>
        <div class="apollo-events-slider <?php echo esc_attr( $atts['class'] ); ?>"
             data-autoplay="<?php echo esc_attr( $atts['autoplay'] ); ?>"
             data-interval="<?php echo esc_attr( $atts['interval'] ); ?>">

            <div class="apollo-events-slider__track">
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        $event = $this->get_event_data( get_post() );
                        ?>
                        <div class="apollo-events-slider__slide">
                            <article class="apollo-event-slide">
                                <div class="apollo-event-slide__image">
                                    <?php if ( $event['thumbnail'] ) : ?>
                                        <img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy">
                                    <?php endif; ?>
                                    <div class="apollo-event-slide__overlay"></div>
                                </div>

                                <div class="apollo-event-slide__content">
                                    <?php if ( ! empty( $event['date'] ) ) : ?>
                                    <div class="apollo-event-slide__date">
                                        <span class="apollo-event-slide__date-day"><?php echo esc_html( $event['date']['day'] ); ?></span>
                                        <span class="apollo-event-slide__date-month"><?php echo esc_html( $event['date']['month'] ); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <h3 class="apollo-event-slide__title"><?php echo esc_html( $event['title'] ); ?></h3>

                                    <?php if ( $event['local_name'] ) : ?>
                                    <p class="apollo-event-slide__meta">
                                        <i class="ri-map-pin-line"></i>
                                        <?php echo esc_html( $event['local_name'] ); ?>
                                    </p>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-btn apollo-btn--primary">
                                        <?php esc_html_e( 'Ver Evento', 'apollo-events-manager' ); ?>
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php endif; ?>
            </div>

            <?php if ( $atts['show_nav'] === 'yes' ) : ?>
            <button type="button" class="apollo-events-slider__nav apollo-events-slider__nav--prev" aria-label="<?php esc_attr_e( 'Anterior', 'apollo-events-manager' ); ?>">
                <i class="ri-arrow-left-s-line"></i>
            </button>
            <button type="button" class="apollo-events-slider__nav apollo-events-slider__nav--next" aria-label="<?php esc_attr_e( 'Próximo', 'apollo-events-manager' ); ?>">
                <i class="ri-arrow-right-s-line"></i>
            </button>
            <?php endif; ?>

            <?php if ( $atts['show_dots'] === 'yes' ) : ?>
            <div class="apollo-events-slider__dots"></div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render compact list shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_compact( $atts ): string {
        $atts = shortcode_atts( [
            'limit'    => 5,
            'category' => '',
            'upcoming' => 'yes',
            'class'    => '',
        ], $atts, 'apollo_events_compact' );

        $this->enqueue_assets();

        $query = $this->query_events( $atts );

        ob_start();
        ?>
        <div class="apollo-events-compact <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post();
                    $event = $this->get_event_data( get_post() );
                    ?>
                    <a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-event-compact">
                        <div class="apollo-event-compact__date">
                            <span class="apollo-event-compact__day"><?php echo esc_html( $event['date']['day'] ?? '—' ); ?></span>
                            <span class="apollo-event-compact__month"><?php echo esc_html( $event['date']['month'] ?? '' ); ?></span>
                        </div>
                        <div class="apollo-event-compact__info">
                            <span class="apollo-event-compact__title"><?php echo esc_html( $event['title'] ); ?></span>
                            <span class="apollo-event-compact__meta">
                                <?php if ( $event['start_time'] ) : ?>
                                    <?php echo esc_html( $event['start_time'] ); ?>
                                <?php endif; ?>
                                <?php if ( $event['local_name'] ) : ?>
                                    • <?php echo esc_html( $event['local_name'] ); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : ?>
                <p class="apollo-events-empty"><?php esc_html_e( 'Nenhum evento.', 'apollo-events-manager' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render featured events shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_featured( $atts ): string {
        $atts = shortcode_atts( [
            'limit'    => 3,
            'category' => '',
            'featured' => 'yes',
            'upcoming' => 'yes',
            'class'    => '',
        ], $atts, 'apollo_featured_events' );

        $this->enqueue_assets();

        $query = $this->query_events( $atts );

        ob_start();
        ?>
        <div class="apollo-featured-events <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $query->have_posts() ) : ?>
                <?php $count = 0; while ( $query->have_posts() ) : $query->the_post();
                    $event = $this->get_event_data( get_post() );
                    $is_main = $count === 0;
                    ?>
                    <article class="apollo-featured-event <?php echo $is_main ? 'apollo-featured-event--main' : ''; ?>">
                        <a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-featured-event__link">
                            <div class="apollo-featured-event__image">
                                <?php if ( $event['thumbnail'] ) : ?>
                                    <img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="apollo-featured-event__overlay"></div>
                            </div>

                            <div class="apollo-featured-event__content">
                                <?php if ( ! empty( $event['date'] ) ) : ?>
                                <div class="apollo-featured-event__date">
                                    <?php echo esc_html( $event['date']['day'] . ' ' . $event['date']['month'] ); ?>
                                </div>
                                <?php endif; ?>

                                <h3 class="apollo-featured-event__title"><?php echo esc_html( $event['title'] ); ?></h3>

                                <?php if ( $event['local_name'] ) : ?>
                                <p class="apollo-featured-event__meta">
                                    <i class="ri-map-pin-line"></i>
                                    <?php echo esc_html( $event['local_name'] ); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php $count++; endwhile; wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema(): array {
        return [
            'default_view' => [
                'type'    => 'select',
                'label'   => __( 'Visualização padrão', 'apollo-events-manager' ),
                'options' => [
                    'grid'   => __( 'Grid', 'apollo-events-manager' ),
                    'list'   => __( 'Lista', 'apollo-events-manager' ),
                    'table'  => __( 'Tabela', 'apollo-events-manager' ),
                ],
                'default' => 'grid',
            ],
            'grid_columns' => [
                'type'    => 'number',
                'label'   => __( 'Colunas do grid', 'apollo-events-manager' ),
                'default' => 3,
                'min'     => 1,
                'max'     => 6,
            ],
            'items_per_page' => [
                'type'    => 'number',
                'label'   => __( 'Itens por página', 'apollo-events-manager' ),
                'default' => 12,
                'min'     => 1,
                'max'     => 50,
            ],
        ];
    }
}
