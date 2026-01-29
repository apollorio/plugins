<?php
/**
 * Apollo Core - Gutenberg Blocks Registration
 *
 * Central registration point for all Apollo Gutenberg blocks.
 * Provides block category, editor assets, and shared utilities.
 *
 * @package Apollo_Core
 * @subpackage Blocks
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Blocks;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Blocks Manager
 *
 * Handles registration and management of all Apollo Gutenberg blocks.
 */
final class Apollo_Blocks_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Blocks_Manager|null
	 */
	private static ?Apollo_Blocks_Manager $instance = null;

	/**
	 * Registered blocks.
	 *
	 * @var array<string, array>
	 */
	private array $blocks = array();

	/**
	 * Block categories.
	 *
	 * @var array<string, array>
	 */
	private array $categories = array();

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Blocks_Manager
	 */
	public static function get_instance(): Apollo_Blocks_Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ), 10 );
		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'apollo_register_blocks', array( $this, 'collect_blocks' ), 5 );

		// Fire action for companion plugins to register their blocks.
		add_action( 'init', array( $this, 'init_companion_blocks' ), 5 );
	}

	/**
	 * Initialize companion plugin blocks.
	 *
	 * @return void
	 */
	public function init_companion_blocks(): void {
		/**
		 * Action: apollo_register_blocks
		 *
		 * Companion plugins should hook here to register their blocks.
		 *
		 * @param Apollo_Blocks_Manager $manager The blocks manager instance.
		 */
		do_action( 'apollo_register_blocks', $this );
	}

	/**
	 * Collect blocks from companion plugins.
	 *
	 * @return void
	 */
	public function collect_blocks(): void {
		// Core blocks are registered here.
		$this->register_core_blocks();
	}

	/**
	 * Register core Apollo blocks.
	 *
	 * @return void
	 */
	private function register_core_blocks(): void {
		// Core provides the shared infrastructure.
		// Individual blocks are registered by their respective plugins.

		// Register shared block patterns if any.
		$this->register_block_patterns();
	}

	/**
	 * Register block patterns.
	 *
	 * @return void
	 */
	private function register_block_patterns(): void {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		register_block_pattern_category(
			'apollo',
			array(
				'label' => __( 'Apollo', 'apollo-core' ),
			)
		);

		register_block_pattern_category(
			'apollo-events',
			array(
				'label' => __( 'Apollo Events', 'apollo-core' ),
			)
		);

		register_block_pattern_category(
			'apollo-social',
			array(
				'label' => __( 'Apollo Social', 'apollo-core' ),
			)
		);
	}

	/**
	 * Register a block.
	 *
	 * @param string $block_name   Block name (e.g., 'apollo/events-grid').
	 * @param string $block_path   Path to block.json directory.
	 * @param array  $args         Optional. Block registration arguments.
	 * @return void
	 */
	public function add_block( string $block_name, string $block_path, array $args = array() ): void {
		$this->blocks[ $block_name ] = array(
			'path' => $block_path,
			'args' => $args,
		);
	}

	/**
	 * Register all collected blocks.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		foreach ( $this->blocks as $block_name => $block_data ) {
			$block_json_path = trailingslashit( $block_data['path'] ) . 'block.json';

			if ( file_exists( $block_json_path ) ) {
				$result = register_block_type(
					$block_data['path'],
					$block_data['args']
				);

				if ( $result instanceof \WP_Block_Type ) {
					/**
					 * Action: apollo_block_registered
					 *
					 * Fires after a block is successfully registered.
					 *
					 * @param string         $block_name Block name.
					 * @param \WP_Block_Type $block      Block type instance.
					 */
					do_action( 'apollo_block_registered', $block_name, $result );
				}
			}
		}
	}

	/**
	 * Register block categories.
	 *
	 * @param array                    $categories Existing categories.
	 * @param \WP_Block_Editor_Context $context    Block editor context.
	 * @return array
	 */
	public function register_block_categories( array $categories, $context ): array {
		// Add Apollo category at the beginning.
		$apollo_categories = array(
			array(
				'slug'  => 'apollo',
				'title' => __( 'Apollo', 'apollo-core' ),
				'icon'  => 'apollo-icon',
			),
			array(
				'slug'  => 'apollo-events',
				'title' => __( 'Apollo Events', 'apollo-core' ),
				'icon'  => 'calendar-alt',
			),
			array(
				'slug'  => 'apollo-social',
				'title' => __( 'Apollo Social', 'apollo-core' ),
				'icon'  => 'groups',
			),
			array(
				'slug'  => 'apollo-performance',
				'title' => __( 'Apollo Performance', 'apollo-core' ),
				'icon'  => 'performance',
			),
		);

		return array_merge( $apollo_categories, $categories );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$editor_css_path = __DIR__ . '/editor.css';
		$editor_js_path  = __DIR__ . '/build/editor.js';

		// Editor styles.
		if ( file_exists( $editor_css_path ) ) {
			wp_enqueue_style(
				'apollo-blocks-editor',
				plugins_url( 'blocks/editor.css', __DIR__ ),
				array( 'wp-edit-blocks' ),
				filemtime( $editor_css_path )
			);
		}

		// Editor scripts.
		if ( file_exists( $editor_js_path ) ) {
			$asset_file = __DIR__ . '/build/editor.asset.php';
			$asset      = file_exists( $asset_file )
				? require $asset_file
				: array(
					'dependencies' => array(),
					'version'      => '1.0.0',
				);

			wp_enqueue_script(
				'apollo-blocks-editor',
				plugins_url( 'blocks/build/editor.js', __DIR__ ),
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_localize_script(
				'apollo-blocks-editor',
				'apolloBlocksData',
				$this->get_editor_data()
			);
		}

		// Enqueue Remix Icon for block icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);
	}

	/**
	 * Get editor localization data.
	 *
	 * @return array
	 */
	private function get_editor_data(): array {
		return array(
			'restUrl'    => rest_url( 'apollo/v1/' ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'siteUrl'    => site_url(),
			'taxonomies' => $this->get_taxonomies_for_editor(),
			'postTypes'  => $this->get_post_types_for_editor(),
			'i18n'       => $this->get_i18n_strings(),
		);
	}

	/**
	 * Get taxonomies for editor.
	 *
	 * @return array
	 */
	private function get_taxonomies_for_editor(): array {
		$taxonomies = array();

		$apollo_taxonomies = array(
			'event_listing_category',
			'event_listing_type',
			'event_sounds',
			'event_season',
			'classified_domain',
			'classified_intent',
		);

		foreach ( $apollo_taxonomies as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				);

				if ( ! is_wp_error( $terms ) ) {
					$taxonomies[ $taxonomy ] = array_map(
						fn( $term ) => array(
							'id'   => $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						),
						$terms
					);
				}
			}
		}

		return $taxonomies;
	}

	/**
	 * Get post types for editor.
	 *
	 * @return array
	 */
	private function get_post_types_for_editor(): array {
		$post_types = array();

		$apollo_post_types = array(
			'event_listing',
			'event_dj',
			'event_local',
			'apollo_classified',
			'apollo_social_post',
		);

		foreach ( $apollo_post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				$obj                      = get_post_type_object( $post_type );
				$post_types[ $post_type ] = array(
					'label'  => $obj->labels->singular_name,
					'labels' => $obj->labels,
				);
			}
		}

		return $post_types;
	}

	/**
	 * Get i18n strings for editor.
	 *
	 * @return array
	 */
	private function get_i18n_strings(): array {
		return array(
			'events'         => __( 'Events', 'apollo-core' ),
			'social'         => __( 'Social', 'apollo-core' ),
			'grid'           => __( 'Grid', 'apollo-core' ),
			'list'           => __( 'List', 'apollo-core' ),
			'carousel'       => __( 'Carousel', 'apollo-core' ),
			'columns'        => __( 'Columns', 'apollo-core' ),
			'limit'          => __( 'Number of items', 'apollo-core' ),
			'category'       => __( 'Category', 'apollo-core' ),
			'orderBy'        => __( 'Order by', 'apollo-core' ),
			'order'          => __( 'Order', 'apollo-core' ),
			'ascending'      => __( 'Ascending', 'apollo-core' ),
			'descending'     => __( 'Descending', 'apollo-core' ),
			'showImage'      => __( 'Show image', 'apollo-core' ),
			'showDate'       => __( 'Show date', 'apollo-core' ),
			'showExcerpt'    => __( 'Show excerpt', 'apollo-core' ),
			'showPagination' => __( 'Show pagination', 'apollo-core' ),
			'selectEvent'    => __( 'Select event', 'apollo-core' ),
			'noEventsFound'  => __( 'No events found.', 'apollo-core' ),
			'preview'        => __( 'Preview', 'apollo-core' ),
			'settings'       => __( 'Settings', 'apollo-core' ),
		);
	}

	/**
	 * Get registered blocks.
	 *
	 * @return array
	 */
	public function get_blocks(): array {
		return $this->blocks;
	}

	/**
	 * Check if a block is registered.
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	public function has_block( string $block_name ): bool {
		return isset( $this->blocks[ $block_name ] );
	}
}

/**
 * Get the blocks manager instance.
 *
 * @return Apollo_Blocks_Manager
 */
function apollo_blocks(): Apollo_Blocks_Manager {
	return Apollo_Blocks_Manager::get_instance();
}

// Initialize blocks manager.
apollo_blocks();
