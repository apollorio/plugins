<?php
/**
 * CENA Calendar Asset Enqueue Function
 *
 * Properly loads all assets with correct dependencies, security, and cache-busting
 *
 * @package Apollo_Social
 * @since 2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue CENA Calendar assets
 *
 * Call this function from your route handler or template loader
 * when rendering the CENA calendar page.
 */
function apollo_cena_enqueue_assets() {
    // Plugin paths and URLs
    $plugin_dir = APOLLO_SOCIAL_PLUGIN_DIR;
    $plugin_url = APOLLO_SOCIAL_PLUGIN_URL;

    $css_file = $plugin_dir . 'templates/cena/assets/cena-calendar.css';
    $js_file  = $plugin_dir . 'templates/cena/assets/cena-calendar.js';

    $css_url = $plugin_url . 'templates/cena/assets/cena-calendar.css';
    $js_url  = $plugin_url . 'templates/cena/assets/cena-calendar.js';

    // Get file modification times for cache-busting
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    $js_version  = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    // ==========================================
    // STYLES
    // ==========================================

    // Apollo CDN - MUST load first
    wp_enqueue_style(
        'apollo-cdn-css',
        'https://cdn.apollo.rio.br/apollo-core.css',
        array(),
        '1.0.0'
    );

    // Google Fonts - Inter
    wp_enqueue_style(
        'google-fonts-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // Remix Icon
    wp_enqueue_style(
        'remixicon',
        'https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css',
        array(),
        '4.0.0'
    );

    // Leaflet CSS (MUST load before custom CSS)
    wp_enqueue_style(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        array(),
        '1.9.4'
    );

    // CENA Custom Styles (depends on Leaflet CSS)
    wp_enqueue_style(
        'apollo-cena-calendar',
        $css_url,
        array( 'leaflet' ),
        $css_version,
        'all'
    );

    // ==========================================
    // SCRIPTS
    // ==========================================

    // Apollo CDN - MUST load first
    wp_enqueue_script(
        'apollo-cdn-js',
        'https://cdn.apollo.rio.br/apollo-core.js',
        array(),
        '1.0.0',
        true
    );

    // Leaflet JS (MUST load before our custom JS)
    wp_enqueue_script(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        array( 'apollo-cdn-js' ),
        '1.9.4',
        true // Load in footer
    );

    // CENA Calendar JS (depends on Leaflet)
    wp_enqueue_script(
        'apollo-cena-calendar',
        $js_url,
        array( 'leaflet' ),
        $js_version,
        true // Load in footer - CRITICAL for DOM ready
    );

    // ==========================================
    // INJECT RUNTIME CONFIG
    // ==========================================

    // Get current user data
    $current_user = wp_get_current_user();

    // Build config object with proper escaping
    $cena_config = array(
        'restUrl'      => esc_url_raw( rest_url( 'apollo/v1/cena-events' ) ),
        'restNonce'    => wp_create_nonce( 'wp_rest' ),
        'geocodeUrl'   => esc_url_raw( rest_url( 'apollo/v1/cena-geocode' ) ),
        'today'        => wp_date( 'Y-m-d' ),
        'currentYear'  => (int) wp_date( 'Y' ),
        'currentMonth' => (int) wp_date( 'm' ),
        'user'         => array(
            'id'        => get_current_user_id(),
            'username'  => $current_user->user_login,
            'canEdit'   => current_user_can( 'edit_posts' ),
            'canDelete' => current_user_can( 'delete_posts' ),
        ),
    );

    // Inject config as inline script BEFORE our JS loads
    wp_add_inline_script(
        'apollo-cena-calendar',
        'window.apolloCenaConfig = ' . wp_json_encode( $cena_config ) . ';',
        'before'
    );
}

/**
 * Add preconnect hints for external resources (performance optimization)
 */
function apollo_cena_preconnect_hints() {
    echo '<link rel="preconnect" href="https://cdn.apollo.rio.br">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://unpkg.com">' . "\n";
    echo '<link rel="preconnect" href="https://cdn.jsdelivr.net">' . "\n";
}
