<?php
/**
 * Apollo Documents Routes.
 *
 * Rotas:
 * /doc/new → Criar novo documento.
 * /doc/{file_id} → Editar documento existente.
 * /pla/new → Criar nova planilha.
 * /pla/{file_id} → Editar planilha existente.
 * /sign → Listagem de documentos para assinatura.
 * /sign/{token} → Assinar documento via link público.
 *
 * @package Apollo\Modules\Documents
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPressVIPMinimum.Variables
 */

namespace Apollo\Modules\Documents;

class DocumentsRoutes {


	public function __construct() {
		add_action( 'init', [ $this, 'registerRoutes' ] );
		add_action( 'template_redirect', [ $this, 'handleRoutes' ] );
		add_filter( 'query_vars', [ $this, 'registerQueryVars' ] );
	}

	/**
	 * Register query vars
	 */
	public function registerQueryVars( $vars ) {
		$vars[] = 'file_id';
		$vars[] = 'signature_token';
		return $vars;
	}

	/**
	 * Register custom routes
	 */
	public function registerRoutes() {
		// Documentos
		add_rewrite_rule( '^doc/new/?$', 'index.php?apollo_route=doc_new', 'top' );
		add_rewrite_rule( '^doc/([a-zA-Z0-9]+)/?$', 'index.php?apollo_route=doc_edit&file_id=$matches[1]', 'top' );

		// Planilhas
		add_rewrite_rule( '^pla/new/?$', 'index.php?apollo_route=pla_new', 'top' );
		add_rewrite_rule( '^pla/([a-zA-Z0-9]+)/?$', 'index.php?apollo_route=pla_edit&file_id=$matches[1]', 'top' );

		// Assinaturas
		add_rewrite_rule( '^sign/?$', 'index.php?apollo_route=sign_list', 'top' );
		add_rewrite_rule( '^sign/([a-zA-Z0-9]+)/?$', 'index.php?apollo_route=sign_document&signature_token=$matches[1]', 'top' );

		// Registrar query vars
		add_rewrite_tag( '%apollo_route%', '([^&]+)' );
		add_rewrite_tag( '%file_id%', '([^&]+)' );
		add_rewrite_tag( '%signature_token%', '([^&]+)' );

		// Flush rewrite rules (apenas em desenvolvimento)
		// flush_rewrite_rules();
	}

	/**
	 * Handle custom routes
	 */
	public function handleRoutes() {
		// Don't interfere with WordPress core functionality
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Don't interfere with feeds, REST API, or other WordPress endpoints
		if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Don't interfere with sitemaps
		if ( function_exists( 'wp_is_sitemap' ) && call_user_func( 'wp_is_sitemap' ) ) {
			return;
		}

		$route = get_query_var( 'apollo_route' );

		if ( empty( $route ) ) {
			return;
		}

		switch ( $route ) {
			case 'doc_new':
				$this->handleDocNew();
				break;

			case 'doc_edit':
				$this->handleDocEdit();
				break;

			case 'pla_new':
				$this->handlePlaNew();
				break;

			case 'pla_edit':
				$this->handlePlaEdit();
				break;

			case 'sign_list':
				$this->handleSignList();
				break;

			case 'sign_document':
				$this->handleSignDocument();
				break;
		}//end switch
	}

	/**
	 * Handle /doc/new
	 */
	private function handleDocNew() {
		$doc_manager = new DocumentsManager();

		// Se POST, criar documento
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$title   = sanitize_text_field( $_POST['title'] ?? 'Novo Documento' );
			$content = wp_kses_post( $_POST['content'] ?? '' );

			$result = $doc_manager->createDocument( 'documento', $title, $content );

			if ( $result['success'] ) {
				wp_redirect( $result['url'] );
				exit;
			}
		}

		// Carregar template de criação
		$this->loadTemplate(
			'documents/editor',
			[
				'type' => 'documento',
				'mode' => 'new',
			]
		);
	}

	/**
	 * Handle /doc/{file_id}
	 */
	private function handleDocEdit() {
		global $wpdb;
		$file_id = get_query_var( 'file_id' );

		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$documents_table} WHERE file_id = %s",
				$file_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			wp_die( 'Documento não encontrado', 'Erro 404', [ 'response' => 404 ] );
		}

		// Se POST, atualizar documento
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$content = wp_kses_post( $_POST['content'] ?? '' );
			$title   = sanitize_text_field( $_POST['title'] ?? $document['title'] );

			$wpdb->update(
				$documents_table,
				[
					'title'      => $title,
					'content'    => $content,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'file_id' => $file_id ]
			);

			// Retornar JSON se AJAX
			if ( ! empty( $_POST['ajax'] ) ) {
				wp_send_json_success( [ 'message' => 'Salvo com sucesso' ] );
			}
		}

		// Carregar template de edição
		$this->loadTemplate(
			'documents/editor',
			[
				'type'     => 'documento',
				'mode'     => 'edit',
				'document' => $document,
			]
		);
	}

	/**
	 * Handle /pla/new
	 */
	private function handlePlaNew() {
		$doc_manager = new DocumentsManager();

		// Se POST, criar planilha
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$title   = sanitize_text_field( $_POST['title'] ?? 'Nova Planilha' );
			$content = wp_kses_post( $_POST['content'] ?? '' );

			$result = $doc_manager->createDocument( 'planilha', $title, $content );

			if ( $result['success'] ) {
				wp_redirect( $result['url'] );
				exit;
			}
		}

		// Carregar template de criação
		$this->loadTemplate(
			'documents/editor',
			[
				'type' => 'planilha',
				'mode' => 'new',
			]
		);
	}

	/**
	 * Handle /pla/{file_id}
	 */
	private function handlePlaEdit() {
		global $wpdb;
		$file_id = get_query_var( 'file_id' );

		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$documents_table} WHERE file_id = %s",
				$file_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			wp_die( 'Planilha não encontrada', 'Erro 404', [ 'response' => 404 ] );
		}

		// Se POST, atualizar planilha
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$content = wp_kses_post( $_POST['content'] ?? '' );
			$title   = sanitize_text_field( $_POST['title'] ?? $document['title'] );

			$wpdb->update(
				$documents_table,
				[
					'title'      => $title,
					'content'    => $content,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'file_id' => $file_id ]
			);

			// Retornar JSON se AJAX
			if ( ! empty( $_POST['ajax'] ) ) {
				wp_send_json_success( [ 'message' => 'Salvo com sucesso' ] );
			}
		}

		// Carregar template de edição
		$this->loadTemplate(
			'documents/editor',
			[
				'type'     => 'planilha',
				'mode'     => 'edit',
				'document' => $document,
			]
		);
	}

	/**
	 * Handle /sign (lista de documentos)
	 */
	private function handleSignList() {
		$this->loadTemplate( 'documents/sign-list' );
	}

	/**
	 * Handle /sign/{token} (assinatura pública)
	 */
	private function handleSignDocument() {
		$this->loadTemplate( 'documents/sign-document' );
	}

	/**
	 * Load template
	 */
	private function loadTemplate( $template, $data = [] ) {
		extract( $data );

		$template_path = ( defined( 'APOLLO_SOCIAL_PATH' ) ? constant( 'APOLLO_SOCIAL_PATH' ) : plugin_dir_path( dirname( dirname( __DIR__ ) ) ) ) . '/templates/' . $template . '.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
			exit;
		}

		wp_die( 'Template não encontrado: ' . $template, 'Erro', [ 'response' => 404 ] );
	}
}

// Inicializar rotas
new DocumentsRoutes();
