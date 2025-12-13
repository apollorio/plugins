<?php
/**
 * REST Controller for Apollo Builder.
 *
 * Handles layout, background, sticker, and asset endpoints.
 *
 * @package Apollo\Modules\Builder\Http
 * @since   1.0.0
 */

namespace Apollo\Modules\Builder\Http;

use Apollo\Modules\Builder\Assets\BackgroundRegistry;
use Apollo\Modules\Builder\Assets\StickerRegistry;
use Apollo\Modules\Builder\LayoutRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API controller for the Builder module.
 */
class BuilderRestController {

	/**
	 * Layout repository instance.
	 *
	 * @var LayoutRepository
	 */
	private LayoutRepository $repository;

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'apollo-social/v1';

	/**
	 * Constructor.
	 *
	 * @param LayoutRepository $repository Layout repository instance.
	 */
	public function __construct( LayoutRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		// Layout CRUD.
		register_rest_route(
			self::NAMESPACE,
			'fabrica/layout',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'show' ],
					'permission_callback' => [ $this, 'canAccess' ],
					'args'                => [
						'user_id' => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
					],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'store' ],
					'permission_callback' => [ $this, 'canAccess' ],
					'args'                => [
						'layout'  => [
							'required' => true,
						],
						'user_id' => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Asset catalogs (backgrounds + stickers).
		register_rest_route(
			self::NAMESPACE,
			'fabrica/assets',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getAssets' ],
				'permission_callback' => [ $this, 'canAccess' ],
			]
		);

		// Background selection.
		register_rest_route(
			self::NAMESPACE,
			'fabrica/background',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'setBackground' ],
				'permission_callback' => [ $this, 'canAccess' ],
				'args'                => [
					'background_id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
					'user_id'       => [
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Sticker management.
		register_rest_route(
			self::NAMESPACE,
			'fabrica/adesivos',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'getStickers' ],
					'permission_callback' => [ $this, 'canAccess' ],
					'args'                => [
						'user_id' => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
					],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'addSticker' ],
					'permission_callback' => [ $this, 'canAccess' ],
					'args'                => [
						'asset'   => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						],
						'x'       => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'intval',
						],
						'y'       => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'intval',
						],
						'scale'   => [
							'type'              => 'number',
							'required'          => false,
							'default'           => 1.0,
							'sanitize_callback' => 'floatval',
						],
						'user_id' => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Single sticker update/delete.
		register_rest_route(
			self::NAMESPACE,
			'fabrica/adesivos/(?P<instance_id>[a-z0-9_-]+)',
			[
				[
					'methods'             => 'PATCH',
					'callback'            => [ $this, 'updateSticker' ],
					'permission_callback' => [ $this, 'canAccess' ],
					'args'                => [
						'instance_id' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						],
						'x'           => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'intval',
						],
						'y'           => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'intval',
						],
						'scale'       => [
							'type'              => 'number',
							'required'          => false,
							'sanitize_callback' => 'floatval',
						],
						'rotation'    => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'intval',
						],
						'z_index'     => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
						'user_id'     => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
					],
				],
				[
					'methods'             => 'DELETE',
					'callback'            => [ $this, 'deleteSticker' ],
					'permission_callback' => [ $this, 'canAccess' ],
					'args'                => [
						'instance_id' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						],
						'user_id'     => [
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Layout Endpoints
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * GET /builder/layout - Retrieve user layout.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function show( WP_REST_Request $request ) {
		$user_id = $this->resolveUserId( $request );
		$layout  = $this->repository->getLayout( $user_id );

		return rest_ensure_response(
			[
				'user_id' => $user_id,
				'layout'  => $layout,
			]
		);
	}

	/**
	 * POST /builder/layout - Save user layout.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function store( WP_REST_Request $request ) {
		$user_id = $this->resolveUserId( $request );
		$layout  = $request->get_param( 'layout' );

		if ( is_string( $layout ) ) {
			$decoded = json_decode( $layout, true );
			$layout  = is_array( $decoded ) ? $decoded : [];
		}

		if ( ! is_array( $layout ) ) {
			return new WP_Error(
				'apollo_invalid_layout',
				__( 'Estrutura de layout inválida.', 'apollo-social' ),
				[ 'status' => 400 ]
			);
		}

		$this->repository->saveLayout( $user_id, $layout );

		return rest_ensure_response(
			[
				'success' => true,
				'layout'  => $this->repository->getLayout( $user_id ),
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Asset Endpoints
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * GET /builder/assets - Retrieve available backgrounds and stickers.
	 *
	 * Returns only assets the current user has access to.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response with assets.
	 */
	public function getAssets( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();

		$backgrounds = BackgroundRegistry::get_available_for_user( $user_id );
		$stickers    = StickerRegistry::get_available_for_user( $user_id );

		return rest_ensure_response(
			[
				'backgrounds'           => array_values( $backgrounds ),
				'background_categories' => BackgroundRegistry::get_categories(),
				'stickers'              => array_values( $stickers ),
				'sticker_categories'    => StickerRegistry::get_categories(),
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Background Endpoints
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * POST /builder/background - Set user background.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function setBackground( WP_REST_Request $request ) {
		$user_id       = $this->resolveUserId( $request );
		$background_id = $request->get_param( 'background_id' );

		// Validate user has access to this background.
		$available = BackgroundRegistry::get_available_for_user( $user_id );
		if ( ! isset( $available[ $background_id ] ) ) {
			return new WP_Error(
				'apollo_background_not_available',
				__( 'Este background não está disponível para você.', 'apollo-social' ),
				[ 'status' => 403 ]
			);
		}

		$success = $this->repository->setBackground( $user_id, $background_id );

		if ( ! $success ) {
			return new WP_Error(
				'apollo_background_save_failed',
				__( 'Falha ao salvar background.', 'apollo-social' ),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response(
			[
				'success'    => true,
				'background' => $this->repository->getBackground( $user_id ),
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Sticker Endpoints
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * GET /builder/stickers - Get user's stickers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response with stickers.
	 */
	public function getStickers( WP_REST_Request $request ): WP_REST_Response {
		$user_id  = $this->resolveUserId( $request );
		$stickers = $this->repository->getStickers( $user_id );

		// Enrich with asset data.
		$enriched = array_map(
			function ( array $sticker ): array {
				$asset                 = StickerRegistry::get_by_id( $sticker['asset'] ?? '' );
				$sticker['asset_data'] = $asset;
				return $sticker;
			},
			$stickers
		);

		return rest_ensure_response(
			[
				'user_id'  => $user_id,
				'stickers' => $enriched,
			]
		);
	}

	/**
	 * POST /builder/stickers - Add a sticker to user layout.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function addSticker( WP_REST_Request $request ) {
		$user_id  = $this->resolveUserId( $request );
		$asset_id = $request->get_param( 'asset' );

		// Validate user has access to this sticker.
		$available = StickerRegistry::get_available_for_user( $user_id );
		if ( ! isset( $available[ $asset_id ] ) ) {
			return new WP_Error(
				'apollo_sticker_not_available',
				__( 'Este sticker não está disponível para você.', 'apollo-social' ),
				[ 'status' => 403 ]
			);
		}

		$sticker_data = [
			'asset' => $asset_id,
			'x'     => $request->get_param( 'x' ),
			'y'     => $request->get_param( 'y' ),
			'scale' => $request->get_param( 'scale' ),
		];

		$instance_id = $this->repository->addSticker( $user_id, $sticker_data );

		if ( empty( $instance_id ) ) {
			return new WP_Error(
				'apollo_sticker_add_failed',
				__( 'Falha ao adicionar sticker.', 'apollo-social' ),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response(
			[
				'success'     => true,
				'instance_id' => $instance_id,
				'stickers'    => $this->repository->getStickers( $user_id ),
			]
		);
	}

	/**
	 * PATCH /builder/stickers/{instance_id} - Update sticker position/properties.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function updateSticker( WP_REST_Request $request ) {
		$user_id     = $this->resolveUserId( $request );
		$instance_id = $request->get_param( 'instance_id' );

		$updates = array_filter(
			[
				'x'        => $request->get_param( 'x' ),
				'y'        => $request->get_param( 'y' ),
				'scale'    => $request->get_param( 'scale' ),
				'rotation' => $request->get_param( 'rotation' ),
				'z_index'  => $request->get_param( 'z_index' ),
			],
			function ( $value ): bool {
				return null !== $value;
			}
		);

		if ( empty( $updates ) ) {
			return new WP_Error(
				'apollo_sticker_no_updates',
				__( 'Nenhuma atualização fornecida.', 'apollo-social' ),
				[ 'status' => 400 ]
			);
		}

		$success = $this->repository->updateSticker( $user_id, $instance_id, $updates );

		if ( ! $success ) {
			return new WP_Error(
				'apollo_sticker_update_failed',
				__( 'Sticker não encontrado ou falha ao atualizar.', 'apollo-social' ),
				[ 'status' => 404 ]
			);
		}

		return rest_ensure_response(
			[
				'success'  => true,
				'stickers' => $this->repository->getStickers( $user_id ),
			]
		);
	}

	/**
	 * DELETE /builder/stickers/{instance_id} - Remove a sticker.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function deleteSticker( WP_REST_Request $request ) {
		$user_id     = $this->resolveUserId( $request );
		$instance_id = $request->get_param( 'instance_id' );

		$success = $this->repository->removeSticker( $user_id, $instance_id );

		if ( ! $success ) {
			return new WP_Error(
				'apollo_sticker_delete_failed',
				__( 'Sticker não encontrado.', 'apollo-social' ),
				[ 'status' => 404 ]
			);
		}

		return rest_ensure_response(
			[
				'success'  => true,
				'stickers' => $this->repository->getStickers( $user_id ),
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Check if current user can access builder.
	 *
	 * @return bool True if user is logged in.
	 */
	public function canAccess(): bool {
		return is_user_logged_in();
	}

	/**
	 * Resolve user ID from request.
	 *
	 * Admins can specify a user_id, regular users get their own ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return int Resolved user ID.
	 */
	private function resolveUserId( WP_REST_Request $request ): int {
		$user_id = absint( $request->get_param( 'user_id' ) );

		if ( $user_id && current_user_can( 'edit_users' ) ) {
			return $user_id;
		}

		return get_current_user_id();
	}
}
