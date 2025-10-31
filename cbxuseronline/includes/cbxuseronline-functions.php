<?php

/**
 * The file that defines the custom functions of the plugin
 *
 *
 *
 * @link       codeboxr.com
 * @since      1.2.10
 *
 * @package    cbxuseronline
 * @subpackage cbxuseronline/includes
 */

if ( ! function_exists( 'cbxuseronline_load_svg' ) ) {
	/**
	 * Load an SVG file from a directory.
	 *
	 * @param  string  $svg_name  The name of the SVG file (without the .svg extension).
	 * @param  string  $directory  The directory where the SVG files are stored.
	 *
	 * @return string|false The SVG content if found, or false on failure.
	 * @since 1.0.0
	 */
	function cbxuseronline_load_svg( $svg_name = '', $folder = '' ) {
		if ( $svg_name == '' ) {
			return '';
		}


		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$credentials = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, null );
		if ( ! WP_Filesystem( $credentials ) ) {
			return ''; // Error handling here
		}

		global $wp_filesystem;


		$directory = cbxuseronline_icon_path();

		// Sanitize the file name to prevent directory traversal attacks.
		$svg_name = sanitize_file_name( $svg_name );
		if ( $folder != '' ) {
			$folder = trailingslashit( $folder );
		}

		// Construct the full file path.
		$file_path = $directory . $folder . $svg_name . '.svg';
		$file_path = apply_filters( 'cbxuseronline_svg_file_path', $file_path, $svg_name );

		// Check if the file exists.
		if ( $wp_filesystem->exists( $file_path ) && is_readable( $file_path ) ) {
			// Get the SVG file content.
			return $wp_filesystem->get_contents( $file_path );
		} else {
			// Return false if the file does not exist or is not readable.
			return '';
		}
	}//end method cbxuseronline_load_svg
}

if ( ! function_exists( 'cbxuseronline_icon_path' ) ) {
	/**
	 * Resume icon path
	 *
	 * @return mixed|null
	 * @since 1.0.0
	 */
	function cbxuseronline_icon_path() {
		$directory = trailingslashit( CBX_USERONLINE_PLUGIN_ROOT_PATH ) . 'assets/icons/';

		return apply_filters( 'cbxuseronline_icon_path', $directory );
	}//end method cbxuseronline_icon_path
}


if(!function_exists('cbxuseronline_isPrivateIP')){
	function cbxuseronline_isPrivateIP( $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			// IPv4 private ranges
			$privateRanges = [
				'10.0.0.0/8',
				'172.16.0.0/12',
				'192.168.0.0/16',
				'127.0.0.0/8' //loopback
			];
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// IPv6 private ranges (simplified)
			$privateRanges = [
				'::1/128',   //loopback
				'fc00::/7',  // Unique Local Addresses (ULA)
				'fe80::/10', // Link-local addresses
			];
		} else {
			// Invalid IP address
			return false;
		}

		foreach ( $privateRanges as $range ) {
			if ( cbxuseronline_ipInRange( $ip, $range ) ) {
				return true;
			}
		}

		return false;
	}//end method cbxuseronline_isPrivateIP
}

if(!function_exists('cbxuseronline_ipInRange')){
	function cbxuseronline_ipInRange( $ip, $range ) {
		if ( strpos( $range, '/' ) === false ) {
			$range .= '/32'; // For single IPs
		}

		[ $net, $mask ] = explode( '/', $range, 2 );

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$ip_long  = ip2long( $ip );
			$net_long = ip2long( $net );
			$mask     = ~( ( 1 << ( 32 - $mask ) ) - 1 );

			return ( $ip_long & $mask ) == ( $net_long & $mask );
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			//IPv6 comparison is more complex. Using inet_pton and gmp
			$ip_bin  = inet_pton( $ip );
			$net_bin = inet_pton( $net );

			$mask_bin = str_repeat( "\xff", $mask / 8 ) . str_repeat( "\x00", 16 - $mask / 8 );

			$ip_gmp   = gmp_init( bin2hex( $ip_bin ), 16 );
			$net_gmp  = gmp_init( bin2hex( $net_bin ), 16 );
			$mask_gmp = gmp_init( bin2hex( $mask_bin ), 16 );

			$ip_masked  = gmp_and( $ip_gmp, $mask_gmp );
			$net_masked = gmp_and( $net_gmp, $mask_gmp );

			return gmp_cmp( $ip_masked, $net_masked ) === 0;

		} else {
			return false;
		}
	}//end method cbxuseronline_ipInRange
}

if(!function_exists('cbxuseronline_is_rest_api_request')){
	/**
	 * Check if doing rest request
	 *
	 * @return bool
	 */
	function cbxuseronline_is_rest_api_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		$request_uri =  isset($_SERVER['REQUEST_URI'])? sanitize_text_field(wp_unslash( $_SERVER['REQUEST_URI'] ) ): '';

		//if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		if ( empty( $request_uri ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		//return ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) );
		return ( false !== strpos( $request_uri, $rest_prefix ) );
	}//end function cbxuseronline_is_rest_api_request
}

if(!function_exists('cbxuseronline_doing_it_wrong')){
	/**
	 * Wrapper for _doing_it_wrong().
	 *
	 * @since  1.0.0
	 * @param string $function Function used.
	 * @param string $message Message to log.
	 * @param string $version Version the message was added in.
	 */
	function cbxuseronline_doing_it_wrong( $function, $message, $version ) {
		// @codingStandardsIgnoreStart
		$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

		if ( wp_doing_ajax() || cbxuseronline_is_rest_api_request() ) {
			do_action( 'doing_it_wrong_run', $function, $message, $version );
			error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
		} else {
			_doing_it_wrong( $function, $message, $version );
		}
		// @codingStandardsIgnoreEnd
	}//end function cbxuseronline_doing_it_wrong
}