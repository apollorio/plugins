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
// === Capabilities e ativação ===
register_activation_hook(__FILE__, function() {
    // Define capability customizada para visualizar estatísticas
    $cap = 'view_apollo_event_stats';
    $roles_to_grant = array('administrator', 'editor');
    foreach ($roles_to_grant as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap($cap)) {
            $role->add_cap($cap);
        }
    }
    // Define papéis permitidos por padrão (configurável depois)
    if (get_option('apollo_stats_allowed_roles') === false) {
        update_option('apollo_stats_allowed_roles', $roles_to_grant);
    }
});

// Helper básico disponível globalmente para futuros módulos (social/analytics)
if (!function_exists('apollo_record_event_view')) {
    function apollo_record_event_view($event_id) {
        $event_id = intval($event_id);
        if ($event_id <= 0) return;
        $views = (int) get_post_meta($event_id, '_apollo_event_views', true);
        $views++;
        update_post_meta($event_id, '_apollo_event_views', $views);
        do_action('apollo_event_viewed', $event_id, get_current_user_id());
    }
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

// Include Dashboard
require_once plugin_dir_path(__FILE__) . 'includes/class-apollo-events-dashboard.php';

// === Admin: página "Shortcodes & Placeholders" (documentação)
add_action('admin_menu', function() {
    // Determina se o usuário pode ver estatísticas/documentação
    $allowed_roles = (array) get_option('apollo_stats_allowed_roles', array('administrator'));
    $user = wp_get_current_user();
    $can_view = current_user_can('view_apollo_event_stats');
    if (!$can_view) {
        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $user->roles, true)) {
                $can_view = true;
                break;
            }
        }
    }
    $capability = $can_view ? 'read' : 'manage_options';

    // Adiciona como submenu do Dashboard Apollo (se existir)
    add_submenu_page(
        'apollo-events-dashboard',
        __('Shortcodes & Placeholders', 'apollo-events-manager'),
        __('Shortcodes & Placeholders', 'apollo-events-manager'),
        $capability,
        'apollo-events-docs',
        function() {
            echo '<div class="wrap">';
            echo '<h1>Shortcodes & Placeholders</h1>';
            echo '<p>Referência rápida de shortcodes e meta keys usadas no portal de eventos.</p>';
            echo '<h2>Shortcodes</h2><ul>';
            echo '<li><code>[apollo_events]</code> — (opcional) exibe listagem de eventos.</li>';
            echo '</ul>';
            echo '<h2>Meta keys (Eventos)</h2>';
            echo '<table class="widefat"><thead><tr><th>Meta key</th><th>Descrição</th></tr></thead><tbody>';
            $rows = array(
                array('_event_start_date', 'Data/hora de início (Y-m-d ou Y-m-d H:i:s)'),
                array('_event_location', 'Local | Área (string)'),
                array('_event_banner', 'Banner do evento (ID ou URL)'),
                array('_timetable', 'Slots de DJs (array serializado com dj/time)'),
                array('_dj_name', 'Fallback de DJ (string)'),
                array('_event_djs', 'Relacionamento com posts de DJ (array de IDs)'),
                array('_apollo_event_views', 'Contador de visualizações do evento (int)'),
            );
            foreach ($rows as $r) {
                printf('<tr><td><code>%s</code></td><td>%s</td></tr>', esc_html($r[0]), esc_html($r[1]));
            }
            echo '</tbody></table>';
            echo '</div>';
        }
    );
});

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
