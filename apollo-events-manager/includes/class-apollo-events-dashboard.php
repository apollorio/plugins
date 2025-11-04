<?php
/**
 * Apollo Events Dashboard
 * Admin dashboard with statistics, events, DJs, and venues management
 *
 * @package Apollo_Events_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Apollo_Events_Dashboard
 */
class Apollo_Events_Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        add_action('rest_api_init', array($this, 'register_rest_endpoint'));
        add_action('wp_ajax_apollo_dashboard_data', array($this, 'ajax_dashboard_data'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Apollo Events', 'apollo-events-manager'),
            __('Apollo', 'apollo-events-manager'),
            'manage_options',
            'apollo-events',
            array($this, 'render_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'apollo-events',
            __('Dashboard', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'manage_options',
            'apollo-events',
            array($this, 'render_dashboard_page')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap apollo-dashboard">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div id="apollo-dashboard-app"></div>
        </div>
        <?php
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets($hook) {
        // Only load on dashboard page
        if ($hook !== 'toplevel_page_apollo-events') {
            return;
        }

        // CSS
        wp_enqueue_style(
            'apollo-events-dashboard',
            APOLLO_WPEM_URL . 'assets/css/apollo-events-dashboard.css',
            array(),
            APOLLO_WPEM_VERSION
        );

        // Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js',
            array(),
            '4.4.0',
            true
        );

        // Dashboard JS
        wp_enqueue_script(
            'apollo-events-dashboard',
            APOLLO_WPEM_URL . 'assets/js/apollo-events-dashboard.js',
            array('chart-js', 'wp-api-fetch'),
            APOLLO_WPEM_VERSION,
            true
        );

        // Localize script
        wp_localize_script('apollo-events-dashboard', 'apolloDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('apollo/v1/dashboard'),
            'nonce' => wp_create_nonce('apollo_dashboard_nonce'),
        ));
    }

    /**
     * Register REST API endpoint
     */
    public function register_rest_endpoint() {
        register_rest_route('apollo/v1', '/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    /**
     * Check permission for REST endpoint
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }

    /**
     * AJAX handler (fallback)
     */
    public function ajax_dashboard_data() {
        check_ajax_referer('apollo_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $data = $this->get_dashboard_data();
        wp_send_json_success($data);
    }

    /**
     * Get dashboard data (used by REST and AJAX)
     */
    public function get_dashboard_data() {
        // Get all events (last 12 months + future)
        $now = current_time('mysql');
        $twelve_months_ago = date('Y-m-d H:i:s', strtotime('-12 months', strtotime($now)));
        
        $args = array(
            'post_type' => 'event_listing',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_event_start_date',
                    'value' => $twelve_months_ago,
                    'compare' => '>=',
                    'type' => 'DATETIME',
                ),
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC',
        );

        $events_query = new WP_Query($args);
        
        // Pre-load all meta to avoid N+1 queries
        if ($events_query->have_posts()) {
            $event_ids = wp_list_pluck($events_query->posts, 'ID');
            update_meta_cache('post', $event_ids);
        }

        // Process events
        $events = array();
        $djs_map = array();
        $venues_map = array();
        $now_timestamp = current_time('timestamp');
        $today_start = strtotime(date('Y-m-d 00:00:00', $now_timestamp));
        $today_end = strtotime(date('Y-m-d 23:59:59', $now_timestamp));

        $total_eventos = 0;
        $eventos_futuros = 0;
        $eventos_hoje = 0;
        $eventos_passados = 0;

        if ($events_query->have_posts()) {
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $event_id = get_the_ID();
                $total_eventos++;

                // Get event data
                $start_date_raw = get_post_meta($event_id, '_event_start_date', true);
                $event_location_raw = get_post_meta($event_id, '_event_location', true);
                $timetable = get_post_meta($event_id, '_timetable', true);
                $dj_name_fallback = get_post_meta($event_id, '_dj_name', true);

                // Parse date
                $date_info = $this->parse_event_date($start_date_raw);
                $event_timestamp = $date_info['timestamp'];

                // Determine status
                $status = 'futuro';
                if ($event_timestamp && $event_timestamp < $today_start) {
                    $status = 'passado';
                    $eventos_passados++;
                } elseif ($event_timestamp && $event_timestamp >= $today_start && $event_timestamp <= $today_end) {
                    $status = 'hoje';
                    $eventos_hoje++;
                } else {
                    $eventos_futuros++;
                }

                // Extract DJs
                $djs_list = $this->extract_djs_from_event($event_id, $timetable, $dj_name_fallback);
                
                // Aggregate DJs
                foreach ($djs_list as $dj_name) {
                    $dj_normalized = $this->normalize_dj_name($dj_name);
                    if (!isset($djs_map[$dj_normalized])) {
                        $djs_map[$dj_normalized] = array(
                            'name' => $dj_name,
                            'events_future' => 0,
                            'events_total' => 0,
                        );
                    }
                    $djs_map[$dj_normalized]['events_total']++;
                    if ($status === 'futuro' || $status === 'hoje') {
                        $djs_map[$dj_normalized]['events_future']++;
                    }
                }

                // Extract venue
                $venue_info = $this->extract_venue_from_location($event_location_raw);
                
                if (!empty($venue_info['local'])) {
                    $venue_key = $venue_info['local'];
                    if (!isset($venues_map[$venue_key])) {
                        $venues_map[$venue_key] = array(
                            'local' => $venue_info['local'],
                            'area' => $venue_info['area'],
                            'events_future' => 0,
                            'events_total' => 0,
                        );
                    }
                    $venues_map[$venue_key]['events_total']++;
                    if ($status === 'futuro' || $status === 'hoje') {
                        $venues_map[$venue_key]['events_future']++;
                    }
                }

                // Build event data
                $events[] = array(
                    'id' => $event_id,
                    'title' => get_the_title(),
                    'date' => $date_info['formatted'],
                    'date_raw' => $start_date_raw,
                    'timestamp' => $event_timestamp,
                    'local' => $venue_info['local'],
                    'area' => $venue_info['area'],
                    'djs' => $djs_list,
                    'djs_display' => $this->format_djs_display($djs_list, 3),
                    'status' => $status,
                    'permalink' => get_permalink(),
                );
            }
            wp_reset_postdata();
        }

        // Convert maps to arrays
        $djs = array_values($djs_map);
        usort($djs, function($a, $b) {
            return $b['events_total'] - $a['events_total'];
        });

        $venues = array_values($venues_map);
        usort($venues, function($a, $b) {
            return $b['events_total'] - $a['events_total'];
        });

        // Get events by month for chart (next 6 months)
        $events_by_month = $this->get_events_by_month($events, 6);

        // Get Plausible data (via filter)
        $plausible_data = $this->get_plausible_data();

        return array(
            'eventos' => $events,
            'djs' => $djs,
            'locais' => $venues,
            'resumo' => array(
                'total_eventos' => $total_eventos,
                'eventos_futuros' => $eventos_futuros,
                'eventos_hoje' => $eventos_hoje,
                'eventos_passados' => $eventos_passados,
            ),
            'eventos_por_mes' => $events_by_month,
            'plausible' => $plausible_data,
        );
    }

    /**
     * Parse event date
     */
    private function parse_event_date($raw) {
        if (empty($raw)) {
            return array(
                'timestamp' => null,
                'formatted' => '--/--',
                'day' => '',
                'month' => '',
            );
        }

        // Try to parse
        $timestamp = strtotime($raw);
        if (!$timestamp) {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            if ($dt) {
                $timestamp = $dt->getTimestamp();
            }
        }

        if (!$timestamp) {
            return array(
                'timestamp' => null,
                'formatted' => '--/--',
                'day' => '',
                'month' => '',
            );
        }

        $pt_months = array('jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez');
        $month_idx = (int) date_i18n('n', $timestamp) - 1;

        return array(
            'timestamp' => $timestamp,
            'formatted' => date_i18n('d', $timestamp) . ' ' . ($pt_months[$month_idx] ?? ''),
            'day' => date_i18n('d', $timestamp),
            'month' => $pt_months[$month_idx] ?? '',
        );
    }

    /**
     * Extract DJs from event
     */
    private function extract_djs_from_event($event_id, $timetable, $dj_name_fallback) {
        $djs = array();

        // From timetable
        if (!empty($timetable) && is_array($timetable)) {
            foreach ($timetable as $slot) {
                if (empty($slot['dj'])) {
                    continue;
                }

                if (is_numeric($slot['dj'])) {
                    // DJ post ID
                    $dj_name = get_post_meta($slot['dj'], '_dj_name', true);
                    if (!$dj_name) {
                        $dj_post = get_post($slot['dj']);
                        $dj_name = $dj_post ? $dj_post->post_title : '';
                    }
                } else {
                    // String DJ name
                    $dj_name = (string) $slot['dj'];
                }

                if (!empty($dj_name)) {
                    $djs[] = trim($dj_name);
                }
            }
        }

        // Fallback: _dj_name meta
        if (empty($djs) && !empty($dj_name_fallback)) {
            $djs[] = trim($dj_name_fallback);
        }

        // Remove duplicates and empty values
        return array_values(array_unique(array_filter($djs)));
    }

    /**
     * Normalize DJ name (for grouping)
     */
    private function normalize_dj_name($name) {
        return strtolower(trim($name));
    }

    /**
     * Format DJs display (max visible + "+X")
     */
    private function format_djs_display($djs, $max_visible = 3) {
        if (empty($djs)) {
            return '—';
        }

        $visible = array_slice($djs, 0, $max_visible);
        $remaining = count($djs) - $max_visible;

        $display = implode(', ', $visible);
        if ($remaining > 0) {
            $display .= ' +' . $remaining;
        }

        return $display;
    }

    /**
     * Extract venue from location string
     */
    private function extract_venue_from_location($location_raw) {
        $local = '';
        $area = '';

        if (!empty($location_raw)) {
            if (strpos($location_raw, '|') !== false) {
                list($local, $area) = array_map('trim', explode('|', $location_raw, 2));
            } else {
                $local = trim($location_raw);
            }
        }

        return array(
            'local' => $local,
            'area' => $area,
        );
    }

    /**
     * Get events grouped by month (next N months)
     */
    private function get_events_by_month($events, $months = 6) {
        $now = current_time('timestamp');
        $future_events = array_filter($events, function($event) use ($now) {
            return $event['timestamp'] && $event['timestamp'] >= $now;
        });

        $by_month = array();
        $pt_months = array('jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez');

        foreach ($future_events as $event) {
            if (!$event['timestamp']) {
                continue;
            }

            $month_key = date('Y-m', $event['timestamp']);
            $month_label = $pt_months[(int) date('n', $event['timestamp']) - 1] . ' ' . date('Y', $event['timestamp']);

            if (!isset($by_month[$month_key])) {
                $by_month[$month_key] = array(
                    'label' => $month_label,
                    'count' => 0,
                );
            }
            $by_month[$month_key]['count']++;
        }

        // Sort and limit
        ksort($by_month);
        $by_month = array_slice($by_month, 0, $months, true);

        return array_values($by_month);
    }

    /**
     * Get Plausible analytics data
     */
    private function get_plausible_data() {
        // Use filter to allow external integration
        $data = apply_filters('apollo_events_plausible_fetch', null, array(
            'endpoint' => 'stats',
            'params' => array(
                'site_id' => 'apollo.rio.br',
                'period' => '30d',
            ),
        ));

        // If filter returns data, use it
        if (is_array($data) && isset($data['pageviews'])) {
            return array(
                'pageviews_30d' => $data['pageviews'],
                'top_event_urls' => isset($data['top_urls']) ? $data['top_urls'] : array(),
            );
        }

        // Default: no data
        return array(
            'pageviews_30d' => null,
            'top_event_urls' => array(),
        );
    }
}

// Initialize dashboard
new Apollo_Events_Dashboard();

