<?php

declare(strict_types=1);
/**
 * Apollo Canvas Mode - Unified Theme Isolation System
 *
 * Provides a centralized mechanism for creating "blank canvas" pages
 * where theme CSS/JS is blocked and only Apollo assets are allowed.
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Class Apollo_Canvas_Mode
 *
 * Centralizes theme isolation logic previously scattered across
 * apollo-events-manager and apollo-rio plugins.
 *
 * Usage:
 *   Apollo_Canvas_Mode::enable( [ 'strict' => true ] );
 *
 * Filters:
 *   - apollo_canvas_allowed_styles  : Add style handles to keep
 *   - apollo_canvas_allowed_scripts : Add script handles to keep
 *   - apollo_canvas_strict_mode     : Override strict mode per-page
 *   - apollo_canvas_remove_content_filters : Control content filter removal
 */
class Apollo_Canvas_Mode
{

	/**
	 * Whether canvas mode is currently enabled
	 *
	 * @var bool
	 */
	private static $enabled = false;

	/**
	 * Current mode configuration
	 *
	 * @var array
	 */
	private static $config = array();

	/**
	 * Default allowed style handles
	 *
	 * @var array
	 */
	private static $default_allowed_styles = array(
		// Apollo assets.
		'apollo-uni-css',
		'apollo-compat-css',
		'apollo-event-modal-css',
		'apollo-infinite-scroll-css',
		'apollo-pwa-templates',
		'apollo-social',
		'apollo-events',
		'apollo-rio',
		// External dependencies.
		'remixicon',
		'leaflet-css',
		'leaflet',
		// WordPress core.
		'admin-bar',
		'dashicons',
		'wp-block-library',
	);

	/**
	 * Default allowed script handles
	 *
	 * @var array
	 */
	private static $default_allowed_scripts = array(
		// jQuery core.
		'jquery',
		'jquery-core',
		'jquery-migrate',
		// Apollo scripts.
		'apollo-base-js',
		'apollo-event-page-js',
		'apollo-loading-animation',
		'apollo-events-portal',
		'apollo-motion-event-card',
		'apollo-motion-modal',
		'apollo-infinite-scroll',
		'apollo-motion-dashboard',
		'apollo-motion-context-menu',
		'apollo-character-counter',
		'apollo-form-validation',
		'apollo-image-modal',
		'apollo-motion-gallery',
		'apollo-motion-local-page',
		'apollo-event-favorites',
		'apollo-events-favorites',
		'apollo-pwa-detect',
		'apollo-social',
		'apollo-events',
		'apollo-rio',
		// External dependencies.
		'leaflet',
		'framer-motion',
		// WordPress core.
		'admin-bar',
		'hoverIntent',
		'wp-embed',
	);

	/**
	 * Enable Canvas Mode
	 *
	 * @param array $args {
	 *     Configuration arguments.
	 *
	 *     @type bool   $strict                   Whether to use strict mode (removes content filters). Default true.
	 *     @type bool   $remove_admin_bar         Whether to remove admin bar assets. Default false.
	 *     @type bool   $elementor_safe           Whether to keep Elementor assets. Default false.
	 *     @type array  $additional_styles        Additional style handles to allow.
	 *     @type array  $additional_scripts       Additional script handles to allow.
	 *     @type bool   $remove_theme_hooks       Whether to remove theme-specific hooks. Default true.
	 *     @type string $context                  Context identifier for logging (e.g., 'events-portal').
	 * }
	 * @return void
	 */
	public static function enable(array $args = array())
	{
		// Prevent double-initialization.
		if (self::$enabled) {
			apollo_log_once('canvas_double_init', 'Canvas Mode already enabled, skipping re-initialization.');
			return;
		}

		// Default configuration.
		$defaults = array(
			'strict'             => true,
			'remove_admin_bar'   => false,
			'elementor_safe'     => false,
			'additional_styles'  => array(),
			'additional_scripts' => array(),
			'remove_theme_hooks' => true,
			'context'            => 'unknown',
		);

		self::$config  = wp_parse_args($args, $defaults);
		self::$enabled = true;

		// Allow per-page override of strict mode.
		self::$config['strict'] = apply_filters('apollo_canvas_strict_mode', self::$config['strict']);

		// Hook asset dequeuing at maximum priority.
		add_action('wp_enqueue_scripts', array(__CLASS__, 'dequeue_non_allowed_assets'), 999999);

		// Add body classes.
		add_filter('body_class', array(__CLASS__, 'add_body_classes'));

		// Strict mode: remove theme content filters.
		if (self::$config['strict'] && self::$config['remove_theme_hooks']) {
			add_action('template_redirect', array(__CLASS__, 'remove_theme_interference'), 1);
		}

		// Elementor compatibility.
		if (self::$config['elementor_safe']) {
			self::add_elementor_allowlist();
		}

		// Optional: remove admin bar.
		if (self::$config['remove_admin_bar']) {
			add_action('wp_enqueue_scripts', array(__CLASS__, 'dequeue_admin_bar_assets'), 999999);
		}

		apollo_log_once(
			'canvas_enabled_' . self::$config['context'],
			sprintf(
				'Canvas Mode enabled: context=%s, strict=%s, elementor_safe=%s',
				self::$config['context'],
				self::$config['strict'] ? 'true' : 'false',
				self::$config['elementor_safe'] ? 'true' : 'false'
			)
		);
	}

	/**
	 * Check if Canvas Mode is currently enabled
	 *
	 * @return bool
	 */
	public static function is_enabled()
	{
		return self::$enabled;
	}

	/**
	 * Get current configuration
	 *
	 * @return array
	 */
	public static function get_config()
	{
		return self::$config;
	}

	/**
	 * Dequeue all non-allowed styles and scripts
	 *
	 * @return void
	 */
	public static function dequeue_non_allowed_assets()
	{
		global $wp_styles, $wp_scripts;

		// Build allowed lists with filters.
		$allowed_styles = array_merge(
			self::$default_allowed_styles,
			self::$config['additional_styles'] ?? array()
		);
		$allowed_styles = apply_filters('apollo_canvas_allowed_styles', $allowed_styles);

		$allowed_scripts = array_merge(
			self::$default_allowed_scripts,
			self::$config['additional_scripts'] ?? array()
		);
		$allowed_scripts = apply_filters('apollo_canvas_allowed_scripts', $allowed_scripts);

		// Dequeue non-allowed styles.
		if (isset($wp_styles->registered) && is_array($wp_styles->registered)) {
			foreach ($wp_styles->registered as $handle => $style) {
				if (! self::is_asset_allowed($handle, $style, $allowed_styles, 'style')) {
					wp_dequeue_style($handle);
					wp_deregister_style($handle);
				}
			}
		}

		// Dequeue non-allowed scripts.
		if (isset($wp_scripts->registered) && is_array($wp_scripts->registered)) {
			foreach ($wp_scripts->registered as $handle => $script) {
				if (! self::is_asset_allowed($handle, $script, $allowed_scripts, 'script')) {
					wp_dequeue_script($handle);
					wp_deregister_script($handle);
				}
			}
		}
	}

	/**
	 * Check if an asset should be allowed
	 *
	 * @param string               $handle        Asset handle.
	 * @param _WP_Dependency|mixed $dependency    Dependency object.
	 * @param array                $allowed_list  List of allowed handles.
	 * @param string               $type          Asset type ('style' or 'script').
	 * @return bool
	 */
	private static function is_asset_allowed($handle, $dependency, array $allowed_list, $type)
	{
		// Explicit allowlist check.
		if (in_array($handle, $allowed_list, true)) {
			return true;
		}

		// Handle prefix check (apollo-*).
		if (strpos($handle, 'apollo-') === 0) {
			return true;
		}

		// Source path check.
		$src = '';
		if (is_object($dependency) && isset($dependency->src)) {
			$src = $dependency->src;
		}

		if (! empty($src)) {
			// Allow Apollo CDN assets.
			if (strpos($src, 'assets.apollo.rio.br') !== false) {
				return true;
			}

			// Allow Apollo plugin paths.
			if (strpos($src, '/apollo-') !== false) {
				return true;
			}

			// Allow Remixicon.
			if (strpos($src, 'remixicon') !== false) {
				return true;
			}

			// Allow Leaflet.
			if (strpos($src, 'leaflet') !== false) {
				return true;
			}

			// Allow WordPress includes (core).
			if (strpos($src, '/wp-includes/') !== false) {
				return true;
			}

			// Allow WordPress admin (for admin bar).
			if (strpos($src, '/wp-admin/') !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add body classes for canvas mode
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public static function add_body_classes($classes)
	{
		$classes[] = 'apollo-canvas-mode';
		$classes[] = 'apollo-independent-page';

		if (self::$config['strict']) {
			$classes[] = 'apollo-canvas-strict';
		}

		if (self::$config['elementor_safe']) {
			$classes[] = 'apollo-canvas-elementor-safe';
		}

		// Add context class.
		if (! empty(self::$config['context'])) {
			$classes[] = 'apollo-context-' . sanitize_html_class(self::$config['context']);
		}

		return $classes;
	}

	/**
	 * Remove theme interference (strict mode)
	 *
	 * @return void
	 */
	public static function remove_theme_interference()
	{
		// Only proceed if strict mode and filter allows.
		$should_remove = apply_filters('apollo_canvas_remove_content_filters', self::$config['strict']);

		if (! $should_remove) {
			return;
		}

		// Get theme info for targeted removal.
		$theme_slug = get_stylesheet();

		// Remove theme hooks from wp_head.
		self::remove_theme_hooks_from_action('wp_head', $theme_slug);

		// Remove theme hooks from wp_footer.
		self::remove_theme_hooks_from_action('wp_footer', $theme_slug);

		// Handle content filters carefully.
		if (self::$config['strict']) {
			// Remove all content filters.
			remove_all_filters('the_content');

			// Re-add WordPress core content filters.
			add_filter('the_content', 'do_blocks', 9);
			add_filter('the_content', 'wptexturize');
			add_filter('the_content', 'convert_smilies', 20);
			add_filter('the_content', 'wpautop');
			add_filter('the_content', 'shortcode_unautop');
			add_filter('the_content', 'prepend_attachment');
			add_filter('the_content', 'wp_filter_content_tags', 12);
			add_filter('the_content', 'wp_replace_insecure_home_url');
			add_filter('the_content', 'do_shortcode', 11);
		}

		// Remove common theme action hooks.
		$theme_hooks_to_remove = array(
			'trx_addons_action_before_header',
			'trx_addons_action_header',
			'trx_addons_action_after_header',
			'trx_addons_action_before_page_header',
			'trx_addons_action_page_header',
			'trx_addons_action_after_page_header',
			'trx_addons_action_before_footer',
			'trx_addons_action_footer',
			'trx_addons_action_after_footer',
		);

		foreach ($theme_hooks_to_remove as $hook) {
			remove_all_actions($hook);
		}
	}

	/**
	 * Remove theme-specific hooks from an action
	 *
	 * @param string $action     Action hook name.
	 * @param string $theme_slug Theme stylesheet slug.
	 * @return void
	 */
	private static function remove_theme_hooks_from_action($action, $theme_slug)
	{
		global $wp_filter;

		if (! isset($wp_filter[$action]) || ! is_object($wp_filter[$action])) {
			return;
		}

		if (! isset($wp_filter[$action]->callbacks) || ! is_array($wp_filter[$action]->callbacks)) {
			return;
		}

		foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
			foreach ($callbacks as $callback) {
				$function = $callback['function'] ?? null;

				if (! $function) {
					continue;
				}

				// Check if callback is from theme.
				if (is_array($function) && isset($function[0])) {
					$class_name = '';
					if (is_object($function[0])) {
						$class_name = get_class($function[0]);
					} elseif (is_string($function[0])) {
						$class_name = $function[0];
					}

					if (! empty($class_name) && strpos(strtolower($class_name), strtolower($theme_slug)) !== false) {
						remove_action($action, $function, $priority);
					}
				}
			}
		}
	}

	/**
	 * Add Elementor assets to allowlist
	 *
	 * @return void
	 */
	private static function add_elementor_allowlist()
	{
		add_filter(
			'apollo_canvas_allowed_styles',
			function ($styles) {
				return array_merge(
					$styles,
					array(
						'elementor-frontend',
						'elementor-post-css',
						'elementor-global-css',
						'elementor-icons',
						'elementor-animations',
						'elementor-pro',
						'font-awesome',
					)
				);
			}
		);

		add_filter(
			'apollo_canvas_allowed_scripts',
			function ($scripts) {
				return array_merge(
					$scripts,
					array(
						'elementor-frontend',
						'elementor-waypoints',
						'elementor-pro',
						'swiper',
					)
				);
			}
		);
	}

	/**
	 * Dequeue admin bar assets (optional)
	 *
	 * @return void
	 */
	public static function dequeue_admin_bar_assets()
	{
		remove_action('wp_head', '_admin_bar_bump_cb');
		wp_dequeue_style('admin-bar');
		wp_dequeue_script('admin-bar');
	}

	/**
	 * Reset Canvas Mode (useful for testing)
	 *
	 * @return void
	 */
	public static function reset()
	{
		self::$enabled = false;
		self::$config  = array();
	}
}
