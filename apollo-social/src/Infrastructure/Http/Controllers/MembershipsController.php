<?php

namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\UnionEntity;
use Apollo\Domain\Memberships\Policies\MembershipPolicy;

/**
 * Memberships REST Controller (Membro)
 *
 * Routes: /apollo/v1/membro (primary), /apollo/v1/uniao (legacy alias)
 */
class MembershipsController extends BaseController
{
    private $membershipPolicy;

    public function __construct()
    {
        $this->membershipPolicy = new MembershipPolicy();
    }

    /**
     * GET /apollo/v1/membro - Listar membros (Portuguese naming)
     * GET /apollo/v1/uniao - Legacy alias
     */
    public function index(): void
    {
        $membros = $this->getMembrosData();

        // Apply view permissions.
        $user             = $this->getCurrentUser();
        $filtered_membros = [];

        foreach ($membros as $membro_data) {
            $membro = new UnionEntity($membro_data);

            if ($this->membershipPolicy->canViewUnion($membro, $user)) {
                $filtered_membros[] = $membro_data;
            }
        }

        $this->success($filtered_membros);
    }

    /**
     * POST /apollo/v1/membro/{id}/toggle-badges - Alternar emblemas
     * POST /apollo/v1/uniao/{id}/toggle-badges - Legacy alias
     */
    public function toggleBadges(): void
    {
        if (! $this->validateNonce()) {
            $this->authError(__('Nonce inválido', 'apollo-social'));
        }

        $user = $this->getCurrentUser();
        if (! $user || ! $user->isLoggedIn()) {
            $this->authError();
        }

        $membro_id = intval($_GET['id'] ?? 0);
        if (! $membro_id) {
            $this->validationError(__('ID de membro inválido', 'apollo-social'));
        }

        $params    = $this->sanitizeParams($_POST);
        $badges_on = isset($params['on']) ? (bool) $params['on'] : true;

        $membro = $this->getMembroById($membro_id);
        if (! $membro) {
            $this->error(__('Membro não encontrado', 'apollo-social'), 404);
        }

        if (! $this->membershipPolicy->canToggleBadges($user, $membro)) {
            $this->permissionError(__('Você não pode alternar emblemas deste membro', 'apollo-social'));
        }

        // TODO: Implement actual toggle logic.
        // For now, mock success.
        $this->success(
            [
                'membro_id'      => $membro_id,
                'badges_enabled' => $badges_on,
            ],
            __('Emblemas atualizados com sucesso', 'apollo-social')
        );
    }

    /**
     * Get membros data (mock implementation)
     *
     * @return array Lista de membros.
     */
    private function getMembrosData(): array
    {
        return [
            [
                'id'            => 1,
                'title'         => 'Membro Desenvolvedores',
                'slug'          => 'desenvolvedores',
                'description'   => 'Membro para profissionais de desenvolvimento',
                'badges_toggle' => true,
                'members_count' => 150,
                'managers'      => [ 1, 2 ],
            ],
            [
                'id'            => 2,
                'title'         => 'Membro Designers',
                'slug'          => 'designers',
                'description'   => 'Membro para profissionais de design',
                'badges_toggle' => false,
                'members_count' => 89,
                'managers'      => [ 1, 3 ],
            ],
        ];
    }

    /**
     * Get membro by ID (mock implementation)
     *
     * @param int $id Membro ID.
     * @return UnionEntity|null Membro entity or null.
     */
    private function getMembroById(int $id): ?UnionEntity
    {
        $membros = $this->getMembrosData();

        foreach ($membros as $membro_data) {
            if ($membro_data['id'] === $id) {
                return new UnionEntity($membro_data);
            }
        }

        return null;
    }
}
