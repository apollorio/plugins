<?php

declare(strict_types=1);

namespace Apollo_Core;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Bootstrap
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST Bootstrap class
 */
class Rest_Bootstrap {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	public const NAMESPACE = 'apollo/v1';

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Health check endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/testando',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'health_check' ),
				'permission_callback' => '__return_true',
			)
		);

		do_action( 'apollo_core_register_rest_routes' );
	}

	/**
	 * Health check endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function health_check( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'status'  => 'ok',
				'version' => APOLLO_CORE_VERSION,
				'modules' => array_keys( apollo_core()->modules->get_modules() ),
			),
			200
		);
	}

	/**
	 * Get namespace
	 *
	 * @return string
	 */
	public static function get_namespace(): string {
		return self::NAMESPACE;
	}

	/**
	 * Get REST URL
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function get_url( string $path = '' ): string {
		return rest_url( self::NAMESPACE . '/' . ltrim( $path, '/' ) );
	}
}
