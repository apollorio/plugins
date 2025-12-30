<?php
/**
 * Analytics Heatmaps Tab
 *
 * Visual heatmap display of user interactions.
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$heatmap_enabled = \Apollo_Core\Analytics::is_heatmap_enabled();
$heatmap_table   = $wpdb->prefix . 'apollo_analytics_heatmap';

// Get available pages with heatmap data.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$pages_with_data = $wpdb->get_results(
	"SELECT DISTINCT page_url, page_url_hash, SUM(count) as total_clicks
	FROM $heatmap_table
	GROUP BY page_url_hash
	ORDER BY total_clicks DESC
	LIMIT 50"
);

// Get selected page.
$selected_page = isset( $_GET['page_url'] ) ? esc_url_raw( wp_unslash( $_GET['page_url'] ) ) : '';
$selected_hash = $selected_page ? md5( $selected_page ) : '';

// Get device filter.
$device_filter = isset( $_GET['device'] ) ? sanitize_text_field( wp_unslash( $_GET['device'] ) ) : 'all';

// Get heatmap data for selected page.
$heatmap_data = array();
if ( $selected_hash ) {
	$device_condition = '';
	if ( 'all' !== $device_filter ) {
		$device_condition = $wpdb->prepare( ' AND device_type = %s', $device_filter );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$heatmap_data = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT position_x_percent, position_y_percent, interaction_type, SUM(count) as clicks
			FROM $heatmap_table
			WHERE page_url_hash = %s $device_condition
			GROUP BY position_x_percent, position_y_percent, interaction_type
			ORDER BY clicks DESC
			LIMIT 500",
			$selected_hash
		)
	);
}

// Calculate max clicks for intensity scaling.
$max_clicks = 1;
foreach ( $heatmap_data as $point ) {
	if ( $point->clicks > $max_clicks ) {
		$max_clicks = $point->clicks;
	}
}

?>

<?php if ( ! $heatmap_enabled ) : ?>
<div class="notice notice-warning">
	<p>
		<strong><?php esc_html_e( 'Heatmap tracking is currently disabled.', 'apollo-core' ); ?></strong>
		<?php esc_html_e( 'Enable it in the Settings tab to start collecting heatmap data.', 'apollo-core' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-analytics&tab=settings' ) ); ?>">
			<?php esc_html_e( 'Go to Settings', 'apollo-core' ); ?>
		</a>
	</p>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 20px;">
	<div>
		<div class="apollo-chart-container">
			<h3><?php esc_html_e( 'Select Page', 'apollo-core' ); ?></h3>
			<div style="max-height: 400px; overflow-y: auto;">
				<?php foreach ( $pages_with_data as $page ) :
					$is_selected = $selected_page === $page->page_url;
				?>
				<a href="<?php echo esc_url( add_query_arg( 'page_url', rawurlencode( $page->page_url ) ) ); ?>"
					style="display: block; padding: 10px; border-bottom: 1px solid #eee; text-decoration: none; color: #333; <?php echo $is_selected ? 'background: #e3f2fd;' : ''; ?>">
					<div style="font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
						<?php echo esc_html( wp_parse_url( $page->page_url, PHP_URL_PATH ) ?: '/' ); ?>
					</div>
					<div style="font-size: 11px; color: #7f8c8d;">
						<?php echo esc_html( number_format_i18n( $page->total_clicks ) ); ?> <?php esc_html_e( 'interactions', 'apollo-core' ); ?>
					</div>
				</a>
				<?php endforeach; ?>
				<?php if ( empty( $pages_with_data ) ) : ?>
				<p style="padding: 20px; text-align: center; color: #7f8c8d;">
					<?php esc_html_e( 'No heatmap data collected yet.', 'apollo-core' ); ?>
				</p>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $selected_page ) : ?>
		<div class="apollo-chart-container" style="margin-top: 20px;">
			<h3><?php esc_html_e( 'Device Filter', 'apollo-core' ); ?></h3>
			<div class="apollo-period-filter" style="flex-direction: column;">
				<a href="<?php echo esc_url( add_query_arg( 'device', 'all' ) ); ?>"
					class="<?php echo 'all' === $device_filter ? 'active' : ''; ?>">
					<?php esc_html_e( 'All Devices', 'apollo-core' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'device', 'desktop' ) ); ?>"
					class="<?php echo 'desktop' === $device_filter ? 'active' : ''; ?>">
					<?php esc_html_e( 'Desktop', 'apollo-core' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'device', 'tablet' ) ); ?>"
					class="<?php echo 'tablet' === $device_filter ? 'active' : ''; ?>">
					<?php esc_html_e( 'Tablet', 'apollo-core' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'device', 'mobile' ) ); ?>"
					class="<?php echo 'mobile' === $device_filter ? 'active' : ''; ?>">
					<?php esc_html_e( 'Mobile', 'apollo-core' ); ?>
				</a>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<div class="apollo-chart-container">
		<h3>
			<?php esc_html_e( 'Heatmap Visualization', 'apollo-core' ); ?>
			<?php if ( $selected_page ) : ?>
			<a href="<?php echo esc_url( $selected_page ); ?>" target="_blank" style="float: right; font-size: 12px; font-weight: normal;">
				<?php esc_html_e( 'View Page', 'apollo-core' ); ?> →
			</a>
			<?php endif; ?>
		</h3>

		<?php if ( $selected_page && ! empty( $heatmap_data ) ) : ?>
		<div class="apollo-heatmap-container" style="position: relative; min-height: 600px; background: #f5f5f5;">
			<!-- Heatmap iframe container -->
			<div id="heatmap-iframe-wrapper" style="position: relative; width: 100%; height: 600px; overflow: hidden;">
				<iframe id="heatmap-iframe"
					src="<?php echo esc_url( $selected_page ); ?>"
					style="width: 100%; height: 100%; border: none; pointer-events: none;"
					sandbox="allow-same-origin">
				</iframe>

				<!-- Heatmap overlay -->
				<div id="heatmap-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none;">
					<?php foreach ( $heatmap_data as $point ) :
						$intensity = min( 1, ( $point->clicks / $max_clicks ) );
						$size = 20 + ( $intensity * 40 );
						$opacity = 0.3 + ( $intensity * 0.5 );
						$color = $point->interaction_type === 'click' ? '255, 0, 0' : '255, 165, 0';
					?>
					<div class="heatmap-point"
						style="position: absolute;
							left: <?php echo esc_attr( $point->position_x_percent ); ?>%;
							top: <?php echo esc_attr( $point->position_y_percent ); ?>%;
							width: <?php echo esc_attr( $size ); ?>px;
							height: <?php echo esc_attr( $size ); ?>px;
							background: radial-gradient(circle, rgba(<?php echo esc_attr( $color ); ?>, <?php echo esc_attr( $opacity ); ?>) 0%, rgba(<?php echo esc_attr( $color ); ?>, 0) 70%);
							border-radius: 50%;
							transform: translate(-50%, -50%);
							pointer-events: none;">
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div style="margin-top: 15px; display: flex; gap: 20px; justify-content: center;">
				<div style="display: flex; align-items: center; gap: 5px;">
					<div style="width: 20px; height: 20px; background: radial-gradient(circle, rgba(255,0,0,0.8) 0%, rgba(255,0,0,0) 70%); border-radius: 50%;"></div>
					<span style="font-size: 12px;"><?php esc_html_e( 'Clicks', 'apollo-core' ); ?></span>
				</div>
				<div style="display: flex; align-items: center; gap: 5px;">
					<div style="width: 20px; height: 20px; background: radial-gradient(circle, rgba(255,165,0,0.8) 0%, rgba(255,165,0,0) 70%); border-radius: 50%;"></div>
					<span style="font-size: 12px;"><?php esc_html_e( 'Hovers', 'apollo-core' ); ?></span>
				</div>
			</div>
		</div>
		<?php elseif ( $selected_page ) : ?>
		<div style="padding: 100px 20px; text-align: center; color: #7f8c8d;">
			<span class="dashicons dashicons-chart-pie" style="font-size: 48px; margin-bottom: 20px;"></span>
			<p><?php esc_html_e( 'No heatmap data for this page yet.', 'apollo-core' ); ?></p>
		</div>
		<?php else : ?>
		<div style="padding: 100px 20px; text-align: center; color: #7f8c8d;">
			<span class="dashicons dashicons-location-alt" style="font-size: 48px; margin-bottom: 20px;"></span>
			<p><?php esc_html_e( 'Select a page from the list to view its heatmap.', 'apollo-core' ); ?></p>
		</div>
		<?php endif; ?>
	</div>
</div>

<style>
.apollo-heatmap-container {
	border-radius: 8px;
	overflow: hidden;
}
#heatmap-iframe-wrapper {
	border: 2px solid #ddd;
	border-radius: 8px;
}
</style>
