<?php
/**
 * Event Single Enhanced Loader
 *
 * Handles enqueuing of assets and template overrides for enhanced single event pages.
 * Integrates with Apollo-Core bridge and Apollo-Social for favorites/RSVP.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

class Apollo_Event_Single_Enhanced_Loader {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Register hooks
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        // DISABLED: Template override removed to avoid conflict with canvas_template()
        // The main plugin's canvas_template() in apollo-events-manager.php handles template loading
        // This loader now only handles: assets enqueuing, AJAX favorites, and data enrichment
        // add_filter('single_template', [$this, 'load_enhanced_template'], 99);
        add_action('wp_ajax_apollo_toggle_favorite', [$this, 'ajax_toggle_favorite']);
        add_action('wp_ajax_nopriv_apollo_toggle_favorite', [$this, 'ajax_toggle_favorite']);

        // Apollo-Core bridge integration
        add_filter('apollo_core_event_data_transform', [$this, 'enrich_event_data'], 10, 2);
    }

    /**
     * Enqueue CSS and JS only on single event pages
     */
    public function enqueue_assets() {
        if (!is_singular('event_listing')) {
            return;
        }

        // Check if enhanced template is enabled (allow opt-out via filter)
        if (!apply_filters('apollo_event_use_enhanced_template', true)) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'apollo-event-single-enhanced',
            APOLLO_APRIO_URL . 'assets/css/event-single-enhanced.css',
            array(),
            '2.0.0',
            'all'
        );

        // Remixicon (if not already loaded)
        if (!wp_style_is('remixicon', 'enqueued')) {
            wp_enqueue_style(
                'remixicon',
                'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
                array(),
                '4.7.0'
            );
        }

        // JS
        wp_enqueue_script(
            'apollo-event-single-enhanced-js',
            APOLLO_APRIO_URL . 'assets/js/event-single-enhanced.js',
            array('jquery'),
            '2.0.0',
            true
        );

        // Localize script data (moved here to ensure script is enqueued)
        global $post;
        if ($post && $post->post_type === 'event_listing') {
            wp_localize_script('apollo-event-single-enhanced-js', 'apolloEventData', array(
                'eventId'   => $post->ID,
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('apollo_event_favorite'),
                'strings'   => array(
                    'loginRequired' => __('Por favor, faça login para favoritar este evento.', 'apollo-events-manager'),
                    'errorOccurred' => __('Ocorreu um erro. Tente novamente.', 'apollo-events-manager'),
                ),
            ));
        }
    }

    /**
     * Load enhanced template for event_listing
     */
    public function load_enhanced_template($template) {
        if (is_singular('event_listing')) {
            // Allow opt-out via filter or URL parameter
            $use_enhanced = apply_filters('apollo_event_use_enhanced_template', true);

            // Allow testing with ?enhanced=0 URL parameter
            if (isset($_GET['enhanced']) && $_GET['enhanced'] === '0') {
                return $template;
            }

            if ($use_enhanced) {
                $enhanced_template = APOLLO_APRIO_PATH . 'templates/single-event_listing-enhanced.php';
                if (file_exists($enhanced_template)) {
                    return $enhanced_template;
                }
            }
        }
        return $template;
    }

    /**
     * AJAX Handler: Toggle Favorite
     */
    public function ajax_toggle_favorite() {
        check_ajax_referer('apollo_event_favorite', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Login necessário para favoritar eventos.', 'apollo-events-manager')
            ]);
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $favorite = isset($_POST['favorite']) ? filter_var($_POST['favorite'], FILTER_VALIDATE_BOOLEAN) : false;

        if (!$event_id || get_post_type($event_id) !== 'event_listing') {
            wp_send_json_error([
                'message' => __('ID de evento inválido.', 'apollo-events-manager')
            ]);
        }

        $current_user_id = get_current_user_id();
        $interested_users = get_post_meta($event_id, '_event_interested_users', true);
        $interested_users = $interested_users ? (array)$interested_users : array();

        // Add or remove user from interested list
        if ($favorite) {
            if (!in_array($current_user_id, $interested_users)) {
                $interested_users[] = $current_user_id;
            }
        } else {
            $interested_users = array_diff($interested_users, [$current_user_id]);
        }

        // Update meta
        update_post_meta($event_id, '_event_interested_users', array_values($interested_users));
        update_post_meta($event_id, '_favorites_count', count($interested_users));

        // Integration with apollo-events-manager favorites module (if exists)
        if (class_exists('Apollo\\Modules\\Favorites\\FavoritesModule')) {
            try {
                $favorites_module = new \Apollo\Modules\Favorites\FavoritesModule();
                if ($favorite) {
                    $favorites_module->add_favorite($event_id, $current_user_id);
                } else {
                    $favorites_module->remove_favorite($event_id, $current_user_id);
                }
            } catch (Exception $e) {
                error_log('Apollo Events: Favorites module error - ' . $e->getMessage());
            }
        }

        // Fire action hook for further integrations
        do_action('apollo_event_favorite_toggled', $event_id, $current_user_id, $favorite);

        wp_send_json_success([
            'count' => count($interested_users),
            'favorited' => $favorite,
            'message' => $favorite
                ? __('Evento adicionado aos favoritos!', 'apollo-events-manager')
                : __('Evento removido dos favoritos.', 'apollo-events-manager')
        ]);
    }

    /**
     * Enrich event data via Apollo-Core bridge
     *
     * @param array $data Event data array
     * @param int $event_id Event post ID
     * @return array Enriched data
     */
    public function enrich_event_data($data, $event_id) {
        if (get_post_type($event_id) !== 'event_listing') {
            return $data;
        }

        // Add interested users count
        $interested_users = get_post_meta($event_id, '_event_interested_users', true);
        $data['interested_count'] = $interested_users ? count((array)$interested_users) : 0;

        // Add favorites count
        $data['favorites_count'] = get_post_meta($event_id, '_favorites_count', true) ?: 0;

        // Add current user favorited status
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $interested_users = (array)$interested_users;
            $data['user_favorited'] = in_array($current_user_id, $interested_users);
        } else {
            $data['user_favorited'] = false;
        }

        return $data;
    }
}

// Initialize loader
add_action('plugins_loaded', function() {
    Apollo_Event_Single_Enhanced_Loader::get_instance();
}, 20); // Priority 20 to ensure apollo-events-manager is loaded
