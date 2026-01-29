<?php

/**
 * Apollo Ecosystem - IDE Helper Stubs
 *
 * Provides complete type hints for Intelephense/PHPStorm.
 * DO NOT INCLUDE IN PRODUCTION - For IDE only.
 *
 * Add to .vscode/settings.json:
 * "intelephense.stubs": [..., "apollo-core/stubs/apollo-stubs.php"]
 *
 * @package Apollo\Stubs
 * @since 3.0.0
 * @phpcs:disable
 */

declare(strict_types=1);

// ============================================================================
// APOLLO CORE - ALIGNMENT BRIDGE
// ============================================================================

namespace Apollo\Core {

	use Apollo\Core\Database\SafeQuery;
	use Apollo\Core\Database\BatchLoader;
	use Apollo\Core\Database\QueryCache;

	/**
	 * Unified Alignment Bridge - Single entry point for Apollo ecosystem.
	 */
	final class AlignmentBridge
	{
		/** @return self */
		public static function getInstance(): self {}
		public function boot(): void {}
		public function validateAll(): bool {}
		public function isPluginLoaded(string $slug): bool {}
		/** @return array{loaded: bool, version: string, namespace: string, path: string, required: bool}|null */
		public function getPlugin(string $slug): ?array {}
		/** @return array<string, string> */
		public function getErrors(): array {}
		public function getCanonicalMetaKey(string $key): string {}
		/** @param array<string, mixed> $config */
		public function registerSchema(string $type, string $name, array $config): void {}
		/** @return array<string, mixed> */
		public function getSchema(?string $type = null): array {}
	}

	/**
	 * Type-safe Meta Access.
	 */
	final class Meta
	{
		public static function getString(int $object_id, string $key, string $default = '', string $type = 'post'): string {}
		public static function getInt(int $object_id, string $key, int $default = 0, string $type = 'post'): int {}
		public static function getFloat(int $object_id, string $key, float $default = 0.0, string $type = 'post'): float {}
		public static function getBool(int $object_id, string $key, bool $default = false, string $type = 'post'): bool {}
		/** @return array<string, mixed> */
		public static function getArray(int $object_id, string $key, array $default = [], string $type = 'post'): array {}
		public static function getDateTime(int $object_id, string $key, string $type = 'post'): ?\DateTimeImmutable {}
		public static function set(int $object_id, string $key, mixed $value, string $type = 'post'): bool|int {}
		public static function delete(int $object_id, string $key, string $type = 'post'): bool {}
	}

	/**
	 * Database Facade (delegates to SafeQuery).
	 */
	final class DB
	{
		/**
		 * @throws \InvalidArgumentException If table not whitelisted.
		 */
		public static function table(string $table): string {}
		public static function tableExists(string $table): bool {}
		/** @param array<string, mixed> $where */
		public static function getRow(string $table, array $where): ?object {}
		/**
		 * @param array<string, mixed> $where
		 * @return array<object>
		 */
		public static function getResults(string $table, array $where = [], int $limit = 100, int $offset = 0, string $orderby = 'id', string $order = 'DESC'): array {}
		/** @param array<string, mixed> $data */
		public static function insert(string $table, array $data): int|false {}
		/**
		 * @param array<string, mixed> $data
		 * @param array<string, mixed> $where
		 */
		public static function update(string $table, array $data, array $where): int|false {}
		/** @param array<string, mixed> $where */
		public static function delete(string $table, array $where): int|false {}
		/** @param array<string, mixed> $where */
		public static function count(string $table, array $where = []): int {}
	}

	// Global functions
	function apollo_bridge(): AlignmentBridge {}
	function apollo_batch_loader(): BatchLoader {}
	function apollo_cache(): QueryCache {}
	/**
	 * @return string Membership slug (nao-verificado, apollo, prod, dj, host, govern, business-pers)
	 */
	function apollo_get_user_membership(int $user_id): string {}
	function apollo_set_user_membership(int $user_id, string $membership): bool|int {}
	function apollo_user_can_sign_documents(int $user_id): bool {}
	/**
	 * @return array{id: int, title: string, permalink: string, start_date: string, end_date: string, location: string, venue_ids: array<int>, dj_ids: array<int>, lat: float, lng: float, thumbnail: string}
	 */
	function apollo_bridge_event_data(int $event_id): array {}
	/**
	 * @param string $plugin apollo-core|apollo-social|apollo-events-manager|apollo-rio
	 */
	function apollo_plugin_available(string $plugin): bool {}
	/** @param array<string, mixed> $context */
	function apollo_audit_log(string $action, array $context = []): int|false {}
}

// ============================================================================
// APOLLO CORE - DATABASE LAYER
// ============================================================================

namespace Apollo\Core\Database {

	/**
	 * Safe database query wrapper with $wpdb->prepare() enforcement.
	 */
	final class SafeQuery
	{
		public static function tableExists(string $table_name): bool {}
		public static function isAllowedTable(string $table_name): bool {}
		public static function getTableName(string $table_name): ?string {}
		/**
		 * @param array<string, mixed> $where
		 * @param array<string, mixed> $args
		 * @return array<object>|null
		 */
		public static function select(string $table, array $where = [], array $args = []): ?array {}
		/**
		 * @param array<string, mixed> $data
		 * @return int|false Insert ID or false.
		 */
		public static function insert(string $table, array $data): int|false {}
		/**
		 * @param array<string, mixed> $data
		 * @param array<string, mixed> $where
		 * @return int|false Rows updated or false.
		 */
		public static function update(string $table, array $data, array $where): int|false {}
		/**
		 * @param array<string, mixed> $where
		 * @return int|false Rows deleted or false.
		 */
		public static function delete(string $table, array $where): int|false {}
		/**
		 * @param array<string, mixed> $where
		 */
		public static function count(string $table, array $where = []): int {}
	}

	/**
	 * Batch Loader for N+1 prevention.
	 */
	final class BatchLoader
	{
		public static function getInstance(): self {}
		/**
		 * @param callable(array<int>): array<int, mixed> $loader
		 */
		public function registerLoader(string $type, callable $loader): self {}
		/** @param array<int>|int $ids */
		public function queue(string $type, array|int $ids): self {}
		public function loadAll(): self {}
		public function get(string $type, int $id): mixed {}
		/** @return array<int, mixed> */
		public function getAll(string $type): array {}
		public function clear(?string $type = null): self {}
	}

	/**
	 * Query Cache with auto-invalidation.
	 */
	final class QueryCache
	{
		public static function getInstance(): self {}
		public function remember(string $key, callable $callback, int $ttl = 3600): mixed {}
		public function get(string $key): mixed {}
		public function set(string $key, mixed $value, int $ttl = 3600): bool {}
		public function delete(string $key): bool {}
		public function deleteByPattern(string $pattern): int {}
		/** @return array{hits: int, misses: int, sets: int} */
		public function getStats(): array {}
	}
}

// ============================================================================
// APOLLO CORE - BRIDGE LOADER
// ============================================================================

namespace Apollo_Core\Bridge {

	/**
	 * Central coordinator that bridges apollo-core with satellite plugins.
	 */
	final class BridgeLoader
	{
		public const STATUS_OPTION = 'apollo_bridge_status';
		public static function getInstance(): self {}
		public function init(): void {}
		/** @return array<string, array{version: string, active: bool, schema: bool, routes: bool, file: string}> */
		public function getConnectedPlugins(): array {}
		public function isConnected(string $plugin): bool {}
		public function getPluginVersion(string $plugin): ?string {}
	}
}

// ============================================================================
// APOLLO CORE - INTEGRATION BRIDGE
// ============================================================================

namespace Apollo_Core\Integration {

	/**
	 * OOP Integration bridge for companion plugins.
	 */
	class Apollo_Integration_Bridge
	{
		public static function get_instance(): self {}
		/** @param array<string, mixed> $config */
		public function register_plugin(string $plugin_slug, array $config = []): bool {}
		public function is_plugin_registered(string $plugin_slug): bool {}
		public function is_plugin_active(string $plugin_slug): bool {}
		/** @return array<string, array> */
		public function get_registered_plugins(): array {}
		public function before_event_display(int $event_id): void {}
		public function after_event_display(int $event_id): void {}
		public function get_social_buttons(int $post_id): string {}
		public function render_social_buttons(int $post_id): void {}
	}
}

// ============================================================================
// APOLLO EVENTS MANAGER
// ============================================================================

namespace Apollo\Events {

	/**
	 * Events Service Loader.
	 */
	final class EventsServiceLoader
	{
		public static function getInstance(): self {}
		public function boot(): void {}
		/** @template T of object */
		public function get(string $service): object {}
		public function getAssetLoader(): Services\EventsAssetLoader {}
		public function getShortcodes(): Services\EventsShortcodes {}
		public function getAnalytics(): Services\EventsAnalytics {}
		public function getAdmin(): Services\EventsAdmin {}
		public function getCronJobs(): Services\EventsCronJobs {}
		public function getAjaxController(): Controllers\EventsAjaxController {}
	}
}

namespace Apollo\Events\Services {

	class EventsAssetLoader
	{
		public function enqueueAdminAssets(): void {}
		public function enqueueFrontendAssets(): void {}
		public function enqueueCanvasAssets(): void {}
	}

	class EventsShortcodes
	{
		public function register(): void {}
		public function renderEventsList(array $atts): string {}
		public function renderEventSingle(array $atts): string {}
		public function renderEventCalendar(array $atts): string {}
	}

	class EventsAnalytics
	{
		public function trackView(int $event_id): void {}
		/** @return array{views: int, unique_views: int, favorites: int, shares: int} */
		public function getStats(int $event_id): array {}
		public function renderDashboard(): void {}
	}

	class EventsAdmin
	{
		public function registerMenus(): void {}
		public function renderSettings(): void {}
		public function addMetaBoxes(): void {}
	}

	class EventsCronJobs
	{
		public function schedule(): void {}
		public function processExpiredEvents(): void {}
		public function cleanupStats(): void {}
	}
}

namespace Apollo\Events\Controllers {

	class EventsAjaxController
	{
		public function handleBookmark(): void {}
		public function handleRSVP(): void {}
		public function handleInterest(): void {}
		public function handleSearch(): void {}
	}
}

// ============================================================================
// APOLLO SOCIAL
// ============================================================================

namespace Apollo\Social {

	/**
	 * Social DI Container.
	 */
	final class ApolloContainer
	{
		public static function getInstance(): self {}
		public function boot(): void {}
		/** @template T of object */
		public function get(string $id): object {}
		public function has(string $id): bool {}
		public function singleton(string $id, callable $factory): void {}
		public function bind(string $id, callable $factory): void {}
	}
}

namespace Apollo\Social\Infrastructure {

	class MetaRegistrar
	{
		public static function register(): void {}
		/**
		 * @return array<string, array{type: string, single: bool, show_in_rest: bool, sanitize_callback: callable}>
		 */
		public static function getUserMeta(): array {}
		/**
		 * @return array<string, array{type: string, single: bool, show_in_rest: bool, sanitize_callback: callable}>
		 */
		public static function getPostMeta(string $post_type): array {}
	}
}

namespace Apollo\Social\Modules\Groups {

	class GroupsBusinessRules
	{
		public const TYPE_COMUNA = 'comuna';
		public const TYPE_NUCLEO = 'nucleo';
		public const TYPE_SEASON = 'season';

		public function canUserJoin(int $user_id, int $group_id): bool {}
		public function canUserPost(int $user_id, int $group_id): bool {}
		public function canUserModerate(int $user_id, int $group_id): bool {}
		public function canUserInvite(int $user_id, int $group_id): bool {}
		public function getGroupType(int $group_id): string {}
		public function isPrivate(int $group_id): bool {}
	}
}

namespace Apollo\Social\Modules\Documents {

	class DocumentsManager
	{
		/**
		 * @param array{title: string, type: string, parties: array<int>, file_id: int} $data
		 */
		public function create(array $data): int|false {}
		public function sign(int $doc_id, int $user_id): bool {}
		public function verify(int $doc_id): bool {}
		public function getSignatures(int $doc_id): array {}
		public function getState(int $doc_id): string {}
	}
}

// ============================================================================
// CANONICAL META KEYS REFERENCE
// ============================================================================

namespace {

	/**
	 * Event Listing Meta Keys.
	 * @var array<string, array{type: string, rest: bool, deprecated?: string}>
	 */
	const APOLLO_EVENT_LISTING_META = [
		'_event_title'            => ['type' => 'string', 'rest' => true],
		'_event_banner'           => ['type' => 'string', 'rest' => true],
		'_event_video_url'        => ['type' => 'string', 'rest' => true],
		'_event_start_date'       => ['type' => 'string', 'rest' => true],
		'_event_end_date'         => ['type' => 'string', 'rest' => true],
		'_event_start_time'       => ['type' => 'string', 'rest' => true],
		'_event_end_time'         => ['type' => 'string', 'rest' => true],
		'_event_location'         => ['type' => 'string', 'rest' => true],
		'_event_country'          => ['type' => 'string', 'rest' => true],
		'_event_lat'              => ['type' => 'float', 'rest' => true],
		'_event_lng'              => ['type' => 'float', 'rest' => true],
		'_event_dj_ids'           => ['type' => 'array', 'rest' => true],
		'_event_local_ids'        => ['type' => 'array', 'rest' => true],
		'_event_dj_slots'         => ['type' => 'array', 'rest' => true],
		'_event_gestao'           => ['type' => 'array', 'rest' => true],
		'_event_interested_users' => ['type' => 'array', 'rest' => true],
		'_event_views'            => ['type' => 'integer', 'rest' => true],
		'_favorites_count'        => ['type' => 'integer', 'rest' => true],
		'_event_season_id'        => ['type' => 'integer', 'rest' => true],
		'_tickets_ext'            => ['type' => 'string', 'rest' => true],
		'_cupom_ario'             => ['type' => 'string', 'rest' => true],
		// DEPRECATED - do not use
		'_event_timetable'        => ['type' => 'array', 'rest' => false, 'deprecated' => 'Use _event_dj_slots'],
		'_event_latitude'         => ['type' => 'float', 'rest' => false, 'deprecated' => 'Use _event_lat'],
		'_event_longitude'        => ['type' => 'float', 'rest' => false, 'deprecated' => 'Use _event_lng'],
	];

	/**
	 * Event DJ Meta Keys.
	 */
	const APOLLO_EVENT_DJ_META = [
		'_dj_name'               => ['type' => 'string', 'rest' => true],
		'_dj_bio'                => ['type' => 'string', 'rest' => true],
		'_dj_image'              => ['type' => 'string', 'rest' => true],
		'_dj_website'            => ['type' => 'string', 'rest' => true],
		'_dj_instagram'          => ['type' => 'string', 'rest' => true],
		'_dj_facebook'           => ['type' => 'string', 'rest' => true],
		'_dj_soundcloud'         => ['type' => 'string', 'rest' => true],
		'_dj_bandcamp'           => ['type' => 'string', 'rest' => true],
		'_dj_spotify'            => ['type' => 'string', 'rest' => true],
		'_dj_youtube'            => ['type' => 'string', 'rest' => true],
		'_dj_mixcloud'           => ['type' => 'string', 'rest' => true],
		'_dj_beatport'           => ['type' => 'string', 'rest' => true],
		'_dj_resident_advisor'   => ['type' => 'string', 'rest' => true],
		'_dj_twitter'            => ['type' => 'string', 'rest' => true],
		'_dj_tiktok'             => ['type' => 'string', 'rest' => true],
		'_dj_user_id'            => ['type' => 'integer', 'rest' => true],
	];

	/**
	 * Event Local (Venue) Meta Keys.
	 */
	const APOLLO_EVENT_LOCAL_META = [
		'_local_name'        => ['type' => 'string', 'rest' => true],
		'_local_description' => ['type' => 'string', 'rest' => true],
		'_local_address'     => ['type' => 'string', 'rest' => true],
		'_local_city'        => ['type' => 'string', 'rest' => true],
		'_local_state'       => ['type' => 'string', 'rest' => true],
		'_local_lat'         => ['type' => 'float', 'rest' => true],
		'_local_lng'         => ['type' => 'float', 'rest' => true],
		'_local_website'     => ['type' => 'string', 'rest' => true],
		'_local_instagram'   => ['type' => 'string', 'rest' => true],
	];

	/**
	 * User Meta Keys.
	 */
	const APOLLO_USER_META = [
		'_apollo_membership'       => ['type' => 'string', 'rest' => true],
		'_apollo_membership_since' => ['type' => 'string', 'rest' => true],
		'_apollo_verified'         => ['type' => 'boolean', 'rest' => true],
		'_apollo_verified_date'    => ['type' => 'string', 'rest' => true],
		'_apollo_bio'              => ['type' => 'string', 'rest' => true],
		'_apollo_avatar_url'       => ['type' => 'string', 'rest' => true],
		'_apollo_cover_image'      => ['type' => 'string', 'rest' => true],
		'_apollo_instagram'        => ['type' => 'string', 'rest' => true],
		'_apollo_badges'           => ['type' => 'array', 'rest' => true],
		'_apollo_points'           => ['type' => 'integer', 'rest' => true],
		'_apollo_level'            => ['type' => 'integer', 'rest' => true],
		'_apollo_cpf'              => ['type' => 'string', 'rest' => false], // Private!
	];

	/**
	 * Group Meta Keys.
	 */
	const APOLLO_GROUP_META = [
		'_group_description'   => ['type' => 'string', 'rest' => true],
		'_group_cover'         => ['type' => 'string', 'rest' => true],
		'_group_avatar'        => ['type' => 'string', 'rest' => true],
		'_group_members_count' => ['type' => 'integer', 'rest' => true],
		'_group_is_private'    => ['type' => 'boolean', 'rest' => true],
		'_nucleo_genres'       => ['type' => 'array', 'rest' => true],
		'_nucleo_founders'     => ['type' => 'array', 'rest' => true],
	];

	/**
	 * Apollo Custom Post Types.
	 * @var array<string, string>
	 */
	const APOLLO_POST_TYPES = [
		'event_listing'     => 'apollo-events-manager',
		'event_dj'          => 'apollo-events-manager',
		'event_local'       => 'apollo-events-manager',
		'event_cena'        => 'apollo-events-manager',
		'apollo_event_stat' => 'apollo-events-manager',
		'apollo_social_post' => 'apollo-social',
		'user_page'         => 'apollo-social',
		'apollo_classified' => 'apollo-social',
		'apollo_supplier'   => 'apollo-social',
		'apollo_home'       => 'apollo-social',
		'apollo_document'   => 'apollo-social',
		'apollo_email_temp' => 'apollo-core',
	];

	/**
	 * Apollo Taxonomies.
	 * @var array<string, string>
	 */
	const APOLLO_TAXONOMIES = [
		'event_listing_category' => 'event_listing',
		'event_listing_type'     => 'event_listing',
		'event_listing_tag'      => 'event_listing',
		'event_sounds'           => 'event_listing',
		'event_season'           => 'event_listing',
		'apollo_post_category'   => 'apollo_social_post',
		'classified_domain'      => 'apollo_classified',
		'classified_intent'      => 'apollo_classified',
	];

	/**
	 * Apollo Database Tables.
	 * @var array<string, string>
	 */
	const APOLLO_DATABASE_TABLES = [
		'apollo_mod_log'       => 'apollo-core',
		'apollo_favorites'     => 'apollo-events-manager',
		'apollo_chat_rooms'    => 'apollo-social',
		'apollo_chat_messages' => 'apollo-social',
		'apollo_documents'     => 'apollo-social',
		'apollo_signatures'    => 'apollo-social',
		'apollo_likes'         => 'apollo-social',
		'apollo_connections'   => 'apollo-social',
		'apollo_activity'      => 'apollo-social',
		'apollo_notifications' => 'apollo-social',
	];
}

// ============================================================================
// APOLLO CORE - SECURITY KERNEL (NEW 2.1.0)
// ============================================================================

namespace Apollo\Core\Security {

	/**
	 * Centralized Security Utilities - WordPress VIP Standards.
	 *
	 * @since 2.1.0
	 */
	final class SecurityKernel
	{
		public static function instance(): self {}

		// Input Sanitization
		public static function string(mixed $value, string $default = ''): string {}
		public static function textarea(mixed $value, string $default = ''): string {}
		public static function int(mixed $value, int $default = 0): int {}
		public static function absint(mixed $value, int $default = 0): int {}
		public static function float(mixed $value, float $default = 0.0): float {}
		public static function bool(mixed $value): bool {}
		public static function email(mixed $value, string $default = ''): string {}
		public static function url(mixed $value, string $default = '', ?array $protocols = null): string {}
		public static function key(mixed $value, string $default = ''): string {}
		public static function filename(mixed $value, string $default = ''): string {}
		public static function html(mixed $value, string $context = 'post'): string {}
		/** @return string[] */
		public static function stringArray(mixed $value, array $default = [], ?callable $sanitizer = null): array {}
		/** @return int[] */
		public static function intArray(mixed $value, array $default = []): array {}
		/** @return array<mixed> */
		public static function json(mixed $value, array $default = []): array {}

		// Output Escaping
		public static function escHtml(mixed $value): string {}
		public static function escAttr(mixed $value): string {}
		public static function escUrl(mixed $value): string {}
		public static function escJs(mixed $value): string {}
		public static function escTextarea(mixed $value): string {}
		public static function escXml(mixed $value): string {}

		// Nonce Operations
		public static function createNonce(string $action): string {}
		public static function verifyNonce(string $nonce, string $action): bool {}
		public static function getNonce(string $key = '_wpnonce', string $method = 'REQUEST'): string {}
		public static function checkAjaxNonce(string $action, string $nonce_key = '_wpnonce'): void {}
		public static function checkAdminNonce(string $action, string $nonce_key = '_wpnonce'): void {}
		public static function nonceField(string $action, string $name = '_wpnonce', bool $referer = true): void {}

		// Capability Checks
		public static function can(string $capability, mixed ...$args): bool {}
		public static function requireCap(string $capability, string $message = 'Permission denied.'): void {}
		public static function requireAuth(string $message = 'Authentication required.'): void {}
		public static function requireOwnership(int $user_id, string $message = 'Access denied.', string $bypass_cap = 'manage_options'): void {}

		// Request Input Helpers
		public static function get(string $key, mixed $default = '', string $type = 'string'): mixed {}
		public static function post(string $key, mixed $default = '', string $type = 'string'): mixed {}
		public static function request(string $key, mixed $default = '', string $type = 'string'): mixed {}

		// SQL Security
		public static function table(string $table): string {}
		public static function prepare(string $query, mixed ...$args): ?string {}

		// Rate Limiting
		public static function checkRateLimit(string $key, int $limit = 60, int $window = 60, string $identifier = ''): bool {}
		public static function getClientIp(): string {}

		// CSRF Protection
		public static function generateCsrfToken(): string {}
		public static function validateCsrfToken(string $token): bool {}
	}

	// Global functions
	function apollo_security(): SecurityKernel {}
	function apollo_sanitize(mixed $value, string $type = 'string', mixed $default = ''): mixed {}
	function apollo_esc(mixed $value, string $type = 'html'): string {}
}

// ============================================================================
// APOLLO CORE - HOOK REGISTRY (NEW 2.1.0)
// ============================================================================

namespace Apollo\Core\Hooks {

	/**
	 * Hook Priority Constants.
	 */
	final class Priority
	{
		public const FIRST = 1;
		public const EARLY = 5;
		public const DEFAULT = 10;
		public const LATE = 15;
		public const LAST = 20;
		public const VERY_LAST = 99;
		public const PHP_INT_MAX = PHP_INT_MAX;
	}

	/**
	 * Type-Safe Hook Registry.
	 *
	 * @since 2.1.0
	 */
	final class HookRegistry
	{
		public static function instance(): self {}

		// Action Registration
		public function action(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self {}
		public function actionMethod(string $hook, object|string $object, string $method, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self {}
		public function actionOnce(string $hook, callable $callback, int $priority = Priority::DEFAULT, int $args = 1): self {}

		// Filter Registration
		public function filter(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self {}
		public function filterMethod(string $hook, object|string $object, string $method, int $priority = Priority::DEFAULT, int $args = 1, string $group = ''): self {}

		// AJAX Hooks
		public function ajax(string $action, callable|string $callback, string $group = ''): self {}
		public function ajaxNopriv(string $action, callable|string $callback, string $group = ''): self {}
		public function ajaxBoth(string $action, callable|string $callback, string $group = ''): self {}

		// REST API
		public function restApiInit(callable $callback, int $priority = Priority::DEFAULT): self {}

		// Shortcodes
		public function shortcode(string $tag, callable $callback): self {}

		// Removal
		public function removeAction(string $hook, callable $callback, int $priority = Priority::DEFAULT): bool {}
		public function removeFilter(string $hook, callable $callback, int $priority = Priority::DEFAULT): bool {}
		public function removeGroup(string $group): int {}

		// Conditional Hooks
		public function actionIf(bool $condition, string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self {}
		public function filterIf(bool $condition, string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self {}
		public function actionAdmin(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self {}
		public function actionFrontend(string $hook, callable|string $callback, int $priority = Priority::DEFAULT, int $args = 1): self {}

		// Debugging
		/** @return array<string, array<string, mixed>> */
		public function getRegisteredHooks(): array {}
		/** @return array<array<string, mixed>> */
		public function getHooksByGroup(string $group): array {}
		public function hasHook(string $hook): bool {}
		public function count(): int {}
		public function dump(): void {}
	}

	// Global function
	function apollo_hooks(): HookRegistry {}
}

// ============================================================================
// APOLLO CORE - NAMESPACE TOOLS (NEW 2.1.0)
// ============================================================================

namespace Apollo\Core\Tools {

	/**
	 * Namespace Mapping from Legacy to Modern PSR-4.
	 */
	final class NamespaceMap
	{
		/** @var array<string, string> */
		public const MAPPINGS = [];

		public static function modernize(string $legacy_namespace): string {}
		public static function isLegacy(string $namespace): bool {}
		/** @return array<string> */
		public static function getLegacyNamespaces(): array {}
	}

	/**
	 * Class Alias Registry for backwards compatibility.
	 */
	final class ClassAliasRegistry
	{
		public static function init(): void {}
		public static function alias(string $legacy_class, string $modern_class): void {}
		/** @param array<string, string> $aliases */
		public static function registerAliases(array $aliases): void {}
		public static function autoload(string $class): void {}
		/** @return array<string, string> */
		public static function getAliases(): array {}
	}

	/**
	 * PSR-4 Autoloader.
	 */
	final class Psr4Autoloader
	{
		public static function register(): void {}
		public static function addNamespace(string $namespace, string $directory): void {}
		public static function autoload(string $class): void {}
		/** @return array<string, string> */
		public static function getNamespaces(): array {}
	}

	/**
	 * Namespace Modernizer for migration.
	 */
	final class NamespaceModernizer
	{
		/** @return array<array{line: int, legacy: string, modern: string}> */
		public static function scanFile(string $file_path): array {}
		/** @return array{migrated: bool, changes: array<array{line: int, before: string, after: string}>} */
		public static function migrateFile(string $file_path, bool $dry_run = true): array {}
		/** @return array<string, array<array{line: int, legacy: string, modern: string}>> */
		public static function scanDirectory(string $directory): array {}
	}
}

// ============================================================================
// APOLLO CORE - NAVBAR FILTERS (NEW 2.1.0)
// ============================================================================

namespace {

	/**
	 * Navbar Horizontal Scroll Notifications Filter.
	 *
	 * @hook apollo_navbar_scroll_notifications
	 * @param array<array{title: string, message: string, icon: string, color: string}> $notifications
	 * @return array<array{title: string, message: string, icon: string, color: string}>
	 *
	 * @example
	 * add_filter('apollo_navbar_scroll_notifications', function($notifications) {
	 *     $notifications[] = [
	 *         'title' => 'Manutenção Programada',
	 *         'message' => 'Sistema offline 02:00-04:00',
	 *         'icon' => 'ri-tools-fill',
	 *         'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
	 *     ];
	 *     return $notifications;
	 * });
	 */
	const APOLLO_NAVBAR_SCROLL_NOTIFICATIONS_FILTER = 'apollo_navbar_scroll_notifications';

	/**
	 * Navbar Dropdown Notifications Filter.
	 *
	 * @hook apollo_navbar_notifications
	 * @param array<array{id: int|string, title: string, message: string, time: string, icon: string, color: string}> $notifications
	 * @return array<array{id: int|string, title: string, message: string, time: string, icon: string, color: string}>
	 *
	 * @example
	 * add_filter('apollo_navbar_notifications', function($notifications) {
	 *     $notifications[] = [
	 *         'id' => 'notif_123',
	 *         'title' => 'Novo Evento',
	 *         'message' => 'Você foi convidado para Tech Summit',
	 *         'time' => '5min atrás',
	 *         'icon' => 'ri-calendar-event-fill',
	 *         'color' => 'var(--ap-bg-primary)',
	 *     ];
	 *     return $notifications;
	 * });
	 */
	const APOLLO_NAVBAR_NOTIFICATIONS_FILTER = 'apollo_navbar_notifications';

	/**
	 * Navbar Chat Conversations Filter.
	 *
	 * @hook apollo_navbar_chat_conversations
	 * @param array<array{avatar: string, name: string, message: string, time: string, is_me?: bool}> $conversations
	 * @return array<array{avatar: string, name: string, message: string, time: string, is_me?: bool}>
	 *
	 * @example
	 * add_filter('apollo_navbar_chat_conversations', function($conversations) {
	 *     $conversations[] = [
	 *         'avatar' => 'JD',
	 *         'name' => 'João Dias',
	 *         'message' => 'Confirma a reunião?',
	 *         'time' => 'Agora',
	 *         'is_me' => false,
	 *     ];
	 *     return $conversations;
	 * });
	 */
	const APOLLO_NAVBAR_CHAT_CONVERSATIONS_FILTER = 'apollo_navbar_chat_conversations';

	/**
	 * Navbar Apps List Filter.
	 *
	 * @hook apollo_navbar_apps_list
	 * @param array<array{id: string, label: string, icon: string, icon_text: string, background_type: string, background_gradient: string, background_color: string, url: string, target: string, active: bool, order: int, capability?: string}> $apps
	 * @return array<array{id: string, label: string, icon: string, icon_text: string, background_type: string, background_gradient: string, background_color: string, url: string, target: string, active: bool, order: int, capability?: string}>
	 *
	 * @example
	 * add_filter('apollo_navbar_apps_list', function($apps) {
	 *     if (current_user_can('manage_options')) {
	 *         $apps[] = [
	 *             'id' => 'admin_panel',
	 *             'label' => 'Admin Panel',
	 *             'icon' => 'ri-settings-3-fill',
	 *             'icon_text' => '',
	 *             'background_type' => 'gradient',
	 *             'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
	 *             'background_color' => '',
	 *             'url' => admin_url(),
	 *             'target' => '_self',
	 *             'active' => true,
	 *             'order' => -10,
	 *             'capability' => 'manage_options',
	 *         ];
	 *     }
	 *     return $apps;
	 * });
	 */
	const APOLLO_NAVBAR_APPS_LIST_FILTER = 'apollo_navbar_apps_list';
}
