<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://codeboxr.com
 * @since      1.0.6
 *
 * @package    CBXUseronline
 * @subpackage CBXUseronline/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.6
 * @package    CBXUseronline
 * @subpackage CBXUseronline/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXUseronlineUninstall {
	/**
	 * Uninstall plugin functionality
	 *
	 *
	 * @since    1.1.3
	 */
	public static function uninstall() {
		// For the regular site.
		if ( ! is_multisite() ) {
			self::uninstall_tasks();
		}
		else{
			//for multi site
			global $wpdb;

			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id )   {
				switch_to_blog( $blog_id );

				self::uninstall_tasks();
			}

			switch_to_blog( $original_blog_id );
		}
	}//end method uninstall

	/**
	 * Do the necessary uninstall tasks
	 *
	 * @return void
	 */
	public static function uninstall_tasks() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		global $wpdb;

		$settings = new CBXUseronlineSetting();
		$delete_global_config = $settings->get_field( 'delete_global_config', 'cbxuseronline_tools', 'no' );

		if ( $delete_global_config == 'yes' ) {
			//before hook
			do_action( 'cbxuseronline_plugin_uninstall_before' );

			//delete options
			$option_values = CBXUserOnlineHelper::getAllOptionNames();

			do_action('cbxuseronline_plugin_options_deleted_before');

			foreach ( $option_values as $key => $option_value ) {
				$option = $option_value['option_name'];

				do_action('cbxuseronline_plugin_option_delete_before', $option);
				delete_option( $option );
				do_action('cbxuseronline_plugin_option_delete_after', $option);
			}

			do_action( 'cbxuseronline_plugin_options_deleted_after' );
			do_action( 'cbxuseronline_plugin_options_deleted' );
			//end delete options

			//delete tables
			$table_names  = CBXUserOnlineHelper::getAllDBTablesList();

			if(!isset($table_names['cbxuseronline_history'])){
				//if pro addon disabled
				if(CBXUseronlineHelper::does_table_exist('cbxuseronline_history')){
					$table_names['cbxuseronline_history'] = $wpdb->prefix . 'cbxuseronline_history';
				}
			}


			if (is_array($table_names) && count($table_names)) {
				do_action('cbxuseronline_plugin_tables_deleted_before', $table_names);

				global $wpdb;

				foreach ($table_names as $table_name){
					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$query_result = $wpdb->query(  "DROP TABLE IF EXISTS {$table_name}");
				}

				do_action('cbxuseronline_plugin_tables_deleted_after', $table_names);
				do_action('cbxuseronline_plugin_tables_deleted');
			}
			//end delete tables

			//after hook
			do_action( 'cbxuseronline_plugin_uninstall_after' );

			//general hook
			do_action( 'cbxuseronline_plugin_uninstall' );
		}
	}//end method uninstall
}//end class CBXUseronlineUninstall