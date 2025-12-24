<?php
/**
 * Suppliers Module
 *
 * Main entry point for the Cena-Rio Suppliers module.
 * Registers CPT, taxonomies, routes, and handles access control.
 *
 * @package Apollo\Modules\Suppliers
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Modules\Suppliers;

use Apollo\Domain\Suppliers\SupplierService;
use Apollo\Infrastructure\Persistence\WPPostSupplierRepository;

/**
 * Class SuppliersModule
 *
 * @since 1.0.0
 */
class SuppliersModule {

	/**
	 * Module version.
	 */
	public const VERSION = '1.0.0';

	/**
	 * Initialized flag.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Service instance.
	 *
	 * @var SupplierService|null
	 */
	private static ?SupplierService $service = null;

	/**
	 * Initialize the module.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		// Initialize service.
		$repository    = new WPPostSupplierRepository();
		self::$service = new SupplierService( $repository );

		// Register CPT and taxonomies.
		add_action( 'init', array( self::class, 'register_post_type' ), 5 );
		add_action( 'init', array( self::class, 'register_taxonomies' ), 5 );
		add_action( 'init', array( self::class, 'register_routes' ), 10 );

		// Template redirect for Canvas Mode.
		add_action( 'template_redirect', array( self::class, 'handle_canvas_routes' ), 5 );

		// Register activation hook.
		if ( defined( 'APOLLO_SOCIAL_PLUGIN_FILE' ) ) {
			register_activation_hook( APOLLO_SOCIAL_PLUGIN_FILE, array( self::class, 'activate' ) );
		}

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( self::class, 'register_admin_menu' ) );
		}

		// Enqueue assets for Canvas pages.
		add_action( 'wp_enqueue_scripts', array( self::class, 'maybe_enqueue_assets' ), 20 );

		// AJAX handlers.
		add_action( 'wp_ajax_apollo_submit_supplier', array( self::class, 'handle_supplier_submit' ) );
		add_action( 'wp_ajax_nopriv_apollo_submit_supplier', array( self::class, 'handle_supplier_submit' ) );

		// Admin-post handler for form submit.
		add_action( 'admin_post_apollo_add_supplier', array( self::class, 'handle_supplier_form_submit' ) );
		add_action( 'admin_post_nopriv_apollo_add_supplier', array( self::class, 'handle_supplier_form_submit' ) );
	}

	/**
	 * Get service instance.
	 *
	 * @return SupplierService
	 *
	 * @since 1.0.0
	 */
	public static function get_service(): SupplierService {
		if ( null === self::$service ) {
			$repository    = new WPPostSupplierRepository();
			self::$service = new SupplierService( $repository );
		}
		return self::$service;
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function activate(): void {
		self::register_post_type();
		self::register_taxonomies();
		self::seed_taxonomy_terms();
		self::register_routes();
		flush_rewrite_rules();

		update_option( 'apollo_suppliers_version', self::VERSION );
	}

	/**
	 * Register custom post type.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function register_post_type(): void {
		$labels = array(
			'name'                  => 'Fornecedores',
			'singular_name'         => 'Fornecedor',
			'menu_name'             => 'Fornecedores',
			'name_admin_bar'        => 'Fornecedor',
			'add_new'               => 'Adicionar Novo',
			'add_new_item'          => 'Adicionar Novo Fornecedor',
			'new_item'              => 'Novo Fornecedor',
			'edit_item'             => 'Editar Fornecedor',
			'view_item'             => 'Ver Fornecedor',
			'all_items'             => 'Todos os Fornecedores',
			'search_items'          => 'Buscar Fornecedores',
			'parent_item_colon'     => 'Fornecedor Pai:',
			'not_found'             => 'Nenhum fornecedor encontrado.',
			'not_found_in_trash'    => 'Nenhum fornecedor na lixeira.',
			'featured_image'        => 'Logo do Fornecedor',
			'set_featured_image'    => 'Definir logo',
			'remove_featured_image' => 'Remover logo',
			'use_featured_image'    => 'Usar como logo',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-store',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => true,
		);

		register_post_type( WPPostSupplierRepository::POST_TYPE, $args );
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function register_taxonomies(): void {
		$post_type = WPPostSupplierRepository::POST_TYPE;

		// Category taxonomy (hierarchical).
		register_taxonomy(
			WPPostSupplierRepository::TAX_CATEGORY,
			$post_type,
			array(
				'labels'            => array(
					'name'              => 'Categorias',
					'singular_name'     => 'Categoria',
					'search_items'      => 'Buscar Categorias',
					'all_items'         => 'Todas as Categorias',
					'parent_item'       => 'Categoria Pai',
					'parent_item_colon' => 'Categoria Pai:',
					'edit_item'         => 'Editar Categoria',
					'update_item'       => 'Atualizar Categoria',
					'add_new_item'      => 'Adicionar Nova Categoria',
					'new_item_name'     => 'Nome da Nova Categoria',
					'menu_name'         => 'Categorias',
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);

		// Region taxonomy.
		register_taxonomy(
			WPPostSupplierRepository::TAX_REGION,
			$post_type,
			array(
				'labels'            => array(
					'name'          => 'Regiões',
					'singular_name' => 'Região',
					'menu_name'     => 'Regiões',
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);

		// Neighborhood taxonomy.
		register_taxonomy(
			WPPostSupplierRepository::TAX_NEIGHBORHOOD,
			$post_type,
			array(
				'labels'            => array(
					'name'          => 'Bairros',
					'singular_name' => 'Bairro',
					'menu_name'     => 'Bairros',
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);

		// Event type taxonomy.
		register_taxonomy(
			WPPostSupplierRepository::TAX_EVENT_TYPE,
			$post_type,
			array(
				'labels'            => array(
					'name'          => 'Tipos de Evento',
					'singular_name' => 'Tipo de Evento',
					'menu_name'     => 'Tipos de Evento',
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);

		// Supplier type taxonomy.
		register_taxonomy(
			WPPostSupplierRepository::TAX_TYPE,
			$post_type,
			array(
				'labels'            => array(
					'name'          => 'Tipos',
					'singular_name' => 'Tipo',
					'menu_name'     => 'Tipos',
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);

		// Mode taxonomy.
		register_taxonomy(
			WPPostSupplierRepository::TAX_MODE,
			$post_type,
			array(
				'labels'            => array(
					'name'          => 'Modalidades',
					'singular_name' => 'Modalidade',
					'menu_name'     => 'Modalidades',
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);

		// Badge taxonomy.
		register_taxonomy(
			WPPostSupplierRepository::TAX_BADGE,
			$post_type,
			array(
				'labels'            => array(
					'name'          => 'Selos',
					'singular_name' => 'Selo',
					'menu_name'     => 'Selos',
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Seed taxonomy terms with default values.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function seed_taxonomy_terms(): void {
		// Categories.
		$categories = SupplierService::get_category_labels();
		foreach ( $categories as $slug => $name ) {
			if ( ! term_exists( $slug, WPPostSupplierRepository::TAX_CATEGORY ) ) {
				wp_insert_term( $name, WPPostSupplierRepository::TAX_CATEGORY, array( 'slug' => $slug ) );
			}
		}

		// Regions.
		$regions = SupplierService::get_region_labels();
		foreach ( $regions as $slug => $name ) {
			if ( ! term_exists( $slug, WPPostSupplierRepository::TAX_REGION ) ) {
				wp_insert_term( $name, WPPostSupplierRepository::TAX_REGION, array( 'slug' => $slug ) );
			}
		}

		// Event types.
		$event_types = SupplierService::get_event_type_labels();
		foreach ( $event_types as $slug => $name ) {
			if ( ! term_exists( $slug, WPPostSupplierRepository::TAX_EVENT_TYPE ) ) {
				wp_insert_term( $name, WPPostSupplierRepository::TAX_EVENT_TYPE, array( 'slug' => $slug ) );
			}
		}

		// Supplier types.
		$supplier_types = SupplierService::get_supplier_type_labels();
		foreach ( $supplier_types as $slug => $name ) {
			if ( ! term_exists( $slug, WPPostSupplierRepository::TAX_TYPE ) ) {
				wp_insert_term( $name, WPPostSupplierRepository::TAX_TYPE, array( 'slug' => $slug ) );
			}
		}

		// Modes.
		$modes = SupplierService::get_mode_labels();
		foreach ( $modes as $slug => $name ) {
			if ( ! term_exists( $slug, WPPostSupplierRepository::TAX_MODE ) ) {
				wp_insert_term( $name, WPPostSupplierRepository::TAX_MODE, array( 'slug' => $slug ) );
			}
		}

		// Badges.
		$badges = SupplierService::get_badge_labels();
		foreach ( $badges as $slug => $name ) {
			if ( ! term_exists( $slug, WPPostSupplierRepository::TAX_BADGE ) ) {
				wp_insert_term( $name, WPPostSupplierRepository::TAX_BADGE, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Register rewrite rules for Canvas routes.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function register_routes(): void {
		// /fornece/ - List.
		add_rewrite_rule(
			'^fornece/?$',
			'index.php?apollo_fornece_view=list',
			'top'
		);

		// /fornece/add/ - Add new.
		add_rewrite_rule(
			'^fornece/add/?$',
			'index.php?apollo_fornece_view=add',
			'top'
		);

		// /fornece/{id}/ - Single (shell with modal).
		add_rewrite_rule(
			'^fornece/([0-9]+)/?$',
			'index.php?apollo_fornece_view=single&apollo_fornece_id=$matches[1]',
			'top'
		);

		// Register query vars.
		add_filter(
			'query_vars',
			function ( array $vars ): array {
				$vars[] = 'apollo_fornece_view';
				$vars[] = 'apollo_fornece_id';
				return $vars;
			}
		);
	}

	/**
	 * Handle Canvas mode routes (template_redirect).
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function handle_canvas_routes(): void {
		$view = get_query_var( 'apollo_fornece_view' );

		if ( empty( $view ) ) {
			return;
		}

		// Access check.
		if ( ! self::check_access() ) {
			return;
		}

		$template_path = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/cena-rio/';

		switch ( $view ) {
			case 'list':
				include $template_path . 'suppliers-list.php';
				exit;

			case 'add':
				if ( ! self::get_service()->user_can_access() ) {
					self::redirect_with_notice();
					return;
				}
				include $template_path . 'supplier-add.php';
				exit;

			case 'single':
				$supplier_id = absint( get_query_var( 'apollo_fornece_id' ) );
				if ( $supplier_id > 0 ) {
					// Pass supplier ID to template.
					$GLOBALS['apollo_supplier_id'] = $supplier_id;
					include $template_path . 'suppliers-list.php';
					exit;
				}
				break;
		}
	}

	/**
	 * Check access and redirect if needed.
	 *
	 * @return bool True if access granted.
	 *
	 * @since 1.0.0
	 */
	private static function check_access(): bool {
		$service = self::get_service();

		// Not logged in - redirect to login.
		if ( ! is_user_logged_in() ) {
			auth_redirect();
			return false;
		}

		// Logged in but no access - redirect to /eventos with notice.
		if ( ! $service->user_can_access() ) {
			self::redirect_with_notice();
			return false;
		}

		return true;
	}

	/**
	 * Redirect to /eventos with access notice.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private static function redirect_with_notice(): void {
		$redirect_url = add_query_arg(
			'apollo_notice',
			'cena_rio_access_required',
			home_url( '/eventos/' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Maybe enqueue assets for suppliers pages.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function maybe_enqueue_assets(): void {
		$view = get_query_var( 'apollo_fornece_view' );

		if ( empty( $view ) ) {
			return;
		}

		// Enqueue suppliers CSS.
		wp_enqueue_style(
			'apollo-cena-rio-suppliers',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena-rio-suppliers.css',
			array(),
			self::VERSION
		);

		// Enqueue suppliers JS.
		wp_enqueue_script(
			'apollo-cena-rio-suppliers',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena-rio-suppliers.js',
			array(),
			self::VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'apollo-cena-rio-suppliers',
			'apolloFornece',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'apollo_fornece_nonce' ),
				'baseUrl'   => home_url( '/fornece/' ),
				'canManage' => self::get_service()->user_can_manage(),
				'strings'   => array(
					'loading'      => 'Carregando...',
					'noResults'    => 'Nenhum fornecedor encontrado.',
					'error'        => 'Ocorreu um erro. Tente novamente.',
					'filterAll'    => 'Todos',
					'clearFilters' => 'Limpar filtros',
					'orcamento'    => 'Solicitar Orçamento',
					'contato'      => 'Contato',
					'mensagem'     => 'Enviar Mensagem',
					'compartilhar' => 'Compartilhar',
					'verificado'   => 'Verificado',
					'avaliacoes'   => 'avaliações',
				),
			)
		);
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function register_admin_menu(): void {
		// The CPT already registers its own menu.
		// Add submenu for settings if needed.
	}

	/**
	 * Handle AJAX supplier submit.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function handle_supplier_submit(): void {
		check_ajax_referer( 'apollo_fornece_nonce', 'nonce' );

		$service = self::get_service();

		if ( ! $service->user_can_access() ) {
			wp_send_json_error( array( 'message' => 'Acesso negado.' ), 403 );
		}

		$data = array(
			'name'              => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description'       => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'category'          => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '',
			'region'            => isset( $_POST['region'] ) ? sanitize_key( wp_unslash( $_POST['region'] ) ) : '',
			'neighborhood'      => isset( $_POST['neighborhood'] ) ? sanitize_text_field( wp_unslash( $_POST['neighborhood'] ) ) : '',
			'supplier_type'     => isset( $_POST['supplier_type'] ) ? sanitize_key( wp_unslash( $_POST['supplier_type'] ) ) : 'servico',
			'price_tier'        => isset( $_POST['price_tier'] ) ? absint( $_POST['price_tier'] ) : 1,
			'contact_email'     => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
			'contact_phone'     => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
			'contact_whatsapp'  => isset( $_POST['contact_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_whatsapp'] ) ) : '',
			'contact_instagram' => isset( $_POST['contact_instagram'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_instagram'] ) ) : '',
			'contact_website'   => isset( $_POST['contact_website'] ) ? esc_url_raw( wp_unslash( $_POST['contact_website'] ) ) : '',
		);

		// Handle arrays.
		if ( isset( $_POST['event_types'] ) && is_array( $_POST['event_types'] ) ) {
			$data['event_types'] = array_map( 'sanitize_key', wp_unslash( $_POST['event_types'] ) );
		}

		if ( isset( $_POST['modes'] ) && is_array( $_POST['modes'] ) ) {
			$data['modes'] = array_map( 'sanitize_key', wp_unslash( $_POST['modes'] ) );
		}

		if ( isset( $_POST['badges'] ) && is_array( $_POST['badges'] ) ) {
			$data['badges'] = array_map( 'sanitize_key', wp_unslash( $_POST['badges'] ) );
		}

		$supplier_id = $service->create_supplier( $data );

		if ( false === $supplier_id ) {
			wp_send_json_error( array( 'message' => 'Erro ao criar fornecedor.' ), 400 );
		}

		wp_send_json_success(
			array(
				'id'      => $supplier_id,
				'url'     => home_url( '/fornece/' . $supplier_id . '/' ),
				'message' => 'Fornecedor criado com sucesso!',
			)
		);
	}

	/**
	 * Handle form submission via admin-post.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function handle_supplier_form_submit(): void {
		if ( ! isset( $_POST['supplier_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['supplier_nonce'] ) ), 'apollo_add_supplier' ) ) {
			wp_die( 'Nonce inválido.', 'Erro de Segurança', array( 'response' => 403 ) );
		}

		$service = self::get_service();

		if ( ! $service->user_can_access() ) {
			wp_die( 'Acesso negado.', 'Sem Permissão', array( 'response' => 403 ) );
		}

		$data = array(
			'name'              => isset( $_POST['supplier_name'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_name'] ) ) : '',
			'description'       => isset( $_POST['supplier_description'] ) ? wp_kses_post( wp_unslash( $_POST['supplier_description'] ) ) : '',
			'category'          => isset( $_POST['supplier_category'] ) ? sanitize_key( wp_unslash( $_POST['supplier_category'] ) ) : '',
			'region'            => isset( $_POST['supplier_region'] ) ? sanitize_key( wp_unslash( $_POST['supplier_region'] ) ) : '',
			'neighborhood'      => isset( $_POST['supplier_neighborhood'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_neighborhood'] ) ) : '',
			'supplier_type'     => isset( $_POST['supplier_type'] ) ? sanitize_key( wp_unslash( $_POST['supplier_type'] ) ) : 'servico',
			'price_tier'        => isset( $_POST['supplier_price_tier'] ) ? absint( $_POST['supplier_price_tier'] ) : 1,
			'capacity_max'      => isset( $_POST['supplier_capacity'] ) ? absint( $_POST['supplier_capacity'] ) : 0,
			'contact_email'     => isset( $_POST['supplier_email'] ) ? sanitize_email( wp_unslash( $_POST['supplier_email'] ) ) : '',
			'contact_phone'     => isset( $_POST['supplier_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_phone'] ) ) : '',
			'contact_whatsapp'  => isset( $_POST['supplier_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_whatsapp'] ) ) : '',
			'contact_instagram' => isset( $_POST['supplier_instagram'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_instagram'] ) ) : '',
			'contact_website'   => isset( $_POST['supplier_website'] ) ? esc_url_raw( wp_unslash( $_POST['supplier_website'] ) ) : '',
		);

		// Handle arrays.
		if ( isset( $_POST['supplier_event_types'] ) && is_array( $_POST['supplier_event_types'] ) ) {
			$data['event_types'] = array_map( 'sanitize_key', wp_unslash( $_POST['supplier_event_types'] ) );
		}

		if ( isset( $_POST['supplier_modes'] ) && is_array( $_POST['supplier_modes'] ) ) {
			$data['modes'] = array_map( 'sanitize_key', wp_unslash( $_POST['supplier_modes'] ) );
		}

		if ( isset( $_POST['supplier_badges'] ) && is_array( $_POST['supplier_badges'] ) ) {
			$data['badges'] = array_map( 'sanitize_key', wp_unslash( $_POST['supplier_badges'] ) );
		}

		$supplier_id = $service->create_supplier( $data );

		if ( false === $supplier_id ) {
			wp_safe_redirect(
				add_query_arg( 'error', 'create_failed', home_url( '/fornece/add/' ) )
			);
			exit;
		}

		wp_safe_redirect( home_url( '/fornece/' . $supplier_id . '/' ) );
		exit;
	}
}
