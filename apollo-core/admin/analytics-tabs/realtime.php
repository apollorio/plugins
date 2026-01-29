<?php
/**
 * Analytics Realtime Tab
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$realtime = \Apollo_Core\Analytics::get_realtime_stats();
?>

<div class="apollo-stat-cards">
	<div class="apollo-stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff;">
		<div class="stat-icon"><span class="dashicons dashicons-admin-users" style="color: #fff;"></span></div>
		<div class="stat-value" style="color: #fff;" id="realtime-users"><?php echo esc_html( $realtime['active_users'] ); ?></div>
		<div class="stat-label" style="color: rgba(255,255,255,0.8);"><?php esc_html_e( 'Active Users Right Now', 'apollo-core' ); ?></div>
	</div>
	<div class="apollo-stat-card">
		<div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div>
		<div class="stat-value" id="realtime-pageviews"><?php echo esc_html( $realtime['pageviews_30min'] ); ?></div>
		<div class="stat-label"><?php esc_html_e( 'Page Views (Last 30 min)', 'apollo-core' ); ?></div>
	</div>
</div>

<div class="apollo-chart-container">
	<h3>
		<?php esc_html_e( 'Active Pages', 'apollo-core' ); ?>
		<span style="float: right; font-size: 12px; color: #7f8c8d;">
			<span class="dashicons dashicons-update" id="refresh-spinner" style="animation: spin 1s linear infinite;"></span>
			<?php esc_html_e( 'Auto-refreshing every 10s', 'apollo-core' ); ?>
		</span>
	</h3>
	<table class="apollo-data-table" id="realtime-pages">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Page', 'apollo-core' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Active Views', 'apollo-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $realtime['top_pages'] as $page ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $page->page_url ); ?>" target="_blank">
							<?php echo esc_html( $page->page_title ?: $page->page_url ); ?>
						</a>
					</td>
					<td>
						<span class="realtime-bar" style="display: inline-block; background: #3498db; height: 20px; width: <?php echo esc_attr( min( 100, ( $page->views / max( 1, $realtime['pageviews_30min'] ) ) * 100 ) ); ?>%; border-radius: 3px;"></span>
						<?php echo esc_html( $page->views ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( empty( $realtime['top_pages'] ) ) : ?>
				<tr>
					<td colspan="2" style="text-align: center; color: #7f8c8d;">
						<?php esc_html_e( 'No active visitors right now', 'apollo-core' ); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<style>
@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
</style>

<script>
// Auto-refresh realtime data every 10 seconds
setInterval(function() {
	fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=apollo_get_realtime_stats&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'apollo_realtime' ) ); ?>')
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				document.getElementById('realtime-users').textContent = data.data.active_users;
				document.getElementById('realtime-pageviews').textContent = data.data.pageviews_30min;

				// Update table
				const tbody = document.querySelector('#realtime-pages tbody');
				if (data.data.top_pages && data.data.top_pages.length > 0) {
					tbody.innerHTML = data.data.top_pages.map(page => `
						<tr>
							<td><a href="${page.page_url}" target="_blank">${page.page_title || page.page_url}</a></td>
							<td>
								<span class="realtime-bar" style="display: inline-block; background: #3498db; height: 20px; width: ${Math.min(100, (page.views / Math.max(1, data.data.pageviews_30min)) * 100)}%; border-radius: 3px;"></span>
								${page.views}
							</td>
						</tr>
					`).join('');
				}
			}
		});
}, 10000);
</script>
