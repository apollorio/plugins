<?php
/**
 * Apollo Email - Dev/Test UI
 *
 * Test interface for email service functionality.
 * Only accessible when WP_DEBUG is enabled.
 *
 * URL: /dev/email
 *
 * @package ApolloEmail
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security check: Only allow in debug mode.
if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
	wp_die( 'Access denied. Enable WP_DEBUG to use dev routes.' );
}

// Ensure user is logged in and has admin capabilities.
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Admin access required.' );
}

// Handle form submissions.
$test_result = null;
$queue_result = null;
$template_preview = null;

if ( isset( $_POST['send_test_email'] ) && check_admin_referer( 'apollo_email_dev_test' ) ) {
	$to = sanitize_email( $_POST['test_email'] ?? get_option( 'admin_email' ) );
	$subject = sanitize_text_field( $_POST['test_subject'] ?? 'Apollo Email Test' );
	$body = wp_kses_post( $_POST['test_body'] ?? '<h1>Test Email</h1><p>Sent at ' . current_time( 'mysql' ) . '</p>' );

	try {
		$test_result = apollo_send_email( $to, $subject, $body, [ 'priority' => 'high' ] );
	} catch ( \Exception $e ) {
		$test_result = [ 'error' => $e->getMessage() ];
	}
}

if ( isset( $_POST['queue_test_email'] ) && check_admin_referer( 'apollo_email_dev_test' ) ) {
	try {
		$queue_result = apollo_queue_email(
			[
				'recipient_email' => get_option( 'admin_email' ),
				'subject'         => 'Queued Email Test',
				'body'            => '<p>This email was queued at ' . current_time( 'mysql' ) . '</p>',
				'priority'        => 'normal',
			]
		);
	} catch ( \Exception $e ) {
		$queue_result = [ 'error' => $e->getMessage() ];
	}
}

if ( isset( $_POST['preview_template'] ) && check_admin_referer( 'apollo_email_dev_test' ) ) {
	try {
		$template_preview = apollo_render_email_template(
			'test-template',
			[
				'user_name'  => 'João Silva',
				'event_title' => 'Summer Festival 2024',
				'event_date' => '15 de Julho de 2024',
			]
		);
	} catch ( \Exception $e ) {
		$template_preview = '<p style="color: red;">Error: ' . esc_html( $e->getMessage() ) . '</p>';
	}
}

// Get queue stats.
global $wpdb;
$queue_stats = [];
try {
	$queue_stats = [
		'pending'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'pending'" ),
		'processing' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'processing'" ),
		'sent'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'sent'" ),
		'failed'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'failed'" ),
		'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue" ),
	];
} catch ( \Exception $e ) {
	$queue_stats['error'] = $e->getMessage();
}

// Get recent security logs.
$security_logs = [];
try {
	$security_logs = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}apollo_email_security_log
		ORDER BY created_at DESC
		LIMIT 10",
		ARRAY_A
	);
} catch ( \Exception $e ) {
	$security_logs = [ [ 'error' => $e->getMessage() ] ];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Apollo Email - Dev UI</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: #f0f2f5;
			padding: 20px;
		}
		.container {
			max-width: 1200px;
			margin: 0 auto;
		}
		h1 {
			font-size: 32px;
			font-weight: 700;
			margin-bottom: 10px;
			color: #1a1a1a;
		}
		.subtitle {
			font-size: 14px;
			color: #666;
			margin-bottom: 30px;
		}
		.grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-bottom: 20px;
		}
		.card {
			background: white;
			border-radius: 8px;
			padding: 20px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		}
		.card h2 {
			font-size: 18px;
			font-weight: 600;
			margin-bottom: 15px;
			color: #1a1a1a;
			border-bottom: 2px solid #007cba;
			padding-bottom: 10px;
		}
		.stats {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 15px;
			margin-bottom: 15px;
		}
		.stat-box {
			background: #f9f9f9;
			border-radius: 6px;
			padding: 15px;
			text-align: center;
		}
		.stat-box .label {
			font-size: 12px;
			color: #666;
			text-transform: uppercase;
			margin-bottom: 5px;
		}
		.stat-box .value {
			font-size: 32px;
			font-weight: 700;
			color: #007cba;
		}
		.stat-box.pending .value { color: #f0b429; }
		.stat-box.failed .value { color: #d63638; }
		.stat-box.sent .value { color: #00a32a; }
		.form-group {
			margin-bottom: 15px;
		}
		label {
			display: block;
			font-size: 14px;
			font-weight: 600;
			margin-bottom: 5px;
			color: #1a1a1a;
		}
		input[type="email"],
		input[type="text"],
		textarea {
			width: 100%;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
		}
		textarea {
			min-height: 100px;
			font-family: monospace;
		}
		button {
			background: #007cba;
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 4px;
			font-size: 14px;
			font-weight: 600;
			cursor: pointer;
			transition: background 0.2s;
		}
		button:hover {
			background: #005a87;
		}
		.result {
			margin-top: 15px;
			padding: 15px;
			border-radius: 4px;
			font-size: 14px;
		}
		.result.success {
			background: #d5f5e3;
			color: #00652a;
			border: 1px solid #00a32a;
		}
		.result.error {
			background: #fde7e7;
			color: #8b0000;
			border: 1px solid #d63638;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}
		table th {
			background: #f9f9f9;
			padding: 10px;
			text-align: left;
			font-weight: 600;
			border-bottom: 2px solid #ddd;
		}
		table td {
			padding: 10px;
			border-bottom: 1px solid #eee;
		}
		.badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.badge.info { background: #d0e5f5; color: #005a87; }
		.badge.warning { background: #fef0d5; color: #8b6914; }
		.badge.error { background: #fde7e7; color: #8b0000; }
		.badge.success { background: #d5f5e3; color: #00652a; }
		pre {
			background: #f9f9f9;
			padding: 15px;
			border-radius: 4px;
			overflow-x: auto;
			font-size: 13px;
			border: 1px solid #ddd;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🚀 Apollo Email Service - Dev UI</h1>
		<p class="subtitle">URL: <code>/dev/email</code> • Plugin Version: <?php echo esc_html( APOLLO_EMAIL_VERSION ); ?></p>

		<!-- Queue Statistics -->
		<div class="grid">
			<div class="card">
				<h2>📊 Queue Statistics</h2>
				<?php if ( isset( $queue_stats['error'] ) ) : ?>
					<div class="result error">
						<strong>Error:</strong> <?php echo esc_html( $queue_stats['error'] ); ?>
					</div>
				<?php else : ?>
					<div class="stats">
						<div class="stat-box pending">
							<div class="label">Pending</div>
							<div class="value"><?php echo esc_html( $queue_stats['pending'] ); ?></div>
						</div>
						<div class="stat-box">
							<div class="label">Processing</div>
							<div class="value"><?php echo esc_html( $queue_stats['processing'] ); ?></div>
						</div>
						<div class="stat-box sent">
							<div class="label">Sent</div>
							<div class="value"><?php echo esc_html( $queue_stats['sent'] ); ?></div>
						</div>
						<div class="stat-box failed">
							<div class="label">Failed</div>
							<div class="value"><?php echo esc_html( $queue_stats['failed'] ); ?></div>
						</div>
					</div>
					<p style="text-align: center; color: #666; font-size: 14px;">
						Total: <strong><?php echo esc_html( $queue_stats['total'] ); ?></strong> emails in queue
					</p>
				<?php endif; ?>
			</div>

			<!-- Send Test Email -->
			<div class="card">
				<h2>📧 Send Test Email</h2>
				<form method="post">
					<?php wp_nonce_field( 'apollo_email_dev_test' ); ?>
					<div class="form-group">
						<label for="test_email">Recipient Email</label>
						<input type="email" id="test_email" name="test_email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required>
					</div>
					<div class="form-group">
						<label for="test_subject">Subject</label>
						<input type="text" id="test_subject" name="test_subject" value="Apollo Email Test" required>
					</div>
					<div class="form-group">
						<label for="test_body">Body (HTML)</label>
						<textarea id="test_body" name="test_body"><h1>Test Email</h1><p>Sent at <?php echo esc_html( current_time( 'mysql' ) ); ?></p></textarea>
					</div>
					<button type="submit" name="send_test_email">Send Now</button>
				</form>
				<?php if ( null !== $test_result ) : ?>
					<div class="result <?php echo $test_result ? 'success' : 'error'; ?>">
						<?php if ( $test_result ) : ?>
							<strong>✅ Success:</strong> Email sent successfully!
						<?php else : ?>
							<strong>❌ Failed:</strong> <?php echo esc_html( $test_result['error'] ?? 'Unknown error' ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Queue Test Email -->
			<div class="card">
				<h2>⏱️ Queue Test Email</h2>
				<form method="post">
					<?php wp_nonce_field( 'apollo_email_dev_test' ); ?>
					<p style="margin-bottom: 15px; color: #666; font-size: 14px;">
						Queue an email for background processing. Will be sent by cron job.
					</p>
					<button type="submit" name="queue_test_email">Queue Email</button>
				</form>
				<?php if ( null !== $queue_result ) : ?>
					<div class="result <?php echo is_numeric( $queue_result ) ? 'success' : 'error'; ?>">
						<?php if ( is_numeric( $queue_result ) ) : ?>
							<strong>✅ Queued:</strong> Email #<?php echo esc_html( $queue_result ); ?> added to queue
						<?php else : ?>
							<strong>❌ Failed:</strong> <?php echo esc_html( $queue_result['error'] ?? 'Unknown error' ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Template Preview -->
			<div class="card">
				<h2>🎨 Template Preview</h2>
				<form method="post">
					<?php wp_nonce_field( 'apollo_email_dev_test' ); ?>
					<p style="margin-bottom: 15px; color: #666; font-size: 14px;">
						Preview email template with mock data.
					</p>
					<button type="submit" name="preview_template">Preview Template</button>
				</form>
				<?php if ( null !== $template_preview ) : ?>
					<div style="margin-top: 15px;">
						<strong>Preview:</strong>
						<div style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-top: 10px;">
							<?php echo $template_preview; // Already escaped by render function ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Recent Security Logs -->
		<div class="card">
			<h2>🔒 Recent Security Logs (Last 10)</h2>
			<?php if ( isset( $security_logs[0]['error'] ) ) : ?>
				<div class="result error">
					<strong>Error:</strong> <?php echo esc_html( $security_logs[0]['error'] ); ?>
				</div>
			<?php elseif ( empty( $security_logs ) ) : ?>
				<p style="color: #666;">No security logs found.</p>
			<?php else : ?>
				<table>
					<thead>
						<tr>
							<th>Type</th>
							<th>Severity</th>
							<th>User</th>
							<th>IP</th>
							<th>Date</th>
							<th>Message</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $security_logs as $log ) : ?>
							<tr>
								<td><span class="badge info"><?php echo esc_html( $log['event_type'] ?? 'N/A' ); ?></span></td>
								<td>
									<?php
									$severity = $log['severity'] ?? 'info';
									$badge_class = 'info';
									if ( 'error' === $severity || 'critical' === $severity ) {
										$badge_class = 'error';
									} elseif ( 'warning' === $severity ) {
										$badge_class = 'warning';
									}
									?>
									<span class="badge <?php echo esc_attr( $badge_class ); ?>">
										<?php echo esc_html( $severity ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $log['user_id'] ?? 'N/A' ); ?></td>
								<td><?php echo esc_html( $log['ip_address'] ?? 'N/A' ); ?></td>
								<td><?php echo esc_html( $log['created_at'] ?? 'N/A' ); ?></td>
								<td><?php echo esc_html( $log['description'] ?? 'N/A' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<!-- System Info -->
		<div class="card">
			<h2>ℹ️ System Info</h2>
			<pre><?php
			echo 'WordPress Version: ' . esc_html( get_bloginfo( 'version' ) ) . "\n";
			echo 'PHP Version: ' . esc_html( PHP_VERSION ) . "\n";
			echo 'Apollo Email Version: ' . esc_html( APOLLO_EMAIL_VERSION ) . "\n";
			echo 'WP_DEBUG: ' . ( WP_DEBUG ? 'Enabled' : 'Disabled' ) . "\n";
			echo 'Admin Email: ' . esc_html( get_option( 'admin_email' ) ) . "\n";
			echo 'Site URL: ' . esc_html( get_site_url() ) . "\n";
			?></pre>
		</div>
	</div>
</body>
</html>
