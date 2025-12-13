<?php
// phpcs:ignoreFile
defined( 'ABSPATH' ) || exit;

class APRIO_REST_Attendee_Settings_Controller extends APRIO_REST_CRUD_Controller {
	protected $namespace = 'aprio';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 10 );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 1.1.0
	 */
	public function register_routes() {
		$auth_controller = new APRIO_REST_Authentication();

		// GET - Retrieve settings
		register_rest_route(
			$this->namespace,
			'/compatibilidade-attendee-settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_compatibilidade_attendee_settings' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
				'args'                => array(
					'user_id'  => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'event_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST - Update settings
		register_rest_route(
			$this->namespace,
			'/ajustar-atual-compatibilidade',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_compatibilidade_attendee_settings' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
				'args'                => array(
					'user_id'              => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'enable_compatibilidade'   => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'message_notification' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'meeting_request_mode' => array(
						'required'          => false,
						'type'              => 'string',
						'enum'              => array( 'approval', 'auto_accept' ),
						'sanitize_callback' => 'sanitize_key',
					),
					'event_participation'  => array(
						'required'          => false,
						'type'              => 'array',
						'sanitize_callback' => function( $arr ) {
							if ( ! is_array( $arr ) ) {
								return array();
							}
							return array_map( function( $item ) {
								return array(
									'event_id'           => isset( $item['event_id'] ) ? absint( $item['event_id'] ) : 0,
									'create_compatibilidade' => isset( $item['create_compatibilidade'] ) ? (bool) $item['create_compatibilidade'] : false,
								);
							}, $arr );
						},
					),
				),
			)
		);
	}

	/**
	 * Check user permission for settings endpoints.
	 *
	 * @return bool|WP_Error True if user is logged in, WP_Error otherwise.
	 */
	public function check_user_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to manage settings.', 'apollo-events-manager' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Retrieve attendee settings for a given user, including:
	 * - `enable_compatibilidade` (int): Whether compatibilidade is enabled for this user.
	 * - `message_notification` (int): Whether this user wants to receive notifications for new messages.
	 * - `event_participation` (array of objects): Array of objects with `event_id` and `create_compatibilidade` properties,
	 *   indicating whether the user has enabled compatibilidade for each event they are registered for.
	 * - `meeting_request_mode` (string): The meeting request mode for this user, either 'approval' or 'auto_accept'.
	 *
	 * @param WP_REST_Request $request The request that was sent to the API.
	 * @return WP_REST_Response Response object that contains the settings.
	 * @since 1.1.0
	 */
	public function get_compatibilidade_attendee_settings( $request ) {
		if ( ! get_option( 'enable_compatibilidade', false ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 403,
					'status'  => 'Disabled',
					'message' => 'Matchmaking functionality is not enabled.',
				),
				403
			);
		}
		$auth_check = $this->aprio_check_authorized_user();
		if ( $auth_check ) {
			return self::prepare_error_for_response( 405 );
		} else {
			$user_id  = $request->get_param( 'user_id' ) ?: get_current_user_id();
			$event_id = (int) $request->get_param( 'event_id' );

			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return new WP_REST_Response(
					array(
						'code'    => 404,
						'status'  => 'Not Found',
						'message' => 'User not found.',
						'data'    => null,
					),
					404
				);
			}

			$user_event_participation = array();

			if ( $event_id ) {
				// Get registrations for specific event
				$registration_post_ids = get_posts(
					array(
						'post_type'      => 'event_registration',
						'posts_per_page' => -1,
						'post_status'    => 'any',
						'author'         => $user_id,
						'post_parent'    => $event_id,
						'fields'         => 'ids',
					)
				);

				if ( ! empty( $registration_post_ids ) ) {
					$create_compatibilidade         = (int) get_post_meta( $registration_post_ids[0], '_create_compatibilidade', true );
					$user_event_participation[] = array(
						'event_id'           => $event_id,
						'create_compatibilidade' => $create_compatibilidade,
					);
				}
			} else {
				// Get all registrations for this user
				$user_registrations = get_posts(
					array(
						'post_type'      => 'event_registration',
						'posts_per_page' => -1,
						'post_status'    => 'any',
						'author'         => $user_id,
						'fields'         => 'ids',
					)
				);

				foreach ( $user_registrations as $registration_id ) {
					$parent_event_id = (int) get_post_field( 'post_parent', $registration_id );
					if ( ! $parent_event_id ) {
						continue;
					}
					$create_compatibilidade         = (int) get_post_meta( $registration_id, '_create_compatibilidade', true );
					$user_event_participation[] = array(
						'event_id'           => $parent_event_id,
						'create_compatibilidade' => $create_compatibilidade,
					);
				}
			}//end if

			$settings = array(
				'enable_compatibilidade'   => (int) get_user_meta( $user_id, '_compatibilidade_profile', true )[0],
				'message_notification' => (int) get_user_meta( $user_id, '_message_notification', true ),
				'event_participation'  => $user_event_participation,
				'meeting_request_mode' => get_user_meta( $user_id, '_aprio_meeting_request_mode', true ) ?: 'approval',
			);

			return new WP_REST_Response(
				array(
					'code'    => 200,
					'status'  => 'OK',
					'message' => 'Settings retrieved successfully.',
					'data'    => $settings,
				),
				200
			);
		}//end if
	}

	/**
	 * Update compatibilidade attendee settings.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 * @since 1.1.0
	 */
	public function update_compatibilidade_attendee_settings( $request ) {
		if ( ! get_option( 'enable_compatibilidade', false ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 403,
					'status'  => 'Disabled',
					'message' => 'Matchmaking functionality is not enabled.',
				),
				403
			);
		}
		$auth_check = $this->aprio_check_authorized_user();
		if ( $auth_check ) {
			return self::prepare_error_for_response( 405 );
		} else {
			$user_id = $request->get_param( 'user_id' ) ?: get_current_user_id();
			$user    = get_user_by( 'id', $user_id );

			if ( ! $user ) {
				return new WP_REST_Response(
					array(
						'code'    => 404,
						'status'  => 'Not Found',
						'message' => 'User not found.',
						'data'    => null,
					),
					404
				);
			}

			// Update user meta
			if ( ! is_null( $request->get_param( 'enable_compatibilidade' ) ) ) {
				update_user_meta( $user_id, '_compatibilidade_profile', (int) $request->get_param( 'enable_compatibilidade' ) );
			}
			if ( ! is_null( $request->get_param( 'message_notification' ) ) ) {
				update_user_meta( $user_id, '_message_notification', (int) $request->get_param( 'message_notification' ) );
			}
			if ( ! is_null( $request->get_param( 'meeting_request_mode' ) ) ) {
				update_user_meta( $user_id, '_aprio_meeting_request_mode', sanitize_text_field( $request->get_param( 'meeting_request_mode' ) ) );
			}

			// Update event participation settings
			$event_participation = $request->get_param( 'event_participation' );
			if ( is_array( $event_participation ) ) {
				foreach ( $event_participation as $event ) {
					if ( ! isset( $event['event_id'] ) ) {
						continue;
					}
					$eid   = (int) $event['event_id'];
					$value = isset( $event['create_compatibilidade'] ) ? (int) $event['create_compatibilidade'] : 0;

					$registration_post_ids = get_posts(
						array(
							'post_type'      => 'event_registration',
							'posts_per_page' => -1,
							'post_status'    => 'any',
							'author'         => $user_id,
							'post_parent'    => $eid,
							'fields'         => 'ids',
						)
					);

					foreach ( $registration_post_ids as $registration_post_id ) {
						update_post_meta( $registration_post_id, '_create_compatibilidade', $value );
					}
				}//end foreach
			}//end if

			return new WP_REST_Response(
				array(
					'code'    => 200,
					'status'  => 'OK',
					'message' => 'Settings updated successfully.',
				),
				200
			);
		}//end if
	}
}

new APRIO_REST_Attendee_Settings_Controller();
