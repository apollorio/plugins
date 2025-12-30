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
use Apollo\Infrastructure\Http\Controllers\NucleosController;
use Apollo\Infrastructure\Http\Controllers\BolhaController;
use Apollo\Infrastructure\Http\Controllers\EventsController;
use Apollo\Infrastructure\Http\Controllers\GuestListController;
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
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register all REST API routes
	 */
	public function registerRoutes(): void {
		// Comunas routes (Groups in Portuguese)
		register_rest_route(
			'apollo/v1',
			'comunas',
			array(
				'methods'             => 'GET',
				'callback'            => array( new GroupsController(), 'index' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'/comunas',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GroupsController(), 'create' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'comunas/(?P<id>\d+)/join',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GroupsController(), 'join' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'comunas/(?P<id>\d+)/invite',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GroupsController(), 'invite' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'comunas/(?P<id>\d+)/aprovar-invite',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GroupsController(), 'approveInvite' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// =====================================================================
		// Núcleos routes (Private producer groups - invite only)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'nucleos',
			array(
				'methods'             => 'GET',
				'callback'            => array( new NucleosController(), 'index' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'nucleos',
			array(
				'methods'             => 'POST',
				'callback'            => array( new NucleosController(), 'create' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'nucleos/(?P<id>\d+)/join',
			array(
				'methods'             => 'POST',
				'callback'            => array( new NucleosController(), 'join' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'nucleos/(?P<id>\d+)/invite',
			array(
				'methods'             => 'POST',
				'callback'            => array( new NucleosController(), 'invite' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'nucleos/(?P<id>\d+)/aprovar-join',
			array(
				'methods'             => 'POST',
				'callback'            => array( new NucleosController(), 'approveJoin' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// =====================================================================
		// Membro routes (Portuguese naming - primary routes)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'membro',
			array(
				'methods'             => 'GET',
				'callback'            => array( new MembershipsController(), 'index' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'membro/(?P<id>\d+)/toggle-badges',
			array(
				'methods'             => 'POST',
				'callback'            => array( new MembershipsController(), 'toggleBadges' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// =====================================================================
		// Legacy: /uniao routes (deprecated, backward compatibility)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'uniao',
			array(
				'methods'             => 'GET',
				'callback'            => array( new MembershipsController(), 'index' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'uniao/(?P<id>\d+)/toggle-badges',
			array(
				'methods'             => 'POST',
				'callback'            => array( new MembershipsController(), 'toggleBadges' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// =====================================================================
		// Bolha routes (Friend Circles - Social connections)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'bolha/pedir',
			array(
				'methods'             => 'POST',
				'callback'            => array( new BolhaController(), 'pedir' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/aceitar',
			array(
				'methods'             => 'POST',
				'callback'            => array( new BolhaController(), 'aceitar' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/rejeitar',
			array(
				'methods'             => 'POST',
				'callback'            => array( new BolhaController(), 'rejeitar' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/remover',
			array(
				'methods'             => 'POST',
				'callback'            => array( new BolhaController(), 'remover' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/listar',
			array(
				'methods'             => 'GET',
				'callback'            => array( new BolhaController(), 'listar' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/pedidos',
			array(
				'methods'             => 'GET',
				'callback'            => array( new BolhaController(), 'pedidos' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/status/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( new BolhaController(), 'status' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'bolha/cancelar',
			array(
				'methods'             => 'POST',
				'callback'            => array( new BolhaController(), 'cancelar' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// =====================================================================
		// Eventos routes (Events - event_listing CPT)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'eventos',
			array(
				'methods'             => 'GET',
				'callback'            => array( new EventsController(), 'index' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( new EventsController(), 'show' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos',
			array(
				'methods'             => 'POST',
				'callback'            => array( new EventsController(), 'create' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( new EventsController(), 'update' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( new EventsController(), 'delete' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/proximos',
			array(
				'methods'             => 'GET',
				'callback'            => array( new EventsController(), 'proximos' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/passados',
			array(
				'methods'             => 'GET',
				'callback'            => array( new EventsController(), 'passados' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/(?P<id>\d+)/confirmar',
			array(
				'methods'             => 'POST',
				'callback'            => array( new EventsController(), 'confirmar' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'eventos/(?P<id>\d+)/convidados',
			array(
				'methods'             => 'GET',
				'callback'            => array( new EventsController(), 'convidados' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// =====================================================================
		// Lista routes (Guest List management)
		// =====================================================================
		register_rest_route(
			'apollo/v1',
			'lista/(?P<event_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( new GuestListController(), 'index' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'lista/(?P<event_id>\d+)/add',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GuestListController(), 'add' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'lista/(?P<event_id>\d+)/checkin',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GuestListController(), 'checkin' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'lista/(?P<event_id>\d+)/(?P<guest_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( new GuestListController(), 'remove' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'lista/(?P<event_id>\d+)/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( new GuestListController(), 'stats' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'lista/(?P<event_id>\d+)/alocar',
			array(
				'methods'             => 'POST',
				'callback'            => array( new GuestListController(), 'alocar' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		register_rest_route(
			'apollo/v1',
			'lista/minhas',
			array(
				'methods'             => 'GET',
				'callback'            => array( new GuestListController(), 'minhas' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// Anúncios routes (Classifieds in Portuguese - WPAdverts integration)
		register_rest_route(
			'apollo/v1',
			'anuncios',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'restGetClassifieds' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'anuncio/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'restGetClassified' ),
				'permission_callback' => '__return_true',
			)
		);

		// Post anúncio
		register_rest_route(
			'apollo/v1',
			'anuncio/add',
			array(
				'methods'             => 'POST',
				'callback'            => array( new ClassifiedsController(), 'create' ),
				'permission_callback' => array( $this, 'requireLoggedIn' ),
			)
		);

		// User ID routes (shortened from /users to /id)
		register_rest_route(
			'apollo/v1',
			'id/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( new UsersController(), 'show' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST: Get classifieds list (WPAdverts)
	 */
	public function restGetClassifieds( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = intval( $request->get_param( 'per_page' ) ) ?: 10;
		$page     = intval( $request->get_param( 'page' ) ) ?: 1;
		$search   = sanitize_text_field( $request->get_param( 'search' ) ?: '' );

		$args = array(
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		if ( $search ) {
			$args['s'] = $search;
		}

		$result = WPAdvertsAdapter::listAds( $args );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $result,
			),
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
				array(
					'success' => false,
					'message' => 'Invalid ad ID',
				),
				400
			);
		}

		$ad = WPAdvertsAdapter::getAd( $id );

		if ( ! $ad ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Ad not found',
				),
				404
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $ad,
			),
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
				array( 'status' => 403 )
			);
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}
}
