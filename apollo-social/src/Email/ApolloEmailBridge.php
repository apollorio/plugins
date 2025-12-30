<?php
declare(strict_types=1);

namespace Apollo\Email;

use Apollo\Modules\Registration\CulturaRioIdentity;

/**
 * Apollo Email Bridge
 *
 * Integrates apollo-email-newsletter and apollo-email-templates
 * plugins with Apollo ecosystem for membership notifications and journey messages.
 *
 * Uses existing plugins instead of reinventing the wheel:
 * - Newsletter plugin: lists, campaigns, autoresponders, queue
 * - Email Templates: visual template + test-email UX
 *
 * @package Apollo_Social
 * @since 1.2.0
 */
class ApolloEmailBridge {

	/**
	 * Singleton instance
	 */
	private static ?self $instance = null;

	/**
	 * Get instance
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the bridge
	 */
	public function init(): void {
		// Hook into Apollo membership events
		add_action( 'apollo_membership_approved', array( $this, 'onMembershipApproved' ), 10, 3 );
		add_action( 'apollo_membership_rejected', array( $this, 'onMembershipRejected' ), 10, 3 );
		add_action( 'apollo_user_registration_complete', array( $this, 'onRegistrationComplete' ), 10, 2 );

		// Add Apollo placeholders to Email Templates plugin
		add_filter( 'emailtpl/placeholders', array( $this, 'addApolloPlaceholders' ), 10, 2 );

		// Hook into Newsletter plugin if active
		if ( class_exists( 'Newsletter' ) ) {
			add_action( 'newsletter_user_confirmed', array( $this, 'syncNewsletterUser' ) );
		}

		// Admin menu for email settings
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );

		// AJAX handlers - DELEGATED TO APOLLO-CORE to avoid duplicity
		// apollo-core/includes/class-apollo-email-integration.php handles:
		// - wp_ajax_apollo_send_test_email
		// - wp_ajax_apollo_save_email_template
	}

	/**
	 * On membership approved - send email notification
	 */
	public function onMembershipApproved( int $user_id, array $memberships, int $admin_id ): void {
		$user  = get_userdata( $user_id );
		$admin = get_userdata( $admin_id );

		if ( ! $user ) {
			return;
		}

		$template = $this->getTemplate( 'membership_approved' );
		$subject  = $this->replacePlaceholders(
			$template['subject'],
			$user_id,
			array(
				'admin_name' => $admin ? $admin->display_name : 'Apollo Team',
			)
		);
		$body     = $this->replacePlaceholders(
			$template['body'],
			$user_id,
			array(
				'admin_name' => $admin ? $admin->display_name : 'Apollo Team',
			)
		);

		$this->sendEmail( $user->user_email, $subject, $body, 'membership_approved' );
	}

	/**
	 * On membership rejected - send email notification
	 */
	public function onMembershipRejected( int $user_id, int $admin_id, string $reason ): void {
		$user  = get_userdata( $user_id );
		$admin = get_userdata( $admin_id );

		if ( ! $user ) {
			return;
		}

		$template = $this->getTemplate( 'membership_rejected' );
		$subject  = $this->replacePlaceholders( $template['subject'], $user_id );
		$body     = $this->replacePlaceholders(
			$template['body'],
			$user_id,
			array(
				'admin_name'       => $admin ? $admin->display_name : 'Apollo Team',
				'rejection_reason' => $reason ?: 'Não especificado',
			)
		);

		$this->sendEmail( $user->user_email, $subject, $body, 'membership_rejected' );
	}

	/**
	 * On registration complete - send welcome email
	 */
	public function onRegistrationComplete( int $user_id, array $data ): void {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$template = $this->getTemplate( 'welcome' );
		$subject  = $this->replacePlaceholders( $template['subject'], $user_id );
		$body     = $this->replacePlaceholders( $template['body'], $user_id );

		$this->sendEmail( $user->user_email, $subject, $body, 'welcome' );

		// If user requested memberships beyond clubber, send pending notification
		$identities             = $data['cultura_identity'] ?? array();
		$has_membership_request = count( $identities ) > 1 || ! in_array( 'clubber', $identities, true );

		if ( $has_membership_request ) {
			$template = $this->getTemplate( 'membership_pending' );
			$subject  = $this->replacePlaceholders( $template['subject'], $user_id );
			$body     = $this->replacePlaceholders( $template['body'], $user_id );

			$this->sendEmail( $user->user_email, $subject, $body, 'membership_pending' );
		}
	}

	/**
	 * Add Apollo-specific placeholders to Email Templates plugin
	 */
	public function addApolloPlaceholders( array $placeholders, string $user_email = '' ): array {
		// Try to get user by email
		$user    = get_user_by( 'email', $user_email );
		$user_id = $user ? $user->ID : 0;

		if ( $user_id ) {
			$sounds               = get_user_meta( $user_id, 'apollo_sounds', true ) ?: array();
			$identities           = get_user_meta( $user_id, 'apollo_cultura_identities', true ) ?: array();
			$membership_status    = get_user_meta( $user_id, 'apollo_membership_status', true ) ?: 'none';
			$membership_requested = get_user_meta( $user_id, 'apollo_membership_requested', true ) ?: array();

			$placeholders['%%APOLLO_USER_NAME%%']            = $user->display_name;
			$placeholders['%%APOLLO_FIRST_NAME%%']           = $user->first_name ?: explode( ' ', $user->display_name )[0];
			$placeholders['%%APOLLO_SOUNDS%%']               = is_array( $sounds ) ? implode( ', ', $sounds ) : '';
			$placeholders['%%APOLLO_IDENTITIES%%']           = is_array( $identities ) ? implode( ', ', $identities ) : '';
			$placeholders['%%APOLLO_MEMBERSHIP_STATUS%%']    = ucfirst( $membership_status );
			$placeholders['%%APOLLO_MEMBERSHIP_REQUESTED%%'] = is_array( $membership_requested ) ? implode( ', ', $membership_requested ) : '';
			$placeholders['%%APOLLO_DASHBOARD_URL%%']        = home_url( '/painel' );
		}

		return $placeholders;
	}

	/**
	 * Sync Newsletter subscriber with Apollo user
	 */
	public function syncNewsletterUser( $newsletter_user ): void {
		if ( empty( $newsletter_user->email ) ) {
			return;
		}

		$wp_user = get_user_by( 'email', $newsletter_user->email );
		if ( ! $wp_user ) {
			return;
		}

		// Mark as synced
		update_user_meta( $wp_user->ID, 'apollo_newsletter_synced', true );
		update_user_meta( $wp_user->ID, 'apollo_newsletter_id', $newsletter_user->id );
	}

	/**
	 * Get email template
	 */
	public function getTemplate( string $key ): array {
		$templates = $this->getDefaultTemplates();
		$saved     = get_option( 'apollo_email_templates', array() );

		if ( isset( $saved[ $key ] ) ) {
			return array_merge( $templates[ $key ] ?? array(), $saved[ $key ] );
		}

		return $templates[ $key ] ?? array(
			'subject' => 'Apollo::Rio Notification',
			'body'    => 'Você tem uma nova notificação.',
		);
	}

	/**
	 * Get default templates
	 */
	public function getDefaultTemplates(): array {
		return array(
			'welcome'             => array(
				'name'        => 'Boas-vindas',
				'description' => 'Enviado após registro completo',
				'subject'     => 'Bem-vindo(a) à Cultura::Rio, [user-first-name]! 🎉',
				'body'        => 'Olá [user-name],

Seja muito bem-vindo(a) ao Apollo::Rio!

Você agora faz parte da nossa comunidade cultural digital. Como parte do BETA LAB, você terá acesso exclusivo às funcionalidades que estamos desenvolvendo.

<strong>Seus dados de registro:</strong>
• Gêneros musicais favoritos: [fav-sounds]
• Identidades culturais: [cultura-identities]
• Data de registro: [registration-date]

<a href="[dashboard-url]" style="display: inline-block; background-color: #00d4ff; color: #000; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Acessar Meu Painel</a>

Com carinho,
Equipe Apollo::Rio',
			),
			'membership_pending'  => array(
				'name'        => 'Membership Pendente',
				'description' => 'Enviado quando solicita membership especial',
				'subject'     => '[user-first-name], recebemos sua solicitação! 📋',
				'body'        => 'Olá [user-name],

Recebemos sua solicitação para os seguintes níveis de acesso:
<strong>[membership-requested]</strong>

Nossa equipe irá analisar em breve. Você receberá uma notificação assim que tivermos uma resposta.

Equipe Apollo::Rio',
			),
			'membership_approved' => array(
				'name'        => 'Membership Aprovado',
				'description' => 'Enviado quando admin aprova',
				'subject'     => '🎉 Parabéns [user-first-name]! Seu membership foi APROVADO!',
				'body'        => 'Olá [user-name],

Sua solicitação de membership foi <strong style="color: #5cb85c;">APROVADA</strong>!

Você agora tem acesso a: <strong>[membership-requested]</strong>

Aprovado por: [admin-name]

<a href="[dashboard-url]" style="display: inline-block; background-color: #5cb85c; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Explorar Novas Funcionalidades</a>

Bem-vindo(a) ao próximo nível!
Equipe Apollo::Rio',
			),
			'membership_rejected' => array(
				'name'        => 'Membership Rejeitado',
				'description' => 'Enviado quando admin rejeita',
				'subject'     => '[user-first-name], atualização sobre sua solicitação',
				'body'        => 'Olá [user-name],

Infelizmente, sua solicitação de membership não foi aprovada neste momento.

<strong>Motivo:</strong> [rejection-reason]

Você continua tendo acesso como Clubber. Tente novamente no futuro!

Equipe Apollo::Rio',
			),
			'journey_progress'    => array(
				'name'        => 'Jornada - Progressão',
				'description' => 'Mensagem de progressão na jornada',
				'subject'     => '[user-first-name], sua jornada está evoluindo! 🚀',
				'body'        => 'Olá [user-name],

Temos muito orgulho de fazer parte dessa jornada com você!

Você começou como [original-identity] e agora faz parte oficialmente da cena carioca.

Desejamos tudo de melhor!

Equipe Apollo::Rio',
			),
		);
	}

	/**
	 * Replace placeholders in content
	 */
	public function replacePlaceholders( string $content, int $user_id, array $extra = array() ): string {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return $content;
		}

		$sounds               = get_user_meta( $user_id, 'apollo_sounds', true ) ?: array();
		$identities           = get_user_meta( $user_id, 'apollo_cultura_identities', true ) ?: array();
		$original_identities  = get_user_meta( $user_id, 'apollo_cultura_original_identities', true ) ?: array();
		$membership_status    = get_user_meta( $user_id, 'apollo_membership_status', true ) ?: 'none';
		$membership_requested = get_user_meta( $user_id, 'apollo_membership_requested', true ) ?: array();
		$registration_date    = get_user_meta( $user_id, 'apollo_registration_date', true ) ?: '';

		$replacements = array(
			'[user-name]'            => $user->display_name,
			'[user-email]'           => $user->user_email,
			'[user-first-name]'      => $user->first_name ?: explode( ' ', $user->display_name )[0],
			'[fav-sounds]'           => is_array( $sounds ) ? implode( ', ', $sounds ) : '',
			'[fav-sounds-by-comma]'  => is_array( $sounds ) ? implode( ', ', $sounds ) : '',
			'[cultura-identities]'   => is_array( $identities ) ? implode( ', ', array_map( fn ( $i ) => CulturaRioIdentity::getIdentityLabel( $i ), $identities ) ) : '',
			'[original-identity]'    => is_array( $original_identities ) ? CulturaRioIdentity::getIdentityLabel( $original_identities[1] ?? $original_identities[0] ?? 'clubber' ) : '',
			'[membership-status]'    => ucfirst( $membership_status ),
			'[membership-requested]' => is_array( $membership_requested ) ? implode( ', ', $membership_requested ) : '',
			'[registration-date]'    => $registration_date ? date_i18n( get_option( 'date_format' ), strtotime( $registration_date ) ) : '',
			'[site-name]'            => get_bloginfo( 'name' ),
			'[site-url]'             => home_url(),
			'[dashboard-url]'        => home_url( '/painel' ),
			'[login-url]'            => wp_login_url(),
			'[admin-name]'           => $extra['admin_name'] ?? 'Apollo Team',
			'[rejection-reason]'     => $extra['rejection_reason'] ?? 'Não especificado',
			'[current-date]'         => date_i18n( get_option( 'date_format' ) ),
			'[current-year]'         => date( 'Y' ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	}

	/**
	 * Send email using Email Templates plugin if available, otherwise wp_mail
	 */
	public function sendEmail( string $to, string $subject, string $body, string $template_key = '' ): bool {
		// Check rate limiting via security log
		if ( class_exists( '\Apollo\Security\EmailSecurityLog' ) ) {
			if ( \Apollo\Security\EmailSecurityLog::isRateLimited( get_current_user_id() ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Apollo Email] Rate limited for user ' . get_current_user_id() );
				}

				return false;
			}
		}

		// Use Email Templates plugin wrapper if available
		if ( class_exists( 'Mailtpl' ) ) {
			// The plugin will automatically wrap the content in template
			$result = wp_mail( $to, $subject, $body );
			$this->logEmailResult( $to, $subject, $template_key, $result );

			return $result;
		}

		// Fallback: wrap in simple HTML template
		$html = $this->wrapInTemplate( $body, $subject );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		$result = wp_mail( $to, $subject, $html, $headers );
		$this->logEmailResult( $to, $subject, $template_key, $result );

		return $result;
	}

	/**
	 * Log email result to security log
	 */
	private function logEmailResult( string $to, string $subject, string $template_key, bool $success ): void {
		if ( class_exists( '\Apollo\Security\EmailSecurityLog' ) ) {
			if ( $success ) {
				\Apollo\Security\EmailSecurityLog::logEmailSent( $to, $subject, $template_key );
			} else {
				\Apollo\Security\EmailSecurityLog::logEmailFailed( $to, $subject, 'wp_mail returned false', $template_key );
			}
		}
	}

	/**
	 * Simple HTML template wrapper
	 */
	private function wrapInTemplate( string $content, string $subject ): string {
		// Convert newlines to <br> if plain text
		if ( strpos( $content, '<' ) === false ) {
			$content = nl2br( esc_html( $content ) );
		}

		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>' . esc_html( $subject ) . '</title></head>
<body style="margin:0;padding:0;background:#1a1a2e;font-family:sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a2e;">
<tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#16213e;border-radius:12px;">
<tr><td style="background:linear-gradient(135deg,#00d4ff,#0066cc);padding:30px;text-align:center;">
<img src="https://assets.apollo.rio.br/logo.png" alt="Apollo::Rio" style="max-height:50px;"/>
</td></tr>
<tr><td style="padding:40px 30px;color:#fff;font-size:16px;line-height:1.6;">' . $content . '</td></tr>
<tr><td style="background:rgba(0,0,0,.3);padding:20px;text-align:center;color:#888;font-size:12px;">
© ' . date( 'Y' ) . ' Apollo::Rio
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
	}

	/**
	 * Add admin menu
	 */
	public function addAdminMenu(): void {
		add_submenu_page(
			'apollo-social-hub',
			__( 'Emails', 'apollo-social' ),
			__( '📧 Emails', 'apollo-social' ),
			'manage_options',
			'apollo-emails',
			array( $this, 'renderAdminPage' )
		);
	}

	/**
	 * Render admin page
	 */
	public function renderAdminPage(): void {
		$current_tab     = sanitize_key( $_GET['tab'] ?? 'templates' );
		$templates       = $this->getDefaultTemplates();
		$saved_templates = get_option( 'apollo_email_templates', array() );
		?>
		<div class="wrap">
			<h1>📧 Apollo Emails</h1>
			
			<nav class="nav-tab-wrapper">
				<a href="?page=apollo-emails&tab=templates" class="nav-tab <?php echo $current_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
					Corpo dos Emails
				</a>
				<a href="?page=apollo-emails&tab=design" class="nav-tab <?php echo $current_tab === 'design' ? 'nav-tab-active' : ''; ?>">
					Design Email
				</a>
				<a href="?page=apollo-emails&tab=placeholders" class="nav-tab <?php echo $current_tab === 'placeholders' ? 'nav-tab-active' : ''; ?>">
					Placeholders
				</a>
			</nav>

			<div class="tab-content" style="margin-top: 20px;">
				<?php if ( $current_tab === 'templates' ) : ?>
					<div class="apollo-email-templates">
						<h2>Templates de Email</h2>
						<p>Edite o conteúdo de cada tipo de email enviado pelo sistema.</p>
						
						<?php
						foreach ( $templates as $key => $template ) :
							$saved   = $saved_templates[ $key ] ?? array();
							$subject = $saved['subject'] ?? $template['subject'];
							$body    = $saved['body'] ?? $template['body'];
							?>
							<div class="apollo-template-card" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
								<h3 style="margin-top: 0;">
									<?php echo esc_html( $template['name'] ); ?>
									<small style="color: #666; font-weight: normal;">— <?php echo esc_html( $template['description'] ); ?></small>
								</h3>
								
								<table class="form-table">
									<tr>
										<th>Assunto</th>
										<td>
											<input type="text" 
													id="subject_<?php echo esc_attr( $key ); ?>" 
													value="<?php echo esc_attr( $subject ); ?>" 
													class="large-text" />
										</td>
									</tr>
									<tr>
										<th>Corpo</th>
										<td>
											<textarea id="body_<?php echo esc_attr( $key ); ?>" 
														rows="10" 
														class="large-text code"><?php echo esc_textarea( $body ); ?></textarea>
										</td>
									</tr>
									<tr>
										<th>Testar</th>
										<td>
											<input type="email" 
													id="test_email_<?php echo esc_attr( $key ); ?>" 
													placeholder="email@exemplo.com" 
													class="regular-text" />
											<button type="button" 
													class="button apollo-test-email-btn" 
													data-template="<?php echo esc_attr( $key ); ?>">
												📤 Enviar Teste
											</button>
											<span class="test-result" style="margin-left: 10px;"></span>
										</td>
									</tr>
								</table>
								
								<p>
									<button type="button" 
											class="button button-primary apollo-save-template-btn" 
											data-template="<?php echo esc_attr( $key ); ?>">
										💾 Salvar Template
									</button>
									<button type="button" 
											class="button apollo-reset-template-btn" 
											data-template="<?php echo esc_attr( $key ); ?>">
										🔄 Restaurar Padrão
									</button>
									<?php if ( isset( $saved['updated_at'] ) ) : ?>
										<span style="color: #666; margin-left: 10px;">
											Última atualização: <?php echo esc_html( $saved['updated_at'] ); ?>
										</span>
									<?php endif; ?>
								</p>
							</div>
						<?php endforeach; ?>
					</div>

				<?php elseif ( $current_tab === 'design' ) : ?>
					<div class="apollo-email-design">
						<h2>Design do Email</h2>
						
						<?php if ( class_exists( 'Mailtpl' ) ) : ?>
							<div class="notice notice-success">
								<p>
									✅ <strong>Email Templates</strong> plugin está ativo!
									<a href="<?php echo admin_url( 'customize.php?autofocus[section]=mailtpl' ); ?>" class="button" style="margin-left: 10px;">
										Abrir Customizer de Design
									</a>
								</p>
							</div>
						<?php else : ?>
							<div class="notice notice-warning">
								<p>
									⚠️ Ative o plugin <strong>apollo-email-templates</strong> para customizar o design visual dos emails.
								</p>
							</div>
						<?php endif; ?>
						
						<?php if ( class_exists( 'Newsletter' ) ) : ?>
							<div class="notice notice-success">
								<p>
									✅ <strong>Newsletter</strong> plugin está ativo!
									<a href="<?php echo admin_url( 'admin.php?page=newsletter_main_main' ); ?>" class="button" style="margin-left: 10px;">
										Abrir Configurações
									</a>
								</p>
							</div>
						<?php endif; ?>
					</div>

				<?php elseif ( $current_tab === 'placeholders' ) : ?>
					<div class="apollo-email-placeholders">
						<h2>Placeholders Disponíveis</h2>
						<p>Use estes placeholders nos templates de email. Eles serão substituídos pelos dados do usuário.</p>
						
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th>Placeholder</th>
									<th>Descrição</th>
								</tr>
							</thead>
							<tbody>
								<tr><td><code>[user-name]</code></td><td>Nome completo do usuário</td></tr>
								<tr><td><code>[user-first-name]</code></td><td>Primeiro nome do usuário</td></tr>
								<tr><td><code>[user-email]</code></td><td>Email do usuário</td></tr>
								<tr><td><code>[fav-sounds]</code></td><td>Gêneros musicais favoritos (separados por vírgula)</td></tr>
								<tr><td><code>[fav-sounds-by-comma]</code></td><td>Mesmo que [fav-sounds]</td></tr>
								<tr><td><code>[cultura-identities]</code></td><td>Identidades Cultura::Rio selecionadas</td></tr>
								<tr><td><code>[original-identity]</code></td><td>Identidade original no registro (para journey)</td></tr>
								<tr><td><code>[membership-status]</code></td><td>Status atual do membership</td></tr>
								<tr><td><code>[membership-requested]</code></td><td>Memberships solicitados</td></tr>
								<tr><td><code>[registration-date]</code></td><td>Data de registro</td></tr>
								<tr><td><code>[dashboard-url]</code></td><td>URL do painel do usuário</td></tr>
								<tr><td><code>[login-url]</code></td><td>URL de login</td></tr>
								<tr><td><code>[site-name]</code></td><td>Nome do site</td></tr>
								<tr><td><code>[site-url]</code></td><td>URL do site</td></tr>
								<tr><td><code>[admin-name]</code></td><td>Nome do admin (em aprovações/rejeições)</td></tr>
								<tr><td><code>[rejection-reason]</code></td><td>Motivo da rejeição</td></tr>
								<tr><td><code>[current-date]</code></td><td>Data atual</td></tr>
								<tr><td><code>[current-year]</code></td><td>Ano atual</td></tr>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<script>
		jQuery(function($) {
			// Test email
			$('.apollo-test-email-btn').on('click', function() {
				var btn = $(this);
				var template = btn.data('template');
				var testEmail = $('#test_email_' + template).val();
				var subject = $('#subject_' + template).val();
				var body = $('#body_' + template).val();
				var result = btn.siblings('.test-result');
				
				if (!testEmail) {
					result.html('<span style="color:red;">Digite um email</span>');
					return;
				}
				
				btn.prop('disabled', true).text('Enviando...');
				
				$.post(ajaxurl, {
					action: 'apollo_send_test_email',
					template: template,
					test_email: testEmail,
					subject: subject,
					body: body,
					_wpnonce: '<?php echo wp_create_nonce( 'apollo_email_action' ); ?>'
				}, function(response) {
					btn.prop('disabled', false).text('📤 Enviar Teste');
					if (response.success) {
						result.html('<span style="color:green;">✅ Enviado!</span>');
					} else {
						result.html('<span style="color:red;">❌ ' + (response.data || 'Erro') + '</span>');
					}
				});
			});
			
			// Save template
			$('.apollo-save-template-btn').on('click', function() {
				var btn = $(this);
				var template = btn.data('template');
				var subject = $('#subject_' + template).val();
				var body = $('#body_' + template).val();
				
				btn.prop('disabled', true).text('Salvando...');
				
				$.post(ajaxurl, {
					action: 'apollo_save_email_template',
					template: template,
					subject: subject,
					body: body,
					_wpnonce: '<?php echo wp_create_nonce( 'apollo_email_action' ); ?>'
				}, function(response) {
					btn.prop('disabled', false).text('💾 Salvar Template');
					if (response.success) {
						alert('Template salvo com sucesso!');
					} else {
						alert('Erro: ' + (response.data || 'Erro desconhecido'));
					}
				});
			});
			
			// Reset template
			$('.apollo-reset-template-btn').on('click', function() {
				if (!confirm('Restaurar para o template padrão?')) return;
				var template = $(this).data('template');
				location.href = '?page=apollo-emails&tab=templates&reset=' + template + '&_wpnonce=<?php echo wp_create_nonce( 'apollo_reset_template' ); ?>';
			});
		});
		</script>
		<?php

		// Handle reset
		if ( isset( $_GET['reset'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'apollo_reset_template' ) ) {
			$key   = sanitize_key( $_GET['reset'] );
			$saved = get_option( 'apollo_email_templates', array() );
			unset( $saved[ $key ] );
			update_option( 'apollo_email_templates', $saved );
			echo '<script>location.href = "?page=apollo-emails&tab=templates";</script>';
		}
	}

	/**
	 * AJAX: Send test email
	 */
	public function ajaxSendTestEmail(): void {
		check_ajax_referer( 'apollo_email_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$test_email = sanitize_email( $_POST['test_email'] ?? '' );
		$subject    = sanitize_text_field( $_POST['subject'] ?? '' );
		$body       = wp_kses_post( $_POST['body'] ?? '' );

		if ( ! is_email( $test_email ) ) {
			wp_send_json_error( 'Email inválido' );
		}

		// Replace placeholders using current user
		$user_id = get_current_user_id();
		$subject = $this->replacePlaceholders( $subject, $user_id );
		$body    = $this->replacePlaceholders( $body, $user_id );

		$sent = $this->sendEmail( $test_email, '[TESTE] ' . $subject, $body );

		if ( $sent ) {
			wp_send_json_success( 'Email enviado' );
		} else {
			wp_send_json_error( 'Falha ao enviar' );
		}
	}

	/**
	 * AJAX: Save template
	 */
	public function ajaxSaveTemplate(): void {
		check_ajax_referer( 'apollo_email_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$template = sanitize_key( $_POST['template'] ?? '' );
		$subject  = sanitize_text_field( $_POST['subject'] ?? '' );
		$body     = wp_kses_post( $_POST['body'] ?? '' );

		$templates = $this->getDefaultTemplates();
		if ( ! isset( $templates[ $template ] ) ) {
			wp_send_json_error( 'Template inválido' );
		}

		$saved              = get_option( 'apollo_email_templates', array() );
		$saved[ $template ] = array(
			'subject'    => $subject,
			'body'       => $body,
			'updated_at' => current_time( 'mysql' ),
			'updated_by' => get_current_user_id(),
		);

		update_option( 'apollo_email_templates', $saved );
		wp_send_json_success( 'Salvo' );
	}

	/**
	 * Send journey progression message
	 */
	public static function sendJourneyMessage( int $user_id ): void {
		$instance = self::instance();
		$user     = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$template = $instance->getTemplate( 'journey_progress' );
		$subject  = $instance->replacePlaceholders( $template['subject'], $user_id );
		$body     = $instance->replacePlaceholders( $template['body'], $user_id );

		$instance->sendEmail( $user->user_email, $subject, $body );
	}
}

// Initialize
add_action(
	'plugins_loaded',
	function () {
		ApolloEmailBridge::instance()->init();
	},
	20
);
