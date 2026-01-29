<?php
/**
 * Apollo Core Protection
 *
 * Prevents accidental modifications to WordPress core files.
 * Provides runtime checks and logging.
 *
 * @package Apollo_Core
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Protection Class
 */
class Core_Protection {

	/**
	 * Core directories that should NEVER be modified
	 *
	 * @var array
	 */
	private static $core_directories = array(
		'wp-includes',
		'wp-admin',
	);

	/**
	 * Initialize protection
	 */
	public static function init() {
		// Only run in development/debug mode to avoid performance impact.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Hook into file operations (if possible).
		add_action( 'init', array( self::class, 'check_core_integrity' ), 999 );
	}

	/**
	 * Check core integrity
	 */
	public static function check_core_integrity() {
		// Verify that core functions exist.
		$required_functions = array(
			'wp_is_valid_utf8',
			'wp_scrub_utf8',
			'sanitize_title',
			'esc_html',
			'esc_attr',
		);

		$missing = array();
		foreach ( $required_functions as $func ) {
			if ( ! function_exists( $func ) ) {
				$missing[] = $func;
			}
		}

		if ( ! empty( $missing ) ) {
			$message = sprintf(
				'Apollo Core Protection: Missing WordPress core functions: %s. This may indicate a corrupted WordPress installation or plugin interference.',
				implode( ', ', $missing )
			);

			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( $message );
			}

			// In admin, show notice.
			if ( is_admin() ) {
				add_action(
					'admin_notices',
					function () use ( $missing ) {
						?>
						<div class="notice notice-error">
							<p><strong>Apollo Core Protection:</strong> Missing WordPress core functions detected: <code><?php echo esc_html( implode( ', ', $missing ) ); ?></code></p>
							<p>This may indicate a corrupted WordPress installation. Please verify your WordPress core files are intact.</p>
						</div>
						<?php
					}
				);
			}
		}
	}

	/**
	 * Verify file is not in core directory
	 *
	 * @param string $file_path File path to check
	 * @return bool True if safe (not in core), false if in core
	 */
	public static function is_safe_path( $file_path ) {
		$abspath            = ABSPATH;
		$normalized_path    = str_replace( '\\', '/', $file_path );
		$normalized_abspath = str_replace( '\\', '/', $abspath );

		foreach ( self::$core_directories as $core_dir ) {
			$core_path = $normalized_abspath . $core_dir;
			if ( strpos( $normalized_path, $core_path ) === 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Log attempt to modify core (if detected)
	 *
	 * @param string $file_path File path
	 * @param string $operation Operation attempted
	 */
	public static function log_core_modification_attempt( $file_path, $operation ) {
		if ( ! self::is_safe_path( $file_path ) ) {
			$message = sprintf(
				'Apollo Core Protection: Attempt to %s core file detected: %s',
				$operation,
				$file_path
			);

			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( $message );
			}

			// In development, throw error.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trigger_error logs to error log, not HTML output
				trigger_error( $message, E_USER_WARNING );
			}
		}
	}
}

// Initialize protection.
Core_Protection::init();
