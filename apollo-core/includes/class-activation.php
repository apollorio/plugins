<?php

declare(strict_types=1);

namespace Apollo_Core;

/**
 * Activation Handler
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation class
 */
class Activation {

	/**
	 * Run activation
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			wp_die( esc_html__( 'Apollo Core requires PHP 8.1 or higher.', 'apollo-core' ) );
		}

		// Check WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '6.0', '<' ) ) {
			wp_die( esc_html__( 'Apollo Core requires WordPress 6.0 or higher.', 'apollo-core' ) );
		}

		// Create roles.
		self::create_roles();

		// Assign Apollo capabilities to roles via centralized Roles Manager
		if ( class_exists( 'Apollo_Roles_Manager' ) ) {
			Apollo_Roles_Manager::setup_capabilities();
		} elseif ( class_exists( '\Apollo_RBAC' ) ) {
			\Apollo_RBAC::assign_capabilities_to_roles();
		} elseif ( class_exists( '\Apollo_Core\RBAC' ) ) {
			\Apollo_Core\RBAC::assign_capabilities_to_roles();
		}

		// Assign CPT capabilities (S1 Consolidation).
		if ( class_exists( '\Apollo_Core\Apollo_Capabilities' ) ) {
			\Apollo_Core\Apollo_Capabilities::get_instance()->assign_capabilities_on_activation();
		}

		// Create options.
		self::create_options();

		// Create database tables.
		self::create_tables();

		// Initialize memberships.
		self::init_memberships();

		// Initialize quiz system.
		self::init_quiz();

		// Setup CENA-RIO roles and capabilities.
		self::init_cena_rio();

		// STRICT MODE: Auto-create Home page with Elementor structure.
		self::init_home_page();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		update_option( 'apollo_core_activated', true );
		update_option( 'apollo_core_version', APOLLO_CORE_VERSION );
		update_option( 'apollo_core_migration_version', APOLLO_CORE_VERSION );

		// Fire activation action for Home Page Builder.
		do_action( 'apollo_core_activated' );
	}

	/**
	 * Create custom roles
	 *
	 * @return void
	 */
	private static function create_roles(): void {
		// Role: apollo (base social role, inherits from editor).
		if ( ! get_role( 'apollo' ) ) {
			$editor = get_role( 'editor' );
			if ( $editor ) {
				add_role(
					'apollo',
					__( 'Apollo', 'apollo-core' ),
					$editor->capabilities
				);
			}
		}

		// Role: cena-rio (industry member).
		if ( ! get_role( 'cena-rio' ) ) {
			$author = get_role( 'author' );
			if ( $author ) {
				add_role(
					'cena-rio',
					__( 'Cena::rio', 'apollo-core' ),
					$author->capabilities
				);

				// Add specific capabilities.
				$role = get_role( 'cena-rio' );
				if ( $role ) {
					$role->add_cap( 'apollo_access_cena_rio' );
					$role->add_cap( 'apollo_create_event_plan' );
					$role->add_cap( 'apollo_submit_draft_event' );
				}
			}
		}

		// Role: dj (verified DJ).
		if ( ! get_role( 'dj' ) ) {
			$author = get_role( 'author' );
			if ( $author ) {
				add_role(
					'dj',
					__( 'DJ', 'apollo-core' ),
					$author->capabilities
				);

				// Add specific capabilities.
				$role = get_role( 'dj' );
				if ( $role ) {
					$role->add_cap( 'apollo_view_dj_stats' );
				}
			}
		}

		// Role: nucleo-member (private group member).
		if ( ! get_role( 'nucleo-member' ) ) {
			$subscriber = get_role( 'subscriber' );
			if ( $subscriber ) {
				add_role(
					'nucleo-member',
					__( 'Núcleo Member', 'apollo-core' ),
					$subscriber->capabilities
				);

				// Add specific capabilities.
				$role = get_role( 'nucleo-member' );
				if ( $role ) {
					$role->add_cap( 'apollo_access_nucleo' );
				}
			}
		}

		// Role: clubber (event attendee, basic public profile).
		if ( ! get_role( 'clubber' ) ) {
			$subscriber = get_role( 'subscriber' );
			if ( $subscriber ) {
				add_role(
					'clubber',
					__( 'Clubber', 'apollo-core' ),
					$subscriber->capabilities
				);

				// Clubbers can publish social posts and create communities.
				$role = get_role( 'clubber' );
				if ( $role ) {
					$role->add_cap( 'edit_posts' );
					$role->add_cap( 'publish_posts' );
					$role->add_cap( 'apollo_create_community' );
				}
			}
		}
	}

	/**
	 * Create default options
	 *
	 * @return void
	 */
	private static function create_options(): void {
		$default_settings = array(
			'auto_approve_events' => false,
			'auto_approve_posts'  => false,
			'require_mod'         => array( 'event_listing', 'apollo_social_post' ),
			'mod_roles'           => array( 'editor', 'administrator' ), // Standard WP roles only
			'audit_log_enabled'   => true,
			'canvas_mode_enabled' => true,
			'migration_completed' => false,
		);

		add_option( 'apollo_mod_settings', $default_settings );
	}

	/**
	 * Create database tables
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		if ( function_exists( 'Apollo_Core\create_db_tables' ) ) {
			create_db_tables();
		}
	}

	/**
	 * Initialize membership system
	 *
	 * @return void
	 */
	private static function init_memberships(): void {
		// Check if memberships.php is loaded.
		if ( ! function_exists( 'apollo_init_memberships_option' ) ) {
			return;
		}

		// Create memberships option.
		apollo_init_memberships_option();

		// Assign default membership to existing users.
		apollo_assign_default_memberships();
	}

	/**
	 * Initialize quiz system
	 *
	 * @return void
	 */
	private static function init_quiz(): void {
		// Check if quiz schema-manager.php is loaded.
		if ( ! function_exists( 'apollo_migrate_quiz_schema' ) ) {
			return;
		}

		// Run quiz migration (creates table and options).
		apollo_migrate_quiz_schema();
	}

	/**
	 * Initialize CENA-RIO system (DEPRECATED - uses Apollo_Roles_Manager)
	 *
	 * @return void
	 */
	private static function init_cena_rio(): void {
		// Use centralized Roles Manager (standard WP roles with custom labels)
		if ( class_exists( 'Apollo_Roles_Manager' ) ) {
			// Setup capabilities and migrate deprecated roles
			Apollo_Roles_Manager::init();
			return;
		}

		// Legacy fallback - check for deprecated classes
		if ( class_exists( '\Apollo_Cena_Rio_Roles' ) ) {
			\Apollo_Cena_Rio_Roles::activate();
		} elseif ( class_exists( '\Apollo_Core\Cena_Rio_Roles' ) ) {
			\Apollo_Core\Cena_Rio_Roles::activate();
		}
	}

	/**
	 * Initialize Home page (STRICT MODE)
	 *
	 * Auto-creates a "Home" page with Elementor structure on activation.
	 * Uses existing CPTs only (event_listing, apollo_classified) - NO new CPTs.
	 *
	 * @return void
	 */
	private static function init_home_page(): void {
		// Check if Home Page Builder class is loaded (correct namespace).
		if ( class_exists( '\Apollo_Core\Apollo_Home_Page_Builder' ) ) {
			// Get instance and call on_activation (it's an instance method, not static)
			\Apollo_Core\Apollo_Home_Page_Builder::get_instance()->on_activation();
		}
	}
}
