<?php
/**
 * Apollo Unified Roles Manager
 *
 * SINGLE SOURCE OF TRUTH for all role management across Apollo plugins.
 *
 * IMPORTANT: This class does NOT create new WordPress roles.
 * It ONLY:
 * 1. Renames existing standard WordPress roles (administrator, editor, author, contributor, subscriber)
 * 2. Migrates users from deprecated custom roles to standard WordPress roles
 * 3. Removes deprecated role definitions
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if (!defined('ABSPATH')) {
	exit;
}

class Apollo_Roles_Manager {

	/**
	 * Role label translations (slug => display name)
	 * WordPress slugs are NEVER changed, only display names
	 */
	private static array $role_labels = [
		'administrator' => 'apollo',
		'editor'        => 'MOD',
		'author'        => 'cult::rio',
		'contributor'   => 'cena::rio',
		'subscriber'    => 'clubber',
	];

	/**
	 * Deprecated roles to migrate
	 */
	private static array $deprecated_roles = [
		// Target => [source roles to migrate FROM]
		'editor'      => ['apollo_moderator', 'moderator', 'mod', 'cena_moderator'],
		'author'      => ['friends', 'friendz'],
		'contributor' => ['cena_role', 'cenario', 'cena-rio', 'industry'],
		'subscriber'  => ['apollo_member', 'clubber'],
	];

	/**
	 * Specialized roles that should be preserved
	 */
	private static array $preserved_roles = [
		'aprio-scanner' => true, // Ticket scanning functionality
	];

	/**
	 * Apollo-specific capabilities to add to standard roles
	 */
	private static array $apollo_capabilities = [
		'administrator' => [
			'manage_apollo',
			'manage_apollo_security',
			'manage_apollo_uploads',
			'manage_apollo_events',
			'manage_apollo_social',
			'apollo_cena_moderate_events',
			'moderate_events',
			'approve_pending_events',
			'view_event_analytics',
			'view_site_analytics',
			'feature_content',
			'pin_content',
			'hide_content',
		],
		'editor' => [ // MOD
			'manage_apollo_events',
			'manage_apollo_social',
			'apollo_cena_moderate_events',
			'moderate_events',
			'approve_pending_events',
			'view_event_analytics',
			'view_site_analytics',
			'edit_others_event_listings',
			'publish_event_listings',
			'delete_others_event_listings',
			'read_private_event_listings',
			'moderate_activity',
			'view_reported_content',
			'moderate_groups',
		],
		'author' => [ // cult::rio
			'edit_event_listing',
			'edit_event_listings',
			'delete_event_listing',
			'publish_event_listings',
			'edit_event_dj',
			'edit_event_djs',
			'publish_event_djs',
			'edit_event_local',
			'edit_event_locals',
			'publish_event_locals',
			'create_groups',
			'generate_api_keys',
			'view_own_analytics',
		],
		'contributor' => [ // cena::rio
			'edit_event_listing',
			'edit_event_listings',
			'delete_event_listing',
			// NO publish_event_listings - drafts only
			'apollo_submit_event',
			'apollo_create_draft_event',
			'view_own_analytics',
		],
		'subscriber' => [ // clubber
			'apollo_submit_event',
			'apollo_create_draft_event',
			'publish_activity',
			'edit_own_activity',
			'delete_own_activity',
			'follow_users',
			'send_messages',
			'report_content',
		],
	];

	/**
	 * Initialize the roles manager
	 */
	public static function init(): void {
		// Translate role names in admin UI
		add_filter('editable_roles', [self::class, 'translate_role_names']);
		add_filter('gettext_with_context', [self::class, 'translate_role_text'], 10, 4);

		// Setup capabilities on admin_init
		add_action('admin_init', [self::class, 'setup_capabilities'], 5);

		// Migration handler
		add_action('admin_init', [self::class, 'maybe_migrate_deprecated_roles']);
	}

	/**
	 * Activation handler - called when apollo-core activates
	 */
	public static function activate(): void {
		self::setup_capabilities();
		self::migrate_all_deprecated_roles();
		self::cleanup_deprecated_role_definitions();
	}

	/**
	 * Translate role display names for admin UI
	 */
	public static function translate_role_names(array $roles): array {
		foreach (self::$role_labels as $slug => $label) {
			if (isset($roles[$slug])) {
				$roles[$slug]['name'] = $label;
			}
		}
		return $roles;
	}

	/**
	 * Translate role names in gettext contexts
	 */
	public static function translate_role_text($translation, $text, $context, $domain): string {
		if ($context === 'User role') {
			$text_lower = strtolower($text);
			foreach (self::$role_labels as $slug => $label) {
				if ($text_lower === $slug || $text_lower === strtolower($label)) {
					return $label;
				}
			}
		}
		return $translation;
	}

	/**
	 * Setup Apollo capabilities on standard WordPress roles
	 */
	public static function setup_capabilities(): void {
		foreach (self::$apollo_capabilities as $role_slug => $caps) {
			$role = get_role($role_slug);
			if (!$role) continue;

			foreach ($caps as $cap) {
				if (!$role->has_cap($cap)) {
					$role->add_cap($cap);
				}
			}
		}
	}

	/**
	 * Migrate users from deprecated roles to standard WordPress roles
	 */
	public static function migrate_all_deprecated_roles(): void {
		foreach (self::$deprecated_roles as $target_role => $source_roles) {
			foreach ($source_roles as $source_role) {
				// Find users with deprecated role
				$users = get_users(['role' => $source_role]);

				foreach ($users as $user) {
					// Remove deprecated role
					$user->remove_role($source_role);

					// Add standard role if user has no roles
					if (empty($user->roles)) {
						$user->add_role($target_role);
					}

					// Log migration
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log(sprintf(
							'✅ Apollo: Migrated user %s from %s to %s',
							$user->user_login,
							$source_role,
							$target_role
						));
					}
				}
			}
		}
	}

	/**
	 * Remove deprecated role definitions from database
	 */
	public static function cleanup_deprecated_role_definitions(): void {
		$all_deprecated = array_merge(...array_values(self::$deprecated_roles));

		foreach ($all_deprecated as $role_slug) {
			if (!isset(self::$preserved_roles[$role_slug])) {
				remove_role($role_slug);
			}
		}

		// Log cleanup
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('✅ Apollo: Cleaned up deprecated role definitions');
		}
	}

	/**
	 * Maybe migrate on admin_init (one-time check)
	 */
	public static function maybe_migrate_deprecated_roles(): void {
		$migration_version = get_option('apollo_roles_migration_version', '0');

		if (version_compare($migration_version, '4.0.0', '<')) {
			self::migrate_all_deprecated_roles();
			self::cleanup_deprecated_role_definitions();
			update_option('apollo_roles_migration_version', '4.0.0');
		}
	}

	/**
	 * Get display label for a role
	 */
	public static function get_role_label(string $role_slug): string {
		return self::$role_labels[$role_slug] ?? ucfirst($role_slug);
	}

	/**
	 * Get all role labels
	 */
	public static function get_all_labels(): array {
		return self::$role_labels;
	}

	/**
	 * Check if role is deprecated
	 */
	public static function is_deprecated_role(string $role_slug): bool {
		$all_deprecated = array_merge(...array_values(self::$deprecated_roles));
		return in_array($role_slug, $all_deprecated, true);
	}

	/**
	 * Get target role for a deprecated role
	 */
	public static function get_migration_target(string $deprecated_role): ?string {
		foreach (self::$deprecated_roles as $target => $sources) {
			if (in_array($deprecated_role, $sources, true)) {
				return $target;
			}
		}
		return null;
	}
}

// Auto-initialize
Apollo_Roles_Manager::init();
