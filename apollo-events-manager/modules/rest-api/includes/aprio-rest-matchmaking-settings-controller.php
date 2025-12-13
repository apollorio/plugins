<?php
// phpcs:ignoreFile
/**
 * REST API Matchmaking Settings controller (Event Controller style)
 *
 * Provides an endpoint to retrieve/update compatibilidade settings for the current user.
 * Structured similarly to the Events controller's route/permission/response style.
 *
 * Route base: /wp-json/aprio/compatibilidade-settings
 * Methods: GET (retrieve), POST (update)
 *
 * @since 1.1.4
 */

defined( 'ABSPATH' ) || exit;

class APRIO_REST_Matchmaking_Settings_Controller extends APRIO_REST_CRUD_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'aprio';

	/**
	 * Route base for compatibilidade settings endpoints.
	 *
	 * @var string
	 */
	protected $rest_base = 'compatibilidade-settings';

	/**
	 * Initialize routes.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 10 );
	}

	/**
	 * Register compatibilidade settings routes (event-controller style structure).
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Permission callback: ensure user is authorized (mirrors other compatibilidade controllers).
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {
		$auth_check = $this->aprio_check_authorized_user();
		if ( $auth_check ) {
			// Parent method returns standardized error payload if unauthorized.
			return $auth_check;
		}
		return true;
	}

	/**
	 * GET /compatibilidade-settings
	 * Retrieve compatibilidade settings for the current user. If event_id is provided,
	 * include participation for that event; otherwise include all events.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|Array
	 */
	public function get_settings( $request ) {
		$settings = array(
			'request_mode'           => get_option( 'aprio_meeting_request_mode' ),
			'scheduling_mode'        => get_option( 'aprio_meeting_scheduling_mode' ),
			'attendee_limit'         => get_option( 'aprio_meeting_attendee_limit' ),
			'meeting_expiration'     => get_option( 'aprio_meeting_expiration' ),
			'enable_compatibilidade'     => get_option( 'enable_compatibilidade' ),
			'participant_activation' => get_option( 'participant_activation' ),
		);

		$response_data         = self::prepare_error_for_response( 200 );
		$response_data['data'] = $settings;
		return wp_send_json( $response_data );
	}
}

new APRIO_REST_Matchmaking_Settings_Controller();
