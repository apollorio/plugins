<?php
/**
 * Apollo Core
 *
 * @package Apollo_Core
 * @license GPL-2.0-or-later
 *
 * Copyright (c) 2026 Apollo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 */

/**
 * Apollo Core Plugin
 *
 * @package Apollo_Core
 *
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
 * Requires at least: 6.4
 * Tested up to: 6.7
 * Requires PHP: 8.1
 */

declare(strict_types=1);


if (! defined('ABSPATH')) {
	exit;
}

// Define plugin constants with guards to prevent redefinition errors.
if (! defined('APOLLO_CORE_VERSION')) {
	define('APOLLO_CORE_VERSION', '1.0.0');
}
if (! defined('APOLLO_CORE_PLUGIN_FILE')) {
	define('APOLLO_CORE_PLUGIN_FILE', __FILE__);
}
if (! defined('APOLLO_CORE_PLUGIN_DIR')) {
	define('APOLLO_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (! defined('APOLLO_CORE_PLUGIN_URL')) {
	define('APOLLO_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (! defined('APOLLO_CORE_PLUGIN_BASENAME')) {
	define('APOLLO_CORE_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Define path constant for autoloader.
if (! defined('APOLLO_CORE_PATH')) {
	define('APOLLO_CORE_PATH', APOLLO_CORE_PLUGIN_DIR);
}

// CDN Constants - Centralized asset URLs (fix for undefined APOLLO_CDN_BASE issue)
if (! defined('APOLLO_CDN_BASE')) {
	define('APOLLO_CDN_BASE', 'https://assets.apollo.rio.br');
}
if (! defined('APOLLO_CDN_URL')) {
	define('APOLLO_CDN_URL', 'https://cdn.apollo.rio.br');
}
if (! defined('APOLLO_ASSETS_URL')) {
	define('APOLLO_ASSETS_URL', APOLLO_CDN_BASE);
}

/**
 * Feature Flags for Frontend Migration
 *
 * APOLLO_USE_CORE_TEMPLATES: When true, events-manager and other plugins
 * will serve templates from apollo-core instead of their own templates/ folder.
 * Default: false (safe rollback path).
 *
 * APOLLO_CANVAS_STRICT_MODE: When true, Canvas Mode removes ALL theme CSS/JS
 * and content filters. Set false for Elementor-compatible pages.
 * Default: true for Apollo-owned templates.
 */
if (! defined('APOLLO_USE_CORE_TEMPLATES')) {
	define('APOLLO_USE_CORE_TEMPLATES', false);
}

if (! defined('APOLLO_CANVAS_STRICT_MODE')) {
	define('APOLLO_CANVAS_STRICT_MODE', true);
}

/**
 * Log a message only once per request to prevent spam
 *
 * @param string $key     Unique identifier for this log entry.
 * @param string $message The message to log.
 * @param string $level   Log level: 'debug', 'info', 'warning', 'error'. Default 'debug'.
 * @return void
 */
function apollo_log_once($key, $message, $level = 'debug')
{
	static $logged = array();

	// Skip if not in debug mode (unless it's an error).
	if (! defined('WP_DEBUG') || ! WP_DEBUG) {
		if ('error' !== $level) {
			return;
		}
	}

	// Skip if already logged this request.
	if (isset($logged[$key])) {
		return;
	}

	$logged[$key] = true;

	// Format the message.
	$prefix = '[Apollo';
	switch ($level) {
		case 'error':
			$prefix .= ' ERROR';
			break;
		case 'warning':
			$prefix .= ' WARN';
			break;
		case 'info':
			$prefix .= ' INFO';
			break;
		default:
			$prefix .= ' DEBUG';
	}
	$prefix .= '] ';

	// Log using error_log (respects WP_DEBUG_LOG).
	if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
		error_log($prefix . $message);
	}
}

// Load autoloader FIRST - enables automatic class loading.
$autoloader = APOLLO_CORE_PLUGIN_DIR . 'src/class-apollo-autoloader.php';
if (file_exists($autoloader)) {
	require_once $autoloader;
}

// Load Apollo_Identifiers EARLY - single source of truth for all identifiers.
// Must be loaded before any other includes that might reference CPT slugs, table names, etc.
$identifiers = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-identifiers.php';
if (file_exists($identifiers)) {
	require_once $identifiers;
}

// Remove deprecated block template skip link for non-block themes.
remove_action('wp_footer', 'the_block_template_skip_link');

// Handle translations API errors gracefully in development.
add_filter(
	'translations_api',
	function ($result, $type, $args) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Only handle core translation requests.
		if ('core' !== $type) {
			return $result;
		}

		// If we already have a result, return it.
		if (! is_wp_error($result) && ! empty($result)) {
			return $result;
		}

		// Check if this is a connection error to WordPress.org.
		if (is_wp_error($result)) {
			$error_codes = $result->get_error_codes();
			if (
				in_array('http_request_failed', $error_codes, true) ||
				in_array('connect', $error_codes, true)
			) {
				// Return empty result to prevent warnings in development.
				return array('translations' => array());
			}
		}

		return $result;
	},
	10,
	3
);

// Cache empty translations response to prevent repeated API calls.
add_action(
	'init',
	function () {
		if (! wp_installing() && false === get_site_transient('available_translations')) {
			// Check if we can connect to WordPress.org.
			$test_connection = wp_remote_get(
				'https://api.wordpress.org/translations/core/1.0/',
				array(
					'timeout'     => 3,
					'redirection' => 1,
				)
			);

			if (is_wp_error($test_connection)) {
				// Can't connect, cache empty response for 24 hours to prevent repeated warnings.
				set_site_transient('available_translations', array(), DAY_IN_SECONDS);
			}
		}
	}
);

// Handle plugins API errors gracefully in development.
add_filter(
	'plugins_api',
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by WordPress filter signature.
	function ($result, $action, $args) {
		// If we already have a result, return it.
		if (! is_wp_error($result) && ! empty($result)) {
			return $result;
		}

		// Check if this is a connection error to WordPress.org.
		if (is_wp_error($result)) {
			$error_codes = $result->get_error_codes();
			if (
				in_array('http_request_failed', $error_codes, true) ||
				in_array('connect', $error_codes, true)
			) {
				// Return false to indicate no plugin info available.
				return false;
			}
		}

		return $result;
	},
	10,
	3
);

// Load Core Protection and Function Checker FIRST - ensures WordPress core integrity.
$core_checker = APOLLO_CORE_PLUGIN_DIR . 'includes/core-function-checker.php';
if (file_exists($core_checker)) {
	require_once $core_checker;
}

$core_protection = APOLLO_CORE_PLUGIN_DIR . 'includes/class-core-protection.php';
if (file_exists($core_protection)) {
	require_once $core_protection;
	if (class_exists('Apollo_Core\Core_Protection')) {
		Apollo_Core\Core_Protection::init();
	}
}

$wp_fix_helper = APOLLO_CORE_PLUGIN_DIR . 'includes/wp-core-fix-helper.php';
if (file_exists($wp_fix_helper)) {
	require_once $wp_fix_helper;
}

// Load Integration Bridge - provides shared utilities for all Apollo plugins.
$integration_bridge = APOLLO_CORE_PLUGIN_DIR . 'includes/integration-bridge.php';
if (file_exists($integration_bridge)) {
	require_once $integration_bridge;
}

// Load OOP Integration Bridge Class - structured integration points for companion plugins.
$integration_bridge_class = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-integration-bridge.php';
if (file_exists($integration_bridge_class)) {
	require_once $integration_bridge_class;
}

// Load CPT Registry - prevents duplicate CPT registration across plugins.
$cpt_registry = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-cpt-registry.php';
if (file_exists($cpt_registry)) {
	require_once $cpt_registry;
	if (function_exists('Apollo_Core\apollo_cpt_registry')) {
		Apollo_Core\apollo_cpt_registry();
	}
}

// Load Capabilities Manager - unified CPT capability mapping.
$capabilities = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-capabilities.php';
if (file_exists($capabilities)) {
	require_once $capabilities;
	if (function_exists('Apollo_Core\apollo_capabilities')) {
		Apollo_Core\apollo_capabilities();
	}
}

// Load Supplier Migration - migrates legacy 'supplier' to 'apollo_supplier'.
$supplier_migration = APOLLO_CORE_PLUGIN_DIR . 'includes/migrations/class-supplier-migration.php';
if (file_exists($supplier_migration)) {
	require_once $supplier_migration;
}

// Load Map Provider - centralized Leaflet tileset configuration (STRICT MODE).
$map_provider = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-map-provider.php';
if (file_exists($map_provider)) {
	require_once $map_provider;
}

// Load Global Assets Manager - centralized UNI.CSS and global asset management.
$global_assets = APOLLO_CORE_PLUGIN_DIR . 'includes/class-global-assets.php';
if (file_exists($global_assets)) {
	require_once $global_assets;
}

// Load Navbar Placeholder Data - example notifications and chat for development.
$navbar_placeholders = APOLLO_CORE_PLUGIN_DIR . 'includes/navbar-placeholder-data.php';
if (file_exists($navbar_placeholders)) {
	require_once $navbar_placeholders;
}

// Load AJAX Login Handler - navbar authentication.
$ajax_login = APOLLO_CORE_PLUGIN_DIR . 'includes/ajax-login-handler.php';
if (file_exists($ajax_login)) {
	require_once $ajax_login;
}

// Load Auth Routes - custom registration page route.
$auth_routes = APOLLO_CORE_PLUGIN_DIR . 'includes/auth-routes.php';
if (file_exists($auth_routes)) {
	require_once $auth_routes;
}

// Load Hide WP-Login - redirect wp-login.php to custom pages.
$hide_wp_login = APOLLO_CORE_PLUGIN_DIR . 'includes/hide-wp-login.php';
if (file_exists($hide_wp_login)) {
	require_once $hide_wp_login;
}

// Load Quiz Tracker - tracks registration quiz attempts.
$quiz_tracker = APOLLO_CORE_PLUGIN_DIR . 'includes/quiz-tracker.php';
if (file_exists($quiz_tracker)) {
	require_once $quiz_tracker;
}

// Load Security Enhancements - critical security features.
$security = APOLLO_CORE_PLUGIN_DIR . 'includes/security-enhancements.php';
if (file_exists($security)) {
	require_once $security;
}

// Load User Sounds System - database schema and handlers (Phase 3.2.0).
$user_sounds_schema = APOLLO_CORE_PLUGIN_DIR . 'includes/database/user-sounds-schema.php';
if (file_exists($user_sounds_schema)) {
	require_once $user_sounds_schema;
}

$user_sounds_handler = APOLLO_CORE_PLUGIN_DIR . 'includes/user/user-sounds-handler.php';
if (file_exists($user_sounds_handler)) {
	require_once $user_sounds_handler;
}

// Load Sounds Matchmaking - event-user matching via sound preferences.
$matchmaking = APOLLO_CORE_PLUGIN_DIR . 'includes/matchmaking/sounds-matchmaking.php';
if (file_exists($matchmaking)) {
	require_once $matchmaking;
}

// Load AJAX Registration Handler - registration with sounds selection.
$ajax_register = APOLLO_CORE_PLUGIN_DIR . 'includes/ajax/ajax-register-handler.php';
if (file_exists($ajax_register)) {
	require_once $ajax_register;
}

// Load Apollo Assets - unified local asset registration (replaces CDN).
$apollo_assets = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-assets.php';
if (file_exists($apollo_assets)) {
	require_once $apollo_assets;
	if (class_exists('Apollo_Core\Assets')) {
		Apollo_Core\Assets::init();
	}
}

// Load Apollo Snippets Manager - admin-managed CSS/JS snippets.
$snippets_manager = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-snippets-manager.php';
if (file_exists($snippets_manager)) {
	require_once $snippets_manager;
	if (class_exists('Apollo_Core\Snippets_Manager')) {
		Apollo_Core\Snippets_Manager::init();
	}
}

// Load Apollo Customizations - functions.php equivalent for Apollo pages.
$customizations = APOLLO_CORE_PLUGIN_DIR . 'customizations/apollo-customizations.php';
if (file_exists($customizations)) {
	require_once $customizations;
}

// Load Canvas Mode - unified theme isolation system for blank-canvas rendering.
$canvas_mode = APOLLO_CORE_PLUGIN_DIR . 'src/Canvas/class-apollo-canvas-mode.php';
if (file_exists($canvas_mode)) {
	require_once $canvas_mode;
}

// Load Navbar Apps Manager - admin panel for managing navbar apps modal.
$navbar_apps = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-navbar-apps.php';
if (file_exists($navbar_apps)) {
	require_once $navbar_apps;
}

// Load i18n Strict Mode - automatic language detection and English translation.
$i18n_strict = APOLLO_CORE_PLUGIN_DIR . 'src/I18n/ApolloStrictModeI18n.php';
if (file_exists($i18n_strict)) {
	require_once $i18n_strict;
	// Initialize early - before any text is output.
	add_action('plugins_loaded', function () {
		if (class_exists('Apollo_Core\I18n\ApolloStrictModeI18n')) {
			Apollo_Core\I18n\ApolloStrictModeI18n::get_instance()->init();
		}
	}, 1); // Priority 1 = very early.
}

// Apollo Navbar Locale Filter - handles user language preferences
add_action('plugins_loaded', function () {
	add_filter('locale', 'apollo_navbar_locale_filter');
}, 5);

function apollo_navbar_locale_filter($locale) {
    if (is_user_logged_in()) {
        $user_lang = get_user_meta(get_current_user_id(), 'apollo_preferred_language', true);
        if ($user_lang) {
            return $user_lang;
        }
    } else {
        if (isset($_COOKIE['apollo_language'])) {
            return sanitize_key($_COOKIE['apollo_language']);
        }
    }
    return $locale;
}

// Load helper functions.
$files = array(
	'caching.php',
	'settings-defaults.php',
	'roles.php',
	'class-apollo-rbac.php',
	'db-schema.php',
	'rest-rate-limiting.php',
	'rest-moderation.php',
	'class-api-response.php',
	'class-email-security-log.php',
	'auth-filters.php',
	'memberships.php',
	'rest-membership.php',
	'template-tags.php',
	'class-template-cache-manager.php',
	'class-asset-lazy-loader.php',
	'class-db-query-optimizer.php',
	'class-cdn-performance-monitor.php',
);

foreach ($files as $file) {
	$file_path = APOLLO_CORE_PLUGIN_DIR . 'includes/' . $file;
	if (file_exists($file_path)) {
		require_once $file_path;
	}
}

// Load forms system.
$form_files = array(
	'forms/schema-manager.php',
	'forms/render.php',
	'forms/rest.php',
);

foreach ($form_files as $file) {
	$file_path = APOLLO_CORE_PLUGIN_DIR . 'includes/' . $file;
	if (file_exists($file_path)) {
		require_once $file_path;
	}
}

// Load quiz system.
$quiz_files = array(
	'quiz/quiz-defaults.php',
	'quiz/schema-manager.php',
	'quiz/attempts.php',
	'quiz/rest.php',
);

foreach ($quiz_files as $file) {
	$file_path = APOLLO_CORE_PLUGIN_DIR . 'includes/' . $file;
	if (file_exists($file_path)) {
		require_once $file_path;
	}
}

// Load unified mod queue (auto-detects ALL pending posts).
$mod_queue = APOLLO_CORE_PLUGIN_DIR . 'includes/class-moderation-queue-unified.php';
if (file_exists($mod_queue)) {
	require_once $mod_queue;
}

// Load unified roles manager (SINGLE SOURCE OF TRUTH for all Apollo role management)
$roles_manager = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-roles-manager.php';
if (file_exists($roles_manager)) {
	require_once $roles_manager;
}

// Load CENA-RIO system (order matters - submissions/mod depend on Roles).
// NOTE: class-cena-rio-roles.php REMOVED - replaced by Apollo_Roles_Manager
$cena_rio_files = array(
	'class-cena-rio-canvas.php',
	'class-cena-rio-submissions.php',
	'class-cena-rio-moderation.php',
);

foreach ($cena_rio_files as $file) {
	$file_path = APOLLO_CORE_PLUGIN_DIR . 'includes/' . $file;
	if (file_exists($file_path)) {
		require_once $file_path;
	}
}

// Design Library (internal reference for AI assistant).
$design_lib = APOLLO_CORE_PLUGIN_DIR . 'includes/class-design-library.php';
if (file_exists($design_lib)) {
	require_once $design_lib;
}

// Load admin pages.
if (is_admin()) {
	$admin_files = array(
		'admin/moderation-page.php',
		'admin/forms-admin.php',
		'admin/moderate-users-membership.php',
		'admin/migration-page.php',
		'admin/admin-apollo-cabin.php', // FASE 3: Admin Cabin.
		'admin/class-apollo-unified-control-panel.php', // Unified Control Panel.
		'includes/class-user-visit-tracker.php',
	);

	foreach ($admin_files as $file) {
		$file_path = APOLLO_CORE_PLUGIN_DIR . $file;
		if (file_exists($file_path)) {
			require_once $file_path;
		}
	}
}

// Load public display.
$public_display = APOLLO_CORE_PLUGIN_DIR . 'public/display-membership.php';
if (file_exists($public_display)) {
	require_once $public_display;
}

// Load native integration classes (self-contained, no external plugin dependencies).
$integration_classes = array(
	// Native Push Notifications - uses Web Push API, no external plugins.
	'class-apollo-native-push.php',
	// Native SEO - meta tags, Open Graph, Schema.org, sitemaps, no external plugins.
	'class-apollo-native-seo.php',
	// Native Newsletter - subscriber management, campaigns, no external plugins.
	'class-apollo-native-newsletter.php',
	// Legacy wrappers for backward compatibility.
	'class-apollo-push-notifications.php',
	'class-apollo-seopress-integration.php',
	// Cookie consent (native).
	'class-apollo-cookie-consent.php',
	// Email system (modular, non-WooCommerce).
	'class-apollo-email-service.php',
	'class-apollo-email-templates-cpt.php',
	'class-apollo-email-admin-ui.php',
	'class-apollo-email-integration.php',
);

foreach ($integration_classes as $file) {
	$file_path = APOLLO_CORE_PLUGIN_DIR . 'includes/' . $file;
	if (file_exists($file_path)) {
		require_once $file_path;
	}
}

// FASE 1+2: Load user mod and modules configuration.
$user_mod = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-user-moderation.php';
if (file_exists($user_mod)) {
	require_once $user_mod;
}

$modules_config = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-modules-config.php';
if (file_exists($modules_config)) {
	require_once $modules_config;
}

// FASE 4: Cross-module integration (Social <-> Eventos <-> Bolha).
$cross_integration = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-cross-module-integration.php';
if (file_exists($cross_integration)) {
	require_once $cross_integration;
}

// Apollo Analytics - Self-hosted tracking system (no external dependencies).
$apollo_analytics = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-analytics.php';
if (file_exists($apollo_analytics)) {
	require_once $apollo_analytics;
}

// Apollo User Stats Widget - Frontend stats display.
$user_stats_widget = APOLLO_CORE_PLUGIN_DIR . 'includes/class-user-stats-widget.php';
if (file_exists($user_stats_widget)) {
	require_once $user_stats_widget;
}

// Apollo Interesse Ranking - Taxonomy scoring based on user interests.
$interesse_ranking = APOLLO_CORE_PLUGIN_DIR . 'includes/class-interesse-ranking.php';
if (file_exists($interesse_ranking)) {
	require_once $interesse_ranking;
}

// Apollo User Dashboard Interesse - Private user dashboard with top sounds.
$user_dashboard = APOLLO_CORE_PLUGIN_DIR . 'includes/class-user-dashboard-interesse.php';
if (file_exists($user_dashboard)) {
	require_once $user_dashboard;
}

// Apollo Communication Hub - Unified email, notifications, and forms system.
$communication_files = array(
	'includes/communication/traits/trait-rate-limiting.php',
	'includes/communication/traits/trait-logging.php',
	'includes/communication/traits/trait-validation.php',
	'includes/communication/email/class-email-manager.php',
	'includes/communication/notifications/class-notification-manager.php',
	'includes/communication/forms/class-form-manager.php',
	'includes/communication/class-communication-manager.php',
);

foreach ($communication_files as $file) {
	$file_path = APOLLO_CORE_PLUGIN_DIR . $file;
	if (file_exists($file_path)) {
		require_once $file_path;
	}
}

if (class_exists('Apollo\Communication\CommunicationManager')) {
	Apollo\Communication\CommunicationManager::instance()->init();
}

// Create global alias for Apollo_Notification_Manager to maintain backward compatibility
if (!class_exists('Apollo_Notification_Manager')) {
	class Apollo_Notification_Manager extends Apollo\Communication\Notifications\NotificationManager {
		private static $instance = null;

		public static function get_instance() {
			if (self::$instance === null) {
				self::$instance = Apollo\Communication\CommunicationManager::instance()->notifications();
			}
			return self::$instance;
		}

		public static function __callStatic($method, $args) {
			$instance = self::get_instance();
			if (method_exists($instance, $method)) {
				return call_user_func_array([$instance, $method], $args);
			}
			throw new \BadMethodCallException("Method $method does not exist on " . __CLASS__);
		}
	}
}

// Apollo Elementor Integration - Elementor widgets support for Apollo plugins.
$elementor_integration = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-elementor-integration.php';
if (file_exists($elementor_integration)) {
	require_once $elementor_integration;
	// Also load base widget class for child plugins.
	$base_widget = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-base-widget.php';
	if (file_exists($base_widget)) {
		require_once $base_widget;
	}
}

// Initialize mod and modules systems.
add_action(
	'plugins_loaded',
	function () {
		if (class_exists('Apollo_Core\User_Moderation')) {
			Apollo_Core\User_Moderation::init();
		}
		if (class_exists('Apollo_Core\Modules_Config')) {
			Apollo_Core\Modules_Config::init();
		}
	},
	5
);

// Load WP-CLI commands.
if (defined('WP_CLI') && WP_CLI) {
	$wp_cli_files = array(
		'wp-cli/commands.php',
		'wp-cli/memberships.php',
		'wp-cli/apollo-template-tests.php',
		'includes/cli/class-apollo-health-command.php', // Health check commands (S7).
	);

	foreach ($wp_cli_files as $file) {
		$file_path = APOLLO_CORE_PLUGIN_DIR . $file;
		if (file_exists($file_path)) {
			require_once $file_path;
		}
	}
}

// Require autoloader.
$autoloader_file = APOLLO_CORE_PLUGIN_DIR . 'includes/class-autoloader.php';
if (file_exists($autoloader_file)) {
	require_once $autoloader_file;

	// Initialize autoloader.
	if (class_exists('Apollo_Core\Autoloader')) {
		$autoloader = new Apollo_Core\Autoloader();
		$autoloader->register();
	}
}

// Load smoke tests for template validation (Phase 6).
$smoke_tests = APOLLO_CORE_PLUGIN_DIR . 'tests/apollo-smoke-tests.php';
if (file_exists($smoke_tests)) {
	require_once $smoke_tests;
}

// Require main class.
$main_class_file = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-core.php';
if (file_exists($main_class_file)) {
	require_once $main_class_file;
}

/**
 * Track an event with Apollo Analytics (self-hosted, no external dependencies).
 *
 * @param array $event_data The event data to track (should contain 'type', 'user_id', 'post_id', 'data').
 * @return void
 */
function apollo_track_event($event_data)
{
	// Use Apollo Analytics class for tracking
	if (class_exists('\\Apollo_Core\\Analytics')) {
		$event_type = $event_data['type'] ?? 'custom_event';
		$user_id    = $event_data['user_id'] ?? get_current_user_id();
		$post_id    = $event_data['post_id'] ?? 0;
		$extra_data = $event_data['data'] ?? $event_data;

		\Apollo_Core\Analytics::track_event($event_type, $user_id, $post_id, $extra_data);
	}

	// Also add inline script for client-side tracking
	wp_add_inline_script(
		'apollo-analytics-tracker',
		"
		if ( typeof window.apolloTrack === 'function' ) {
			window.apolloTrack('custom_event', " . wp_json_encode($event_data) . ');
		}
	'
	);
}

// ========================================
// ULTRA-PRO WordPress STRUCTURE INTEGRATION
// ========================================

/**
 * Initialize Ultra-Pro modules after core bootstrap.
 */
add_action(
	'plugins_loaded',
	function () {
		// Self-healing: Check Core Tables
		if (is_admin()) {
			global $wpdb;
			$test_table = $wpdb->prefix . 'apollo_mod_log';
			// Simple check if table exists
			if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $test_table)) !== $test_table) {
				// Initialize DB schema
				if (! function_exists('Apollo_Core\create_db_tables')) {
					$db_schema = APOLLO_CORE_PLUGIN_DIR . 'includes/db-schema.php';
					if (file_exists($db_schema)) {
						require_once $db_schema;
					}
				}

				if (function_exists('Apollo_Core\create_db_tables')) {
					\Apollo_Core\create_db_tables();
				}
			}

			// Check for Activity Log (v2 controller)
			$test_table_v2 = $wpdb->prefix . 'apollo_activity_log';
			if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $test_table_v2)) !== $test_table_v2) {
				$v2_controller = APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-activation-controller.php';
				if (file_exists($v2_controller)) {
					require_once $v2_controller;
					if (class_exists('Apollo_Core\Apollo_Activation_Controller')) {
						try {
							\Apollo_Core\Apollo_Activation_Controller::activate();
						} catch (\Exception $e) {
							error_log('Apollo Core V2 Activation failed: ' . $e->getMessage());
						}
					}
				}
			}
		}

		// Performance Module
		if (class_exists('\Apollo_Core\Performance\PerformanceModule')) {
			\Apollo_Core\Performance\PerformanceModule::getInstance()->init();
		}

		// SEO Module
		if (class_exists('\Apollo_Core\SEO\SEOModule')) {
			\Apollo_Core\SEO\SEOModule::getInstance()->init();
		}

		// Maintenance Module
		if (class_exists('\Apollo_Core\Maintenance\MaintenanceModule')) {
			\Apollo_Core\Maintenance\MaintenanceModule::getInstance()->init();
		}

		// Bridge Loader (connects all plugins)
		if (class_exists('\Apollo_Core\Bridge\BridgeLoader')) {
			\Apollo_Core\Bridge\BridgeLoader::getInstance()->init();
		}

		if (class_exists('Apollo\Communication\CommunicationManager')) {
			$GLOBALS['apollo_communication_manager'] = new \Apollo\Communication\CommunicationManager();
		}
	},
	5
);

// Mark core as bootstrapped for child plugins dependency check.
if (! defined('APOLLO_CORE_BOOTSTRAPPED')) {
	define('APOLLO_CORE_BOOTSTRAPPED', true);
}

// Register activation hook.
if (class_exists('Apollo_Core\Core')) {
	register_activation_hook(__FILE__, array('Apollo_Core\Core', 'activate'));

	// Register unified roles manager activation (PHASE 2)
	if (class_exists('Apollo_Core\Apollo_Roles_Manager')) {
		register_activation_hook(__FILE__, array('Apollo_Core\Apollo_Roles_Manager', 'activate'));
	}

	// Register quiz tracker table creation on activation.
	register_activation_hook(__FILE__, 'apollo_quiz_tracker_activation');

	// Register auth routes flush on activation.
	register_activation_hook(__FILE__, 'apollo_auth_flush_rewrite_rules');

	// Register user sounds table creation on activation (Phase 3.2.0).
	register_activation_hook(__FILE__, 'apollo_create_user_sounds_table');

	// Register deactivation hook.
	register_deactivation_hook(__FILE__, array('Apollo_Core\Core', 'deactivate'));

	// Initialize plugin.
	/**
	 * Returns the singleton instance of Apollo_Core.
	 *
	 * @return Apollo_Core\Core
	 */
	function apollo_core(): Apollo_Core\Core
	{
		return Apollo_Core\Core::instance();
	}

	// Start the plugin.
	apollo_core();

	// Load WP-CLI commands if available
	if (defined('WP_CLI') && WP_CLI) {
		require_once __DIR__ . '/includes/cli/class-apollo-health-command.php';
	}
}
