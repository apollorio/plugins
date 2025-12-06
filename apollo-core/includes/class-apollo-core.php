<?php
declare(strict_types=1);

/**
 * Main Apollo Core Class
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Core main class
 */
final class Apollo_Core {
	/**
	 * Single instance
	 *
	 * @var Apollo_Core
	 */
	private static $instance = null;

	/**
	 * Module loader
	 *
	 * @var Apollo_Core_Module_Loader
	 */
	public $modules;

	/**
	 * Canvas loader
	 *
	 * @var Apollo_Core_Canvas_Loader
	 */
	public $canvas;

	/**
	 * REST bootstrap
	 *
	 * @var Apollo_Core_Rest_Bootstrap
	 */
	public $rest;

	/**
	 * Permissions helper
	 *
	 * @var Apollo_Core_Permissions
	 */
	public $permissions;

	/**
	 * Get instance
	 */
	public static function instance() {
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
	 */
	private function includes() {
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-module-loader.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-canvas-loader.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-rest-bootstrap.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-permissions.php';
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-migration.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Initialize components.
		$this->modules     = new Apollo_Core_Module_Loader();
		$this->canvas      = new Apollo_Core_Canvas_Loader();
		$this->rest        = new Apollo_Core_Rest_Bootstrap();
		$this->permissions = new Apollo_Core_Permissions();

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
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'apollo-core', false, dirname( APOLLO_CORE_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Check if migration is needed
	 */
	private function check_migration() {
		$migration_version = get_option( 'apollo_core_migration_version', '0' );

		if ( version_compare( $migration_version, APOLLO_CORE_VERSION, '<' ) ) {
			// Migration needed - admin will trigger manually.
			add_action( 'admin_notices', array( $this, 'migration_notice' ) );
		}
	}

	/**
	 * Show migration notice
	 */
	public function migration_notice() {
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
	 */
	public static function activate() {
		require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-activation.php';
		Apollo_Core_Activation::activate();
	}

	/**
	 * Deactivation hook
	 */
	public static function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

