<?php
/**
 * AJAX Handler: Event Single HTML for Modal
 * ==========================================
 * Path: apollo-events-manager/includes/ajax/class-event-modal-ajax.php
 *
 * Provides AJAX endpoint to load single event HTML for popup modal.
 * Returns the event single template content as HTML string.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Prevent redeclaration
if ( class_exists( 'Apollo_Event_Modal_Ajax' ) ) {
    return;
}

/**
 * Class Apollo_Event_Modal_Ajax
 *
 * Handles AJAX requests for event modal popup system.
 */
class Apollo_Event_Modal_Ajax {

    /**
     * Singleton instance.
     *
     * @var Apollo_Event_Modal_Ajax
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return Apollo_Event_Modal_Ajax
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Register AJAX hooks.
     */
    private function __construct() {
        add_action( 'wp_ajax_apollo_get_event_single_html', array( $this, 'get_event_single_html' ) );
        add_action( 'wp_ajax_nopriv_apollo_get_event_single_html', array( $this, 'get_event_single_html' ) );
    }

    /**
     * AJAX Handler: Get event single page HTML.
     *
     * @return void Outputs JSON response.
     */
    public function get_event_single_html() {
        // Verify nonce - but allow nopriv requests with basic rate limiting
        $nonce_valid = isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'apollo_eventos_nonce' );

        // For public AJAX (nopriv), we still want to work but add basic protection
        if ( ! $nonce_valid ) {
            // Allow if it's a nopriv request but add rate limiting via transient
            $ip_hash = md5( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
            $rate_key = 'apollo_modal_rate_' . $ip_hash;
            $requests = get_transient( $rate_key );

            if ( $requests && (int) $requests > 30 ) {
                wp_send_json_error( array( 'message' => __( 'Muitas requisições. Tente novamente em alguns minutos.', 'apollo-events-manager' ) ) );
            }

            set_transient( $rate_key, ( (int) $requests + 1 ), MINUTE_IN_SECONDS );
        }

        // Get event ID
        $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
        $context  = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : 'modal';

        if ( ! $event_id ) {
            wp_send_json_error( array( 'message' => __( 'ID do evento inválido.', 'apollo-events-manager' ) ) );
        }

        // Get the event post
        $event = get_post( $event_id );

        if ( ! $event || 'event_listing' !== $event->post_type || 'publish' !== $event->post_status ) {
            wp_send_json_error( array( 'message' => __( 'Evento não encontrado.', 'apollo-events-manager' ) ) );
        }

        // Set up post data
        global $post;
        $post = $event;
        setup_postdata( $post );

        // Start output buffering
        ob_start();

        // Render event modal content
        $this->render_event_modal_content( $event_id, $context );

        $html = ob_get_clean();

        // Reset post data
        wp_reset_postdata();

        // Send response
        wp_send_json_success( array(
            'html'      => $html,
            'event_id'  => $event_id,
            'title'     => get_post_meta( $event_id, '_event_title', true ) ?: get_the_title( $event_id ),
            'permalink' => get_permalink( $event_id ),
        ) );
    }

    /**
     * Render event modal content.
     *
     * @param int    $event_id Event post ID.
     * @param string $context  Rendering context (modal, embed, etc).
     * @return void Outputs HTML.
     */
    private function render_event_modal_content( $event_id, $context = 'modal' ) {
        // Get all event meta
        $event_title    = get_post_meta( $event_id, '_event_title', true ) ?: get_the_title( $event_id );
        $event_banner   = get_post_meta( $event_id, '_event_banner', true );
        $event_video    = get_post_meta( $event_id, '_event_video_url', true );
        $event_desc     = get_post_field( 'post_content', $event_id );
        $start_date     = get_post_meta( $event_id, '_event_start_date', true );
        $start_time     = get_post_meta( $event_id, '_event_start_time', true );
        $end_time       = get_post_meta( $event_id, '_event_end_time', true );
        $location_name  = get_post_meta( $event_id, '_event_location', true );
        $tickets_url    = get_post_meta( $event_id, '_tickets_ext', true );
        $cupom          = get_post_meta( $event_id, '_cupom_ario', true );
        $dj_slots       = get_post_meta( $event_id, '_event_dj_slots', true );
        $local_ids      = get_post_meta( $event_id, '_event_local_ids', true );
        $promo_images   = get_post_meta( $event_id, '_3_imagens_promo', true );
        $favorites_count = get_post_meta( $event_id, '_favorites_count', true ) ?: 0;

        // Get taxonomies
        $sounds_terms = get_the_terms( $event_id, 'event_sounds' );
        $season_terms = get_the_terms( $event_id, 'event_season' );

        // Format date
        $date_formatted = '';
        if ( $start_date ) {
            $date_obj = DateTime::createFromFormat( 'Y-m-d', $start_date );
            if ( $date_obj ) {
                $date_formatted = $date_obj->format( 'd M \'y' );
            }
        }

        // Get local data
        $local_data = array(
            'name'      => '',
            'address'   => '',
            'city'      => '',
            'latitude'  => '',
            'longitude' => '',
            'images'    => array(),
        );

        if ( ! empty( $local_ids ) ) {
            $local_id = is_array( $local_ids ) ? $local_ids[0] : $local_ids;
            $local_data = array(
                'name'      => get_post_meta( $local_id, '_local_name', true ) ?: get_the_title( $local_id ),
                'address'   => get_post_meta( $local_id, '_local_address', true ),
                'city'      => get_post_meta( $local_id, '_local_city', true ),
                'latitude'  => get_post_meta( $local_id, '_local_latitude', true ),
                'longitude' => get_post_meta( $local_id, '_local_longitude', true ),
                'images'    => array(),
            );

            for ( $i = 1; $i <= 5; $i++ ) {
                $img = get_post_meta( $local_id, "_local_image_{$i}", true );
                if ( $img ) {
                    $local_data['images'][] = $img;
                }
            }

            if ( empty( $location_name ) ) {
                $location_name = $local_data['name'];
            }
        }

        // Full address
        $full_address = '';
        if ( ! empty( $local_data['address'] ) && ! empty( $local_data['city'] ) ) {
            $full_address = $local_data['address'] . ', ' . $local_data['city'];
        }

        // RSVP users
        $interested_users = get_post_meta( $event_id, '_event_interested_users', true );
        $interested_users = $interested_users ? (array) $interested_users : array();
        $interested_count = count( $interested_users );

        // Current user favorited
        $current_user_favorited = false;
        if ( is_user_logged_in() ) {
            $current_user_id = get_current_user_id();
            $current_user_favorited = in_array( $current_user_id, $interested_users, true );
        }

        // Fallback banner
        if ( empty( $event_banner ) ) {
            $event_banner = get_the_post_thumbnail_url( $event_id, 'large' );
        }

        // Hero type
        $hero_type = ! empty( $event_video ) ? 'video' : 'image';
        ?>
        <div class="apollo-event-single-page apollo-event-modal-content" data-event-id="<?php echo esc_attr( $event_id ); ?>">
            <div class="mobile-container">

                <!-- Hero Media -->
                <section class="event-hero section">
                    <?php if ( 'video' === $hero_type && $event_video ) : ?>
                    <div class="event-hero-video">
                        <?php
                        // Handle different video sources
                        if ( strpos( $event_video, 'youtube.com' ) !== false || strpos( $event_video, 'youtu.be' ) !== false ) {
                            $video_id = '';
                            if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $event_video, $match ) ) {
                                $video_id = $match[1];
                            }
                            if ( $video_id ) {
                                echo '<iframe src="https://www.youtube.com/embed/' . esc_attr( $video_id ) . '?autoplay=0&rel=0" frameborder="0" allowfullscreen loading="lazy"></iframe>';
                            }
                        } elseif ( strpos( $event_video, 'vimeo.com' ) !== false ) {
                            $video_id = '';
                            if ( preg_match( '/vimeo\.com\/(\d+)/', $event_video, $match ) ) {
                                $video_id = $match[1];
                            }
                            if ( $video_id ) {
                                echo '<iframe src="https://player.vimeo.com/video/' . esc_attr( $video_id ) . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
                            }
                        } else {
                            echo '<video src="' . esc_url( $event_video ) . '" controls preload="metadata"></video>';
                        }
                        ?>
                    </div>
                    <?php else : ?>
                    <div class="event-hero-image">
                        <?php if ( $event_banner ) : ?>
                        <img src="<?php echo esc_url( $event_banner ); ?>" alt="<?php echo esc_attr( $event_title ); ?>" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="event-hero-info">
                        <?php if ( $date_formatted || $start_time ) : ?>
                        <div class="event-datetime">
                            <?php if ( $date_formatted ) : ?>
                            <span class="event-date"><?php echo esc_html( $date_formatted ); ?></span>
                            <?php endif; ?>
                            <?php if ( $start_time ) : ?>
                            <span class="event-time">
                                <?php echo esc_html( $start_time ); ?>
                                <?php if ( $end_time ) : ?>
                                - <?php echo esc_html( $end_time ); ?>
                                <?php endif; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <h1 class="event-title"><?php echo esc_html( $event_title ); ?></h1>

                        <?php if ( $location_name ) : ?>
                        <div class="event-location">
                            <i class="ri-map-pin-2-line"></i>
                            <span><?php echo esc_html( $location_name ); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ( $season_terms && ! is_wp_error( $season_terms ) ) : ?>
                        <div class="event-season">
                            <?php foreach ( $season_terms as $term ) : ?>
                            <span class="season-badge"><?php echo esc_html( $term->name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Event Body -->
                <div class="event-body">

                    <!-- Quick Actions -->
                    <section class="event-quick-actions section">
                        <div class="quick-actions-row">
                            <button type="button" class="action-btn share-btn" data-event-id="<?php echo esc_attr( $event_id ); ?>">
                                <i class="ri-share-line"></i>
                                <span><?php esc_html_e( 'Compartilhar', 'apollo-events-manager' ); ?></span>
                            </button>
                            <button type="button" class="action-btn favorite-btn <?php echo $current_user_favorited ? 'active' : ''; ?>" data-event-id="<?php echo esc_attr( $event_id ); ?>">
                                <i class="<?php echo $current_user_favorited ? 'ri-heart-fill' : 'ri-heart-line'; ?>"></i>
                                <span><?php echo esc_html( $favorites_count ); ?></span>
                            </button>
                            <?php if ( $full_address ) : ?>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr( urlencode( $full_address ) ); ?>" target="_blank" rel="noopener noreferrer" class="action-btn map-btn">
                                <i class="ri-map-2-line"></i>
                                <span><?php esc_html_e( 'Mapa', 'apollo-events-manager' ); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- RSVP Section -->
                    <?php if ( $interested_count > 0 ) : ?>
                    <section class="event-rsvp section">
                        <div class="rsvp-avatars">
                            <?php
                            $display_users = array_slice( $interested_users, 0, 5 );
                            foreach ( $display_users as $user_id ) :
                                $avatar = get_avatar_url( $user_id, array( 'size' => 48 ) );
                            ?>
                            <img src="<?php echo esc_url( $avatar ); ?>" alt="" class="rsvp-avatar">
                            <?php endforeach; ?>
                            <?php if ( $interested_count > 5 ) : ?>
                            <span class="rsvp-more">+<?php echo esc_html( $interested_count - 5 ); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="rsvp-text">
                            <?php
                            printf(
                                /* translators: %d: number of interested users */
                                esc_html( _n( '%d pessoa interessada', '%d pessoas interessadas', $interested_count, 'apollo-events-manager' ) ),
                                $interested_count
                            );
                            ?>
                        </span>
                    </section>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if ( ! empty( $event_desc ) ) : ?>
                    <section class="event-description section">
                        <h2 class="section-title"><?php esc_html_e( 'Sobre', 'apollo-events-manager' ); ?></h2>
                        <div class="description-content">
                            <?php echo wp_kses_post( wpautop( $event_desc ) ); ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Sounds/Genres -->
                    <?php if ( $sounds_terms && ! is_wp_error( $sounds_terms ) ) : ?>
                    <section class="event-sounds section">
                        <div class="sounds-tags">
                            <?php foreach ( $sounds_terms as $term ) : ?>
                            <span class="sound-tag"><?php echo esc_html( $term->name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Promo Gallery -->
                    <?php if ( ! empty( $promo_images ) && is_array( $promo_images ) ) : ?>
                    <section class="event-gallery section">
                        <h2 class="section-title"><?php esc_html_e( 'Galeria', 'apollo-events-manager' ); ?></h2>
                        <div class="gallery-grid">
                            <?php foreach ( $promo_images as $img ) : ?>
                            <div class="gallery-item">
                                <img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- DJ Lineup -->
                    <?php if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) : ?>
                    <section class="event-lineup section">
                        <h2 class="section-title"><?php esc_html_e( 'Line-up', 'apollo-events-manager' ); ?></h2>
                        <div class="lineup-list">
                            <?php foreach ( $dj_slots as $slot ) :
                                if ( empty( $slot['dj_id'] ) ) continue;
                                $dj_id    = absint( $slot['dj_id'] );
                                $dj_name  = get_post_meta( $dj_id, '_dj_name', true ) ?: get_the_title( $dj_id );
                                $dj_image = get_post_meta( $dj_id, '_dj_image', true );
                                $dj_time  = $slot['time'] ?? '';
                            ?>
                            <div class="lineup-item">
                                <div class="dj-avatar">
                                    <?php if ( $dj_image ) : ?>
                                    <img src="<?php echo esc_url( $dj_image ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>" loading="lazy">
                                    <?php else : ?>
                                    <i class="ri-user-3-line"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="dj-info">
                                    <span class="dj-name"><?php echo esc_html( $dj_name ); ?></span>
                                    <?php if ( $dj_time ) : ?>
                                    <span class="dj-time"><?php echo esc_html( $dj_time ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Venue Section -->
                    <?php if ( ! empty( $local_data['name'] ) ) : ?>
                    <section class="event-venue section">
                        <h2 class="section-title"><?php esc_html_e( 'Local', 'apollo-events-manager' ); ?></h2>
                        <div class="venue-card">
                            <h3 class="venue-name"><?php echo esc_html( $local_data['name'] ); ?></h3>
                            <?php if ( ! empty( $full_address ) ) : ?>
                            <p class="venue-address">
                                <i class="ri-map-pin-line"></i>
                                <?php echo esc_html( $full_address ); ?>
                            </p>
                            <?php endif; ?>

                            <?php if ( ! empty( $local_data['latitude'] ) && ! empty( $local_data['longitude'] ) ) : ?>
                            <div class="venue-map" id="venueMap" data-lat="<?php echo esc_attr( $local_data['latitude'] ); ?>" data-lng="<?php echo esc_attr( $local_data['longitude'] ); ?>">
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr( $local_data['latitude'] . ',' . $local_data['longitude'] ); ?>" target="_blank" rel="noopener noreferrer" class="map-placeholder">
                                    <i class="ri-map-2-line"></i>
                                    <span><?php esc_html_e( 'Ver no Google Maps', 'apollo-events-manager' ); ?></span>
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $local_data['images'] ) ) : ?>
                            <div class="venue-gallery">
                                <?php foreach ( array_slice( $local_data['images'], 0, 3 ) as $img ) : ?>
                                <img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Tickets Section -->
                    <?php if ( $tickets_url || $cupom ) : ?>
                    <section class="event-tickets section">
                        <?php if ( $cupom ) : ?>
                        <div class="coupon-box">
                            <div class="coupon-label"><?php esc_html_e( 'Cupom de desconto', 'apollo-events-manager' ); ?></div>
                            <div class="coupon-code" data-coupon="<?php echo esc_attr( $cupom ); ?>">
                                <span><?php echo esc_html( $cupom ); ?></span>
                                <button type="button" class="copy-coupon" data-copy="<?php echo esc_attr( $cupom ); ?>">
                                    <i class="ri-file-copy-line"></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( $tickets_url ) : ?>
                        <a href="<?php echo esc_url( $tickets_url ); ?>" target="_blank" rel="noopener noreferrer" class="tickets-btn">
                            <i class="ri-ticket-2-line"></i>
                            <span><?php esc_html_e( 'Comprar Ingressos', 'apollo-events-manager' ); ?></span>
                            <i class="ri-external-link-line"></i>
                        </a>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>

                </div>

                <!-- Protection Notice -->
                <section class="section">
                    <div class="respaldo_eve">
                        <?php esc_html_e( '*A organização e execução deste evento cabem integralmente aos seus idealizadores.', 'apollo-events-manager' ); ?>
                    </div>
                </section>

            </div>
        </div>
        <?php
    }
}

// Initialize
Apollo_Event_Modal_Ajax::get_instance();
