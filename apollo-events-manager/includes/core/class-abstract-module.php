<?php
/**
 * Abstract Module Base
 *
 * Provides a base implementation of Module_Interface with sensible defaults.
 * Extend this class to create new modules with minimal boilerplate.
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
 * Abstract Class Abstract_Module
 *
 * Base class for all Apollo Events Manager modules.
 * Provides default implementations for optional interface methods.
 *
 * @since 1.0.0
 */
abstract class Abstract_Module implements Module_Interface {

	/**
	 * Module version.
	 *
	 * @var string
	 */
	protected string $version = '1.0.0';

	/**
	 * Whether module is enabled by default.
	 *
	 * @var bool
	 */
	protected bool $default_enabled = false;

	/**
	 * Module dependencies.
	 *
	 * @var array<string>
	 */
	protected array $dependencies = array();

	/**
	 * Module settings.
	 *
	 * @var array<string, mixed>
	 */
	protected array $settings = array();

	/**
	 * Get the module version.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module version string.
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get module dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> Array of required module IDs.
	 */
	public function get_dependencies(): array {
		return $this->dependencies;
	}

	/**
	 * Check if the module is enabled by default.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if enabled by default.
	 */
	public function is_default_enabled(): bool {
		return $this->default_enabled;
	}

	/**
	 * Register module assets.
	 *
	 * Override in child class if assets are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		// Default: no assets to register.
	}

	/**
	 * Enqueue module assets.
	 *
	 * Override in child class if assets are needed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context identifier.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $context ): void {
		// Default: no assets to enqueue.
	}

	/**
	 * Register REST API routes.
	 *
	 * Override in child class if REST routes are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		// Default: no REST routes.
	}

	/**
	 * Register shortcodes.
	 *
	 * Override in child class if shortcodes are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		// Default: no shortcodes.
	}

	/**
	 * Register Gutenberg blocks.
	 *
	 * Override in child class if blocks are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		// Default: no blocks.
	}

	/**
	 * Register admin metaboxes.
	 *
	 * Override in child class if metaboxes are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		// Default: no metaboxes.
	}

	/**
	 * Get module settings schema.
	 *
	 * Override in child class if settings are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Settings schema.
	 */
	public function get_settings_schema(): array {
		return array();
	}

	/**
	 * Activate module callback.
	 *
	 * Override in child class for activation logic.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function on_activate(): void {
		// Default: no activation logic.
	}

	/**
	 * Deactivate module callback.
	 *
	 * Override in child class for deactivation logic.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function on_deactivate(): void {
		// Default: no deactivation logic.
	}

	/**
	 * Get a module setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not set.
	 *
	 * @return mixed Setting value or default.
	 */
	protected function get_setting( string $key, $default = null ) {
		if ( empty( $this->settings ) ) {
			$this->settings = get_option( 'apollo_em_module_' . $this->get_id(), array() );
		}

		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Update a module setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 *
	 * @return bool True if updated, false otherwise.
	 */
	protected function update_setting( string $key, $value ): bool {
		$this->settings[ $key ] = $value;

		return update_option( 'apollo_em_module_' . $this->get_id(), $this->settings );
	}

	/**
	 * Log a debug message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (debug, info, warning, error).
	 *
	 * @return void
	 */
	protected function log( string $message, string $level = 'debug' ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$prefix = sprintf(
			'[Apollo Events][%s][%s] ',
			strtoupper( $level ),
			$this->get_id()
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $prefix . $message );
	}

	/**
	 * Get the module's asset URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Relative path to the asset.
	 *
	 * @return string Full URL to the asset.
	 */
	protected function get_asset_url( string $path ): string {
		return APOLLO_APRIO_URL . 'assets/modules/' . $this->get_id() . '/' . ltrim( $path, '/' );
	}

	/**
	 * Get the module's asset path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Relative path to the asset.
	 *
	 * @return string Full filesystem path to the asset.
	 */
	protected function get_asset_path( string $path ): string {
		return APOLLO_APRIO_PATH . 'assets/modules/' . $this->get_id() . '/' . ltrim( $path, '/' );
	}

	/**
	 * Get the module's template path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Template name without extension.
	 *
	 * @return string Full path to the template file.
	 */
	protected function get_template_path( string $template ): string {
		return APOLLO_APRIO_PATH . 'templates/modules/' . $this->get_id() . '/' . $template . '.php';
	}

	/**
	 * Render a module template.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $template Template name without extension.
	 * @param array<string,mixed> $args     Template arguments.
	 * @param bool                $echo     Whether to echo or return the output.
	 *
	 * @return string|void Template output if $echo is false.
	 */
	protected function render_template( string $template, array $args = array(), bool $echo = true ) {
		$template_path = $this->get_template_path( $template );

		if ( ! file_exists( $template_path ) ) {
			$this->log( sprintf( 'Template not found: %s', $template_path ), 'error' );
			return $echo ? null : '';
		}

		// Extract args for template use.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );

		if ( ! $echo ) {
			ob_start();
		}

		include $template_path;

		if ( ! $echo ) {
			return ob_get_clean();
		}
	}
}
