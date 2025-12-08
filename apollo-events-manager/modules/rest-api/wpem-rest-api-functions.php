<?php
// phpcs:ignoreFile
/**
 * APRIO Rest Api public functions
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'aprio_rest_api_prepare_date_response' ) ) {
	/**
	 * Parses and formats a date for ISO8601/RFC3339.
	 *
	 * Required WP 4.4 or later.
	 * See https://developer.wordpress.org/reference/functions/mysql_to_rfc3339/
	 *
	 * @since  1.0.0
	 * @param  string|null| $date Date.
	 * @param  bool         $utc  Send false to get local/offset time.
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	function aprio_rest_api_prepare_date_response( $date, $utc = true ) {
		// need improvements as per aprio date time class
		return $date;
	}
}

if ( ! function_exists( 'aprio_rest_api_check_post_permissions' ) ) {
	/**
	 * Check permissions of posts on REST API.
	 *
	 * @since  1.0.0
	 * @param  string $post_type Post type.
	 * @param  string $context   Request context.
	 * @param  int    $object_id Post ID.
	 * @return bool
	 */
	function aprio_rest_api_check_post_permissions( $post_type, $context = 'read', $object_id = 0 ) {
		global $wpdb;
		$contexts   = array(
			'read'   => 'read',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		);
		$permission = true;

		return apply_filters( 'aprio_rest_api_check_permissions', $permission, $context, $object_id, $post_type );
	}
}//end if

if ( ! function_exists( 'aprio_rest_api_urlencode_rfc3986' ) ) {
	/**
	 * Encodes a value according to RFC 3986.
	 * Supports multidimensional arrays.
	 *
	 * @since  1.0.0
	 * @param  string|array $value The value to encode.
	 * @return string|array       Encoded values.
	 */
	function aprio_rest_api_urlencode_rfc3986( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'aprio_rest_api_urlencode_rfc3986', $value );
		}

		return str_replace( array( '+', '%7E' ), array( ' ', '~' ), rawurlencode( $value ) );
	}
}

if ( ! function_exists( 'aprio_rest_api_color_brightness' ) ) {

	/**
	 * APRIO Color Brightness
	 *
	 * @since  1.0.0
	 * @param  string $data Message to be hashed.
	 * @return string
	 */
	function aprio_rest_api_color_brightness( $hexCode, $adjustPercent ) {
		$hexCode = ltrim( $hexCode, '#' );

		if ( strlen( $hexCode ) == 3 ) {
			$hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
		}

		$hexCode = array_map( 'hexdec', str_split( $hexCode, 2 ) );

		foreach ( $hexCode as & $color ) {
			$adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
			$adjustAmount    = ceil( $adjustableLimit * $adjustPercent );

			$color = str_pad( dechex( $color + $adjustAmount ), 2, '0', STR_PAD_LEFT );
		}

		return '#' . implode( $hexCode );
	}
}//end if

if ( ! function_exists( 'aprio_rest_api_hex_to_rgb' ) ) {
	/**
	 * APRIO hex to rgb
	 *
	 * @since  1.0.0
	 * @param  string $data Message to be hashed.
	 * @return string
	 */
	function aprio_rest_api_hex_to_rgb( $colour ) {
		if ( $colour[0] == '#' ) {
			$colour = substr( $colour, 1 );
		}
		if ( strlen( $colour ) == 6 ) {
			list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
		} elseif ( strlen( $colour ) == 3 ) {
			list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
		} else {
			return false;
		}
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return array(
			'red'   => $r,
			'green' => $g,
			'blue'  => $b,
		);
	}
}//end if
if ( ! function_exists( 'aprio_rest_api_check_manager_permissions' ) ) {
	/**
	 * Check manager permissions on REST API.
	 *
	 * @since  2.6.0
	 * @param  string $object  Object.
	 * @param  string $context Request context.
	 * @return bool
	 */
	function aprio_rest_api_check_manager_permissions( $object, $context = 'read' ) {
		$permission = true;

		return apply_filters( 'aprio_rest_api_check_permissions', $permission, $context, 0, $object );
	}
}

if ( ! function_exists( 'aprio_response_default_status' ) ) {
	/**
	 * This function is used to get error code, status, messages
	 *
	 * @since 1.0.1
	 */
	function aprio_response_default_status() {
		$error_info = apply_filters(
			'aprio_rest_response_default_status',
			array(
				array(
					'code'    => 200,
					'status'  => 'OK',
					'message' => __( 'Request is successfully completed.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 201,
					'status'  => 'Created',
					'message' => __( 'Resource was successfully created.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 202,
					'status'  => 'Updated',
					'message' => __( 'Resource was successfully updated.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 204,
					'status'  => 'No Content',
					'message' => __( 'Request was successfully processed and there is no content to return.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 400,
					'status'  => 'Bad request',
					'message' => __( 'Invalid syntax, incorrectly formatted JSON, or data violating a database constraint.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 401,
					'status'  => 'Unauthorized',
					'message' => __( 'Username or Password Wrong, Please try again.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 403,
					'status'  => 'Forbidden',
					'message' => __( 'Does not have permissions to access the requested resource.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 404,
					'status'  => 'Not found',
					'message' => __( 'Data not found.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 406,
					'status'  => 'Unauthorized',
					'message' => __( 'Username already exists.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 407,
					'status'  => 'Unauthorized',
					'message' => __( 'Email already exists.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 413,
					'status'  => 'Error',
					'message' => __( 'Unable to accept items for this request.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 408,
					'status'  => 'Error',
					'message' => __( 'Failed to create Resource.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 409,
					'status'  => 'Error',
					'message' => __( 'Failed to update Resource.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 410,
					'status'  => 'Error',
					'message' => __( 'The item already deleted.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 418,
					'status'  => 'Error',
					'message' => __( 'Already Checkin.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 416,
					'status'  => 'Error',
					'message' => __( 'You can Checkin only for confirmed ticket.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 412,
					'status'  => 'Error',
					'message' => __( 'You Do Not Have Permission to Delete Resource.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 500,
					'status'  => 'Internal server error',
					'message' => __( 'An unexpected error has occurred in processing the request. View the logs on the device for details.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 503,
					'status'  => 'Service unavailable',
					'message' => __( 'You Do Not Have Permission to access this app.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 504,
					'status'  => 'Permission Denied',
					'message' => __( 'You do not have permission to edit this resource.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 505,
					'status'  => 'Checkin Denied',
					'message' => __( 'You do not have permission to checkin yet.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 506,
					'status'  => 'Disabled Matchmaking',
					'message' => __( 'Matchmaking functionality is not enabled.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 203,
					'status'  => 'Non-Authorative Information',
					'message' => __( 'You does not have read permissions.', 'aprio-rest-api' ),
				),
				array(
					'code'    => 405,
					'status'  => 'Authentication Failed',
					'message' => __( 'User not exist.', 'aprio-rest-api' ),
				),
			)
		);
		return $error_info;
	}
}//end if

if ( ! function_exists( 'get_aprio_rest_api_ecosystem_info' ) ) {
	/**
	 * This function is used to get ecosystem information of website
	 *
	 * @since 1.0.1
	 */
	function get_aprio_rest_api_ecosystem_info() {
		// Create required plugin list for aprio rest api
		$required_plugins = apply_filters(
			'aprio_rest_api_required_plugin_list',
			array(
				'woocommerce'                    => 'Woocommerce',
				'wp-event-manager'               => 'WP Event Manager',
				'aprio-rest-api'                  => 'APRIO Rest API',
				'wp-event-manager-sell-tickets'  => 'WP Event Manager Sell Tickets',
				'wp-event-manager-registrations' => 'WP Event Manager Registrations',
				'aprio-guests'                    => 'WP Event Manager Guests',
				'aprio-speaker-schedule'          => 'WP Event Manager Speaker & Schedule',
				'aprio-name-badges'               => 'WP Event Manager - Name Badges',
			)
		);

		// Get ecosystem data
		$plugins        = get_plugins();
		$ecosystem_info = array();

		foreach ( $plugins as $filename => $plugin ) {
			if ( 'woocommerce' == $plugin['TextDomain'] || 'wp-event-manager' == $plugin['TextDomain'] || 'aprio-rest-api' == $plugin['TextDomain'] ) {
				$ecosystem_info[ $plugin['TextDomain'] ] = array(
					'version'     => $plugin['Version'],
					'activated'   => is_plugin_active( $filename ),
					'plugin_name' => $plugin['Name'],
				);
			} elseif ( $plugin['AuthorName'] == 'WP Event Manager' && is_plugin_active( $filename ) ) {
					$licence_activate = get_option( $plugin['TextDomain'] . '_licence_key' );

				if ( ! empty( $licence_activate ) ) {
					$license_status                          = check_aprio_license_expire_date( $licence_activate );
					$ecosystem_info[ $plugin['TextDomain'] ] = array(
						'version'     => $plugin['Version'],
						'activated'   => $license_status,
						'plugin_name' => $plugin['Name'],
					);
				} else {
					$ecosystem_info[ $plugin['TextDomain'] ] = array(
						'version'     => $plugin['Version'],
						'activated'   => false,
						'plugin_name' => $plugin['Name'],
					);
				}
			}//end if
		}//end foreach

		$plugin_list = array();
		// Check id required plugin is not in list
		foreach ( $required_plugins as $plugin_key => $plugin_name ) {
			if ( array_key_exists( $plugin_key, $ecosystem_info ) ) {
				$plugin_list[ $plugin_key ] = $ecosystem_info[ $plugin_key ];
			} else {
				$plugin_list[ $plugin_key ] = array(
					'version'     => '',
					'activated'   => false,
					'plugin_name' => $plugin_name,
				);
			}
		}
		return $plugin_list;
	}
}//end if

if ( ! function_exists( 'check_aprio_license_expire_date' ) ) {
	/**
	 * This function is used to check plugin license key is expired or not
	 */
	function check_aprio_license_expire_date( $licence_key ) {

		$args     = array();
		$defaults = array(
			'request'     => 'check_expire_key',
			'licence_key' => $licence_key,
		);

		$args    = wp_parse_args( $args, $defaults );
		$request = wp_remote_get( APRIO_PLUGIN_ACTIVATION_API_URL . '&' . http_build_query( $args, '', '&' ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );
		$response = (object) $response;

		if ( isset( $response->error ) ) {
			return false;
		}

		// Set version variables
		if ( isset( $response ) && is_object( $response ) && $response !== false ) {
			return true;
		}
	}
}//end if

if ( ! function_exists( 'get_aprio_event_users' ) ) {

	/**
	 * This function used to get all event users
	 *
	 * @since 1.0.1
	 */
	function get_aprio_event_users() {
		// Get allowed roles from settings; default to dj and aprio-scanner
		$allowed_roles = get_option( 'aprio_rest_allowed_roles' );
		if ( empty( $allowed_roles ) || ! is_array( $allowed_roles ) ) {
			$allowed_roles = array( 'dj', 'aprio-scanner', 'administrator' );
		}
		$allowed_roles = array_map( 'sanitize_key', $allowed_roles );

		$args = array(
			'role__in' => $allowed_roles,
		);

		$users          = get_users( $args );
		$filtered_users = array();

		foreach ( $users as $user ) {
			$filtered_users[] = array(
				'ID'       => $user->ID,
				'username' => $user->user_login,
				'email'    => $user->user_email,
				'roles'    => isset( $user->roles ) ? $user->roles : array(),
			);
		}
		return $filtered_users;
	}
}//end if

if ( ! function_exists( 'aprio_rest_get_current_user_id' ) ) {
	/**
	 * This function is used to check user is exist or not.
	 *
	 * @since 1.0.1
	 */
	function aprio_rest_get_current_user_id() {
		// Get the authorization header
		$headers = getallheaders();
		$token   = '';

		// First try standard header
		if ( isset( $headers['Authorization'] ) ) {
			$token = trim( str_replace( 'Bearer', '', $headers['Authorization'] ) );
		}
		// Try for some server environments
		elseif ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$token = trim( str_replace( 'Bearer', '', $_SERVER['HTTP_AUTHORIZATION'] ) );
		}
		// NGINX or fastcgi_pass may use this
		elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$token = trim( str_replace( 'Bearer', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
		}
		if ( empty( $token ) ) {
			return APRIO_REST_CRUD_Controller::prepare_error_for_response( 401 );
		}

		$user_data = APRIO_REST_CRUD_Controller::aprio_validate_jwt_token( $token );
		if ( ! $user_data ) {
			return APRIO_REST_CRUD_Controller::prepare_error_for_response( 405 );
		}

		$user_id = $user_data['id'];
		return $user_id;
	}
}//end if

if ( ! function_exists( 'check_aprio_plugin_activation' ) ) {
	/**
	 * This function is used to check perticular plugin license activated or not.
	 *
	 * @param $post Event instance.
	 * @param string $context Request context.
	 * @return array
	 */
	function check_aprio_plugin_activation( $plugin_domain ) {
		if ( ! is_plugin_active( $plugin_domain . '/' . $plugin_domain . '.php' ) ) {
			return APRIO_REST_CRUD_Controller::prepare_error_for_response( 203 );
		} else {
			$licence_activate = get_option( $plugin_domain . '_licence_key' );

			if ( ! empty( $licence_activate ) ) {
				$license_status = check_aprio_license_expire_date( $licence_activate );

				if ( $license_status ) {
					return true;
				} else {
					return APRIO_REST_CRUD_Controller::prepare_error_for_response( 203 );
				}
			} else {
				return APRIO_REST_CRUD_Controller::prepare_error_for_response( 203 );
			}
		}
	}
}//end if

/**
 * This function will used to generate base64url_encode
 *
 * @since 1.0.9
 */
function aprio_base64url_encode( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

function aprio_base64url_decode( $data ) {
	$remainder = strlen( $data ) % 4;
	if ( $remainder ) {
		$padlen = 4 - $remainder;
		$data  .= str_repeat( '=', $padlen );
	}
	return base64_decode( strtr( $data, '-_', '+/' ) );
}
