<?php
/**
 * QR Code Module
 *
 * Generates QR codes for events.
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
 * Class QRCode_Module
 *
 * QR code generation for events.
 *
 * @since 2.0.0
 */
class QRCode_Module extends Abstract_Module {

    /**
     * Google Charts QR API base URL.
     *
     * @var string
     */
    private string $api_base = 'https://chart.googleapis.com/chart';

    /**
     * Get module unique identifier.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_id(): string {
        return 'qrcode';
    }

    /**
     * Get module name.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_name(): string {
        return __( 'QR Code', 'apollo-events' );
    }

    /**
     * Get module description.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_description(): string {
        return __( 'Gera QR codes para compartilhamento e check-in de eventos.', 'apollo-events' );
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
        $this->register_hooks();
    }

    /**
     * Register hooks.
     *
     * @since 2.0.0
     * @return void
     */
    private function register_hooks(): void {
        // Add metabox.
        add_action( 'add_meta_boxes', array( $this, 'add_qr_metabox' ) );

        // REST API endpoint for QR generation.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_apollo_generate_qr', array( $this, 'ajax_generate_qr' ) );
        add_action( 'wp_ajax_apollo_download_qr', array( $this, 'ajax_download_qr' ) );
    }

    /**
     * Register module shortcodes.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode( 'apollo_event_qr', array( $this, 'render_event_qr' ) );
        add_shortcode( 'apollo_qr_download', array( $this, 'render_qr_download' ) );
        add_shortcode( 'apollo_qr_card', array( $this, 'render_qr_card' ) );
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
     * Enqueue module assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function enqueue_assets(): void {
        $plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

        wp_register_style(
            'apollo-qrcode',
            $plugin_url . '/assets/css/qrcode.css',
            array(),
            $this->get_version()
        );

        wp_register_script(
            'qrcodejs',
            'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js',
            array(),
            '1.5.3',
            true
        );

        wp_register_script(
            'apollo-qrcode',
            $plugin_url . '/assets/js/qrcode.js',
            array( 'jquery', 'qrcodejs' ),
            $this->get_version(),
            true
        );

        wp_localize_script( 'apollo-qrcode', 'apolloQRCode', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'apollo_qrcode_nonce' ),
            'i18n'    => array(
                'generating' => __( 'Gerando QR Code...', 'apollo-events' ),
                'download'   => __( 'Baixar QR Code', 'apollo-events' ),
                'error'      => __( 'Erro ao gerar QR Code', 'apollo-events' ),
            ),
        ) );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 2.0.0
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueue_admin_assets( string $hook ): void {
        global $post_type;

        if ( 'event_listing' !== $post_type ) {
            return;
        }

        wp_enqueue_style( 'apollo-qrcode' );
        wp_enqueue_script( 'qrcodejs' );
        wp_enqueue_script( 'apollo-qrcode' );
    }

    /**
     * Render event QR shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_event_qr( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id' => get_the_ID(),
            'size'     => 200,
            'type'     => 'url', // url, vcard, checkin.
            'color'    => '#000000',
            'bg'       => '#ffffff',
            'class'    => '',
        ), $atts, 'apollo_event_qr' );

        $event_id = absint( $atts['event_id'] );
        $size     = absint( $atts['size'] );

        if ( ! $event_id ) {
            return '';
        }

        wp_enqueue_style( 'apollo-qrcode' );
        wp_enqueue_script( 'qrcodejs' );
        wp_enqueue_script( 'apollo-qrcode' );

        $qr_data = $this->get_qr_data( $event_id, $atts['type'] );
        $qr_url  = $this->generate_qr_url( $qr_data, $size, $atts['color'], $atts['bg'] );

        $classes = array( 'apollo-qr' );
        if ( $atts['class'] ) {
            $classes[] = esc_attr( $atts['class'] );
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-event-id="<?php echo esc_attr( $event_id ); ?>"
             data-qr-data="<?php echo esc_attr( $qr_data ); ?>"
             data-size="<?php echo esc_attr( $size ); ?>"
             data-color="<?php echo esc_attr( ltrim( $atts['color'], '#' ) ); ?>"
             data-bg="<?php echo esc_attr( ltrim( $atts['bg'], '#' ) ); ?>">
            <img src="<?php echo esc_url( $qr_url ); ?>"
                 alt="<?php esc_attr_e( 'QR Code do Evento', 'apollo-events' ); ?>"
                 width="<?php echo esc_attr( $size ); ?>"
                 height="<?php echo esc_attr( $size ); ?>"
                 class="apollo-qr__image">
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render QR download button shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_qr_download( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id' => get_the_ID(),
            'text'     => __( 'Baixar QR Code', 'apollo-events' ),
            'size'     => 500,
            'format'   => 'png', // png, svg.
        ), $atts, 'apollo_qr_download' );

        $event_id = absint( $atts['event_id'] );

        if ( ! $event_id ) {
            return '';
        }

        wp_enqueue_style( 'apollo-qrcode' );
        wp_enqueue_script( 'apollo-qrcode' );

        $qr_data = $this->get_qr_data( $event_id, 'url' );

        ob_start();
        ?>
        <button type="button"
                class="apollo-qr-download-btn"
                data-qr-data="<?php echo esc_attr( $qr_data ); ?>"
                data-size="<?php echo esc_attr( $atts['size'] ); ?>"
                data-format="<?php echo esc_attr( $atts['format'] ); ?>"
                data-filename="evento-<?php echo esc_attr( $event_id ); ?>">
            <i class="fas fa-download"></i>
            <span><?php echo esc_html( $atts['text'] ); ?></span>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Render QR card shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_qr_card( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id'  => get_the_ID(),
            'show_info' => 'true',
        ), $atts, 'apollo_qr_card' );

        $event_id  = absint( $atts['event_id'] );
        $show_info = filter_var( $atts['show_info'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $event_id ) {
            return '';
        }

        $event = get_post( $event_id );
        if ( ! $event ) {
            return '';
        }

        wp_enqueue_style( 'apollo-qrcode' );
        wp_enqueue_script( 'qrcodejs' );
        wp_enqueue_script( 'apollo-qrcode' );

        $qr_data    = $this->get_qr_data( $event_id, 'url' );
        $qr_url     = $this->generate_qr_url( $qr_data, 200 );
        $event_date = get_post_meta( $event_id, '_event_start_date', true );

        ob_start();
        ?>
        <div class="apollo-qr-card">
            <div class="apollo-qr-card__header">
                <h3 class="apollo-qr-card__title"><?php echo esc_html( $event->post_title ); ?></h3>
                <?php if ( $event_date ) : ?>
                    <p class="apollo-qr-card__date">
                        <i class="far fa-calendar"></i>
                        <?php echo esc_html( wp_date( 'd/m/Y - H:i', strtotime( $event_date ) ) ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="apollo-qr-card__qr">
                <div class="apollo-qr"
                     data-event-id="<?php echo esc_attr( $event_id ); ?>"
                     data-qr-data="<?php echo esc_attr( $qr_data ); ?>"
                     data-size="200">
                    <img src="<?php echo esc_url( $qr_url ); ?>"
                         alt="<?php esc_attr_e( 'QR Code', 'apollo-events' ); ?>"
                         class="apollo-qr__image">
                </div>
            </div>

            <div class="apollo-qr-card__footer">
                <p class="apollo-qr-card__scan">
                    <i class="fas fa-camera"></i>
                    <?php esc_html_e( 'Aponte a câmera para acessar', 'apollo-events' ); ?>
                </p>
                <button type="button"
                        class="apollo-qr-download-btn apollo-qr-download-btn--small"
                        data-qr-data="<?php echo esc_attr( $qr_data ); ?>"
                        data-size="500"
                        data-filename="evento-<?php echo esc_attr( $event_id ); ?>">
                    <i class="fas fa-download"></i>
                    <span><?php esc_html_e( 'Baixar', 'apollo-events' ); ?></span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get QR data based on type.
     *
     * @since 2.0.0
     * @param int    $event_id Event ID.
     * @param string $type     QR type.
     * @return string
     */
    private function get_qr_data( int $event_id, string $type = 'url' ): string {
        switch ( $type ) {
            case 'vcard':
                return $this->generate_vcard( $event_id );
            case 'checkin':
                return $this->generate_checkin_url( $event_id );
            case 'url':
            default:
                return get_permalink( $event_id );
        }
    }

    /**
     * Generate vCard data.
     *
     * @since 2.0.0
     * @param int $event_id Event ID.
     * @return string
     */
    private function generate_vcard( int $event_id ): string {
        $event      = get_post( $event_id );
        $event_date = get_post_meta( $event_id, '_event_start_date', true );
        $end_date   = get_post_meta( $event_id, '_event_end_date', true );

        if ( ! $event_date ) {
            return get_permalink( $event_id );
        }

        $dtstart = wp_date( 'Ymd\THis', strtotime( $event_date ) );
        $dtend   = $end_date ? wp_date( 'Ymd\THis', strtotime( $end_date ) ) : $dtstart;

        $vcal = "BEGIN:VCALENDAR\n";
        $vcal .= "VERSION:2.0\n";
        $vcal .= "PRODID:-//Apollo Events//EN\n";
        $vcal .= "BEGIN:VEVENT\n";
        $vcal .= "UID:" . uniqid() . "@" . wp_parse_url( home_url(), PHP_URL_HOST ) . "\n";
        $vcal .= "DTSTART:{$dtstart}\n";
        $vcal .= "DTEND:{$dtend}\n";
        $vcal .= "SUMMARY:" . $event->post_title . "\n";
        $vcal .= "URL:" . get_permalink( $event_id ) . "\n";
        $vcal .= "END:VEVENT\n";
        $vcal .= "END:VCALENDAR";

        return $vcal;
    }

    /**
     * Generate check-in URL.
     *
     * @since 2.0.0
     * @param int $event_id Event ID.
     * @return string
     */
    private function generate_checkin_url( int $event_id ): string {
        $token = wp_create_nonce( 'apollo_checkin_' . $event_id );

        return add_query_arg(
            array(
                'apollo_checkin' => $event_id,
                'token'          => $token,
            ),
            home_url()
        );
    }

    /**
     * Generate QR code URL using Google Charts API.
     *
     * @since 2.0.0
     * @param string $data  Data to encode.
     * @param int    $size  QR size.
     * @param string $color Foreground color.
     * @param string $bg    Background color.
     * @return string
     */
    private function generate_qr_url( string $data, int $size = 200, string $color = '#000000', string $bg = '#ffffff' ): string {
        $color = str_replace( '#', '', $color );
        $bg    = str_replace( '#', '', $bg );

        return add_query_arg(
            array(
                'cht'  => 'qr',
                'chs'  => "{$size}x{$size}",
                'chl'  => rawurlencode( $data ),
                'chco' => $color,
                'chf'  => "bg,s,{$bg}",
                'choe' => 'UTF-8',
            ),
            $this->api_base
        );
    }

    /**
     * Add QR code metabox.
     *
     * @since 2.0.0
     * @return void
     */
    public function add_qr_metabox(): void {
        add_meta_box(
            'apollo-qrcode',
            __( 'QR Code do Evento', 'apollo-events' ),
            array( $this, 'render_metabox' ),
            'event_listing',
            'side',
            'default'
        );
    }

    /**
     * Render QR metabox.
     *
     * @since 2.0.0
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_metabox( $post ): void {
        if ( 'publish' !== $post->post_status ) {
            echo '<p>' . esc_html__( 'Publique o evento para gerar o QR Code.', 'apollo-events' ) . '</p>';
            return;
        }

        $qr_data = get_permalink( $post->ID );
        $qr_url  = $this->generate_qr_url( $qr_data, 200 );

        ?>
        <div class="apollo-qr-metabox">
            <div class="apollo-qr-metabox__preview">
                <img src="<?php echo esc_url( $qr_url ); ?>" alt="QR Code" width="200" height="200">
            </div>

            <div class="apollo-qr-metabox__actions">
                <button type="button"
                        class="button apollo-qr-download-btn"
                        data-qr-data="<?php echo esc_attr( $qr_data ); ?>"
                        data-size="500"
                        data-filename="evento-<?php echo esc_attr( $post->ID ); ?>">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'Baixar PNG', 'apollo-events' ); ?>
                </button>
            </div>

            <p class="apollo-qr-metabox__tip">
                <?php esc_html_e( 'Use este QR Code em materiais impressos para direcionar ao evento.', 'apollo-events' ); ?>
            </p>
        </div>
        <style>
            .apollo-qr-metabox {
                text-align: center;
            }
            .apollo-qr-metabox__preview {
                padding: 15px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                margin-bottom: 15px;
            }
            .apollo-qr-metabox__preview img {
                display: block;
                margin: 0 auto;
            }
            .apollo-qr-metabox__actions .button {
                width: 100%;
                justify-content: center;
            }
            .apollo-qr-metabox__tip {
                font-size: 12px;
                color: #666;
                margin-top: 10px;
            }
        </style>
        <?php
    }

    /**
     * Register REST routes.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route( 'apollo-events/v1', '/qr/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_qr' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id'   => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                ),
                'size' => array(
                    'default'           => 200,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param >= 50 && $param <= 1000;
                    },
                ),
                'type' => array(
                    'default' => 'url',
                    'enum'    => array( 'url', 'vcard', 'checkin' ),
                ),
            ),
        ) );
    }

    /**
     * REST callback for QR generation.
     *
     * @since 2.0.0
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_get_qr( $request ): \WP_REST_Response {
        $event_id = $request->get_param( 'id' );
        $size     = $request->get_param( 'size' );
        $type     = $request->get_param( 'type' );

        $event = get_post( $event_id );

        if ( ! $event || 'event_listing' !== $event->post_type ) {
            return new \WP_REST_Response(
                array( 'error' => __( 'Evento não encontrado', 'apollo-events' ) ),
                404
            );
        }

        $qr_data = $this->get_qr_data( $event_id, $type );
        $qr_url  = $this->generate_qr_url( $qr_data, $size );

        return new \WP_REST_Response( array(
            'url'  => $qr_url,
            'data' => $qr_data,
            'size' => $size,
        ) );
    }

    /**
     * AJAX handler for QR generation.
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_generate_qr(): void {
        check_ajax_referer( 'apollo_qrcode_nonce', 'nonce' );

        $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
        $size     = isset( $_POST['size'] ) ? absint( $_POST['size'] ) : 200;
        $type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'url';

        if ( ! $event_id ) {
            wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events' ) ), 400 );
        }

        $qr_data = $this->get_qr_data( $event_id, $type );
        $qr_url  = $this->generate_qr_url( $qr_data, $size );

        wp_send_json_success( array(
            'url'  => $qr_url,
            'data' => $qr_data,
        ) );
    }

    /**
     * Get settings schema.
     *
     * @since 2.0.0
     * @return array
     */
    public function get_settings_schema(): array {
        return array(
            'default_size'  => array(
                'type'        => 'number',
                'label'       => __( 'Tamanho padrão (px)', 'apollo-events' ),
                'default'     => 200,
            ),
            'default_color' => array(
                'type'        => 'color',
                'label'       => __( 'Cor do QR Code', 'apollo-events' ),
                'default'     => '#000000',
            ),
            'show_in_single' => array(
                'type'        => 'boolean',
                'label'       => __( 'Mostrar na página do evento', 'apollo-events' ),
                'default'     => false,
            ),
        );
    }
}
