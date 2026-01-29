<?php
/**
 * Moderation REST API
 *
 * NOTE: Some routes in this file are DEPRECATED and forward to Apollo_User_Moderation.
 * - mod/suspender-user → Use mod/suspender instead (class-apollo-user-moderation.php)
 * - mod/bloquear-user → Use mod/ban instead (class-apollo-user-moderation.php)
 *
 * Unique routes in this file (NOT duplicated):
 * - mod/aprovar (post content)
 * - mod/negar (post content)
 * - mod/fila (get queue)
 * - mod/notificar (send notification)
 *
 * @package    Apollo_Core
 * @since      1.0.0
 *
 * @deprecated 3.0.0 User mod routes moved to class-apollo-user-mod.php
 *
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Legacy file name.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API class
 */
class Apollo_Moderation_REST_API {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'apollo_core_register_rest_routes', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 *
	 * NOTE: User suspend/bloquear routes are NOT registered here anymore.
	 * They are handled by Apollo_User_Moderation::register_rest_routes()
	 * which registers mod/suspender, mod/unsuspend, mod/ban
	 */
	public static function register_routes() {
		$namespace = \Apollo_Core\Rest_Bootstrap::get_namespace();

		// Approve/publish post - UNIQUE to this file.
		register_rest_route(
			$namespace,
			'mod/aprovar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'approve_post' ),
				'permission_callback' => array( __CLASS__, 'permission_moderate' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'note'    => array(
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Reject post - UNIQUE to this file.
		register_rest_route(
			$namespace,
			'mod/negar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'reject_post' ),
				'permission_callback' => array( __CLASS__, 'permission_moderate' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'note'    => array(
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Get mod queue - UNIQUE to this file.
		register_rest_route(
			$namespace,
			'mod/fila',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_queue' ),
				'permission_callback' => array( __CLASS__, 'permission_view_queue' ),
			)
		);

		// NOTE: mod/suspender-user and mod/bloquear-user REMOVED.
		// These are now handled by Apollo_User_Moderation with routes:.
		// - POST mod/suspender (suspend user).
		// - POST mod/unsuspend (unsuspend user).
		// - POST mod/ban (block/ban user).
		// - GET mod/user-status/{user_id} (get user status).

		// Send notification to user - UNIQUE to this file.
		register_rest_route(
			$namespace,
			'mod/notificar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'notify_user' ),
				'permission_callback' => array( __CLASS__, 'permission_notify' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'message' => array(
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);
	}

	/**
	 * Approve/publish post
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function approve_post( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$note    = $request->get_param( 'note' );

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found or not in draft/pending status.', 'apollo-core' ),
				array( 'status' => 400 )
			);
		}

		// Check if user can moderate this content type.
		if ( ! Apollo_Moderation_Roles::can_moderate_content_type( get_current_user_id(), $post->post_type ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to moderate this content type.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		// Publish post.
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Log action.
		Apollo_Moderation_Audit_Log::log_action(
			get_current_user_id(),
			'approve_publish',
			$post->post_type,
			$post_id,
			array( 'note' => $note )
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'post_id' => $post_id,
				'message' => __( 'Post approved and published successfully.', 'apollo-core' ),
			),
			200
		);
	}

	/**
	 * Reject post
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function reject_post( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$note    = $request->get_param( 'note' );

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found or not in draft/pending status.', 'apollo-core' ),
				array( 'status' => 400 )
			);
		}

		// Check if user can moderate this content type.
		if ( ! Apollo_Moderation_Roles::can_moderate_content_type( get_current_user_id(), $post->post_type ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to moderate this content type.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		// Add rejection note as post meta.
		update_post_meta( $post_id, '_apollo_rejection_note', $note );

		// Log action.
		Apollo_Moderation_Audit_Log::log_action(
			get_current_user_id(),
			'reject_post',
			$post->post_type,
			$post_id,
			array( 'note' => $note )
		);

		// Optionally trash or set to draft.
		wp_trash_post( $post_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'post_id' => $post_id,
				'message' => __( 'Post rejected successfully.', 'apollo-core' ),
			),
			200
		);
	}

	/**
	 * Get mod queue
	 *
	 * @param WP_REST_Request $request Request object (unused, required by REST API callback signature).
	 * @return WP_REST_Response
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function get_queue( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by REST API callback signature.
		$content_types = Apollo_Moderation_Roles::get_enabled_content_types();

		if ( empty( $content_types ) ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(),
					'total'   => 0,
				),
				200
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => $content_types,
				'post_status'    => array( 'draft', 'pending' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$queue = array();
		foreach ( $query->posts as $post ) {
			$queue[] = array(
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'type'      => $post->post_type,
				'status'    => $post->post_status,
				'author'    => array(
					'id'   => $post->post_author,
					'name' => get_the_author_meta( 'display_name', $post->post_author ),
				),
				'date'      => $post->post_date,
				'edit_link' => get_edit_post_link( $post->ID, 'raw' ),
				'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $queue,
				'total'   => $query->found_posts,
			),
			200
		);
	}

	/**
	 * Suspend user
	 *
	 * @deprecated 3.0.0 Use Apollo_User_Moderation::rest_suspend_user() via mod/suspender.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function suspend_user( $request ) {
		// Forward to Apollo_User_Moderation if available.
		if ( class_exists( 'Apollo_User_Moderation' ) && method_exists( 'Apollo_User_Moderation', 'rest_suspend_user' ) ) {
			/* @var callable $callback Callback to forward request. */
			$callback = array( 'Apollo_User_Moderation', 'rest_suspend_user' );
			return call_user_func( $callback, $request );
		}

		// Fallback to legacy implementation.
		$user_id = $request->get_param( 'user_id' );
		$days    = $request->get_param( 'days' );
		$reason  = $request->get_param( 'reason' );

		$success = Apollo_Moderation_Suspension::suspend_user( $user_id, $days, $reason );

		if ( ! $success ) {
			return new WP_Error(
				'suspension_failed',
				__( 'Failed to suspend user.', 'apollo-core' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of days */
					__( 'User suspended for %d days.', 'apollo-core' ),
					$days
				),
			),
			200
		);
	}

	/**
	 * Block user
	 *
	 * @deprecated 3.0.0 Use Apollo_User_Moderation::rest_ban_user() via /mod/ban.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function block_user( $request ) {
		// Forward to Apollo_User_Moderation if available.
		if ( class_exists( 'Apollo_User_Moderation' ) && method_exists( 'Apollo_User_Moderation', 'rest_ban_user' ) ) {
			/* @var callable $callback Callback to forward request. */
			$callback = array( 'Apollo_User_Moderation', 'rest_ban_user' );
			return call_user_func( $callback, $request );
		}

		// Fallback to legacy implementation.
		$user_id = $request->get_param( 'user_id' );
		$reason  = $request->get_param( 'reason' );

		$success = Apollo_Moderation_Suspension::block_user( $user_id, $reason );

		if ( ! $success ) {
			return new WP_Error(
				'block_failed',
				__( 'Failed to block user.', 'apollo-core' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'User blocked successfully.', 'apollo-core' ),
			),
			200
		);
	}

	/**
	 * Send notification to user
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function notify_user( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$message = $request->get_param( 'message' );

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'invalid_user',
				__( 'User not found.', 'apollo-core' ),
				array( 'status' => 404 )
			);
		}

		// Send email.
		$subject = __( 'Notification from Apollo Moderation', 'apollo-core' );
		$sent    = wp_mail( $user->user_email, $subject, $message );

		// Log action.
		Apollo_Moderation_Audit_Log::log_action(
			get_current_user_id(),
			'send_notification',
			'user',
			$user_id,
			array( 'message' => $message )
		);

		return new WP_REST_Response(
			array(
				'success' => $sent,
				'message' => $sent ? __( 'Notification sent successfully.', 'apollo-core' ) : __( 'Failed to send notification.', 'apollo-core' ),
			),
			$sent ? 200 : 500
		);
	}

	/**
	 * Permission callback: can moderate
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_moderate() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'moderate_apollo_content' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability registered by Apollo_Moderation_Roles.
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to moderate content.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback: can view queue
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_view_queue() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'view_mod_queue' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability registered by Apollo_Moderation_Roles.
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view the mod queue.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback: can suspend
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_suspend() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'suspend_users' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability registered by Apollo_Moderation_Roles.
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to suspend users.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback: can block
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_block() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'block_users' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability registered by Apollo_Moderation_Roles.
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to block users.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback: can notify
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_notify() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'send_user_notifications' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability registered by Apollo_Moderation_Roles.
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to send notifications.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
