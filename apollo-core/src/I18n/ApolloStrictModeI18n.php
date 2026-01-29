<?php

declare(strict_types=1);
/**
 * Apollo Strict Mode I18n - Auto-Translation System
 *
 * Automatically detects user's browser language and device type.
 * Translates Portuguese site content to user's preferred language.
 * Falls back to English if language cannot be detected.
 * Self-contained, no external dependencies beyond PHP curl.
 *
 * @package Apollo_Core\I18n
 * @since 2.0.0
 */

namespace Apollo_Core\I18n;

/**
 * Class ApolloStrictModeI18n
 *
 * Main class for handling automatic language detection, device detection,
 * and dynamic translation across all Apollo plugins.
 *
 * BEHAVIOR:
 * - Detects user browser language from Accept-Language header
 * - If detected: translates Portuguese content to user's language
 * - If not detected: defaults to English
 * - Original site language: Portuguese (pt_BR)
 */
class ApolloStrictModeI18n {

	/**
	 * Singleton instance.
	 *
	 * @var ApolloStrictModeI18n|null
	 */
	private static $instance = null;

	/**
	 * Detected user language code (e.g., 'en', 'fr', 'es').
	 *
	 * @var string
	 */
	private $detected_lang = 'en';

	/**
	 * Detected device type (mobile, tablet, desktop, unknown).
	 *
	 * @var string
	 */
	private $device_type = 'desktop';

	/**
	 * Original site language (source for translations).
	 *
	 * @var string
	 */
	private $source_lang = 'pt';

	/**
	 * LibreTranslate API URL (local installation).
	 *
	 * @var string
	 */
	private $libretranslate_url = 'http://localhost:5000/translate';

	/**
	 * Whether LibreTranslate is available.
	 *
	 * @var bool|null
	 */
	private $libretranslate_available = null;

	/**
	 * Translation cache to avoid repeated API calls.
	 *
	 * @var array
	 */
	private $translation_cache = array();

	/**
	 * Apollo plugin textdomains to intercept.
	 *
	 * @var array
	 */
	private $apollo_domains = array(
		'apollo-core',
		'apollo-events-manager',
		'apollo-social',
		'apollo-rio',
	);

	/**
	 * Meta keys that should NEVER be translated (user-generated content).
	 *
	 * @var array
	 */
	private $never_translate_meta = array(
		'_event_title',
		'_event_location',
		'_event_city',
		'_event_address',
		'_cupom_ario',
		'_event_dj_ids',
		'_event_local_ids',
		'_event_dj_slots',
		'_event_timetable',
		'_event_season_id',
		'_classified_location_text',
		'_classified_event_title',
		'_classified_season_id',
		'_dj_name',
		'_local_name',
		'_local_address',
		'_local_city',
	);

	/**
	 * Taxonomies that should NEVER be translated.
	 *
	 * @var array
	 */
	private $never_translate_taxonomies = array(
		'event_season',
		'event_sounds',
	);

	/**
	 * Post types that should NEVER be translated (documents, signatures).
	 *
	 * @var array
	 */
	private $never_translate_post_types = array(
		'cena_document',
		'attachment',
	);

	/**
	 * Supported locales mapping (language code => WordPress locale).
	 *
	 * @var array
	 */
	private $supported_locales = array(
		'en' => 'en_US',
		'pt' => 'pt_BR',
		'es' => 'es_ES',
		'fr' => 'fr_FR',
		'de' => 'de_DE',
		'it' => 'it_IT',
		'el' => 'el',      // Greek
		'he' => 'he_IL',   // Hebrew
		'zh' => 'zh_CN',
		'ja' => 'ja',
		'ko' => 'ko_KR',
		'ru' => 'ru_RU',
		'ar' => 'ar',
		'nl' => 'nl_NL',
	);

	/**
	 * Get singleton instance.
	 *
	 * @return ApolloStrictModeI18n
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor - use get_instance().
	 */
	private function __construct() {
		// Skip for bots/crawlers to optimize performance.
		if ( $this->is_bot() ) {
			return;
		}

		// Run detection early.
		$this->detect_language();
		$this->detect_device();

		// Define constants for access across all plugins.
		if ( ! defined( 'APOLLO_DETECTED_LANG' ) ) {
			define( 'APOLLO_DETECTED_LANG', $this->detected_lang );
		}
		if ( ! defined( 'APOLLO_DEVICE_TYPE' ) ) {
			define( 'APOLLO_DEVICE_TYPE', $this->device_type );
		}
	}

	/**
	 * Initialize hooks - call this from plugins_loaded or earlier.
	 *
	 * @return void
	 */
	public function init() {
		// Skip for bots.
		if ( $this->is_bot() ) {
			return;
		}

		// Hook into locale filter to set WordPress locale.
		add_filter( 'locale', array( $this, 'filter_locale' ), 1 );

		// Hook into gettext to force English translation.
		add_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );
		add_filter( 'gettext_with_context', array( $this, 'filter_gettext_with_context' ), 10, 4 );
		add_filter( 'ngettext', array( $this, 'filter_ngettext' ), 10, 5 );

		// Load textdomains for Apollo plugins.
		add_action( 'plugins_loaded', array( $this, 'load_textdomains' ), 5 );

		// Add admin settings page.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Check if current request is from a bot/crawler.
	 *
	 * @return bool
	 */
	private function is_bot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		return (bool) \preg_match( '/bot|crawl|slurp|spider|mediapartners|googlebot|bingbot|yandex/i', $ua );
	}

	/**
	 * Detect user's preferred language.
	 * Priority: User preference > Browser Accept-Language > English fallback.
	 *
	 * @return void
	 */
	private function detect_language() {
		// 1. Check user preference first (if logged in).
		$user_pref = $this->get_user_language_preference();
		if ( $user_pref && isset( $this->supported_locales[ $user_pref ] ) ) {
			$this->detected_lang = $user_pref;
			return;
		}

		// 2. Check browser Accept-Language header.
		$header = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? \trim( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) : '';

		if ( empty( $header ) ) {
			$this->detected_lang = 'en';
			return;
		}

		// Parse Accept-Language header with quality factors.
		$locales = array();
		foreach ( \explode( ',', $header ) as $part ) {
			$parts  = \explode( ';', $part );
			$locale = \trim( $parts[0] );
			$q      = 1.0;

			if ( isset( $parts[1] ) ) {
				$q_parts = \explode( '=', $parts[1] );
				if ( isset( $q_parts[1] ) ) {
					$q = \floatval( \trim( $q_parts[1] ) );
				}
			}

			$locales[] = array(
				'locale' => $locale,
				'q'      => $q,
			);
		}

		// Sort by quality factor descending.
		\usort( $locales, function( $a, $b ) {
			return $b['q'] <=> $a['q'];
		} );

		// Find first supported locale.
		foreach ( $locales as $locale_data ) {
			$lang_code = \strtolower( \substr( $locale_data['locale'], 0, 2 ) );
			if ( isset( $this->supported_locales[ $lang_code ] ) ) {
				$this->detected_lang = $lang_code;
				return;
			}
		}

		// 3. Default to English.
		$this->detected_lang = 'en';
	}

	/**
	 * Get user's saved language preference from user meta.
	 *
	 * @return string|null Language code or null if not set.
	 */
	public function get_user_language_preference() {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$user_id = get_current_user_id();
		$pref    = get_user_meta( $user_id, 'apollo_preferred_language', true );

		if ( ! empty( $pref ) && isset( $this->supported_locales[ $pref ] ) ) {
			return $pref;
		}

		return null;
	}

	/**
	 * Save user's language preference.
	 *
	 * @param int    $user_id User ID.
	 * @param string $lang    Language code (e.g., 'en', 'pt', 'de').
	 * @return bool True on success.
	 */
	public function save_user_language_preference( $user_id, $lang ) {
		if ( ! isset( $this->supported_locales[ $lang ] ) ) {
			return false;
		}

		return (bool) update_user_meta( $user_id, 'apollo_preferred_language', sanitize_key( $lang ) );
	}

	/**
	 * Get all supported languages with labels for UI.
	 *
	 * @return array Associative array of lang_code => label.
	 */
	public function get_supported_languages_labels() {
		return array(
			'pt' => __( 'Português (Brasil)', 'apollo-core' ),
			'en' => __( 'English', 'apollo-core' ),
			'es' => __( 'Español', 'apollo-core' ),
			'fr' => __( 'Français', 'apollo-core' ),
			'de' => __( 'Deutsch', 'apollo-core' ),
			'it' => __( 'Italiano', 'apollo-core' ),
			'el' => __( 'Ελληνικά (Greek)', 'apollo-core' ),
			'he' => __( 'עברית (Hebrew)', 'apollo-core' ),
			'zh' => __( '中文 (Chinese)', 'apollo-core' ),
			'ja' => __( '日本語 (Japanese)', 'apollo-core' ),
			'ko' => __( '한국어 (Korean)', 'apollo-core' ),
			'ru' => __( 'Русский (Russian)', 'apollo-core' ),
			'ar' => __( 'العربية (Arabic)', 'apollo-core' ),
			'nl' => __( 'Nederlands (Dutch)', 'apollo-core' ),
		);
	}

	/**
	 * Detect device type from User-Agent header.
	 *
	 * @return void
	 */
	private function detect_device() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? \strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';

		if ( empty( $ua ) ) {
			$this->device_type = 'unknown';
			return;
		}

		// Tablet detection first (often misdetected as mobile).
		if ( \stripos( $ua, 'tablet' ) !== false || \preg_match( '/(ipad|kindle|silk|playbook)/i', $ua ) ) {
			$this->device_type = 'tablet';
			return;
		}

		// Mobile detection.
		$mobile_pattern = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i';

		if ( \preg_match( $mobile_pattern, $ua ) ) {
			$this->device_type = 'mobile';
			return;
		}

		// Desktop fallback.
		$this->device_type = 'desktop';
	}

	/**
	 * Filter WordPress locale based on detected language.
	 *
	 * @param string $locale Current locale.
	 * @return string Modified locale.
	 */
	public function filter_locale( $locale ) {
		// Admin always uses site default (Portuguese).
		if ( is_admin() ) {
			return 'pt_BR';
		}

		// Use detected language's locale if available.
		if ( isset( $this->supported_locales[ $this->detected_lang ] ) ) {
			return $this->supported_locales[ $this->detected_lang ];
		}

		// Default to English if not detected.
		return 'en_US';
	}

	/**
	 * Check if current context should skip translation.
	 *
	 * @return bool True if translation should be skipped.
	 */
	private function should_skip_translation() {
		// Skip on admin pages.
		if ( is_admin() ) {
			return true;
		}

		// Skip if strict mode is disabled.
		if ( ! $this->is_strict_mode_enabled() ) {
			return true;
		}

		// Skip if user language is same as source (Portuguese).
		if ( 'pt' === $this->detected_lang ) {
			return true;
		}

		// Skip for document post types (signatures, legal docs).
		if ( $this->is_document_context() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current context is a document (never translate).
	 *
	 * @return bool
	 */
	private function is_document_context() {
		global $post;

		if ( ! $post ) {
			return false;
		}

		// Check if current post type should not be translated.
		if ( \in_array( $post->post_type, $this->never_translate_post_types, true ) ) {
			return true;
		}

		// Check for signature pages.
		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( \strpos( $current_url, 'sign' ) !== false || \strpos( $current_url, 'document' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter gettext to translate to user's language.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @return string Translated text in user's language.
	 */
	public function filter_gettext( $translation, $text, $domain ) {
		// Only process Apollo domains.
		if ( ! \in_array( $domain, $this->apollo_domains, true ) ) {
			return $translation;
		}

		// Check if we should skip translation.
		if ( $this->should_skip_translation() ) {
			return $translation;
		}

		// Check if text contains protected content (URLs, code, meta values).
		if ( $this->contains_protected_content( $text ) ) {
			return $translation;
		}

		// Translate from Portuguese to user's detected language.
		return $this->translate_text( $translation, $this->source_lang, $this->detected_lang );
	}

	/**
	 * Filter gettext with context.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $context     Context.
	 * @param string $domain      Text domain.
	 * @return string Forced English translation.
	 */
	public function filter_gettext_with_context( $translation, $text, $context, $domain ) {
		return $this->filter_gettext( $translation, $text, $domain );
	}

	/**
	 * Filter ngettext (plural forms).
	 *
	 * @param string $translation Translated text.
	 * @param string $single      Singular form.
	 * @param string $plural      Plural form.
	 * @param int    $number      Number for plural.
	 * @param string $domain      Text domain.
	 * @return string Translated text in user's language.
	 */
	public function filter_ngettext( $translation, $single, $plural, $number, $domain ) {
		return $this->filter_gettext( $translation, $single, $domain );
	}

	/**
	 * Check if text contains protected content that should not be translated.
	 *
	 * @param string $text Text to check.
	 * @return bool True if contains protected content.
	 */
	private function contains_protected_content( $text ) {
		// Skip URLs.
		if ( \preg_match( '/https?:\/\/[^\s]+/', $text ) ) {
			return true;
		}

		// Skip if contains <code> blocks.
		if ( \preg_match( '/<code[^>]*>.*?<\/code>/is', $text ) ) {
			return true;
		}

		// Skip if looks like a meta key value.
		foreach ( $this->never_translate_meta as $meta_key ) {
			if ( \strpos( $text, $meta_key ) !== false ) {
				return true;
			}
		}

		// Skip if contains taxonomy slugs.
		foreach ( $this->never_translate_taxonomies as $taxonomy ) {
			if ( \strpos( $text, $taxonomy ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Translate text using LibreTranslate.
	 *
	 * @param string $text        Text to translate.
	 * @param string $source_lang Source language code.
	 * @param string $target_lang Target language code.
	 * @return string Translated text or original if failed.
	 */
	private function translate_text( $text, $source_lang, $target_lang ) {
		// Return empty strings as-is.
		if ( empty( $text ) || \strlen( \trim( $text ) ) < 2 ) {
			return $text;
		}

		// Same language, no translation needed.
		if ( $source_lang === $target_lang ) {
			return $text;
		}

		// Check cache first.
		$cache_key = \md5( $source_lang . ':' . $target_lang . ':' . $text );
		if ( isset( $this->translation_cache[ $cache_key ] ) ) {
			return $this->translation_cache[ $cache_key ];
		}

		// Check transient cache (persists across requests).
		$transient_key = 'apollo_trans_' . \substr( $cache_key, 0, 32 );
		$cached        = get_transient( $transient_key );
		if ( false !== $cached ) {
			$this->translation_cache[ $cache_key ] = $cached;
			return $cached;
		}

		// Check if LibreTranslate is available.
		if ( ! $this->is_libretranslate_available() ) {
			// Fallback: return original text.
			return $text;
		}

		// Call LibreTranslate API.
		$translated = $this->call_libretranslate( $text, $source_lang, $target_lang );

		if ( false !== $translated ) {
			// Cache the translation.
			$this->translation_cache[ $cache_key ] = $translated;
			set_transient( $transient_key, $translated, DAY_IN_SECONDS );
			return $translated;
		}

		// Fallback to original text.
		return $text;
	}

	/**
	 * Check if LibreTranslate is available.

		// Fallback to original text.
		return $text;
	}

	/**
	 * Check if LibreTranslate is available.
	 *
	 * @return bool
	 */
	private function is_libretranslate_available() {
		if ( null !== $this->libretranslate_available ) {
			return $this->libretranslate_available;
		}

		// Check transient for availability status.
		$status = get_transient( 'apollo_libretranslate_status' );
		if ( false !== $status ) {
			$this->libretranslate_available = ( 'available' === $status );
			return $this->libretranslate_available;
		}

		// Ping LibreTranslate to check availability.
		$url = \str_replace( '/translate', '/languages', $this->get_libretranslate_url() );

		$ch = \curl_init( $url );
		\curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		\curl_setopt( $ch, CURLOPT_TIMEOUT, 2 ); // Short timeout.
		\curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 1 );
		$response = \curl_exec( $ch );
		$code     = \curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		\curl_close( $ch );

		$this->libretranslate_available = ( 200 === $code && ! empty( $response ) );

		// Cache status for 5 minutes.
		set_transient(
			'apollo_libretranslate_status',
			$this->libretranslate_available ? 'available' : 'unavailable',
			5 * MINUTE_IN_SECONDS
		);

		return $this->libretranslate_available;
	}

	/**
	 * Call LibreTranslate API for translation.
	 *
	 * @param string $text        Text to translate.
	 * @param string $source_lang Source language code.
	 * @param string $target_lang Target language code.
	 * @return string|false Translated text or false on failure.
	 */
	private function call_libretranslate( $text, $source_lang, $target_lang ) {
		$url = $this->get_libretranslate_url();

		$data = array(
			'q'       => $text,
			'source'  => $source_lang,
			'target'  => $target_lang,
			'format'  => 'text',
			'api_key' => '', // No key needed for local installation.
		);

		$ch = \curl_init( $url );
		\curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		\curl_setopt( $ch, CURLOPT_POST, true );
		\curl_setopt( $ch, CURLOPT_POSTFIELDS, \http_build_query( $data ) );
		\curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded' ) );
		\curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
		\curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );

		$response = \curl_exec( $ch );
		$error    = \curl_error( $ch );
		$code     = \curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		\curl_close( $ch );

		if ( 200 !== $code || empty( $response ) ) {
			// Log error for debugging.
			if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				\error_log( 'Apollo i18n: LibreTranslate error - ' . $error );
			}
			return false;
		}

		$json = \json_decode( $response, true );

		if ( isset( $json['translatedText'] ) ) {
			return $json['translatedText'];
		}

		return false;
	}

	/**
	 * Get LibreTranslate URL from settings.
	 *
	 * @return string
	 */
	private function get_libretranslate_url() {
		$url = get_option( 'apollo_libretranslate_url', $this->libretranslate_url );
		return ! empty( $url ) ? $url : $this->libretranslate_url;
	}

	/**
	 * Check if strict mode is enabled.
	 *
	 * @return bool
	 */
	private function is_strict_mode_enabled() {
		return (bool) get_option( 'apollo_i18n_strict_mode', true );
	}

	/**
	 * Load textdomains for all Apollo plugins.
	 *
	 * @return void
	 */
	public function load_textdomains() {
		$plugins_dir = WP_PLUGIN_DIR;

		// Apollo Core.
		load_plugin_textdomain(
			'apollo-core',
			false,
			'apollo-core/languages/'
		);

		// Apollo Events Manager.
		load_plugin_textdomain(
			'apollo-events-manager',
			false,
			'apollo-events-manager/languages/'
		);

		// Apollo Social.
		load_plugin_textdomain(
			'apollo-social',
			false,
			'apollo-social/languages/'
		);

		// Apollo Rio.
		load_plugin_textdomain(
			'apollo-rio',
			false,
			'apollo-rio/languages/'
		);
	}

	/**
	 * Get detected language code.
	 *
	 * @return string
	 */
	public function get_detected_lang() {
		return $this->detected_lang;
	}

	/**
	 * Get detected device type.
	 *
	 * @return string
	 */
	public function get_device_type() {
		return $this->device_type;
	}

	/**
	 * Add admin menu for settings.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'apollo-core',
			__( 'Language Settings', 'apollo-core' ),
			__( 'Language', 'apollo-core' ),
			'manage_options',
			'apollo-i18n-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'apollo_i18n_settings', 'apollo_i18n_strict_mode' );
		register_setting( 'apollo_i18n_settings', 'apollo_libretranslate_url' );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$strict_mode = get_option( 'apollo_i18n_strict_mode', true );
		$translate_url = get_option( 'apollo_libretranslate_url', $this->libretranslate_url );
		$libre_available = $this->is_libretranslate_available();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Language Settings', 'apollo-core' ); ?></h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Detected Language:', 'apollo-core' ); ?></strong>
					<?php echo esc_html( \strtoupper( $this->detected_lang ) ); ?>
					&nbsp;|&nbsp;
					<strong><?php esc_html_e( 'Device:', 'apollo-core' ); ?></strong>
					<?php echo esc_html( \ucfirst( $this->device_type ) ); ?>
				</p>
			</div>

			<?php if ( $libre_available ) : ?>
				<div class="notice notice-success">
					<p><?php esc_html_e( 'LibreTranslate is available and running.', 'apollo-core' ); ?></p>
				</div>
			<?php else : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'LibreTranslate is not available. Automatic translation will use fallback text.', 'apollo-core' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'apollo_i18n_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-Translation Mode', 'apollo-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="apollo_i18n_strict_mode" value="1" <?php checked( $strict_mode ); ?>>
								<?php esc_html_e( 'Enable automatic translation based on user browser language', 'apollo-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, Portuguese content is automatically translated to the user\'s detected browser language. If language cannot be detected, defaults to English.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'LibreTranslate URL', 'apollo-core' ); ?></th>
						<td>
							<input type="url" name="apollo_libretranslate_url" value="<?php echo esc_attr( $translate_url ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Local LibreTranslate server URL (e.g., http://localhost:5000/translate)', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Protected Content (Never Translated)', 'apollo-core' ); ?></h2>
				<p class="description"><?php esc_html_e( 'The following content types are never translated:', 'apollo-core' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Event titles, locations, cities, addresses', 'apollo-core' ); ?></li>
					<li><?php esc_html_e( 'DJ names, venue names, coupon codes', 'apollo-core' ); ?></li>
					<li><?php esc_html_e( 'Season and sound taxonomies', 'apollo-core' ); ?></li>
					<li><?php esc_html_e( 'Documents (especially signed ones)', 'apollo-core' ); ?></li>
					<li><?php esc_html_e( 'URLs and code blocks', 'apollo-core' ); ?></li>
				</ul>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
