<?php

declare(strict_types=1);

/**
 * Apollo Namespace Modernizer - PSR-4 Compliance Tool
 *
 * Provides utilities to migrate legacy namespaces to modern PSR-4 format.
 * Run via WP-CLI: wp apollo namespace:migrate
 *
 * @package Apollo\Core\Tools
 * @since   2.1.0
 * @author  Apollo Team
 */

namespace Apollo\Core\Tools;

// Prevent direct file access.
defined('ABSPATH') || exit;

/**
 * Namespace mapping from legacy to modern format.
 */
final class NamespaceMap
{

	/**
	 * Legacy to modern namespace mappings.
	 *
	 * @var array<string, string>
	 */
	public const MAPPINGS = [
		// Apollo Core
		'Apollo_Core'                     => 'Apollo\\Core',
		'Apollo_Core\\Admin'              => 'Apollo\\Core\\Admin',
		'Apollo_Core\\API'                => 'Apollo\\Core\\Api',
		'Apollo_Core\\AJAX'               => 'Apollo\\Core\\Ajax',
		'Apollo_Core\\Database'           => 'Apollo\\Core\\Database',
		'Apollo_Core\\Modules'            => 'Apollo\\Core\\Modules',
		'Apollo_Core\\Security'           => 'Apollo\\Core\\Security',
		'Apollo_Core\\Hooks'              => 'Apollo\\Core\\Hooks',
		'Apollo_Core\\Services'           => 'Apollo\\Core\\Services',

		// Apollo Events Manager
		'Apollo_Events'                   => 'Apollo\\Events',
		'Apollo_Events_Manager'           => 'Apollo\\Events',
		'ApolloEventsManager'             => 'Apollo\\Events',
		'Apollo\\EventsManager'           => 'Apollo\\Events',
		'Apollo_Events\\Admin'            => 'Apollo\\Events\\Admin',
		'Apollo_Events\\REST'             => 'Apollo\\Events\\Rest',
		'Apollo_Events\\Modules'          => 'Apollo\\Events\\Modules',

		// Apollo Social
		'Apollo_Social'                   => 'Apollo\\Social',
		'ApolloSocial'                    => 'Apollo\\Social',
		'Apollo\\Social\\Modules'         => 'Apollo\\Social\\Modules',
		'Apollo\\Social\\Infrastructure'  => 'Apollo\\Social\\Infrastructure',
		'Apollo\\Social\\RestAPI'         => 'Apollo\\Social\\Rest',
		'Apollo\\Social\\CenaRio'         => 'Apollo\\Social\\CenaRio',

		// Apollo Rio
		'Apollo_Rio'                      => 'Apollo\\Rio',
		'ApolloRio'                       => 'Apollo\\Rio',
	];

	/**
	 * Get modern namespace from legacy.
	 *
	 * @param string $legacy_namespace Legacy namespace.
	 * @return string Modern namespace.
	 */
	public static function modernize(string $legacy_namespace): string
	{
		// Direct mapping.
		if (isset(self::MAPPINGS[$legacy_namespace])) {
			return self::MAPPINGS[$legacy_namespace];
		}

		// Check if already modern.
		if (str_starts_with($legacy_namespace, 'Apollo\\') && ! str_contains($legacy_namespace, '_')) {
			return $legacy_namespace;
		}

		// Transform underscores to backslashes.
		$modernized = str_replace('_', '\\', $legacy_namespace);

		// Ensure starts with Apollo\.
		if (! str_starts_with($modernized, 'Apollo\\')) {
			$modernized = 'Apollo\\' . $modernized;
		}

		return $modernized;
	}

	/**
	 * Check if namespace is legacy format.
	 *
	 * @param string $namespace Namespace to check.
	 * @return bool
	 */
	public static function isLegacy(string $namespace): bool
	{
		// Contains underscore in namespace declaration.
		if (str_contains($namespace, '_')) {
			return true;
		}

		// Known legacy namespaces.
		return isset(self::MAPPINGS[$namespace]);
	}

	/**
	 * Get all legacy namespaces.
	 *
	 * @return array<string>
	 */
	public static function getLegacyNamespaces(): array
	{
		return array_keys(self::MAPPINGS);
	}
}

/**
 * Class Alias Registry - Backwards compatibility layer.
 *
 * Provides class aliases so legacy code continues to work
 * while we migrate to modern namespaces.
 */
final class ClassAliasRegistry
{

	/**
	 * Registered aliases.
	 *
	 * @var array<string, string>
	 */
	private static array $aliases = [];

	/**
	 * Has been initialized.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize alias registry.
	 *
	 * @return void
	 */
	public static function init(): void
	{
		if (self::$initialized) {
			return;
		}

		spl_autoload_register([self::class, 'autoload'], true, true);
		self::$initialized = true;
	}

	/**
	 * Register a class alias.
	 *
	 * @param string $legacy_class Legacy class name.
	 * @param string $modern_class Modern class name.
	 * @return void
	 */
	public static function alias(string $legacy_class, string $modern_class): void
	{
		self::$aliases[$legacy_class] = $modern_class;
	}

	/**
	 * Register multiple aliases.
	 *
	 * @param array<string, string> $aliases Legacy => Modern mappings.
	 * @return void
	 */
	public static function registerAliases(array $aliases): void
	{
		foreach ($aliases as $legacy => $modern) {
			self::alias($legacy, $modern);
		}
	}

	/**
	 * Autoload handler for legacy classes.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function autoload(string $class): void
	{
		if (isset(self::$aliases[$class])) {
			$modern_class = self::$aliases[$class];

			// Trigger deprecation notice in debug mode.
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					sprintf(
						'Class "%s" is deprecated. Use "%s" instead.',
						$class,
						$modern_class
					),
					E_USER_DEPRECATED
				);
			}

			// Create the alias if the modern class exists.
			if (class_exists($modern_class, true)) {
				class_alias($modern_class, $class);
			}
		}
	}

	/**
	 * Get all registered aliases.
	 *
	 * @return array<string, string>
	 */
	public static function getAliases(): array
	{
		return self::$aliases;
	}
}

/**
 * PSR-4 Autoloader for Apollo plugins.
 */
final class Psr4Autoloader
{

	/**
	 * Namespace to directory mappings.
	 *
	 * @var array<string, string>
	 */
	private static array $namespaces = [];

	/**
	 * Has been registered.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void
	{
		if (self::$registered) {
			return;
		}

		spl_autoload_register([self::class, 'autoload']);
		self::$registered = true;

		// Initialize class alias registry.
		ClassAliasRegistry::init();
	}

	/**
	 * Add a namespace mapping.
	 *
	 * @param string $namespace Namespace prefix.
	 * @param string $directory Base directory.
	 * @return void
	 */
	public static function addNamespace(string $namespace, string $directory): void
	{
		$namespace = trim($namespace, '\\') . '\\';
		$directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		self::$namespaces[$namespace] = $directory;
	}

	/**
	 * Autoload a class.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function autoload(string $class): void
	{
		// Check each registered namespace.
		foreach (self::$namespaces as $namespace => $directory) {
			if (! str_starts_with($class, $namespace)) {
				continue;
			}

			// Get relative class name.
			$relative_class = substr($class, strlen($namespace));

			// Convert namespace separators to directory separators.
			$file = $directory . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

			if (file_exists($file)) {
				require_once $file;
				return;
			}

			// Try WordPress naming convention (class-name-here.php).
			$wp_file = $directory . 'class-' . strtolower(str_replace(['\\', '_'], '-', $relative_class)) . '.php';
			if (file_exists($wp_file)) {
				require_once $wp_file;
				return;
			}
		}
	}

	/**
	 * Get registered namespaces.
	 *
	 * @return array<string, string>
	 */
	public static function getNamespaces(): array
	{
		return self::$namespaces;
	}
}

/**
 * Namespace Modernizer - Migration utility.
 *
 * Scans files and provides migration suggestions.
 */
final class NamespaceModernizer
{

	/**
	 * Scan a file for legacy namespaces.
	 *
	 * @param string $file_path File path.
	 * @return array<array{line: int, legacy: string, modern: string}>
	 */
	public static function scanFile(string $file_path): array
	{
		if (! file_exists($file_path)) {
			return [];
		}

		$content = file_get_contents($file_path);
		if (false === $content) {
			return [];
		}

		$issues = [];
		$lines  = explode("\n", $content);

		foreach ($lines as $line_num => $line) {
			// Check namespace declaration.
			if (preg_match('/^\s*namespace\s+([^;]+);/', $line, $matches)) {
				$namespace = trim($matches[1]);
				if (NamespaceMap::isLegacy($namespace)) {
					$issues[] = [
						'line'   => $line_num + 1,
						'legacy' => $namespace,
						'modern' => NamespaceMap::modernize($namespace),
					];
				}
			}

			// Check use statements.
			if (preg_match('/^\s*use\s+([^;]+);/', $line, $matches)) {
				$use_statement = trim($matches[1]);
				$parts         = explode(' as ', $use_statement);
				$class_path    = trim($parts[0]);

				// Extract namespace from full class path.
				$last_slash = strrpos($class_path, '\\');
				if (false !== $last_slash) {
					$namespace = substr($class_path, 0, $last_slash);
					if (NamespaceMap::isLegacy($namespace)) {
						$issues[] = [
							'line'   => $line_num + 1,
							'legacy' => $namespace,
							'modern' => NamespaceMap::modernize($namespace),
						];
					}
				}
			}
		}

		return $issues;
	}

	/**
	 * Migrate a file to modern namespaces.
	 *
	 * @param string $file_path File path.
	 * @param bool   $dry_run   If true, don't write changes.
	 * @return array{migrated: bool, changes: array<array{line: int, before: string, after: string}>}
	 */
	public static function migrateFile(string $file_path, bool $dry_run = true): array
	{
		$result = [
			'migrated' => false,
			'changes'  => [],
		];

		if (! file_exists($file_path)) {
			return $result;
		}

		$content  = file_get_contents($file_path);
		$original = $content;
		if (false === $content) {
			return $result;
		}

		$lines = explode("\n", $content);

		foreach ($lines as $line_num => &$line) {
			$original_line = $line;

			// Migrate namespace declaration.
			if (preg_match('/^(\s*namespace\s+)([^;]+)(;.*)$/', $line, $matches)) {
				$namespace = trim($matches[2]);
				if (NamespaceMap::isLegacy($namespace)) {
					$modern = NamespaceMap::modernize($namespace);
					$line   = $matches[1] . $modern . $matches[3];
				}
			}

			// Migrate use statements.
			if (preg_match('/^(\s*use\s+)([^;]+)(;.*)$/', $line, $matches)) {
				$use_part = $matches[2];
				$migrated = self::migrateUseStatement($use_part);
				if ($migrated !== $use_part) {
					$line = $matches[1] . $migrated . $matches[3];
				}
			}

			if ($line !== $original_line) {
				$result['changes'][] = [
					'line'   => $line_num + 1,
					'before' => $original_line,
					'after'  => $line,
				];
			}
		}

		if (! empty($result['changes']) && ! $dry_run) {
			$new_content = implode("\n", $lines);
			file_put_contents($file_path, $new_content);
			$result['migrated'] = true;
		}

		return $result;
	}

	/**
	 * Migrate a use statement.
	 *
	 * @param string $use_statement Use statement content.
	 * @return string Migrated statement.
	 */
	private static function migrateUseStatement(string $use_statement): string
	{
		// Handle "as" aliases.
		$parts = explode(' as ', $use_statement);
		$class = trim($parts[0]);
		$alias = isset($parts[1]) ? ' as ' . trim($parts[1]) : '';

		// Split into namespace and class name.
		$last_slash = strrpos($class, '\\');
		if (false !== $last_slash) {
			$namespace  = substr($class, 0, $last_slash);
			$class_name = substr($class, $last_slash + 1);

			if (NamespaceMap::isLegacy($namespace)) {
				$modern = NamespaceMap::modernize($namespace);
				return $modern . '\\' . $class_name . $alias;
			}
		}

		return $use_statement;
	}

	/**
	 * Scan directory for legacy namespaces.
	 *
	 * @param string $directory Directory path.
	 * @return array<string, array<array{line: int, legacy: string, modern: string}>>
	 */
	public static function scanDirectory(string $directory): array
	{
		$results = [];

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isFile() && 'php' === $file->getExtension()) {
				$issues = self::scanFile($file->getPathname());
				if (! empty($issues)) {
					$results[$file->getPathname()] = $issues;
				}
			}
		}

		return $results;
	}
}

// =========================================================================
// BOOTSTRAP - Register default namespaces
// =========================================================================

// Auto-register when loaded.
add_action('plugins_loaded', function () {
	Psr4Autoloader::register();

	// Define plugin base paths.
	$plugins_dir = WP_PLUGIN_DIR;

	// Apollo Core.
	if (file_exists($plugins_dir . '/apollo-core/src')) {
		Psr4Autoloader::addNamespace('Apollo\\Core', $plugins_dir . '/apollo-core/src');
	}

	// Apollo Events.
	if (file_exists($plugins_dir . '/apollo-events-manager/src')) {
		Psr4Autoloader::addNamespace('Apollo\\Events', $plugins_dir . '/apollo-events-manager/src');
	}

	// Apollo Social.
	if (file_exists($plugins_dir . '/apollo-social/src')) {
		Psr4Autoloader::addNamespace('Apollo\\Social', $plugins_dir . '/apollo-social/src');
	}

	// Apollo Rio.
	if (file_exists($plugins_dir . '/apollo-rio/src')) {
		Psr4Autoloader::addNamespace('Apollo\\Rio', $plugins_dir . '/apollo-rio/src');
	}

	// Register class aliases for backwards compatibility.
	ClassAliasRegistry::registerAliases([
		// Add legacy => modern class mappings here as needed.
		// 'Apollo_Core_Something' => 'Apollo\\Core\\Something',
	]);
}, 1);
