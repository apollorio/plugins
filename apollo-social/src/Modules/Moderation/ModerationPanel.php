<?php
declare(strict_types=1);
namespace Apollo\Modules\Moderation;
defined('ABSPATH') || exit;
final class ModerationPanel {
    private static ?self $instance = null;
    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    public function init(): void {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
    }
    public function getPendingUsers(int $limit = 50): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, u.user_email, u.user_registered, um.meta_value as membership_requested FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'apollo_membership_status' WHERE um.meta_value = 'pending' ORDER BY u.user_registered DESC LIMIT %d",
            $limit
        ), ARRAY_A);
    }
    public function getSpammerCandidates(int $limit = 50): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, COUNT(*) as report_count FROM {$wpdb->prefix}apollo_reports WHERE status = 'pending' GROUP BY user_id HAVING report_count >= 3 ORDER BY report_count DESC LIMIT %d",
            $limit
        ), ARRAY_A);
    }
    public function markAsSpammer(int $user_id, int $admin_id): bool {
        update_user_meta($user_id, '_apollo_spammer', true);
        update_user_meta($user_id, '_apollo_spammer_marked_at', current_time('mysql', true));
        update_user_meta($user_id, '_apollo_spammer_marked_by', $admin_id);
        $user = new \WP_User($user_id);
        $user->set_role('apollo_spammer');
        do_action('apollo_user_marked_spammer', $user_id, $admin_id);
        return true;
    }
    public function unmarkSpammer(int $user_id): bool {
        delete_user_meta($user_id, '_apollo_spammer');
        delete_user_meta($user_id, '_apollo_spammer_marked_at');
        delete_user_meta($user_id, '_apollo_spammer_marked_by');
        $user = new \WP_User($user_id);
        $user->set_role('apollo_member');
        do_action('apollo_user_unmarked_spammer', $user_id);
        return true;
    }
    public function isSpammer(int $user_id): bool {
        return (bool) get_user_meta($user_id, '_apollo_spammer', true);
    }
    public function reportContent(int $reporter_id, string $content_type, int $content_id, string $reason): int {
        global $wpdb;
        $author_id = $this->getContentAuthor($content_type, $content_id);
        $wpdb->insert($wpdb->prefix . 'apollo_reports', [
            'reporter_id' => $reporter_id,
            'content_type' => $content_type,
            'content_id' => $content_id,
            'user_id' => $author_id,
            'reason' => sanitize_textarea_field($reason),
            'status' => 'pending',
            'created_at' => current_time('mysql', true),
        ]);
        do_action('apollo_content_reported', $content_type, $content_id, $reporter_id, $reason);
        return (int) $wpdb->insert_id;
    }
    public function getPendingReports(int $limit = 50): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apollo_reports WHERE status = 'pending' ORDER BY created_at DESC LIMIT %d",
            $limit
        ), ARRAY_A);
    }
    public function resolveReport(int $report_id, string $action, int $admin_id): bool {
        global $wpdb;
        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apollo_reports WHERE id = %d",
            $report_id
        ), ARRAY_A);
        if (!$report) return false;
        $wpdb->update($wpdb->prefix . 'apollo_reports', [
            'status' => $action,
            'resolved_by' => $admin_id,
            'resolved_at' => current_time('mysql', true),
        ], ['id' => $report_id]);
        if ($action === 'approved') {
            $this->takeAction($report);
        }
        do_action('apollo_report_resolved', $report_id, $action, $admin_id);
        return true;
    }
    private function takeAction(array $report): void {
        $type = $report['content_type'];
        $id = (int) $report['content_id'];
        match($type) {
            'post', 'comment' => wp_update_post(['ID' => $id, 'post_status' => 'trash']),
            'activity' => $this->deleteActivity($id),
            'message' => $this->deleteMessage($id),
            default => null,
        };
    }
    private function deleteActivity(int $id): void {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'apollo_activity', ['id' => $id]);
    }
    private function deleteMessage(int $id): void {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'apollo_chat_messages', ['id' => $id]);
    }
    private function getContentAuthor(string $type, int $id): int {
        return match($type) {
            'post' => (int) get_post_field('post_author', $id),
            'comment' => (int) get_comment($id)?->user_id ?? 0,
            default => 0,
        };
    }
    public function getActivityStats(): array {
        global $wpdb;
        return [
            'pending_users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'apollo_membership_status' AND meta_value = 'pending'"),
            'pending_reports' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}apollo_reports WHERE status = 'pending'"),
            'spammers' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_spammer' AND meta_value = '1'"),
            'verified_users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_verified' AND meta_value = '1'"),
            'total_users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
        ];
    }
    public function registerEndpoints(): void {
        register_rest_route('apollo/v1', '/mod/stats', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getActivityStats(), 200),
            'permission_callback' => fn() => current_user_can('moderate_comments'),
        ]);
        register_rest_route('apollo/v1', '/mod/pending-users', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getPendingUsers(), 200),
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);
        register_rest_route('apollo/v1', '/mod/reports', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getPendingReports(), 200),
            'permission_callback' => fn() => current_user_can('moderate_comments'),
        ]);
        register_rest_route('apollo/v1', '/mod/mark-spammer', [
            'methods' => 'POST',
            'callback' => fn($r) => new \WP_REST_Response(['success' => $this->markAsSpammer((int) $r->get_param('user_id'), get_current_user_id())], 200),
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);
        register_rest_route('apollo/v1', '/mod/report', [
            'methods' => 'POST',
            'callback' => fn($r) => new \WP_REST_Response(['id' => $this->reportContent(get_current_user_id(), $r->get_param('type'), (int) $r->get_param('id'), $r->get_param('reason'))], 200),
            'permission_callback' => 'is_user_logged_in',
        ]);
        register_rest_route('apollo/v1', '/mod/resolve', [
            'methods' => 'POST',
            'callback' => fn($r) => new \WP_REST_Response(['success' => $this->resolveReport((int) $r->get_param('report_id'), $r->get_param('action'), get_current_user_id())], 200),
            'permission_callback' => fn() => current_user_can('moderate_comments'),
        ]);
    }
}
add_action('plugins_loaded', fn() => ModerationPanel::instance()->init(), 15);
