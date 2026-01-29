<?php
/**
 * Analytics Users Tab
 *
 * Shows user analytics with visibility controls.
 * Admin can see all; users see based on settings.
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

$user_stats_table = $wpdb->prefix . 'apollo_analytics_user_stats';

// Get top users by content views.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$top_users = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			us.user_id,
			u.display_name,
			u.user_email,
			SUM(us.profile_views) as profile_views,
			SUM(us.event_views) as event_views,
			SUM(us.post_views) as post_views,
			SUM(us.unique_visitors) as unique_visitors,
			SUM(us.social_shares) as shares,
			SUM(us.likes_received) as likes,
			SUM(us.followers_gained) as followers
		FROM $user_stats_table us
		JOIN {$wpdb->users} u ON us.user_id = u.ID
		WHERE us.stat_date BETWEEN %s AND %s
		GROUP BY us.user_id
		ORDER BY (us.profile_views + us.event_views + us.post_views) DESC
		LIMIT 50",
		$start_date,
		$end_date
	)
);

// Get user engagement totals.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$totals = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT
			SUM(profile_views) as total_profile_views,
			SUM(event_views) as total_event_views,
			SUM(post_views) as total_post_views,
			SUM(unique_visitors) as total_unique_visitors,
			SUM(social_shares) as total_shares,
			SUM(likes_received) as total_likes,
			SUM(followers_gained) as total_followers
		FROM $user_stats_table
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
		<div class="stat-icon"><span class="dashicons dashicons-admin-users"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $totals->total_profile_views ?? 0 ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Total Profile Views', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $totals->total_event_views ?? 0 ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Event Page Views', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-admin-post"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $totals->total_post_views ?? 0 ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Post Views', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-share"></span></div>
		<div class="stat-value"><?php echo esc_html( number_format_i18n( $totals->total_shares ?? 0 ) ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Social Shares', 'apollo-core' ); ?></div>
	</div>
</div>

<div class="apollo-chart-container">
	<h3><?php esc_html_e( 'Top Users by Engagement', 'apollo-core' ); ?></h3>
	<table class="apollo-data-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'User', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Profile Views', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Event Views', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Post Views', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Unique Visitors', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Shares', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Likes', 'apollo-core' ); ?></th>
				<th><?php esc_html_e( 'Visibility', 'apollo-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $top_users as $user ) :
				$visibility  = \Apollo_Core\Analytics::get_user_stats_visibility( $user->user_id );
				$total_views = ( $user->profile_views ?? 0 ) + ( $user->event_views ?? 0 ) + ( $user->post_views ?? 0 );
				?>
			<tr>
				<td>
					<div style="display: flex; align-items: center; gap: 10px;">
						<?php echo get_avatar( $user->user_id, 32 ); ?>
						<div>
							<strong><?php echo esc_html( $user->display_name ); ?></strong>
							<br>
							<small style="color: #7f8c8d;"><?php echo esc_html( $user->user_email ); ?></small>
						</div>
					</div>
				</td>
				<td><?php echo esc_html( number_format_i18n( $user->profile_views ?? 0 ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $user->event_views ?? 0 ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $user->post_views ?? 0 ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $user->unique_visitors ?? 0 ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $user->shares ?? 0 ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $user->likes ?? 0 ) ); ?></td>
				<td>
					<span class="badge badge-<?php echo esc_attr( $visibility['show_to'] ); ?>">
						<?php
						switch ( $visibility['show_to'] ) {
							case 'public':
								esc_html_e( 'Public', 'apollo-core' );
								break;
							case 'followers':
								esc_html_e( 'Followers', 'apollo-core' );
								break;
							default:
								esc_html_e( 'Private', 'apollo-core' );
						}
						?>
					</span>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php if ( empty( $top_users ) ) : ?>
			<tr>
				<td colspan="8" style="text-align: center; color: #7f8c8d;">
					<?php esc_html_e( 'No user data for this period', 'apollo-core' ); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<style>
.badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
}
.badge-public {
	background: #d4edda;
	color: #155724;
}
.badge-followers {
	background: #fff3cd;
	color: #856404;
}
.badge-self {
	background: #f8d7da;
	color: #721c24;
}
.badge-event_listing {
	background: #e3f2fd;
	color: #1565c0;
}
.badge-post {
	background: #fce4ec;
	color: #c2185b;
}
.badge-page {
	background: #e8f5e9;
	color: #2e7d32;
}
</style>
