<?php
declare(strict_types=1);

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
class Apollo_Core_Activation {
	/**
	 * Run activation
	 */
	public static function activate() {
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

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		update_option( 'apollo_core_activated', true );
		update_option( 'apollo_core_version', APOLLO_CORE_VERSION );
	}

	/**
	 * Create custom roles
	 */
	private static function create_roles() {
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
	 */
	private static function create_options() {
		$default_settings = array(
			'auto_approve_events'   => false,
			'auto_approve_posts'    => false,
			'require_moderation'    => array( 'event_listing', 'apollo_social_post' ),
			'mod_roles'             => array( 'apollo', 'editor', 'administrator' ),
			'audit_log_enabled'     => true,
			'canvas_mode_enabled'   => true,
			'migration_completed'   => false,
		);

		add_option( 'apollo_mod_settings', $default_settings );
	}

	/**
	 * Create database tables
	 */
	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Table: apollo_mod_log.
		$table_name = $wpdb->prefix . 'apollo_mod_log';
		$sql        = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action varchar(50) NOT NULL,
			content_type varchar(50) NOT NULL,
			content_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			old_status varchar(20) DEFAULT NULL,
			new_status varchar(20) DEFAULT NULL,
			notes text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY action_idx (action),
			KEY content_type_idx (content_type),
			KEY content_id_idx (content_id),
			KEY user_id_idx (user_id),
			KEY created_at_idx (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Initialize membership system
	 */
	private static function init_memberships() {
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
	 * Initialize CENA-RIO system
	 */
	private static function init_cena_rio(): void {
		// Check if CENA-RIO roles class is loaded.
		if ( ! class_exists( 'Apollo_Cena_Rio_Roles' ) ) {
			return;
		}

		// Setup roles and capabilities.
		Apollo_Cena_Rio_Roles::activate();
	}
}

