<?php

/**
 * Apollo Ecosystem - Unified Alignment Bridge
 *
 * This facade provides a single entry point to the Apollo ecosystem.
 * It delegates to existing infrastructure (BridgeLoader, SafeQuery, etc.)
 * while adding type-safe helpers and IDE-friendly interfaces.
 *
 * @package Apollo\Core
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

use Apollo\Core\Database\SafeQuery;
use Apollo\Core\Database\BatchLoader;
use Apollo\Core\Database\QueryCache;
use Apollo_Core\Bridge\BridgeLoader;
use Apollo_Core\Integration\Apollo_Integration_Bridge;

// Prevent direct access.
defined('ABSPATH') || exit;

/**
 * Unified Alignment Bridge - Single entry point for Apollo ecosystem.
 *
 * Consolidates access to:
 * - Plugin registration and discovery (via BridgeLoader)
 * - Integration hooks (via Apollo_Integration_Bridge)
 * - Safe database operations (via SafeQuery)
 * - Batch loading (via BatchLoader)
 * - Query caching (via QueryCache)
 *
 * @since 3.0.0
 */
final class AlignmentBridge
{

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Plugin registry with status.
	 *
	 * @var array<string, array{loaded: bool, version: string, namespace: string, path: string, required: bool}>
	 */
	private array $plugins = [];

	/**
	 * Validation errors.
	 *
	 * @var array<string, string>
	 */
	private array $errors = [];

	/**
	 * Schema registry.
	 *
	 * @var array<string, array<string, array>>
	 */
	private array $schema = [];

	/**
	 * Meta key canonical aliases.
	 *
	 * @var array<string, string>
	 */
	private array $metaAliases = [
		'_event_timetable'       => '_event_dj_slots',
		'_event_latitude'        => '_event_lat',
		'_event_longitude'       => '_event_lng',
		'_local_latitude'        => '_local_lat',
		'_local_longitude'       => '_local_lng',
		'_venue_lat'             => '_local_lat',
		'_venue_lng'             => '_local_lng',
		'_apollo_bookmark_count' => '_favorites_count',
	];

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct()
	{
		$this->discoverPlugins();
	}

	/**
	 * Discover all Apollo plugins.
	 *
	 * @return void
	 */
	private function discoverPlugins(): void
	{
		$this->plugins = [
			'apollo-core' => [
				'loaded'    => defined('APOLLO_CORE_VERSION'),
				'version'   => defined('APOLLO_CORE_VERSION') ? (string) APOLLO_CORE_VERSION : '0.0.0',
				'namespace' => 'Apollo\\Core\\',
				'path'      => defined('APOLLO_CORE_PATH') ? (string) APOLLO_CORE_PATH : '',
				'required'  => true,
			],
			'apollo-social' => [
				'loaded'    => defined('APOLLO_SOCIAL_VERSION'),
				'version'   => defined('APOLLO_SOCIAL_VERSION') ? (string) APOLLO_SOCIAL_VERSION : '0.0.0',
				'namespace' => 'Apollo\\Social\\',
				'path'      => defined('APOLLO_SOCIAL_PATH') ? (string) APOLLO_SOCIAL_PATH : '',
				'required'  => false,
			],
			'apollo-events-manager' => [
				'loaded'    => defined('APOLLO_EVENTS_MANAGER_VERSION'),
				'version'   => defined('APOLLO_EVENTS_MANAGER_VERSION') ? (string) APOLLO_EVENTS_MANAGER_VERSION : '0.0.0',
				'namespace' => 'Apollo\\Events\\',
				'path'      => defined('APOLLO_EVENTS_PATH') ? (string) APOLLO_EVENTS_PATH : '',
				'required'  => false,
			],
			'apollo-rio' => [
				'loaded'    => defined('APOLLO_RIO_VERSION'),
				'version'   => defined('APOLLO_RIO_VERSION') ? (string) APOLLO_RIO_VERSION : '0.0.0',
				'namespace' => 'Apollo\\Rio\\',
				'path'      => defined('APOLLO_RIO_PATH') ? (string) APOLLO_RIO_PATH : '',
				'required'  => false,
			],
		];
	}

	/**
	 * Boot the alignment bridge.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// Initialize underlying bridge systems.
		if (class_exists(BridgeLoader::class)) {
			BridgeLoader::getInstance()->init();
		}

		// Validation on admin (for debugging).
		if (is_admin()) {
			add_action('admin_init', [$this, 'validateAll']);
		}

		// Register unified schema hooks.
		add_action('init', [$this, 'registerUnifiedSchema'], 5);

		// Bridge plugins via hooks.
		add_action('init', [$this, 'bridgePlugins'], 10);
	}

	/**
	 * Validate all plugins and integration.
	 *
	 * @return bool True if valid.
	 */
	public function validateAll(): bool
	{
		$this->errors = [];

		$this->validatePluginDependencies();
		$this->validateDatabaseTables();

		if (! empty($this->errors) && current_user_can('manage_options')) {
			add_action('admin_notices', [$this, 'displayValidationErrors']);
		}

		return empty($this->errors);
	}

	/**
	 * Validate plugin dependencies.
	 *
	 * @return void
	 */
	private function validatePluginDependencies(): void
	{
		if (! $this->plugins['apollo-core']['loaded']) {
			$this->errors['core_missing'] = __('Apollo Core is required but not loaded.', 'apollo-core');
			return;
		}

		$core_version = $this->plugins['apollo-core']['version'];
		$core_major   = explode('.', $core_version)[0] ?? '0';

		foreach ($this->plugins as $slug => $plugin) {
			if ('apollo-core' === $slug || ! $plugin['loaded']) {
				continue;
			}

			$plugin_major = explode('.', $plugin['version'])[0] ?? '0';

			if ($core_major !== $plugin_major) {
				$this->errors["version_{$slug}"] = sprintf(
					/* translators: 1: plugin slug 2: plugin version 3: core version */
					__('%1$s version %2$s is incompatible with Apollo Core %3$s', 'apollo-core'),
					$slug,
					$plugin['version'],
					$core_version
				);
			}
		}
	}

	/**
	 * Validate database tables exist.
	 *
	 * @return void
	 */
	private function validateDatabaseTables(): void
	{
		$required_tables = [
			'apollo_mod_log'    => 'apollo-core',
			'apollo_activity'   => 'apollo-social',
			'apollo_connections' => 'apollo-social',
		];

		foreach ($required_tables as $table => $owner) {
			if (! $this->plugins[$owner]['loaded']) {
				continue;
			}

			if (! SafeQuery::tableExists($table)) {
				$this->errors["table_{$table}"] = sprintf(
					/* translators: 1: table name 2: owner plugin */
					__('Required table %1$s missing (owner: %2$s)', 'apollo-core'),
					$table,
					$owner
				);
			}
		}
	}

	/**
	 * Register unified schema.
	 *
	 * @return void
	 */
	public function registerUnifiedSchema(): void
	{
		/**
		 * Action for plugins to register their schemas.
		 *
		 * @param self $bridge The AlignmentBridge instance.
		 */
		do_action('apollo_register_schema', $this);

		$this->registerCoreMeta();
	}

	/**
	 * Register core meta keys.
	 *
	 * @return void
	 */
	private function registerCoreMeta(): void
	{
		$user_meta = [
			'_apollo_membership'       => [
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_key',
				'description'       => __('User membership type slug', 'apollo-core'),
			],
			'_apollo_membership_since' => [
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __('Membership start date', 'apollo-core'),
			],
			'_apollo_verified'         => [
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'description'       => __('User verification status', 'apollo-core'),
			],
		];

		foreach ($user_meta as $key => $args) {
			register_meta('user', $key, $args);
		}

		/**
		 * Action for plugins to register their meta.
		 *
		 * @param self $bridge The AlignmentBridge instance.
		 */
		do_action('apollo_register_meta', $this);
	}

	/**
	 * Bridge plugins together.
	 *
	 * @return void
	 */
	public function bridgePlugins(): void
	{
		/**
		 * Action fired when core is ready.
		 *
		 * @param self $bridge The AlignmentBridge instance.
		 */
		do_action('apollo_core_ready', $this);

		// Bridge Events ↔ Social.
		if ($this->isPluginLoaded('apollo-events-manager') && $this->isPluginLoaded('apollo-social')) {
			$this->bridgeEventsSocial();
		}

		// Bridge Social → Core.
		if ($this->isPluginLoaded('apollo-social')) {
			$this->bridgeSocialCore();
		}

		// Bridge Rio.
		if ($this->isPluginLoaded('apollo-rio')) {
			$this->bridgeRio();
		}
	}

	/**
	 * Bridge Events ↔ Social.
	 *
	 * @return void
	 */
	private function bridgeEventsSocial(): void
	{
		// Sync event bookmarks to social activity.
		add_action('apollo_event_bookmarked', static function (int $event_id, int $user_id): void {
			do_action('apollo_social_activity', 'bookmark', $user_id, $event_id, 'event_listing');
		}, 10, 2);

		// Sync DJ profiles to user profiles.
		add_action('apollo_dj_profile_updated', static function (int $dj_id): void {
			$user_id = get_post_meta($dj_id, '_dj_user_id', true);
			if ($user_id) {
				do_action('apollo_sync_user_profile', (int) $user_id);
			}
		});

		// Share event data with groups.
		add_filter('apollo_group_events', static function (array $events, int $group_id): array {
			$linked = get_posts([
				'post_type'      => 'event_listing',
				'posts_per_page' => 50,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => '_event_group_id',
						'value' => $group_id,
					],
				],
			]);
			return array_merge($events, $linked);
		}, 10, 2);
	}

	/**
	 * Bridge Social → Core.
	 *
	 * @return void
	 */
	private function bridgeSocialCore(): void
	{
		// Log membership changes.
		add_action('apollo_membership_changed', static function (int $user_id, string $old, string $new): void {
			do_action('apollo_audit_log', 'membership_change', [
				'user_id' => $user_id,
				'old'     => $old,
				'new'     => $new,
			]);
		}, 10, 3);

		// Bridge capability checks.
		add_filter('apollo_user_can', static function (bool $can, int $user_id, string $capability): bool {
			$caps = [
				'sign_documents'   => 'apollo_sign_docs',
				'create_nucleo'    => 'apollo_create_group',
				'moderate_comunas' => 'apollo_moderate',
			];

			if (isset($caps[$capability])) {
				return user_can($user_id, $caps[$capability]);
			}

			return $can;
		}, 10, 3);
	}

	/**
	 * Bridge Rio customizations.
	 *
	 * @return void
	 */
	private function bridgeRio(): void
	{
		add_filter('apollo_locale', static function (): string {
			return 'pt_BR';
		});

		add_filter('apollo_pwa_manifest', static function (array $manifest): array {
			$manifest['name']        = 'Apollo::rio';
			$manifest['short_name']  = 'Apollo';
			$manifest['description'] = 'Cultura carioca na palma da mão';
			return $manifest;
		});
	}

	/**
	 * Display validation errors.
	 *
	 * @return void
	 */
	public function displayValidationErrors(): void
	{
		if (empty($this->errors)) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>' . esc_html__('Apollo Alignment Issues:', 'apollo-core') . '</strong></p><ul>';
		foreach ($this->errors as $code => $message) {
			printf(
				'<li><code>%s</code>: %s</li>',
				esc_html($code),
				esc_html($message)
			);
		}
		echo '</ul></div>';
	}

	/**
	 * Check if plugin is loaded.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public function isPluginLoaded(string $slug): bool
	{
		return $this->plugins[$slug]['loaded'] ?? false;
	}

	/**
	 * Get plugin info.
	 *
	 * @param string $slug Plugin slug.
	 * @return array<string, mixed>|null
	 */
	public function getPlugin(string $slug): ?array
	{
		return $this->plugins[$slug] ?? null;
	}

	/**
	 * Get all validation errors.
	 *
	 * @return array<string, string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Get canonical meta key.
	 *
	 * @param string $key The meta key to resolve.
	 * @return string The canonical key.
	 */
	public function getCanonicalMetaKey(string $key): string
	{
		return $this->metaAliases[$key] ?? $key;
	}

	/**
	 * Register a schema component.
	 *
	 * @param string               $type   Schema type (cpt, taxonomy, meta, table).
	 * @param string               $name   Component name.
	 * @param array<string, mixed> $config Configuration.
	 * @return void
	 */
	public function registerSchema(string $type, string $name, array $config): void
	{
		if (! isset($this->schema[$type])) {
			$this->schema[$type] = [];
		}
		$this->schema[$type][$name] = $config;
	}

	/**
	 * Get schema.
	 *
	 * @param string|null $type Optional type filter.
	 * @return array<string, mixed>
	 */
	public function getSchema(?string $type = null): array
	{
		if (null === $type) {
			return $this->schema;
		}
		return $this->schema[$type] ?? [];
	}
}

// ============================================================================
// TYPE-SAFE META HELPER
// ============================================================================

/**
 * Type-safe meta access with canonical key resolution.
 *
 * Provides IDE-friendly typed access to post and user meta.
 *
 * @since 3.0.0
 */
final class Meta
{

	/**
	 * Get string meta value.
	 *
	 * @param int    $object_id Object ID (post or user).
	 * @param string $key       Meta key.
	 * @param string $default   Default value.
	 * @param string $type      Object type: 'post' or 'user'.
	 * @return string
	 */
	public static function getString(int $object_id, string $key, string $default = '', string $type = 'post'): string
	{
		$key   = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);
		$value = 'user' === $type
			? get_user_meta($object_id, $key, true)
			: get_post_meta($object_id, $key, true);

		return is_string($value) && '' !== $value ? $value : $default;
	}

	/**
	 * Get integer meta value.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $key       Meta key.
	 * @param int    $default   Default value.
	 * @param string $type      Object type.
	 * @return int
	 */
	public static function getInt(int $object_id, string $key, int $default = 0, string $type = 'post'): int
	{
		$key   = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);
		$value = 'user' === $type
			? get_user_meta($object_id, $key, true)
			: get_post_meta($object_id, $key, true);

		return is_numeric($value) ? (int) $value : $default;
	}

	/**
	 * Get float meta value.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $key       Meta key.
	 * @param float  $default   Default value.
	 * @param string $type      Object type.
	 * @return float
	 */
	public static function getFloat(int $object_id, string $key, float $default = 0.0, string $type = 'post'): float
	{
		$key   = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);
		$value = 'user' === $type
			? get_user_meta($object_id, $key, true)
			: get_post_meta($object_id, $key, true);

		return is_numeric($value) ? (float) $value : $default;
	}

	/**
	 * Get boolean meta value.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $key       Meta key.
	 * @param bool   $default   Default value.
	 * @param string $type      Object type.
	 * @return bool
	 */
	public static function getBool(int $object_id, string $key, bool $default = false, string $type = 'post'): bool
	{
		$key   = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);
		$value = 'user' === $type
			? get_user_meta($object_id, $key, true)
			: get_post_meta($object_id, $key, true);

		if ('' === $value || null === $value) {
			return $default;
		}

		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Get array meta value.
	 *
	 * @param int                  $object_id Object ID.
	 * @param string               $key       Meta key.
	 * @param array<string, mixed> $default   Default value.
	 * @param string               $type      Object type.
	 * @return array<string, mixed>
	 */
	public static function getArray(int $object_id, string $key, array $default = [], string $type = 'post'): array
	{
		$key   = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);
		$value = 'user' === $type
			? get_user_meta($object_id, $key, true)
			: get_post_meta($object_id, $key, true);

		if (is_array($value)) {
			return $value;
		}

		if (is_string($value) && '' !== $value) {
			$decoded = json_decode($value, true);
			return is_array($decoded) ? $decoded : $default;
		}

		return $default;
	}

	/**
	 * Get DateTime meta value.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $key       Meta key.
	 * @param string $type      Object type.
	 * @return \DateTimeImmutable|null
	 */
	public static function getDateTime(int $object_id, string $key, string $type = 'post'): ?\DateTimeImmutable
	{
		$value = self::getString($object_id, $key, '', $type);

		if ('' === $value) {
			return null;
		}

		try {
			return new \DateTimeImmutable($value, wp_timezone());
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Set meta value with sanitization.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $key       Meta key.
	 * @param mixed  $value     Value to set.
	 * @param string $type      Object type.
	 * @return bool|int Meta ID or false.
	 */
	public static function set(int $object_id, string $key, mixed $value, string $type = 'post'): bool|int
	{
		$key = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);

		// Serialize arrays to JSON.
		if (is_array($value)) {
			$value = wp_json_encode($value);
		} elseif (is_bool($value)) {
			$value = $value ? '1' : '0';
		}

		return 'user' === $type
			? update_user_meta($object_id, $key, $value)
			: update_post_meta($object_id, $key, $value);
	}

	/**
	 * Delete meta.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $key       Meta key.
	 * @param string $type      Object type.
	 * @return bool
	 */
	public static function delete(int $object_id, string $key, string $type = 'post'): bool
	{
		$key = AlignmentBridge::getInstance()->getCanonicalMetaKey($key);

		return 'user' === $type
			? delete_user_meta($object_id, $key)
			: delete_post_meta($object_id, $key);
	}
}

// ============================================================================
// DB ALIAS (Facade to SafeQuery)
// ============================================================================

/**
 * Simplified DB facade for common operations.
 *
 * Delegates to SafeQuery for actual implementation.
 *
 * @since 3.0.0
 */
final class DB
{

	/**
	 * Get prefixed table name (validated).
	 *
	 * @param string $table Table name without prefix.
	 * @return string Full table name.
	 * @throws \InvalidArgumentException If table not whitelisted.
	 */
	public static function table(string $table): string
	{
		$full = SafeQuery::getTableName($table);

		if (null === $full) {
			throw new \InvalidArgumentException(
				sprintf('Table "%s" not in whitelist.', $table)
			);
		}

		return $full;
	}

	/**
	 * Check if table exists.
	 *
	 * @param string $table Table name without prefix.
	 * @return bool
	 */
	public static function tableExists(string $table): bool
	{
		return SafeQuery::tableExists($table);
	}

	/**
	 * Get single row.
	 *
	 * @param string               $table  Table name.
	 * @param array<string, mixed> $where  WHERE conditions.
	 * @return object|null
	 */
	public static function getRow(string $table, array $where): ?object
	{
		$results = SafeQuery::select($table, $where, ['limit' => 1]);
		return $results[0] ?? null;
	}

	/**
	 * Get multiple rows.
	 *
	 * @param string               $table   Table name.
	 * @param array<string, mixed> $where   WHERE conditions.
	 * @param int                  $limit   Result limit.
	 * @param int                  $offset  Result offset.
	 * @param string               $orderby ORDER BY column.
	 * @param string               $order   ASC or DESC.
	 * @return array<object>
	 */
	public static function getResults(
		string $table,
		array $where = [],
		int $limit = 100,
		int $offset = 0,
		string $orderby = 'id',
		string $order = 'DESC'
	): array {
		return SafeQuery::select($table, $where, [
			'limit'   => $limit,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
		]) ?? [];
	}

	/**
	 * Insert row.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $data  Data to insert.
	 * @return int|false Insert ID or false.
	 */
	public static function insert(string $table, array $data): int|false
	{
		return SafeQuery::insert($table, $data);
	}

	/**
	 * Update rows.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $data  Data to update.
	 * @param array<string, mixed> $where WHERE conditions.
	 * @return int|false Rows affected or false.
	 */
	public static function update(string $table, array $data, array $where): int|false
	{
		return SafeQuery::update($table, $data, $where);
	}

	/**
	 * Delete rows.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $where WHERE conditions.
	 * @return int|false Rows deleted or false.
	 */
	public static function delete(string $table, array $where): int|false
	{
		return SafeQuery::delete($table, $where);
	}

	/**
	 * Count rows.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $where WHERE conditions.
	 * @return int
	 */
	public static function count(string $table, array $where = []): int
	{
		return SafeQuery::count($table, $where);
	}
}

// ============================================================================
// GLOBAL HELPER FUNCTIONS
// ============================================================================

/**
 * Get AlignmentBridge instance.
 *
 * @return AlignmentBridge
 */
function apollo_bridge(): AlignmentBridge
{
	return AlignmentBridge::getInstance();
}

/**
 * Get BatchLoader instance.
 *
 * @return BatchLoader
 */
function apollo_batch_loader(): BatchLoader
{
	return BatchLoader::getInstance();
}

/**
 * Get QueryCache instance.
 *
 * @return QueryCache
 */
function apollo_cache(): QueryCache
{
	return QueryCache::getInstance();
}

/**
 * Get user membership type.
 *
 * @param int $user_id User ID.
 * @return string Membership slug.
 */
function apollo_get_user_membership(int $user_id): string
{
	return Meta::getString($user_id, '_apollo_membership', 'nao-verificado', 'user');
}

/**
 * Set user membership type.
 *
 * @param int    $user_id    User ID.
 * @param string $membership Membership slug.
 * @return bool|int
 */
function apollo_set_user_membership(int $user_id, string $membership): bool|int
{
	$old    = apollo_get_user_membership($user_id);
	$result = Meta::set($user_id, '_apollo_membership', sanitize_key($membership), 'user');

	if ($result && $old !== $membership) {
		/**
		 * Action fired when membership changes.
		 *
		 * @param int    $user_id User ID.
		 * @param string $old     Old membership.
		 * @param string $new     New membership.
		 */
		do_action('apollo_membership_changed', $user_id, $old, $membership);
	}

	return $result;
}

/**
 * Check if user can sign documents.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function apollo_user_can_sign_documents(int $user_id): bool
{
	$verified   = Meta::getBool($user_id, '_apollo_verified', false, 'user');
	$membership = apollo_get_user_membership($user_id);

	$signing_memberships = ['apollo', 'prod', 'dj', 'host', 'govern', 'business-pers'];

	return $verified && in_array($membership, $signing_memberships, true);
}

/**
 * Bridge event data to social activity.
 *
 * @param int $event_id Event post ID.
 * @return array<string, mixed> Event data.
 */
function apollo_bridge_event_data(int $event_id): array
{
	$event = get_post($event_id);

	if (! $event || 'event_listing' !== $event->post_type) {
		return [];
	}

	return [
		'id'         => $event_id,
		'title'      => $event->post_title,
		'permalink'  => get_permalink($event_id),
		'start_date' => Meta::getString($event_id, '_event_start_date'),
		'end_date'   => Meta::getString($event_id, '_event_end_date'),
		'location'   => Meta::getString($event_id, '_event_location'),
		'venue_ids'  => Meta::getArray($event_id, '_event_local_ids'),
		'dj_ids'     => Meta::getArray($event_id, '_event_dj_ids'),
		'lat'        => Meta::getFloat($event_id, '_event_lat'),
		'lng'        => Meta::getFloat($event_id, '_event_lng'),
		'thumbnail'  => get_the_post_thumbnail_url($event_id, 'medium') ?: '',
	];
}

/**
 * Check if Apollo plugin is available.
 *
 * @param string $plugin Plugin slug.
 * @return bool
 */
function apollo_plugin_available(string $plugin): bool
{
	return apollo_bridge()->isPluginLoaded($plugin);
}

/**
 * Log to Apollo audit log.
 *
 * @param string               $action  Action performed.
 * @param array<string, mixed> $context Context data.
 * @return int|false Insert ID or false.
 */
function apollo_audit_log(string $action, array $context = []): int|false
{
	if (! DB::tableExists('apollo_mod_log')) {
		return false;
	}

	return DB::insert('apollo_mod_log', [
		'action'     => sanitize_key($action),
		'user_id'    => get_current_user_id(),
		'context'    => wp_json_encode($context),
		'ip_address' => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')),
		'user_agent' => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
		'created_at' => current_time('mysql'),
	]);
}

// ============================================================================
// BOOTSTRAP
// ============================================================================

add_action('plugins_loaded', static function (): void {
	AlignmentBridge::getInstance()->boot();
}, 5);
