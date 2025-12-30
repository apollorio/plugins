<?php
/**
 * Apollo Router
 *
 * Centralized routing management for Apollo Social.
 * Prevents duplicate rewrite rules and controls flush timing.
 *
 * FASE 2: Este roteador centraliza:
 * 1. Todas as regras de rewrite do plugin
 * 2. Flush controlado (apenas em ativação/desativação)
 * 3. Proteção de rotas WordPress (/feed/, /wp-admin/, etc.)
 * 4. Anti-loop para redirects
 *
 * @package Apollo\Infrastructure\Http
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Infrastructure\Http;

use Apollo\Infrastructure\ApolloLogger;
use Apollo\Infrastructure\FeatureFlags;

/**
 * Apollo Router - Centralized Route Management
 *
 * IMPORTANTE: Todas as rotas do Apollo devem ser registradas aqui.
 * NÃO use add_rewrite_rule() diretamente em outros arquivos.
 */
class Apollo_Router {

	/** @var string Prefixo para todas as rotas Apollo */
	public const ROUTE_PREFIX = 'apollo';

	/** @var string Option key for version-based flush */
	private const VERSION_OPTION = 'apollo_rewrite_version';

	/** @var string Current rewrite rules version */
	private const RULES_VERSION = '2.2.0';

	/** @var array Registered routes */
	private static array $routes = array();

	/** @var bool Whether routes have been registered */
	private static bool $initialized = false;

	/** @var array Protected WordPress paths */
	private const WP_PROTECTED_PATHS = array(
		'feed',
		'rss',
		'rss2',
		'atom',
		'rdf',
		'wp-admin',
		'wp-login',
		'wp-json',
		'xmlrpc',
		'wp-cron',
		'wp-content',
		'wp-includes',
	);

	/**
	 * Initialize router
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Register all routes early
		add_action( 'init', array( __CLASS__, 'registerRoutes' ), 5 );

		// Add query vars
		add_filter( 'query_vars', array( __CLASS__, 'addQueryVars' ) );

		// Handle route matching
		add_action( 'template_redirect', array( __CLASS__, 'handleRoutes' ), 1 );

		self::$initialized = true;
	}

	/**
	 * Register all Apollo routes
	 */
	public static function registerRoutes(): void {
		// Register rewrite tags first
		self::registerTags();

		// Collect routes from modules (if enabled)
		self::collectModuleRoutes();

		// Register all collected routes
		foreach ( self::$routes as $route ) {
			if ( self::isProtectedPath( $route['pattern'] ) ) {
				ApolloLogger::warning( 'protected_path_collision', array(
					'pattern' => $route['pattern'],
					'module'  => $route['module'] ?? 'unknown',
				), ApolloLogger::CAT_REWRITE );
				continue;
			}

			add_rewrite_rule(
				$route['pattern'],
				$route['rewrite'],
				$route['position'] ?? 'top'
			);
		}
	}

	/**
	 * Register rewrite tags
	 */
	private static function registerTags(): void {
		add_rewrite_tag( '%apollo_action%', '([^&/]+)' );
		add_rewrite_tag( '%apollo_page%', '([^&/]+)' );
		add_rewrite_tag( '%apollo_id%', '([0-9]+)' );
		add_rewrite_tag( '%apollo_slug%', '([^&/]+)' );
		add_rewrite_tag( '%apollo_username%', '([^&/]+)' );

		// Documents
		add_rewrite_tag( '%doc_action%', '([^&/]+)' );
		add_rewrite_tag( '%doc_id%', '([^&/]+)' );

		// Classifieds
		add_rewrite_tag( '%classified_slug%', '([^&/]+)' );
		add_rewrite_tag( '%classified_id%', '([0-9]+)' );

		// User pages
		add_rewrite_tag( '%user_page%', '([^&/]+)' );
		add_rewrite_tag( '%user_action%', '([^&/]+)' );
	}

	/**
	 * Collect routes from enabled modules
	 */
	private static function collectModuleRoutes(): void {
		// =====================================================================
		// Core Routes (always enabled)
		// =====================================================================
		self::addRoute(
			'^' . self::ROUTE_PREFIX . '/?$',
			'index.php?apollo_action=dashboard',
			'core'
		);

		// =====================================================================
		// User Pages Routes
		// =====================================================================
		if ( FeatureFlags::isEnabled( 'user_pages' ) ) {
			// User profile: /apollo/user/{username}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/user/([^/]+)/?$',
				'index.php?apollo_action=user_profile&apollo_username=$matches[1]',
				'user_pages'
			);

			// User page: /apollo/user/{username}/{page}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/user/([^/]+)/([^/]+)/?$',
				'index.php?apollo_action=user_page&apollo_username=$matches[1]&user_page=$matches[2]',
				'user_pages'
			);
		}

		// =====================================================================
		// Documents Routes
		// =====================================================================
		if ( FeatureFlags::isEnabled( 'documents' ) ) {
			// Documents list: /apollo/documents
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/documents/?$',
				'index.php?apollo_action=documents&doc_action=list',
				'documents'
			);

			// Document view: /apollo/documents/{id}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/documents/([^/]+)/?$',
				'index.php?apollo_action=documents&doc_action=view&doc_id=$matches[1]',
				'documents'
			);

			// Document sign: /apollo/documents/{id}/sign
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/documents/([^/]+)/sign/?$',
				'index.php?apollo_action=documents&doc_action=sign&doc_id=$matches[1]',
				'documents'
			);

			// Document verify: /apollo/documents/{id}/verify
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/documents/([^/]+)/verify/?$',
				'index.php?apollo_action=documents&doc_action=verify&doc_id=$matches[1]',
				'documents'
			);
		}

		// =====================================================================
		// Signatures Routes
		// =====================================================================
		if ( FeatureFlags::isEnabled( 'signatures' ) ) {
			// Signature verification: /apollo/signature/{hash}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/signature/([a-f0-9]+)/?$',
				'index.php?apollo_action=signature_verify&apollo_id=$matches[1]',
				'signatures'
			);
		}

		// =====================================================================
		// Classifieds Routes
		// =====================================================================
		if ( FeatureFlags::isEnabled( 'classifieds' ) ) {
			// Classifieds list: /apollo/classificados
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/classificados/?$',
				'index.php?apollo_action=classifieds&classified_action=list',
				'classifieds'
			);

			// Classified view: /apollo/classificados/{slug}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/classificados/([^/]+)/?$',
				'index.php?apollo_action=classifieds&classified_slug=$matches[1]',
				'classifieds'
			);
		}

		// =====================================================================
		// Builder Routes
		// =====================================================================
		if ( FeatureFlags::isEnabled( 'builder' ) ) {
			// Builder: /apollo/builder
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/builder/?$',
				'index.php?apollo_action=builder&builder_action=index',
				'builder'
			);

			// Builder edit: /apollo/builder/{id}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/builder/([0-9]+)/?$',
				'index.php?apollo_action=builder&builder_action=edit&apollo_id=$matches[1]',
				'builder'
			);
		}

		// =====================================================================
		// Feed Routes (Apollo feed, not WP feed)
		// =====================================================================
		if ( FeatureFlags::isEnabled( 'feed' ) ) {
			// Apollo feed: /apollo/feed
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/feed/?$',
				'index.php?apollo_action=apollo_feed',
				'feed'
			);

			// User feed: /apollo/feed/{username}
			self::addRoute(
				'^' . self::ROUTE_PREFIX . '/feed/([^/]+)/?$',
				'index.php?apollo_action=apollo_feed&apollo_username=$matches[1]',
				'feed'
			);
		}

		// =====================================================================
		// DISABLED MODULES - Routes blocked with 403
		// =====================================================================

		// Chat (disabled by default)
		if ( ! FeatureFlags::isEnabled( 'chat' ) ) {
			self::addBlockedRoute( '^' . self::ROUTE_PREFIX . '/chat/?', 'chat' );
			self::addBlockedRoute( '^' . self::ROUTE_PREFIX . '/messages/?', 'chat' );
		}

		// Groups (disabled by default)
		if ( ! FeatureFlags::isEnabled( 'groups' ) ) {
			self::addBlockedRoute( '^' . self::ROUTE_PREFIX . '/groups/?', 'groups' );
		}

		// GOV.BR (disabled by default)
		if ( ! FeatureFlags::isEnabled( 'govbr' ) ) {
			self::addBlockedRoute( '^' . self::ROUTE_PREFIX . '/govbr/?', 'govbr' );
			self::addBlockedRoute( '^' . self::ROUTE_PREFIX . '/sso/?', 'govbr' );
		}

		// Notifications (disabled by default)
		if ( ! FeatureFlags::isEnabled( 'notifications' ) ) {
			self::addBlockedRoute( '^' . self::ROUTE_PREFIX . '/notifications/?', 'notifications' );
		}
	}

	/**
	 * Add a route to the registry
	 *
	 * @param string $pattern  Regex pattern.
	 * @param string $rewrite  Rewrite string.
	 * @param string $module   Module name.
	 * @param string $position Position (top/bottom).
	 */
	public static function addRoute( string $pattern, string $rewrite, string $module = 'core', string $position = 'top' ): void {
		self::$routes[] = array(
			'pattern'  => $pattern,
			'rewrite'  => $rewrite,
			'module'   => $module,
			'position' => $position,
			'blocked'  => false,
		);
	}

	/**
	 * Add a blocked route (returns 403)
	 *
	 * @param string $pattern Regex pattern.
	 * @param string $module  Module name.
	 */
	public static function addBlockedRoute( string $pattern, string $module ): void {
		self::$routes[] = array(
			'pattern'  => $pattern,
			'rewrite'  => 'index.php?apollo_action=blocked&apollo_module=' . $module,
			'module'   => $module,
			'position' => 'top',
			'blocked'  => true,
		);

		// Also register the rewrite rule
		add_rewrite_rule(
			$pattern,
			'index.php?apollo_action=blocked&apollo_module=' . $module,
			'top'
		);
	}

	/**
	 * Add query vars
	 *
	 * @param array $vars Existing vars.
	 * @return array Modified vars.
	 */
	public static function addQueryVars( array $vars ): array {
		return array_merge( $vars, array(
			'apollo_action',
			'apollo_page',
			'apollo_id',
			'apollo_slug',
			'apollo_username',
			'apollo_module',
			'doc_action',
			'doc_id',
			'classified_slug',
			'classified_id',
			'user_page',
			'user_action',
			'builder_action',
		) );
	}

	/**
	 * Handle route matching
	 */
	public static function handleRoutes(): void {
		// Never intercept WordPress feeds (RSS, Atom, etc).
		if ( is_feed() ) {
			return;
		}

		$action = get_query_var( 'apollo_action' );

		if ( empty( $action ) ) {
			return;
		}

		// Check for blocked routes
		if ( $action === 'blocked' ) {
			$module = get_query_var( 'apollo_module' );

			ApolloLogger::logBlockedAccess( get_current_user_id(), 'route_blocked', array(
				'module' => $module,
				'uri'    => $_SERVER['REQUEST_URI'] ?? '',
			) );

			wp_die(
				__( 'Este recurso não está disponível no momento.', 'apollo-social' ),
				__( 'Recurso Indisponível', 'apollo-social' ),
				array( 'response' => 403 )
			);
		}

		// Anti-loop check
		if ( self::isRedirectLoop() ) {
			ApolloLogger::error( 'redirect_loop_detected', array(
				'action' => $action,
				'uri'    => $_SERVER['REQUEST_URI'] ?? '',
			), ApolloLogger::CAT_REWRITE );

			wp_die(
				__( 'Erro de redirecionamento detectado.', 'apollo-social' ),
				__( 'Erro', 'apollo-social' ),
				array( 'response' => 500 )
			);
		}

		// Set anti-loop cookie
		self::setLoopCookie();
	}

	/**
	 * Check if this is a redirect loop
	 *
	 * @return bool
	 */
	private static function isRedirectLoop(): bool {
		$cookie_name = 'apollo_loop_check';
		$max_hits    = 5;

		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return false;
		}

		$hits = (int) $_COOKIE[ $cookie_name ];
		return $hits >= $max_hits;
	}

	/**
	 * Set anti-loop cookie
	 */
	private static function setLoopCookie(): void {
		$cookie_name = 'apollo_loop_check';
		$hits        = isset( $_COOKIE[ $cookie_name ] ) ? (int) $_COOKIE[ $cookie_name ] + 1 : 1;

		setcookie( $cookie_name, (string) $hits, time() + 5, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Check if path is protected WordPress path
	 *
	 * @param string $pattern Route pattern.
	 * @return bool
	 */
	private static function isProtectedPath( string $pattern ): bool {
		foreach ( self::WP_PROTECTED_PATHS as $protected ) {
			if ( strpos( $pattern, '^' . $protected ) === 0 ) {
				return true;
			}
			if ( strpos( $pattern, '/' . $protected ) !== false && strpos( $pattern, self::ROUTE_PREFIX ) !== 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Maybe flush rewrite rules (version-based)
	 */
	public static function maybeFlush(): void {
		// Never flush on frontend
		if ( ! is_admin() ) {
			return;
		}

		// Never flush on AJAX
		if ( wp_doing_ajax() ) {
			return;
		}

		$stored_version = get_option( self::VERSION_OPTION, '' );

		if ( $stored_version !== self::RULES_VERSION ) {
			self::flush();
			update_option( self::VERSION_OPTION, self::RULES_VERSION );

			ApolloLogger::info( 'rewrite_rules_flushed', array(
				'old_version' => $stored_version,
				'new_version' => self::RULES_VERSION,
			), ApolloLogger::CAT_REWRITE );
		}
	}

	/**
	 * Force flush rewrite rules
	 */
	public static function flush(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * Activation hook - flush rules
	 */
	public static function onActivation(): void {
		self::registerRoutes();
		self::flush();
		update_option( self::VERSION_OPTION, self::RULES_VERSION );

		ApolloLogger::info( 'plugin_activated_flush', array(
			'version' => self::RULES_VERSION,
		), ApolloLogger::CAT_REWRITE );
	}

	/**
	 * Deactivation hook - flush rules
	 */
	public static function onDeactivation(): void {
		delete_option( self::VERSION_OPTION );
		self::flush();

		ApolloLogger::info( 'plugin_deactivated_flush', array(), ApolloLogger::CAT_REWRITE );
	}

	/**
	 * Get all registered routes
	 *
	 * @return array
	 */
	public static function getRoutes(): array {
		return self::$routes;
	}

	/**
	 * Get route inventory for debugging
	 *
	 * @return array
	 */
	public static function getInventory(): array {
		$inventory = array(
			'version'         => self::RULES_VERSION,
			'prefix'          => self::ROUTE_PREFIX,
			'total_routes'    => count( self::$routes ),
			'routes_by_module' => array(),
			'blocked_routes'  => array(),
		);

		foreach ( self::$routes as $route ) {
			$module = $route['module'];

			if ( ! isset( $inventory['routes_by_module'][ $module ] ) ) {
				$inventory['routes_by_module'][ $module ] = array();
			}

			$inventory['routes_by_module'][ $module ][] = array(
				'pattern' => $route['pattern'],
				'blocked' => $route['blocked'] ?? false,
			);

			if ( $route['blocked'] ?? false ) {
				$inventory['blocked_routes'][] = $route['pattern'];
			}
		}

		return $inventory;
	}

	/**
	 * Debug: dump current rewrite rules
	 *
	 * @return array WordPress rewrite rules.
	 */
	public static function dumpRules(): array {
		global $wp_rewrite;

		$all_rules   = $wp_rewrite->wp_rewrite_rules();
		$apollo_rules = array();

		if ( is_array( $all_rules ) ) {
			foreach ( $all_rules as $pattern => $rewrite ) {
				if ( strpos( $pattern, self::ROUTE_PREFIX ) !== false ||
					 strpos( $rewrite, 'apollo_' ) !== false ) {
					$apollo_rules[ $pattern ] = $rewrite;
				}
			}
		}

		return $apollo_rules;
	}
}
