<?php
/**
 * Suppliers Controller
 *
 * This class handles HTTP requests related to suppliers, including listing,
 * adding, and retrieving supplier information.
 *
 * @package Apollo_Social
 */

namespace Apollo\Presentation\Controllers;

use Apollo\Domain\Suppliers\SupplierService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class SuppliersController {
	/**
	 * @var SupplierService
	 */
	private $supplier_service;

	/**
	 * SuppliersController constructor.
	 *
	 * @param SupplierService $supplier_service The supplier service instance.
	 */
	public function __construct( SupplierService $supplier_service ) {
		$this->supplier_service = $supplier_service;
	}

	/**
	 * List all suppliers.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function list_suppliers( WP_REST_Request $request ) {
		$suppliers = $this->supplier_service->get_all_suppliers();

		return new WP_REST_Response( $suppliers, 200 );
	}

	/**
	 * Add a new supplier.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_supplier( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		// Validate and sanitize input data.
		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$contact_info = isset( $data['contact_info'] ) ? sanitize_text_field( $data['contact_info'] ) : '';

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_name', __( 'Supplier name is required.', 'apollo-social' ), array( 'status' => 400 ) );
		}

		$supplier_id = $this->supplier_service->add_supplier( $name, $contact_info );

		if ( is_wp_error( $supplier_id ) ) {
			return $supplier_id;
		}

		return new WP_REST_Response( array( 'id' => $supplier_id ), 201 );
	}

	/**
	 * Get a single supplier by ID.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_supplier( WP_REST_Request $request ) {
		$supplier_id = (int) $request['id'];
		$supplier = $this->supplier_service->get_supplier_by_id( $supplier_id );

		if ( is_wp_error( $supplier ) ) {
			return $supplier;
		}

		return new WP_REST_Response( $supplier, 200 );
	}
}

