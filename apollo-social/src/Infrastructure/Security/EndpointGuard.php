<?php
/**
 * Apollo Endpoint Guard
 *
 * Blocks sensitive REST and AJAX endpoints for disabled or incomplete modules.
 *
 * FASE 0: Proteção de endpoints sensíveis:
 * 1. Bloqueia REST endpoints de módulos desativados
 * 2. Bloqueia AJAX handlers de módulos incompletos
 * 3. Retorna 403 com mensagem apropriada
 * 4. Loga tentativas de acesso bloqueado
 *
 * @package Apollo\Infrastructure\Security
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Infrastructure\Security;

use Apollo\Infrastructure\FeatureFlags;
use Apollo\Infrastructure\ApolloLogger;
use WP_Error;
use WP_REST_Request;

/**
 * Endpoint Guard - Blocks access to disabled module endpoints
 */
class EndpointGuard {

	/** @var bool Whether guard is initialized */
	private static bool $initialized = false;

	/** @var array Blocked REST namespaces by feature */
	private const BLOCKED_REST_NAMESPACES = array(
		'chat'          => array( 'apollo/v1/chat', 'apollo/v1/messages' ),
		'notifications' => array( 'apollo/v1/notifications', 'apollo/v1/alerts' ),
		'groups'        => array( 'apollo/v1/groups' ),
		'govbr'         => array( 'apollo/v1/govbr', 'apollo/v1/sso' ),
		'pwa'           => array( 'apollo/v1/pwa', 'apollo/v1/push' ),
	);

	/** @var array Blocked AJAX actions by feature */
	private const BLOCKED_AJAX_ACTIONS = array(
		'chat'          => array(
			'apollo_send_message',
			'apollo_get_messages',
			'apollo_mark_read',
			'apollo_chat_history',
		),
		'notifications' => array(
			'apollo_get_notifications',
			'apollo_mark_notification_read',
			'apollo_clear_notifications',
		),
		'groups'        => array(
			'apollo_create_group',
			'apollo_join_group',
			'apollo_leave_group',
			'apollo_group_settings',
		),
		'govbr'         => array(
			'apollo_govbr_login',
			'apollo_govbr_callback',
			'apollo_sso_auth',
		),
	);

	/**
	 * Initialize the endpoint guard
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Guard REST API
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'guardRestEndpoints' ), 1, 3 );

		// Guard AJAX endpoints
		add_action( 'admin_init', array( __CLASS__, 'registerAjaxGuards' ), 1 );
		add_action( 'init', array( __CLASS__, 'registerAjaxGuards' ), 1 );

		self::$initialized = true;
	}

	/**
	 * Guard REST API endpoints
	 *
	 * @param mixed           $result  Pre-dispatch result.
	 * @param \WP_REST_Server $server  REST server.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed|WP_Error Result or error.
	 */
	public static function guardRestEndpoints( $result, $server, WP_REST_Request $request ) {
		// If already blocked, pass through
		if ( $result !== null ) {
			return $result;
		}

		$route = $request->get_route();

		foreach ( self::BLOCKED_REST_NAMESPACES as $feature => $namespaces ) {
			// Skip if feature is enabled
			if ( FeatureFlags::isEnabled( $feature ) ) {
				continue;
			}

			foreach ( $namespaces as $namespace ) {
				if ( strpos( $route, '/' . $namespace ) === 0 ) {
					// Log blocked access
					self::logBlockedAccess( 'rest', $feature, $route );

					return new WP_Error(
						'apollo_feature_disabled',
						sprintf(
							/* translators: %s: feature name */
							__( 'O recurso "%s" não está disponível no momento.', 'apollo-social' ),
							$feature
						),
						array( 'status' => 403 )
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Register AJAX guards for blocked actions
	 */
	public static function registerAjaxGuards(): void {
		foreach ( self::BLOCKED_AJAX_ACTIONS as $feature => $actions ) {
			// Skip if feature is enabled
			if ( FeatureFlags::isEnabled( $feature ) ) {
				continue;
			}

			foreach ( $actions as $action ) {
				// Register with very high priority (1) to run before real handlers
				add_action( 'wp_ajax_' . $action, array( __CLASS__, 'blockAjaxAction' ), 1 );
				add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, 'blockAjaxAction' ), 1 );
			}
		}
	}

	/**
	 * Block AJAX action with 403
	 */
	public static function blockAjaxAction(): void {
		$action  = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'unknown';
		$feature = self::getFeatureForAjaxAction( $action );

		// Log blocked access
		self::logBlockedAccess( 'ajax', $feature, $action );

		wp_send_json_error(
			array(
				'code'    => 'feature_disabled',
				'message' => sprintf(
					/* translators: %s: feature name */
					__( 'O recurso "%s" não está disponível no momento.', 'apollo-social' ),
					$feature
				),
			),
			403
		);
	}

	/**
	 * Get feature name for AJAX action
	 *
	 * @param string $action AJAX action.
	 * @return string Feature name.
	 */
	private static function getFeatureForAjaxAction( string $action ): string {
		foreach ( self::BLOCKED_AJAX_ACTIONS as $feature => $actions ) {
			if ( in_array( $action, $actions, true ) ) {
				return $feature;
			}
		}
		return 'unknown';
	}

	/**
	 * Log blocked access attempt
	 *
	 * @param string $type    Type of endpoint (rest/ajax).
	 * @param string $feature Feature that was blocked.
	 * @param string $route   Route or action.
	 */
	private static function logBlockedAccess( string $type, string $feature, string $route ): void {
		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::logBlockedAccess(
				get_current_user_id(),
				$type . '_endpoint_blocked',
				array(
					'feature' => $feature,
					'route'   => $route,
					'ip'      => self::getClientIp(),
				)
			);
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string IP address.
	 */
	private static function getClientIp(): string {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Check if an endpoint should be blocked
	 *
	 * @param string $type   Type (rest/ajax).
	 * @param string $target Route or action name.
	 * @return bool True if should be blocked.
	 */
	public static function isBlocked( string $type, string $target ): bool {
		if ( $type === 'rest' ) {
			foreach ( self::BLOCKED_REST_NAMESPACES as $feature => $namespaces ) {
				if ( FeatureFlags::isEnabled( $feature ) ) {
					continue;
				}

				foreach ( $namespaces as $namespace ) {
					if ( strpos( $target, $namespace ) !== false ) {
						return true;
					}
				}
			}
		} elseif ( $type === 'ajax' ) {
			foreach ( self::BLOCKED_AJAX_ACTIONS as $feature => $actions ) {
				if ( FeatureFlags::isEnabled( $feature ) ) {
					continue;
				}

				if ( in_array( $target, $actions, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get list of currently blocked endpoints
	 *
	 * @return array Blocked endpoints by type.
	 */
	public static function getBlockedEndpoints(): array {
		$blocked = array(
			'rest' => array(),
			'ajax' => array(),
		);

		foreach ( self::BLOCKED_REST_NAMESPACES as $feature => $namespaces ) {
			if ( ! FeatureFlags::isEnabled( $feature ) ) {
				$blocked['rest'][ $feature ] = $namespaces;
			}
		}

		foreach ( self::BLOCKED_AJAX_ACTIONS as $feature => $actions ) {
			if ( ! FeatureFlags::isEnabled( $feature ) ) {
				$blocked['ajax'][ $feature ] = $actions;
			}
		}

		return $blocked;
	}
}
