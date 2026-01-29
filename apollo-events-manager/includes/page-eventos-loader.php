<?php
/**
 * Page Eventos Loader
 * ====================
 * Path: apollo-events-manager/includes/page-eventos-loader.php
 *
 * Registers the /eventos/ page template and assets.
 * Handles rewrite rules and template loading.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Prevent redeclaration
if ( class_exists( 'Apollo_Page_Eventos_Loader' ) ) {
    return;
}

/**
 * Class Apollo_Page_Eventos_Loader
 */
class Apollo_Page_Eventos_Loader {

    /**
     * Singleton instance.
     *
     * @var Apollo_Page_Eventos_Loader
     */
    private static $instance = null;

    /**
     * Page slug for eventos.
     *
     * @var string
     */
    const PAGE_SLUG = 'eventos';

    /**
     * Get singleton instance.
     *
     * @return Apollo_Page_Eventos_Loader
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Register hooks.
     */
    private function __construct() {
        // Template loading
        add_filter( 'template_include', array( $this, 'load_eventos_template' ), 100 );

        // Register assets
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );

        // Ensure page exists
        add_action( 'init', array( $this, 'ensure_eventos_page' ), 30 );
    }

    /**
     * Load eventos template for the page.
     *
     * @param string $template Current template path.
     * @return string Modified template path.
     */
    public function load_eventos_template( $template ) {
        // Check if this is the eventos page
        if ( ! $this->is_eventos_page() ) {
            return $template;
        }

        // Look for template in plugin - use multiple fallback paths
        $plugin_dir = '';
        if ( defined( 'APOLLO_APRIO_PATH' ) ) {
            $plugin_dir = APOLLO_APRIO_PATH;
        } elseif ( defined( 'APOLLO_APRIO_PLUGIN_DIR' ) ) {
            $plugin_dir = APOLLO_APRIO_PLUGIN_DIR;
        } else {
            $plugin_dir = plugin_dir_path( dirname( __FILE__ ) );
        }
        $plugin_template = $plugin_dir . 'templates/page-eventos.php';

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
    }

    /**
     * Check if current page is the eventos page.
     *
     * @return bool
     */
    public function is_eventos_page() {
        if ( is_admin() ) {
            return false;
        }

        // Check by page slug
        if ( is_page( self::PAGE_SLUG ) ) {
            return true;
        }

        // Check by page template
        if ( is_page() && 'page-eventos.php' === get_page_template_slug() ) {
            return true;
        }

        // Check by post type archive
        if ( is_post_type_archive( 'event_listing' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Register page assets.
     *
     * @return void
     */
    public function register_assets() {
        $plugin_url = defined( 'APOLLO_APRIO_URL' ) ? APOLLO_APRIO_URL : plugins_url( '/', dirname( __FILE__ ) );
        $version    = defined( 'APOLLO_APRIO_VERSION' ) ? APOLLO_APRIO_VERSION : '2.0.0';

        // Event card CSS (main design)
        wp_register_style(
            'apollo-event-card',
            $plugin_url . 'assets/css/event-card.css',
            array(),
            $version
        );

        // Page eventos CSS
        wp_register_style(
            'apollo-page-eventos',
            $plugin_url . 'assets/css/page-eventos.css',
            array( 'apollo-event-card' ),
            $version
        );

        // Event modal content CSS
        wp_register_style(
            'apollo-event-modal-content',
            $plugin_url . 'assets/css/event-modal-content.css',
            array(),
            $version
        );

        // Page eventos JS
        wp_register_script(
            'apollo-page-eventos',
            $plugin_url . 'assets/js/page-eventos.js',
            array( 'jquery' ),
            $version,
            true
        );

        // Global event modal system JS (for ALL pages with event cards)
        wp_register_script(
            'apollo-event-modal-system',
            $plugin_url . 'assets/js/event-modal-system.js',
            array( 'jquery' ),
            $version,
            true
        );
    }

    /**
     * Enqueue page assets when needed.
     *
     * @return void
     */
    public function enqueue_assets() {
        $plugin_url = defined( 'APOLLO_APRIO_URL' ) ? APOLLO_APRIO_URL : plugins_url( '/', dirname( __FILE__ ) );

        // ALWAYS enqueue the global modal system (works on any page with event cards)
        wp_enqueue_style( 'apollo-event-modal-content' );
        wp_enqueue_script( 'apollo-event-modal-system' );

        // Localize global modal script
        wp_localize_script( 'apollo-event-modal-system', 'apolloEventModalData', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'apollo_eventos_nonce' ),
            'singleBase' => home_url( '/evento/' ),
        ) );

        // Only on eventos page - additional assets
        if ( ! $this->is_eventos_page() ) {
            return;
        }

        // Enqueue page-specific assets
        wp_enqueue_style( 'apollo-event-card' );
        wp_enqueue_style( 'apollo-page-eventos' );
        wp_enqueue_script( 'apollo-page-eventos' );

        // Localize script with data
        $month_names = array(
            1 => __( 'Jan', 'apollo-events-manager' ),
            2 => __( 'Fev', 'apollo-events-manager' ),
            3 => __( 'Mar', 'apollo-events-manager' ),
            4 => __( 'Abr', 'apollo-events-manager' ),
            5 => __( 'Mai', 'apollo-events-manager' ),
            6 => __( 'Jun', 'apollo-events-manager' ),
            7 => __( 'Jul', 'apollo-events-manager' ),
            8 => __( 'Ago', 'apollo-events-manager' ),
            9 => __( 'Set', 'apollo-events-manager' ),
            10 => __( 'Out', 'apollo-events-manager' ),
            11 => __( 'Nov', 'apollo-events-manager' ),
            12 => __( 'Dez', 'apollo-events-manager' ),
        );

        wp_localize_script( 'apollo-page-eventos', 'apolloEventosData', array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'restUrl'      => rest_url( 'apollo/v1/events' ),
            'nonce'        => wp_create_nonce( 'apollo_eventos_nonce' ),
            'baseUrl'      => home_url( '/' . self::PAGE_SLUG . '/' ),
            'singleBase'   => home_url( '/evento/' ),
            'popupEventId' => isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0,
            'monthNames'   => $month_names,
            'i18n'         => array(
                'loading'  => __( 'Carregando...', 'apollo-events-manager' ),
                'error'    => __( 'Erro ao carregar evento', 'apollo-events-manager' ),
                'close'    => __( 'Fechar', 'apollo-events-manager' ),
            ),
        ) );
    }

    /**
     * Ensure eventos page exists.
     *
     * @return void
     */
    public function ensure_eventos_page() {
        // Only run once per day
        $last_check = get_transient( 'apollo_eventos_page_check' );
        if ( $last_check ) {
            return;
        }

        // Check if page exists
        $page = get_page_by_path( self::PAGE_SLUG );

        if ( ! $page ) {
            // Create the page
            $page_id = wp_insert_post( array(
                'post_title'   => __( 'Eventos', 'apollo-events-manager' ),
                'post_name'    => self::PAGE_SLUG,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
                'meta_input'   => array(
                    '_wp_page_template' => 'page-eventos.php',
                ),
            ) );

            if ( ! is_wp_error( $page_id ) ) {
                // Flush rewrite rules
                flush_rewrite_rules();
            }
        }

        // Set transient for 24 hours
        set_transient( 'apollo_eventos_page_check', true, DAY_IN_SECONDS );
    }
}

// Initialize
Apollo_Page_Eventos_Loader::get_instance();
