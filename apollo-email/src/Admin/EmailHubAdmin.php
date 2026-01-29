<?php
/**
 * Email Hub Admin
 *
 * Admin interface for email management.
 *
 * @package ApolloEmail\Admin
 */

declare(strict_types=1);

namespace ApolloEmail\Admin;

/**
 * Email Hub Admin Class
 */
class EmailHubAdmin {

	/**
	 * Instance
	 *
	 * @var EmailHubAdmin|null
	 */
	private static ?EmailHubAdmin $instance = null;

	/**
	 * Get instance
	 *
	 * @return EmailHubAdmin
	 */
	public static function get_instance(): EmailHubAdmin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Email Hub', 'apollo-email' ),
			__( 'Email Hub', 'apollo-email' ),
			'manage_options',
			'apollo-email-hub',
			array( $this, 'render_admin_page' ),
			'dashicons-email',
			30
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Email Hub', 'apollo-email' ); ?></h1>
			<p><?php esc_html_e( 'Manage email settings, queue, and templates.', 'apollo-email' ); ?></p>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Dev/Test UI:', 'apollo-email' ); ?></strong>
					<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
						<a href="<?php echo esc_url( site_url( '/dev/email' ) ); ?>" target="_blank">
							<?php esc_html_e( 'Open Dev UI', 'apollo-email' ); ?>
						</a>
					<?php else : ?>
						<?php esc_html_e( 'Enable WP_DEBUG to access dev UI', 'apollo-email' ); ?>
					<?php endif; ?>
				</p>
			</div>

			<h2><?php esc_html_e( 'Email Queue Status', 'apollo-email' ); ?></h2>
			<?php $this->render_queue_stats(); ?>

			<h2><?php esc_html_e( 'SMTP Settings', 'apollo-email' ); ?></h2>
			<p><?php esc_html_e( 'Configure SMTP settings here (coming soon).', 'apollo-email' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render queue statistics
	 */
	private function render_queue_stats(): void {
		$queue_manager = \ApolloEmail\Queue\QueueManager::get_instance();
		$stats = $queue_manager->get_stats();
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Status', 'apollo-email' ); ?></th>
					<th><?php esc_html_e( 'Count', 'apollo-email' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Pending', 'apollo-email' ); ?></td>
					<td><strong><?php echo esc_html( $stats['pending'] ); ?></strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Processing', 'apollo-email' ); ?></td>
					<td><?php echo esc_html( $stats['processing'] ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Sent', 'apollo-email' ); ?></td>
					<td><?php echo esc_html( $stats['sent'] ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Failed', 'apollo-email' ); ?></td>
					<td style="color: #d63638;"><strong><?php echo esc_html( $stats['failed'] ); ?></strong></td>
				</tr>
				<tr style="border-top: 2px solid #ddd;">
					<td><strong><?php esc_html_e( 'Total', 'apollo-email' ); ?></strong></td>
					<td><strong><?php echo esc_html( $stats['total'] ); ?></strong></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
}
