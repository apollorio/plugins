<?php
/**
 * Plugin Name: Apollo Events Manager
 * Plugin URI: https://apollo.rio.br
 * Description: Complete event management system for Apollo::Rio with custom templates, maps, and favorites
 * Version: 0.1.0
 * Author: Apollo Events Team
 * License: GPL v2 or later
 * Text Domain: apollo-events-manager
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('APOLLO_WPEM_VERSION', '0.1.0');
define('APOLLO_WPEM_PATH', plugin_dir_path(__FILE__));
define('APOLLO_WPEM_URL', plugin_dir_url(__FILE__));

// Debug mode (enable in wp-config.php with: define('APOLLO_DEBUG', true);)
if (!defined('APOLLO_DEBUG')) {
    define('APOLLO_DEBUG', false);
}

/**
 * Helper function: Parse event start date
 * Aceita _event_start_date em "Y-m-d", "Y-m-d H:i:s" ou o que strtotime() aceitar.
 * Retorna array com: timestamp, day, month_pt, iso_date, iso_dt
 */
if (!function_exists('apollo_eve_parse_start_date')) {
    function apollo_eve_parse_start_date($raw) {
        $raw = trim((string) $raw);
        
        if ($raw === '') {
            return array(
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            );
        }
        
        // 1) tenta parser direto
        $ts = strtotime($raw);
        
        // 2) fallback: se vier só "Y-m-d", garante datetime
        if (!$ts) {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            if ($dt instanceof DateTime) {
                $ts = $dt->getTimestamp();
            }
        }
        
        if (!$ts) {
            // nada deu certo
            return array(
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            );
        }
        
        $pt_months = array('jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez');
        $month_idx = (int) date_i18n('n', $ts) - 1;
        
        return array(
            'timestamp' => $ts,
            'day'       => date_i18n('d', $ts),
            'month_pt'  => $pt_months[$month_idx] ?? '',
            'iso_date'  => date_i18n('Y-m-d', $ts),
            'iso_dt'    => date_i18n('Y-m-d H:i:s', $ts),
        );
    }
}

// apollo-events-manager.php (top-level helper) - DEFENSIVE VERSION
function apollo_cfg(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $path = plugin_dir_path(__FILE__) . 'includes/config.php';
    if (!file_exists($path)) return [];

    // Capture output buffer to prevent leaks
    ob_start();
    $loaded = include $path;
    $leaked = ob_get_clean();
    
    // Log if config leaked content
    if (!empty($leaked)) {
        error_log('Apollo Config leaked content: ' . $leaked);
    }
    
    $cfg = is_array($loaded) ? $loaded : [];
    return $cfg;
}

// Include AJAX handlers
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';

// Load placeholder registry and access API
require_once plugin_dir_path(__FILE__) . 'includes/class-apollo-events-placeholders.php';

// Load analytics and statistics
require_once plugin_dir_path(__FILE__) . 'includes/class-apollo-events-analytics.php';

/**
 * Main Plugin Class
 */
class Apollo_Events_Manager_Plugin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'ensure_events_page'));
        add_filter('template_include', array($this, 'canvas_template'), 99);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Shortcodes
        add_shortcode('apollo_events', array($this, 'events_shortcode'));
        add_shortcode('eventos-page', array($this, 'eventos_page_shortcode'));
        add_shortcode('apollo_event', array($this, 'apollo_event_shortcode'));
        add_shortcode('apollo_event_user_overview', array($this, 'apollo_event_user_overview_shortcode'));
        
        // Additional shortcodes
        add_shortcode('events', array($this, 'events_shortcode')); // Alias
        add_shortcode('event', array($this, 'event_single_shortcode')); // NEW: Full event content for lightbox
        add_shortcode('event_djs', array($this, 'event_djs_shortcode'));
        add_shortcode('event_locals', array($this, 'event_locals_shortcode'));
        add_shortcode('event_summary', array($this, 'event_summary_shortcode'));
        add_shortcode('local_dashboard', array($this, 'local_dashboard_shortcode'));
        add_shortcode('past_events', array($this, 'past_events_shortcode'));
        add_shortcode('single_event_dj', array($this, 'single_event_dj_shortcode'));
        add_shortcode('single_event_local', array($this, 'single_event_local_shortcode'));
        add_shortcode('submit_event_form', array($this, 'submit_event_form_shortcode'));
        add_shortcode('submit_dj_form', array($this, 'submit_dj_form_shortcode'));
        add_shortcode('submit_local_form', array($this, 'submit_local_form_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_filter_events', array($this, 'ajax_filter_events'));
        add_action('wp_ajax_nopriv_filter_events', array($this, 'ajax_filter_events'));
        add_action('wp_ajax_load_event_single', array($this, 'ajax_load_event_single'));
        add_action('wp_ajax_nopriv_load_event_single', array($this, 'ajax_load_event_single'));
        
        // Favorites AJAX handlers
        add_action('wp_ajax_toggle_favorite', array($this, 'ajax_toggle_favorite'));
        add_action('wp_ajax_nopriv_toggle_favorite', array($this, 'ajax_toggle_favorite'));
        
        // Modal AJAX handler
        add_action('wp_ajax_apollo_get_event_modal', array($this, 'ajax_get_event_modal'));
        add_action('wp_ajax_nopriv_apollo_get_event_modal', array($this, 'ajax_get_event_modal'));
        
        // Force Brazil as default country
        add_filter('submit_event_form_fields', array($this, 'force_brazil_country'));

        // Add custom fields to event submission form
        add_filter('submit_event_form_fields', array($this, 'add_custom_event_fields'));

        // Validate custom fields
        add_filter('submit_event_form_validate_fields', array($this, 'validate_custom_event_fields'));

        // Save custom fields (using native WordPress hook instead of WPEM hook)
        add_action('save_post_event_listing', array($this, 'save_custom_event_fields'), 10, 2);

        // Auto-geocoding for event_local posts
        add_action('save_post_event_local', array($this, 'auto_geocode_local'), 10, 2);

        // Load post types registration (INDEPENDENT)
        require_once APOLLO_WPEM_PATH . 'includes/post-types.php';
        
        // Load data migration utilities (for WP-CLI and maintenance)
        require_once APOLLO_WPEM_PATH . 'includes/data-migration.php';
        
        // Load admin metaboxes
        if (is_admin()) {
            $admin_file = APOLLO_WPEM_PATH . 'includes/admin-metaboxes.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
            }
            
            // Load migration validator
            require_once APOLLO_WPEM_PATH . 'includes/migration-validator.php';
        }
        
        // Backward compatibility layer (prevents fatal errors if WPEM reactivated)
        add_filter('event_manager_event_listing_templates', array($this, 'wpem_compatibility_notice'), 1);
        add_filter('event_manager_single_event_templates', array($this, 'wpem_compatibility_notice'), 1);
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Admin menu for placeholders documentation and analytics
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Track event views when modal is opened
        add_action('wp_ajax_apollo_get_event_modal', array($this, 'track_event_view_on_modal'), 5);
        add_action('wp_ajax_nopriv_apollo_get_event_modal', array($this, 'track_event_view_on_modal'), 5);
        
        // Debug footer
        if (APOLLO_DEBUG) {
            add_action('wp_footer', array($this, 'debug_footer'));
        }

        // Content injector for single events
        add_filter('the_content', array($this, 'inject_event_content'), 10);
    }

    /**
     * WPEM Compatibility Notice
     * Prevents fatal errors if WPEM is accidentally reactivated
     */
    public function wpem_compatibility_notice($templates) {
        if (class_exists('WP_Event_Manager')) {
            error_log('⚠️ WPEM hook called but Apollo is independent now. Consider deactivating WP Event Manager.');
        }
        return $templates;
    }
    
    /**
     * Admin Notices
     */
    public function admin_notices() {
        // Notice if WPEM is still active
        if (class_exists('WP_Event_Manager')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>⚠️ Apollo Events Manager</strong> is now independent and no longer requires WP Event Manager.</p>';
            echo '<p>You can safely <a href="' . admin_url('plugins.php') . '">deactivate WP Event Manager</a> to improve performance.</p>';
            echo '</div>';
        }
        
        // Notice if CPTs not registered
        if (!post_type_exists('event_listing')) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>❌ Apollo Events Manager:</strong> CPTs not registered. Please deactivate and reactivate the plugin.</p>';
            echo '</div>';
        }
    }
    
    /**
     * Debug Footer
     * Shows debug info in HTML comments (admin only)
     */
    public function debug_footer() {
        if (!current_user_can('administrator')) {
            return;
        }
        
        echo '<!-- Apollo Debug Info -->' . "\n";
        echo '<!-- WPEM Active: ' . (class_exists('WP_Event_Manager') ? 'YES ⚠️' : 'NO ✅') . ' -->' . "\n";
        echo '<!-- CPTs Registered: ' . (post_type_exists('event_listing') ? 'YES ✅' : 'NO ❌') . ' -->' . "\n";
        echo '<!-- event_listing: ' . (post_type_exists('event_listing') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- event_dj: ' . (post_type_exists('event_dj') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- event_local: ' . (post_type_exists('event_local') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- Taxonomies: event_listing_category=' . (taxonomy_exists('event_listing_category') ? 'YES' : 'NO');
        echo ', event_sounds=' . (taxonomy_exists('event_sounds') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- Total Events: ' . wp_count_posts('event_listing')->publish . ' -->' . "\n";
        echo '<!-- Total DJs: ' . wp_count_posts('event_dj')->publish . ' -->' . "\n";
        echo '<!-- Total Locals: ' . wp_count_posts('event_local')->publish . ' -->' . "\n";
        echo '<!-- /Apollo Debug Info -->' . "\n";
    }

    /**
     * Auto-geocode Local posts when saved
     * Uses OpenStreetMap Nominatim API to get coordinates from address
     */
    public function auto_geocode_local($post_id, $post) {
        // Skip autosave/revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        
        // Get address and city
        $addr = get_post_meta($post_id, '_local_address', true);
        $city = get_post_meta($post_id, '_local_city', true);
        
        // Need at least city
        if (empty($city)) return;
        
        // Check if already has coordinates
        $lat = get_post_meta($post_id, '_local_latitude', true);
        $lng = get_post_meta($post_id, '_local_longitude', true);
        if (!empty($lat) && !empty($lng)) return; // Already has coords
        
        // Build search query
        $query_parts = array_filter([$addr, $city, 'Brasil']);
        $query = urlencode(implode(', ', $query_parts));
        $url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=BR&q={$query}";
        
        // Call Nominatim API
        $res = wp_remote_get($url, [
            'timeout' => 10,
            'user-agent' => 'Apollo::Rio/2.0 (WordPress Event Manager)'
        ]);
        
        if (is_wp_error($res)) {
            error_log("❌ Geocoding failed for local {$post_id}: " . $res->get_error_message());
            return;
        }
        
        $data = json_decode(wp_remote_retrieve_body($res), true);
        
        if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
            update_post_meta($post_id, '_local_latitude', $data[0]['lat']);
            update_post_meta($post_id, '_local_longitude', $data[0]['lon']);
            error_log("✅ Auto-geocoded local {$post_id}: {$data[0]['lat']}, {$data[0]['lon']}");
        } else {
            error_log("⚠️ No coordinates found for local {$post_id}: {$query}");
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('apollo-events-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Ensure the Eventos page exists (self-healing)
     * Uses helper function to check trash status - does NOT create if in trash
     */
    public function ensure_events_page() {
        if (is_admin()) return;
        
        $events_page = apollo_em_get_events_page();
        
        // Only create if page doesn't exist at all (not in trash)
        // Restoration from trash should only happen on activation
        if (!$events_page) {
            $page_id = wp_insert_post([
                'post_title'   => 'Eventos',
                'post_name'    => 'eventos',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '[apollo_events_portal]',
            ]);
            if ($page_id && !is_wp_error($page_id)) {
                flush_rewrite_rules(false);
                error_log('✅ Apollo: Auto-created /eventos/ page (ID: ' . $page_id . ')');
            }
        }
    }

    /**
     * Force Apollo templates for events - STRICT MODE
     * Plugin templates ALWAYS override theme, regardless of active theme
     * 
     * This ensures visual consistency matching CodePens:
     * - /eventos/ → templates/portal-discover.php (raxqVGR)
     * - /evento/{slug} → templates/single-event-standalone.php (JoGvgaY)
     */
    public function canvas_template($template) {
        // Don't override in admin
        if (is_admin()) {
            return $template;
        }
        
        // FORCE SINGLE DJ TEMPLATE
        // Any single event_dj MUST use our DJ template
        if (is_singular('event_dj')) {
            $plugin_template = APOLLO_WPEM_PATH . 'templates/single-event_dj.php';
            if (file_exists($plugin_template)) {
                error_log('🎯 Apollo: Forcing single-event_dj.php for DJ: ' . get_the_ID());
                return $plugin_template;
            }
        }
        
        // FORCE SINGLE EVENT TEMPLATE
        // Any single event_listing MUST use our standalone template
        if (is_singular('event_listing')) {
            $plugin_template = APOLLO_WPEM_PATH . 'templates/single-event-standalone.php';
            if (file_exists($plugin_template)) {
                // Log for debugging
                error_log('🎯 Apollo: Forcing single-event-standalone.php for event: ' . get_the_ID());
                return $plugin_template;
            }
        }
        
        // FORCE ARCHIVE/LIST TEMPLATE
        // /eventos/ page OR event_listing archive MUST use portal-discover
        if (is_page('eventos') || is_post_type_archive('event_listing')) {
            $plugin_template = APOLLO_WPEM_PATH . 'templates/portal-discover.php';
            if (file_exists($plugin_template)) {
                // Log for debugging
                error_log('🎯 Apollo: Forcing portal-discover.php for /eventos/');
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Enqueue plugin assets
     * FORCE LOAD from assets.apollo.rio.br
     */
    public function enqueue_assets() {
        // Only enqueue on event pages or shortcode pages
        if (!$this->should_enqueue_assets()) {
            return;
        }

        // Get config to determine page type
        $config = apollo_cfg();
        $event_post_type = isset($config['cpt']['event']) ? $config['cpt']['event'] : 'event_listing';
        $is_single_event = is_singular($event_post_type);

        // ============================================
        // FORCE LOAD: RemixIcon (required for all)
        // ============================================
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
            array(),
            '4.7.0'
        );

        // ============================================
        // FORCE LOAD: uni.css (required for all)
        // ============================================
        wp_enqueue_style(
            'apollo-uni-css',
            'https://assets.apollo.rio.br/uni.css',
            array('remixicon'),
            '2.0.0'
        );

        // ============================================
        // CONDITIONAL: base.js (events portal/listing)
        // ============================================
        if (!$is_single_event) {
            wp_enqueue_script(
                'apollo-base-js',
                'https://assets.apollo.rio.br/base.js',
                array('jquery'),
                '2.0.0',
                true
            );
            
            // Portal modal handler (local JS)
            wp_enqueue_script(
                'apollo-events-portal',
                APOLLO_WPEM_URL . 'assets/js/apollo-events-portal.js',
                array(), // Vanilla JS, sem dependências
                '1.0.1',
                true
            );
            
            // Localize for AJAX (shared between base.js and portal.js)
            wp_localize_script('apollo-events-portal', 'apollo_events_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apollo_events_nonce')
            ));
        }

        // ============================================
        // CONDITIONAL: event-page.js (single event + lightbox)
        // ============================================
        if ($is_single_event) {
            wp_enqueue_script(
                'apollo-event-page-js',
                'https://assets.apollo.rio.br/event-page.js',
                array('jquery'),
                '2.0.0',
                true
            );
            
            // Localize for AJAX
            wp_localize_script('apollo-event-page-js', 'apollo_events_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apollo_events_nonce')
            ));
        }

        // ============================================
        // FORCE LOAD: Leaflet (maps - all pages)
        // ============================================
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        // ============================================
        // DEBUG: Log asset loading
        // ============================================
        if (APOLLO_DEBUG) {
            error_log('🎨 Apollo Assets Loaded: ' . ($is_single_event ? 'SINGLE EVENT' : 'EVENTS PORTAL'));
        }
    }

    /**
     * Check if assets should be enqueued
     */
    private function should_enqueue_assets() {
        global $post;

        // Get config safely
        $config = apollo_cfg();
        if (!is_array($config) || !isset($config['cpt']) || !is_array($config['cpt']) || !isset($config['cpt']['event'])) {
            return false;
        }

        $event_post_type = $config['cpt']['event'];

        // Check if we're on the eventos page, event archive, or single event
        if (is_page('eventos') || is_post_type_archive($event_post_type) || is_singular($event_post_type)) {
            return true;
        }

        // Check if shortcode is present
        if (isset($post) && (has_shortcode($post->post_content, 'apollo_events') || has_shortcode($post->post_content, 'eventos-page'))) {
            return true;
        }

        return false;
    }

    /**
     * Events shortcode
     */
    public function events_shortcode($atts) {
        ob_start();

        // Get config safely
        $config = apollo_cfg();
        if (!is_array($config) || !isset($config['cpt']) || !is_array($config['cpt']) || !isset($config['cpt']['event'])) {
            return '<p>' . esc_html__('Configuration error.', 'apollo-events-manager') . '</p>';
        }

        $event_post_type = $config['cpt']['event'];

        // Get events data (optimized with cache and limit)
        $cache_key = 'apollo_events_shortcode_' . md5(serialize($atts));
        $events = wp_cache_get($cache_key, 'apollo_events');

        if ($events === false) {
            $events = get_posts(array(
                'post_type' => $event_post_type,
                'posts_per_page' => 50, // Limit to prevent performance issues
                'meta_query' => array(
                    array(
                        'key' => '_event_start_date',
                        'value' => date('Y-m-d'),
                        'compare' => '>=',
                        'type' => 'DATE'
                    )
                ),
                'orderby' => 'meta_value',
                'meta_key' => '_event_start_date',
                'order' => 'ASC'
            ));

            // Cache for 5 minutes
            wp_cache_set($cache_key, $events, 'apollo_events', 300);
        }

        // Include template parts
        include APOLLO_WPEM_PATH . 'templates/event-listings-start.php';

        if ($events) {
            global $post;
            foreach ($events as $post) {
                setup_postdata($post);
                include APOLLO_WPEM_PATH . 'templates/event-card.php';
            }
            wp_reset_postdata();
        } else {
            echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
        }

        include APOLLO_WPEM_PATH . 'templates/event-listings-end.php';

        return ob_get_clean();
    }

    /**
     * AJAX event filtering
     */
    public function ajax_filter_events() {
        check_ajax_referer('apollo_events_nonce', 'nonce');

        // Get config safely
        $config = apollo_cfg();
        if (!is_array($config) || !isset($config['cpt']) || !is_array($config['cpt']) || !isset($config['cpt']['event'])) {
            wp_send_json_error('Configuration error');
            return;
        }

        $event_post_type = $config['cpt']['event'];

        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? '');

        $args = array(
            'post_type' => $event_post_type,
            'posts_per_page' => 100, // Limit AJAX results to prevent performance issues
            'meta_query' => array(
                array(
                    'key' => '_event_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC'
        );

        // Add category filter
        if ($category && $category !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_listing_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }

        // Add search filter
        if ($search) {
            $args['s'] = $search;
        }

        // Add date filter
        if ($date) {
            $args['meta_query'][] = array(
                'key' => '_event_start_date',
                'value' => array($date . '-01', $date . '-31'),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            );
        }

        $events = get_posts($args);

        ob_start();
        if ($events) {
            foreach ($events as $event) {
                global $post;
                $post = $event;
                setup_postdata($post);
                include APOLLO_WPEM_PATH . 'templates/content-event_listing.php';
            }
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__('No events found.', 'apollo-events-manager') . '</p>';
        }

        $response = ob_get_clean();
        wp_send_json_success($response);
    }

    /**
     * Get event location
     */
    private function get_event_location($event) {
        $event_id = is_object($event) ? $event->ID : $event;
        $location = get_post_meta($event_id, '_event_location', true);
        return $location ?: __('Location TBA', 'apollo-events-manager');
    }

    /**
     * Get event banner
     */
    private function get_event_banner($event_id) {
        $banner_id = get_post_meta($event_id, '_event_banner', true);
        if ($banner_id) {
            return wp_get_attachment_image_src($banner_id, 'full');
        }

        if (has_post_thumbnail($event_id)) {
            return wp_get_attachment_image_src(get_post_thumbnail_id($event_id), 'full');
        }

        return false;
    }

    /**
     * Eventos Page Shortcode (Complete Portal)
     */
    public function eventos_page_shortcode($atts) {
        ob_start();
        
        // Get config safely
        $config = apollo_cfg();
        if (!is_array($config) || !isset($config['cpt']) || !is_array($config['cpt']) || !isset($config['cpt']['event'])) {
            return '<p>' . esc_html__('Configuration error.', 'apollo-events-manager') . '</p>';
        }

        $event_post_type = $config['cpt']['event'];
        
        // Get all events
        $args = array(
            'post_type' => $event_post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_event_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC'
        );
        
        $events_query = new WP_Query($args);
        
        // Get unique months for filtering
        $event_months = array();
        if ($events_query->have_posts()) {
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $start_date = get_post_meta(get_the_ID(), '_event_start_date', true);
                if ($start_date) {
                    $month_key = date('M', strtotime($start_date));
                    $month_lower = strtolower($month_key);
                    if ($month_lower == 'oct') $month_lower = 'out';
                    if ($month_lower == 'dec') $month_lower = 'dez';
                    $event_months[$month_lower] = true;
                }
            }
            wp_reset_postdata();
        }
        ?>
        <div class="event-manager-shortcode-wrapper discover-events-now-shortcode">
            
            <section class="hero-section">
                <h1 class="title-page">Experience Tomorrow's Events</h1>
                <p class="subtitle-page">Um novo <mark>&nbsp;hub digital que conecta cultura,&nbsp;</mark> tecnologia e experiências em tempo real... <mark>&nbsp;O futuro da cultura carioca começa aqui!&nbsp;</mark></p>
            </section>

            <!-- Filters & Search -->
            <div class="filters-and-search">
                <div class="menutags event_types">
                    <button class="menutag event-category active" data-slug="all">All</button>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'event_listing_category',
                        'hide_empty' => false,
                    ));
                    foreach ($categories as $cat) {
                        $cat_name = $cat->name;
                        // Custom labels
                        if ($cat->slug == 'music') $cat_name = 'Underground';
                        if ($cat->slug == 'art-culture') $cat_name = 'Art & Cultur<font style="position:absolute;transform:rotate(210deg);margin-left:0px">a</font>';
                        if ($cat->slug == 'mainstream') $cat_name = 'Mainstream';
                        if ($cat->slug == 'workshops') $cat_name = 'D-Edge club';
                        
                        echo '<button class="menutag event-category" data-slug="' . esc_attr($cat->slug) . '">' . $cat_name . '</button>';
                    }
                    ?>
                </div>
                
                <div class="search-date-controls">
                    <form class="box-search" role="search" id="eventSearchForm">
                        <label for="eventSearchInput" class="visually-hidden">Procurar</label>
                        <i class="ri-search-line"></i>
                        <input type="text" name="search_keywords" id="eventSearchInput" placeholder="">
                        <input type="hidden" name="post_type" value="event_listing">
                    </form>
                    
                    <div class="box-rio" id="eventDatePicker">
                        <button type="button" class="date-arrow" id="datePrev" aria-label="Previous month">‹</button>
                        <span class="date-display" id="dateDisplay"></span>
                        <button type="button" class="date-arrow" id="dateNext" aria-label="Next month">›</button>
                    </div>
                </div>
            </div>

            <!-- Layout Toggle -->
            <div class="wpem-col wpem-col-12 wpem-col-sm-6 wpem-col-md-6 wpem-col-lg-4">
                <div class="wpem-event-layout-action-wrapper">
                    <div class="wpem-event-layout-action">
                        <div class="wpem-event-layout-icon wpem-event-list-layout wpem-active-layout" title="Events List View" id="wpem-event-toggle-layout" onclick="toggleLayout(this)">
                            <i class="wpem-icon-menu"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Listings Grid -->
            <div class="event_listings">
                <?php
                if ($events_query->have_posts()) {
                    while ($events_query->have_posts()) {
                        $events_query->the_post();
                        include APOLLO_WPEM_PATH . 'templates/event-card.php';
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p>Nenhum evento encontrado.</p>';
                }
                ?>
            </div>

            <!-- Highlight Banner -->
            <section class="banner-ario-1-wrapper" style="margin-top: 80px;">
                <img src="https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop" class="ban-ario-1-img" alt="Upcoming Festival">
                <div class="ban-ario-1-content">
                    <h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
                    <h2 class="ban-ario-1-titl">Retrospectiva Clubbe::rio 2026</h2>
                    <p class="ban-ario-1-txt">
                        A Retrospectiva Clubber 2026 está chegando! E em breve vamos liberar as primeiras novidades... Fique ligado, porque essa publicação promete celebrar tudo o que fez o coração da pista bater mais forte! Spoilers?
                    </p>
                    <a href="#" class="ban-ario-1-btn">
                        Saiba Mais <i class="ri-arrow-right-long-line"></i>
                    </a>
                </div>
            </section>
            
        </div>

        <!-- Lightbox Modal for Single Event -->
        <div id="eventLightbox" class="event-lightbox" style="display:none;">
            <div class="event-lightbox-overlay"></div>
            <div class="event-lightbox-content">
                <button class="event-lightbox-close"><i class="ri-close-line"></i></button>
                <div id="eventLightboxBody"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Event card click handler for lightbox
            $(document).on('click', '.event_listing', function(e) {
                e.preventDefault();
                var eventId = $(this).data('event-id');
                
                // Load event content via AJAX
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'load_event_single',
                        event_id: eventId
                    },
                    success: function(response) {
                        $('#eventLightboxBody').html(response);
                        $('#eventLightbox').fadeIn(300);
                        $('body').css('overflow', 'hidden');
                    }
                });
            });
            
            // Close lightbox
            $(document).on('click', '.event-lightbox-close, .event-lightbox-overlay', function() {
                $('#eventLightbox').fadeOut(300);
                $('body').css('overflow', 'auto');
            });
        });
        </script>

        <style>
        .event-lightbox {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 99999;
        }
        .event-lightbox-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(10px);
        }
        .event-lightbox-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            background: var(--bg-color, #fff);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .event-lightbox-close {
            position: sticky;
            top: 10px;
            right: 10px;
            float: right;
            z-index: 10;
            background: rgba(0,0,0,0.5);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #fff;
            font-size: 24px;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for loading single event
     */
    public function ajax_load_event_single() {
        check_ajax_referer('apollo_events_nonce', 'nonce');
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if ($event_id <= 0) {
            wp_send_json_error('Evento inválido');
        }
        
        global $post;
        $post = get_post($event_id);
        
        if (!$post || $post->post_type !== 'event_listing') {
            wp_send_json_error('Evento não encontrado');
        }
        
        setup_postdata($post);
        
        include APOLLO_WPEM_PATH . 'templates/single-event.php';
        
        wp_reset_postdata();
        wp_die();
    }

    /**
     * Handle favorite toggle AJAX
     */
    public function ajax_toggle_favorite() {
        check_ajax_referer('apollo_events_nonce', 'nonce');
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if ($event_id <= 0) {
            wp_send_json_error('Evento inválido');
        }
        
        // Verify event exists
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing') {
            wp_send_json_error('Evento não encontrado');
        }
        
        // Get current favorites count
        $current_count = get_post_meta($event_id, '_favorites_count', true);
        $current_count = $current_count ? intval($current_count) : 0;
        
        // For now, just increment/decrement based on request
        // TODO: Implement proper user-based favorites tracking
        $action = sanitize_text_field($_POST['action_type'] ?? 'add');
        
        if ($action === 'add') {
            $new_count = $current_count + 1;
        } else {
            $new_count = max(0, $current_count - 1);
        }
        
        update_post_meta($event_id, '_favorites_count', $new_count);
        
        wp_send_json_success(array(
            'count' => $new_count,
            'action' => $action
        ));
    }

    /**
     * AJAX handler for event modal content
     * Returns HTML for the lightbox modal
     */
    public function ajax_get_event_modal() {
        // Verificar nonce
        check_ajax_referer('apollo_events_nonce', 'nonce');
        
        // Validar ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error(array('message' => 'ID do evento inválido'));
        }
        
        // Verificar se evento existe
        $post = get_post($event_id);
        if (!$post || $post->post_type !== 'event_listing' || $post->post_status !== 'publish') {
            wp_send_json_error(array('message' => 'Evento não encontrado'));
        }
        
        // Obter dados do evento
        $start_date_raw = get_post_meta($event_id, '_event_start_date', true);
        $event_location_r = get_post_meta($event_id, '_event_location', true);
        $event_banner = get_post_meta($event_id, '_event_banner', true);
        $timetable = get_post_meta($event_id, '_timetable', true);
        
        // Processar data
        $date_info = apollo_eve_parse_start_date($start_date_raw);
        $day = $date_info['day'];
        $month_pt = $date_info['month_pt'];
        
        // Processar localização
        $event_location = '';
        $event_location_area = '';
        if (!empty($event_location_r)) {
            if (strpos($event_location_r, '|') !== false) {
                list($event_location, $event_location_area) = array_map('trim', explode('|', $event_location_r, 2));
            } else {
                $event_location = trim($event_location_r);
            }
        }
        
        // Processar DJs
        $djs_names = array();
        if (!empty($timetable) && is_array($timetable)) {
            foreach ($timetable as $slot) {
                if (empty($slot['dj'])) {
                    continue;
                }
                $dj_id = $slot['dj'];
                if (is_numeric($dj_id)) {
                    $dj_post = get_post($dj_id);
                    if ($dj_post && $dj_post->post_status === 'publish') {
                        $dj_name = get_post_meta($dj_id, '_dj_name', true);
                        if (empty($dj_name)) {
                            $dj_name = $dj_post->post_title;
                        }
                        if (!empty($dj_name)) {
                            $djs_names[] = trim($dj_name);
                        }
                    }
                } else {
                    $djs_names[] = trim((string) $dj_id);
                }
            }
        }
        
        // Fallback DJ meta
        $event_dj_meta = get_post_meta($event_id, '_dj_name', true);
        if (!empty($event_dj_meta)) {
            $djs_names[] = trim($event_dj_meta);
        }
        
        $djs_names = array_values(array_unique(array_filter($djs_names)));
        
        // Formatar display de DJs
        if (!empty($djs_names)) {
            $max_visible = 6;
            $visible = array_slice($djs_names, 0, $max_visible);
            $remaining = max(count($djs_names) - $max_visible, 0);
            
            $dj_display = '<strong>' . esc_html($visible[0]) . '</strong>';
            if (count($visible) > 1) {
                $rest = array_slice($visible, 1);
                $dj_display .= ', ' . esc_html(implode(', ', $rest));
            }
            if ($remaining > 0) {
                $dj_display .= ' <span class="dj-more">+' . $remaining . ' DJs</span>';
            }
        } else {
            $dj_display = '<span class="dj-fallback">Line-up em breve</span>';
        }
        
        // Processar banner
        $banner_url = '';
        if ($event_banner) {
            $banner_url = is_numeric($event_banner) ? wp_get_attachment_url($event_banner) : $event_banner;
        }
        if (!$banner_url && has_post_thumbnail($event_id)) {
            $banner_url = get_the_post_thumbnail_url($event_id, 'large');
        }
        if (!$banner_url) {
            $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
        }
        
        // Obter conteúdo do evento
        $content = apply_filters('the_content', get_post_field('post_content', $event_id));
        
        // Gerar HTML do modal
        ob_start();
        ?>
        <div class="apollo-event-modal-overlay" data-apollo-close></div>
        <div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo $event_id; ?>">
            
            <button class="apollo-event-modal-close" type="button" data-apollo-close aria-label="Fechar">
                <i class="ri-close-line"></i>
            </button>
            
            <div class="apollo-event-hero">
                <div class="apollo-event-hero-media">
                    <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr(get_the_title($event_id)); ?>">
                    <div class="apollo-event-date-chip">
                        <span class="d"><?php echo esc_html($day); ?></span>
                        <span class="m"><?php echo esc_html($month_pt); ?></span>
                    </div>
                </div>
                
                <div class="apollo-event-hero-info">
                    <h1 class="apollo-event-title" id="modal-title-<?php echo $event_id; ?>">
                        <?php echo esc_html(get_the_title($event_id)); ?>
                    </h1>
                    <p class="apollo-event-djs">
                        <i class="ri-sound-module-fill"></i>
                        <span><?php echo wp_kses_post($dj_display); ?></span>
                    </p>
                    <?php if (!empty($event_location)): ?>
                        <p class="apollo-event-location">
                            <i class="ri-map-pin-2-line"></i>
                            <span class="event-location-name">
                                <?php echo esc_html($event_location); ?>
                            </span>
                            <?php if (!empty($event_location_area)): ?>
                                <span class="event-location-area">
                                    (<?php echo esc_html($event_location_area); ?>)
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="apollo-event-body">
                <?php echo $content; ?>
            </div>
            
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Force Brazil as the default country
     */
    public function force_brazil_country($fields) {
        if (isset($fields['event']['event_country'])) {
            $fields['event']['event_country']['default'] = 'BR';
            $fields['event']['event_country']['options'] = array('BR' => 'Brazil');
            $fields['event']['event_country']['type'] = 'hidden'; // Hide the field since it's always Brazil
        }
        return $fields;
    }

    /**
     * Add custom fields to event submission form
     */
    public function add_custom_event_fields($fields) {
        // Add DJ selection field (multiple)
        $fields['event']['event_djs'] = array(
            'label' => __('DJs', 'apollo-events-manager'),
            'type' => 'multiselect',
            'required' => false,
            'options' => $this->get_dj_options(),
            'placeholder' => __('Select DJs', 'apollo-events-manager'),
            'description' => __('Select the DJs performing at this event', 'apollo-events-manager'),
            'priority' => 7
        );

        // Add timetable field
        $fields['event']['timetable'] = array(
            'label' => __('Timetable', 'apollo-events-manager'),
            'type' => 'timetable',
            'required' => false,
            'placeholder' => '',
            'description' => __('Add DJs and their performance times', 'apollo-events-manager'),
            'priority' => 8
        );

        // Add local selection field
        $fields['event']['event_local'] = array(
            'label' => __('Local', 'apollo-events-manager'),
            'type' => 'select',
            'required' => false,
            'options' => $this->get_local_options(),
            'placeholder' => __('Selecione um local', 'apollo-events-manager'),
            'description' => __('Escolha o local do evento', 'apollo-events-manager'),
            'priority' => 9
        );

        // Add promotional images field
        $fields['event']['_3_imagens_promo'] = array(
            'label' => __('Promotional Images', 'apollo-events-manager'),
            'type' => 'file',
            'required' => false,
            'multiple' => true,
            'placeholder' => '',
            'description' => __('Upload up to 3 promotional images', 'apollo-events-manager'),
            'priority' => 10
        );

        // Add final image field
        $fields['event']['_imagem_final'] = array(
            'label' => __('Final Image', 'apollo-events-manager'),
            'type' => 'file',
            'required' => false,
            'placeholder' => '',
            'description' => __('Upload the final promotional image', 'apollo-events-manager'),
            'priority' => 11
        );

        // Add coupon field
        $fields['event']['cupom_ario'] = array(
            'label' => __('Coupon Code', 'apollo-events-manager'),
            'type' => 'text',
            'required' => false,
            'placeholder' => __('Enter coupon code', 'apollo-events-manager'),
            'description' => __('Special coupon code for this event', 'apollo-events-manager'),
            'priority' => 12
        );

        return $fields;
    }

    /**
     * Get DJ options for select field
     */
    private function get_dj_options() {
        $options = array();

        $djs = get_posts(array(
            'post_type' => 'event_dj',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        foreach ($djs as $dj) {
            $dj_name = get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
            $options[$dj->ID] = $dj_name;
        }

        return $options;
    }

    /**
     * Get local options for select field
     */
    private function get_local_options() {
        $options = array('' => __('Selecione um local', 'apollo-events-manager'));

        $locals = get_posts(array(
            'post_type' => 'event_local',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        foreach ($locals as $local) {
            $options[$local->ID] = $local->post_title;
        }

        return $options;
    }

    /**
     * Validate custom event fields
     */
    public function validate_custom_event_fields($validation_errors) {
        // Validate timetable format
        if (isset($_POST['timetable']) && !empty($_POST['timetable'])) {
            $timetable = $_POST['timetable'];
            if (is_array($timetable)) {
                foreach ($timetable as $slot) {
                    if (!isset($slot['time']) || !isset($slot['dj'])) {
                        $validation_errors[] = __('Invalid timetable format', 'apollo-events-manager');
                        break;
                    }
                }
            }
        }

        // Validate coupon code format
        if (isset($_POST['cupom_ario']) && !empty($_POST['cupom_ario'])) {
            $coupon = sanitize_text_field($_POST['cupom_ario']);
            if (strlen($coupon) > 20) {
                $validation_errors[] = __('Coupon code must be less than 20 characters', 'apollo-events-manager');
            }
        }

        return $validation_errors;
    }

    /**
     * Save custom event fields
     */
    public function save_custom_event_fields($post_id, $post) {
        // Security checks
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save DJs (WordPress handles serialization automatically)
        if (isset($_POST['event_djs'])) {
            $djs = array_map('intval', (array) $_POST['event_djs']);
            update_post_meta($post_id, '_event_dj_ids', $djs);
        }

        // Save local
        if (isset($_POST['event_local'])) {
            update_post_meta($post_id, '_event_local_ids', intval($_POST['event_local']));
        }

        // Save timetable
        if (isset($_POST['timetable']) && is_array($_POST['timetable'])) {
            $clean_timetable = array();
            foreach ($_POST['timetable'] as $slot) {
                if (!empty($slot['dj']) && !empty($slot['start'])) {
                    $clean_timetable[] = array(
                        'dj' => intval($slot['dj']),
                        'start' => sanitize_text_field($slot['start']),
                        'end' => sanitize_text_field($slot['end'] ?? $slot['start'])
                    );
                }
            }
            // Sort by start time
            usort($clean_timetable, function($a, $b) {
                return strcmp($a['start'], $b['start']);
            });
            update_post_meta($post_id, '_event_timetable', $clean_timetable);
        }

        // Save promotional images (array of URLs)
        if (isset($_POST['_3_imagens_promo']) && is_array($_POST['_3_imagens_promo'])) {
            $clean_images = array_map('esc_url_raw', array_filter($_POST['_3_imagens_promo']));
            update_post_meta($post_id, '_3_imagens_promo', $clean_images);
        }

        // Save final image (ID or URL)
        if (isset($_POST['_imagem_final'])) {
            $final_image = is_numeric($_POST['_imagem_final'])
                ? absint($_POST['_imagem_final'])
                : esc_url_raw($_POST['_imagem_final']);
            
            update_post_meta($post_id, '_imagem_final', $final_image);
        }

        // Save coupon
        if (isset($_POST['cupom_ario'])) {
            update_post_meta($post_id, '_cupom_ario', sanitize_text_field($_POST['cupom_ario']));
        }
        
        // Clear cache after saving (safe for any WordPress installation)
        clean_post_cache($post_id);
        
        // Clear custom transients if used
        delete_transient('apollo_events_portal_cache');
        delete_transient('apollo_events_home_cache');
    }

    /**
     * Inject content in single event pages (prepend/append)
     * Only on singular, main query, in the loop
     */
    public function inject_event_content($content) {
        // Get config safely
        $config = apollo_cfg();
        if (!is_array($config) || !isset($config['cpt']) || !isset($config['cpt']['event'])) {
            return $content;
        }

        $event_post_type = $config['cpt']['event'];

        // Only inject on single event pages in main query
        if (!is_singular($event_post_type) || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        ob_start();
        ?>
        <div class="apollo-single__compact">
            <?php
            $event_id = get_the_ID();
            $startD = get_post_meta($event_id, '_event_start_date', true);
            $startT = get_post_meta($event_id, '_event_start_time', true);
            ?>
            <div class="compact-datetime">
                <strong><?php echo esc_html($startD); ?></strong>
                <?php if ($startT): ?>
                    <span><?php echo esc_html($startT); ?></span>
                <?php endif; ?>
            </div>
            <div class="compact-actions">
                <a class="btn share" href="#" onclick="if(navigator.share){navigator.share({title:document.title, url:location.href});return false;}">
                    <i class="ri-share-forward-line"></i> Compartilhar
                </a>
            </div>
        </div>
        <?php
        $prepend = ob_get_clean();

        ob_start();
        ?>
        <div class="apollo-single__extra">
            <?php do_action('apollo_single_after_content'); ?>
        </div>
        <?php
        $append = ob_get_clean();

        return $prepend . $content . $append;
    }

    /**
     * Apollo Event Shortcode
     * 
     * Usage: [apollo_event field="dj_list" id="123"]
     *        [apollo_event field="location"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Placeholder value
     */
    public function apollo_event_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'field' => '',
                'id'    => 0,
            ),
            $atts,
            'apollo_event'
        );

        if ( empty( $atts['field'] ) ) {
            return '';
        }

        $event_id = $atts['id'] ? (int) $atts['id'] : get_the_ID();
        if ( ! $event_id ) {
            return '';
        }

        if ( ! function_exists( 'apollo_event_get_placeholder_value' ) ) {
            return '';
        }

        $value = apollo_event_get_placeholder_value( $atts['field'], $event_id );
        return $value;
    }

    /**
     * Add admin menu for placeholders documentation
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Apollo Events', 'apollo-events-manager'),
            __('Apollo Events', 'apollo-events-manager'),
            'manage_options',
            'apollo-events',
            array($this, 'render_admin_dashboard'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'apollo-events',
            __('Dashboard', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-events-dashboard',
            array($this, 'render_analytics_dashboard')
        );

        add_submenu_page(
            'apollo-events',
            __('User Overview', 'apollo-events-manager'),
            __('User Overview', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-events-user-overview',
            array($this, 'render_user_overview')
        );

        add_submenu_page(
            'apollo-events',
            __('Shortcodes & Placeholders', 'apollo-events-manager'),
            __('Shortcodes & Placeholders', 'apollo-events-manager'),
            'manage_options',
            'apollo-events-placeholders',
            array($this, 'render_placeholders_page')
        );
    }

    /**
     * Render admin dashboard (main menu page)
     */
    public function render_admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Apollo Events Manager', 'apollo-events-manager'); ?></h1>
            <p><?php echo esc_html__('Welcome to Apollo Events Manager. Use the submenus to access dashboards, analytics, and documentation.', 'apollo-events-manager'); ?></p>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php echo esc_html__('Quick Links', 'apollo-events-manager'); ?></h2>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=apollo-events-dashboard'); ?>"><?php echo esc_html__('Dashboard & Analytics', 'apollo-events-manager'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=apollo-events-user-overview'); ?>"><?php echo esc_html__('User Overview', 'apollo-events-manager'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=apollo-events-placeholders'); ?>"><?php echo esc_html__('Shortcodes & Placeholders', 'apollo-events-manager'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Track event view when modal is opened
     */
    public function track_event_view_on_modal() {
        // This runs before ajax_get_event_modal processes the request
        // Extract event_id from POST data
        if (isset($_POST['event_id']) && is_numeric($_POST['event_id'])) {
            $event_id = absint($_POST['event_id']);
            if (function_exists('apollo_record_event_view')) {
                apollo_record_event_view($event_id);
            }
        }
    }

    /**
     * Render analytics dashboard page
     */
    public function render_analytics_dashboard() {
        // Allow manage_options as fallback for admin
        if (!current_user_can('view_apollo_event_stats') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to view this page.', 'apollo-events-manager'));
        }

        if (!function_exists('apollo_get_global_event_stats')) {
            echo '<div class="wrap"><p>' . esc_html__('Analytics system not loaded.', 'apollo-events-manager') . '</p></div>';
            return;
        }

        $stats = apollo_get_global_event_stats();
        $top_users = apollo_get_top_users_by_interactions(10);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Apollo Events – Dashboard', 'apollo-events-manager'); ?></h1>
            
            <h2><?php echo esc_html__('Key Metrics', 'apollo-events-manager'); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="card">
                    <h3><?php echo esc_html__('Total Events', 'apollo-events-manager'); ?></h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['total_events'])); ?></p>
                </div>
                <div class="card">
                    <h3><?php echo esc_html__('Future Events', 'apollo-events-manager'); ?></h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['future_events'])); ?></p>
                </div>
                <div class="card">
                    <h3><?php echo esc_html__('Total Views', 'apollo-events-manager'); ?></h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['total_views'])); ?></p>
                </div>
            </div>

            <h2><?php echo esc_html__('Top Events by Views', 'apollo-events-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 5%;"><?php echo esc_html__('ID', 'apollo-events-manager'); ?></th>
                        <th style="width: 60%;"><?php echo esc_html__('Event Title', 'apollo-events-manager'); ?></th>
                        <th style="width: 15%;"><?php echo esc_html__('Views', 'apollo-events-manager'); ?></th>
                        <th style="width: 20%;"><?php echo esc_html__('Actions', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['top_events_by_views'])): ?>
                        <?php foreach ($stats['top_events_by_views'] as $event): ?>
                        <tr>
                            <td><?php echo esc_html($event['id']); ?></td>
                            <td><strong><?php echo esc_html($event['title']); ?></strong></td>
                            <td><?php echo esc_html(number_format_i18n($event['views'])); ?></td>
                            <td>
                                <a href="<?php echo esc_url($event['permalink']); ?>" target="_blank"><?php echo esc_html__('View', 'apollo-events-manager'); ?></a> |
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $event['id'] . '&action=edit')); ?>"><?php echo esc_html__('Edit', 'apollo-events-manager'); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php echo esc_html__('No events with views yet.', 'apollo-events-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php echo esc_html__('Top Sounds (by Event Count)', 'apollo-events-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 70%;"><?php echo esc_html__('Sound', 'apollo-events-manager'); ?></th>
                        <th style="width: 30%;"><?php echo esc_html__('Event Count', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['top_sounds'])): ?>
                        <?php foreach ($stats['top_sounds'] as $sound): ?>
                        <tr>
                            <td><strong><?php echo esc_html($sound['name']); ?></strong></td>
                            <td><?php echo esc_html(number_format_i18n($sound['count'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2"><?php echo esc_html__('No sounds found.', 'apollo-events-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php echo esc_html__('Top Locations (by Event Count)', 'apollo-events-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 70%;"><?php echo esc_html__('Location', 'apollo-events-manager'); ?></th>
                        <th style="width: 30%;"><?php echo esc_html__('Event Count', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['top_locations'])): ?>
                        <?php foreach ($stats['top_locations'] as $location): ?>
                        <tr>
                            <td><strong><?php echo esc_html($location['name']); ?></strong></td>
                            <td><?php echo esc_html(number_format_i18n($location['count'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2"><?php echo esc_html__('No locations found.', 'apollo-events-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php echo esc_html__('Top Users by Interactions', 'apollo-events-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 5%;"><?php echo esc_html__('ID', 'apollo-events-manager'); ?></th>
                        <th style="width: 30%;"><?php echo esc_html__('Name', 'apollo-events-manager'); ?></th>
                        <th style="width: 30%;"><?php echo esc_html__('Email', 'apollo-events-manager'); ?></th>
                        <th style="width: 15%;"><?php echo esc_html__('Co-Author', 'apollo-events-manager'); ?></th>
                        <th style="width: 15%;"><?php echo esc_html__('Favorited', 'apollo-events-manager'); ?></th>
                        <th style="width: 15%;"><?php echo esc_html__('Total', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_users)): ?>
                        <?php foreach ($top_users as $user_data): ?>
                        <tr>
                            <td><?php echo esc_html($user_data['id']); ?></td>
                            <td><strong><?php echo esc_html($user_data['name']); ?></strong></td>
                            <td><?php echo esc_html($user_data['email']); ?></td>
                            <td><?php echo esc_html(number_format_i18n($user_data['coauthor_count'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n($user_data['favorited_count'])); ?></td>
                            <td><strong><?php echo esc_html(number_format_i18n($user_data['total_interactions'])); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php echo esc_html__('No user interactions found.', 'apollo-events-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render user overview page
     */
    public function render_user_overview() {
        // Allow manage_options as fallback for admin
        if (!current_user_can('view_apollo_event_stats') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to view this page.', 'apollo-events-manager'));
        }

        if (!function_exists('apollo_get_user_event_stats')) {
            echo '<div class="wrap"><p>' . esc_html__('Analytics system not loaded.', 'apollo-events-manager') . '</p></div>';
            return;
        }

        // Get user ID from request or use current user
        $target_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : get_current_user_id();
        $user = get_user_by('id', $target_user_id);
        
        if (!$user) {
            $target_user_id = get_current_user_id();
            $user = get_user_by('id', $target_user_id);
        }

        $stats = apollo_get_user_event_stats($target_user_id);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Apollo Events – User Overview', 'apollo-events-manager'); ?></h1>
            
            <form method="get" style="margin: 20px 0;">
                <input type="hidden" name="page" value="apollo-events-user-overview">
                <label for="user_id"><?php echo esc_html__('Select User:', 'apollo-events-manager'); ?></label>
                <select name="user_id" id="user_id">
                    <?php
                    $users = get_users(array('number' => 100));
                    foreach ($users as $u) {
                        $selected = ($u->ID == $target_user_id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($u->ID) . '" ' . $selected . '>' . esc_html($u->display_name . ' (' . $u->user_email . ')') . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="button"><?php echo esc_html__('Load User Stats', 'apollo-events-manager'); ?></button>
            </form>

            <h2><?php echo esc_html__('User Statistics', 'apollo-events-manager'); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="card">
                    <h3><?php echo esc_html__('Co-Author Events', 'apollo-events-manager'); ?></h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['coauthor_count'])); ?></p>
                </div>
                <div class="card">
                    <h3><?php echo esc_html__('Favorited Events', 'apollo-events-manager'); ?></h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['favorited_count'])); ?></p>
                </div>
            </div>

            <?php if (!empty($stats['sounds_distribution'])): ?>
            <h2><?php echo esc_html__('Sounds Distribution', 'apollo-events-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;"><?php echo esc_html__('Sound', 'apollo-events-manager'); ?></th>
                        <th style="width: 20%;"><?php echo esc_html__('Count', 'apollo-events-manager'); ?></th>
                        <th style="width: 40%;"><?php echo esc_html__('Percentage', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['sounds_distribution'] as $sound): ?>
                    <tr>
                        <td><strong><?php echo esc_html($sound['name']); ?></strong></td>
                        <td><?php echo esc_html(number_format_i18n($sound['count'])); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f0; height: 20px; border-radius: 3px; overflow: hidden;">
                                    <div style="background: #0073aa; height: 100%; width: <?php echo esc_attr($sound['percentage']); ?>%;"></div>
                                </div>
                                <span><strong><?php echo esc_html($sound['percentage']); ?>%</strong></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($stats['locations_distribution'])): ?>
            <h2><?php echo esc_html__('Locations Distribution', 'apollo-events-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;"><?php echo esc_html__('Location', 'apollo-events-manager'); ?></th>
                        <th style="width: 20%;"><?php echo esc_html__('Count', 'apollo-events-manager'); ?></th>
                        <th style="width: 40%;"><?php echo esc_html__('Percentage', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['locations_distribution'] as $location): ?>
                    <tr>
                        <td><strong><?php echo esc_html($location['name']); ?></strong></td>
                        <td><?php echo esc_html(number_format_i18n($location['count'])); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f0; height: 20px; border-radius: 3px; overflow: hidden;">
                                    <div style="background: #0073aa; height: 100%; width: <?php echo esc_attr($location['percentage']); ?>%;"></div>
                                </div>
                                <span><strong><?php echo esc_html($location['percentage']); ?>%</strong></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Front-end user overview shortcode
     */
    public function apollo_event_user_overview_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('This content is only available for logged-in users.', 'apollo-events-manager') . '</p>';
        }

        if (!function_exists('apollo_get_user_event_stats')) {
            return '';
        }

        $user_id = get_current_user_id();
        $stats = apollo_get_user_event_stats($user_id);

        ob_start();
        ?>
        <div class="apollo-user-overview">
            <h3><?php echo esc_html__('My Event Statistics', 'apollo-events-manager'); ?></h3>
            
            <div class="apollo-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                <div class="apollo-stat-card" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <strong><?php echo esc_html__('Co-Author Events', 'apollo-events-manager'); ?></strong>
                    <p style="font-size: 24px; margin: 10px 0 0 0; font-weight: bold;"><?php echo esc_html(number_format_i18n($stats['coauthor_count'])); ?></p>
                </div>
                <div class="apollo-stat-card" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <strong><?php echo esc_html__('Favorited Events', 'apollo-events-manager'); ?></strong>
                    <p style="font-size: 24px; margin: 10px 0 0 0; font-weight: bold;"><?php echo esc_html(number_format_i18n($stats['favorited_count'])); ?></p>
                </div>
            </div>

            <?php if (!empty($stats['sounds_distribution'])): ?>
            <h4><?php echo esc_html__('My Top Sounds', 'apollo-events-manager'); ?></h4>
            <ul style="list-style: none; padding: 0;">
                <?php foreach (array_slice($stats['sounds_distribution'], 0, 5) as $sound): ?>
                <li style="padding: 10px; background: #f9f9f9; margin: 5px 0; border-radius: 3px;">
                    <strong><?php echo esc_html($sound['name']); ?></strong> 
                    <span style="color: #666;">(<?php echo esc_html($sound['count']); ?> eventos, <?php echo esc_html($sound['percentage']); ?>%)</span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (!empty($stats['locations_distribution'])): ?>
            <h4><?php echo esc_html__('My Top Locations', 'apollo-events-manager'); ?></h4>
            <ul style="list-style: none; padding: 0;">
                <?php foreach (array_slice($stats['locations_distribution'], 0, 5) as $location): ?>
                <li style="padding: 10px; background: #f9f9f9; margin: 5px 0; border-radius: 3px;">
                    <strong><?php echo esc_html($location['name']); ?></strong> 
                    <span style="color: #666;">(<?php echo esc_html($location['count']); ?> eventos, <?php echo esc_html($location['percentage']); ?>%)</span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [event id="123"] - Full event content for lightbox
     */
    public function event_single_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'event');
        
        $event_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (!$event_id) {
            return '<p>' . esc_html__('Event ID not provided.', 'apollo-events-manager') . '</p>';
        }
        
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing' || $event->post_status !== 'publish') {
            return '<p>' . esc_html__('Event not found.', 'apollo-events-manager') . '</p>';
        }
        
        // Setup post data
        global $post;
        $original_post = $post;
        $post = $event;
        setup_postdata($post);
        
        // Get event data
        $start_date_raw = get_post_meta($event_id, '_event_start_date', true);
        $date_info = apollo_eve_parse_start_date($start_date_raw);
        $banner = get_post_meta($event_id, '_event_banner', true);
        $video_url = get_post_meta($event_id, '_event_video_url', true);
        $tickets_url = get_post_meta($event_id, '_tickets_ext', true);
        
        // Get DJs
        $dj_list = apollo_event_get_placeholder_value('dj_list', $event_id);
        
        // Get Location
        $location = apollo_event_get_placeholder_value('location', $event_id);
        $location_area = apollo_event_get_placeholder_value('location_area', $event_id);
        
        // Get Banner URL
        $banner_url = apollo_event_get_placeholder_value('banner_url', $event_id);
        
        ob_start();
        ?>
        <div class="apollo-event-lightbox-content">
            <div class="apollo-event-hero">
                <div class="apollo-event-hero-media">
                    <?php if ($banner_url): ?>
                        <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                    <?php endif; ?>
                    
                    <?php if ($date_info['day'] && $date_info['month_pt']): ?>
                        <div class="apollo-event-date-chip">
                            <span class="d"><?php echo esc_html($date_info['day']); ?></span>
                            <span class="m"><?php echo esc_html($date_info['month_pt']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="apollo-event-hero-info">
                    <h1 class="apollo-event-title"><?php echo esc_html(get_the_title()); ?></h1>
                    
                    <?php if ($dj_list): ?>
                        <p class="apollo-event-djs">
                            <i class="ri-sound-module-fill"></i>
                            <span><?php echo wp_kses_post($dj_list); ?></span>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <p class="apollo-event-location">
                            <i class="ri-map-pin-2-line"></i>
                            <span><?php echo esc_html($location); ?></span>
                            <?php if ($location_area): ?>
                                <span style="opacity: 0.5;">&nbsp;(<?php echo esc_html($location_area); ?>)</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($date_info['iso_date']): ?>
                        <p class="apollo-event-date">
                            <i class="ri-calendar-event-line"></i>
                            <span><?php echo esc_html(date_i18n('l, F j, Y', strtotime($date_info['iso_date']))); ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="apollo-event-body">
                <?php echo apply_filters('the_content', get_the_content()); ?>
                
                <?php if ($video_url): ?>
                    <div class="apollo-event-video" style="margin: 20px 0;">
                        <?php
                        // Simple YouTube embed detection
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                            if (!empty($matches[1])) {
                                echo '<div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000;">';
                                echo '<iframe src="https://www.youtube.com/embed/' . esc_attr($matches[1]) . '" frameborder="0" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p><a href="' . esc_url($video_url) . '" target="_blank" rel="noopener">' . esc_html__('Watch Video', 'apollo-events-manager') . '</a></p>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($tickets_url): ?>
                    <div class="apollo-event-tickets" style="margin: 20px 0;">
                        <a href="<?php echo esc_url($tickets_url); ?>" target="_blank" rel="noopener" class="button button-primary" style="display: inline-block; padding: 12px 24px; background: #0078d4; color: #fff; text-decoration: none; border-radius: 4px;">
                            <?php echo esc_html__('Buy Tickets', 'apollo-events-manager'); ?>
                            <i class="ri-external-link-line"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        // Restore original post
        $post = $original_post;
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [event_djs] - Lista de DJs
     */
    public function event_djs_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ), $atts, 'event_djs');
        
        $args = array(
            'post_type' => 'event_dj',
            'posts_per_page' => absint($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
        );
        
        $djs = get_posts($args);
        
        if (empty($djs)) {
            return '<p>' . esc_html__('No DJs found.', 'apollo-events-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="apollo-djs-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">';
        foreach ($djs as $dj) {
            $dj_name = get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
            $dj_image = get_post_meta($dj->ID, '_dj_image', true);
            $dj_bio = get_post_meta($dj->ID, '_dj_bio', true);
            
            echo '<div class="dj-card glass" style="padding: 20px; border-radius: 8px;">';
            if ($dj_image) {
                $image_url = filter_var($dj_image, FILTER_VALIDATE_URL) ? $dj_image : wp_get_attachment_url($dj_image);
                if ($image_url) {
                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($dj_name) . '" style="width: 100%; border-radius: 4px; margin-bottom: 10px;">';
                }
            }
            echo '<h3 style="margin: 10px 0;">' . esc_html($dj_name) . '</h3>';
            if ($dj_bio) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;">' . esc_html(wp_trim_words($dj_bio, 20)) . '</p>';
            }
            echo '<a href="' . get_permalink($dj->ID) . '" class="button" style="margin-top: 10px;">Ver Perfil</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [event_locals] - Lista de Locais
     */
    public function event_locals_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ), $atts, 'event_locals');
        
        $args = array(
            'post_type' => 'event_local',
            'posts_per_page' => absint($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
        );
        
        $locals = get_posts($args);
        
        if (empty($locals)) {
            return '<p>' . esc_html__('No venues found.', 'apollo-events-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="apollo-locals-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
        foreach ($locals as $local) {
            $local_name = get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
            $local_address = get_post_meta($local->ID, '_local_address', true);
            $local_city = get_post_meta($local->ID, '_local_city', true);
            
            echo '<div class="local-card glass" style="padding: 20px; border-radius: 8px;">';
            echo '<h3 style="margin: 0 0 10px;">' . esc_html($local_name) . '</h3>';
            if ($local_address) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;"><i class="ri-map-pin-line"></i> ' . esc_html($local_address) . '</p>';
            }
            if ($local_city) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;">' . esc_html($local_city) . '</p>';
            }
            echo '<a href="' . get_permalink($local->ID) . '" class="button" style="margin-top: 10px;">Ver Local</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [event_summary id="123"] - Resumo do evento
     */
    public function event_summary_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'event_summary');
        
        $event_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (!$event_id) {
            return '';
        }
        
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing') {
            return '';
        }
        
        ob_start();
        echo '<div class="event-summary glass" style="padding: 20px; border-radius: 8px;">';
        echo '<h2>' . esc_html(get_the_title($event_id)) . '</h2>';
        echo '<p>' . esc_html(get_the_excerpt($event_id)) . '</p>';
        echo '<a href="' . get_permalink($event_id) . '" class="button">Ver Evento</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [local_dashboard id="95"] - Dashboard do local
     */
    public function local_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'local_dashboard');
        
        $local_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (!$local_id) {
            return '';
        }
        
        $local = get_post($local_id);
        if (!$local || $local->post_type !== 'event_local') {
            return '';
        }
        
        // Get events for this local
        $events = get_posts(array(
            'post_type' => 'event_listing',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_event_local_ids',
                    'value' => $local_id,
                    'compare' => '=',
                ),
            ),
        ));
        
        $local_name = get_post_meta($local_id, '_local_name', true) ?: $local->post_title;
        
        ob_start();
        echo '<div class="local-dashboard glass" style="padding: 20px; border-radius: 8px;">';
        echo '<h2>' . esc_html($local_name) . '</h2>';
        echo '<p>' . sprintf(esc_html__('Total Events: %d', 'apollo-events-manager'), count($events)) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [past_events] - Eventos passados
     */
    public function past_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
        ), $atts, 'past_events');
        
        $args = array(
            'post_type' => 'event_listing',
            'posts_per_page' => absint($atts['limit']),
            'post_status' => 'publish',
            'meta_key' => '_event_start_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_event_start_date',
                    'value' => current_time('mysql'),
                    'compare' => '<',
                    'type' => 'DATETIME',
                ),
            ),
        );
        
        $events = get_posts($args);
        
        if (empty($events)) {
            return '<p>' . esc_html__('No past events found.', 'apollo-events-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="past-events-list" style="display: grid; gap: 15px;">';
        foreach ($events as $event) {
            echo '<div class="past-event-item glass" style="padding: 15px; border-radius: 6px;">';
            echo '<h3 style="margin: 0;"><a href="' . get_permalink($event->ID) . '">' . esc_html(get_the_title($event->ID)) . '</a></h3>';
            $start_date = get_post_meta($event->ID, '_event_start_date', true);
            if ($start_date) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;">' . esc_html(date_i18n('F j, Y', strtotime($start_date))) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [single_event_dj id="92"] - Single DJ do evento
     */
    public function single_event_dj_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'single_event_dj');
        
        $dj_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (!$dj_id) {
            return '';
        }
        
        $dj = get_post($dj_id);
        if (!$dj || $dj->post_type !== 'event_dj') {
            return '';
        }
        
        $dj_name = get_post_meta($dj_id, '_dj_name', true) ?: $dj->post_title;
        $dj_bio = get_post_meta($dj_id, '_dj_bio', true);
        $dj_image = get_post_meta($dj_id, '_dj_image', true);
        
        ob_start();
        echo '<div class="single-dj-profile glass" style="padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto;">';
        if ($dj_image) {
            $image_url = filter_var($dj_image, FILTER_VALIDATE_URL) ? $dj_image : wp_get_attachment_url($dj_image);
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($dj_name) . '" style="width: 100%; max-width: 300px; border-radius: 8px; display: block; margin: 0 auto 20px;">';
            }
        }
        echo '<h1 style="text-align: center; margin-bottom: 20px;">' . esc_html($dj_name) . '</h1>';
        if ($dj_bio) {
            echo '<div class="dj-bio" style="line-height: 1.6;">' . wp_kses_post(wpautop($dj_bio)) . '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [single_event_local id="95"] - Single local do evento
     */
    public function single_event_local_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'single_event_local');
        
        $local_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (!$local_id) {
            return '';
        }
        
        $local = get_post($local_id);
        if (!$local || $local->post_type !== 'event_local') {
            return '';
        }
        
        $local_name = get_post_meta($local_id, '_local_name', true) ?: $local->post_title;
        $local_description = get_post_meta($local_id, '_local_description', true);
        $local_address = get_post_meta($local_id, '_local_address', true);
        $local_city = get_post_meta($local_id, '_local_city', true);
        
        ob_start();
        echo '<div class="single-local-profile glass" style="padding: 30px; border-radius: 12px;">';
        echo '<h1 style="margin-bottom: 20px;">' . esc_html($local_name) . '</h1>';
        if ($local_description) {
            echo '<div class="local-description" style="margin-bottom: 20px; line-height: 1.6;">' . wp_kses_post(wpautop($local_description)) . '</div>';
        }
        if ($local_address || $local_city) {
            echo '<div class="local-info" style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
            if ($local_address) {
                echo '<p style="margin: 5px 0;"><i class="ri-map-pin-line"></i> ' . esc_html($local_address) . '</p>';
            }
            if ($local_city) {
                echo '<p style="margin: 5px 0;"><i class="ri-building-line"></i> ' . esc_html($local_city) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [submit_event_form] - Formulário de submissão de evento
     */
    public function submit_event_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('You must be logged in to submit an event.', 'apollo-events-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="submit-event-form-wrapper glass" style="padding: 30px; border-radius: 12px; max-width: 800px; margin: 0 auto;">';
        echo '<h2>' . esc_html__('Submit New Event', 'apollo-events-manager') . '</h2>';
        echo '<form method="post" action="' . admin_url('post-new.php?post_type=event_listing') . '">';
        echo '<p style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
        echo esc_html__('Use the WordPress admin to create events with full functionality.', 'apollo-events-manager');
        echo '</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=event_listing') . '" class="button button-primary" style="margin-top: 15px;">' . esc_html__('Create Event', 'apollo-events-manager') . '</a>';
        echo '</form>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [submit_dj_form] - Formulário de submissão de DJ
     */
    public function submit_dj_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('You must be logged in to submit a DJ.', 'apollo-events-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="submit-dj-form-wrapper glass" style="padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto;">';
        echo '<h2>' . esc_html__('Submit New DJ', 'apollo-events-manager') . '</h2>';
        echo '<p style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
        echo esc_html__('Use the WordPress admin to create DJ profiles.', 'apollo-events-manager');
        echo '</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=event_dj') . '" class="button button-primary" style="margin-top: 15px;">' . esc_html__('Create DJ Profile', 'apollo-events-manager') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [submit_local_form] - Formulário de submissão de local
     */
    public function submit_local_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('You must be logged in to submit a venue.', 'apollo-events-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="submit-local-form-wrapper glass" style="padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto;">';
        echo '<h2>' . esc_html__('Submit New Venue', 'apollo-events-manager') . '</h2>';
        echo '<p style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
        echo esc_html__('Use the WordPress admin to create venue profiles.', 'apollo-events-manager');
        echo '</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=event_local') . '" class="button button-primary" style="margin-top: 15px;">' . esc_html__('Create Venue', 'apollo-events-manager') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Render placeholders documentation page
     * Organized by CPT with uni.css styling
     */
    public function render_placeholders_page() {
        if ( ! function_exists( 'apollo_events_get_placeholders' ) ) {
            echo '<div class="wrap"><p>' . esc_html__('Placeholder system not loaded.', 'apollo-events-manager') . '</p></div>';
            return;
        }

        $placeholders = apollo_events_get_placeholders();
        
        // Organize placeholders by CPT
        $organized = [
            'event_listing' => [],
            'event_local' => [],
            'event_dj' => [],
        ];
        
        foreach ( $placeholders as $id => $placeholder ) {
            // Determine CPT based on key patterns
            if ( strpos( $id, 'local_' ) === 0 ) {
                $organized['event_local'][$id] = $placeholder;
            } elseif ( strpos( $id, 'dj_' ) === 0 ) {
                $organized['event_dj'][$id] = $placeholder;
            } else {
                $organized['event_listing'][$id] = $placeholder;
            }
        }
        
        // Enqueue uni.css and RemixIcon for admin page
        if ( ! wp_style_is( 'apollo-uni-css', 'enqueued' ) ) {
            wp_enqueue_style( 'apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', array(), null );
        }
        if ( ! wp_style_is( 'remixicon', 'enqueued' ) ) {
            wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );
        }
        ?>
        <div class="wrap" style="padding: 20px;">
            <h1 style="margin-bottom: 10px;"><?php echo esc_html__('Apollo Events – Shortcodes & Placeholders', 'apollo-events-manager'); ?></h1>
            <p><?php echo esc_html__('Complete reference of all available placeholders organized by Custom Post Type.', 'apollo-events-manager'); ?></p>

            <div class="container p-10" style="max-width: 1400px; margin: 30px auto 0;">
                
                <!-- EVENT LISTING PLACEHOLDERS -->
                <div class="glass-table-card glass" style="margin-bottom: 30px;">
                    <div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                        <h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 10px;">
                            <i class="ri-calendar-event-line" style="font-size: 1.5rem;"></i>
                            <?php echo esc_html__('Event Listing (event_listing)', 'apollo-events-manager'); ?>
                            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-main, #64748b); margin-left: 10px;">
                                (<?php echo count( $organized['event_listing'] ); ?> placeholders)
                            </span>
                        </h3>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;"><?php echo esc_html__('Placeholder ID', 'apollo-events-manager'); ?></th>
                                    <th style="width: 18%;"><?php echo esc_html__('Label', 'apollo-events-manager'); ?></th>
                                    <th style="width: 30%;"><?php echo esc_html__('Description', 'apollo-events-manager'); ?></th>
                                    <th style="width: 10%;"><?php echo esc_html__('Source', 'apollo-events-manager'); ?></th>
                                    <th style="width: 17%;"><?php echo esc_html__('Meta Key / Taxonomy', 'apollo-events-manager'); ?></th>
                                    <th style="width: 10%;"><?php echo esc_html__('Example', 'apollo-events-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $organized['event_listing'] ) ) : ?>
                                    <?php foreach ( $organized['event_listing'] as $placeholder ) : ?>
                                    <tr>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo esc_html( $placeholder['id'] ); ?></code></td>
                                        <td><strong><?php echo esc_html( $placeholder['label'] ); ?></strong></td>
                                        <td><?php echo esc_html( $placeholder['description'] ); ?></td>
                                        <td>
                                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                                <?php
                                                $icon = 'ri-file-text-line';
                                                $color = '#64748b';
                                                if ( $placeholder['source'] === 'meta' ) {
                                                    $icon = 'ri-database-line';
                                                    $color = '#3b82f6';
                                                } elseif ( $placeholder['source'] === 'taxonomy' ) {
                                                    $icon = 'ri-price-tag-3-line';
                                                    $color = '#10b981';
                                                }
                                                ?>
                                                <i class="<?php echo esc_attr( $icon ); ?>" style="color: <?php echo esc_attr( $color ); ?>;"></i>
                                                <?php echo esc_html( ucfirst( $placeholder['source'] ) ); ?>
                                            </span>
                                        </td>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em;"><?php echo esc_html( $placeholder['key'] ); ?></code></td>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: var(--text-main, #64748b);"><?php echo esc_html( $placeholder['example'] ); ?></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-main, #64748b);">
                                            <?php echo esc_html__('No placeholders found for this CPT.', 'apollo-events-manager'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- EVENT LOCAL PLACEHOLDERS -->
                <div class="glass-table-card glass" style="margin-bottom: 30px;">
                    <div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                        <h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 10px;">
                            <i class="ri-map-pin-line" style="font-size: 1.5rem;"></i>
                            <?php echo esc_html__('Local / Venue (event_local)', 'apollo-events-manager'); ?>
                            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-main, #64748b); margin-left: 10px;">
                                (<?php echo count( $organized['event_local'] ); ?> placeholders)
                            </span>
                        </h3>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;"><?php echo esc_html__('Placeholder ID', 'apollo-events-manager'); ?></th>
                                    <th style="width: 18%;"><?php echo esc_html__('Label', 'apollo-events-manager'); ?></th>
                                    <th style="width: 30%;"><?php echo esc_html__('Description', 'apollo-events-manager'); ?></th>
                                    <th style="width: 10%;"><?php echo esc_html__('Source', 'apollo-events-manager'); ?></th>
                                    <th style="width: 17%;"><?php echo esc_html__('Meta Key / Taxonomy', 'apollo-events-manager'); ?></th>
                                    <th style="width: 10%;"><?php echo esc_html__('Example', 'apollo-events-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $organized['event_local'] ) ) : ?>
                                    <?php foreach ( $organized['event_local'] as $placeholder ) : ?>
                                    <tr>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo esc_html( $placeholder['id'] ); ?></code></td>
                                        <td><strong><?php echo esc_html( $placeholder['label'] ); ?></strong></td>
                                        <td><?php echo esc_html( $placeholder['description'] ); ?></td>
                                        <td>
                                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="ri-database-line" style="color: #3b82f6;"></i>
                                                <?php echo esc_html( ucfirst( $placeholder['source'] ) ); ?>
                                            </span>
                                        </td>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em;"><?php echo esc_html( $placeholder['key'] ); ?></code></td>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: var(--text-main, #64748b);"><?php echo esc_html( $placeholder['example'] ); ?></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-main, #64748b);">
                                            <?php echo esc_html__('No placeholders found for this CPT.', 'apollo-events-manager'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- EVENT DJ PLACEHOLDERS -->
                <div class="glass-table-card glass" style="margin-bottom: 30px;">
                    <div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                        <h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 10px;">
                            <i class="ri-music-line" style="font-size: 1.5rem;"></i>
                            <?php echo esc_html__('DJ (event_dj)', 'apollo-events-manager'); ?>
                            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-main, #64748b); margin-left: 10px;">
                                (<?php echo count( $organized['event_dj'] ); ?> placeholders)
                            </span>
                        </h3>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;"><?php echo esc_html__('Placeholder ID', 'apollo-events-manager'); ?></th>
                                    <th style="width: 18%;"><?php echo esc_html__('Label', 'apollo-events-manager'); ?></th>
                                    <th style="width: 30%;"><?php echo esc_html__('Description', 'apollo-events-manager'); ?></th>
                                    <th style="width: 10%;"><?php echo esc_html__('Source', 'apollo-events-manager'); ?></th>
                                    <th style="width: 17%;"><?php echo esc_html__('Meta Key / Taxonomy', 'apollo-events-manager'); ?></th>
                                    <th style="width: 10%;"><?php echo esc_html__('Example', 'apollo-events-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $organized['event_dj'] ) ) : ?>
                                    <?php foreach ( $organized['event_dj'] as $placeholder ) : ?>
                                    <tr>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo esc_html( $placeholder['id'] ); ?></code></td>
                                        <td><strong><?php echo esc_html( $placeholder['label'] ); ?></strong></td>
                                        <td><?php echo esc_html( $placeholder['description'] ); ?></td>
                                        <td>
                                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="ri-database-line" style="color: #3b82f6;"></i>
                                                <?php echo esc_html( ucfirst( $placeholder['source'] ) ); ?>
                                            </span>
                                        </td>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em;"><?php echo esc_html( $placeholder['key'] ); ?></code></td>
                                        <td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: var(--text-main, #64748b);"><?php echo esc_html( $placeholder['example'] ); ?></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-main, #64748b);">
                                            <?php echo esc_html__('No placeholders found for this CPT.', 'apollo-events-manager'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Usage Examples Section -->
            <div class="container p-10" style="max-width: 1400px; margin: 30px auto;">
                <div class="glass-table-card glass">
                    <div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                        <h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b);">
                            <i class="ri-code-s-slash-line"></i>
                            <?php echo esc_html__('Usage Examples', 'apollo-events-manager'); ?>
                        </h3>
                    </div>
                    <div style="padding: 20px;">
                        <h4 style="margin-top: 0;"><?php echo esc_html__('Template Tags', 'apollo-events-manager'); ?></h4>
                        <pre style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 8px; overflow-x: auto; border: 1px solid var(--border-color, #e2e8f0);"><code><?php
echo esc_html( "<?php\n" );
echo esc_html( "// Get placeholder value for current event\n" );
echo esc_html( "echo apollo_event_get_placeholder_value('start_month_pt');\n" );
echo esc_html( "\n" );
echo esc_html( "// Get placeholder value for specific event\n" );
echo esc_html( "echo apollo_event_get_placeholder_value('dj_list', 123);\n" );
echo esc_html( "\n" );
echo esc_html( "// Get all placeholders registry\n" );
echo esc_html( "\$placeholders = apollo_events_get_placeholders();\n" );
?></code></pre>

                        <h4><?php echo esc_html__('Shortcodes', 'apollo-events-manager'); ?></h4>
                        <pre style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 8px; overflow-x: auto; border: 1px solid var(--border-color, #e2e8f0);"><code><?php
echo esc_html( "[apollo_event field=\"start_month_pt\"]\n" );
echo esc_html( "[apollo_event field=\"dj_list\" id=\"123\"]\n" );
echo esc_html( "[apollo_event field=\"location\"]\n" );
echo esc_html( "[apollo_event field=\"banner_url\"]\n" );
echo esc_html( "[apollo_event field=\"video_url\"]\n" );
echo esc_html( "[apollo_event field=\"dj_set_url\"]\n" );
?></code></pre>

                        <h4><?php echo esc_html__('Common Use Cases', 'apollo-events-manager'); ?></h4>
                        <ul style="line-height: 2;">
                            <li><strong><?php echo esc_html__('Display DJ line-up:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="dj_list"]</code></li>
                            <li><strong><?php echo esc_html__('Display event date:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="start_day"]</code> <code>[apollo_event field="start_month_pt"]</code></li>
                            <li><strong><?php echo esc_html__('Display location:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="location"]</code> or <code>[apollo_event field="location_full"]</code></li>
                            <li><strong><?php echo esc_html__('Display banner image:', 'apollo-events-manager'); ?></strong> <code>&lt;img src="[apollo_event field="banner_url"]" alt="Event Banner"&gt;</code></li>
                            <li><strong><?php echo esc_html__('Display YouTube video:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="video_url"]</code></li>
                            <li><strong><?php echo esc_html__('Display DJ set URL:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="dj_set_url"]</code></li>
                            <li><strong><?php echo esc_html__('Display DJ original projects:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="dj_original_project_1"]</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new Apollo_Events_Manager_Plugin();

// Log verification completion
if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Apollo Events Manager 2.0.0: Plugin loaded successfully - ' . date('Y-m-d H:i:s'));
}

/**
 * Helper: Get events page (published or trash)
 * Returns page object if found, null otherwise
 */
function apollo_em_get_events_page() {
    // Try published page first
    $page = get_page_by_path('eventos');
    if ($page) {
        return $page;
    }
    
    // Try to find in trash
    $trashed = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'trash',
        'name'           => 'eventos',
        'posts_per_page' => 1,
        'fields'         => 'all',
    ]);
    
    if (!empty($trashed)) {
        return $trashed[0];
    }
    
    return null;
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'apollo_events_manager_activate');
function apollo_events_manager_activate() {
    // Register CPTs and flush rewrite rules
    require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
    Apollo_Post_Types::flush_rewrite_rules_on_activation();
    
    // Register analytics capability
    $roles = array('administrator', 'editor');
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap('view_apollo_event_stats')) {
            $role->add_cap('view_apollo_event_stats');
        }
    }
    
    // Handle events page creation/restoration
    $events_page = apollo_em_get_events_page();
    
    if ($events_page && 'trash' === $events_page->post_status) {
        // Restore from trash
        wp_update_post([
            'ID'          => $events_page->ID,
            'post_status' => 'publish',
        ]);
        error_log('✅ Apollo: Restored /eventos/ page from trash (ID: ' . $events_page->ID . ')');
    } elseif (!$events_page) {
        // Create new only if doesn't exist at all
        $page_id = wp_insert_post([
            'post_title'   => 'Eventos',
            'post_name'    => 'eventos',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[apollo_events_portal]',
        ]);
        
        if ($page_id && !is_wp_error($page_id)) {
            error_log('✅ Apollo: Created /eventos/ page (ID: ' . $page_id . ')');
        }
    } else {
        // Page already exists and is published
        error_log('✅ Apollo: /eventos/ page already exists (ID: ' . $events_page->ID . ')');
    }
    
    // Log activation
    error_log('✅ Apollo Events Manager 2.0.0 activated successfully');
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'apollo_events_manager_deactivate');
function apollo_events_manager_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    error_log('⚠️ Apollo Events Manager 2.0.0 deactivated');
}
