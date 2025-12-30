<?php
/**
 * Apollo Advanced Analytics Dashboard
 *
 * Admin page for viewing analytics data.
 * Admins can see all stats; users see only what admin allows.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Verify admin access.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Access denied.', 'apollo-core' ) );
}

// Get current tab.
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'overview';

// Handle settings save.
if ( isset( $_POST['apollo_analytics_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_analytics_settings_nonce'] ) ), 'apollo_analytics_settings' ) ) {
	update_option( 'apollo_analytics_enabled', isset( $_POST['tracking_enabled'] ) ? 1 : 0 );
	update_option( 'apollo_analytics_heatmap_enabled', isset( $_POST['heatmap_enabled'] ) ? 1 : 0 );
	update_option( 'apollo_analytics_track_admins', isset( $_POST['track_admins'] ) ? 1 : 0 );

	$visibility_override = array(
		'force_visibility'  => isset( $_POST['force_visibility'] ) ? 1 : 0,
		'default_show_to'   => sanitize_text_field( wp_unslash( $_POST['default_show_to'] ?? 'self' ) ),
		'allow_user_change' => isset( $_POST['allow_user_change'] ) ? 1 : 0,
	);
	update_option( 'apollo_analytics_admin_override', $visibility_override );

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'apollo-core' ) . '</p></div>';
}

// Get stats.
$realtime = \Apollo_Core\Analytics::get_realtime_stats();

// Get period.
$period = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : 'week';

?>
<div class="wrap apollo-analytics-dashboard">
	<h1>
		<span class="dashicons dashicons-chart-area"></span>
		<?php esc_html_e( 'Apollo Advanced Analytics', 'apollo-core' ); ?>
	</h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=overview' ) ); ?>"
			class="nav-tab <?php echo 'overview' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Overview', 'apollo-core' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=realtime' ) ); ?>"
			class="nav-tab <?php echo 'realtime' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Realtime', 'apollo-core' ); ?>
			<span class="realtime-badge"><?php echo esc_html( $realtime['active_users'] ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=ranking' ) ); ?>"
			class="nav-tab <?php echo 'ranking' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Ranking', 'apollo-core' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=content' ) ); ?>"
			class="nav-tab <?php echo 'content' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Content', 'apollo-core' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=users' ) ); ?>"
			class="nav-tab <?php echo 'users' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Users', 'apollo-core' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=heatmaps' ) ); ?>"
			class="nav-tab <?php echo 'heatmaps' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Heatmaps', 'apollo-core' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=settings' ) ); ?>"
			class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Settings', 'apollo-core' ); ?>
		</a>
	</nav>

	<div class="tab-content" style="margin-top: 20px;">
		<?php
		switch ( $current_tab ) {
			case 'realtime':
				include __DIR__ . '/analytics-tabs/realtime.php';
				break;
			case 'ranking':
				include __DIR__ . '/analytics-tabs/ranking.php';
				break;
			case 'content':
				include __DIR__ . '/analytics-tabs/content.php';
				break;
			case 'users':
				include __DIR__ . '/analytics-tabs/users.php';
				break;
			case 'heatmaps':
				include __DIR__ . '/analytics-tabs/heatmaps.php';
				break;
			case 'settings':
				include __DIR__ . '/analytics-tabs/settings.php';
				break;
			default:
				include __DIR__ . '/analytics-tabs/overview.php';
				break;
		}
		?>
	</div>
</div>

<style>
.apollo-analytics-dashboard {
	max-width: 1400px;
}

.apollo-analytics-dashboard h1 {
	display: flex;
	align-items: center;
	gap: 10px;
}

.apollo-analytics-dashboard .realtime-badge {
	background: #e74c3c;
	color: #fff;
	font-size: 11px;
	padding: 2px 6px;
	border-radius: 10px;
	margin-left: 5px;
	animation: pulse 2s infinite;
}

@keyframes pulse {

	0%,
	100% {
		opacity: 1;
	}

	50% {
		opacity: 0.6;
	}
}

.apollo-stat-cards {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.apollo-stat-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	text-align: center;
}

.apollo-stat-card .stat-icon {
	font-size: 32px;
	color: #3498db;
	margin-bottom: 10px;
}

.apollo-stat-card .stat-value {
	font-size: 36px;
	font-weight: bold;
	color: #2c3e50;
	line-height: 1;
}

.apollo-stat-card .stat-label {
	color: #7f8c8d;
	margin-top: 5px;
	font-size: 13px;
}

.apollo-stat-card .stat-change {
	margin-top: 10px;
	font-size: 12px;
}

.apollo-stat-card .stat-change.positive {
	color: #27ae60;
}

.apollo-stat-card .stat-change.negative {
	color: #e74c3c;
}

.apollo-chart-container {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 30px;
}

.apollo-chart-container h3 {
	margin-top: 0;
	color: #2c3e50;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
}

.apollo-data-table {
	width: 100%;
	border-collapse: collapse;
	background: #fff;
	border-radius: 8px;
	overflow: hidden;
}

.apollo-data-table th,
.apollo-data-table td {
	padding: 12px 15px;
	text-align: left;
	border-bottom: 1px solid #eee;
}

.apollo-data-table th {
	background: #f8f9fa;
	font-weight: 600;
	color: #2c3e50;
}

.apollo-data-table tr:hover {
	background: #f8f9fa;
}

.apollo-period-filter {
	display: flex;
	gap: 10px;
	margin-bottom: 20px;
}

.apollo-period-filter a {
	padding: 8px 16px;
	text-decoration: none;
	border-radius: 4px;
	background: #f0f0f0;
	color: #333;
}

.apollo-period-filter a.active {
	background: #3498db;
	color: #fff;
}

.apollo-heatmap-container {
	position: relative;
	background: #f5f5f5;
	border-radius: 8px;
	overflow: hidden;
}

.apollo-heatmap-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
}

.apollo-heatmap-point {
	position: absolute;
	width: 20px;
	height: 20px;
	border-radius: 50%;
	transform: translate(-50%, -50%);
	opacity: 0.6;
	background: radial-gradient(circle, rgba(255, 0, 0, 0.8) 0%, rgba(255, 0, 0, 0) 70%);
}
</style>
