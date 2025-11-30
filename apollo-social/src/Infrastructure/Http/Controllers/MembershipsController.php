<?php
namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\UnionEntity;
use Apollo\Domain\Memberships\Policies\MembershipPolicy;

/**
 * Memberships REST Controller
 */
class MembershipsController extends BaseController
{
    private $membershipPolicy;
    
    public function __construct()
    {
        $this->membershipPolicy = new MembershipPolicy();
    }
    
    /**
     * GET /apollo/v1/unions
     */
    public function index(): void
    {
        $unions = $this->getUnionsData();
        
        // Apply view permissions
        $user = $this->getCurrentUser();
        $filtered_unions = [];
        
        foreach ($unions as $union_data) {
            $union = new UnionEntity($union_data);
            
            if ($this->membershipPolicy->canViewUnion($union, $user)) {
                $filtered_unions[] = $union_data;
            }
        }
        
        $this->success($filtered_unions);
    }
    
    /**
     * POST /apollo/v1/unions/{id}/toggle-badges
     */
    public function toggleBadges(): void
    {
        if (!$this->validateNonce()) {
            $this->authError('Invalid nonce');
        }
        
        $user = $this->getCurrentUser();
        if (!$user || !$user->isLoggedIn()) {
            $this->authError();
        }
        
        $union_id = intval($_GET['id'] ?? 0);
        if (!$union_id) {
            $this->validationError('Invalid union ID');
        }
        
        $params = $this->sanitizeParams($_POST);
        $badges_on = isset($params['on']) ? (bool) $params['on'] : true;
        
        $union = $this->getUnionById($union_id);
        if (!$union) {
            $this->error('Union not found', 404);
        }
        
        if (!$this->membershipPolicy->canToggleBadges($user, $union)) {
            $this->permissionError('You cannot toggle badges for this union');
        }
        
        // TODO: Implement actual toggle logic
        // For now, mock success
        $this->success([
            'union_id' => $union_id,
            'badges_enabled' => $badges_on
        ], 'Badges toggle updated successfully');
    }
    
    /**
     * Get unions data (mock implementation)
     */
    private function getUnionsData(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'União dos Desenvolvedores',
                'slug' => 'desenvolvedores',
                'description' => 'União para profissionais de desenvolvimento',
                'badges_toggle' => true,
                'members_count' => 150,
                'managers' => [1, 2]
            ],
            [
                'id' => 2,
                'title' => 'União dos Designers',
                'slug' => 'designers',
                'description' => 'União para profissionais de design',
                'badges_toggle' => false,
                'members_count' => 89,
                'managers' => [1, 3]
            ]
        ];
    }
    
    /**
     * Get union by ID (mock implementation)
     */
    private function getUnionById(int $id): ?UnionEntity
    {
        $unions = $this->getUnionsData();
        
        foreach ($unions as $union_data) {
            if ($union_data['id'] === $id) {
                return new UnionEntity($union_data);
            }
        }
        
        return null;
    }
}