<?php
/**
 * Supplier Repository Interface
 *
 * Defines the contract for supplier data persistence operations.
 *
 * @package Apollo\Domain\Suppliers
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Domain\Suppliers;

/**
 * Interface SupplierRepositoryInterface
 *
 * @since 1.0.0
 */
interface SupplierRepositoryInterface {

	/**
	 * Find a supplier by ID.
	 *
	 * @param int $supplier_id The ID of the supplier.
	 *
	 * @return Supplier|null The supplier object or null if not found.
	 *
	 * @since 1.0.0
	 */
	public function find( int $supplier_id ): ?Supplier;

	/**
	 * Get all suppliers.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return array<Supplier> Array of Supplier objects.
	 *
	 * @since 1.0.0
	 */
	public function find_all( array $args = array() ): array;

	/**
	 * Get suppliers by category.
	 *
	 * @param string $category Category slug.
	 *
	 * @return array<Supplier> Array of Supplier objects.
	 *
	 * @since 1.0.0
	 */
	public function find_by_category( string $category ): array;

	/**
	 * Get suppliers by region.
	 *
	 * @param string $region Region slug.
	 *
	 * @return array<Supplier> Array of Supplier objects.
	 *
	 * @since 1.0.0
	 */
	public function find_by_region( string $region ): array;

	/**
	 * Create a new supplier.
	 *
	 * @param array<string, mixed> $data Supplier data.
	 *
	 * @return int|false The new supplier ID or false on failure.
	 *
	 * @since 1.0.0
	 */
	public function create( array $data );

	/**
	 * Update an existing supplier.
	 *
	 * @param int                  $supplier_id The supplier ID.
	 * @param array<string, mixed> $data        Updated data.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function update( int $supplier_id, array $data ): bool;

	/**
	 * Delete a supplier.
	 *
	 * @param int $supplier_id The supplier ID.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function delete( int $supplier_id ): bool;

	/**
	 * Count suppliers.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return int Total count.
	 *
	 * @since 1.0.0
	 */
	public function count( array $args = array() ): int;

	/**
	 * Get filter options with counts.
	 *
	 * @return array<string, array<string, mixed>> Filter options grouped by type.
	 *
	 * @since 1.0.0
	 */
	public function get_filter_options(): array;
}
