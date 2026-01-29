<?php
/**
 * Apollo Social Elementor Widgets Loader
 *
 * Registers all Elementor widgets for Apollo Social plugin.
 *
 * @package Apollo_Social
 * @subpackage Elementor
 * @since 1.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Social_Elementor_Widgets
 *
 * Handles registration of all Apollo Social Elementor widgets.
 */
class Apollo_Social_Elementor_Widgets {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Hook into Apollo Core's Elementor integration.
		add_action( 'apollo_elementor_widgets_register', array( $this, 'register_widgets' ) );

		// Fallback: register directly if apollo-core not active.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register all Apollo Social widgets.
	 *
	 * @param \Elementor\Widgets_Manager|null $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager = null ): void {
		// Include widget files.
		$this->include_widgets();

		// Get widgets manager.
		if ( ! $widgets_manager && did_action( 'elementor/loaded' ) ) {
			$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
		}

		if ( ! $widgets_manager ) {
			return;
		}

		// Register widgets.
		$widgets = array(
			'Apollo_Social_Feed_Widget',
			'Apollo_Social_Share_Widget',
			'Apollo_User_Profile_Widget',
			'Apollo_Classifieds_Grid_Widget',
		);

		foreach ( $widgets as $widget_class ) {
			if ( class_exists( $widget_class ) ) {
				$widgets_manager->register( new $widget_class() );
			}
		}
	}

	/**
	 * Include widget files.
	 *
	 * @return void
	 */
	private function include_widgets(): void {
		$widgets_dir = plugin_dir_path( __FILE__ ) . 'widgets/';

		$widgets = array(
			'class-apollo-social-feed-widget.php',
			'class-apollo-social-share-widget.php',
			'class-apollo-user-profile-widget.php',
			'class-apollo-classifieds-grid-widget.php',
		);

		foreach ( $widgets as $widget_file ) {
			$file_path = $widgets_dir . $widget_file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}
}

// Initialize.
Apollo_Social_Elementor_Widgets::get_instance();
