<?php
/**
 * Apollo Events Elementor Widgets Loader
 *
 * Registers all Elementor widgets for Apollo Events Manager.
 *
 * @package Apollo_Events_Manager
 * @subpackage Elementor
 * @since 2.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Events_Elementor_Widgets
 *
 * Handles Elementor widget registration for Events Manager.
 */
class Apollo_Events_Elementor_Widgets {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Events_Elementor_Widgets|null
	 */
	private static ?Apollo_Events_Elementor_Widgets $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Events_Elementor_Widgets
	 */
	public static function get_instance(): Apollo_Events_Elementor_Widgets {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Register widgets when Apollo fires the action.
		add_action( 'apollo_elementor_widgets_register', array( $this, 'register_widgets' ) );

		// Also hook directly to Elementor (fallback).
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 15 );
	}

	/**
	 * Register all event widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ): void {
		// Include widget files.
		$this->include_widget_files();

		// Register widgets.
		if ( class_exists( 'Apollo_Events_Grid_Widget' ) ) {
			$widgets_manager->register( new Apollo_Events_Grid_Widget() );
		}

		if ( class_exists( 'Apollo_Event_Single_Widget' ) ) {
			$widgets_manager->register( new Apollo_Event_Single_Widget() );
		}

		if ( class_exists( 'Apollo_Event_Calendar_Widget' ) ) {
			$widgets_manager->register( new Apollo_Event_Calendar_Widget() );
		}
	}

	/**
	 * Include widget class files.
	 *
	 * @return void
	 */
	private function include_widget_files(): void {
		$widgets_dir = __DIR__ . '/widgets/';

		// Include base widget if not loaded.
		if ( ! class_exists( 'Apollo_Base_Widget' ) ) {
			$base_widget = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-base-widget.php';
			if ( file_exists( $base_widget ) ) {
				require_once $base_widget;
			}
		}

		// Widget files.
		$widget_files = array(
			'class-apollo-events-grid-widget.php',
			'class-apollo-event-single-widget.php',
			'class-apollo-event-calendar-widget.php',
		);

		foreach ( $widget_files as $file ) {
			$filepath = $widgets_dir . $file;
			if ( file_exists( $filepath ) ) {
				require_once $filepath;
			}
		}
	}
}

// Initialize.
Apollo_Events_Elementor_Widgets::get_instance();
