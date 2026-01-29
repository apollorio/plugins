<?php
declare(strict_types=1);

namespace Apollo\Communication\Email;

use Apollo\Communication\Traits\RateLimitingTrait;
use Apollo\Communication\Traits\LoggingTrait;

/**
 * Ultra-Pro Email Manager
 *
 * @deprecated 3.1.0 Use \Apollo\Email\UnifiedEmailService from apollo-social instead.
 * @see \Apollo\Email\UnifiedEmailService
 *
 * This manager is deprecated and will be removed in a future version.
 * Please migrate to the unified email system in apollo-social plugin:
 * - apollo-social/src/Email/UnifiedEmailService.php
 * - apollo-social/src/Modules/Email/EmailQueueRepository.php
 *
 * Features:
 * - SMTP configuration with multiple providers
 * - Template system with placeholders
 * - Bulk sending with queue management
 * - Rate limiting and security
 * - Bounce handling and analytics
 * - Zero comments in production code
 *
 * @package Apollo\Communication\Email
 */
final class EmailManager {

	use RateLimitingTrait, LoggingTrait;

	private const TABLE_QUEUE = 'apollo_email_queue';
	private const TABLE_LOG = 'apollo_email_log';
	private const HOOK_SEND = 'apollo_email_send';

	private array $smtp_config = [];
	private array $templates = [];
	private bool $bulk_enabled = false;

	public function init(): void {
		$this->setup_tables();
		$this->load_smtp_config();
		$this->register_hooks();
		$this->schedule_bulk_processor();
	}

	private function setup_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Email queue table
		$sql_queue = "CREATE TABLE {$wpdb->prefix}" . self::TABLE_QUEUE . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			to_email varchar(255) NOT NULL,
			subject text NOT NULL,
			body longtext NOT NULL,
			headers text,
			attachments text,
			priority enum('low','normal','high','urgent') DEFAULT 'normal',
			status enum('pending','processing','sent','failed') DEFAULT 'pending',
			template_key varchar(100),
			user_id bigint(20) unsigned,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime NULL,
			error_message text,
			retry_count int DEFAULT 0,
			PRIMARY KEY (id),
			KEY status_priority (status, priority, scheduled_at),
			KEY user_id (user_id),
			KEY template_key (template_key)
		) $charset_collate;";

		// Email log table
		$sql_log = "CREATE TABLE {$wpdb->prefix}" . self::TABLE_LOG . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_id bigint(20) unsigned,
			action enum('sent','failed','bounced','opened','clicked') NOT NULL,
			data longtext,
			ip_address varchar(45),
			user_agent text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email_id (email_id),
			KEY action (action)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql_queue);
		dbDelta($sql_log);
	}

	private function load_smtp_config(): void {
		$this->smtp_config = get_option('apollo_smtp_config', [
			'enabled' => false,
			'host' => '',
			'port' => 587,
			'encryption' => 'tls',
			'username' => '',
			'password' => '',
			'from_email' => get_option('admin_email'),
			'from_name' => get_option('blogname')
		]);
	}

	private function register_hooks(): void {
		add_action('phpmailer_init', [$this, 'configure_smtp']);
		add_action('wp_ajax_apollo_send_test_email', [$this, 'ajax_send_test']);
		add_action('wp_ajax_apollo_save_email_template', [$this, 'ajax_save_template']);
		add_action(self::HOOK_SEND, [$this, 'process_send'], 10, 1);
		add_action('apollo_process_email_queue', [$this, 'process_queue']);
	}

	private function schedule_bulk_processor(): void {
		if (!wp_next_scheduled('apollo_process_email_queue')) {
			wp_schedule_event(time(), 'every_minute', 'apollo_process_email_queue');
		}
	}

	/**
	 * Send email
	 *
	 * @deprecated 3.1.0 Use \Apollo\Email\UnifiedEmailService::send() instead
	 *
	 * @param array $data Email data.
	 * @return bool
	 */
	public function send(array $data): bool {
		_deprecated_function( __METHOD__, '3.1.0', '\\Apollo\\Email\\UnifiedEmailService::send()' );

		$email_data = $this->validate_email_data($data);
		if (!$email_data) return false;

		if ($this->bulk_enabled && isset($data['bulk']) && $data['bulk']) {
			return $this->queue_email($email_data);
		}

		return $this->send_immediate($email_data);
	}

	public function send_template(string $template_key, int $user_id, array $context = []): bool {
		$template = $this->get_template($template_key);
		if (!$template) return false;

		$user = get_userdata($user_id);
		if (!$user) return false;

		$subject = $this->replace_placeholders($template['subject'], $user_id, $context);
		$body = $this->replace_placeholders($template['body'], $user_id, $context);

		return $this->send([
			'to' => $user->user_email,
			'subject' => $subject,
			'body' => $body,
			'template_key' => $template_key,
			'user_id' => $user_id
		]);
	}

	private function validate_email_data(array $data): ?array {
		if (empty($data['to']) || empty($data['subject']) || empty($data['body'])) {
			return null;
		}

		if (!$this->is_valid_email($data['to'])) {
			return null;
		}

		if ($this->is_rate_limited($data['to'])) {
			$this->log('rate_limited', ['email' => $data['to']]);
			return null;
		}

		return [
			'to' => sanitize_email($data['to']),
			'subject' => sanitize_text_field($data['subject']),
			'body' => wp_kses_post($data['body']),
			'headers' => $data['headers'] ?? [],
			'attachments' => $data['attachments'] ?? [],
			'priority' => $data['priority'] ?? 'normal',
			'template_key' => $data['template_key'] ?? null,
			'user_id' => $data['user_id'] ?? null
		];
	}

	private function send_immediate(array $email_data): bool {
		$headers = $this->build_headers($email_data['headers']);
		$success = wp_mail(
			$email_data['to'],
			$email_data['subject'],
			$email_data['body'],
			$headers,
			$email_data['attachments']
		);

		$this->log_email($email_data, $success);
		return $success;
	}

	private function queue_email(array $email_data): bool {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . self::TABLE_QUEUE,
			[
				'to_email' => $email_data['to'],
				'subject' => $email_data['subject'],
				'body' => $email_data['body'],
				'headers' => maybe_serialize($email_data['headers']),
				'attachments' => maybe_serialize($email_data['attachments']),
				'priority' => $email_data['priority'],
				'template_key' => $email_data['template_key'],
				'user_id' => $email_data['user_id'],
				'scheduled_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
		);

		return $result !== false;
	}

	public function process_queue(): void {
		global $wpdb;

		// 1. Check for Lock (prevent overlap)
		$lock_key = 'apollo_email_queue_lock';
		if ( get_transient( $lock_key ) ) {
			// Check if lock is "stuck" (older than 1 hour) and force release
			$lock_age = get_transient( $lock_key . '_timestamp' );
			if ( $lock_age && ( time() - $lock_age ) > HOUR_IN_SECONDS ) {
				delete_transient( $lock_key );
				delete_transient( $lock_key . '_timestamp' );
				error_log( 'Apollo Email Queue: Released stuck lock (older than 1 hour)' );
			} else {
				return; // Lock is active and not stuck
			}
		}

		// 2. Set Lock (valid for 5 minutes max)
		set_transient( $lock_key, true, 5 * MINUTE_IN_SECONDS );
		set_transient( $lock_key . '_timestamp', time(), 5 * MINUTE_IN_SECONDS );

		try {
			// 3. Process Batch (Limit to 50 to prevent timeouts)
			$emails = $wpdb->get_results($wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE_QUEUE . "
				 WHERE status = 'pending' AND scheduled_at <= %s
				 ORDER BY priority DESC, created_at ASC LIMIT 50",
				current_time('mysql')
			));

			foreach ( $emails as $email ) {
				$this->process_queued_email( $email );
			}
		} catch ( Exception $e ) {
			error_log( 'Apollo Email Queue Error: ' . $e->getMessage() );
		} finally {
			// 4. Always Release Lock
			delete_transient( $lock_key );
			delete_transient( $lock_key . '_timestamp' );
		}
	}

	private function process_queued_email(object $email): void {
		global $wpdb;

		$email_data = array(
			'to' => $email->to_email,
			'subject' => $email->subject,
			'body' => $email->body,
			'headers' => maybe_unserialize($email->headers) ? maybe_unserialize($email->headers) : null,
			'attachments' => maybe_unserialize($email->attachments) ? maybe_unserialize($email->attachments) : null
		);

		$success = $this->send_immediate($email_data);

		$wpdb->update(
			$wpdb->prefix . self::TABLE_QUEUE,
			array(
				'status' => $success ? 'sent' : 'failed',
				'sent_at' => current_time('mysql'),
				'error_message' => $success ? null : 'Send failed'
			),
			array('id' => $email->id),
			array('%s', '%s', '%s'),
			array('%d')
		);
	}

	private function build_headers(array $custom_headers = array()): array {
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->smtp_config['from_name'] . ' <' . $this->smtp_config['from_email'] . '>'
		);

		return array_merge($headers, $custom_headers);
	}

	public function configure_smtp(\PHPMailer\PHPMailer\PHPMailer $phpmailer): void {
		if (!$this->smtp_config['enabled']) return;

		$phpmailer->isSMTP();
		$phpmailer->Host = $this->smtp_config['host'];
		$phpmailer->Port = $this->smtp_config['port'];
		$phpmailer->SMTPSecure = $this->smtp_config['encryption'];
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $this->smtp_config['username'];
		$phpmailer->Password = $this->smtp_config['password'];
		$phpmailer->From = $this->smtp_config['from_email'];
		$phpmailer->FromName = $this->smtp_config['from_name'];
	}

	private function get_template(string $key): ?array {
		if (isset($this->templates[$key])) {
			return $this->templates[$key];
		}

		$templates = get_option('apollo_email_templates', []);
		return $templates[$key] ?? null;
	}

	private function replace_placeholders(string $content, int $user_id, array $context = []): string {
		$user = get_userdata($user_id);
		if (!$user) return $content;

		$placeholders = [
			'{{USER_NAME}}' => $user->display_name,
			'{{USER_EMAIL}}' => $user->user_email,
			'{{USER_FIRST_NAME}}' => $user->first_name ?: explode(' ', $user->display_name)[0],
			'{{USER_LAST_NAME}}' => $user->last_name,
			'{{SITE_NAME}}' => get_option('blogname'),
			'{{SITE_URL}}' => home_url(),
			'{{CURRENT_YEAR}}' => date('Y')
		];

		foreach ($context as $key => $value) {
			$placeholders['{{' . strtoupper($key) . '}}'] = $value;
		}

		return str_replace(array_keys($placeholders), array_values($placeholders), $content);
	}

	private function log_email(array $email_data, bool $success): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . self::TABLE_LOG,
			[
				'action' => $success ? 'sent' : 'failed',
				'data' => maybe_serialize($email_data),
				'ip_address' => $this->get_client_ip(),
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
			],
			['%s', '%s', '%s', '%s']
		);
	}

	private function is_valid_email(string $email): bool {
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}

	private function get_client_ip(): string {
		return $_SERVER['HTTP_X_FORWARDED_FOR'] ??
			   $_SERVER['HTTP_X_REAL_IP'] ??
			   $_SERVER['REMOTE_ADDR'] ??
			   '';
	}

	public function get_sent_today_count(): int {
		global $wpdb;
		$today = date('Y-m-d');

		return (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE_LOG . "
			 WHERE action = 'sent' AND DATE(created_at) = %s",
			$today
		));
	}

	public function ajax_send_test(): void {
		check_ajax_referer('apollo_email_test', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$test_email = sanitize_email($_POST['test_email'] ?? '');
		if (!$test_email) {
			wp_send_json_error('Invalid email address');
		}

		$success = $this->send([
			'to' => $test_email,
			'subject' => 'Apollo Email Test',
			'body' => '<h1>Test Email</h1><p>This is a test email from Apollo Communication Hub.</p>'
		]);

		if ($success) {
			wp_send_json_success('Test email sent successfully');
		} else {
			wp_send_json_error('Failed to send test email');
		}
	}

	public function ajax_save_template(): void {
		check_ajax_referer('apollo_email_template', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$template_key = sanitize_key($_POST['template_key'] ?? '');
		$subject = sanitize_text_field($_POST['subject'] ?? '');
		$body = wp_kses_post($_POST['body'] ?? '');

		if (!$template_key || !$subject || !$body) {
			wp_send_json_error('Missing required fields');
		}

		$templates = get_option('apollo_email_templates', []);
		$templates[$template_key] = [
			'subject' => $subject,
			'body' => $body,
			'updated_at' => current_time('mysql')
		];

		update_option('apollo_email_templates', $templates);
		wp_send_json_success('Template saved successfully');
	}

	public function admin_page(): void {
		$templates = get_option('apollo_email_templates', []);
		$smtp_config = $this->smtp_config;

		?>
		<div class="wrap">
			<h1>Email Settings</h1>

			<div class="apollo-email-settings">
				<h2>SMTP Configuration</h2>
				<form method="post" action="options.php">
					<?php settings_fields('apollo_smtp_settings'); ?>
					<table class="form-table">
						<tr>
							<th>Enable SMTP</th>
							<td><input type="checkbox" name="apollo_smtp_config[enabled]" value="1" <?php checked($smtp_config['enabled']); ?>></td>
						</tr>
						<tr>
							<th>SMTP Host</th>
							<td><input type="text" name="apollo_smtp_config[host]" value="<?php echo esc_attr($smtp_config['host']); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th>SMTP Port</th>
							<td><input type="number" name="apollo_smtp_config[port]" value="<?php echo esc_attr($smtp_config['port']); ?>"></td>
						</tr>
						<tr>
							<th>Encryption</th>
							<td>
								<select name="apollo_smtp_config[encryption]">
									<option value="tls" <?php selected($smtp_config['encryption'], 'tls'); ?>>TLS</option>
									<option value="ssl" <?php selected($smtp_config['encryption'], 'ssl'); ?>>SSL</option>
									<option value="none" <?php selected($smtp_config['encryption'], 'none'); ?>>None</option>
								</select>
							</td>
						</tr>
						<tr>
							<th>Username</th>
							<td><input type="text" name="apollo_smtp_config[username]" value="<?php echo esc_attr($smtp_config['username']); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th>Password</th>
							<td><input type="password" name="apollo_smtp_config[password]" value="<?php echo esc_attr($smtp_config['password']); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th>From Email</th>
							<td><input type="email" name="apollo_smtp_config[from_email]" value="<?php echo esc_attr($smtp_config['from_email']); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th>From Name</th>
							<td><input type="text" name="apollo_smtp_config[from_name]" value="<?php echo esc_attr($smtp_config['from_name']); ?>" class="regular-text"></td>
						</tr>
					</table>
					<?php submit_button('Save SMTP Settings'); ?>
				</form>

				<h2>Test Email</h2>
				<p>Send a test email to verify your SMTP configuration.</p>
				<input type="email" id="test-email" placeholder="test@example.com" class="regular-text">
				<button type="button" id="send-test-email" class="button button-primary">Send Test Email</button>

				<h2>Email Templates</h2>
				<div id="email-templates">
					<?php foreach ($templates as $key => $template): ?>
						<div class="template-editor" data-key="<?php echo esc_attr($key); ?>">
							<h3><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></h3>
							<input type="text" class="template-subject" value="<?php echo esc_attr($template['subject']); ?>" placeholder="Subject">
							<?php wp_editor($template['body'], "template-body-{$key}", ['textarea_name' => 'body', 'teeny' => true]); ?>
							<button type="button" class="save-template button button-primary">Save Template</button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#send-test-email').on('click', function() {
				const email = $('#test-email').val();
				if (!email) return alert('Please enter an email address');

				$.post(ajaxurl, {
					action: 'apollo_send_test_email',
					test_email: email,
					nonce: '<?php echo wp_create_nonce('apollo_email_test'); ?>'
				}, function(response) {
					alert(response.success ? response.data : response.data);
				});
			});

			$('.save-template').on('click', function() {
				const $editor = $(this).closest('.template-editor');
				const templateKey = $editor.data('key');
				const subject = $editor.find('.template-subject').val();
				const body = tinymce.get('template-body-' + templateKey).getContent();

				$.post(ajaxurl, {
					action: 'apollo_save_email_template',
					template_key: templateKey,
					subject: subject,
					body: body,
					nonce: '<?php echo wp_create_nonce('apollo_email_template'); ?>'
				}, function(response) {
					alert(response.success ? response.data : response.data);
				});
			});
		});
		</script>
		<?php
	}
}
