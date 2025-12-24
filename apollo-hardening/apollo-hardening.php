<?php
/**
 * Plugin Name: Apollo Hardening
 * Plugin URI: https://apollorio.com/plugins/hardening
 * Description: Security hardening plugin for Apollo ecosystem, based on simple-security-plugin
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Apollo Team
 * Author URI: https://apollorio.com
 * License: MIT
 * Text Domain: apollo-hardening
 *
 * @package Apollo_Hardening
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Apollo Core is active
if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p><strong>Apollo Hardening:</strong> Requires Apollo Core plugin to be active.</p></div>';
		}
	);
	return;
}

/**
 * Security Settings
 */
const APOLLO_HARDENING_SETTINGS = array(
	'disable_xmlrpc'      => array(
		'label'       => 'Disable XML-RPC',
		'description' => 'Disables XML-RPC to prevent pingbacks and reduce attack surface.',
		'input'       => 'settingsCheckbox',
	),
	'hide_wp_version'     => array(
		'label'       => 'Hide WordPress Version',
		'description' => 'Removes WordPress version from headers and scripts.',
		'input'       => 'settingsCheckbox',
	),
	'disable_file_editor' => array(
		'label'       => 'Disable File Editor in Admin',
		'description' => 'Prevents editing themes and plugins via admin panel.',
		'input'       => 'settingsCheckbox',
	),
	'secure_headers'      => array(
		'label'       => 'Enforce Secure Headers',
		'description' => 'Adds X-Frame-Options, X-Content-Type-Options, and basic CSP.',
		'input'       => 'settingsCheckbox',
	),
	'restrict_rest_api'   => array(
		'label'       => 'Restrict REST API for Unauthenticated Users',
		'description' => 'Limits REST API access, whitelisting Apollo endpoints.',
		'input'       => 'settingsCheckbox',
	),
);

/**
 * Business Logic - Disable XML-RPC
 */
function apollo_hardening_disable_xmlrpc(): void {
	add_filter( 'xmlrpc_enabled', fn () => false );
	add_filter( 'pings_open', fn () => false );
	add_filter( 'wp_headers', fn ( $headers ) => array_merge( $headers, array( 'X-Pingback' => null ) ) );
}

/**
 * Business Logic - Hide WordPress Version
 */
function apollo_hardening_hide_wp_version(): void {
	add_filter( 'the_generator', fn () => '' );
	add_filter( 'style_loader_src', fn ( $src ) => remove_query_arg( 'ver', $src ), PHP_INT_MAX );
	add_filter( 'script_loader_src', fn ( $src ) => remove_query_arg( 'ver', $src ), PHP_INT_MAX );
}

/**
 * Business Logic - Disable File Editor
 */
function apollo_hardening_disable_file_editor(): void {
	if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- DISALLOW_FILE_EDIT is a WordPress core constant.
		define( 'DISALLOW_FILE_EDIT', true );
	}
}

/**
 * Business Logic - Add Secure Headers
 */
function apollo_hardening_secure_headers(): void {
	add_action(
		'send_headers',
		function () {
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'X-Content-Type-Options: nosniff' );
			header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'" );
		}
	);
}

/**
 * Business Logic - Restrict REST API
 */
function apollo_hardening_restrict_rest_api(): void {
	add_filter(
		'rest_authentication_errors',
		function ( $result ) {
			if ( ! empty( $result ) ) {
				return $result;
			}
			if ( ! is_user_logged_in() ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				$allowed     = array(
					'/wp-json/apollo-core/',
					'/wp-json/apollo-events-manager/',
					'/wp-json/apollo-social/',
				);
				foreach ( $allowed as $prefix ) {
					if ( strpos( $request_uri, $prefix ) === 0 ) {
						return $result;
					}
				}

				return new WP_Error( 'rest_not_logged_in', __( 'You are not authorized to access this endpoint.', 'apollo-hardening' ), array( 'status' => 401 ) );
			}

			return $result;
		}
	);
}

/**
 * WordPress Plugin
 *
 * phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Plugin architecture requires both functions and class.
 */
if ( ! class_exists( 'ApolloHardening' ) ) {
	class ApolloHardening {

		/**
		 * Initializers
		 */
		public static function initPages() {
			// Changed to always use Apollo Cabin as parent menu to organize better
			add_submenu_page(
				'apollo-cabin',
				__( 'Apollo Hardening', 'apollo-hardening' ),
				__( '🔒 Hardening', 'apollo-hardening' ),
				__( 'Hardening', 'apollo-hardening' ),
				'manage_apollo_security',
				'apollo-hardening-settings',
				array( self::class, 'settingsPage' )
			);
		}

		public static function enqueueStyles( $hook ) {
			if ( $hook === 'apollo-core_page_apollo-hardening-settings' ) {
				wp_enqueue_style(
					'apollo-hardening-uni',
					plugins_url( 'apollo-core/templates/design-library/assets/uni.css', dirname( __DIR__, 1 ) ),
					array(),
					'1.0.0'
				);
			}
		}

		public static function initSettings() {
			register_setting( 'apollo_hardening', 'apollo_hardening_settings' );
			add_settings_section(
				'apollo_hardening_core_section',
				__( 'Apollo Hardening Plugin', 'apollo-hardening' ),
				array( self::class, 'settingsCoreSection' ),
				'apollo_hardening'
			);

			foreach ( APOLLO_HARDENING_SETTINGS as $field => $args ) {
				add_settings_field(
					$field,
					esc_html( $args['label'] ),
					array( self::class, $args['input'] ),
					'apollo_hardening',
					'apollo_hardening_core_section',
					array_merge( array( 'field' => $field ), $args )
				);
			}
		}

		public static function settingsCoreSection(): void {
			?>
			<p>
				These settings enhance the security of your Apollo WordPress installation.
			</p>
			<p>
				Enable features to harden against common vulnerabilities.
			</p>
			<?php
		}

		public static function settingsCheckbox( $args ): void {
			$options = get_option( 'apollo_hardening_settings' );
			$checked = isset( $options[ $args['field'] ] ) && $options[ $args['field'] ] ? "checked='checked'" : '';
			?>
			<input
					id="<?php echo esc_attr( $args['field'] ); ?>"
					type='checkbox'
					name='apollo_hardening_settings[<?php echo esc_attr( $args['field'] ); ?>]'
					<?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
			<label for="<?php echo esc_attr( $args['field'] ); ?>"><?php echo esc_html( $args['description'] ); ?></label>
			<?php
		}

		public static function settingsPage(): void {
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'apollo_hardening' );
				do_settings_sections( 'apollo_hardening' );
				submit_button();
				?>
			</form>
			<?php
		}

		public static function applySettings(): void {
			$options = get_option( 'apollo_hardening_settings' );
			if ( ! $options ) {
				return;
			}
			foreach ( $options as $key => $value ) {
				if ( $value !== 'on' ) {
					continue;
				}

				$function_name = 'apollo_hardening_' . $key;
				if ( function_exists( $function_name ) ) {
					call_user_func( $function_name );
				}
			}
		}

		/**
		 * Log settings changes for audit
		 *
		 * @param mixed  $old_value Old option value.
		 * @param mixed  $new_value New option value.
		 * @param string $option Option name (unused but required by WordPress hook).
		 *
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by WordPress hook signature.
		 */
		public static function logSettingsChange( $old_value, $new_value, $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by WordPress hook signature.
			unset( $option ); // Mark as intentionally unused.
			if ( ! function_exists( 'Apollo_Audit_Log' ) ) {
				$audit_file = plugin_dir_path( __FILE__ ) . '../apollo-core/includes/class-apollo-audit-log.php';
				if ( file_exists( $audit_file ) ) {
					require_once $audit_file;
				}
			}

			$changes      = array();
			$old_settings = is_array( $old_value ) ? $old_value : array();
			$new_settings = is_array( $new_value ) ? $new_value : array();

			foreach ( APOLLO_HARDENING_SETTINGS as $key => $config ) {
				$old_val = isset( $old_settings[ $key ] ) ? $old_settings[ $key ] : 'off';
				$new_val = isset( $new_settings[ $key ] ) ? $new_settings[ $key ] : 'off';

				if ( $old_val !== $new_val ) {
					$changes[] = $config['label'] . ': ' . $old_val . ' → ' . $new_val;
				}
			}

			if ( ! empty( $changes ) ) {
				Apollo_Audit_Log::log_event(
					'security_settings_change',
					array(
						'message'  => 'Apollo Hardening settings updated',
						'context'  => array(
							'changes'      => $changes,
							'old_settings' => $old_settings,
							'new_settings' => $new_settings,
						),
						'severity' => 'warning',
					)
				);
			}
		}
	}

	add_action( 'admin_menu', array( ApolloHardening::class, 'initPages' ) );
	add_action( 'admin_init', array( ApolloHardening::class, 'initSettings' ) );
	add_action( 'admin_enqueue_scripts', array( ApolloHardening::class, 'enqueueStyles' ) );
	add_action( 'init', array( ApolloHardening::class, 'applySettings' ) );
	add_action( 'update_option_apollo_hardening_settings', array( ApolloHardening::class, 'logSettingsChange' ), 10, 3 );
}
