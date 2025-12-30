<?php
/**
 * Speakers Module
 *
 * Handles DJ/Speakers display, timetables, and schedule builder.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Speakers_Module
 *
 * Provides speakers/DJs functionality for events.
 *
 * @since 2.0.0
 */
class Speakers_Module extends Abstract_Module {

    /**
     * Meta key for DJ slots.
     *
     * @var string
     */
    const SLOTS_META_KEY = '_event_dj_slots';

    /**
     * Meta key for DJ IDs.
     *
     * @var string
     */
    const DJ_IDS_META_KEY = '_event_dj_ids';

    /**
     * Get module unique identifier.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_id(): string {
        return 'speakers';
    }

    /**
     * Get module name.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_name(): string {
        return __( 'DJs / Speakers', 'apollo-events' );
    }

    /**
     * Get module description.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_description(): string {
        return __( 'Exibe DJs/speakers do evento com cards, timetables e horários.', 'apollo-events' );
    }

    /**
     * Get module version.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_version(): string {
        return '2.0.0';
    }

    /**
     * Check if module is enabled by default.
     *
     * @since 2.0.0
     * @return bool
     */
    public function is_default_enabled(): bool {
        return true;
    }

    /**
     * Initialize the module.
     *
     * @since 2.0.0
     * @return void
     */
    public function init(): void {
        $this->register_shortcodes();
        $this->register_assets();
    }

    /**
     * Register module shortcodes.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode( 'apollo_event_djs', array( $this, 'render_event_djs' ) );
        add_shortcode( 'apollo_dj_card', array( $this, 'render_dj_card' ) );
        add_shortcode( 'apollo_dj_grid', array( $this, 'render_dj_grid' ) );
        add_shortcode( 'apollo_timetable', array( $this, 'render_timetable' ) );
        add_shortcode( 'apollo_schedule', array( $this, 'render_schedule' ) );
        add_shortcode( 'apollo_dj_slider', array( $this, 'render_dj_slider' ) );
    }

    /**
     * Register module assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_assets(): void {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue module assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function enqueue_assets(): void {
        $plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

        wp_register_style(
            'apollo-speakers',
            $plugin_url . '/assets/css/speakers.css',
            array(),
            $this->get_version()
        );

        wp_register_script(
            'apollo-speakers',
            $plugin_url . '/assets/js/speakers.js',
            array( 'jquery' ),
            $this->get_version(),
            true
        );

        wp_localize_script( 'apollo-speakers', 'apolloSpeakers', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'apollo_speakers_nonce' ),
            'i18n'    => array(
                'viewProfile' => __( 'Ver Perfil', 'apollo-events' ),
                'playing'     => __( 'Tocando', 'apollo-events' ),
                'upcoming'    => __( 'Em breve', 'apollo-events' ),
                'finished'    => __( 'Finalizado', 'apollo-events' ),
            ),
        ) );
    }

    /**
     * Render event DJs shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_event_djs( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id'    => get_the_ID(),
            'layout'      => 'grid',
            'columns'     => 4,
            'show_time'   => 'true',
            'show_social' => 'true',
        ), $atts, 'apollo_event_djs' );

        $event_id    = absint( $atts['event_id'] );
        $layout      = sanitize_key( $atts['layout'] );
        $columns     = absint( $atts['columns'] );
        $show_time   = filter_var( $atts['show_time'], FILTER_VALIDATE_BOOLEAN );
        $show_social = filter_var( $atts['show_social'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $event_id ) {
            return '';
        }

        wp_enqueue_style( 'apollo-speakers' );
        wp_enqueue_script( 'apollo-speakers' );

        $dj_slots = get_post_meta( $event_id, self::SLOTS_META_KEY, true );
        $dj_ids   = get_post_meta( $event_id, self::DJ_IDS_META_KEY, true );

        // Merge DJs from slots and IDs.
        $all_djs = array();

        if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) {
            foreach ( $dj_slots as $slot ) {
                if ( ! empty( $slot['dj_id'] ) ) {
                    $all_djs[ $slot['dj_id'] ] = $slot;
                }
            }
        }

        if ( ! empty( $dj_ids ) && is_array( $dj_ids ) ) {
            foreach ( $dj_ids as $dj_id ) {
                if ( ! isset( $all_djs[ $dj_id ] ) ) {
                    $all_djs[ $dj_id ] = array( 'dj_id' => $dj_id );
                }
            }
        }

        if ( empty( $all_djs ) ) {
            return '<p class="apollo-no-djs">' . esc_html__( 'Nenhum DJ confirmado ainda.', 'apollo-events' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="apollo-event-djs apollo-event-djs--<?php echo esc_attr( $layout ); ?>">
            <?php if ( 'grid' === $layout ) : ?>
                <div class="apollo-dj-grid apollo-dj-grid--cols-<?php echo esc_attr( $columns ); ?>">
                    <?php foreach ( $all_djs as $dj_id => $slot_data ) : ?>
                        <?php $this->render_dj_card_html( $dj_id, $slot_data, $show_time, $show_social ); ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ( 'list' === $layout ) : ?>
                <div class="apollo-dj-list">
                    <?php foreach ( $all_djs as $dj_id => $slot_data ) : ?>
                        <?php $this->render_dj_row_html( $dj_id, $slot_data, $show_time, $show_social ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single DJ card shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_dj_card( $atts ): string {
        $atts = shortcode_atts( array(
            'dj_id'       => 0,
            'show_time'   => 'false',
            'show_social' => 'true',
            'size'        => 'medium',
        ), $atts, 'apollo_dj_card' );

        $dj_id       = absint( $atts['dj_id'] );
        $show_time   = filter_var( $atts['show_time'], FILTER_VALIDATE_BOOLEAN );
        $show_social = filter_var( $atts['show_social'], FILTER_VALIDATE_BOOLEAN );
        $size        = sanitize_key( $atts['size'] );

        if ( ! $dj_id || 'event_dj' !== get_post_type( $dj_id ) ) {
            return '';
        }

        wp_enqueue_style( 'apollo-speakers' );

        ob_start();
        ?>
        <div class="apollo-dj-card-wrapper apollo-dj-card-wrapper--<?php echo esc_attr( $size ); ?>">
            <?php $this->render_dj_card_html( $dj_id, array(), $show_time, $show_social ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render DJ grid shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_dj_grid( $atts ): string {
        $atts = shortcode_atts( array(
            'limit'       => 12,
            'columns'     => 4,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'show_social' => 'true',
        ), $atts, 'apollo_dj_grid' );

        $limit       = absint( $atts['limit'] );
        $columns     = absint( $atts['columns'] );
        $orderby     = sanitize_key( $atts['orderby'] );
        $order       = strtoupper( sanitize_key( $atts['order'] ) );
        $show_social = filter_var( $atts['show_social'], FILTER_VALIDATE_BOOLEAN );

        wp_enqueue_style( 'apollo-speakers' );

        $args = array(
            'post_type'      => 'event_dj',
            'posts_per_page' => $limit,
            'orderby'        => $orderby,
            'order'          => $order,
        );

        $query = new \WP_Query( $args );

        ob_start();
        ?>
        <div class="apollo-dj-grid apollo-dj-grid--cols-<?php echo esc_attr( $columns ); ?>">
            <?php
            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) :
                    $query->the_post();
                    $this->render_dj_card_html( get_the_ID(), array(), false, $show_social );
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <p class="apollo-no-djs"><?php esc_html_e( 'Nenhum DJ encontrado.', 'apollo-events' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render timetable shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_timetable( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id'  => get_the_ID(),
            'show_dj'   => 'true',
            'show_live' => 'true',
        ), $atts, 'apollo_timetable' );

        $event_id  = absint( $atts['event_id'] );
        $show_dj   = filter_var( $atts['show_dj'], FILTER_VALIDATE_BOOLEAN );
        $show_live = filter_var( $atts['show_live'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $event_id ) {
            return '';
        }

        wp_enqueue_style( 'apollo-speakers' );
        wp_enqueue_script( 'apollo-speakers' );

        $dj_slots = get_post_meta( $event_id, self::SLOTS_META_KEY, true );

        if ( empty( $dj_slots ) || ! is_array( $dj_slots ) ) {
            return '<p class="apollo-no-timetable">' . esc_html__( 'Timetable ainda não disponível.', 'apollo-events' ) . '</p>';
        }

        // Sort slots by start time.
        usort( $dj_slots, function( $a, $b ) {
            $time_a = isset( $a['start_time'] ) ? strtotime( $a['start_time'] ) : 0;
            $time_b = isset( $b['start_time'] ) ? strtotime( $b['start_time'] ) : 0;
            return $time_a - $time_b;
        } );

        $event_date = get_post_meta( $event_id, '_event_start_date', true );
        $now        = current_time( 'timestamp' );

        ob_start();
        ?>
        <div class="apollo-timetable" data-event-id="<?php echo esc_attr( $event_id ); ?>"
             data-event-date="<?php echo esc_attr( $event_date ); ?>">
            <div class="apollo-timetable__header">
                <h3 class="apollo-timetable__title">
                    <i class="fas fa-clock"></i>
                    <?php esc_html_e( 'Timetable', 'apollo-events' ); ?>
                </h3>
                <?php if ( $show_live ) : ?>
                    <span class="apollo-timetable__live-indicator is-hidden">
                        <span class="apollo-pulse"></span>
                        <?php esc_html_e( 'Ao Vivo', 'apollo-events' ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="apollo-timetable__slots">
                <?php foreach ( $dj_slots as $index => $slot ) : ?>
                    <?php
                    $dj_id      = isset( $slot['dj_id'] ) ? absint( $slot['dj_id'] ) : 0;
                    $start_time = isset( $slot['start_time'] ) ? $slot['start_time'] : '';
                    $end_time   = isset( $slot['end_time'] ) ? $slot['end_time'] : '';
                    $stage      = isset( $slot['stage'] ) ? $slot['stage'] : '';

                    $dj_name = $dj_id ? get_the_title( $dj_id ) : ( isset( $slot['name'] ) ? $slot['name'] : '' );

                    $slot_status = $this->get_slot_status( $event_date, $start_time, $end_time );
                    ?>
                    <div class="apollo-timetable__slot apollo-timetable__slot--<?php echo esc_attr( $slot_status ); ?>"
                         data-start="<?php echo esc_attr( $start_time ); ?>"
                         data-end="<?php echo esc_attr( $end_time ); ?>">

                        <div class="apollo-timetable__time">
                            <?php if ( $start_time ) : ?>
                                <span class="apollo-timetable__start"><?php echo esc_html( $start_time ); ?></span>
                            <?php endif; ?>
                            <?php if ( $end_time ) : ?>
                                <span class="apollo-timetable__end"><?php echo esc_html( $end_time ); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="apollo-timetable__content">
                            <?php if ( $show_dj && $dj_id ) : ?>
                                <div class="apollo-timetable__dj-avatar">
                                    <?php if ( has_post_thumbnail( $dj_id ) ) : ?>
                                        <?php echo get_the_post_thumbnail( $dj_id, 'thumbnail' ); ?>
                                    <?php else : ?>
                                        <div class="apollo-timetable__dj-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="apollo-timetable__info">
                                <span class="apollo-timetable__dj-name">
                                    <?php if ( $dj_id ) : ?>
                                        <a href="<?php echo esc_url( get_permalink( $dj_id ) ); ?>">
                                            <?php echo esc_html( $dj_name ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html( $dj_name ); ?>
                                    <?php endif; ?>
                                </span>
                                <?php if ( $stage ) : ?>
                                    <span class="apollo-timetable__stage">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo esc_html( $stage ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ( 'playing' === $slot_status ) : ?>
                                <span class="apollo-timetable__status apollo-timetable__status--live">
                                    <span class="apollo-pulse"></span>
                                    <?php esc_html_e( 'Tocando Agora', 'apollo-events' ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render schedule shortcode (multi-day/stage).
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_schedule( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id' => get_the_ID(),
        ), $atts, 'apollo_schedule' );

        $event_id = absint( $atts['event_id'] );

        if ( ! $event_id ) {
            return '';
        }

        wp_enqueue_style( 'apollo-speakers' );
        wp_enqueue_script( 'apollo-speakers' );

        $dj_slots = get_post_meta( $event_id, self::SLOTS_META_KEY, true );

        if ( empty( $dj_slots ) || ! is_array( $dj_slots ) ) {
            return '<p class="apollo-no-schedule">' . esc_html__( 'Programação ainda não disponível.', 'apollo-events' ) . '</p>';
        }

        // Group by stage/day.
        $stages = array();
        foreach ( $dj_slots as $slot ) {
            $stage_name = isset( $slot['stage'] ) && $slot['stage'] ? $slot['stage'] : __( 'Main Stage', 'apollo-events' );
            if ( ! isset( $stages[ $stage_name ] ) ) {
                $stages[ $stage_name ] = array();
            }
            $stages[ $stage_name ][] = $slot;
        }

        // Sort each stage by time.
        foreach ( $stages as $stage_name => $slots ) {
            usort( $stages[ $stage_name ], function( $a, $b ) {
                $time_a = isset( $a['start_time'] ) ? strtotime( $a['start_time'] ) : 0;
                $time_b = isset( $b['start_time'] ) ? strtotime( $b['start_time'] ) : 0;
                return $time_a - $time_b;
            } );
        }

        ob_start();
        ?>
        <div class="apollo-schedule">
            <div class="apollo-schedule__tabs">
                <?php $first = true; ?>
                <?php foreach ( array_keys( $stages ) as $stage_name ) : ?>
                    <button type="button"
                            class="apollo-schedule__tab <?php echo $first ? 'is-active' : ''; ?>"
                            data-stage="<?php echo esc_attr( sanitize_title( $stage_name ) ); ?>">
                        <?php echo esc_html( $stage_name ); ?>
                    </button>
                    <?php $first = false; ?>
                <?php endforeach; ?>
            </div>

            <div class="apollo-schedule__content">
                <?php $first = true; ?>
                <?php foreach ( $stages as $stage_name => $slots ) : ?>
                    <div class="apollo-schedule__panel <?php echo $first ? 'is-active' : ''; ?>"
                         data-stage="<?php echo esc_attr( sanitize_title( $stage_name ) ); ?>">

                        <div class="apollo-schedule__timeline">
                            <?php foreach ( $slots as $slot ) : ?>
                                <?php
                                $dj_id      = isset( $slot['dj_id'] ) ? absint( $slot['dj_id'] ) : 0;
                                $start_time = isset( $slot['start_time'] ) ? $slot['start_time'] : '';
                                $end_time   = isset( $slot['end_time'] ) ? $slot['end_time'] : '';
                                $dj_name    = $dj_id ? get_the_title( $dj_id ) : ( isset( $slot['name'] ) ? $slot['name'] : '' );
                                ?>
                                <div class="apollo-schedule__item">
                                    <div class="apollo-schedule__time-block">
                                        <span class="apollo-schedule__time-start"><?php echo esc_html( $start_time ); ?></span>
                                        <?php if ( $end_time ) : ?>
                                            <span class="apollo-schedule__time-end"><?php echo esc_html( $end_time ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="apollo-schedule__connector"></div>
                                    <div class="apollo-schedule__details">
                                        <?php if ( $dj_id && has_post_thumbnail( $dj_id ) ) : ?>
                                            <div class="apollo-schedule__avatar">
                                                <?php echo get_the_post_thumbnail( $dj_id, 'thumbnail' ); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="apollo-schedule__text">
                                            <span class="apollo-schedule__name">
                                                <?php echo esc_html( $dj_name ); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $first = false; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render DJ slider shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_dj_slider( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id'    => get_the_ID(),
            'autoplay'    => 'true',
            'show_social' => 'true',
        ), $atts, 'apollo_dj_slider' );

        $event_id    = absint( $atts['event_id'] );
        $autoplay    = filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN );
        $show_social = filter_var( $atts['show_social'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $event_id ) {
            return '';
        }

        wp_enqueue_style( 'apollo-speakers' );
        wp_enqueue_script( 'apollo-speakers' );

        $dj_ids = get_post_meta( $event_id, self::DJ_IDS_META_KEY, true );

        if ( empty( $dj_ids ) || ! is_array( $dj_ids ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="apollo-dj-slider" data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>">
            <div class="apollo-dj-slider__track">
                <?php foreach ( $dj_ids as $dj_id ) : ?>
                    <div class="apollo-dj-slider__slide">
                        <?php $this->render_dj_card_html( $dj_id, array(), false, $show_social ); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="apollo-dj-slider__nav apollo-dj-slider__nav--prev" aria-label="<?php esc_attr_e( 'Anterior', 'apollo-events' ); ?>">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" class="apollo-dj-slider__nav apollo-dj-slider__nav--next" aria-label="<?php esc_attr_e( 'Próximo', 'apollo-events' ); ?>">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render DJ card HTML.
     *
     * @since 2.0.0
     * @param int   $dj_id       DJ post ID.
     * @param array $slot_data   Slot data if available.
     * @param bool  $show_time   Whether to show time.
     * @param bool  $show_social Whether to show social links.
     * @return void
     */
    private function render_dj_card_html( int $dj_id, array $slot_data, bool $show_time, bool $show_social ): void {
        if ( ! $dj_id || 'event_dj' !== get_post_type( $dj_id ) ) {
            return;
        }

        $dj_name      = get_the_title( $dj_id );
        $dj_bio       = get_the_excerpt( $dj_id );
        $start_time   = isset( $slot_data['start_time'] ) ? $slot_data['start_time'] : '';
        $end_time     = isset( $slot_data['end_time'] ) ? $slot_data['end_time'] : '';
        $stage        = isset( $slot_data['stage'] ) ? $slot_data['stage'] : '';

        // Get social links.
        $instagram   = get_post_meta( $dj_id, '_dj_instagram', true );
        $soundcloud  = get_post_meta( $dj_id, '_dj_soundcloud', true );
        $spotify     = get_post_meta( $dj_id, '_dj_spotify', true );
        ?>
        <article class="apollo-dj-card">
            <a href="<?php echo esc_url( get_permalink( $dj_id ) ); ?>" class="apollo-dj-card__link">
                <div class="apollo-dj-card__image">
                    <?php if ( has_post_thumbnail( $dj_id ) ) : ?>
                        <?php echo get_the_post_thumbnail( $dj_id, 'medium_large' ); ?>
                    <?php else : ?>
                        <div class="apollo-dj-card__placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>

                    <div class="apollo-dj-card__overlay"></div>
                </div>

                <div class="apollo-dj-card__content">
                    <h3 class="apollo-dj-card__name"><?php echo esc_html( $dj_name ); ?></h3>

                    <?php if ( $show_time && ( $start_time || $end_time ) ) : ?>
                        <div class="apollo-dj-card__time">
                            <i class="fas fa-clock"></i>
                            <?php
                            if ( $start_time && $end_time ) {
                                echo esc_html( $start_time . ' - ' . $end_time );
                            } elseif ( $start_time ) {
                                echo esc_html( $start_time );
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $stage ) : ?>
                        <div class="apollo-dj-card__stage">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html( $stage ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>

            <?php if ( $show_social && ( $instagram || $soundcloud || $spotify ) ) : ?>
                <div class="apollo-dj-card__social">
                    <?php if ( $instagram ) : ?>
                        <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener" class="apollo-dj-card__social-link">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ( $soundcloud ) : ?>
                        <a href="<?php echo esc_url( $soundcloud ); ?>" target="_blank" rel="noopener" class="apollo-dj-card__social-link">
                            <i class="fab fa-soundcloud"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ( $spotify ) : ?>
                        <a href="<?php echo esc_url( $spotify ); ?>" target="_blank" rel="noopener" class="apollo-dj-card__social-link">
                            <i class="fab fa-spotify"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
        <?php
    }

    /**
     * Render DJ row HTML for list layout.
     *
     * @since 2.0.0
     * @param int   $dj_id       DJ post ID.
     * @param array $slot_data   Slot data if available.
     * @param bool  $show_time   Whether to show time.
     * @param bool  $show_social Whether to show social links.
     * @return void
     */
    private function render_dj_row_html( int $dj_id, array $slot_data, bool $show_time, bool $show_social ): void {
        if ( ! $dj_id || 'event_dj' !== get_post_type( $dj_id ) ) {
            return;
        }

        $dj_name    = get_the_title( $dj_id );
        $start_time = isset( $slot_data['start_time'] ) ? $slot_data['start_time'] : '';
        $end_time   = isset( $slot_data['end_time'] ) ? $slot_data['end_time'] : '';
        $stage      = isset( $slot_data['stage'] ) ? $slot_data['stage'] : '';

        $instagram  = get_post_meta( $dj_id, '_dj_instagram', true );
        $soundcloud = get_post_meta( $dj_id, '_dj_soundcloud', true );
        $spotify    = get_post_meta( $dj_id, '_dj_spotify', true );
        ?>
        <div class="apollo-dj-row">
            <div class="apollo-dj-row__avatar">
                <?php if ( has_post_thumbnail( $dj_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $dj_id, 'thumbnail' ); ?>
                <?php else : ?>
                    <div class="apollo-dj-row__placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( $show_time && $start_time ) : ?>
                <div class="apollo-dj-row__time">
                    <?php echo esc_html( $start_time ); ?>
                    <?php if ( $end_time ) : ?>
                        <span class="apollo-dj-row__time-separator">-</span>
                        <?php echo esc_html( $end_time ); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="apollo-dj-row__info">
                <a href="<?php echo esc_url( get_permalink( $dj_id ) ); ?>" class="apollo-dj-row__name">
                    <?php echo esc_html( $dj_name ); ?>
                </a>
                <?php if ( $stage ) : ?>
                    <span class="apollo-dj-row__stage"><?php echo esc_html( $stage ); ?></span>
                <?php endif; ?>
            </div>

            <?php if ( $show_social && ( $instagram || $soundcloud || $spotify ) ) : ?>
                <div class="apollo-dj-row__social">
                    <?php if ( $instagram ) : ?>
                        <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ( $soundcloud ) : ?>
                        <a href="<?php echo esc_url( $soundcloud ); ?>" target="_blank" rel="noopener">
                            <i class="fab fa-soundcloud"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ( $spotify ) : ?>
                        <a href="<?php echo esc_url( $spotify ); ?>" target="_blank" rel="noopener">
                            <i class="fab fa-spotify"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get slot status (upcoming, playing, finished).
     *
     * @since 2.0.0
     * @param string $event_date Event date.
     * @param string $start_time Slot start time.
     * @param string $end_time   Slot end time.
     * @return string
     */
    private function get_slot_status( string $event_date, string $start_time, string $end_time ): string {
        if ( ! $event_date || ! $start_time ) {
            return 'upcoming';
        }

        $now = current_time( 'timestamp' );
        $slot_start = strtotime( $event_date . ' ' . $start_time );
        $slot_end = $end_time ? strtotime( $event_date . ' ' . $end_time ) : $slot_start + 3600;

        if ( $now >= $slot_start && $now < $slot_end ) {
            return 'playing';
        } elseif ( $now >= $slot_end ) {
            return 'finished';
        }

        return 'upcoming';
    }

    /**
     * Get settings schema.
     *
     * @since 2.0.0
     * @return array
     */
    public function get_settings_schema(): array {
        return array(
            'default_layout'     => array(
                'type'        => 'select',
                'label'       => __( 'Layout padrão', 'apollo-events' ),
                'description' => __( 'Layout padrão para exibição de DJs.', 'apollo-events' ),
                'default'     => 'grid',
                'options'     => array(
                    'grid' => __( 'Grid', 'apollo-events' ),
                    'list' => __( 'Lista', 'apollo-events' ),
                ),
            ),
            'default_columns'    => array(
                'type'        => 'number',
                'label'       => __( 'Colunas padrão', 'apollo-events' ),
                'description' => __( 'Número de colunas no grid.', 'apollo-events' ),
                'default'     => 4,
                'min'         => 2,
                'max'         => 6,
            ),
            'show_social_links'  => array(
                'type'        => 'boolean',
                'label'       => __( 'Exibir redes sociais', 'apollo-events' ),
                'description' => __( 'Exibe links de redes sociais nos cards.', 'apollo-events' ),
                'default'     => true,
            ),
            'enable_live_status' => array(
                'type'        => 'boolean',
                'label'       => __( 'Status ao vivo', 'apollo-events' ),
                'description' => __( 'Mostra indicador quando DJ está tocando.', 'apollo-events' ),
                'default'     => true,
            ),
        );
    }
}
