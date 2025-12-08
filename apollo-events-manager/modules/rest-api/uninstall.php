<?php
// phpcs:ignoreFile
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete options of rest api plugin
$options = array(
	'aprio_rest_api_version',
	'aprio_primary_color',
	'aprio_success_color',
	'aprio_info_color',
	'aprio_warning_color',
	'aprio_danger_color',
	'aprio_primary_dark_color',
	'aprio_success_dark_color',
	'aprio_info_dark_color',
	'aprio_warning_dark_color',
	'aprio_danger_dark_color',
	'aprio_app_branding_settings',
	'aprio_app_branding_dark_settings',
	'aprio_rest_api_app_logo',
	'aprio_rest_api_app_name',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
