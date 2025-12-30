<?php
/**
 * Module Interface
 *
 * Defines the contract that all Apollo Events Manager modules must implement.
 * This ensures consistent module structure across the plugin.
 *
 * @package Apollo_Events_Manager
 * @subpackage Core
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Events\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Module_Interface
 *
 * All addon modules must implement this interface to ensure
 * consistent initialization and lifecycle management.
 *
 * @since 1.0.0
 */
interface Module_Interface {

	/**
	 * Get the unique module identifier.
	 *
	 * This should be a lowercase string with underscores (e.g., 'calendar_views').
	 *
	 * @since 1.0.0
	 *
	 * @return string Module unique identifier.
	 */
	public function get_id(): string;

	/**
	 * Get the human-readable module name.
	 *
	 * This is displayed in the admin settings panel.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module display name (translatable).
	 */
	public function get_name(): string;

	/**
	 * Get the module description.
	 *
	 * Brief description of what the module does.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module description (translatable).
	 */
	public function get_description(): string;

	/**
	 * Get the module version.
	 *
	 * Follows semantic versioning (e.g., '1.0.0').
	 *
	 * @since 1.0.0
	 *
	 * @return string Module version string.
	 */
	public function get_version(): string;

	/**
	 * Get module dependencies.
	 *
	 * Returns an array of module IDs that must be active for this module to work.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> Array of required module IDs.
	 */
	public function get_dependencies(): array;

	/**
	 * Check if the module is enabled by default.
	 *
	 * Core modules return true; optional addons return false.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if enabled by default, false otherwise.
	 */
	public function is_default_enabled(): bool;

	/**
	 * Initialize the module.
	 *
	 * Called when the module is loaded and active.
	 * Register hooks, filters, shortcodes, etc. here.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Register module assets (CSS/JS).
	 *
	 * Called during wp_enqueue_scripts and admin_enqueue_scripts.
	 * Use wp_register_script/wp_register_style here.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets(): void;

	/**
	 * Enqueue module assets for the current context.
	 *
	 * Called after register_assets when assets should be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context identifier ('admin', 'frontend', 'block_editor').
	 *
	 * @return void
	 */
	public function enqueue_assets( string $context ): void;

	/**
	 * Register REST API routes.
	 *
	 * Called during rest_api_init action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void;

	/**
	 * Register shortcodes.
	 *
	 * Called during init action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_shortcodes(): void;

	/**
	 * Register Gutenberg blocks.
	 *
	 * Called during init action if Gutenberg is available.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_blocks(): void;

	/**
	 * Register admin metaboxes.
	 *
	 * Called during add_meta_boxes action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_metaboxes(): void;

	/**
	 * Get module settings schema.
	 *
	 * Returns an array defining the settings fields for this module.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Settings schema.
	 */
	public function get_settings_schema(): array;

	/**
	 * Activate module callback.
	 *
	 * Called when the module is activated in settings.
	 * Use for database table creation, option initialization, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function on_activate(): void;

	/**
	 * Deactivate module callback.
	 *
	 * Called when the module is deactivated in settings.
	 * Do NOT delete data here; just cleanup transients, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function on_deactivate(): void;
}
