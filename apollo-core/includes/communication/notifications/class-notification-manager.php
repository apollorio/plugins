<?php
declare(strict_types=1);

namespace Apollo\Communication\Notifications;

use Apollo\Communication\Traits\LoggingTrait;

/**
 * Ultra-Pro Notification Manager
 *
 * Features:
 * - In-app notifications with real-time updates
 * - Push notification support
 * - Notification preferences per user
 * - Bulk notification sending
 * - Analytics and tracking
 * - Zero comments in production code
 *
 * @package Apollo\Communication\Notifications
 */
final class NotificationManager {

	use LoggingTrait;

	private const TABLE_NOTIFICATIONS = 'apollo_notifications';
	private const TABLE_PREFERENCES = 'apollo_notification_preferences';
	private const HOOK_SEND = 'apollo_notification_send';

	private array $preferences_cache = [];
	private bool $push_enabled = false;

	public function init(): void {
		$this->setup_tables();
		$this->register_hooks();
		$this->load_push_config();
	}

	private function setup_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Notifications table
		$sql_notifications = "CREATE TABLE {$wpdb->prefix}" . self::TABLE_NOTIFICATIONS . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			message text NOT NULL,
			data longtext,
			is_read tinyint(1) DEFAULT 0,
			is_archived tinyint(1) DEFAULT 0,
			priority enum('low','normal','high','urgent') DEFAULT 'normal',
			expires_at datetime NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			read_at datetime NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY type (type),
			KEY is_read (is_read),
			KEY priority (priority),
			KEY expires_at (expires_at)
		) $charset_collate;";

		// Notification preferences table
		$sql_preferences = "CREATE TABLE {$wpdb->prefix}" . self::TABLE_PREFERENCES . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(50) NOT NULL,
			email_enabled tinyint(1) DEFAULT 1,
			push_enabled tinyint(1) DEFAULT 1,
			in_app_enabled tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_type (user_id, type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql_notifications);
		dbDelta($sql_preferences);
	}

	private function register_hooks(): void {
		add_action('wp_ajax_apollo_get_notifications', [$this, 'ajax_get_notifications']);
		add_action('wp_ajax_apollo_mark_notification_read', [$this, 'ajax_mark_read']);
		add_action('wp_ajax_apollo_update_preferences', [$this, 'ajax_update_preferences']);
		add_action(self::HOOK_SEND, [$this, 'process_send'], 10, 1);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
	}

	private function load_push_config(): void {
		$this->push_enabled = get_option('apollo_push_enabled', false);
	}

	public function send(array $data): bool {
		$notification_data = $this->validate_notification_data($data);
		if (!$notification_data) return false;

		if (isset($data['bulk']) && $data['bulk']) {
			return $this->send_bulk($notification_data);
		}

		return $this->send_single($notification_data);
	}

	public function send_to_users(array $user_ids, array $data): int {
		$sent_count = 0;
		foreach ($user_ids as $user_id) {
			$data['user_id'] = $user_id;
			if ($this->send($data)) {
				$sent_count++;
			}
		}
		return $sent_count;
	}

	public function send_to_all_users(array $data): int {
		$users = get_users(['fields' => 'ID']);
		return $this->send_to_users($users, $data);
	}

	public function send_to_user(int $user_id, array $data): bool {
		$data['user_id'] = $user_id;
		return $this->send($data);
	}

	public function send_to_role(string $role, array $data): int {
		$users = get_users(['role' => $role, 'fields' => 'ID']);
		return $this->send_to_users($users, $data);
	}

	private function validate_notification_data(array $data): ?array {
		if (empty($data['user_id']) || empty($data['type']) || empty($data['title']) || empty($data['message'])) {
			return null;
		}

		if (!get_userdata($data['user_id'])) {
			return null;
		}

		if (!$this->user_wants_notification($data['user_id'], $data['type'])) {
			return null;
		}

		return [
			'user_id' => (int) $data['user_id'],
			'type' => sanitize_key($data['type']),
			'title' => sanitize_text_field($data['title']),
			'message' => wp_kses_post($data['message']),
			'data' => $data['data'] ?? [],
			'priority' => $data['priority'] ?? 'normal',
			'expires_at' => $data['expires_at'] ?? null
		];
	}

	private function send_single(array $notification_data): bool {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . self::TABLE_NOTIFICATIONS,
			[
				'user_id' => $notification_data['user_id'],
				'type' => $notification_data['type'],
				'title' => $notification_data['title'],
				'message' => $notification_data['message'],
				'data' => maybe_serialize($notification_data['data']),
				'priority' => $notification_data['priority'],
				'expires_at' => $notification_data['expires_at']
			],
			['%d', '%s', '%s', '%s', '%s', '%s', '%s']
		);

		if ($result) {
			$this->trigger_real_time_update($notification_data['user_id']);
			$this->send_push_notification($notification_data);
			$this->log('notification_sent', $notification_data);
		}

		return $result !== false;
	}

	private function send_bulk(array $base_data): bool {
		$user_ids = $base_data['user_ids'] ?? [];
		unset($base_data['user_ids']);

		$sent_count = 0;
		foreach ($user_ids as $user_id) {
			$data = array_merge($base_data, ['user_id' => $user_id]);
			if ($this->send_single($data)) {
				$sent_count++;
			}
		}

		return $sent_count > 0;
	}

	private function user_wants_notification(int $user_id, string $type): bool {
		$preferences = $this->get_user_preferences($user_id);
		return $preferences[$type]['in_app_enabled'] ?? true;
	}

	public function get_notifications(int $user_id, array $filters = []): array {
		global $wpdb;

		$where = ['user_id = %d'];
		$args = [$user_id];

		if (isset($filters['is_read'])) {
			$where[] = 'is_read = %d';
			$args[] = (int) $filters['is_read'];
		}

		if (isset($filters['type'])) {
			$where[] = 'type = %s';
			$args[] = $filters['type'];
		}

		if (isset($filters['limit'])) {
			$limit = ' LIMIT %d';
			$args[] = (int) $filters['limit'];
		} else {
			$limit = ' LIMIT 50';
		}

		$query = "SELECT * FROM {$wpdb->prefix}" . self::TABLE_NOTIFICATIONS . "
				 WHERE " . implode(' AND ', $where) . "
				 ORDER BY priority DESC, created_at DESC" . $limit;

		$notifications = $wpdb->get_results($wpdb->prepare($query, $args));

		foreach ($notifications as &$notification) {
			$notification->data = maybe_unserialize($notification->data);
		}

		return $notifications;
	}

	public function mark_read(int $notification_id, int $user_id): bool {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . self::TABLE_NOTIFICATIONS,
			['is_read' => 1, 'read_at' => current_time('mysql')],
			['id' => $notification_id, 'user_id' => $user_id],
			['%d', '%s'],
			['%d', '%d']
		);

		if ($result) {
			$this->update_unread_count($user_id);
		}

		return $result !== false;
	}

	public function mark_all_read(int $user_id): bool {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . self::TABLE_NOTIFICATIONS,
			['is_read' => 1, 'read_at' => current_time('mysql')],
			['user_id' => $user_id, 'is_read' => 0],
			['%d', '%s'],
			['%d', '%d']
		);

		if ($result) {
			$this->update_unread_count($user_id);
		}

		return $result !== false;
	}

	private function update_unread_count(int $user_id): void {
		$unread_count = $this->get_unread_count($user_id);
		update_user_meta($user_id, 'apollo_unread_notifications', $unread_count);
	}

	public function get_unread_count(int $user_id): int {
		global $wpdb;

		return (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE_NOTIFICATIONS . "
			 WHERE user_id = %d AND is_read = 0 AND (expires_at IS NULL OR expires_at > %s)",
			$user_id, current_time('mysql')
		));
	}

	public function get_user_preferences(int $user_id): array {
		if (isset($this->preferences_cache[$user_id])) {
			return $this->preferences_cache[$user_id];
		}

		global $wpdb;

		$preferences = $wpdb->get_results($wpdb->prepare(
			"SELECT type, email_enabled, push_enabled, in_app_enabled
			 FROM {$wpdb->prefix}" . self::TABLE_PREFERENCES . "
			 WHERE user_id = %d",
			$user_id
		), OBJECT_K);

		$default_types = ['event_created', 'event_updated', 'event_cancelled', 'friend_request', 'message_received', 'system_alert'];

		$formatted_preferences = [];
		foreach ($default_types as $type) {
			$formatted_preferences[$type] = [
				'email_enabled' => $preferences[$type]->email_enabled ?? true,
				'push_enabled' => $preferences[$type]->push_enabled ?? true,
				'in_app_enabled' => $preferences[$type]->in_app_enabled ?? true
			];
		}

		$this->preferences_cache[$user_id] = $formatted_preferences;
		return $formatted_preferences;
	}

	public function update_user_preferences(int $user_id, array $preferences): bool {
		global $wpdb;

		$success = true;
		foreach ($preferences as $type => $settings) {
			$result = $wpdb->replace(
				$wpdb->prefix . self::TABLE_PREFERENCES,
				[
					'user_id' => $user_id,
					'type' => $type,
					'email_enabled' => (int) ($settings['email_enabled'] ?? true),
					'push_enabled' => (int) ($settings['push_enabled'] ?? true),
					'in_app_enabled' => (int) ($settings['in_app_enabled'] ?? true)
				],
				['%d', '%s', '%d', '%d', '%d']
			);
			if ($result === false) {
				$success = false;
			}
		}

		if ($success) {
			unset($this->preferences_cache[$user_id]);
		}

		return $success;
	}

	private function trigger_real_time_update(int $user_id): void {
		if (!class_exists('WP_WebSocket')) return;

		$unread_count = $this->get_unread_count($user_id);
		$message = [
			'type' => 'notification_update',
			'unread_count' => $unread_count,
			'user_id' => $user_id
		];

		do_action('apollo_websocket_broadcast', $message, [$user_id]);
	}

	private function send_push_notification(array $notification_data): void {
		if (!$this->push_enabled) return;

		$push_data = [
			'user_id' => $notification_data['user_id'],
			'title' => $notification_data['title'],
			'body' => wp_strip_all_tags($notification_data['message']),
			'data' => $notification_data['data']
		];

		do_action('apollo_send_push_notification', $push_data);
	}

	public function enqueue_scripts(): void {
		if (!is_user_logged_in()) return;

		wp_enqueue_script('apollo-notifications', plugins_url('assets/js/notifications.js', __FILE__), ['jquery'], '1.0.0', true);
		wp_localize_script('apollo-notifications', 'apolloNotifications', [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('apollo_notifications'),
			'unread_count' => $this->get_unread_count(get_current_user_id())
		]);
	}

	public function ajax_get_notifications(): void {
		check_ajax_referer('apollo_notifications', 'nonce');

		$user_id = get_current_user_id();
		if (!$user_id) {
			wp_send_json_error('Not logged in');
		}

		$filters = [
			'is_read' => isset($_POST['is_read']) ? (int) $_POST['is_read'] : null,
			'type' => sanitize_key($_POST['type'] ?? ''),
			'limit' => (int) ($_POST['limit'] ?? 20)
		];

		$notifications = $this->get_notifications($user_id, array_filter($filters));

		wp_send_json_success([
			'notifications' => $notifications,
			'unread_count' => $this->get_unread_count($user_id)
		]);
	}

	public function ajax_mark_read(): void {
		check_ajax_referer('apollo_notifications', 'nonce');

		$user_id = get_current_user_id();
		$notification_id = (int) ($_POST['notification_id'] ?? 0);

		if (!$user_id || !$notification_id) {
			wp_send_json_error('Invalid request');
		}

		if ($this->mark_read($notification_id, $user_id)) {
			wp_send_json_success([
				'unread_count' => $this->get_unread_count($user_id)
			]);
		} else {
			wp_send_json_error('Failed to mark as read');
		}
	}

	public function ajax_update_preferences(): void {
		check_ajax_referer('apollo_notifications', 'nonce');

		$user_id = get_current_user_id();
		if (!$user_id) {
			wp_send_json_error('Not logged in');
		}

		$preferences = $_POST['preferences'] ?? [];
		if (empty($preferences) || !is_array($preferences)) {
			wp_send_json_error('Invalid preferences');
		}

		$sanitized_preferences = [];
		foreach ($preferences as $type => $settings) {
			$sanitized_preferences[sanitize_key($type)] = [
				'email_enabled' => (bool) ($settings['email_enabled'] ?? true),
				'push_enabled' => (bool) ($settings['push_enabled'] ?? true),
				'in_app_enabled' => (bool) ($settings['in_app_enabled'] ?? true)
			];
		}

		if ($this->update_user_preferences($user_id, $sanitized_preferences)) {
			wp_send_json_success('Preferences updated');
		} else {
			wp_send_json_error('Failed to update preferences');
		}
	}

	public function admin_page(): void {
		global $wpdb;

		$stats = $wpdb->get_row($wpdb->prepare(
			"SELECT
				COUNT(*) as total_notifications,
				SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications,
				COUNT(DISTINCT user_id) as active_users
			 FROM {$wpdb->prefix}" . self::TABLE_NOTIFICATIONS . "
			 WHERE created_at >= %s",
			date('Y-m-d H:i:s', strtotime('-30 days'))
		));

		?>
		<div class="wrap">
			<h1>Notification Management</h1>

			<div class="apollo-notification-stats">
				<div class="stat-box">
					<h3>Total Notifications (30 days)</h3>
					<span class="stat-number"><?php echo number_format($stats->total_notifications ?? 0); ?></span>
				</div>
				<div class="stat-box">
					<h3>Unread Notifications</h3>
					<span class="stat-number"><?php echo number_format($stats->unread_notifications ?? 0); ?></span>
				</div>
				<div class="stat-box">
					<h3>Active Users</h3>
					<span class="stat-number"><?php echo number_format($stats->active_users ?? 0); ?></span>
				</div>
			</div>

			<div class="apollo-notification-settings">
				<h2>Push Notification Settings</h2>
				<form method="post" action="options.php">
					<?php settings_fields('apollo_push_settings'); ?>
					<table class="form-table">
						<tr>
							<th>Enable Push Notifications</th>
							<td><input type="checkbox" name="apollo_push_enabled" value="1" <?php checked($this->push_enabled); ?>></td>
						</tr>
					</table>
					<?php submit_button('Save Settings'); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
