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
        if (!\Apollo\Infrastructure\FeatureFlags::isEnabled('groups_api')) {
            return;
        }
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

    /**
     * Handle Comuna creation with business rule validation
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    private function handleCreateComuna(\WP_REST_Request $request): \WP_REST_Response {
        $data = \Apollo\Modules\Groups\GroupsBusinessRules::sanitizeGroupData(
            array_merge($request->get_json_params(), ['group_type' => self::TYPE_COMUNA])
        );

        // Validate business rules
        $can_create = \Apollo\Modules\Groups\GroupsBusinessRules::canCreate(self::TYPE_COMUNA, get_current_user_id());
        if (\is_wp_error($can_create)) {
            return new \WP_REST_Response($can_create->get_error_data(), 403);
        }

        $group_id = $this->create($data);
        if (!$group_id) {
            return new \WP_REST_Response(['error' => 'Failed to create group'], 500);
        }

        return new \WP_REST_Response(['id' => $group_id, 'type' => self::TYPE_COMUNA], 201);
    }

    /**
     * Handle Nucleo creation with business rule validation
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    private function handleCreateNucleo(\WP_REST_Request $request): \WP_REST_Response {
        $data = \Apollo\Modules\Groups\GroupsBusinessRules::sanitizeGroupData(
            array_merge($request->get_json_params(), ['group_type' => self::TYPE_NUCLEO])
        );

        // Validate business rules
        $can_create = \Apollo\Modules\Groups\GroupsBusinessRules::canCreate(self::TYPE_NUCLEO, get_current_user_id());
        if (\is_wp_error($can_create)) {
            return new \WP_REST_Response($can_create->get_error_data(), 403);
        }

        $group_id = $this->create($data);
        if (!$group_id) {
            return new \WP_REST_Response(['error' => 'Failed to create nucleo'], 500);
        }

        return new \WP_REST_Response(['id' => $group_id, 'type' => self::TYPE_NUCLEO], 201);
    }

    /**
     * Handle join request with rate limiting and business rules
     *
     * @param \WP_REST_Request $request REST request
     * @param int             $group_id Group ID
     * @return \WP_REST_Response
     */
    private function handleJoin(\WP_REST_Request $request, int $group_id): \WP_REST_Response {
        $user_id = get_current_user_id();
        $security = '\Apollo\Api\RestSecurity';

        // Rate limit: max 10 joins per hour per user
        $rate_check = $security::rateLimitByUserGroup($user_id, 'join', $group_id, 10);
        if (\is_wp_error($rate_check)) {
            return new \WP_REST_Response($rate_check->get_error_data(), 429);
        }

        $success = $this->addMember($group_id, $user_id);
        if (!$success) {
            return new \WP_REST_Response(['error' => 'Failed to join group'], 500);
        }

        return new \WP_REST_Response(['success' => true], 200);
    }

    /**
     * Handle Nucleo join with approval workflow
     *
     * @param \WP_REST_Request $request REST request
     * @param int             $group_id Group ID
     * @return \WP_REST_Response
     */
    private function handleJoinNucleo(\WP_REST_Request $request, int $group_id): \WP_REST_Response {
        global $wpdb;
        $user_id = get_current_user_id();
        $security = '\Apollo\Api\RestSecurity';

        // Rate limit
        $rate_check = $security::rateLimitByUserGroup($user_id, 'join_nucleo', $group_id, 5);
        if (\is_wp_error($rate_check)) {
            return new \WP_REST_Response($rate_check->get_error_data(), 429);
        }

        $group = $this->get($group_id);
        if (!$group) {
            return new \WP_REST_Response(['error' => 'Group not found'], 404);
        }

        // Nucleos require approval
        if (\Apollo\Modules\Groups\GroupsBusinessRules::joinRequiresApproval($group)) {
            // Add as pending member
            $result = $wpdb->insert(
                $wpdb->prefix . 'apollo_group_members',
                ['group_id' => $group_id, 'user_id' => $user_id, 'role' => 'pending', 'date_joined' => current_time('mysql', true)],
                ['%d', '%d', '%s', '%s']
            );

            if (!$result) {
                return new \WP_REST_Response(['error' => 'Failed to request join'], 500);
            }

            do_action('apollo_nucleo_join_requested', $group_id, $user_id);
            return new \WP_REST_Response(['success' => true, 'status' => 'pending_approval'], 202);
        }

        // Direct join
        $success = $this->addMember($group_id, $user_id);
        if (!$success) {
            return new \WP_REST_Response(['error' => 'Failed to join'], 500);
        }

        return new \WP_REST_Response(['success' => true, 'status' => 'joined'], 200);
    }

    /**
     * Handle leave request
     *
     * @param \WP_REST_Request $request REST request
     * @param int             $group_id Group ID
     * @return \WP_REST_Response
     */
    private function handleLeave(\WP_REST_Request $request, int $group_id): \WP_REST_Response {
        $user_id = get_current_user_id();
        $success = $this->removeMember($group_id, $user_id);

        if (!$success) {
            return new \WP_REST_Response(['error' => 'Failed to leave group'], 500);
        }

        return new \WP_REST_Response(['success' => true], 200);
    }

    /**
     * Handle invite request with validation and rate limiting
     *
     * @param \WP_REST_Request $request REST request
     * @param int             $group_id Group ID
     * @return \WP_REST_Response
     */
    private function handleInvite(\WP_REST_Request $request, int $group_id): \WP_REST_Response {
        $user_id = get_current_user_id();
        $security = '\Apollo\Api\RestSecurity';

        // Validate invite data
        $data = $security::validateInviteData(['group_id' => $group_id] + $request->get_json_params());
        if (\is_wp_error($data)) {
            return new \WP_REST_Response($data->get_error_data(), 400);
        }

        // Check if user can invite
        $can_invite = \Apollo\Modules\Groups\GroupsBusinessRules::canInvite($group_id, $user_id);
        if (\is_wp_error($can_invite)) {
            return new \WP_REST_Response($can_invite->get_error_data(), 403);
        }

        // Rate limit: max 20 invites per hour
        $rate_check = $security::rateLimitByUserGroup($user_id, 'invite', $group_id, 20);
        if (\is_wp_error($rate_check)) {
            return new \WP_REST_Response($rate_check->get_error_data(), 429);
        }

        $success = $this->invite($group_id, $user_id, $data['user_id']);
        if (!$success) {
            return new \WP_REST_Response(['error' => 'Failed to send invite'], 500);
        }

        return new \WP_REST_Response(['success' => true], 200);
    }

    /**
     * Handle Nucleo invite (stricter permissions)
     *
     * @param \WP_REST_Request $request REST request
     * @param int             $group_id Group ID
     * @return \WP_REST_Response
     */
    private function handleInviteNucleo(\WP_REST_Request $request, int $group_id): \WP_REST_Response {
        return $this->handleInvite($request, $group_id);  // Same logic for now
    }

    public function registerEndpoints(): void {
        // Use security handler for proper nonce/cap verification
        $security = '\Apollo\Api\RestSecurity';

        // Comunas (public communities)
        register_rest_route('apollo/v1', '/comunas', [
            'methods' => 'GET',
            'callback' => fn($r) => new \WP_REST_Response($this->getDirectory(self::TYPE_COMUNA, (int)($r->get_param('limit') ?: 20), (int)($r->get_param('offset') ?: 0), $r->get_param('search') ?: ''), 200),
            'permission_callback' => '__return_true'  // Read-only, public list OK
        ]);
        register_rest_route('apollo/v1', '/comunas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => fn($r) => new \WP_REST_Response($this->get((int) $r->get_param('id')), 200),
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('apollo/v1', '/comunas/(?P<id>\d+)/members', [
            'methods' => 'GET',
            'callback' => fn($r) => new \WP_REST_Response($this->getMembers((int) $r->get_param('id')), 200),
            'permission_callback' => fn($r) => $security::canViewMembers($r, (int) $r->get_param('id'))
        ]);
        register_rest_route('apollo/v1', '/comunas/create', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleCreateComuna($r),
            'permission_callback' => fn($r) => $security::verify($r, 'read')  // Subscribers can create
        ]);
        register_rest_route('apollo/v1', '/comunas/(?P<id>\d+)/join', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleJoin($r, (int) $r->get_param('id')),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/comunas/(?P<id>\d+)/leave', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleLeave($r, (int) $r->get_param('id')),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/comunas/my', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getUserGroups(get_current_user_id(), self::TYPE_COMUNA), 200),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/comunas/(?P<id>\d+)/invite', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleInvite($r, (int) $r->get_param('id')),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);

        // Nucleos (private/industry teams)
        register_rest_route('apollo/v1', '/nucleos', [
            'methods' => 'GET',
            'callback' => fn($r) => new \WP_REST_Response($this->getDirectory(self::TYPE_NUCLEO, (int)($r->get_param('limit') ?: 20), (int)($r->get_param('offset') ?: 0), $r->get_param('search') ?: ''), 200),
            'permission_callback' => fn($r) => $security::verify($r)  // Auth required for nucleo listing
        ]);
        register_rest_route('apollo/v1', '/nucleos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => fn($r) => new \WP_REST_Response($this->get((int) $r->get_param('id')), 200),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/nucleos/(?P<id>\d+)/members', [
            'methods' => 'GET',
            'callback' => fn($r) => new \WP_REST_Response($this->getMembers((int) $r->get_param('id')), 200),
            'permission_callback' => fn($r) => $security::canViewMembers($r, (int) $r->get_param('id'))
        ]);
        register_rest_route('apollo/v1', '/nucleos/create', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleCreateNucleo($r),
            'permission_callback' => fn($r) => $security::verify($r, 'apollo_create_nucleo')  // Specific cap
        ]);
        register_rest_route('apollo/v1', '/nucleos/(?P<id>\d+)/join', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleJoinNucleo($r, (int) $r->get_param('id')),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/nucleos/(?P<id>\d+)/leave', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleLeave($r, (int) $r->get_param('id')),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/nucleos/my', [
            'methods' => 'GET',
            'callback' => fn() => new \WP_REST_Response($this->getUserGroups(get_current_user_id(), self::TYPE_NUCLEO), 200),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);
        register_rest_route('apollo/v1', '/nucleos/(?P<id>\d+)/invite', [
            'methods' => 'POST',
            'callback' => fn($r) => $this->handleInviteNucleo($r, (int) $r->get_param('id')),
            'permission_callback' => fn($r) => $security::verify($r)
        ]);

        // Legacy /groups proxy (deprecated)
        if (\Apollo\Infrastructure\FeatureFlags::isEnabled('groups_api_legacy')) {
            $proxy_callback = function($r) {
                $response = new \WP_REST_Response($this->getDirectory(self::TYPE_COMUNA, (int)($r->get_param('limit') ?: 20), (int)($r->get_param('offset') ?: 0), $r->get_param('search') ?: ''), 200);
                $response->header('Deprecation', 'true');
                $response->header('Sunset', date('c', strtotime('+6 months')));
                $response->header('Link', '</apollo/v1/comunas>; rel="successor-version"');
                return $response;
            };
            register_rest_route('apollo/v1', '/groups', [
                'methods' => 'GET',
                'callback' => $proxy_callback,
                'permission_callback' => '__return_true'
            ]);
        }
    }
    public static function getRoles(): array { return self::$roles; }
}
add_action('plugins_loaded', fn() => GroupsModule::instance()->init(), 15);
