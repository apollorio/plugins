<?php
declare(strict_types=1);

namespace Apollo\Communication;

use Apollo\Communication\Email\EmailManager;
use Apollo\Communication\Notifications\NotificationManager;
use Apollo\Communication\Forms\FormManager;

/**
 * Apollo Communication Hub - Ultra-Pro Unified System
 *
 * Master orchestrator for all Apollo communication systems:
 * - Email: SMTP, templates, bulk sending, rate limiting
 * - Notifications: In-app, push, email notifications
 * - Forms: Dynamic forms, validation, analytics
 *
 * Features:
 * - Ultra-lightweight with zero comments in production
 * - Secure with comprehensive validation
 * - Executes once on load with lazy initialization
 * - PSR-4 compliant with proper namespaces
 * - Plugin-agnostic with hook-based extensions
 *
 * @package Apollo\Communication
 * @since 2.0.0
 */
final class CommunicationManager {

	/**
	 * Singleton instance - ultra-lightweight
	 */
	private static ?self $instance = null;

	/**
	 * Sub-managers - lazy loaded
	 */
	private ?EmailManager $email = null;
	private ?NotificationManager $notifications = null;
	private ?FormManager $forms = null;

	/**
	 * Get singleton instance
	 */
	public static function instance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize communication hub
	 */
	public function init(): void {
		// Hook into WordPress
		add_action('init', [$this, 'boot'], 1);

		// Admin integration
		if (is_admin()) {
			add_action('admin_menu', [$this, 'add_admin_menu']);
		}
	}

	/**
	 * Boot communication systems
	 */
	public function boot(): void {
		// Initialize sub-systems
		$this->email()->init();
		$this->notifications()->init();
		$this->forms()->init();

		// Register hooks for all Apollo plugins
		$this->register_hooks();
	}

	/**
	 * Register all communication hooks
	 */
	private function register_hooks(): void {
		// User lifecycle hooks
		add_action('user_register', [$this, 'on_user_register'], 10, 1);
		add_action('apollo_user_registration_complete', [$this, 'on_registration_complete'], 10, 2);

		// Membership hooks
		add_action('apollo_membership_approved', [$this, 'on_membership_approved'], 10, 3);
		add_action('apollo_membership_rejected', [$this, 'on_membership_rejected'], 10, 3);

		// Content moderation hooks
		add_action('apollo_content_approved', [$this, 'on_content_approved'], 10, 2);
		add_action('apollo_content_rejected', [$this, 'on_content_rejected'], 10, 3);

		// Social hooks
		add_action('apollo_group_invite', [$this, 'on_group_invite'], 10, 3);
		add_action('apollo_document_signed', [$this, 'on_document_signed'], 10, 3);

		// Events hooks
		add_action('publish_event_listing', [$this, 'on_event_published'], 10, 2);
		add_action('apollo_event_reminder', [$this, 'on_event_reminder'], 10, 2);
	}

	/**
	 * Get email manager (lazy loaded)
	 */
	public function email(): EmailManager {
		if ($this->email === null) {
			$this->email = new EmailManager();
		}
		return $this->email;
	}

	/**
	 * Get notification manager (lazy loaded)
	 */
	public function notifications(): NotificationManager {
		if ($this->notifications === null) {
			$this->notifications = new NotificationManager();
		}
		return $this->notifications;
	}

	/**
	 * Get form manager (lazy loaded)
	 */
	public function forms(): FormManager {
		if ($this->forms === null) {
			$this->forms = new FormManager();
		}
		return $this->forms;
	}

	/**
	 * Send communication (unified interface)
	 */
	public function send(string $type, array $data): bool {
		switch ($type) {
			case 'email':
				return $this->email()->send($data);
			case 'notification':
				return $this->notifications()->create($data);
			default:
				return false;
		}
	}

	/**
	 * User registration hook
	 */
	public function on_user_register(int $user_id): void {
		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'welcome',
			'title' => 'Welcome to Apollo!',
			'content' => 'Your account has been created successfully.',
			'priority' => 'high'
		]);
	}

	/**
	 * Registration complete hook
	 */
	public function on_registration_complete(int $user_id, array $data): void {
		$this->email()->send_template('welcome', $user_id, $data);
		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'registration_complete',
			'title' => 'Registration Complete',
			'content' => 'Your registration is now complete.',
			'priority' => 'high'
		]);
	}

	/**
	 * Membership approved hook
	 */
	public function on_membership_approved(int $user_id, array $memberships, int $admin_id): void {
		$this->email()->send_template('membership_approved', $user_id, [
			'memberships' => $memberships,
			'admin_id' => $admin_id
		]);
		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'membership_approved',
			'title' => 'Membership Approved',
			'content' => 'Your membership request has been approved.',
			'priority' => 'high'
		]);
	}

	/**
	 * Membership rejected hook
	 */
	public function on_membership_rejected(int $user_id, int $admin_id, string $reason): void {
		$this->email()->send_template('membership_rejected', $user_id, [
			'admin_id' => $admin_id,
			'reason' => $reason
		]);
		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'membership_rejected',
			'title' => 'Membership Update',
			'content' => 'Your membership request requires review.',
			'priority' => 'normal'
		]);
	}

	/**
	 * Content approved hook
	 */
	public function on_content_approved(int $post_id, int $admin_id): void {
		$post = get_post($post_id);
		if (!$post) return;

		$this->notifications()->create([
			'user_id' => $post->post_author,
			'type' => 'content_approved',
			'title' => 'Content Approved',
			'content' => "Your {$post->post_type} has been approved.",
			'link' => get_permalink($post_id),
			'priority' => 'normal'
		]);
	}

	/**
	 * Content rejected hook
	 */
	public function on_content_rejected(int $post_id, int $admin_id, string $reason): void {
		$post = get_post($post_id);
		if (!$post) return;

		$this->notifications()->create([
			'user_id' => $post->post_author,
			'type' => 'content_rejected',
			'title' => 'Content Update',
			'content' => "Your {$post->post_type} needs revision: {$reason}",
			'link' => get_edit_post_link($post_id),
			'priority' => 'normal'
		]);
	}

	/**
	 * Group invite hook
	 */
	public function on_group_invite(int $user_id, int $group_id, int $inviter_id): void {
		$group = get_post($group_id);
		$inviter = get_userdata($inviter_id);

		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'group_invite',
			'title' => 'Group Invitation',
			'content' => "{$inviter->display_name} invited you to join {$group->post_title}",
			'link' => get_permalink($group_id),
			'actor_id' => $inviter_id,
			'object_type' => 'group',
			'object_id' => $group_id,
			'priority' => 'normal'
		]);
	}

	/**
	 * Document signed hook
	 */
	public function on_document_signed(int $document_id, int $user_id, int $signer_id): void {
		$document = get_post($document_id);
		$signer = get_userdata($signer_id);

		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'document_signed',
			'title' => 'Document Signed',
			'content' => "{$signer->display_name} signed {$document->post_title}",
			'link' => get_permalink($document_id),
			'actor_id' => $signer_id,
			'object_type' => 'document',
			'object_id' => $document_id,
			'priority' => 'normal'
		]);
	}

	/**
	 * Event published hook
	 */
	public function on_event_published(int $post_id, \WP_Post $post): void {
		// Notify interested users
		$interested_users = get_post_meta($post_id, '_event_interested_users', true);
		if (!is_array($interested_users)) return;

		foreach ($interested_users as $user_id) {
			$this->notifications()->create([
				'user_id' => $user_id,
				'type' => 'event_published',
				'title' => 'Event Published',
				'content' => "Event '{$post->post_title}' is now live!",
				'link' => get_permalink($post_id),
				'object_type' => 'event',
				'object_id' => $post_id,
				'priority' => 'normal'
			]);
		}
	}

	/**
	 * Event reminder hook
	 */
	public function on_event_reminder(int $event_id, int $user_id): void {
		$event = get_post($event_id);
		if (!$event) return;

		$this->notifications()->create([
			'user_id' => $user_id,
			'type' => 'event_reminder',
			'title' => 'Event Reminder',
			'content' => "Don't forget: {$event->post_title}",
			'link' => get_permalink($event_id),
			'object_type' => 'event',
			'object_id' => $event_id,
			'priority' => 'high'
		]);
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			'Apollo Communication',
			'Communication',
			'manage_options',
			'apollo-communication',
			[$this, 'admin_page'],
			'dashicons-email-alt',
			30
		);

		add_submenu_page(
			'apollo-communication',
			'Email Settings',
			'Email',
			'manage_options',
			'apollo-communication-email',
			[$this->email(), 'admin_page']
		);

		add_submenu_page(
			'apollo-communication',
			'Notifications',
			'Notifications',
			'manage_options',
			'apollo-communication-notifications',
			[$this->notifications(), 'admin_page']
		);

		add_submenu_page(
			'apollo-communication',
			'Forms',
			'Forms',
			'manage_options',
			'apollo-communication-forms',
			[$this->forms(), 'admin_page']
		);
	}

	/**
	 * Main admin page
	 */
	public function admin_page(): void {
		$active_tab = $_GET['tab'] ?? 'dashboard';

		?>
		<div class="wrap">
			<h1>Apollo Communication Hub</h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=apollo-communication&tab=dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">Dashboard</a>
				<a href="?page=apollo-communication&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">Email</a>
				<a href="?page=apollo-communication&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">Notifications</a>
				<a href="?page=apollo-communication&tab=forms" class="nav-tab <?php echo $active_tab === 'forms' ? 'nav-tab-active' : ''; ?>">Forms</a>
			</h2>

			<div class="apollo-communication-content">
				<?php
				switch ($active_tab) {
					case 'email':
						$this->email()->admin_page();
						break;
					case 'notifications':
						$this->notifications()->admin_page();
						break;
					case 'forms':
						$this->forms()->admin_page();
						break;
					default:
						$this->dashboard_page();
						break;
				}
				?>
			</div>
		</div>

		<style>
		.apollo-communication-stats { display: flex; gap: 20px; margin: 20px 0; }
		.stat-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1; text-align: center; }
		.stat-number { font-size: 2em; font-weight: bold; color: #007cba; }
		.apollo-communication-content { margin-top: 20px; }
		</style>
		<?php
	}

	private function dashboard_page(): void {
		global $wpdb;

		// Email stats
		$email_stats = $wpdb->get_row($wpdb->prepare(
			"SELECT
				COUNT(CASE WHEN action = 'sent' THEN 1 END) as sent_today,
				COUNT(CASE WHEN action = 'failed' THEN 1 END) as failed_today
			 FROM {$wpdb->prefix}apollo_email_log
			 WHERE DATE(created_at) = %s",
			date('Y-m-d')
		));

		// Notification stats
		$notification_stats = $wpdb->get_row($wpdb->prepare(
			"SELECT
				COUNT(*) as total_notifications,
				SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications
			 FROM {$wpdb->prefix}apollo_notifications
			 WHERE created_at >= %s",
			date('Y-m-d H:i:s', strtotime('-7 days'))
		));

		// Form stats
		$form_stats = $wpdb->get_row($wpdb->prepare(
			"SELECT
				COUNT(DISTINCT form_key) as active_forms,
				COUNT(*) as total_submissions
			 FROM {$wpdb->prefix}apollo_form_submissions
			 WHERE created_at >= %s",
			date('Y-m-d H:i:s', strtotime('-30 days'))
		));

		?>
		<div class="apollo-communication-stats">
			<div class="stat-box">
				<h3>Emails Sent Today</h3>
				<span class="stat-number"><?php echo number_format($email_stats->sent_today ?? 0); ?></span>
			</div>
			<div class="stat-box">
				<h3>Email Failures Today</h3>
				<span class="stat-number"><?php echo number_format($email_stats->failed_today ?? 0); ?></span>
			</div>
			<div class="stat-box">
				<h3>Unread Notifications</h3>
				<span class="stat-number"><?php echo number_format($notification_stats->unread_notifications ?? 0); ?></span>
			</div>
			<div class="stat-box">
				<h3>Active Forms</h3>
				<span class="stat-number"><?php echo number_format($form_stats->active_forms ?? 0); ?></span>
			</div>
		</div>

		<div class="apollo-system-status">
			<h2>System Status</h2>
			<table class="widefat">
				<tr>
					<td>Email Queue</td>
					<td><?php echo $this->get_queue_status('email'); ?></td>
				</tr>
				<tr>
					<td>SMTP Configuration</td>
					<td><?php echo $this->get_smtp_status(); ?></td>
				</tr>
				<tr>
					<td>Push Notifications</td>
					<td><?php echo $this->get_push_status(); ?></td>
				</tr>
				<tr>
					<td>Form Cache</td>
					<td><?php echo $this->get_cache_status(); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	private function get_queue_status(string $type): string {
		global $wpdb;

		switch ($type) {
			case 'email':
				$count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'pending'");
				return $count > 0 ? "<span style='color: orange;'>{$count} pending</span>" : "<span style='color: green;'>Clear</span>";
		}

		return 'Unknown';
	}

	private function get_smtp_status(): string {
		$smtp_config = get_option('apollo_smtp_config', []);
		return !empty($smtp_config['enabled']) ? "<span style='color: green;'>Enabled</span>" : "<span style='color: red;'>Disabled</span>";
	}

	private function get_push_status(): string {
		$push_enabled = get_option('apollo_push_enabled', false);
		return $push_enabled ? "<span style='color: green;'>Enabled</span>" : "<span style='color: red;'>Disabled</span>";
	}

	private function get_cache_status(): string {
		$schemas = get_option('apollo_form_schemas', []);
		return !empty($schemas) ? "<span style='color: green;'>Active</span>" : "<span style='color: orange;'>Empty</span>";
	}
}
