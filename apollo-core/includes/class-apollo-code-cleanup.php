<?php
/**
 * Apollo Code Cleanup Utilities
 *
 * PHASE 8: Optimization - Code Cleanup
 *
 * Utilities for removing debug code, validating code quality,
 * and ensuring production-ready code.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Code Cleanup Utilities Class
 *
 * Provides methods for code validation and cleanup.
 *
 * @since 2.0.0
 */
class Code_Cleanup {

	/**
	 * Debug patterns to detect in code.
	 *
	 * @var string[]
	 */
	private static array $debug_patterns = array(
		'/console\.(log|debug|info|warn|error|trace|dir|table)\s*\(/i',
		'/error_log\s*\(/i',
		'/var_dump\s*\(/i',
		'/print_r\s*\(/i',
		'/var_export\s*\(/i',
		'/debug_backtrace\s*\(/i',
		'/xdebug_/i',
		'/dump\s*\(/i',
		'/dd\s*\(/i',
		'/@todo/i',
		'/@fixme/i',
		'/\/\/\s*DEBUG/i',
		'/\/\*\s*DEBUG/i',
	);

	/**
	 * File extensions to scan.
	 *
	 * @var string[]
	 */
	private static array $scan_extensions = array( 'php', 'js', 'ts', 'jsx', 'tsx' );

	/**
	 * Directories to skip.
	 *
	 * @var string[]
	 */
	private static array $skip_directories = array(
		'node_modules',
		'vendor',
		'.git',
		'.vscode',
		'tests',
		'dev-vendor',
	);

	/**
	 * Scan plugin directory for debug code.
	 *
	 * @since 2.0.0
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array{file: string, line: int, pattern: string, match: string}[] Found debug code.
	 */
	public static function scan_for_debug_code( string $plugin_dir ): array {
		$issues = array();

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $plugin_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			// Check extension.
			$extension = \strtolower( $file->getExtension() );
			if ( ! \in_array( $extension, self::$scan_extensions, true ) ) {
				continue;
			}

			// Check if in skip directory.
			$path = $file->getPathname();
			$skip = false;
			foreach ( self::$skip_directories as $skip_dir ) {
				if ( str_contains( $path, DIRECTORY_SEPARATOR . $skip_dir . DIRECTORY_SEPARATOR ) ) {
					$skip = true;
					break;
				}
			}

			if ( $skip ) {
				continue;
			}

			// Scan file contents.
			$issues = \array_merge( $issues, self::scan_file( $path ) );
		}

		return $issues;
	}

	/**
	 * Scan a single file for debug code.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_path File path.
	 * @return array{file: string, line: int, pattern: string, match: string}[] Found issues.
	 */
	public static function scan_file( string $file_path ): array {
		$issues  = array();
		$content = file_get_contents( $file_path );

		if ( false === $content ) {
			return $issues;
		}

		$lines = \explode( "\n", $content );

		foreach ( $lines as $line_num => $line ) {
			foreach ( self::$debug_patterns as $pattern ) {
				if ( \preg_match( $pattern, $line, $matches ) ) {
					// Skip if in a comment that's not a TODO/FIXME.
					if ( self::is_in_docblock( $line ) && ! \preg_match( '/@todo|@fixme/i', $line ) ) {
						continue;
					}

					$issues[] = array(
						'file'    => $file_path,
						'line'    => $line_num + 1,
						'pattern' => $pattern,
						'match'   => \trim( $line ),
					);
				}
			}
		}

		return $issues;
	}

	/**
	 * Check if line is within a docblock.
	 *
	 * @since 2.0.0
	 *
	 * @param string $line Line content.
	 * @return bool
	 */
	private static function is_in_docblock( string $line ): bool {
		$trimmed = \ltrim( $line );
		return \str_starts_with( $trimmed, '*' ) || \str_starts_with( $trimmed, '/**' );
	}

	/**
	 * Validate PHPDoc blocks in a file.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_path File path.
	 * @return array{file: string, line: int, issue: string}[] PHPDoc issues.
	 */
	public static function validate_phpdoc( string $file_path ): array {
		$issues  = array();
		$content = file_get_contents( $file_path );

		if ( false === $content ) {
			return $issues;
		}

		$tokens = \token_get_all( $content );
		$count  = \count( $tokens );

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];

			if ( ! \is_array( $token ) ) {
				continue;
			}

			// Check for function/class/method without docblock.
			if ( T_FUNCTION === $token[0] || T_CLASS === $token[0] || T_INTERFACE === $token[0] || T_TRAIT === $token[0] ) {
				$has_docblock = false;

				// Look back for docblock.
				for ( $j = $i - 1; $j >= 0; $j-- ) {
					if ( ! \is_array( $tokens[ $j ] ) ) {
						continue;
					}

					if ( T_WHITESPACE === $tokens[ $j ][0] ) {
						continue;
					}

					if ( T_DOC_COMMENT === $tokens[ $j ][0] ) {
						$has_docblock = true;
					}

					break;
				}

				if ( ! $has_docblock ) {
					// Get the name.
					$name = '';
					for ( $k = $i + 1; $k < $count; $k++ ) {
						if ( \is_array( $tokens[ $k ] ) && T_STRING === $tokens[ $k ][0] ) {
							$name = $tokens[ $k ][1];
							break;
						}
					}

					$type = \token_name( $token[0] );

					$issues[] = array(
						'file'  => $file_path,
						'line'  => $token[2],
						'issue' => \sprintf( 'Missing PHPDoc for %s: %s', \strtolower( \str_replace( 'T_', '', $type ) ), $name ),
					);
				}
			}
		}

		return $issues;
	}

	/**
	 * Check for unused variables in a function.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_path File path.
	 * @return array{file: string, line: int, variable: string}[] Unused variables.
	 */
	public static function find_unused_variables( string $file_path ): array {
		$issues  = array();
		$content = file_get_contents( $file_path );

		if ( false === $content ) {
			return $issues;
		}

		// Find all variable declarations.
		\preg_match_all( '/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=/', $content, $declarations, PREG_OFFSET_CAPTURE );

		foreach ( $declarations[1] as $match ) {
			$var_name = $match[0];
			$offset   = $match[1];

			// Skip common variables.
			if ( \in_array( $var_name, array( 'this', 'wpdb', 'post', 'wp_query', 'current_user' ), true ) ) {
				continue;
			}

			// Count usages (excluding declaration).
			$pattern = '/\$' . \preg_quote( $var_name, '/' ) . '(?![a-zA-Z0-9_])/';
			$count   = \preg_match_all( $pattern, $content );

			// If only appears once (the declaration), it might be unused.
			if ( $count <= 1 ) {
				// Get line number.
				$line = \substr_count( \substr( $content, 0, $offset ), "\n" ) + 1;

				$issues[] = array(
					'file'     => $file_path,
					'line'     => $line,
					'variable' => '$' . $var_name,
				);
			}
		}

		return $issues;
	}

	/**
	 * Generate cleanup report for a plugin.
	 *
	 * @since 2.0.0
	 *
	 * @param string $plugin_dir Plugin directory.
	 * @return array{debug_code: array, missing_phpdoc: array, unused_vars: array, summary: array}
	 */
	public static function generate_report( string $plugin_dir ): array {
		$debug_code   = self::scan_for_debug_code( $plugin_dir );
		$missing_docs = array();
		$unused_vars  = array();

		// Scan PHP files for PHPDoc and unused variables.
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $plugin_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() || 'php' !== \strtolower( $file->getExtension() ) ) {
				continue;
			}

			$path = $file->getPathname();

			// Skip vendor directories.
			$skip = false;
			foreach ( self::$skip_directories as $skip_dir ) {
				if ( \str_contains( $path, DIRECTORY_SEPARATOR . $skip_dir . DIRECTORY_SEPARATOR ) ) {
					$skip = true;
					break;
				}
			}

			if ( $skip ) {
				continue;
			}

			$missing_docs = \array_merge( $missing_docs, self::validate_phpdoc( $path ) );
			$unused_vars  = \array_merge( $unused_vars, self::find_unused_variables( $path ) );
		}

		return array(
			'debug_code'     => $debug_code,
			'missing_phpdoc' => $missing_docs,
			'unused_vars'    => $unused_vars,
			'summary'        => array(
				'debug_code_count'     => \count( $debug_code ),
				'missing_phpdoc_count' => \count( $missing_docs ),
				'unused_vars_count'    => \count( $unused_vars ),
				'scan_time'            => \gmdate( 'Y-m-d H:i:s' ),
			),
		);
	}

	/**
	 * Format report as text.
	 *
	 * @since 2.0.0
	 *
	 * @param array $report Report data.
	 * @return string Formatted report.
	 */
	public static function format_report_text( array $report ): string {
		$output  = "=== Apollo Code Cleanup Report ===\n";
		$output .= 'Generated: ' . $report['summary']['scan_time'] . "\n\n";

		// Debug code.
		$output .= '## Debug Code Found: ' . $report['summary']['debug_code_count'] . "\n";
		foreach ( $report['debug_code'] as $issue ) {
			$relative = \basename( \dirname( $issue['file'] ) ) . '/' . \basename( $issue['file'] );
			$output  .= \sprintf( "- %s:%d - %s\n", $relative, $issue['line'], \substr( $issue['match'], 0, 60 ) );
		}
		$output .= "\n";

		// Missing PHPDoc.
		$output .= '## Missing PHPDoc: ' . $report['summary']['missing_phpdoc_count'] . "\n";
		foreach ( \array_slice( $report['missing_phpdoc'], 0, 20 ) as $issue ) {
			$relative = \basename( \dirname( $issue['file'] ) ) . '/' . \basename( $issue['file'] );
			$output  .= \sprintf( "- %s:%d - %s\n", $relative, $issue['line'], $issue['issue'] );
		}
		if ( \count( $report['missing_phpdoc'] ) > 20 ) {
			$output .= \sprintf( "... and %d more\n", \count( $report['missing_phpdoc'] ) - 20 );
		}
		$output .= "\n";

		// Potentially unused variables.
		$output .= '## Potentially Unused Variables: ' . $report['summary']['unused_vars_count'] . "\n";
		foreach ( \array_slice( $report['unused_vars'], 0, 10 ) as $issue ) {
			$relative = \basename( \dirname( $issue['file'] ) ) . '/' . \basename( $issue['file'] );
			$output  .= \sprintf( "- %s:%d - %s\n", $relative, $issue['line'], $issue['variable'] );
		}
		if ( \count( $report['unused_vars'] ) > 10 ) {
			$output .= \sprintf( "... and %d more (may be false positives)\n", \count( $report['unused_vars'] ) - 10 );
		}

		return $output;
	}
}
