<?php

/**
 * Apollo User Moderation System
 *
 * Centraliza meta apollo_status, suspensões, banimentos, níveis de moderador
 * e controle de IP blocklist. Integra com audit log existente.
 *
 * FASE 1 do plano de modularização Apollo.
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo User Moderation class
 *
 * Meta keys utilizadas:
 * - apollo_status: active, suspended, banned
 * - apollo_suspension_until: timestamp UTC de fim da suspensão
 * - apollo_suspension_reason: motivo da suspensão
 * - apollo_banned_at: timestamp do banimento
 * - apollo_banned_reason: motivo do banimento
 * - apollo_mod_level: 0, 1, ou 3 (níveis de moderador)
 */
class Apollo_User_Moderation {

	/**
	 * User status constants
	 */
	public const STATUS_ACTIVE    = 'active';
	public const STATUS_SUSPENDED = 'suspended';
	public const STATUS_BANNED    = 'banned';

	/**
	 * Moderator level constants
	 * MOD 0: Básico (visualizar fila, aprovar conteúdo)
	 * MOD 1: Avançado (editar/remover conteúdo, suspensão temporária)
	 * MOD 3: Pleno (suspender/banir usuários, acesso total exceto IP)
	 */
	public const MOD_LEVEL_NONE     = -1;
	public const MOD_LEVEL_BASIC    = 0;
	public const MOD_LEVEL_ADVANCED = 1;
	public const MOD_LEVEL_FULL     = 3;

	/**
	 * Capabilities by mod level
	 */
	private static array $mod_capabilities = array(
		0 => array(
			'apollo_moderate_basic',
			'apollo_view_mod_queue',
			'apollo_approve_content',
		),
		1 => array(
			'apollo_moderate_basic',
			'apollo_moderate_advanced',
			'apollo_view_mod_queue',
			'apollo_approve_content',
			'apollo_edit_user_content',
			'apollo_remove_content',
			'apollo_suspend_users_temp',
		),
		3 => array(
			'apollo_moderate_basic',
			'apollo_moderate_advanced',
			'apollo_moderate_full',
			'apollo_view_mod_queue',
			'apollo_approve_content',
			'apollo_edit_user_content',
			'apollo_remove_content',
			'apollo_suspend_users_temp',
			'apollo_suspend_users',
			'apollo_ban_users',
			'apollo_view_audit_log',
		),
	);

	/**
	 * Admin-only capabilities (never given to moderators)
	 */
	private static array $admin_only_capabilities = array(
		'apollo_block_ip',
		'apollo_manage_moderators',
		'apollo_manage_modules',
		'apollo_manage_limits',
		'apollo_view_analytics',
		'apollo_lockdown',
	);

	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Block suspended/banned users on every page load.
		add_action( 'init', array( __CLASS__, 'check_user_access' ), 1 );

		// Block IPs on init.
		add_action( 'init', array( __CLASS__, 'check_ip_blocklist' ), 0 );

		// Auto-unsuspend expired suspensions.
		add_action( 'init', array( __CLASS__, 'maybe_auto_unsuspend' ), 2 );

		// Add capabilities on role setup.
		add_action( 'admin_init', array( __CLASS__, 'setup_capabilities' ) );

		// REST API endpoints.
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		// Filter user authentication.
		add_filter( 'authenticate', array( __CLASS__, 'block_suspended_login' ), 100, 3 );
	}

	// =========================================================================
	// USER STATUS FUNCTIONS
	// =========================================================================

	/**
	 * Get user status
	 *
	 * @param int $user_id User ID.
	 * @return string Status: active, suspended, or banned.
	 */
	public static function get_user_status( int $user_id ): string {
		$status = get_user_meta( $user_id, 'apollo_status', true );
		return in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_BANNED ), true )
			? $status
			: self::STATUS_ACTIVE;
	}

	/**
	 * Set user status
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Status to set.
	 * @return bool Success.
	 */
	public static function set_user_status( int $user_id, string $status ): bool {
		if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_BANNED ), true ) ) {
			return false;
		}
		return (bool) update_user_meta( $user_id, 'apollo_status', $status );
	}

	/**
	 * Check if user is suspended
	 *
	 * @param int $user_id User ID.
	 * @return bool True if suspended.
	 */
	public static function is_user_suspended( int $user_id ): bool {
		$status = self::get_user_status( $user_id );
		if ( self::STATUS_SUSPENDED !== $status ) {
			return false;
		}

		// Check if suspension has expired.
		$until = (int) get_user_meta( $user_id, 'apollo_suspension_until', true );
		if ( $until > 0 && $until < time() ) {
			// Auto-unsuspend.
			self::unsuspend_user( $user_id, 0, 'Suspensão expirada automaticamente' );
			return false;
		}

		return true;
	}

	/**
	 * Check if user is banned
	 *
	 * @param int $user_id User ID.
	 * @return bool True if banned.
	 */
	public static function is_user_banned( int $user_id ): bool {
		return self::STATUS_BANNED === self::get_user_status( $user_id );
	}

	/**
	 * Check if user can perform actions (not suspended/banned)
	 *
	 * @param int    $user_id    User ID.
	 * @param string $capability Optional specific capability to check.
	 * @return bool True if user can perform actions.
	 */
	public static function can_user_perform( int $user_id, string $capability = '' ): bool {
		// Banned users can never do anything.
		if ( self::is_user_banned( $user_id ) ) {
			return false;
		}

		// Suspended users can only read.
		if ( self::is_user_suspended( $user_id ) ) {
			return false;
		}

		// Check specific capability if provided.
		if ( ! empty( $capability ) ) {
			return user_can( $user_id, $capability );
		}

		return true;
	}

	// =========================================================================
	// SUSPENSION FUNCTIONS
	// =========================================================================

	/**
	 * Suspend a user
	 *
	 * @param int    $user_id     User ID to suspend.
	 * @param int    $actor_id    ID of moderator performing action.
	 * @param string $reason      Reason for suspension.
	 * @param int    $duration    Duration in seconds (0 = indefinite until manual unsuspend).
	 * @return bool Success.
	 */
	public static function suspend_user( int $user_id, int $actor_id, string $reason = '', int $duration = 0 ): bool {
		// Cannot suspend admins.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		// Check actor permission.
		if ( $actor_id > 0 && ! self::can_moderate( $actor_id, 'suspend' ) ) {
			return false;
		}

		// Set status.
		self::set_user_status( $user_id, self::STATUS_SUSPENDED );

		// Set suspension details.
		update_user_meta( $user_id, 'apollo_suspension_reason', sanitize_text_field( $reason ) );
		update_user_meta( $user_id, 'apollo_suspended_at', time() );
		update_user_meta( $user_id, 'apollo_suspended_by', $actor_id );

		if ( $duration > 0 ) {
			update_user_meta( $user_id, 'apollo_suspension_until', time() + $duration );
		} else {
			delete_user_meta( $user_id, 'apollo_suspension_until' );
		}

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'suspend_user',
				'user',
				$user_id,
				array(
					'reason'   => $reason,
					'duration' => $duration,
					'until'    => $duration > 0 ? gmdate( 'Y-m-d H:i:s', time() + $duration ) : 'indefinite',
				)
			);
		}

		// Trigger hook.
		do_action( 'apollo_user_suspended', $user_id, $actor_id, $reason, $duration );

		return true;
	}

	/**
	 * Unsuspend a user
	 *
	 * @param int    $user_id  User ID to unsuspend.
	 * @param int    $actor_id ID of moderator performing action.
	 * @param string $reason   Reason for unsuspension.
	 * @return bool Success.
	 */
	public static function unsuspend_user( int $user_id, int $actor_id = 0, string $reason = '' ): bool {
		if ( self::STATUS_SUSPENDED !== self::get_user_status( $user_id ) ) {
			return false;
		}

		// Set status back to active.
		self::set_user_status( $user_id, self::STATUS_ACTIVE );

		// Clean up suspension meta.
		delete_user_meta( $user_id, 'apollo_suspension_reason' );
		delete_user_meta( $user_id, 'apollo_suspension_until' );
		delete_user_meta( $user_id, 'apollo_suspended_at' );
		delete_user_meta( $user_id, 'apollo_suspended_by' );

		// Log action.
		if ( $actor_id > 0 && function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'unsuspend_user',
				'user',
				$user_id,
				array( 'reason' => $reason )
			);
		}

		// Trigger hook.
		do_action( 'apollo_user_unsuspended', $user_id, $actor_id, $reason );

		return true;
	}

	/**
	 * Auto-unsuspend expired suspensions (called on init)
	 */
	public static function maybe_auto_unsuspend(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$status = get_user_meta( $user_id, 'apollo_status', true );
		if ( self::STATUS_SUSPENDED !== $status ) {
			return;
		}

		$until = (int) get_user_meta( $user_id, 'apollo_suspension_until', true );
		if ( $until > 0 && $until < time() ) {
			self::unsuspend_user( $user_id, 0, 'Suspensão expirada automaticamente' );
		}
	}

	// =========================================================================
	// BAN FUNCTIONS
	// =========================================================================

	/**
	 * Ban a user permanently
	 *
	 * @param int    $user_id  User ID to ban.
	 * @param int    $actor_id ID of moderator performing action.
	 * @param string $reason   Reason for ban.
	 * @return bool Success.
	 */
	public static function ban_user( int $user_id, int $actor_id, string $reason = '' ): bool {
		// Cannot ban admins.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		// Check actor permission.
		if ( $actor_id > 0 && ! self::can_moderate( $actor_id, 'ban' ) ) {
			return false;
		}

		// Set status.
		self::set_user_status( $user_id, self::STATUS_BANNED );

		// Set ban details.
		update_user_meta( $user_id, 'apollo_banned_reason', sanitize_text_field( $reason ) );
		update_user_meta( $user_id, 'apollo_banned_at', time() );
		update_user_meta( $user_id, 'apollo_banned_by', $actor_id );

		// Clean up any suspension meta.
		delete_user_meta( $user_id, 'apollo_suspension_reason' );
		delete_user_meta( $user_id, 'apollo_suspension_until' );

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'ban_user',
				'user',
				$user_id,
				array( 'reason' => $reason )
			);
		}

		// Trigger hook.
		do_action( 'apollo_user_banned', $user_id, $actor_id, $reason );

		return true;
	}

	/**
	 * Unban a user (admin only)
	 *
	 * @param int    $user_id  User ID to unban.
	 * @param int    $actor_id ID of admin performing action.
	 * @param string $reason   Reason for unban.
	 * @return bool Success.
	 */
	public static function unban_user( int $user_id, int $actor_id, string $reason = '' ): bool {
		// Only admins can unban.
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		if ( self::STATUS_BANNED !== self::get_user_status( $user_id ) ) {
			return false;
		}

		// Set status back to active.
		self::set_user_status( $user_id, self::STATUS_ACTIVE );

		// Clean up ban meta.
		delete_user_meta( $user_id, 'apollo_banned_reason' );
		delete_user_meta( $user_id, 'apollo_banned_at' );
		delete_user_meta( $user_id, 'apollo_banned_by' );

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'unban_user',
				'user',
				$user_id,
				array( 'reason' => $reason )
			);
		}

		// Trigger hook.
		do_action( 'apollo_user_unbanned', $user_id, $actor_id, $reason );

		return true;
	}

	// =========================================================================
	// MODERATOR LEVEL FUNCTIONS
	// =========================================================================

	/**
	 * Get user's moderator level
	 *
	 * @param int $user_id User ID.
	 * @return int Mod level: -1 (none), 0, 1, or 3.
	 */
	public static function get_mod_level( int $user_id ): int {
		// Admins are always full level.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return self::MOD_LEVEL_FULL;
		}

		$level = (int) get_user_meta( $user_id, 'apollo_mod_level', true );

		if ( in_array( $level, array( self::MOD_LEVEL_BASIC, self::MOD_LEVEL_ADVANCED, self::MOD_LEVEL_FULL ), true ) ) {
			return $level;
		}

		return self::MOD_LEVEL_NONE;
	}

	/**
	 * Set user's moderator level
	 *
	 * @param int $user_id  User ID.
	 * @param int $level    Mod level: 0, 1, or 3.
	 * @param int $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function set_mod_level( int $user_id, int $level, int $actor_id ): bool {
		// Only admins can set mod levels.
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		// Validate level.
		$valid_levels = array(
			self::MOD_LEVEL_NONE,
			self::MOD_LEVEL_BASIC,
			self::MOD_LEVEL_ADVANCED,
			self::MOD_LEVEL_FULL,
		);
		if ( ! in_array( $level, $valid_levels, true ) ) {
			return false;
		}

		$old_level = self::get_mod_level( $user_id );

		if ( self::MOD_LEVEL_NONE === $level ) {
			delete_user_meta( $user_id, 'apollo_mod_level' );
		} else {
			update_user_meta( $user_id, 'apollo_mod_level', $level );
		}

		// Update capabilities.
		self::sync_user_capabilities( $user_id );

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'set_mod_level',
				'user',
				$user_id,
				array(
					'old_level' => $old_level,
					'new_level' => $level,
				)
			);
		}

		// Trigger hook.
		do_action( 'apollo_mod_level_changed', $user_id, $level, $old_level, $actor_id );

		return true;
	}

	/**
	 * Check if user can moderate at a specific action level
	 *
	 * @param int    $user_id User ID.
	 * @param string $action  Action type: view, approve, edit, remove, suspend, ban.
	 * @return bool True if can moderate.
	 */
	public static function can_moderate( int $user_id, string $action ): bool {
		$level = self::get_mod_level( $user_id );

		if ( self::MOD_LEVEL_NONE === $level ) {
			return false;
		}

		switch ( $action ) {
			case 'view':
			case 'approve':
				return $level >= self::MOD_LEVEL_BASIC;

			case 'edit':
			case 'remove':
			case 'suspend_temp':
				return $level >= self::MOD_LEVEL_ADVANCED;

			case 'suspend':
			case 'ban':
			case 'audit':
				return $level >= self::MOD_LEVEL_FULL;

			default:
				return false;
		}
	}

	/**
	 * Sync user capabilities based on mod level
	 *
	 * @param int $user_id User ID.
	 */
	public static function sync_user_capabilities( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$level = self::get_mod_level( $user_id );

		// Remove all mod capabilities first.
		foreach ( self::$mod_capabilities as $caps ) {
			foreach ( $caps as $cap ) {
				$user->remove_cap( $cap );
			}
		}

		// Add capabilities for current level.
		if ( $level >= 0 && isset( self::$mod_capabilities[ $level ] ) ) {
			foreach ( self::$mod_capabilities[ $level ] as $cap ) {
				$user->add_cap( $cap );
			}
		}
	}

	// =========================================================================
	// IP BLOCKLIST FUNCTIONS
	// =========================================================================

	/**
	 * Get IP blocklist
	 *
	 * @return array Array of blocked IP hashes.
	 */
	public static function get_ip_blocklist(): array {
		$list = get_option( 'apollo_ip_blocklist', array() );
		return is_array( $list ) ? $list : array();
	}

	/**
	 * Block an IP address (admin only)
	 *
	 * @param string $ip       IP address to block.
	 * @param int    $actor_id ID of admin performing action.
	 * @param string $reason   Reason for blocking.
	 * @return bool Success.
	 */
	public static function block_ip( string $ip, int $actor_id, string $reason = '' ): bool {
		// Only admins can block IPs.
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		if ( ! $ip ) {
			return false;
		}

		// Hash the IP for storage.
		$ip_hash = hash( 'sha256', $ip . wp_salt( 'auth' ) );

		$blocklist = self::get_ip_blocklist();

		// Check if already blocked.
		foreach ( $blocklist as $entry ) {
			if ( $entry['hash'] === $ip_hash ) {
				return false;
			}
		}

		$blocklist[] = array(
			'hash'       => $ip_hash,
			'blocked_at' => time(),
			'blocked_by' => $actor_id,
			'reason'     => sanitize_text_field( $reason ),
		);

		update_option( 'apollo_ip_blocklist', $blocklist );

		// Log action (with partial IP for reference).
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'block_ip',
				'ip',
				0,
				array(
					'ip_partial' => self::mask_ip( $ip ),
					'reason'     => $reason,
				)
			);
		}

		return true;
	}

	/**
	 * Unblock an IP address (admin only)
	 *
	 * @param string $ip_hash  Hashed IP to unblock.
	 * @param int    $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function unblock_ip( string $ip_hash, int $actor_id ): bool {
		// Only admins can unblock IPs.
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		$blocklist = self::get_ip_blocklist();
		$found     = false;

		foreach ( $blocklist as $key => $entry ) {
			if ( $entry['hash'] === $ip_hash ) {
				unset( $blocklist[ $key ] );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return false;
		}

		update_option( 'apollo_ip_blocklist', array_values( $blocklist ) );

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'unblock_ip',
				'ip',
				0,
				array( 'ip_hash' => substr( $ip_hash, 0, 12 ) . '...' )
			);
		}

		return true;
	}

	/**
	 * Check if current IP is blocked (called on init)
	 */
	public static function check_ip_blocklist(): void {
		// Skip for admins.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip for login page.
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			return;
		}

		$ip = self::get_client_ip();
		if ( ! $ip ) {
			return;
		}

		$ip_hash   = hash( 'sha256', $ip . wp_salt( 'auth' ) );
		$blocklist = self::get_ip_blocklist();

		foreach ( $blocklist as $entry ) {
			if ( $entry['hash'] === $ip_hash ) {
				$message = esc_html__(
					'Seu acesso foi bloqueado. Entre em contato com o suporte se acredita que isso é um erro.',
					'apollo-core'
				);
				wp_die(
					$message,
					esc_html__( 'Acesso Bloqueado', 'apollo-core' ),
					array( 'response' => 403 )
				);
			}
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string|null IP address or null.
	 */
	private static function get_client_ip(): ?string {
		$ip = null;

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : null;
	}

	/**
	 * Mask IP for logging (privacy)
	 *
	 * @param string $ip IP address.
	 * @return string Masked IP.
	 */
	private static function mask_ip( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts    = explode( '.', $ip );
			$parts[3] = 'xxx';
			return implode( '.', $parts );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return substr( $ip, 0, 19 ) . ':xxxx:xxxx';
		}

		return 'xxx.xxx.xxx.xxx';
	}

	// =========================================================================
	// ACCESS CONTROL HOOKS
	// =========================================================================

	/**
	 * Check user access on every page load
	 */
	public static function check_user_access(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Skip for admins.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = self::get_user_status( $user_id );

		if ( self::STATUS_BANNED === $status ) {
			wp_logout();
			$message = esc_html__(
				'Sua conta foi banida permanentemente. Entre em contato com o suporte para mais informações.',
				'apollo-core'
			);
			wp_die(
				$message,
				esc_html__( 'Conta Banida', 'apollo-core' ),
				array( 'response' => 403 )
			);
		}

		if ( self::STATUS_SUSPENDED === $status && self::is_user_suspended( $user_id ) ) {
			$until  = (int) get_user_meta( $user_id, 'apollo_suspension_until', true );
			$reason = get_user_meta( $user_id, 'apollo_suspension_reason', true );

			$message = __( 'Sua conta está suspensa.', 'apollo-core' );
			if ( $until > 0 ) {
				$message .= ' ' . sprintf(
					/* translators: %s: date/time */
					__( 'A suspensão termina em: %s', 'apollo-core' ),
					wp_date( 'd/m/Y H:i', $until )
				);
			}
			if ( $reason ) {
				$message .= '<br><br><strong>' . __( 'Motivo:', 'apollo-core' ) . '</strong> ' . esc_html( $reason );
			}

			wp_die(
				wp_kses_post( $message ),
				esc_html__( 'Conta Suspensa', 'apollo-core' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Block suspended/banned users from logging in
	 *
	 * @param WP_User|WP_Error|null $user     User object or error.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error
	 */
	public static function block_suspended_login( $user, $username, $password ) {
		if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
			return $user;
		}

		$status = self::get_user_status( $user->ID );

		if ( self::STATUS_BANNED === $status ) {
			return new WP_Error(
				'apollo_banned',
				__( 'Sua conta foi banida permanentemente.', 'apollo-core' )
			);
		}

		if ( self::STATUS_SUSPENDED === $status && self::is_user_suspended( $user->ID ) ) {
			$until = (int) get_user_meta( $user->ID, 'apollo_suspension_until', true );
			if ( $until > 0 ) {
				return new WP_Error(
					'apollo_suspended',
					sprintf(
						/* translators: %s: date/time */
						__( 'Sua conta está suspensa até %s.', 'apollo-core' ),
						wp_date( 'd/m/Y H:i', $until )
					)
				);
			}
			return new WP_Error(
				'apollo_suspended',
				__( 'Sua conta está suspensa.', 'apollo-core' )
			);
		}

		return $user;
	}

	// =========================================================================
	// CAPABILITIES SETUP
	// =========================================================================

	/**
	 * Setup admin capabilities (called on admin_init)
	 */
	public static function setup_capabilities(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}

		// Add all mod capabilities to admin.
		foreach ( self::$mod_capabilities as $caps ) {
			foreach ( $caps as $cap ) {
				if ( ! $admin->has_cap( $cap ) ) {
					$admin->add_cap( $cap );
				}
			}
		}

		// Add admin-only capabilities.
		foreach ( self::$admin_only_capabilities as $cap ) {
			if ( ! $admin->has_cap( $cap ) ) {
				$admin->add_cap( $cap );
			}
		}
	}

	// =========================================================================
	// REST API
	// =========================================================================

	/**
	 * Register REST routes
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'apollo/v1',
			'mod/suspender',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_suspend_user' ),
				'permission_callback' => function () {
					return self::can_moderate( get_current_user_id(), 'suspend' );
				},
				'args'                => array(
					'user_id'  => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'reason'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
					'duration' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
				),
			)
		);

		register_rest_route(
			'apollo/v1',
			'mod/unsuspend',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_unsuspend_user' ),
				'permission_callback' => function () {
					return self::can_moderate( get_current_user_id(), 'suspend' );
				},
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'reason'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			'apollo/v1',
			'mod/ban',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_ban_user' ),
				'permission_callback' => function () {
					return self::can_moderate( get_current_user_id(), 'ban' );
				},
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'reason'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			'apollo/v1',
			'mod/user-status/(?P<user_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_user_status' ),
				'permission_callback' => function () {
					return self::can_moderate( get_current_user_id(), 'view' );
				},
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * REST: Suspend user
	 */
	public static function rest_suspend_user( WP_REST_Request $request ): WP_REST_Response {
		$user_id  = $request->get_param( 'user_id' );
		$reason   = $request->get_param( 'reason' );
		$duration = $request->get_param( 'duration' );
		$actor_id = get_current_user_id();

		$success = self::suspend_user( $user_id, $actor_id, $reason, $duration );

		if ( ! $success ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Não foi possível suspender o usuário.', 'apollo-core' ),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Usuário suspenso com sucesso.', 'apollo-core' ),
				'status'  => self::get_user_status( $user_id ),
			),
			200
		);
	}

	/**
	 * REST: Unsuspend user
	 */
	public static function rest_unsuspend_user( WP_REST_Request $request ): WP_REST_Response {
		$user_id  = $request->get_param( 'user_id' );
		$reason   = $request->get_param( 'reason' );
		$actor_id = get_current_user_id();

		$success = self::unsuspend_user( $user_id, $actor_id, $reason );

		if ( ! $success ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Não foi possível reativar o usuário.', 'apollo-core' ),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Usuário reativado com sucesso.', 'apollo-core' ),
				'status'  => self::get_user_status( $user_id ),
			),
			200
		);
	}

	/**
	 * REST: Ban user
	 */
	public static function rest_ban_user( WP_REST_Request $request ): WP_REST_Response {
		$user_id  = $request->get_param( 'user_id' );
		$reason   = $request->get_param( 'reason' );
		$actor_id = get_current_user_id();

		$success = self::ban_user( $user_id, $actor_id, $reason );

		if ( ! $success ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Não foi possível banir o usuário.', 'apollo-core' ),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Usuário banido com sucesso.', 'apollo-core' ),
				'status'  => self::get_user_status( $user_id ),
			),
			200
		);
	}

	/**
	 * REST: Get user status
	 */
	public static function rest_get_user_status( WP_REST_Request $request ): WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Usuário não encontrado.', 'apollo-core' ),
				),
				404
			);
		}

		$status = self::get_user_status( $user_id );

		$data = array(
			'success'   => true,
			'user_id'   => $user_id,
			'status'    => $status,
			'mod_level' => self::get_mod_level( $user_id ),
		);

		if ( self::STATUS_SUSPENDED === $status ) {
			$data['suspension'] = array(
				'reason' => get_user_meta( $user_id, 'apollo_suspension_reason', true ),
				'until'  => get_user_meta( $user_id, 'apollo_suspension_until', true ),
				'at'     => get_user_meta( $user_id, 'apollo_suspended_at', true ),
				'by'     => get_user_meta( $user_id, 'apollo_suspended_by', true ),
			);
		}

		if ( self::STATUS_BANNED === $status ) {
			$data['ban'] = array(
				'reason' => get_user_meta( $user_id, 'apollo_banned_reason', true ),
				'at'     => get_user_meta( $user_id, 'apollo_banned_at', true ),
				'by'     => get_user_meta( $user_id, 'apollo_banned_by', true ),
			);
		}

		return new WP_REST_Response( $data, 200 );
	}
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Suspend a user
 *
 * @param int    $user_id  User ID to suspend.
 * @param int    $actor_id ID of moderator performing action.
 * @param string $reason   Reason for suspension.
 * @param int    $duration Duration in seconds (0 = indefinite).
 * @return bool Success.
 */
function apollo_suspend_user( int $user_id, int $actor_id, string $reason = '', int $duration = 0 ): bool {
	return Apollo_User_Moderation::suspend_user( $user_id, $actor_id, $reason, $duration );
}

/**
 * Unsuspend a user
 *
 * @param int    $user_id  User ID to unsuspend.
 * @param int    $actor_id ID of moderator performing action.
 * @param string $reason   Reason for unsuspension.
 * @return bool Success.
 */
function apollo_unsuspend_user( int $user_id, int $actor_id = 0, string $reason = '' ): bool {
	return Apollo_User_Moderation::unsuspend_user( $user_id, $actor_id, $reason );
}

/**
 * Ban a user permanently
 *
 * @param int    $user_id  User ID to ban.
 * @param int    $actor_id ID of moderator performing action.
 * @param string $reason   Reason for ban.
 * @return bool Success.
 */
function apollo_ban_user( int $user_id, int $actor_id, string $reason = '' ): bool {
	return Apollo_User_Moderation::ban_user( $user_id, $actor_id, $reason );
}

/**
 * Check if user is suspended
 *
 * @param int $user_id User ID.
 * @return bool True if suspended.
 */
function apollo_is_user_suspended( int $user_id ): bool {
	return Apollo_User_Moderation::is_user_suspended( $user_id );
}

/**
 * Check if user is banned
 *
 * @param int $user_id User ID.
 * @return bool True if banned.
 */
function apollo_is_user_banned( int $user_id ): bool {
	return Apollo_User_Moderation::is_user_banned( $user_id );
}

/**
 * Check if user can perform actions
 *
 * @param int    $user_id    User ID.
 * @param string $capability Optional specific capability.
 * @return bool True if user can perform actions.
 */
function apollo_can_user_perform( int $user_id, string $capability = '' ): bool {
	return Apollo_User_Moderation::can_user_perform( $user_id, $capability );
}

/**
 * Get user's moderator level
 *
 * @param int $user_id User ID.
 * @return int Mod level: -1 (none), 0, 1, or 3.
 */
function apollo_get_mod_level( int $user_id ): int {
	return Apollo_User_Moderation::get_mod_level( $user_id );
}

/**
 * Check if user can moderate at a specific action level
 *
 * @param int    $user_id User ID.
 * @param string $action  Action type.
 * @return bool True if can moderate.
 */
function apollo_can_moderate( int $user_id, string $action ): bool {
	return Apollo_User_Moderation::can_moderate( $user_id, $action );
}
