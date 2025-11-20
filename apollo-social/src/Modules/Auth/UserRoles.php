<?php

namespace Apollo\Modules\Auth;

class UserRoles
{
    public function register()
    {
        add_action('init', [$this, 'modifyUserRoles']);
    }

    public function modifyUserRoles()
    {
        global $wp_roles;

        if (!isset($wp_roles)) {
            return;
        }

        // 0. 'Subscriber' => 'Clubber'
        // Can submit new events on public form add new event as draft
        if (isset($wp_roles->roles['subscriber'])) {
            $wp_roles->roles['subscriber']['name'] = 'Clubber';
            $wp_roles->role_names['subscriber'] = 'Clubber';
            // Add capability to submit events if not present (usually handled by form plugin, but we can ensure cap)
            // Note: 'edit_posts' allows basic post creation, but specific event capabilities depend on Events Manager
        }

        // 1. 'Contributor' => 'Cena::rio'
        if (isset($wp_roles->roles['contributor'])) {
            $wp_roles->roles['contributor']['name'] = 'Cena::rio';
            $wp_roles->role_names['contributor'] = 'Cena::rio';
        }

        // Create 'cena-rio' role if it doesn't exist, with same rights as Contributor
        if (!isset($wp_roles->roles['cena-rio'])) {
            $contributor = get_role('contributor');
            add_role('cena-rio', 'Cena::rio', $contributor->capabilities);
        }

        // 2. Author => 'Cena::rj'
        if (isset($wp_roles->roles['author'])) {
            $wp_roles->roles['author']['name'] = 'Cena::rj';
            $wp_roles->role_names['author'] = 'Cena::rj';
        }

        // 3. Editor => 'Apollo::rio'
        if (isset($wp_roles->roles['editor'])) {
            $wp_roles->roles['editor']['name'] = 'Apollo::rio';
            $wp_roles->role_names['editor'] = 'Apollo::rio';
        }

        // 4. Administrator => 'Apollo'
        if (isset($wp_roles->roles['administrator'])) {
            $wp_roles->roles['administrator']['name'] = 'Apollo';
            $wp_roles->role_names['administrator'] = 'Apollo';
        }
    }
}

