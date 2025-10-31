<?php

/**
 * Fired during plugin activation
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXUserOnlineActivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		//create tables
		CBXUserOnlineHelper::create_tables();

		set_transient( 'cbxuseronline_activated_notice', 1 );

		//on core plugin activation check pro addon compatibility
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( defined( 'CBX_USERONLINEPROADDON_PLUGIN_NAME' ) || in_array( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ) {
			//plugin is activated

			$plugin_version = CBX_USERONLINEPROADDON_PLUGIN_VERSION;

			if ( version_compare( $plugin_version, '1.0.15', '<' ) ) {
				deactivate_plugins( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php' );
				set_transient( 'cbxuseronline_deactivated_notice', 1 );
			}
		}//end checking pro addon compatibility

		// Update the saved version
		update_option( 'cbxuseronline_version', CBX_USERONLINE_PLUGIN_VERSION );
	}//end method activate
}//end class CBXUserOnlineActivator
