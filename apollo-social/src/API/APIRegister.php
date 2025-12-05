<?php

namespace Apollo\API;

use Apollo\API\Endpoints\OnboardingEndpoints;

/**
 * APIRegister
 * Registers all Apollo REST API endpoints
 */
class APIRegister {

	private OnboardingEndpoints $onboardingEndpoints;

	public function __construct() {
		$this->onboardingEndpoints = new OnboardingEndpoints();
	}

	/**
	 * Initialize API registration
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
		add_action( 'rest_api_init', array( $this, 'addCorsHeaders' ) );
	}

	/**
	 * Register all API routes
	 */
	public function registerRoutes(): void {
		// Register onboarding endpoints
		$this->onboardingEndpoints->registerEndpoints();

		// Add API documentation endpoint
		register_rest_route(
			'apollo/v1',
			'/docs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getApiDocumentation' ),
				'permission_callback' => '__return_true',
			)
		);

		// Add health check endpoint
		register_rest_route(
			'apollo/v1',
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'healthCheck' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Add CORS headers for API requests
	 */
	public function addCorsHeaders(): void {
		add_action(
			'rest_pre_serve_request',
			function ( $served, $result, $request, $server ) {
				$origin = get_http_origin();

				// Allow requests from same domain and Canvas Mode
				if ( $origin && $this->isAllowedOrigin( $origin ) ) {
					header( "Access-Control-Allow-Origin: {$origin}" );
				}

				header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
				header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce' );
				header( 'Access-Control-Allow-Credentials: true' );

				return $served;
			},
			10,
			4
		);
	}

	/**
	 * Check if origin is allowed for CORS
	 */
	private function isAllowedOrigin( string $origin ): bool {
		$site_url        = get_site_url();
		$allowed_origins = array(
			$site_url,
			str_replace( 'http://', 'https://', $site_url ),
			str_replace( 'https://', 'http://', $site_url ),
		);

		// Allow localhost for development
		if ( strpos( $origin, 'localhost' ) !== false || strpos( $origin, '127.0.0.1' ) !== false ) {
			return true;
		}

		return in_array( $origin, $allowed_origins );
	}

	/**
	 * API documentation endpoint
	 */
	public function getApiDocumentation( \WP_REST_Request $request ): \WP_REST_Response {
		$documentation = array(
			'version'        => '1.0.0',
			'name'           => 'Apollo Onboarding API',
			'description'    => 'REST API for Apollo conversational onboarding system',
			'endpoints'      => array(
				'GET /apollo/v1/onboarding/options'          => array(
					'description'    => 'Get available industries, roles, and membership options',
					'authentication' => 'required',
					'response'       => array(
						'industries'  => 'object',
						'roles'       => 'object',
						'memberships' => 'object',
					),
				),
				'POST /apollo/v1/onboarding/begin'           => array(
					'description'    => 'Begin onboarding process and validate user data',
					'authentication' => 'required',
					'parameters'     => array(
						'name'      => 'string (required)',
						'industry'  => 'string (required)',
						'roles'     => 'array (optional)',
						'member_of' => 'array (optional)',
						'whatsapp'  => 'string (optional)',
						'instagram' => 'string (optional)',
					),
				),
				'POST /apollo/v1/onboarding/complete'        => array(
					'description'    => 'Complete onboarding and create verification record',
					'authentication' => 'required',
					'parameters'     => array(
						'confirm' => 'boolean (required)',
					),
				),
				'POST /apollo/v1/onboarding/verify/upload'   => array(
					'description'    => 'Upload Instagram verification images',
					'authentication' => 'required',
					'parameters'     => array(
						'verification_images' => 'file[] (1-3 images, max 5MB each)',
					),
				),
				'GET /apollo/v1/onboarding/verify/status'    => array(
					'description'    => 'Get current verification status',
					'authentication' => 'required',
				),
				'DELETE /apollo/v1/onboarding/verify/delete' => array(
					'description'    => 'Delete verification assets for re-upload',
					'authentication' => 'required',
				),
				'GET /apollo/v1/onboarding/profile'          => array(
					'description'    => 'Get user onboarding profile data',
					'authentication' => 'required',
				),
			),
			'authentication' => array(
				'type'        => 'WordPress Authentication',
				'description' => 'Uses WordPress user authentication and nonce verification',
				'headers'     => array(
					'X-WP-Nonce' => 'WordPress nonce for CSRF protection',
				),
			),
			'rate_limiting'  => array(
				'description' => 'Rate limited to 100 requests per hour per IP',
				'headers'     => array(
					'X-RateLimit-Limit'     => 'Request limit per hour',
					'X-RateLimit-Remaining' => 'Remaining requests',
				),
			),
			'errors'         => array(
				'format'     => array(
					'success' => false,
					'message' => 'Human readable error message',
					'errors'  => 'Field-specific validation errors (optional)',
				),
				'http_codes' => array(
					'200' => 'Success',
					'400' => 'Bad Request / Validation Error',
					'401' => 'Unauthorized',
					'429' => 'Rate Limited',
					'500' => 'Internal Server Error',
				),
			),
		);

		return new \WP_REST_Response( $documentation, 200 );
	}

	/**
	 * Health check endpoint
	 */
	public function healthCheck( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$health = array(
			'status'    => 'healthy',
			'timestamp' => current_time( 'mysql' ),
			'version'   => '1.0.0',
			'checks'    => array(),
		);

		// Database check
		try {
			$wpdb->get_var( 'SELECT 1' );
			$health['checks']['database'] = 'ok';
		} catch ( \Exception $e ) {
			$health['checks']['database'] = 'error';
			$health['status']             = 'unhealthy';
		}

		// Tables check
		$required_tables = array(
			$wpdb->prefix . 'apollo_verifications',
			$wpdb->prefix . 'apollo_audit_log',
			$wpdb->prefix . 'apollo_analytics_events',
		);

		$missing_tables = array();
		foreach ( $required_tables as $table ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
				$missing_tables[] = $table;
			}
		}

		if ( empty( $missing_tables ) ) {
			$health['checks']['tables'] = 'ok';
		} else {
			$health['checks']['tables'] = 'missing: ' . implode( ', ', $missing_tables );
			$health['status']           = 'degraded';
		}

		// File permissions check
		$upload_dir = wp_upload_dir();
		if ( is_writable( $upload_dir['basedir'] ) ) {
			$health['checks']['file_permissions'] = 'ok';
		} else {
			$health['checks']['file_permissions'] = 'uploads directory not writable';
			$health['status']                     = 'degraded';
		}

		// API availability check
		$health['checks']['rest_api'] = 'ok';

		$status_code = $health['status'] === 'healthy' ? 200 : ( $health['status'] === 'degraded' ? 200 : 503 );

		return new \WP_REST_Response( $health, $status_code );
	}
}
