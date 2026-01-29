<?php
/**
 * Template: Single Event Enhanced - Apollo Design
 * ===============================================
 * Path: apollo-events-manager/templates/single-event_listing-enhanced.php
 *
 * Full single event page with Berlin Brutalism mobile-first design.
 * Integrates with Apollo-Events-Manager CPTs and meta keys.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Get event data
global $post;
$event_id = get_the_ID();

// Get all event meta
$event_title    = get_post_meta( $event_id, '_event_title', true ) ?: get_the_title();
$event_banner   = get_post_meta( $event_id, '_event_banner', true );
$event_video    = get_post_meta( $event_id, '_event_video_url', true );
$event_desc     = get_the_content();
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
$sounds_terms   = get_the_terms( $event_id, 'event_sounds' );
$season_terms   = get_the_terms( $event_id, 'event_season' );

// Format date
$date_formatted = '';
if ( $start_date ) {
    $date_obj = DateTime::createFromFormat( 'Y-m-d', $start_date );
    if ( $date_obj ) {
        $date_formatted = $date_obj->format( 'd M \'\y' );
    }
}

// Get local data from first local_id
$local_data = array();
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

    // Get local images
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

// Full address for Google Maps
$full_address = '';
if ( ! empty( $local_data['address'] ) && ! empty( $local_data['city'] ) ) {
    $full_address = $local_data['address'] . ', ' . $local_data['city'];
}

// Get RSVP users from Apollo-Social integration
$interested_users = get_post_meta( $event_id, '_event_interested_users', true );
$interested_users = $interested_users ? (array) $interested_users : array();
$interested_count = count( $interested_users );

// Check if current user favorited
$current_user_favorited = false;
if ( is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    $current_user_favorited = in_array( $current_user_id, $interested_users );
}

// Fallback banner
if ( empty( $event_banner ) ) {
    $event_banner = get_the_post_thumbnail_url( $event_id, 'large' );
}

// Hero media type
$hero_type = ! empty( $event_video ) ? 'video' : 'image';

get_header();
?>

<div class="apollo-event-single-page">
    <div class="mobile-container">

        <!-- Hero Media -->
        <?php get_template_part( 'template-parts/event/hero', null, array(
            'hero_type'     => $hero_type,
            'video_url'     => $event_video,
            'banner_url'    => $event_banner,
            'title'         => $event_title,
            'date'          => $date_formatted,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'location'      => $location_name,
            'season_terms'  => $season_terms,
        ) ); ?>

        <!-- Event Body -->
        <div class="event-body">

            <!-- Quick Actions -->
            <?php get_template_part( 'template-parts/event/quick-actions' ); ?>

            <!-- RSVP Row -->
            <?php get_template_part( 'template-parts/event/rsvp', null, array(
                'users'             => $interested_users,
                'count'             => $interested_count,
                'user_favorited'    => $current_user_favorited,
            ) ); ?>

            <!-- Info Section -->
            <?php get_template_part( 'template-parts/event/info', null, array(
                'description'   => $event_desc,
                'sounds_terms'  => $sounds_terms,
            ) ); ?>

            <!-- Promo Gallery -->
            <?php if ( ! empty( $promo_images ) && is_array( $promo_images ) ) :
                get_template_part( 'template-parts/event/promo-gallery', null, array(
                    'images' => $promo_images,
                ) );
            endif; ?>

            <!-- DJ Lineup -->
            <?php if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) :
                get_template_part( 'template-parts/event/lineup', null, array(
                    'dj_slots' => $dj_slots,
                ) );
            endif; ?>

            <!-- Venue Section -->
            <?php if ( ! empty( $local_data['name'] ) ) :
                get_template_part( 'template-parts/event/venue', null, array(
                    'local_name'    => $local_data['name'],
                    'address'       => $local_data['address'] ?? '',
                    'city'          => $local_data['city'] ?? '',
                    'full_address'  => $full_address,
                    'latitude'      => $local_data['latitude'] ?? '',
                    'longitude'     => $local_data['longitude'] ?? '',
                    'images'        => $local_data['images'] ?? array(),
                ) );
            endif; ?>

            <!-- Tickets Section -->
            <?php get_template_part( 'template-parts/event/tickets', null, array(
                'tickets_url'   => $tickets_url,
                'cupom'         => $cupom,
            ) ); ?>

            <!-- Final Event Image -->
            <?php if ( ! empty( $event_banner ) ) : ?>
                <section class="section">
                    <div class="secondary-image">
                        <img src="<?php echo esc_url( $event_banner ); ?>" alt="<?php echo esc_attr( $event_title ); ?>">
                    </div>
                </section>
            <?php endif; ?>

        </div>

        <!-- Protection -->
        <section class="section">
            <div class="respaldo_eve">
                <?php esc_html_e( '*A organização e execução deste evento cabem integralmente aos seus idealizadores.', 'apollo-events-manager' ); ?>
            </div>
        </section>

    </div>

    <!-- Bottom Bar -->
    <?php get_template_part( 'template-parts/event/bottom-bar', null, array(
        'tickets_url' => $tickets_url,
    ) ); ?>

</div>

<?php
// Localize script data
wp_localize_script( 'apollo-event-single-enhanced-js', 'apolloEventData', array(
    'eventId'   => $event_id,
    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
    'nonce'     => wp_create_nonce( 'apollo_event_favorite' ),
) );

get_footer();
