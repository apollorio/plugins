<?php
/**
 * Autoloader - Apollo Design System
 *
 * Automatically loads Apollo classes when needed.
 *
 * @package ApolloCore
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Autoloader
 */
class Apollo_Autoloader {
	/**
	 * Base namespace for Apollo classes
	 */
	const NAMESPACE_BASE = 'Apollo';

	/**
	 * Directory mapping for namespaces
	 */
	private static $namespace_map = array(
		'ApolloCore'            => 'src',
		'ApolloCore\ViewModels' => 'src/ViewModels',
		'ApolloCore\Assets'     => 'src/Assets',
		'ApolloCore\Templates'  => 'src/Templates',
	);

	/**
	 * Register the autoloader
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload function
	 *
	 * @param string $class_name Full class name with namespace
	 * @return void
	 */
	public static function autoload( $class_name ) {
		// Handle underscore-based Apollo classes (legacy).
		if ( strpos( $class_name, 'Apollo_' ) === 0 ) {
			$file_path = self::load_underscore_class( $class_name );
			if ( $file_path && file_exists( $file_path ) ) {
				require_once $file_path;
				return;
			}
		}

		// Handle namespaced Apollo classes.
		if ( strpos( $class_name, self::NAMESPACE_BASE ) !== 0 ) {
			return;
		}

		// Convert namespace to file path.
		$file_path = self::namespace_to_path( $class_name );

		if ( $file_path && file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}

	/**
	 * Load underscore-based Apollo classes
	 *
	 * @param string $class_name Class name with underscores
	 * @return string|false File path or false if not found
	 */
	private static function load_underscore_class( $class_name ) {
		// Convert class name to file name: Apollo_ViewModel_Factory -> class-apollo-viewmodel-factory.php
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		// Define search paths in order of priority.
		$search_paths = array(
			APOLLO_CORE_PATH . 'src/ViewModels/' . $file_name,
			APOLLO_CORE_PATH . 'src/Templates/' . $file_name,
			APOLLO_CORE_PATH . 'src/Assets/' . $file_name,
			APOLLO_CORE_PATH . 'src/' . $file_name,
			APOLLO_CORE_PATH . 'includes/' . $file_name,
		);

		foreach ( $search_paths as $file_path ) {
			if ( file_exists( $file_path ) ) {
				return $file_path;
			}
		}

		return false;
	}

	/**
	 * Convert namespace to file path
	 *
	 * @param string $namespace Full namespace
	 * @return string|false File path or false if not found
	 */
	private static function namespace_to_path( $namespace ) {
		// Remove base namespace.
		$relative_namespace = str_replace( self::NAMESPACE_BASE, '', $namespace );
		$relative_namespace = ltrim( $relative_namespace, '\\' );

		// Split into parts.
		$parts = explode( '\\', $relative_namespace );

		// Get directory from mapping.
		$namespace_key = self::NAMESPACE_BASE;
		if ( ! empty( $parts ) ) {
			$namespace_key .= '\\' . $parts[0];
		}

		if ( ! isset( self::$namespace_map[ $namespace_key ] ) ) {
			return false;
		}

		$base_dir   = self::$namespace_map[ $namespace_key ];
		$plugin_dir = APOLLO_CORE_PATH;

		// Build file path.
		$file_parts = $parts;
		if ( count( $file_parts ) > 1 ) {
			// Remove the namespace segment that's mapped.
			array_shift( $file_parts );
		}

		// Add class name.
		$class_name   = array_pop( $file_parts );
		$file_parts[] = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		// Build full path.
		$relative_path = $base_dir . '/' . implode( '/', $file_parts );
		$full_path     = $plugin_dir . $relative_path;

		return $full_path;
	}

	/**
	 * Get all available classes
	 *
	 * @return array List of available classes
	 */
	public static function get_available_classes() {
		$classes = array();

		foreach ( self::$namespace_map as $namespace => $dir ) {
			$full_dir = APOLLO_CORE_PATH . $dir;
			if ( is_dir( $full_dir ) ) {
				$files = glob( $full_dir . '/class-*.php' );
				foreach ( $files as $file ) {
					$class_name = self::path_to_namespace( $file );
					if ( $class_name ) {
						$classes[] = $class_name;
					}
				}
			}
		}

		return $classes;
	}

	/**
	 * Convert file path to namespace
	 *
	 * @param string $file_path Full file path
	 * @return string|false Namespace or false if not found
	 */
	private static function path_to_namespace( $file_path ) {
		$plugin_dir = APOLLO_CORE_PATH;

		// Remove plugin directory.
		if ( strpos( $file_path, $plugin_dir ) !== 0 ) {
			return false;
		}

		$relative_path = str_replace( $plugin_dir, '', $file_path );
		$relative_path = trim( $relative_path, '/' );

		// Find matching namespace.
		foreach ( self::$namespace_map as $namespace => $dir ) {
			if ( strpos( $relative_path, $dir ) === 0 ) {
				$remaining_path = str_replace( $dir, '', $relative_path );
				$remaining_path = trim( $remaining_path, '/' );

				if ( empty( $remaining_path ) ) {
					return $namespace;
				}

				// Convert path to namespace.
				$path_parts      = explode( '/', $remaining_path );
				$namespace_parts = explode( '\\', $namespace );

				// Remove .php and class- prefix from filename
				$filename = array_pop( $path_parts );
				$filename = str_replace( '.php', '', $filename );
				$filename = str_replace( 'class-', '', $filename );
				$filename = str_replace( '-', '_', $filename );

				// Convert to CamelCase.
				$filename = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $filename ) ) );

				$path_parts[] = $filename;

				$full_namespace = $namespace . '\\' . implode( '\\', $path_parts );

				return $full_namespace;
			}
		}

		return false;
	}
}

// Register the autoloader.
Apollo_Autoloader::register();
