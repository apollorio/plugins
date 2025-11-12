<?php

namespace Apollo\Modules\UserPages;

defined('ABSPATH') || exit;

/**
 * Handles CPT registration and rewrite rules for user pages.
 *
 * @category ApolloSocial
 * @package  ApolloSocial\UserPages
 * @author   Apollo Platform <tech@apollo.rio.br>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://apollo.rio.br
 */
class UserPageRegistrar
{
    public const POST_TYPE = 'user_page';
    public const QUERY_VAR = 'apollo_user_page_owner';
    public const META_KEY = '_apollo_user_id';

    /**
     * Register the custom post type used to store user profiles.
     *
     * @return void
     */
    public static function registerPostType(): void
    {
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }

        $labels = [
            'name'                  => __('Páginas de Usuário', 'apollo-social'),
            'singular_name'         => __('Página de Usuário', 'apollo-social'),
            'add_new'               => __('Adicionar nova', 'apollo-social'),
            'add_new_item'          => __('Adicionar nova página', 'apollo-social'),
            'edit_item'             => __('Editar página', 'apollo-social'),
            'new_item'              => __('Nova página', 'apollo-social'),
            'view_item'             => __('Ver página', 'apollo-social'),
            'search_items'          => __('Buscar páginas', 'apollo-social'),
            'not_found'             => __(
                'Nenhuma página encontrada',
                'apollo-social'
            ),
            'not_found_in_trash'    => __(
                'Nenhuma página na lixeira',
                'apollo-social'
            ),
            'all_items'             => __('Todas as páginas', 'apollo-social'),
            'menu_name'             => __('Páginas de Usuário', 'apollo-social'),
            'name_admin_bar'        => __('Página de Usuário', 'apollo-social'),
        ];

        register_post_type(
            self::POST_TYPE,
            [
                'labels'             => $labels,
                'public'             => true,
                'show_ui'            => true,
                'supports'           => [
                    'title',
                    'editor',
                    'thumbnail',
                    'custom-fields',
                    'revisions',
                ],
                'rewrite'            => [
                    'slug'       => 'user-page',
                    'with_front' => false,
                ],
                'has_archive'        => false,
                'publicly_queryable' => true,
                'show_in_rest'       => false,
            ]
        );
    }

    /**
     * Register the custom rewrite rule mapping /id/{userID} to our query var.
     *
     * @return void
     */
    public static function registerRewriteRules(): void
    {
        add_rewrite_rule(
            '^id/([0-9]+)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    /**
     * Make the custom query var available to WP_Query.
     *
     * @param array $vars Existing query vars.
     *
     * @return array
     */
    public static function registerQueryVar(array $vars): array
    {
        if (!in_array(self::QUERY_VAR, $vars, true)) {
            $vars[] = self::QUERY_VAR;
        }

        return $vars;
    }
}
