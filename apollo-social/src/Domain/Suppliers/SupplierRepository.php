<?php
/**
 * Supplier Repository Interface
 *
 * This interface defines the methods for interacting with supplier data.
 *
 * @package Apollo_Social
 */

namespace Apollo\Domain\Suppliers;

/**
 * Interface SupplierRepository
 *
 * @package Apollo\Domain\Suppliers
 */
interface SupplierRepository {

	/**
	 * Find a supplier by ID.
	 *
	 * @param int $supplier_id The ID of the supplier.
	 * @return Supplier|null The supplier object or null if not found.
	 * @throws \InvalidArgumentException If the supplier ID is invalid.
	 */
	public function find( int $supplier_id ): ?Supplier;

	/**
	 * Add a new supplier.
	 *
	 * @param Supplier $supplier The supplier object to add.
	 * @return int The ID of the newly created supplier.
	 * @throws \Exception If there is an error during the creation process.
	 */
	public function add( Supplier $supplier ): int;

	/**
	 * Update an existing supplier.
	 *
	 * @param Supplier $supplier The supplier object with updated data.
	 * @return bool True on success, false on failure.
	 * @throws \Exception If there is an error during the update process.
	 */
	public function update( Supplier $supplier ): bool;

	/**
	 * Delete a supplier by ID.
	 *
	 * @param int $supplier_id The ID of the supplier to delete.
	 * @return bool True on success, false on failure.
	 * @throws \InvalidArgumentException If the supplier ID is invalid.
	 */
	public function delete( int $supplier_id ): bool;

	/**
	 * Get all suppliers.
	 *
	 * @return Supplier[] An array of supplier objects.
	 */
	public function get_all(): array;
}
