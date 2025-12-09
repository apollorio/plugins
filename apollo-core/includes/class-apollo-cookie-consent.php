<?php
/**
 * Apollo GDPR Cookie Consent Banner
 *
 * Outputs a cookie consent banner and stores user consent in wp_options.
 * Blocks analytics scripts until consent is given.
 *
 * @package Apollo_Core
 * @since   1.0.0
 *
 * SAFETY NOTES:
 * - Validates all user input and cookie values
 * - Uses secure cookie settings (HttpOnly, Secure on HTTPS)
 * - Nonce protection on AJAX handlers
 * - Graceful fallbacks if assets fail to load
 * - Admin settings panel for customization
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Cookie_Consent
 *
 * @tooltip GDPR-compliant cookie consent banner for Apollo.
 *          Blocks analytics until user accepts cookies.
 */
class Apollo_Cookie_Consent {

	/** @var string Option name for consent settings. */
	const OPTION_NAME = 'apollo_cookie_consent_settings';

	/** @var string Cookie name for user consent. */
	const COOKIE_NAME = 'apollo_cookie_consent';

	/** @var int Cookie expiry in seconds (1 year). */
	const COOKIE_EXPIRY = YEAR_IN_SECONDS;

	/** @var array Valid consent values. */
	const VALID_CONSENT_VALUES = array( 'accepted', 'declined', 'pending' );

	/**
	 * Initialize hooks.
	 *
	 * @tooltip Registers all necessary hooks for the consent banner.
	 *          Safe to call multiple times (hooks are only added once).
	 */
	public static function init(): void {
		// Frontend hooks.
		add_action( 'wp_footer', array( __CLASS__, 'render_banner' ), 100 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// AJAX handlers (with nonce verification).
		add_action( 'wp_ajax_apollo_save_consent', array( __CLASS__, 'ajax_save_consent' ) );
		add_action( 'wp_ajax_nopriv_apollo_save_consent', array( __CLASS__, 'ajax_save_consent' ) );

		// Filter to check if analytics can load.
		add_filter( 'apollo_can_load_analytics', array( __CLASS__, 'can_load_analytics' ) );

		// Admin settings.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
			add_action( 'admin_notices', array( __CLASS__, 'show_admin_status' ) );
		}
	}

	/**
	 * Add settings submenu under Apollo Core.
	 */
	public static function add_settings_page(): void {
		add_submenu_page(
			'apollo-core-hub',
			__( 'Cookie Consent', 'apollo-core' ),
			__( 'Cookie Consent', 'apollo-core' ),
			'manage_options',
			'apollo-cookie-consent',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'apollo_cookie_consent_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 *
	 * @tooltip Validates and sanitizes all user-provided settings.
	 */
	public static function sanitize_settings( $input ): array {
		$defaults  = self::get_default_settings();
		$sanitized = array();

		// Sanitize message.
		$sanitized['message'] = isset( $input['message'] )
			? sanitize_textarea_field( $input['message'] )
			: $defaults['message'];

		// Sanitize button texts.
		$sanitized['accept_text'] = isset( $input['accept_text'] )
			? sanitize_text_field( $input['accept_text'] )
			: $defaults['accept_text'];

		$sanitized['decline_text'] = isset( $input['decline_text'] )
			? sanitize_text_field( $input['decline_text'] )
			: $defaults['decline_text'];

		// Sanitize and validate URL.
		$sanitized['privacy_url'] = isset( $input['privacy_url'] )
			? esc_url_raw( $input['privacy_url'] )
			: $defaults['privacy_url'];

		// Sanitize enable/disable flag.
		$sanitized['enabled'] = isset( $input['enabled'] ) ? (bool) $input['enabled'] : true;

		return $sanitized;
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings array.
	 */
	private static function get_default_settings(): array {
		return array(
			'message'      => __( 'We use cookies to improve your experience. By continuing to use this site, you consent to our use of cookies.', 'apollo-core' ),
			'accept_text'  => __( 'Accept', 'apollo-core' ),
			'decline_text' => __( 'Decline', 'apollo-core' ),
			'privacy_url'  => '',
			'enabled'      => true,
		);
	}

	/**
	 * Show admin status notice on Apollo pages.
	 */
	public static function show_admin_status(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'apollo' ) === false ) {
			return;
		}

		$settings = self::get_settings();

		echo '<div class="notice notice-info is-dismissible apollo-cookie-status">';
		echo '<p><span class="dashicons dashicons-shield" style="color:#0073aa;"></span> ';
		echo '<strong>' . esc_html__( 'Apollo Cookie Consent:', 'apollo-core' ) . '</strong> ';

		if ( $settings['enabled'] ) {
			echo '<span style="color:#46b450;">✓ ' . esc_html__( 'Active', 'apollo-core' ) . '</span>';
		} else {
			echo '<span style="color:#dc3232;">✗ ' . esc_html__( 'Disabled', 'apollo-core' ) . '</span>';
		}

		echo ' <span class="apollo-tooltip" title="' . esc_attr__( 'GDPR cookie consent banner. Configure in Apollo Core → Cookie Consent.', 'apollo-core' ) . '">ℹ️</span>';

		if ( empty( $settings['privacy_url'] ) ) {
			echo '<br><span style="color:#ffb900;">⚠️ ' . esc_html__( 'Privacy Policy URL not set.', 'apollo-core' ) . '</span>';
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=apollo-cookie-consent' ) ) . '">';
			echo esc_html__( 'Configure', 'apollo-core' ) . '</a>';
		}

		echo '</p></div>';
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Cookie Consent Settings', 'apollo-core' ); ?>
			</h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'GDPR Compliance:', 'apollo-core' ); ?></strong>
					<?php esc_html_e( 'This banner helps you comply with GDPR cookie consent requirements. Analytics scripts are blocked until the user accepts cookies.', 'apollo-core' ); ?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'apollo_cookie_consent_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="apollo_cookie_enabled"><?php esc_html_e( 'Enable Banner', 'apollo-core' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="apollo_cookie_enabled" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
							<p class="description"><?php esc_html_e( 'Show the cookie consent banner to visitors.', 'apollo-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="apollo_cookie_message"><?php esc_html_e( 'Banner Message', 'apollo-core' ); ?></label>
						</th>
						<td>
							<textarea id="apollo_cookie_message" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[message]" rows="3" class="large-text"><?php echo esc_textarea( $settings['message'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'The message displayed in the consent banner.', 'apollo-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="apollo_cookie_accept"><?php esc_html_e( 'Accept Button Text', 'apollo-core' ); ?></label>
						</th>
						<td>
							<input type="text" id="apollo_cookie_accept" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[accept_text]" value="<?php echo esc_attr( $settings['accept_text'] ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="apollo_cookie_decline"><?php esc_html_e( 'Decline Button Text', 'apollo-core' ); ?></label>
						</th>
						<td>
							<input type="text" id="apollo_cookie_decline" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[decline_text]" value="<?php echo esc_attr( $settings['decline_text'] ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="apollo_cookie_privacy"><?php esc_html_e( 'Privacy Policy URL', 'apollo-core' ); ?></label>
						</th>
						<td>
							<input type="url" id="apollo_cookie_privacy" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[privacy_url]" value="<?php echo esc_url( $settings['privacy_url'] ); ?>" class="large-text">
							<p class="description">
								<?php esc_html_e( 'Link to your privacy policy page.', 'apollo-core' ); ?>
								<?php if ( function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ) : ?>
									<br><strong><?php esc_html_e( 'WordPress Privacy Policy:', 'apollo-core' ); ?></strong>
									<code><?php echo esc_url( get_privacy_policy_url() ); ?></code>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue consent scripts and styles.
	 *
	 * @tooltip Only enqueues assets if banner should be shown.
	 *          Includes fallback inline styles for reliability.
	 */
	public static function enqueue_scripts(): void {
		// SAFETY: Check if banner is enabled.
		$settings = self::get_settings();
		if ( ! $settings['enabled'] ) {
			return;
		}

		// SAFETY: Only show banner if consent not given.
		if ( self::has_consent() ) {
			return;
		}

		// Enqueue CSS with fallback.
		$css_path = APOLLO_CORE_PLUGIN_DIR . 'assets/css/cookie-consent.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'apollo-cookie-consent',
				APOLLO_CORE_PLUGIN_URL . 'assets/css/cookie-consent.css',
				array(),
				filemtime( $css_path )
			);
		} else {
			// Inline fallback styles.
			add_action( 'wp_head', array( __CLASS__, 'inline_fallback_styles' ) );
		}

		// Enqueue JS with fallback.
		$js_path = APOLLO_CORE_PLUGIN_DIR . 'assets/js/cookie-consent.js';
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'apollo-cookie-consent',
				APOLLO_CORE_PLUGIN_URL . 'assets/js/cookie-consent.js',
				array( 'jquery' ),
				filemtime( $js_path ),
				true
			);
		}

		// Localize script with safe data.
		wp_localize_script(
			'apollo-cookie-consent',
			'apolloCookieConsent',
			array(
				'ajaxUrl'    => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'      => wp_create_nonce( 'apollo_cookie_consent' ),
				'cookieName' => self::COOKIE_NAME,
				'expiry'     => self::COOKIE_EXPIRY,
			)
		);
	}

	/**
	 * Output inline fallback styles if CSS file is missing.
	 */
	public static function inline_fallback_styles(): void {
		?>
		<style id="apollo-cookie-consent-fallback">
			.apollo-cookie-banner {
				position: fixed;
				bottom: 0;
				left: 0;
				right: 0;
				background: #333;
				color: #fff;
				padding: 20px;
				z-index: 99999;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			}
			.apollo-cookie-banner-content { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; flex-wrap: wrap; gap: 15px; }
			.apollo-cookie-banner p { margin: 0; flex: 1; min-width: 300px; }
			.apollo-cookie-banner-actions { display: flex; gap: 10px; flex-wrap: wrap; }
			.apollo-cookie-accept, .apollo-cookie-decline { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
			.apollo-cookie-accept { background: #0073aa; color: #fff; }
			.apollo-cookie-decline { background: #666; color: #fff; }
			.apollo-cookie-privacy { color: #fff; text-decoration: underline; }
		</style>
		<?php
	}

	/**
	 * Render the consent banner.
	 *
	 * @tooltip Renders banner with all proper escaping.
	 *          Includes inline JS fallback for showing banner.
	 */
	public static function render_banner(): void {
		// SAFETY: Check if enabled.
		$settings = self::get_settings();
		if ( ! $settings['enabled'] ) {
			return;
		}

		// SAFETY: Don't show if consent already given.
		if ( self::has_consent() ) {
			return;
		}

		// SAFETY: Validate settings before output.
		$message      = ! empty( $settings['message'] ) ? $settings['message'] : __( 'We use cookies to improve your experience.', 'apollo-core' );
		$accept_text  = ! empty( $settings['accept_text'] ) ? $settings['accept_text'] : __( 'Accept', 'apollo-core' );
		$decline_text = ! empty( $settings['decline_text'] ) ? $settings['decline_text'] : __( 'Decline', 'apollo-core' );
		$privacy_url  = ! empty( $settings['privacy_url'] ) ? $settings['privacy_url'] : get_privacy_policy_url();
		?>
		<div id="apollo-cookie-consent-banner" class="apollo-cookie-banner" style="display:none;" role="dialog" aria-label="<?php esc_attr_e( 'Cookie Consent', 'apollo-core' ); ?>">
			<div class="apollo-cookie-banner-content">
				<p><?php echo esc_html( $message ); ?></p>
				<div class="apollo-cookie-banner-actions">
					<button type="button" class="apollo-cookie-accept" data-consent="accept" aria-label="<?php esc_attr_e( 'Accept cookies', 'apollo-core' ); ?>">
						<?php echo esc_html( $accept_text ); ?>
					</button>
					<button type="button" class="apollo-cookie-decline" data-consent="decline" aria-label="<?php esc_attr_e( 'Decline cookies', 'apollo-core' ); ?>">
						<?php echo esc_html( $decline_text ); ?>
					</button>
					<?php if ( ! empty( $privacy_url ) && filter_var( $privacy_url, FILTER_VALIDATE_URL ) ) : ?>
						<a href="<?php echo esc_url( $privacy_url ); ?>" class="apollo-cookie-privacy" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Privacy Policy', 'apollo-core' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<script>
			(function() {
				'use strict';
				document.addEventListener('DOMContentLoaded', function() {
					var banner = document.getElementById('apollo-cookie-consent-banner');
					if (banner) {
						banner.style.display = 'block';

						// Fallback click handlers if JS file fails to load.
						var buttons = banner.querySelectorAll('[data-consent]');
						buttons.forEach(function(btn) {
							btn.addEventListener('click', function() {
								var consent = this.getAttribute('data-consent');
								var value = (consent === 'accept') ? 'accepted' : 'declined';

								// Set cookie.
								var expiry = new Date();
								expiry.setTime(expiry.getTime() + (365 * 24 * 60 * 60 * 1000));
								document.cookie = '<?php echo esc_js( self::COOKIE_NAME ); ?>=' + value + ';expires=' + expiry.toUTCString() + ';path=/;SameSite=Lax<?php echo is_ssl() ? ';Secure' : ''; ?>';

								// Hide banner.
								banner.style.display = 'none';

								// Send to server if AJAX available.
								if (typeof jQuery !== 'undefined' && typeof apolloCookieConsent !== 'undefined') {
									jQuery.post(apolloCookieConsent.ajaxUrl, {
										action: 'apollo_save_consent',
										consent: consent,
										nonce: apolloCookieConsent.nonce
									});
								}
							});
						});
					}
				});
			})();
		</script>
		<?php
	}

	/**
	 * AJAX handler to save consent.
	 *
	 * @tooltip Validates nonce and sanitizes input before processing.
	 */
	public static function ajax_save_consent(): void {
		// SAFETY: Verify nonce.
		if ( ! check_ajax_referer( 'apollo_cookie_consent', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'apollo-core' ) ), 403 );
			return;
		}

		// SAFETY: Sanitize and validate consent value.
		$consent = isset( $_POST['consent'] ) ? sanitize_text_field( wp_unslash( $_POST['consent'] ) ) : '';

		if ( ! in_array( $consent, array( 'accept', 'decline' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid consent value.', 'apollo-core' ) ), 400 );
			return;
		}

		$value = ( 'accept' === $consent ) ? 'accepted' : 'declined';

		// SAFETY: Set secure cookie.
		$expiry  = time() + self::COOKIE_EXPIRY;
		$secure  = is_ssl();
		$result  = setcookie( self::COOKIE_NAME, $value, $expiry, COOKIEPATH, COOKIE_DOMAIN, $secure, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to set cookie.', 'apollo-core' ) ), 500 );
			return;
		}

		wp_send_json_success( array(
			'consent'   => $value,
			'timestamp' => current_time( 'mysql' ),
		) );
	}

	/**
	 * Check if user has given consent.
	 *
	 * @return bool True if consent was accepted.
	 *
	 * @tooltip Returns true only if explicitly accepted.
	 *          Returns false for declined or no cookie.
	 */
	public static function has_consent(): bool {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}

		$value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );

		// SAFETY: Validate cookie value.
		if ( ! in_array( $value, self::VALID_CONSENT_VALUES, true ) ) {
			return false;
		}

		return 'accepted' === $value;
	}

	/**
	 * Check if user has explicitly declined.
	 *
	 * @return bool True if consent was declined.
	 */
	public static function has_declined(): bool {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}

		$value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );

		return 'declined' === $value;
	}

	/**
	 * Filter callback to check if analytics can load.
	 *
	 * @param bool $can_load Default value.
	 * @return bool True if consent was given.
	 *
	 * @tooltip Use this filter before loading any analytics scripts.
	 *          Example: if ( apply_filters( 'apollo_can_load_analytics', false ) ) { ... }
	 */
	public static function can_load_analytics( $can_load ): bool {
		return self::has_consent();
	}

	/**
	 * Get consent banner settings with validation.
	 *
	 * @return array Validated settings array.
	 *
	 * @tooltip Always returns valid settings with defaults applied.
	 */
	public static function get_settings(): array {
		$defaults = self::get_default_settings();
		$settings = get_option( self::OPTION_NAME );

		// SAFETY: Ensure settings is array.
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$merged = wp_parse_args( $settings, $defaults );

		// SAFETY: Validate privacy URL if set.
		if ( ! empty( $merged['privacy_url'] ) && ! filter_var( $merged['privacy_url'], FILTER_VALIDATE_URL ) ) {
			$merged['privacy_url'] = get_privacy_policy_url();
		}

		return $merged;
	}
}

// Initialize on init.
add_action( 'init', array( 'Apollo_Cookie_Consent', 'init' ) );
