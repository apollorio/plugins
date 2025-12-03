<?php
namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\AdEntity;
use Apollo\Domain\Entities\GroupEntity;
use Apollo\Domain\Classifieds\Policies\ClassifiedsPolicy;
use Apollo\Application\Classifieds\BindSeason;

/**
 * Classifieds REST Controller
 */
class ClassifiedsController extends BaseController {

	private $classifiedsPolicy;
	private $bindSeason;

	public function __construct() {
		$this->classifiedsPolicy = new ClassifiedsPolicy();
		$this->bindSeason        = new BindSeason();
	}

	/**
	 * GET /apollo/v1/classifieds
	 */
	public function index(): void {
		$params = $this->sanitizeParams( $_GET );

		$season = $params['season'] ?? '';
		$search = $params['search'] ?? '';

		$classifieds = $this->getClassifiedsData( $season, $search );

		// Apply view permissions
		$user         = $this->getCurrentUser();
		$filtered_ads = array();

		foreach ( $classifieds as $ad_data ) {
			$ad = new AdEntity( $ad_data );

			if ( $this->classifiedsPolicy->canView( $ad, $user ) ) {
				$filtered_ads[] = $ad_data;
			}
		}

		$this->success( $filtered_ads );
	}

	/**
	 * POST /apollo/v1/classifieds
	 */
	public function create(): void {
		if ( ! $this->validateNonce() ) {
			$this->authError( 'Invalid nonce' );
		}

		$user = $this->getCurrentUser();
		if ( ! $user || ! $user->isLoggedIn() ) {
			$this->authError();
		}

		$params = $this->sanitizeParams( $_POST );

		// Validate required fields
		if ( empty( $params['title'] ) ) {
			$this->validationError( 'Title is required' );
		}

		if ( empty( $params['body'] ) ) {
			$this->validationError( 'Body is required' );
		}

		// Get context group if specified
		$contextGroup = null;
		if ( ! empty( $params['group_id'] ) ) {
			$contextGroup = $this->getGroupById( intval( $params['group_id'] ) );
		}

		// Check creation permissions
		if ( ! $this->classifiedsPolicy->canCreate( $user, $contextGroup ) ) {
			$this->permissionError( 'You cannot create classifieds in this context' );
		}

		// Validate season binding if in season context
		try {
			$this->bindSeason->validate( $params, $contextGroup );
		} catch ( \InvalidArgumentException $e ) {
			$this->validationError( $e->getMessage() );
		}

		// Apply season binding
		$params = $this->bindSeason->apply( $params, $contextGroup );

		// Create classified (mock implementation)
		$ad_data = array(
			'id'          => rand( 1000, 9999 ),
			'title'       => $params['title'],
			'slug'        => $this->sanitizeTitle( $params['title'] ),
			'description' => $params['body'],
			'price'       => $params['price'] ?? '',
			'category'    => $params['category'] ?? 'Geral',
			'season_slug' => $params['season_slug'] ?? null,
			'group_id'    => $params['group_id'] ?? null,
			'author_id'   => $user->id,
			'status'      => 'active',
			'created_at'  => date( 'Y-m-d H:i:s' ),
		);

		$this->success( $ad_data, 'Classified created successfully' );
	}

	/**
	 * Sanitize title for slug
	 */
	private function sanitizeTitle( string $title ): string {
		return strtolower( trim( preg_replace( '/[^a-zA-Z0-9]+/', '-', $title ), '-' ) );
	}

	/**
	 * Get classifieds data (mock implementation)
	 */
	private function getClassifiedsData( string $season = '', string $search = '' ): array {
		$ads = array(
			array(
				'id'          => 1,
				'title'       => 'Casa para Venda',
				'slug'        => 'casa-venda-centro',
				'description' => 'Casa excelente no centro',
				'price'       => 'R$ 350.000',
				'category'    => 'Imóveis',
				'season_slug' => 'verao-2025',
				'author_id'   => 1,
				'status'      => 'active',
			),
			array(
				'id'          => 2,
				'title'       => 'Carro Usado',
				'slug'        => 'carro-usado-2020',
				'description' => 'Carro em ótimo estado',
				'price'       => 'R$ 45.000',
				'category'    => 'Veículos',
				'season_slug' => null,
				'author_id'   => 2,
				'status'      => 'active',
			),
		);

		// Apply filters
		if ( $season ) {
			$ads = array_filter(
				$ads,
				function ( $ad ) use ( $season ) {
					return $ad['season_slug'] === $season;
				}
			);
		}

		if ( $search ) {
			$ads = array_filter(
				$ads,
				function ( $ad ) use ( $search ) {
					return stripos( $ad['title'], $search ) !== false ||
						stripos( $ad['description'], $search ) !== false;
				}
			);
		}

		return array_values( $ads );
	}

	/**
	 * Get group by ID (mock implementation)
	 */
	private function getGroupById( int $id ): ?GroupEntity {
		// Mock group data
		if ( $id === 3 ) {
			return new GroupEntity(
				array(
					'id'          => 3,
					'title'       => 'Verão 2025',
					'type'        => 'season',
					'season_slug' => 'verao-2025',
				)
			);
		}

		return null;
	}
}
