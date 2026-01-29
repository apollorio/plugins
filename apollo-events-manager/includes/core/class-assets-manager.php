<?php
/**
 * Assets Manager
 *
 * Centralized asset management for Apollo Events Manager.
 * Handles script/style registration, versioning, and conditional loading.
 *
 * @package Apollo_Events_Manager
 * @subpackage Core
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Events\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets_Manager
 *
 * Manages all plugin assets with smart loading.
 *
 * @since 1.0.0
 */
final class Assets_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var Assets_Manager|null
	 */
	private static ?Assets_Manager $instance = null;

	/**
	 * Registered scripts.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $scripts = array();

	/**
	 * Registered styles.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $styles = array();

	/**
	 * Scripts to enqueue.
	 *
	 * @var array<string>
	 */
	private array $scripts_to_enqueue = array();

	/**
	 * Styles to enqueue.
	 *
	 * @var array<string>
	 */
	private array $styles_to_enqueue = array();

	/**
	 * Localized script data.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $localized_data = array();

	/**
	 * Private constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Nothing to initialize.
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Assets_Manager The instance.
	 */
	public static function get_instance(): Assets_Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a script.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $handle       Script handle.
	 * @param string               $src          Source URL.
	 * @param array<string>        $deps         Dependencies.
	 * @param string|null          $version      Version string.
	 * @param array<string, mixed> $args         Additional args (in_footer, strategy).
	 *
	 * @return void
	 */
	public function register_script(
		string $handle,
		string $src,
		array $deps = array(),
		?string $version = null,
		array $args = array()
	): void {
		$this->scripts[ $handle ] = array(
			'src'     => $src,
			'deps'    => $deps,
			'version' => $version ?? APOLLO_APRIO_VERSION,
			'args'    => wp_parse_args(
				$args,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			),
		);
	}

	/**
	 * Register a style.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $handle  Style handle.
	 * @param string        $src     Source URL.
	 * @param array<string> $deps    Dependencies.
	 * @param string|null   $version Version string.
	 * @param string        $media   Media query.
	 *
	 * @return void
	 */
	public function register_style(
		string $handle,
		string $src,
		array $deps = array(),
		?string $version = null,
		string $media = 'all'
	): void {
		$this->styles[ $handle ] = array(
			'src'     => $src,
			'deps'    => $deps,
			'version' => $version ?? APOLLO_APRIO_VERSION,
			'media'   => $media,
		);
	}

	/**
	 * Add localized data to a script.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $handle      Script handle.
	 * @param string               $object_name JS object name.
	 * @param array<string, mixed> $data        Data to localize.
	 *
	 * @return void
	 */
	public function localize_script( string $handle, string $object_name, array $data ): void {
		$this->localized_data[ $handle ] = array(
			'object_name' => $object_name,
			'data'        => $data,
		);
	}

	/**
	 * Enqueue a registered script.
	 *
	 * @since 1.0.0
	 *
	 * @param string $handle Script handle.
	 *
	 * @return void
	 */
	public function enqueue_script( string $handle ): void {
		if ( ! in_array( $handle, $this->scripts_to_enqueue, true ) ) {
			$this->scripts_to_enqueue[] = $handle;
		}
	}

	/**
	 * Enqueue a registered style.
	 *
	 * @since 1.0.0
	 *
	 * @param string $handle Style handle.
	 *
	 * @return void
	 */
	public function enqueue_style( string $handle ): void {
		if ( ! in_array( $handle, $this->styles_to_enqueue, true ) ) {
			$this->styles_to_enqueue[] = $handle;
		}
	}

	/**
	 * Process and output all enqueued assets.
	 *
	 * Call this during wp_enqueue_scripts or admin_enqueue_scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function process_enqueue(): void {
		// Register and enqueue scripts.
		foreach ( $this->scripts_to_enqueue as $handle ) {
			if ( isset( $this->scripts[ $handle ] ) ) {
				$script = $this->scripts[ $handle ];

				wp_register_script(
					$handle,
					$script['src'],
					$script['deps'],
					$script['version'],
					$script['args']
				);

				// Add localized data if exists.
				if ( isset( $this->localized_data[ $handle ] ) ) {
					wp_localize_script(
						$handle,
						$this->localized_data[ $handle ]['object_name'],
						$this->localized_data[ $handle ]['data']
					);
				}

				wp_enqueue_script( $handle );
			}
		}

		// Register and enqueue styles.
		foreach ( $this->styles_to_enqueue as $handle ) {
			if ( isset( $this->styles[ $handle ] ) ) {
				$style = $this->styles[ $handle ];

				wp_register_style(
					$handle,
					$style['src'],
					$style['deps'],
					$style['version'],
					$style['media']
				);

				wp_enqueue_style( $handle );
			}
		}
	}

	/**
	 * Get asset URL with cache busting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path Relative path from plugin assets folder.
	 *
	 * @return string Full URL with version.
	 */
	public function get_asset_url( string $relative_path ): string {
		return APOLLO_APRIO_URL . 'assets/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Get asset path for filesystem operations.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path Relative path from plugin assets folder.
	 *
	 * @return string Full filesystem path.
	 */
	public function get_asset_path( string $relative_path ): string {
		return APOLLO_APRIO_PATH . 'assets/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Check if an asset file exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path Relative path from plugin assets folder.
	 *
	 * @return bool True if exists.
	 */
	public function asset_exists( string $relative_path ): bool {
		return file_exists( $this->get_asset_path( $relative_path ) );
	}

	/**
	 * Register common plugin assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_common_assets(): void {
		// Common CSS.
		$this->register_style(
			'apollo-events-common',
			$this->get_asset_url( 'css/apollo-events-common.css' ),
			array(),
			APOLLO_APRIO_VERSION
		);

		// Common JS.
		$this->register_script(
			'apollo-events-common',
			$this->get_asset_url( 'js/apollo-events-common.js' ),
			array( 'jquery' ),
			APOLLO_APRIO_VERSION,
			array( 'in_footer' => true )
		);

		// Add common localized data.
		$this->localize_script(
			'apollo-events-common',
			'apolloEvents',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => rest_url( 'apollo/v1/' ),
				'nonce'     => wp_create_nonce( 'apollo_events_nonce' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'      => array(
					'loading'       => __( 'Carregando...', 'apollo-events-manager' ),
					'error'         => __( 'Erro ao processar.', 'apollo-events-manager' ),
					'success'       => __( 'Sucesso!', 'apollo-events-manager' ),
					'confirm'       => __( 'Tem certeza?', 'apollo-events-manager' ),
					'interested'    => __( 'Interessado', 'apollo-events-manager' ),
					'notInterested' => __( 'Tenho Interesse', 'apollo-events-manager' ),
				),
			)
		);
	}

	/**
	 * Reset the assets manager (for testing).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->scripts            = array();
		$this->styles             = array();
		$this->scripts_to_enqueue = array();
		$this->styles_to_enqueue  = array();
		$this->localized_data     = array();
	}
}
