<?php
/**
 * Apollo SEOPress Integration
 *
 * Initializes SEOPress settings on plugin activation.
 * Sets default meta title/description based on site name and tagline.
 * Enables XML sitemaps.
 *
 * @package Apollo_Core
 * @since   1.0.0
 *
 * SAFETY NOTES:
 * - Only activates if SEOPress plugin is detected via SEOPRESS_VERSION constant
 * - Uses one-time initialization flag to prevent overwriting user settings
 * - Validates all values before saving to options
 * - Provides admin notices for status visibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_SEOPress_Integration
 *
 * @tooltip This class auto-configures SEOPress with Apollo-optimized defaults.
 *          Only runs once on first detection. Manual changes are preserved.
 */
class Apollo_SEOPress_Integration {

	/** @var string Option name for initialization flag. */
	const INIT_FLAG = 'apollo_seopress_initialized';

	/** @var bool Flag indicating if SEOPress is available. */
	private static bool $seopress_available = false;

	/** @var string SEOPress version if available. */
	private static string $seopress_version = '';

	/**
	 * Initialize hooks.
	 *
	 * @tooltip Checks for SEOPress before registering any hooks.
	 *          Displays helpful admin notices about integration status.
	 */
	public static function init(): void {
		// Detect SEOPress availability.
		self::detect_seopress();

		// Only proceed if SEOPress is active.
		if ( ! self::$seopress_available ) {
			add_action( 'admin_notices', array( __CLASS__, 'maybe_show_plugin_notice' ) );
			return;
		}

		// Set defaults on first run (admin only for safety).
		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_set_defaults' ), 20 );
			add_action( 'admin_notices', array( __CLASS__, 'show_status_notice' ) );
		}
	}

	/**
	 * Detect SEOPress plugin and version.
	 */
	private static function detect_seopress(): void {
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			self::$seopress_available = true;
			self::$seopress_version   = SEOPRESS_VERSION;
		}
	}

	/**
	 * Show status notice on Apollo admin pages.
	 */
	public static function show_status_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'apollo' ) === false ) {
			return;
		}

		$initialized = get_option( self::INIT_FLAG );

		echo '<div class="notice notice-info is-dismissible apollo-seo-status">';
		echo '<p><span class="dashicons dashicons-search" style="color:#0073aa;"></span> ';
		printf(
			/* translators: %s: SEOPress version */
			esc_html__( 'Apollo SEOPress Integration: Active (SEOPress v%s)', 'apollo-core' ),
			esc_html( self::$seopress_version )
		);

		if ( $initialized ) {
			echo ' <span style="color:#46b450;">✓ ' . esc_html__( 'Configured', 'apollo-core' ) . '</span>';
			echo ' <span class="apollo-tooltip" title="' . esc_attr__( 'Apollo has set default SEO settings. You can customize them in SEOPress settings.', 'apollo-core' ) . '">ℹ️</span>';
		}

		echo '</p></div>';
	}

	/**
	 * Show notice if SEOPress is not installed (only on plugins page).
	 */
	public static function maybe_show_plugin_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p><span class="dashicons dashicons-search" style="color:#ffb900;"></span> ';
		echo '<strong>' . esc_html__( 'Apollo SEO Integration:', 'apollo-core' ) . '</strong> ';
		echo esc_html__( 'To enable automatic SEO configuration, install SEOPress plugin.', 'apollo-core' );
		echo ' <a href="' . esc_url( admin_url( 'plugin-install.php?s=seopress&tab=search&type=term' ) ) . '">';
		echo esc_html__( 'Install Now', 'apollo-core' ) . '</a>';
		echo '</p></div>';
	}

	/**
	 * Set SEOPress defaults if not already configured.
	 *
	 * @tooltip Only runs once. Checks initialization flag before making any changes.
	 *          All existing user settings are preserved.
	 */
	public static function maybe_set_defaults(): void {
		// SAFETY: Check if we've already run.
		if ( get_option( self::INIT_FLAG ) ) {
			return;
		}

		// SAFETY: Verify SEOPress is still active (could have been deactivated).
		if ( ! defined( 'SEOPRESS_VERSION' ) ) {
			return;
		}

		// SAFETY: Only allow admins to trigger initialization.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$errors = array();

		// Set title settings with error handling.
		$title_result = self::set_title_settings();
		if ( is_wp_error( $title_result ) ) {
			$errors[] = $title_result->get_error_message();
		}

		// Enable sitemaps with error handling.
		$sitemap_result = self::enable_xml_sitemaps();
		if ( is_wp_error( $sitemap_result ) ) {
			$errors[] = $sitemap_result->get_error_message();
		}

		// Mark as initialized only if no critical errors.
		if ( empty( $errors ) ) {
			update_option( self::INIT_FLAG, array(
				'timestamp' => current_time( 'mysql' ),
				'version'   => self::$seopress_version,
				'user'      => get_current_user_id(),
			) );

			// Log success.
			self::log_info( 'SEOPress defaults configured successfully' );
		} else {
			self::log_error( 'SEOPress configuration had errors', $errors );
		}
	}

	/**
	 * Set default title settings.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 *
	 * @tooltip Sets home page title and description based on WordPress site settings.
	 *          Does not overwrite existing values.
	 */
	private static function set_title_settings() {
		// SAFETY: Get and validate site info.
		$site_name = get_bloginfo( 'name' );
		$tagline   = get_bloginfo( 'description' );

		// SAFETY: Ensure we have valid data.
		if ( empty( $site_name ) ) {
			$site_name = __( 'Apollo Site', 'apollo-core' );
		}

		// SAFETY: Sanitize values.
		$site_name = sanitize_text_field( $site_name );
		$tagline   = sanitize_text_field( $tagline );

		// SAFETY: Get existing options with fallback.
		$titles = get_option( 'seopress_titles_option_name' );
		if ( ! is_array( $titles ) ) {
			$titles = array();
		}

		// Home page title - only set if empty.
		if ( empty( $titles['seopress_titles_home_site_title'] ) ) {
			$titles['seopress_titles_home_site_title'] = $site_name . ( ! empty( $tagline ) ? ' - ' . $tagline : '' );
		}

		// Home page meta description - only set if empty.
		if ( empty( $titles['seopress_titles_home_site_desc'] ) ) {
			$titles['seopress_titles_home_site_desc'] = ! empty( $tagline ) ? $tagline : $site_name;
		}

		// Separator - only set if empty.
		if ( empty( $titles['seopress_titles_sep'] ) ) {
			$titles['seopress_titles_sep'] = '-';
		}

		// SAFETY: Validate before saving.
		$titles = array_map( 'sanitize_text_field', $titles );

		$result = update_option( 'seopress_titles_option_name', $titles );

		return $result !== false ? true : new WP_Error( 'save_failed', __( 'Failed to save title settings', 'apollo-core' ) );
	}

	/**
	 * Enable XML sitemaps.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 *
	 * @tooltip Enables XML sitemap and includes event_listing post type.
	 *          Does not overwrite existing configuration.
	 */
	private static function enable_xml_sitemaps() {
		// SAFETY: Get existing options with fallback.
		$xml = get_option( 'seopress_xml_sitemap_option_name' );
		if ( ! is_array( $xml ) ) {
			$xml = array();
		}

		// Enable XML sitemap - only if not already set.
		if ( ! isset( $xml['seopress_xml_sitemap_general_enable'] ) ) {
			$xml['seopress_xml_sitemap_general_enable'] = '1';
		}

		// Enable HTML sitemap - only if not already set.
		if ( ! isset( $xml['seopress_xml_sitemap_html_enable'] ) ) {
			$xml['seopress_xml_sitemap_html_enable'] = '1';
		}

		// Include post types - only if not already set.
		if ( ! isset( $xml['seopress_xml_sitemap_post_types_list'] ) ) {
			// SAFETY: Only include post types that exist.
			$post_types = array();

			if ( post_type_exists( 'post' ) ) {
				$post_types['post'] = array( 'include' => '1' );
			}
			if ( post_type_exists( 'page' ) ) {
				$post_types['page'] = array( 'include' => '1' );
			}
			if ( post_type_exists( 'event_listing' ) ) {
				$post_types['event_listing'] = array( 'include' => '1' );
			}

			$xml['seopress_xml_sitemap_post_types_list'] = $post_types;
		}

		$result = update_option( 'seopress_xml_sitemap_option_name', $xml );

		return $result !== false ? true : new WP_Error( 'save_failed', __( 'Failed to save sitemap settings', 'apollo-core' ) );
	}

	/**
	 * Check if SEOPress integration is available.
	 *
	 * @return bool True if SEOPress is active.
	 *
	 * @tooltip Use this to check SEOPress availability before relying on SEO features.
	 */
	public static function is_available(): bool {
		return self::$seopress_available;
	}

	/**
	 * Check if initialization has been completed.
	 *
	 * @return bool True if defaults have been set.
	 */
	public static function is_initialized(): bool {
		return (bool) get_option( self::INIT_FLAG );
	}

	/**
	 * Reset initialization flag (for re-running setup).
	 *
	 * @return bool True on success.
	 */
	public static function reset_initialization(): bool {
		return delete_option( self::INIT_FLAG );
	}

	/**
	 * Log informational messages (debug mode only).
	 *
	 * @param string $message Message to log.
	 */
	private static function log_info( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Apollo SEOPress] ' . $message );
		}
	}

	/**
	 * Log errors (debug mode only).
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context.
	 */
	private static function log_error( string $message, array $context = array() ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf(
				'[Apollo SEOPress ERROR] %s | Context: %s',
				$message,
				wp_json_encode( $context )
			) );
		}
	}
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', array( 'Apollo_SEOPress_Integration', 'init' ) );
