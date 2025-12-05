<?php
/**
 * Onboarding Endpoints.
 *
 * REST API endpoints for onboarding system.
 *
 * @package Apollo\API\Endpoints
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */

namespace Apollo\API\Endpoints;

use Apollo\Application\Users\BeginOnboarding;
use Apollo\Application\Users\CompleteOnboarding;
use Apollo\Application\Users\VerifyInstagram;
use Apollo\Application\Users\UserProfileRepository;

/**
 * OnboardingEndpoints class.
 *
 * Handles REST API endpoints for user onboarding.
 */
class OnboardingEndpoints {

	/**
	 * User profile repository.
	 *
	 * @var UserProfileRepository
	 */
	private UserProfileRepository $userRepo;

	/**
	 * Begin onboarding handler.
	 *
	 * @var BeginOnboarding
	 */
	private BeginOnboarding $beginOnboarding;

	/**
	 * Complete onboarding handler.
	 *
	 * @var CompleteOnboarding
	 */
	private CompleteOnboarding $completeOnboarding;

	/**
	 * Verify Instagram handler.
	 *
	 * @var VerifyInstagram
	 */
	private VerifyInstagram $verifyInstagram;

	/**
	 * Constructor - initialize dependencies.
	 */
	public function __construct() {
		$this->userRepo           = new UserProfileRepository();
		$this->beginOnboarding    = new BeginOnboarding();
		$this->completeOnboarding = new CompleteOnboarding();
		$this->verifyInstagram    = new VerifyInstagram();
	}

	/**
	 * Register all onboarding endpoints.
	 *
	 * @return void
	 */
	public function registerEndpoints(): void {
		// Get onboarding options (industries, roles, memberships).
		register_rest_route(
			'apollo/v1',
			'/onboarding/options',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getOnboardingOptions' ),
				'permission_callback' => array( $this, 'checkUserPermission' ),
			)
		);

		// Begin onboarding process.
		register_rest_route(
			'apollo/v1',
			'/onboarding/begin',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'beginOnboardingProcess' ),
				'permission_callback' => array( $this, 'checkUserPermission' ),
				'args'                => $this->getBeginOnboardingArgs(),
			)
		);

		// Complete onboarding process.
		register_rest_route(
			'apollo/v1',
			'/onboarding/complete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'completeOnboardingProcess' ),
				'permission_callback' => array( $this, 'checkUserPermission' ),
				'args'                => $this->getCompleteOnboardingArgs(),
			)
		);

		// Request DM verification (user).
		register_rest_route(
			'apollo/v1',
			'/onboarding/verify/request-dm',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'requestDmVerification' ),
				'permission_callback' => array( $this, 'checkUserPermission' ),
			)
		);

		// Get verification status.
		register_rest_route(
			'apollo/v1',
			'/onboarding/verify/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getVerificationStatus' ),
				'permission_callback' => array( $this, 'checkUserPermission' ),
			)
		);

		// Confirm verification (admin/mod).
		register_rest_route(
			'apollo/v1',
			'/onboarding/verify/confirm',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'confirmVerification' ),
				'permission_callback' => array( $this, 'checkAdminPermission' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
				),
			)
		);

		// Cancel verification (admin/mod).
		register_rest_route(
			'apollo/v1',
			'/onboarding/verify/cancel',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'cancelVerification' ),
				'permission_callback' => array( $this, 'checkAdminPermission' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
					'reason'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Get user profile.
		register_rest_route(
			'apollo/v1',
			'/onboarding/profile',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getUserProfile' ),
				'permission_callback' => array( $this, 'checkUserPermission' ),
			)
		);
	}

	/**
	 * Get onboarding options (industries, roles, memberships).
	 *
	 * @param \WP_REST_Request $_request REST request object (unused, required by REST API).
	 * @return \WP_REST_Response REST response.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getOnboardingOptions( \WP_REST_Request $_request ): \WP_REST_Response {
		try {
			$options = array(
				'industries'  => $this->userRepo->getIndustryOptions(),
				'roles'       => $this->userRepo->getRoleOptions(),
				'memberships' => $this->userRepo->getMembershipOptions(),
			);

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $options,
				),
				200
			);

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::getOnboardingOptions error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao carregar opções',
				),
				500
			);
		}//end try
	}

	/**
	 * Begin onboarding process.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function beginOnboardingProcess( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Usuário não autenticado',
					),
					401
				);
			}

			$data = $request->get_json_params();

			// Sanitize data from JSON params (in addition to REST arg sanitization).
			$data = $this->sanitizeOnboardingData( $data );

			// Validate required fields.
			$validation = $this->validateBeginOnboardingData( $data );
			if ( ! $validation['valid'] ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Dados inválidos',
						'errors'  => $validation['errors'],
					),
					400
				);
			}

			// Process onboarding.
			$result = $this->beginOnboarding->handle( $user_id, $data );

			return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::beginOnboardingProcess error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro interno no servidor',
				),
				500
			);
		}//end try
	}

	/**
	 * Complete onboarding process.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function completeOnboardingProcess( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Usuário não autenticado',
					),
					401
				);
			}

			$data = $request->get_json_params();

			// Rate limiting check.
			$rate_check = $this->completeOnboarding->checkRateLimit( $user_id );
			if ( ! $rate_check['allowed'] ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => "Aguarde {$rate_check['wait_time']} segundos",
					),
					429
				);
			}

			// Process completion.
			$result = $this->completeOnboarding->handle( $user_id, $data );

			return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::completeOnboardingProcess error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro interno no servidor',
				),
				500
			);
		}//end try
	}

	/**
	 * Request DM verification (user).
	 *
	 * @param \WP_REST_Request $_request The REST API request object (unused, required by REST API).
	 * @return \WP_REST_Response The REST API response.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function requestDmVerification( \WP_REST_Request $_request ): \WP_REST_Response {
		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Usuário não autenticado',
					),
					401
				);
			}

			// Rate limiting: 1 request per minute.
			$rate_check = $this->checkDmRequestRateLimit( $user_id );
			if ( ! $rate_check['allowed'] ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => "Aguarde {$rate_check['wait_time']} segundos antes de solicitar novamente",
					),
					429
				);
			}

			// Request DM verification.
			$result = $this->verifyInstagram->requestDmVerification( $user_id );

			return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::requestDmVerification error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro interno no servidor',
				),
				500
			);
		}//end try
	}

	/**
	 * Get verification status.
	 *
	 * @param \WP_REST_Request $_request The REST API request object (unused, required by REST API).
	 * @return \WP_REST_Response The REST API response.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getVerificationStatus( \WP_REST_Request $_request ): \WP_REST_Response {
		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Usuário não autenticado',
					),
					401
				);
			}

			$status = $this->verifyInstagram->getVerificationStatus( $user_id );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $status,
				),
				200
			);

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::getVerificationStatus error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao carregar status',
				),
				500
			);
		}//end try
	}

	/**
	 * Confirm verification (admin/mod).
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response The REST API response.
	 */
	public function confirmVerification( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_users' ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Sem permissão',
					),
					403
				);
			}

			// Get sanitized user_id from REST args.
			$user_id = $request->get_param( 'user_id' );

			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'ID do usuário inválido',
					),
					400
				);
			}

			$result = $this->verifyInstagram->confirmVerification( $user_id, get_current_user_id() );

			return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::confirmVerification error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro interno no servidor',
				),
				500
			);
		}//end try
	}

	/**
	 * Cancel verification (admin/mod).
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response The REST API response.
	 */
	public function cancelVerification( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_users' ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Sem permissão',
					),
					403
				);
			}

			// Get sanitized params from REST args.
			$user_id = $request->get_param( 'user_id' );
			$reason  = $request->get_param( 'reason' ) ?? '';

			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'ID do usuário inválido',
					),
					400
				);
			}

			$result = $this->verifyInstagram->cancelVerification( $user_id, get_current_user_id(), $reason );

			return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::cancelVerification error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro interno no servidor',
				),
				500
			);
		}//end try
	}

	/**
	 * Get user profile.
	 *
	 * @param \WP_REST_Request $_request The REST API request object (unused, required by REST API).
	 * @return \WP_REST_Response The REST API response.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getUserProfile( \WP_REST_Request $_request ): \WP_REST_Response {
		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Usuário não autenticado',
					),
					401
				);
			}

			$profile = $this->userRepo->getUserProfile( $user_id );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $profile,
				),
				200
			);

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'OnboardingEndpoints::getUserProfile error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao carregar perfil',
				),
				500
			);
		}//end try
	}

	/**
	 * Check user permission for API access.
	 *
	 * @param \WP_REST_Request $_request The REST API request object (unused, required by REST API).
	 * @return bool Whether user has permission.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function checkUserPermission( \WP_REST_Request $_request ): bool {
		// Must be logged in.
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Rate limiting per IP (100 requests per hour).
		$ip        = $this->getClientIp();
		$cache_key = "apollo_api_rate_limit_{$ip}";
		$requests  = wp_cache_get( $cache_key );
		$requests  = ( false !== $requests ) ? $requests : 0;

		if ( $requests >= 100 ) {
			return false;
		}

		wp_cache_set( $cache_key, $requests + 1, '', HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Check admin/mod permission for API access.
	 *
	 * @param \WP_REST_Request $_request The REST API request object (unused, required by REST API).
	 * @return bool Whether user has admin permission.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function checkAdminPermission( \WP_REST_Request $_request ): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_users' );
	}

	/**
	 * Check DM request rate limit (1 per minute).
	 *
	 * @param int $user_id The user ID to check.
	 * @return array Rate limit check result with allowed and wait_time.
	 */
	private function checkDmRequestRateLimit( int $user_id ): array {
		$cache_key    = "apollo_dm_request_rate_limit_{$user_id}";
		$last_request = wp_cache_get( $cache_key );

		if ( $last_request && ( time() - $last_request ) < 60 ) {
			return array(
				'allowed'   => false,
				'wait_time' => 60 - ( time() - $last_request ),
			);
		}

		wp_cache_set( $cache_key, time(), '', 60 );

		return array( 'allowed' => true );
	}

	/**
	 * Sanitize onboarding data from JSON params.
	 *
	 * @param array $data The raw onboarding data.
	 * @return array The sanitized onboarding data.
	 */
	private function sanitizeOnboardingData( array $data ): array {
		$sanitized = array();

		// Sanitize string fields.
		$string_fields = array( 'name', 'industry', 'whatsapp', 'instagram' );
		foreach ( $string_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		// Sanitize array fields (roles, member_of).
		$array_fields = array( 'roles', 'member_of' );
		foreach ( $array_fields as $field ) {
			if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
				$sanitized[ $field ] = array_map( 'sanitize_key', $data[ $field ] );
			}
		}

		// Sanitize email if present.
		if ( isset( $data['email'] ) ) {
			$sanitized['email'] = sanitize_email( $data['email'] );
		}

		return $sanitized;
	}

	/**
	 * Validate begin onboarding data.
	 *
	 * @param array $data The onboarding data to validate.
	 * @return array Validation result with valid flag and errors.
	 */
	private function validateBeginOnboardingData( array $data ): array {
		$errors = array();

		// Required fields.
		$required_fields = array( 'name', 'industry' );
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				$errors[ $field ] = "Campo {$field} é obrigatório";
			}
		}

		// Validate industry.
		if ( ! empty( $data['industry'] ) ) {
			$industries = $this->userRepo->getIndustryOptions();
			if ( ! isset( $industries[ $data['industry'] ] ) ) {
				$errors['industry'] = 'Indústria inválida';
			}
		}

		// Validate roles.
		if ( ! empty( $data['roles'] ) && is_array( $data['roles'] ) ) {
			$valid_roles = array_keys( $this->userRepo->getRoleOptions() );
			foreach ( $data['roles'] as $role ) {
				if ( ! in_array( $role, $valid_roles, true ) ) {
					$errors['roles'] = 'Função inválida detectada';
					break;
				}
			}
		}

		// Validate memberships.
		if ( ! empty( $data['member_of'] ) && is_array( $data['member_of'] ) ) {
			$valid_memberships = array_keys( $this->userRepo->getMembershipOptions() );
			foreach ( $data['member_of'] as $membership ) {
				if ( ! in_array( $membership, $valid_memberships, true ) ) {
					$errors['member_of'] = 'Membro inválido detectado';
					break;
				}
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Get client IP address.
	 *
	 * @return string The client IP address or 'unknown'.
	 */
	private function getClientIp(): string {
		$ip_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) && ! empty( $_SERVER[ $header ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP address validation.
				return sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			}
		}

		return 'unknown';
	}

	/**
	 * Get args for begin onboarding endpoint.
	 *
	 * @return array The endpoint arguments configuration.
	 */
	private function getBeginOnboardingArgs(): array {
		return array(
			'name'      => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $param ) {
					return ! empty( $param ) && strlen( $param ) <= 100;
				},
			),
			'industry'  => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => function ( $param ) {
					return ! empty( $param );
				},
			),
			'roles'     => array(
				'required'          => false,
				'type'              => 'array',
				'sanitize_callback' => function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					return array_map( 'sanitize_key', $value );
				},
				'validate_callback' => function ( $value ) {
					return is_array( $value ) || empty( $value );
				},
			),
			'member_of' => array(
				'required'          => false,
				'type'              => 'array',
				'sanitize_callback' => function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					return array_map( 'sanitize_key', $value );
				},
				'validate_callback' => function ( $value ) {
					return is_array( $value ) || empty( $value );
				},
			),
			'whatsapp'  => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'instagram' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get args for complete onboarding endpoint.
	 *
	 * @return array The endpoint arguments configuration.
	 */
	private function getCompleteOnboardingArgs(): array {
		return array(
			'confirm' => array(
				'required'          => true,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => function ( $param ) {
					return is_bool( $param ) || 'true' === $param || 'false' === $param || 1 === $param || 0 === $param;
				},
			),
		);
	}
}
