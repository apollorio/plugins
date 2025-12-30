<?php
declare(strict_types=1);
namespace Apollo\Modules\Groups;
defined('ABSPATH') || exit;
final class GroupsModule {
    public const TYPE_COMUNA = 'comuna';
    public const TYPE_NUCLEO = 'nucleo';
    public const TYPE_SEASON = 'season';
    private static ?self $instance = null;
    private static array $roles = [
        'owner' => ['label' => 'Dono', 'cap' => ['manage', 'invite', 'moderate', 'post', 'view']],
        'admin' => ['label' => 'Admin', 'cap' => ['invite', 'moderate', 'post', 'view']],
        'moderator' => ['label' => 'Moderador', 'cap' => ['moderate', 'post', 'view']],
        'member' => ['label' => 'Membro', 'cap' => ['post', 'view']],
        'pending' => ['label' => 'Pendente', 'cap' => []],
    ];
    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    public function init(): void {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_shortcode('apollo_groups_directory', [$this, 'renderDirectory']);
        add_shortcode('apollo_my_groups', [$this, 'renderMyGroups']);
    }
    public function create(array $data): int|false {
        global $wpdb;
        $type = sanitize_key($data['type'] ?? self::TYPE_COMUNA);
        if (!in_array($type, [self::TYPE_COMUNA, self::TYPE_NUCLEO, self::TYPE_SEASON], true)) return false;
        $visibility = $type === self::TYPE_NUCLEO ? 'private' : ($data['visibility'] ?? 'public');
        $result = $wpdb->insert($wpdb->prefix . 'apollo_groups', [
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['name']),
            'description' => wp_kses_post($data['description'] ?? ''),
            'type' => $type,
            'visibility' => $visibility,
            'owner_id' => (int) ($data['owner_id'] ?? get_current_user_id()),
            'avatar' => esc_url_raw($data['avatar'] ?? ''),
            'cover' => esc_url_raw($data['cover'] ?? ''),
            'settings' => wp_json_encode($data['settings'] ?? []),
            'created_at' => current_time('mysql', true),
        ]);
        if (!$result) return false;
        $group_id = (int) $wpdb->insert_id;
        $this->addMember($group_id, (int) ($data['owner_id'] ?? get_current_user_id()), 'owner');
        do_action('apollo_group_created', $group_id, get_current_user_id());
        return $group_id;
    }
    public function get(int $group_id): ?array {
        global $wpdb;
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apollo_groups WHERE id = %d",
            $group_id
        ), ARRAY_A);
        if (!$group) return null;
        $group['settings'] = json_decode($group['settings'] ?: '{}', true);
        $group['members_count'] = $this->getMembersCount($group_id);
        return $group;
    }
    public function update(int $group_id, array $data): bool {
        global $wpdb;
        $update = [];
        if (isset($data['name'])) $update['name'] = sanitize_text_field($data['name']);
        if (isset($data['description'])) $update['description'] = wp_kses_post($data['description']);
        if (isset($data['visibility'])) $update['visibility'] = sanitize_key($data['visibility']);
        if (isset($data['avatar'])) $update['avatar'] = esc_url_raw($data['avatar']);
        if (isset($data['cover'])) $update['cover'] = esc_url_raw($data['cover']);
        if (isset($data['settings'])) $update['settings'] = wp_json_encode($data['settings']);
        if (empty($update)) return false;
        return (bool) $wpdb->update($wpdb->prefix . 'apollo_groups', $update, ['id' => $group_id]);
    }
    public function delete(int $group_id): bool {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'apollo_group_members', ['group_id' => $group_id]);
        $wpdb->delete($wpdb->prefix . 'apollo_activity', ['group_id' => $group_id]);
        return (bool) $wpdb->delete($wpdb->prefix . 'apollo_groups', ['id' => $group_id]);
    }
    public function addMember(int $group_id, int $user_id, string $role = 'member'): bool {
        global $wpdb;
        if ($this->isMember($group_id, $user_id)) return false;
        $group = $this->get($group_id);
        if (!$group) return false;
        $status = ($group['type'] === self::TYPE_NUCLEO || $group['visibility'] === 'private') ? 'pending' : 'active';
        if ($role === 'owner') $status = 'active';
        $result = $wpdb->insert($wpdb->prefix . 'apollo_group_members', [
            'group_id' => $group_id,
            'user_id' => $user_id,
            'role' => $role,
            'status' => $status,
            'joined_at' => current_time('mysql', true),
        ]);
        if ($result && $status === 'active') {
            do_action('apollo_group_joined', $group_id, $user_id);
        }
        return (bool) $result;
    }
    public function removeMember(int $group_id, int $user_id): bool {
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix . 'apollo_group_members', ['group_id' => $group_id, 'user_id' => $user_id]);
        if ($result) do_action('apollo_group_left', $group_id, $user_id);
        return (bool) $result;
    }
    public function updateRole(int $group_id, int $user_id, string $role): bool {
        global $wpdb;
        if (!isset(self::$roles[$role])) return false;
        return (bool) $wpdb->update($wpdb->prefix . 'apollo_group_members', ['role' => $role], ['group_id' => $group_id, 'user_id' => $user_id]);
    }
    public function approveMember(int $group_id, int $user_id): bool {
        global $wpdb;
        $result = $wpdb->update($wpdb->prefix . 'apollo_group_members', ['status' => 'active', 'role' => 'member'], ['group_id' => $group_id, 'user_id' => $user_id, 'status' => 'pending']);
        if ($result) do_action('apollo_group_joined', $group_id, $user_id);
        return (bool) $result;
    }
    public function isMember(int $group_id, int $user_id): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}apollo_group_members WHERE group_id = %d AND user_id = %d AND status = 'active'",
            $group_id, $user_id
        ));
    }
    public function getMemberRole(int $group_id, int $user_id): ?string {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM {$wpdb->prefix}apollo_group_members WHERE group_id = %d AND user_id = %d AND status = 'active'",
            $group_id, $user_id
        ));
    }
    public function canUser(int $group_id, int $user_id, string $capability): bool {
        $role = $this->getMemberRole($group_id, $user_id);
        if (!$role || !isset(self::$roles[$role])) return false;
        return in_array($capability, self::$roles[$role]['cap'], true);
    }
    public function getMembers(int $group_id, int $limit = 50): array {
        global $wpdb;
        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT gm.*, u.display_name, u.user_email FROM {$wpdb->prefix}apollo_group_members gm INNER JOIN {$wpdb->users} u ON gm.user_id = u.ID WHERE gm.group_id = %d AND gm.status = 'active' ORDER BY gm.joined_at DESC LIMIT %d",
            $group_id, $limit
        ), ARRAY_A);
        foreach ($members as &$m) {
            $m['avatar'] = get_avatar_url((int) $m['user_id'], ['size' => 80]);
            $m['role_label'] = self::$roles[$m['role']]['label'] ?? $m['role'];
        }
        return $members;
    }
    public function getMembersCount(int $group_id): int {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_group_members WHERE group_id = %d AND status = 'active'",
            $group_id
        ));
    }
    public function getPendingMembers(int $group_id): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT gm.*, u.display_name FROM {$wpdb->prefix}apollo_group_members gm INNER JOIN {$wpdb->users} u ON gm.user_id = u.ID WHERE gm.group_id = %d AND gm.status = 'pending' ORDER BY gm.joined_at DESC",
            $group_id
        ), ARRAY_A);
    }
    public function getUserGroups(int $user_id, ?string $type = null): array {
        global $wpdb;
        $sql = "SELECT g.*, gm.role, gm.joined_at as member_since FROM {$wpdb->prefix}apollo_groups g INNER JOIN {$wpdb->prefix}apollo_group_members gm ON g.id = gm.group_id WHERE gm.user_id = %d AND gm.status = 'active'";
        $params = [$user_id];
        if ($type) {
            $sql .= " AND g.type = %s";
            $params[] = $type;
        }
        $sql .= " ORDER BY gm.joined_at DESC";
        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }
    public function getDirectory(string $type = '', int $limit = 20, int $offset = 0, string $search = ''): array {
        global $wpdb;
        $sql = "SELECT g.*, (SELECT COUNT(*) FROM {$wpdb->prefix}apollo_group_members gm WHERE gm.group_id = g.id AND gm.status = 'active') as members_count FROM {$wpdb->prefix}apollo_groups g WHERE g.visibility = 'public'";
        $params = [];
        if ($type) {
            $sql .= " AND g.type = %s";
            $params[] = $type;
        }
        if ($search) {
            $sql .= " AND (g.name LIKE %s OR g.description LIKE %s)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        $sql .= " ORDER BY members_count DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }
    public function invite(int $group_id, int $inviter_id, int $invitee_id): bool {
        if (!$this->canUser($group_id, $inviter_id, 'invite')) return false;
        if ($this->isMember($group_id, $invitee_id)) return false;
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'apollo_group_invites', [
            'group_id' => $group_id,
            'inviter_id' => $inviter_id,
            'invitee_id' => $invitee_id,
            'status' => 'pending',
            'created_at' => current_time('mysql', true),
        ]);
        do_action('apollo_group_invite_sent', $group_id, $inviter_id, $invitee_id);
        return true;
    }
    public function acceptInvite(int $group_id, int $user_id): bool {
        global $wpdb;
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apollo_group_invites WHERE group_id = %d AND invitee_id = %d AND status = 'pending'",
            $group_id, $user_id
        ));
        if (!$invite) return false;
        $wpdb->update($wpdb->prefix . 'apollo_group_invites', ['status' => 'accepted'], ['id' => $invite->id]);
        $this->addMember($group_id, $user_id, 'member');
        $wpdb->update($wpdb->prefix . 'apollo_group_members', ['status' => 'active'], ['group_id' => $group_id, 'user_id' => $user_id]);
        do_action('apollo_group_invite_accepted', $group_id, $user_id);
        return true;
    }
    public function renderDirectory(array $atts = []): string {
        $atts = shortcode_atts(['type' => '', 'limit' => 20], $atts);
        $groups = $this->getDirectory($atts['type'], (int) $atts['limit']);
        return '<div id="apollo-groups-directory" data-groups="' . esc_attr(wp_json_encode($groups)) . '"></div>';
    }
    public function renderMyGroups(array $atts = []): string {
        if (!is_user_logged_in()) return '<p>Faça login para ver seus grupos.</p>';
        $groups = $this->getUserGroups(get_current_user_id());
        return '<div id="apollo-my-groups" data-groups="' . esc_attr(wp_json_encode($groups)) . '"></div>';
    }
    public function registerEndpoints(): void {
        register_rest_route('apollo/v1', '/groups', ['methods' => 'GET', 'callback' => fn($r) => new \WP_REST_Response($this->getDirectory($r->get_param('type') ?: '', (int)($r->get_param('limit') ?: 20), (int)($r->get_param('offset') ?: 0), $r->get_param('search') ?: ''), 200), 'permission_callback' => '__return_true']);
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)', ['methods' => 'GET', 'callback' => fn($r) => new \WP_REST_Response($this->get((int) $r->get_param('id')), 200), 'permission_callback' => '__return_true']);
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/members', ['methods' => 'GET', 'callback' => fn($r) => new \WP_REST_Response($this->getMembers((int) $r->get_param('id')), 200), 'permission_callback' => '__return_true']);
        register_rest_route('apollo/v1', '/groups/create', ['methods' => 'POST', 'callback' => fn($r) => new \WP_REST_Response(['id' => $this->create($r->get_json_params())], 200), 'permission_callback' => 'is_user_logged_in']);
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/join', ['methods' => 'POST', 'callback' => fn($r) => new \WP_REST_Response(['success' => $this->addMember((int) $r->get_param('id'), get_current_user_id())], 200), 'permission_callback' => 'is_user_logged_in']);
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/leave', ['methods' => 'POST', 'callback' => fn($r) => new \WP_REST_Response(['success' => $this->removeMember((int) $r->get_param('id'), get_current_user_id())], 200), 'permission_callback' => 'is_user_logged_in']);
        register_rest_route('apollo/v1', '/groups/my', ['methods' => 'GET', 'callback' => fn() => new \WP_REST_Response($this->getUserGroups(get_current_user_id()), 200), 'permission_callback' => 'is_user_logged_in']);
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/invite', ['methods' => 'POST', 'callback' => fn($r) => new \WP_REST_Response(['success' => $this->invite((int) $r->get_param('id'), get_current_user_id(), (int) $r->get_param('user_id'))], 200), 'permission_callback' => 'is_user_logged_in']);
    }
    public static function getRoles(): array { return self::$roles; }
}
add_action('plugins_loaded', fn() => GroupsModule::instance()->init(), 15);
