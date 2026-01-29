<?php
/**
 * Notifications Module
 *
 * Handles email notifications and reminders for events.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notifications_Module
 *
 * Email notifications and reminder system.
 *
 * @since 2.0.0
 */
class Notifications_Module extends Abstract_Module {

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'notifications';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Notificações', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Sistema de notificações por email e lembretes de eventos.', 'apollo-events' );
	}

	/**
	 * Get module version.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_version(): string {
		return '2.0.0';
	}

	/**
	 * Check if module is enabled by default.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		$this->register_shortcodes();
		$this->register_assets();
		$this->register_hooks();
		$this->register_cron_jobs();
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_hooks(): void {
		// Event triggers.
		add_action( 'apollo_review_added', array( $this, 'notify_review_added' ), 10, 4 );
		add_action( 'apollo_event_duplicated', array( $this, 'notify_event_duplicated' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'notify_event_published' ), 10, 3 );

		// Interest triggers.
		add_action( 'apollo_interest_added', array( $this, 'notify_interest_added' ), 10, 2 );
		add_action( 'apollo_ticket_purchased', array( $this, 'notify_ticket_purchased' ), 10, 3 );

		// AJAX handlers.
		add_action( 'wp_ajax_apollo_subscribe_notifications', array( $this, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_nopriv_apollo_subscribe_notifications', array( $this, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_apollo_unsubscribe_notifications', array( $this, 'ajax_unsubscribe' ) );

		// Admin.
		add_action( 'admin_menu', array( $this, 'add_notifications_page' ) );
	}

	/**
	 * Register cron jobs.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_cron_jobs(): void {
		add_action( 'apollo_send_event_reminders', array( $this, 'send_event_reminders' ) );
		add_action( 'apollo_send_digest', array( $this, 'send_weekly_digest' ) );

		if ( ! wp_next_scheduled( 'apollo_send_event_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'apollo_send_event_reminders' );
		}

		if ( ! wp_next_scheduled( 'apollo_send_digest' ) ) {
			wp_schedule_event( time(), 'weekly', 'apollo_send_digest' );
		}
	}

	/**
	 * Register module shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_notify_button', array( $this, 'render_notify_button' ) );
		add_shortcode( 'apollo_notification_preferences', array( $this, 'render_preferences' ) );
	}

	/**
	 * Register module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_assets(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		$plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

		wp_register_style(
			'apollo-notifications',
			$plugin_url . '/assets/css/notifications.css',
			array(),
			$this->get_version()
		);

		wp_register_script(
			'apollo-notifications',
			$plugin_url . '/assets/js/notifications.js',
			array( 'jquery' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-notifications',
			'apolloNotifications',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_notifications_nonce' ),
				'i18n'    => array(
					'subscribed'   => __( 'Inscrição confirmada!', 'apollo-events' ),
					'unsubscribed' => __( 'Você foi desinscrito.', 'apollo-events' ),
					'error'        => __( 'Erro. Tente novamente.', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Render notify button shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_notify_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'text'     => __( 'Receber lembretes', 'apollo-events' ),
			),
			$atts,
			'apollo_notify_button'
		);

		$event_id = absint( $atts['event_id'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-notifications' );
		wp_enqueue_script( 'apollo-notifications' );

		$is_subscribed = $this->is_user_subscribed( $event_id );

		ob_start();
		?>
		<div class="apollo-notify-wrapper" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<?php if ( is_user_logged_in() ) : ?>
				<button type="button" class="apollo-notify-btn <?php echo $is_subscribed ? 'is-subscribed' : ''; ?>">
					<i class="fas <?php echo $is_subscribed ? 'fa-bell-slash' : 'fa-bell'; ?>"></i>
					<span><?php echo $is_subscribed ? esc_html__( 'Notificações ativadas', 'apollo-events' ) : esc_html( $atts['text'] ); ?></span>
				</button>
			<?php else : ?>
				<form class="apollo-notify-form">
					<input type="email" name="email" placeholder="<?php esc_attr_e( 'Seu email', 'apollo-events' ); ?>" required>
					<button type="submit" class="apollo-notify-btn">
						<i class="fas fa-bell"></i>
						<span><?php echo esc_html( $atts['text'] ); ?></span>
					</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render notification preferences.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_preferences( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Faça login para gerenciar suas notificações.', 'apollo-events' ) . '</p>';
		}

		wp_enqueue_style( 'apollo-notifications' );
		wp_enqueue_script( 'apollo-notifications' );

		$user_id     = get_current_user_id();
		$preferences = $this->get_user_preferences( $user_id );

		ob_start();
		?>
		<div class="apollo-notification-preferences">
			<h3><?php esc_html_e( 'Preferências de Notificação', 'apollo-events' ); ?></h3>

			<form class="apollo-preferences-form" method="post">
				<?php wp_nonce_field( 'apollo_save_preferences', 'apollo_prefs_nonce' ); ?>

				<div class="apollo-pref-group">
					<label>
						<input type="checkbox" name="email_reminders" value="1"
								<?php checked( $preferences['email_reminders'] ?? true ); ?>>
						<?php esc_html_e( 'Lembretes de eventos por email', 'apollo-events' ); ?>
					</label>
				</div>

				<div class="apollo-pref-group">
					<label>
						<input type="checkbox" name="email_updates" value="1"
								<?php checked( $preferences['email_updates'] ?? true ); ?>>
						<?php esc_html_e( 'Atualizações de eventos que tenho interesse', 'apollo-events' ); ?>
					</label>
				</div>

				<div class="apollo-pref-group">
					<label>
						<input type="checkbox" name="email_digest" value="1"
								<?php checked( $preferences['email_digest'] ?? false ); ?>>
						<?php esc_html_e( 'Resumo semanal de eventos', 'apollo-events' ); ?>
					</label>
				</div>

				<div class="apollo-pref-group">
					<label for="reminder_hours"><?php esc_html_e( 'Lembrar quanto tempo antes:', 'apollo-events' ); ?></label>
					<select name="reminder_hours" id="reminder_hours">
						<option value="1" <?php selected( $preferences['reminder_hours'] ?? 24, 1 ); ?>>1 hora</option>
						<option value="3" <?php selected( $preferences['reminder_hours'] ?? 24, 3 ); ?>>3 horas</option>
						<option value="24" <?php selected( $preferences['reminder_hours'] ?? 24, 24 ); ?>>1 dia</option>
						<option value="48" <?php selected( $preferences['reminder_hours'] ?? 24, 48 ); ?>>2 dias</option>
						<option value="168" <?php selected( $preferences['reminder_hours'] ?? 24, 168 ); ?>>1 semana</option>
					</select>
				</div>

				<button type="submit" class="apollo-btn apollo-btn--primary">
					<?php esc_html_e( 'Salvar Preferências', 'apollo-events' ); ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if user is subscribed to event notifications.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	private function is_user_subscribed( int $event_id ): bool {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$subscriptions = get_user_meta( $user_id, '_apollo_event_subscriptions', true );
		$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

		return in_array( $event_id, $subscriptions, true );
	}

	/**
	 * Get user preferences.
	 *
	 * @since 2.0.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_user_preferences( int $user_id ): array {
		$defaults = array(
			'email_reminders' => true,
			'email_updates'   => true,
			'email_digest'    => false,
			'reminder_hours'  => 24,
		);

		$prefs = get_user_meta( $user_id, '_apollo_notification_prefs', true );

		return wp_parse_args( is_array( $prefs ) ? $prefs : array(), $defaults );
	}

	/**
	 * AJAX subscribe handler.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_subscribe(): void {
		check_ajax_referer( 'apollo_notifications_nonce', 'nonce' );

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events' ) ), 400 );
		}

		$user_id = get_current_user_id();

		if ( $user_id ) {
			// Logged in user.
			$subscriptions = get_user_meta( $user_id, '_apollo_event_subscriptions', true );
			$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

			if ( in_array( $event_id, $subscriptions, true ) ) {
				// Unsubscribe.
				$subscriptions = array_diff( $subscriptions, array( $event_id ) );
				update_user_meta( $user_id, '_apollo_event_subscriptions', array_values( $subscriptions ) );
				wp_send_json_success(
					array(
						'subscribed' => false,
						'message'    => __( 'Notificações desativadas', 'apollo-events' ),
					)
				);
			} else {
				// Subscribe.
				$subscriptions[] = $event_id;
				update_user_meta( $user_id, '_apollo_event_subscriptions', array_unique( $subscriptions ) );
				wp_send_json_success(
					array(
						'subscribed' => true,
						'message'    => __( 'Notificações ativadas!', 'apollo-events' ),
					)
				);
			}
		} elseif ( $email ) {
			// Guest with email.
			$this->add_email_subscription( $event_id, $email );
			wp_send_json_success(
				array(
					'subscribed' => true,
					'message'    => __( 'Email cadastrado para notificações!', 'apollo-events' ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Email necessário', 'apollo-events' ) ), 400 );
		}
	}

	/**
	 * Add email subscription.
	 *
	 * @since 2.0.0
	 * @param int    $event_id Event ID.
	 * @param string $email    Email.
	 * @return void
	 */
	private function add_email_subscription( int $event_id, string $email ): void {
		$subscriptions = get_post_meta( $event_id, '_apollo_email_subscriptions', true );
		$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

		if ( ! in_array( $email, $subscriptions, true ) ) {
			$subscriptions[] = $email;
			update_post_meta( $event_id, '_apollo_email_subscriptions', $subscriptions );
		}
	}

	/**
	 * Send event reminders.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function send_event_reminders(): void {
		$events = $this->get_upcoming_events();

		foreach ( $events as $event_id ) {
			$this->send_reminder_for_event( $event_id );
		}
	}

	/**
	 * Get upcoming events that need reminders.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function get_upcoming_events(): array {
		global $wpdb;

		// Events starting in the next 48 hours.
		$start = current_time( 'mysql' );
		$end   = wp_date( 'Y-m-d H:i:s', strtotime( '+48 hours' ) );

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_event_start_date'
                 AND meta_value BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		return array_map( 'absint', $results );
	}

	/**
	 * Send reminder for specific event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function send_reminder_for_event( int $event_id ): void {
		$sent_key = '_apollo_reminders_sent_' . wp_date( 'Y-m-d' );
		$sent     = get_post_meta( $event_id, $sent_key, true );

		if ( $sent ) {
			return;
		}

		$subscribers = $this->get_event_subscribers( $event_id );
		$event       = get_post( $event_id );
		$event_date  = get_post_meta( $event_id, '_event_start_date', true );

		foreach ( $subscribers as $subscriber ) {
			$this->send_reminder_email( $subscriber, $event, $event_date );
		}

		update_post_meta( $event_id, $sent_key, true );
	}

	/**
	 * Get event subscribers.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	private function get_event_subscribers( int $event_id ): array {
		$subscribers = array();

		// Users with interest.
		$interested = get_post_meta( $event_id, '_event_interested_users', true );
		if ( is_array( $interested ) ) {
			foreach ( $interested as $user_id ) {
				$user = get_userdata( $user_id );
				if ( $user ) {
					$prefs = $this->get_user_preferences( $user_id );
					if ( $prefs['email_reminders'] ) {
						$subscribers[] = array(
							'email' => $user->user_email,
							'name'  => $user->display_name,
						);
					}
				}
			}
		}

		// Email subscriptions.
		$emails = get_post_meta( $event_id, '_apollo_email_subscriptions', true );
		if ( is_array( $emails ) ) {
			foreach ( $emails as $email ) {
				$subscribers[] = array(
					'email' => $email,
					'name'  => '',
				);
			}
		}

		return $subscribers;
	}

	/**
	 * Send reminder email.
	 *
	 * @since 2.0.0
	 * @param array    $subscriber Subscriber data.
	 * @param \WP_Post $event      Event post.
	 * @param string   $event_date Event date.
	 * @return void
	 */
	private function send_reminder_email( array $subscriber, $event, string $event_date ): void {
		$subject = sprintf(
			/* translators: %s: event title */
			__( 'Lembrete: %s está chegando!', 'apollo-events' ),
			$event->post_title
		);

		$message = $this->get_email_template(
			'reminder',
			array(
				'event'      => $event,
				'event_date' => $event_date,
				'subscriber' => $subscriber,
			)
		);

		\Apollo_Core\Email_Integration::instance()->send_email(
			$subscriber['email'],
			$subject,
			$message,
			'event_reminder'
		);
	}

	/**
	 * Get email template.
	 *
	 * @since 2.0.0
	 * @param string $template Template name.
	 * @param array  $data     Template data.
	 * @return string
	 */
	private function get_email_template( string $template, array $data ): string {
		$event      = $data['event'] ?? null;
		$event_date = $data['event_date'] ?? '';
		$subscriber = $data['subscriber'] ?? array();

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px;">
			<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
				<div style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); padding: 30px; text-align: center;">
					<h1 style="color: #fff; margin: 0; font-size: 24px;">
						🎉 <?php esc_html_e( 'Lembrete de Evento', 'apollo-events' ); ?>
					</h1>
				</div>

				<div style="padding: 30px;">
					<?php if ( ! empty( $subscriber['name'] ) ) : ?>
						<p style="font-size: 16px; color: #333;">
							<?php
							printf(
								esc_html__( 'Olá %s,', 'apollo-events' ),
								esc_html( $subscriber['name'] )
							);
							?>
						</p>
					<?php endif; ?>

					<p style="font-size: 16px; color: #333; line-height: 1.6;">
						<?php esc_html_e( 'O evento que você demonstrou interesse está chegando!', 'apollo-events' ); ?>
					</p>

					<?php if ( $event ) : ?>
						<div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">
							<h2 style="margin: 0 0 10px; color: #333; font-size: 20px;">
								<?php echo esc_html( $event->post_title ); ?>
							</h2>

							<?php if ( $event_date ) : ?>
								<p style="margin: 0; color: #666; font-size: 14px;">
									📅 <?php echo esc_html( wp_date( 'd/m/Y \à\s H:i', strtotime( $event_date ) ) ); ?>
								</p>
							<?php endif; ?>
						</div>

						<p style="text-align: center;">
							<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"
								style="display: inline-block; padding: 14px 28px; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">
								<?php esc_html_e( 'Ver Evento', 'apollo-events' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>

				<div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #888;">
					<p style="margin: 0;">
						<?php esc_html_e( 'Você recebeu este email porque demonstrou interesse neste evento.', 'apollo-events' ); ?>
					</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Notify when review is added.
	 *
	 * @since 2.0.0
	 * @param string $review_id Review ID.
	 * @param int    $event_id  Event ID.
	 * @param int    $user_id   User ID.
	 * @param int    $rating    Rating.
	 * @return void
	 */
	public function notify_review_added( string $review_id, int $event_id, int $user_id, int $rating ): void {
		$event     = get_post( $event_id );
		$author_id = $event->post_author;
		$author    = get_userdata( $author_id );
		$reviewer  = get_userdata( $user_id );

		if ( ! $author || ! $reviewer || $author_id === $user_id ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: event title */
			__( 'Nova avaliação em: %s', 'apollo-events' ),
			$event->post_title
		);

		$message = sprintf(
			/* translators: 1: reviewer name, 2: rating, 3: event title */
			__( '%1$s avaliou seu evento "%3$s" com %2$d estrelas.', 'apollo-events' ),
			$reviewer->display_name,
			$rating,
			$event->post_title
		);

		\Apollo_Core\Email_Integration::instance()->send_email(
			$author->user_email,
			$subject,
			$message,
			'event_review_added'
		);
	}

	/**
	 * Notify when event is published.
	 *
	 * @since 2.0.0
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function notify_event_published( string $new_status, string $old_status, $post ): void {
		if ( 'event_listing' !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		/**
		 * Fires when an event is published.
		 *
		 * @since 2.0.0
		 * @param \WP_Post $post Event post.
		 */
		do_action( 'apollo_event_published', $post );
	}

	/**
	 * Add notifications admin page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_notifications_page(): void {
		add_submenu_page(
			'edit.php?post_type=event_listing',
			__( 'Notificações', 'apollo-events' ),
			__( 'Notificações', 'apollo-events' ),
			'manage_options',
			'apollo-notifications',
			array( $this, 'render_notifications_page' )
		);
	}

	/**
	 * Render notifications admin page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_notifications_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configurações de Notificações', 'apollo-events' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'apollo_notifications' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Lembretes Automáticos', 'apollo-events' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="apollo_auto_reminders" value="1"
										<?php checked( get_option( 'apollo_auto_reminders', true ) ); ?>>
								<?php esc_html_e( 'Enviar lembretes automáticos para usuários interessados', 'apollo-events' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Tempo de Antecedência', 'apollo-events' ); ?></th>
						<td>
							<select name="apollo_reminder_hours">
								<option value="1" <?php selected( get_option( 'apollo_reminder_hours', 24 ), 1 ); ?>>1 hora</option>
								<option value="3" <?php selected( get_option( 'apollo_reminder_hours', 24 ), 3 ); ?>>3 horas</option>
								<option value="24" <?php selected( get_option( 'apollo_reminder_hours', 24 ), 24 ); ?>>1 dia</option>
								<option value="48" <?php selected( get_option( 'apollo_reminder_hours', 24 ), 48 ); ?>>2 dias</option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Email de Remetente', 'apollo-events' ); ?></th>
						<td>
							<input type="email" name="apollo_sender_email"
									value="<?php echo esc_attr( get_option( 'apollo_sender_email', get_option( 'admin_email' ) ) ); ?>"
									class="regular-text">
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'auto_reminders' => array(
				'type'    => 'boolean',
				'label'   => __( 'Lembretes automáticos', 'apollo-events' ),
				'default' => true,
			),
			'reminder_hours' => array(
				'type'    => 'number',
				'label'   => __( 'Horas de antecedência', 'apollo-events' ),
				'default' => 24,
			),
			'sender_name'    => array(
				'type'    => 'text',
				'label'   => __( 'Nome do remetente', 'apollo-events' ),
				'default' => get_bloginfo( 'name' ),
			),
			'digest_enabled' => array(
				'type'    => 'boolean',
				'label'   => __( 'Enviar resumo semanal', 'apollo-events' ),
				'default' => false,
			),
		);
	}
}
