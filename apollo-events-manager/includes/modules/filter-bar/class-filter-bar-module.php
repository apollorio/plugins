<?php
/**
 * Filter Bar Module - Apollo Events Manager
 * Advanced filtering for events with category, date, location filters
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
 * Filter Bar Module Class
 * Provides advanced filtering UI for events
 */
class Filter_Bar_Module extends Abstract_Module {

    /**
     * Get module ID
     *
     * @return string
     */
    public function get_id(): string {
        return 'filter-bar';
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function get_name(): string {
        return __( 'Barra de Filtros', 'apollo-events-manager' );
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'Filtros avançados para eventos: categoria, data, local, DJs.', 'apollo-events-manager' );
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
        // Nothing to init - shortcodes registered via register_shortcodes
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode( 'apollo_filter_bar', [ $this, 'render_filter_bar' ] );
        add_shortcode( 'apollo_filter_sidebar', [ $this, 'render_filter_sidebar' ] );
    }

    /**
     * Register assets
     *
     * @return void
     */
    public function register_assets(): void {
        wp_register_style(
            'apollo-filter-bar',
            APOLLO_APRIO_URL . 'assets/css/filter-bar.css',
            [],
            APOLLO_APRIO_VERSION
        );

        wp_register_script(
            'apollo-filter-bar',
            APOLLO_APRIO_URL . 'assets/js/filter-bar.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        wp_localize_script( 'apollo-filter-bar', 'apolloFilterBar', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'apollo_filter_nonce' ),
            'i18n'     => [
                'loading'   => __( 'Carregando...', 'apollo-events-manager' ),
                'no_events' => __( 'Nenhum evento encontrado.', 'apollo-events-manager' ),
                'error'     => __( 'Erro ao carregar eventos.', 'apollo-events-manager' ),
            ],
        ] );
    }

    /**
     * Enqueue assets
     *
     * @return void
     */
    public function enqueue_assets(): void {
        wp_enqueue_style( 'apollo-filter-bar' );
        wp_enqueue_script( 'apollo-filter-bar' );
    }

    /**
     * Render filter bar shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_filter_bar( $atts ): string {
        $atts = shortcode_atts( [
            'show_search'     => 'yes',
            'show_category'   => 'yes',
            'show_date'       => 'yes',
            'show_location'   => 'yes',
            'show_dj'         => 'no',
            'layout'          => 'horizontal', // horizontal, vertical
            'target'          => '', // ID of container to update
            'class'           => '',
        ], $atts, 'apollo_filter_bar' );

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="apollo-filter-bar apollo-filter-bar--<?php echo esc_attr( $atts['layout'] ); ?> <?php echo esc_attr( $atts['class'] ); ?>"
             data-target="<?php echo esc_attr( $atts['target'] ); ?>">

            <form class="apollo-filter-form" method="get">

                <?php if ( $atts['show_search'] === 'yes' ) : ?>
                <div class="apollo-filter-field apollo-filter-field--search">
                    <label for="apollo-search" class="sr-only"><?php esc_html_e( 'Buscar', 'apollo-events-manager' ); ?></label>
                    <div class="apollo-input-wrapper">
                        <i class="ri-search-line"></i>
                        <input type="text"
                               id="apollo-search"
                               name="s"
                               placeholder="<?php esc_attr_e( 'Buscar eventos...', 'apollo-events-manager' ); ?>"
                               value="<?php echo esc_attr( get_query_var( 's' ) ); ?>">
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_category'] === 'yes' ) : ?>
                <div class="apollo-filter-field apollo-filter-field--category">
                    <label for="apollo-category" class="sr-only"><?php esc_html_e( 'Categoria', 'apollo-events-manager' ); ?></label>
                    <select id="apollo-category" name="event_category">
                        <option value=""><?php esc_html_e( 'Todas as categorias', 'apollo-events-manager' ); ?></option>
                        <?php
                        $categories = get_terms( [
                            'taxonomy'   => 'event_category',
                            'hide_empty' => true,
                        ] );
                        if ( ! is_wp_error( $categories ) ) {
                            foreach ( $categories as $cat ) {
                                printf(
                                    '<option value="%s" %s>%s (%d)</option>',
                                    esc_attr( $cat->slug ),
                                    selected( get_query_var( 'event_category' ), $cat->slug, false ),
                                    esc_html( $cat->name ),
                                    (int) $cat->count
                                );
                            }
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_date'] === 'yes' ) : ?>
                <div class="apollo-filter-field apollo-filter-field--date">
                    <label for="apollo-date-from" class="sr-only"><?php esc_html_e( 'Data início', 'apollo-events-manager' ); ?></label>
                    <input type="date"
                           id="apollo-date-from"
                           name="date_from"
                           placeholder="<?php esc_attr_e( 'De', 'apollo-events-manager' ); ?>"
                           value="<?php echo esc_attr( sanitize_text_field( $_GET['date_from'] ?? '' ) ); ?>">
                    <span class="apollo-filter-separator">—</span>
                    <label for="apollo-date-to" class="sr-only"><?php esc_html_e( 'Data fim', 'apollo-events-manager' ); ?></label>
                    <input type="date"
                           id="apollo-date-to"
                           name="date_to"
                           placeholder="<?php esc_attr_e( 'Até', 'apollo-events-manager' ); ?>"
                           value="<?php echo esc_attr( sanitize_text_field( $_GET['date_to'] ?? '' ) ); ?>">
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_location'] === 'yes' ) : ?>
                <div class="apollo-filter-field apollo-filter-field--location">
                    <label for="apollo-location" class="sr-only"><?php esc_html_e( 'Local', 'apollo-events-manager' ); ?></label>
                    <select id="apollo-location" name="local_id">
                        <option value=""><?php esc_html_e( 'Todos os locais', 'apollo-events-manager' ); ?></option>
                        <?php
                        $locals = get_posts( [
                            'post_type'      => 'event_local',
                            'posts_per_page' => -1,
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                            'post_status'    => 'publish',
                        ] );
                        $current_local = absint( $_GET['local_id'] ?? 0 );
                        foreach ( $locals as $local ) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                (int) $local->ID,
                                selected( $current_local, $local->ID, false ),
                                esc_html( $local->post_title )
                            );
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_dj'] === 'yes' ) : ?>
                <div class="apollo-filter-field apollo-filter-field--dj">
                    <label for="apollo-dj" class="sr-only"><?php esc_html_e( 'DJ', 'apollo-events-manager' ); ?></label>
                    <select id="apollo-dj" name="dj_id">
                        <option value=""><?php esc_html_e( 'Todos os DJs', 'apollo-events-manager' ); ?></option>
                        <?php
                        $djs = get_posts( [
                            'post_type'      => 'event_dj',
                            'posts_per_page' => -1,
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                            'post_status'    => 'publish',
                        ] );
                        $current_dj = absint( $_GET['dj_id'] ?? 0 );
                        foreach ( $djs as $dj ) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                (int) $dj->ID,
                                selected( $current_dj, $dj->ID, false ),
                                esc_html( $dj->post_title )
                            );
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="apollo-filter-actions">
                    <button type="submit" class="apollo-btn apollo-btn--primary">
                        <i class="ri-filter-line"></i>
                        <?php esc_html_e( 'Filtrar', 'apollo-events-manager' ); ?>
                    </button>
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'event_listing' ) ); ?>" class="apollo-btn apollo-btn--ghost">
                        <i class="ri-refresh-line"></i>
                        <?php esc_html_e( 'Limpar', 'apollo-events-manager' ); ?>
                    </a>
                </div>

            </form>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render filter sidebar shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_filter_sidebar( $atts ): string {
        $atts = shortcode_atts( [
            'title'           => __( 'Filtrar Eventos', 'apollo-events-manager' ),
            'show_search'     => 'yes',
            'show_category'   => 'yes',
            'show_date'       => 'yes',
            'show_location'   => 'yes',
            'show_upcoming'   => 'yes',
            'class'           => '',
        ], $atts, 'apollo_filter_sidebar' );

        $this->enqueue_assets();

        ob_start();
        ?>
        <aside class="apollo-filter-sidebar <?php echo esc_attr( $atts['class'] ); ?>">

            <?php if ( ! empty( $atts['title'] ) ) : ?>
            <h3 class="apollo-filter-sidebar__title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>

            <form class="apollo-filter-sidebar__form" method="get">

                <?php if ( $atts['show_search'] === 'yes' ) : ?>
                <div class="apollo-filter-sidebar__section">
                    <h4><?php esc_html_e( 'Buscar', 'apollo-events-manager' ); ?></h4>
                    <input type="text"
                           name="s"
                           placeholder="<?php esc_attr_e( 'Palavras-chave...', 'apollo-events-manager' ); ?>"
                           value="<?php echo esc_attr( get_query_var( 's' ) ); ?>">
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_upcoming'] === 'yes' ) : ?>
                <div class="apollo-filter-sidebar__section">
                    <h4><?php esc_html_e( 'Período', 'apollo-events-manager' ); ?></h4>
                    <ul class="apollo-filter-sidebar__list">
                        <li>
                            <label>
                                <input type="radio" name="period" value="upcoming" <?php checked( $_GET['period'] ?? '', 'upcoming' ); ?>>
                                <?php esc_html_e( 'Próximos eventos', 'apollo-events-manager' ); ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="period" value="today" <?php checked( $_GET['period'] ?? '', 'today' ); ?>>
                                <?php esc_html_e( 'Hoje', 'apollo-events-manager' ); ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="period" value="week" <?php checked( $_GET['period'] ?? '', 'week' ); ?>>
                                <?php esc_html_e( 'Esta semana', 'apollo-events-manager' ); ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="period" value="month" <?php checked( $_GET['period'] ?? '', 'month' ); ?>>
                                <?php esc_html_e( 'Este mês', 'apollo-events-manager' ); ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="period" value="past" <?php checked( $_GET['period'] ?? '', 'past' ); ?>>
                                <?php esc_html_e( 'Eventos passados', 'apollo-events-manager' ); ?>
                            </label>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_category'] === 'yes' ) : ?>
                <div class="apollo-filter-sidebar__section">
                    <h4><?php esc_html_e( 'Categorias', 'apollo-events-manager' ); ?></h4>
                    <ul class="apollo-filter-sidebar__list">
                        <?php
                        $categories = get_terms( [
                            'taxonomy'   => 'event_category',
                            'hide_empty' => true,
                        ] );
                        $current_cats = (array) ( $_GET['event_cats'] ?? [] );
                        if ( ! is_wp_error( $categories ) ) {
                            foreach ( $categories as $cat ) {
                                printf(
                                    '<li><label><input type="checkbox" name="event_cats[]" value="%s" %s> %s <span>(%d)</span></label></li>',
                                    esc_attr( $cat->slug ),
                                    checked( in_array( $cat->slug, $current_cats, true ), true, false ),
                                    esc_html( $cat->name ),
                                    (int) $cat->count
                                );
                            }
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_location'] === 'yes' ) : ?>
                <div class="apollo-filter-sidebar__section">
                    <h4><?php esc_html_e( 'Locais', 'apollo-events-manager' ); ?></h4>
                    <ul class="apollo-filter-sidebar__list apollo-filter-sidebar__list--scrollable">
                        <?php
                        $locals = get_posts( [
                            'post_type'      => 'event_local',
                            'posts_per_page' => 20,
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                            'post_status'    => 'publish',
                        ] );
                        $current_locals = (array) ( $_GET['local_ids'] ?? [] );
                        foreach ( $locals as $local ) {
                            printf(
                                '<li><label><input type="checkbox" name="local_ids[]" value="%d" %s> %s</label></li>',
                                (int) $local->ID,
                                checked( in_array( (string) $local->ID, $current_locals, true ), true, false ),
                                esc_html( $local->post_title )
                            );
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ( $atts['show_date'] === 'yes' ) : ?>
                <div class="apollo-filter-sidebar__section">
                    <h4><?php esc_html_e( 'Data', 'apollo-events-manager' ); ?></h4>
                    <div class="apollo-filter-sidebar__dates">
                        <label>
                            <?php esc_html_e( 'De:', 'apollo-events-manager' ); ?>
                            <input type="date" name="date_from" value="<?php echo esc_attr( $_GET['date_from'] ?? '' ); ?>">
                        </label>
                        <label>
                            <?php esc_html_e( 'Até:', 'apollo-events-manager' ); ?>
                            <input type="date" name="date_to" value="<?php echo esc_attr( $_GET['date_to'] ?? '' ); ?>">
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="apollo-filter-sidebar__actions">
                    <button type="submit" class="apollo-btn apollo-btn--primary apollo-btn--block">
                        <?php esc_html_e( 'Aplicar Filtros', 'apollo-events-manager' ); ?>
                    </button>
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'event_listing' ) ); ?>" class="apollo-btn apollo-btn--ghost apollo-btn--block">
                        <?php esc_html_e( 'Limpar Filtros', 'apollo-events-manager' ); ?>
                    </a>
                </div>

            </form>

        </aside>
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
            'default_layout' => [
                'type'        => 'select',
                'label'       => __( 'Layout padrão', 'apollo-events-manager' ),
                'options'     => [
                    'horizontal' => __( 'Horizontal', 'apollo-events-manager' ),
                    'vertical'   => __( 'Vertical', 'apollo-events-manager' ),
                ],
                'default'     => 'horizontal',
            ],
            'enable_ajax' => [
                'type'    => 'checkbox',
                'label'   => __( 'Filtrar via AJAX (sem recarregar página)', 'apollo-events-manager' ),
                'default' => true,
            ],
        ];
    }
}
