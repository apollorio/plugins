<?php
/**
 * Tracking Module
 *
 * Provides analytics and tracking for events.
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
 * Class Tracking_Module
 *
 * Analytics dashboard and event tracking functionality.
 *
 * @since 2.0.0
 */
class Tracking_Module extends Abstract_Module {

    /**
     * Meta key for event tracking data.
     *
     * @var string
     */
    const TRACKING_META_KEY = '_event_tracking';

    /**
     * Get module unique identifier.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_id(): string {
        return 'tracking';
    }

    /**
     * Get module name.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_name(): string {
        return __( 'Tracking & Insights', 'apollo-events' );
    }

    /**
     * Get module description.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_description(): string {
        return __( 'Analytics e insights detalhados para seus eventos.', 'apollo-events' );
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
        $this->register_tracking_hooks();
        $this->register_admin_hooks();
    }

    /**
     * Register tracking hooks.
     *
     * @since 2.0.0
     * @return void
     */
    private function register_tracking_hooks(): void {
        // Track page views.
        add_action( 'template_redirect', array( $this, 'track_page_view' ) );

        // AJAX tracking.
        add_action( 'wp_ajax_apollo_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'wp_ajax_nopriv_apollo_track_event', array( $this, 'ajax_track_event' ) );
    }

    /**
     * Register admin hooks.
     *
     * @since 2.0.0
     * @return void
     */
    private function register_admin_hooks(): void {
        add_action( 'admin_menu', array( $this, 'add_analytics_page' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_stats_metabox' ) );
        add_filter( 'manage_event_listing_posts_columns', array( $this, 'add_stats_column' ) );
        add_action( 'manage_event_listing_posts_custom_column', array( $this, 'render_stats_column' ), 10, 2 );
    }

    /**
     * Register module shortcodes.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode( 'apollo_event_stats', array( $this, 'render_event_stats' ) );
        add_shortcode( 'apollo_popular_events', array( $this, 'render_popular_events' ) );
        add_shortcode( 'apollo_trending_events', array( $this, 'render_trending_events' ) );
    }

    /**
     * Register module assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_assets(): void {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function enqueue_assets(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        $plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

        wp_enqueue_script(
            'apollo-tracking',
            $plugin_url . '/assets/js/tracking.js',
            array( 'jquery' ),
            $this->get_version(),
            true
        );

        wp_localize_script( 'apollo-tracking', 'apolloTracking', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'eventId' => get_the_ID(),
            'nonce'   => wp_create_nonce( 'apollo_tracking_nonce' ),
        ) );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();

        if ( ! $screen ) {
            return;
        }

        if ( 'event_listing_page_apollo-analytics' === $screen->id || 'event_listing' === $screen->post_type ) {
            $plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

            wp_enqueue_style(
                'apollo-tracking-admin',
                $plugin_url . '/assets/css/tracking.css',
                array(),
                $this->get_version()
            );

            // Chart.js for analytics.
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                array(),
                '4.4.1',
                true
            );

            wp_enqueue_script(
                'apollo-tracking-admin',
                $plugin_url . '/assets/js/tracking-admin.js',
                array( 'jquery', 'chartjs' ),
                $this->get_version(),
                true
            );

            wp_localize_script( 'apollo-tracking-admin', 'apolloTrackingAdmin', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'apollo_tracking_admin_nonce' ),
            ) );
        }
    }

    /**
     * Track page view.
     *
     * @since 2.0.0
     * @return void
     */
    public function track_page_view(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        // Don't track admin users.
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        $post_id = get_the_ID();
        $this->record_view( $post_id );
    }

    /**
     * Record a view.
     *
     * @since 2.0.0
     * @param int $post_id Post ID.
     * @return void
     */
    private function record_view( int $post_id ): void {
        $tracking = $this->get_tracking_data( $post_id );
        $today    = wp_date( 'Y-m-d' );

        // Increment total views.
        $tracking['total_views'] = isset( $tracking['total_views'] ) ? $tracking['total_views'] + 1 : 1;

        // Increment daily views.
        if ( ! isset( $tracking['daily_views'] ) ) {
            $tracking['daily_views'] = array();
        }
        $tracking['daily_views'][ $today ] = isset( $tracking['daily_views'][ $today ] )
            ? $tracking['daily_views'][ $today ] + 1
            : 1;

        // Keep only last 90 days.
        $tracking['daily_views'] = array_slice( $tracking['daily_views'], -90, 90, true );

        // Track unique visitors (using IP hash).
        $visitor_hash = md5( $this->get_visitor_ip() . wp_date( 'Y-m-d' ) );
        if ( ! isset( $tracking['unique_visitors'] ) ) {
            $tracking['unique_visitors'] = array();
        }
        if ( ! in_array( $visitor_hash, $tracking['unique_visitors'], true ) ) {
            $tracking['unique_visitors'][] = $visitor_hash;
            $tracking['unique_visitors']   = array_slice( $tracking['unique_visitors'], -1000 );
        }

        // Update last viewed.
        $tracking['last_viewed'] = current_time( 'mysql' );

        $this->save_tracking_data( $post_id, $tracking );
    }

    /**
     * Get visitor IP (hashed for privacy).
     *
     * @since 2.0.0
     * @return string
     */
    private function get_visitor_ip(): string {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return $ip;
    }

    /**
     * Get tracking data.
     *
     * @since 2.0.0
     * @param int $post_id Post ID.
     * @return array
     */
    public function get_tracking_data( int $post_id ): array {
        $data = get_post_meta( $post_id, self::TRACKING_META_KEY, true );
        return is_array( $data ) ? $data : array();
    }

    /**
     * Save tracking data.
     *
     * @since 2.0.0
     * @param int   $post_id Post ID.
     * @param array $data    Tracking data.
     * @return void
     */
    private function save_tracking_data( int $post_id, array $data ): void {
        update_post_meta( $post_id, self::TRACKING_META_KEY, $data );
    }

    /**
     * AJAX handler for tracking events.
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_track_event(): void {
        $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
        $action   = isset( $_POST['track_action'] ) ? sanitize_key( $_POST['track_action'] ) : '';

        if ( ! $event_id || ! $action ) {
            wp_send_json_error();
        }

        $tracking = $this->get_tracking_data( $event_id );

        switch ( $action ) {
            case 'ticket_click':
                $tracking['ticket_clicks'] = isset( $tracking['ticket_clicks'] ) ? $tracking['ticket_clicks'] + 1 : 1;
                break;

            case 'share':
                $platform = isset( $_POST['platform'] ) ? sanitize_key( $_POST['platform'] ) : 'unknown';
                if ( ! isset( $tracking['shares'] ) ) {
                    $tracking['shares'] = array();
                }
                $tracking['shares'][ $platform ] = isset( $tracking['shares'][ $platform ] )
                    ? $tracking['shares'][ $platform ] + 1
                    : 1;
                break;

            case 'interest':
                $tracking['interest_actions'] = isset( $tracking['interest_actions'] ) ? $tracking['interest_actions'] + 1 : 1;
                break;
        }

        $this->save_tracking_data( $event_id, $tracking );

        wp_send_json_success();
    }

    /**
     * Add analytics admin page.
     *
     * @since 2.0.0
     * @return void
     */
    public function add_analytics_page(): void {
        add_submenu_page(
            'edit.php?post_type=event_listing',
            __( 'Analytics', 'apollo-events' ),
            __( 'Analytics', 'apollo-events' ),
            'edit_posts',
            'apollo-analytics',
            array( $this, 'render_analytics_page' )
        );
    }

    /**
     * Render analytics page.
     *
     * @since 2.0.0
     * @return void
     */
    public function render_analytics_page(): void {
        $stats = $this->get_global_stats();
        ?>
        <div class="wrap apollo-analytics">
            <h1><?php esc_html_e( 'Analytics de Eventos', 'apollo-events' ); ?></h1>

            <div class="apollo-analytics__overview">
                <div class="apollo-stat-card">
                    <div class="apollo-stat-card__icon">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="apollo-stat-card__content">
                        <span class="apollo-stat-card__value"><?php echo esc_html( number_format_i18n( $stats['total_views'] ) ); ?></span>
                        <span class="apollo-stat-card__label"><?php esc_html_e( 'Total de Visualizações', 'apollo-events' ); ?></span>
                    </div>
                </div>

                <div class="apollo-stat-card">
                    <div class="apollo-stat-card__icon">
                        <span class="dashicons dashicons-heart"></span>
                    </div>
                    <div class="apollo-stat-card__content">
                        <span class="apollo-stat-card__value"><?php echo esc_html( number_format_i18n( $stats['total_interests'] ) ); ?></span>
                        <span class="apollo-stat-card__label"><?php esc_html_e( 'Interesses', 'apollo-events' ); ?></span>
                    </div>
                </div>

                <div class="apollo-stat-card">
                    <div class="apollo-stat-card__icon">
                        <span class="dashicons dashicons-tickets-alt"></span>
                    </div>
                    <div class="apollo-stat-card__content">
                        <span class="apollo-stat-card__value"><?php echo esc_html( number_format_i18n( $stats['ticket_clicks'] ) ); ?></span>
                        <span class="apollo-stat-card__label"><?php esc_html_e( 'Cliques em Ingressos', 'apollo-events' ); ?></span>
                    </div>
                </div>

                <div class="apollo-stat-card">
                    <div class="apollo-stat-card__icon">
                        <span class="dashicons dashicons-share"></span>
                    </div>
                    <div class="apollo-stat-card__content">
                        <span class="apollo-stat-card__value"><?php echo esc_html( number_format_i18n( $stats['total_shares'] ) ); ?></span>
                        <span class="apollo-stat-card__label"><?php esc_html_e( 'Compartilhamentos', 'apollo-events' ); ?></span>
                    </div>
                </div>
            </div>

            <div class="apollo-analytics__charts">
                <div class="apollo-chart-card">
                    <h2><?php esc_html_e( 'Visualizações por Dia', 'apollo-events' ); ?></h2>
                    <canvas id="apollo-views-chart" height="300"></canvas>
                </div>

                <div class="apollo-chart-card">
                    <h2><?php esc_html_e( 'Eventos Mais Populares', 'apollo-events' ); ?></h2>
                    <canvas id="apollo-popular-chart" height="300"></canvas>
                </div>
            </div>

            <div class="apollo-analytics__tables">
                <div class="apollo-table-card">
                    <h2><?php esc_html_e( 'Top 10 Eventos', 'apollo-events' ); ?></h2>
                    <?php $this->render_top_events_table(); ?>
                </div>
            </div>
        </div>

        <script>
            var apolloAnalyticsData = <?php echo wp_json_encode( $this->get_chart_data() ); ?>;
        </script>
        <?php
    }

    /**
     * Get global stats.
     *
     * @since 2.0.0
     * @return array
     */
    private function get_global_stats(): array {
        global $wpdb;

        $events = get_posts( array(
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        $stats = array(
            'total_views'     => 0,
            'total_interests' => 0,
            'ticket_clicks'   => 0,
            'total_shares'    => 0,
        );

        foreach ( $events as $event_id ) {
            $tracking = $this->get_tracking_data( $event_id );

            $stats['total_views']   += isset( $tracking['total_views'] ) ? $tracking['total_views'] : 0;
            $stats['ticket_clicks'] += isset( $tracking['ticket_clicks'] ) ? $tracking['ticket_clicks'] : 0;

            if ( isset( $tracking['shares'] ) && is_array( $tracking['shares'] ) ) {
                $stats['total_shares'] += array_sum( $tracking['shares'] );
            }

            // Get interest count.
            $interests = get_post_meta( $event_id, '_event_interested_users', true );
            if ( is_array( $interests ) ) {
                $stats['total_interests'] += count( $interests );
            }
        }

        return $stats;
    }

    /**
     * Get chart data.
     *
     * @since 2.0.0
     * @return array
     */
    private function get_chart_data(): array {
        $events = get_posts( array(
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        // Aggregate daily views.
        $daily_views = array();
        $event_views = array();

        foreach ( $events as $event_id ) {
            $tracking = $this->get_tracking_data( $event_id );

            // Aggregate daily.
            if ( isset( $tracking['daily_views'] ) && is_array( $tracking['daily_views'] ) ) {
                foreach ( $tracking['daily_views'] as $date => $views ) {
                    $daily_views[ $date ] = isset( $daily_views[ $date ] ) ? $daily_views[ $date ] + $views : $views;
                }
            }

            // Event totals.
            $event_views[] = array(
                'id'    => $event_id,
                'title' => get_the_title( $event_id ),
                'views' => isset( $tracking['total_views'] ) ? $tracking['total_views'] : 0,
            );
        }

        // Sort events by views.
        usort( $event_views, function( $a, $b ) {
            return $b['views'] - $a['views'];
        } );

        // Get last 30 days.
        $labels = array();
        $values = array();

        for ( $i = 29; $i >= 0; $i-- ) {
            $date     = wp_date( 'Y-m-d', strtotime( "-{$i} days" ) );
            $labels[] = wp_date( 'd/m', strtotime( $date ) );
            $values[] = isset( $daily_views[ $date ] ) ? $daily_views[ $date ] : 0;
        }

        return array(
            'views'   => array(
                'labels' => $labels,
                'values' => $values,
            ),
            'popular' => array_slice( $event_views, 0, 10 ),
        );
    }

    /**
     * Render top events table.
     *
     * @since 2.0.0
     * @return void
     */
    private function render_top_events_table(): void {
        $events = get_posts( array(
            'post_type'      => 'event_listing',
            'posts_per_page' => 10,
            'meta_key'       => self::TRACKING_META_KEY,
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ) );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Evento', 'apollo-events' ); ?></th>
                    <th><?php esc_html_e( 'Views', 'apollo-events' ); ?></th>
                    <th><?php esc_html_e( 'Interesses', 'apollo-events' ); ?></th>
                    <th><?php esc_html_e( 'Cliques', 'apollo-events' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $events as $event ) :
                    $tracking = $this->get_tracking_data( $event->ID );
                    $interests = get_post_meta( $event->ID, '_event_interested_users', true );
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>">
                                <?php echo esc_html( $event->post_title ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( number_format_i18n( $tracking['total_views'] ?? 0 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( is_array( $interests ) ? count( $interests ) : 0 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $tracking['ticket_clicks'] ?? 0 ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Add stats metabox.
     *
     * @since 2.0.0
     * @return void
     */
    public function add_stats_metabox(): void {
        add_meta_box(
            'apollo_event_stats',
            __( 'Estatísticas', 'apollo-events' ),
            array( $this, 'render_stats_metabox' ),
            'event_listing',
            'side',
            'default'
        );
    }

    /**
     * Render stats metabox.
     *
     * @since 2.0.0
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_stats_metabox( $post ): void {
        $tracking  = $this->get_tracking_data( $post->ID );
        $interests = get_post_meta( $post->ID, '_event_interested_users', true );
        ?>
        <div class="apollo-stats-mini">
            <div class="apollo-stat-row">
                <span class="dashicons dashicons-visibility"></span>
                <span class="apollo-stat-label"><?php esc_html_e( 'Views:', 'apollo-events' ); ?></span>
                <strong><?php echo esc_html( number_format_i18n( $tracking['total_views'] ?? 0 ) ); ?></strong>
            </div>
            <div class="apollo-stat-row">
                <span class="dashicons dashicons-heart"></span>
                <span class="apollo-stat-label"><?php esc_html_e( 'Interesses:', 'apollo-events' ); ?></span>
                <strong><?php echo esc_html( number_format_i18n( is_array( $interests ) ? count( $interests ) : 0 ) ); ?></strong>
            </div>
            <div class="apollo-stat-row">
                <span class="dashicons dashicons-tickets-alt"></span>
                <span class="apollo-stat-label"><?php esc_html_e( 'Cliques:', 'apollo-events' ); ?></span>
                <strong><?php echo esc_html( number_format_i18n( $tracking['ticket_clicks'] ?? 0 ) ); ?></strong>
            </div>
            <?php if ( isset( $tracking['last_viewed'] ) ) : ?>
                <p class="apollo-stat-info">
                    <?php
                    printf(
                        esc_html__( 'Última view: %s', 'apollo-events' ),
                        esc_html( human_time_diff( strtotime( $tracking['last_viewed'] ), current_time( 'timestamp' ) ) . ' atrás' )
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Add stats column to events list.
     *
     * @since 2.0.0
     * @param array $columns Columns.
     * @return array
     */
    public function add_stats_column( array $columns ): array {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( 'title' === $key ) {
                $new_columns['apollo_views'] = '<span class="dashicons dashicons-visibility" title="' . esc_attr__( 'Visualizações', 'apollo-events' ) . '"></span>';
            }
        }

        return $new_columns;
    }

    /**
     * Render stats column.
     *
     * @since 2.0.0
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function render_stats_column( string $column, int $post_id ): void {
        if ( 'apollo_views' !== $column ) {
            return;
        }

        $tracking = $this->get_tracking_data( $post_id );
        echo esc_html( number_format_i18n( $tracking['total_views'] ?? 0 ) );
    }

    /**
     * Render event stats shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_event_stats( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id' => get_the_ID(),
            'show'     => 'views,interests',
        ), $atts, 'apollo_event_stats' );

        $event_id = absint( $atts['event_id'] );
        $show     = array_map( 'trim', explode( ',', $atts['show'] ) );

        if ( ! $event_id ) {
            return '';
        }

        $tracking  = $this->get_tracking_data( $event_id );
        $interests = get_post_meta( $event_id, '_event_interested_users', true );

        ob_start();
        ?>
        <div class="apollo-event-stats">
            <?php if ( in_array( 'views', $show, true ) ) : ?>
                <span class="apollo-event-stat">
                    <i class="fas fa-eye"></i>
                    <?php echo esc_html( number_format_i18n( $tracking['total_views'] ?? 0 ) ); ?>
                </span>
            <?php endif; ?>

            <?php if ( in_array( 'interests', $show, true ) ) : ?>
                <span class="apollo-event-stat">
                    <i class="fas fa-heart"></i>
                    <?php echo esc_html( number_format_i18n( is_array( $interests ) ? count( $interests ) : 0 ) ); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render popular events shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_popular_events( $atts ): string {
        $atts = shortcode_atts( array(
            'limit'  => 5,
            'period' => 'all',
        ), $atts, 'apollo_popular_events' );

        $limit = absint( $atts['limit'] );

        $events = $this->get_popular_events( $limit );

        if ( empty( $events ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="apollo-popular-events">
            <h3><?php esc_html_e( 'Eventos Populares', 'apollo-events' ); ?></h3>
            <ul>
                <?php foreach ( $events as $event ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $event['id'] ) ); ?>">
                            <?php echo esc_html( $event['title'] ); ?>
                        </a>
                        <span class="views"><?php echo esc_html( number_format_i18n( $event['views'] ) ); ?> views</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render trending events shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_trending_events( $atts ): string {
        $atts = shortcode_atts( array(
            'limit' => 5,
        ), $atts, 'apollo_trending_events' );

        $limit = absint( $atts['limit'] );

        $events = $this->get_trending_events( $limit );

        if ( empty( $events ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="apollo-trending-events">
            <h3>🔥 <?php esc_html_e( 'Em Alta', 'apollo-events' ); ?></h3>
            <ul>
                <?php foreach ( $events as $event ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $event['id'] ) ); ?>">
                            <?php echo esc_html( $event['title'] ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get popular events.
     *
     * @since 2.0.0
     * @param int $limit Limit.
     * @return array
     */
    private function get_popular_events( int $limit = 5 ): array {
        $events = get_posts( array(
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        $event_views = array();

        foreach ( $events as $event_id ) {
            $tracking = $this->get_tracking_data( $event_id );
            $event_views[] = array(
                'id'    => $event_id,
                'title' => get_the_title( $event_id ),
                'views' => isset( $tracking['total_views'] ) ? $tracking['total_views'] : 0,
            );
        }

        usort( $event_views, function( $a, $b ) {
            return $b['views'] - $a['views'];
        } );

        return array_slice( $event_views, 0, $limit );
    }

    /**
     * Get trending events (most views in last 7 days).
     *
     * @since 2.0.0
     * @param int $limit Limit.
     * @return array
     */
    private function get_trending_events( int $limit = 5 ): array {
        $events = get_posts( array(
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        $event_views = array();
        $week_ago    = strtotime( '-7 days' );

        foreach ( $events as $event_id ) {
            $tracking = $this->get_tracking_data( $event_id );
            $views    = 0;

            if ( isset( $tracking['daily_views'] ) && is_array( $tracking['daily_views'] ) ) {
                foreach ( $tracking['daily_views'] as $date => $count ) {
                    if ( strtotime( $date ) >= $week_ago ) {
                        $views += $count;
                    }
                }
            }

            $event_views[] = array(
                'id'    => $event_id,
                'title' => get_the_title( $event_id ),
                'views' => $views,
            );
        }

        usort( $event_views, function( $a, $b ) {
            return $b['views'] - $a['views'];
        } );

        return array_filter( array_slice( $event_views, 0, $limit ), function( $e ) {
            return $e['views'] > 0;
        } );
    }

    /**
     * Get settings schema.
     *
     * @since 2.0.0
     * @return array
     */
    public function get_settings_schema(): array {
        return array(
            'track_views'     => array(
                'type'        => 'boolean',
                'label'       => __( 'Rastrear visualizações', 'apollo-events' ),
                'default'     => true,
            ),
            'track_admins'    => array(
                'type'        => 'boolean',
                'label'       => __( 'Incluir administradores', 'apollo-events' ),
                'default'     => false,
            ),
            'retention_days'  => array(
                'type'        => 'number',
                'label'       => __( 'Dias de retenção', 'apollo-events' ),
                'description' => __( 'Quantos dias manter dados detalhados.', 'apollo-events' ),
                'default'     => 90,
            ),
        );
    }
}
