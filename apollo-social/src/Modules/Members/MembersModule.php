<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;
defined('ABSPATH') || exit;
final class MembersModule {
    private static ?self $instance = null;
    private bool $initialized = false;
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function init(): void {
        if ($this->initialized) return;
        $this->initialized = true;
        add_action('init', [$this, 'registerHooks'], 5);
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    public function registerHooks(): void {
        add_shortcode('apollo_members_directory', [$this, 'renderDirectory']);
        add_shortcode('apollo_online_users', [$this, 'renderOnlineUsers']);
        add_shortcode('apollo_recently_active', [$this, 'renderRecentlyActive']);
    }
    public function registerEndpoints(): void {
        register_rest_route('apollo/v1', '/members', [
            'methods' => 'GET',
            'callback' => [$this, 'getMembers'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('apollo/v1', '/members/online', [
            'methods' => 'GET',
            'callback' => [$this, 'getOnlineMembers'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('apollo/v1', '/members/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getMember'],
            'permission_callback' => '__return_true',
        ]);
    }
    public function enqueueAssets(): void {
        if (!is_admin()) {
            wp_register_script('apollo-members', APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/members.min.js', [], APOLLO_SOCIAL_VERSION, true);
            wp_localize_script('apollo-members', 'apolloMembers', [
                'api' => rest_url('apollo/v1/members'),
                'nonce' => wp_create_nonce('wp_rest'),
                'online_interval' => 60000,
            ]);
        }
    }
    public function getMembers(\WP_REST_Request $request): \WP_REST_Response {
        $page = (int) $request->get_param('page') ?: 1;
        $per_page = min((int) $request->get_param('per_page') ?: 20, 100);
        $search = sanitize_text_field($request->get_param('search') ?: '');
        $role = sanitize_key($request->get_param('role') ?: '');
        $orderby = sanitize_key($request->get_param('orderby') ?: 'registered');
        $args = [
            'number' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => 'DESC',
            'fields' => ['ID', 'display_name', 'user_registered'],
        ];
        if ($search) $args['search'] = "*{$search}*";
        if ($role) $args['role'] = $role;
        $query = new \WP_User_Query($args);
        $users = $query->get_results();
        $members = [];
        foreach ($users as $user) {
            $members[] = $this->formatMember($user->ID);
        }
        return new \WP_REST_Response([
            'members' => $members,
            'total' => $query->get_total(),
            'pages' => ceil($query->get_total() / $per_page),
        ], 200);
    }
    public function getOnlineMembers(): \WP_REST_Response {
        global $wpdb;
        $threshold = gmdate('Y-m-d H:i:s', time() - 900);
        $online_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'apollo_last_active' AND meta_value > %s ORDER BY meta_value DESC LIMIT 50",
            $threshold
        ));
        $members = [];
        foreach ($online_ids as $uid) {
            $members[] = $this->formatMember((int)$uid, true);
        }
        return new \WP_REST_Response(['online' => $members, 'count' => count($members)], 200);
    }
    public function getMember(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = (int) $request->get_param('id');
        $user = get_userdata($user_id);
        if (!$user) return new \WP_REST_Response(['error' => 'User not found'], 404);
        return new \WP_REST_Response($this->formatMember($user_id, true), 200);
    }
    private function formatMember(int $user_id, bool $detailed = false): array {
        $user = get_userdata($user_id);
        if (!$user) return [];
        $data = [
            'id' => $user_id,
            'name' => $user->display_name,
            'avatar' => get_avatar_url($user_id, ['size' => 150]),
            'online' => $this->isOnline($user_id),
            'badges' => function_exists('apollo_social_get_user_badges') ? apollo_social_get_user_badges($user_id) : [],
            'verified' => (bool) get_user_meta($user_id, '_apollo_verified', true),
        ];
        if ($detailed) {
            $data['profile_url'] = home_url("/id/{$user_id}");
            $data['registered'] = $user->user_registered;
            $data['points'] = (int) get_user_meta($user_id, '_apollo_points', true);
            $data['rank'] = get_user_meta($user_id, '_apollo_rank', true) ?: 'bronze';
            $data['close_friends_count'] = count(get_user_meta($user_id, '_apollo_bubble', true) ?: []);
            $data['groups_count'] = $this->getUserGroupsCount($user_id);
            $data['events_count'] = $this->getUserEventsCount($user_id);
        }
        return $data;
    }
    public function isOnline(int $user_id): bool {
        $last = get_user_meta($user_id, 'apollo_last_active', true);
        return $last && strtotime($last) > (time() - 900);
    }
    public static function updateLastActive(int $user_id = 0): void {
        if (!$user_id) $user_id = get_current_user_id();
        if ($user_id > 0) {
            update_user_meta($user_id, 'apollo_last_active', current_time('mysql', true));
        }
    }
    private function getUserGroupsCount(int $user_id): int {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_group_members WHERE user_id = %d",
            $user_id
        )) ?: 0;
    }
    private function getUserEventsCount(int $user_id): int {
        return (int) get_user_meta($user_id, '_apollo_events_attended', true) ?: 0;
    }
    public function renderDirectory(array $atts = []): string {
        wp_enqueue_script('apollo-members');
        $atts = shortcode_atts(['per_page' => 20, 'layout' => 'grid'], $atts);
        return '<div id="apollo-members-directory" data-per-page="' . esc_attr($atts['per_page']) . '" data-layout="' . esc_attr($atts['layout']) . '"><div class="apollo-loading"></div></div>';
    }
    public function renderOnlineUsers(array $atts = []): string {
        wp_enqueue_script('apollo-members');
        $atts = shortcode_atts(['limit' => 10, 'avatar_size' => 40], $atts);
        return '<div id="apollo-online-users" data-limit="' . esc_attr($atts['limit']) . '" data-avatar="' . esc_attr($atts['avatar_size']) . '"></div>';
    }
    public function renderRecentlyActive(array $atts = []): string {
        wp_enqueue_script('apollo-members');
        return '<div id="apollo-recently-active" data-limit="' . esc_attr($atts['limit'] ?? 12) . '"></div>';
    }
}
add_action('plugins_loaded', function() { MembersModule::instance()->init(); }, 15);
add_action('wp_footer', function() { if (is_user_logged_in()) MembersModule::updateLastActive(); });
