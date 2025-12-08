<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * APRIO_Rest_API_Dashboard class to show rest api data at front-end.
 */
class APRIO_Rest_API_Dashboard {

	/**
	 * __construct function.
	 */
	public function __construct() {
	}

	/**
	 * add dashboard menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function aprio_dashboard_menu_add( $menus ) {
		$menus['rest_api'] = array(
			'title'   => __( 'Rest API', 'aprio-rest-api' ),
			'icon'    => 'aprio-icon-loop',
			'submenu' => array(
				'aprio_rest_api_setting' => array(
					'title'     => __( 'Settings', 'aprio-rest-api' ),
					'query_arg' => array( 'action' => 'aprio_rest_api_setting' ),
				),
			),
		);
		return $menus;
	}

	/**
	 * Show dashboard menu content function.
	 *
	 * @access public
	 * @return void
	 */
	public function aprio_rest_api_output_setting() {
		get_event_manager_template(
			'aprio-dashboard-rest-api-settings.php',
			array(),
			'aprio-rest-api',
			APRIO_REST_API_PLUGIN_DIR . '/templates/'
		);
	}
}
new APRIO_Rest_API_Dashboard();
