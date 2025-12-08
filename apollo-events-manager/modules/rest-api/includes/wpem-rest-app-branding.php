<?php
// phpcs:ignoreFile
defined( 'ABSPATH' ) || exit;

/**
 * REST API APP Branding controller class.
 *
 * @extends APRIO_REST_CRUD_Controller
 */
class APRIO_REST_APP_Branding_Controller extends APRIO_REST_CRUD_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'aprio';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'branding';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'event_listing';

	/**
	 * Initialize event actions.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 10 );

		if ( ! class_exists( 'APRIO_Rest_API_Settings' ) ) {
			include_once APRIO_REST_API_PLUGIN_DIR . '/admin/aprio-rest-api-settings.php';
		}
	}

	/**
	 * Register the routes for events.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_branding_settings' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * function get_branding_settings
	 *
	 * @since 1.0.0
	 * @param
	 */
	public function get_branding_settings() {
		$auth_check = $this->aprio_check_authorized_user();
		if ( $auth_check ) {
			return self::prepare_error_for_response( 405 );
		} else {
			$aprio_app_branding_settings = array();

			$aprio_app_branding_settings['app_name']                = get_option( 'aprio_rest_api_app_name' );
			$aprio_app_branding_settings['app_logo']                = get_option( 'aprio_rest_api_app_logo' );
			$aprio_app_branding_settings['app_splash_screen_image'] = get_option( 'aprio_rest_api_app_splash_screen_image' );
			$aprio_app_branding_settings['color_scheme']            = get_option( 'aprio_app_branding_settings' );
			$aprio_app_branding_settings['dark_color_scheme']       = get_option( 'aprio_app_branding_dark_settings' );
			$response_data         = self::prepare_error_for_response( 200 );
			$response_data['data'] = array(
				'aprio_app_branding_settings' => $aprio_app_branding_settings,
			);
			return wp_send_json( $response_data );
		}
	}
}
new APRIO_REST_APP_Branding_Controller();
