<?php
declare(strict_types=1);

namespace Apollo_Core;

/**
 * Apollo Email Integration Hub
 *
 * Central email integration that connects Newsletter plugin (apollo-email-newsletter)
 * and Email Templates plugin (apollo-email-templates) with the entire Apollo ecosystem.
 *
 * Provides rich connectors to:
 * - Apollo Core: memberships, mod actions, user journeys
 * - Apollo Social: document signatures, group invites, social posts
 * - Apollo Events: event publishing, CENA-RIO mod, event reminders
 * - Apollo Rio: PWA notifications, consent updates
 *
 * @package Apollo_Core
 * @since 1.0.0
 */
class Email_Integration {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Newsletter plugin available
	 *
	 * @var bool
	 */
	private bool $newsletter_active = false;

	/**
	 * Email Templates plugin available
	 *
	 * @var bool
	 */
	private bool $email_templates_active = false;

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the integration
	 */
	public function init(): void {
		// Load centralized email service.
		if ( ! class_exists( '\Apollo_Email_Service' ) && ! class_exists( '\Apollo_Core\Email_Service' ) ) {
			require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-email-service.php';
		}

		// Load email templates CPT.
		if ( ! class_exists( '\Apollo_Email_Templates_CPT' ) && ! class_exists( '\Apollo_Core\Email_Templates_CPT' ) ) {
			require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-apollo-email-templates-cpt.php';
		}

		// Check plugin availability (optional integrations).
		$this->newsletter_active = class_exists( '\Newsletter' );
		// Email Templates plugin is optional - don't require WooCommerce.
		$this->email_templates_active = class_exists( '\Mailtpl_Woomail_Composer' );

		// Show admin notices about integration status.
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		// Core Hooks - Memberships & Moderation.
		add_action( 'apollo_membership_approved', array( $this, 'on_membership_approved' ), 10, 3 );
		add_action( 'apollo_membership_rejected', array( $this, 'on_membership_rejected' ), 10, 3 );
		add_action( 'apollo_user_suspended', array( $this, 'on_user_suspended' ), 10, 3 );
		add_action( 'apollo_user_blocked', array( $this, 'on_user_blocked' ), 10, 2 );
		add_action( 'apollo_content_approved', array( $this, 'on_content_approved' ), 10, 2 );
		add_action( 'apollo_content_rejected', array( $this, 'on_content_rejected' ), 10, 3 );

		// Social Hooks - Documents, Groups, Posts.
		add_action( 'apollo_document_finalized', array( $this, 'on_document_finalized' ), 10, 2 );
		add_action( 'apollo_document_signed', array( $this, 'on_document_signed' ), 10, 3 );
		add_action( 'apollo_group_invite', array( $this, 'on_group_invite' ), 10, 3 );
		add_action( 'apollo_group_approved', array( $this, 'on_group_approved' ), 10, 2 );
		add_action( 'apollo_social_post_mention', array( $this, 'on_social_mention' ), 10, 3 );

		// Events Hooks - Event Publishing, CENA-RIO.
		add_action( 'publish_event_listing', array( $this, 'on_event_published' ), 10, 2 );
		add_action( 'apollo_cena_rio_event_confirmed', array( $this, 'on_cena_rio_confirmed' ), 10, 2 );
		add_action( 'apollo_cena_rio_event_approved', array( $this, 'on_cena_rio_approved' ), 10, 3 );
		add_action( 'apollo_cena_rio_event_rejected', array( $this, 'on_cena_rio_rejected' ), 10, 3 );
		add_action( 'apollo_event_reminder', array( $this, 'on_event_reminder' ), 10, 2 );

		// User Journey Hooks.
		add_action( 'apollo_user_registration_complete', array( $this, 'on_registration_complete' ), 10, 2 );
		add_action( 'apollo_user_verification_complete', array( $this, 'on_verification_complete' ), 10, 1 );
		add_action( 'apollo_user_onboarding_complete', array( $this, 'on_onboarding_complete' ), 10, 1 );

		// Newsletter sync.
		if ( $this->newsletter_active ) {
			add_action( 'newsletter_user_confirmed', array( $this, 'sync_newsletter_user' ) );
		}

		// Add Apollo placeholders to Email Templates.
		if ( $this->email_templates_active ) {
			add_filter( 'emailtpl/placeholders', array( $this, 'add_apollo_placeholders' ), 10, 2 );
		}

		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_apollo_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_apollo_save_email_template', array( $this, 'ajax_save_template' ) );
	}

	/**
	 * Check if email system is available
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->newsletter_active || $this->email_templates_active || function_exists( 'wp_mail' );
	}

	/**
	 * Get integration status
	 *
	 * @return array
	 */
	public function get_status(): array {
		$status = array(
			'available'         => $this->is_available(),
			'newsletter'        => $this->newsletter_active,
			'email_templates'   => $this->email_templates_active,
			'wp_mail'           => function_exists( 'wp_mail' ),
			'emails_sent_today' => $this->get_emails_sent_today(),
		);

		return $status;
	}

	/**
	 * Get emails sent today count
	 *
	 * @return int
	 */
	private function get_emails_sent_today(): int {
		$count = get_transient( 'apollo_emails_sent_' . gmdate( 'Y-m-d' ) );

		return (int) ( $count ?: 0 );
	}

	/**
	 * Show admin notice with tooltip
	 */
	public function admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->parent_base, array( 'apollo-core-hub', 'apollo-control' ), true ) ) {
			return;
		}

		$status = $this->get_status();

		$class = $status['available'] ? 'notice-success' : 'notice-warning';
		$icon  = $status['available'] ? '✅' : '⚠️';

		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
			<p>
				<strong><?php echo esc_html( $icon ); ?> Apollo Email Integration</strong>
				<span style="margin-left: 10px;" title="Newsletter: <?php echo $status['newsletter'] ? 'Active' : 'Inactive'; ?> | Email Templates: <?php echo $status['email_templates'] ? 'Active' : 'Inactive'; ?> | Emails Today: <?php echo esc_attr( $status['emails_sent_today'] ); ?>">
					<?php
					if ( $status['newsletter'] && $status['email_templates'] ) {
						echo '✨ Full integration active';
					} elseif ( $status['newsletter'] || $status['email_templates'] ) {
						echo '📧 Partial integration (using ' . ( $status['newsletter'] ? 'Newsletter' : 'Email Templates' ) . ')';
					} else {
						echo '📨 Basic wp_mail() only';
					}
					?>
				</span>
				<?php if ( $status['available'] ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-email-hub' ) ); ?>" style="margin-left: 10px;">Configure</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'apollo-core-hub',
			__( 'Email Hub', 'apollo-core' ),
			__( '📧 Email Hub', 'apollo-core' ),
			'manage_options',
			'apollo-email-hub',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin settings page
	 */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied', 'apollo-core' ) );
		}

		$status    = $this->get_status();
		$templates = $this->get_default_templates();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '📧 Apollo Email Integration Hub', 'apollo-core' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Integration Status', 'apollo-core' ); ?></h2>
				<table class="widefat">
					<tr>
						<td><strong>Newsletter Plugin</strong></td>
						<td><?php echo $status['newsletter'] ? '✅ Active' : '❌ Not installed'; ?></td>
					</tr>
					<tr>
						<td><strong>Email Templates Plugin</strong></td>
						<td><?php echo $status['email_templates'] ? '✅ Active' : '❌ Not installed'; ?></td>
					</tr>
					<tr>
						<td><strong>WordPress Mail</strong></td>
						<td><?php echo $status['wp_mail'] ? '✅ Available' : '❌ Not available'; ?></td>
					</tr>
					<tr>
						<td><strong>Emails Sent Today</strong></td>
						<td><?php echo esc_html( $status['emails_sent_today'] ); ?></td>
					</tr>
				</table>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Available Email Templates', 'apollo-core' ); ?></h2>
				<p><?php esc_html_e( 'These templates are triggered automatically by Apollo ecosystem actions:', 'apollo-core' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Template</th>
							<th>Trigger</th>
							<th>Connects To</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $templates as $key => $template ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $template['name'] ); ?></strong></td>
								<td><?php echo esc_html( $template['trigger'] ); ?></td>
								<td><code><?php echo esc_html( $template['hook'] ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Integration Points', 'apollo-core' ); ?></h2>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><strong>Apollo Core:</strong> Membership approvals/rejections, user suspensions/blocks, content mod</li>
					<li><strong>Apollo Social:</strong> Document signatures, group invites, social mentions</li>
					<li><strong>Apollo Events:</strong> Event publishing, CENA-RIO workflows, event reminders</li>
					<li><strong>Apollo Rio:</strong> PWA updates, consent changes</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Get default templates
	 *
	 * @return array
	 */
	public function get_default_templates(): array {
		return array(
			'membership_approved'  => array(
				'name'    => 'Membership Approved',
				'trigger' => 'Admin approves membership',
				'hook'    => 'apollo_membership_approved',
			),
			'membership_rejected'  => array(
				'name'    => 'Membership Rejected',
				'trigger' => 'Admin rejects membership',
				'hook'    => 'apollo_membership_rejected',
			),
			'user_suspended'       => array(
				'name'    => 'User Suspended',
				'trigger' => 'Moderator suspends user',
				'hook'    => 'apollo_user_suspended',
			),
			'user_blocked'         => array(
				'name'    => 'User Blocked',
				'trigger' => 'Moderator blocks user',
				'hook'    => 'apollo_user_blocked',
			),
			'content_approved'     => array(
				'name'    => 'Content Approved',
				'trigger' => 'Moderator approves content',
				'hook'    => 'apollo_content_approved',
			),
			'content_rejected'     => array(
				'name'    => 'Content Rejected',
				'trigger' => 'Moderator rejects content',
				'hook'    => 'apollo_content_rejected',
			),
			'document_finalized'   => array(
				'name'    => 'Document Finalized',
				'trigger' => 'User finalizes document',
				'hook'    => 'apollo_document_finalized',
			),
			'document_signed'      => array(
				'name'    => 'Document Signed',
				'trigger' => 'User signs document',
				'hook'    => 'apollo_document_signed',
			),
			'group_invite'         => array(
				'name'    => 'Group Invite',
				'trigger' => 'User invited to group',
				'hook'    => 'apollo_group_invite',
			),
			'group_approved'       => array(
				'name'    => 'Group Approved',
				'trigger' => 'Moderator approves group',
				'hook'    => 'apollo_group_approved',
			),
			'event_published'      => array(
				'name'    => 'Event Published',
				'trigger' => 'Event goes live',
				'hook'    => 'publish_event_listing',
			),
			'cena_rio_confirmed'   => array(
				'name'    => 'CENA-RIO Confirmed',
				'trigger' => 'CENA-RIO event confirmed',
				'hook'    => 'apollo_cena_rio_event_confirmed',
			),
			'cena_rio_approved'    => array(
				'name'    => 'CENA-RIO Approved',
				'trigger' => 'Moderator approves CENA-RIO',
				'hook'    => 'apollo_cena_rio_event_approved',
			),
			'cena_rio_rejected'    => array(
				'name'    => 'CENA-RIO Rejected',
				'trigger' => 'Moderator rejects CENA-RIO',
				'hook'    => 'apollo_cena_rio_event_rejected',
			),
			'event_reminder'       => array(
				'name'    => 'Event Reminder',
				'trigger' => '24h before event starts',
				'hook'    => 'apollo_event_reminder',
			),
			'registration_welcome' => array(
				'name'    => 'Welcome Email',
				'trigger' => 'User completes registration',
				'hook'    => 'apollo_user_registration_complete',
			),
			'verification_done'    => array(
				'name'    => 'Verification Complete',
				'trigger' => 'User verification approved',
				'hook'    => 'apollo_user_verification_complete',
			),
			'onboarding_done'      => array(
				'name'    => 'Onboarding Complete',
				'trigger' => 'User finishes onboarding',
				'hook'    => 'apollo_user_onboarding_complete',
			),
		);
	}

	/**
	 * Send email with tracking and rate limiting
	 *
	 * @param string $to      Recipient email.
	 * @param string $subject Subject.
	 * @param string $body    Body content.
	 * @param string $type    Email type for tracking.
	 * @return bool
	 */
	private function send_email( string $to, string $subject, string $body, string $type = 'general' ): bool {
		// Use centralized email service.
		// Check for namespaced class first, then global.
		if ( class_exists( '\Apollo_Core\Email_Service' ) ) {
			$email_service = \Apollo_Core\Email_Service::instance();
		} else {
			$email_service = \Apollo_Email_Service::instance();
		}

		$result = $email_service->send(
			array(
				'to'        => $to,
				'subject'   => $subject,
				'body_html' => $body,
				'flow'      => $type,
			)
		);

		return ! is_wp_error( $result ) && $result;
	}

	// ========================================.
	// Hook Handlers - Core (Memberships & Moderation).
	// ========================================.

	/**
	 * On membership approved
	 *
	 * @param int   $user_id     User ID.
	 * @param array $memberships Approved memberships.
	 * @param int   $admin_id    Admin who approved.
	 */
	public function on_membership_approved( int $user_id, array $memberships, int $admin_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Parabéns! Seu membership foi aprovado' );
		$body    = sprintf(
			'<h2>Membership Aprovado</h2><p>Olá %s,</p><p>Seu acesso foi aprovado para: <strong>%s</strong></p>',
			esc_html( $user->display_name ),
			esc_html( implode( ', ', $memberships ) )
		);

		$this->send_email( $user->user_email, $subject, $body, 'membership_approved' );
	}

	/**
	 * On membership rejected
	 *
	 * @param int    $user_id  User ID.
	 * @param int    $admin_id Admin who rejected.
	 * @param string $reason   Rejection reason.
	 */
	public function on_membership_rejected( int $user_id, int $admin_id, string $reason ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Atualização sobre sua solicitação' );
		$body    = sprintf(
			'<h2>Membership Rejeitado</h2><p>Olá %s,</p><p>Sua solicitação não foi aprovada. Motivo: <strong>%s</strong></p>',
			esc_html( $user->display_name ),
			esc_html( $reason ?: 'Não especificado' )
		);

		$this->send_email( $user->user_email, $subject, $body, 'membership_rejected' );
	}

	/**
	 * On user suspended
	 *
	 * @param int    $user_id User ID.
	 * @param int    $days    Suspension days.
	 * @param string $reason  Reason.
	 */
	public function on_user_suspended( int $user_id, int $days, string $reason ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Sua conta foi suspensa temporariamente' );
		$body    = sprintf(
			'<h2>Conta Suspensa</h2><p>Olá %s,</p><p>Sua conta foi suspensa por %d dias. Motivo: <strong>%s</strong></p>',
			esc_html( $user->display_name ),
			$days,
			esc_html( $reason )
		);

		$this->send_email( $user->user_email, $subject, $body, 'user_suspended' );
	}

	/**
	 * On user blocked
	 *
	 * @param int    $user_id User ID.
	 * @param string $reason  Reason.
	 */
	public function on_user_blocked( int $user_id, string $reason ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Sua conta foi bloqueada' );
		$body    = sprintf(
			'<h2>Conta Bloqueada</h2><p>Olá %s,</p><p>Sua conta foi bloqueada permanentemente. Motivo: <strong>%s</strong></p>',
			esc_html( $user->display_name ),
			esc_html( $reason )
		);

		$this->send_email( $user->user_email, $subject, $body, 'user_blocked' );
	}

	/**
	 * On content approved
	 *
	 * @param int $post_id  Post ID.
	 * @param int $admin_id Admin ID.
	 */
	public function on_content_approved( int $post_id, int $admin_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf( 'Seu conteúdo foi aprovado!' );
		$body    = sprintf(
			'<h2>Conteúdo Aprovado</h2><p>Olá %s,</p><p>Seu conteúdo "<strong>%s</strong>" foi aprovado e está público.</p>',
			esc_html( $author->display_name ),
			esc_html( $post->post_title )
		);

		$this->send_email( $author->user_email, $subject, $body, 'content_approved' );
	}

	/**
	 * On content rejected
	 *
	 * @param int    $post_id  Post ID.
	 * @param int    $admin_id Admin ID.
	 * @param string $reason   Reason.
	 */
	public function on_content_rejected( int $post_id, int $admin_id, string $reason ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf( 'Seu conteúdo foi rejeitado' );
		$body    = sprintf(
			'<h2>Conteúdo Rejeitado</h2><p>Olá %s,</p><p>Seu conteúdo "<strong>%s</strong>" não foi aprovado. Motivo: <strong>%s</strong></p>',
			esc_html( $author->display_name ),
			esc_html( $post->post_title ),
			esc_html( $reason )
		);

		$this->send_email( $author->user_email, $subject, $body, 'content_rejected' );
	}

	// ========================================.
	// Hook Handlers - Social.
	// ========================================.

	/**
	 * On document finalized
	 *
	 * @param int   $document_id Document post ID.
	 * @param array $data        Document data.
	 */
	public function on_document_finalized( int $document_id, array $data ): void {
		$post = get_post( $document_id );
		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf( 'Documento finalizado!' );
		$body    = sprintf(
			'<h2>Documento Pronto</h2><p>Olá %s,</p><p>Seu documento "<strong>%s</strong>" foi finalizado.</p>',
			esc_html( $author->display_name ),
			esc_html( $post->post_title )
		);

		$this->send_email( $author->user_email, $subject, $body, 'document_finalized' );
	}

	/**
	 * On document signed
	 *
	 * @param int    $document_id Document ID.
	 * @param int    $signer_id   Signer user ID.
	 * @param string $signature   Signature data.
	 */
	public function on_document_signed( int $document_id, int $signer_id, string $signature ): void {
		$post = get_post( $document_id );
		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		$signer = get_userdata( $signer_id );

		if ( ! $author || ! $signer ) {
			return;
		}

		$subject = sprintf( 'Documento assinado!' );
		$body    = sprintf(
			'<h2>Nova Assinatura</h2><p>Olá %s,</p><p><strong>%s</strong> assinou seu documento "<strong>%s</strong>".</p>',
			esc_html( $author->display_name ),
			esc_html( $signer->display_name ),
			esc_html( $post->post_title )
		);

		$this->send_email( $author->user_email, $subject, $body, 'document_signed' );
	}

	/**
	 * On group invite
	 *
	 * @param int $group_id   Group post ID.
	 * @param int $user_id    Invited user ID.
	 * @param int $inviter_id Inviter ID.
	 */
	public function on_group_invite( int $group_id, int $user_id, int $inviter_id ): void {
		$group   = get_post( $group_id );
		$user    = get_userdata( $user_id );
		$inviter = get_userdata( $inviter_id );

		if ( ! $group || ! $user || ! $inviter ) {
			return;
		}

		$subject = sprintf( 'Convite para grupo!' );
		$body    = sprintf(
			'<h2>Novo Convite</h2><p>Olá %s,</p><p><strong>%s</strong> convidou você para o grupo "<strong>%s</strong>".</p>',
			esc_html( $user->display_name ),
			esc_html( $inviter->display_name ),
			esc_html( $group->post_title )
		);

		$this->send_email( $user->user_email, $subject, $body, 'group_invite' );
	}

	/**
	 * On group approved
	 *
	 * @param int $group_id Group post ID.
	 * @param int $admin_id Admin ID.
	 */
	public function on_group_approved( int $group_id, int $admin_id ): void {
		$group = get_post( $group_id );
		if ( ! $group ) {
			return;
		}

		$author = get_userdata( $group->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf( 'Grupo aprovado!' );
		$body    = sprintf(
			'<h2>Grupo Aprovado</h2><p>Olá %s,</p><p>Seu grupo "<strong>%s</strong>" foi aprovado!</p>',
			esc_html( $author->display_name ),
			esc_html( $group->post_title )
		);

		$this->send_email( $author->user_email, $subject, $body, 'group_approved' );
	}

	/**
	 * On social mention
	 *
	 * @param int $post_id      Post ID.
	 * @param int $mentioned_id Mentioned user ID.
	 * @param int $author_id    Author ID.
	 */
	public function on_social_mention( int $post_id, int $mentioned_id, int $author_id ): void {
		$post      = get_post( $post_id );
		$mentioned = get_userdata( $mentioned_id );
		$author    = get_userdata( $author_id );

		if ( ! $post || ! $mentioned || ! $author ) {
			return;
		}

		$subject = sprintf( 'Você foi mencionado!' );
		$body    = sprintf(
			'<h2>Nova Menção</h2><p>Olá %s,</p><p><strong>%s</strong> mencionou você em uma publicação.</p>',
			esc_html( $mentioned->display_name ),
			esc_html( $author->display_name )
		);

		$this->send_email( $mentioned->user_email, $subject, $body, 'social_mention' );
	}

	// ========================================.
	// Hook Handlers - Events.
	// ========================================.

	/**
	 * On event published
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_event_published( int $post_id, $post ): void {
		if ( ! $post || $post->post_type !== 'event_listing' ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf( 'Evento publicado!' );
		$body    = sprintf(
			'<h2>Evento Ao Vivo</h2><p>Olá %s,</p><p>Seu evento "<strong>%s</strong>" está publicado!</p>',
			esc_html( $author->display_name ),
			esc_html( $post->post_title )
		);

		$this->send_email( $author->user_email, $subject, $body, 'event_published' );
	}

	/**
	 * On CENA-RIO event confirmed
	 *
	 * @param int $event_id Event ID.
	 * @param int $user_id  Confirmer ID.
	 */
	public function on_cena_rio_confirmed( int $event_id, int $user_id ): void {
		$event = get_post( $event_id );
		$user  = get_userdata( $user_id );

		if ( ! $event || ! $user ) {
			return;
		}

		$subject = sprintf( 'Evento CENA-RIO confirmado!' );
		$body    = sprintf(
			'<h2>Evento Confirmado</h2><p>Olá %s,</p><p>Seu evento "<strong>%s</strong>" foi confirmado e enviado para moderação.</p>',
			esc_html( $user->display_name ),
			esc_html( $event->post_title )
		);

		$this->send_email( $user->user_email, $subject, $body, 'cena_rio_confirmed' );
	}

	/**
	 * On CENA-RIO event approved
	 *
	 * @param int $event_id Event ID.
	 * @param int $mod_id   Moderator ID.
	 * @param int $user_id  Event creator ID.
	 */
	public function on_cena_rio_approved( int $event_id, int $mod_id, int $user_id ): void {
		$event = get_post( $event_id );
		$user  = get_userdata( $user_id );

		if ( ! $event || ! $user ) {
			return;
		}

		$subject = sprintf( 'Evento CENA-RIO aprovado!' );
		$body    = sprintf(
			'<h2>Aprovado!</h2><p>Olá %s,</p><p>Seu evento CENA-RIO "<strong>%s</strong>" foi aprovado!</p>',
			esc_html( $user->display_name ),
			esc_html( $event->post_title )
		);

		$this->send_email( $user->user_email, $subject, $body, 'cena_rio_approved' );
	}

	/**
	 * On CENA-RIO event rejected
	 *
	 * @param int    $event_id Event ID.
	 * @param int    $mod_id   Moderator ID.
	 * @param string $reason   Rejection reason.
	 */
	public function on_cena_rio_rejected( int $event_id, int $mod_id, string $reason ): void {
		$event = get_post( $event_id );
		if ( ! $event ) {
			return;
		}

		$author = get_userdata( $event->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf( 'Evento CENA-RIO rejeitado' );
		$body    = sprintf(
			'<h2>Evento Rejeitado</h2><p>Olá %s,</p><p>Seu evento "<strong>%s</strong>" não foi aprovado. Motivo: <strong>%s</strong></p>',
			esc_html( $author->display_name ),
			esc_html( $event->post_title ),
			esc_html( $reason )
		);

		$this->send_email( $author->user_email, $subject, $body, 'cena_rio_rejected' );
	}

	/**
	 * On event reminder (24h before)
	 *
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 */
	public function on_event_reminder( int $event_id, int $user_id ): void {
		$event = get_post( $event_id );
		$user  = get_userdata( $user_id );

		if ( ! $event || ! $user ) {
			return;
		}

		$subject = sprintf( 'Lembrete: Evento amanhã!' );
		$body    = sprintf(
			'<h2>Lembrete</h2><p>Olá %s,</p><p>Não esqueça: "<strong>%s</strong>" acontece amanhã!</p>',
			esc_html( $user->display_name ),
			esc_html( $event->post_title )
		);

		$this->send_email( $user->user_email, $subject, $body, 'event_reminder' );
	}

	// ========================================.
	// Hook Handlers - User Journey.
	// ========================================.

	/**
	 * On registration complete
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Registration data.
	 */
	public function on_registration_complete( int $user_id, array $data ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Bem-vindo ao Apollo::Rio!' );
		$body    = sprintf(
			'<h2>Bem-vindo!</h2><p>Olá %s,</p><p>Obrigado por se registrar. Sua jornada começa agora!</p>',
			esc_html( $user->display_name )
		);

		$this->send_email( $user->user_email, $subject, $body, 'registration_welcome' );
	}

	/**
	 * On verification complete
	 *
	 * @param int $user_id User ID.
	 */
	public function on_verification_complete( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Verificação completa!' );
		$body    = sprintf(
			'<h2>Verificado!</h2><p>Olá %s,</p><p>Sua conta foi verificada com sucesso!</p>',
			esc_html( $user->display_name )
		);

		$this->send_email( $user->user_email, $subject, $body, 'verification_done' );
	}

	/**
	 * On onboarding complete
	 *
	 * @param int $user_id User ID.
	 */
	public function on_onboarding_complete( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$subject = sprintf( 'Onboarding completo!' );
		$body    = sprintf(
			'<h2>Pronto!</h2><p>Olá %s,</p><p>Você completou o onboarding. Agora aproveite o Apollo!</p>',
			esc_html( $user->display_name )
		);

		$this->send_email( $user->user_email, $subject, $body, 'onboarding_done' );
	}

	// ========================================.
	// Newsletter Integration.
	// ========================================.

	/**
	 * Sync Newsletter subscriber with Apollo user
	 *
	 * @param object $newsletter_user Newsletter user object.
	 */
	public function sync_newsletter_user( $newsletter_user ): void {
		if ( empty( $newsletter_user->email ) ) {
			return;
		}

		$wp_user = get_user_by( 'email', $newsletter_user->email );
		if ( ! $wp_user ) {
			return;
		}

		update_user_meta( $wp_user->ID, 'apollo_newsletter_synced', true );
		update_user_meta( $wp_user->ID, 'apollo_newsletter_id', $newsletter_user->id );
	}

	/**
	 * Add Apollo placeholders to Email Templates plugin
	 *
	 * @param array  $placeholders Existing placeholders.
	 * @param string $user_email   User email.
	 * @return array
	 */
	public function add_apollo_placeholders( array $placeholders, string $user_email = '' ): array {
		$user = get_user_by( 'email', $user_email );
		if ( ! $user ) {
			return $placeholders;
		}

		$placeholders['%%APOLLO_USER_NAME%%']         = $user->display_name;
		$placeholders['%%APOLLO_FIRST_NAME%%']        = $user->first_name ?: explode( ' ', $user->display_name )[0];
		$placeholders['%%APOLLO_MEMBERSHIP_STATUS%%'] = ucfirst( get_user_meta( $user->ID, 'apollo_membership_status', true ) ?: 'none' );
		$placeholders['%%APOLLO_DASHBOARD_URL%%']     = home_url( '/painel' );

		return $placeholders;
	}

	// ========================================.
	// AJAX Handlers.
	// ========================================.

	/**
	 * AJAX: Send test email
	 */
	public function ajax_send_test_email(): void {
		check_ajax_referer( 'apollo_email_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$to      = sanitize_email( $_POST['to'] ?? '' );
		$subject = sanitize_text_field( $_POST['subject'] ?? 'Test Email' );
		$body    = wp_kses_post( $_POST['body'] ?? 'This is a test email from Apollo.' );

		if ( ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address' ) );
		}

		$result = $this->send_email( $to, $subject, $body, 'test' );

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Test email sent successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to send test email' ) );
		}
	}

	/**
	 * AJAX: Save email template
	 */
	public function ajax_save_template(): void {
		check_ajax_referer( 'apollo_email_temp', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$key     = sanitize_key( $_POST['key'] ?? '' );
		$subject = sanitize_text_field( $_POST['subject'] ?? '' );
		$body    = wp_kses_post( $_POST['body'] ?? '' );

		if ( ! $key || ! $subject || ! $body ) {
			wp_send_json_error( array( 'message' => 'Missing required fields' ) );
		}

		$templates         = get_option( 'apollo_email_templates', array() );
		$templates[ $key ] = array(
			'subject' => $subject,
			'body'    => $body,
		);

		update_option( 'apollo_email_templates', $templates );

		wp_send_json_success( array( 'message' => 'Template saved successfully' ) );
	}
}

// Initialize.
add_action(
	'plugins_loaded',
	function () {
		Email_Integration::instance()->init();
	},
	15
);
