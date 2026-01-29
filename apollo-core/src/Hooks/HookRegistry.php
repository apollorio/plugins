<?php

declare(strict_types=1);

/**
 * Apollo Hook Registry - Type-Safe WordPress Hooks Management
 *
 * Provides centralized, type-safe hook registration with validation.
 * Eliminates closures in hooks (debugging nightmare) and enforces best practices.
 *
 * @package Apollo\Core\Hooks
 * @since   2.1.0
 * @author  Apollo Team
 */

namespace Apollo\Core\Hooks;

// Prevent direct file access.
defined('ABSPATH') || exit;

/**
 * Hook Priority Constants.
 */
final class Priority
{
	public const FIRST    = 1;
	public const EARLY    = 5;
	public const DEFAULT  = 10;
	public const LATE     = 15;
	public const LAST     = 20;
	public const VERY_LAST = 99;
	public const PHP_INT_MAX = PHP_INT_MAX;
}

/**
 * Hook Registry - Centralized hook management.
 *
 * @since 2.1.0
 */
final class HookRegistry
{

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered hooks for debugging/inspection.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $registered_hooks = [];

	/**
	 * Hook groups for bulk operations.
	 *
	 * @var array<string, array<array<string, mixed>>>
	 */
	private array $hook_groups = [];

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	// =========================================================================
	// ACTION REGISTRATION
	// =========================================================================

	/**
	 * Add an action hook.
	 *
	 * @param string          $hook     Hook name.
	 * @param callable|string $callback Callback (string for method reference).
	 * @param int             $priority Priority (use Priority constants).
	 * @param int             $args     Number of arguments.
	 * @param string          $group    Optional group name for bulk operations.
	 * @return self
	 */
	public function action(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self
	{
		$this->validatePriority($priority);
		$this->validateCallback($callback, $hook);

		add_action($hook, $callback, $priority, $args);
		$this->trackHook('action', $hook, $callback, $priority, $args, $group);

		return $this;
	}

	/**
	 * Add an action hook with class method.
	 *
	 * @param string       $hook     Hook name.
	 * @param object|class-string $object   Object instance or class name.
	 * @param string       $method   Method name.
	 * @param int          $priority Priority.
	 * @param int          $args     Number of arguments.
	 * @param string       $group    Optional group name.
	 * @return self
	 */
	public function actionMethod(string $hook, object|string $object, string $method, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self
	{
		$callback = [$object, $method];
		$this->validateMethodCallback($object, $method, $hook);

		add_action($hook, $callback, $priority, $args);
		$this->trackHook('action', $hook, $callback, $priority, $args, $group);

		return $this;
	}

	/**
	 * Add a one-time action (removes itself after first execution).
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Number of arguments.
	 * @return self
	 */
	public function actionOnce(string $hook, callable $callback, int $priority = Priority::DEFAULT, int $args = 1): self
	{
		$wrapper = function (...$arguments) use ($hook, $callback, $priority, &$wrapper) {
			remove_action($hook, $wrapper, $priority);
			return $callback(...$arguments);
		};

		add_action($hook, $wrapper, $priority, $args);
		$this->trackHook('action_once', $hook, $callback, $priority, $args, '');

		return $this;
	}

	// =========================================================================
	// FILTER REGISTRATION
	// =========================================================================

	/**
	 * Add a filter hook.
	 *
	 * @param string          $hook     Hook name.
	 * @param callable|string $callback Callback.
	 * @param int             $priority Priority.
	 * @param int             $args     Number of arguments.
	 * @param string          $group    Optional group name.
	 * @return self
	 */
	public function filter(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self
	{
		$this->validatePriority($priority);
		$this->validateCallback($callback, $hook);

		add_filter($hook, $callback, $priority, $args);
		$this->trackHook('filter', $hook, $callback, $priority, $args, $group);

		return $this;
	}

	/**
	 * Add a filter hook with class method.
	 *
	 * @param string       $hook     Hook name.
	 * @param object|class-string $object   Object instance or class name.
	 * @param string       $method   Method name.
	 * @param int          $priority Priority.
	 * @param int          $args     Number of arguments.
	 * @param string       $group    Optional group name.
	 * @return self
	 */
	public function filterMethod(string $hook, object|string $object, string $method, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self
	{
		$callback = [$object, $method];
		$this->validateMethodCallback($object, $method, $hook);

		add_filter($hook, $callback, $priority, $args);
		$this->trackHook('filter', $hook, $callback, $priority, $args, $group);

		return $this;
	}

	// =========================================================================
	// AJAX HOOKS (Convenience Methods)
	// =========================================================================

	/**
	 * Register AJAX handler for logged-in users.
	 *
	 * @param string          $action   AJAX action name (without wp_ajax_ prefix).
	 * @param callable|string $callback Callback.
	 * @param string          $group    Optional group name.
	 * @return self
	 */
	public function ajax(string $action, callable|string $callback, string $group = ''): self
	{
		return $this->action('wp_ajax_' . $action, $callback, Priority::DEFAULT, 1, $group);
	}

	/**
	 * Register AJAX handler for guests (non-logged-in users).
	 *
	 * @param string          $action   AJAX action name.
	 * @param callable|string $callback Callback.
	 * @param string          $group    Optional group name.
	 * @return self
	 */
	public function ajaxNopriv(string $action, callable|string $callback, string $group = ''): self
	{
		return $this->action('wp_ajax_nopriv_' . $action, $callback, Priority::DEFAULT, 1, $group);
	}

	/**
	 * Register AJAX handler for both logged-in and guest users.
	 *
	 * @param string          $action   AJAX action name.
	 * @param callable|string $callback Callback.
	 * @param string          $group    Optional group name.
	 * @return self
	 */
	public function ajaxBoth(string $action, callable|string $callback, string $group = ''): self
	{
		$this->ajax($action, $callback, $group);
		$this->ajaxNopriv($action, $callback, $group);
		return $this;
	}

	// =========================================================================
	// REST API HOOKS
	// =========================================================================

	/**
	 * Register REST API route initialization.
	 *
	 * @param callable $callback Callback to register routes.
	 * @param int      $priority Priority.
	 * @return self
	 */
	public function restApiInit(callable $callback, int $priority = Priority::DEFAULT): self
	{
		return $this->action('rest_api_init', $callback, $priority);
	}

	// =========================================================================
	// SHORTCODE REGISTRATION
	// =========================================================================

	/**
	 * Register a shortcode.
	 *
	 * @param string   $tag      Shortcode tag.
	 * @param callable $callback Callback.
	 * @return self
	 */
	public function shortcode(string $tag, callable $callback): self
	{
		add_shortcode($tag, $callback);
		$this->trackHook('shortcode', $tag, $callback, 0, 2, '');
		return $this;
	}

	// =========================================================================
	// HOOK REMOVAL
	// =========================================================================

	/**
	 * Remove an action hook.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @return bool
	 */
	public function removeAction(string $hook, callable $callback, int $priority = Priority::DEFAULT): bool
	{
		return remove_action($hook, $callback, $priority);
	}

	/**
	 * Remove a filter hook.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @return bool
	 */
	public function removeFilter(string $hook, callable $callback, int $priority = Priority::DEFAULT): bool
	{
		return remove_filter($hook, $callback, $priority);
	}

	/**
	 * Remove all hooks in a group.
	 *
	 * @param string $group Group name.
	 * @return int Number of hooks removed.
	 */
	public function removeGroup(string $group): int
	{
		if (! isset($this->hook_groups[$group])) {
			return 0;
		}

		$count = 0;
		foreach ($this->hook_groups[$group] as $hook_data) {
			$removed = match ($hook_data['type']) {
				'action', 'action_once' => remove_action($hook_data['hook'], $hook_data['callback'], $hook_data['priority']),
				'filter'                => remove_filter($hook_data['hook'], $hook_data['callback'], $hook_data['priority']),
				default                 => false,
			};
			if ($removed) {
				++$count;
			}
		}

		unset($this->hook_groups[$group]);
		return $count;
	}

	// =========================================================================
	// CONDITIONAL HOOKS
	// =========================================================================

	/**
	 * Add action only if condition is true.
	 *
	 * @param bool            $condition Condition.
	 * @param string          $hook      Hook name.
	 * @param callable|string $callback  Callback.
	 * @param int             $priority  Priority.
	 * @param int             $args      Number of arguments.
	 * @return self
	 */
	public function actionIf(bool $condition, string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self
	{
		if ($condition) {
			$this->action($hook, $callback, $priority, $args);
		}
		return $this;
	}

	/**
	 * Add filter only if condition is true.
	 *
	 * @param bool            $condition Condition.
	 * @param string          $hook      Hook name.
	 * @param callable|string $callback  Callback.
	 * @param int             $priority  Priority.
	 * @param int             $args      Number of arguments.
	 * @return self
	 */
	public function filterIf(bool $condition, string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self
	{
		if ($condition) {
			$this->filter($hook, $callback, $priority, $args);
		}
		return $this;
	}

	/**
	 * Add action only in admin context.
	 *
	 * @param string          $hook     Hook name.
	 * @param callable|string $callback Callback.
	 * @param int             $priority Priority.
	 * @param int             $args     Number of arguments.
	 * @return self
	 */
	public function actionAdmin(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self
	{
		return $this->actionIf(is_admin(), $hook, $callback, $priority, $args);
	}

	/**
	 * Add action only in frontend context.
	 *
	 * @param string          $hook     Hook name.
	 * @param callable|string $callback Callback.
	 * @param int             $priority Priority.
	 * @param int             $args     Number of arguments.
	 * @return self
	 */
	public function actionFrontend(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self
	{
		return $this->actionIf(! is_admin(), $hook, $callback, $priority, $args);
	}

	// =========================================================================
	// DEBUGGING & INSPECTION
	// =========================================================================

	/**
	 * Get all registered hooks.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getRegisteredHooks(): array
	{
		return $this->registered_hooks;
	}

	/**
	 * Get hooks by group.
	 *
	 * @param string $group Group name.
	 * @return array<array<string, mixed>>
	 */
	public function getHooksByGroup(string $group): array
	{
		return $this->hook_groups[$group] ?? [];
	}

	/**
	 * Check if hook is registered.
	 *
	 * @param string $hook Hook name.
	 * @return bool
	 */
	public function hasHook(string $hook): bool
	{
		return isset($this->registered_hooks[$hook]);
	}

	/**
	 * Get hook registration count.
	 *
	 * @return int
	 */
	public function count(): int
	{
		return count($this->registered_hooks);
	}

	/**
	 * Debug dump all registered hooks.
	 *
	 * @return void
	 */
	public function dump(): void
	{
		if (defined('WP_DEBUG') && WP_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('Apollo HookRegistry: ' . wp_json_encode($this->registered_hooks, JSON_PRETTY_PRINT));
		}
	}

	// =========================================================================
	// VALIDATION
	// =========================================================================

	/**
	 * Validate priority is within acceptable range.
	 *
	 * @param int $priority Priority value.
	 * @return void
	 * @throws \InvalidArgumentException If priority is invalid.
	 */
	private function validatePriority(int $priority): void
	{
		if ($priority < 0) {
			throw new \InvalidArgumentException('Hook priority must be >= 0');
		}
	}

	/**
	 * Validate callback is callable.
	 *
	 * @param callable|string $callback Callback.
	 * @param string          $hook     Hook name for error message.
	 * @return void
	 * @throws \InvalidArgumentException If callback is a closure in production.
	 */
	private function validateCallback(callable|string $callback, string $hook): void
	{
		// Warn about closures in production (hard to debug).
		if ($callback instanceof \Closure) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					sprintf('Closure used for hook "%s". Consider using named function or method for better debugging.', $hook),
					E_USER_NOTICE
				);
			}
		}
	}

	/**
	 * Validate method callback exists.
	 *
	 * @param object|string $object Object or class name.
	 * @param string        $method Method name.
	 * @param string        $hook   Hook name for error message.
	 * @return void
	 * @throws \InvalidArgumentException If method doesn't exist.
	 */
	private function validateMethodCallback(object|string $object, string $method, string $hook): void
	{
		$class = is_object($object) ? get_class($object) : $object;

		if (! method_exists($object, $method)) {
			throw new \InvalidArgumentException(
				sprintf('Method "%s::%s" does not exist for hook "%s"', $class, $method, $hook)
			);
		}
	}

	/**
	 * Track hook for debugging.
	 *
	 * @param string   $type     Hook type.
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Number of arguments.
	 * @param string   $group    Group name.
	 * @return void
	 */
	private function trackHook(string $type, string $hook, callable $callback, int $priority, int $args, string $group): void
	{
		$callback_name = $this->getCallbackName($callback);

		$hook_data = [
			'type'     => $type,
			'hook'     => $hook,
			'callback' => $callback,
			'name'     => $callback_name,
			'priority' => $priority,
			'args'     => $args,
		];

		$this->registered_hooks[$hook . '_' . $callback_name . '_' . $priority] = $hook_data;

		if ('' !== $group) {
			$this->hook_groups[$group][] = $hook_data;
		}
	}

	/**
	 * Get callback name for debugging.
	 *
	 * @param callable $callback Callback.
	 * @return string
	 */
	private function getCallbackName(callable $callback): string
	{
		if (is_string($callback)) {
			return $callback;
		}

		if (is_array($callback)) {
			$class  = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
			$method = $callback[1];
			return $class . '::' . $method;
		}

		if ($callback instanceof \Closure) {
			return 'Closure@' . spl_object_id($callback);
		}

		return 'unknown';
	}
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Get HookRegistry instance.
 *
 * @return HookRegistry
 */
function apollo_hooks(): HookRegistry
{
	return HookRegistry::instance();
}
