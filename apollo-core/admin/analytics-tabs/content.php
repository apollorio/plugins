<?php
/**
 * Analytics Content Tab
 *
 * Shows detailed content performance stats.
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get period.
$period = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : 'week';

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
	default:
		$start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$end_date   = current_time( 'Y-m-d' );
		break;
}

$content_stats_table = $wpdb->prefix . 'apollo_analytics_content_stats';
$pageviews_table     = $wpdb->prefix . 'apollo_analytics_pageviews';

// Get content type filter.
$content_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'all';

$type_condition = '';
if ( 'all' !== $content_type ) {
	$type_condition = $wpdb->prepare( ' AND post_type = %s', $content_type );
}

// Get top performing content.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$top_content = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			cs.post_id,
			p.post_title,
			cs.post_type,
			u.display_name as author,
			SUM(cs.views) as total_views,
			SUM(cs.unique_views) as unique_views,
			AVG(cs.avg_time_seconds) as avg_time,
			AVG(cs.avg_scroll_depth) as avg_scroll,
			AVG(cs.bounce_rate) as bounce_rate,
			SUM(cs.shares) as shares,
			SUM(cs.likes) as likes
		FROM $content_stats_table cs
		JOIN {$wpdb->posts} p ON cs.post_id = p.ID
		JOIN {$wpdb->users} u ON cs.author_id = u.ID
		WHERE cs.stat_date BETWEEN %s AND %s $type_condition
		GROUP BY cs.post_id
		ORDER BY total_views DESC
		LIMIT 50",
		$start_date,
		$end_date
	)
);

// Get content types breakdown.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$content_types = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT post_type, SUM(views) as views
		FROM $content_stats_table
		WHERE stat_date BETWEEN %s AND %s
		GROUP BY post_type
		ORDER BY views DESC",
		$start_date,
		$end_date
	)
);

// Get engagement metrics.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$engagement = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT
			AVG(avg_time_seconds) as overall_avg_time,
			AVG(avg_scroll_depth) as overall_avg_scroll,
			AVG(bounce_rate) as overall_bounce_rate
		FROM $content_stats_table
		WHERE stat_date BETWEEN %s AND %s",
		$start_date,
		$end_date
	)
);

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
		<div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
		<div class="stat-value"><?php echo esc_html( gmdate( 'i:s', intval( $engagement->overall_avg_time ?? 0 ) ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Avg Time on Content', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-arrow-down-alt2"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format( $engagement->overall_avg_scroll ?? 0, 0 ) ); ?>%</div>
		<div class="stat-label"><?php esc_html_e( 'Avg Scroll Depth', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-migrate"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format( $engagement->overall_bounce_rate ?? 0, 1 ) ); ?>%</div>
		<div class="stat-label"><?php esc_html_e( 'Avg Bounce Rate', 'apollo-core' ); ?></div>
	</div>
</div>

<div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
	<div class="apollo-chart-container">
		<h3>
			<?php esc_html_e( 'Top Performing Content', 'apollo-core' ); ?>
			<select id="type-filter" style="float: right; padding: 5px;">
				<option value="all" <?php selected( $content_type, 'all' ); ?>><?php esc_html_e( 'All Types', 'apollo-core' ); ?></option>
				<option value="event_listing" <?php selected( $content_type, 'event_listing' ); ?>><?php esc_html_e( 'Events', 'apollo-core' ); ?></option>
				<option value="post" <?php selected( $content_type, 'post' ); ?>><?php esc_html_e( 'Posts', 'apollo-core' ); ?></option>
				<option value="page" <?php selected( $content_type, 'page' ); ?>><?php esc_html_e( 'Pages', 'apollo-core' ); ?></option>
			</select>
		</h3>
		<table class="apollo-data-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Content', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Author', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Views', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Unique', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Avg Time', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Scroll', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Engagement', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $top_content as $content ) :
					$engagement_score = ( ( 100 - ( $content->bounce_rate ?? 100 ) ) + ( $content->avg_scroll ?? 0 ) ) / 2;
					?>
				<tr>
					<td>
						<a href="<?php echo esc_url( get_permalink( $content->post_id ) ); ?>" target="_blank">
							<?php echo esc_html( $content->post_title ); ?>
						</a>
					</td>
					<td><span class="badge badge-<?php echo esc_attr( $content->post_type ); ?>"><?php echo esc_html( $content->post_type ); ?></span></td>
					<td><?php echo esc_html( $content->author ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $content->total_views ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $content->unique_views ) ); ?></td>
					<td><?php echo esc_html( gmdate( 'i:s', intval( $content->avg_time ) ) ); ?></td>
					<td><?php echo esc_html( number_format( $content->avg_scroll, 0 ) ); ?>%</td>
					<td>
						<div style="display: flex; align-items: center; gap: 5px;">
							<div style="width: 60px; height: 8px; background: #eee; border-radius: 4px; overflow: hidden;">
								<div style="width: <?php echo esc_attr( $engagement_score ); ?>%; height: 100%; background: <?php echo $engagement_score > 60 ? '#27ae60' : ( $engagement_score > 30 ? '#f39c12' : '#e74c3c' ); ?>;"></div>
							</div>
							<span style="font-size: 11px;"><?php echo esc_html( number_format( $engagement_score, 0 ) ); ?>%</span>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php if ( empty( $top_content ) ) : ?>
				<tr>
					<td colspan="8" style="text-align: center; color: #7f8c8d;">
						<?php esc_html_e( 'No content data for this period', 'apollo-core' ); ?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div>
		<div class="apollo-chart-container">
			<h3><?php esc_html_e( 'Views by Type', 'apollo-core' ); ?></h3>
			<canvas id="contentTypeChart" height="200"></canvas>
		</div>
	</div>
</div>

<?php wp_enqueue_script( 'apollo-vendor-chartjs' ); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Content Type Chart
	new Chart(document.getElementById('contentTypeChart'), {
		type: 'doughnut',
		data: {
			labels: <?php echo wp_json_encode( array_column( $content_types, 'post_type' ) ); ?>,
			datasets: [{
				data: <?php echo wp_json_encode( array_map( 'intval', array_column( $content_types, 'views' ) ) ); ?>,
				backgroundColor: ['#3498db', '#e74c3c', '#2ecc71', '#9b59b6', '#f39c12']
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

	// Type filter
	document.getElementById('type-filter').addEventListener('change', function() {
		const url = new URL(window.location);
		url.searchParams.set('type', this.value);
		window.location = url;
	});
});
</script>
