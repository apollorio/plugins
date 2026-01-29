<?php
/**
 * Apollo Capabilities Manager
 *
 * Unified capability mapping for all Apollo CPTs across plugins.
 * Handles role assignment and cross-plugin permission checks.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Capabilities
 *
 * Centralized capability management for Apollo ecosystem.
 */
class Apollo_Capabilities {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Capabilities|null
	 */
	private static ?Apollo_Capabilities $instance = null;

	/**
	 * Capability map for all CPTs
	 *
	 * @var array<string, array<string, string>>
	 */
	private const CPT_CAPABILITIES = array(
		// Events Manager CPTs.
		'event_listing'      => array(
			'edit_post'              => 'edit_event_listing',
			'read_post'              => 'read_event_listing',
			'delete_post'            => 'delete_event_listing',
			'edit_posts'             => 'edit_event_listings',
			'edit_others_posts'      => 'edit_others_event_listings',
			'publish_posts'          => 'publish_event_listings',
			'read_private_posts'     => 'read_private_event_listings',
			'delete_posts'           => 'delete_event_listings',
			'delete_private_posts'   => 'delete_private_event_listings',
			'delete_published_posts' => 'delete_published_event_listings',
			'delete_others_posts'    => 'delete_others_event_listings',
			'edit_private_posts'     => 'edit_private_event_listings',
			'edit_published_posts'   => 'edit_published_event_listings',
			'create_posts'           => 'create_event_listings',
		),
		'event_dj'           => array(
			'edit_post'          => 'edit_event_dj',
			'read_post'          => 'read_event_dj',
			'delete_post'        => 'delete_event_dj',
			'edit_posts'         => 'edit_event_djs',
			'edit_others_posts'  => 'edit_others_event_djs',
			'publish_posts'      => 'publish_event_djs',
			'read_private_posts' => 'read_private_event_djs',
			'create_posts'       => 'create_event_djs',
		),
		'event_local'        => array(
			'edit_post'          => 'edit_event_local',
			'read_post'          => 'read_event_local',
			'delete_post'        => 'delete_event_local',
			'edit_posts'         => 'edit_event_locals',
			'edit_others_posts'  => 'edit_others_event_locals',
			'publish_posts'      => 'publish_event_locals',
			'read_private_posts' => 'read_private_event_locals',
			'create_posts'       => 'create_event_locals',
		),

		// Social CPTs.
		'apollo_social_post' => array(
			'edit_post'          => 'edit_social_post',
			'read_post'          => 'read_social_post',
			'delete_post'        => 'delete_social_post',
			'edit_posts'         => 'edit_social_posts',
			'edit_others_posts'  => 'edit_others_social_posts',
			'publish_posts'      => 'publish_social_posts',
			'read_private_posts' => 'read_private_social_posts',
			'create_posts'       => 'create_social_posts',
		),
		'user_page'          => array(
			'edit_post'          => 'edit_user_page',
			'read_post'          => 'read_user_page',
			'delete_post'        => 'delete_user_page',
			'edit_posts'         => 'edit_user_pages',
			'edit_others_posts'  => 'edit_others_user_pages',
			'publish_posts'      => 'publish_user_pages',
			'read_private_posts' => 'read_private_user_pages',
			'create_posts'       => 'create_user_pages',
		),
		'apollo_supplier'    => array(
			'edit_post'          => 'edit_supplier',
			'read_post'          => 'read_supplier',
			'delete_post'        => 'delete_supplier',
			'edit_posts'         => 'edit_suppliers',
			'edit_others_posts'  => 'edit_others_suppliers',
			'publish_posts'      => 'publish_suppliers',
			'read_private_posts' => 'read_private_suppliers',
			'create_posts'       => 'create_suppliers',
		),
		'apollo_classified'  => array(
			'edit_post'          => 'edit_classified',
			'read_post'          => 'read_classified',
			'delete_post'        => 'delete_classified',
			'edit_posts'         => 'edit_classifieds',
			'edit_others_posts'  => 'edit_others_classifieds',
			'publish_posts'      => 'publish_classifieds',
			'read_private_posts' => 'read_private_classifieds',
			'create_posts'       => 'create_classifieds',
		),
		'apollo_document'    => array(
			'edit_post'          => 'edit_document',
			'read_post'          => 'read_document',
			'delete_post'        => 'delete_document',
			'edit_posts'         => 'edit_documents',
			'edit_others_posts'  => 'edit_others_documents',
			'publish_posts'      => 'publish_documents',
			'read_private_posts' => 'read_private_documents',
			'create_posts'       => 'create_documents',
		),
	);

	/**
	 * Role capability assignments
	 *
	 * @var array<string, array<string, bool>>
	 */
	private const ROLE_CAPABILITIES = array(
		'administrator' => array(
			// Full access to all CPTs.
			'manage_apollo'                => true,
			'manage_apollo_settings'       => true,
			// Events.
			'edit_event_listings'          => true,
			'edit_others_event_listings'   => true,
			'publish_event_listings'       => true,
			'read_private_event_listings'  => true,
			'delete_event_listings'        => true,
			'delete_others_event_listings' => true,
			'create_event_listings'        => true,
			'edit_event_djs'               => true,
			'edit_others_event_djs'        => true,
			'publish_event_djs'            => true,
			'create_event_djs'             => true,
			'edit_event_locals'            => true,
			'edit_others_event_locals'     => true,
			'publish_event_locals'         => true,
			'create_event_locals'          => true,
			// Social.
			'edit_social_posts'            => true,
			'edit_others_social_posts'     => true,
			'publish_social_posts'         => true,
			'create_social_posts'          => true,
			'edit_user_pages'              => true,
			'edit_others_user_pages'       => true,
			'publish_user_pages'           => true,
			'create_user_pages'            => true,
			// Suppliers.
			'edit_suppliers'               => true,
			'edit_others_suppliers'        => true,
			'publish_suppliers'            => true,
			'create_suppliers'             => true,
			// Classifieds.
			'edit_classifieds'             => true,
			'edit_others_classifieds'      => true,
			'publish_classifieds'          => true,
			'create_classifieds'           => true,
			// Documents.
			'edit_documents'               => true,
			'edit_others_documents'        => true,
			'publish_documents'            => true,
			'create_documents'             => true,
			// Moderation.
			'moderate_apollo'              => true,
			'view_apollo_reports'          => true,
		),

		'editor'        => array(
			// Edit all content but limited admin.
			'edit_event_listings'         => true,
			'edit_others_event_listings'  => true,
			'publish_event_listings'      => true,
			'read_private_event_listings' => true,
			'create_event_listings'       => true,
			'edit_event_djs'              => true,
			'edit_others_event_djs'       => true,
			'publish_event_djs'           => true,
			'create_event_djs'            => true,
			'edit_event_locals'           => true,
			'edit_others_event_locals'    => true,
			'publish_event_locals'        => true,
			'create_event_locals'         => true,
			'edit_social_posts'           => true,
			'edit_others_social_posts'    => true,
			'publish_social_posts'        => true,
			'create_social_posts'         => true,
			'moderate_apollo'             => true,
		),

		'author'        => array(
			// Own content only.
			'edit_event_listings'    => true,
			'publish_event_listings' => true,
			'create_event_listings'  => true,
			'edit_social_posts'      => true,
			'publish_social_posts'   => true,
			'create_social_posts'    => true,
			'edit_user_pages'        => true,
			'publish_user_pages'     => true,
			'create_user_pages'      => true,
			'edit_classifieds'       => true,
			'publish_classifieds'    => true,
			'create_classifieds'     => true,
			'edit_documents'         => true,
			'publish_documents'      => true,
			'create_documents'       => true,
		),

		'contributor'   => array(
			// Submit for review.
			'edit_event_listings'   => true,
			'create_event_listings' => true,
			'edit_social_posts'     => true,
			'create_social_posts'   => true,
			'edit_classifieds'      => true,
			'create_classifieds'    => true,
		),

		'subscriber'    => array(
			// Read only + own profile.
			'read_event_listing' => true,
			'read_social_post'   => true,
			'edit_user_pages'    => true,
			'create_user_pages'  => true,
			'edit_documents'     => true,
			'create_documents'   => true,
		),
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Capabilities
	 */
	public static function get_instance(): Apollo_Capabilities {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Map meta capabilities for custom CPTs.
		\add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Assign capabilities to roles on plugin activation
	 *
	 * @return void
	 */
	public function assign_capabilities_on_activation(): void {
		foreach ( self::ROLE_CAPABILITIES as $role_name => $capabilities ) {
			$role = \get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $capabilities as $cap => $grant ) {
				$role->add_cap( $cap, $grant );
			}
		}

		// Mark as assigned.
		\update_option( 'apollo_capabilities_version', APOLLO_CORE_VERSION );
	}

	/**
	 * Remove capabilities from roles on plugin deactivation
	 *
	 * @return void
	 */
	public function remove_capabilities_on_deactivation(): void {
		foreach ( self::ROLE_CAPABILITIES as $role_name => $capabilities ) {
			$role = \get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $capabilities as $cap => $grant ) {
				$role->remove_cap( $cap );
			}
		}

		\delete_option( 'apollo_capabilities_version' );
	}

	/**
	 * Map meta capabilities for CPTs
	 *
	 * @param array  $caps    Required capabilities.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Additional arguments.
	 * @return array
	 */
	public function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		// Check if this is a CPT capability we handle.
		foreach ( self::CPT_CAPABILITIES as $post_type => $cpt_caps ) {
			// Check edit_post capability.
			if ( $cap === $cpt_caps['edit_post'] && ! empty( $args[0] ) ) {
				$post = \get_post( $args[0] );
				if ( ! $post || $post->post_type !== $post_type ) {
					continue;
				}

				if ( (int) $post->post_author === $user_id ) {
					$caps = array( $cpt_caps['edit_posts'] );
				} else {
					$caps = array( $cpt_caps['edit_others_posts'] );
				}

				return $caps;
			}

			// Check delete_post capability.
			if ( $cap === $cpt_caps['delete_post'] && ! empty( $args[0] ) ) {
				$post = \get_post( $args[0] );
				if ( ! $post || $post->post_type !== $post_type ) {
					continue;
				}

				if ( (int) $post->post_author === $user_id ) {
					$caps = array( $cpt_caps['edit_posts'] );
				} else {
					$caps = array( $cpt_caps['delete_others_posts'] ?? $cpt_caps['edit_others_posts'] );
				}

				return $caps;
			}

			// Check read_post capability.
			if ( $cap === $cpt_caps['read_post'] && ! empty( $args[0] ) ) {
				$post = \get_post( $args[0] );
				if ( ! $post || $post->post_type !== $post_type ) {
					continue;
				}

				if ( 'private' === $post->post_status ) {
					if ( (int) $post->post_author === $user_id ) {
						$caps = array( 'read' );
					} else {
						$caps = array( $cpt_caps['read_private_posts'] );
					}
				} else {
					$caps = array( 'read' );
				}

				return $caps;
			}
		}

		return $caps;
	}

	/**
	 * Get capabilities for a CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return array|null
	 */
	public function get_cpt_capabilities( string $post_type ): ?array {
		return self::CPT_CAPABILITIES[ $post_type ] ?? null;
	}

	/**
	 * Get all CPT capabilities
	 *
	 * @return array<string, array>
	 */
	public function get_all_capabilities(): array {
		return self::CPT_CAPABILITIES;
	}

	/**
	 * Get role capabilities
	 *
	 * @param string $role Role name.
	 * @return array|null
	 */
	public function get_role_capabilities( string $role ): ?array {
		return self::ROLE_CAPABILITIES[ $role ] ?? null;
	}

	/**
	 * Check if user can perform action on CPT
	 *
	 * @param int    $user_id   User ID.
	 * @param string $post_type Post type.
	 * @param string $action    Action (create, edit, delete, publish).
	 * @param int    $post_id   Optional. Post ID for context.
	 * @return bool
	 */
	public function user_can_cpt( int $user_id, string $post_type, string $action, int $post_id = 0 ): bool {
		$cpt_caps = $this->get_cpt_capabilities( $post_type );

		if ( ! $cpt_caps ) {
			// Unknown CPT - fall back to standard check.
			return \user_can( $user_id, "{$action}_posts" );
		}

		$cap_map = array(
			'create'  => $cpt_caps['create_posts'] ?? $cpt_caps['edit_posts'],
			'edit'    => $cpt_caps['edit_post'],
			'delete'  => $cpt_caps['delete_post'],
			'publish' => $cpt_caps['publish_posts'],
			'read'    => $cpt_caps['read_post'],
		);

		$cap = $cap_map[ $action ] ?? $cpt_caps['edit_posts'];

		if ( $post_id && \in_array( $action, array( 'edit', 'delete', 'read' ), true ) ) {
			return \user_can( $user_id, $cap, $post_id );
		}

		return \user_can( $user_id, $cap );
	}

	/**
	 * Check cross-plugin permission
	 *
	 * @param int    $user_id User ID.
	 * @param string $plugin  Plugin identifier (events, social, core).
	 * @param string $action  Action to check.
	 * @return bool
	 */
	public function check_cross_plugin_permission( int $user_id, string $plugin, string $action ): bool {
		$plugin_caps = array(
			'events' => array(
				'manage'   => 'edit_event_listings',
				'create'   => 'create_event_listings',
				'moderate' => 'moderate_apollo',
			),
			'social' => array(
				'manage'   => 'edit_social_posts',
				'create'   => 'create_social_posts',
				'moderate' => 'moderate_apollo',
			),
			'core'   => array(
				'manage'   => 'manage_apollo',
				'settings' => 'manage_apollo_settings',
				'moderate' => 'moderate_apollo',
			),
		);

		if ( ! isset( $plugin_caps[ $plugin ][ $action ] ) ) {
			return false;
		}

		return \user_can( $user_id, $plugin_caps[ $plugin ][ $action ] );
	}

	/**
	 * Get capabilities summary for admin display
	 *
	 * @return array
	 */
	public function get_capabilities_summary(): array {
		$summary = array();

		foreach ( self::ROLE_CAPABILITIES as $role => $caps ) {
			$summary[ $role ] = array(
				'total'  => \count( $caps ),
				'events' => \count( \array_filter( \array_keys( $caps ), fn( $c ) => \str_contains( $c, 'event' ) ) ),
				'social' => \count( \array_filter( \array_keys( $caps ), fn( $c ) => \str_contains( $c, 'social' ) || \str_contains( $c, 'user_page' ) ) ),
				'admin'  => \count( \array_filter( \array_keys( $caps ), fn( $c ) => \str_contains( $c, 'manage' ) || \str_contains( $c, 'moderate' ) ) ),
			);
		}

		return $summary;
	}
}

/**
 * Get Apollo Capabilities instance
 *
 * @return Apollo_Capabilities
 */
function apollo_capabilities(): Apollo_Capabilities {
	return Apollo_Capabilities::get_instance();
}

/**
 * Helper: Check if user can perform CPT action
 *
 * @param string $post_type Post type.
 * @param string $action    Action (create, edit, delete, publish).
 * @param int    $post_id   Optional. Post ID.
 * @return bool
 */
function apollo_user_can( string $post_type, string $action, int $post_id = 0 ): bool {
	return Apollo_Capabilities::get_instance()->user_can_cpt(
		\get_current_user_id(),
		$post_type,
		$action,
		$post_id
	);
}
