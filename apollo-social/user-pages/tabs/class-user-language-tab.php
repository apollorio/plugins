<?php
/**
 * User Language Tab
 *
 * Adds language preference settings tab to user profile/dashboard page.
 * Integrates with Apollo Core i18n Strict Mode.
 *
 * @package Apollo_Social
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User Language Tab Class
 */
class Apollo_User_Language_Tab {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'apollo_user_page_tabs', array( __CLASS__, 'add_language_tab' ), 15 );
		add_action( 'apollo_user_page_tab_content', array( __CLASS__, 'render_language_content' ), 15 );
		add_action( 'wp_ajax_apollo_save_language_preference', array( __CLASS__, 'ajax_save_language' ) );
	}

	/**
	 * Add language tab button.
	 */
	public static function add_language_tab() {
		?>
<button class="apollo-tab-btn" data-tab="language">
	<i class="ri-global-line"></i> <?php esc_html_e( 'Idioma', 'apollo-social' ); ?>
</button>
<?php
	}

	/**
	 * Render language settings content.
	 */
	public static function render_language_content() {
		$user_id     = get_current_user_id();
		$current_pref = get_user_meta( $user_id, 'apollo_preferred_language', true );

		// Get supported languages from i18n class.
		$languages = self::get_supported_languages();

		// Detect browser language for display.
		$browser_lang = self::detect_browser_language();

		?>
<div class="apollo-tab-panel" data-panel="language">
	<h3><?php esc_html_e( 'Preferência de Idioma', 'apollo-social' ); ?></h3>
	<p class="description" style="margin-bottom: 20px;">
		<?php esc_html_e( 'Escolha o idioma de sua preferência para visualização do site. Se não selecionar, o sistema detectará automaticamente o idioma do seu navegador.', 'apollo-social' ); ?>
	</p>

	<?php if ( $browser_lang ) : ?>
	<div class="apollo-browser-lang-info"
		style="background: #f0f7ff; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
		<strong><i class="ri-information-line"></i>
			<?php esc_html_e( 'Idioma detectado do navegador:', 'apollo-social' ); ?></strong>
		<span style="margin-left: 8px;"><?php echo esc_html( $languages[ $browser_lang ] ?? $browser_lang ); ?></span>
	</div>
	<?php endif; ?>

	<div class="apollo-language-setting" style="margin-bottom: 24px;">
		<label for="apollo_preferred_language">
			<strong><?php esc_html_e( 'Selecione seu idioma preferido:', 'apollo-social' ); ?></strong>
		</label>
		<select id="apollo_preferred_language" class="regular-text"
			style="display: block; margin-top: 8px; min-width: 300px; padding: 8px 12px; font-size: 14px;">
			<option value="" <?php selected( $current_pref, '' ); ?>>
				<?php esc_html_e( '— Automático (detectar do navegador) —', 'apollo-social' ); ?>
			</option>
			<?php foreach ( $languages as $code => $label ) : ?>
			<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_pref, $code ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<p class="description" style="margin-top: 8px; color: #666;">
			<?php esc_html_e( 'O site original é em Português. A tradução automática será aplicada para outros idiomas.', 'apollo-social' ); ?>
		</p>
	</div>

	<div class="apollo-language-flags"
		style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 24px;">
		<?php foreach ( $languages as $code => $label ) :
					$is_current = ( $current_pref === $code ) || ( empty( $current_pref ) && $browser_lang === $code );
					?>
		<button type="button" class="apollo-lang-flag-btn <?php echo $is_current ? 'active' : ''; ?>"
			data-lang="<?php echo esc_attr( $code ); ?>"
			style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: <?php echo $is_current ? '#0073aa' : '#f5f5f5'; ?>; color: <?php echo $is_current ? '#fff' : '#333'; ?>; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; transition: all 0.2s;">
			<span class="lang-code"
				style="font-weight: bold; text-transform: uppercase;"><?php echo esc_html( $code ); ?></span>
			<span class="lang-name"><?php echo esc_html( $label ); ?></span>
		</button>
		<?php endforeach; ?>
	</div>

	<button type="button" class="button button-primary apollo-save-language"
		style="padding: 10px 24px; font-size: 14px;">
		<i class="ri-save-line"></i> <?php esc_html_e( 'Salvar Preferência', 'apollo-social' ); ?>
	</button>

	<span class="apollo-language-status" style="margin-left: 12px; display: none;"></span>
</div>

<style>
.apollo-lang-flag-btn:hover {
	background: #0073aa !important;
	color: #fff !important;
	transform: translateY(-1px);
}

.apollo-lang-flag-btn.active {
	box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.3);
}
</style>

<script>
(function($) {
	// Flag button click handler
	$('.apollo-lang-flag-btn').on('click', function() {
		var lang = $(this).data('lang');
		$('#apollo_preferred_language').val(lang);
		$('.apollo-lang-flag-btn').removeClass('active').css({
			background: '#f5f5f5',
			color: '#333'
		});
		$(this).addClass('active').css({
			background: '#0073aa',
			color: '#fff'
		});
	});

	// Save button handler
	$('.apollo-save-language').on('click', function() {
		var $btn = $(this);
		var $status = $('.apollo-language-status');
		var lang = $('#apollo_preferred_language').val();

		$btn.prop('disabled', true).text('<?php esc_html_e( 'Salvando...', 'apollo-social' ); ?>');
		$status.hide();

		$.ajax({
			url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type: 'POST',
			data: {
				action: 'apollo_save_language_preference',
				nonce: '<?php echo esc_js( wp_create_nonce( 'apollo_user_language_nonce' ) ); ?>',
				language: lang
			},
			success: function(response) {
				$btn.prop('disabled', false).html(
					'<i class="ri-save-line"></i> <?php esc_html_e( 'Salvar Preferência', 'apollo-social' ); ?>'
				);

				if (response.success) {
					$status.css('color', '#46b450').text('✓ ' + response.data.message).fadeIn();

					// Reload page after 1 second to apply new language
					setTimeout(function() {
						window.location.reload();
					}, 1500);
				} else {
					$status.css('color', '#dc3232').text('✗ ' + (response.data.message ||
						'Erro ao salvar')).fadeIn();
				}
			},
			error: function() {
				$btn.prop('disabled', false).html(
					'<i class="ri-save-line"></i> <?php esc_html_e( 'Salvar Preferência', 'apollo-social' ); ?>'
				);
				$status.css('color', '#dc3232').text(
					'✗ <?php esc_html_e( 'Erro de conexão', 'apollo-social' ); ?>').fadeIn();
			}
		});
	});
})(jQuery);
</script>
<?php
	}

	/**
	 * Get supported languages.
	 *
	 * @return array Language code => Label.
	 */
	private static function get_supported_languages() {
		// Try to get from i18n class first.
		if ( class_exists( 'Apollo_Core\I18n\ApolloStrictModeI18n' ) ) {
			$i18n = \Apollo_Core\I18n\ApolloStrictModeI18n::get_instance();
			if ( method_exists( $i18n, 'get_supported_languages_labels' ) ) {
				return $i18n->get_supported_languages_labels();
			}
		}

		// Fallback to hardcoded list.
		return array(
			'pt' => __( 'Português (Brasil)', 'apollo-social' ),
			'en' => __( 'English', 'apollo-social' ),
			'es' => __( 'Español', 'apollo-social' ),
			'fr' => __( 'Français', 'apollo-social' ),
			'de' => __( 'Deutsch', 'apollo-social' ),
			'it' => __( 'Italiano', 'apollo-social' ),
			'el' => __( 'Ελληνικά (Greek)', 'apollo-social' ),
			'he' => __( 'עברית (Hebrew)', 'apollo-social' ),
			'zh' => __( '中文 (Chinese)', 'apollo-social' ),
			'ja' => __( '日本語 (Japanese)', 'apollo-social' ),
			'ko' => __( '한국어 (Korean)', 'apollo-social' ),
			'ru' => __( 'Русский (Russian)', 'apollo-social' ),
			'ar' => __( 'العربية (Arabic)', 'apollo-social' ),
			'nl' => __( 'Nederlands (Dutch)', 'apollo-social' ),
		);
	}

	/**
	 * Detect browser language from Accept-Language header.
	 *
	 * @return string|null Language code or null.
	 */
	private static function detect_browser_language() {
		$languages = self::get_supported_languages();
		$header    = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? trim( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) : '';

		if ( empty( $header ) ) {
			return null;
		}

		// Get first language from header.
		$first = explode( ',', $header )[0];
		$lang  = strtolower( substr( trim( $first ), 0, 2 ) );

		return isset( $languages[ $lang ] ) ? $lang : null;
	}

	/**
	 * AJAX: Save language preference.
	 */
	public static function ajax_save_language() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'apollo_user_language_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessão expirada. Recarregue a página.', 'apollo-social' ) ) );
		}

		// Check login.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.', 'apollo-social' ) ) );
		}

		$user_id  = get_current_user_id();
		$language = isset( $_POST['language'] ) ? sanitize_key( $_POST['language'] ) : '';

		// Validate language.
		$languages = self::get_supported_languages();
		if ( ! empty( $language ) && ! isset( $languages[ $language ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Idioma inválido.', 'apollo-social' ) ) );
		}

		// Save preference.
		if ( empty( $language ) ) {
			delete_user_meta( $user_id, 'apollo_preferred_language' );
			wp_send_json_success( array(
				'message'  => __( 'Preferência removida. O idioma será detectado automaticamente.', 'apollo-social' ),
				'language' => 'auto',
			) );
		} else {
			update_user_meta( $user_id, 'apollo_preferred_language', $language );
			wp_send_json_success( array(
				'message'  => sprintf(
					/* translators: %s: Language name */
					__( 'Idioma alterado para %s.', 'apollo-social' ),
					$languages[ $language ]
				),
				'language' => $language,
			) );
		}
	}
}

// Initialize the tab.
add_action( 'init', array( 'Apollo_User_Language_Tab', 'init' ) );