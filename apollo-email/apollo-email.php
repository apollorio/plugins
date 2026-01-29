<?php
/**
 * Plugin Name: Apollo Email Service
 * Plugin URI: https://apollo.rio.br
 * Description: Unified email service for Apollo platform - handles email queue, templates, SMTP, and notifications
 * Version: 1.0.0
 * Author: Apollo Team
 * Author URI: https://apollo.rio.br
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: apollo-email
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package ApolloEmail
 */

declare(strict_types=1);

namespace ApolloEmail;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants
 */
define( 'APOLLO_EMAIL_VERSION', '1.0.0' );
define( 'APOLLO_EMAIL_PLUGIN_FILE', __FILE__ );
define( 'APOLLO_EMAIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_EMAIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_EMAIL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader
 */
require_once APOLLO_EMAIL_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Main plugin class
 */
class ApolloEmailPlugin {

	/**
	 * Plugin instance
	 *
	 * @var ApolloEmailPlugin|null
	 */
	private static ?ApolloEmailPlugin $instance = null;

	/**
	 * Get plugin instance (singleton)
	 *
	 * @return ApolloEmailPlugin
	 */
	public static function get_instance(): ApolloEmailPlugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->check_dependencies();
		$this->init_hooks();
	}

	/**
	 * Check required dependencies
	 */
	private function check_dependencies(): void {
		// Check if apollo-core is active.
		if ( ! class_exists( 'Apollo_Core' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_apollo_core_notice' ) );
			return;
		}
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		// Activation/deactivation hooks.
		register_activation_hook( APOLLO_EMAIL_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( APOLLO_EMAIL_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );

		// Load text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Dev routes (only in WP_DEBUG mode).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action( 'init', array( $this, 'register_dev_routes' ) );
			add_filter( 'query_vars', array( $this, 'add_dev_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'handle_dev_routes' ) );
		}
	}

	/**
	 * Plugin activation
	 */
	public function activate(): void {
		// Create database tables.
		Schema\EmailSchema::create_tables();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		update_option( 'apollo_email_activated', current_time( 'mysql' ) );
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Initialize plugin components
	 */
	public function init(): void {
		// Initialize services.
		UnifiedEmailService::get_instance();
		Queue\QueueProcessor::get_instance();
		Templates\TemplateManager::get_instance();
		Admin\EmailHubAdmin::get_instance();
	}

	/**
	 * Load plugin text domain for translations
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'apollo-email',
			false,
			dirname( APOLLO_EMAIL_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register dev routes
	 */
	public function register_dev_routes(): void {
		add_rewrite_rule( '^dev/email/?$', 'index.php?apollo_dev_email=1', 'top' );
	}

	/**
	 * Add dev query vars
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_dev_query_vars( array $vars ): array {
		$vars[] = 'apollo_dev_email';
		return $vars;
	}

	/**
	 * Handle dev routes
	 */
	public function handle_dev_routes(): void {
		if ( get_query_var( 'apollo_dev_email' ) ) {
			require_once APOLLO_EMAIL_PLUGIN_DIR . 'dev/test-email.php';
			exit;
		}
	}

	/**
	 * Admin notice: Missing apollo-core
	 */
	public function missing_apollo_core_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Apollo Email Service', 'apollo-email' ); ?>:</strong>
				<?php esc_html_e( 'Requires Apollo Core plugin to be installed and activated.', 'apollo-email' ); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * Initialize plugin
 */
function apollo_email() {
	return ApolloEmailPlugin::get_instance();
}

// Kick off the plugin.
apollo_email();
