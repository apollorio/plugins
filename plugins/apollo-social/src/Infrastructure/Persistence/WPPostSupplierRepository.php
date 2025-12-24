<?php
/**
 * WPPostSupplierRepository Class
 *
 * This class implements the SupplierRepository interface using WordPress post types for data persistence.
 * It includes methods for CRUD operations on suppliers stored as custom post types.
 *
 * @package Apollo_Social
 */

namespace Apollo\Infrastructure\Persistence;

use Apollo\Domain\Suppliers\Supplier;
use Apollo\Domain\Suppliers\SupplierRepository;
use WP_Error;

class WPPostSupplierRepository implements SupplierRepository {
	/**
	 * Get all suppliers.
	 *
	 * @return Supplier[]|WP_Error Array of Supplier objects or WP_Error on failure.
	 */
	public function get_all_suppliers() {
		$args = array(
			'post_type'      => 'supplier',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return new WP_Error( 'no_suppliers', __( 'No suppliers found.', 'apollo-social' ) );
		}

		$suppliers = array();

		foreach ( $query->posts as $post ) {
			$suppliers[] = $this->map_post_to_supplier( $post );
		}

		return $suppliers;
	}

	/**
	 * Add a new supplier.
	 *
	 * @param Supplier $supplier The supplier to add.
	 * @return int|WP_Error The new supplier ID or WP_Error on failure.
	 */
	public function add_supplier( Supplier $supplier ) {
		$post_data = array(
			'post_title'   => $supplier->get_name(),
			'post_content' => $supplier->get_description(),
			'post_type'    => 'supplier',
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set custom fields for supplier details.
		update_post_meta( $post_id, 'contact_info', $supplier->get_contact_info() );

		return $post_id;
	}

	/**
	 * Get a supplier by ID.
	 *
	 * @param int $id The supplier ID.
	 * @return Supplier|WP_Error The supplier object or WP_Error on failure.
	 */
	public function get_supplier_by_id( $id ) {
		$post = get_post( $id );

		if ( ! $post || 'supplier' !== $post->post_type ) {
			return new WP_Error( 'supplier_not_found', __( 'Supplier not found.', 'apollo-social' ) );
		}

		return $this->map_post_to_supplier( $post );
	}

	/**
	 * Update a supplier.
	 *
	 * @param Supplier $supplier The supplier to update.
	 * @return bool|WP_Error True on success, or WP_Error on failure.
	 */
	public function update_supplier( Supplier $supplier ) {
		$post_data = array(
			'ID'          => $supplier->get_id(),
			'post_title'  => $supplier->get_name(),
			'post_content'=> $supplier->get_description(),
		);

		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update custom fields.
		update_post_meta( $supplier->get_id(), 'contact_info', $supplier->get_contact_info() );

		return true;
	}

	/**
	 * Delete a supplier.
	 *
	 * @param int $id The supplier ID.
	 * @return bool|WP_Error True on success, or WP_Error on failure.
	 */
	public function delete_supplier( $id ) {
		$result = wp_delete_post( $id, true );

		if ( ! $result ) {
			return new WP_Error( 'supplier_delete_failed', __( 'Failed to delete supplier.', 'apollo-social' ) );
		}

		return true;
	}

	/**
	 * Map a WP_Post object to a Supplier object.
	 *
	 * @param \WP_Post $post The post object.
	 * @return Supplier The mapped Supplier object.
	 */
	private function map_post_to_supplier( \WP_Post $post ) {
		$supplier = new Supplier();
		$supplier->set_id( $post->ID );
		$supplier->set_name( $post->post_title );
		$supplier->set_description( $post->post_content );
		$supplier->set_contact_info( get_post_meta( $post->ID, 'contact_info', true ) );

		return $supplier;
	}
}