<?php
/**
 * Apollo Feature Flags
 *
 * Controle centralizado de funcionalidades do plugin.
 * Permite desativar módulos incompletos sem remover código.
 *
 * @package Apollo\Infrastructure
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Infrastructure;

/**
 * Feature Flags Manager
 *
 * Usage:
 *   if (FeatureFlags::isEnabled('chat')) { ... }
 *   FeatureFlags::disable('chat');
 */
class FeatureFlags {

	/** @var string Option key for storing flags */
	private const OPTION_KEY = 'apollo_feature_flags';

	/** @var bool Whether init() has been called */
	private static bool $initialized = false;

	/** @var array Default feature states */
	private const DEFAULTS = array(
		// Core features (enabled by default)
		'documents'      => true,
		'signatures'     => true,
		'classifieds'    => true,
		'user_pages'     => true,
		'builder'        => true,
		'feed'           => true,
		'reactions'      => true,
		'groups_api'     => true,  // Comunas/Nucleos API

		// Features disabled until ready (stubs)
		'chat'           => false,  // ChatModule - tables exist but UI incomplete
		'notifications'  => false,  // NotificationsModule - not implemented
		'groups'         => false,  // GroupsServiceProvider - stub
		'groups_api_legacy' => false,  // Legacy /groups API proxy
		'govbr'          => false,  // GOV.BR OAuth - not implemented
		'analytics'      => true,   // Analytics - optional, graceful degradation
		'pwa'            => false,  // PWA - incomplete
	);

	/** @var array|null Cached flags */
	private static ?array $cache = null;

	/**
	 * Initialize Feature Flags (must be called early in bootstrap).
	 *
	 * This is a "fail-closed" guard: if init() is not called,
	 * isInitialized() returns false and modules should not load.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Pre-load cache to avoid DB hits during request.
		self::getFlags();

		self::$initialized = true;

		// Log initialization in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$disabled = self::getDisabledFeatures();
			if ( ! empty( $disabled ) ) {
				error_log( 'Apollo FeatureFlags: Disabled features: ' . \implode( ', ', $disabled ) );
			}
		}
	}

	/**
	 * Check if FeatureFlags has been initialized.
	 *
	 * Fail-closed rule: if not initialized, modules must not run.
	 *
	 * @return bool
	 */
	public static function isInitialized(): bool {
		return self::$initialized;
	}

	/**
	 * Check if a feature is enabled
	 *
	 * Fail-closed: if not initialized, returns false.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public static function isEnabled( string $feature ): bool {
		// Fail-closed: if not initialized, block all features.
		if ( ! self::$initialized ) {
			// Allow during activation hook (before init is called).
			if ( ! doing_action( 'activate_' ) && ! did_action( 'plugins_loaded' ) ) {
				return self::DEFAULTS[ $feature ] ?? false;
			}
			// Log warning if init() was skipped after plugins_loaded.
			if ( did_action( 'plugins_loaded' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Apollo FeatureFlags: Not initialized. Feature '{$feature}' blocked (fail-closed)." );
			}
			return false;
		}

		$flags = self::getFlags();

		// Check if feature exists
		if ( ! isset( $flags[ $feature ] ) && ! isset( self::DEFAULTS[ $feature ] ) ) {
			// Unknown feature - log warning and return false for safety
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Apollo FeatureFlags: Unknown feature '{$feature}'" );
			}
			return false;
		}

		// Allow runtime override via filter
		$enabled = $flags[ $feature ] ?? self::DEFAULTS[ $feature ] ?? false;

		/**
		 * Filter feature flag status
		 *
		 * @param bool   $enabled Whether feature is enabled.
		 * @param string $feature Feature key.
		 */
		return (bool) apply_filters( 'apollo_feature_enabled', $enabled, $feature );
	}

	/**
	 * Check if a feature is disabled
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public static function isDisabled( string $feature ): bool {
		return ! self::isEnabled( $feature );
	}

	/**
	 * Enable a feature
	 *
	 * @param string $feature Feature key.
	 * @return bool Success.
	 */
	public static function enable( string $feature ): bool {
		return self::setFlag( $feature, true );
	}

	/**
	 * Disable a feature
	 *
	 * @param string $feature Feature key.
	 * @return bool Success.
	 */
	public static function disable( string $feature ): bool {
		return self::setFlag( $feature, false );
	}

	/**
	 * Set a feature flag
	 *
	 * @param string $feature Feature key.
	 * @param bool   $enabled Whether enabled.
	 * @return bool Success.
	 */
	public static function setFlag( string $feature, bool $enabled ): bool {
		$flags             = self::getFlags();
		$flags[ $feature ] = $enabled;

		$result = update_option( self::OPTION_KEY, $flags );

		// Clear cache
		self::$cache = null;

		// Log the change
		ApolloLogger::info( 'feature_flag_changed', array(
			'feature' => $feature,
			'enabled' => $enabled,
			'user_id' => get_current_user_id(),
		) );

		return $result;
	}

	/**
	 * Get all feature flags
	 *
	 * @return array
	 */
	public static function getFlags(): array {
		if ( self::$cache !== null ) {
			return self::$cache;
		}

		$stored = get_option( self::OPTION_KEY, array() );

		// Merge with defaults
		self::$cache = array_merge( self::DEFAULTS, is_array( $stored ) ? $stored : array() );

		return self::$cache;
	}

	/**
	 * Get all features with their status
	 *
	 * @return array Array of ['feature' => ['enabled' => bool, 'default' => bool]]
	 */
	public static function getAllFeatures(): array {
		$flags    = self::getFlags();
		$features = array();

		foreach ( self::DEFAULTS as $feature => $default ) {
			$features[ $feature ] = array(
				'enabled' => $flags[ $feature ] ?? $default,
				'default' => $default,
			);
		}

		return $features;
	}

	/**
	 * Reset all flags to defaults
	 *
	 * @return bool Success.
	 */
	public static function resetToDefaults(): bool {
		self::$cache = null;
		return delete_option( self::OPTION_KEY );
	}

	/**
	 * Check if current environment is production
	 *
	 * @return bool
	 */
	public static function isProduction(): bool {
		return defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE === 'production';
	}

	/**
	 * Get disabled features list (for logging/debugging)
	 *
	 * @return array
	 */
	public static function getDisabledFeatures(): array {
		$disabled = array();

		foreach ( self::getFlags() as $feature => $enabled ) {
			if ( ! $enabled ) {
				$disabled[] = $feature;
			}
		}

		return $disabled;
	}
}
