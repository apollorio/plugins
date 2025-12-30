<?php

namespace Apollo\Modules\Classifieds;

/**
 * Classifieds Service Provider
 *
 * Registers the Classifieds module for Apollo Social.
 * Supports two domains: Ingressos (tickets) and Acomodação (accommodation)
 * with safety-first UX and internal chat integration.
 *
 * @package Apollo\Modules\Classifieds
 * @since 2.2.0
 */
class ClassifiedsServiceProvider {

	/**
	 * Register classifieds services
	 */
	public function register(): void {
		// Load the ClassifiedsModule
		if ( ! class_exists( ClassifiedsModule::class ) ) {
			require_once __DIR__ . '/ClassifiedsModule.php';
		}
	}

	/**
	 * Boot the classifieds module
	 */
	public function boot(): void {
		// Initialize the module
		ClassifiedsModule::init();

		// Register shortcodes
		add_shortcode( 'apollo_classifieds', array( $this, 'render_classifieds_shortcode' ) );
		add_shortcode( 'apollo_classified_form', array( $this, 'render_form_shortcode' ) );

		// Register AJAX endpoints for non-REST contexts
		add_action( 'wp_ajax_apollo_classifieds_search', array( $this, 'ajax_search' ) );
		add_action( 'wp_ajax_nopriv_apollo_classifieds_search', array( $this, 'ajax_search' ) );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue classifieds assets
	 */
	public function enqueue_assets(): void {
		if ( ! is_singular( ClassifiedsModule::POST_TYPE ) && ! is_post_type_archive( ClassifiedsModule::POST_TYPE ) ) {
			return;
		}

		wp_enqueue_style(
			'apollo-classifieds',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/classifieds.css',
			array(),
			APOLLO_SOCIAL_VERSION
		);

		wp_enqueue_script(
			'apollo-classifieds',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/classifieds.js',
			array( 'jquery' ),
			APOLLO_SOCIAL_VERSION,
			true
		);

		wp_localize_script( 'apollo-classifieds', 'apolloClassifieds', array(
			'restUrl'   => rest_url( 'apollo/v1' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'postType'  => ClassifiedsModule::POST_TYPE,
			'domains'   => ClassifiedsModule::DOMAINS,
			'intents'   => ClassifiedsModule::INTENTS,
			'i18n'      => array(
				'loading'     => __( 'Carregando...', 'apollo-social' ),
				'noResults'   => __( 'Nenhum anúncio encontrado.', 'apollo-social' ),
				'error'       => __( 'Erro ao carregar. Tente novamente.', 'apollo-social' ),
				'confirmDelete' => __( 'Tem certeza que deseja remover este anúncio?', 'apollo-social' ),
			),
		) );
	}

	/**
	 * Render classifieds directory shortcode
	 */
	public function render_classifieds_shortcode( array $atts = array() ): string {
		$atts = shortcode_atts( array(
			'domain'   => '',
			'intent'   => '',
			'per_page' => 12,
		), $atts );

		ob_start();
		include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/classifieds/shortcode-directory.php';
		return ob_get_clean();
	}

	/**
	 * Render classified form shortcode
	 */
	public function render_form_shortcode( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apollo-notice">' . __( 'Você precisa estar logado para criar anúncios.', 'apollo-social' ) . '</p>';
		}

		ob_start();
		include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/classifieds/shortcode-form.php';
		return ob_get_clean();
	}

	/**
	 * AJAX search handler
	 */
	public function ajax_search(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'apollo_classifieds_search' ) ) {
			wp_send_json_error( array( 'message' => __( 'Requisição inválida.', 'apollo-social' ) ) );
		}

		$request = new \WP_REST_Request( 'GET' );

		// Map AJAX params to REST params
		$params = array( 'page', 'per_page', 'domain', 'intent', 'search', 'location', 'date_from', 'date_to' );
		foreach ( $params as $param ) {
			if ( isset( $_POST[ $param ] ) ) {
				$request->set_param( $param, sanitize_text_field( wp_unslash( $_POST[ $param ] ) ) );
			}
		}

		$response = ClassifiedsModule::rest_list_classifieds( $request );
		$data     = $response->get_data();

		if ( $data['success'] ) {
			wp_send_json_success( $data['data'] );
		} else {
			wp_send_json_error( $data );
		}
	}
}
