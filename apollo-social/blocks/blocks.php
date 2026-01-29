<?php
/**
 * Apollo Social - Gutenberg Blocks Registration
 *
 * Registers all social-related Gutenberg blocks.
 *
 * @package Apollo_Social
 * @subpackage Blocks
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Social\Blocks;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Social Blocks
 *
 * Handles registration and management of social Gutenberg blocks.
 */
final class Apollo_Social_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Social_Blocks|null
	 */
	private static ?Apollo_Social_Blocks $instance = null;

	/**
	 * Blocks directory path.
	 *
	 * @var string
	 */
	private string $blocks_dir;

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Social_Blocks
	 */
	public static function get_instance(): Apollo_Social_Blocks {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->blocks_dir = plugin_dir_path( __FILE__ );
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'apollo_register_blocks', array( $this, 'register_blocks' ) );
		add_action( 'init', array( $this, 'register_blocks_directly' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register blocks via Apollo Core.
	 *
	 * @param object $manager Apollo Blocks Manager instance.
	 * @return void
	 */
	public function register_blocks( $manager ): void {
		$blocks = array(
			'social-feed',
			'social-share',
			'user-profile',
			'classifieds-grid',
		);

		foreach ( $blocks as $block ) {
			$block_path = $this->blocks_dir . $block;
			if ( file_exists( $block_path . '/block.json' ) ) {
				$manager->add_block( "apollo/{$block}", $block_path );
			}
		}
	}

	/**
	 * Register blocks directly if Core is not available.
	 *
	 * @return void
	 */
	public function register_blocks_directly(): void {
		// Skip if Core's block manager will handle registration.
		if ( function_exists( 'Apollo\\Core\\Blocks\\apollo_blocks' ) ) {
			return;
		}

		$blocks = array(
			'social-feed'      => 'render_social_feed',
			'social-share'     => 'render_social_share',
			'user-profile'     => 'render_user_profile',
			'classifieds-grid' => 'render_classifieds_grid',
		);

		foreach ( $blocks as $block => $render_callback ) {
			$block_path = $this->blocks_dir . $block;

			if ( file_exists( $block_path . '/block.json' ) ) {
				register_block_type(
					$block_path,
					array(
						'render_callback' => array( $this, $render_callback ),
					)
				);
			}
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$blocks = array( 'social-feed', 'social-share', 'user-profile', 'classifieds-grid' );

		foreach ( $blocks as $block ) {
			$script_path = $this->blocks_dir . "{$block}/build/index.js";
			$asset_path  = $this->blocks_dir . "{$block}/build/index.asset.php";

			if ( file_exists( $script_path ) ) {
				$asset = file_exists( $asset_path )
					? require $asset_path
					: array(
						'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
						'version'      => '1.0.0',
					);

				wp_enqueue_script(
					"apollo-{$block}-editor",
					plugins_url( "blocks/{$block}/build/index.js", __DIR__ ),
					$asset['dependencies'],
					$asset['version'],
					true
				);
			}
		}
	}

	/**
	 * Render social feed block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_social_feed( array $attributes ): string {
		$render_file = $this->blocks_dir . 'social-feed/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Render social share block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_social_share( array $attributes ): string {
		$render_file = $this->blocks_dir . 'social-share/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Render user profile block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_user_profile( array $attributes ): string {
		$render_file = $this->blocks_dir . 'user-profile/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Render classifieds grid block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_classifieds_grid( array $attributes ): string {
		$render_file = $this->blocks_dir . 'classifieds-grid/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}
}

// Initialize.
Apollo_Social_Blocks::get_instance();
