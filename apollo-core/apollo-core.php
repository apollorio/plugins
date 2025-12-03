<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Plugin Name: Apollo Core
 * Plugin URI: https://apollo.rio.br
 * Description: Core plugin for Apollo ecosystem - unifies Events Manager and Social features
 * Version: 1.0.0
 * Author: Apollo Team
 * Author URI: https://apollo.rio.br
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
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
define( 'APOLLO_CORE_VERSION', '1.0.0' );
define( 'APOLLO_CORE_PLUGIN_FILE', __FILE__ );
define( 'APOLLO_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_CORE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load Integration Bridge FIRST - provides shared utilities for all Apollo plugins.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/integration-bridge.php';

// Load Global Assets Manager - centralized UNI.CSS and global asset management.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-global-assets.php';

// Load helper functions.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/caching.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/settings-defaults.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/roles.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/db-schema.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/rest-rate-limiting.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/rest-moderation.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-api-response.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-email-security-log.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/auth-filters.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/memberships.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/rest-membership.php';

// Load forms system.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/forms/schema-manager.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/forms/render.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/forms/rest.php';

// Load quiz system.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/quiz/quiz-defaults.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/quiz/schema-manager.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/quiz/attempts.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/quiz/rest.php';

// Load unified moderation queue (auto-detects ALL pending posts).
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-moderation-queue-unified.php';

// Load CENA-RIO system (order matters - submissions/moderation depend on Roles).
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-cena-rio-roles.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-cena-rio-canvas.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-cena-rio-submissions.php';
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-cena-rio-moderation.php';

// Design Library (internal reference for AI assistant)
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-design-library.php';

// Load admin pages.
if ( is_admin() ) {
	require_once APOLLO_CORE_PLUGIN_DIR . 'admin/moderation-page.php';
	require_once APOLLO_CORE_PLUGIN_DIR . 'admin/forms-admin.php';
	require_once APOLLO_CORE_PLUGIN_DIR . 'admin/moderate-users-membership.php';
	require_once APOLLO_CORE_PLUGIN_DIR . 'admin/migration-page.php';
}

// Load public display.
require_once APOLLO_CORE_PLUGIN_DIR . 'public/display-membership.php';

// Load WP-CLI commands.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once APOLLO_CORE_PLUGIN_DIR . 'wp-cli/commands.php';
	require_once APOLLO_CORE_PLUGIN_DIR . 'wp-cli/memberships.php';
}

// Require autoloader.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-autoloader.php';

// Initialize autoloader.
$autoloader = new Apollo_Core_Autoloader();
$autoloader->register();

// Require main class.
require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-core.php';

// Mark core as bootstrapped for child plugins dependency check.
if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
	define( 'APOLLO_CORE_BOOTSTRAPPED', true );
}

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
