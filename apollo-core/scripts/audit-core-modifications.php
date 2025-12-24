<?php
/**
 * Apollo Core Modifications Auditor
 *
 * Scans all apollo-* plugins to detect any attempts to modify WordPress core files.
 * This script should be run regularly to ensure plugins never modify wp-core.
 *
 * @package Apollo_Core
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Allow running from CLI.
	if ( php_sapi_name() !== 'cli' ) {
		die( 'Direct access not allowed.' );
	}
	// Define minimal constants for CLI execution.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', dirname( dirname( dirname( __DIR__ ) ) ) . '/' );
	}
}

/**
 * Audit Core Modifications
 */
class Apollo_Core_Modifications_Auditor {

	/**
	 * Core directories that should NEVER be modified
	 *
	 * @var array
	 */
	private $core_directories = array(
		'wp-includes',
		'wp-admin',
	);

	/**
	 * Core root files that should NEVER be modified
	 *
	 * @var array
	 */
	private $core_root_files = array(
		'wp-config.php',
		'wp-load.php',
		'wp-settings.php',
		'wp-blog-header.php',
		'wp-cron.php',
		'wp-mail.php',
		'wp-signup.php',
		'wp-trackback.php',
		'xmlrpc.php',
		'index.php',
	);

	/**
	 * Dangerous patterns that indicate core modification
	 *
	 * @var array
	 */
	private $dangerous_patterns = array(
		'file_put_contents.*wp-includes',
		'file_put_contents.*wp-admin',
		'copy.*wp-includes',
		'copy.*wp-admin',
		'rename.*wp-includes',
		'rename.*wp-admin',
		'unlink.*wp-includes',
		'unlink.*wp-admin',
		'fwrite.*wp-includes',
		'fwrite.*wp-admin',
		'fopen.*wp-includes',
		'fopen.*wp-admin',
		'move_uploaded_file.*wp-includes',
		'move_uploaded_file.*wp-admin',
	);

	/**
	 * WordPress functions that should NOT be redefined
	 *
	 * @var array
	 */
	private $wp_functions = array(
		'wp_',
		'sanitize_',
		'esc_',
		'wpdb',
		'get_option',
		'update_option',
		'add_action',
		'add_filter',
	);

	/**
	 * Run full audit
	 *
	 * @return array Audit results
	 */
	public function run_audit() {
		$results = array(
			'core_modifications' => array(),
			'function_overrides' => array(),
			'dangerous_patterns' => array(),
			'allowed_includes'   => array(),
			'timestamp'          => current_time( 'mysql' ),
		);

		$plugins_dir    = dirname( __DIR__ ) . '/../';
		$apollo_plugins = glob( $plugins_dir . 'apollo-*' );

		foreach ( $apollo_plugins as $plugin_dir ) {
			if ( ! is_dir( $plugin_dir ) ) {
				continue;
			}

			$plugin_name = basename( $plugin_dir );
			$php_files   = $this->get_php_files( $plugin_dir );

			foreach ( $php_files as $file ) {
				$content       = file_get_contents( $file );
				$relative_path = str_replace( $plugins_dir, '', $file );

				// Check for dangerous patterns.
				foreach ( $this->dangerous_patterns as $pattern ) {
					if ( preg_match( '/' . $pattern . '/i', $content ) ) {
						$results['dangerous_patterns'][] = array(
							'plugin'  => $plugin_name,
							'file'    => $relative_path,
							'pattern' => $pattern,
							'line'    => $this->get_line_number( $content, $pattern ),
						);
					}
				}

				// Check for function overrides.
				$function_overrides            = $this->check_function_overrides( $content, $relative_path, $plugin_name );
				$results['function_overrides'] = array_merge( $results['function_overrides'], $function_overrides );

				// Check for allowed includes (these are OK).
				$allowed_includes            = $this->check_allowed_includes( $content, $relative_path, $plugin_name );
				$results['allowed_includes'] = array_merge( $results['allowed_includes'], $allowed_includes );
			}
		}

		return $results;
	}

	/**
	 * Get all PHP files in directory
	 *
	 * @param string $dir Directory path
	 * @return array PHP files
	 */
	private function get_php_files( $dir ) {
		$files    = array();
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				// Skip vendor, node_modules, etc.
				$path = $file->getPathname();
				if (
					strpos( $path, '/vendor/' ) !== false ||
					strpos( $path, '/node_modules/' ) !== false ||
					strpos( $path, '/.git/' ) !== false ||
					strpos( $path, '/build/' ) !== false ||
					strpos( $path, '/dist/' ) !== false
				) {
					continue;
				}
				$files[] = $path;
			}
		}

		return $files;
	}

	/**
	 * Check for function overrides
	 *
	 * @param string $content File content
	 * @param string $file_path File path
	 * @param string $plugin_name Plugin name
	 * @return array Overrides found
	 */
	private function check_function_overrides( $content, $file_path, $plugin_name ) {
		$overrides = array();

		// Check for function definitions that might override WordPress functions.
		if ( preg_match_all( '/^\s*function\s+([a-z_][a-z0-9_]*)\s*\(/im', $content, $matches ) ) {
			foreach ( $matches[1] as $function_name ) {
				// Check if it's a WordPress core function.
				foreach ( $this->wp_functions as $wp_prefix ) {
					if ( strpos( $function_name, $wp_prefix ) === 0 ) {
						// Check if it's not a wrapper or safe function.
						if ( ! $this->is_safe_function( $function_name, $content ) ) {
							$overrides[] = array(
								'plugin'   => $plugin_name,
								'file'     => $file_path,
								'function' => $function_name,
								'warning'  => 'Possible WordPress function override',
							);
						}
					}
				}
			}
		}

		return $overrides;
	}

	/**
	 * Check if function is safe (wrapper, not override)
	 *
	 * @param string $function_name Function name
	 * @param string $content File content
	 * @return bool True if safe
	 */
	private function is_safe_function( $function_name, $content ) {
		// Functions with apollo_ prefix are safe.
		if ( strpos( $function_name, 'apollo_' ) === 0 ) {
			return true;
		}

		// Functions that check if WordPress function exists before using are safe.
		if ( preg_match( '/function_exists\s*\(\s*[\'"]' . preg_quote( $function_name, '/' ) . '[\'"]\s*\)/i', $content ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check for allowed includes (wp-admin/includes/* is OK)
	 *
	 * @param string $content File content
	 * @param string $file_path File path
	 * @param string $plugin_name Plugin name
	 * @return array Allowed includes found
	 */
	private function check_allowed_includes( $content, $file_path, $plugin_name ) {
		$includes = array();

		// These are allowed - plugins can include wp-admin/includes/* files.
		if ( preg_match_all( '/require.*wp-admin\/includes\/([^\'"]+)/i', $content, $matches ) ) {
			foreach ( $matches[1] as $included_file ) {
				$includes[] = array(
					'plugin'   => $plugin_name,
					'file'     => $file_path,
					'included' => 'wp-admin/includes/' . $included_file,
					'status'   => 'ALLOWED',
				);
			}
		}

		return $includes;
	}

	/**
	 * Get line number for pattern
	 *
	 * @param string $content File content
	 * @param string $pattern Pattern to find
	 * @return int Line number
	 */
	private function get_line_number( $content, $pattern ) {
		$lines = explode( "\n", $content );
		foreach ( $lines as $num => $line ) {
			if ( preg_match( '/' . $pattern . '/i', $line ) ) {
				return $num + 1;
			}
		}
		return 0;
	}

	/**
	 * Generate report
	 *
	 * @param array $results Audit results
	 * @return string Report HTML/text
	 */
	public function generate_report( $results ) {
		$report  = "=== Apollo Core Modifications Audit Report ===\n\n";
		$report .= 'Generated: ' . $results['timestamp'] . "\n\n";

		// Dangerous patterns.
		if ( ! empty( $results['dangerous_patterns'] ) ) {
			$report .= "⚠️  DANGEROUS PATTERNS FOUND:\n";
			$report .= "These patterns indicate attempts to modify WordPress core:\n\n";
			foreach ( $results['dangerous_patterns'] as $item ) {
				$report .= sprintf(
					"  - Plugin: %s\n    File: %s\n    Pattern: %s\n    Line: %d\n\n",
					$item['plugin'],
					$item['file'],
					$item['pattern'],
					$item['line']
				);
			}
		} else {
			$report .= "✅ No dangerous patterns found.\n\n";
		}

		// Function overrides.
		if ( ! empty( $results['function_overrides'] ) ) {
			$report .= "⚠️  FUNCTION OVERRIDES FOUND:\n";
			$report .= "These functions may override WordPress core functions:\n\n";
			foreach ( $results['function_overrides'] as $item ) {
				$report .= sprintf(
					"  - Plugin: %s\n    File: %s\n    Function: %s\n    Warning: %s\n\n",
					$item['plugin'],
					$item['file'],
					$item['function'],
					$item['warning']
				);
			}
		} else {
			$report .= "✅ No function overrides found.\n\n";
		}

		// Allowed includes summary.
		$report .= sprintf( "📋 Allowed includes (wp-admin/includes/*): %d\n\n", count( $results['allowed_includes'] ) );

		return $report;
	}
}

// Run audit if executed directly.
if ( php_sapi_name() === 'cli' ) {
	$script_name = '';
	if ( isset( $_SERVER['PHP_SELF'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- CLI context, safe
		$script_name = basename( $_SERVER['PHP_SELF'] );
	}
	if ( basename( __FILE__ ) === $script_name || ( empty( $script_name ) && php_sapi_name() === 'cli' ) ) {
		$auditor = new Apollo_Core_Modifications_Auditor();
		$results = $auditor->run_audit();
		$report  = $auditor->generate_report( $results );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output, safe
		echo $report;

		// Exit with error code if issues found.
		if ( ! empty( $results['dangerous_patterns'] ) || ! empty( $results['function_overrides'] ) ) {
			exit( 1 );
		}
		exit( 0 );
	}
}
