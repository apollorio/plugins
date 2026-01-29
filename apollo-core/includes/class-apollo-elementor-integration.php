<?php
/**
 * Apollo Elementor Integration
 *
 * Provides Elementor widget registration, category setup, and editor assets.
 * Only loads when Elementor is active.
 *
 * @package Apollo_Core
 * @subpackage Elementor
 * @since 2.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Elementor_Integration
 *
 * Handles Elementor integration for Apollo plugins.
 */
class Apollo_Elementor_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Elementor_Integration|null
	 */
	private static ?Apollo_Elementor_Integration $instance = null;

	/**
	 * Minimum Elementor version required.
	 *
	 * @var string
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.5.0';

	/**
	 * Minimum PHP version required.
	 *
	 * @var string
	 */
	const MINIMUM_PHP_VERSION = '8.1';

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Elementor_Integration
	 */
	public static function get_instance(): Apollo_Elementor_Integration {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Check if Elementor is installed and activated.
		if ( ! $this->is_elementor_active() ) {
			return;
		}

		// Check for required Elementor version.
		if ( ! $this->is_compatible() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		// Check for required PHP version.
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}

		// Register hooks.
		$this->register_hooks();
	}

	/**
	 * Check if Elementor is installed and activated.
	 *
	 * @return bool
	 */
	public function is_elementor_active(): bool {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Check Elementor version compatibility.
	 *
	 * @return bool
	 */
	public function is_compatible(): bool {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return false;
		}
		return version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' );
	}

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register widget categories.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_categories' ) );

		// Register editor styles.
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );

		// Register preview styles.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_styles' ) );

		// Register frontend styles.
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_frontend_styles' ) );

		// Add custom icons to icon control.
		add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'add_remix_icons' ) );

		// Fire action for other plugins to register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'fire_widget_registration' ), 5 );
	}

	/**
	 * Register Apollo widget categories.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_categories( $elements_manager ): void {
		// Main Apollo category.
		$elements_manager->add_category(
			'apollo',
			array(
				'title' => esc_html__( 'Apollo', 'apollo-core' ),
				'icon'  => 'eicon-apps',
			)
		);

		// Apollo Events category.
		$elements_manager->add_category(
			'apollo-events',
			array(
				'title' => esc_html__( 'Apollo Eventos', 'apollo-core' ),
				'icon'  => 'eicon-calendar',
			)
		);

		// Apollo Social category.
		$elements_manager->add_category(
			'apollo-social',
			array(
				'title' => esc_html__( 'Apollo Social', 'apollo-core' ),
				'icon'  => 'eicon-user-circle-o',
			)
		);

		// Apollo Performance category.
		$elements_manager->add_category(
			'apollo-performance',
			array(
				'title' => esc_html__( 'Apollo Performance', 'apollo-core' ),
				'icon'  => 'eicon-flash',
			)
		);
	}

	/**
	 * Enqueue editor styles.
	 *
	 * @return void
	 */
	public function enqueue_editor_styles(): void {
		// Remix Icons for widget icons.
		wp_enqueue_style(
			'remix-icons',
			'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css',
			array(),
			'4.5.0'
		);

		// Apollo Elementor editor styles.
		wp_enqueue_style(
			'apollo-elementor-editor',
			APOLLO_CORE_PLUGIN_URL . 'assets/css/elementor-editor.css',
			array( 'remix-icons' ),
			APOLLO_CORE_VERSION
		);

		// Inline styles for widget preview.
		wp_add_inline_style( 'apollo-elementor-editor', $this->get_editor_inline_styles() );
	}

	/**
	 * Enqueue preview styles.
	 *
	 * @return void
	 */
	public function enqueue_preview_styles(): void {
		// Core design system styles.
		if ( class_exists( 'Apollo_Assets_Loader' ) ) {
			Apollo_Assets_Loader::ensure_assets_loaded();
		}

		wp_enqueue_style(
			'remix-icons',
			'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css',
			array(),
			'4.5.0'
		);
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles(): void {
		// Only enqueue if Apollo widgets are used on the page.
		if ( ! $this->page_has_apollo_widgets() ) {
			return;
		}

		// Remix Icons.
		wp_enqueue_style(
			'remix-icons',
			'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css',
			array(),
			'4.5.0'
		);
	}

	/**
	 * Check if current page has Apollo widgets.
	 *
	 * @return bool
	 */
	private function page_has_apollo_widgets(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$document = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
		if ( ! $document ) {
			return false;
		}

		$data = $document->get_elements_data();
		return $this->check_elements_for_apollo( $data );
	}

	/**
	 * Recursively check elements for Apollo widgets.
	 *
	 * @param array $elements Elements data.
	 * @return bool
	 */
	private function check_elements_for_apollo( array $elements ): bool {
		foreach ( $elements as $element ) {
			if ( isset( $element['widgetType'] ) && str_starts_with( $element['widgetType'], 'apollo-' ) ) {
				return true;
			}
			if ( ! empty( $element['elements'] ) ) {
				if ( $this->check_elements_for_apollo( $element['elements'] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Add Remix Icons to Elementor icon control.
	 *
	 * @param array $tabs Existing icon tabs.
	 * @return array
	 */
	public function add_remix_icons( array $tabs ): array {
		$tabs['remix-icons'] = array(
			'name'          => 'remix-icons',
			'label'         => esc_html__( 'Remix Icons', 'apollo-core' ),
			'url'           => 'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css',
			'enqueue'       => array( 'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css' ),
			'prefix'        => 'ri-',
			'displayPrefix' => 'ri',
			'labelIcon'     => 'ri-remixicon-fill',
			'ver'           => '4.5.0',
			'fetchJson'     => APOLLO_CORE_PLUGIN_URL . 'assets/json/remix-icons.json',
			'native'        => false,
		);

		return $tabs;
	}

	/**
	 * Fire widget registration action.
	 *
	 * This allows other Apollo plugins to register their widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function fire_widget_registration( $widgets_manager ): void {
		/**
		 * Fires when Elementor is ready to register widgets.
		 *
		 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
		 */
		do_action( 'apollo_elementor_widgets_register', $widgets_manager );
	}

	/**
	 * Get inline editor styles.
	 *
	 * @return string
	 */
	private function get_editor_inline_styles(): string {
		return '
            /* Apollo Widget Category Icons */
            .elementor-element .eicon-apollo:before {
                font-family: "remixicon" !important;
                content: "\eb89"; /* ri-apps-2-fill */
            }

            /* Apollo widget icons in panel */
            .elementor-panel .elementor-element[data-widget_type^="apollo-"] .icon i {
                font-family: "remixicon" !important;
            }

            /* Category styling */
            .elementor-panel-category[data-category="apollo"] .elementor-panel-category-title,
            .elementor-panel-category[data-category="apollo-events"] .elementor-panel-category-title,
            .elementor-panel-category[data-category="apollo-social"] .elementor-panel-category-title {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
            }

            /* Widget preview styling */
            .elementor-widget-apollo-events-grid .elementor-widget-container,
            .elementor-widget-apollo-event-calendar .elementor-widget-container,
            .elementor-widget-apollo-social-feed .elementor-widget-container {
                min-height: 200px;
            }

            /* Apollo placeholder styling */
            .apollo-elementor-placeholder {
                background: #f8fafc;
                border: 2px dashed #cbd5e1;
                border-radius: 8px;
                padding: 2rem;
                text-align: center;
                color: #64748b;
            }

            .apollo-elementor-placeholder i {
                font-size: 2rem;
                margin-bottom: 0.5rem;
                display: block;
            }
        ';
	}

	/**
	 * Admin notice for minimum Elementor version.
	 *
	 * @return void
	 */
	public function admin_notice_minimum_elementor_version(): void {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
					esc_html__( '"%1$s" requer "%2$s" versão %3$s ou superior.', 'apollo-core' ),
					'<strong>' . esc_html__( 'Apollo Elementor Widgets', 'apollo-core' ) . '</strong>',
					'<strong>Elementor</strong>',
					self::MINIMUM_ELEMENTOR_VERSION
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin notice for minimum PHP version.
	 *
	 * @return void
	 */
	public function admin_notice_minimum_php_version(): void {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
					esc_html__( '"%1$s" requer "%2$s" versão %3$s ou superior.', 'apollo-core' ),
					'<strong>' . esc_html__( 'Apollo Elementor Widgets', 'apollo-core' ) . '</strong>',
					'<strong>PHP</strong>',
					self::MINIMUM_PHP_VERSION
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Helper method to register a widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @param string                     $widget_class    Widget class name.
	 * @return void
	 */
	public static function register_widget( $widgets_manager, string $widget_class ): void {
		if ( class_exists( $widget_class ) ) {
			$widgets_manager->register( new $widget_class() );
		}
	}
}

// Initialize on Elementor load.
add_action(
	'elementor/loaded',
	function () {
		Apollo_Elementor_Integration::get_instance();
	},
	10
);
