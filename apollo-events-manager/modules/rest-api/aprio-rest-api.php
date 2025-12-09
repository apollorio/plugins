<?php
// phpcs:ignoreFile
/**
 * Plugin Name: Apollo Events Manager - REST API
 * Plugin URI: https://apollo.rio.br/
 *
 * Description: REST API for Apollo Events Manager - Lets users connect mobile applications with Apollo Events.
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 *
 * Text Domain: apollo-rest-api
 * Domain Path: /languages
 * Version: 2.0.0
 * Since: 1.0.0
 *
 * Requires WordPress Version at least: 6.5.1
 * Requires Apollo Events Manager: 2.0.0
 * Copyright: 2025 Apollo::Rio
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
/**
 * WP_Event_Manager_Rest_API class.
 */
class APRIO_Rest_API {

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Check if Apollo Events Manager is active
		if ( ! in_array( 'apollo-events-manager/apollo-events-manager.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			// Admin notice if Apollo EM is not active
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>';
					echo '<strong>Apollo Events Manager - REST API</strong> requires <strong>Apollo Events Manager 2.0.0+</strong> to be installed and active.';
					echo '</p></div>';
				}
			);
			return;
		}

		// Define constants
		define( 'APRIO_REST_API_VERSION', '1.2.0' );
		define( 'APRIO_REST_API_FILE', __FILE__ );
		define( 'APRIO_REST_API_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'APRIO_REST_API_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'APRIO_PLUGIN_ACTIVATION_API_URL', 'https://wp-eventmanager.com/?wc-api=apriostore_licensing_expire_license' );

		// Compatibility: Define EVENT_MANAGER_PLUGIN_URL if not already defined
		if ( ! defined( 'EVENT_MANAGER_PLUGIN_URL' ) ) {
			// Point to Apollo Events Manager assets instead
			define( 'EVENT_MANAGER_PLUGIN_URL', plugins_url( 'apollo-events-manager' ) );
		}

		/**
		 * JWT_SECRET_KEY: NEVER use the hard-coded fallback in production.
		 * Define APOLLO_JWT_SECRET in wp-config.php or use the apollo_jwt_secret option.
		 */
		if ( ! defined( 'JWT_SECRET_KEY' ) ) {
			$jwt_secret = defined( 'APOLLO_JWT_SECRET' ) ? APOLLO_JWT_SECRET : get_option( 'apollo_jwt_secret', '' );
			if ( empty( $jwt_secret ) ) {
				// Generate a secure secret once and persist it.
				$jwt_secret = wp_generate_password( 64, true, true );
				update_option( 'apollo_jwt_secret', $jwt_secret );
			}
			define( 'JWT_SECRET_KEY', $jwt_secret );
		}
		if ( ! defined( 'JWT_ALGO' ) ) {
			define( 'JWT_ALGO', 'HS256' );
		}
		if ( is_admin() ) {
			include 'admin/aprio-rest-api-admin.php';
		}
		// include
		include 'aprio-rest-api-functions.php';

		include 'includes/aprio-rest-api-dashboard.php';
		include 'includes/aprio-rest-conroller.php';
		include 'includes/aprio-rest-posts-conroller.php';
		include 'includes/aprio-rest-crud-controller.php';
		include 'includes/aprio-rest-authentication.php';
		include 'includes/aprio-rest-events-controller.php';
		include 'includes/aprio-rest-app-branding.php';
		include 'includes/aprio-rest-ecosystem-controller.php';

		// match making api
		include 'includes/aprio-rest-matchmaking-profile.php';
		include 'includes/aprio-rest-matchmaking-get-texonomy.php';
		include 'includes/aprio-rest-matchmaking-user-messages.php';
		include 'includes/aprio-rest-matchmaking-filter-users.php';
		include 'includes/aprio-rest-matchmaking-user-settings.php';
		include 'includes/aprio-rest-matchmaking-create-meetings.php';
		include 'includes/aprio-rest-matchmaking-meetings-controller.php';
		include 'includes/aprio-rest-matchmaking-profile-controller.php';
		include 'includes/aprio-rest-matchmaking-settings-controller.php';
		include 'includes/aprio-rest-user-registered-events-controller.php';

		// Activate
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 12 );

		// Call when update plugin
		add_action( 'admin_init', array( $this, 'updater' ) );
		// Restrict Scanner role from accessing wp-admin
		add_action( 'admin_init', array( $this, 'restrict_scanner_admin' ) );
		// Enforce that Scanner users can only manage their own registrations
		// add_filter('map_meta_cap', array($this, 'limit_scanner_own_registration'), 10, 4);
	}

	/**
	 * Localisation
	 **/
	public function load_plugin_textdomain() {
		$domain = 'aprio-rest-api';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/aprio-rest-api/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Handle Updates.
	 *
	 * @since 1.1.2
	 */
	public function updater() {
		if ( version_compare( get_option( 'aprio_rest_api_version', APRIO_REST_API_VERSION ), '1.0.9', '<' ) ) {
			$this->check_rest_api_table();
			flush_rewrite_rules();
		}
		if ( version_compare( APRIO_REST_API_VERSION, get_option( 'aprio_rest_api_version' ), '>' ) ) {
			// Ensure roles/capabilities exist on admin init (covers updates)
			$this->init_user_roles();
			flush_rewrite_rules();
		}
	}

	/**
	 * Check rest api table
	 *
	 * @since 1.1.2
	 * @return void
	 */
	public function check_rest_api_table() {
		global $wpdb;

		$wpdb->hide_errors();
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table for storing licence keys for purchases
		$sql = "
            CREATE TABLE {$wpdb->prefix}aprio_rest_api_keys (
            key_id BIGINT UNSIGNED NOT NULL auto_increment,
            app_key varchar(200) NOT NULL,	
            user_id BIGINT UNSIGNED NOT NULL,
            event_id varchar(255) NULL,
            description varchar(200) NULL,
            permissions varchar(10) NOT NULL,
            consumer_key char(64) NOT NULL,
            consumer_secret char(43) NOT NULL,
            nonces longtext NULL,
            truncated_key char(7) NOT NULL,
            last_access datetime NULL default null,
            event_show_by varchar(20) NULL default 'loggedin',
			selected_events longtext NULL,
            date_created datetime NULL default null,
            date_expires datetime NULL default null,
            PRIMARY KEY  (key_id),
            KEY consumer_key (consumer_key),
            KEY consumer_secret (consumer_secret)
            ) $collate;";

		dbDelta( $sql );

		// Check if we need to alter existing table
		$table_name = $wpdb->prefix . 'aprio_rest_api_keys';

		// Verify table exists before attempting ALTER
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		if ( $table_exists ) {
			$columns = $wpdb->get_col( "DESC {$table_name}", 0 );

			// Add event_show_by column if it doesn't exist
			if ( ! in_array( 'event_show_by', $columns ) ) {
				$result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN event_show_by varchar(20) NULL DEFAULT 'loggedin'" );
				if ( $result === false ) {
					error_log( 'APRIO REST API: Failed to add event_show_by column - ' . $wpdb->last_error );
				}
			}

			// Add selected_events column if it doesn't exist
			if ( ! in_array( 'selected_events', $columns ) ) {
				$result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN selected_events longtext NULL" );
				if ( $result === false ) {
					error_log( 'APRIO REST API: Failed to add selected_events column - ' . $wpdb->last_error );
				}
			}
		}

		update_option( 'aprio_rest_api_version', APRIO_REST_API_VERSION );

		// check for Application Name is already defined
		if ( empty( get_option( 'aprio_rest_api_app_name' ) ) ) {
			update_option( 'aprio_rest_api_app_name', 'WP Event Manager' );
		}
	}

	/**
	 * Init user roles.
	 * Creates/updates roles and capabilities used by this plugin.
	 */
	private function init_user_roles() {
		global $wp_roles;
		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {
			// Create Scanner role if not exists with minimal base caps.
			add_role(
				'aprio-scanner',
				__( 'Ticket Scanner', 'aprio-rest-api' ),
				array(
					'read'         => true,
					'edit_posts'   => false,
					'delete_posts' => false,
				)
			);

			// Ensure administrator has full capabilities.
			$capabilities = $this->get_core_capabilities();
			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}

			// Grant Scanner role same capabilities as WooCommerce 'customer' and add registration caps.
			if ( $role = get_role( 'aprio-scanner' ) ) {
				// Mirror WooCommerce customer capabilities if role exists.
				if ( $customer = get_role( 'customer' ) ) {
					if ( is_array( $customer->capabilities ) ) {
						foreach ( $customer->capabilities as $cap => $grant ) {
							if ( $grant ) {
								$role->add_cap( $cap );
							}
						}
					}
				}

				// Add own registration management capabilities.
				// $scanner_caps = array(
				// 'read_event_registration',
				// 'edit_event_registration',
				// 'edit_event_registrations',
				// 'create_event_registrations',
				// 'publish_event_registrations',
				// 'delete_event_registration',
				// 'delete_event_registrations',
				// 'edit_published_event_registrations',
				// 'delete_published_event_registrations',
				// );
				// foreach ( $scanner_caps as $cap ) {
				// $role->add_cap( $cap );
				// }
			}//end if
		}//end if
	}

	/**
	 * Get capabilities required by the plugin.
	 *
	 * @return array
	 */
	private function get_core_capabilities() {
		return array(
			'core'               => array(
				'manage_event_registrations',
			),
			'event_registration' => array(
				'edit_event_registration',
				'read_event_registration',
				'delete_event_registration',
				'edit_event_registrations',
				'edit_others_event_registrations',
				'publish_event_registrations',
				'read_private_event_registrations',
				'delete_event_registrations',
				'delete_private_event_registrations',
				'delete_published_event_registrations',
				'delete_others_event_registrations',
				'edit_private_event_registrations',
				'edit_published_event_registrations',
			),
		);
	}

	/**
	 * Restrict Scanner role from accessing wp-admin (except AJAX).
	 */
	public function restrict_scanner_admin() {
		if ( ! is_admin() ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		$user = wp_get_current_user();
		if ( $user && is_array( $user->roles ) && in_array( 'aprio-scanner', $user->roles, true ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Enforce that Scanner users can only manage their own event_registration posts.
	 * Denies edit/read/delete on registrations not authored by the current Scanner user.
	 */
	public function limit_scanner_own_registration( $caps, $cap, $user_id, $args ) {
		// Only act on singular registration caps where a post ID is present
		$target_caps = array( 'edit_event_registration', 'delete_event_registration', 'read_event_registration' );
		if ( ! in_array( $cap, $target_caps, true ) ) {
			return $caps;
		}

		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $post_id ) {
			return $caps;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'event_registration' !== $post->post_type ) {
			return $caps;
		}

		// If user has a higher capability (e.g., admin), don't restrict.
		if ( user_can( $user_id, 'manage_event_registrations' ) || user_can( $user_id, 'administrator' ) ) {
			return $caps;
		}

		// Get user roles
		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->roles ) ) {
			return $caps;
		}

		// If the user is a Scanner and is trying to manage someone else's registration, deny.
		if ( in_array( 'aprio-scanner', (array) $user->roles, true ) && (int) $post->post_author !== (int) $user_id ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * Install
	 */
	public function install() {
		$this->check_rest_api_table();
		$this->init_user_roles();
	}
}

// Check for Apollo Events Manager is active
if ( is_plugin_active( 'apollo-events-manager/apollo-events-manager.php' ) ) {
	$GLOBALS['aprio_rest_api'] = new APRIO_Rest_API();
}

/**
 * Check if WP Event Manager is not active then show notice at admin
 *
 * @since 1.0.0
 */
function aprio_rest_api_pre_check_before_installing_event_rest_api() {
	/*
	* Check weather WP Event Manager is installed or not
	*/
	if ( ! in_array( 'apollo-events-manager/apollo-events-manager.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			global $pagenow;
		if ( $pagenow == 'plugins.php' ) {
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __( 'Apollo Events Manager is required to use Apollo Events Manager REST API ', 'apollo-rest-api' );
			echo '</p></div>';
		}
		return false;
	}
}
add_action( 'admin_notices', 'aprio_rest_api_pre_check_before_installing_event_rest_api' );
