<?php
/**
 * Block Registry Singleton
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks;

/**
 * Class BlockRegistry
 *
 * Gerencia registro e acesso a todos os blocos HUB::rio
 */
class BlockRegistry {

	/**
	 * Singleton instance
	 *
	 * @var BlockRegistry
	 */
	private static $instance = null;

	/**
	 * Registered blocks
	 *
	 * @var BlockInterface[]
	 */
	private $blocks = array();

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->register_core_blocks();
	}

	/**
	 * Get singleton instance
	 *
	 * @return BlockRegistry
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a block
	 *
	 * @param BlockInterface $block Block instance
	 * @return void
	 */
	public function register( BlockInterface $block ) {
		$type = $block->get_type();

		if ( empty( $type ) ) {
			return;
		}

		$this->blocks[ $type ] = $block;
	}

	/**
	 * Get block by type
	 *
	 * @param string $type Block type
	 * @return BlockInterface|null
	 */
	public function get( $type ) {
		return isset( $this->blocks[ $type ] ) ? $this->blocks[ $type ] : null;
	}

	/**
	 * Get all registered blocks
	 *
	 * @return BlockInterface[]
	 */
	public function get_all() {
		return apply_filters( 'apollo_hub_blocks', $this->blocks );
	}

	/**
	 * Check if block type exists
	 *
	 * @param string $type Block type
	 * @return bool
	 */
	public function has( $type ) {
		return isset( $this->blocks[ $type ] );
	}

	/**
	 * Render block
	 *
	 * @param array $block_data Block data {id, type, ...props}
	 * @param array $profile Profile data
	 * @return string Rendered HTML
	 */
	public function render_block( $block_data, $profile ) {
		if ( empty( $block_data['type'] ) ) {
			return '';
		}

		$block = $this->get( $block_data['type'] );

		if ( ! $block ) {
			return '';
		}

		return $block->render( $block_data, $profile );
	}

	/**
	 * Get blocks for JavaScript
	 *
	 * @return array Array of block metadata [{type, name, icon, settings}]
	 */
	public function get_blocks_metadata() {
		$metadata = array();

		foreach ( $this->blocks as $block ) {
			$metadata[] = array(
				'type'     => $block->get_type(),
				'name'     => $block->get_name(),
				'icon'     => $block->get_icon(),
				'settings' => $block->get_settings(),
			);
		}

		return $metadata;
	}

	/**
	 * Register core blocks
	 *
	 * @return void
	 */
	private function register_core_blocks() {
		// Auto-load block classes
		$blocks_dir = __DIR__ . '/Types';

		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		$block_files = glob( $blocks_dir . '/*Block.php' );

		foreach ( $block_files as $file ) {
			$class_name = basename( $file, '.php' );
			$full_class = "Apollo_Social\\Builder\\Blocks\\Types\\{$class_name}";

			if ( class_exists( $full_class ) ) {
				$this->register( new $full_class() );
			}
		}
	}
}
