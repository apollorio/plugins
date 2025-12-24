<?php
/**
 * Supplier Service Class
 *
 * This class implements business logic related to suppliers.
 * It uses the SupplierRepository to perform operations like
 * validating supplier data and managing supplier records.
 *
 * @package Apollo_Social
 */

namespace Apollo\Social\Domain\Suppliers;

use Apollo\Social\Domain\Suppliers\SupplierRepository;
use InvalidArgumentException;

/**
 * Class SupplierService
 *
 * @since 1.0.0
 */
class SupplierService {
	/**
	 * @var SupplierRepository
	 */
	private $supplier_repository;

	/**
	 * SupplierService constructor.
	 *
	 * @param SupplierRepository $supplier_repository The supplier repository instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct( SupplierRepository $supplier_repository ) {
		$this->supplier_repository = $supplier_repository;
	}

	/**
	 * Add a new supplier.
	 *
	 * @param array $supplier_data The supplier data.
	 *
	 * @return int The ID of the newly created supplier.
	 *
	 * @throws InvalidArgumentException If the supplier data is invalid.
	 *
	 * @since 1.0.0
	 */
	public function add_supplier( array $supplier_data ) {
		$this->validate_supplier_data( $supplier_data );

		return $this->supplier_repository->add( $supplier_data );
	}

	/**
	 * Validate supplier data.
	 *
	 * @param array $supplier_data The supplier data to validate.
	 *
	 * @throws InvalidArgumentException If the supplier data is invalid.
	 *
	 * @since 1.0.0
	 */
	private function validate_supplier_data( array $supplier_data ) {
		if ( empty( $supplier_data['name'] ) ) {
			throw new InvalidArgumentException( 'Supplier name is required.' );
		}

		if ( ! isset( $supplier_data['contact'] ) || ! filter_var( $supplier_data['contact'], FILTER_VALIDATE_EMAIL ) ) {
			throw new InvalidArgumentException( 'A valid contact email is required.' );
		}
	}

	/**
	 * Get all suppliers.
	 *
	 * @return Supplier[] An array of suppliers.
	 *
	 * @since 1.0.0
	 */
	public function get_all_suppliers() {
		return $this->supplier_repository->find_all();
	}

	/**
	 * Get a supplier by ID.
	 *
	 * @param int $supplier_id The supplier ID.
	 *
	 * @return Supplier|null The supplier object or null if not found.
	 *
	 * @since 1.0.0
	 */
	public function get_supplier_by_id( int $supplier_id ) {
		return $this->supplier_repository->find( $supplier_id );
	}
}
