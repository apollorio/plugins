<?php
/**
 * Plugin Name: Apollo Core
 * Plugin URI: https://apollo.rio.br
 * Description: Core plugin for Apollo ecosystem - unifies Events Manager and Social features
 * Version: 3.0.0
 * Author: Apollo Team
 * Author URI: https://apollo.rio.br
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apollo-core
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'APOLLO_CORE_VERSION', '3.0.0' );
define( 'APOLLO_CORE_PLUGIN_FILE', __FILE__ );
define( 'APOLLO_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_CORE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load helper functions.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/settings-defaults.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/roles.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/db-schema.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/rest-moderation.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/auth-filters.php';

// Load admin pages.
if ( is_admin() ) {
	require_once APOLLO_CORE_PLUGIN_DIR . 'admin/moderation-page.php';
}

// Load WP-CLI commands.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once APOLLO_CORE_PLUGIN_DIR . 'wp-cli/commands.php';
}

// Require autoloader.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-autoloader.php';

// Initialize autoloader.
$autoloader = new Apollo_Core_Autoloader();
$autoloader->register();

// Require main class.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-core.php';

// Register activation hook.
register_activation_hook( __FILE__, array( 'Apollo_Core', 'activate' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( 'Apollo_Core', 'deactivate' ) );

// Initialize plugin.
function apollo_core() {
	return Apollo_Core::instance();
}

// Start the plugin.
apollo_core();

