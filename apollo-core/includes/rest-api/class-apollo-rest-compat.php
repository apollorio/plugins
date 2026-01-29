<?php
/**
 * Apollo REST Backward Compatibility Layer
 *
 * Handles legacy namespace redirects and deprecation warnings.
 * Ensures smooth migration from old endpoints to unified namespace.
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
 * Class Apollo_REST_Compat
 *
 * Backward compatibility for legacy REST namespaces.
 */
class Apollo_REST_Compat {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_REST_Compat|null
	 */
	private static ?Apollo_REST_Compat $instance = null;

	/**
	 * Deprecation log
	 *
	 * @var array<string, array>
	 */
	private array $deprecation_log = array();

	/**
	 * Legacy route mappings
	 *
	 * @var array<string, string>
	 */
	private array $legacy_mappings = array();

	/**
	 * Redirect count (for debugging)
	 *
	 * @var int
	 */
	private int $redirect_count = 0;

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_REST_Compat
	 */
	public static function get_instance(): Apollo_REST_Compat {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_legacy_mappings();
		$this->init_hooks();
	}

	/**
	 * Initialize legacy route mappings
	 *
	 * @return void
	 */
	private function init_legacy_mappings(): void {
		/**
		 * Legacy routes from apollo-core/v1 namespace.
		 */
		$core_routes = array(
			// Events (moved from core to unified).
			'apollo-core/v1/events'                  => 'apollo/v1/events',
			'apollo-core/v1/events/(?P<id>\d+)'      => 'apollo/v1/events/(?P<id>\d+)',
			'apollo-core/v1/events/featured'         => 'apollo/v1/events/featured',
			'apollo-core/v1/events/upcoming'         => 'apollo/v1/events/upcoming',

			// DJs.
			'apollo-core/v1/djs'                     => 'apollo/v1/djs',
			'apollo-core/v1/djs/(?P<id>\d+)'         => 'apollo/v1/djs/(?P<id>\d+)',
			'apollo-core/v1/djs/(?P<id>\d+)/events'  => 'apollo/v1/djs/(?P<id>\d+)/events',

			// Venues.
			'apollo-core/v1/venues'                  => 'apollo/v1/venues',
			'apollo-core/v1/venues/(?P<id>\d+)'      => 'apollo/v1/venues/(?P<id>\d+)',
			'apollo-core/v1/locals'                  => 'apollo/v1/venues',

			// Classifieds.
			'apollo-core/v1/classifieds'             => 'apollo/v1/classifieds',
			'apollo-core/v1/classifieds/(?P<id>\d+)' => 'apollo/v1/classifieds/(?P<id>\d+)',

			// Suppliers.
			'apollo-core/v1/suppliers'               => 'apollo/v1/suppliers',
			'apollo-core/v1/suppliers/(?P<id>\d+)'   => 'apollo/v1/suppliers/(?P<id>\d+)',

			// User.
			'apollo-core/v1/user/profile'            => 'apollo/v1/users/me',
			'apollo-core/v1/user/settings'           => 'apollo/v1/users/me/settings',
		);

		/**
		 * Legacy routes from apollo-events/v1 namespace.
		 */
		$events_routes = array(
			// Events.
			'apollo-events/v1/events'             => 'apollo/v1/events',
			'apollo-events/v1/events/(?P<id>\d+)' => 'apollo/v1/events/(?P<id>\d+)',
			'apollo-events/v1/list'               => 'apollo/v1/events',
			'apollo-events/v1/featured'           => 'apollo/v1/events/featured',
			'apollo-events/v1/upcoming'           => 'apollo/v1/events/upcoming',

			// Search.
			'apollo-events/v1/search'             => 'apollo/v1/events/search',
			'apollo-events/v1/geo-search'         => 'apollo/v1/events/geo-search',

			// Venues (from events manager).
			'apollo-events/v1/venues'             => 'apollo/v1/venues',
			'apollo-events/v1/locations'          => 'apollo/v1/venues',

			// DJs (from events manager).
			'apollo-events/v1/artists'            => 'apollo/v1/djs',
			'apollo-events/v1/djs'                => 'apollo/v1/djs',

			// Categories.
			'apollo-events/v1/categories'         => 'apollo/v1/events/categories',
			'apollo-events/v1/music-styles'       => 'apollo/v1/events/music-styles',
		);

		/**
		 * Legacy routes from apollo-social/v1 (already unified, but some may have changed).
		 */
		$social_routes = array(
			// Moderation (duplicate in core).
			'apollo-core/v1/mod/reports'        => 'apollo/v1/mod/reports',
			'apollo-core/v1/mod/actions'        => 'apollo/v1/mod/actions',
			'apollo-core/v1/moderation/queue'   => 'apollo/v1/mod/queue',

			// Activity.
			'apollo-core/v1/activity'           => 'apollo/v1/social/activity',
			'apollo-core/v1/activity/feed'      => 'apollo/v1/social/activity/feed',

			// Notifications.
			'apollo-core/v1/notifications'      => 'apollo/v1/social/notifications',
			'apollo-core/v1/user/notifications' => 'apollo/v1/social/notifications',

			// Groups (if any legacy).
			'apollo-core/v1/groups'             => 'apollo/v1/groups',
			'apollo-core/v1/comunas'            => 'apollo/v1/groups/comunas',
			'apollo-core/v1/nucleos'            => 'apollo/v1/groups/nucleos',
		);

		$this->legacy_mappings = \array_merge( $core_routes, $events_routes, $social_routes );

		/**
		 * Filter to add custom legacy mappings.
		 *
		 * @param array $mappings Current legacy mappings.
		 */
		$this->legacy_mappings = \apply_filters( 'apollo_rest_legacy_mappings', $this->legacy_mappings );
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Register legacy namespace routes.
		\add_action( 'rest_api_init', array( $this, 'register_legacy_routes' ), 100 );

		// Add deprecation headers.
		\add_filter( 'rest_pre_serve_request', array( $this, 'add_deprecation_headers' ), 10, 4 );

		// Log deprecation usage (in debug mode).
		\add_action( 'shutdown', array( $this, 'log_deprecations' ) );

		// Admin notice for deprecation.
		\add_action( 'admin_notices', array( $this, 'admin_deprecation_notice' ) );
	}

	/**
	 * Register legacy routes as redirects
	 *
	 * @return void
	 */
	public function register_legacy_routes(): void {
		foreach ( $this->legacy_mappings as $legacy_route => $new_route ) {
			$parts            = \explode( '/', $legacy_route, 2 );
			$legacy_namespace = $parts[0];
			$route_path       = $parts[1] ?? '';

			\register_rest_route(
				$legacy_namespace,
				$route_path,
				array(
					array(
						'methods'             => \WP_REST_Server::ALLMETHODS,
						'callback'            => fn( $request ) => $this->handle_legacy_request( $request, $legacy_route, $new_route ),
						'permission_callback' => '__return_true',
					),
				)
			);
		}
	}

	/**
	 * Handle legacy request
	 *
	 * @param \WP_REST_Request $request      Original request.
	 * @param string           $legacy_route Legacy route.
	 * @param string           $new_route    New route.
	 * @return \WP_REST_Response
	 */
	private function handle_legacy_request(
		\WP_REST_Request $request,
		string $legacy_route,
		string $new_route
	): \WP_REST_Response {
		++$this->redirect_count;

		// Log the deprecation.
		$this->log_deprecation( $legacy_route, $new_route, $request );

		// Build the new URL.
		$new_url = $this->build_new_url( $request, $new_route );

		// Create internal request to new endpoint.
		$internal_request = new \WP_REST_Request(
			$request->get_method(),
			'/' . $new_route
		);

		// Copy parameters.
		$internal_request->set_query_params( $request->get_query_params() );
		$internal_request->set_body_params( $request->get_body_params() );
		$internal_request->set_headers( $request->get_headers() );

		// Copy URL params.
		foreach ( $request->get_url_params() as $key => $value ) {
			$internal_request->set_url_params( array( $key => $value ) );
		}

		// Dispatch the new request.
		$server   = \rest_get_server();
		$response = $server->dispatch( $internal_request );

		// Add deprecation headers.
		$response->header( 'X-Apollo-Deprecated', 'true' );
		$response->header( 'X-Apollo-Deprecated-Route', $legacy_route );
		$response->header( 'X-Apollo-Replacement-Route', $new_route );
		$response->header( 'X-Apollo-Replacement-URL', $new_url );
		$response->header(
			'Deprecation',
			'true; sunset="2025-12-31T23:59:59Z"'
		);
		$response->header(
			'Sunset',
			'Sat, 31 Dec 2025 23:59:59 GMT'
		);
		$response->header(
			'Link',
			\sprintf( '<%s>; rel="successor-version"', $new_url )
		);

		// Add warning to response data.
		$data = $response->get_data();
		if ( \is_array( $data ) ) {
			$data['_deprecated'] = array(
				'warning'     => 'This endpoint is deprecated and will be removed.',
				'legacy'      => $legacy_route,
				'replacement' => $new_route,
				'sunset'      => '2025-12-31',
				'docs'        => \rest_url( 'apollo/v1/discover' ),
			);
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Build new URL from legacy request
	 *
	 * @param \WP_REST_Request $request   Original request.
	 * @param string           $new_route New route.
	 * @return string
	 */
	private function build_new_url( \WP_REST_Request $request, string $new_route ): string {
		// Replace URL params in new route.
		$url = $new_route;
		foreach ( $request->get_url_params() as $key => $value ) {
			$pattern = "/\(\?P<{$key}>[^)]+\)/";
			$url     = \preg_replace( $pattern, $value, $url );
		}

		// Build full URL.
		$full_url = \rest_url( $url );

		// Add query params.
		$query_params = $request->get_query_params();
		if ( ! empty( $query_params ) ) {
			$full_url = \add_query_arg( $query_params, $full_url );
		}

		return $full_url;
	}

	/**
	 * Log deprecation usage
	 *
	 * @param string           $legacy_route Legacy route.
	 * @param string           $new_route    New route.
	 * @param \WP_REST_Request $request      Request object.
	 * @return void
	 */
	private function log_deprecation(
		string $legacy_route,
		string $new_route,
		\WP_REST_Request $request
	): void {
		if ( ! isset( $this->deprecation_log[ $legacy_route ] ) ) {
			$this->deprecation_log[ $legacy_route ] = array(
				'new_route' => $new_route,
				'count'     => 0,
				'last_used' => '',
				'clients'   => array(),
			);
		}

		++$this->deprecation_log[ $legacy_route ]['count'];
		$this->deprecation_log[ $legacy_route ]['last_used'] = \current_time( 'mysql' );

		// Track client (in debug mode only).
		if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$client = $request->get_header( 'user-agent' ) ?? 'unknown';
			if ( ! \in_array( $client, $this->deprecation_log[ $legacy_route ]['clients'], true ) ) {
				$this->deprecation_log[ $legacy_route ]['clients'][] = $client;
			}
		}
	}

	/**
	 * Add deprecation headers to legacy namespace responses
	 *
	 * @param bool              $served  Whether the request has been served.
	 * @param \WP_REST_Response $result  Response object.
	 * @param \WP_REST_Request  $request Request object.
	 * @param \WP_REST_Server   $server  Server object.
	 * @return bool
	 */
	public function add_deprecation_headers(
		bool $served,
		\WP_REST_Response $result,
		\WP_REST_Request $request,
		\WP_REST_Server $server
	): bool {
		$route = $request->get_route();

		// Check if this is a legacy namespace.
		if ( Apollo_REST_Namespace::is_legacy( $this->extract_namespace( $route ) ) ) {
			$result->header( 'X-Apollo-Namespace-Deprecated', 'true' );
			$result->header(
				'X-Apollo-Canonical-Namespace',
				Apollo_REST_Namespace::V1
			);
		}

		return $served;
	}

	/**
	 * Extract namespace from route
	 *
	 * @param string $route Full route.
	 * @return string Namespace.
	 */
	private function extract_namespace( string $route ): string {
		$route = \ltrim( $route, '/' );
		$parts = \explode( '/', $route );

		if ( \count( $parts ) >= 2 ) {
			return $parts[0] . '/' . $parts[1];
		}

		return $parts[0] ?? '';
	}

	/**
	 * Log deprecations at shutdown
	 *
	 * @return void
	 */
	public function log_deprecations(): void {
		if ( empty( $this->deprecation_log ) ) {
			return;
		}

		// Only log in debug mode.
		if ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Log to debug.log.
		foreach ( $this->deprecation_log as $route => $info ) {
			\error_log(
				\sprintf(
					'Apollo REST Deprecation: %s called %d times (replacement: %s)',
					$route,
					$info['count'],
					$info['new_route']
				)
			);
		}

		// Store aggregate data for admin display.
		$stored = \get_option( 'apollo_rest_deprecation_stats', array() );

		foreach ( $this->deprecation_log as $route => $info ) {
			if ( ! isset( $stored[ $route ] ) ) {
				$stored[ $route ] = array(
					'new_route'   => $info['new_route'],
					'total_count' => 0,
					'first_seen'  => \current_time( 'mysql' ),
					'last_seen'   => '',
				);
			}

			$stored[ $route ]['total_count'] += $info['count'];
			$stored[ $route ]['last_seen']    = \current_time( 'mysql' );
		}

		\update_option( 'apollo_rest_deprecation_stats', $stored, false );
	}

	/**
	 * Admin deprecation notice
	 *
	 * @return void
	 */
	public function admin_deprecation_notice(): void {
		// Only for admins.
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only on relevant pages.
		$screen = \get_current_screen();
		if ( ! $screen || ! \in_array( $screen->id, array( 'tools_page_apollo-rest-migration', 'plugins' ), true ) ) {
			return;
		}

		$stats = \get_option( 'apollo_rest_deprecation_stats', array() );

		if ( empty( $stats ) ) {
			return;
		}

		$total_calls = \array_sum( \array_column( $stats, 'total_count' ) );
		$routes_used = \count( $stats );

		if ( $total_calls > 0 ) {
			\printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				\sprintf(
					/* translators: 1: Number of deprecated routes, 2: Number of calls */
					\esc_html__( 'Apollo REST: %1$d deprecated route(s) have been called %2$d times. Please update your integrations to use the unified apollo/v1 namespace.', 'apollo-core' ),
					$routes_used,
					$total_calls
				)
			);
		}
	}

	/**
	 * Get deprecation statistics
	 *
	 * @return array
	 */
	public function get_deprecation_stats(): array {
		return \get_option( 'apollo_rest_deprecation_stats', array() );
	}

	/**
	 * Clear deprecation statistics
	 *
	 * @return bool
	 */
	public function clear_deprecation_stats(): bool {
		return \delete_option( 'apollo_rest_deprecation_stats' );
	}

	/**
	 * Get legacy mappings
	 *
	 * @return array
	 */
	public function get_legacy_mappings(): array {
		return $this->legacy_mappings;
	}

	/**
	 * Add custom legacy mapping
	 *
	 * @param string $legacy_route Legacy route.
	 * @param string $new_route    New route.
	 * @return void
	 */
	public function add_mapping( string $legacy_route, string $new_route ): void {
		$this->legacy_mappings[ $legacy_route ] = $new_route;
	}

	/**
	 * Get redirect count
	 *
	 * @return int
	 */
	public function get_redirect_count(): int {
		return $this->redirect_count;
	}

	/**
	 * Check if route is deprecated
	 *
	 * @param string $route Route to check.
	 * @return bool
	 */
	public function is_deprecated_route( string $route ): bool {
		return isset( $this->legacy_mappings[ $route ] );
	}

	/**
	 * Get replacement for deprecated route
	 *
	 * @param string $route Deprecated route.
	 * @return string|null New route or null.
	 */
	public function get_replacement( string $route ): ?string {
		return $this->legacy_mappings[ $route ] ?? null;
	}
}

// Initialize compatibility layer.
Apollo_REST_Compat::get_instance();
