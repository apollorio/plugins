<?php

/**
 * CPT User Page: Páginas customizáveis de usuário
 *
 * DEPRECATED: Este arquivo é legado e foi substituído pelo UserPageRegistrar.
 * Mantido apenas para backward compatibility.
 *
 * @see \Apollo\Modules\UserPages\UserPageRegistrar
 * @deprecated Use UserPageRegistrar instead
 */
class Apollo_User_Page_CPT {

	public static function register() {
		// Guard: Skip if already registered by modern UserPageRegistrar.
		if ( post_type_exists( 'user_page' ) ) {
			return;
		}

		register_post_type(
			'user_page',
			array(
				'label'        => 'Página de Usuário',
				'labels'       => array(
					'name'               => 'Páginas de Usuário',
					'singular_name'      => 'Página de Usuário',
					'add_new'            => 'Adicionar Nova',
					'add_new_item'       => 'Adicionar Nova Página',
					'edit_item'          => 'Editar Página',
					'new_item'           => 'Nova Página',
					'view_item'          => 'Ver Página',
					'search_items'       => 'Buscar Página',
					'not_found'          => 'Nenhuma página encontrada',
					'not_found_in_trash' => 'Nenhuma página na lixeira',
					'all_items'          => 'Todas as Páginas',
					'menu_name'          => 'Páginas de Usuário',
					'name_admin_bar'     => 'Página de Usuário',
					'comments'           => 'Depoimentos',
					'comments_add'       => 'Adicionar Depoimento',
					'comments_view'      => 'Ver Depoimentos',
				),
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => false,
				'supports'     => array( 'title', 'thumbnail', 'comments', 'revisions' ),
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-id',
			)
		);
	}
}
add_action( 'init', array( 'Apollo_User_Page_CPT', 'register' ) );