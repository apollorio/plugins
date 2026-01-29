<?php
/**
 * Apollo REST Namespace
 *
 * Unified namespace constants for all Apollo REST API endpoints.
 * Consolidates fragmented namespaces into a single canonical namespace.
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Previously:
 * - apollo-social: apollo/v1 (44 endpoints)
 * - apollo-core: apollo-core/v1 (55 endpoints)
 * - apollo-events-manager: apollo-events/v1 (13 endpoints)
 *
 * Now unified under: apollo/v1
 */

declare(strict_types=1);

namespace Apollo_Core\REST_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_REST_Namespace
 *
 * Central namespace definitions for REST API.
 */
final class Apollo_REST_Namespace {

	// =========================================================================
	// CANONICAL NAMESPACES
	// =========================================================================

	/**
	 * Primary API namespace (v1)
	 *
	 * @var string
	 */
	public const V1 = 'apollo/v1';

	/**
	 * Future API namespace (v2)
	 *
	 * @var string
	 */
	public const V2 = 'apollo/v2';

	// =========================================================================
	// LEGACY NAMESPACES (for backward compatibility)
	// =========================================================================

	/**
	 * Legacy apollo-core namespace
	 *
	 * @deprecated 2.0.0 Use Apollo_REST_Namespace::V1 instead.
	 * @var string
	 */
	public const LEGACY_CORE = 'apollo-core/v1';

	/**
	 * Legacy apollo-events-manager namespace
	 *
	 * @deprecated 2.0.0 Use Apollo_REST_Namespace::V1 instead.
	 * @var string
	 */
	public const LEGACY_EVENTS = 'apollo-events/v1';

	/**
	 * Legacy apollo-social namespace (was already correct)
	 *
	 * @deprecated 2.0.0 Use Apollo_REST_Namespace::V1 instead.
	 * @var string
	 */
	public const LEGACY_SOCIAL = 'apollo/v1';

	// =========================================================================
	// ROUTE PREFIXES
	// =========================================================================

	/**
	 * Route prefixes for different modules within the unified namespace.
	 *
	 * @var array<string, string>
	 */
	public const PREFIXES = array(
		'events'      => 'events',
		'djs'         => 'djs',
		'venues'      => 'venues',
		'classifieds' => 'classifieds',
		'social'      => 'social',
		'users'       => 'users',
		'groups'      => 'groups',
		'comunas'     => 'comunas',
		'nucleos'     => 'nucleos',
		'members'     => 'members',
		'activity'    => 'activity',
		'moderation'  => 'mod',
		'chat'        => 'chat',
		'documents'   => 'documents',
		'signatures'  => 'signatures',
		'suppliers'   => 'suppliers',
		'onboarding'  => 'onboarding',
		'bubble'      => 'bubble',
		'points'      => 'points',
		'notices'     => 'notices',
		'favorites'   => 'favorites',
		'likes'       => 'likes',
	);

	// =========================================================================
	// NAMESPACE MAPPING
	// =========================================================================

	/**
	 * Map of legacy routes to new unified routes.
	 *
	 * @var array<string, string>
	 */
	private const ROUTE_MIGRATION_MAP = array(
		// apollo-core/v1 → apollo/v1
		'apollo-core/v1/explore'           => 'apollo/v1/social/explore',
		'apollo-core/v1/posts'             => 'apollo/v1/social/posts',
		'apollo-core/v1/wow'               => 'apollo/v1/social/wow',
		'apollo-core/v1/events/upcoming'   => 'apollo/v1/events/upcoming',
		'apollo-core/v1/moderation/queue'  => 'apollo/v1/mod/queue',

		// apollo-events/v1 → apollo/v1/events
		'apollo-events/v1/events'          => 'apollo/v1/events',
		'apollo-events/v1/events/calendar' => 'apollo/v1/events/calendar',
		'apollo-events/v1/qr'              => 'apollo/v1/events/qr',
	);

	/**
	 * Get the canonical namespace.
	 *
	 * @return string
	 */
	public static function get(): string {
		return self::V1;
	}

	/**
	 * Get full route with namespace.
	 *
	 * @param string $route Route path without namespace.
	 * @return string Full route with namespace.
	 */
	public static function route( string $route ): string {
		$route = \ltrim( $route, '/' );
		return self::V1 . '/' . $route;
	}

	/**
	 * Get prefixed route for a module.
	 *
	 * @param string $module Module name (events, social, etc).
	 * @param string $route  Route within the module.
	 * @return string Full prefixed route.
	 */
	public static function prefixed_route( string $module, string $route = '' ): string {
		$prefix = self::PREFIXES[ $module ] ?? $module;
		$route  = \ltrim( $route, '/' );

		if ( empty( $route ) ) {
			return self::V1 . '/' . $prefix;
		}

		return self::V1 . '/' . $prefix . '/' . $route;
	}

	/**
	 * Get REST URL for an endpoint.
	 *
	 * @param string $route Route path.
	 * @return string Full REST URL.
	 */
	public static function url( string $route = '' ): string {
		$route = \ltrim( $route, '/' );
		return \rest_url( self::V1 . '/' . $route );
	}

	/**
	 * Check if a namespace is legacy.
	 *
	 * @param string $namespace Namespace to check.
	 * @return bool True if legacy.
	 */
	public static function is_legacy( string $namespace ): bool {
		return \in_array( $namespace, array( self::LEGACY_CORE, self::LEGACY_EVENTS ), true );
	}

	/**
	 * Get canonical namespace for a legacy namespace.
	 *
	 * @param string $legacy_namespace Legacy namespace.
	 * @return string Canonical namespace.
	 */
	public static function get_canonical( string $legacy_namespace ): string {
		if ( self::is_legacy( $legacy_namespace ) ) {
			return self::V1;
		}
		return $legacy_namespace;
	}

	/**
	 * Get new route for a legacy route.
	 *
	 * @param string $legacy_route Full legacy route (namespace/path).
	 * @return string|null New route or null if not mapped.
	 */
	public static function get_migrated_route( string $legacy_route ): ?string {
		return self::ROUTE_MIGRATION_MAP[ $legacy_route ] ?? null;
	}

	/**
	 * Get all legacy namespaces.
	 *
	 * @return array<string>
	 */
	public static function get_legacy_namespaces(): array {
		return array(
			self::LEGACY_CORE,
			self::LEGACY_EVENTS,
		);
	}

	/**
	 * Get route migration map.
	 *
	 * @return array<string, string>
	 */
	public static function get_route_migration_map(): array {
		return self::ROUTE_MIGRATION_MAP;
	}

	/**
	 * Generate OpenAPI-compatible namespace info.
	 *
	 * @return array
	 */
	public static function get_api_info(): array {
		return array(
			'title'       => 'Apollo REST API',
			'description' => 'Unified REST API for Apollo plugin ecosystem',
			'version'     => '1.0.0',
			'namespace'   => self::V1,
			'base_url'    => \rest_url( self::V1 ),
			'modules'     => \array_keys( self::PREFIXES ),
		);
	}
}
