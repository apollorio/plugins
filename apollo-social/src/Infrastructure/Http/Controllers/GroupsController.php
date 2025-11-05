<?php
namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\GroupEntity;
use Apollo\Domain\Groups\Policies\GroupPolicy;

/**
 * Groups REST Controller
 */
class GroupsController extends BaseController
{
    private $groupPolicy;
    
    public function __construct()
    {
        $this->groupPolicy = new GroupPolicy();
    }
    
    /**
     * GET /apollo/v1/groups
     */
    public function index(): void
    {
        $params = $this->sanitizeParams($_GET);
        
        $type = $params['type'] ?? '';
        $season = $params['season'] ?? '';
        $search = $params['search'] ?? '';
        
        $groups = $this->getGroupsData($type, $season, $search);
        
        // Apply view permissions
        $user = $this->getCurrentUser();
        $filtered_groups = [];
        
        foreach ($groups as $group_data) {
            $group = new GroupEntity($group_data);
            
            if ($this->groupPolicy->canView($user, $group)) {
                $filtered_groups[] = $group_data;
            }
        }
        
        $this->success($filtered_groups);
    }
    
    /**
     * POST /apollo/v1/groups
     */
    public function create(): void
    {
        if (!$this->validateNonce()) {
            $this->authError('Invalid nonce');
        }
        
        $user = $this->getCurrentUser();
        if (!$user || !$user->isLoggedIn()) {
            $this->authError();
        }
        
        $params = $this->sanitizeParams($_POST);
        
        // Validate required fields
        if (empty($params['title'])) {
            $this->validationError('Title is required');
        }
        
        if (empty($params['type'])) {
            $this->validationError('Type is required');
        }
        
        // Validate type
        $valid_types = ['comunidade', 'nucleo', 'season'];
        if (!in_array($params['type'], $valid_types)) {
            $this->validationError('Invalid group type');
        }
        
        // For season type, season_slug is required
        if ($params['type'] === 'season' && empty($params['season_slug'])) {
            $this->validationError('Season slug is required for season type groups');
        }
        
        // Create group (mock implementation)
        $group_data = [
            'id' => rand(1000, 9999),
            'title' => $params['title'],
            'slug' => $this->sanitizeTitle($params['title']),
            'type' => $params['type'],
            'season_slug' => $params['season_slug'] ?? null,
            'description' => $params['description'] ?? '',
            'status' => 'active',
            'created_by' => $user->id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->success($group_data, 'Group created successfully');
    }
    
    /**
     * POST /apollo/v1/groups/{id}/join
     */
    public function join(): void
    {
        if (!$this->validateNonce()) {
            $this->authError('Invalid nonce');
        }
        
        $user = $this->getCurrentUser();
        if (!$user || !$user->isLoggedIn()) {
            $this->authError();
        }
        
        $group_id = intval($_GET['id'] ?? 0);
        if (!$group_id) {
            $this->validationError('Invalid group ID');
        }
        
        $group = $this->getGroupById($group_id);
        if (!$group) {
            $this->error('Group not found', 404);
        }
        
        if (!$this->groupPolicy->canJoin($user, $group)) {
            $this->permissionError('You cannot join this group');
        }
        
        // TODO: Implement actual join logic
        // For now, mock success
        $this->success(['joined' => true], 'Successfully joined group');
    }
    
    /**
     * POST /apollo/v1/groups/{id}/invite
     */
    public function invite(): void
    {
        if (!$this->validateNonce()) {
            $this->authError('Invalid nonce');
        }
        
        $user = $this->getCurrentUser();
        if (!$user || !$user->isLoggedIn()) {
            $this->authError();
        }
        
        $group_id = intval($_GET['id'] ?? 0);
        if (!$group_id) {
            $this->validationError('Invalid group ID');
        }
        
        $params = $this->sanitizeParams($_POST);
        if (empty($params['user_id'])) {
            $this->validationError('User ID is required');
        }
        
        $group = $this->getGroupById($group_id);
        if (!$group) {
            $this->error('Group not found', 404);
        }
        
        if (!$this->groupPolicy->canInvite($user, $group)) {
            $this->permissionError('You cannot send invites for this group');
        }
        
        // TODO: Implement actual invite logic
        // For now, mock success
        $this->success(['invited' => true], 'Invitation sent successfully');
    }
    
    /**
     * POST /apollo/v1/groups/{id}/approve-invite
     */
    public function approveInvite(): void
    {
        if (!$this->validateNonce()) {
            $this->authError('Invalid nonce');
        }
        
        $user = $this->getCurrentUser();
        if (!$user || !$user->isLoggedIn()) {
            $this->authError();
        }
        
        $params = $this->sanitizeParams($_POST);
        if (empty($params['invite_id'])) {
            $this->validationError('Invite ID is required');
        }
        
        // TODO: Implement invite approval logic
        // For now, placeholder
        $this->success(['approved' => true], 'Invite approved successfully');
    }
    
    /**
     * Sanitize title for slug
     */
    private function sanitizeTitle(string $title): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    }
    
    /**
     * Get groups data (mock implementation)
     */
    private function getGroupsData(string $type = '', string $season = '', string $search = ''): array
    {
        $groups = [
            [
                'id' => 1,
                'title' => 'Desenvolvedores PHP',
                'slug' => 'desenvolvedores-php',
                'type' => 'comunidade',
                'season_slug' => null,
                'description' => 'Comunidade de desenvolvedores PHP',
                'members_count' => 150
            ],
            [
                'id' => 2,
                'title' => 'Core Team',
                'slug' => 'core-team',
                'type' => 'nucleo',
                'season_slug' => null,
                'description' => 'Núcleo principal de desenvolvimento',
                'members_count' => 12
            ],
            [
                'id' => 3,
                'title' => 'Verão 2025',
                'slug' => 'verao-2025',
                'type' => 'season',
                'season_slug' => 'verao-2025',
                'description' => 'Season de verão 2025',
                'members_count' => 89
            ]
        ];
        
        // Apply filters
        if ($type) {
            $groups = array_filter($groups, function($group) use ($type) {
                return $group['type'] === $type;
            });
        }
        
        if ($season) {
            $groups = array_filter($groups, function($group) use ($season) {
                return $group['season_slug'] === $season;
            });
        }
        
        if ($search) {
            $groups = array_filter($groups, function($group) use ($search) {
                return stripos($group['title'], $search) !== false ||
                       stripos($group['description'], $search) !== false;
            });
        }
        
        return array_values($groups);
    }
    
    /**
     * Get group by ID (mock implementation)
     */
    private function getGroupById(int $id): ?GroupEntity
    {
        $groups = $this->getGroupsData();
        
        foreach ($groups as $group_data) {
            if ($group_data['id'] === $id) {
                return new GroupEntity($group_data);
            }
        }
        
        return null;
    }
}