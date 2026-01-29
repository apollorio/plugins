<?php
/**
 * Apollo Activation Controller
 *
 * Master controller for plugin activation, deactivation, and uninstallation.
 * Handles database migrations, cron scheduling, and initial setup.
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
 * Class Apollo_Activation_Controller
 *
 * Orchestrates plugin lifecycle events.
 */
class Apollo_Activation_Controller {

	/**
	 * Minimum PHP version required.
	 */
	const MIN_PHP_VERSION = '8.1.0';

	/**
	 * Minimum WordPress version required.
	 */
	const MIN_WP_VERSION = '6.0.0';

	/**
	 * Database schema version.
	 */
	const DB_VERSION = '2.0.0';

	/**
	 * Option key for database version.
	 */
	const DB_VERSION_OPTION = 'apollo_db_version';

	/**
	 * Activation hook callback.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 * @return void
	 * @throws \Exception If requirements are not met.
	 */
	public static function activate( bool $network_wide = false ): void {
		// 1. Check system requirements.
		self::check_requirements();

		// 2. Create database tables.
		self::create_tables();

		// 3. Run migrations.
		self::run_migrations();

		// 4. Register CPTs and flush rewrite rules.
		self::register_post_types();
		\flush_rewrite_rules();

		// 5. Set default options.
		self::set_default_options();

		// 6. Schedule cron jobs.
		self::schedule_cron_jobs();

		// 7. Create default content.
		self::create_default_content();

		// 8. Set activation flag.
		\update_option( 'apollo_activated', true );
		\update_option( 'apollo_activation_time', \current_time( 'mysql' ) );
		\update_option( 'apollo_version', APOLLO_CORE_VERSION ?? '2.0.0' );

		// 9. Fire activation hook.
		\do_action( 'apollo_activated', $network_wide );

		// 10. Log activation.
		self::log_activation();
	}

	/**
	 * Deactivation hook callback.
	 *
	 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
	 * @return void
	 */
	public static function deactivate( bool $network_wide = false ): void {
		// 1. Clear scheduled cron jobs.
		self::unschedule_cron_jobs();

		// 2. Clear transients.
		self::clear_transients();

		// 3. Flush rewrite rules.
		\flush_rewrite_rules();

		// 4. Fire deactivation hook.
		\do_action( 'apollo_deactivated', $network_wide );

		// 5. Update status.
		\update_option( 'apollo_deactivation_time', \current_time( 'mysql' ) );

		// 6. Log deactivation.
		\error_log( 'Apollo Core deactivated at ' . \current_time( 'mysql' ) );
	}

	/**
	 * Uninstall hook callback.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		// Check if data should be deleted.
		$delete_data = \get_option( 'apollo_delete_data_on_uninstall', false );

		if ( $delete_data ) {
			// 1. Delete all CPT posts.
			self::delete_all_posts();

			// 2. Delete all terms.
			self::delete_all_terms();

			// 3. Drop database tables.
			self::drop_tables();

			// 4. Delete all options.
			self::delete_all_options();

			// 5. Delete user meta.
			self::delete_user_meta();

			// 6. Delete transients.
			self::clear_transients();
		}

		// Fire uninstall hook.
		\do_action( 'apollo_uninstalled', $delete_data );
	}

	/**
	 * Check system requirements.
	 *
	 * @return void
	 * @throws \Exception If requirements are not met.
	 */
	private static function check_requirements(): void {
		$errors = array();

		// Check PHP version.
		if ( \version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			$errors[] = \sprintf(
				/* translators: %1$s: Required PHP version, %2$s: Current PHP version */
				\__( 'Apollo Core requires PHP %1$s or higher. You are running PHP %2$s.', 'apollo-core' ),
				self::MIN_PHP_VERSION,
				PHP_VERSION
			);
		}

		// Check WordPress version.
		global $wp_version;
		if ( \version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			$errors[] = \sprintf(
				/* translators: %1$s: Required WP version, %2$s: Current WP version */
				\__( 'Apollo Core requires WordPress %1$s or higher. You are running WordPress %2$s.', 'apollo-core' ),
				self::MIN_WP_VERSION,
				$wp_version
			);
		}

		// Check required PHP extensions.
		$required_extensions = array( 'json', 'mbstring' );
		foreach ( $required_extensions as $ext ) {
			if ( ! \extension_loaded( $ext ) ) {
				$errors[] = \sprintf(
					/* translators: %s: PHP extension name */
					\__( 'Apollo Core requires the %s PHP extension.', 'apollo-core' ),
					$ext
				);
			}
		}

		if ( ! empty( $errors ) ) {
			\deactivate_plugins( \plugin_basename( APOLLO_CORE_FILE ?? __FILE__ ) );
			\wp_die(
				\implode( '<br>', $errors ),
				\__( 'Plugin Activation Error', 'apollo-core' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$tables = array();

		// Activity log table.
		$tables['apollo_activity_log'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apollo_activity_log (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(100) NOT NULL,
			object_type VARCHAR(50) NOT NULL DEFAULT '',
			object_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			object_subtype VARCHAR(50) NOT NULL DEFAULT '',
			meta LONGTEXT,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent TEXT,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY object_type (object_type, object_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Relationship pivot table (for many-to-many with metadata).
		$tables['apollo_relationships'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apollo_relationships (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			relationship_name VARCHAR(100) NOT NULL,
			from_id BIGINT(20) UNSIGNED NOT NULL,
			from_type VARCHAR(50) NOT NULL DEFAULT 'post',
			to_id BIGINT(20) UNSIGNED NOT NULL,
			to_type VARCHAR(50) NOT NULL DEFAULT 'post',
			order_index INT(11) NOT NULL DEFAULT 0,
			meta LONGTEXT,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_relationship (relationship_name, from_id, to_id),
			KEY from_lookup (from_type, from_id),
			KEY to_lookup (to_type, to_id),
			KEY relationship_name (relationship_name)
		) {$charset_collate};";

		// Event bus queue (for deferred events).
		$tables['apollo_event_queue'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apollo_event_queue (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_name VARCHAR(100) NOT NULL,
			payload LONGTEXT NOT NULL,
			status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
			attempts TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed_at DATETIME DEFAULT NULL,
			error_message TEXT,
			PRIMARY KEY (id),
			KEY status (status),
			KEY scheduled_at (scheduled_at),
			KEY event_name (event_name)
		) {$charset_collate};";

		// Include WordPress upgrade functions.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create tables.
		foreach ( $tables as $table_name => $sql ) {
			\dbDelta( $sql );
		}

		// Update database version.
		\update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Run database migrations.
	 *
	 * @return void
	 */
	private static function run_migrations(): void {
		$current_version = \get_option( self::DB_VERSION_OPTION, '0.0.0' );

		// Migrations array: version => callback.
		$migrations = array(
			'1.0.0' => array( self::class, 'migrate_1_0_0' ),
			'1.5.0' => array( self::class, 'migrate_1_5_0' ),
			'2.0.0' => array( self::class, 'migrate_2_0_0' ),
		);

		foreach ( $migrations as $version => $callback ) {
			if ( \version_compare( $current_version, $version, '<' ) ) {
				if ( \is_callable( $callback ) ) {
					\call_user_func( $callback );
				}
				\update_option( self::DB_VERSION_OPTION, $version );
			}
		}
	}

	/**
	 * Migration for version 1.0.0.
	 *
	 * @return void
	 */
	private static function migrate_1_0_0(): void {
		// Initial setup - nothing to migrate.
	}

	/**
	 * Migration for version 1.5.0.
	 *
	 * @return void
	 */
	private static function migrate_1_5_0(): void {
		// Convert legacy meta keys if they exist.
		global $wpdb;

		$legacy_mappings = array(
			'_apollo_event_djs'    => '_event_dj_ids',
			'_apollo_event_venues' => '_event_local_ids',
		);

		foreach ( $legacy_mappings as $old_key => $new_key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
					$new_key,
					$old_key
				)
			);
		}
	}

	/**
	 * Migration for version 2.0.0.
	 *
	 * @return void
	 */
	private static function migrate_2_0_0(): void {
		// Ensure all relationship meta is properly formatted.
		// This migration normalizes storage formats.
	}

	/**
	 * Register post types for activation.
	 *
	 * @return void
	 */
	private static function register_post_types(): void {
		// CPTs are normally registered on init, but we need them for flush.
		if ( \class_exists( Apollo_CPT_Registry::class ) ) {
			Apollo_CPT_Registry::register_all();
		}

		// Fire hook for companion plugins.
		\do_action( 'apollo_register_post_types' );
	}

	/**
	 * Set default options.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		$defaults = array(
			'apollo_events_enabled'           => true,
			'apollo_social_enabled'           => true,
			'apollo_classifieds_enabled'      => true,
			'apollo_rest_api_enabled'         => true,
			'apollo_relationship_sync'        => true,
			'apollo_integrity_checks'         => true,
			'apollo_delete_data_on_uninstall' => false,
			'apollo_default_event_status'     => 'publish',
			'apollo_events_per_page'          => 12,
			'apollo_enable_rsvp'              => true,
			'apollo_enable_favorites'         => true,
			'apollo_enable_follow'            => true,
			'apollo_enable_bubble'            => true,
			'apollo_moderation_enabled'       => true,
			'apollo_gamification_enabled'     => true,
			'apollo_points_per_post'          => 10,
			'apollo_points_per_comment'       => 5,
			'apollo_points_per_like'          => 2,
		);

		foreach ( $defaults as $option => $value ) {
			if ( false === \get_option( $option ) ) {
				\update_option( $option, $value );
			}
		}
	}

	/**
	 * Schedule cron jobs.
	 *
	 * @return void
	 */
	private static function schedule_cron_jobs(): void {
		$cron_jobs = array(
			'apollo_daily_cleanup'                => array(
				'interval' => 'daily',
				'callback' => 'daily_cleanup',
			),
			'apollo_weekly_digest'                => array(
				'interval' => 'weekly',
				'callback' => 'weekly_digest',
			),
			'apollo_relationship_integrity_check' => array(
				'interval' => 'weekly',
				'callback' => 'integrity_check',
			),
			'apollo_event_reminders'              => array(
				'interval' => 'hourly',
				'callback' => 'event_reminders',
			),
			'apollo_process_event_queue'          => array(
				'interval' => 'every_five_minutes',
				'callback' => 'process_queue',
			),
		);

		// Register custom intervals.
		\add_filter(
			'cron_schedules',
			function ( $schedules ) {
				$schedules['every_five_minutes'] = array(
					'interval' => 300,
					'display'  => \__( 'Every 5 Minutes', 'apollo-core' ),
				);
				return $schedules;
			}
		);

		foreach ( $cron_jobs as $hook => $config ) {
			if ( ! \wp_next_scheduled( $hook ) ) {
				\wp_schedule_event( \time(), $config['interval'], $hook );
			}
		}
	}

	/**
	 * Unschedule cron jobs.
	 *
	 * @return void
	 */
	private static function unschedule_cron_jobs(): void {
		$cron_jobs = array(
			'apollo_daily_cleanup',
			'apollo_weekly_digest',
			'apollo_relationship_integrity_check',
			'apollo_event_reminders',
			'apollo_process_event_queue',
		);

		foreach ( $cron_jobs as $hook ) {
			$timestamp = \wp_next_scheduled( $hook );
			if ( $timestamp ) {
				\wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Create default content.
	 *
	 * @return void
	 */
	private static function create_default_content(): void {
		// Create default terms if taxonomies exist.
		$default_terms = array(
			'event_listing_category' => array(
				'Festas'    => array( 'slug' => 'festas' ),
				'Shows'     => array( 'slug' => 'shows' ),
				'Workshops' => array( 'slug' => 'workshops' ),
				'Encontros' => array( 'slug' => 'encontros' ),
			),
			'event_sounds'           => array(
				'House'       => array( 'slug' => 'house' ),
				'Techno'      => array( 'slug' => 'techno' ),
				'Trance'      => array( 'slug' => 'trance' ),
				'Drum & Bass' => array( 'slug' => 'dnb' ),
			),
		);

		foreach ( $default_terms as $taxonomy => $terms ) {
			if ( \taxonomy_exists( $taxonomy ) ) {
				foreach ( $terms as $name => $args ) {
					if ( ! \term_exists( $name, $taxonomy ) ) {
						\wp_insert_term( $name, $taxonomy, $args );
					}
				}
			}
		}
	}

	/**
	 * Clear all transients.
	 *
	 * @return void
	 */
	private static function clear_transients(): void {
		global $wpdb;

		// Delete Apollo transients.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_apollo_%'
			OR option_name LIKE '_transient_timeout_apollo_%'"
		);

		// Clear object cache if available.
		if ( \function_exists( 'wp_cache_flush' ) ) {
			\wp_cache_flush();
		}
	}

	/**
	 * Delete all Apollo posts.
	 *
	 * @return void
	 */
	private static function delete_all_posts(): void {
		$post_types = array(
			'apollo-event',
			'apollo-dj',
			'apollo-local',
			'apollo-classified',
			'apollo-supplier',
			'apollo-social',
			'event_listing',
			'event_dj',
			'event_local',
		);

		foreach ( $post_types as $post_type ) {
			$posts = \get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);

			foreach ( $posts as $post_id ) {
				\wp_delete_post( $post_id, true );
			}
		}
	}

	/**
	 * Delete all Apollo terms.
	 *
	 * @return void
	 */
	private static function delete_all_terms(): void {
		$taxonomies = array(
			'event_listing_category',
			'event_listing_type',
			'event_listing_tag',
			'event_sounds',
			'apollo_genre',
			'apollo_location',
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = \get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( ! \is_wp_error( $terms ) ) {
				foreach ( $terms as $term_id ) {
					\wp_delete_term( $term_id, $taxonomy );
				}
			}
		}
	}

	/**
	 * Drop database tables.
	 *
	 * @return void
	 */
	private static function drop_tables(): void {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}apollo_activity_log",
			"{$wpdb->prefix}apollo_relationships",
			"{$wpdb->prefix}apollo_event_queue",
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	/**
	 * Delete all Apollo options.
	 *
	 * @return void
	 */
	private static function delete_all_options(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'apollo_%'"
		);
	}

	/**
	 * Delete Apollo user meta.
	 *
	 * @return void
	 */
	private static function delete_user_meta(): void {
		global $wpdb;

		$meta_keys = array(
			'_user_event_rsvps',
			'_user_followers',
			'_user_following',
			'_user_favorites',
			'_user_bubble',
			'_user_close_friends',
			'_apollo_points',
			'_apollo_badges',
			'_apollo_onboarding_complete',
		);

		foreach ( $meta_keys as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query(
				$wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s", $key )
			);
		}
	}

	/**
	 * Log activation.
	 *
	 * @return void
	 */
	private static function log_activation(): void {
		$log_data = array(
			'time'        => \current_time( 'mysql' ),
			'php_version' => PHP_VERSION,
			'wp_version'  => \get_bloginfo( 'version' ),
			'db_version'  => self::DB_VERSION,
			'plugins'     => self::get_active_apollo_plugins(),
		);

		\update_option( 'apollo_activation_log', $log_data );
		\error_log( 'Apollo Core activated: ' . \wp_json_encode( $log_data ) );
	}

	/**
	 * Get active Apollo plugins.
	 *
	 * @return array
	 */
	private static function get_active_apollo_plugins(): array {
		$plugins = array();
		$active  = \get_option( 'active_plugins', array() );

		foreach ( $active as $plugin ) {
			if ( \strpos( $plugin, 'apollo-' ) !== false ) {
				$plugins[] = $plugin;
			}
		}

		return $plugins;
	}
}
