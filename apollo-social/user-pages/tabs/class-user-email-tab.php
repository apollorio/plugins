<?php
/**
 * User Email Preferences Tab
 *
 * Adds email notification preferences tab to user profile/dashboard page.
 * Unified email preferences for the entire Apollo ecosystem.
 *
 * @package Apollo_Social
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User Email Tab Class
 *
 * Manages email notification preferences for users across:
 * - Apollo::Rio (newsletter, events)
 * - Apollo Events (reminders, updates)
 * - Apollo Social (messages, membership)
 * - Classifieds (anúncios)
 */
class Apollo_User_Email_Tab {

	/**
	 * User meta key for email preferences.
	 */
	const PREFS_META_KEY = '_apollo_email_prefs';

	/**
	 * Default preferences.
	 * All defaults are OFF - users must opt-in to receive notifications.
	 */
	private static array $defaults = array(
		// Apollo::Rio
		'apollo_news'                => false,
		'new_events_registered'      => false,
		'weekly_notifications'       => false,
		'weekly_messages_unanswered' => false,

		// Eventos
		'event_status_reminder'      => false,
		'event_lineup_updates'       => false,
		'event_changed_interest'     => false,
		'event_cancelled'            => false,
		'event_invite_response'      => false,
		'event_djs_update'           => false,
		'event_category_update'      => false,

		// Anúncios (Classifieds)
		'classifieds_messages'       => false,

		// Comunidade & Núcleos
		'community_invites'          => false,
		'nucleo_invites'             => false,
		'nucleo_approvals'           => false,

		// Documentos
		'document_signatures'        => false,
	);

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'apollo_user_page_tabs', array( __CLASS__, 'add_email_tab' ), 25 );
		add_action( 'apollo_user_page_tab_content', array( __CLASS__, 'render_email_content' ), 25 );
		add_action( 'wp_ajax_apollo_save_email_preferences', array( __CLASS__, 'ajax_save_preferences' ) );
		add_action( 'wp_footer', array( __CLASS__, 'inline_scripts' ) );
	}

	/**
	 * Add email tab button.
	 */
	public static function add_email_tab() {
		?>
		<button class="apollo-tab-btn" data-tab="email">
			<i class="ri-mail-settings-line"></i> <?php esc_html_e( 'Email', 'apollo-social' ); ?>
		</button>
		<?php
	}

	/**
	 * Get user preferences with defaults.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_preferences( int $user_id ): array {
		$saved = get_user_meta( $user_id, self::PREFS_META_KEY, true );
		$saved = is_array( $saved ) ? $saved : array();
		return wp_parse_args( $saved, self::$defaults );
	}

	/**
	 * Save user preferences.
	 *
	 * @param int   $user_id User ID.
	 * @param array $prefs   Preferences array.
	 * @return bool
	 */
	public static function save_preferences( int $user_id, array $prefs ): bool {
		$sanitized = array();
		foreach ( self::$defaults as $key => $default ) {
			$sanitized[ $key ] = isset( $prefs[ $key ] ) ? (bool) $prefs[ $key ] : false;
		}
		return (bool) update_user_meta( $user_id, self::PREFS_META_KEY, $sanitized );
	}

	/**
	 * Check if user has enabled a specific notification type.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Notification type key.
	 * @return bool
	 */
	public static function is_enabled( int $user_id, string $type ): bool {
		$prefs = self::get_preferences( $user_id );
		return $prefs[ $type ] ?? false;
	}

	/**
	 * Render email preferences content.
	 */
	public static function render_email_content() {
		$user_id = get_current_user_id();
		$prefs   = self::get_preferences( $user_id );
		?>
		<div class="apollo-tab-panel" data-panel="email">
			<div class="apollo-email-prefs-header">
				<h3><i class="ri-mail-settings-line"></i> <?php esc_html_e( 'Preferências de Email', 'apollo-social' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Controle quais notificações você deseja receber por email. As alterações são salvas automaticamente.', 'apollo-social' ); ?>
				</p>
			</div>

			<form id="apollo-email-prefs-form" class="apollo-email-prefs-form">
				<?php wp_nonce_field( 'apollo_email_prefs_nonce', 'email_prefs_nonce' ); ?>

				<!-- Apollo::Rio Section -->
				<div class="apollo-email-section">
					<div class="section-header">
						<span class="section-icon">🌴</span>
						<h4><?php esc_html_e( 'Apollo::Rio', 'apollo-social' ); ?></h4>
					</div>
					<div class="section-content">
						<?php self::render_toggle( 'apollo_news', $prefs, __( 'Quero saber das novidades Apollo', 'apollo-social' ), __( 'Receba atualizações sobre novidades da plataforma Apollo::Rio', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'new_events_registered', $prefs, __( 'Receber todo novo evento registrado', 'apollo-social' ), __( 'Seja notificado quando um novo evento for publicado na plataforma', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'weekly_notifications', $prefs, __( 'Receber lembrete semanal de notificações', 'apollo-social' ), __( 'Resumo semanal das suas notificações pendentes', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'weekly_messages_unanswered', $prefs, __( 'Receber lembrete semanal de mensagens não respondidas', 'apollo-social' ), __( 'Alerta sobre mensagens que aguardam sua resposta', 'apollo-social' ) ); ?>
					</div>
				</div>

				<!-- Eventos Section -->
				<div class="apollo-email-section">
					<div class="section-header">
						<span class="section-icon">🎉</span>
						<h4><?php esc_html_e( 'Eventos', 'apollo-social' ); ?></h4>
					</div>
					<div class="section-content">
						<?php self::render_toggle( 'event_status_reminder', $prefs, __( 'Receber lembrete de status da festa', 'apollo-social' ), __( 'Notificações sobre "Sold out em breve", "Sold out", etc.', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'event_lineup_updates', $prefs, __( 'Receber lembrete de atualização ou lançamento de line-up', 'apollo-social' ), __( 'Quando houver mudanças no line-up de eventos que você tem interesse', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'event_changed_interest', $prefs, __( 'Um evento marcado como interesse foi alterado', 'apollo-social' ), __( 'Notificação quando eventos na sua lista de interesses são editados', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'event_cancelled', $prefs, __( 'Um evento desta agenda foi cancelado', 'apollo-social' ), __( 'Alerta imediato quando um evento que você tem interesse é cancelado', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'event_invite_response', $prefs, __( 'Um convidado responder a um evento desta agenda', 'apollo-social' ), __( 'Respostas de confirmação de presença em eventos que você organiza', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'event_djs_update', $prefs, __( 'Novidade na programação do evento (DJs)', 'apollo-social' ), __( 'Quando o campo event_djs for atualizado em eventos de interesse', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'event_category_update', $prefs, __( 'Mudança de categoria do evento', 'apollo-social' ), __( 'Quando a categoria (taxonomy) do evento mudar', 'apollo-social' ) ); ?>
					</div>
				</div>

				<!-- Anúncios (Classifieds) Section -->
				<div class="apollo-email-section">
					<div class="section-header">
						<span class="section-icon">📢</span>
						<h4><?php esc_html_e( 'Anúncios (Classificados)', 'apollo-social' ); ?></h4>
					</div>
					<div class="section-content">
						<?php self::render_toggle( 'classifieds_messages', $prefs, __( 'Receber lembrete a toda mensagem no anúncio', 'apollo-social' ), __( 'Notificação quando alguém enviar mensagem em seus anúncios', 'apollo-social' ) ); ?>
					</div>
				</div>

				<!-- Comunidade & Núcleos Section -->
				<div class="apollo-email-section">
					<div class="section-header">
						<span class="section-icon">🤝</span>
						<h4><?php esc_html_e( 'Comunidade & Núcleos', 'apollo-social' ); ?></h4>
					</div>
					<div class="section-content">
						<?php self::render_toggle( 'community_invites', $prefs, __( 'Convites para a comunidade', 'apollo-social' ), __( 'Receber emails quando alguém te convidar para a comunidade Apollo', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'nucleo_invites', $prefs, __( 'Convites para núcleos', 'apollo-social' ), __( 'Notificações de convites para núcleos privados de produtores', 'apollo-social' ) ); ?>
						<?php self::render_toggle( 'nucleo_approvals', $prefs, __( 'Aprovação em núcleos', 'apollo-social' ), __( 'Receber confirmação quando sua solicitação para núcleo for aprovada', 'apollo-social' ) ); ?>
					</div>
				</div>

				<!-- Documentos Section -->
				<div class="apollo-email-section">
					<div class="section-header">
						<span class="section-icon">📝</span>
						<h4><?php esc_html_e( 'Documentos', 'apollo-social' ); ?></h4>
					</div>
					<div class="section-content">
						<?php self::render_toggle( 'document_signatures', $prefs, __( 'Solicitações de assinatura', 'apollo-social' ), __( 'Notificação quando houver documentos aguardando sua assinatura', 'apollo-social' ) ); ?>
					</div>
				</div>

				<!-- Master Controls -->
				<div class="apollo-email-section apollo-email-master">
					<div class="section-header">
						<span class="section-icon">⚙️</span>
						<h4><?php esc_html_e( 'Controles Gerais', 'apollo-social' ); ?></h4>
					</div>
					<div class="section-content">
						<div class="apollo-master-controls">
							<button type="button" class="apollo-btn-link" id="apollo-email-enable-all">
								<i class="ri-checkbox-circle-line"></i> <?php esc_html_e( 'Ativar todas', 'apollo-social' ); ?>
							</button>
							<button type="button" class="apollo-btn-link" id="apollo-email-disable-all">
								<i class="ri-close-circle-line"></i> <?php esc_html_e( 'Desativar todas', 'apollo-social' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div class="apollo-email-actions">
					<button type="submit" class="apollo-btn apollo-btn--primary">
						<i class="ri-save-line"></i> <?php esc_html_e( 'Salvar Preferências', 'apollo-social' ); ?>
					</button>
					<span class="apollo-email-status"></span>
				</div>
			</form>
		</div>

		<style>
			.apollo-email-prefs-form {
				max-width: 700px;
			}
			.apollo-email-prefs-header {
				margin-bottom: 24px;
			}
			.apollo-email-prefs-header h3 {
				display: flex;
				align-items: center;
				gap: 8px;
				margin: 0 0 8px 0;
				font: var(--font-main);
				font-size: 20px;
				font-weight: 600;
				color: var(--txt-hover);
			}
			.apollo-email-prefs-header .description {
				color: var(--txt-muted);
				font: var(--font-main);
				font-size: 14px;
				margin: 0;
			}
			.apollo-email-section {
				background: var(--card);
				border: 1px solid var(--border);
				border-radius: var(--radius);
				margin-bottom: 16px;
				overflow: hidden;
				transition: var(--transition-fast);
			}
			.apollo-email-section:hover {
				background: var(--card-hover);
				border-color: var(--border-hover);
				box-shadow: var(--shadow);
			}
			.apollo-email-section .section-header {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 16px 20px;
				background: var(--surface);
				border-bottom: 1px solid var(--border);
			}
			.apollo-email-section .section-icon {
				font-size: 24px;
			}
			.apollo-email-section .section-header h4 {
				margin: 0;
				font: var(--font-main);
				font-size: 16px;
				font-weight: 600;
				color: var(--txt-hover);
			}
			.apollo-email-section .section-content {
				padding: 16px 20px;
			}
			.apollo-email-toggle {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				padding: 12px 0;
				border-bottom: 1px solid var(--border-light);
				transition: var(--transition-fast);
			}
			.apollo-email-toggle:hover {
				padding-left: 8px;
			}
			.apollo-email-toggle:last-child {
				border-bottom: none;
			}
			.apollo-email-toggle-info {
				flex: 1;
				padding-right: 20px;
			}
			.apollo-email-toggle-label {
				font-weight: 500;
				color: var(--txt-hover);
				display: block;
				margin-bottom: 4px;
				font: var(--font-main);
			}
			.apollo-email-toggle-desc {
				font-size: 12px;
				color: var(--txt-muted);
				font: var(--font-main);
			}
			.apollo-toggle-switch {
				position: relative;
				width: 50px;
				height: 28px;
				flex-shrink: 0;
			}
			.apollo-toggle-switch input {
				opacity: 0;
				width: 0;
				height: 0;
			}
			.apollo-toggle-slider {
				position: absolute;
				cursor: pointer;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: var(--border);
				transition: var(--transition-fast);
				border-radius: 28px;
			}
			.apollo-toggle-slider:before {
				position: absolute;
				content: "";
				height: 22px;
				width: 22px;
				left: 3px;
				bottom: 3px;
				background: var(--white);
				transition: var(--transition-fast);
				border-radius: 50%;
				box-shadow: var(--shadow);
			}
			.apollo-toggle-switch input:checked + .apollo-toggle-slider {
				background: linear-gradient(135deg, var(--primary) 0%, var(--orange-7) 100%);
			}
			.apollo-toggle-switch input:checked + .apollo-toggle-slider:before {
				transform: translateX(22px);
			}
			.apollo-toggle-switch input:focus + .apollo-toggle-slider {
				box-shadow: 0 0 0 3px var(--glass);
			}
			.apollo-email-master {
				background: linear-gradient(135deg, var(--surface) 0%, var(--card) 100%);
				border-color: var(--border-hover);
			}
			.apollo-master-controls {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
			}
			.apollo-btn-link {
				background: transparent;
				border: 1px solid var(--border);
				color: var(--txt-hover);
				cursor: pointer;
				font: var(--font-main);
				font-size: 14px;
				display: flex;
				align-items: center;
				gap: 6px;
				padding: 8px 16px;
				border-radius: var(--radius);
				transition: var(--transition-fast);
			}
			.apollo-btn-link:hover {
				background: var(--surface);
				border-color: var(--border-hover);
				transform: translateY(-1px);
				box-shadow: var(--shadow);
			}
			.apollo-email-actions {
				margin-top: 24px;
				display: flex;
				align-items: center;
				gap: 16px;
				flex-wrap: wrap;
			}
			.apollo-btn--primary {
				background: linear-gradient(135deg, var(--primary) 0%, var(--orange-7) 100%);
				color: var(--white);
				border: none;
				padding: 12px 24px;
				border-radius: var(--radius);
				font: var(--font-main);
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
				display: flex;
				align-items: center;
				gap: 8px;
				transition: var(--transition-fast);
			}
			.apollo-btn--primary:hover {
				transform: translateY(-2px);
				box-shadow: var(--shadow-hover);
			}
			.apollo-btn--primary:disabled {
				opacity: 0.6;
				cursor: not-allowed;
				transform: none;
			}
			.apollo-email-status {
				font: var(--font-main);
				font-size: 14px;
				color: var(--accent-1);
				display: none;
			}
			.apollo-email-status.error {
				color: #d00;
			}
			.apollo-email-status.show {
				display: inline-flex;
				align-items: center;
				gap: 6px;
			}
		</style>
		<?php
	}

	/**
	 * Render a toggle switch.
	 *
	 * @param string $key   Preference key.
	 * @param array  $prefs Current preferences.
	 * @param string $label Toggle label.
	 * @param string $desc  Toggle description.
	 */
	private static function render_toggle( string $key, array $prefs, string $label, string $desc = '' ): void {
		$checked = $prefs[ $key ] ?? false;
		?>
		<div class="apollo-email-toggle">
			<div class="apollo-email-toggle-info">
				<span class="apollo-email-toggle-label"><?php echo esc_html( $label ); ?></span>
				<?php if ( $desc ) : ?>
					<span class="apollo-email-toggle-desc"><?php echo esc_html( $desc ); ?></span>
				<?php endif; ?>
			</div>
			<label class="apollo-toggle-switch">
				<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( $checked ); ?>>
				<span class="apollo-toggle-slider"></span>
			</label>
		</div>
		<?php
	}

	/**
	 * Inline JavaScript for the tab.
	 */
	public static function inline_scripts(): void {
		if ( ! is_page() ) {
			return;
		}
		?>
		<script>
		(function($) {
			'use strict';

			$(document).ready(function() {
				var $form = $('#apollo-email-prefs-form');
				var $status = $('.apollo-email-status');

				// Form submit handler
				$form.on('submit', function(e) {
					e.preventDefault();
					savePreferences();
				});

				// Enable all
				$('#apollo-email-enable-all').on('click', function() {
					$form.find('input[type="checkbox"]').prop('checked', true);
					savePreferences();
				});

				// Disable all
				$('#apollo-email-disable-all').on('click', function() {
					$form.find('input[type="checkbox"]').prop('checked', false);
					savePreferences();
				});

				// Auto-save on toggle change
				$form.find('input[type="checkbox"]').on('change', function() {
					savePreferences();
				});

				function savePreferences() {
					var $btn = $form.find('button[type="submit"]');
					var btnText = $btn.html();

					$btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> <?php esc_html_e( 'Salvando...', 'apollo-social' ); ?>');
					$status.removeClass('show error');

					var formData = {
						action: 'apollo_save_email_preferences',
						nonce: $form.find('#email_prefs_nonce').val()
					};

					// Collect all checkbox states
					$form.find('input[type="checkbox"]').each(function() {
						formData[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
					});

					$.ajax({
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						type: 'POST',
						data: formData,
						success: function(response) {
							$btn.prop('disabled', false).html(btnText);
							if (response.success) {
								$status.html('<i class="ri-check-line"></i> <?php esc_html_e( 'Salvo!', 'apollo-social' ); ?>')
									.removeClass('error').addClass('show');
								setTimeout(function() {
									$status.removeClass('show');
								}, 3000);
							} else {
								$status.html('<i class="ri-error-warning-line"></i> ' + (response.data?.message || '<?php esc_html_e( 'Erro ao salvar', 'apollo-social' ); ?>'))
									.addClass('show error');
							}
						},
						error: function() {
							$btn.prop('disabled', false).html(btnText);
							$status.html('<i class="ri-error-warning-line"></i> <?php esc_html_e( 'Erro de conexão', 'apollo-social' ); ?>')
								.addClass('show error');
						}
					});
				}
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * AJAX handler: Save email preferences.
	 */
	public static function ajax_save_preferences(): void {
		check_ajax_referer( 'apollo_email_prefs_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.', 'apollo-social' ) ) );
		}

		$user_id = get_current_user_id();
		$prefs   = array();

		// Collect all preferences from POST
		foreach ( array_keys( self::$defaults ) as $key ) {
			$prefs[ $key ] = isset( $_POST[ $key ] ) && $_POST[ $key ] === '1';
		}

		$saved = self::save_preferences( $user_id, $prefs );

		if ( $saved ) {
			// Also sync with legacy meta keys for backward compatibility
			self::sync_legacy_preferences( $user_id, $prefs );

			wp_send_json_success( array( 'message' => __( 'Preferências salvas com sucesso!', 'apollo-social' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Erro ao salvar preferências.', 'apollo-social' ) ) );
		}
	}

	/**
	 * Sync with legacy preference meta keys for backward compatibility.
	 *
	 * @param int   $user_id User ID.
	 * @param array $prefs   New preferences.
	 */
	private static function sync_legacy_preferences( int $user_id, array $prefs ): void {
		// Sync with apollo-events-manager preferences
		$events_prefs = array(
			'email_reminders' => $prefs['event_status_reminder'],
			'email_updates'   => $prefs['event_changed_interest'],
			'email_digest'    => $prefs['weekly_notifications'],
		);
		update_user_meta( $user_id, '_apollo_notification_prefs', $events_prefs );

		// Sync with apollo-social preferences
		$social_prefs = array();
		foreach ( $prefs as $key => $value ) {
			$social_prefs[ $key ] = $value;
		}
		update_user_meta( $user_id, 'apollo_email_notification_prefs', $social_prefs );
	}
}

Apollo_User_Email_Tab::init();
