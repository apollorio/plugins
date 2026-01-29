<?php
/**
 * Supplier Service
 *
 * Business logic layer for supplier operations.
 * Handles access control, filtering, view model preparation, and policy enforcement.
 *
 * @package Apollo\Domain\Suppliers
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Domain\Suppliers;

/**
 * Class SupplierService
 *
 * @since 1.0.0
 */
class SupplierService {

	/**
	 * Repository instance.
	 *
	 * @var SupplierRepositoryInterface
	 */
	private SupplierRepositoryInterface $repository;

	/**
	 * Cena-Rio roles allowed to access suppliers.
	 *
	 * @var array<string>
	 */
	private array $cena_rio_roles;

	/**
	 * Constructor.
	 *
	 * @param SupplierRepositoryInterface $repository Repository instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct( SupplierRepositoryInterface $repository ) {
		$this->repository     = $repository;
		$this->cena_rio_roles = $this->get_cena_rio_roles();
	}

	/**
	 * Get allowed Cena-Rio roles.
	 *
	 * @return array<string>
	 *
	 * @since 1.0.0
	 */
	private function get_cena_rio_roles(): array {
		$default_roles = array(
			'administrator',
			'cena_rio_producer',
			'cena_rio_member',
			'cena_rio_professional',
			'event_manager',
			'produtor',
			'dj',
			'artista',
			'promoter',
		);

		/**
		 * Filter the roles allowed to access Cena-Rio suppliers.
		 *
		 * @param array<string> $roles Default allowed roles.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'apollo_fornece_allowed_roles', $default_roles );
	}

	/**
	 * Check if current user can access suppliers.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function user_can_access(): bool {
		if ( ! \is_user_logged_in() ) {
			return false;
		}

		$user = \wp_get_current_user();

		// Administrators always have access.
		if ( \user_can( $user, 'manage_options' ) ) {
			return true;
		}

		// Check for Cena-Rio membership meta.
		$is_cena_rio_member = \get_user_meta( $user->ID, '_apollo_cena_rio_member', true );
		if ( '1' === $is_cena_rio_member || true === $is_cena_rio_member ) {
			return true;
		}

		// Check roles.
		foreach ( $this->cena_rio_roles as $role ) {
			if ( \in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if current user can manage suppliers.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function user_can_manage(): bool {
		if ( ! \is_user_logged_in() ) {
			return false;
		}

		$user = \wp_get_current_user();

		// Check for specific capability.
		if ( \user_can( $user, 'manage_apollo_suppliers' ) ) {
			return true;
		}

		// Administrators always can manage.
		if ( \user_can( $user, 'manage_options' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all suppliers as view models.
	 *
	 * @param array<string, mixed> $filters Filter parameters.
	 *
	 * @return array<array<string, mixed>> Array of supplier view models.
	 *
	 * @since 1.0.0
	 */
	public function get_suppliers( array $filters = array() ): array {
		$args      = $this->normalize_filters( $filters );
		$suppliers = $this->repository->find_all( $args );

		return \array_map(
			function ( Supplier $supplier ) {
				return $supplier->to_array();
			},
			$suppliers
		);
	}

	/**
	 * Get single supplier as view model.
	 *
	 * @param int $supplier_id Supplier ID.
	 *
	 * @return array<string, mixed>|null Supplier view model or null.
	 *
	 * @since 1.0.0
	 */
	public function get_supplier( int $supplier_id ): ?array {
		$supplier = $this->repository->find( $supplier_id );

		if ( null === $supplier ) {
			return null;
		}

		return $supplier->to_array();
	}

	/**
	 * Create a new supplier.
	 *
	 * @param array<string, mixed> $data Supplier data.
	 *
	 * @return int|false New supplier ID or false on failure.
	 *
	 * @since 1.0.0
	 */
	public function create_supplier( array $data ) {
		// Validate required fields.
		if ( empty( $data['name'] ) ) {
			return false;
		}

		// Set default status based on user capability.
		if ( ! isset( $data['status'] ) ) {
			$data['status'] = $this->user_can_manage() ? 'publish' : 'pending';
		}

		return $this->repository->create( $data );
	}

	/**
	 * Update supplier.
	 *
	 * @param int                  $supplier_id Supplier ID.
	 * @param array<string, mixed> $data        Updated data.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function update_supplier( int $supplier_id, array $data ): bool {
		return $this->repository->update( $supplier_id, $data );
	}

	/**
	 * Delete supplier.
	 *
	 * @param int $supplier_id Supplier ID.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function delete_supplier( int $supplier_id ): bool {
		return $this->repository->delete( $supplier_id );
	}

	/**
	 * Get filter options with counts for UI.
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @since 1.0.0
	 */
	public function get_filter_options(): array {
		$options = $this->repository->get_filter_options();

		// Add pt-BR labels.
		return $this->localize_filter_options( $options );
	}

	/**
	 * Get total suppliers count.
	 *
	 * @param array<string, mixed> $filters Optional filters.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function get_count( array $filters = array() ): int {
		$args = $this->normalize_filters( $filters );
		return $this->repository->count( $args );
	}

	/**
	 * Normalize filter parameters.
	 *
	 * @param array<string, mixed> $filters Raw filters.
	 *
	 * @return array<string, mixed> Normalized query arguments.
	 *
	 * @since 1.0.0
	 */
	private function normalize_filters( array $filters ): array {
		$args = array(
			'post_status' => 'publish',
		);

		if ( ! empty( $filters['category'] ) ) {
			$args['category'] = sanitize_key( $filters['category'] );
		}

		if ( ! empty( $filters['region'] ) ) {
			$args['region'] = sanitize_key( $filters['region'] );
		}

		if ( ! empty( $filters['neighborhood'] ) ) {
			$args['neighborhood'] = sanitize_text_field( $filters['neighborhood'] );
		}

		if ( ! empty( $filters['event_type'] ) ) {
			$args['event_type'] = sanitize_key( $filters['event_type'] );
		}

		if ( ! empty( $filters['price_tier'] ) ) {
			$args['price_tier'] = absint( $filters['price_tier'] );
		}

		if ( ! empty( $filters['supplier_type'] ) ) {
			$args['supplier_type'] = sanitize_key( $filters['supplier_type'] );
		}

		if ( ! empty( $filters['mode'] ) ) {
			$args['mode'] = sanitize_key( $filters['mode'] );
		}

		if ( ! empty( $filters['badge'] ) ) {
			$args['badge'] = sanitize_key( $filters['badge'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$args['search'] = sanitize_text_field( $filters['search'] );
		}

		if ( ! empty( $filters['orderby'] ) ) {
			$args['orderby'] = sanitize_key( $filters['orderby'] );
		}

		if ( ! empty( $filters['order'] ) ) {
			$args['order'] = strtoupper( $filters['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		}

		if ( ! empty( $filters['per_page'] ) ) {
			$args['posts_per_page'] = min( 100, absint( $filters['per_page'] ) );
		}

		if ( ! empty( $filters['page'] ) ) {
			$args['paged'] = absint( $filters['page'] );
		}

		return $args;
	}

	/**
	 * Add pt-BR labels to filter options.
	 *
	 * @param array<string, array<string, mixed>> $options Raw options.
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @since 1.0.0
	 */
	private function localize_filter_options( array $options ): array {
		$labels = array(
			'categories'     => array(
				'label' => 'Categorias',
			),
			'regions'        => array(
				'label'   => 'Regiões',
				'options' => array(
					'regiao-dos-lagos'     => 'Região dos Lagos',
					'regiao-metropolitana' => 'Região Metropolitana',
					'zona-norte'           => 'Zona Norte',
					'zona-sul'             => 'Zona Sul',
					'centro'               => 'Centro',
					'zona-oeste'           => 'Zona Oeste',
				),
			),
			'event_types'    => array(
				'label'   => 'Tipo de Evento',
				'options' => array(
					'independente'    => 'Evento independente',
					'nightclub'       => 'Nightclub',
					'festival-indoor' => 'Festival indoor',
					'festival-open'   => 'Festival Open Air',
				),
			),
			'supplier_types' => array(
				'label'   => 'Tipo',
				'options' => array(
					'servico' => 'Serviço',
					'produto' => 'Produto',
					'hibrido' => 'Híbrido',
				),
			),
			'modes'          => array(
				'label'   => 'Modalidades',
				'options' => array(
					'consignado'    => 'Consignado',
					'ecologico'     => 'Ecológico',
					'personalizado' => 'Personalizado',
				),
			),
			'badges'         => array(
				'label'   => 'Selos',
				'options' => array(
					'ecologico'     => 'Ecológico',
					'acessivel_pcd' => 'Acessível para PCD',
					'diversidade'   => 'Diversidade',
				),
			),
			'price_tiers'    => array(
				'label'   => 'Faixa de Preço',
				'options' => array(
					'1' => '$',
					'2' => '$$',
					'3' => '$$$',
				),
			),
		);

		foreach ( $labels as $key => $label_data ) {
			if ( isset( $options[ $key ] ) ) {
				$options[ $key ]['label'] = $label_data['label'];
				if ( isset( $label_data['options'] ) && isset( $options[ $key ]['options'] ) ) {
					foreach ( $options[ $key ]['options'] as &$option ) {
						if ( isset( $label_data['options'][ $option['value'] ] ) ) {
							$option['label'] = $label_data['options'][ $option['value'] ];
						}
					}
				}
			}
		}

		return $options;
	}

	/**
	 * Get category options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_category_labels(): array {
		return array(
			'locais'               => 'Locais',
			'bar-bebidas'          => 'Bar/Bebidas',
			'descartaveis'         => 'Descartáveis',
			'seguranca'            => 'Segurança',
			'equipes'              => 'Equipes',
			'educacao'             => 'Educação',
			'som-dj'               => 'Som/DJ',
			'iluminacao-efeitos'   => 'Iluminação/Efeitos',
			'decoracao-cenografia' => 'Decoração/Cenografia',
			'producao-organizacao' => 'Produção/Organização',
			'alimentos-catering'   => 'Alimentos/Catering',
			'midia-fotografia'     => 'Mídia/Fotografia',
		);
	}

	/**
	 * Get region options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_region_labels(): array {
		return array(
			'regiao-dos-lagos'     => 'Região dos Lagos',
			'regiao-metropolitana' => 'Região Metropolitana',
			'zona-norte'           => 'Zona Norte',
			'zona-sul'             => 'Zona Sul',
			'centro'               => 'Centro',
			'zona-oeste'           => 'Zona Oeste',
		);
	}

	/**
	 * Get event type options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_event_type_labels(): array {
		return array(
			'independente'    => 'Evento independente',
			'nightclub'       => 'Nightclub',
			'festival-indoor' => 'Festival indoor',
			'festival-open'   => 'Festival Open Air',
		);
	}

	/**
	 * Get supplier type options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_supplier_type_labels(): array {
		return array(
			'servico' => 'Serviço',
			'produto' => 'Produto',
			'hibrido' => 'Híbrido',
		);
	}

	/**
	 * Get mode options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_mode_labels(): array {
		return array(
			'consignado'    => 'Consignado',
			'ecologico'     => 'Ecológico',
			'personalizado' => 'Personalizado',
		);
	}

	/**
	 * Get badge options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_badge_labels(): array {
		return array(
			'ecologico'     => 'Ecológico',
			'acessivel_pcd' => 'Acessível para PCD',
			'diversidade'   => 'Diversidade',
		);
	}

	/**
	 * Get neighborhood options with pt-BR labels.
	 *
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function get_neighborhood_labels(): array {
		return array(
			'copacabana'      => 'Copacabana',
			'ipanema'         => 'Ipanema',
			'leblon'          => 'Leblon',
			'botafogo'        => 'Botafogo',
			'flamengo'        => 'Flamengo',
			'lapa'            => 'Lapa',
			'centro'          => 'Centro',
			'barra'           => 'Barra da Tijuca',
			'tijuca'          => 'Tijuca',
			'lagoa'           => 'Lagoa',
			'gavea'           => 'Gávea',
			'jardim-botanico' => 'Jardim Botânico',
		);
	}
}
