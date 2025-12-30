<?php
declare(strict_types=1);
namespace Apollo\Modules\Notices;
defined('ABSPATH') || exit;
final class SitewideNotices {
    private static ?self $instance = null;
    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    public function init(): void {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_action('wp_footer', [$this, 'renderNotices']);
        add_shortcode('apollo_notices', [$this, 'shortcodeNotices']);
    }
    public function create(array $data): int {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'apollo_notices', [
            'title' => sanitize_text_field($data['title'] ?? ''),
            'message' => wp_kses_post($data['message'] ?? ''),
            'type' => sanitize_key($data['type'] ?? 'info'),
            'target' => sanitize_key($data['target'] ?? 'all'),
            'target_value' => sanitize_text_field($data['target_value'] ?? ''),
            'priority' => (int) ($data['priority'] ?? 10),
            'dismissible' => (int) ($data['dismissible'] ?? 1),
            'start_date' => $data['start_date'] ?? current_time('mysql'),
            'end_date' => $data['end_date'] ?? null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql', true),
        ]);
        return (int) $wpdb->insert_id;
    }
    public function update(int $id, array $data): bool {
        global $wpdb;
        return (bool) $wpdb->update($wpdb->prefix . 'apollo_notices', [
            'title' => sanitize_text_field($data['title'] ?? ''),
            'message' => wp_kses_post($data['message'] ?? ''),
            'type' => sanitize_key($data['type'] ?? 'info'),
            'target' => sanitize_key($data['target'] ?? 'all'),
            'target_value' => sanitize_text_field($data['target_value'] ?? ''),
            'priority' => (int) ($data['priority'] ?? 10),
            'dismissible' => (int) ($data['dismissible'] ?? 1),
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ], ['id' => $id]);
    }
    public function delete(int $id): bool {
        global $wpdb;
        return (bool) $wpdb->delete($wpdb->prefix . 'apollo_notices', ['id' => $id]);
    }
    public function getActiveNotices(int $user_id = 0): array {
        global $wpdb;
        if (!$user_id) $user_id = get_current_user_id();
        $now = current_time('mysql');
        $notices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apollo_notices WHERE (start_date IS NULL OR start_date <= %s) AND (end_date IS NULL OR end_date >= %s) ORDER BY priority DESC, created_at DESC",
            $now, $now
        ), ARRAY_A);
        $dismissed = $user_id ? (get_user_meta($user_id, '_apollo_dismissed_notices', true) ?: []) : [];
        $result = [];
        foreach ($notices as $notice) {
            if (in_array((int) $notice['id'], $dismissed, true)) continue;
            if (!$this->matchesTarget($notice, $user_id)) continue;
            $result[] = $this->formatNotice($notice);
        }
        return $result;
    }
    private function matchesTarget(array $notice, int $user_id): bool {
        $target = $notice['target'];
        $value = $notice['target_value'];
        if ($target === 'all') return true;
        if ($target === 'logged_in') return $user_id > 0;
        if ($target === 'logged_out') return $user_id === 0;
        if (!$user_id) return false;
        $user = get_userdata($user_id);
        if (!$user) return false;
        return match($target) {
            'role' => in_array($value, $user->roles, true),
            'membership' => get_user_meta($user_id, 'apollo_membership_status', true) === $value,
            'verified' => (bool) get_user_meta($user_id, '_apollo_verified', true),
            'unverified' => !get_user_meta($user_id, '_apollo_verified', true),
            'rank' => get_user_meta($user_id, '_apollo_rank', true) === $value,
            'group' => $this->userInGroup($user_id, (int) $value),
            'identity' => in_array($value, get_user_meta($user_id, 'apollo_cultura_identities', true) ?: [], true),
            default => true,
        };
    }
    private function userInGroup(int $user_id, int $group_id): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}apollo_group_members WHERE user_id = %d AND group_id = %d",
            $user_id, $group_id
        ));
    }
    private function formatNotice(array $notice): array {
        return [
            'id' => (int) $notice['id'],
            'title' => $notice['title'],
            'message' => $notice['message'],
            'type' => $notice['type'],
            'dismissible' => (bool) $notice['dismissible'],
            'priority' => (int) $notice['priority'],
        ];
    }
    public function dismiss(int $user_id, int $notice_id): bool {
        $dismissed = get_user_meta($user_id, '_apollo_dismissed_notices', true) ?: [];
        if (in_array($notice_id, $dismissed, true)) return true;
        $dismissed[] = $notice_id;
        return (bool) update_user_meta($user_id, '_apollo_dismissed_notices', $dismissed);
    }
    public function renderNotices(): void {
        if (is_admin()) return;
        $notices = $this->getActiveNotices();
        if (empty($notices)) return;
        echo '<div id="apollo-sitewide-notices" data-notices="' . esc_attr(wp_json_encode($notices)) . '"></div>';
    }
    public function shortcodeNotices(array $atts = []): string {
        $notices = $this->getActiveNotices();
        if (empty($notices)) return '';
        $html = '<div class="apollo-notices">';
        foreach ($notices as $notice) {
            $html .= sprintf('<div class="apollo-notice apollo-notice-%s" data-id="%d">%s<strong>%s</strong> %s%s</div>',
                esc_attr($notice['type']),
                $notice['id'],
                $notice['dismissible'] ? '<button class="apollo-notice-dismiss" onclick="apolloDismissNotice(' . $notice['id'] . ')">×</button>' : '',
                esc_html($notice['title']),
                wp_kses_post($notice['message']),
                ''
            );
        }
        $html .= '</div>';
        return $html;
    }
    public function registerEndpoints(): void {
        register_rest_route('apollo/v1', '/notices', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getActiveNotices(), 200),
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('apollo/v1', '/notices/dismiss', [
            'methods' => 'POST',
            'callback' => fn($r) => new \WP_REST_Response(['success' => $this->dismiss(get_current_user_id(), (int) $r->get_param('notice_id'))], 200),
            'permission_callback' => 'is_user_logged_in',
        ]);
        register_rest_route('apollo/v1', '/notices/admin', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getAllNotices(), 200),
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);
        register_rest_route('apollo/v1', '/notices/admin', [
            'methods' => 'POST',
            'callback' => fn($r) => new \WP_REST_Response(['id' => $this->create($r->get_json_params())], 200),
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);
    }
    private function getAllNotices(): array {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}apollo_notices ORDER BY created_at DESC", ARRAY_A);
    }
}
add_action('plugins_loaded', fn() => SitewideNotices::instance()->init(), 15);
