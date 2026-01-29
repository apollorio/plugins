<?php
/**
 * Analytics Settings Tab
 *
 * Admin controls for analytics tracking and user visibility.
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings.
$tracking_enabled = get_option( 'apollo_analytics_enabled', true );
$heatmap_enabled  = get_option( 'apollo_analytics_heatmap_enabled', false );
$track_admins     = get_option( 'apollo_analytics_track_admins', false );

$admin_override = get_option(
	'apollo_analytics_admin_override',
	array(
		'force_visibility'  => false,
		'default_show_to'   => 'self',
		'allow_user_change' => true,
	)
);

// Get database stats.
global $wpdb;
$pageviews_table    = $wpdb->prefix . 'apollo_analytics_pageviews';
$interactions_table = $wpdb->prefix . 'apollo_analytics_interactions';
$sessions_table     = $wpdb->prefix . 'apollo_analytics_sessions';
$heatmap_table      = $wpdb->prefix . 'apollo_analytics_heatmap';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$db_stats = array(
	'pageviews'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $pageviews_table" ),
	'interactions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $interactions_table" ),
	'sessions'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table" ),
	'heatmap'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $heatmap_table" ),
);

$total_records = array_sum( $db_stats );

?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
	<div>
		<div class="apollo-chart-container">
			<h3><?php esc_html_e( 'Tracking Settings', 'apollo-core' ); ?></h3>
			<form method="post">
				<?php wp_nonce_field( 'apollo_analytics_settings', 'apollo_analytics_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="tracking_enabled"><?php esc_html_e( 'Enable Analytics Tracking', 'apollo-core' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="tracking_enabled" id="tracking_enabled" value="1" <?php checked( $tracking_enabled ); ?>>
								<?php esc_html_e( 'Track page views, sessions, and user interactions', 'apollo-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Master switch for all analytics tracking. Disabling stops all data collection.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="heatmap_enabled"><?php esc_html_e( 'Enable Heatmap Tracking', 'apollo-core' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="heatmap_enabled" id="heatmap_enabled" value="1" <?php checked( $heatmap_enabled ); ?>>
								<?php esc_html_e( 'Track mouse movements and click positions', 'apollo-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Collects more data. Useful for UX analysis but increases storage.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="track_admins"><?php esc_html_e( 'Track Administrators', 'apollo-core' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="track_admins" id="track_admins" value="1" <?php checked( $track_admins ); ?>>
								<?php esc_html_e( 'Include admin users in analytics data', 'apollo-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'By default, admin activity is excluded from statistics.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h3 style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
					<?php esc_html_e( 'User Stats Visibility Controls', 'apollo-core' ); ?>
				</h3>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Control what statistics users can see about themselves and whether they can share their stats.', 'apollo-core' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="force_visibility"><?php esc_html_e( 'Force Visibility Settings', 'apollo-core' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="force_visibility" id="force_visibility" value="1" <?php checked( $admin_override['force_visibility'] ?? false ); ?>>
								<?php esc_html_e( 'Override user visibility preferences', 'apollo-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, users cannot change their stats visibility - admin controls apply.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_show_to"><?php esc_html_e( 'Default Visibility', 'apollo-core' ); ?></label>
						</th>
						<td>
							<select name="default_show_to" id="default_show_to">
								<option value="self" <?php selected( $admin_override['default_show_to'] ?? 'self', 'self' ); ?>>
									<?php esc_html_e( 'Private (Only user sees their stats)', 'apollo-core' ); ?>
								</option>
								<option value="followers" <?php selected( $admin_override['default_show_to'] ?? 'self', 'followers' ); ?>>
									<?php esc_html_e( 'Followers (User and their followers)', 'apollo-core' ); ?>
								</option>
								<option value="public" <?php selected( $admin_override['default_show_to'] ?? 'self', 'public' ); ?>>
									<?php esc_html_e( 'Public (Everyone can see)', 'apollo-core' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Default visibility level for new users or when forcing visibility.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="allow_user_change"><?php esc_html_e( 'Allow User Changes', 'apollo-core' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="allow_user_change" id="allow_user_change" value="1" <?php checked( $admin_override['allow_user_change'] ?? true ); ?>>
								<?php esc_html_e( 'Users can change their own visibility settings', 'apollo-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'If disabled with Force Visibility off, users keep their current settings but cannot change.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'apollo-core' ); ?>
					</button>
				</p>
			</form>
		</div>
	</div>

	<div>
		<div class="apollo-chart-container">
			<h3><?php esc_html_e( 'Database Statistics', 'apollo-core' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Table', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Records', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Page Views', 'apollo-core' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $db_stats['pageviews'] ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Interactions', 'apollo-core' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $db_stats['interactions'] ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Sessions', 'apollo-core' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $db_stats['sessions'] ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Heatmap Points', 'apollo-core' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $db_stats['heatmap'] ) ); ?></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'Total Records', 'apollo-core' ); ?></th>
						<th><?php echo esc_html( number_format_i18n( $total_records ) ); ?></th>
					</tr>
				</tfoot>
			</table>
		</div>

		<div class="apollo-chart-container" style="margin-top: 20px;">
			<h3><?php esc_html_e( 'Data Maintenance', 'apollo-core' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Raw data older than 90 days is automatically cleaned up. Aggregated stats are kept indefinitely.', 'apollo-core' ); ?>
			</p>

			<form method="post" style="margin-top: 15px;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure? This will permanently delete the selected data.', 'apollo-core' ); ?>');">
				<?php wp_nonce_field( 'apollo_analytics_cleanup', 'apollo_analytics_cleanup_nonce' ); ?>

				<p>
					<label>
						<input type="checkbox" name="cleanup_pageviews" value="1">
						<?php esc_html_e( 'Clear all page views', 'apollo-core' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="cleanup_interactions" value="1">
						<?php esc_html_e( 'Clear all interactions', 'apollo-core' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="cleanup_sessions" value="1">
						<?php esc_html_e( 'Clear all sessions', 'apollo-core' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="cleanup_heatmap" value="1">
						<?php esc_html_e( 'Clear all heatmap data', 'apollo-core' ); ?>
					</label>
				</p>

				<p class="submit">
					<button type="submit" name="cleanup_action" value="1" class="button button-secondary">
						<?php esc_html_e( 'Clean Selected Data', 'apollo-core' ); ?>
					</button>
				</p>
			</form>
		</div>

		<div class="apollo-chart-container" style="margin-top: 20px;">
			<h3><?php esc_html_e( 'Export Data', 'apollo-core' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Export analytics data for backup or analysis.', 'apollo-core' ); ?>
			</p>
			<p style="margin-top: 15px;">
				<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=apollo_export_analytics&type=csv&_wpnonce=' . wp_create_nonce( 'apollo_export' ) ) ); ?>" class="button">
					<?php esc_html_e( 'Export as CSV', 'apollo-core' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=apollo_export_analytics&type=json&_wpnonce=' . wp_create_nonce( 'apollo_export' ) ) ); ?>" class="button">
					<?php esc_html_e( 'Export as JSON', 'apollo-core' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>

<?php
// Handle cleanup action.
if ( isset( $_POST['cleanup_action'] ) && isset( $_POST['apollo_analytics_cleanup_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_analytics_cleanup_nonce'] ) ), 'apollo_analytics_cleanup' ) ) {
	$cleaned = array();

	if ( ! empty( $_POST['cleanup_pageviews'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE $pageviews_table" );
		$cleaned[] = __( 'Page Views', 'apollo-core' );
	}
	if ( ! empty( $_POST['cleanup_interactions'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE $interactions_table" );
		$cleaned[] = __( 'Interactions', 'apollo-core' );
	}
	if ( ! empty( $_POST['cleanup_sessions'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE $sessions_table" );
		$cleaned[] = __( 'Sessions', 'apollo-core' );
	}
	if ( ! empty( $_POST['cleanup_heatmap'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE $heatmap_table" );
		$cleaned[] = __( 'Heatmap', 'apollo-core' );
	}

	if ( ! empty( $cleaned ) ) {
		echo '<div class="notice notice-success"><p>';
		// translators: %s: list of cleaned data types.
		printf( esc_html__( 'Successfully cleaned: %s', 'apollo-core' ), esc_html( implode( ', ', $cleaned ) ) );
		echo '</p></div>';
	}
}
?>
