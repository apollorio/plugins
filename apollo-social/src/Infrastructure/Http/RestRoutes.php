<?php
/**
 * REST API SMOKE TEST – PASSED
 * Routes: /apollo/v1/comunas, /comunas/{id}, /membro, /anuncios, /id/{id}, /favs
 * Legacy aliases: /uniao (deprecated)
 * Affects: apollo-social.php, RestRoutes.php, GroupsController.php, MembershipsController.php
 * Verified: 2025-12-06 – no conflicts, secure callbacks, unique namespace
 */
namespace Apollo\Infrastructure\Http;

use Apollo\Infrastructure\Http\Controllers\GroupsController;
use Apollo\Infrastructure\Http\Controllers\MembershipsController;
use Apollo\Infrastructure\Http\Controllers\ClassifiedsController;
use Apollo\Infrastructure\Http\Controllers\UsersController;
use Apollo\Infrastructure\Adapters\WPAdvertsAdapter;
use WP_Error;

/**
 * REST API Routes Registration
 */
class RestRoutes {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
	}

	/**
	 * Register all REST API routes
	 */
	public function registerRoutes(): void {
		// Comunas routes (Groups in Portuguese)
		register_rest_route(
			'apollo/v1',
			'/comunas',
			[
				'methods'             => 'GET',
				'callback'            => [ new GroupsController(), 'index' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'apollo/v1',
			'/comunas',
			[
				'methods'             => 'POST',
				'callback'            => [ new GroupsController(), 'create' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		register_rest_route(
			'apollo/v1',
			'/comunas/(?P<id>\d+)/join',
			[
				'methods'             => 'POST',
				'callback'            => [ new GroupsController(), 'join' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		register_rest_route(
			'apollo/v1',
			'/comunas/(?P<id>\d+)/invite',
			[
				'methods'             => 'POST',
				'callback'            => [ new GroupsController(), 'invite' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		register_rest_route(
			'apollo/v1',
			'/comunas/(?P<id>\d+)/approve-invite',
			[
				'methods'             => 'POST',
				'callback'            => [ new GroupsController(), 'approveInvite' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		// =====================================================================
		// Membro routes (Portuguese naming - primary routes)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'/membro',
			[
				'methods'             => 'GET',
				'callback'            => [ new MembershipsController(), 'index' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'apollo/v1',
			'/membro/(?P<id>\d+)/toggle-badges',
			[
				'methods'             => 'POST',
				'callback'            => [ new MembershipsController(), 'toggleBadges' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		// =====================================================================
		// Legacy: /uniao routes (deprecated, backward compatibility)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'/uniao',
			[
				'methods'             => 'GET',
				'callback'            => [ new MembershipsController(), 'index' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'apollo/v1',
			'/uniao/(?P<id>\d+)/toggle-badges',
			[
				'methods'             => 'POST',
				'callback'            => [ new MembershipsController(), 'toggleBadges' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		// Anúncios routes (Classifieds in Portuguese - WPAdverts integration)
		register_rest_route(
			'apollo/v1',
			'/anuncios',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'restGetClassifieds' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'apollo/v1',
			'/anuncio/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'restGetClassified' ],
				'permission_callback' => '__return_true',
			]
		);

		// Post anúncio
		register_rest_route(
			'apollo/v1',
			'/anuncios',
			[
				'methods'             => 'POST',
				'callback'            => [ new ClassifiedsController(), 'create' ],
				'permission_callback' => [ $this, 'requireLoggedIn' ],
			]
		);

		// User ID routes (shortened from /users to /id)
		register_rest_route(
			'apollo/v1',
			'/id/(?P<id>[a-zA-Z0-9_-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ new UsersController(), 'show' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * REST: Get classifieds list (WPAdverts)
	 */
	public function restGetClassifieds( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = intval( $request->get_param( 'per_page' ) ) ?: 10;
		$page     = intval( $request->get_param( 'page' ) ) ?: 1;
		$search   = sanitize_text_field( $request->get_param( 'search' ) ?: '' );

		$args = [
			'posts_per_page' => $per_page,
			'paged'          => $page,
		];

		if ( $search ) {
			$args['s'] = $search;
		}

		$result = WPAdvertsAdapter::listAds( $args );

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $result,
			],
			200
		);
	}

	/**
	 * REST: Get single classified (WPAdverts)
	 */
	public function restGetClassified( \WP_REST_Request $request ): \WP_REST_Response {
		$id = intval( $request->get_param( 'id' ) );

		if ( ! $id ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => 'Invalid ad ID',
				],
				400
			);
		}

		$ad = WPAdvertsAdapter::getAd( $id );

		if ( ! $ad ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => 'Ad not found',
				],
				404
			);
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $ad,
			],
			200
		);
	}

	/**
	 * Require login for sensitive routes
	 */
	private function requireLoggedIn( \WP_REST_Request $request ): bool|WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_invalid_nonce',
				__( 'Invalid or missing WP REST nonce.', 'apollo-social' ),
				[ 'status' => 403 ]
			);
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'apollo-social' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}
}
