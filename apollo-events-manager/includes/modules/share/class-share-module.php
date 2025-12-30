<?php
/**
 * Share Module
 *
 * Social sharing buttons and functionality for events.
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
 * Class Share_Module
 *
 * Social sharing buttons and tracking.
 *
 * @since 2.0.0
 */
class Share_Module extends Abstract_Module {

    /**
     * Supported networks.
     *
     * @var array
     */
    private array $networks = array();

    /**
     * Get module unique identifier.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_id(): string {
        return 'share';
    }

    /**
     * Get module name.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_name(): string {
        return __( 'Compartilhamento', 'apollo-events' );
    }

    /**
     * Get module description.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_description(): string {
        return __( 'Botões de compartilhamento em redes sociais para eventos.', 'apollo-events' );
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
        $this->setup_networks();
        $this->register_shortcodes();
        $this->register_assets();
        $this->register_hooks();
    }

    /**
     * Setup available networks.
     *
     * @since 2.0.0
     * @return void
     */
    private function setup_networks(): void {
        $this->networks = array(
            'whatsapp' => array(
                'label'     => 'WhatsApp',
                'icon'      => 'fab fa-whatsapp',
                'color'     => '#25D366',
                'url'       => 'https://api.whatsapp.com/send?text={title}%20{url}',
                'mobile'    => true,
            ),
            'facebook' => array(
                'label'     => 'Facebook',
                'icon'      => 'fab fa-facebook-f',
                'color'     => '#1877F2',
                'url'       => 'https://www.facebook.com/sharer/sharer.php?u={url}',
                'mobile'    => true,
            ),
            'twitter' => array(
                'label'     => 'X (Twitter)',
                'icon'      => 'fab fa-x-twitter',
                'color'     => '#000000',
                'url'       => 'https://twitter.com/intent/tweet?text={title}&url={url}',
                'mobile'    => true,
            ),
            'linkedin' => array(
                'label'     => 'LinkedIn',
                'icon'      => 'fab fa-linkedin-in',
                'color'     => '#0A66C2',
                'url'       => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
                'mobile'    => false,
            ),
            'telegram' => array(
                'label'     => 'Telegram',
                'icon'      => 'fab fa-telegram-plane',
                'color'     => '#0088CC',
                'url'       => 'https://t.me/share/url?url={url}&text={title}',
                'mobile'    => true,
            ),
            'email' => array(
                'label'     => 'Email',
                'icon'      => 'fas fa-envelope',
                'color'     => '#EA4335',
                'url'       => 'mailto:?subject={title}&body={url}',
                'mobile'    => true,
            ),
            'copy' => array(
                'label'     => __( 'Copiar Link', 'apollo-events' ),
                'icon'      => 'fas fa-link',
                'color'     => '#6B7280',
                'url'       => '#copy',
                'mobile'    => true,
            ),
        );

        /**
         * Filter available share networks.
         *
         * @since 2.0.0
         * @param array $networks Available networks.
         */
        $this->networks = apply_filters( 'apollo_share_networks', $this->networks );
    }

    /**
     * Register hooks.
     *
     * @since 2.0.0
     * @return void
     */
    private function register_hooks(): void {
        // Auto-append to content.
        add_filter( 'the_content', array( $this, 'maybe_append_share_buttons' ), 20 );

        // Track shares.
        add_action( 'wp_ajax_apollo_track_share', array( $this, 'ajax_track_share' ) );
        add_action( 'wp_ajax_nopriv_apollo_track_share', array( $this, 'ajax_track_share' ) );

        // Add to event meta.
        add_action( 'add_meta_boxes', array( $this, 'add_share_metabox' ) );
    }

    /**
     * Register module shortcodes.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode( 'apollo_share_buttons', array( $this, 'render_share_buttons' ) );
        add_shortcode( 'apollo_share_count', array( $this, 'render_share_count' ) );
        add_shortcode( 'apollo_share_single', array( $this, 'render_single_button' ) );
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
            'apollo-share',
            $plugin_url . '/assets/css/share.css',
            array(),
            $this->get_version()
        );

        wp_register_script(
            'apollo-share',
            $plugin_url . '/assets/js/share.js',
            array( 'jquery' ),
            $this->get_version(),
            true
        );

        wp_localize_script( 'apollo-share', 'apolloShare', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'apollo_share_nonce' ),
            'i18n'       => array(
                'copied'  => __( 'Link copiado!', 'apollo-events' ),
                'error'   => __( 'Erro ao copiar', 'apollo-events' ),
                'share'   => __( 'Compartilhar', 'apollo-events' ),
            ),
        ) );
    }

    /**
     * Render share buttons shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_share_buttons( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id'     => get_the_ID(),
            'networks'     => 'whatsapp,facebook,twitter,telegram,copy',
            'style'        => 'buttons', // buttons, icons, minimal, floating.
            'size'         => 'medium', // small, medium, large.
            'show_label'   => 'true',
            'show_count'   => 'false',
            'class'        => '',
        ), $atts, 'apollo_share_buttons' );

        $event_id       = absint( $atts['event_id'] );
        $networks       = array_map( 'trim', explode( ',', $atts['networks'] ) );
        $show_label     = filter_var( $atts['show_label'], FILTER_VALIDATE_BOOLEAN );
        $show_count     = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $event_id ) {
            return '';
        }

        $event = get_post( $event_id );
        if ( ! $event ) {
            return '';
        }

        wp_enqueue_style( 'apollo-share' );
        wp_enqueue_script( 'apollo-share' );

        $title       = rawurlencode( $event->post_title );
        $url         = rawurlencode( get_permalink( $event_id ) );
        $share_count = $show_count ? $this->get_share_count( $event_id ) : 0;

        $classes = array(
            'apollo-share',
            'apollo-share--' . esc_attr( $atts['style'] ),
            'apollo-share--' . esc_attr( $atts['size'] ),
        );

        if ( $atts['class'] ) {
            $classes[] = esc_attr( $atts['class'] );
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-event-id="<?php echo esc_attr( $event_id ); ?>">
            <?php if ( $show_count && $share_count > 0 ) : ?>
                <div class="apollo-share__count">
                    <span class="apollo-share__count-number"><?php echo esc_html( $share_count ); ?></span>
                    <span class="apollo-share__count-label"><?php esc_html_e( 'compartilhamentos', 'apollo-events' ); ?></span>
                </div>
            <?php endif; ?>

            <div class="apollo-share__buttons">
                <?php foreach ( $networks as $network ) : ?>
                    <?php if ( isset( $this->networks[ $network ] ) ) : ?>
                        <?php echo $this->render_button( $network, $title, $url, $show_label ); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single share button.
     *
     * @since 2.0.0
     * @param string $network   Network key.
     * @param string $title     Encoded title.
     * @param string $url       Encoded URL.
     * @param bool   $show_label Show label.
     * @return string
     */
    private function render_button( string $network, string $title, string $url, bool $show_label ): string {
        $config = $this->networks[ $network ];
        $share_url = str_replace(
            array( '{title}', '{url}' ),
            array( $title, $url ),
            $config['url']
        );

        $classes = array(
            'apollo-share__btn',
            'apollo-share__btn--' . esc_attr( $network ),
        );

        $is_copy = 'copy' === $network;
        $target  = $is_copy ? '' : ' target="_blank" rel="noopener noreferrer"';

        ob_start();
        ?>
        <a href="<?php echo esc_url( $share_url ); ?>"
           class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
           data-network="<?php echo esc_attr( $network ); ?>"
           <?php if ( $is_copy ) : ?>
           data-clipboard-text="<?php echo esc_url( rawurldecode( $url ) ); ?>"
           <?php endif; ?>
           style="--btn-color: <?php echo esc_attr( $config['color'] ); ?>"
           <?php echo $target; ?>>
            <i class="<?php echo esc_attr( $config['icon'] ); ?>"></i>
            <?php if ( $show_label ) : ?>
                <span><?php echo esc_html( $config['label'] ); ?></span>
            <?php endif; ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Render share count shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_share_count( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id' => get_the_ID(),
            'format'   => 'full', // full, number, compact.
        ), $atts, 'apollo_share_count' );

        $event_id = absint( $atts['event_id'] );
        $count    = $this->get_share_count( $event_id );

        if ( 'number' === $atts['format'] ) {
            return (string) $count;
        }

        if ( 'compact' === $atts['format'] ) {
            return $this->format_number_compact( $count );
        }

        return sprintf(
            '<span class="apollo-share-count">%s %s</span>',
            esc_html( $count ),
            esc_html( _n( 'compartilhamento', 'compartilhamentos', $count, 'apollo-events' ) )
        );
    }

    /**
     * Render single share button shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_single_button( $atts ): string {
        $atts = shortcode_atts( array(
            'event_id'   => get_the_ID(),
            'network'    => 'whatsapp',
            'show_label' => 'true',
            'size'       => 'medium',
        ), $atts, 'apollo_share_single' );

        $event_id = absint( $atts['event_id'] );

        if ( ! $event_id || ! isset( $this->networks[ $atts['network'] ] ) ) {
            return '';
        }

        $event = get_post( $event_id );
        if ( ! $event ) {
            return '';
        }

        wp_enqueue_style( 'apollo-share' );
        wp_enqueue_script( 'apollo-share' );

        $title      = rawurlencode( $event->post_title );
        $url        = rawurlencode( get_permalink( $event_id ) );
        $show_label = filter_var( $atts['show_label'], FILTER_VALIDATE_BOOLEAN );

        return sprintf(
            '<div class="apollo-share apollo-share--single apollo-share--%s" data-event-id="%d">%s</div>',
            esc_attr( $atts['size'] ),
            $event_id,
            $this->render_button( $atts['network'], $title, $url, $show_label )
        );
    }

    /**
     * Maybe append share buttons to content.
     *
     * @since 2.0.0
     * @param string $content Post content.
     * @return string
     */
    public function maybe_append_share_buttons( string $content ): string {
        if ( ! is_singular( 'event_listing' ) ) {
            return $content;
        }

        $auto_append = get_option( 'apollo_share_auto_append', true );

        if ( ! $auto_append ) {
            return $content;
        }

        $share_buttons = $this->render_share_buttons( array(
            'event_id' => get_the_ID(),
            'style'    => 'buttons',
        ) );

        return $content . $share_buttons;
    }

    /**
     * Track share via AJAX.
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_track_share(): void {
        check_ajax_referer( 'apollo_share_nonce', 'nonce' );

        $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
        $network  = isset( $_POST['network'] ) ? sanitize_key( wp_unslash( $_POST['network'] ) ) : '';

        if ( ! $event_id || ! $network ) {
            wp_send_json_error( array( 'message' => __( 'Dados inválidos', 'apollo-events' ) ), 400 );
        }

        $this->increment_share_count( $event_id, $network );

        /**
         * Fires when an event is shared.
         *
         * @since 2.0.0
         * @param int    $event_id Event ID.
         * @param string $network  Network key.
         */
        do_action( 'apollo_event_shared', $event_id, $network );

        wp_send_json_success( array(
            'message' => __( 'Compartilhamento registrado', 'apollo-events' ),
            'count'   => $this->get_share_count( $event_id ),
        ) );
    }

    /**
     * Get share count for event.
     *
     * @since 2.0.0
     * @param int $event_id Event ID.
     * @return int
     */
    public function get_share_count( int $event_id ): int {
        $shares = get_post_meta( $event_id, '_event_shares', true );

        if ( ! is_array( $shares ) ) {
            return 0;
        }

        return array_sum( $shares );
    }

    /**
     * Increment share count.
     *
     * @since 2.0.0
     * @param int    $event_id Event ID.
     * @param string $network  Network key.
     * @return void
     */
    private function increment_share_count( int $event_id, string $network ): void {
        $shares = get_post_meta( $event_id, '_event_shares', true );
        $shares = is_array( $shares ) ? $shares : array();

        if ( ! isset( $shares[ $network ] ) ) {
            $shares[ $network ] = 0;
        }

        $shares[ $network ]++;

        update_post_meta( $event_id, '_event_shares', $shares );

        // Also update tracking if module is active.
        $tracking = get_post_meta( $event_id, '_event_tracking', true );
        if ( is_array( $tracking ) ) {
            $tracking['shares'] = isset( $tracking['shares'] ) ? $tracking['shares'] + 1 : 1;
            update_post_meta( $event_id, '_event_tracking', $tracking );
        }
    }

    /**
     * Format number in compact form.
     *
     * @since 2.0.0
     * @param int $number Number.
     * @return string
     */
    private function format_number_compact( int $number ): string {
        if ( $number >= 1000000 ) {
            return round( $number / 1000000, 1 ) . 'M';
        }
        if ( $number >= 1000 ) {
            return round( $number / 1000, 1 ) . 'K';
        }
        return (string) $number;
    }

    /**
     * Add share metabox.
     *
     * @since 2.0.0
     * @return void
     */
    public function add_share_metabox(): void {
        add_meta_box(
            'apollo-share-stats',
            __( 'Estatísticas de Compartilhamento', 'apollo-events' ),
            array( $this, 'render_share_metabox' ),
            'event_listing',
            'side',
            'default'
        );
    }

    /**
     * Render share metabox.
     *
     * @since 2.0.0
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_share_metabox( $post ): void {
        $shares = get_post_meta( $post->ID, '_event_shares', true );
        $shares = is_array( $shares ) ? $shares : array();
        $total  = array_sum( $shares );

        ?>
        <div class="apollo-share-metabox">
            <div class="apollo-share-total">
                <strong><?php echo esc_html( $total ); ?></strong>
                <span><?php esc_html_e( 'compartilhamentos totais', 'apollo-events' ); ?></span>
            </div>

            <?php if ( ! empty( $shares ) ) : ?>
                <ul class="apollo-share-breakdown">
                    <?php foreach ( $shares as $network => $count ) : ?>
                        <?php if ( isset( $this->networks[ $network ] ) ) : ?>
                            <li>
                                <i class="<?php echo esc_attr( $this->networks[ $network ]['icon'] ); ?>"></i>
                                <span><?php echo esc_html( $this->networks[ $network ]['label'] ); ?>:</span>
                                <strong><?php echo esc_html( $count ); ?></strong>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="apollo-share-empty">
                    <?php esc_html_e( 'Nenhum compartilhamento ainda.', 'apollo-events' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <style>
            .apollo-share-metabox .apollo-share-total {
                text-align: center;
                padding: 15px;
                background: #f0f0f1;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            .apollo-share-metabox .apollo-share-total strong {
                display: block;
                font-size: 24px;
                color: #7c3aed;
            }
            .apollo-share-metabox .apollo-share-breakdown {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .apollo-share-metabox .apollo-share-breakdown li {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            .apollo-share-metabox .apollo-share-breakdown li:last-child {
                border-bottom: none;
            }
            .apollo-share-metabox .apollo-share-breakdown i {
                width: 20px;
                text-align: center;
            }
            .apollo-share-metabox .apollo-share-breakdown span {
                flex: 1;
            }
            .apollo-share-metabox .apollo-share-empty {
                color: #666;
                font-style: italic;
                text-align: center;
            }
        </style>
        <?php
    }

    /**
     * Get settings schema.
     *
     * @since 2.0.0
     * @return array
     */
    public function get_settings_schema(): array {
        return array(
            'auto_append'     => array(
                'type'        => 'boolean',
                'label'       => __( 'Adicionar automaticamente ao conteúdo', 'apollo-events' ),
                'default'     => true,
            ),
            'default_networks' => array(
                'type'        => 'text',
                'label'       => __( 'Redes padrão', 'apollo-events' ),
                'default'     => 'whatsapp,facebook,twitter,telegram,copy',
            ),
            'default_style'    => array(
                'type'        => 'select',
                'label'       => __( 'Estilo padrão', 'apollo-events' ),
                'options'     => array(
                    'buttons'  => __( 'Botões', 'apollo-events' ),
                    'icons'    => __( 'Apenas ícones', 'apollo-events' ),
                    'minimal'  => __( 'Minimalista', 'apollo-events' ),
                    'floating' => __( 'Flutuante', 'apollo-events' ),
                ),
                'default'     => 'buttons',
            ),
            'track_shares'     => array(
                'type'        => 'boolean',
                'label'       => __( 'Rastrear compartilhamentos', 'apollo-events' ),
                'default'     => true,
            ),
        );
    }
}
