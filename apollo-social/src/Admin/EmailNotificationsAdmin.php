<?php
/**
 * Apollo Unified Email Notifications Admin Settings
 *
 * Admin page for configuring global email notification settings
 * for events: changes, cancellations, responses, DJ updates, etc.
 *
 * @package Apollo_Social
 * @since   2.0.0
 */

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Notifications Admin Settings
 *
 * Provides admin controls for:
 * - Event change notifications
 * - Event cancellation notifications
 * - Event response notifications
 * - DJ lineup update notifications
 * - Category change notifications
 */
class EmailNotificationsAdmin {

	/**
	 * Option key for notification settings.
	 */
	const OPTION_KEY = 'apollo_email_notifications_settings';

	/**
	 * Default settings.
	 * All notification types are OFF by default - admin must enable them.
	 */
	private static array $defaults = array(
		// Global toggles (admin can enable/disable for all users) - ALL OFF by default
		'enable_event_changed'          => false,
		'enable_event_cancelled'        => false,
		'enable_event_response'         => false,
		'enable_event_djs_update'       => false,
		'enable_event_category_update'  => false,

		// Notification frequency
		'batch_notifications'           => false,
		'batch_interval_hours'          => 6,

		// Rate limiting
		'max_emails_per_user_per_day'   => 20,

		// Templates enabled
		'use_custom_templates'          => false,
	);

	/**
	 * Initialize the admin settings.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_admin_submenu' ), 30 );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_ajax_apollo_save_notification_settings', array( self::class, 'ajax_save_settings' ) );
	}

	/**
	 * Add submenu under Apollo Email Hub.
	 */
	public static function add_admin_submenu(): void {
		add_submenu_page(
			'apollo-email-hub',
			__( 'Notificações de Eventos', 'apollo-social' ),
			__( '🔔 Notificações', 'apollo-social' ),
			'manage_options',
			'apollo-email-notifications',
			array( self::class, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'apollo_email_notifications_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_settings' ),
				'default'           => self::$defaults,
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array
	 */
	public static function sanitize_settings( $input ): array {
		$sanitized = array();

		// Boolean toggles
		$booleans = array(
			'enable_event_changed',
			'enable_event_cancelled',
			'enable_event_response',
			'enable_event_djs_update',
			'enable_event_category_update',
			'batch_notifications',
			'use_custom_templates',
		);

		foreach ( $booleans as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}

		// Integer values
		$sanitized['batch_interval_hours']       = absint( $input['batch_interval_hours'] ?? 6 );
		$sanitized['max_emails_per_user_per_day'] = absint( $input['max_emails_per_user_per_day'] ?? 20 );

		return $sanitized;
	}

	/**
	 * Get settings with defaults.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		$settings = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $settings, self::$defaults );
	}

	/**
	 * Check if a notification type is enabled globally.
	 *
	 * @param string $type Notification type.
	 * @return bool
	 */
	public static function is_enabled( string $type ): bool {
		$settings = self::get_settings();
		$key      = 'enable_' . $type;
		return $settings[ $key ] ?? false;
	}

	/**
	 * Enqueue assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'apollo-email-notifications' ) === false ) {
			return;
		}

		// Inline styles for the settings page
		wp_add_inline_style( 'wp-admin', self::get_inline_styles() );
	}

	/**
	 * Get inline styles.
	 *
	 * @return string
	 */
	private static function get_inline_styles(): string {
		return '
			.apollo-notif-settings {
				max-width: 900px;
			}
			.apollo-notif-settings .settings-section {
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 12px;
				margin-bottom: 20px;
				overflow: hidden;
			}
			.apollo-notif-settings .section-header {
				background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
				padding: 16px 24px;
				border-bottom: 1px solid #e0e0e0;
				display: flex;
				align-items: center;
				gap: 12px;
			}
			.apollo-notif-settings .section-header h2 {
				margin: 0;
				font-size: 18px;
				font-weight: 600;
			}
			.apollo-notif-settings .section-content {
				padding: 24px;
			}
			.apollo-notif-settings .setting-row {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				padding: 16px 0;
				border-bottom: 1px solid #f0f0f0;
			}
			.apollo-notif-settings .setting-row:last-child {
				border-bottom: none;
			}
			.apollo-notif-settings .setting-info {
				flex: 1;
				padding-right: 24px;
			}
			.apollo-notif-settings .setting-label {
				font-weight: 500;
				color: #333;
				margin-bottom: 4px;
				display: block;
			}
			.apollo-notif-settings .setting-desc {
				font-size: 13px;
				color: #666;
			}
			.apollo-notif-settings .setting-control {
				flex-shrink: 0;
			}
			.apollo-toggle {
				position: relative;
				width: 50px;
				height: 28px;
			}
			.apollo-toggle input {
				opacity: 0;
				width: 0;
				height: 0;
			}
			.apollo-toggle .slider {
				position: absolute;
				cursor: pointer;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background-color: #ccc;
				transition: 0.3s;
				border-radius: 28px;
			}
			.apollo-toggle .slider:before {
				position: absolute;
				content: "";
				height: 22px;
				width: 22px;
				left: 3px;
				bottom: 3px;
				background-color: white;
				transition: 0.3s;
				border-radius: 50%;
				box-shadow: 0 2px 4px rgba(0,0,0,0.2);
			}
			.apollo-toggle input:checked + .slider {
				background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
			}
			.apollo-toggle input:checked + .slider:before {
				transform: translateX(22px);
			}
			.apollo-badge {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				padding: 4px 10px;
				border-radius: 20px;
				font-size: 12px;
				font-weight: 500;
			}
			.apollo-badge.user-control {
				background: #e8f5e9;
				color: #2e7d32;
			}
			.apollo-notif-settings .submit-section {
				padding: 20px;
				background: #f8f9fa;
				border-radius: 0 0 12px 12px;
				display: flex;
				align-items: center;
				gap: 16px;
			}
			.apollo-notif-settings .save-status {
				font-size: 14px;
				color: #0a0;
			}
		';
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page(): void {
		$settings = self::get_settings();
		?>
		<div class="wrap apollo-notif-settings">
			<h1>
				<span class="dashicons dashicons-bell" style="font-size: 28px; margin-right: 8px;"></span>
				<?php esc_html_e( 'Configurações de Notificações de Eventos', 'apollo-social' ); ?>
			</h1>

			<p class="description" style="font-size: 14px; margin-bottom: 24px; max-width: 700px;">
				<?php esc_html_e( 'Configure quais tipos de notificações de email estão disponíveis para os usuários. Quando ativado, os usuários podem controlar individualmente nas suas preferências.', 'apollo-social' ); ?>
			</p>

			<form method="post" action="options.php" id="apollo-notif-form">
				<?php settings_fields( 'apollo_email_notifications_group' ); ?>

				<!-- Event Notifications Section -->
				<div class="settings-section">
					<div class="section-header">
						<span style="font-size: 24px;">🎉</span>
						<h2><?php esc_html_e( 'Notificações de Eventos', 'apollo-social' ); ?></h2>
					</div>
					<div class="section-content">
						<?php
						self::render_setting_row(
							'enable_event_changed',
							$settings,
							__( 'Eventos alterados', 'apollo-social' ),
							__( 'Notificar usuários quando um evento marcado como interesse for alterado.', 'apollo-social' ),
							true
						);

						self::render_setting_row(
							'enable_event_cancelled',
							$settings,
							__( 'Eventos cancelados', 'apollo-social' ),
							__( 'Notificar usuários quando um evento desta agenda for cancelado.', 'apollo-social' ),
							true
						);

						self::render_setting_row(
							'enable_event_response',
							$settings,
							__( 'Respostas a eventos', 'apollo-social' ),
							__( 'Notificar quando um convidado responder a um evento desta agenda.', 'apollo-social' ),
							true
						);

						self::render_setting_row(
							'enable_event_djs_update',
							$settings,
							__( 'Novidade na programação (DJs)', 'apollo-social' ),
							__( 'Notificar "Novidade na programação da {{NAME EVENT}}" quando event_djs for editado.', 'apollo-social' ),
							true
						);

						self::render_setting_row(
							'enable_event_category_update',
							$settings,
							__( 'Mudança de categoria', 'apollo-social' ),
							__( 'Notificar quando a taxonomy category do event_listing for alterada.', 'apollo-social' ),
							true
						);
						?>
					</div>
				</div>

				<!-- Delivery Settings Section -->
				<div class="settings-section">
					<div class="section-header">
						<span style="font-size: 24px;">⚙️</span>
						<h2><?php esc_html_e( 'Configurações de Entrega', 'apollo-social' ); ?></h2>
					</div>
					<div class="section-content">
						<?php
						self::render_setting_row(
							'batch_notifications',
							$settings,
							__( 'Agrupar notificações', 'apollo-social' ),
							__( 'Agrupa múltiplas notificações em um único email para evitar spam.', 'apollo-social' ),
							false
						);
						?>

						<div class="setting-row">
							<div class="setting-info">
								<span class="setting-label"><?php esc_html_e( 'Intervalo de agrupamento', 'apollo-social' ); ?></span>
								<span class="setting-desc"><?php esc_html_e( 'Tempo em horas para agrupar notificações pendentes.', 'apollo-social' ); ?></span>
							</div>
							<div class="setting-control">
								<select name="<?php echo self::OPTION_KEY; ?>[batch_interval_hours]" class="regular-text">
									<option value="1" <?php selected( $settings['batch_interval_hours'], 1 ); ?>>1 hora</option>
									<option value="3" <?php selected( $settings['batch_interval_hours'], 3 ); ?>>3 horas</option>
									<option value="6" <?php selected( $settings['batch_interval_hours'], 6 ); ?>>6 horas</option>
									<option value="12" <?php selected( $settings['batch_interval_hours'], 12 ); ?>>12 horas</option>
									<option value="24" <?php selected( $settings['batch_interval_hours'], 24 ); ?>>24 horas</option>
								</select>
							</div>
						</div>

						<div class="setting-row">
							<div class="setting-info">
								<span class="setting-label"><?php esc_html_e( 'Limite diário por usuário', 'apollo-social' ); ?></span>
								<span class="setting-desc"><?php esc_html_e( 'Máximo de emails de notificação enviados por usuário por dia.', 'apollo-social' ); ?></span>
							</div>
							<div class="setting-control">
								<input type="number"
									name="<?php echo self::OPTION_KEY; ?>[max_emails_per_user_per_day]"
									value="<?php echo esc_attr( $settings['max_emails_per_user_per_day'] ); ?>"
									min="1"
									max="100"
									class="small-text">
							</div>
						</div>
					</div>
				</div>

				<!-- Template Settings Section -->
				<div class="settings-section">
					<div class="section-header">
						<span style="font-size: 24px;">📝</span>
						<h2><?php esc_html_e( 'Templates', 'apollo-social' ); ?></h2>
					</div>
					<div class="section-content">
						<?php
						self::render_setting_row(
							'use_custom_templates',
							$settings,
							__( 'Usar templates personalizados', 'apollo-social' ),
							__( 'Permite usar templates personalizados do Email Hub ao invés dos padrões.', 'apollo-social' ),
							false
						);
						?>

						<div class="setting-row" style="margin-top: 16px;">
							<a href="<?php echo admin_url( 'admin.php?page=apollo-email-templates&category=events' ); ?>" class="button">
								📝 <?php esc_html_e( 'Editar Templates de Eventos', 'apollo-social' ); ?>
							</a>
						</div>
					</div>

					<div class="submit-section">
						<?php submit_button( __( 'Salvar Configurações', 'apollo-social' ), 'primary', 'submit', false ); ?>
						<span class="save-status" id="save-status"></span>
					</div>
				</div>
			</form>

			<!-- Info Box -->
			<div class="settings-section" style="background: linear-gradient(135deg, #e8f5e9 0%, #fff 100%);">
				<div class="section-header" style="background: transparent; border-bottom: none;">
					<span style="font-size: 24px;">💡</span>
					<h2><?php esc_html_e( 'Como funciona', 'apollo-social' ); ?></h2>
				</div>
				<div class="section-content">
					<ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
						<li><?php esc_html_e( 'As configurações acima controlam quais tipos de notificações estão disponíveis no sistema.', 'apollo-social' ); ?></li>
						<li><?php esc_html_e( 'Quando um tipo está ativado, os usuários podem escolher individualmente se querem receber ou não.', 'apollo-social' ); ?></li>
						<li><?php esc_html_e( 'Os usuários configuram suas preferências em: Minha Conta → Email', 'apollo-social' ); ?></li>
						<li><?php esc_html_e( 'O sistema respeita tanto a configuração global quanto a preferência individual do usuário.', 'apollo-social' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a setting row with toggle.
	 *
	 * @param string $key          Setting key.
	 * @param array  $settings     Current settings.
	 * @param string $label        Setting label.
	 * @param string $desc         Setting description.
	 * @param bool   $user_control Whether users can control this setting.
	 */
	private static function render_setting_row( string $key, array $settings, string $label, string $desc, bool $user_control ): void {
		?>
		<div class="setting-row">
			<div class="setting-info">
				<span class="setting-label">
					<?php echo esc_html( $label ); ?>
					<?php if ( $user_control ) : ?>
						<span class="apollo-badge user-control" title="<?php esc_attr_e( 'Usuários podem controlar esta opção individualmente', 'apollo-social' ); ?>">
							👤 <?php esc_html_e( 'Controle do usuário', 'apollo-social' ); ?>
						</span>
					<?php endif; ?>
				</span>
				<span class="setting-desc"><?php echo esc_html( $desc ); ?></span>
			</div>
			<div class="setting-control">
				<label class="apollo-toggle">
					<input type="checkbox"
						name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
						value="1"
						<?php checked( $settings[ $key ] ?? false ); ?>>
					<span class="slider"></span>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX save settings.
	 */
	public static function ajax_save_settings(): void {
		check_ajax_referer( 'apollo_email_notifications_group-options', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-social' ) ) );
		}

		$settings = isset( $_POST[ self::OPTION_KEY ] ) ? $_POST[ self::OPTION_KEY ] : array();
		$settings = self::sanitize_settings( $settings );

		update_option( self::OPTION_KEY, $settings );

		wp_send_json_success( array( 'message' => __( 'Configurações salvas!', 'apollo-social' ) ) );
	}
}

// Initialize
EmailNotificationsAdmin::init();
