<?php
/**
 * Events Asset Loader Service
 *
 * Handles all asset enqueueing for Apollo Events Manager.
 * Extracted from the main plugin class to follow SRP.
 *
 * @package Apollo\Events\Services
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Services;

/**
 * Manages all CSS and JavaScript asset loading for events.
 */
final class EventsAssetLoader {

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_url Plugin URL.
	 * @param string $version    Plugin version.
	 */
	public function __construct( string $plugin_url, string $version ) {
		$this->plugin_url = $plugin_url;
		$this->version    = $version;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueFrontendAssets' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ), 10 );
		add_filter( 'style_loader_tag', array( $this, 'forceUniCssLast' ), 999, 2 );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueueFrontendAssets(): void {
		// Skip if not on events pages
		if ( ! $this->shouldLoadAssets() ) {
			return;
		}

		// Core CSS
		wp_enqueue_style(
			'apollo-events-core',
			$this->plugin_url . 'assets/css/events-core.css',
			array(),
			$this->version
		);

		// Main bundle (built with Vite/esbuild)
		if ( file_exists( APOLLO_APRIO_PATH . 'assets/dist/events-bundle.css' ) ) {
			wp_enqueue_style(
				'apollo-events-bundle',
				$this->plugin_url . 'assets/dist/events-bundle.css',
				array( 'apollo-events-core' ),
				$this->version
			);
		}

		// Unified CSS (canvas mode)
		if ( $this->isCanvasMode() ) {
			wp_enqueue_style(
				'apollo-events-uni',
				$this->plugin_url . 'assets/css/uni.css',
				array( 'apollo-events-core' ),
				$this->version
			);
		}

		// JavaScript
		wp_enqueue_script(
			'apollo-events-main',
			$this->plugin_url . 'assets/js/events-main.js',
			array( 'jquery', 'wp-util' ),
			$this->version,
			true
		);

		// Localize script
		wp_localize_script(
			'apollo-events-main',
			'apolloEvents',
			$this->getLocalizedData()
		);

		// Motion.dev animation library
		if ( $this->shouldLoadAnimations() ) {
			wp_enqueue_script(
				'motion-dev',
				$this->plugin_url . 'assets/vendor/motion.min.js',
				array(),
				'10.18.0',
				true
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function enqueueAdminAssets(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		// Only load on event-related screens
		$event_screens = array(
			'event_listing',
			'edit-event_listing',
			'toplevel_page_apollo-events',
			'eventos_page_apollo-events-analytics',
		);

		if ( ! in_array( $screen->id, $event_screens, true ) ) {
			return;
		}

		wp_enqueue_style(
			'apollo-events-admin',
			$this->plugin_url . 'assets/css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'apollo-events-admin',
			$this->plugin_url . 'assets/js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}

	/**
	 * Force uni.css to load last for proper cascade.
	 *
	 * @param string $html   The link tag HTML.
	 * @param string $handle The style handle.
	 * @return string
	 */
	public function forceUniCssLast( string $html, string $handle ): string {
		if ( 'apollo-events-uni' === $handle ) {
			// Remove existing and re-add at end
			remove_action( 'wp_head', 'wp_enqueue_scripts' );
		}
		return $html;
	}

	/**
	 * Check if we should load event assets.
	 *
	 * @return bool
	 */
	private function shouldLoadAssets(): bool {
		// Always load on events page
		if ( is_page( 'eventos' ) || is_singular( 'event_listing' ) ) {
			return true;
		}

		// Check for events shortcode in content
		global $post;
		if ( $post && has_shortcode( $post->post_content ?? '', 'apollo_events' ) ) {
			return true;
		}

		// Canvas mode
		if ( $this->isCanvasMode() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we're in canvas mode.
	 *
	 * @return bool
	 */
	private function isCanvasMode(): bool {
		return isset( $_GET['canvas'] ) || // phpcs:ignore WordPress.Security.NonceVerification
			( is_singular( 'event_listing' ) && get_post_meta( get_the_ID(), '_canvas_mode', true ) );
	}

	/**
	 * Check if animations should be loaded.
	 *
	 * @return bool
	 */
	private function shouldLoadAnimations(): bool {
		// Respect reduced motion preference
		$reduced_motion = isset( $_COOKIE['prefers-reduced-motion'] ) && 'true' === $_COOKIE['prefers-reduced-motion'];
		return ! $reduced_motion && apply_filters( 'apollo_events_load_animations', true );
	}

	/**
	 * Get localized script data.
	 *
	 * @return array<string, mixed>
	 */
	private function getLocalizedData(): array {
		return array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'restUrl'     => rest_url( 'apollo-events/v1/' ),
			'nonce'       => wp_create_nonce( 'apollo_events_nonce' ),
			'restNonce'   => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn'  => is_user_logged_in(),
			'userId'      => get_current_user_id(),
			'canvasMode'  => $this->isCanvasMode(),
			'debugMode'   => defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG,
			'i18n'        => array(
				'loading'     => __( 'Carregando...', 'apollo-events-manager' ),
				'error'       => __( 'Erro ao carregar', 'apollo-events-manager' ),
				'noResults'   => __( 'Nenhum evento encontrado', 'apollo-events-manager' ),
				'viewMore'    => __( 'Ver mais', 'apollo-events-manager' ),
				'interested'  => __( 'Interessado', 'apollo-events-manager' ),
				'going'       => __( 'Vou!', 'apollo-events-manager' ),
			),
		);
	}

	/**
	 * Dequeue conflicting theme assets in canvas mode.
	 *
	 * @return void
	 */
	public function dequeueThemeAssets(): void {
		if ( ! $this->isCanvasMode() ) {
			return;
		}

		// Get theme stylesheet handles
		$theme_styles = array(
			get_template() . '-style',
			get_stylesheet() . '-style',
			'theme-style',
			'main-style',
		);

		foreach ( $theme_styles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		// Remove admin bar styles
		wp_dequeue_style( 'admin-bar' );
	}
}
