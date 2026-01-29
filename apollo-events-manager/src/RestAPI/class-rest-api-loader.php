<?php
/**
 * Apollo Events REST API Loader
 *
 * Registers all REST API routes for the Events Manager.
 *
 * @package Apollo_Events_Manager
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\RestAPI;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_API_Loader
 *
 * Initializes REST API endpoints.
 *
 * @since 2.0.0
 */
class REST_API_Loader {

	/**
	 * Instance.
	 *
	 * @since 2.0.0
	 * @var REST_API_Loader|null
	 */
	private static ?REST_API_Loader $instance = null;

	/**
	 * Controllers.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $controllers = array();

	/**
	 * Get instance.
	 *
	 * @since 2.0.0
	 * @return REST_API_Loader
	 */
	public static function get_instance(): REST_API_Loader {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes(): void {
		// Check if base controller exists.
		if ( ! class_exists( 'Apollo_Core\REST_API\Apollo_REST_Controller' ) ) {
			return;
		}

		// Load and register controllers.
		$this->load_controllers();

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Load controllers.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function load_controllers(): void {
		$dir = __DIR__;

		// Events Controller.
		$events_file = $dir . '/class-events-controller.php';
		if ( file_exists( $events_file ) ) {
			require_once $events_file;
			if ( class_exists( __NAMESPACE__ . '\Events_Controller' ) ) {
				$this->controllers['events'] = new Events_Controller();
			}
		}

		/**
		 * Filter to add custom controllers.
		 *
		 * @since 2.0.0
		 *
		 * @param array $controllers Registered controllers.
		 */
		$this->controllers = apply_filters( 'apollo_events_rest_controllers', $this->controllers );
	}

	/**
	 * Get controller.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name Controller name.
	 * @return object|null
	 */
	public function get_controller( string $name ): ?object {
		return $this->controllers[ $name ] ?? null;
	}
}

// Initialize.
REST_API_Loader::get_instance();
