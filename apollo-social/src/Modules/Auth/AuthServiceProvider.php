<?php

namespace Apollo\Modules\Auth;

defined('ABSPATH') || exit;

class AuthServiceProvider
{
    public function register(): void
    {
        add_filter('login_redirect', [$this, 'redirectToWorld'], 10, 3);
        
        // Register User Roles modifications
        $userRoles = new UserRoles();
        $userRoles->register();
    }

    /**
     * Redireciona o usuário autenticado para o dashboard Apollo em /world/.
     *
     * @param string       $redirectTo Valor calculado pelo WordPress para redirecionamento.
     * @param string       $requested  Redirecionamento solicitado explicitamente.
     * @param \WP_User|WP_Error $user  Objeto do usuário autenticado ou erro.
     *
     * @return string URL final de redirecionamento.
     */
    public function redirectToWorld($redirectTo, $requested, $user): string
    {
        if (is_wp_error($user) || !$user instanceof \WP_User) {
            return $redirectTo;
        }

        // Respeita redirecionamentos explícitos (por exemplo, formulários com redirect_to customizado).
        if (!empty($requested)) {
            return $requested;
        }

        if (!empty($redirectTo) && !str_contains($redirectTo, 'wp-admin')) {
            return $redirectTo;
        }

        // Redireciona para o feed principal do Apollo.
        return home_url('/world/');
    }
}

