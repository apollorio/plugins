<?php

/**
 * FASE 2: Social Post Type Registration
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\Infrastructure\PostTypes;

if (! defined('ABSPATH')) {
    exit;
}

class SocialPostType
{
    /**
     * Register the post type
     */
    public function register(): void
    {
        add_action('init', [ $this, 'registerPostType' ], 10);
    }

    /**
     * Register apollo_social_post CPT
     */
    public function registerPostType(): void
    {
        $labels = [
            'name'                  => __('Posts Sociais', 'apollo-social'),
            'singular_name'         => __('Post Social', 'apollo-social'),
            'menu_name'             => __('Posts Sociais', 'apollo-social'),
            'name_admin_bar'        => __('Post Social', 'apollo-social'),
            'add_new'               => __('Adicionar Novo', 'apollo-social'),
            'add_new_item'          => __('Adicionar Novo Post', 'apollo-social'),
            'new_item'              => __('Novo Post', 'apollo-social'),
            'edit_item'             => __('Editar Post', 'apollo-social'),
            'view_item'             => __('Ver Post', 'apollo-social'),
            'all_items'             => __('Todos os Posts', 'apollo-social'),
            'search_items'          => __('Buscar Posts', 'apollo-social'),
            'not_found'             => __('Nenhum post encontrado.', 'apollo-social'),
            'not_found_in_trash'    => __('Nenhum post encontrado na lixeira.', 'apollo-social'),
            'featured_image'        => __('Imagem do Post', 'apollo-social'),
            'set_featured_image'    => __('Definir imagem do post', 'apollo-social'),
            'remove_featured_image' => __('Remover imagem do post', 'apollo-social'),
            'use_featured_image'    => __('Usar como imagem do post', 'apollo-social'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            // Gutenberg support
                            'query_var' => true,
            'rewrite'                   => [ 'slug' => 'post-social' ],
            'capability_type'           => 'post',
            'has_archive'               => false,
            'hierarchical'              => false,
            'menu_position'             => 5,
            'menu_icon'                 => 'dashicons-format-status',
            'supports'                  => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'comments',
                'custom-fields',
            ],
            'comment_status' => 'open',
            // FASE 2: Permitir comentários
                            'map_meta_cap' => true,
            'capabilities'                 => [
                'create_posts' => 'publish_posts',
        // Apenas usuários que podem publicar podem criar
            ],
        ];

        register_post_type('apollo_social_post', $args);

        // FASE 2: Registrar taxonomia opcional para categorias
        $this->registerTaxonomy();
    }

    /**
     * Register taxonomy for social posts
     */
    private function registerTaxonomy(): void
    {
        $labels = [
            'name'          => __('Categorias de Post', 'apollo-social'),
            'singular_name' => __('Categoria', 'apollo-social'),
            'search_items'  => __('Buscar Categorias', 'apollo-social'),
            'all_items'     => __('Todas as Categorias', 'apollo-social'),
            'edit_item'     => __('Editar Categoria', 'apollo-social'),
            'update_item'   => __('Atualizar Categoria', 'apollo-social'),
            'add_new_item'  => __('Adicionar Nova Categoria', 'apollo-social'),
            'new_item_name' => __('Nome da Nova Categoria', 'apollo-social'),
            'menu_name'     => __('Categorias', 'apollo-social'),
        ];

        register_taxonomy(
            'apollo_post_category',
            [ 'apollo_social_post' ],
            [
                'hierarchical'      => true,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => [ 'slug' => 'categoria-post' ],
                'show_in_rest'      => true,
            ]
        );
    }
}
