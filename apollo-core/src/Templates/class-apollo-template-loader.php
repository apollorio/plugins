<?php

declare(strict_types=1);
/**
 * Template Loader - Apollo Design System
 *
 * Loads templates with ViewModel data and ensures consistent
 * asset loading and partial integration.
 *
 * PHASE 1A FIX: Added defined() guards for all plugin path constants
 * to prevent fatals when other Apollo plugins are not active.
 *
 * @package ApolloCore\Templates
 */

class Apollo_Template_Loader {
	/**
	 * Template base path
	 */
	const TEMPLATE_BASE = 'templates/';

	/**
	 * Partials base path
	 */
	const PARTIALS_BASE = 'templates/partials/';

	/**
	 * Layouts base path
	 */
	const LAYOUTS_BASE = 'templates/layouts/';

	/**
	 * Get all registered template root paths
	 *
	 * Uses defined() guards to only include paths for active plugins.
	 * External plugins can add paths via 'apollo_template_paths' filter.
	 *
	 * @return array Associative array of plugin => path
	 */
	public static function get_template_paths() {
		$paths = array();

		// Apollo Core is always available (we're in it).
		if ( defined( 'APOLLO_CORE_PATH' ) ) {
			$paths['core'] = APOLLO_CORE_PATH;
		}

		// Events Manager (optional).
		if ( defined( 'APOLLO_EVENTS_PATH' ) ) {
			$paths['events'] = APOLLO_EVENTS_PATH;
		}

		// Social (optional).
		if ( defined( 'APOLLO_SOCIAL_PATH' ) ) {
			$paths['social'] = APOLLO_SOCIAL_PATH;
		}

		// Rio PWA (optional).
		if ( defined( 'APOLLO_RIO_PATH' ) ) {
			$paths['rio'] = APOLLO_RIO_PATH;
		}

		/**
		 * Filter template root paths
		 *
		 * Allows external plugins to register their template directories.
		 *
		 * @param array $paths Associative array of plugin_slug => path
		 */
		return apply_filters( 'apollo_template_paths', $paths );
	}

	/**
	 * Load a template with data
	 *
	 * @param string $template Template name (without .php)
	 * @param array  $data Data to pass to template
	 * @param bool   $return Whether to return or echo output
	 * @return string|null
	 */
	public static function load( $template, $data = array(), $return = false ) {
		// Ensure assets are loaded.
		Apollo_Assets_Loader::ensure_assets_loaded();

		// Get template file path.
		$template_file = self::locate_template( $template );

		if ( ! $template_file ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Apollo Template Loader: Template '{$template}' not found" );
			}
			return $return ? '' : null;
		}

		// Extract data for template use.
		if ( is_array( $data ) ) {
			extract( $data, EXTR_SKIP );
		}

		// Buffer output if returning.
		if ( $return ) {
			ob_start();
		}

		// Include template.
		include $template_file;

		// Return buffered output if requested.
		if ( $return ) {
			return ob_get_clean();
		}

		return null;
	}

	/**
	 * Load a partial with data
	 *
	 * @param string $partial Partial name (without .php)
	 * @param array  $args Arguments for partial
	 * @param bool   $return Whether to return or echo output
	 * @return string|null
	 */
	public static function load_partial( $partial, $args = array(), $return = false ) {
		$partial_file = self::locate_partial( $partial );

		if ( ! $partial_file ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Apollo Template Loader: Partial '{$partial}' not found" );
			}
			return $return ? '' : null;
		}

		// Buffer output if returning.
		if ( $return ) {
			ob_start();
		}

		// Include partial.
		include $partial_file;

		// Return buffered output if requested.
		if ( $return ) {
			return ob_get_clean();
		}

		return null;
	}

	/**
	 * Render a template with ViewModel data
	 *
	 * @param string                $template Template name
	 * @param Apollo_Base_ViewModel $viewmodel ViewModel instance
	 * @param string                $method ViewModel method to call (default: 'get_template_data')
	 * @param bool                  $return Whether to return or echo output
	 * @return string|null
	 */
	public static function render_with_viewmodel( $template, $viewmodel, $method = 'get_template_data', $return = false ) {
		if ( ! $viewmodel instanceof Apollo_Base_ViewModel ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Apollo Template Loader: Invalid ViewModel provided' );
			}
			return $return ? '' : null;
		}

		// Get data from ViewModel.
		$data = method_exists( $viewmodel, $method ) ? $viewmodel->$method() : $viewmodel->get_template_data();

		// Add ViewModel instance to data for advanced usage.
		$data['_viewmodel'] = $viewmodel;

		return self::load( $template, $data, $return );
	}

	/**
	 * Locate template file
	 *
	 * Uses get_template_paths() with defined() guards to safely
	 * check only active plugin directories.
	 *
	 * @param string $template Template name
	 * @return string|false Template file path or false if not found
	 */
	private static function locate_template( $template ) {
		$template = sanitize_file_name( $template );
		$paths    = self::get_template_paths();

		// Priority order: events > social > rio > core > theme.
		$priority_order = array( 'events', 'social', 'rio', 'core' );

		foreach ( $priority_order as $plugin ) {
			if ( ! isset( $paths[ $plugin ] ) ) {
				continue;
			}

			$template_file = $paths[ $plugin ] . self::TEMPLATE_BASE . $template . '.php';
			if ( file_exists( $template_file ) ) {
				return $template_file;
			}
		}

		// Check any additional paths from filter.
		foreach ( $paths as $plugin => $path ) {
			if ( in_array( $plugin, $priority_order, true ) ) {
				continue; // Already checked.
			}

			$template_file = $path . self::TEMPLATE_BASE . $template . '.php';
			if ( file_exists( $template_file ) ) {
				return $template_file;
			}
		}

		// Check in theme.
		$theme_template = locate_template( 'apollo/' . $template . '.php' );
		if ( $theme_template ) {
			return $theme_template;
		}

		return false;
	}

	/**
	 * Locate partial file
	 *
	 * Checks core partials first, then other plugin partials.
	 *
	 * @param string $partial Partial name
	 * @return string|false Partial file path or false if not found
	 */
	private static function locate_partial( $partial ) {
		$partial = sanitize_file_name( $partial );
		$paths   = self::get_template_paths();

		// Check core first (partials should primarily live in core).
		if ( isset( $paths['core'] ) ) {
			$partial_file = $paths['core'] . self::PARTIALS_BASE . $partial . '.php';
			if ( file_exists( $partial_file ) ) {
				return $partial_file;
			}
		}

		// Check other plugins for plugin-specific partials.
		foreach ( $paths as $plugin => $path ) {
			if ( $plugin === 'core' ) {
				continue;
			}

			$partial_file = $path . self::PARTIALS_BASE . $partial . '.php';
			if ( file_exists( $partial_file ) ) {
				return $partial_file;
			}
		}

		return false;
	}

	/**
	 * Get available templates
	 *
	 * @return array List of available templates
	 */
	public static function get_available_templates() {
		$templates = array();
		$paths     = self::get_template_paths();

		// Scan plugin template directories.
		foreach ( $paths as $plugin => $path ) {
			$dir = $path . self::TEMPLATE_BASE;
			if ( is_dir( $dir ) ) {
				$files = glob( $dir . '*.php' );
				if ( $files ) {
					foreach ( $files as $file ) {
						$templates[] = basename( $file, '.php' );
					}
				}
			}
		}

		return array_unique( $templates );
	}

	/**
	 * Get available partials
	 *
	 * @return array List of available partials
	 */
	public static function get_available_partials() {
		$partials = array();
		$paths    = self::get_template_paths();

		foreach ( $paths as $plugin => $path ) {
			$partials_dir = $path . self::PARTIALS_BASE;
			if ( is_dir( $partials_dir ) ) {
				$files = glob( $partials_dir . '*.php' );
				if ( $files ) {
					foreach ( $files as $file ) {
						$partials[] = basename( $file, '.php' );
					}
				}
			}
		}

		return array_unique( $partials );
	}

	/**
	 * Render a layout with content callback
	 *
	 * Used for apollo-social layout shell pattern where layout wraps
	 * dynamic main content.
	 *
	 * @param string   $layout           Layout name (without .php)
	 * @param mixed    $viewmodel        ViewModel for layout data
	 * @param callable $content_callback Callback that returns main content HTML
	 * @param bool     $return           Whether to return or echo output
	 * @return string|null
	 */
	public static function render_layout( $layout, $viewmodel = null, $content_callback = null, $return = false ) {
		$paths = self::get_template_paths();

		// Locate layout file (core only).
		$layout_file = null;
		if ( isset( $paths['core'] ) ) {
			$potential = $paths['core'] . self::LAYOUTS_BASE . $layout . '.php';
			if ( file_exists( $potential ) ) {
				$layout_file = $potential;
			}
		}

		if ( ! $layout_file ) {
			apollo_log_once( 'layout_not_found_' . $layout, "Layout '{$layout}' not found" );
			return $return ? '' : null;
		}

		// Get layout data from ViewModel.
		$data = array();
		if ( $viewmodel && is_object( $viewmodel ) && method_exists( $viewmodel, 'get_template_data' ) ) {
			$data = $viewmodel->get_template_data();
		} elseif ( is_array( $viewmodel ) ) {
			$data = $viewmodel;
		}

		// Generate main content if callback provided.
		$main_content = '';
		if ( is_callable( $content_callback ) ) {
			ob_start();
			call_user_func( $content_callback, $data );
			$main_content = ob_get_clean();
		}

		$data['main_content'] = $main_content;
		$data['_layout']      = $layout;

		// Extract data for template use.
		extract( $data, EXTR_SKIP );

		// Buffer output if returning.
		if ( $return ) {
			ob_start();
		}

		include $layout_file;

		if ( $return ) {
			return ob_get_clean();
		}

		return null;
	}

	/**
	 * Render 404 template
	 *
	 * @param bool $return Whether to return or echo output
	 * @return string|null
	 */
	public static function render_404( $return = false ) {
		$data = array(
			'title'   => __( 'Page Not Found', 'apollo' ),
			'message' => __( 'The page you are looking for does not exist.', 'apollo' ),
		);

		return self::load( '404', $data, $return );
	}

	/**
	 * Render maintenance template
	 *
	 * @param bool $return Whether to return or echo output
	 * @return string|null
	 */
	public static function render_maintenance( $return = false ) {
		$data = array(
			'title'   => __( 'Under Maintenance', 'apollo' ),
			'message' => __( 'We are currently performing maintenance. Please check back soon.', 'apollo' ),
		);

		return self::load( 'maintenance', $data, $return );
	}
}
