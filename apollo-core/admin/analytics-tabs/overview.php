<?php
/**
 * Analytics Overview Tab
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get period stats.
$period = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : 'week';

// Get date range based on period.
switch ( $period ) {
	case 'today':
		$start_date = current_time( 'Y-m-d' );
		$end_date   = $start_date;
		break;
	case 'month':
		$start_date = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date   = current_time( 'Y-m-d' );
		break;
	case 'year':
		$start_date = gmdate( 'Y-m-d', strtotime( '-365 days' ) );
		$end_date   = current_time( 'Y-m-d' );
		break;
	default: // week
		$start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$end_date   = current_time( 'Y-m-d' );
		break;
}

// Get totals.
$sessions_table   = $wpdb->prefix . 'apollo_analytics_sessions';
$pageviews_table  = $wpdb->prefix . 'apollo_analytics_pageviews';
$interactions_table = $wpdb->prefix . 'apollo_analytics_interactions';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_sessions = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $sessions_table WHERE DATE(started_at) BETWEEN %s AND %s",
		$start_date,
		$end_date
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_pageviews = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $pageviews_table WHERE DATE(created_at) BETWEEN %s AND %s",
		$start_date,
		$end_date
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$unique_users = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) FROM $pageviews_table WHERE DATE(created_at) BETWEEN %s AND %s",
		$start_date,
		$end_date
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$avg_session_duration = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT AVG(total_time_seconds) FROM $sessions_table WHERE DATE(started_at) BETWEEN %s AND %s AND total_time_seconds > 0",
		$start_date,
		$end_date
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$bounce_rate = (float) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT (SUM(is_bounce) / COUNT(*)) * 100 FROM $sessions_table WHERE DATE(started_at) BETWEEN %s AND %s",
		$start_date,
		$end_date
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$avg_scroll_depth = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT AVG(max_scroll_depth) FROM $sessions_table WHERE DATE(started_at) BETWEEN %s AND %s",
		$start_date,
		$end_date
	)
);

// Get device breakdown.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$devices = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT device_type, COUNT(*) as count FROM $sessions_table WHERE DATE(started_at) BETWEEN %s AND %s GROUP BY device_type",
		$start_date,
		$end_date
	)
);

// Get top pages.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$top_pages = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT page_url, page_title, page_type, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_views
		FROM $pageviews_table
		WHERE DATE(created_at) BETWEEN %s AND %s
		GROUP BY page_url
		ORDER BY views DESC
		LIMIT 10",
		$start_date,
		$end_date
	)
);

// Get traffic sources.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$sources = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			CASE
				WHEN utm_source != '' THEN utm_source
				WHEN referrer LIKE '%google%' THEN 'Google'
				WHEN referrer LIKE '%facebook%' THEN 'Facebook'
				WHEN referrer LIKE '%instagram%' THEN 'Instagram'
				WHEN referrer LIKE '%twitter%' OR referrer LIKE '%x.com%' THEN 'Twitter/X'
				WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
				ELSE 'Other'
			END as source,
			COUNT(*) as sessions
		FROM $sessions_table
		WHERE DATE(started_at) BETWEEN %s AND %s
		GROUP BY source
		ORDER BY sessions DESC
		LIMIT 10",
		$start_date,
		$end_date
	)
);

// Get daily trend for chart.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$daily_trend = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT DATE(created_at) as date, COUNT(*) as pageviews, COUNT(DISTINCT session_id) as sessions
		FROM $pageviews_table
		WHERE DATE(created_at) BETWEEN %s AND %s
		GROUP BY DATE(created_at)
		ORDER BY date ASC",
		$start_date,
		$end_date
	)
);

$chart_labels = wp_json_encode( array_column( $daily_trend, 'date' ) );
$chart_pageviews = wp_json_encode( array_map( 'intval', array_column( $daily_trend, 'pageviews' ) ) );
$chart_sessions = wp_json_encode( array_map( 'intval', array_column( $daily_trend, 'sessions' ) ) );

?>

<div class="apollo-period-filter">
	<a href="<?php echo esc_url( add_query_arg( 'period', 'today' ) ); ?>"
		class="<?php echo 'today' === $period ? 'active' : ''; ?>">
		<?php esc_html_e( 'Today', 'apollo-core' ); ?>
	</a>
	<a href="<?php echo esc_url( add_query_arg( 'period', 'week' ) ); ?>"
		class="<?php echo 'week' === $period ? 'active' : ''; ?>">
		<?php esc_html_e( 'Last 7 Days', 'apollo-core' ); ?>
	</a>
	<a href="<?php echo esc_url( add_query_arg( 'period', 'month' ) ); ?>"
		class="<?php echo 'month' === $period ? 'active' : ''; ?>">
		<?php esc_html_e( 'Last 30 Days', 'apollo-core' ); ?>
	</a>
	<a href="<?php echo esc_url( add_query_arg( 'period', 'year' ) ); ?>"
		class="<?php echo 'year' === $period ? 'active' : ''; ?>">
		<?php esc_html_e( 'Last Year', 'apollo-core' ); ?>
	</a>
</div>

<div class="apollo-stat-cards">
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $total_pageviews ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Page Views', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-admin-users"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $unique_users ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Unique Visitors', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-networking"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $total_sessions ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Sessions', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
		<div class="stat-value"><?php echo esc_html( gmdate( 'i:s', $avg_session_duration ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Avg Session Duration', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-migrate"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format( $bounce_rate, 1 ) ); ?>%</div>
		<div class="stat-label"><?php esc_html_e( 'Bounce Rate', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-arrow-down-alt2"></span></div>
		<div class="stat-value"><?php echo esc_html( $avg_scroll_depth ); ?>%</div>
		<div class="stat-label"><?php esc_html_e( 'Avg Scroll Depth', 'apollo-core' ); ?></div>
	</div>
</div>

<div class="apollo-chart-container">
	<h3><?php esc_html_e( 'Traffic Trend', 'apollo-core' ); ?></h3>
	<canvas id="trafficChart" height="100"></canvas>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
	<div class="apollo-chart-container">
		<h3><?php esc_html_e( 'Top Pages', 'apollo-core' ); ?></h3>
		<table class="apollo-data-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Page', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Views', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Unique', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $top_pages as $page ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $page->page_url ); ?>" target="_blank">
							<?php echo esc_html( $page->page_title ?: $page->page_url ); ?>
						</a>
					</td>
					<td><span class="badge"><?php echo esc_html( $page->page_type ); ?></span></td>
					<td><?php echo esc_html( number_format_i18n( $page->views ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $page->unique_views ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div>
		<div class="apollo-chart-container">
			<h3><?php esc_html_e( 'Devices', 'apollo-core' ); ?></h3>
			<canvas id="deviceChart" height="150"></canvas>
		</div>
		<div class="apollo-chart-container">
			<h3><?php esc_html_e( 'Traffic Sources', 'apollo-core' ); ?></h3>
			<canvas id="sourceChart" height="150"></canvas>
		</div>
	</div>
</div>

<?php
// Enqueue Chart.js from local vendor.
wp_enqueue_script( 'apollo-vendor-chartjs' );
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Traffic Chart
	new Chart(document.getElementById('trafficChart'), {
		type: 'line',
		data: {
			labels: <?php echo $chart_labels; // phpcs:ignore ?>,
			datasets: [{
				label: '<?php esc_attr_e( 'Page Views', 'apollo-core' ); ?>',
				data: <?php echo $chart_pageviews; // phpcs:ignore ?>,
				borderColor: '#3498db',
				backgroundColor: 'rgba(52, 152, 219, 0.1)',
				fill: true,
				tension: 0.3
			}, {
				label: '<?php esc_attr_e( 'Sessions', 'apollo-core' ); ?>',
				data: <?php echo $chart_sessions; // phpcs:ignore ?>,
				borderColor: '#2ecc71',
				backgroundColor: 'rgba(46, 204, 113, 0.1)',
				fill: true,
				tension: 0.3
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'bottom'
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});

	// Device Chart
	new Chart(document.getElementById('deviceChart'), {
		type: 'doughnut',
		data: {
			labels: <?php echo wp_json_encode( array_column( $devices, 'device_type' ) ); ?>,
			datasets: [{
				data: <?php echo wp_json_encode( array_map( 'intval', array_column( $devices, 'count' ) ) ); ?>,
				backgroundColor: ['#3498db', '#e74c3c', '#2ecc71']
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'bottom'
				}
			}
		}
	});

	// Source Chart
	new Chart(document.getElementById('sourceChart'), {
		type: 'doughnut',
		data: {
			labels: <?php echo wp_json_encode( array_column( $sources, 'source' ) ); ?>,
			datasets: [{
				data: <?php echo wp_json_encode( array_map( 'intval', array_column( $sources, 'sessions' ) ) ); ?>,
				backgroundColor: ['#9b59b6', '#3498db', '#1abc9c', '#f39c12', '#e74c3c',
					'#34495e', '#95a5a6'
				]
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'bottom'
				}
			}
		}
	});
});
</script>