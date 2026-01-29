<?php
/**
 * Apollo Service Discovery - Strategy Pattern
 *
 * Implements passive provider service discovery for CPT ownership.
 * Uses strategy pattern to handle different scenarios:
 * - Remote Manager Strategy (when apollo-events-manager is active)
 * - Local Fallback Strategy (when manager is inactive)
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for CPT Provider Strategies
 */
interface CPT_Provider_Strategy_Interface {

	/**
	 * Check if this strategy can handle the given CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function can_handle( string $post_type ): bool;

	/**
	 * Register the CPT with this strategy
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $args      Registration arguments.
	 * @param string $owner     Plugin owner.
	 * @return bool True on success, false on failure.
	 */
	public function register( string $post_type, array $args, string $owner ): bool;

	/**
	 * Check if the CPT already exists in this provider
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function exists( string $post_type ): bool;
}

/**
 * Remote Manager Strategy - Delegates to apollo-events-manager
 */
class Remote_Manager_Strategy implements CPT_Provider_Strategy_Interface {

	/**
	 * Check if this strategy can handle the given CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function can_handle( string $post_type ): bool {
		$remote_cpts = array( 'event_listing', 'event_dj', 'event_local' );
		return in_array( $post_type, $remote_cpts, true ) && $this->is_manager_active();
	}

	/**
	 * Register the CPT by delegating to the remote manager
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $args      Registration arguments.
	 * @param string $owner     Plugin owner.
	 * @return bool True on success, false on failure.
	 */
	public function register( string $post_type, array $args, string $owner ): bool {
		// Check if manager already has this CPT
		if ( $this->exists( $post_type ) ) {
			return true; // Already exists, no need to register
		}

		// Try to delegate registration to manager via action/filter
		$result = apply_filters( 'apollo_events_manager_register_cpt', false, $post_type, $args, $owner );

		if ( $result ) {
			return true;
		}

		// If delegation failed, log and return false
		error_log( "Apollo Service Discovery: Failed to delegate {$post_type} to apollo-events-manager" );
		return false;
	}

	/**
	 * Check if the CPT exists in the remote manager
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function exists( string $post_type ): bool {
		// Check via filter if manager has this CPT
		return apply_filters( 'apollo_events_manager_has_cpt', false, $post_type );
	}

	/**
	 * Check if apollo-events-manager is active
	 *
	 * @return bool
	 */
	private function is_manager_active(): bool {
		return defined( 'APOLLO_EVENTS_VERSION' ) ||
				( function_exists( 'is_plugin_active' ) &&
				is_plugin_active( 'apollo-events-manager/apollo-events-manager.php' ) );
	}
}

/**
 * Local Fallback Strategy - Registers locally when manager is unavailable
 */
class Local_Fallback_Strategy implements CPT_Provider_Strategy_Interface {

	/**
	 * Check if this strategy can handle the given CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function can_handle( string $post_type ): bool {
		$fallback_cpts = array( 'event_listing', 'event_dj', 'event_local', 'apollo_social_post', 'user_page' );
		return in_array( $post_type, $fallback_cpts, true ) && ! $this->is_manager_active();
	}

	/**
	 * Register the CPT locally
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $args      Registration arguments.
	 * @param string $owner     Plugin owner.
	 * @return bool True on success, false on failure.
	 */
	public function register( string $post_type, array $args, string $owner ): bool {
		// Check if already exists
		if ( post_type_exists( $post_type ) ) {
			return true;
		}

		// Register locally
		$result = register_post_type( $post_type, $args );

		if ( is_wp_error( $result ) ) {
			error_log( "Apollo Service Discovery: Failed to register {$post_type} locally: " . $result->get_error_message() );
			return false;
		}

		return true;
	}

	/**
	 * Check if the CPT exists locally
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function exists( string $post_type ): bool {
		return post_type_exists( $post_type );
	}

	/**
	 * Check if apollo-events-manager is active (inverse logic)
	 *
	 * @return bool
	 */
	private function is_manager_active(): bool {
		return defined( 'APOLLO_EVENTS_VERSION' ) ||
				( function_exists( 'is_plugin_active' ) &&
				is_plugin_active( 'apollo-events-manager/apollo-events-manager.php' ) );
	}
}

/**
 * Service Discovery Factory - Decides which strategy to use
 */
class CPT_Service_Discovery_Factory {

	/**
	 * Get the appropriate strategy for a CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return CPT_Provider_Strategy_Interface|null
	 */
	public static function get_strategy( string $post_type ): ?CPT_Provider_Strategy_Interface {
		$strategies = array(
			new Remote_Manager_Strategy(),
			new Local_Fallback_Strategy(),
		);

		foreach ( $strategies as $strategy ) {
			if ( $strategy->can_handle( $post_type ) ) {
				return $strategy;
			}
		}

		return null; // No strategy available
	}

	/**
	 * Register a CPT using the appropriate strategy
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $args      Registration arguments.
	 * @param string $owner     Plugin owner.
	 * @return bool True on success, false on failure.
	 */
	public static function register_cpt( string $post_type, array $args, string $owner ): bool {
		$strategy = self::get_strategy( $post_type );

		if ( ! $strategy ) {
			error_log( "Apollo Service Discovery: No strategy available for {$post_type}" );
			return false;
		}

		return $strategy->register( $post_type, $args, $owner );
	}
}
