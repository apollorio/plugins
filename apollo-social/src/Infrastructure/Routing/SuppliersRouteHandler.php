<?php
/**
 * Suppliers Route Handler
 *
 * This file handles routing for the suppliers REST API.
 * It defines endpoints such as /fornece/, /fornece/add/, and /fornece/{id}
 * and maps them to appropriate controller methods.
 *
 * @package Apollo_Social
 */

namespace Apollo\Infrastructure\Routing;

use Apollo\Presentation\Controllers\SuppliersController;
use WP_REST_Request;
use WP_REST_Response;

class SuppliersRouteHandler {

	/**
	 * Register the suppliers routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'fornece', '/', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_suppliers' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'fornece', '/add/', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'add_supplier' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		register_rest_route( 'fornece', '/{id}', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_supplier' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get all suppliers.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_suppliers( WP_REST_Request $request ) {
		$suppliers_controller = new SuppliersController();
		return $suppliers_controller->list_suppliers( $request );
	}

	/**
	 * Add a new supplier.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function add_supplier( WP_REST_Request $request ) {
		$suppliers_controller = new SuppliersController();
		return $suppliers_controller->add_supplier( $request );
	}

	/**
	 * Get a single supplier by ID.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_supplier( WP_REST_Request $request ) {
		$suppliers_controller = new SuppliersController();
		return $suppliers_controller->get_supplier( $request );
	}

	/**
	 * Check permissions for adding a supplier.
	 *
	 * @return bool
	 */
	public function check_permissions() {
		return current_user_can( 'manage_options' );
	}
}