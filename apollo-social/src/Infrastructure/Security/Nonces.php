<?php

namespace Apollo\Infrastructure\Security;

/**
 * Nonces manager
 *
 * Thin wrapper around WordPress nonce functions.
 * Individual endpoints handle nonces directly; this class is for shared utilities.
 *
 * @since 2.3.0
 * @status ACTIVE - Implemented
 */
class Nonces {

	/**
	 * Nonce action prefix
	 */
	private const PREFIX = 'apollo_';

	/**
	 * Generate nonce for action
	 *
	 * @param string $action Action name.
	 * @return string Nonce value.
	 */
	public static function create( string $action ): string {
		return wp_create_nonce( self::PREFIX . $action );
	}

	/**
	 * Verify nonce for action
	 *
	 * @param string $nonce  Nonce value.
	 * @param string $action Action name.
	 * @return bool True if valid, false otherwise.
	 */
	public static function verify( string $nonce, string $action ): bool {
		return (bool) wp_verify_nonce( $nonce, self::PREFIX . $action );
	}

	/**
	 * Get nonce field HTML
	 *
	 * @param string $action Action name.
	 * @param string $name   Field name.
	 * @param bool   $referer Include referer field.
	 * @return string HTML string.
	 */
	public static function field( string $action, string $name = '_wpnonce', bool $referer = true ): string {
		return wp_nonce_field( self::PREFIX . $action, $name, $referer, false );
	}
}
