<?php
namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\User;

/**
 * Users REST Controller
 */
class UsersController extends BaseController
{
    /**
     * GET /apollo/v1/users/{id|login}
     */
    public function show(): void
    {
        $user_identifier = $_GET['id'] ?? $_GET['login'] ?? '';
        
        if (empty($user_identifier)) {
            $this->validationError('User ID or login is required');
        }
        
        $user_data = $this->getUserData($user_identifier);
        
        if (!$user_data) {
            $this->error('User not found', 404);
        }
        
        $this->success($user_data);
    }
    
    /**
     * Get user data including groups (mock implementation)
     */
    private function getUserData(string $identifier): ?array
    {
        // Mock users data
        $users = [
            [
                'id' => 1,
                'login' => 'joao-silva',
                'email' => 'joao@example.com',
                'display_name' => 'João Silva',
                'bio' => 'Desenvolvedor PHP experiente',
                'groups' => [
                    ['id' => 1, 'title' => 'Desenvolvedores PHP', 'type' => 'comunidade'],
                    ['id' => 2, 'title' => 'Core Team', 'type' => 'nucleo']
                ],
                'unions' => [
                    ['id' => 1, 'title' => 'União dos Desenvolvedores']
                ]
            ],
            [
                'id' => 2,
                'login' => 'maria-santos',
                'email' => 'maria@example.com',
                'display_name' => 'Maria Santos',
                'bio' => 'Designer UX/UI',
                'groups' => [
                    ['id' => 1, 'title' => 'Desenvolvedores PHP', 'type' => 'comunidade']
                ],
                'unions' => [
                    ['id' => 2, 'title' => 'União dos Designers']
                ]
            ]
        ];
        
        // Find by ID or login
        foreach ($users as $user) {
            if ($user['id'] == $identifier || $user['login'] === $identifier) {
                return $user;
            }
        }
        
        return null;
    }
}