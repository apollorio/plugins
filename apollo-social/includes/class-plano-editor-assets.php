<?php
/**
 * Plano Editor Assets Manager
 *
 * Handles enqueuing of local assets for the Plano editor (no CDN dependencies)
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Plano_Editor_Assets
 */
class Apollo_Plano_Editor_Assets {

	/**
	 * Initialize asset enqueuing
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
	}

	/**
	 * Enqueue all Plano editor assets
	 */
	public static function enqueue_assets() {
		// Only enqueue on plano editor pages
		if ( ! self::is_plano_editor_page() ) {
			return;
		}

		// Enqueue UNI.CSS (Apollo design system)
		apollo_enqueue_global_assets( 'css' );

		// Enqueue Remixicon (local)
		self::enqueue_remixicon();

		// Enqueue Plano editor CSS
		self::enqueue_plano_css();

		// Enqueue JavaScript libraries (local)
		self::enqueue_js_libraries();

		// Enqueue Plano editor JS
		self::enqueue_plano_js();

		// Localize script with REST endpoints
		self::localize_script();
	}

	/**
	 * Check if current page is Plano editor
	 *
	 * @return bool
	 */
	private static function is_plano_editor_page() {
		// Check for plano editor routes
		$is_plano = false;

		// Check query vars
		global $wp_query;
		if ( isset( $wp_query->query_vars['apollo_route'] ) ) {
			$route    = $wp_query->query_vars['apollo_route'];
			$is_plano = in_array( $route, array( 'plano_editor', 'studio', 'plano' ), true );
		}

		// Check URL patterns
		if ( ! $is_plano ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$is_plano    = (
				strpos( $request_uri, '/studio/' ) !== false ||
				strpos( $request_uri, '/plano/' ) !== false ||
				( strpos( $request_uri, '/id/' ) !== false && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}

		return apply_filters( 'apollo_is_plano_editor_page', $is_plano );
	}

	/**
	 * Enqueue Remixicon fonts (local ONLY - NO CDN)
	 *
	 * @throws \Exception If local file not found.
	 */
	private static function enqueue_remixicon() {
		$remixicon_css  = APOLLO_SOCIAL_PLUGIN_URL . 'assets/fonts/remixicon.css';
		$remixicon_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/fonts/remixicon.css';

		if ( ! file_exists( $remixicon_path ) ) {
			// NO CDN FALLBACK - Show admin notice instead.
			add_action( 'admin_notices', array( __CLASS__, 'missing_remixicon_notice' ) );
			return;
		}

		wp_enqueue_style(
			'apollo-remixicon-local',
			$remixicon_css,
			array(),
			APOLLO_SOCIAL_VERSION
		);
	}

	/**
	 * Admin notice for missing Remixicon
	 */
	public static function missing_remixicon_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Apollo Plano Editor', 'apollo-social' ); ?>:</strong>
				<?php esc_html_e( 'Remixicon local não encontrado. Execute o script de download de assets.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue Plano editor CSS
	 */
	private static function enqueue_plano_css() {
		wp_enqueue_style(
			'apollo-plano-editor',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/canvas-plano.css',
			array( 'apollo-uni-css', 'apollo-remixicon-local' ),
			APOLLO_SOCIAL_VERSION
		);
	}

	/**
	 * Enqueue JavaScript libraries (local ONLY - NO CDN)
	 */
	private static function enqueue_js_libraries() {
		// Fabric.js - REQUIRED, NO CDN FALLBACK.
		$fabric_js   = APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/fabric.min.js';
		$fabric_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/fabric.min.js';

		if ( ! file_exists( $fabric_path ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_fabric_notice' ) );
			return;
		}

		wp_enqueue_script(
			'fabric-js',
			$fabric_js,
			array(),
			'5.3.0',
			true
		);

		// html2canvas - REQUIRED, NO CDN FALLBACK.
		$html2canvas_js   = APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/html2canvas.min.js';
		$html2canvas_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/html2canvas.min.js';

		if ( ! file_exists( $html2canvas_path ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_html2canvas_notice' ) );
			return;
		}

		wp_enqueue_script(
			'html2canvas',
			$html2canvas_js,
			array(),
			'1.4.1',
			true
		);

		// Sortable.js (optional, for drag-and-drop).
		$sortable_js   = APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/sortable.min.js';
		$sortable_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/sortable.min.js';

		if ( file_exists( $sortable_path ) ) {
			wp_enqueue_script(
				'sortable-js',
				$sortable_js,
				array(),
				'1.15.0',
				true
			);
		}
	}

	/**
	 * Admin notice for missing Fabric.js
	 */
	public static function missing_fabric_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Apollo Plano Editor', 'apollo-social' ); ?>:</strong>
				<?php esc_html_e( 'Fabric.js local não encontrado. Execute o script de download de assets.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin notice for missing html2canvas
	 */
	public static function missing_html2canvas_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Apollo Plano Editor', 'apollo-social' ); ?>:</strong>
				<?php esc_html_e( 'html2canvas local não encontrado. Execute o script de download de assets.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue Plano editor JavaScript
	 */
	private static function enqueue_plano_js() {
		wp_enqueue_script(
			'apollo-plano-editor',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/canvas-plano.js',
			array( 'fabric-js', 'html2canvas', 'jquery' ),
			APOLLO_SOCIAL_VERSION,
			true
		);

		// Enqueue CanvasTools if exists
		$canvas_tools = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/CanvasTools.js';
		if ( file_exists( $canvas_tools ) ) {
			wp_enqueue_script(
				'apollo-canvas-tools',
				APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/CanvasTools.js',
				array( 'fabric-js', 'apollo-plano-editor' ),
				APOLLO_SOCIAL_VERSION,
				true
			);
		}
	}

	/**
	 * Localize script with REST endpoints and data
	 */
	private static function localize_script() {
		wp_localize_script(
			'apollo-plano-editor',
			'planoRest',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'rest_url'     => get_rest_url( null, 'apollo/v1/' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'assets_url'   => APOLLO_SOCIAL_PLUGIN_URL . 'assets/',
				'textures_url' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/img/textures/',
				'stickers_url' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/img/stickers/',
			)
		);
	}
}

// Initialize
Apollo_Plano_Editor_Assets::init();
