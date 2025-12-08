<?php
/**
 * CPT User Page: Páginas customizáveis de usuário
 */
class Apollo_User_Page_CPT {
	public static function register() {
		register_post_type(
			'user_page',
			[
				'label'        => 'Página de Usuário',
				'labels'       => [
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
				],
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => false,
				'supports'     => [ 'title', 'thumbnail', 'comments', 'revisions' ],
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-id',
			]
		);
	}
}
add_action( 'init', [ 'Apollo_User_Page_CPT', 'register' ] );
