<?php
/**
 * Apollo REST Route Registry
 *
 * Central registration system for all REST routes across Apollo plugins.
 * Provides conflict detection, documentation generation, and route management.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\REST_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_REST_Registry
 *
 * Central registry for all Apollo REST routes.
 */
class Apollo_REST_Registry {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_REST_Registry|null
	 */
	private static ?Apollo_REST_Registry $instance = null;

	/**
	 * Registered routes
	 *
	 * @var array<string, array>
	 */
	private array $routes = array();

	/**
	 * Route conflicts
	 *
	 * @var array<string, array>
	 */
	private array $conflicts = array();

	/**
	 * Controllers
	 *
	 * @var array<string, object>
	 */
	private array $controllers = array();

	/**
	 * Route groups
	 *
	 * @var array<string, array>
	 */
	private array $groups = array();

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_REST_Registry
	 */
	public static function get_instance(): Apollo_REST_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Register routes on rest_api_init.
		\add_action( 'rest_api_init', array( $this, 'register_all_routes' ), 5 );

		// Add discovery endpoint.
		\add_action( 'rest_api_init', array( $this, 'register_discovery_endpoint' ), 100 );

		// Collect routes after all are registered.
		\add_action( 'rest_api_init', array( $this, 'collect_routes' ), 999 );
	}

	/**
	 * Register a route
	 *
	 * @param string $route    Route path (without namespace).
	 * @param array  $args     Route arguments.
	 * @param string $plugin   Registering plugin.
	 * @param string $group    Route group (events, social, etc).
	 * @return bool Success.
	 */
	public function register(
		string $route,
		array $args,
		string $plugin = 'apollo-core',
		string $group = 'general'
	): bool {
		$namespace  = Apollo_REST_Namespace::V1;
		$full_route = $namespace . '/' . \ltrim( $route, '/' );

		// Check for conflicts.
		if ( isset( $this->routes[ $full_route ] ) ) {
			$this->conflicts[ $full_route ][] = array(
				'plugin'   => $plugin,
				'existing' => $this->routes[ $full_route ]['plugin'],
			);

			// Log conflict in debug mode.
			if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				\error_log(
					\sprintf(
						'Apollo REST: Route conflict detected for %s (registered by %s, attempted by %s)',
						$full_route,
						$this->routes[ $full_route ]['plugin'],
						$plugin
					)
				);
			}

			return false;
		}

		// Normalize args.
		$args = $this->normalize_route_args( $args );

		// Store route info.
		$this->routes[ $full_route ] = array(
			'route'     => $route,
			'namespace' => $namespace,
			'args'      => $args,
			'plugin'    => $plugin,
			'group'     => $group,
			'methods'   => $this->extract_methods( $args ),
		);

		// Add to group.
		if ( ! isset( $this->groups[ $group ] ) ) {
			$this->groups[ $group ] = array();
		}
		$this->groups[ $group ][] = $full_route;

		// Actually register the route.
		\register_rest_route( $namespace, $route, $args );

		return true;
	}

	/**
	 * Register a controller
	 *
	 * @param string $name       Controller name.
	 * @param object $controller Controller instance.
	 * @return void
	 */
	public function register_controller( string $name, object $controller ): void {
		$this->controllers[ $name ] = $controller;

		// If controller has register_routes method, call it.
		if ( \method_exists( $controller, 'register_routes' ) ) {
			$controller->register_routes();
		}
	}

	/**
	 * Get a registered controller
	 *
	 * @param string $name Controller name.
	 * @return object|null Controller or null.
	 */
	public function get_controller( string $name ): ?object {
		return $this->controllers[ $name ] ?? null;
	}

	/**
	 * Normalize route arguments
	 *
	 * @param array $args Route args.
	 * @return array Normalized args.
	 */
	private function normalize_route_args( array $args ): array {
		// Ensure consistent structure.
		if ( isset( $args['callback'] ) && ! isset( $args['methods'] ) ) {
			$args['methods'] = 'GET';
		}

		// Add default permission callback if missing.
		if ( ! isset( $args['permission_callback'] ) ) {
			$args['permission_callback'] = '__return_true';
		}

		return $args;
	}

	/**
	 * Extract HTTP methods from args
	 *
	 * @param array $args Route args.
	 * @return array Methods.
	 */
	private function extract_methods( array $args ): array {
		$methods = array();

		if ( isset( $args['methods'] ) ) {
			if ( \is_string( $args['methods'] ) ) {
				$methods = \explode( ',', $args['methods'] );
			} else {
				$methods = (array) $args['methods'];
			}
		}

		// Check for multiple endpoint definitions.
		foreach ( $args as $key => $value ) {
			if ( \is_numeric( $key ) && \is_array( $value ) && isset( $value['methods'] ) ) {
				if ( \is_string( $value['methods'] ) ) {
					$methods = \array_merge( $methods, \explode( ',', $value['methods'] ) );
				} else {
					$methods = \array_merge( $methods, (array) $value['methods'] );
				}
			}
		}

		return \array_unique( \array_map( 'strtoupper', \array_map( 'trim', $methods ) ) );
	}

	/**
	 * Register all queued routes
	 *
	 * @return void
	 */
	public function register_all_routes(): void {
		/**
		 * Fires before Apollo routes are registered.
		 *
		 * @param Apollo_REST_Registry $registry The registry instance.
		 */
		\do_action( 'apollo_rest_before_register', $this );

		/**
		 * Fires to allow plugins to register routes.
		 *
		 * @param Apollo_REST_Registry $registry The registry instance.
		 */
		\do_action( 'apollo_rest_register_routes', $this );

		/**
		 * Fires after Apollo routes are registered.
		 *
		 * @param Apollo_REST_Registry $registry The registry instance.
		 */
		\do_action( 'apollo_rest_after_register', $this );
	}

	/**
	 * Register discovery endpoint
	 *
	 * @return void
	 */
	public function register_discovery_endpoint(): void {
		\register_rest_route(
			Apollo_REST_Namespace::V1,
			'/discover',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_discovery' ),
				'permission_callback' => '__return_true',
			)
		);

		\register_rest_route(
			Apollo_REST_Namespace::V1,
			'/discover/(?P<group>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_group_discovery' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'group' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Handle discovery request
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_discovery( \WP_REST_Request $request ): \WP_REST_Response {
		$data = array(
			'api'        => Apollo_REST_Namespace::get_api_info(),
			'groups'     => \array_keys( $this->groups ),
			'routes'     => $this->get_route_documentation(),
			'conflicts'  => $this->conflicts,
			'statistics' => $this->get_statistics(),
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Handle group discovery request
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_group_discovery( \WP_REST_Request $request ): \WP_REST_Response {
		$group = $request->get_param( 'group' );

		if ( ! isset( $this->groups[ $group ] ) ) {
			return new \WP_REST_Response(
				array(
					'error'     => 'Group not found',
					'available' => \array_keys( $this->groups ),
				),
				404
			);
		}

		$routes = array();
		foreach ( $this->groups[ $group ] as $full_route ) {
			if ( isset( $this->routes[ $full_route ] ) ) {
				$routes[ $full_route ] = $this->format_route_for_docs( $this->routes[ $full_route ] );
			}
		}

		return new \WP_REST_Response(
			array(
				'group'  => $group,
				'routes' => $routes,
			),
			200
		);
	}

	/**
	 * Collect routes after registration
	 *
	 * @return void
	 */
	public function collect_routes(): void {
		$server     = \rest_get_server();
		$all_routes = $server->get_routes();

		// Filter Apollo routes.
		foreach ( $all_routes as $route => $handlers ) {
			if ( \strpos( $route, '/' . Apollo_REST_Namespace::V1 ) === 0 ) {
				if ( ! isset( $this->routes[ $route ] ) ) {
					// Route registered directly, not through registry.
					$this->routes[ $route ] = array(
						'route'     => $route,
						'namespace' => Apollo_REST_Namespace::V1,
						'args'      => $handlers,
						'plugin'    => 'unknown',
						'group'     => 'untracked',
						'methods'   => $this->extract_methods_from_handlers( $handlers ),
					);
				}
			}
		}
	}

	/**
	 * Extract methods from route handlers
	 *
	 * @param array $handlers Route handlers.
	 * @return array Methods.
	 */
	private function extract_methods_from_handlers( array $handlers ): array {
		$methods = array();

		foreach ( $handlers as $handler ) {
			if ( isset( $handler['methods'] ) ) {
				$methods = \array_merge( $methods, \array_keys( $handler['methods'] ) );
			}
		}

		return \array_unique( $methods );
	}

	/**
	 * Get route documentation
	 *
	 * @return array
	 */
	public function get_route_documentation(): array {
		$docs = array();

		foreach ( $this->routes as $full_route => $info ) {
			$docs[ $full_route ] = $this->format_route_for_docs( $info );
		}

		return $docs;
	}

	/**
	 * Format route for documentation
	 *
	 * @param array $info Route info.
	 * @return array Formatted info.
	 */
	private function format_route_for_docs( array $info ): array {
		return array(
			'route'   => $info['route'],
			'methods' => $info['methods'],
			'plugin'  => $info['plugin'],
			'group'   => $info['group'],
		);
	}

	/**
	 * Get statistics
	 *
	 * @return array
	 */
	public function get_statistics(): array {
		$by_plugin = array();
		$by_method = array();
		$by_group  = array();

		foreach ( $this->routes as $info ) {
			// By plugin.
			$plugin               = $info['plugin'];
			$by_plugin[ $plugin ] = ( $by_plugin[ $plugin ] ?? 0 ) + 1;

			// By method.
			foreach ( $info['methods'] as $method ) {
				$by_method[ $method ] = ( $by_method[ $method ] ?? 0 ) + 1;
			}

			// By group.
			$group              = $info['group'];
			$by_group[ $group ] = ( $by_group[ $group ] ?? 0 ) + 1;
		}

		return array(
			'total_routes'    => \count( $this->routes ),
			'total_conflicts' => \count( $this->conflicts ),
			'by_plugin'       => $by_plugin,
			'by_method'       => $by_method,
			'by_group'        => $by_group,
		);
	}

	/**
	 * Get all registered routes
	 *
	 * @return array
	 */
	public function get_routes(): array {
		return $this->routes;
	}

	/**
	 * Get routes by group
	 *
	 * @param string $group Group name.
	 * @return array
	 */
	public function get_routes_by_group( string $group ): array {
		$routes = array();

		if ( isset( $this->groups[ $group ] ) ) {
			foreach ( $this->groups[ $group ] as $full_route ) {
				if ( isset( $this->routes[ $full_route ] ) ) {
					$routes[ $full_route ] = $this->routes[ $full_route ];
				}
			}
		}

		return $routes;
	}

	/**
	 * Get routes by plugin
	 *
	 * @param string $plugin Plugin name.
	 * @return array
	 */
	public function get_routes_by_plugin( string $plugin ): array {
		return \array_filter( $this->routes, fn( $info ) => $info['plugin'] === $plugin );
	}

	/**
	 * Check if route exists
	 *
	 * @param string $route Route path.
	 * @return bool
	 */
	public function has_route( string $route ): bool {
		$full_route = Apollo_REST_Namespace::V1 . '/' . \ltrim( $route, '/' );
		return isset( $this->routes[ $full_route ] );
	}

	/**
	 * Get conflicts
	 *
	 * @return array
	 */
	public function get_conflicts(): array {
		return $this->conflicts;
	}

	/**
	 * Check if there are conflicts
	 *
	 * @return bool
	 */
	public function has_conflicts(): bool {
		return ! empty( $this->conflicts );
	}

	/**
	 * Generate OpenAPI spec
	 *
	 * @return array
	 */
	public function generate_openapi_spec(): array {
		$paths = array();

		foreach ( $this->routes as $full_route => $info ) {
			$path           = '/' . $info['route'];
			$paths[ $path ] = array();

			foreach ( $info['methods'] as $method ) {
				$paths[ $path ][ \strtolower( $method ) ] = array(
					'summary'     => $info['route'],
					'tags'        => array( $info['group'] ),
					'operationId' => $this->generate_operation_id( $info['route'], $method ),
					'responses'   => array(
						'200' => array( 'description' => 'Successful response' ),
						'400' => array( 'description' => 'Bad request' ),
						'401' => array( 'description' => 'Unauthorized' ),
						'404' => array( 'description' => 'Not found' ),
					),
				);
			}
		}

		return array(
			'openapi' => '3.0.0',
			'info'    => Apollo_REST_Namespace::get_api_info(),
			'servers' => array(
				array( 'url' => \rest_url( Apollo_REST_Namespace::V1 ) ),
			),
			'paths'   => $paths,
		);
	}

	/**
	 * Generate operation ID
	 *
	 * @param string $route  Route path.
	 * @param string $method HTTP method.
	 * @return string
	 */
	private function generate_operation_id( string $route, string $method ): string {
		$parts = \explode( '/', \trim( $route, '/' ) );
		$parts = \array_filter( $parts, fn( $p ) => ! \preg_match( '/^\(\?P/', $p ) );
		$name  = \implode( '_', $parts );
		return \strtolower( $method ) . '_' . \sanitize_key( $name );
	}
}

// Initialize registry.
Apollo_REST_Registry::get_instance();
