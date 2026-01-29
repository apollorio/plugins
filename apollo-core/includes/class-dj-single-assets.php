<?php

declare(strict_types=1);
/**
 * DJ Single Page Assets Handler
 * ==============================
 * Path: apollo-core/includes/class-dj-single-assets.php
 *
 * Handles enqueueing of CSS and JS assets for the DJ single page template.
 * Can be used both in standalone template mode and integrated WordPress mode.
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

namespace Apollo_Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class DJ_Single_Assets
 *
 * Manages asset loading for DJ single pages.
 */
class DJ_Single_Assets {

	/**
	 * Singleton instance
	 *
	 * @var DJ_Single_Assets|null
	 */
	private static $instance = null;

	/**
	 * CDN base URL
	 *
	 * @var string
	 */
	private $cdn_base = 'https://assets.apollo.rio.br/';

	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	private $plugin_url = '';

	/**
	 * Plugin path
	 *
	 * @var string
	 */
	private $plugin_path = '';

	/**
	 * Whether assets have been enqueued
	 *
	 * @var bool
	 */
	private $enqueued = false;

	/**
	 * Get singleton instance
	 *
	 * @return DJ_Single_Assets
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->plugin_url  = defined( 'APOLLO_CORE_PLUGIN_URL' )
			? APOLLO_CORE_PLUGIN_URL
			: plugin_dir_url( dirname( __DIR__ ) );

		$this->plugin_path = defined( 'APOLLO_CORE_PLUGIN_DIR' )
			? APOLLO_CORE_PLUGIN_DIR
			: plugin_dir_path( dirname( __DIR__ ) );
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ), 20 );
		add_action( 'apollo_dj_single_head_before', array( $this, 'head_meta' ) );
	}

	/**
	 * Conditionally enqueue assets
	 *
	 * @return void
	 */
	public function maybe_enqueue() {
		if ( ! is_singular( 'event_dj' ) ) {
			return;
		}

		$this->enqueue();
	}

	/**
	 * Enqueue all DJ single page assets
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( $this->enqueued ) {
			return;
		}

		// CDN Loader (provides base styles)
		wp_enqueue_script(
			'apollo-cdn-loader',
			$this->cdn_base . 'index.min.js',
			array(),
			'3.1.0',
			false // Load in head
		);

		// DJ Single Page CSS
		wp_enqueue_style(
			'apollo-dj-single',
			$this->plugin_url . 'assets/css/dj-single.css',
			array(),
			$this->get_file_version( 'assets/css/dj-single.css' )
		);

		// DJ Single Page JS
		wp_enqueue_script(
			'apollo-dj-single',
			$this->plugin_url . 'assets/js/dj-single.js',
			array(),
			$this->get_file_version( 'assets/js/dj-single.js' ),
			true // Load in footer
		);

		// SoundCloud API (conditional - only if DJ has SoundCloud link)
		$dj_id       = get_the_ID();
		$soundcloud  = get_post_meta( $dj_id, '_dj_soundcloud', true );
		$set_url     = get_post_meta( $dj_id, '_dj_set_url', true );

		if ( ! empty( $soundcloud ) || ! empty( $set_url ) ) {
			wp_enqueue_script(
				'soundcloud-api',
				'https://w.soundcloud.com/player/api.js',
				array(),
				null,
				false // Load in head for early availability
			);
		}

		// Localize script with data
		wp_localize_script(
			'apollo-dj-single',
			'apolloDJConfig',
			array(
				'djId'       => $dj_id,
				'djName'     => get_the_title( $dj_id ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'apollo_dj_single' ),
				'cdnBase'    => $this->cdn_base,
				'pluginUrl'  => $this->plugin_url,
				'i18n'       => array(
					'play'   => __( 'Play', 'apollo-core' ),
					'pause'  => __( 'Pause', 'apollo-core' ),
					'close'  => __( 'Fechar', 'apollo-core' ),
				),
			)
		);

		$this->enqueued = true;

		/**
		 * Action after DJ single assets are enqueued
		 *
		 * @param int $dj_id DJ post ID
		 */
		do_action( 'apollo_dj_single_assets_enqueued', $dj_id );
	}

	/**
	 * Add head meta tags
	 *
	 * @param int $dj_id DJ post ID
	 * @return void
	 */
	public function head_meta( $dj_id ) {
		if ( ! $dj_id ) {
			return;
		}

		$dj_name     = get_the_title( $dj_id );
		$dj_photo    = get_the_post_thumbnail_url( $dj_id, 'large' );
		$dj_excerpt  = get_the_excerpt( $dj_id );
		$dj_url      = get_permalink( $dj_id );

		// Open Graph
		?>
<meta property="og:title" content="<?php echo esc_attr( $dj_name ); ?> · Apollo Roster">
<meta property="og:description" content="<?php echo esc_attr( wp_trim_words( $dj_excerpt, 30 ) ); ?>">
<meta property="og:type" content="profile">
<meta property="og:url" content="<?php echo esc_url( $dj_url ); ?>">
<?php if ( $dj_photo ) : ?>
<meta property="og:image" content="<?php echo esc_url( $dj_photo ); ?>">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr( $dj_name ); ?> · Apollo Roster">
<meta name="twitter:description" content="<?php echo esc_attr( wp_trim_words( $dj_excerpt, 30 ) ); ?>">
<?php if ( $dj_photo ) : ?>
<meta name="twitter:image" content="<?php echo esc_url( $dj_photo ); ?>">
<?php endif; ?>
<?php
	}

	/**
	 * Get file version based on modification time
	 *
	 * @param string $relative_path Relative path to file
	 * @return string Version string
	 */
	private function get_file_version( $relative_path ) {
		$file_path = $this->plugin_path . $relative_path;

		if ( file_exists( $file_path ) ) {
			return (string) filemtime( $file_path );
		}

		return '2.0.0';
	}

	/**
	 * Get CDN base URL
	 *
	 * @return string
	 */
	public function get_cdn_base() {
		return $this->cdn_base;
	}

	/**
	 * Get plugin URL
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Register Elementor widget (if Elementor is active)
	 *
	 * @return void
	 */
	public function register_elementor_widget() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action(
			'elementor/widgets/register',
			function( $widgets_manager ) {
				// Widget file path
				$widget_file = $this->plugin_path . 'includes/widgets/class-dj-profile-widget.php';

				if ( file_exists( $widget_file ) ) {
					require_once $widget_file;
					$widgets_manager->register( new \Apollo_DJ_Profile_Widget() );
				}
			}
		);
	}

	/**
	 * Register Gutenberg block
	 *
	 * @return void
	 */
	public function register_gutenberg_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		add_action(
			'init',
			function() {
				register_block_type(
					'apollo/dj-profile',
					array(
						'attributes'      => array(
							'djId' => array(
								'type'    => 'number',
								'default' => 0,
							),
						),
						'render_callback' => array( $this, 'render_gutenberg_block' ),
					)
				);
			}
		);
	}

	/**
	 * Render Gutenberg block
	 *
	 * @param array $attributes Block attributes
	 * @return string HTML output
	 */
	public function render_gutenberg_block( $attributes ) {
		$dj_id = absint( $attributes['djId'] ?? 0 );

		if ( ! $dj_id ) {
			return '<p>' . esc_html__( 'Selecione um DJ para exibir.', 'apollo-core' ) . '</p>';
		}

		// Enqueue assets
		$this->enqueue();

		// Get DJ data
		$dj_post = get_post( $dj_id );
		if ( ! $dj_post || 'event_dj' !== $dj_post->post_type ) {
			return '<p>' . esc_html__( 'DJ não encontrado.', 'apollo-core' ) . '</p>';
		}

		// Build context and render
		ob_start();

		// Use shortcode handler for block rendering
		echo do_shortcode( '[apollo_dj_profile id="' . $dj_id . '"]' );

		return ob_get_clean();
	}
}

// Initialize
add_action(
	'plugins_loaded',
	function() {
		$instance = DJ_Single_Assets::get_instance();
		$instance->init();
		$instance->register_elementor_widget();
		$instance->register_gutenberg_block();
	}
);
