<?php
/**
 * Apollo Events Manager - Gutenberg Blocks Registration
 *
 * Registers all event-related Gutenberg blocks.
 *
 * @package Apollo_Events_Manager
 * @subpackage Blocks
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Blocks;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Events Blocks
 *
 * Handles registration and management of event Gutenberg blocks.
 */
final class Apollo_Events_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Events_Blocks|null
	 */
	private static ?Apollo_Events_Blocks $instance = null;

	/**
	 * Blocks directory path.
	 *
	 * @var string
	 */
	private string $blocks_dir;

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Events_Blocks
	 */
	public static function get_instance(): Apollo_Events_Blocks {
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
			'events-grid',
			'event-single',
			'event-calendar',
			'featured-events',
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
			'events-grid'     => 'render_events_grid',
			'event-single'    => 'render_event_single',
			'event-calendar'  => 'render_event_calendar',
			'featured-events' => 'render_featured_events',
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
		$blocks = array( 'events-grid', 'event-single', 'event-calendar', 'featured-events' );

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
	 * Render events grid block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_events_grid( array $attributes ): string {
		$render_file = $this->blocks_dir . 'events-grid/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Render event single block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_event_single( array $attributes ): string {
		$render_file = $this->blocks_dir . 'event-single/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Render event calendar block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_event_calendar( array $attributes ): string {
		$render_file = $this->blocks_dir . 'event-calendar/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Render featured events block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_featured_events( array $attributes ): string {
		$render_file = $this->blocks_dir . 'featured-events/render.php';

		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		return '';
	}
}

// Initialize.
Apollo_Events_Blocks::get_instance();
