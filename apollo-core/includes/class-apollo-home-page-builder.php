<?php
/**
 * Apollo Home Page Builder
 *
 * Creates and manages the Apollo Home landing page on plugin activation.
 * Uses WordPress template system to display dynamic content from Apollo CPTs.
 * NO new CPTs, taxonomies, or meta keys - only connects existing data.
 *
 * @package Apollo_Core
 * @subpackage Home
 * @since 2.1.0
 */

declare(strict_types=1);

namespace Apollo_Core;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Home_Page_Builder
 *
 * Handles Home page creation and WordPress template setup.
 */
class Apollo_Home_Page_Builder {

	/**
	 * Home page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'home';

	/**
	 * Home page title.
	 *
	 * @var string
	 */
	const PAGE_TITLE = 'Home';

	/**
	 * Option name for storing home page ID.
	 *
	 * @var string
	 */
	const OPTION_PAGE_ID = 'apollo_home_page_id';

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Home_Page_Builder|null
	 */
	private static ?Apollo_Home_Page_Builder $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Home_Page_Builder
	 */
	public static function get_instance(): Apollo_Home_Page_Builder {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Register hooks.
		add_action( 'apollo_core_activated', array( $this, 'on_activation' ) );
		add_action( 'admin_init', array( $this, 'maybe_create_home_page' ) );

		// Register template include filter for plugin templates.
		add_filter( 'template_include', array( $this, 'load_home_template' ), 99 );

		// Register page-home.php in dropdown.
		add_filter( 'theme_page_templates', array( $this, 'register_home_template' ) );
	}

	/**
	 * Register home template in the page template dropdown.
	 *
	 * @param array $templates Available templates.
	 * @return array Modified templates.
	 */
	public function register_home_template( array $templates ): array {
		$templates['page-home.php'] = __( 'Apollo Home', 'apollo-core' );
		return $templates;
	}

	/**
	 * Load the home template from plugin directory.
	 *
	 * @param string $template Current template path.
	 * @return string Modified template path.
	 */
	public function load_home_template( string $template ): string {
		global $post;

		if ( ! $post ) {
			return $template;
		}

		// Check if this is the home page or uses page-home.php template.
		$page_template = get_post_meta( $post->ID, '_wp_page_template', true );
		$is_home_page  = ( $post->post_name === self::PAGE_SLUG ) || ( $page_template === 'page-home.php' );

		if ( ! $is_home_page ) {
			return $template;
		}

		// Look for template in plugin directory.
		$plugin_template = APOLLO_CORE_PLUGIN_DIR . 'templates/page-home.php';

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Initialize the home page builder.
	 *
	 * @return void
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Handle plugin activation.
	 *
	 * @return void
	 */
	public function on_activation(): void {
		$this->create_home_page();
	}

	/**
	 * Maybe create home page if it doesn't exist.
	 *
	 * This runs on admin_init to ensure the home page exists.
	 * Will create it if missing.
	 *
	 * @return void
	 */
	public function maybe_create_home_page(): void {
		$page_id = get_option( self::OPTION_PAGE_ID, 0 );

		// Check if page exists and is published.
		if ( $page_id && get_post_status( $page_id ) === 'publish' ) {
			return;
		}

		// Check if page exists by slug.
		$existing = get_page_by_path( self::PAGE_SLUG );
		if ( $existing && $existing->post_status === 'publish' ) {
			update_option( self::OPTION_PAGE_ID, $existing->ID );
			return;
		}

		// Page doesn't exist - create it now
		$this->create_home_page();
	}

	/**
	 * Create the Apollo Home page with WordPress template structure.
	 *
	 * @return int|false Page ID or false on failure.
	 */
	public function create_home_page() {
		// Check if page already exists.
		$existing = get_page_by_path( self::PAGE_SLUG );
		if ( $existing ) {
			update_option( self::OPTION_PAGE_ID, $existing->ID );
			return $existing->ID;
		}

		// Create the page.
		$page_content = $this->get_default_page_content();

		$page_data = array(
			'post_title'   => self::PAGE_TITLE,
			'post_name'    => self::PAGE_SLUG,
			'post_content' => $page_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id() ?: 1,
			'meta_input'   => array(
				'_wp_page_template' => 'page-home.php',
				'_apollo_home_page' => '1',
			),
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			return false;
		}

		// Store page ID.
		update_option( self::OPTION_PAGE_ID, $page_id );

		// Flush rewrite rules.
		flush_rewrite_rules();

		return $page_id;
	}

	/**
	 * Get default page content (fallback for non-template viewing).
	 *
	 * @return string Page content with shortcodes.
	 */
	private function get_default_page_content(): string {
		return <<<HTML
<!-- Apollo Home Page -->
<!-- This page uses the page-home.php template -->
<!-- Content is loaded via get_template_part() calls -->

<p>Bem-vindo ao Apollo::rio - Sua ferramenta de cultura digital para o Rio de Janeiro.</p>

<p>Esta página carrega automaticamente todas as seções usando o template WordPress modular.</p>
HTML;
	}

	/**
	 * Get Elementor data structure for the home page.
	 *
	 * @deprecated 2.1.0 - Now using WordPress template system instead of Elementor
	 * @return array Empty array since we use WordPress templates now.
	 */
	private function get_elementor_data(): array {
		// Deprecated: Now using WordPress template system (page-home.php)
		// All sections are loaded via get_template_part() calls
		return array();
	}

	/**
	 * Create Elementor section structure.
	 *
	 * @deprecated 2.1.0 - Now using WordPress template system
	 * @param string $name     Section name.
	 * @param array  $columns  Columns data.
	 * @param array  $settings Section settings.
	 * @return array Section data.
	 */
	private function create_section( string $name, array $columns, array $settings = array() ): array {
		// Deprecated: Now using WordPress template system
		return array();
	}

	/**
	 * Create Elementor column structure.
	 *
	 * @deprecated 2.1.0 - Now using WordPress template system
	 * @param string $width    Column width percentage.
	 * @param array  $elements Widget elements.
	 * @return array Column data.
	 */
	private function create_column( string $width, array $elements ): array {
		// Deprecated: Now using WordPress template system
		return array();
	}

	/**
	 * Generate unique Elementor element ID.
	 *
	 * @deprecated 2.1.0 - Now using WordPress template system
	 * @return string Element ID.
	 */
	private function generate_id(): string {
		// Deprecated: Now using WordPress template system
		return \substr( \md5( wp_rand() . \microtime() ), 0, 7 );
	}

	/**
	 * Get home page ID.
	 *
	 * @return int Page ID or 0.
	 */
	public static function get_home_page_id(): int {
		return (int) \get_option( self::OPTION_PAGE_ID, 0 );
	}

	/**
	 * Get home page URL.
	 *
	 * @return string Page URL or empty string.
	 */
	public static function get_home_page_url(): string {
		$page_id = self::get_home_page_id();
		return $page_id ? \get_permalink( $page_id ) : '';
	}
}

// Initialize.
add_action( 'plugins_loaded', array( '\Apollo_Core\Apollo_Home_Page_Builder', 'init' ), 15 );
