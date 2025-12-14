<?php

/**
 * Apollo Modules & Limits Configuration
 *
 * Centraliza ativação/desativação de módulos e limites globais por tipo.
 * Outras funcionalidades consultam este núcleo antes de criar recursos.
 *
 * FASE 2 do plano de modularização Apollo.
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Modules Config class
 *
 * Options utilizadas:
 * - apollo_modules: array de flags para cada módulo
 * - apollo_limits: array de limites por tipo de recurso
 */
class Apollo_Modules_Config {

	/**
	 * Available modules with defaults
	 */
	private static array $default_modules = array(
		'social'          => true,  // Feed social, posts, likes, comments.
		'events'          => true,  // Apollo Events Manager.
		'bolha'           => true,  // Sistema de bolha (15 pessoas).
		'chat'            => true,  // Mensagens diretas.
		'docs'            => true,  // Documentos e assinaturas.
		'comunas'         => true,  // Comunidades/grupos.
		'compatibilidade' => false, // Match entre usuários (futuro).
		'cena_rio'        => true,  // Submissões CENA::RIO.
		'classifieds'     => true,  // Classificados.
		'notifications'   => true, // Sistema de notificações.
		'onboarding'      => true,  // Trilha de onboarding.
		'achievements'    => false, // Conquistas privadas (futuro).
	);

	/**
	 * Default limits
	 */
	private static array $default_limits = array(
		// Events.
		'max_events_per_user_month'  => 10,
		'max_events_pending_review'  => 5,

		// Social.
		'max_social_posts_per_day'   => 20,
		'max_comments_per_hour'      => 50,

		// Bolha.
		'max_bubble_members'         => 15,
		'max_bubble_invites_per_day' => 10,

		// Comunas.
		'max_comunas_per_user'       => 5,
		'max_comuna_members'         => 500,

		// Chat.
		'max_dm_per_hour'            => 30,
		'max_dm_recipients_per_day'  => 20,

		// Docs.
		'max_docs_per_user'          => 50,
		'max_pending_signatures'     => 10,

		// Classifieds.
		'max_ads_per_user'           => 10,
		'max_ad_duration_days'       => 30,

		// General.
		'max_uploads_per_day'        => 50,
		'max_upload_size_mb'         => 10,
		'max_reports_per_day'        => 10,
	);

	/**
	 * Cached modules (loaded once per request)
	 */
	private static ?array $modules_cache = null;

	/**
	 * Cached limits (loaded once per request)
	 */
	private static ?array $limits_cache = null;

	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Nothing needed on init for now.
		// This class is primarily used as a service/helper.
	}

	// =========================================================================
	// MODULES
	// =========================================================================

	/**
	 * Get all modules with their status
	 *
	 * @return array Module name => enabled status.
	 */
	public static function get_modules(): array {
		if ( null !== self::$modules_cache ) {
			return self::$modules_cache;
		}

		$saved   = get_option( 'apollo_modules', array() );
		$modules = array_merge( self::$default_modules, is_array( $saved ) ? $saved : array() );

		self::$modules_cache = $modules;

		return $modules;
	}

	/**
	 * Check if a module is enabled
	 *
	 * @param string $module Module name.
	 * @return bool True if enabled.
	 */
	public static function is_module_enabled( string $module ): bool {
		$modules = self::get_modules();

		return ! empty( $modules[ $module ] );
	}

	/**
	 * Enable a module
	 *
	 * @param string $module   Module name.
	 * @param int    $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function enable_module( string $module, int $actor_id ): bool {
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		if ( ! isset( self::$default_modules[ $module ] ) ) {
			return false;
		}

		$modules            = self::get_modules();
		$modules[ $module ] = true;

		update_option( 'apollo_modules', $modules );
		self::$modules_cache = null; // Clear cache.

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action( $actor_id, 'enable_module', 'module', 0, array( 'module' => $module ) );
		}

		do_action( 'apollo_module_enabled', $module, $actor_id );

		return true;
	}

	/**
	 * Disable a module
	 *
	 * @param string $module   Module name.
	 * @param int    $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function disable_module( string $module, int $actor_id ): bool {
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		if ( ! isset( self::$default_modules[ $module ] ) ) {
			return false;
		}

		$modules            = self::get_modules();
		$modules[ $module ] = false;

		update_option( 'apollo_modules', $modules );
		self::$modules_cache = null; // Clear cache.

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action( $actor_id, 'disable_module', 'module', 0, array( 'module' => $module ) );
		}

		do_action( 'apollo_module_disabled', $module, $actor_id );

		return true;
	}

	/**
	 * Toggle a module
	 *
	 * @param string $module   Module name.
	 * @param int    $actor_id ID of admin performing action.
	 * @return bool New state of the module.
	 */
	public static function toggle_module( string $module, int $actor_id ): bool {
		if ( self::is_module_enabled( $module ) ) {
			self::disable_module( $module, $actor_id );

			return false;
		} else {
			self::enable_module( $module, $actor_id );

			return true;
		}
	}

	/**
	 * Update multiple modules at once
	 *
	 * @param array $modules  Array of module => enabled.
	 * @param int   $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function update_modules( array $modules, int $actor_id ): bool {
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		$current = self::get_modules();
		$changes = array();

		foreach ( $modules as $module => $enabled ) {
			if ( ! isset( self::$default_modules[ $module ] ) ) {
				continue;
			}

			$enabled = (bool) $enabled;
			if ( $current[ $module ] !== $enabled ) {
				$changes[ $module ] = $enabled;
				$current[ $module ] = $enabled;
			}
		}

		if ( empty( $changes ) ) {
			return true; // No changes needed.
		}

		update_option( 'apollo_modules', $current );
		self::$modules_cache = null; // Clear cache.

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action( $actor_id, 'update_modules', 'module', 0, array( 'changes' => $changes ) );
		}

		do_action( 'apollo_modules_updated', $changes, $actor_id );

		return true;
	}

	/**
	 * Get available module names
	 *
	 * @return array Module names.
	 */
	public static function get_available_modules(): array {
		return array_keys( self::$default_modules );
	}

	// =========================================================================
	// LIMITS
	// =========================================================================

	/**
	 * Get all limits
	 *
	 * @return array Limit key => value.
	 */
	public static function get_limits(): array {
		if ( null !== self::$limits_cache ) {
			return self::$limits_cache;
		}

		$saved  = get_option( 'apollo_limits', array() );
		$limits = array_merge( self::$default_limits, is_array( $saved ) ? $saved : array() );

		self::$limits_cache = $limits;

		return $limits;
	}

	/**
	 * Get a specific limit value
	 *
	 * @param string $key     Limit key.
	 * @param int    $default Default value if not set.
	 * @return int Limit value.
	 */
	public static function get_limit( string $key, int $default = 0 ): int {
		$limits = self::get_limits();

		return isset( $limits[ $key ] ) ? (int) $limits[ $key ] : $default;
	}

	/**
	 * Set a specific limit value
	 *
	 * @param string $key      Limit key.
	 * @param int    $value    Limit value.
	 * @param int    $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function set_limit( string $key, int $value, int $actor_id ): bool {
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		if ( ! isset( self::$default_limits[ $key ] ) ) {
			return false;
		}

		$limits         = self::get_limits();
		$old_value      = $limits[ $key ] ?? 0;
		$limits[ $key ] = max( 0, $value ); // No negative limits.

		update_option( 'apollo_limits', $limits );
		self::$limits_cache = null; // Clear cache.

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				$actor_id,
				'set_limit',
				'limit',
				0,
				array(
					'key'       => $key,
					'old_value' => $old_value,
					'new_value' => $value,
				)
			);
		}

		return true;
	}

	/**
	 * Update multiple limits at once
	 *
	 * @param array $limits   Array of key => value.
	 * @param int   $actor_id ID of admin performing action.
	 * @return bool Success.
	 */
	public static function update_limits( array $limits, int $actor_id ): bool {
		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return false;
		}

		$current = self::get_limits();
		$changes = array();

		foreach ( $limits as $key => $value ) {
			if ( ! isset( self::$default_limits[ $key ] ) ) {
				continue;
			}

			$value = max( 0, (int) $value );
			if ( $current[ $key ] !== $value ) {
				$changes[ $key ] = array(
					'old' => $current[ $key ],
					'new' => $value,
				);
				$current[ $key ] = $value;
			}
		}

		if ( empty( $changes ) ) {
			return true; // No changes needed.
		}

		update_option( 'apollo_limits', $current );
		self::$limits_cache = null; // Clear cache.

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action( $actor_id, 'update_limits', 'limit', 0, array( 'changes' => $changes ) );
		}

		do_action( 'apollo_limits_updated', $changes, $actor_id );

		return true;
	}

	/**
	 * Get default limits
	 *
	 * @return array Default limits.
	 */
	public static function get_default_limits(): array {
		return self::$default_limits;
	}

	// =========================================================================
	// LIMIT CHECKING HELPERS
	// =========================================================================

	/**
	 * Check if user has reached a limit
	 *
	 * @param int    $user_id   User ID.
	 * @param string $limit_key Limit key.
	 * @param int    $current   Current count.
	 * @return bool True if limit reached.
	 */
	public static function has_reached_limit( int $user_id, string $limit_key, int $current ): bool {
		// Admins bypass limits.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		$limit = self::get_limit( $limit_key );

		return $current >= $limit;
	}

	/**
	 * Check if user can create more of a resource type
	 *
	 * @param int    $user_id   User ID.
	 * @param string $limit_key Limit key.
	 * @param int    $current   Current count.
	 * @return array [ 'allowed' => bool, 'remaining' => int, 'limit' => int ].
	 */
	public static function check_limit( int $user_id, string $limit_key, int $current ): array {
		$limit = self::get_limit( $limit_key );

		// Admins bypass limits.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return array(
				'allowed'   => true,
				'remaining' => PHP_INT_MAX,
				'limit'     => $limit,
				'bypassed'  => true,
			);
		}

		$remaining = max( 0, $limit - $current );

		return array(
			'allowed'   => $current < $limit,
			'remaining' => $remaining,
			'limit'     => $limit,
			'bypassed'  => false,
		);
	}

	/**
	 * Get user's current count for a resource type (helper for common counts)
	 *
	 * @param int    $user_id      User ID.
	 * @param string $resource     Resource type: events_month, posts_day, bubble, comunas, etc.
	 * @return int Current count.
	 */
	public static function get_user_resource_count( int $user_id, string $resource ): int {
		global $wpdb;

		switch ( $resource ) {
			case 'events_month':
				// Events created this month.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts}
						WHERE post_author = %d
						AND post_type = 'event_listing'
						AND post_status IN ('publish', 'pending', 'draft')
						AND post_date >= %s",
						$user_id,
						gmdate( 'Y-m-01 00:00:00' )
					)
				);

			case 'posts_day':
				// Social posts today.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts}
						WHERE post_author = %d
						AND post_type = 'apollo_social_post'
						AND post_status = 'publish'
						AND post_date >= %s",
						$user_id,
						gmdate( 'Y-m-d 00:00:00' )
					)
				);

			case 'bubble':
				// Bubble members.
				$bolha = get_user_meta( $user_id, 'apollo_bolha', true );

				return is_array( $bolha ) ? count( $bolha ) : 0;

			case 'comunas':
				// Comunas created by user.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts}
						WHERE post_author = %d
						AND post_type = 'apollo_comuna'
						AND post_status IN ('publish', 'pending', 'draft')",
						$user_id
					)
				);

			case 'ads':
				// Active classifieds.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts}
						WHERE post_author = %d
						AND post_type = 'apollo_classified'
						AND post_status = 'publish'",
						$user_id
					)
				);

			case 'docs':
				// User documents.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts}
						WHERE post_author = %d
						AND post_type = 'apollo_document'
						AND post_status IN ('publish', 'private')",
						$user_id
					)
				);

			default:
				return 0;
		}
	}

	/**
	 * Quick check: can user create resource?
	 *
	 * @param int    $user_id  User ID.
	 * @param string $resource Resource type: event, post, bubble_invite, comuna, ad, doc.
	 * @return array [ 'allowed' => bool, 'message' => string ].
	 */
	public static function can_create_resource( int $user_id, string $resource ): array {
		$mapping = array(
			'event'         => array( 'events_month', 'max_events_per_user_month' ),
			'post'          => array( 'posts_day', 'max_social_posts_per_day' ),
			'bubble_invite' => array( 'bubble', 'max_bubble_members' ),
			'comuna'        => array( 'comunas', 'max_comunas_per_user' ),
			'ad'            => array( 'ads', 'max_ads_per_user' ),
			'doc'           => array( 'docs', 'max_docs_per_user' ),
		);

		if ( ! isset( $mapping[ $resource ] ) ) {
			return array(
				'allowed' => true,
				'message' => '',
			);
		}

		list($count_key, $limit_key) = $mapping[ $resource ];

		$current = self::get_user_resource_count( $user_id, $count_key );
		$check   = self::check_limit( $user_id, $limit_key, $current );

		if ( $check['allowed'] ) {
			return array(
				'allowed'   => true,
				'message'   => '',
				'remaining' => $check['remaining'],
			);
		}

		$messages = array(
			'event'         => __( 'Você atingiu o limite de eventos por mês.', 'apollo-core' ),
			'post'          => __( 'Você atingiu o limite de posts por dia.', 'apollo-core' ),
			'bubble_invite' => __( 'Sua bolha está cheia (máximo 15 pessoas).', 'apollo-core' ),
			'comuna'        => __( 'Você atingiu o limite de comunas.', 'apollo-core' ),
			'ad'            => __( 'Você atingiu o limite de anúncios ativos.', 'apollo-core' ),
			'doc'           => __( 'Você atingiu o limite de documentos.', 'apollo-core' ),
		);

		return array(
			'allowed'   => false,
			'message'   => $messages[ $resource ] ?? __( 'Limite atingido.', 'apollo-core' ),
			'remaining' => 0,
		);
	}
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Check if a module is enabled
 *
 * @param string $module Module name.
 * @return bool True if enabled.
 */
function apollo_is_module_enabled( string $module ): bool {
	return Apollo_Modules_Config::is_module_enabled( $module );
}

/**
 * Get a limit value
 *
 * @param string $key     Limit key.
 * @param int    $default Default value.
 * @return int Limit value.
 */
function apollo_get_limit( string $key, int $default = 0 ): int {
	return Apollo_Modules_Config::get_limit( $key, $default );
}

/**
 * Check if user can create a resource
 *
 * @param int    $user_id  User ID.
 * @param string $resource Resource type.
 * @return array [ 'allowed' => bool, 'message' => string ].
 */
function apollo_check_limit( int $user_id, string $resource ): array {
	return Apollo_Modules_Config::can_create_resource( $user_id, $resource );
}

/**
 * Check if user has reached a limit
 *
 * @param int    $user_id   User ID.
 * @param string $limit_key Limit key.
 * @param int    $current   Current count.
 * @return bool True if limit reached.
 */
function apollo_has_reached_limit( int $user_id, string $limit_key, int $current ): bool {
	return Apollo_Modules_Config::has_reached_limit( $user_id, $limit_key, $current );
}
