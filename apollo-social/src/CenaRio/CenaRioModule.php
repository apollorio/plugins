<?php

namespace Apollo\CenaRio;

use DateTime;
use WP_Post;
use WP_Query;

/**
 * Porta de entrada da área Cena Rio.
 *
 * @category ApolloSocial
 * @package  ApolloSocial\CenaRio
 * @author   Apollo Platform <tech@apollo.rio.br>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://apollo.rio.br
 */
class CenaRioModule {

	public const PAGE_SLUG              = 'cena-rio';
	public const DOC_POST_TYPE          = 'cena_document';
	public const PLAN_POST_TYPE         = 'cena_event_plan';
	public const ROLE                   = 'cena-rio';
	public const MAX_DOCUMENTS_PER_USER = 5;

	/**
	 * Registra hooks públicos.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'init', array( self::class, 'registerRole' ) );
		add_action( 'init', array( self::class, 'registerPostTypes' ) );

		add_action(
			'admin_post_cena_create_document',
			array( self::class, 'handleCreateDocument' )
		);

		add_action(
			'admin_post_cena_add_plan',
			array( self::class, 'handleAddPlan' )
		);

		add_filter( 'template_include', array( self::class, 'maybeUseTemplate' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
	}

	/**
	 * Executado na ativação do plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::registerRole();
		self::registerPostTypes();
		self::ensurePageExists();
		flush_rewrite_rules();
	}

	/**
	 * Garante que a role personalizada exista.
	 *
	 * @return void
	 */
	public static function registerRole(): void {
		if ( get_role( self::ROLE ) ) {
			return;
		}

		$author_role = get_role( 'author' );
		$caps        = $author_role ? $author_role->capabilities : array();

		add_role( self::ROLE, 'Cena Rio', $caps );
	}

	/**
	 * Registra os CPTs e metas necessárias.
	 *
	 * @return void
	 */
	public static function registerPostTypes(): void {
		register_post_type(
			self::DOC_POST_TYPE,
			array(
				'label'           => 'Documentos Cena Rio',
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'supports'        => array( 'title', 'editor', 'author', 'revisions' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_position'   => 25,
				'menu_icon'       => 'dashicons-analytics',
			)
		);

		register_post_meta(
			self::DOC_POST_TYPE,
			'_cena_is_library',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => '__return_true',
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		register_post_type(
			self::PLAN_POST_TYPE,
			array(
				'label'           => 'Eventos em Planejamento',
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'supports'        => array( 'title', 'editor', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_position'   => 26,
				'menu_icon'       => 'dashicons-calendar-alt',
			)
		);

		register_post_meta(
			self::PLAN_POST_TYPE,
			'_cena_plan_date',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( self::class, 'sanitizeDateMeta' ),
			)
		);
	}

	/**
	 * Sanitiza a meta de data (Y-m-d).
	 *
	 * @param mixed $value String recebida via REST.
	 *
	 * @return string
	 */
	public static function sanitizeDateMeta( $value ) {
		$date = DateTime::createFromFormat( 'Y-m-d', (string) $value );

		return $date ? $date->format( 'Y-m-d' ) : '';
	}

	/**
	 * Cria a página se ainda não existir.
	 *
	 * @return void
	 */
	public static function ensurePageExists(): void {
		$page = get_page_by_path( self::PAGE_SLUG );
		if ( $page instanceof WP_Post ) {
			return;
		}

		wp_insert_post(
			array(
				'post_title'  => 'Cena Rio',
				'post_name'   => self::PAGE_SLUG,
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Define o template personalizado quando aplicável.
	 *
	 * @param string $template Caminho padrão do tema.
	 *
	 * @return string
	 */
	public static function maybeUseTemplate( string $template ): string {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return $template;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		if ( ! self::currentUserCanAccess() ) {
			wp_die( __( 'Acesso restrito à indústria.', 'apollo-social' ), 403 );
		}

		return APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/page-cena-rio.php';
	}

	/**
	 * Enfileira assets específicos da página.
	 * Usa sistema centralizado Apollo ShadCN/Tailwind
	 *
	 * @return void
	 */
	public static function enqueueAssets(): void {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return;
		}

		// Carregar sistema centralizado ShadCN/Tailwind
		$shadcn_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/apollo-shadcn-loader.php';
		if ( file_exists( $shadcn_loader ) ) {
			require_once $shadcn_loader;
			if ( class_exists( 'Apollo_ShadCN_Loader' ) ) {
				$shadcn_class = 'Apollo_ShadCN_Loader';
				call_user_func( array( $shadcn_class, 'get_instance' ) );
			}
		}

		// CSS específico da página Cena::Rio
		wp_enqueue_style(
			'cena-rio-page',
			APOLLO_SOCIAL_PLUGIN_URL . 'cena-rio/assets/cena-rio-page.css',
			array( 'apollo-shadcn-base', 'apollo-uni-css' ),
			APOLLO_SOCIAL_VERSION
		);

		// JavaScript específico da página
		wp_enqueue_script(
			'cena-rio-page',
			APOLLO_SOCIAL_PLUGIN_URL . 'cena-rio/assets/cena-rio-page.js',
			array( 'jquery' ),
			APOLLO_SOCIAL_VERSION,
			true
		);

		// Localizar script
		wp_localize_script(
			'cena-rio-page',
			'cenaRioData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cena_rio_nonce' ),
				'userId'  => get_current_user_id(),
			)
		);
	}

	/**
	 * Lida com a criação de documentos.
	 *
	 * @return void
	 */
	public static function handleCreateDocument(): void {
		if ( ! self::currentUserCanAccess() ) {
			wp_die( __( 'Acesso negado.', 'apollo-social' ), 403 );
		}

		check_admin_referer( 'cena_create_document', 'cena_doc_nonce' );

		$user_id = get_current_user_id();
		if ( self::getUserDocumentCount( $user_id ) >= self::MAX_DOCUMENTS_PER_USER ) {
			self::redirectWithMessage( 'limit' );
		}

		$title = isset( $_POST['cena_doc_title'] )
			? sanitize_text_field( wp_unslash( $_POST['cena_doc_title'] ) )
			: '';

		$content = isset( $_POST['cena_doc_content'] )
			? wp_kses_post( wp_unslash( $_POST['cena_doc_content'] ) )
			: '';

		if ( '' === $title || '' === $content ) {
			self::redirectWithMessage( 'missing' );
		}

		$result = wp_insert_post(
			array(
				'post_type'    => self::DOC_POST_TYPE,
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => $user_id,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			self::redirectWithMessage( 'error' );
		}

		self::redirectWithMessage( 'doc_created' );
	}

	/**
	 * Lida com novo evento em planejamento.
	 *
	 * @return void
	 */
	public static function handleAddPlan(): void {
		if ( ! self::currentUserCanAccess() ) {
			wp_die( __( 'Acesso negado.', 'apollo-social' ), 403 );
		}

		check_admin_referer( 'cena_add_plan', 'cena_plan_nonce' );

		$title = isset( $_POST['cena_plan_title'] )
			? sanitize_text_field( wp_unslash( $_POST['cena_plan_title'] ) )
			: '';

		$date_raw = isset( $_POST['cena_plan_date'] )
			? sanitize_text_field( wp_unslash( $_POST['cena_plan_date'] ) )
			: '';

		$notes = isset( $_POST['cena_plan_notes'] )
			? wp_kses_post( wp_unslash( $_POST['cena_plan_notes'] ) )
			: '';

		$date = DateTime::createFromFormat( 'Y-m-d', $date_raw );
		if ( '' === $title || false === $date ) {
			self::redirectWithMessage( 'plan_missing' );
		}

		$result = wp_insert_post(
			array(
				'post_type'    => self::PLAN_POST_TYPE,
				'post_title'   => $title,
				'post_content' => $notes,
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
				'meta_input'   => array(
					'_cena_plan_date' => $date->format( 'Y-m-d' ),
				),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			self::redirectWithMessage( 'plan_error' );
		}

		self::redirectWithMessage( 'plan_created' );
	}

	/**
	 * Checa se usuário atual pode acessar a área.
	 *
	 * @return bool
	 */
	public static function currentUserCanAccess(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user    = wp_get_current_user();
		$allowed = array( 'administrator', 'editor', 'author', self::ROLE );

		return (bool) array_intersect( $allowed, $user->roles );
	}

	/**
	 * Retorna docs do usuário logado.
	 *
	 * @param int $user_id ID do autor.
	 *
	 * @return array
	 */
	public static function getUserDocuments( int $user_id ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::DOC_POST_TYPE,
				'post_status'    => array( 'draft', 'pending', 'publish' ),
				'posts_per_page' => self::MAX_DOCUMENTS_PER_USER,
				'author'         => $user_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return $query->posts;
	}

	/**
	 * Conta docs de um usuário.
	 *
	 * @param int $user_id ID do autor.
	 *
	 * @return int
	 */
	public static function getUserDocumentCount( int $user_id ): int {
		$query = new WP_Query(
			array(
				'post_type'      => self::DOC_POST_TYPE,
				'post_status'    => array( 'draft', 'pending', 'publish' ),
				'author'         => $user_id,
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Lista documentos marcados como biblioteca.
	 *
	 * @param int $limit Quantidade máxima.
	 *
	 * @return array
	 */
	public static function getLibraryDocuments( int $limit = 8 ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::DOC_POST_TYPE,
				'post_status'    => 'publish',
				'meta_key'       => '_cena_is_library',
				'meta_value'     => '1',
				'posts_per_page' => $limit,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		return $query->posts;
	}

	/**
	 * Busca eventos em planejamento do usuário.
	 *
	 * @param int $user_id ID do autor.
	 * @param int $limit   Quantidade máxima.
	 *
	 * @return array
	 */
	public static function getEventPlans( int $user_id, int $limit = 10 ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::PLAN_POST_TYPE,
				'post_status'    => array( 'draft', 'pending', 'publish' ),
				'author'         => $user_id,
				'posts_per_page' => $limit,
				'meta_key'       => '_cena_plan_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			)
		);

		return $query->posts;
	}

	/**
	 * Faz redirect para a página principal com query string.
	 *
	 * @param string $code Código da mensagem.
	 *
	 * @return void
	 */
	public static function redirectWithMessage( string $code ): void {
		$location = add_query_arg(
			'cena_message',
			$code,
			home_url( '/' . self::PAGE_SLUG . '/' ),
		);

		wp_safe_redirect( $location );
		exit;
	}
}
