<?php
declare(strict_types=1);

namespace Apollo\Admin;

/**
 * Apollo Email Hub - Unified Email Management System
 *
 * Central admin panel for all email settings across:
 * - Apollo Social (account, membership, journey)
 * - Apollo Core (system, mod)
 * - Apollo Events Manager (event notifications, bookmarks)
 *
 * @package Apollo_Social
 * @since 1.3.0
 */
class EmailHubAdmin {

	private const OPTION_KEY    = 'apollo_email_hub_settings';
	private const TEMPLATES_KEY = 'apollo_email_templates';

	/**
	 * All available placeholders with descriptions
	 */
	private static array $placeholders = array();

	/**
	 * All email templates organized by category
	 */
	private static array $template_categories = array();

	/**
	 * Initialize the Email Hub
	 */
	public static function init(): void {
		self::registerPlaceholders();
		self::registerTemplateCategories();

		add_action( 'admin_menu', array( self::class, 'addAdminMenu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'wp_ajax_apollo_email_hub_save', array( self::class, 'ajaxSaveSettings' ) );
		add_action( 'wp_ajax_apollo_email_hub_test', array( self::class, 'ajaxSendTestEmail' ) );
		add_action( 'wp_ajax_apollo_email_hub_preview', array( self::class, 'ajaxPreviewEmail' ) );
	}

	/**
	 * Register all available placeholders
	 */
	private static function registerPlaceholders(): void {
		self::$placeholders = array(
			// User placeholders
			'user'        => array(
				'category' => '👤 Usuário',
				'items'    => array(
					'[user-name]'       => array(
						'label'       => 'Nome do usuário',
						'description' => 'Nome de login do WordPress',
						'example'     => 'joao_silva',
						'source'      => 'wp_users.user_login',
					),
					'[display-name]'    => array(
						'label'       => 'Nome de exibição',
						'description' => 'Nome público escolhido pelo usuário',
						'example'     => 'João Silva',
						'source'      => 'wp_users.display_name',
					),
					'[user-email]'      => array(
						'label'       => 'Email do usuário',
						'description' => 'Email registrado na conta',
						'example'     => 'joao@email.com',
						'source'      => 'wp_users.user_email',
					),
					'[user-id]'         => array(
						'label'       => 'ID do usuário',
						'description' => 'ID único no banco de dados',
						'example'     => '42',
						'source'      => 'wp_users.ID',
					),
					'[user-registered]' => array(
						'label'       => 'Data de registro',
						'description' => 'Quando a conta foi criada',
						'example'     => '15/03/2024',
						'source'      => 'wp_users.user_registered',
					),
					'[first-name]'      => array(
						'label'       => 'Primeiro nome',
						'description' => 'Primeiro nome do perfil',
						'example'     => 'João',
						'source'      => 'usermeta.first_name',
					),
					'[last-name]'       => array(
						'label'       => 'Sobrenome',
						'description' => 'Sobrenome do perfil',
						'example'     => 'Silva',
						'source'      => 'usermeta.last_name',
					),
				),
			),
			// Cultura::Rio placeholders
			'cultura'     => array(
				'category' => '🎭 Cultura::Rio',
				'items'    => array(
					'[cultura-identities]'       => array(
						'label'       => 'Identidades culturais',
						'description' => 'Lista de identidades selecionadas no registro',
						'example'     => 'Clubber, DJ Profissional, Producer',
						'source'      => 'usermeta.apollo_cultura_identities',
					),
					'[membership-status]'        => array(
						'label'       => 'Status do membership',
						'description' => 'Estado atual da solicitação',
						'example'     => 'Aprovado',
						'source'      => 'usermeta.apollo_membership_status',
					),
					'[membership-requested]'     => array(
						'label'       => 'Identidades solicitadas',
						'description' => 'Identidades que precisam de aprovação',
						'example'     => 'DJ Profissional',
						'source'      => 'usermeta.apollo_membership_requested',
					),
					'[membership-approved-date]' => array(
						'label'       => 'Data de aprovação',
						'description' => 'Quando o membership foi aprovado',
						'example'     => '20/03/2024',
						'source'      => 'usermeta.apollo_membership_approved_at',
					),
				),
			),
			// Preferences placeholders
			'preferences' => array(
				'category' => '🎵 Preferências',
				'items'    => array(
					'[fav-sounds]'       => array(
						'label'       => 'Gêneros favoritos',
						'description' => 'Lista de gêneros musicais separados por vírgula',
						'example'     => 'House, Techno, Drum & Bass',
						'source'      => 'usermeta.apollo_sounds',
					),
					'[fav-sounds-count]' => array(
						'label'       => 'Quantidade de gêneros',
						'description' => 'Número de gêneros selecionados',
						'example'     => '3',
						'source'      => 'usermeta.apollo_sounds (count)',
					),
				),
			),
			// Event placeholders
			'event'       => array(
				'category' => '📅 Eventos',
				'items'    => array(
					'[event-name]'    => array(
						'label'       => 'Nome do evento',
						'description' => 'Título do evento',
						'example'     => 'Sunset Sessions Vol. 5',
						'source'      => 'post.post_title',
					),
					'[event-date]'    => array(
						'label'       => 'Data do evento',
						'description' => 'Data formatada do evento',
						'example'     => 'Sábado, 25 de Março',
						'source'      => 'postmeta.event_date',
					),
					'[event-time]'    => array(
						'label'       => 'Horário',
						'description' => 'Hora de início do evento',
						'example'     => '22:00',
						'source'      => 'postmeta.event_time',
					),
					'[event-venue]'   => array(
						'label'       => 'Local do evento',
						'description' => 'Nome do venue/local',
						'example'     => 'Club Rio',
						'source'      => 'postmeta.event_venue',
					),
					'[event-address]' => array(
						'label'       => 'Endereço',
						'description' => 'Endereço completo do local',
						'example'     => 'Rua das Flores, 123 - Lapa, RJ',
						'source'      => 'postmeta.event_address',
					),
					'[event-url]'     => array(
						'label'       => 'Link do evento',
						'description' => 'URL da página do evento',
						'example'     => 'https://site.com/evento/sunset-sessions',
						'source'      => 'get_permalink()',
					),
					'[event-djs]'     => array(
						'label'       => 'DJs do evento',
						'description' => 'Lista de DJs que vão tocar',
						'example'     => 'DJ Marky, Patife, XRS',
						'source'      => 'postmeta.event_djs',
					),
				),
			),
			// Site placeholders
			'site'        => array(
				'category' => '🌐 Site',
				'items'    => array(
					'[site-name]'     => array(
						'label'       => 'Nome do site',
						'description' => 'Título do WordPress',
						'example'     => 'Apollo::Rio',
						'source'      => 'get_bloginfo("name")',
					),
					'[site-url]'      => array(
						'label'       => 'URL do site',
						'description' => 'Endereço principal do site',
						'example'     => 'https://apollo.rio',
						'source'      => 'home_url()',
					),
					'[login-url]'     => array(
						'label'       => 'URL de login',
						'description' => 'Link para página de login',
						'example'     => 'https://apollo.rio/entrar',
						'source'      => 'wp_login_url()',
					),
					'[profile-url]'   => array(
						'label'       => 'URL do perfil',
						'description' => 'Link para o perfil do usuário',
						'example'     => 'https://apollo.rio/perfil/joao',
						'source'      => 'apollo_get_profile_url()',
					),
					'[dashboard-url]' => array(
						'label'       => 'URL do dashboard',
						'description' => 'Link para o painel do usuário',
						'example'     => 'https://apollo.rio/minha-conta',
						'source'      => 'apollo_get_dashboard_url()',
					),
					'[current-year]'  => array(
						'label'       => 'Ano atual',
						'description' => 'Ano para copyright e datas',
						'example'     => '2024',
						'source'      => 'date("Y")',
					),
				),
			),
			// Admin placeholders
			'admin'       => array(
				'category' => '🔧 Admin',
				'items'    => array(
					'[admin-name]'       => array(
						'label'       => 'Nome do admin',
						'description' => 'Nome de quem aprovou/rejeitou',
						'example'     => 'Admin Apollo',
						'source'      => 'usermeta.apollo_membership_approved_by',
					),
					'[admin-message]'    => array(
						'label'       => 'Mensagem do admin',
						'description' => 'Mensagem personalizada do admin',
						'example'     => 'Bem-vindo à equipe!',
						'source'      => 'custom',
					),
					'[rejection-reason]' => array(
						'label'       => 'Motivo da rejeição',
						'description' => 'Explicação se membership foi rejeitado',
						'example'     => 'Documentação incompleta',
						'source'      => 'usermeta.apollo_membership_rejection_reason',
					),
				),
			),
		);
	}

	/**
	 * Register all email template categories
	 */
	private static function registerTemplateCategories(): void {
		self::$template_categories = array(
			'social' => array(
				'label'       => '🔵 Apollo Social',
				'description' => 'Emails de conta, perfil e interações sociais',
				'plugin'      => 'apollo-social',
				'templates'   => array(
					'welcome'                   => array(
						'name'                  => 'Boas-vindas',
						'description'           => 'Enviado após criação de conta',
						'trigger'               => 'user_register',
						'icon'                  => '👋',
						'required_placeholders' => array( '[user-name]', '[display-name]', '[login-url]' ),
						'default_subject'       => 'Bem-vindo(a) à Cultura::Rio, [display-name]! 🎉',
						'default_body'          => self::getDefaultWelcomeTemplate(),
					),
					'membership_approved'       => array(
						'name'                  => 'Membership Aprovado',
						'description'           => 'Enviado quando membership é aprovado',
						'trigger'               => 'apollo_membership_approved',
						'icon'                  => '✅',
						'required_placeholders' => array( '[display-name]', '[membership-status]', '[cultura-identities]' ),
						'default_subject'       => 'Parabéns [display-name]! Seu membership foi aprovado 🎭',
						'default_body'          => self::getDefaultApprovedTemplate(),
					),
					'membership_rejected'       => array(
						'name'                  => 'Membership Rejeitado',
						'description'           => 'Enviado quando membership é rejeitado',
						'trigger'               => 'apollo_membership_rejected',
						'icon'                  => '❌',
						'required_placeholders' => array( '[display-name]', '[rejection-reason]' ),
						'default_subject'       => '[display-name], sobre sua solicitação de membership',
						'default_body'          => self::getDefaultRejectedTemplate(),
					),
					'membership_pending'        => array(
						'name'                  => 'Membership em Análise',
						'description'           => 'Confirmação de solicitação recebida',
						'trigger'               => 'apollo_membership_requested',
						'icon'                  => '⏳',
						'required_placeholders' => array( '[display-name]', '[membership-requested]' ),
						'default_subject'       => 'Recebemos sua solicitação, [display-name]!',
						'default_body'          => self::getDefaultPendingTemplate(),
					),
					'journey_dj_progress'       => array(
						'name'                  => 'Journey: DJ Progresso',
						'description'           => 'Mensagem de progressão para DJs',
						'trigger'               => 'apollo_journey_dj_progress',
						'icon'                  => '🎧',
						'required_placeholders' => array( '[display-name]', '[cultura-identities]' ),
						'default_subject'       => '[display-name], você está evoluindo como DJ! 🎧',
						'default_body'          => self::getDefaultJourneyDJTemplate(),
					),
					'journey_producer_progress' => array(
						'name'                  => 'Journey: Producer Progresso',
						'description'           => 'Mensagem de progressão para Producers',
						'trigger'               => 'apollo_journey_producer_progress',
						'icon'                  => '🎛️',
						'required_placeholders' => array( '[display-name]' ),
						'default_subject'       => 'Sua jornada como Producer continua! 🎛️',
						'default_body'          => self::getDefaultJourneyProducerTemplate(),
					),
				),
			),
			'core'   => array(
				'label'       => '🟢 Apollo Core',
				'description' => 'Emails de sistema, moderação e segurança',
				'plugin'      => 'apollo-core',
				'templates'   => array(
					'password_reset' => array(
						'name'                  => 'Redefinir Senha',
						'description'           => 'Link para redefinição de senha',
						'trigger'               => 'retrieve_password',
						'icon'                  => '🔑',
						'required_placeholders' => array( '[user-name]', '[site-name]' ),
						'default_subject'       => 'Redefinição de senha - [site-name]',
						'default_body'          => self::getDefaultPasswordResetTemplate(),
					),
					'security_alert' => array(
						'name'                  => 'Alerta de Segurança',
						'description'           => 'Notificação de atividade suspeita',
						'trigger'               => 'apollo_security_alert',
						'icon'                  => '🚨',
						'required_placeholders' => array( '[user-name]', '[site-name]' ),
						'default_subject'       => '⚠️ Alerta de segurança - [site-name]',
						'default_body'          => self::getDefaultSecurityAlertTemplate(),
					),
					'mod_notice'     => array(
						'name'                  => 'Aviso de Moderação',
						'description'           => 'Quando conteúdo é moderado',
						'trigger'               => 'apollo_content_moderated',
						'icon'                  => '⚖️',
						'required_placeholders' => array( '[display-name]', '[admin-message]' ),
						'default_subject'       => 'Aviso sobre seu conteúdo - [site-name]',
						'default_body'          => self::getDefaultModerationTemplate(),
					),
				),
			),
			'events' => array(
				'label'       => '🟣 Apollo Events',
				'description' => 'Emails de eventos, bookmarks e lembretes',
				'plugin'      => 'apollo-events-manager',
				'templates'   => array(
					'event_reminder'  => array(
						'name'                  => 'Lembrete de Evento',
						'description'           => 'Enviado 24h antes do evento',
						'trigger'               => 'apollo_event_reminder',
						'icon'                  => '⏰',
						'required_placeholders' => array( '[display-name]', '[event-name]', '[event-date]', '[event-venue]' ),
						'default_subject'       => '⏰ Amanhã: [event-name]!',
						'default_body'          => self::getDefaultEventReminderTemplate(),
					),
					'event_bookmark'  => array(
						'name'                  => 'Evento Salvo',
						'description'           => 'Confirmação de bookmark de evento',
						'trigger'               => 'apollo_event_bookmarked',
						'icon'                  => '🔖',
						'required_placeholders' => array( '[display-name]', '[event-name]', '[event-url]' ),
						'default_subject'       => '🔖 Você salvou: [event-name]',
						'default_body'          => self::getDefaultEventBookmarkTemplate(),
					),
					'event_update'    => array(
						'name'                  => 'Atualização de Evento',
						'description'           => 'Quando um evento salvo é atualizado',
						'trigger'               => 'apollo_event_updated',
						'icon'                  => '📢',
						'required_placeholders' => array( '[display-name]', '[event-name]' ),
						'default_subject'       => '📢 Atualização: [event-name]',
						'default_body'          => self::getDefaultEventUpdateTemplate(),
					),
					'event_cancelled' => array(
						'name'                  => 'Evento Cancelado',
						'description'           => 'Quando um evento é cancelado',
						'trigger'               => 'apollo_event_cancelled',
						'icon'                  => '🚫',
						'required_placeholders' => array( '[display-name]', '[event-name]' ),
						'default_subject'       => '🚫 Evento cancelado: [event-name]',
						'default_body'          => self::getDefaultEventCancelledTemplate(),
					),
					'weekly_digest'   => array(
						'name'                  => 'Digest Semanal',
						'description'           => 'Resumo semanal de eventos',
						'trigger'               => 'apollo_weekly_digest',
						'icon'                  => '📰',
						'required_placeholders' => array( '[display-name]', '[fav-sounds]' ),
						'default_subject'       => '🎉 Eventos desta semana no Rio!',
						'default_body'          => self::getDefaultWeeklyDigestTemplate(),
					),
				),
			),
		);
	}

	/**
	 * Add admin menu
	 */
	public static function addAdminMenu(): void {
		add_menu_page(
			__( 'Apollo Emails', 'apollo-social' ),
			__( 'Apollo Emails', 'apollo-social' ),
			'manage_options',
			'apollo-email-hub',
			array( self::class, 'renderHubPage' ),
			'dashicons-email-alt',
			57
		);

		add_submenu_page(
			'apollo-email-hub',
			__( 'Dashboard', 'apollo-social' ),
			__( 'Dashboard', 'apollo-social' ),
			'manage_options',
			'apollo-email-hub',
			array( self::class, 'renderHubPage' )
		);

		add_submenu_page(
			'apollo-email-hub',
			__( 'Templates', 'apollo-social' ),
			__( '📝 Templates', 'apollo-social' ),
			'manage_options',
			'apollo-email-templates',
			array( self::class, 'renderTemplatesPage' )
		);

		add_submenu_page(
			'apollo-email-hub',
			__( 'Placeholders', 'apollo-social' ),
			__( '🏷️ Placeholders', 'apollo-social' ),
			'manage_options',
			'apollo-email-placeholders',
			array( self::class, 'renderPlaceholdersPage' )
		);

		add_submenu_page(
			'apollo-email-hub',
			__( 'Configurações', 'apollo-social' ),
			__( '⚙️ Configurações', 'apollo-social' ),
			'manage_options',
			'apollo-email-settings',
			array( self::class, 'renderSettingsPage' )
		);

		add_submenu_page(
			'apollo-email-hub',
			__( 'Logs', 'apollo-social' ),
			__( '📊 Logs', 'apollo-social' ),
			'manage_options',
			'apollo-email-logs',
			array( self::class, 'renderLogsPage' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public static function enqueueAssets( string $hook ): void {
		if ( strpos( $hook, 'apollo-email' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_script( 'wp-codemirror' );
		wp_enqueue_script( 'csslint' );
		wp_enqueue_script( 'htmlhint' );

		wp_add_inline_style( 'admin-bar', self::getAdminStyles() );
		wp_add_inline_script( 'jquery', self::getAdminScripts(), 'after' );
	}

	/**
	 * Render Hub Dashboard
	 */
	public static function renderHubPage(): void {
		$settings  = get_option( self::OPTION_KEY, array() );
		$templates = get_option( self::TEMPLATES_KEY, array() );

		$stats = array(
			'total_templates' => 0,
			'configured'      => 0,
			'pending'         => 0,
		);

		foreach ( self::$template_categories as $cat ) {
			$stats['total_templates'] += count( $cat['templates'] );
			foreach ( $cat['templates'] as $key => $tpl ) {
				if ( ! empty( $templates[ $key ]['body'] ) ) {
					++$stats['configured'];
				} else {
					++$stats['pending'];
				}
			}
		}
		?>
		<div class="wrap apollo-email-hub">
			<h1>
				<span class="dashicons dashicons-email-alt" style="font-size: 32px; margin-right: 10px;"></span>
				Apollo Email Hub
			</h1>
			
			<div class="apollo-hub-header">
				<p class="description" style="font-size: 14px; max-width: 800px;">
					Central de gerenciamento de emails para todo o ecossistema Apollo. 
					Configure templates, placeholders e monitore envios em um único lugar.
				</p>
			</div>

			<!-- Stats Cards -->
			<div class="apollo-stats-grid">
				<div class="apollo-stat-card">
					<div class="stat-icon">📧</div>
					<div class="stat-value"><?php echo esc_html( $stats['total_templates'] ); ?></div>
					<div class="stat-label">Templates Totais</div>
				</div>
				<div class="apollo-stat-card apollo-stat-success">
					<div class="stat-icon">✅</div>
					<div class="stat-value"><?php echo esc_html( $stats['configured'] ); ?></div>
					<div class="stat-label">Configurados</div>
				</div>
				<div class="apollo-stat-card apollo-stat-warning">
					<div class="stat-icon">⏳</div>
					<div class="stat-value"><?php echo esc_html( $stats['pending'] ); ?></div>
					<div class="stat-label">Pendentes</div>
				</div>
				<div class="apollo-stat-card apollo-stat-info">
					<div class="stat-icon">🏷️</div>
					<div class="stat-value"><?php echo esc_html( self::countPlaceholders() ); ?></div>
					<div class="stat-label">Placeholders</div>
				</div>
			</div>

			<!-- Plugin Integration Status -->
			<div class="apollo-integration-status">
				<h2>🔗 Status de Integração</h2>
				<div class="integration-grid">
					<?php
					foreach ( self::$template_categories as $key => $cat ) :
						$is_active = self::isPluginActive( $cat['plugin'] );
						?>
						<div class="integration-card <?php echo $is_active ? 'active' : 'inactive'; ?>">
							<div class="integration-icon"><?php echo explode( ' ', $cat['label'] )[0]; ?></div>
							<div class="integration-info">
								<strong><?php echo esc_html( $cat['label'] ); ?></strong>
								<span class="integration-status">
									<?php echo $is_active ? '✅ Ativo' : '⚠️ Inativo'; ?>
								</span>
								<span class="template-count">
									<?php echo count( $cat['templates'] ); ?> templates
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Quick Actions -->
			<div class="apollo-quick-actions">
				<h2>⚡ Ações Rápidas</h2>
				<div class="actions-grid">
					<a href="<?php echo admin_url( 'admin.php?page=apollo-email-templates' ); ?>" class="action-card">
						<span class="action-icon">📝</span>
						<span class="action-label">Editar Templates</span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=apollo-email-placeholders' ); ?>" class="action-card">
						<span class="action-icon">🏷️</span>
						<span class="action-label">Ver Placeholders</span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=apollo-email-settings' ); ?>" class="action-card">
						<span class="action-icon">⚙️</span>
						<span class="action-label">Configurações</span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=apollo-email-logs' ); ?>" class="action-card">
						<span class="action-icon">📊</span>
						<span class="action-label">Ver Logs</span>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Templates Page
	 */
	public static function renderTemplatesPage(): void {
		$templates       = get_option( self::TEMPLATES_KEY, array() );
		$active_category = sanitize_key( $_GET['category'] ?? 'social' );
		$active_template = sanitize_key( $_GET['template'] ?? '' );
		?>
		<div class="wrap apollo-email-hub">
			<h1>📝 Email Templates</h1>
			
			<div class="apollo-templates-layout">
				<!-- Sidebar: Categories & Templates -->
				<div class="templates-sidebar">
					<?php foreach ( self::$template_categories as $cat_key => $category ) : ?>
						<div class="template-category <?php echo $cat_key === $active_category ? 'active' : ''; ?>">
							<div class="category-header" data-category="<?php echo esc_attr( $cat_key ); ?>">
								<?php echo esc_html( $category['label'] ); ?>
								<span class="category-count"><?php echo count( $category['templates'] ); ?></span>
							</div>
							<div class="category-templates">
								<?php
								foreach ( $category['templates'] as $tpl_key => $template ) :
									$is_configured = ! empty( $templates[ $tpl_key ]['body'] );
									?>
									<a href="<?php echo admin_url( "admin.php?page=apollo-email-templates&category={$cat_key}&template={$tpl_key}" ); ?>" 
										class="template-item <?php echo $tpl_key === $active_template ? 'active' : ''; ?> <?php echo $is_configured ? 'configured' : ''; ?>">
										<span class="template-icon"><?php echo $template['icon']; ?></span>
										<span class="template-name"><?php echo esc_html( $template['name'] ); ?></span>
										<span class="template-status"><?php echo $is_configured ? '✓' : '○'; ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Main: Template Editor -->
				<div class="templates-editor">
					<?php
					if ( $active_template && isset( self::$template_categories[ $active_category ]['templates'][ $active_template ] ) ) :
						$tpl   = self::$template_categories[ $active_category ]['templates'][ $active_template ];
						$saved = $templates[ $active_template ] ?? array();
						?>
						<div class="editor-header">
							<h2>
								<?php echo $tpl['icon']; ?> <?php echo esc_html( $tpl['name'] ); ?>
								<span class="trigger-badge" title="Hook WordPress que dispara este email">
									🎯 <?php echo esc_html( $tpl['trigger'] ); ?>
								</span>
							</h2>
							<p class="description"><?php echo esc_html( $tpl['description'] ); ?></p>
						</div>

						<form id="template-form" class="template-form">
							<input type="hidden" name="template_key" value="<?php echo esc_attr( $active_template ); ?>">
							<input type="hidden" name="category_key" value="<?php echo esc_attr( $active_category ); ?>">
							<?php wp_nonce_field( 'apollo_email_hub', 'apollo_email_nonce' ); ?>

							<!-- Subject -->
							<div class="form-field">
								<label>
									📌 Assunto do Email
									<span class="tooltip" data-tip="Use placeholders como [display-name] para personalizar">ℹ️</span>
								</label>
								<input type="text" 
										name="subject" 
										class="large-text subject-input"
										value="<?php echo esc_attr( $saved['subject'] ?? $tpl['default_subject'] ); ?>"
										placeholder="<?php echo esc_attr( $tpl['default_subject'] ); ?>">
							</div>

							<!-- Required Placeholders -->
							<div class="required-placeholders">
								<strong>Placeholders recomendados:</strong>
								<?php
								foreach ( $tpl['required_placeholders'] as $ph ) :
									$ph_info = self::getPlaceholderInfo( $ph );
									?>
									<span class="placeholder-chip" 
											data-placeholder="<?php echo esc_attr( $ph ); ?>"
											title="<?php echo esc_attr( $ph_info['description'] ?? '' ); ?>">
										<?php echo esc_html( $ph ); ?>
										<span class="copy-btn" title="Clique para copiar">📋</span>
									</span>
								<?php endforeach; ?>
							</div>

							<!-- Body Editor -->
							<div class="form-field">
								<label>
									📄 Corpo do Email (HTML)
									<span class="tooltip" data-tip="Use HTML para formatar. Todos os placeholders serão substituídos automaticamente.">ℹ️</span>
								</label>
								<div class="editor-toolbar">
									<button type="button" class="toolbar-btn" data-action="bold" title="Negrito (Ctrl+B)">B</button>
									<button type="button" class="toolbar-btn" data-action="italic" title="Itálico (Ctrl+I)"><em>I</em></button>
									<button type="button" class="toolbar-btn" data-action="link" title="Link">🔗</button>
									<button type="button" class="toolbar-btn" data-action="heading" title="Título">H</button>
									<span class="toolbar-separator"></span>
									<button type="button" class="toolbar-btn insert-placeholder-btn" title="Inserir Placeholder">🏷️ Inserir Placeholder</button>
								</div>
								<textarea name="body" 
											id="template-body" 
											class="large-text code" 
											rows="20"><?php echo esc_textarea( $saved['body'] ?? $tpl['default_body'] ); ?></textarea>
							</div>

							<!-- All Placeholders Reference -->
							<div class="placeholders-reference">
								<h4>🏷️ Todos os Placeholders Disponíveis</h4>
								<div class="placeholders-accordion">
									<?php foreach ( self::$placeholders as $cat_key => $cat ) : ?>
										<div class="placeholder-group">
											<div class="placeholder-group-header"><?php echo esc_html( $cat['category'] ); ?></div>
											<div class="placeholder-group-items">
												<?php foreach ( $cat['items'] as $ph => $info ) : ?>
													<div class="placeholder-item" data-placeholder="<?php echo esc_attr( $ph ); ?>">
														<code class="ph-code"><?php echo esc_html( $ph ); ?></code>
														<span class="ph-label"><?php echo esc_html( $info['label'] ); ?></span>
														<span class="ph-tooltip" title="<?php echo esc_attr( $info['description'] ); ?>&#10;Exemplo: <?php echo esc_attr( $info['example'] ); ?>&#10;Fonte: <?php echo esc_attr( $info['source'] ); ?>">ℹ️</span>
														<button type="button" class="ph-insert" title="Inserir no editor">+</button>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Test Email Section -->
							<div class="test-email-section">
								<h4>📧 Testar Email</h4>
								<p class="description">Envie um email de teste antes de salvar o template.</p>
								<div class="test-email-row">
									<input type="email" 
											name="test_email" 
											class="regular-text" 
											placeholder="seu@email.com"
											value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
									<button type="button" id="send-test-btn" class="button">
										📤 Enviar Teste
									</button>
									<button type="button" id="preview-btn" class="button">
										👁️ Preview
									</button>
								</div>
								<div id="test-email-result"></div>
							</div>

							<!-- Save Actions -->
							<div class="form-actions">
								<button type="submit" class="button button-primary button-hero">
									💾 Salvar Template
								</button>
								<button type="button" id="reset-default-btn" class="button">
									🔄 Restaurar Padrão
								</button>
								<span id="save-status"></span>
							</div>
						</form>

						<!-- Preview Modal -->
						<div id="preview-modal" class="apollo-modal" style="display:none;">
							<div class="modal-content">
								<div class="modal-header">
									<h3>👁️ Preview do Email</h3>
									<button type="button" class="modal-close">&times;</button>
								</div>
								<div class="modal-body">
									<div class="preview-subject"></div>
									<iframe id="preview-frame" class="preview-frame"></iframe>
								</div>
							</div>
						</div>

					<?php else : ?>
						<div class="no-template-selected">
							<div class="empty-state">
								<span class="empty-icon">📝</span>
								<h3>Selecione um template</h3>
								<p>Escolha um template na lista à esquerda para começar a editar.</p>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Placeholders Reference Page
	 */
	public static function renderPlaceholdersPage(): void {
		?>
		<div class="wrap apollo-email-hub">
			<h1>🏷️ Referência de Placeholders</h1>
			<p class="description" style="max-width: 800px;">
				Placeholders são variáveis que são substituídas por valores reais quando o email é enviado.
				Clique em qualquer placeholder para copiá-lo.
			</p>

			<div class="placeholders-full-reference">
				<?php foreach ( self::$placeholders as $cat_key => $category ) : ?>
					<div class="placeholder-category-card">
						<h2><?php echo esc_html( $category['category'] ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th style="width:180px;">Placeholder</th>
									<th style="width:200px;">Descrição</th>
									<th style="width:150px;">Exemplo</th>
									<th>Fonte de Dados</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $category['items'] as $ph => $info ) : ?>
									<tr class="placeholder-row" data-placeholder="<?php echo esc_attr( $ph ); ?>">
										<td>
											<code class="copyable-placeholder" title="Clique para copiar"><?php echo esc_html( $ph ); ?></code>
										</td>
										<td>
											<strong><?php echo esc_html( $info['label'] ); ?></strong>
											<br><small><?php echo esc_html( $info['description'] ); ?></small>
										</td>
										<td><code><?php echo esc_html( $info['example'] ); ?></code></td>
										<td><small class="source-ref"><?php echo esc_html( $info['source'] ); ?></small></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Settings Page
	 */
	public static function renderSettingsPage(): void {
		$settings = get_option( self::OPTION_KEY, array() );
		?>
		<div class="wrap apollo-email-hub">
			<h1>⚙️ Configurações de Email</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'apollo_email_hub_group' ); ?>
				
				<div class="settings-sections">
					<!-- General Settings -->
					<div class="settings-section">
						<h2>📧 Configurações Gerais</h2>
						<table class="form-table">
							<tr>
								<th>
									<label for="from_name">Nome do Remetente</label>
									<span class="tooltip" data-tip="Nome que aparece como remetente dos emails">ℹ️</span>
								</th>
								<td>
									<input type="text" 
											id="from_name" 
											name="<?php echo self::OPTION_KEY; ?>[from_name]" 
											class="regular-text"
											value="<?php echo esc_attr( $settings['from_name'] ?? get_bloginfo( 'name' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="from_email">Email do Remetente</label>
									<span class="tooltip" data-tip="Endereço de email que aparece como remetente">ℹ️</span>
								</th>
								<td>
									<input type="email" 
											id="from_email" 
											name="<?php echo self::OPTION_KEY; ?>[from_email]" 
											class="regular-text"
											value="<?php echo esc_attr( $settings['from_email'] ?? get_option( 'admin_email' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="reply_to">Reply-To</label>
									<span class="tooltip" data-tip="Email para onde respostas serão enviadas">ℹ️</span>
								</th>
								<td>
									<input type="email" 
											id="reply_to" 
											name="<?php echo self::OPTION_KEY; ?>[reply_to]" 
											class="regular-text"
											value="<?php echo esc_attr( $settings['reply_to'] ?? '' ); ?>"
											placeholder="Deixe vazio para usar o email do remetente">
								</td>
							</tr>
						</table>
					</div>

					<!-- Template Settings -->
					<div class="settings-section">
						<h2>🎨 Estilo dos Templates</h2>
						<table class="form-table">
							<tr>
								<th>
									<label for="primary_color">Cor Primária</label>
									<span class="tooltip" data-tip="Cor principal usada nos templates">ℹ️</span>
								</th>
								<td>
									<input type="color" 
											id="primary_color" 
											name="<?php echo self::OPTION_KEY; ?>[primary_color]" 
											value="<?php echo esc_attr( $settings['primary_color'] ?? '#00d4ff' ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="logo_url">URL do Logo</label>
									<span class="tooltip" data-tip="Logo que aparece no header dos emails">ℹ️</span>
								</th>
								<td>
									<input type="url" 
											id="logo_url" 
											name="<?php echo self::OPTION_KEY; ?>[logo_url]" 
											class="regular-text"
											value="<?php echo esc_attr( $settings['logo_url'] ?? '' ); ?>">
									<button type="button" class="button upload-logo-btn">📷 Upload</button>
								</td>
							</tr>
							<tr>
								<th>
									<label for="footer_text">Texto do Rodapé</label>
									<span class="tooltip" data-tip="Texto que aparece no footer de todos os emails">ℹ️</span>
								</th>
								<td>
									<textarea id="footer_text" 
												name="<?php echo self::OPTION_KEY; ?>[footer_text]" 
												class="large-text" 
												rows="3"><?php echo esc_textarea( $settings['footer_text'] ?? '© [current-year] [site-name]. Todos os direitos reservados.' ); ?></textarea>
								</td>
							</tr>
						</table>
					</div>

					<!-- Notification Settings -->
					<div class="settings-section">
						<h2>🔔 Notificações</h2>
						<table class="form-table">
							<?php
							$notifications = array(
								'welcome_email'     => array(
									'label'   => 'Email de boas-vindas',
									'default' => true,
								),
								'membership_emails' => array(
									'label'   => 'Emails de membership',
									'default' => true,
								),
								'event_reminders'   => array(
									'label'   => 'Lembretes de eventos',
									'default' => true,
								),
								'weekly_digest'     => array(
									'label'   => 'Digest semanal',
									'default' => false,
								),
								'security_alerts'   => array(
									'label'   => 'Alertas de segurança',
									'default' => true,
								),
							);
							foreach ( $notifications as $key => $notif ) :
								?>
								<tr>
									<th>
										<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $notif['label'] ); ?></label>
									</th>
									<td>
										<label class="toggle-switch">
											<input type="checkbox" 
													id="<?php echo esc_attr( $key ); ?>" 
													name="<?php echo self::OPTION_KEY; ?>[<?php echo esc_attr( $key ); ?>]" 
													value="1"
													<?php checked( $settings[ $key ] ?? $notif['default'] ); ?>>
											<span class="toggle-slider"></span>
										</label>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>

				<?php submit_button( '💾 Salvar Configurações' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Logs Page
	 */
	public static function renderLogsPage(): void {
		// Filters
		$filter_type     = sanitize_key( $_GET['type'] ?? '' );
		$filter_severity = sanitize_key( $_GET['severity'] ?? '' );
		$filter_template = sanitize_key( $_GET['template'] ?? '' );
		$page            = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$per_page        = 50;

		// Use EmailSecurityLog if available
		if ( class_exists( '\Apollo\Security\EmailSecurityLog' ) ) {
			$result      = \Apollo\Security\EmailSecurityLog::getLogs(
				array(
					'type'     => $filter_type,
					'severity' => $filter_severity,
					'template' => $filter_template,
					'page'     => $page,
					'per_page' => $per_page,
				)
			);
			$logs        = $result['items'];
			$total       = $result['total'];
			$total_pages = $result['pages'];

			$stats = \Apollo\Security\EmailSecurityLog::getStats( 'today' );
		} else {
			// Fallback to wp_options
			$all_logs    = get_option( 'apollo_email_logs', array() );
			$logs        = array_slice( array_reverse( $all_logs ), 0, $per_page );
			$total       = count( $all_logs );
			$total_pages = 1;
			$stats       = array(
				'total_sent'       => 0,
				'total_failed'     => 0,
				'total_blocked'    => 0,
				'total_suspicious' => 0,
			);
		}//end if
		?>
		<div class="wrap apollo-email-hub">
			<h1>📊 Logs de Email - Security Dashboard</h1>
			
			<!-- Stats Cards -->
			<div class="logs-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;margin:20px 0;">
				<div class="stat-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;border-left:4px solid #46b450;">
					<div style="font-size:28px;font-weight:700;color:#46b450;"><?php echo esc_html( $stats['total_sent'] ); ?></div>
					<div style="color:#666;">✅ Enviados Hoje</div>
				</div>
				<div class="stat-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;border-left:4px solid #dc3232;">
					<div style="font-size:28px;font-weight:700;color:#dc3232;"><?php echo esc_html( $stats['total_failed'] ); ?></div>
					<div style="color:#666;">❌ Falhas Hoje</div>
				</div>
				<div class="stat-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;border-left:4px solid #f0ad4e;">
					<div style="font-size:28px;font-weight:700;color:#f0ad4e;"><?php echo esc_html( $stats['total_blocked'] ?? 0 ); ?></div>
					<div style="color:#666;">🚫 Bloqueados</div>
				</div>
				<div class="stat-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;border-left:4px solid #9b59b6;">
					<div style="font-size:28px;font-weight:700;color:#9b59b6;"><?php echo esc_html( $stats['total_suspicious'] ?? 0 ); ?></div>
					<div style="color:#666;">⚠️ Suspeitos</div>
				</div>
			</div>

			<!-- Filters -->
			<div class="logs-filters" style="background:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
				<form method="get" style="display:flex;gap:15px;flex-wrap:wrap;align-items:center;">
					<input type="hidden" name="page" value="apollo-email-logs">
					
					<label style="display:flex;align-items:center;gap:5px;">
						<span style="color:#666;">Tipo:</span>
						<select name="type" onchange="this.form.submit()">
							<option value="">Todos</option>
							<option value="sent" <?php selected( $filter_type, 'sent' ); ?>>✅ Enviados</option>
							<option value="failed" <?php selected( $filter_type, 'failed' ); ?>>❌ Falhas</option>
							<option value="blocked" <?php selected( $filter_type, 'blocked' ); ?>>🚫 Bloqueados</option>
							<option value="suspicious" <?php selected( $filter_type, 'suspicious' ); ?>>⚠️ Suspeitos</option>
							<option value="rate_limited" <?php selected( $filter_type, 'rate_limited' ); ?>>⏱️ Rate Limited</option>
						</select>
					</label>
					
					<label style="display:flex;align-items:center;gap:5px;">
						<span style="color:#666;">Severidade:</span>
						<select name="severity" onchange="this.form.submit()">
							<option value="">Todas</option>
							<option value="info" <?php selected( $filter_severity, 'info' ); ?>>ℹ️ Info</option>
							<option value="warning" <?php selected( $filter_severity, 'warning' ); ?>>⚠️ Warning</option>
							<option value="error" <?php selected( $filter_severity, 'error' ); ?>>❌ Error</option>
							<option value="critical" <?php selected( $filter_severity, 'critical' ); ?>>🚨 Critical</option>
						</select>
					</label>

					<?php if ( $filter_type || $filter_severity || $filter_template ) : ?>
						<a href="<?php echo admin_url( 'admin.php?page=apollo-email-logs' ); ?>" class="button">Limpar Filtros</a>
					<?php endif; ?>

					<span style="margin-left:auto;color:#888;">
						Total: <strong><?php echo esc_html( $total ); ?></strong> registros
					</span>
				</form>
			</div>

			<!-- Logs Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:150px;">Data/Hora</th>
						<th style="width:80px;">Tipo</th>
						<th style="width:80px;">Severidade</th>
						<th style="width:200px;">Destinatário</th>
						<th style="width:120px;">Template</th>
						<th>Assunto</th>
						<th style="width:120px;">IP</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="7" style="text-align:center;padding:30px;">
							<span style="font-size:48px;">📭</span><br><br>
							Nenhum log encontrado com os filtros atuais.
						</td></tr>
						<?php
						else :
							foreach ( $logs as $log ) :
								$type_icons      = array(
									'sent'             => '✅',
									'failed'           => '❌',
									'blocked'          => '🚫',
									'suspicious'       => '⚠️',
									'rate_limited'     => '⏱️',
									'template_updated' => '📝',
									'test_sent'        => '🧪',
								);
								$severity_colors = array(
									'info'     => '#0073aa',
									'warning'  => '#f0ad4e',
									'error'    => '#dc3232',
									'critical' => '#8b0000',
								);
								$type            = $log['type'] ?? 'sent';
								$severity        = $log['severity'] ?? 'info';
								?>
						<tr>
							<td>
								<span title="<?php echo esc_attr( $log['created_at'] ?? '' ); ?>">
									<?php echo esc_html( isset( $log['created_at'] ) ? date_i18n( 'd/m/Y H:i', strtotime( $log['created_at'] ) ) : date_i18n( 'd/m/Y H:i', $log['timestamp'] ?? time() ) ); ?>
								</span>
							</td>
							<td>
								<span title="<?php echo esc_attr( ucfirst( $type ) ); ?>">
									<?php echo $type_icons[ $type ] ?? '📧'; ?>
								</span>
							</td>
							<td>
								<span style="color:<?php echo esc_attr( $severity_colors[ $severity ] ?? '#666' ); ?>;font-weight:600;">
									<?php echo esc_html( ucfirst( $severity ) ); ?>
								</span>
							</td>
							<td>
								<code style="font-size:12px;"><?php echo esc_html( $log['recipient_email'] ?? $log['to'] ?? '' ); ?></code>
							</td>
							<td>
								<?php if ( ! empty( $log['template_key'] ?? $log['template'] ) ) : ?>
									<code style="font-size:11px;background:#f0f0f1;padding:2px 6px;border-radius:3px;">
										<?php echo esc_html( $log['template_key'] ?? $log['template'] ); ?>
									</code>
								<?php else : ?>
									<span style="color:#999;">-</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( substr( $log['subject'] ?? '', 0, 50 ) ); ?>
								<?php
								if ( strlen( $log['subject'] ?? '' ) > 50 ) :
									?>
									...<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $log['ip_address'] ) ) : ?>
									<code style="font-size:11px;"><?php echo esc_html( $log['ip_address'] ); ?></code>
								<?php else : ?>
									<span style="color:#999;">-</span>
								<?php endif; ?>
							</td>
						</tr>
								<?php
							endforeach;
		endif;
						?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo esc_html( $total ); ?> itens</span>
						<span class="pagination-links">
							<?php if ( $page > 1 ) : ?>
								<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>">‹</a>
							<?php endif; ?>
							<span class="paging-input">
								<span class="current-page"><?php echo esc_html( $page ); ?></span>
								de
								<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
							</span>
							<?php if ( $page < $total_pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>">›</a>
							<?php endif; ?>
						</span>
					</div>
				</div>
			<?php endif; ?>

			<!-- Security Notice -->
			<div class="security-notice" style="margin-top:30px;padding:20px;background:#f0f6fc;border-left:4px solid #0073aa;border-radius:4px;">
				<h3 style="margin:0 0 10px;">🔒 Segurança do Sistema de Emails</h3>
				<ul style="margin:0;padding-left:20px;">
					<li><strong>Rate Limiting:</strong> Máximo 50 emails/hora por usuário</li>
					<li><strong>Detecção de Anomalias:</strong> Alertas para atividade suspeita</li>
					<li><strong>Logs Completos:</strong> IP, User Agent, timestamps para auditoria</li>
					<li><strong>Retenção:</strong> Logs mantidos por 90 dias (máx. 10.000 registros)</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Save template
	 */
	public static function ajaxSaveSettings(): void {
		check_ajax_referer( 'apollo_email_hub', 'apollo_email_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permissão negada' );
		}

		$template_key = sanitize_key( $_POST['template_key'] ?? '' );
		$subject      = sanitize_text_field( $_POST['subject'] ?? '' );
		$body         = wp_kses_post( $_POST['body'] ?? '' );

		if ( empty( $template_key ) ) {
			wp_send_json_error( 'Template inválido' );
		}

		$templates                  = get_option( self::TEMPLATES_KEY, array() );
		$templates[ $template_key ] = array(
			'subject'    => $subject,
			'body'       => $body,
			'updated_at' => current_time( 'mysql' ),
			'updated_by' => get_current_user_id(),
		);

		update_option( self::TEMPLATES_KEY, $templates );
		wp_send_json_success( 'Template salvo com sucesso!' );
	}

	/**
	 * AJAX: Send test email
	 */
	public static function ajaxSendTestEmail(): void {
		check_ajax_referer( 'apollo_email_hub', 'apollo_email_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permissão negada' );
		}

		$to           = sanitize_email( $_POST['test_email'] ?? '' );
		$subject      = sanitize_text_field( $_POST['subject'] ?? '' );
		$body         = wp_kses_post( $_POST['body'] ?? '' );
		$template_key = sanitize_key( $_POST['template_key'] ?? '' );

		if ( ! is_email( $to ) ) {
			wp_send_json_error( 'Email inválido' );
		}

		// Replace placeholders with test data
		$body    = self::replacePlaceholders( $body, self::getTestData() );
		$subject = self::replacePlaceholders( $subject, self::getTestData() );

		// Wrap in HTML template
		$html = self::wrapInHtmlTemplate( $body, $subject );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, '[TESTE] ' . $subject, $html, $headers );

		// Log
		self::logEmail( $to, $template_key, $subject, $sent ? 'sent' : 'failed' );

		if ( $sent ) {
			wp_send_json_success( 'Email de teste enviado para ' . $to );
		} else {
			wp_send_json_error( 'Falha ao enviar email. Verifique configurações SMTP.' );
		}
	}

	/**
	 * AJAX: Preview email
	 */
	public static function ajaxPreviewEmail(): void {
		check_ajax_referer( 'apollo_email_hub', 'apollo_email_nonce' );

		$subject = sanitize_text_field( $_POST['subject'] ?? '' );
		$body    = wp_kses_post( $_POST['body'] ?? '' );

		// Replace with test data
		$body    = self::replacePlaceholders( $body, self::getTestData() );
		$subject = self::replacePlaceholders( $subject, self::getTestData() );

		$html = self::wrapInHtmlTemplate( $body, $subject );

		wp_send_json_success(
			array(
				'subject' => $subject,
				'html'    => $html,
			)
		);
	}

	/**
	 * Replace placeholders in text
	 */
	public static function replacePlaceholders( string $text, array $data ): string {
		foreach ( $data as $key => $value ) {
			$text = str_replace( $key, $value, $text );
		}

		return $text;
	}

	/**
	 * Get test data for preview
	 */
	private static function getTestData(): array {
		$user = wp_get_current_user();

		return array(
			'[user-name]'                => $user->user_login,
			'[display-name]'             => $user->display_name,
			'[user-email]'               => $user->user_email,
			'[user-id]'                  => (string) $user->ID,
			'[user-registered]'          => date_i18n( get_option( 'date_format' ) ),
			'[first-name]'               => $user->first_name ?: 'João',
			'[last-name]'                => $user->last_name ?: 'Silva',
			'[cultura-identities]'       => 'Clubber, DJ Profissional',
			'[membership-status]'        => 'Aprovado',
			'[membership-requested]'     => 'DJ Profissional',
			'[membership-approved-date]' => date_i18n( get_option( 'date_format' ) ),
			'[fav-sounds]'               => 'House, Techno, Drum & Bass',
			'[fav-sounds-count]'         => '3',
			'[event-name]'               => 'Sunset Sessions Vol. 5',
			'[event-date]'               => 'Sábado, 25 de Março',
			'[event-time]'               => '22:00',
			'[event-venue]'              => 'Club Rio',
			'[event-address]'            => 'Rua das Flores, 123 - Lapa, RJ',
			'[event-url]'                => home_url( '/evento/sunset-sessions' ),
			'[event-djs]'                => 'DJ Marky, Patife, XRS',
			'[site-name]'                => get_bloginfo( 'name' ),
			'[site-url]'                 => home_url(),
			'[login-url]'                => wp_login_url(),
			'[profile-url]'              => home_url( '/perfil/' . $user->user_nicename ),
			'[dashboard-url]'            => home_url( '/minha-conta' ),
			'[current-year]'             => date( 'Y' ),
			'[admin-name]'               => 'Admin Apollo',
			'[admin-message]'            => 'Mensagem de teste do administrador.',
			'[rejection-reason]'         => 'Documentação incompleta.',
		);
	}

	/**
	 * Wrap body in HTML email template
	 */
	private static function wrapInHtmlTemplate( string $body, string $subject ): string {
		$settings      = get_option( self::OPTION_KEY, array() );
		$primary_color = $settings['primary_color'] ?? '#00d4ff';
		$logo_url      = $settings['logo_url'] ?? '';
		$footer_text   = $settings['footer_text'] ?? '© ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' );
		$footer_text   = self::replacePlaceholders( $footer_text, self::getTestData() );

		return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html( $subject ) . '</title>
</head>
<body style="margin:0;padding:0;background:#1a1a2e;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a2e;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#16213e;border-radius:12px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,' . esc_attr( $primary_color ) . ' 0%,#0066cc 100%);padding:30px;text-align:center;">
                            ' . ( $logo_url ? '<img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-height:60px;margin-bottom:15px;">' : '' ) . '
                            <h1 style="margin:0;color:#fff;font-size:24px;">' . esc_html( get_bloginfo( 'name' ) ) . '</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 30px;color:#e0e0e0;font-size:16px;line-height:1.6;">
                            ' . $body . '
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:rgba(0,0,0,0.3);padding:20px 30px;text-align:center;color:#888;font-size:12px;">
                            ' . wp_kses_post( $footer_text ) . '
                            <br><br>
                            <a href="' . esc_url( home_url() ) . '" style="color:' . esc_attr( $primary_color ) . ';text-decoration:none;">Visitar site</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
	}

	/**
	 * Log email
	 */
	private static function logEmail( string $to, string $template, string $subject, string $status ): void {
		$logs   = get_option( 'apollo_email_logs', array() );
		$logs[] = array(
			'to'        => $to,
			'template'  => $template,
			'subject'   => $subject,
			'status'    => $status,
			'timestamp' => time(),
		);

		// Keep only last 1000
		if ( count( $logs ) > 1000 ) {
			$logs = array_slice( $logs, -1000 );
		}

		update_option( 'apollo_email_logs', $logs );
	}

	/**
	 * Helper: Count total placeholders
	 */
	private static function countPlaceholders(): int {
		$count = 0;
		foreach ( self::$placeholders as $cat ) {
			$count += count( $cat['items'] );
		}

		return $count;
	}

	/**
	 * Helper: Get placeholder info
	 */
	private static function getPlaceholderInfo( string $placeholder ): array {
		foreach ( self::$placeholders as $cat ) {
			if ( isset( $cat['items'][ $placeholder ] ) ) {
				return $cat['items'][ $placeholder ];
			}
		}

		return array();
	}

	/**
	 * Helper: Check if plugin is active
	 */
	private static function isPluginActive( string $plugin_slug ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_slug . '/' . $plugin_slug . '.php' );
	}

	/**
	 * Get admin styles
	 */
	private static function getAdminStyles(): string {
		return '
        .apollo-email-hub { max-width: 1400px; }
        .apollo-email-hub h1 { display: flex; align-items: center; }
        
        /* Stats Grid */
        .apollo-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .apollo-stat-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .apollo-stat-card .stat-icon { font-size: 32px; margin-bottom: 10px; }
        .apollo-stat-card .stat-value { font-size: 36px; font-weight: 700; color: #1d2327; }
        .apollo-stat-card .stat-label { color: #666; margin-top: 5px; }
        .apollo-stat-success .stat-value { color: #46b450; }
        .apollo-stat-warning .stat-value { color: #f0ad4e; }
        .apollo-stat-info .stat-value { color: #00a0d2; }

        /* Integration Status */
        .apollo-integration-status { background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 30px; }
        .integration-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
        .integration-card { display: flex; align-items: center; gap: 15px; padding: 15px; background: #f9f9f9; border-radius: 6px; border-left: 4px solid #ccc; }
        .integration-card.active { border-left-color: #46b450; }
        .integration-card.inactive { border-left-color: #f0ad4e; }
        .integration-icon { font-size: 28px; }
        .integration-info { flex: 1; }
        .integration-info strong { display: block; }
        .integration-status { font-size: 12px; }
        .template-count { color: #888; font-size: 12px; display: block; }

        /* Quick Actions */
        .apollo-quick-actions { background: #fff; padding: 25px; border-radius: 8px; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px; }
        .action-card { display: flex; flex-direction: column; align-items: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: #fff; text-decoration: none; transition: transform 0.2s; }
        .action-card:hover { transform: translateY(-3px); color: #fff; }
        .action-icon { font-size: 28px; margin-bottom: 8px; }

        /* Templates Layout */
        .apollo-templates-layout { display: grid; grid-template-columns: 280px 1fr; gap: 25px; margin-top: 20px; }
        .templates-sidebar { background: #fff; border-radius: 8px; padding: 0; overflow: hidden; }
        .template-category { border-bottom: 1px solid #eee; }
        .category-header { padding: 15px 20px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; background: #f9f9f9; }
        .category-count { background: #ddd; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .category-templates { padding: 5px 0; }
        .template-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; text-decoration: none; color: #333; transition: background 0.2s; }
        .template-item:hover { background: #f0f0f1; }
        .template-item.active { background: #0073aa; color: #fff; }
        .template-item.configured .template-status { color: #46b450; }
        .template-icon { font-size: 18px; }
        .template-name { flex: 1; }
        .template-status { font-size: 12px; color: #ccc; }

        /* Editor */
        .templates-editor { background: #fff; border-radius: 8px; padding: 30px; }
        .editor-header h2 { margin-top: 0; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .trigger-badge { font-size: 12px; background: #f0f0f1; padding: 4px 10px; border-radius: 4px; font-weight: normal; }

        /* Form */
        .form-field { margin-bottom: 25px; }
        .form-field label { display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 8px; }
        .subject-input { font-size: 16px !important; padding: 12px !important; }

        /* Required Placeholders */
        .required-placeholders { background: #f0f6fc; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .placeholder-chip { display: inline-flex; align-items: center; gap: 5px; background: #fff; padding: 5px 10px; border-radius: 4px; margin: 3px; font-family: monospace; font-size: 13px; cursor: pointer; border: 1px solid #c3c4c7; }
        .placeholder-chip:hover { background: #0073aa; color: #fff; border-color: #0073aa; }
        .copy-btn { opacity: 0.6; }

        /* Editor Toolbar */
        .editor-toolbar { display: flex; gap: 5px; margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px; }
        .toolbar-btn { padding: 5px 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
        .toolbar-btn:hover { background: #f0f0f1; }
        .toolbar-separator { width: 1px; background: #ddd; margin: 0 10px; }
        .insert-placeholder-btn { background: #0073aa; color: #fff; border-color: #0073aa; }

        /* Placeholders Reference */
        .placeholders-reference { margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee; }
        .placeholder-group { margin-bottom: 15px; border: 1px solid #eee; border-radius: 6px; }
        .placeholder-group-header { padding: 12px 15px; background: #f9f9f9; font-weight: 600; cursor: pointer; }
        .placeholder-group-items { padding: 10px; display: none; }
        .placeholder-group.open .placeholder-group-items { display: block; }
        .placeholder-item { display: flex; align-items: center; gap: 10px; padding: 8px; border-bottom: 1px solid #f0f0f1; }
        .placeholder-item:last-child { border-bottom: none; }
        .ph-code { background: #f0f0f1; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .ph-label { flex: 1; color: #666; font-size: 13px; }
        .ph-tooltip { cursor: help; }
        .ph-insert { padding: 3px 8px; font-size: 12px; cursor: pointer; background: #0073aa; color: #fff; border: none; border-radius: 3px; }

        /* Test Email */
        .test-email-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 30px; }
        .test-email-row { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        #test-email-result { margin-top: 10px; padding: 10px; border-radius: 4px; display: none; }
        #test-email-result.success { display: block; background: #d4edda; color: #155724; }
        #test-email-result.error { display: block; background: #f8d7da; color: #721c24; }

        /* Form Actions */
        .form-actions { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; display: flex; gap: 15px; align-items: center; }
        #save-status { margin-left: auto; }

        /* Modal */
        .apollo-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; display: flex; align-items: center; justify-content: center; }
        .modal-content { background: #fff; border-radius: 8px; width: 90%; max-width: 800px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { padding: 15px 20px; background: #f9f9f9; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
        .modal-body { flex: 1; overflow: auto; padding: 0; }
        .preview-subject { padding: 15px 20px; background: #f0f6fc; font-weight: 600; }
        .preview-frame { width: 100%; height: 500px; border: none; }

        /* Empty State */
        .no-template-selected { display: flex; align-items: center; justify-content: center; min-height: 400px; }
        .empty-state { text-align: center; color: #888; }
        .empty-icon { font-size: 64px; display: block; margin-bottom: 20px; }

        /* Tooltips */
        .tooltip { cursor: help; position: relative; }
        .tooltip:hover::after { content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #333; color: #fff; padding: 8px 12px; border-radius: 4px; font-size: 12px; white-space: nowrap; z-index: 1000; font-weight: normal; }

        /* Toggle Switch */
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 26px; transition: 0.3s; }
        .toggle-slider::before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: #46b450; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(24px); }

        /* Settings Sections */
        .settings-sections { display: grid; gap: 30px; }
        .settings-section { background: #fff; padding: 25px; border-radius: 8px; }
        .settings-section h2 { margin-top: 0; }

        /* Placeholders Full Reference */
        .placeholder-category-card { background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .placeholder-category-card h2 { margin-top: 0; }
        .copyable-placeholder { cursor: pointer; }
        .copyable-placeholder:hover { background: #0073aa; color: #fff; }
        .source-ref { color: #888; font-family: monospace; }

        /* Status Badge */
        .status-badge { padding: 3px 8px; border-radius: 3px; }
        .status-sent { background: #d4edda; }
        .status-failed { background: #f8d7da; }
        ';
	}

	/**
	 * Get admin scripts
	 */
	private static function getAdminScripts(): string {
		return '
        jQuery(document).ready(function($) {
            // Category accordion
            $(".category-header").on("click", function() {
                $(this).parent().toggleClass("open");
            });

            // Placeholder group accordion
            $(".placeholder-group-header").on("click", function() {
                $(this).parent().toggleClass("open");
            });

            // Copy placeholder
            $(".placeholder-chip, .copyable-placeholder").on("click", function() {
                var ph = $(this).data("placeholder") || $(this).text();
                navigator.clipboard.writeText(ph);
                var $this = $(this);
                $this.css("background", "#46b450");
                setTimeout(function() { $this.css("background", ""); }, 500);
            });

            // Insert placeholder into editor
            $(".ph-insert").on("click", function() {
                var ph = $(this).closest(".placeholder-item").data("placeholder");
                var textarea = document.getElementById("template-body");
                if (textarea) {
                    var pos = textarea.selectionStart;
                    var val = textarea.value;
                    textarea.value = val.substring(0, pos) + ph + val.substring(pos);
                    textarea.focus();
                    textarea.setSelectionRange(pos + ph.length, pos + ph.length);
                }
            });

            // Save template
            $("#template-form").on("submit", function(e) {
                e.preventDefault();
                var $btn = $(this).find("button[type=submit]");
                var $status = $("#save-status");
                $btn.prop("disabled", true).text("Salvando...");

                $.post(ajaxurl, {
                    action: "apollo_email_hub_save",
                    apollo_email_nonce: $("#apollo_email_nonce").val(),
                    template_key: $("input[name=template_key]").val(),
                    subject: $("input[name=subject]").val(),
                    body: $("#template-body").val()
                }, function(r) {
                    $btn.prop("disabled", false).html("💾 Salvar Template");
                    if (r.success) {
                        $status.html("<span style=color:#46b450>✅ " + r.data + "</span>");
                    } else {
                        $status.html("<span style=color:#dc3232>❌ " + r.data + "</span>");
                    }
                    setTimeout(function() { $status.html(""); }, 3000);
                });
            });

            // Test email
            $("#send-test-btn").on("click", function() {
                var $btn = $(this);
                var $result = $("#test-email-result");
                $btn.prop("disabled", true).text("Enviando...");
                $result.removeClass("success error").hide();

                $.post(ajaxurl, {
                    action: "apollo_email_hub_test",
                    apollo_email_nonce: $("#apollo_email_nonce").val(),
                    template_key: $("input[name=template_key]").val(),
                    test_email: $("input[name=test_email]").val(),
                    subject: $("input[name=subject]").val(),
                    body: $("#template-body").val()
                }, function(r) {
                    $btn.prop("disabled", false).text("📤 Enviar Teste");
                    if (r.success) {
                        $result.addClass("success").text(r.data).show();
                    } else {
                        $result.addClass("error").text(r.data).show();
                    }
                });
            });

            // Preview
            $("#preview-btn").on("click", function() {
                $.post(ajaxurl, {
                    action: "apollo_email_hub_preview",
                    apollo_email_nonce: $("#apollo_email_nonce").val(),
                    subject: $("input[name=subject]").val(),
                    body: $("#template-body").val()
                }, function(r) {
                    if (r.success) {
                        $(".preview-subject").text("Assunto: " + r.data.subject);
                        var frame = document.getElementById("preview-frame");
                        frame.srcdoc = r.data.html;
                        $("#preview-modal").show();
                    }
                });
            });

            // Close modal
            $(".modal-close, .apollo-modal").on("click", function(e) {
                if (e.target === this) {
                    $("#preview-modal").hide();
                }
            });

            // Reset default
            $("#reset-default-btn").on("click", function() {
                if (confirm("Restaurar template para o padrão? Suas alterações serão perdidas.")) {
                    location.reload();
                }
            });
        });
        ';
	}

	// =====================================================
	// DEFAULT TEMPLATES
	// =====================================================

	private static function getDefaultWelcomeTemplate(): string {
		return '<h2 style="color:#00d4ff;margin-top:0;">Olá, [display-name]! 👋</h2>

<p>Bem-vindo(a) à <strong>Cultura::Rio</strong>! Sua conta foi criada com sucesso.</p>

<p>Você agora faz parte de uma comunidade vibrante de amantes da música eletrônica carioca.</p>

<div style="background:rgba(0,212,255,0.1);padding:20px;border-radius:8px;margin:20px 0;">
    <strong>Próximos passos:</strong>
    <ul style="margin:10px 0 0;padding-left:20px;">
        <li>Complete seu perfil</li>
        <li>Explore os eventos da semana</li>
        <li>Conecte-se com outros clubbers</li>
    </ul>
</div>

<p style="text-align:center;margin-top:30px;">
    <a href="[login-url]" style="display:inline-block;background:linear-gradient(135deg,#00d4ff 0%,#0066cc 100%);color:#fff;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:600;">
        Acessar minha conta →
    </a>
</p>

<p style="color:#888;font-size:14px;">Nos vemos na pista! 🎉</p>';
	}

	private static function getDefaultApprovedTemplate(): string {
		return '<h2 style="color:#46b450;margin-top:0;">🎉 Parabéns, [display-name]!</h2>

<p>Sua solicitação de membership foi <strong style="color:#46b450;">APROVADA</strong>!</p>

<div style="background:rgba(70,180,80,0.1);padding:20px;border-radius:8px;margin:20px 0;">
    <strong>Suas identidades na Cultura::Rio:</strong>
    <p style="margin:10px 0 0;font-size:18px;">[cultura-identities]</p>
</div>

<p>Agora você tem acesso a recursos exclusivos:</p>
<ul>
    <li>✅ Badge especial no perfil</li>
    <li>✅ Acesso a funcionalidades premium</li>
    <li>✅ Prioridade em eventos</li>
</ul>

<p style="text-align:center;margin-top:30px;">
    <a href="[profile-url]" style="display:inline-block;background:linear-gradient(135deg,#46b450 0%,#228b22 100%);color:#fff;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:600;">
        Ver meu perfil →
    </a>
</p>';
	}

	private static function getDefaultRejectedTemplate(): string {
		return '<h2 style="color:#dc3232;margin-top:0;">Olá, [display-name]</h2>

<p>Infelizmente, sua solicitação de membership não foi aprovada neste momento.</p>

<div style="background:rgba(220,50,50,0.1);padding:20px;border-radius:8px;margin:20px 0;">
    <strong>Motivo:</strong>
    <p style="margin:10px 0 0;">[rejection-reason]</p>
</div>

<p>Isso não significa que você não pode tentar novamente! Revise os requisitos e envie uma nova solicitação quando estiver pronto.</p>

<p>Se tiver dúvidas, entre em contato conosco.</p>

<p style="color:#888;">Continuamos juntos na cena! 🎵</p>';
	}

	private static function getDefaultPendingTemplate(): string {
		return '<h2 style="color:#f0ad4e;margin-top:0;">Recebemos sua solicitação! ⏳</h2>

<p>Olá, [display-name]!</p>

<p>Sua solicitação de membership foi recebida e está em análise.</p>

<div style="background:rgba(240,173,78,0.1);padding:20px;border-radius:8px;margin:20px 0;">
    <strong>Identidades solicitadas:</strong>
    <p style="margin:10px 0 0;">[membership-requested]</p>
</div>

<p>Nossa equipe vai analisar sua solicitação em breve. Você receberá um email assim que tivermos uma resposta.</p>

<p style="color:#888;">Obrigado pela paciência! 🙏</p>';
	}

	private static function getDefaultJourneyDJTemplate(): string {
		return '<h2 style="color:#00d4ff;margin-top:0;">🎧 Sua jornada como DJ continua!</h2>

<p>E aí, [display-name]!</p>

<p>Lembra quando você estava tentando ser DJ? Agora estamos orgulhosos de fazer parte dessa jornada com você!</p>

<p>Desejamos todo o sucesso do mundo. Continue evoluindo! 🚀</p>

<p style="color:#888;font-style:italic;">"The journey is the destination."</p>';
	}

	private static function getDefaultJourneyProducerTemplate(): string {
		return '<h2 style="color:#00d4ff;margin-top:0;">🎛️ Producer em evolução!</h2>

<p>Olá, [display-name]!</p>

<p>Sua jornada como Producer está só começando. Continue criando, produzindo e fazendo acontecer!</p>

<p>A cena carioca precisa de pessoas como você. 🎉</p>';
	}

	private static function getDefaultPasswordResetTemplate(): string {
		return '<h2 style="margin-top:0;">🔑 Redefinição de Senha</h2>

<p>Olá, [user-name]!</p>

<p>Recebemos uma solicitação para redefinir a senha da sua conta em [site-name].</p>

<p>Se você não fez essa solicitação, ignore este email.</p>

<p style="color:#888;font-size:14px;">Por segurança, este link expira em 24 horas.</p>';
	}

	private static function getDefaultSecurityAlertTemplate(): string {
		return '<h2 style="color:#dc3232;margin-top:0;">⚠️ Alerta de Segurança</h2>

<p>Olá, [user-name]!</p>

<p>Detectamos uma atividade incomum na sua conta em [site-name].</p>

<p>Se foi você, pode ignorar este email. Caso contrário, recomendamos alterar sua senha imediatamente.</p>';
	}

	private static function getDefaultModerationTemplate(): string {
		return '<h2 style="margin-top:0;">⚖️ Aviso sobre seu conteúdo</h2>

<p>Olá, [display-name]!</p>

<p>Um conteúdo seu foi revisado pela nossa equipe de moderação.</p>

<div style="background:rgba(0,0,0,0.1);padding:20px;border-radius:8px;margin:20px 0;">
    <strong>Mensagem da moderação:</strong>
    <p style="margin:10px 0 0;">[admin-message]</p>
</div>

<p>Em caso de dúvidas, entre em contato conosco.</p>';
	}

	private static function getDefaultEventReminderTemplate(): string {
		return '<h2 style="color:#00d4ff;margin-top:0;">⏰ Lembrete: [event-name]</h2>

<p>E aí, [display-name]!</p>

<p>Não esqueça: amanhã tem <strong>[event-name]</strong>!</p>

<div style="background:rgba(0,212,255,0.1);padding:20px;border-radius:8px;margin:20px 0;">
    <p style="margin:0;">📅 <strong>[event-date]</strong> às <strong>[event-time]</strong></p>
    <p style="margin:10px 0 0;">📍 [event-venue]</p>
    <p style="margin:5px 0 0;color:#888;font-size:14px;">[event-address]</p>
</div>

<p>Line-up: [event-djs]</p>

<p style="text-align:center;margin-top:30px;">
    <a href="[event-url]" style="display:inline-block;background:linear-gradient(135deg,#00d4ff 0%,#0066cc 100%);color:#fff;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:600;">
        Ver detalhes do evento →
    </a>
</p>';
	}

	private static function getDefaultEventBookmarkTemplate(): string {
		return '<h2 style="margin-top:0;">🔖 Evento salvo!</h2>

<p>Olá, [display-name]!</p>

<p>Você salvou o evento <strong>[event-name]</strong> nos seus favoritos.</p>

<p>Vamos te avisar quando o evento estiver chegando!</p>

<p style="text-align:center;">
    <a href="[event-url]" style="color:#00d4ff;">Ver evento →</a>
</p>';
	}

	private static function getDefaultEventUpdateTemplate(): string {
		return '<h2 style="color:#f0ad4e;margin-top:0;">📢 Atualização: [event-name]</h2>

<p>Olá, [display-name]!</p>

<p>O evento <strong>[event-name]</strong> que você salvou foi atualizado.</p>

<p>Confira as novidades:</p>

<p style="text-align:center;">
    <a href="[event-url]" style="display:inline-block;background:#f0ad4e;color:#000;padding:12px 25px;border-radius:6px;text-decoration:none;font-weight:600;">
        Ver atualizações →
    </a>
</p>';
	}

	private static function getDefaultEventCancelledTemplate(): string {
		return '<h2 style="color:#dc3232;margin-top:0;">🚫 Evento Cancelado</h2>

<p>Olá, [display-name]!</p>

<p>Infelizmente, o evento <strong>[event-name]</strong> foi cancelado.</p>

<p>Sentimos muito por isso. Fique de olho em novos eventos!</p>

<p style="text-align:center;">
    <a href="[site-url]" style="color:#00d4ff;">Ver outros eventos →</a>
</p>';
	}

	private static function getDefaultWeeklyDigestTemplate(): string {
		return '<h2 style="color:#00d4ff;margin-top:0;">🎉 Eventos desta semana!</h2>

<p>E aí, [display-name]!</p>

<p>Separamos os melhores eventos da semana com base nos seus gêneros favoritos:</p>

<div style="background:rgba(0,212,255,0.1);padding:15px;border-radius:8px;margin:20px 0;">
    <strong>Seus sons:</strong> [fav-sounds]
</div>

<p>Confira a agenda completa no site!</p>

<p style="text-align:center;margin-top:30px;">
    <a href="[site-url]/agenda" style="display:inline-block;background:linear-gradient(135deg,#00d4ff 0%,#0066cc 100%);color:#fff;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:600;">
        Ver agenda completa →
    </a>
</p>

<p style="color:#888;font-size:12px;text-align:center;">
    <a href="[dashboard-url]" style="color:#888;">Gerenciar preferências de email</a>
</p>';
	}
}

// Initialize
add_action( 'plugins_loaded', array( EmailHubAdmin::class, 'init' ), 20 );
