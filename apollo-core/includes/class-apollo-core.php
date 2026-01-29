<?php
/**
 * Main Apollo Core Class
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Core main class
 */
final class Core {

	/**
	 * Single instance
	 *
	 * @var Core|null
	 */
	private static ?Core $instance = null;

	/**
	 * Module loader
	 *
	 * @var Module_Loader
	 */
	public $modules;

	/**
	 * Canvas loader
	 *
	 * @var Canvas_Loader
	 */
	public $canvas;

	/**
	 * REST bootstrap
	 *
	 * @var Rest_Bootstrap
	 */
	public $rest;

	/**
	 * Permissions helper
	 *
	 * @var Permissions
	 */
	public $permissions;

	/**
	 * Get instance
	 *
	 * @return Core
	 */
	public static function instance(): Core {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	private function includes(): void {
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-module-loader.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-canvas-loader.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-rest-bootstrap.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-permissions.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-migration.php';

		// Home Page Builder - STRICT MODE auto-creates Home page on activation.
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-home-page-builder.php';

		// Home Widgets Loader - registers all Elementor widgets for Home page.
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/widgets/class-apollo-home-widgets-loader.php';
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Use priority 10 instead of 0 to ensure WordPress core is fully loaded.
		// Priority 0 can cause issues if WordPress core functions aren't ready yet.
		add_action( 'plugins_loaded', array( $this, 'init' ), 10 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Initialize plugin
	 *
	 * @return void
	 */
	public function init(): void {
		// Initialize components.
		$this->modules     = new Module_Loader();
		$this->canvas      = new Canvas_Loader();
		$this->rest        = new Rest_Bootstrap();
		$this->permissions = new Permissions();

		// Load modules.
		$this->modules->load();

		// Initialize REST API.
		$this->rest->init();

		// Initialize Canvas loader.
		$this->canvas->init();

		// Run migration check.
		$this->check_migration();

		do_action( 'apollo_core_loaded' );
	}

	/**
	 * Load text domain
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'apollo-core', false, dirname( APOLLO_CORE_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Check if migration is needed
	 *
	 * @return void
	 */
	private function check_migration(): void {
		$migration_version = get_option( 'apollo_core_migration_version', '0' );

		if ( version_compare( $migration_version, APOLLO_CORE_VERSION, '<' ) ) {
			// For fresh installs (migration version is '0'), set it to current version
			if ( $migration_version === '0' ) {
				update_option( 'apollo_core_migration_version', APOLLO_CORE_VERSION );
				return;
			}
			// Migration needed - admin will trigger manually.
			add_action( 'admin_notices', array( $this, 'migration_notice' ) );
		}
	}

	/**
	 * Show migration notice
	 *
	 * @return void
	 */
	public function migration_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Apollo Core migration available. Please visit the migration page to complete the update.', 'apollo-core' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-core-migration' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Run Migration', 'apollo-core' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Activation hook
	 *
	 * @return void
	 */
	public static function activate(): void {
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-activation.php';
		Activation::activate();
	}

	/**
	 * Deactivation hook
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
