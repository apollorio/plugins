<?php
/**
 * Configuration helper function
 * Provides a simple way to access configuration files
 */

if ( ! function_exists( 'config' ) ) {
	/**
	 * Get configuration value by key
	 *
	 * @param string $key Configuration key in dot notation (e.g., 'integrations.badgeos')
	 * @param mixed  $default Default value if key not found
	 * @return mixed Configuration value
	 */
	function config( $key, $default = null ) {
		static $configs = array();

		// Validate key is string
		if ( ! is_string( $key ) || empty( $key ) ) {
			return $default;
		}

		// Parse the key
		$parts = explode( '.', $key );
		$file  = $parts[0];

		// Security: Prevent directory traversal in config file name
		$file = sanitize_file_name( $file );
		if ( empty( $file ) ) {
			return $default;
		}

		// Load config file if not already loaded
		if ( ! isset( $configs[ $file ] ) ) {
			$config_file = __DIR__ . "/../config/{$file}.php";

			// Security: Ensure file is within config directory
			$config_dir       = realpath( __DIR__ . '/../config/' );
			$config_file_path = realpath( $config_file );

			if ( $config_file_path && strpos( $config_file_path, $config_dir ) === 0 && file_exists( $config_file ) ) {
				$configs[ $file ] = require $config_file;
			} else {
				$configs[ $file ] = array();
			}
		}

		// Navigate through the config array
		$value = $configs[ $file ];

		for ( $i = 1; $i < count( $parts ); $i++ ) {
			$part = $parts[ $i ];
			if ( isset( $value[ $part ] ) ) {
				$value = $value[ $part ];
			} else {
				return $default;
			}
		}

		return $value;
	}
}//end if

if ( ! function_exists( 'apollo_get_user_page' ) ) {
	/**
	 * Fetch the user_page assigned to the given user.
	 *
	 * @param int $user_id User identifier.
	 *
	 * @return \WP_Post|null
	 */
	function apollo_get_user_page( $user_id ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return null;
		}

		return \Apollo\Modules\UserPages\UserPageRepository::get( $user_id );
	}
}

if ( ! function_exists( 'apollo_get_or_create_user_page' ) ) {
	/**
	 * Retrieve the user_page or create it when missing.
	 *
	 * @param int $user_id User identifier.
	 *
	 * @return \WP_Post|null
	 */
	function apollo_get_or_create_user_page( $user_id ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return null;
		}

		return \Apollo\Modules\UserPages\UserPageRepository::getOrCreate( $user_id );
	}
}

// Load form helpers
if ( file_exists( __DIR__ . '/helpers-forms.php' ) ) {
	require_once __DIR__ . '/helpers-forms.php';
	require_once __DIR__ . '/helpers-user-pages.php';
}
