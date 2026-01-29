<?php
/**
 * Apollo Hook Priorities
 *
 * Centralized priority constants for all WordPress hooks across Apollo plugins.
 * Ensures consistent execution order and prevents timing conflicts.
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
 * Class Apollo_Hook_Priorities
 *
 * Defines standardized hook priorities for the Apollo plugin ecosystem.
 */
final class Apollo_Hook_Priorities {

	/*
	|--------------------------------------------------------------------------
	| Plugin Loading Order (plugins_loaded)
	|--------------------------------------------------------------------------
	|
	| These priorities define when each plugin initializes during plugins_loaded.
	| Lower numbers load first. Core must load before all other plugins.
	|
	*/

	/** Core plugin initialization (must be first) */
	public const CORE_INIT = 1;

	/** Events Manager initialization */
	public const EVENTS_INIT = 5;

	/** Social plugin initialization */
	public const SOCIAL_INIT = 5;

	/** Rio PWA plugin initialization (last, for optimization) */
	public const RIO_INIT = 20;

	/** Third-party integrations */
	public const THIRD_PARTY_INIT = 50;

	/*
	|--------------------------------------------------------------------------
	| Registration Priorities (init)
	|--------------------------------------------------------------------------
	|
	| These priorities ensure proper registration order during WordPress init.
	| CPTs must register before taxonomies, taxonomies before meta, etc.
	|
	*/

	/** Custom Post Type registration (earliest) */
	public const CPT_REGISTRATION = 5;

	/** Taxonomy registration (after CPTs) */
	public const TAXONOMY_REGISTRATION = 6;

	/** Meta key registration (after taxonomies) */
	public const META_REGISTRATION = 7;

	/** Rewrite rules registration */
	public const REWRITE_REGISTRATION = 8;

	/** REST API route registration */
	public const REST_REGISTRATION = 10;

	/** Shortcode registration */
	public const SHORTCODE_REGISTRATION = 15;

	/** Widget registration */
	public const WIDGET_REGISTRATION = 20;

	/** Block registration */
	public const BLOCK_REGISTRATION = 25;

	/*
	|--------------------------------------------------------------------------
	| Template & Rendering Priorities
	|--------------------------------------------------------------------------
	|
	| Control the order of template modifications and rendering hooks.
	|
	*/

	/** Before any rendering occurs */
	public const BEFORE_RENDER = 5;

	/** Default rendering priority */
	public const RENDER = 10;

	/** After rendering completes */
	public const AFTER_RENDER = 15;

	/** Template override (high priority) */
	public const TEMPLATE_OVERRIDE = 1;

	/** Template fallback (low priority) */
	public const TEMPLATE_FALLBACK = 100;

	/*
	|--------------------------------------------------------------------------
	| Data Processing Priorities
	|--------------------------------------------------------------------------
	|
	| Order for data transformation, validation, and sanitization.
	|
	*/

	/** Data validation (first) */
	public const VALIDATION = 5;

	/** Data sanitization */
	public const SANITIZATION = 10;

	/** Data transformation */
	public const TRANSFORMATION = 15;

	/** Data persistence */
	public const PERSISTENCE = 20;

	/** Cache update (after persistence) */
	public const CACHE_UPDATE = 25;

	/*
	|--------------------------------------------------------------------------
	| Event Propagation Priorities
	|--------------------------------------------------------------------------
	|
	| Order for cross-plugin event handling.
	|
	*/

	/** Critical handlers (logging, security) */
	public const EVENT_CRITICAL = 1;

	/** Core plugin event handlers */
	public const EVENT_CORE = 5;

	/** Companion plugin event handlers */
	public const EVENT_COMPANION = 10;

	/** Analytics and tracking */
	public const EVENT_ANALYTICS = 50;

	/** Cleanup handlers (last) */
	public const EVENT_CLEANUP = 100;

	/*
	|--------------------------------------------------------------------------
	| Admin Priorities
	|--------------------------------------------------------------------------
	|
	| Order for admin menu, settings, and notices.
	|
	*/

	/** Admin menu registration */
	public const ADMIN_MENU = 10;

	/** Admin submenu registration */
	public const ADMIN_SUBMENU = 20;

	/** Admin settings registration */
	public const ADMIN_SETTINGS = 10;

	/** Admin notices (high priority for important) */
	public const ADMIN_NOTICE_HIGH = 5;

	/** Admin notices (normal) */
	public const ADMIN_NOTICE = 10;

	/** Admin notices (low priority) */
	public const ADMIN_NOTICE_LOW = 20;

	/*
	|--------------------------------------------------------------------------
	| AJAX & REST Priorities
	|--------------------------------------------------------------------------
	|
	| Order for AJAX and REST API request handling.
	|
	*/

	/** Security/authentication check */
	public const REST_AUTH = 1;

	/** Rate limiting check */
	public const REST_RATE_LIMIT = 2;

	/** Request validation */
	public const REST_VALIDATE = 5;

	/** Request processing */
	public const REST_PROCESS = 10;

	/** Response formatting */
	public const REST_FORMAT = 15;

	/*
	|--------------------------------------------------------------------------
	| Cache Priorities
	|--------------------------------------------------------------------------
	|
	| Order for cache operations.
	|
	*/

	/** Cache read (try cache first) */
	public const CACHE_READ = 1;

	/** Cache bypass check */
	public const CACHE_BYPASS = 2;

	/** Cache write */
	public const CACHE_WRITE = 100;

	/** Cache invalidation */
	public const CACHE_INVALIDATE = 50;

	/*
	|--------------------------------------------------------------------------
	| Filter Priorities (common patterns)
	|--------------------------------------------------------------------------
	|
	| Standardized priorities for filter chains.
	|
	*/

	/** First in filter chain */
	public const FILTER_FIRST = 1;

	/** Early in filter chain */
	public const FILTER_EARLY = 5;

	/** Default filter priority */
	public const FILTER_DEFAULT = 10;

	/** Late in filter chain */
	public const FILTER_LATE = 15;

	/** Last in filter chain */
	public const FILTER_LAST = 100;

	/** Override all others */
	public const FILTER_OVERRIDE = 999;

	/*
	|--------------------------------------------------------------------------
	| Helper Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all priorities as an array
	 *
	 * @return array<string, int>
	 */
	public static function get_all(): array {
		$reflection = new \ReflectionClass( self::class );
		return $reflection->getConstants();
	}

	/**
	 * Get priorities for a specific category
	 *
	 * @param string $prefix Category prefix (e.g., 'CPT', 'REST', 'CACHE').
	 * @return array<string, int>
	 */
	public static function get_by_category( string $prefix ): array {
		$all    = self::get_all();
		$prefix = \strtoupper( $prefix ) . '_';

		return \array_filter(
			$all,
			fn( $key ) => \str_starts_with( $key, $prefix ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get the recommended priority for a hook type
	 *
	 * @param string $type Hook type (cpt, taxonomy, rest, etc).
	 * @return int Priority value.
	 */
	public static function for_type( string $type ): int {
		$map = array(
			'cpt'       => self::CPT_REGISTRATION,
			'taxonomy'  => self::TAXONOMY_REGISTRATION,
			'meta'      => self::META_REGISTRATION,
			'rest'      => self::REST_REGISTRATION,
			'shortcode' => self::SHORTCODE_REGISTRATION,
			'widget'    => self::WIDGET_REGISTRATION,
			'block'     => self::BLOCK_REGISTRATION,
		);

		return $map[ \strtolower( $type ) ] ?? self::FILTER_DEFAULT;
	}

	/**
	 * Get priority relative to another
	 *
	 * @param int $base   Base priority.
	 * @param int $offset Offset (positive = after, negative = before).
	 * @return int Calculated priority.
	 */
	public static function relative( int $base, int $offset ): int {
		return \max( 1, $base + $offset );
	}

	/**
	 * Ensure priority is before another
	 *
	 * @param int $before Priority to be before.
	 * @return int Priority that comes before.
	 */
	public static function before( int $before ): int {
		return \max( 1, $before - 1 );
	}

	/**
	 * Ensure priority is after another
	 *
	 * @param int $after Priority to be after.
	 * @return int Priority that comes after.
	 */
	public static function after( int $after ): int {
		return $after + 1;
	}
}
