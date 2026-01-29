<?php
/**
 * Apollo REST API Loader
 *
 * Registers REST API routes and loads controllers.
 *
 * @package Apollo_Core
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\REST_API;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_API_Loader
 *
 * Initializes the REST API infrastructure.
 *
 * @since 2.0.0
 */
class REST_API_Loader {

	/**
	 * Instance.
	 *
	 * @since 2.0.0
	 * @var REST_API_Loader|null
	 */
	private static ?REST_API_Loader $instance = null;

	/**
	 * Get instance.
	 *
	 * @since 2.0.0
	 * @return REST_API_Loader
	 */
	public static function get_instance(): REST_API_Loader {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Load dependencies.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function load_dependencies(): void {
		$dir = __DIR__;

		// Load base controller.
		require_once $dir . '/class-apollo-rest-controller.php';
	}

	/**
	 * Register scripts.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_scripts(): void {
		$version = defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : '2.0.0';

		wp_register_script(
			'apollo-api',
			plugins_url( 'assets/js/apollo-api.js', dirname( __DIR__ ) ),
			array(),
			$version,
			true
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Only enqueue when needed.
		if ( ! $this->should_load_api() ) {
			return;
		}

		wp_enqueue_script( 'apollo-api' );

		// Localize API configuration.
		wp_localize_script( 'apollo-api', 'apolloApiConfig', $this->get_api_config() );
	}

	/**
	 * Check if API should be loaded.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function should_load_api(): bool {
		// Always load in admin.
		if ( is_admin() ) {
			return true;
		}

		/**
		 * Filter whether to load the API scripts.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $load Whether to load API scripts.
		 */
		return apply_filters( 'apollo_load_api_scripts', true );
	}

	/**
	 * Get API configuration.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function get_api_config(): array {
		return array(
			'restUrl' => esc_url_raw( rest_url( 'apollo/v1' ) ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'userId'  => get_current_user_id(),
			'useRest' => $this->is_rest_available(),
			'locale'  => get_locale(),
			'i18n'    => array(
				'loading'      => __( 'Carregando...', 'apollo-core' ),
				'error'        => __( 'Erro ao carregar dados.', 'apollo-core' ),
				'noResults'    => __( 'Nenhum resultado encontrado.', 'apollo-core' ),
				'loadMore'     => __( 'Carregar mais', 'apollo-core' ),
				'unauthorized' => __( 'Você precisa estar logado.', 'apollo-core' ),
			),
		);
	}

	/**
	 * Check if REST API is available.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function is_rest_available(): bool {
		// Check if REST API is disabled.
		if ( defined( 'APOLLO_DISABLE_REST_API' ) && APOLLO_DISABLE_REST_API ) {
			return false;
		}

		/**
		 * Filter whether REST API is available.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $available Whether REST API is available.
		 */
		return apply_filters( 'apollo_rest_api_available', true );
	}
}

// Initialize.
REST_API_Loader::get_instance();
