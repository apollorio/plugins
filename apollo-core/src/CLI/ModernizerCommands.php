<?php

declare(strict_types=1);

/**
 * Apollo Code Modernizer CLI - Automated Code Quality Improvements
 *
 * Run via WP-CLI: wp apollo modernize [--dry-run] [--fix]
 *
 * @package Apollo\Core\CLI
 * @since   2.1.0
 * @author  Apollo Team
 */

namespace Apollo\Core\CLI;

use WP_CLI;

// Prevent direct file access.
defined('ABSPATH') || exit;

// Bail if WP-CLI is not available.
if (! defined('WP_CLI') || ! WP_CLI) {
	return;
}

/**
 * Apollo Code Modernizer Commands.
 *
 * @since 2.1.0
 */
class ModernizerCommands
{

	/**
	 * Plugin directories to scan.
	 *
	 * @var array<string>
	 */
	private array $plugin_dirs = [
		'apollo-core',
		'apollo-events-manager',
		'apollo-social',
		'apollo-rio',
	];

	/**
	 * Directories to skip.
	 *
	 * @var array<string>
	 */
	private array $skip_dirs = [
		'vendor',
		'node_modules',
		'tests',
		'stubs',
		'build',
		'dist',
		'.git',
	];

	/**
	 * Files to skip.
	 *
	 * @var array<string>
	 */
	private array $skip_files = [
		'apollo-stubs.php',
	];

	/**
	 * Apply strict types declaration to all PHP files.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * [--plugin=<plugin>]
	 * : Only process specific plugin (apollo-core, apollo-social, etc.)
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo strict-types --dry-run
	 *     wp apollo strict-types --plugin=apollo-core
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function strict_types(array $args, array $assoc_args): void
	{
		$dry_run = isset($assoc_args['dry-run']);
		$plugin  = $assoc_args['plugin'] ?? null;

		$dirs = $plugin ? [$plugin] : $this->plugin_dirs;

		$total_files    = 0;
		$modified_files = 0;
		$skipped_files  = 0;
		$already_strict = 0;

		foreach ($dirs as $dir) {
			$path = WP_PLUGIN_DIR . '/' . $dir;
			if (! is_dir($path)) {
				WP_CLI::warning("Plugin directory not found: {$dir}");
				continue;
			}

			WP_CLI::log("Processing: {$dir}");

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
			);

			foreach ($iterator as $file) {
				if (! $file->isFile() || 'php' !== $file->getExtension()) {
					continue;
				}

				// Check if in skip directory.
				$relative_path = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
				$should_skip   = false;

				foreach ($this->skip_dirs as $skip_dir) {
					if (str_starts_with($relative_path, $skip_dir . DIRECTORY_SEPARATOR)) {
						$should_skip = true;
						break;
					}
				}

				// Check if in skip files.
				foreach ($this->skip_files as $skip_file) {
					if (str_ends_with($relative_path, $skip_file)) {
						$should_skip = true;
						break;
					}
				}

				if ($should_skip) {
					++$skipped_files;
					continue;
				}

				++$total_files;

				$result = $this->add_strict_types($file->getPathname(), $dry_run);

				if ('already' === $result) {
					++$already_strict;
				} elseif ('modified' === $result) {
					++$modified_files;
					WP_CLI::log("  ✓ {$relative_path}");
				}
			}
		}

		WP_CLI::log('');
		WP_CLI::success(sprintf(
			'Strict types: %d files processed, %d modified, %d already strict, %d skipped',
			$total_files,
			$modified_files,
			$already_strict,
			$skipped_files
		));

		if ($dry_run && $modified_files > 0) {
			WP_CLI::log('');
			WP_CLI::log('Run without --dry-run to apply changes.');
		}
	}

	/**
	 * Add strict types declaration to a file.
	 *
	 * @param string $file_path File path.
	 * @param bool   $dry_run   Dry run mode.
	 * @return string 'already', 'modified', or 'error'.
	 */
	private function add_strict_types(string $file_path, bool $dry_run): string
	{
		$content = file_get_contents($file_path);
		if (false === $content) {
			return 'error';
		}

		// Check if already has strict_types.
		if (str_contains($content, 'declare(strict_types=1)') || str_contains($content, 'declare( strict_types=1 )')) {
			return 'already';
		}

		// Skip files that aren't actual PHP files (templates, etc.).
		if (! str_contains($content, '<?php')) {
			return 'already';
		}

		// Find the opening PHP tag.
		$opening_tag_pos = strpos($content, '<?php');
		if (false === $opening_tag_pos) {
			return 'error';
		}

		// Check what comes after <?php.
		$after_tag = substr($content, $opening_tag_pos + 5, 100);

		// Build the new content.
		$before_tag = substr($content, 0, $opening_tag_pos);
		$after_full = substr($content, $opening_tag_pos + 5);

		// Preserve any existing newline/whitespace pattern.
		$whitespace = '';
		if (preg_match('/^(\s*)/', $after_full, $matches)) {
			$whitespace = $matches[1];
		}

		// Inject strict_types after <?php.
		$new_content = $before_tag . "<?php\n\ndeclare(strict_types=1);" . $after_full;

		// If the file has docblock right after <?php, preserve it.
		if (preg_match('/^\s*\n\s*\/\*\*/', $after_full)) {
			$new_content = $before_tag . "<?php\n\ndeclare(strict_types=1);\n" . ltrim($after_full);
		}

		if (! $dry_run) {
			file_put_contents($file_path, $new_content);
		}

		return 'modified';
	}

	/**
	 * Fix common security issues automatically.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * [--type=<type>]
	 * : Type of fix: nonce, escape, sanitize, all
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo fix-security --dry-run
	 *     wp apollo fix-security --type=escape
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function fix_security(array $args, array $assoc_args): void
	{
		$dry_run = isset($assoc_args['dry-run']);
		$type    = $assoc_args['type'] ?? 'all';

		WP_CLI::log('Security fix type: ' . $type);
		WP_CLI::log('Dry run: ' . ($dry_run ? 'yes' : 'no'));
		WP_CLI::log('');

		$fixes = [
			'escape'   => 0,
			'sanitize' => 0,
			'nonce'    => 0,
		];

		foreach ($this->plugin_dirs as $dir) {
			$path = WP_PLUGIN_DIR . '/' . $dir;
			if (! is_dir($path)) {
				continue;
			}

			WP_CLI::log("Scanning: {$dir}");

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
			);

			foreach ($iterator as $file) {
				if (! $file->isFile() || 'php' !== $file->getExtension()) {
					continue;
				}

				$relative_path = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());

				// Skip vendor and other dirs.
				$should_skip = false;
				foreach ($this->skip_dirs as $skip_dir) {
					if (str_starts_with($relative_path, $skip_dir . DIRECTORY_SEPARATOR)) {
						$should_skip = true;
						break;
					}
				}
				if ($should_skip) {
					continue;
				}

				$result = $this->fix_security_in_file($file->getPathname(), $type, $dry_run);

				if ($result['changes'] > 0) {
					WP_CLI::log("  ✓ {$relative_path}: {$result['changes']} fixes");
				}

				foreach ($result['types'] as $fix_type => $count) {
					$fixes[$fix_type] += $count;
				}
			}
		}

		WP_CLI::log('');
		WP_CLI::success(sprintf(
			'Security fixes: %d escape, %d sanitize, %d nonce',
			$fixes['escape'],
			$fixes['sanitize'],
			$fixes['nonce']
		));
	}

	/**
	 * Fix security issues in a single file.
	 *
	 * @param string $file_path File path.
	 * @param string $type      Fix type.
	 * @param bool   $dry_run   Dry run mode.
	 * @return array{changes: int, types: array<string, int>}
	 */
	private function fix_security_in_file(string $file_path, string $type, bool $dry_run): array
	{
		$result = [
			'changes' => 0,
			'types'   => [
				'escape'   => 0,
				'sanitize' => 0,
				'nonce'    => 0,
			],
		];

		$content  = file_get_contents($file_path);
		$original = $content;

		if (false === $content) {
			return $result;
		}

		// Fix unescaped echo statements.
		if (in_array($type, ['all', 'escape'], true)) {
			// Pattern: echo $variable; → echo esc_html( $variable );
			$patterns = [
				// echo $var; (simple variable)
				'/echo\s+(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*;/' => function ($m) {
					return 'echo esc_html( ' . $m[1] . ' );';
				},
			];

			foreach ($patterns as $pattern => $replacement) {
				$new_content = preg_replace_callback($pattern, $replacement, $content);
				if ($new_content !== $content) {
					$count = preg_match_all($pattern, $content);
					$result['types']['escape'] += $count;
					$result['changes']         += $count;
					$content                    = $new_content;
				}
			}
		}

		// Write changes if not dry run and content changed.
		if (! $dry_run && $content !== $original) {
			file_put_contents($file_path, $content);
		}

		return $result;
	}

	/**
	 * Migrate namespaces to PSR-4 format.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * [--plugin=<plugin>]
	 * : Only process specific plugin.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo namespace-migrate --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function namespace_migrate(array $args, array $assoc_args): void
	{
		$dry_run = isset($assoc_args['dry-run']);
		$plugin  = $assoc_args['plugin'] ?? null;

		$dirs = $plugin ? [$plugin] : $this->plugin_dirs;

		// Load the NamespaceModernizer.
		$modernizer_file = WP_PLUGIN_DIR . '/apollo-core/src/Tools/NamespaceModernizer.php';
		if (file_exists($modernizer_file)) {
			require_once $modernizer_file;
		}

		if (! class_exists('\\Apollo\\Core\\Tools\\NamespaceModernizer')) {
			WP_CLI::error('NamespaceModernizer class not found.');
			return;
		}

		$total_issues  = 0;
		$total_fixed   = 0;

		foreach ($dirs as $dir) {
			$path = WP_PLUGIN_DIR . '/' . $dir;
			if (! is_dir($path)) {
				WP_CLI::warning("Plugin directory not found: {$dir}");
				continue;
			}

			WP_CLI::log("Scanning: {$dir}");

			$issues = \Apollo\Core\Tools\NamespaceModernizer::scanDirectory($path);

			foreach ($issues as $file => $file_issues) {
				$relative = str_replace(WP_PLUGIN_DIR . '/', '', $file);
				$total_issues += count($file_issues);

				foreach ($file_issues as $issue) {
					WP_CLI::log(sprintf(
						"  Line %d: %s → %s",
						$issue['line'],
						$issue['legacy'],
						$issue['modern']
					));
				}

				if (! $dry_run) {
					$result = \Apollo\Core\Tools\NamespaceModernizer::migrateFile($file, false);
					if ($result['migrated']) {
						$total_fixed += count($result['changes']);
					}
				}
			}
		}

		WP_CLI::log('');
		WP_CLI::success(sprintf(
			'Namespace issues: %d found, %d fixed',
			$total_issues,
			$dry_run ? 0 : $total_fixed
		));
	}

	/**
	 * Run full modernization suite.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be changed without making changes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo modernize --dry-run
	 *     wp apollo modernize
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function modernize(array $args, array $assoc_args): void
	{
		WP_CLI::log('╔══════════════════════════════════════════════════════════════╗');
		WP_CLI::log('║           APOLLO CODE MODERNIZER v2.1.0                      ║');
		WP_CLI::log('╚══════════════════════════════════════════════════════════════╝');
		WP_CLI::log('');

		// Step 1: Strict Types.
		WP_CLI::log('▶ Step 1: Adding strict_types declarations...');
		$this->strict_types([], $assoc_args);
		WP_CLI::log('');

		// Step 2: Namespace Migration.
		WP_CLI::log('▶ Step 2: Migrating namespaces to PSR-4...');
		$this->namespace_migrate([], $assoc_args);
		WP_CLI::log('');

		// Step 3: Security Fixes.
		WP_CLI::log('▶ Step 3: Applying security fixes...');
		$this->fix_security([], $assoc_args);
		WP_CLI::log('');

		WP_CLI::success('✓ Modernization complete!');
	}

	/**
	 * Show modernization status report.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function status(array $args, array $assoc_args): void
	{
		WP_CLI::log('╔══════════════════════════════════════════════════════════════╗');
		WP_CLI::log('║           APOLLO ECOSYSTEM STATUS                            ║');
		WP_CLI::log('╚══════════════════════════════════════════════════════════════╝');
		WP_CLI::log('');

		$stats = [
			'total_files'       => 0,
			'strict_types'      => 0,
			'legacy_namespaces' => 0,
			'missing_nonce'     => 0,
		];

		foreach ($this->plugin_dirs as $dir) {
			$path = WP_PLUGIN_DIR . '/' . $dir;
			if (! is_dir($path)) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
			);

			foreach ($iterator as $file) {
				if (! $file->isFile() || 'php' !== $file->getExtension()) {
					continue;
				}

				$relative = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());

				// Skip vendor.
				$should_skip = false;
				foreach ($this->skip_dirs as $skip_dir) {
					if (str_starts_with($relative, $skip_dir . DIRECTORY_SEPARATOR)) {
						$should_skip = true;
						break;
					}
				}
				if ($should_skip) {
					continue;
				}

				++$stats['total_files'];

				$content = file_get_contents($file->getPathname());
				if (false === $content) {
					continue;
				}

				// Check strict types.
				if (str_contains($content, 'declare(strict_types=1)') || str_contains($content, 'declare( strict_types=1 )')) {
					++$stats['strict_types'];
				}

				// Check legacy namespaces.
				if (preg_match('/namespace\s+[A-Za-z_]+\\\\/', $content) && preg_match('/namespace\s+[A-Za-z]+_[A-Za-z]/', $content)) {
					++$stats['legacy_namespaces'];
				}
			}
		}

		$strict_pct = $stats['total_files'] > 0
			? round(($stats['strict_types'] / $stats['total_files']) * 100, 1)
			: 0;

		WP_CLI::log(sprintf('📁 Total PHP Files:     %d', $stats['total_files']));
		WP_CLI::log(sprintf('✓ Strict Types:         %d (%s%%)', $stats['strict_types'], $strict_pct));
		WP_CLI::log(sprintf('⚠ Legacy Namespaces:    %d', $stats['legacy_namespaces']));
		WP_CLI::log('');

		if ($strict_pct >= 90) {
			WP_CLI::success('Code modernization: EXCELLENT');
		} elseif ($strict_pct >= 50) {
			WP_CLI::log('⚠ Code modernization: IN PROGRESS');
		} else {
			WP_CLI::warning('Code modernization: NEEDS WORK');
		}
	}
}

// Register commands.
WP_CLI::add_command('apollo', ModernizerCommands::class);
