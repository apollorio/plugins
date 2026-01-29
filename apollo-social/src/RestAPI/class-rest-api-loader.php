<?php
/**
 * Apollo Social REST API Loader
 *
 * Registers all REST API routes for the Social plugin.
 *
 * @package Apollo_Social
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\RestAPI;

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

		// Feed Controller.
		$feed_file = $dir . '/class-feed-controller.php';
		if ( file_exists( $feed_file ) ) {
			require_once $feed_file;
			if ( class_exists( __NAMESPACE__ . '\Feed_Controller' ) ) {
				$this->controllers['feed'] = new Feed_Controller();
			}
		}

		// Profile Controller.
		$profile_file = $dir . '/class-profile-controller.php';
		if ( file_exists( $profile_file ) ) {
			require_once $profile_file;
			if ( class_exists( __NAMESPACE__ . '\Profile_Controller' ) ) {
				$this->controllers['profile'] = new Profile_Controller();
			}
		}

		// Classifieds Controller.
		$classifieds_file = $dir . '/class-classifieds-controller.php';
		if ( file_exists( $classifieds_file ) ) {
			require_once $classifieds_file;
			if ( class_exists( __NAMESPACE__ . '\Classifieds_Controller' ) ) {
				$this->controllers['classifieds'] = new Classifieds_Controller();
			}
		}

		/**
		 * Filter to add custom controllers.
		 *
		 * @since 2.0.0
		 *
		 * @param array $controllers Registered controllers.
		 */
		$this->controllers = apply_filters( 'apollo_social_rest_controllers', $this->controllers );
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
