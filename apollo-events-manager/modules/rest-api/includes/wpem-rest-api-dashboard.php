<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPEM_Rest_API_Dashboard class to show rest api data at front-end.
 */
class WPEM_Rest_API_Dashboard {

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
	public function wpem_dashboard_menu_add( $menus ) {
		$menus['rest_api'] = array(
			'title'   => __( 'Rest API', 'wpem-rest-api' ),
			'icon'    => 'wpem-icon-loop',
			'submenu' => array(
				'wpem_rest_api_setting' => array(
					'title'     => __( 'Settings', 'wpem-rest-api' ),
					'query_arg' => array( 'action' => 'wpem_rest_api_setting' ),
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
	public function wpem_rest_api_output_setting() {
		get_event_manager_template(
			'wpem-dashboard-rest-api-settings.php',
			array(),
			'wpem-rest-api',
			WPEM_REST_API_PLUGIN_DIR . '/templates/'
		);
	}
}
new WPEM_Rest_API_Dashboard();
