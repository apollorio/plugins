<?php

declare(strict_types=1);

/**
 * Moderation Roles Management
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Moderation Roles class
 */
class Apollo_Moderation_Roles {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'apollo_core_activated', [ __CLASS__, 'setup_roles' ] );
	}

	/**
	 * Setup roles and capabilities
	 */
	public static function setup_roles() {
		// Apollo role - moderator.
		$apollo = get_role( 'apollo' );
		if ( $apollo ) {
			// Add mod capabilities.
			$apollo->add_cap( 'moderate_apollo_content' );
			$apollo->add_cap( 'edit_apollo_users' );
			$apollo->add_cap( 'view_mod_queue' );
			$apollo->add_cap( 'send_user_notifications' );

			// Note: Do NOT add suspend_users, block_users, manage_apollo_mod_settings.
		}

		// Administrator - full access.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'manage_apollo_mod_settings' );
			$admin->add_cap( 'suspend_users' );
			$admin->add_cap( 'block_users' );
			$admin->add_cap( 'moderate_apollo_content' );
			$admin->add_cap( 'edit_apollo_users' );
			$admin->add_cap( 'view_mod_queue' );
			$admin->add_cap( 'send_user_notifications' );
		}

		// Optional: Fine-grained capabilities (can be toggled in Tab 1).
		self::setup_content_type_capabilities();
	}

	/**
	 * Setup content-type specific capabilities
	 */
	private static function setup_content_type_capabilities() {
		$settings = get_option( 'apollo_mod_settings', [] );
		$enabled  = isset( $settings['enabled_caps'] ) ? $settings['enabled_caps'] : [];

		$apollo = get_role( 'apollo' );
		if ( ! $apollo ) {
			return;
		}

		// List of all possible capabilities.
		$all_caps = [
			'edit_events',
			'publish_events',
			'edit_locals',
			'publish_locals',
			'edit_djs',
			'publish_djs',
			'edit_nucleos',
			'publish_nucleos',
			'edit_comunidades',
			'publish_comunidades',
			'edit_classifieds',
			'edit_posts',
		];

		// Add or remove capabilities based on settings.
		foreach ( $all_caps as $cap ) {
			if ( ! empty( $enabled[ $cap ] ) ) {
				$apollo->add_cap( $cap );
			} else {
				$apollo->remove_cap( $cap );
			}
		}
	}

	/**
	 * Check if user can moderate content type
	 *
	 * @param int    $user_id User ID.
	 * @param string $content_type Content type.
	 * @return bool
	 */
	public static function can_moderate_content_type( $user_id, $content_type ) {
		$settings = get_option( 'apollo_mod_settings', [] );
		$enabled  = isset( $settings['enabled_caps'] ) ? $settings['enabled_caps'] : [];

		// Map content type to capability.
		$cap_map = [
			'event_listing'      => 'publish_events',
			'event_local'        => 'publish_locals',
			'event_dj'           => 'publish_djs',
			'apollo_nucleo'      => 'publish_nucleos',
			'apollo_comunidade'  => 'publish_comunidades',
			'apollo_classified'  => 'edit_classifieds',
			'apollo_social_post' => 'edit_posts',
			'post'               => 'edit_posts',
		];

		$required_cap = isset( $cap_map[ $content_type ] ) ? $cap_map[ $content_type ] : false;

		if ( ! $required_cap ) {
			return false;
		}

		// Check if capability is enabled in settings.
		if ( empty( $enabled[ $required_cap ] ) ) {
			return false;
		}

		// Check if user has the capability.
		return user_can( $user_id, $required_cap );
	}

	/**
	 * Get enabled content types for mod queue
	 *
	 * Auto-includes all CPTs with pending posts for visibility.
	 *
	 * @return array
	 */
	public static function get_enabled_content_types(): array {
		$settings = get_option( 'apollo_mod_settings', [] );
		$enabled  = isset( $settings['enabled_caps'] ) ? $settings['enabled_caps'] : [];

		$types = [];

		// Always include event_listing (core CPT for events/CENA-RIO)
		$types[] = 'event_listing';

		if ( ! empty( $enabled['publish_locals'] ) ) {
			$types[] = 'event_local';
		}
		if ( ! empty( $enabled['publish_djs'] ) ) {
			$types[] = 'event_dj';
		}
		if ( ! empty( $enabled['publish_nucleos'] ) ) {
			$types[] = 'apollo_nucleo';
		}
		if ( ! empty( $enabled['publish_comunidades'] ) ) {
			$types[] = 'apollo_comunidade';
		}
		if ( ! empty( $enabled['edit_classifieds'] ) ) {
			$types[] = 'apollo_classified';
		}
		if ( ! empty( $enabled['edit_posts'] ) ) {
			$types[] = 'apollo_social_post';
		}

		// Auto-detect CPTs with pending posts (ensures nothing is missed)
		$types = self::auto_include_pending_cpts( $types );

		return array_unique( $types );
	}

	/**
	 * Auto-include any CPT that has pending posts
	 *
	 * @param array $types Existing types.
	 * @return array Updated types.
	 */
	private static function auto_include_pending_cpts( array $types ): array {
		global $wpdb;

		// Get all CPTs that have pending/draft posts
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cpts_with_pending = $wpdb->get_col(
			"SELECT DISTINCT post_type FROM {$wpdb->posts}
			 WHERE post_status IN ('pending', 'draft')
			 AND post_title != ''
			 AND post_type NOT IN ('revision', 'attachment', 'nav_menu_item', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation')"
		);

		if ( $cpts_with_pending ) {
			$types = array_merge( $types, $cpts_with_pending );
		}

		return array_unique( $types );
	}
}
